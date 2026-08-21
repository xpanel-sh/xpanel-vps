# Política de seguridad

XPanel VPS está en fase alfa. Solamente la última versión publicada recibe correcciones de seguridad.

No abras un Issue público con detalles explotables. Usa **Security > Report a vulnerability** en GitHub e incluye versión, impacto y pasos mínimos de reproducción sin adjuntar secretos ni datos de clientes.

Son áreas especialmente sensibles los escapes entre tenants, ejecución mediante helpers/sudoers, exposición de credenciales, omisiones de autorización, escritura fuera del document root e inyección en configuraciones del sistema.
