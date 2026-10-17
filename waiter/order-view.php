<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
checkAuth();
checkRole('waiter');

$orderId = (int)($_GET['id'] ?? 0);

// Получаем заказ
$stmt = $pdo->prepare("
    SELECT o.*, t.number AS table_number, u.full_name AS waiter_name
    FROM orders o
    JOIN tables t ON o.table_id = t.id
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    die('Заказ не найден');
}

// Позиции заказа
$stmt = $pdo->prepare("
    SELECT oi.*, d.name, d.cooking_time
    FROM order_items oi
    JOIN dishes d ON oi.dish_id = d.id
    WHERE oi.order_id = ?
    ORDER BY oi.id
");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll();

// Статусные метки
$statusLabels = [
    'draft' => 'Черновик',
    'in_progress' => 'Готовится',
    'ready' => 'Готов',
    'paid' => 'Оплачен',
    'closed' => 'Закрыт'
];
?>
<?php include '../includes/header.php'; ?>

<h1>Заказ №<?= $orderId ?></h1>

<div class="order-info">
    <p><strong>Столик:</strong> №<?= $order['table_number'] ?></p>
    <p><strong>Официант:</strong> <?= htmlspecialchars($order['waiter_name']) ?></p>
    <p><strong>Создан:</strong> <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></p>
    <p><strong>Статус:</strong> <span class="status-<?= $order['status'] ?>"><?= $statusLabels[$order['status']] ?? $order['status'] ?></span></p>
    <?php if ($order['closed_at']): ?>
        <p><strong>Закрыт:</strong> <?= date('d.m.Y H:i', strtotime($order['closed_at'])) ?></p>
    <?php endif; ?>
</div>

<h2>Состав заказа</h2>
<table class="data-table">
    <thead>
        <tr><th>Блюдо</th><th>Кол-во</th><th>Цена</th><th>Сумма</th><th>Готово</th></tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td><?= number_format($item['price_at_moment'], 2) ?> ₽</td>
                <td><?= number_format($item['quantity'] * $item['price_at_moment'], 2) ?> ₽</td>
                <td><?= !empty($item['is_ready']) ? '✅' : '⏳' ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3"><strong>Итого:</strong></td>
            <td colspan="2"><strong><?= number_format($order['total'], 2) ?> ₽</strong></td>
        </tr>
    </tfoot>
</table>

<div class="actions">
    <?php if ($order['status'] === 'draft' || $order['status'] === 'in_progress'): ?>
        <a href="order-edit.php?id=<?= $orderId ?>" class="btn-primary">Редактировать</a>
    <?php endif; ?>

    <?php if ($order['status'] === 'ready'): ?>
        <a href="payment.php?order_id=<?= $orderId ?>" class="btn-primary">Оплатить</a>
    <?php endif; ?>

    <a href="dashboard.php" class="btn-back">← Назад</a>
</div>

<?php include '../includes/footer.php'; ?>