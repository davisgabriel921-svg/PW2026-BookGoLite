<?php
$titulo = "Detalle del servicio";
require "inc/init.php";

$id = (int)($_GET["id"] ?? 0);
$stmt = $pdo->prepare("SELECT s.*, c.nombre AS categoria FROM servicios s
    JOIN categorias c ON c.id_categoria = s.id_categoria
    WHERE s.id_servicio = ? AND s.estado = 1 AND c.estado = 1");
$stmt->execute([$id]);
$serv = $stmt->fetch();
if (!$serv) {
    http_response_code(404);
    die("Servicio no encontrado.");
}

$msg = "";
$err = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["accion"] ?? "") === "reservar") {
    requerir_rol("CLIENTE");
    csrf_validar();
    $idh = (int)($_POST["id_horario"] ?? 0);
    $uid = $_SESSION["usuario"]["id"];

    try {
        $pdo->beginTransaction();
        $q = $pdo->prepare("SELECT * FROM horarios WHERE id_horario = ? AND id_servicio = ? AND estado = 1 FOR UPDATE");
        $q->execute([$idh, $id]);
        $h = $q->fetch();
        if (!$h || !es_futuro($h["fecha"], $h["hora_inicio"])) {
            throw new Exception("El horario seleccionado no está disponible.");
        }
        $q = $pdo->prepare("SELECT COUNT(*) FROM reservas WHERE id_usuario = ? AND id_horario = ? AND estado IN ('PENDIENTE','CONFIRMADA')");
        $q->execute([$uid, $idh]);
        if ($q->fetchColumn() > 0) {
            throw new Exception("Ya tiene una reserva activa para este horario.");
        }
        $q = $pdo->prepare("UPDATE horarios SET cupos_disponibles = cupos_disponibles - 1 WHERE id_horario = ? AND cupos_disponibles > 0");
        $q->execute([$idh]);
        if ($q->rowCount() !== 1) {
            throw new Exception("Ya no quedan cupos en ese horario.");
        }
        $q = $pdo->prepare("INSERT INTO reservas (id_usuario, id_horario, estado) VALUES (?, ?, 'PENDIENTE')");
        $q->execute([$uid, $idh]);
        $pdo->commit();
        $msg = "Reserva creada. Quedó en estado PENDIENTE.";
    } catch (Exception $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($ex instanceof PDOException) {
            error_log($ex->getMessage());
            $err = "No se pudo crear la reserva.";
        } else {
            $err = $ex->getMessage();
        }
    }
}

$q = $pdo->prepare("SELECT * FROM horarios WHERE id_servicio = ? AND estado = 1 ORDER BY fecha, hora_inicio");
$q->execute([$id]);
$horarios = array_filter($q->fetchAll(), function ($h) {
    return es_futuro($h["fecha"], $h["hora_inicio"]);
});

$q = $pdo->prepare("SELECT rs.calificacion, rs.comentario, rs.fecha, u.nombre
    FROM resenas rs
    JOIN reservas r ON r.id_reserva = rs.id_reserva
    JOIN horarios h ON h.id_horario = r.id_horario
    JOIN usuarios u ON u.id_usuario = rs.id_usuario
    WHERE h.id_servicio = ? ORDER BY rs.fecha DESC");
$q->execute([$id]);
$resenas = $q->fetchAll();

$u = usuario_actual();
require "inc/header.php";
?>
<p><a href="index.php">&laquo; Volver al catálogo</a></p>
<h1><?= e($serv["nombre"]) ?></h1>
<p class="etiqueta"><?= e($serv["categoria"]) ?></p>
<p><?= e($serv["descripcion"]) ?></p>
<p class="precio">$<?= number_format($serv["precio"], 2) ?> · <?= (int)$serv["duracion_min"] ?> min</p>

<?php if ($msg !== ""): ?><p class="ok"><?= e($msg) ?></p><?php endif; ?>
<?php if ($err !== ""): ?><p class="error"><?= e($err) ?></p><?php endif; ?>

<h2>Horarios disponibles</h2>
<?php if (!$horarios): ?>
    <p class="aviso">No hay horarios disponibles por ahora.</p>
<?php else: ?>
<div class="tabla-scroll">
<table>
    <thead><tr><th>Fecha</th><th>Inicio</th><th>Fin</th><th>Cupos totales</th><th>Cupos disponibles</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($horarios as $h): ?>
        <tr>
            <td><?= e($h["fecha"]) ?></td>
            <td><?= e(substr($h["hora_inicio"], 0, 5)) ?></td>
            <td><?= e(substr($h["hora_fin"], 0, 5)) ?></td>
            <td><?= (int)$h["cupos_total"] ?></td>
            <td><?= (int)$h["cupos_disponibles"] ?></td>
            <td>
                <?php if ($h["cupos_disponibles"] <= 0): ?>
                    Agotado
                <?php elseif ($u && $u["rol"] === "CLIENTE"): ?>
                    <form method="post" action="servicio.php?id=<?= $id ?>" class="en-linea">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="accion" value="reservar">
                        <input type="hidden" name="id_horario" value="<?= (int)$h["id_horario"] ?>">
                        <button type="submit" class="enlace">Reservar</button>
                    </form>
                <?php elseif (!$u): ?>
                    <a href="login.php">Inicie sesión para reservar</a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

<h2>Reseñas</h2>
<?php if (!$resenas): ?>
    <p class="aviso">Este servicio aún no tiene reseñas.</p>
<?php else: ?>
    <?php foreach ($resenas as $r): ?>
        <article class="tarjeta resena">
            <p class="precio"><?= e($r["nombre"]) ?> · <?= (int)$r["calificacion"] ?>/5</p>
            <p><?= e($r["comentario"]) ?></p>
        </article>
    <?php endforeach; ?>
<?php endif; ?>
<?php require "inc/footer.php"; ?>
