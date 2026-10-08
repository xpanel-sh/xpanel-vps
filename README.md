# XPanel VPS

XPanel VPS es una plataforma de hosting multi-tenant todo en uno. Combina el portal comercial, la administración global tipo WHM y una instancia aislada de XPanel Host por cada cuenta de hosting contratada. Un cliente puede poseer varias cuentas y cada cuenta puede alojar varios sitios según su plan.

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
- hogar Unix por cliente, más usuario, pool PHP-FPM y document root aislados por sitio;
- gestor de archivos ikode con editor Monaco;
- bases de datos y usuarios MariaDB;
- certificados Let's Encrypt mediante Certbot;
- suspensión coordinada de clientes y sitios;
- aplicaciones Docker opcionales;
- integración con el CLI compartido `xpanel`.
- varias cuentas de hosting por cliente, cada una con su propia instancia aislada de XPanel Host.

## Instancias XPanel Host

XPanel VPS funciona como plano de control y reutiliza el proyecto `xpanel-host` como panel de cada cuenta de hosting. El código se instala por versiones en `/opt/xpanel-host/releases` y no se duplica por cuenta. Cada instancia conserva de forma independiente:

- usuario Linux y proceso PHP-FPM;
- hogar de alojamiento `/home/<usuario-instancia>` con sitios bajo `public_html`;
- `.env`, `APP_KEY`, SQLite, sesiones, caché, logs y archivos privados;
- dominio de acceso, versión de PHP, canal y versión de XPanel Host;
- estado activo o suspendido.

El estado vive en `/var/lib/xpanel-vps/instances/<uuid>`. Al crearla, la instancia queda fijada a la ruta inmutable de su release, aunque cambie el enlace global `current`. El usuario de la instancia no recibe `sudo`; las operaciones se firman con HMAC y pasan por el broker de XPanel VPS. El broker verifica la instancia, cliente, nonce, tiempo, SQLite, dominio, rutas y prefijos Unix antes de delegar al helper root.

## Cloud, cuentas y acceso

La URL pública recomendada es `https://cloud.example.com`. Allí el cliente inicia sesión una sola vez, contrata planes, consulta boletas y ve todas sus cuentas de hosting. Cada contratación crea una entidad independiente:

```text
Cliente
├── Hosting Starter → instancia Host A → varios sitios según el plan
└── Hosting Pro     → instancia Host B → varios sitios según el plan
```

Cada instancia recibe una dirección técnica estable `h-<id>.cloud.example.com`; no es una URL temporal. **Administrar hosting** emite un token HMAC de un solo uso y corta duración, y XPanel Host abre la sesión del propietario sin pedir otra contraseña. La instancia puede estar fijada a una versión distinta de Host porque el SSO no depende de compartir sesiones ni bases de datos.

El cliente también puede registrar `panel.sudominio.com`. VPS lo añade como alias del mismo virtual host, comprueba que su DNS apunte al servidor y amplía el certificado SAN. La dirección técnica continúa disponible como respaldo. El acceso por IP y puerto se reserva para recuperación cuando el DNS todavía no funciona.

`XPANEL_CLOUD_DOMAIN` define el dominio base. En producción se recomienda crear un registro wildcard `*.cloud.example.com` hacia la IP del VPS para que las direcciones incluidas funcionen automáticamente.

Si se instala inicialmente sin dominio, el instalador utiliza una dirección técnica provisional basada en la IP mediante `sslip.io`; antes de ofrecer el servicio debe configurarse un dominio propio y su registro wildcard.

El código compartido de Host permanece en `/opt/xpanel-host/releases`; no es la carpeta del cliente. Cada broker sólo autoriza raíces web bajo `/home/<usuario-instancia>/public_html`, donde el administrador general de archivos ve la cuenta completa y cada administrador de dominio permanece confinado a su propio proyecto.

