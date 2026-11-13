<?php
$titulo = "Mis reservas";
require "inc/init.php";
requerir_rol("CLIENTE");
$uid = $_SESSION["usuario"]["id"];

$msg = "";
$err = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_validar();
    $accion = $_POST["accion"] ?? "";
    $idr = (int)($_POST["id_reserva"] ?? 0);

    if ($accion === "cancelar") {
        try {
            $pdo->beginTransaction();
            $q = $pdo->prepare("SELECT r.id_horario, r.estado, h.fecha, h.hora_inicio FROM reservas r
                JOIN horarios h ON h.id_horario = r.id_horario
                WHERE r.id_reserva = ? AND r.id_usuario = ? FOR UPDATE");
            $q->execute([$idr, $uid]);
            $r = $q->fetch();
            if (!$r) {
                throw new Exception("Reserva no encontrada.");
            }
            if (!in_array($r["estado"], ["PENDIENTE", "CONFIRMADA"], true)) {
                throw new Exception("Esta reserva ya no se puede cancelar.");
            }
            if (!es_futuro($r["fecha"], $r["hora_inicio"])) {
                throw new Exception("La cita ya comenzó y no se puede cancelar.");
            }
            $q = $pdo->prepare("UPDATE reservas SET estado = 'CANCELADA' WHERE id_reserva = ? AND estado IN ('PENDIENTE','CONFIRMADA')");
            $q->execute([$idr]);
            if ($q->rowCount() === 1) {
                $pdo->prepare("UPDATE horarios SET cupos_disponibles = cupos_disponibles + 1 WHERE id_horario = ? AND cupos_disponibles < cupos_total")
                    ->execute([$r["id_horario"]]);
            }
            $pdo->commit();
            $msg = "Reserva cancelada.";
        } catch (Exception $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($ex instanceof PDOException) {
                error_log($ex->getMessage());
                $err = "No se pudo cancelar la reserva.";
            } else {
                $err = $ex->getMessage();
            }
        }
    } elseif ($accion === "resenar") {
        $cal = (string)($_POST["calificacion"] ?? "");
        $com = trim($_POST["comentario"] ?? "");
        $q = $pdo->prepare("SELECT estado FROM reservas WHERE id_reserva = ? AND id_usuario = ?");
        $q->execute([$idr, $uid]);
        $r = $q->fetch();
        if (!$r || $r["estado"] !== "REALIZADA") {
            $err = "Solo puede reseñar sus reservas realizadas.";
        } elseif (!ctype_digit($cal) || $cal < 1 || $cal > 5) {
            $err = "La calificación debe estar entre 1 y 5.";
        } elseif ($com === "" || mb_strlen($com) > 500) {
            $err = "Escriba un comentario (máximo 500 caracteres).";
        } else {
            try {
                $pdo->prepare("INSERT INTO resenas (id_reserva, id_usuario, calificacion, comentario) VALUES (?, ?, ?, ?)")
                    ->execute([$idr, $uid, (int)$cal, $com]);
                $msg = "Gracias por su reseña.";
            } catch (PDOException $ex) {
                $err = $ex->getCode() === "23000" ? "Esta reserva ya tiene una reseña." : "No se pudo guardar la reseña.";
            }
        }
    }
}

$q = $pdo->prepare("SELECT r.id_reserva, r.estado, s.nombre AS servicio, h.fecha, h.hora_inicio, h.hora_fin, rs.id_resena
    FROM reservas r
    JOIN horarios h ON h.id_horario = r.id_horario
    JOIN servicios s ON s.id_servicio = h.id_servicio
    LEFT JOIN resenas rs ON rs.id_reserva = r.id_reserva
    WHERE r.id_usuario = ? ORDER BY h.fecha DESC, h.hora_inicio DESC");
$q->execute([$uid]);
$lista = $q->fetchAll();

require "inc/header.php";
?>
<h1>Mis reservas</h1>
<?php if ($msg !== ""): ?><p class="ok"><?= e($msg) ?></p><?php endif; ?>
<?php if ($err !== ""): ?><p class="error"><?= e($err) ?></p><?php endif; ?>

<?php if (!$lista): ?>
    <p class="aviso">Aún no tiene reservas. Explore el <a href="index.php">catálogo</a>.</p>
<?php else: ?>
<div class="tabla-scroll">
<table>
    <thead><tr><th>Servicio</th><th>Fecha</th><th>Horario</th><th>Estado</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($lista as $r): ?>
        <tr>
            <td><?= e($r["servicio"]) ?></td>
            <td><?= e($r["fecha"]) ?></td>
            <td><?= e(substr($r["hora_inicio"], 0, 5)) ?> - <?= e(substr($r["hora_fin"], 0, 5)) ?></td>
            <td><?= e($r["estado"]) ?></td>
            <td>
                <?php if (in_array($r["estado"], ["PENDIENTE", "CONFIRMADA"], true) && es_futuro($r["fecha"], $r["hora_inicio"])): ?>
                    <form method="post" action="reservas.php" class="en-linea">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="accion" value="cancelar">
                        <input type="hidden" name="id_reserva" value="<?= (int)$r["id_reserva"] ?>">
                        <button type="submit" class="enlace">Cancelar</button>
                    </form>
                <?php elseif ($r["estado"] === "REALIZADA" && !$r["id_resena"]): ?>
                    <form method="post" action="reservas.php">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="accion" value="resenar">
                        <input type="hidden" name="id_reserva" value="<?= (int)$r["id_reserva"] ?>">
                        <select name="calificacion" aria-label="Calificación">
                            <option value="5">5</option><option value="4">4</option><option value="3">3</option><option value="2">2</option><option value="1">1</option>
                        </select>
                        <input type="text" name="comentario" maxlength="500" placeholder="Comentario" aria-label="Comentario" required>
                        <button type="submit" class="enlace">Reseñar</button>
                    </form>
                <?php elseif ($r["id_resena"]): ?>
                    Reseñada
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
<?php require "inc/footer.php"; ?>
