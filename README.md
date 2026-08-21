# XPanel VPS

XPanel VPS es una plataforma de hosting multi-tenant todo en uno. Combina el portal comercial, la administración global tipo WHM y una instancia aislada de XPanel Host para cada cliente en una sola instalación.

Los sitios PHP, Node.js y estáticos se ejecutan directamente sobre Nginx, PHP-FPM y servicios systemd aislados. Docker permanece como módulo opcional para aplicaciones que realmente necesiten contenedores.

XPanel VPS no crea una máquina virtual por cliente. Las instancias comparten el kernel y los servicios base del servidor, pero separan identidades Unix, configuración, datos, procesos, dominios y operaciones privilegiadas. XPanel VM continúa siendo la opción para aislamiento fuerte mediante MicroVM.

> Estado: versión alfa para desarrollo y pruebas. Aún no se recomienda para producción con datos críticos.

## Características

- portal público, catálogo y contratación manual de planes;
- administración de clientes, planes, sitios y software;
- panel cliente para dominios, archivos, bases de datos y correo;
- Nginx, Apache opcional y PHP-FPM 8.1–8.4;
- Node.js 22 LTS nativo, procesos systemd por sitio y proxy WebSocket;
- aplicaciones SaaS tenant por ruta, subdominio wildcard o dominio personalizado;
- usuario Unix, pool PHP-FPM y document root aislados por sitio;
- gestor de archivos ikode con editor Monaco;
- bases de datos y usuarios MariaDB;
- certificados Let's Encrypt mediante Certbot;
- suspensión coordinada de clientes y sitios;
- aplicaciones Docker opcionales;
- integración con el CLI compartido `xpanel`.
- una instancia aislada de XPanel Host por cliente, administrada por XPanel VPS.

## Instancias XPanel Host

XPanel VPS funciona como plano de control y reutiliza el proyecto `xpanel-host` como panel de cada cliente. El código se instala por versiones en `/opt/xpanel-host/releases` y no se duplica por cuenta. Cada instancia conserva de forma independiente:

- usuario Linux y proceso PHP-FPM;
- `.env`, `APP_KEY`, SQLite, sesiones, caché, logs y archivos privados;
- dominio de acceso, versión de PHP, canal y versión de XPanel Host;
- estado activo o suspendido.

El estado vive en `/var/lib/xpanel-vps/instances/<uuid>`. Al crearla, la instancia queda fijada a la ruta inmutable de su release, aunque cambie el enlace global `current`. El usuario de la instancia no recibe `sudo`; las operaciones se firman con HMAC y pasan por el broker de XPanel VPS. El broker verifica la instancia, cliente, nonce, tiempo, SQLite, dominio, rutas y prefijos Unix antes de delegar al helper root.

El broker admite creación, eliminación y reinicio de sitios, runtimes Node.js, reserva global de puertos y dominios wildcard, certificados normales o wildcard y operaciones MariaDB. Los secretos DNS viajan por stdin y no se conservan en el historial. Correo permanece bloqueado hasta que VPS genere mapas agregados de Postfix/Dovecot para todas las instancias; un mapa por cliente no es seguro en un servicio global.

## Aplicaciones alojadas y tenancy

Cada instancia entrega al cliente un XPanel Host completo. Dentro de ella puede publicar:

- aplicaciones PHP y Laravel mediante PHP-FPM;
- aplicaciones Node.js 22 LTS mediante systemd, proxy Nginx y WebSockets;
- sitios estáticos;
- aplicaciones SaaS con tenants por ruta, subdominio wildcard, dominio personalizado o modo híbrido;
- contenedores Docker opcionales cuando el plan y el administrador los permitan.

El tenancy de una web alojada pertenece a esa aplicación y a XPanel Host. XPanel VPS no crea los usuarios internos del SaaS: administra la instancia, aplica el plan y protege los recursos compartidos del servidor.

## Límites de recursos por instancia

Una instancia Linux puede limitar recursos sin convertirse en MicroVM, aunque el aislamiento es menos fuerte. El diseño previsto utiliza una unidad `xpanel-instance-<uuid>.slice` de systemd/cgroups v2 por cliente.

