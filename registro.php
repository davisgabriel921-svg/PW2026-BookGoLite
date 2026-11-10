<?php
$titulo = "Registro";
require "inc/init.php";
if (usuario_actual()) {
    ir("index.php");
}

$err = "";
$nombre = "";
$apellido = "";
$correo = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_validar();
    $nombre = trim($_POST["nombre"] ?? "");
    $apellido = trim($_POST["apellido"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $clave = $_POST["clave"] ?? "";
    $clave2 = $_POST["clave2"] ?? "";

    if ($nombre === "" || $apellido === "" || $correo === "" || $clave === "") {
        $err = "Complete todos los campos.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $err = "El correo no es válido.";
    } elseif (strlen($clave) < 8) {
        $err = "La contraseña debe tener al menos 8 caracteres.";
    } elseif ($clave !== $clave2) {
        $err = "Las contraseñas no coinciden.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO usuarios (id_rol, nombre, apellido, correo, contrasena)
                VALUES ((SELECT id_rol FROM roles WHERE nombre = 'CLIENTE'), ?, ?, ?, ?)");
            $stmt->execute([$nombre, $apellido, $correo, password_hash($clave, PASSWORD_DEFAULT)]);
            $_SESSION["ok"] = "Cuenta creada. Ya puede iniciar sesión.";
            ir("login.php");
        } catch (PDOException $ex) {
            $err = $ex->getCode() === "23000" ? "Ese correo ya está registrado." : "No se pudo crear la cuenta.";
        }
    }
}
require "inc/header.php";
?>
<h1>Crear cuenta</h1>
<?php if ($err !== ""): ?><p class="error"><?= e($err) ?></p><?php endif; ?>
<form method="post" action="registro.php" class="formulario">
    <?= csrf_campo() ?>
    <label for="nombre">Nombre</label>
    <input type="text" id="nombre" name="nombre" value="<?= e($nombre) ?>" maxlength="60" required>
    <label for="apellido">Apellido</label>
    <input type="text" id="apellido" name="apellido" value="<?= e($apellido) ?>" maxlength="60" required>
    <label for="correo">Correo electrónico</label>
    <input type="email" id="correo" name="correo" value="<?= e($correo) ?>" maxlength="120" required>
    <label for="clave">Contraseña (mínimo 8 caracteres)</label>
    <input type="password" id="clave" name="clave" minlength="8" required>
    <label for="clave2">Repetir contraseña</label>
    <input type="password" id="clave2" name="clave2" minlength="8" required>
    <button type="submit" class="boton">Registrarme</button>
</form>
<?php require "inc/footer.php"; ?>
