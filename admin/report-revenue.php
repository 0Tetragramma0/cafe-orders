<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
checkAuth();
checkRole('admin');

// Период по умолчанию — текущий месяц
$startDate = $_GET['start'] ?? date('Y-m-01');
$endDate = $_GET['end'] ?? date('Y-m-d');

// Выручка по дням
$stmt = $pdo->prepare("
    SELECT DATE(created_at) AS day, SUM(amount) AS total, COUNT(*) AS cnt
    FROM payments
    WHERE status = 'completed' AND DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY day DESC
");
$stmt->execute([$startDate, $endDate]);
$rows = $stmt->fetchAll();

// Итоговая выручка
$totalRevenue = array_sum(array_column($rows, 'total'));

// Разбивка по способам оплаты
$stmt = $pdo->prepare("
    SELECT type, SUM(amount) AS total
    FROM payments
    WHERE status = 'completed' AND DATE(created_at) BETWEEN ? AND ?
    GROUP BY type
");
$stmt->execute([$startDate, $endDate]);
$byType = $stmt->fetchAll();
?>
<?php include '../includes/header.php'; ?>

<h1>Отчёт по выручке</h1>

<form method="get" class="filter-form">
    <label>С: <input type="date" name="start" value="<?= htmlspecialchars($startDate) ?>"></label>
    <label>По: <input type="date" name="end" value="<?= htmlspecialchars($endDate) ?>"></label>
    <button type="submit">Сформировать</button>
</form>

<div class="metric-card">
    <div class="metric-title">Итого за период</div>
    <div class="metric-value"><?= number_format($totalRevenue, 2) ?> ₽</div>
</div>

<h2>Разбивка по способам оплаты</h2>
<table class="data-table">
    <thead><tr><th>Способ оплаты</th><th>Сумма</th></tr></thead>
    <tbody>
        <?php foreach ($byType as $row): ?>
            <tr>
                <td><?= $row['type'] === 'cash' ? 'Наличные' : 'Карта' ?></td>
                <td><?= number_format($row['total'], 2) ?> ₽</td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h2>Выручка по дням</h2>
<table class="data-table">
    <thead><tr><th>Дата</th><th>Количество платежей</th><th>Сумма</th></tr></thead>
    <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="3">Нет данных за период</td></tr>
        <?php else: ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= date('d.m.Y', strtotime($row['day'])) ?></td>
                    <td><?= $row['cnt'] ?></td>
                    <td><?= number_format($row['total'], 2) ?> ₽</td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php include '../includes/footer.php'; ?>