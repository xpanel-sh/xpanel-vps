<?php

namespace App\Support;

use App\Models\SystemSetting;

class PublicPageRegistry
{
    public const PAGES = [
        'home' => 'Inicio',
        'about' => 'Empresa',
        'privacy' => 'Privacidad',
        'terms' => 'Terminos del servicio',
        'refunds' => 'Reembolsos',
    ];

    public static function defaults(string $slug): array
    {
        return match ($slug) {
            'home' => [
                'company_name' => 'XPanel Hosting',
                'company_tagline' => 'Hosting rapido, claro y bajo tu control',
                'hero_title' => 'Tu web merece una infraestructura que simplemente funcione.',
                'hero_description' => 'Publica sitios, administra dominios, correo y bases de datos desde un panel construido para crecer contigo.',
                'company_description' => 'Alojamiento administrado con tecnologia moderna, soporte cercano y recursos transparentes.',
                'support_email' => 'soporte@example.com',
                'sales_email' => 'ventas@example.com',
                'company_phone' => '',
                'company_address' => '',
                'currency_symbol' => '$',
                'cta_title' => 'Empieza a publicar hoy',
                'cta_description' => 'Elige un plan y lleva tu próximo proyecto a producción con una plataforma preparada para crecer.',
            ],
            'about' => ['title' => 'Nuestra empresa', 'body' => "Construimos una experiencia de hosting simple, segura y transparente.\n\nNuestro equipo combina automatizacion, infraestructura Linux y soporte humano para que puedas concentrarte en tu proyecto."],
            'privacy' => ['title' => 'Politica de privacidad', 'body' => 'Explica aqui que datos recopila tu empresa, para que los utiliza, durante cuanto tiempo los conserva y como pueden los clientes ejercer sus derechos.'],
            'terms' => ['title' => 'Terminos del servicio', 'body' => 'Define aqui las condiciones de uso, responsabilidades, disponibilidad, contenido permitido, pagos, renovaciones y causas de suspension del servicio.'],
            'refunds' => ['title' => 'Politica de reembolsos', 'body' => 'Indica aqui los plazos, requisitos y excepciones aplicables a cancelaciones y solicitudes de reembolso.'],
            default => [],
        };
    }

    public static function content(string $slug): array
    {
        $defaults = self::defaults($slug);

        return collect($defaults)->mapWithKeys(fn ($default, $field) => [
            $field => SystemSetting::get("page_{$slug}_{$field}", $default),
        ])->all();
    }
}
