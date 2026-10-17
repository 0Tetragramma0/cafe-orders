<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
checkAuth();
checkRole('waiter');

$orderId = (int)($_GET['id'] ?? 0);
$message = '';

// Получаем заказ
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND status IN ('draft','in_progress')");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    die('Заказ не найден или недоступен для редактирования');
}

// Обработка сохранения
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $items = json_decode($_POST['items_json'] ?? '[]', true);

    try {
        $pdo->beginTransaction();

        // Удаляем старые позиции
        $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$orderId]);

        // Добавляем новые
        $total = 0;
        $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, dish_id, quantity, price_at_moment) VALUES (?, ?, ?, ?)");
        foreach ($items as $item) {
            $dishId = (int)$item['dish_id'];
            $qty = (int)$item['quantity'];
            $price = (float)$item['price'];
            $stmtItem->execute([$orderId, $dishId, $qty, $price]);
            $total += $qty * $price;
        }

        // Обновляем сумму
        $pdo->prepare("UPDATE orders SET total = ? WHERE id = ?")->execute([$total, $orderId]);

        $pdo->commit();
        $message = 'Заказ сохранён';
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = 'Ошибка: ' . $e->getMessage();
    }
}

// Текущие позиции заказа
$stmt = $pdo->prepare("
    SELECT oi.*, d.name
    FROM order_items oi
    JOIN dishes d ON oi.dish_id = d.id
    WHERE oi.order_id = ?
");
$stmt->execute([$orderId]);
$currentItems = $stmt->fetchAll();

// Доступные блюда
$dishes = $pdo->query("SELECT * FROM dishes WHERE is_available = 1 ORDER BY name")->fetchAll();
?>
<?php include '../includes/header.php'; ?>

<h1>Редактирование заказа №<?= $orderId ?></h1>

<?php if ($message): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<form method="post" id="order-form">
    <input type="hidden" name="items_json" id="items_json">

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

    <button type="submit" name="save" onclick="return prepareOrder()">Сохранить</button>
    <a href="dashboard.php">Отмена</a>
</form>

<script>
    const cart = <?= json_encode(array_map(function($item) {
        return [
            'dish_id' => (int)$item['dish_id'],
            'name' => $item['name'],
            'price' => (float)$item['price_at_moment'],
            'quantity' => (int)$item['quantity']
        ];
    }, $currentItems)) ?>;

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

    renderCart();
</script>

<?php include '../includes/footer.php'; ?>