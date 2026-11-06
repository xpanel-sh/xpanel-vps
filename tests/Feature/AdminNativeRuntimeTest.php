<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNativeRuntimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_reads_native_runtime_without_local_daemon(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Runtime nativo')
            ->assertDontSee('127.0.0.1:7070');

        $this->actingAs($admin, 'admin')
            ->getJson(route('admin.dashboard.runtime'))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('runtime.source', 'linux-native');
    }

    public function test_operations_screen_uses_broker_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.operations'))
            ->assertOk()
            ->assertSee('Historial del broker')
            ->assertDontSee('No se pudo consultar el agente');
    }

    public function test_software_and_instance_pages_render_with_the_current_admin_layout(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'admin')->get(route('admin.software-packages.index'))
            ->assertOk()->assertSee('Software del servidor')->assertSee('Paquetes');
        $this->actingAs($admin, 'admin')->get(route('admin.instances.index'))
            ->assertOk()->assertSee('Instancias XPanel Host')->assertSee('Aún no hay hostings');
        $this->actingAs($admin, 'admin')->get(route('admin.plans.index'))
            ->assertOk()->assertSee('Planes de hosting')->assertSee('Crear plan');
        $this->actingAs($admin, 'admin')->get(route('admin.servers.index'))
            ->assertOk()->assertSee('Servidores conectados')->assertSee('Inventario de nodos');
        $this->actingAs($admin, 'admin')->get(route('admin.pages.index'))
            ->assertOk()->assertSee('Páginas públicas')->assertSee('Contenido');
    }
}
