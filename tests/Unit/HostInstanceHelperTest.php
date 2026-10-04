<?php

namespace Tests\Unit;

use Tests\TestCase;

class HostInstanceHelperTest extends TestCase
{
    public function test_instance_creation_does_not_reload_the_control_plane_fpm(): void
    {
        $helper = file_get_contents(base_path('scripts/xpanel-instance-helper.sh'));

        $this->assertStringContainsString('systemctl restart "xpanel-instance-$UUID-fpm.service"', $helper);
        $this->assertStringNotContainsString('systemctl reload "php$PHP_VERSION-fpm"', $helper);
    }
}
