<?php
$raiz = "../";
$titulo = "Reservas";
require "../inc/init.php";
requerir_rol("ADMIN");

$msg = "";
$err = "";
$transiciones = [
    "CONFIRMADA" => ["PENDIENTE"],
    "REALIZADA" => ["CONFIRMADA"],
    "CANCELADA" => ["PENDIENTE", "CONFIRMADA"]
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_validar();
    $idr = (int)($_POST["id_reserva"] ?? 0);
    $nuevo = $_POST["nuevo"] ?? "";

    if (!isset($transiciones[$nuevo])) {
        $err = "Estado no válido.";
    } else {
        try {
            $pdo->beginTransaction();
            $q = $pdo->prepare("SELECT id_horario, estado FROM reservas WHERE id_reserva = ? FOR UPDATE");
            $q->execute([$idr]);
            $r = $q->fetch();
            if (!$r || !in_array($r["estado"], $transiciones[$nuevo], true)) {
                throw new Exception("Ese cambio de estado no está permitido.");
            }
            $pdo->prepare("UPDATE reservas SET estado = ? WHERE id_reserva = ?")->execute([$nuevo, $idr]);
            if ($nuevo === "CANCELADA") {
                $pdo->prepare("UPDATE horarios SET cupos_disponibles = cupos_disponibles + 1 WHERE id_horario = ? AND cupos_disponibles < cupos_total")
                    ->execute([$r["id_horario"]]);
            }
            $pdo->commit();
            $msg = "Estado actualizado a " . $nuevo . ".";
        } catch (Exception $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($ex instanceof PDOException) {
                error_log($ex->getMessage());
                $err = "No se pudo actualizar la reserva.";
            } else {
                $err = $ex->getMessage();
            }
        }
    }
}

$lista = $pdo->query("SELECT r.id_reserva, r.estado, u.nombre, u.apellido, s.nombre AS servicio, h.fecha, h.hora_inicio, h.hora_fin
    FROM reservas r
    JOIN usuarios u ON u.id_usuario = r.id_usuario
    JOIN horarios h ON h.id_horario = r.id_horario
    JOIN servicios s ON s.id_servicio = h.id_servicio
    ORDER BY h.fecha DESC, h.hora_inicio DESC")->fetchAll();

require "../inc/header.php";
?>
<h1>Reservas</h1>
<?php if ($msg !== ""): ?><p class="ok"><?= e($msg) ?></p><?php endif; ?>
<?php if ($err !== ""): ?><p class="error"><?= e($err) ?></p><?php endif; ?>
<?php if (!$lista): ?>
    <p class="aviso">No hay reservas registradas.</p>
<?php else: ?>
<div class="tabla-scroll">
<table>
    <thead><tr><th>Cliente</th><th>Servicio</th><th>Fecha</th><th>Horario</th><th>Estado</th><th>Cambiar a</th></tr></thead>
    <tbody>
    <?php foreach ($lista as $r): ?>
        <tr>
            <td><?= e($r["nombre"] . " " . $r["apellido"]) ?></td>
            <td><?= e($r["servicio"]) ?></td>
            <td><?= e($r["fecha"]) ?></td>
            <td><?= e(substr($r["hora_inicio"], 0, 5)) ?> - <?= e(substr($r["hora_fin"], 0, 5)) ?></td>
            <td><?= e($r["estado"]) ?></td>
            <td>
                <?php foreach ($transiciones as $destino => $origenes): ?>
                    <?php if (in_array($r["estado"], $origenes, true)): ?>
                        <form method="post" action="reservas.php" class="en-linea">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="id_reserva" value="<?= (int)$r["id_reserva"] ?>">
                            <input type="hidden" name="nuevo" value="<?= e($destino) ?>">
                            <button type="submit" class="enlace"><?= e($destino) ?></button>
                        </form>
                    <?php endif; ?>
                <?php endforeach; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
<?php require "../inc/footer.php"; ?>
