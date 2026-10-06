# Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y versionado semántico.

## [Unreleased]

- Corregido el falso fallo al preparar una release: la limpieza temporal devolvía código 1 después de imprimir el hash. El preparador ahora emite etapas verificables y el estado de cada cuenta se consulta en vivo tanto desde VPS como desde Host, sin llenar el registro de auditoría con cada sondeo.

- La preparación de una nueva release de Host registra por separado descarga, Composer y compilación en `/var/log/xpanel-vps/host-release-prepare.log`; si falla, el panel muestra el error completo en vez de recortar la salida de progreso de Composer. Composer usa la versión PHP configurada para Host y limita descargas concurrentes.

- La actualización de Host se separa de la de VPS: una nueva release oficial se prepara bajo demanda y se aplica únicamente a la cuenta seleccionada, también a petición firmada desde ese Host. Actualizar VPS conserva la release de Host ya preparada y no cambia las versiones fijadas de las cuentas.

- Managed Host broker checks now inspect only allowlisted, read-only SQLite records through a privileged helper; the VPS web process no longer needs direct access to each customer's private database. This fixes site operations failing with an unavailable instance database while preserving account isolation.
- Instance apply now repairs the ownership of Laravel's `storage`, `storage/app`, and `storage/framework` parent directories. Earlier root-owned parents blocked PHP from staging vhosts or the OpenLiteSpeed registry with `mkdir(): Permission denied`; only those directory entries are changed, never site contents.
- Updating an instance reapplies its configuration even when it is already on the current Host release, so VPS-side helper repairs reach existing accounts.
- The Host broker now authorizes site access-identity removal only when the Unix user and document root match that instance's registered site, allowing managed Host deletion to complete without exposing other accounts.
- Managed Host instance provisioning now creates or repairs only its account home and `public_html` ownership before running the tenant panel. Reapplying an existing instance repairs root-owned account directories left by an earlier site creation without recursively changing customer files.
- The signed Host broker now permits site-scoped ownership repair and targeted subtree synchronization after iKode file operations; it verifies the site's SQLite identity and canonical path before invoking the privileged helper. This does not enable the managed terminal.
- Added brokered web-engine discovery, an isolated Apache service and loopback port per Host account, and a compact software administration view. OpenLiteSpeed stays unavailable to managed instances until its backend can be isolated.
- Custom panel certificates validate and include only the customer's selected domain; an invalid legacy panel domain no longer blocks activation. Changing the address resets its previous SSL state.

- Panel-domain requests now return success only after Nginx and SSL finish; managed Host FPM restarts are deferred until the initiating response is safe, preventing false confirmations, 404 responses and stale IP URLs.
- Added per-instance Host updates from the VPS administrator, backed by immutable commit-addressed releases and automatic rollback when applying an update fails.
- Added signed custom panel-domain requests from managed Host instances, kept the same option in the VPS client portal and propagated the public server IPv4 into each Host environment.
- Administrator SSO now uses the permanent IP-and-port recovery address, while customers use their verified custom panel domain.
- Reapplied managed instance configuration after successful SSL issuance so Laravel immediately adopts the verified custom panel URL.
- Fixed fresh MariaDB installations by preserving a normal tenant index before removing the former one-instance unique constraint.
- Installer repositories now ignore executable-bit changes applied to privileged helpers, keeping future `xpanel update` operations clean.
- Fixed strict-mode early exits when package, firewall or CLI steps are intentionally skipped during recovery and updates.
- Corrected Composer, environment and built-asset permissions so the unprivileged web process can load VPS and every shared Host release without making application code writable.
- Added secure administrator password rotation through `xpanel:admin-bootstrap --reset-password --password-stdin`.
- Replaced the obsolete local daemon dependency on port 7070 with native Linux runtime metrics and broker operation history.
- Prevented Host provisioning from reloading the control-plane PHP-FPM service, avoiding a successful creation ending in a 502 response.
- Separated commercial clients from hosting administrators: every Host account now defines its own administrator, while the first one also enables the client's central access.
- Allowed changing the Cloud base domain with existing hostings and automatically migrated generated instance domains, Nginx configuration and pending SSL state.
- Reworked the administrator client detail with compact native cards, clearer hosting actions and a responsive three-column provisioning form.
- Host access SSO now authenticates with the administrator assigned to the selected hosting instead of always using the client's first account.

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