El broker admite creación, eliminación y reinicio de sitios, perfiles PHP-FPM con selecciones verificadas contra la SQLite de la instancia, runtimes Node.js, reserva global de puertos y dominios wildcard, certificados normales o wildcard y operaciones MariaDB. También permite consultar estado, emisor y vencimiento del certificado únicamente cuando el dominio pertenece a la SQLite de esa instancia; nunca entrega la clave privada. El cliente sólo puede elegir entre módulos instalados por el administrador de VPS. Los secretos DNS viajan por stdin y no se conservan en el historial. Correo permanece bloqueado hasta que VPS genere mapas agregados de Postfix/Dovecot para todas las instancias; un mapa por cliente no es seguro en un servicio global.

El gestor iKode de Host también puede solicitar corrección de propiedad de un sitio o de un árbol modificado. VPS valida la identidad Unix, el dominio y la ruta contra la SQLite de esa instancia antes de llamar al helper, y el helper comprueba la ruta física para impedir escapes por enlaces. Esto mantiene operativas las operaciones de archivos sin entregar `sudo` a Host. La terminal web usa un agente de transporte compartido en loopback; cada token se consume en la instancia que lo emitió y cada shell entra a la cárcel SSH del usuario Unix de esa cuenta o sitio. Host independiente conserva su agente e instalación propios.

## Aplicaciones alojadas y tenancy

Cada instancia entrega al cliente un XPanel Host completo. Dentro de ella puede publicar:

- aplicaciones PHP y Laravel mediante PHP-FPM;
- aplicaciones Node.js 22 LTS mediante systemd, proxy Nginx y WebSockets;
- sitios estáticos;
- aplicaciones SaaS con tenants por ruta, subdominio wildcard, dominio personalizado o modo híbrido;
- contenedores Docker opcionales cuando el plan y el administrador los permitan.

El tenancy de una web alojada pertenece a esa aplicación y a XPanel Host. XPanel VPS no crea los usuarios internos del SaaS: administra la instancia, aplica el plan y protege los recursos compartidos del servidor.

## Límites de recursos por instancia

Una instancia Linux puede limitar recursos sin convertirse en MicroVM, aunque el aislamiento es menos fuerte. El diseño utiliza una unidad `xpanel-instance-<uuid>.slice` de systemd/cgroups v2 por cuenta de hosting.

| Recurso | Estado actual | Aplicación prevista |
| --- | --- | --- |
| Sitios, bases y correos | Host administrado comprueba los tres cupos antes de crear recursos (incluye subdominios, WordPress y migraciones con SQL); Host independiente no hereda un plan VPS | Validación de aplicación; no sustituye cuotas de servicios externos |
| RAM | Aplicado a panel, PHP y Node.js | `MemoryHigh`, `MemoryMax` y `MemorySwapMax` en la slice |
| CPU | Aplicado a panel, PHP y Node.js | `CPUQuota` por instancia |
| Procesos | Aplicado a panel, PHP y Node.js | `TasksMax` para evitar fork bombs |
| Disco e inodos | Cuotas ext4 por proyecto sobre archivos de cuenta, datos del panel y bases MariaDB (en el mismo volumen y con `innodb_file_per_table`); creación de cuentas bloqueada si falta soporte | Comprobar en Linux real las rutas auxiliares y archivos temporales antes de anunciar un cupo total garantizado |
| Transferencia | Host suma mensualmente el cuerpo HTTP registrado por Nginx y avisa al 80 % y al 100 % | Es un umbral de aviso: nunca suspende ni reduce tráfico; no equivale a facturación exacta de red |
| I/O de disco | Pendiente | `IOWeight` y, cuando el dispositivo lo permita, límites de lectura/escritura |
| Docker | Pendiente por plan | Límites de CPU, memoria, procesos y almacenamiento adicionales por contenedor |

Cada instancia ejecuta un master PHP-FPM independiente dentro de `xpanel-instance-<uuid>.slice`; los pools PHP de sus sitios y sus unidades Node.js se incorporan a la misma slice. Si el administrador habilita Apache, Host inicia además un servicio Apache exclusivo de esa instancia en `127.0.0.1:<puerto de recuperación + 40000>` dentro de la misma slice. El paquete se instala una vez, pero sus procesos, configuración y virtual hosts son independientes por hosting. Cada instancia administrada recibe además su propio timer de Laravel dentro de la slice. Los trabajos cron específicos de sitios, otros workers, terminales y contenedores todavía necesitan incorporarse a esa frontera antes de considerarlos cubiertos por todos esos límites.

