<?php
if (PHP_SAPI !== "cli") {
    die("Este script solo se ejecuta desde la línea de comandos.");
}
require __DIR__ . "/../inc/init.php";

if ($argc < 5) {
    die("Uso: php crear_admin.php correo clave nombre apellido\n");
}
list(, $correo, $clave, $nombre, $apellido) = $argv;

if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($clave) < 8) {
    die("Correo no válido o contraseña de menos de 8 caracteres.\n");
}

try {
    $stmt = $pdo->prepare("INSERT INTO usuarios (id_rol, nombre, apellido, correo, contrasena)
        VALUES ((SELECT id_rol FROM roles WHERE nombre = 'ADMIN'), ?, ?, ?, ?)");
    $stmt->execute([$nombre, $apellido, $correo, password_hash($clave, PASSWORD_DEFAULT)]);
    echo "Administrador creado.\n";
} catch (PDOException $ex) {
    echo "No se pudo crear el administrador (¿el correo ya existe?).\n";
}
