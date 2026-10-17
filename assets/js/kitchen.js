let updateInterval = null;
let lastOrdersCount = 0;

document.addEventListener('DOMContentLoaded', function() {
    refreshOrders();
    updateInterval = setInterval(refreshOrders, 10000);
});

function refreshOrders() {
    fetch('/api/kitchen/orders.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateOrdersList(data.orders);
                checkForNewOrders(data.orders.length);
            }
        })
        .catch(error => console.error('Ошибка загрузки заказов:', error));
}

function updateOrdersList(orders) {
    const container = document.getElementById('orders-container');
    if (!container) return;
    
    if (orders.length === 0) {
        container.innerHTML = '<div class="no-orders">Нет активных заказов</div>';
        return;
    }
    
    let html = '';
    orders.forEach(order => {
        html += `
            <div class="order-card" data-order-id="${order.id}">
                <div class="order-header">
                    <span>Заказ №${order.id}</span>
                    <span>Столик №${order.table_number}</span>
                    <span>${formatTime(order.created_at)}</span>
                </div>
                <div class="order-items">${order.items}</div>
                
                <!-- ВОТ ЭТОТ БЛОК НУЖНО ДОБАВИТЬ/ПРОВЕРИТЬ -->
                <div class="order-actions" style="margin-top: 10px; display: flex; gap: 10px;">
                    <a href="order-details.php?id=${order.id}" class="btn-primary" style="text-decoration: none; padding: 6px 12px; font-size: 14px;">Детали / блюда</a>
                    <button class="btn-ready-order" data-order-id="${order.id}" style="padding: 6px 12px; font-size: 14px;">Весь заказ готов</button>
                </div>
                <!-- ---------------------------------------------- -->
                
            </div>
        `;
    });
    container.innerHTML = html;

    // Навешиваем обработчики заново после обновления DOM
    document.querySelectorAll('.btn-ready-order').forEach(btn => {
        btn.addEventListener('click', () => markOrderReady(btn.dataset.orderId));
    });
}

function checkForNewOrders(newCount) {
    if (newCount > lastOrdersCount && lastOrdersCount !== 0) {
        playNotificationSound();
    }
    lastOrdersCount = newCount;
}

function playNotificationSound() {
    try {
        const audio = new Audio('/cafe_orders/assets/sounds/notification.mp3');
        audio.play().catch(e => console.log('Звук не воспроизведён:', e));
    } catch (e) {
        console.log('Аудио не поддерживается');
    }
}

function markDishReady(orderId, dishId) {
    fetch('/api/kitchen/ready.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_id: orderId, dish_id: dishId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            refreshOrders();
            showToast('Блюдо готово', 'success');
        }
    });
}

function markOrderReady(orderId) {
    if (!confirm('Отметить весь заказ как готовый?')) return;

    fetch('/api/kitchen/ready.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_id: orderId }) // без dish_id — значит весь заказ
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            refreshOrders();
            showToast('Заказ отмечен как готовый', 'success');
        } else {
            showToast('Ошибка: ' + data.error, 'error');
        }
    });
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed; bottom: 20px; right: 20px;
        padding: 12px 20px; background: ${type === 'success' ? '#28a745' : '#dc3545'};
        color: white; border-radius: 8px; z-index: 1000;
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

/**
 * Форматирование времени
 * @param {string} datetime Строка даты и времени
 * @returns {string} Время в формате ЧЧ:ММ
 */
function formatTime(datetime) {
    if (!datetime) return '';
    // Заменяем пробел на 'T' для корректного парсинга в Safari
    const date = new Date(datetime.replace(' ', 'T'));
    return date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
}