<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
checkAuth();
checkRole('waiter');

$message = '';

// Обработка отправки заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_to_kitchen'])) {
    $tableId = (int)$_POST['table_id'];
    $items = json_decode($_POST['items_json'] ?? '[]', true);

    if ($tableId > 0 && !empty($items)) {
        try {
            $pdo->beginTransaction();

            // Создание заказа
            $stmt = $pdo->prepare("INSERT INTO orders (table_id, user_id, status) VALUES (?, ?, 'in_progress')");
            $stmt->execute([$tableId, $_SESSION['user_id']]);
            $orderId = $pdo->lastInsertId();

            // Добавление позиций
            $total = 0;
            $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, dish_id, quantity, price_at_moment) VALUES (?, ?, ?, ?)");
            foreach ($items as $item) {
                $dishId = (int)$item['dish_id'];
                $qty = (int)$item['quantity'];
                $price = (float)$item['price'];
                $stmtItem->execute([$orderId, $dishId, $qty, $price]);
                $total += $qty * $price;
            }

            // Обновление суммы заказа и статуса столика
            $pdo->prepare("UPDATE orders SET total = ? WHERE id = ?")->execute([$total, $orderId]);
            $pdo->prepare("UPDATE tables SET status = 'occupied' WHERE id = ?")->execute([$tableId]);

            $pdo->commit();
            $message = "Заказ №{$orderId} отправлен на кухню";
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = 'Ошибка: ' . $e->getMessage();
        }
    } else {
        $message = 'Выберите столик и добавьте блюда';
    }
}

// Свободные столики
$tables = $pdo->query("SELECT * FROM tables WHERE status = 'free' ORDER BY number")->fetchAll();

// Доступные блюда
$dishes = $pdo->query("SELECT * FROM dishes WHERE is_available = 1 ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Новый заказ</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <main>
        <h1>Новый заказ</h1>

        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="post" id="order-form">
            <input type="hidden" name="items_json" id="items_json">

            <label>Столик:
                <select name="table_id" required>
                    <option value="">— выберите —</option>
                    <?php foreach ($tables as $t): ?>
                        <option value="<?= $t['id'] ?>">№<?= $t['number'] ?> (<?= $t['capacity'] ?> мест)</option>
                    <?php endforeach; ?>
                </select>
            </label>

            <h2>Меню</h2>
            <div class="dish-list">
                <?php foreach ($dishes as $d): ?>
                    <div class="dish-item">
                        <span><?= htmlspecialchars($d['name']) ?> — <?= number_format($d['price'], 2) ?> ₽</span>
                        <button type="button"
                                onclick="addDish(<?= $d['id'] ?>, '<?= htmlspecialchars($d['name'], ENT_QUOTES) ?>', <?= $d['price'] ?>)">
                            + Добавить
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>

            <h2>Корзина</h2>
            <table class="cart-table">
                <thead>
                    <tr><th>Блюдо</th><th>Кол-во</th><th>Цена</th><th>Сумма</th><th></th></tr>
                </thead>
                <tbody id="cart-body"></tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"><strong>Итого:</strong></td>
                        <td colspan="2"><strong id="total">0 ₽</strong></td>
                    </tr>
                </tfoot>
            </table>

            <button type="submit" name="send_to_kitchen" onclick="return prepareOrder()">Отправить на кухню</button>
            <a href="dashboard.php">Отмена</a>
        </form>
    </main>

    <script>
        const cart = [];

        function addDish(id, name, price) {
            const existing = cart.find(item => item.dish_id === id);
            if (existing) {
                existing.quantity++;
            } else {
                cart.push({ dish_id: id, name: name, price: price, quantity: 1 });
            }
            renderCart();
        }

        function removeDish(index) {
            cart.splice(index, 1);
            renderCart();
        }

        function changeQty(index, delta) {
            cart[index].quantity += delta;
            if (cart[index].quantity <= 0) cart.splice(index, 1);
            renderCart();
        }

        function renderCart() {
            const body = document.getElementById('cart-body');
            const totalEl = document.getElementById('total');
            body.innerHTML = '';
            let total = 0;

            cart.forEach((item, i) => {
                const subtotal = item.price * item.quantity;
                total += subtotal;
                body.innerHTML += `
                    <tr>
                        <td>${item.name}</td>
                        <td>
                            <button type="button" onclick="changeQty(${i}, -1)">-</button>
                            ${item.quantity}
                            <button type="button" onclick="changeQty(${i}, 1)">+</button>
                        </td>
                        <td>${item.price.toFixed(2)} ₽</td>
                        <td>${subtotal.toFixed(2)} ₽</td>
                        <td><button type="button" onclick="removeDish(${i})">🗑️</button></td>
                    </tr>
                `;
            });

            totalEl.textContent = total.toFixed(2) + ' ₽';
        }

        function prepareOrder() {
            if (cart.length === 0) {
                alert('Добавьте хотя бы одно блюдо');
                return false;
            }
            document.getElementById('items_json').value = JSON.stringify(cart);
            return true;
        }
    </script>
</body>
</html>