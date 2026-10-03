# Guía de instalación de XPanel VPS

Esta guía instala XPanel VPS como plano de control Cloud y XPanel Host como panel aislado para cada cuenta de hosting contratada. Un mismo cliente puede tener varias cuentas. Está orientada a un servidor limpio con Ubuntu o Debian.

## 1. Requisitos

- Ubuntu 22.04/24.04 o Debian 12.
- Acceso `root` o un usuario con `sudo`.
- 2 CPU, 2 GB de RAM y 20 GB de disco como mínimo recomendado.
- Una IP pública fija.
- Un dominio para la tienda y administración es recomendable, pero no obligatorio durante la instalación.
- Acceso a Internet para descargar los repositorios y paquetes.

En el proveedor del VPS permite tráfico TCP para:

- `80` y `443`: web, validación DNS/HTTP y SSL.
- `22`: SSH.
- `10000-19999`: accesos temporales de las instancias antes de configurar sus dominios. El rango puede cambiarse con `XPANEL_HOST_PORT_START` y `XPANEL_HOST_PORT_END`.

No expongas MariaDB, PHP-FPM ni los sockets internos a Internet. Las aplicaciones Node.js tampoco exponen su puerto directamente: XPanel reserva un puerto entre `20000-49999` sólo para loopback y Nginx publica el dominio por `80/443`.

## 2. Instalación automática recomendada

No necesitas crear `.env`, una base de datos, contraseñas ni claves manualmente. Tampoco es obligatorio tener un dominio durante la primera instalación.

```bash
sudo -i
git clone https://github.com/TU_ORGANIZACION/xpanel-vps.git /opt/xpanel-vps
cd /opt/xpanel-vps
chmod +x install.sh
./install.sh
```

El instalador detecta la IP pública, crea `.env`, genera `APP_KEY`, prepara MariaDB con credenciales aleatorias, instala la release oficial de XPanel Host, configura Nginx/PHP-FPM, instala XPanel CLI y muestra el usuario y contraseña iniciales.

Al terminar podrás entrar mediante la URL IP que aparece en pantalla. La primera conexión usa un certificado temporal autofirmado, por lo que el navegador puede pedir confirmación. Guarda las credenciales mostradas.

## 3. ¿La consola pregunta algo?

En la instalación automática con repositorios públicos, XPanel no hace preguntas interactivas. Puedes iniciar el proceso y esperar a que termine.

No solicita:

- dominio;
- dirección IP;
- correo administrativo;
- contraseña administrativa;
- usuario o contraseña de MariaDB;
- versiones PHP;
- configuración de `.env`;
- confirmación para instalar paquetes.

Todos esos valores se detectan o generan automáticamente. XPanel CLI tampoco presenta preguntas durante su instalación.

Solo pueden aparecer solicitudes externas a XPanel:

- `sudo` puede pedir la contraseña del usuario Linux antes de obtener permisos de administrador;
- Git puede pedir autenticación o confirmar la huella SSH si `xpanel-vps`, `xpanel-host` o `xpanel-cli` están en repositorios privados;
- el proveedor del VPS puede requerir que abras los puertos desde su panel web.

Al finalizar se imprime un resumen similar a este:

```text
XPanel VPS instalado correctamente
Panel: https://IP_DEL_VPS
Admin: https://IP_DEL_VPS/admin/login
Correo: admin@xpanel.local
Contraseña: CONTRASEÑA_GENERADA
CLI global: xpanel
```

La contraseña administrativa solamente se muestra durante la primera instalación. Debes guardarla antes de cerrar la consola. En ejecuciones posteriores el instalador conserva `.env`, la base de datos y el administrador existentes.

## 4. Dominio opcional del panel principal

Si ya dispones de un dominio, crea un registro `A` antes de instalar:

```text
host.example.com  A  IP_PUBLICA_DEL_VPS
```

Este paso es opcional. XPanel VPS puede instalarse usando solamente la IP. Si ya tienes un dominio, puedes apuntarlo antes de instalar para obtener una URL más clara y preparar HTTPS.

