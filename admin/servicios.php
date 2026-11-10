<?php
$raiz = "../";
$titulo = "Servicios";
require "../inc/init.php";
requerir_rol("ADMIN");

$msg = "";
$err = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_validar();
    $accion = $_POST["accion"] ?? "";
    $id = (int)($_POST["id"] ?? 0);

    if ($accion === "guardar") {
        $cat = (int)($_POST["id_categoria"] ?? 0);
        $nombre = trim($_POST["nombre"] ?? "");
        $desc = trim($_POST["descripcion"] ?? "");
        $precio = $_POST["precio"] ?? "";
        $dur = (string)($_POST["duracion_min"] ?? "");

        $existe = $pdo->prepare("SELECT COUNT(*) FROM categorias WHERE id_categoria = ? AND estado = 1");
        $existe->execute([$cat]);

        if ($nombre === "" || mb_strlen($nombre) > 100) {
            $err = "El nombre es obligatorio (máximo 100 caracteres).";
        } elseif (!$existe->fetchColumn()) {
            $err = "Seleccione una categoría válida.";
        } elseif (!is_numeric($precio) || $precio < 0) {
            $err = "El precio no puede ser negativo.";
        } elseif (!ctype_digit($dur) || (int)$dur <= 0) {
            $err = "La duración debe ser mayor que cero.";
        } else {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE servicios SET id_categoria = ?, nombre = ?, descripcion = ?, precio = ?, duracion_min = ? WHERE id_servicio = ?");
                $stmt->execute([$cat, $nombre, $desc, $precio, (int)$dur, $id]);
                $msg = "Servicio actualizado.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO servicios (id_categoria, nombre, descripcion, precio, duracion_min) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$cat, $nombre, $desc, $precio, (int)$dur]);
                $msg = "Servicio creado.";
            }
        }
    } elseif ($accion === "eliminar" && $id > 0) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM horarios WHERE id_servicio = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            $pdo->prepare("UPDATE servicios SET estado = 0 WHERE id_servicio = ?")->execute([$id]);
            $msg = "El servicio tiene horarios asociados, por eso se desactivó en lugar de eliminarse.";
        } else {
            $pdo->prepare("DELETE FROM servicios WHERE id_servicio = ?")->execute([$id]);
            $msg = "Servicio eliminado.";
        }
    } elseif ($accion === "activar" && $id > 0) {
        $pdo->prepare("UPDATE servicios SET estado = 1 WHERE id_servicio = ?")->execute([$id]);
        $msg = "Servicio activado.";
    }
}

$editar = null;
if (isset($_GET["editar"])) {
    $stmt = $pdo->prepare("SELECT * FROM servicios WHERE id_servicio = ?");
    $stmt->execute([(int)$_GET["editar"]]);
    $editar = $stmt->fetch() ?: null;
}
$categorias = $pdo->query("SELECT id_categoria, nombre FROM categorias WHERE estado = 1 ORDER BY nombre")->fetchAll();
$lista = $pdo->query("SELECT s.*, c.nombre AS categoria FROM servicios s JOIN categorias c ON c.id_categoria = s.id_categoria ORDER BY s.nombre")->fetchAll();

require "../inc/header.php";
?>
<h1>Servicios</h1>
<?php if ($msg !== ""): ?><p class="ok"><?= e($msg) ?></p><?php endif; ?>
<?php if ($err !== ""): ?><p class="error"><?= e($err) ?></p><?php endif; ?>

<form method="post" action="servicios.php" class="formulario">
    <?= csrf_campo() ?>
    <input type="hidden" name="accion" value="guardar">
    <input type="hidden" name="id" value="<?= (int)($editar["id_servicio"] ?? 0) ?>">
    <h2><?= $editar ? "Editar servicio" : "Nuevo servicio" ?></h2>
    <label for="id_categoria">Categoría</label>
    <select id="id_categoria" name="id_categoria" required>
        <?php foreach ($categorias as $c): ?>
            <option value="<?= (int)$c["id_categoria"] ?>" <?= (int)($editar["id_categoria"] ?? 0) === (int)$c["id_categoria"] ? "selected" : "" ?>><?= e($c["nombre"]) ?></option>
        <?php endforeach; ?>
    </select>
    <label for="nombre">Nombre</label>
    <input type="text" id="nombre" name="nombre" value="<?= e($editar["nombre"] ?? "") ?>" maxlength="100" required>
    <label for="descripcion">Descripción</label>
    <input type="text" id="descripcion" name="descripcion" value="<?= e($editar["descripcion"] ?? "") ?>">
    <label for="precio">Precio ($)</label>
    <input type="number" id="precio" name="precio" min="0" step="0.01" value="<?= e($editar["precio"] ?? "") ?>" required>
    <label for="duracion_min">Duración (minutos)</label>
    <input type="number" id="duracion_min" name="duracion_min" min="1" step="1" value="<?= e($editar["duracion_min"] ?? "") ?>" required>
    <button type="submit" class="boton">Guardar</button>
    <?php if ($editar): ?><a href="servicios.php">Cancelar</a><?php endif; ?>
</form>

<div class="tabla-scroll">
<table>
    <thead><tr><th>Servicio</th><th>Categoría</th><th>Precio</th><th>Duración</th><th>Estado</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($lista as $s): ?>
        <tr>
            <td><?= e($s["nombre"]) ?></td>
            <td><?= e($s["categoria"]) ?></td>
            <td>$<?= number_format($s["precio"], 2) ?></td>
            <td><?= (int)$s["duracion_min"] ?> min</td>
            <td><?= $s["estado"] ? "Activo" : "Inactivo" ?></td>
            <td>
                <a href="servicios.php?editar=<?= (int)$s["id_servicio"] ?>">Editar</a>
                <form method="post" action="servicios.php" class="en-linea">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="id" value="<?= (int)$s["id_servicio"] ?>">
                    <?php if ($s["estado"]): ?>
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