VPS entrega a Host el contrato del plan —CPU, RAM, disco, inodos, transferencia, sitios, bases y buzones— mediante el entorno generado de la instancia. Host consulta en vivo la slice que le pertenece y refresca el dashboard sin recargar; no necesita XPanel Pod ni Docker para medir o funcionar. Un Host standalone conserva el mismo dashboard usando métricas locales de Linux, y XPanel Pod puede instalarse en el mismo servidor como producto separado.

Las instalaciones nuevas preparan cuatro planes inactivos (Esencial, Plus, Pro y Max) dimensionados inicialmente para un VPS de 4 vCPU, 8 GB de RAM y 150 GB SSD, con avisos de tráfico de 25/75/150/300 GB al mes. Reejecutar el seeder no cambia precios ni cupos de planes ya existentes; los planes heredados Starter/Growth quedan fuera de venta sin alterar los recursos asignados a sus clientes. Aun con precio, la activación de los cuatro planes nuevos permanece bloqueada hasta comprobar todos los caminos de almacenamiento en Linux real. `bandwidth_gb = 0` significa que no se ha definido un umbral de aviso, no una promesa de tráfico ilimitado.

### Preparar cuotas ext4 para cuentas administradas

El instalador instala `quota` y `e2fsprogs`, comprueba las funciones `project` y `quota`, verifica `prjquota` y activa la contabilidad si el sistema de archivos ya está preparado. Cada nueva cuenta recibe un identificador de proyecto estable, límite duro de espacio y de inodos sobre sus archivos web y datos del panel; al cambiar el plan se actualizan los límites. Si el disco no está preparado, el panel se instala pero **no crea nuevas instancias administradas**; una cuota mostrada en la interfaz nunca sustituye a una cuota del sistema de archivos.

En un VPS como el actual, donde `/dev/sda1` alberga `/`, `/home` y `/var/lib/xpanel-vps`, **no ejecutes `tune2fs` ni `e2fsck` desde Ubuntu en funcionamiento**. Primero prepara y comprueba un respaldo recuperable, inicia el servidor en modo rescate de Contabo, confirma el dispositivo real con `lsblk -f` y que está desmontado. Solo en ese entorno se comprueba el volumen con `e2fsck -f`, se habilitan `project,quota` y `prjquota` con `tune2fs`, y se vuelve a comprobar con `e2fsck -f`. Añade `prjquota` a las opciones de la entrada de raíz en `/etc/fstab` del sistema instalado, reinicia normalmente y verifica `findmnt -no FSTYPE,OPTIONS -T /home`. No cambies particiones ni formatees. Si alguna comprobación falla, detén la instalación de cuentas y restaura el respaldo antes de continuar.

La cuota etiqueta los archivos del usuario, los datos del panel y cada directorio MariaDB al crear o restaurar una base. También sitúa los cachés npm/WP-CLI y los registros cron de Host administrado dentro de la cuenta. No cubre por completo tablas InnoDB compartidas, temporales del sistema ni todos los registros auxiliares; por eso sigue siendo necesario verificar el conjunto en Linux antes de venderlo como cupo total garantizado. El tráfico genera avisos visibles en Host; los sitios permanecen en línea.

MariaDB, el Nginx frontal, Postfix y otros servicios continúan siendo compartidos. OpenLiteSpeed no se ofrece a las instancias administradas mientras no tenga un backend aislado; Host independiente conserva su instalación local. Se pueden aplicar límites lógicos —conexiones, bases, buzones, tamaño y frecuencia—, pero no ofrecen la misma frontera de CPU/RAM que una MicroVM. Si un cliente necesita kernel, memoria reservada o aislamiento fuerte frente al resto, debe desplegarse con XPanel VM.

## Requisitos

- Ubuntu o Debian reciente;
- acceso `root` durante la instalación;
- dominio o IP para el panel;
- mínimo recomendado: 2 CPU, 2 GB RAM y 20 GB de disco.

## Instalación

La guía completa de servidor, DNS, acceso temporal y SSL está en [GUIA.md](GUIA.md).

```bash
curl -fsSL https://get.xpanel.sh | sudo bash -s -- vps stable es
```

