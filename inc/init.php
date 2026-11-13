<?php
$raiz = $raiz ?? "";
date_default_timezone_set("America/Panama");

if (PHP_SAPI !== "cli") {
    ini_set("session.cookie_httponly", "1");
    ini_set("session.cookie_samesite", "Lax");
    ini_set("session.use_strict_mode", "1");
    if (!empty($_SERVER["HTTPS"])) {
        ini_set("session.cookie_secure", "1");
    }
    session_start();
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: DENY");
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; frame-ancestors 'none'");
}

$servidor = "localhost";
$base = "bookgo_lite";
$usuarioBd = "root";
$claveBd = "";

try {
    $pdo = new PDO(
        "mysql:host=$servidor;dbname=$base;charset=utf8mb4",
        $usuarioBd,
        $claveBd,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $ex) {
    error_log($ex->getMessage());
    die("No se pudo conectar con la base de datos. Intente más tarde.");
}

function e($texto)
{
    return htmlspecialchars((string)$texto, ENT_QUOTES, "UTF-8");
}

function csrf_token()
{
    if (empty($_SESSION["csrf"])) {
        $_SESSION["csrf"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf"];
}

function csrf_campo()
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_validar()
{
    if (!isset($_POST["csrf"], $_SESSION["csrf"]) || !hash_equals($_SESSION["csrf"], $_POST["csrf"])) {
        http_response_code(403);
        die("Solicitud no válida.");
    }
}

function usuario_actual()
{
    return $_SESSION["usuario"] ?? null;
}

function ir($ruta)
{
    global $raiz;
    header("Location: " . $raiz . $ruta);
    exit;
}

function requerir_login()
{
    if (!usuario_actual()) {
        ir("login.php");
    }
}

function requerir_rol($rol)
{
    requerir_login();
    if ($_SESSION["usuario"]["rol"] !== $rol) {
        http_response_code(403);
        die("Acceso denegado.");
    }
}

function es_futuro($fecha, $hora)
{
    return strtotime($fecha . " " . $hora) > time();
}
