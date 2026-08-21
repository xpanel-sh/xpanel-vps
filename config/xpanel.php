<?php

return [
    'routes' => [
        'admin_login_path' => trim(env('XPANEL_ADMIN_LOGIN_PATH', 'admin/login'), '/'),
        'client_login_path' => trim(env('XPANEL_CLIENT_LOGIN_PATH', 'client/login'), '/'),
        'admin_base_path' => trim(env('XPANEL_ADMIN_BASE_PATH', 'admin'), '/'),
    ],
    'home_enabled' => (bool) env('XPANEL_HOME_ENABLED', false),
    'server_ip' => env('XPANEL_SERVER_IP', ''),

    'native_hosting' => [
        'enabled' => filter_var(env('XPANEL_NATIVE_HOSTING', true), FILTER_VALIDATE_BOOL),
        'apply_system_changes' => filter_var(env('XPANEL_APPLY_SYSTEM_CHANGES', false), FILTER_VALIDATE_BOOL),
        'site_helper' => env('XPANEL_SITE_HELPER', base_path('scripts/xpanel-site-helper.sh')),
        'package_helper' => env('XPANEL_PACKAGE_HELPER', base_path('scripts/xpanel-package-helper.sh')),
        'web_root' => rtrim(env('XPANEL_WEB_ROOT', '/var/www/xpanel'), '/'),
        'web_servers' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('XPANEL_WEB_SERVERS', 'nginx,apache'))
        ))),
        'php_versions' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('XPANEL_PHP_VERSIONS', '8.2,8.3,8.4'))
        ))),
    ],

    'host_instances' => [
        'enabled' => filter_var(env('XPANEL_HOST_INSTANCES', true), FILTER_VALIDATE_BOOL),
        'root' => rtrim(env('XPANEL_INSTANCE_ROOT', '/var/lib/xpanel-vps/instances'), '/'),
        'release_path' => rtrim(env('XPANEL_HOST_RELEASE', '/opt/xpanel-host/current'), '/'),
        'version' => env('XPANEL_HOST_VERSION', 'dev'),
        'php_version' => env('XPANEL_HOST_PHP_VERSION', '8.3'),
        'helper' => env('XPANEL_INSTANCE_HELPER', base_path('scripts/xpanel-instance-helper.sh')),
        'staging_root' => env('XPANEL_INSTANCE_STAGING_ROOT', storage_path('app/native/host-instances')),
        'control_plane_url' => env('XPANEL_CONTROL_PLANE_URL', env('APP_URL')),
        'broker_url' => env('XPANEL_BROKER_URL', rtrim(env('APP_URL'), '/').'/api/internal/host-broker'),
        'broker_helper' => env('XPANEL_BROKER_HELPER', base_path('scripts/xpanel-host-broker-helper.sh')),
        'fallback_port_start' => (int) env('XPANEL_HOST_PORT_START', 10000),
        'fallback_port_end' => (int) env('XPANEL_HOST_PORT_END', 19999),
        'fallback_certificate' => env('XPANEL_FALLBACK_CERTIFICATE', '/etc/xpanel/tls/fallback.crt'),
        'fallback_certificate_key' => env('XPANEL_FALLBACK_CERTIFICATE_KEY', '/etc/xpanel/tls/fallback.key'),
        'default_limits' => [
            'memory_mb' => (int) env('XPANEL_INSTANCE_DEFAULT_MEMORY_MB', 512),
            'swap_mb' => (int) env('XPANEL_INSTANCE_DEFAULT_SWAP_MB', 0),
            'cpu_percent' => (int) env('XPANEL_INSTANCE_DEFAULT_CPU_PERCENT', 100),
            'tasks_max' => (int) env('XPANEL_INSTANCE_DEFAULT_TASKS_MAX', 256),
        ],
    ],

    'docker' => [
        'enabled' => filter_var(env('XPANEL_DOCKER_APPS', false), FILTER_VALIDATE_BOOL),
    ],
];
