<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
checkAuth();
checkRole('admin');

$message = '';

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Добавление блюда
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $time = (int)($_POST['cooking_time'] ?? 10);

        if ($name !== '' && $price > 0) {
            $stmt = $pdo->prepare("INSERT INTO dishes (name, price, cooking_time, is_available) VALUES (?, ?, ?, 1)");
            $stmt->execute([$name, $price, $time]);
            $message = 'Блюдо добавлено';
        } else {
            $message = 'Заполните название и цену';
        }
    }

    // Редактирование блюда
    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $time = (int)($_POST['cooking_time'] ?? 10);
        $available = isset($_POST['is_available']) ? 1 : 0;

        $stmt = $pdo->prepare("UPDATE dishes SET name = ?, price = ?, cooking_time = ?, is_available = ? WHERE id = ?");
        $stmt->execute([$name, $price, $time, $available, $id]);
        $message = 'Блюдо обновлено';
    }

    // Удаление
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM dishes WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Блюдо удалено';
    }
}

// Получение списка блюд
$dishes = $pdo->query("SELECT * FROM dishes ORDER BY name")->fetchAll();

// Блюдо для редактирования
$editDish = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM dishes WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editDish = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление меню</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <main>
        <h1>Управление меню</h1>

        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Форма добавления / редактирования -->
        <form method="post" class="dish-form">
            <h2><?= $editDish ? 'Редактировать блюдо' : 'Добавить блюдо' ?></h2>
            <input type="hidden" name="action" value="<?= $editDish ? 'edit' : 'add' ?>">
            <?php if ($editDish): ?>
                <input type="hidden" name="id" value="<?= $editDish['id'] ?>">
            <?php endif; ?>

            <label>Название:
                <input type="text" name="name" required value="<?= htmlspecialchars($editDish['name'] ?? '') ?>">
            </label>
            <label>Цена, ₽:
                <input type="number" name="price" step="0.01" min="0" required value="<?= $editDish['price'] ?? '' ?>">
            </label>
            <label>Время приготовления, мин:
                <input type="number" name="cooking_time" min="0" value="<?= $editDish['cooking_time'] ?? 10 ?>">
            </label>
            <?php if ($editDish): ?>
                <label>
                    <input type="checkbox" name="is_available" <?= $editDish['is_available'] ? 'checked' : '' ?>>
                    Доступно в меню
                </label>
            <?php endif; ?>

            <button type="submit"><?= $editDish ? 'Сохранить' : 'Добавить' ?></button>
            <?php if ($editDish): ?>
                <a href="dishes.php">Отмена</a>
            <?php endif; ?>
        </form>

        <!-- Таблица блюд -->
        <h2>Список блюд</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th><th>Название</th><th>Цена</th><th>Время</th><th>Доступно</th><th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dishes as $dish): ?>
                    <tr>
                        <td><?= $dish['id'] ?></td>
                        <td><?= htmlspecialchars($dish['name']) ?></td>
                        <td><?= number_format($dish['price'], 2) ?> ₽</td>
                        <td><?= $dish['cooking_time'] ?> мин</td>
                        <td><?= $dish['is_available'] ? 'Да' : 'Нет' ?></td>
                        <td>
                            <a href="dishes.php?edit=<?= $dish['id'] ?>">✏️</a>
                            <form method="post" style="display:inline" onsubmit="return confirm('Удалить блюдо?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $dish['id'] ?>">
                                <button type="submit">🗑️</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
    <?php include '../includes/footer.php'; ?>
</body>
</html>