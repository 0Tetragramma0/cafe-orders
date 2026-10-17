<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
checkAuth();
checkRole('admin');

$startDate = $_GET['start'] ?? date('Y-m-01');
$endDate = $_GET['end'] ?? date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT d.name, SUM(oi.quantity) AS total_qty, SUM(oi.quantity * oi.price_at_moment) AS total_sum
    FROM order_items oi
    JOIN dishes d ON oi.dish_id = d.id
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY d.id
    ORDER BY total_qty DESC
    LIMIT 10
");
$stmt->execute([$startDate, $endDate]);
$rows = $stmt->fetchAll();
?>
<?php include '../includes/header.php'; ?>

<h1>Популярность блюд (ТОП-10)</h1>

<form method="get" class="filter-form">
    <label>С: <input type="date" name="start" value="<?= htmlspecialchars($startDate) ?>"></label>
    <label>По: <input type="date" name="end" value="<?= htmlspecialchars($endDate) ?>"></label>
    <button type="submit">Сформировать</button>
</form>

<table class="data-table">
    <thead>
        <tr><th>#</th><th>Блюдо</th><th>Продано, шт.</th><th>Сумма</th></tr>
    </thead>
    <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="4">Нет данных за период</td></tr>
        <?php else: ?>
            <?php foreach ($rows as $i => $row): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= $row['total_qty'] ?></td>
                    <td><?= number_format($row['total_sum'], 2) ?> ₽</td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php include '../includes/footer.php'; ?>