| Recurso | Estado actual | Aplicación prevista |
| --- | --- | --- |
| Sitios, bases y correos | Aplicado por plan | Validación antes de crear recursos |
| RAM | Aplicado a panel, PHP y Node.js | `MemoryHigh`, `MemoryMax` y `MemorySwapMax` en la slice |
| CPU | Aplicado a panel, PHP y Node.js | `CPUQuota` por instancia |
| Procesos | Aplicado a panel, PHP y Node.js | `TasksMax` para evitar fork bombs |
| Disco | El plan guarda `storage_mb`, todavía no es una cuota real | Project quotas de XFS/ext4 sobre datos y webs de la instancia |
| Transferencia | El plan guarda `bandwidth_gb`, todavía no corta tráfico | Contadores Nginx por dominio, ciclo mensual y suspensión o reducción al alcanzar el límite |
| I/O de disco | Pendiente | `IOWeight` y, cuando el dispositivo lo permita, límites de lectura/escritura |
| Docker | Pendiente por plan | Límites de CPU, memoria, procesos y almacenamiento adicionales por contenedor |

Cada instancia ejecuta un master PHP-FPM independiente dentro de `xpanel-instance-<uuid>.slice`; los pools PHP de sus sitios y sus unidades Node.js se incorporan a la misma slice. Los próximos contratos del broker deben hacer lo mismo con cron, workers, terminales y contenedores antes de considerarlos cubiertos por estos límites.

MariaDB, Nginx, Postfix y otros servicios continúan siendo compartidos. Se pueden aplicar límites lógicos —conexiones, bases, buzones, tamaño y frecuencia—, pero no ofrecen la misma frontera de CPU/RAM que una MicroVM. Si un cliente necesita kernel, memoria reservada o aislamiento fuerte frente al resto, debe desplegarse con XPanel VM.

## Requisitos

- Ubuntu o Debian reciente;
- acceso `root` durante la instalación;
- dominio o IP para el panel;
- mínimo recomendado: 2 CPU, 2 GB RAM y 20 GB de disco.

## Instalación

La guía completa de servidor, DNS, acceso temporal y SSL está en [GUIA.md](GUIA.md).

```bash
git clone https://github.com/xpanel-sh/xpanel-vps.git /opt/xpanel-vps
cd /opt/xpanel-vps
chmod +x install.sh
sudo ./install.sh
```

No es necesario crear ni editar `.env`. El instalador no hace preguntas interactivas: detecta la IP pública y configura automáticamente Laravel, credenciales de MariaDB, Nginx, PHP-FPM, Apache opcional, Certbot, helpers limitados mediante sudoers, la release de XPanel Host y `xpanel-cli`. Al terminar muestra la URL y las credenciales iniciales. `sudo` o un repositorio Git privado sí pueden solicitar autenticación externa.

Configuración avanzada opcional:

```bash
XPANEL_PANEL_DOMAIN=host.example.com \
XPANEL_INSTALL_APACHE=true \
XPANEL_PHP_VERSIONS=8.2,8.3,8.4 \
XPANEL_HOST_SOURCE=/ruta/al/repositorio/xpanel-host \
sudo -E ./install.sh
```

Si `xpanel-host` está junto a `xpanel-vps`, el instalador lo detecta. En otro caso clona `xpanel-sh/xpanel-host`; `XPANEL_HOST_REPO` y `XPANEL_HOST_REVISION` permiten elegir otro remoto o revisión.

Actualización:

```bash
sudo ./scripts/xpanel-update.sh
```

## Desarrollo local

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=5000
```

En una estación de trabajo debe mantenerse:

```dotenv
XPANEL_APPLY_SYSTEM_CHANGES=false
XPANEL_DOCKER_APPS=false
```

## Estado del desarrollo

Completado: runtime web nativo PHP/Node.js/estático, hosting para aplicaciones SaaS tenant, wildcard DNS/SSL con Cloudflare, instalador, software, archivos multi-tenant, MariaDB, suspensión, SSL y aprovisionamiento aislado de XPanel Host por cliente.

La tienda habilita el plan y prepara la instancia Host al contratar, sin esperar el pago. La boleta conserva precio, duración y plazo configurado; el administrador confirma el pago como un estado financiero separado que no modifica el acceso. En desarrollo: incorporar cron, workers, terminales y Docker a la slice; cuotas de disco y medición mensual de transferencia; métodos de pago, cobro automático y renovaciones; ampliación del broker para correo agregado, cron, Git y backups; SSO desde VPS; actualizaciones/rollback por instancia y límites Docker.

## Seguridad y contribuciones

No publiques vulnerabilidades en Issues. Consulta [SECURITY.md](SECURITY.md). Para contribuir, lee [CONTRIBUTING.md](CONTRIBUTING.md) y acompaña los cambios de pruebas.

Los cambios se registran en [CHANGELOG.md](CHANGELOG.md).

## Licencia

XPanel VPS se distribuye bajo la licencia MIT. Consulta [LICENSE](LICENSE).
