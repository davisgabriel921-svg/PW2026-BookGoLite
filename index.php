<?php
$titulo = "Catálogo";
require "inc/init.php";

$q = trim($_GET["q"] ?? "");
$cat = (int)($_GET["categoria"] ?? 0);

$sql = "SELECT s.*, c.nombre AS categoria FROM servicios s
        JOIN categorias c ON c.id_categoria = s.id_categoria
        WHERE s.estado = 1 AND c.estado = 1";
$params = [];
if ($q !== "") {
    $sql .= " AND s.nombre LIKE ?";
    $params[] = "%" . $q . "%";
}
if ($cat > 0) {
    $sql .= " AND s.id_categoria = ?";
    $params[] = $cat;
}
$sql .= " ORDER BY s.nombre";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$servicios = $stmt->fetchAll();

$categorias = $pdo->query("SELECT id_categoria, nombre FROM categorias WHERE estado = 1 ORDER BY nombre")->fetchAll();

require "inc/header.php";
?>
<h1>Servicios de soporte técnico</h1>
<form method="get" action="index.php" class="filtros">
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Buscar servicio">
    <select name="categoria">
        <option value="0">Todas las categorías</option>
        <?php foreach ($categorias as $c): ?>
            <option value="<?= (int)$c["id_categoria"] ?>" <?= $cat === (int)$c["id_categoria"] ? "selected" : "" ?>><?= e($c["nombre"]) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="boton">Buscar</button>
</form>

<?php if (!$servicios): ?>
    <p class="aviso">No se encontraron servicios.</p>
<?php else: ?>
    <div class="tarjetas">
        <?php foreach ($servicios as $s): ?>
            <article class="tarjeta">
                <p class="etiqueta"><?= e($s["categoria"]) ?></p>
                <h3><a href="servicio.php?id=<?= (int)$s["id_servicio"] ?>"><?= e($s["nombre"]) ?></a></h3>
                <p><?= e($s["descripcion"]) ?></p>
                <p class="precio">$<?= number_format($s["precio"], 2) ?> · <?= (int)$s["duracion_min"] ?> min</p>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php require "inc/footer.php"; ?>
