<?php
$titulo = "Ingresar";
require "inc/init.php";
if (usuario_actual()) {
    ir("index.php");
}

$err = "";
$ok = $_SESSION["ok"] ?? "";
unset($_SESSION["ok"]);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_validar();
    $correo = trim($_POST["correo"] ?? "");
    $clave = $_POST["clave"] ?? "";

    $stmt = $pdo->prepare("SELECT u.id_usuario, u.nombre, u.contrasena, u.estado, r.nombre AS rol
        FROM usuarios u JOIN roles r ON r.id_rol = u.id_rol WHERE u.correo = ?");
    $stmt->execute([$correo]);
    $fila = $stmt->fetch();

    if ($fila && $fila["estado"] == 1 && password_verify($clave, $fila["contrasena"])) {
        session_regenerate_id(true);
        $_SESSION["usuario"] = [
            "id" => (int)$fila["id_usuario"],
            "nombre" => $fila["nombre"],
            "rol" => $fila["rol"]
        ];
        ir($fila["rol"] === "ADMIN" ? "admin/index.php" : "index.php");
    }
    $err = "Correo o contraseña incorrectos.";
}
require "inc/header.php";
?>
<h1>Iniciar sesión</h1>
<?php if ($ok !== ""): ?><p class="ok"><?= e($ok) ?></p><?php endif; ?>
<?php if ($err !== ""): ?><p class="error"><?= e($err) ?></p><?php endif; ?>
<form method="post" action="login.php" class="formulario">
    <?= csrf_campo() ?>
    <label for="correo">Correo electrónico</label>
    <input type="email" id="correo" name="correo" required>
    <label for="clave">Contraseña</label>
    <input type="password" id="clave" name="clave" required>
    <button type="submit" class="boton">Ingresar</button>
</form>
<?php require "inc/footer.php"; ?>