## 5. Repositorios privados o configuración avanzada

Si `xpanel-host` es privado, clónalo junto a VPS:

```bash
git clone git@github.com:TU_ORGANIZACION/xpanel-host.git /opt/xpanel-host-source
```

También puedes permitir que el instalador lo clone directamente mediante `XPANEL_HOST_REPO`.

Las siguientes variables son opcionales. Úsalas únicamente si quieres una configuración distinta a la automática.

Con un repositorio Host ya clonado:

```bash
XPANEL_PANEL_DOMAIN=host.example.com \
XPANEL_SERVER_IP=IP_PUBLICA_DEL_VPS \
XPANEL_HOST_SOURCE=/opt/xpanel-host-source \
XPANEL_INSTALL_APACHE=true \
XPANEL_PHP_VERSIONS=8.2,8.3,8.4 \
sudo -E ./install.sh
```

Clonando Host desde Git:

```bash
XPANEL_PANEL_DOMAIN=host.example.com \
XPANEL_SERVER_IP=IP_PUBLICA_DEL_VPS \
XPANEL_HOST_REPO=https://github.com/TU_ORGANIZACION/xpanel-host.git \
XPANEL_HOST_REVISION=main \
sudo -E ./install.sh
```

El instalador:

1. instala Nginx, PHP-FPM, MariaDB, Certbot, cron y dependencias;
2. configura la base central de XPanel VPS;
3. instala XPanel Host una sola vez en `/opt/xpanel-host/releases/<revision>`;
4. crea el enlace `/opt/xpanel-host/current`;
5. registra helpers restringidos mediante `sudoers`;
6. activa el broker firmado entre cada Host y VPS;
7. configura el certificado temporal para acceso por IP y puerto;
8. registra el programador de Laravel en `/etc/cron.d/xpanel-vps`;
9. muestra las credenciales iniciales del administrador una sola vez.

Normalmente la IP pública se detecta mediante un servicio externo y no necesitas indicar `XPANEL_SERVER_IP`. En redes cerradas o instalaciones sin salida a Internet puedes establecerla manualmente.

## 6. Cómo se crea un hosting

Cuando un cliente contrata un plan:

1. se crea una nueva cuenta de hosting ligada al plan; no se reemplazan las contrataciones anteriores;
2. la boleta permanece pendiente durante los días configurados en el plan;
3. XPanel VPS reserva un puerto temporal único;
4. crea usuario Linux, `.env`, SQLite y almacenamiento independientes;
5. crea una slice cgroups v2 y un master PHP-FPM propio con los límites del plan;
6. reutiliza la release instalada de XPanel Host, sin clonar Git por cuenta;
7. crea el vhost Nginx para `panel.dominio-del-cliente.com`;
8. intenta emitir SSL cuando el DNS ya apunta al servidor.

Marcar la boleta como pagada es una operación financiera separada y no crea, activa ni suspende el hosting.

Los campos de RAM, swap, CPU y procesos se configuran en **Administración → Planes**. `100%` de CPU equivale a un núcleo lógico; `200%`, a dos. Para comprobar una instancia en el servidor:

```bash
systemctl show xpanel-instance-UUID.slice \
  -p MemoryHigh -p MemoryMax -p MemorySwapMax -p CPUQuotaPerSecUSec -p TasksMax
systemd-cgls xpanel-instance-UUID.slice
```

El panel Host, los pools PHP de sus sitios y los servicios Node.js aparecen dentro de esa slice. Cron, workers, terminales y contenedores todavía no deben considerarse cubiertos por el límite.

## 7. Cloud, direcciones incluidas y dominio personalizado

Usa `cloud.example.com` para la tienda, la cuenta del cliente y la administración global. Antes de vender planes, crea:

```text
cloud.example.com    A    IP_PUBLICA_DEL_VPS
*.cloud.example.com  A    IP_PUBLICA_DEL_VPS
```

