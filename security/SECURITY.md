# Pruebas de seguridad – Book&Go Lite (Soporte Técnico)

Proyecto Parcial 2 · Desarrollo Web 2026 · Grupo 1S3221
Integrantes: Gabriel Davis, Mateo López y Ronaldo Garcia
Entorno de pruebas: aplicación local (XAMPP, `http://localhost/PW2026_Grupo_BookGoLite/`). Todas las pruebas se hicieron únicamente en nuestra aplicación de desarrollo.
Fecha de las pruebas: 11/11/2026
Las capturas están en la carpeta `evidencias/`.

---

## Prueba 1. SQL Injection en login y búsqueda

**Objetivo:** comprobar que un texto malicioso no altera la consulta SQL ni permite entrar sin credenciales.

**Pasos:**
1. En el catálogo (`index.php`), escribir en el buscador `' OR 1=1 --` y presionar Buscar.
2. En `login.php`, abrir el inspector (F12) y cambiar el campo de correo de `type="email"` a `type="text"` (el navegador solo valida el formato en el campo de correo).
3. Escribir como correo `' OR '1'='1` y cualquier contraseña, y enviar.

**Resultado esperado:** la búsqueda no devuelve todos los servicios y el login no permite el acceso.
**Resultado observado:** Se escribió ' OR 1=1 -- en el buscador y ' OR '1'='1 en el login. La aplicación no listó servicios ni permitió el acceso. Las consultas preparadas tratan la entrada como dato.
**Control aplicado:** PDO con consultas preparadas y parámetros (`?`) en todos los valores recibidos (`index.php`, `login.php`). El texto del usuario se trata como dato y nunca como parte del SQL.
**Evidencia:** `evidencias/01_sqli_busqueda.png`, `evidencias/01_sqli_login.png`

## Prueba 2. XSS en un campo visible

**Objetivo:** comprobar que un script escrito en un campo se muestra como texto y no se ejecuta.

**Pasos:**
1. En `registro.php`, registrar un cliente con el nombre `<script>alert(1)</script>`.
2. Iniciar sesión con esa cuenta y ver cómo aparece el nombre en el encabezado.

**Resultado esperado:** el texto se ve literalmente y no aparece ninguna alerta.
**Resultado observado:** Se registró un usuario con el nombre <script>alert(1)</script>. Se mostró como texto y no se ejecutó ninguna alerta. La función e() con htmlspecialchars codifica la salida
**Control aplicado:** la función `e()` de `inc/init.php` usa `htmlspecialchars()` con `ENT_QUOTES` y UTF-8 en toda salida de datos. Además, la cabecera Content-Security-Policy no permite scripts en línea.
**Evidencia:** `evidencias/02_xss.png`

## Prueba 3. Acceso directo de un CLIENTE a una página ADMIN

**Objetivo:** comprobar que un cliente no puede entrar a las páginas del administrador.

**Pasos:**
1. Iniciar sesión como CLIENTE.
2. Escribir en la barra de direcciones `http://localhost/PW2026_Grupo_BookGoLite/admin/index.php` (y también `admin/horarios.php`).

**Resultado esperado:** se muestra "Acceso denegado." con código 403.
**Resultado observado:** Con sesión de cliente se abrió directamente admin/index.php. El sistema respondió ‘Acceso denegado.’ requerir_rol valida el rol en PHP.
**Control aplicado:** `requerir_rol("ADMIN")` al inicio de cada página de `admin/`, validado en el servidor con el rol guardado en la sesión.
**Evidencia:** `evidencias/03_acceso_admin.png`

## Prueba 4. Acceso a la reserva de otro cliente

**Objetivo:** comprobar que un cliente no puede ver ni modificar reservas ajenas.

**Pasos:**
1. Con el cliente A, crear una reserva y anotar su `id_reserva` (inspector, campo oculto del botón Cancelar en "Mis reservas").
2. Con el cliente B, crear una reserva propia para que aparezca el botón Cancelar.
3. En el inspector, cambiar el valor del campo oculto `id_reserva` por el de A y presionar Cancelar.
4. Volver a entrar como A y verificar que su reserva sigue igual.

