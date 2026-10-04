# Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y versionado semántico.

## [Unreleased]

- Fixed fresh MariaDB installations by preserving a normal tenant index before removing the former one-instance unique constraint.
- Installer repositories now ignore executable-bit changes applied to privileged helpers, keeping future `xpanel update` operations clean.
- Fixed strict-mode early exits when package, firewall or CLI steps are intentionally skipped during recovery and updates.
- Corrected Composer, environment and built-asset permissions so the unprivileged web process can load VPS and every shared Host release without making application code writable.
- Added secure administrator password rotation through `xpanel:admin-bootstrap --reset-password --password-stdin`.
- Replaced the obsolete local daemon dependency on port 7070 with native Linux runtime metrics and broker operation history.
- Prevented Host provisioning from reloading the control-plane PHP-FPM service, avoiding a successful creation ending in a 502 response.
- Separated commercial clients from hosting administrators: every Host account now defines its own administrator, while the first one also enables the client's central access.
- Allowed changing the Cloud base domain with existing hostings and automatically migrated generated instance domains, Nginx configuration and pending SSL state.

- Simplified fresh installations to a single non-interactive command with automatic IP detection, generated administrator credentials and permanent `IP:8443` recovery access.
- Added control-plane domain activation from Administration, including DNS validation, Nginx configuration and automatic Let's Encrypt issuance.

### Changed

- El modelo comercial separa cliente, contratación y cuenta de hosting: un cliente puede mantener varias suscripciones activas y cada una recibe su propia instancia Host, plan, versión y límites.
- Cloud utiliza una dirección técnica estable `h-<id>.<dominio-cloud>` por instancia; IP y puerto quedan únicamente como recuperación.
- El broker permite a cada instancia inspeccionar únicamente los certificados de dominios registrados en su propia SQLite, habilitando la recuperación segura del estado SSL sin acceso directo de Laravel a `/etc/letsencrypt`.
- Cada instancia Host recibe una cuenta Unix y hogar `/home/<instancia>`; sus sitios se autorizan exclusivamente bajo `public_html`, mientras los releases compartidos permanecen en `/opt/xpanel-host`.
- Las referencias al antiguo producto de MicroVMs `xpanel-core` ahora usan su identidad definitiva `xpanel-vm`; `xpanel-core` queda reservado para el futuro plano central del ecosistema.

### Added

- Acceso SSO HMAC de un solo uso desde Cloud hacia la instancia Host seleccionada, sin compartir cookies ni bases de datos entre aplicaciones.
- Dominio personalizado opcional por cuenta de hosting, con validación DNS, alias Nginx, certificado SAN y reintentos automáticos independientes del SSL técnico.
- El broker autoriza perfiles PHP por los registros reales de cada SQLite y confina sus masters PHP-FPM a la slice de la instancia; las extensiones disponibles siguen bajo control del administrador de VPS.
- Los planes incorporan límite de inodos y el entorno de cada Host recibe CPU, RAM, almacenamiento, inodos, transferencia y máximo de sitios para presentar capacidad y consumo con el alcance correcto.
- Límites por plan de RAM, swap, CPU y procesos mediante slices systemd/cgroups v2, con PHP-FPM independiente por instancia y sincronización al editar el plan.
- Contrato de runtime para que los pools PHP y servicios Node.js creados por XPanel Host pertenezcan a la slice de su instancia VPS.

- Soporte en las instancias Host para aplicaciones Node.js nativas, WebSockets y tenancy SaaS por rutas, wildcard o dominios personalizados.
- Reserva por broker de puertos y dominios wildcard, más certificados HTTP-01/DNS-01 sin registrar secretos Cloudflare.
- Aprovisionamiento de una instancia aislada de XPanel Host por cliente.
- Registro central de instancias, configuración PHP-FPM/Nginx, almacenamiento y SQLite separados.
- Instalación versionada del repositorio `xpanel-host` y suspensión desde la administración VPS.
- Portal público, dashboard, planes e información de cliente adaptados desde `Plantilla/cliente` sin CSS paralelo.
- Broker HMAC anti-replay para operaciones privilegiadas de sitios y bases de instancias Host.
- Auditoría administrativa de solicitudes autorizadas, completadas, preparadas o rechazadas.
- Activación inmediata del servicio al contratar, boletas con plazo configurable y confirmación manual de pago independiente.
- Acceso temporal cifrado por IP y puerto para instancias cuyo dominio aún no resuelve.
- Validación DNS, emisión Let’s Encrypt y reintentos SSL programados y manuales para paneles Host.
- Guía completa de instalación, DNS, firewall, acceso temporal y diagnóstico en `GUIA.md`.

### Añadido

- portal público editable y administración de contenido;
- mejoras de ikode adaptadas al aislamiento multi-tenant;
- documentación para publicación en GitHub.

## [0.1.0-alpha.1] - 2026-08-16

### Añadido

- instalador Ubuntu/Debian e integración con `xpanel-cli`;
- Nginx, Apache opcional y PHP-FPM por sitio;
- usuarios Unix y document roots aislados;
- gestión nativa de paquetes, MariaDB, suspensión y SSL;
- Docker como módulo opcional.

### Cambiado

- Docker dejó de ser el runtime principal;
- archivos y bases de datos dejaron de depender del daemon legado.

[Unreleased]: https://github.com/xpanel-sh/xpanel-vps/compare/v0.1.0-alpha.1...HEAD
[0.1.0-alpha.1]: https://github.com/xpanel-sh/xpanel-vps/releases/tag/v0.1.0-alpha.1
