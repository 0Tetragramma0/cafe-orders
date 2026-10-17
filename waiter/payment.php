<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
checkAuth();
checkRole('waiter');

$orderId = (int)($_GET['order_id'] ?? 0);
$message = '';

// Обработка оплаты
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay'])) {
    $type = $_POST['type'] ?? 'cash';
    $amount = (float)($_POST['amount'] ?? 0);

    if ($type === 'cash' && $amount < $_POST['total']) {
        $message = 'Внесённая сумма меньше итоговой';
    } else {
        try {
            $pdo->beginTransaction();

            // Получаем сумму заказа
            $stmt = $pdo->prepare("SELECT total, table_id FROM orders WHERE id = ?");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();

            // Создаём платёж
            $pdo->prepare("INSERT INTO payments (order_id, amount, type, status) VALUES (?, ?, ?, 'completed')")
                ->execute([$orderId, $order['total'], $type]);

            // Обновляем статус заказа
            $pdo->prepare("UPDATE orders SET status = 'paid', closed_at = NOW() WHERE id = ?")
                ->execute([$orderId]);

            // Освобождаем столик
            $pdo->prepare("UPDATE tables SET status = 'free' WHERE id = ?")
                ->execute([$order['table_id']]);

            $pdo->commit();
            $message = 'Заказ оплачен';
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = 'Ошибка: ' . $e->getMessage();
        }
    }
}

// Получаем данные заказа
$stmt = $pdo->prepare("
    SELECT o.*, t.number AS table_number
    FROM orders o
    JOIN tables t ON o.table_id = t.id
    WHERE o.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    die('Заказ не найден');
}

// Позиции заказа
$stmt = $pdo->prepare("
    SELECT oi.*, d.name
    FROM order_items oi
    JOIN dishes d ON oi.dish_id = d.id
    WHERE oi.order_id = ?
");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Оплата заказа №<?= $orderId ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <main>
        <h1>Оплата заказа №<?= $orderId ?></h1>
        <p>Столик №<?= $order['table_number'] ?></p>

        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Состав заказа -->
        <table class="data-table">
            <thead>
                <tr><th>Блюдо</th><th>Кол-во</th><th>Цена</th><th>Сумма</th></tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td><?= number_format($item['price_at_moment'], 2) ?> ₽</td>
                        <td><?= number_format($item['quantity'] * $item['price_at_moment'], 2) ?> ₽</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3"><strong>Итого:</strong></td>
                    <td><strong><?= number_format($order['total'], 2) ?> ₽</strong></td>
                </tr>
            </tfoot>
        </table>

        <?php if ($order['status'] === 'paid'): ?>
            <div class="message">Заказ уже оплачен.</div>
            <a href="dashboard.php">Вернуться на рабочий стол</a>
        <?php else: ?>
            <!-- Форма оплаты -->
            <form method="post" class="payment-form">
                <input type="hidden" name="total" value="<?= $order['total'] ?>">

                <label>Способ оплаты:</label>
                <label><input type="radio" name="type" value="cash" checked onchange="toggleAmount()"> Наличные</label>
                <label><input type="radio" name="type" value="card" onchange="toggleAmount()"> Карта</label>

                <div id="cash-block">
                    <label>Внесено:
                        <input type="number" name="amount" step="0.01" min="0" value="<?= $order['total'] ?>">
                    </label>
                    <p>Сдача: <span id="change">0.00</span> ₽</p>
                </div>

                <button type="submit" name="pay">Оплатить</button>
                <a href="dashboard.php">Отмена</a>
            </form>

            <script>
                function toggleAmount() {
                    const type = document.querySelector('input[name="type"]:checked').value;
                    document.getElementById('cash-block').style.display = (type === 'cash') ? 'block' : 'none';
                    updateChange();
                }

                function updateChange() {
                    const total = parseFloat(document.querySelector('input[name="total"]').value);
                    const amountInput = document.querySelector('input[name="amount"]');
                    if (!amountInput) return;
                    const amount = parseFloat(amountInput.value || 0);
                    document.getElementById('change').textContent = (amount - total).toFixed(2);
                }

                document.querySelector('input[name="amount"]')?.addEventListener('input', updateChange);
                toggleAmount();
            </script>
        <?php endif; ?>
    </main>
    <?php include '../includes/footer.php'; ?>
</body>
</html>