**Resultado esperado:** mensaje "Reserva no encontrada." y la reserva de A queda intacta.
**Resultado observado:** Con la sesión de Beto se cambió el id_reserva por el de Ana y se intentó cancelar. El sistema respondió ‘Reserva no encontrada’ y la reserva de Ana no cambió. La consulta filtra por el usuario de la sesión.
**Control aplicado:** la consulta de `reservas.php` filtra por `id_reserva` y por `id_usuario` de la sesión, así que una reserva ajena no se encuentra. La tabla "Mis reservas" solo lista las del usuario.
**Evidencia:** `evidencias/04_reserva_ajena.png`

## Prueba 5. Formulario sin token CSRF válido

**Objetivo:** comprobar que una operación sin token válido se rechaza sin cambiar datos.

**Pasos:**
1. Iniciar sesión como ADMIN y abrir `admin/categorias.php`.
2. En el inspector, borrar el campo oculto `csrf` (o cambiar su valor) del formulario de crear o del botón Eliminar.
3. Enviar el formulario.

**Resultado esperado:** se muestra "Solicitud no válida." y no cambia nada en la base de datos.
**Resultado observado:** Se alteró el token CSRF de un formulario con el inspector y se envió. El sistema respondió ‘Solicitud no válida’ y no se creó el registro. csrf_validar() compara el token con el de la sesión
**Control aplicado:** `csrf_campo()` agrega un token aleatorio de sesión a cada formulario y `csrf_validar()` lo compara con `hash_equals()` antes de ejecutar cualquier operación POST. Además, la cookie de sesión usa `SameSite=Lax`.
**Evidencia:** `evidencias/05_csrf.png`

## Prueba 6. Cinco intentos de login fallidos

**Objetivo:** comprobar que se activa un bloqueo temporal.

**Pasos:**
1. En `login.php`, escribir una contraseña incorrecta cinco veces seguidas.
2. Intentar un sexto intento, incluso con la contraseña correcta.

**Resultado esperado:** al quinto intento aparece el mensaje de espera y durante 60 segundos no se permite iniciar sesión.
**Resultado observado:** Se fallaron cinco intentos de login. Al quinto se activó el bloqueo de 60 segundos y el intento con la clave correcta también fue rechazado durante ese tiempo. Los mensajes no revelan si el correo existe.
**Control aplicado:** contador de intentos y tiempo de bloqueo guardados en la sesión (`login.php`). El mensaje de error es el mismo exista o no el correo, para no revelar cuentas.
**Evidencia:** `evidencias/06_bloqueo_login.png`

## Prueba 7. Inspección de cabeceras HTTP

**Objetivo:** comprobar que se envían las cabeceras de seguridad configuradas.

**Pasos:**
1. Abrir el navegador, presionar F12 y entrar a la pestaña Red (Network).
2. Recargar `index.php`, hacer clic en la primera petición y ver las cabeceras de respuesta.

**Resultado esperado:** aparecen `Content-Security-Policy`, `X-Content-Type-Options: nosniff` y `X-Frame-Options: DENY`.
**Resultado observado:** En las herramientas del navegador se observaron en la respuesta las cabeceras Content-Security-Policy, X-Content-Type-Options (nosniff) y X-Frame-Options (DENY).
**Control aplicado:** cabeceras enviadas con `header()` en `inc/init.php`, que se incluye en todas las páginas.
**Evidencia:** `evidencias/07_cabeceras.png`

---

## Otros controles implementados
- Contraseñas con `password_hash()` y `password_verify()`, mínimo 8 caracteres.
- Sesión: se regenera el identificador al iniciar sesión, se destruye al salir, y la cookie es HttpOnly y SameSite (Secure cuando hay HTTPS).
- Errores: los detalles técnicos van a `error_log` y al usuario solo se le muestran mensajes generales.
- Reservas y cancelaciones dentro de transacciones, con actualización condicionada de los cupos.

## Limitaciones conocidas
- El bloqueo de login se guarda en la sesión, por lo que un atacante podría evadirlo borrando las cookies. En un sistema real se guardaría por correo o IP en la base de datos.
- La aplicación se probó en un entorno local sin HTTPS, por lo que la bandera Secure de la cookie no se activa.