Instala indicando el mismo dominio:

```bash
XPANEL_PANEL_DOMAIN=cloud.example.com \
XPANEL_CLOUD_DOMAIN=cloud.example.com \
sudo -E ./install.sh
```

Si todavía no indicas un dominio, el instalador genera provisionalmente un dominio técnico basado en la IP mediante `sslip.io`. Para producción y venta de planes debes reemplazarlo por tu dominio real y crear los dos registros DNS anteriores.

Cada cuenta recibe automáticamente una dirección estable similar a:

```text
h-a82f10bc2391.cloud.example.com
```

El cliente entra primero en Cloud y **Administrar hosting** crea un token SSO de un solo uso; la dirección de Host no es temporal. Si desea una dirección propia puede registrar desde Cloud:

```text
panel.cliente.com  CNAME  h-a82f10bc2391.cloud.example.com
```

También puede utilizar un registro `A` hacia la IP pública. XPanel añade el alias al virtual host y amplía el certificado cuando el DNS es correcto.

Si todavía no funciona ningún DNS, se conserva un acceso de recuperación:

```text
https://IP_PUBLICA_DEL_VPS:10000
```

El certificado de recuperación es autofirmado y el navegador puede mostrar una advertencia. No debe utilizarse como URL pública definitiva.

No se recomienda usar `IP:puerto` después de activar el dominio y su SSL.

## 8. SSL automático y reintentos

XPanel verifica que la dirección técnica y cualquier dominio personalizado resuelvan hacia `XPANEL_SERVER_IP` antes de solicitar Let’s Encrypt.

- Si todavía no coincide, muestra `Esperando DNS` y mantiene el acceso temporal.
- El programador reintenta cada 15 minutos.
- El administrador puede usar **Reintentar SSL** en el detalle del cliente.
- Cuando Certbot finaliza, el dominio personalizado pasa a ser el acceso preferido; la dirección incluida permanece activa.
- Los errores quedan registrados en `ssl_last_error`; un fallo de SSL no elimina la instancia ni la boleta.

Reintento manual desde terminal:

```bash
cd /opt/xpanel-vps
sudo -u www-data php artisan xpanel:instances:retry-ssl
```

Para una instancia concreta:

```bash
sudo -u www-data php artisan xpanel:instances:retry-ssl --instance=UUID
```

## 9. Verificaciones después de instalar

```bash
cd /opt/xpanel-vps
php artisan about
php artisan migrate:status
php artisan schedule:list
sudo nginx -t
sudo systemctl status nginx mariadb cron
sudo xpanel status
```

Comprueba también:

```bash
ls -la /opt/xpanel-host/current
sudo cat /etc/cron.d/xpanel-vps
sudo ss -lntp
```

## 10. Actualización

```bash
cd /opt/xpanel-vps
sudo ./scripts/xpanel-update.sh
```

Las instancias guardan su `release_path`. Esto permite preparar actualizaciones y rollback por versión sin duplicar todos los datos de cada cliente.

## 11. Diagnóstico rápido

### El dominio no abre

```bash
dig +short panel.cliente.com A
```

Debe devolver exactamente `XPANEL_SERVER_IP`. Mientras se propaga DNS, utiliza el acceso temporal mostrado en el panel del cliente.

### SSL permanece esperando

Verifica DNS, puertos 80/443 y luego ejecuta:

```bash
sudo -u www-data php /opt/xpanel-vps/artisan xpanel:instances:retry-ssl
```

### El acceso por puerto no responde

Revisa el firewall del proveedor, el rango configurado y Nginx:

```bash
sudo nginx -t
sudo ss -lntp | grep -E ':(10000|10001|10002)'
```

### La instancia queda en error

Abre **Administración → Clientes → Instancia XPanel Host**. Corrige el error mostrado y usa **Aplicar configuración**. Los registros también están en `storage/logs/laravel.log` y en el journal de Nginx/PHP-FPM.
