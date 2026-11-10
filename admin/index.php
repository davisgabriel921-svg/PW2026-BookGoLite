<?php
$raiz = "../";
$titulo = "Panel de administración";
require "../inc/init.php";
requerir_rol("ADMIN");

$totalCategorias = $pdo->query("SELECT COUNT(*) FROM categorias")->fetchColumn();
$totalServicios = $pdo->query("SELECT COUNT(*) FROM servicios")->fetchColumn();
$totalClientes = $pdo->query("SELECT COUNT(*) FROM usuarios u JOIN roles r ON r.id_rol = u.id_rol WHERE r.nombre = 'CLIENTE'")->fetchColumn();

require "../inc/header.php";
?>
<h1>Panel de administración</h1>
<div class="tarjetas">
    <article class="tarjeta"><h3>Categorías</h3><p class="precio"><?= (int)$totalCategorias ?></p><a href="categorias.php">Administrar</a></article>
    <article class="tarjeta"><h3>Servicios</h3><p class="precio"><?= (int)$totalServicios ?></p><a href="servicios.php">Administrar</a></article>
    <article class="tarjeta"><h3>Clientes</h3><p class="precio"><?= (int)$totalClientes ?></p></article>
</div>
<?php require "../inc/footer.php"; ?>
