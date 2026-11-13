# Book&Go Lite – Soporte Técnico

Sistema web de reservas para un negocio de soporte técnico a domicilio. Los clientes consultan el catálogo de servicios, reservan horarios con cupos limitados y dejan reseñas. El administrador gestiona categorías, servicios, horarios y reservas.

**Proyecto Parcial 2 · Desarrollo Web 2026 · Grupo 1S3221**
Integrantes: Gabriel Davis, Mateo López y Ronaldo Garcia

Tecnologías: PHP, MySQL (PDO), HTML5 y CSS3. No usa librerías ni servicios externos.

## Funcionalidades

**Visitante:** catálogo con búsqueda y filtro por categoría, detalle del servicio con sus horarios y reseñas, registro e inicio de sesión.

**Cliente:** reservar un horario, ver sus reservas, cancelarlas (el cupo se devuelve) y reseñar los servicios que ya fueron realizados.

**Administrador:** CRUD de categorías, servicios y horarios, y gestión del estado de las reservas (PENDIENTE, CONFIRMADA, REALIZADA y CANCELADA).

## Cómo ejecutarlo (XAMPP)

1. Iniciar Apache y MySQL desde el panel de XAMPP.
2. En phpMyAdmin (`http://localhost/phpmyadmin`), importar `database/bookgo.sql`. Crea la base `bookgo_lite`, las 7 tablas y datos de prueba (3 categorías, 6 servicios y 3 horarios).
3. Copiar esta carpeta dentro de `C:\xampp\htdocs` y revisar los datos de conexión en `inc/init.php` (por defecto, usuario `root` sin contraseña).
4. Crear el usuario administrador desde la terminal. La contraseña no se guarda en el repositorio:
   `php database/crear_admin.php correo@ejemplo.com ClaveSegura123 Nombre Apellido`
5. Abrir `http://localhost/<nombre-de-la-carpeta>/index.php`.
6. Los clientes de prueba se crean desde la opción "Registrarme".

Los horarios de prueba del SQL tienen fechas de noviembre de 2026. Si ya pasaron, crear nuevos desde el panel del administrador (Horarios).

## Estructura

| Carpeta o archivo | Contenido |
|---|---|
| `inc/` | Conexión, sesión, funciones de seguridad, encabezado y pie |
| `admin/` | Panel, categorías, servicios, horarios y reservas (solo ADMIN) |
| `database/` | `bookgo.sql` y `crear_admin.php` |
| `security/` | `SECURITY.md` con las pruebas de seguridad y sus evidencias |
| `css/` | Hoja de estilos |

## Seguridad implementada

Contraseñas con `password_hash`, consultas preparadas con PDO, escape de salida contra XSS, tokens CSRF en todos los formularios, control de acceso por rol, filtro por usuario en las reservas, bloqueo temporal tras 5 intentos fallidos de login, sesiones con cookies HttpOnly y SameSite, y cabeceras HTTP de seguridad. Las pruebas realizadas están en `security/SECURITY.md`.

## Limitaciones conocidas

El bloqueo por intentos fallidos se guarda en la sesión, por lo que podría evadirse borrando las cookies. En un sistema real se guardaría por correo o IP en la base de datos. La aplicación se probó en un entorno local sin HTTPS.
