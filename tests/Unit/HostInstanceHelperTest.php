<?php

namespace Tests\Unit;

use Tests\TestCase;

class HostInstanceHelperTest extends TestCase
{
    public function test_instance_creation_does_not_reload_the_control_plane_fpm(): void
    {
        $helper = file_get_contents(base_path('scripts/xpanel-instance-helper.sh'));

        $this->assertStringContainsString('systemctl restart "xpanel-instance-$UUID-fpm.service"', $helper);
        $this->assertStringContainsString('RESTART_MODE="${14}"', $helper);
        $this->assertStringContainsString('--on-active=5s', $helper);
        $this->assertStringNotContainsString('systemctl reload "php$PHP_VERSION-fpm"', $helper);
    }

    public function test_managed_host_gets_its_own_scheduler_and_disk_quota(): void
    {
        $helper = file_get_contents(base_path('scripts/xpanel-instance-helper.sh'));

        $this->assertStringContainsString('xpanel-project-quota.sh" apply "$UUID" "$SYSTEM_USER"', $helper);
        $this->assertStringContainsString('xpanel-instance-$UUID-scheduler.timer', $helper);
        $this->assertStringContainsString('schedule:run --no-interaction', $helper);
        $this->assertStringContainsString('systemctl disable --now "xpanel-instance-$UUID-scheduler.timer"', $helper);
    }

    public function test_access_staging_parent_is_writable_by_the_instance_user(): void
    {
        $helper = file_get_contents(base_path('scripts/xpanel-instance-helper.sh'));

        $this->assertStringContainsString('"$INSTANCE_ROOT/storage/app/access" "$INSTANCE_ROOT/storage/framework"', $helper);
        $this->assertStringContainsString('owner="$(stat -c %U -- "$storage_path")"', $helper);
        $this->assertStringContainsString('install -d -m 0750 -o "$SYSTEM_USER" -g "$SYSTEM_USER" "$storage_path"', $helper);
        $this->assertStringContainsString('chown "$SYSTEM_USER:$SYSTEM_USER" "$storage_path"', $helper);
        $this->assertStringContainsString('ACCESS_STAGE="$INSTANCE_ROOT/storage/app/access/$SYSTEM_USER"', $helper);
    }

    public function test_broker_repairs_only_its_instance_access_staging_directories(): void
    {
        $helper = file_get_contents(base_path('scripts/xpanel-host-broker-helper.sh'));

        $this->assertStringContainsString('if [[ "$ACTION" == "access-stage-prepare" ]]', $helper);
        $this->assertStringContainsString('"$INSTANCE_ROOT/storage/app/access/$SITE_USER"', $helper);
        $this->assertStringContainsString('[[ "$access_owner" == root || "$access_owner" == "$PANEL_USER" ]]', $helper);
    }

    public function test_terminal_installer_updates_existing_jail_profiles_for_optional_file_colors(): void
    {
        $installer = file_get_contents(base_path('scripts/configure-host-terminal.sh'));

        $this->assertStringContainsString('XPANEL_TERMINAL_COLORS', $installer);
        $this->assertStringContainsString("alias ls='ls --color=auto'", $installer);
        $this->assertStringContainsString("alias grep='grep --color=auto'", $installer);
        $this->assertStringContainsString("alias diff='diff --color=auto'", $installer);
        $this->assertStringContainsString('grep -q \'XPANEL_TERMINAL_STYLE_V2\' "$jail_profile"', $installer);
    }

    public function test_retention_restore_validates_archives_and_keeps_idempotent_lifecycle_markers(): void
    {
        $helper = file_get_contents(base_path('scripts/xpanel-instance-helper.sh'));

        $this->assertStringContainsString('python3 - "$inner_archive" "$expected_root"', $helper);
        $this->assertStringContainsString('member.isfile() or member.isdir() or member.issym()', $helper);
        $this->assertStringContainsString('retention component contains a symbolic link outside its root', $helper);
        $this->assertStringContainsString('CREATE OR REPLACE USER', $helper);
        $this->assertStringContainsString('RESTORED_ROOT="/var/lib/xpanel-vps/restored"', $helper);
        $this->assertStringContainsString('restore-complete', $helper);
        $this->assertStringContainsString('xpanel:mail-sync --stage-only', $helper);
        $this->assertStringContainsString('xpanel-mail-reconcile.sh" sync', $helper);
        $this->assertStringContainsString('xpanel-mail-reconcile.sh" outbound-sync', $helper);
    }

    public function test_managed_mail_reconciliation_aggregates_all_instance_maps(): void
    {
        $reconciler = file_get_contents(base_path('scripts/xpanel-mail-reconcile.sh'));
        $broker = file_get_contents(base_path('scripts/xpanel-host-broker-helper.sh'));

        $this->assertStringContainsString('for root in "$instances"/*', $reconciler);
        $this->assertStringContainsString('mail domain $domain belongs to more than one instance', $reconciler);
        $this->assertStringContainsString('mailbox path escaped instance $uuid', $reconciler);
        $this->assertStringContainsString('duplicate identity in aggregated $map map', $reconciler);
        $this->assertStringContainsString('XPANEL_MANAGED_MAIL_SYNC=1', $reconciler);
        $this->assertStringContainsString('mail-sync|mail-outbound-sync', $broker);
    }
}
