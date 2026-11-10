<?php $u = usuario_actual(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo ?? "Inicio") ?> | Book&amp;Go Lite</title>
    <link rel="stylesheet" href="<?= $raiz ?>css/estilos.css">
</head>
<body>
<header class="cab">
    <a class="logo" href="<?= $raiz ?>index.php">Book&amp;Go <span>Soporte Técnico</span></a>
    <nav>
        <a href="<?= $raiz ?>index.php">Catálogo</a>
        <?php if (!$u): ?>
            <a href="<?= $raiz ?>registro.php">Registrarme</a>
            <a href="<?= $raiz ?>login.php">Ingresar</a>
        <?php elseif ($u["rol"] === "ADMIN"): ?>
            <a href="<?= $raiz ?>admin/index.php">Panel</a>
            <a href="<?= $raiz ?>admin/categorias.php">Categorías</a>
            <a href="<?= $raiz ?>admin/servicios.php">Servicios</a>
        <?php else: ?>
            <a href="<?= $raiz ?>reservas.php">Mis reservas</a>
        <?php endif; ?>
        <?php if ($u): ?>
            <span class="quien"><?= e($u["nombre"]) ?> (<?= e($u["rol"]) ?>)</span>
            <form method="post" action="<?= $raiz ?>logout.php" class="en-linea">
                <?= csrf_campo() ?>
                <button type="submit" class="enlace">Salir</button>
            </form>
        <?php endif; ?>
    </nav>
</header>
<main>
