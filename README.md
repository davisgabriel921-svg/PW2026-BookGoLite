# Book&Go Lite – Soporte Técnico (revisión preliminar)

Negocio elegido: soporte técnico a domicilio. Tecnologías: PHP, MySQL (PDO), HTML5 y CSS3, sin librerías externas.

## Cómo ejecutarlo (XAMPP)
1. Iniciar Apache y MySQL.
2. En phpMyAdmin, importar `database/bookgo.sql` (crea la base `bookgo_lite`, las 7 tablas y los datos de prueba).
3. Copiar esta carpeta dentro de `htdocs` y revisar los datos de conexión en `inc/init.php`.
4. Crear el administrador desde la terminal (la contraseña no se guarda en el repositorio):
   `php database/crear_admin.php correo@ejemplo.com ClaveSegura123 Nombre Apellido`
5. Abrir `http://localhost/<carpeta>/index.php`. Los clientes se crean desde "Registrarme".

## Avance de la revisión preliminar
Base de datos con 7 tablas, registro, inicio y cierre de sesión, menú por rol, CRUD de categorías y servicios, catálogo con búsqueda y filtro.

## Pendiente
Horarios, reservas, reseñas, bloqueo por intentos fallidos de login y evidencias de seguridad.
