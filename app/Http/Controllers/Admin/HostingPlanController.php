<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HostingPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Services\HostInstanceProvisioner;

class HostingPlanController extends Controller
{
    public function index()
    {
        $plans = HostingPlan::query()
            ->withCount('hostingAccounts')
            ->latest()
            ->paginate(12);

        return view('admin.plans.index', compact('plans'));
    }

    public function create()
    {
        return view('admin.plans.create', ['plan' => new HostingPlan]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePlan($request);
        $validated['slug'] = Str::slug($validated['slug'] ?: $validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);
        $this->assertPaidDraftHasPrice($validated);

        HostingPlan::create($validated);

        return redirect()->route('admin.plans.index')->with('success', 'Plan creado correctamente.');
    }

    public function edit(HostingPlan $plan)
    {
        return view('admin.plans.edit', compact('plan'));
    }

    public function update(Request $request, HostingPlan $plan, HostInstanceProvisioner $provisioner)
    {
        $validated = $this->validatePlan($request, $plan);
        $validated['slug'] = Str::slug($validated['slug'] ?: $validated['name']);
        $validated['is_active'] = $request->boolean('is_active');
        $this->assertPaidDraftHasPrice($validated);

        $plan->update($validated);

        $plan->hostingAccounts()->with('hostInstance')->get()->each(function ($account) use ($provisioner): void {
            if ($account->hostInstance) {
                // Rebuild Host's environment as well as its systemd slice.
                $provisioner->apply($account->hostInstance);
            }
        });

        return redirect()->route('admin.plans.index')->with('success', 'Plan actualizado correctamente.');
    }

    public function toggle(HostingPlan $plan)
    {
        if (! $plan->is_active) {
            $this->assertPaidDraftHasPrice(array_merge($plan->toArray(), ['is_active' => true]));
        }
        $plan->update(['is_active' => ! $plan->is_active]);

        return redirect()->route('admin.plans.index')->with('success', 'Estado del plan actualizado.');
    }

    private function assertPaidDraftHasPrice(array $data): void
    {
        if (($data['is_active'] ?? false)
            && in_array($data['slug'] ?? '', ['esencial', 'plus', 'pro', 'max'], true)) {
            if ((float) ($data['monthly_price'] ?? 0) <= 0) {
                throw ValidationException::withMessages(['monthly_price' => 'Define un precio mensual mayor que cero antes de activar este plan.']);
            }
            foreach (['max_sites', 'max_databases', 'email_accounts', 'storage_mb', 'inode_limit'] as $field) {
                if ((int) ($data[$field] ?? 0) <= 0) {
                    throw ValidationException::withMessages([$field => 'Este límite debe ser mayor que cero para activar el plan.']);
                }
            }
            // Per-account files are quota-tagged, but shared MariaDB files and
            // other external data paths still need complete hard enforcement.
            throw ValidationException::withMessages([
                'storage_mb' => 'La cuota de archivos ya está preparada, pero la base MariaDB compartida y otros datos externos aún no están incluidos en el límite duro total. No actives este plan como cuota garantizada.',
            ]);
        }
    }

    private function validatePlan(Request $request, ?HostingPlan $plan = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('hosting_plans', 'slug')->ignore($plan)],
            'max_sites' => ['required', 'integer', 'min:0', 'max:100000'],
            'max_databases' => ['required', 'integer', 'min:0', 'max:100000'],
            'storage_mb' => ['required', 'integer', 'min:0', 'max:100000000'],
            'inode_limit' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'bandwidth_gb' => ['required', 'integer', 'min:0', 'max:1000000'],
            'email_accounts' => ['required', 'integer', 'min:0', 'max:100000'],
            'memory_mb' => ['required', 'integer', 'min:128', 'max:1048576'],
            'swap_mb' => ['required', 'integer', 'min:0', 'max:1048576'],
            'cpu_percent' => ['required', 'integer', 'min:10', 'max:65535'],
            'tasks_max' => ['required', 'integer', 'min:32', 'max:1000000'],
            'monthly_price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'billing_period_months' => ['required', 'integer', 'min:1', 'max:36'],
            'payment_due_days' => ['required', 'integer', 'min:1', 'max:365'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
