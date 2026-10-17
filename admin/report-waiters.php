<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
checkAuth();
checkRole('admin');

$startDate = $_GET['start'] ?? date('Y-m-01');
$endDate = $_GET['end'] ?? date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT u.full_name,
           COUNT(DISTINCT o.id) AS orders_count,
           COALESCE(SUM(p.amount), 0) AS revenue
    FROM users u
    LEFT JOIN orders o ON o.user_id = u.id AND DATE(o.created_at) BETWEEN ? AND ?
    LEFT JOIN payments p ON p.order_id = o.id AND p.status = 'completed'
    WHERE u.role = 'waiter'
    GROUP BY u.id
    ORDER BY orders_count DESC
");
$stmt->execute([$startDate, $endDate]);
$rows = $stmt->fetchAll();
?>
<?php include '../includes/header.php'; ?>

<h1>Активность официантов</h1>

<form method="get" class="filter-form">
    <label>С: <input type="date" name="start" value="<?= htmlspecialchars($startDate) ?>"></label>
    <label>По: <input type="date" name="end" value="<?= htmlspecialchars($endDate) ?>"></label>
    <button type="submit">Сформировать</button>
</form>

<table class="data-table">
    <thead>
        <tr><th>Официант</th><th>Заказов</th><th>Выручка</th></tr>
    </thead>
    <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="3">Нет данных за период</td></tr>
        <?php else: ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= $row['orders_count'] ?></td>
                    <td><?= number_format($row['revenue'], 2) ?> ₽</td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php include '../includes/footer.php'; ?>