No es necesario crear ni editar `.env`. El instalador no hace preguntas interactivas: detecta la IP pública y configura automáticamente Laravel, credenciales de MariaDB, Nginx, PHP-FPM, Apache opcional, Certbot, firewall, helpers limitados mediante sudoers, la release de XPanel Host y `xpanel-cli`. Al terminar muestra `https://IP:8443` y las credenciales generadas. El dominio se configura después desde Administración y el acceso IP permanece para recuperación.

Configuración avanzada opcional:

```bash
XPANEL_PANEL_DOMAIN=cloud.example.com \
XPANEL_CLOUD_DOMAIN=cloud.example.com \
XPANEL_INSTALL_APACHE=true \
XPANEL_PHP_VERSIONS=8.2,8.3,8.4 \
XPANEL_HOST_SOURCE=/ruta/al/repositorio/xpanel-host \
sudo -E ./install.sh
```

Si `xpanel-host` está junto a `xpanel-vps`, el instalador lo detecta. En otro caso clona `xpanel-sh/xpanel-host`; `XPANEL_HOST_REPO` y `XPANEL_HOST_REVISION` permiten elegir otro remoto o revisión.

Actualización:

```bash
xpanel update --force
```

La CLI actualiza VPS y ejecuta su verificador de sistema. Después de aplicar componentes globales, vuelve a validar y reconciliar cada instancia Host activa con su release fijada, límites de CPU/RAM/disco, migraciones y servicios. Esto también etiqueta los directorios MariaDB que existían antes de introducir las cuotas. Si alguna instancia requiere una intervención manual, la actualización informa su UUID y el motivo sin eliminarla ni actualizar a la fuerza su release de Host. `--force` solo respalda y restaura cambios Git locales; **no** fuerza cuotas ext4 sobre un disco montado. Cuando faltan las funciones ext4, prepara el volumen en modo rescate y repite `xpanel update --force`.

Al añadir un requisito del sistema en versiones futuras, incorpora su configuración idempotente al instalador y su comprobación a la reconciliación: así el mismo comando cubre instalaciones nuevas y existentes. Puedes repetir solo el diagnóstico con `php artisan xpanel:system-reconcile`; `--repair` reaplica la configuración de las instancias activas.

La terminal web de una instancia requiere que VPS instale su agente y que esa cuenta use una release de Host compatible. La reconciliación de VPS **no** cambia la versión de Host fijada por cliente: para eso pulsa **Actualizar Host** desde Administración o desde los ajustes de esa instancia. En Host → iKode, la terminal general cubre solo esa cuenta. Para la terminal de un sitio, activa **Avanzado → Acceso SSH → Permitir terminal real desde el navegador**. El transporte escucha únicamente en `127.0.0.1:7093` y no se debe publicar ese puerto.

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

Completado: runtime web nativo PHP/Node.js/estático, hosting para aplicaciones SaaS tenant, wildcard DNS/SSL con Cloudflare, instalador, software, archivos multi-tenant, MariaDB, suspensión, SSL, varias cuentas de hosting por cliente, dominio técnico estable, dominio personalizado y SSO de Cloud hacia Host.

La tienda crea una nueva cuenta de hosting y prepara su instancia Host al contratar, sin esperar el pago. La boleta conserva precio, duración y plazo configurado; el administrador confirma el pago como un estado financiero separado que no modifica el acceso. En desarrollo: incorporar cron, workers, terminales y Docker a la slice; cerrar las rutas de almacenamiento fuera de cuota y validar el montaje ext4 en un servidor Linux real; métodos de pago, cobro automático y renovaciones; ampliación del broker para correo agregado, cron, Git y backups; actualizaciones/rollback por instancia y límites Docker.

## Seguridad y contribuciones

No publiques vulnerabilidades en Issues. Consulta [SECURITY.md](SECURITY.md). Para contribuir, lee [CONTRIBUTING.md](CONTRIBUTING.md) y acompaña los cambios de pruebas.

Los cambios se registran en [CHANGELOG.md](CHANGELOG.md).

## Licencia

XPanel VPS se distribuye bajo la licencia MIT. Consulta [LICENSE](LICENSE).
