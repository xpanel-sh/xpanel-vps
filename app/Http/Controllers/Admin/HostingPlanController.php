<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HostingPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Services\HostInstanceResourceLimiter;

class HostingPlanController extends Controller
{
    public function index()
    {
        $plans = HostingPlan::query()
            ->withCount('tenants')
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

        HostingPlan::create($validated);

        return redirect()->route('admin.plans.index')->with('success', 'Plan creado correctamente.');
    }

    public function edit(HostingPlan $plan)
    {
        return view('admin.plans.edit', compact('plan'));
    }

    public function update(Request $request, HostingPlan $plan, HostInstanceResourceLimiter $limits)
    {
        $validated = $this->validatePlan($request, $plan);
        $validated['slug'] = Str::slug($validated['slug'] ?: $validated['name']);
        $validated['is_active'] = $request->boolean('is_active');

        $plan->update($validated);

        $plan->tenants()->with('hostInstance')->get()->each(function ($tenant) use ($limits): void {
            if ($tenant->hostInstance) {
                $limits->apply($tenant->hostInstance);
            }
        });

        return redirect()->route('admin.plans.index')->with('success', 'Plan actualizado correctamente.');
    }

    public function toggle(HostingPlan $plan)
    {
        $plan->update(['is_active' => ! $plan->is_active]);

        return redirect()->route('admin.plans.index')->with('success', 'Estado del plan actualizado.');
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
