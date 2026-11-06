<?php

namespace Tests\Feature;

use App\Models\AccountNotification;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AccountNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Mail;
use App\Mail\AccountAlertMail;

class AccountNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_read_only_its_own_notifications(): void
    {
        $owner = User::factory()->create(['role' => 'client']);
        $other = User::factory()->create(['role' => 'client']);
        $tenant = Tenant::create(['name' => 'Owner', 'domain' => 'owner.test', 'user_id' => $owner->id, 'status' => 'active']);
        $otherTenant = Tenant::create(['name' => 'Other', 'domain' => 'other.test', 'user_id' => $other->id, 'status' => 'active']);
        $notification = app(AccountNotifier::class)->send($tenant, 'payment.pending', 'Boleta pendiente', 'Revisa tu contratación.', actionUrl: '/client/orders');
        $foreign = app(AccountNotifier::class)->send($otherTenant, 'payment.pending', 'Privada', 'No debe aparecer.');

        $this->actingAs($owner)->get(route('client.notifications.index'))
            ->assertOk()->assertSee('Boleta pendiente')->assertDontSee('Privada');
        $this->actingAs($owner)->post(route('client.notifications.read', $foreign))->assertNotFound();
        $this->actingAs($owner)->post(route('client.notifications.read', $notification))
            ->assertRedirect('/client/orders');

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_all_affects_only_current_tenant(): void
    {
        $owner = User::factory()->create(['role' => 'client']);
        $other = User::factory()->create(['role' => 'client']);
        $tenant = Tenant::create(['name' => 'Owner', 'domain' => 'owner.test', 'user_id' => $owner->id, 'status' => 'active']);
        $otherTenant = Tenant::create(['name' => 'Other', 'domain' => 'other.test', 'user_id' => $other->id, 'status' => 'active']);
        app(AccountNotifier::class)->send($tenant, 'hosting.restored', 'Restaurado', 'Activo.');
        $foreign = app(AccountNotifier::class)->send($otherTenant, 'hosting.restored', 'Otro', 'Activo.');

        $this->actingAs($owner)->post(route('client.notifications.read-all'))->assertRedirect();

        $this->assertSame(0, AccountNotification::where('tenant_id', $tenant->id)->whereNull('read_at')->count());
        $this->assertNull($foreign->fresh()->read_at);
    }

    public function test_external_action_urls_are_rejected(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $tenant = Tenant::create(['name' => 'Owner', 'domain' => 'owner.test', 'user_id' => $user->id, 'status' => 'active']);
        $this->expectException(\InvalidArgumentException::class);

        app(AccountNotifier::class)->send($tenant, 'test', 'Test', 'Test', actionUrl: 'https://example.com');
    }

    public function test_email_channel_is_optional_and_records_delivery(): void
    {
        Mail::fake();
        SystemSetting::set('notification_email_enabled', '1');
        $user = User::factory()->create(['role' => 'client']);
        $tenant = Tenant::create(['name' => 'Owner', 'domain' => 'owner.test', 'user_id' => $user->id, 'status' => 'active']);

        $notification = app(AccountNotifier::class)->send($tenant, 'payment.confirmed', 'Pago confirmado', 'Recibimos el pago.');

        Mail::assertSent(AccountAlertMail::class, 1);
        $this->assertNotNull($notification->email_sent_at);
        $this->assertNull($notification->email_error);
    }
}
