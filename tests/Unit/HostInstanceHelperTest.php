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
}
