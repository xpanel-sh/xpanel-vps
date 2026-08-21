# Contribuir a XPanel VPS

1. Crea una rama enfocada desde la rama principal.
2. No incluyas `.env`, logs, credenciales ni datos de clientes.
3. Ejecuta `vendor/bin/pint` y `php artisan test`.
4. Documenta variables, migraciones y cambios de privilegios.
5. Abre un pull request explicando problema, solución y validación.

Los cambios en helpers, sudoers, autorización, aislamiento o configuración del servidor requieren pruebas específicas de seguridad.
