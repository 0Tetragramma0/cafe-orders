<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
checkAuth();
checkRole('admin');

// Метрики за сегодня
$today = date('Y-m-d');

// Выручка сегодня
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE status = 'completed' AND DATE(created_at) = ?");
$stmt->execute([$today]);
$revenueToday = $stmt->fetch()['total'];

// Количество заказов сегодня
$stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM orders WHERE DATE(created_at) = ?");
$stmt->execute([$today]);
$ordersToday = $stmt->fetch()['cnt'];

// Популярное блюдо дня
$stmt = $pdo->prepare("
    SELECT d.name, SUM(oi.quantity) AS total_qty
    FROM order_items oi
    JOIN dishes d ON oi.dish_id = d.id
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) = ?
    GROUP BY d.id
    ORDER BY total_qty DESC
    LIMIT 1
");
$stmt->execute([$today]);
$popularDish = $stmt->fetch();

// Активные официанты сегодня
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT user_id) AS cnt
    FROM orders
    WHERE DATE(created_at) = ?
");
$stmt->execute([$today]);
$activeWaiters = $stmt->fetch()['cnt'];

// Данные для графика за последние 7 дней
$chartData = [];
$chartLabels = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i day"));
    $chartLabels[] = date('d.m', strtotime($day));

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE status = 'completed' AND DATE(created_at) = ?");
    $stmt->execute([$day]);
    $chartData[] = (float)$stmt->fetch()['total'];
}
?>
<?php include '../includes/header.php'; ?>

<h1>Панель администратора</h1>

<div class="metrics-grid">
    <div class="metric-card">
        <div class="metric-title">Выручка сегодня</div>
        <div class="metric-value"><?= number_format($revenueToday, 2) ?> ₽</div>
    </div>
    <div class="metric-card">
        <div class="metric-title">Заказов сегодня</div>
        <div class="metric-value"><?= $ordersToday ?></div>
    </div>
    <div class="metric-card">
        <div class="metric-title">Популярное блюдо</div>
        <div class="metric-value"><?= $popularDish ? htmlspecialchars($popularDish['name']) : '—' ?></div>
    </div>
    <div class="metric-card">
        <div class="metric-title">Активных официантов</div>
        <div class="metric-value"><?= $activeWaiters ?></div>
    </div>
</div>

<h2>Выручка за последние 7 дней</h2>
<canvas id="revenueChart" width="800" height="300"></canvas>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                label: 'Выручка (₽)',
                data: <?= json_encode($chartData) ?>,
                borderColor: '#FF6B35',
                backgroundColor: 'rgba(255,107,53,0.1)',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true } }
        }
    });
</script>

<?php include '../includes/footer.php'; ?>