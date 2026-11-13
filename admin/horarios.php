<?php
$raiz = "../";
$titulo = "Horarios";
require "../inc/init.php";
requerir_rol("ADMIN");

$msg = "";
$err = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_validar();
    $accion = $_POST["accion"] ?? "";
    $id = (int)($_POST["id"] ?? 0);

    if ($accion === "guardar") {
        $ids = (int)($_POST["id_servicio"] ?? 0);
        $fecha = $_POST["fecha"] ?? "";
        $hi = $_POST["hora_inicio"] ?? "";
        $hf = $_POST["hora_fin"] ?? "";
        $ct = (string)($_POST["cupos_total"] ?? "");

        $existe = $pdo->prepare("SELECT COUNT(*) FROM servicios WHERE id_servicio = ? AND estado = 1");
        $existe->execute([$ids]);
        $d = DateTime::createFromFormat("Y-m-d", $fecha);

        if (!$existe->fetchColumn()) {
            $err = "Seleccione un servicio válido.";
        } elseif (!$d || $d->format("Y-m-d") !== $fecha || !preg_match('/^\d{2}:\d{2}$/', $hi) || !preg_match('/^\d{2}:\d{2}$/', $hf)) {
            $err = "Fecha u hora no válidas.";
        } elseif ($hf <= $hi) {
            $err = "La hora final debe ser posterior a la hora inicial.";
        } elseif (!es_futuro($fecha, $hi)) {
            $err = "No se pueden crear horarios en el pasado.";
        } elseif (!ctype_digit($ct) || (int)$ct <= 0) {
            $err = "Los cupos deben ser un número mayor que cero.";
        } else {
            $ct = (int)$ct;
            if ($id > 0) {
                $q = $pdo->prepare("SELECT COUNT(*) FROM reservas WHERE id_horario = ? AND estado <> 'CANCELADA'");
                $q->execute([$id]);
                $ocupados = (int)$q->fetchColumn();
                if ($ct < $ocupados) {
                    $err = "Los cupos totales no pueden ser menores que las reservas existentes (" . $ocupados . ").";
                } else {
                    $q = $pdo->prepare("UPDATE horarios SET id_servicio = ?, fecha = ?, hora_inicio = ?, hora_fin = ?, cupos_total = ?, cupos_disponibles = ? WHERE id_horario = ?");
                    $q->execute([$ids, $fecha, $hi, $hf, $ct, $ct - $ocupados, $id]);
                    $msg = "Horario actualizado.";
                }
            } else {
                $q = $pdo->prepare("INSERT INTO horarios (id_servicio, fecha, hora_inicio, hora_fin, cupos_total, cupos_disponibles) VALUES (?, ?, ?, ?, ?, ?)");
                $q->execute([$ids, $fecha, $hi, $hf, $ct, $ct]);
                $msg = "Horario creado.";
            }
        }
    } elseif ($accion === "eliminar" && $id > 0) {
        $q = $pdo->prepare("SELECT COUNT(*) FROM reservas WHERE id_horario = ?");
        $q->execute([$id]);
        if ($q->fetchColumn() > 0) {
            $pdo->prepare("UPDATE horarios SET estado = 0 WHERE id_horario = ?")->execute([$id]);
            $msg = "El horario tiene reservas asociadas, por eso se desactivó en lugar de eliminarse.";
        } else {
            $pdo->prepare("DELETE FROM horarios WHERE id_horario = ?")->execute([$id]);
            $msg = "Horario eliminado.";
        }
    } elseif ($accion === "activar" && $id > 0) {
        $pdo->prepare("UPDATE horarios SET estado = 1 WHERE id_horario = ?")->execute([$id]);
        $msg = "Horario activado.";
    }
}

$editar = null;
if (isset($_GET["editar"])) {
    $q = $pdo->prepare("SELECT * FROM horarios WHERE id_horario = ?");
    $q->execute([(int)$_GET["editar"]]);
    $editar = $q->fetch() ?: null;
}
$servicios = $pdo->query("SELECT id_servicio, nombre FROM servicios WHERE estado = 1 ORDER BY nombre")->fetchAll();
$lista = $pdo->query("SELECT h.*, s.nombre AS servicio FROM horarios h JOIN servicios s ON s.id_servicio = h.id_servicio ORDER BY h.fecha, h.hora_inicio")->fetchAll();

require "../inc/header.php";
?>
<h1>Horarios</h1>
<?php if ($msg !== ""): ?><p class="ok"><?= e($msg) ?></p><?php endif; ?>
<?php if ($err !== ""): ?><p class="error"><?= e($err) ?></p><?php endif; ?>

<form method="post" action="horarios.php" class="formulario">
    <?= csrf_campo() ?>
    <input type="hidden" name="accion" value="guardar">
    <input type="hidden" name="id" value="<?= (int)($editar["id_horario"] ?? 0) ?>">
    <h2><?= $editar ? "Editar horario" : "Nuevo horario" ?></h2>
    <label for="id_servicio">Servicio</label>
    <select id="id_servicio" name="id_servicio" required>
        <?php foreach ($servicios as $s): ?>
            <option value="<?= (int)$s["id_servicio"] ?>" <?= (int)($editar["id_servicio"] ?? 0) === (int)$s["id_servicio"] ? "selected" : "" ?>><?= e($s["nombre"]) ?></option>
        <?php endforeach; ?>
    </select>
    <label for="fecha">Fecha</label>
    <input type="date" id="fecha" name="fecha" value="<?= e($editar["fecha"] ?? "") ?>" required>
    <label for="hora_inicio">Hora de inicio</label>
    <input type="time" id="hora_inicio" name="hora_inicio" value="<?= e(substr($editar["hora_inicio"] ?? "", 0, 5)) ?>" required>
    <label for="hora_fin">Hora de finalización</label>
    <input type="time" id="hora_fin" name="hora_fin" value="<?= e(substr($editar["hora_fin"] ?? "", 0, 5)) ?>" required>
    <label for="cupos_total">Cupos totales</label>
    <input type="number" id="cupos_total" name="cupos_total" min="1" step="1" value="<?= e($editar["cupos_total"] ?? "") ?>" required>
    <button type="submit" class="boton">Guardar</button>
    <?php if ($editar): ?><a href="horarios.php">Cancelar</a><?php endif; ?>
</form>

<div class="tabla-scroll">
<table>
    <thead><tr><th>Servicio</th><th>Fecha</th><th>Inicio</th><th>Fin</th><th>Cupos totales</th><th>Cupos disponibles</th><th>Estado</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($lista as $h): ?>
        <tr>
            <td><?= e($h["servicio"]) ?></td>
            <td><?= e($h["fecha"]) ?></td>
            <td><?= e(substr($h["hora_inicio"], 0, 5)) ?></td>
            <td><?= e(substr($h["hora_fin"], 0, 5)) ?></td>
            <td><?= (int)$h["cupos_total"] ?></td>
            <td><?= (int)$h["cupos_disponibles"] ?></td>
            <td><?= $h["estado"] ? "Activo" : "Inactivo" ?></td>
            <td>
                <a href="horarios.php?editar=<?= (int)$h["id_horario"] ?>">Editar</a>
                <form method="post" action="horarios.php" class="en-linea">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="id" value="<?= (int)$h["id_horario"] ?>">
                    <?php if ($h["estado"]): ?>
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
