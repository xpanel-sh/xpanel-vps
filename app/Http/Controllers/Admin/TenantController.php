<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HostingPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantLifecycleManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class TenantController extends Controller
{
    public function index()
    {
        $tenants = Tenant::with('user')
            ->withCount([
                'hostingAccounts',
                'sites',
                'brokerResources as managed_sites_count' => fn ($query) => $query
                    ->where('type', 'site-domain')
                    ->where('name', 'not like', '*.%'),
            ])
            ->latest()
            ->paginate(10);

        return view('admin.clients.index', compact('tenants'));
    }

    public function create()
    {
        return view('admin.clients.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'owner_password' => ['required', 'string', 'min:12', 'max:128', 'confirmed'],
        ]);

        try {
            $tenant = DB::transaction(function () use ($validated): Tenant {
                $user = User::create([
                    'name' => $validated['company_name'],
                    'email' => strtolower($validated['owner_email']),
                    'password' => Hash::make($validated['owner_password']),
                    'role' => 'client',
                ]);

                return Tenant::create([
                    'name' => $validated['company_name'],
                    'domain' => null,
                    'user_id' => $user->id,
                    'access_ready' => true,
                    'status' => 'active',
                ]);
            });
        } catch (\Throwable $e) {
            Log::warning('Tenant creation failed', ['company' => $request->input('company_name'), 'exception' => $e]);

            return back()->withErrors(['error' => 'Error al crear el cliente. Revisa los datos e intenta nuevamente.'])->withInput();
        }

        return redirect()->route('admin.clients.show', $tenant)
            ->with('success', 'Cliente creado con acceso inmediato al portal. Ya puedes añadir uno o más hostings.');
    }

    public function show(Tenant $tenant)
    {
        $tenant->load([
            'user', 'plan', 'hostingAccounts.plan',
            'hostingAccounts.hostInstance.brokerOperations' => fn ($query) => $query->limit(10),
            'hostingAccounts.hostInstance.brokerResources' => fn ($query) => $query
                ->where('type', 'site-domain')
                ->where('name', 'not like', '*.%'),
            'sites' => fn ($query) => $query->latest(),
        ]);

        $plans = HostingPlan::query()->where('is_active', true)->orderBy('monthly_price')->get();

        return view('admin.clients.show', compact('tenant', 'plans'));
    }

    public function edit(Tenant $tenant)
    {
        $tenant->load(['user', 'plan']);

        return view('admin.clients.edit', compact('tenant'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:active,suspended'],
            'owner_name' => [$tenant->access_ready ? 'required' : 'nullable', 'string', 'max:255'],
            'owner_email' => [$tenant->access_ready ? 'required' : 'nullable', 'email', 'unique:users,email,'.$tenant->user_id],
            'owner_password' => ['nullable', 'string', 'min:8'],
        ]);

        DB::beginTransaction();
        try {
            $oldStatus = $tenant->status;
            $tenant->update([
                'name' => $validated['company_name'],
            ]);

            if ($tenant->access_ready && $tenant->user) {
                $userData = [
                    'name' => $validated['owner_name'],
                    'email' => $validated['owner_email'],
                ];
                if (! empty($validated['owner_password'])) {
                    $userData['password'] = Hash::make($validated['owner_password']);
                }
                $tenant->user->update($userData);
            }

            DB::commit();
            if ($oldStatus !== $validated['status']) {
                app(TenantLifecycleManager::class)->setSuspended($tenant, $validated['status'] === 'suspended');
            }

            return redirect()->route('admin.clients.show', $tenant)->with('success', 'Cliente actualizado correctamente.');
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::warning('Tenant update failed', ['tenant_id' => $tenant->id, 'exception' => $e]);

            return back()->withErrors(['error' => 'Error al actualizar el cliente. Revisa los datos e intenta nuevamente.'])->withInput();
        }
    }

    public function toggleStatus(Tenant $tenant, TenantLifecycleManager $lifecycle)
    {
        $lifecycle->setSuspended($tenant, $tenant->status === 'active');

        return redirect()->route('admin.clients.show', $tenant)->with('success', 'Estado del cliente y sus servicios actualizado.');
    }
}
