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
        $tenants = Tenant::with(['sites', 'plan', 'user'])->latest()->paginate(10);

        return view('admin.clients.index', compact('tenants'));
    }

    public function create()
    {
        $plans = HostingPlan::query()->where('is_active', true)->orderBy('monthly_price')->get();

        return view('admin.clients.create', compact('plans'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'domain' => ['required', 'string', 'max:255', 'unique:tenants,domain', 'regex:/^([a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/'],
            'owner_name' => 'required|string|max:255',
            'owner_email' => 'required|email|unique:users,email',
            'owner_password' => 'required|min:8',
            'plan_id' => ['nullable', 'integer', 'exists:hosting_plans,id'],
        ]);

        DB::beginTransaction();
        try {
            // 1. Crear el usuario para el cliente
            $user = User::create([
                'name' => $request->owner_name,
                'email' => $request->owner_email,
                'password' => Hash::make($request->owner_password),
                'role' => 'client',
            ]);

            // 2. Crear el Tenant
            Tenant::create([
                'name' => $request->company_name,
                'domain' => strtolower($request->domain),
                'user_id' => $user->id,
                'plan_id' => $request->plan_id,
                'status' => 'active',
            ]);

            DB::commit();

            return redirect()->route('admin.clients.index')->with('success', 'Cliente y usuario creados correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::warning('Tenant creation failed', ['domain' => $request->input('domain'), 'exception' => $e]);

            return back()->withErrors(['error' => 'Error al crear el cliente. Revisa los datos e intenta nuevamente.'])->withInput();
        }
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
