<?php
require "inc/init.php";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_validar();
    $_SESSION = [];
    session_destroy();
}
ir("login.php");
