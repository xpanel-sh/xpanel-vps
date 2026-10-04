<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HostingAccount;
use App\Models\HostingPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\HostInstanceProvisioner;
use App\Services\TenantLifecycleManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    public function index()
    {
        $tenants = Tenant::with(['sites', 'plan', 'user'])->latest()->paginate(10);

        return view('admin.clients.index', compact('tenants'));
    }

    public function create()
    {
        $plans = HostingPlan::query()->where('is_active', true)->orderBy('monthly_price')->get();

        return view('admin.clients.create', compact('plans'));
    }

    public function store(Request $request, HostInstanceProvisioner $provisioner)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'domain' => ['required', 'string', 'max:255', 'unique:tenants,domain', 'regex:/^([a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/'],
            'owner_name' => 'required|string|max:255',
            'owner_email' => 'required|email|unique:users,email',
            'owner_password' => ['required', 'string', 'min:16', 'max:128'],
            'plan_id' => ['required', 'integer', 'exists:hosting_plans,id'],
        ]);

        try {
            [$tenant, $account] = DB::transaction(function () use ($validated): array {
                $user = User::create([
                    'name' => $validated['owner_name'],
                    'email' => strtolower($validated['owner_email']),
                    'password' => Hash::make($validated['owner_password']),
                    'role' => 'client',
                ]);
                $tenant = Tenant::create([
                    'name' => $validated['company_name'],
                    'domain' => strtolower($validated['domain']),
                    'user_id' => $user->id,
                    'plan_id' => $validated['plan_id'],
                    'status' => 'active',
                ]);
                $account = HostingAccount::create([
                    'uuid' => (string) Str::uuid(),
                    'tenant_id' => $tenant->id,
                    'hosting_plan_id' => $validated['plan_id'],
                    'name' => 'Hosting principal',
                    'status' => 'active',
                ]);

                return [$tenant, $account];
            });
        } catch (\Throwable $e) {
            Log::warning('Tenant creation failed', ['domain' => $request->input('domain'), 'exception' => $e]);

            return back()->withErrors(['error' => 'Error al crear el cliente. Revisa los datos e intenta nuevamente.'])->withInput();
        }

        try {
            $instance = $provisioner->create($account, null, $validated['owner_password']);
        } catch (\Throwable $e) {
            Log::error('Initial hosting provisioning failed', [
                'tenant_id' => $tenant->id,
                'hosting_account_id' => $account->id,
                'exception' => $e,
            ]);

            return redirect()->route('admin.clients.show', $tenant)->withErrors([
                'hosting' => 'El cliente fue creado, pero el hosting no terminó de aprovisionarse. Puedes reintentarlo desde esta pantalla.',
            ]);
        }

        return redirect()->route('admin.clients.show', $tenant)->with('success',
            $instance->status === 'active'
                ? 'Cliente, acceso principal y hosting creados con las mismas credenciales.'
                : 'Cliente y hosting preparados. La instancia quedó pendiente de aplicación.'
        );
    }

    public function show(Tenant $tenant)
    {
        $tenant->load([
            'user', 'plan', 'hostingAccounts.plan',
            'hostingAccounts.hostInstance.brokerOperations' => fn ($query) => $query->limit(10),
            'sites' => fn ($query) => $query->latest(),
        ]);

        return view('admin.clients.show', compact('tenant'));
    }

    public function edit(Tenant $tenant)
    {
        $tenant->load(['user', 'plan']);
        $plans = HostingPlan::query()->where('is_active', true)->orderBy('monthly_price')->get();

        return view('admin.clients.edit', compact('tenant', 'plans'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:255', 'unique:tenants,domain,'.$tenant->id, 'regex:/^([a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/'],
            'status' => ['required', 'in:active,suspended'],
            'plan_id' => ['nullable', 'integer', 'exists:hosting_plans,id'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'unique:users,email,'.$tenant->user_id],
            'owner_password' => ['nullable', 'string', 'min:8'],
        ]);

        DB::beginTransaction();
        try {
            $oldStatus = $tenant->status;
            $tenant->update([
                'name' => $validated['company_name'],
                'domain' => strtolower($validated['domain']),
                'plan_id' => $validated['plan_id'] ?? null,
            ]);

            $userData = [
                'name' => $validated['owner_name'],
                'email' => $validated['owner_email'],
            ];

            if (! empty($validated['owner_password'])) {
                $userData['password'] = Hash::make($validated['owner_password']);
            }

            $tenant->user?->update($userData);

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
