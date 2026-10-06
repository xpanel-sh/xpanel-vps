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
}
