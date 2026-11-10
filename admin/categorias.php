<?php
$raiz = "../";
$titulo = "Categorías";
require "../inc/init.php";
requerir_rol("ADMIN");

$msg = "";
$err = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_validar();
    $accion = $_POST["accion"] ?? "";
    $id = (int)($_POST["id"] ?? 0);

    if ($accion === "guardar") {
        $nombre = trim($_POST["nombre"] ?? "");
        $desc = trim($_POST["descripcion"] ?? "");
        if ($nombre === "" || mb_strlen($nombre) > 80) {
            $err = "El nombre es obligatorio (máximo 80 caracteres).";
        } else {
            try {
                if ($id > 0) {
                    $stmt = $pdo->prepare("UPDATE categorias SET nombre = ?, descripcion = ? WHERE id_categoria = ?");
                    $stmt->execute([$nombre, $desc, $id]);
                    $msg = "Categoría actualizada.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO categorias (nombre, descripcion) VALUES (?, ?)");
                    $stmt->execute([$nombre, $desc]);
                    $msg = "Categoría creada.";
                }
            } catch (PDOException $ex) {
                $err = $ex->getCode() === "23000" ? "Ya existe una categoría con ese nombre." : "No se pudo guardar.";
            }
        }
    } elseif ($accion === "eliminar" && $id > 0) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM servicios WHERE id_categoria = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            $pdo->prepare("UPDATE categorias SET estado = 0 WHERE id_categoria = ?")->execute([$id]);
            $msg = "La categoría tiene servicios asociados, por eso se desactivó en lugar de eliminarse.";
        } else {
            $pdo->prepare("DELETE FROM categorias WHERE id_categoria = ?")->execute([$id]);
            $msg = "Categoría eliminada.";
        }
    } elseif ($accion === "activar" && $id > 0) {
        $pdo->prepare("UPDATE categorias SET estado = 1 WHERE id_categoria = ?")->execute([$id]);
        $msg = "Categoría activada.";
    }
}

$editar = null;
if (isset($_GET["editar"])) {
    $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id_categoria = ?");
    $stmt->execute([(int)$_GET["editar"]]);
    $editar = $stmt->fetch() ?: null;
}
$lista = $pdo->query("SELECT * FROM categorias ORDER BY nombre")->fetchAll();

require "../inc/header.php";
?>
<h1>Categorías</h1>
<?php if ($msg !== ""): ?><p class="ok"><?= e($msg) ?></p><?php endif; ?>
<?php if ($err !== ""): ?><p class="error"><?= e($err) ?></p><?php endif; ?>

<form method="post" action="categorias.php" class="formulario">
    <?= csrf_campo() ?>
    <input type="hidden" name="accion" value="guardar">
    <input type="hidden" name="id" value="<?= (int)($editar["id_categoria"] ?? 0) ?>">
    <h2><?= $editar ? "Editar categoría" : "Nueva categoría" ?></h2>
    <label for="nombre">Nombre</label>
    <input type="text" id="nombre" name="nombre" value="<?= e($editar["nombre"] ?? "") ?>" maxlength="80" required>
    <label for="descripcion">Descripción</label>
    <input type="text" id="descripcion" name="descripcion" value="<?= e($editar["descripcion"] ?? "") ?>" maxlength="255">
    <button type="submit" class="boton">Guardar</button>
    <?php if ($editar): ?><a href="categorias.php">Cancelar</a><?php endif; ?>
</form>

<div class="tabla-scroll">
<table>
    <thead><tr><th>Nombre</th><th>Descripción</th><th>Estado</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($lista as $c): ?>
        <tr>
            <td><?= e($c["nombre"]) ?></td>
            <td><?= e($c["descripcion"]) ?></td>
            <td><?= $c["estado"] ? "Activa" : "Inactiva" ?></td>
            <td>
                <a href="categorias.php?editar=<?= (int)$c["id_categoria"] ?>">Editar</a>
                <form method="post" action="categorias.php" class="en-linea">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="id" value="<?= (int)$c["id_categoria"] ?>">
                    <?php if ($c["estado"]): ?>
                        <input type="hidden" name="accion" value="eliminar">
                        <button type="submit" class="enlace">Eliminar</button>
                    <?php else: ?>
                        <input type="hidden" name="accion" value="activar">
                        <button type="submit" class="enlace">Activar</button>
                    <?php endif; ?>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php require "../inc/footer.php"; ?>
