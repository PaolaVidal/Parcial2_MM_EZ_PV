# Proyecto Psicología - PHP MVC Puro

Implementación básica MVC en PHP (sin frameworks) para gestionar Citas, Pagos y Tickets con generación de códigos QR.

## Requisitos Cubiertos
1. PHP puro, estructura MVC (models, views, controllers, helpers, public, config, vendor/phpqrcode).
2. Conexión MySQL por PDO (`config/db.php`).
3. Estructura de carpetas solicitada.
4. Al crear cita se genera QR con enlace a detalle.
5. Al marcar pago como pagado se genera ticket con QR.
6. Imágenes QR guardadas en `public/qrcodes/` y ruta en BD.
7. Recalculo de monto total: base + extras (en `Pago::recalcularTotal`).
8. Botón "Marcar como Pagado" crea ticket y QR.
9. Vistas Bootstrap 5: citas, pagos, ticket.
10. Comentarios en código explicando partes clave.
11. Sesión iniciada en `public/index.php`.
12. Listo para adaptar diseño a imagen referencial.

## Instalación
1. Crear BD ejecutando script SQL proporcionado.
2. Copiar librería phpqrcode: descargar `qrlib.php` a `vendor/phpqrcode/`.
3. Configurar credenciales en `config/db.php`.
4. Servir carpeta `public/` como raíz (en XAMPP: apuntar VirtualHost o mover `index.php`). Si usas directamente, acceder a: `http://localhost/Parcial2_MM_EZ_PV/public/index.php`.

### Autenticación Básica
Se incluyó un flujo mínimo de login/logout:
1. Crea un usuario manualmente en la tabla `Usuario` (rol por defecto `paciente`). Ejemplo:
	INSERT INTO Usuario (nombre,email,contraseña,rol,estado) VALUES ('Admin','admin@demo.com', '$2y$10$abcdefghijklmnopqrstuvABCDEFGHijklmno1234567890abc', 'admin','activo');
	(La contraseña encriptada anterior es de ejemplo y no válida; genera una usando PHP: `password_hash('tu_password', PASSWORD_BCRYPT)`.)
2. Accede a `?controller=Auth&action=login` y autentícate.
3. Una vez logeado podrás navegar a Citas y Pagos. Si no estás logeado el sistema te redirige al login.

## Notas
- Simplificado: no incluye autenticación completa ni validaciones avanzadas; se puede extender.
- Para seguridad: sanitizar entradas, proteger contra CSRF, roles por sesión.
- Se puede mejorar generando clases repository, formularios con selects reales (pacientes, psicólogos), etc.
- Si modificas la tabla `Paciente` para incluir `id_usuario` (FK a Usuario) debes crear pacientes usando ese ID y las consultas ya están adaptadas (`Paciente::crear`, `Cita::listar`).
- Se reemplazaron todos los enlaces manuales `?controller=...&action=...` por el helper `url('Controlador','accion',[params])` definido en `helpers/UrlHelper.php` para evitar errores de concatenación y facilitar refactors.

## Ruteo Actual
- El ruteo se gestiona por `public/index.php` leyendo `?controller=Nombre&action=metodo`.
- Si el controlador o acción no existen, carga `views/404.php` con HTTP 404.
- Archivo `config/rutas.php` quedó DEPRECADO y solo se mantiene para compatibilidad.

## Helpers de URL
`helpers/UrlHelper.php` expone:
- `base_url()` devuelve la URL base donde vive `public/index.php`.
- `url('Cita','ver',['id'=>5])` construye una URL completa con parámetros.

Ejemplo de uso en una vista:
`<a href="<?= url('Cita','ver',['id'=>$c['id']]) ?>">Ver Cita</a>`

## Próximos Pasos Sugeridos
- Implementar login de Usuario y restricción por rol.
- Añadir CRUD de Paciente y Psicólogo.
- Mejorar manejo de errores y mensajes flash.
- Agregar tests unitarios con PHPUnit.
- Internacionalización y formato de fechas.
