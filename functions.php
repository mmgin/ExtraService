<?php
// functions.php
// Все функции для работы с заявками

/**
 * Получение текста статуса на русском
 */
function getStatusText($status) {
    $texts = [
        'pending' => 'Ожидает обработки',
        'in_progress' => 'В работе',
        'completed' => 'Выполнена',
        'rejected' => 'Отклонена',
        'cancelled' => 'Отменена'
    ];
    return $texts[$status] ?? $status;
}

/**
 * Получение цвета статуса
 */
function getStatusColor($status) {
    $colors = [
        'pending' => '#f39c12',
        'in_progress' => '#3498db',
        'completed' => '#27ae60',
        'rejected' => '#e74c3c',
        'cancelled' => '#95a5a6'
    ];
    return $colors[$status] ?? '#95a5a6';
}

/**
 * Получение текста приоритета на русском
 */
function getPriorityText($priority) {
    $texts = [
        'low' => 'Низкий',
        'medium' => 'Средний',
        'high' => 'Высокий',
        'urgent' => 'Срочный'
    ];
    return $texts[$priority] ?? $priority;
}

/**
 * Получение цвета приоритета
 */
function getPriorityColor($priority) {
    $colors = [
        'low' => '#95a5a6',
        'medium' => '#3498db',
        'high' => '#e67e22',
        'urgent' => '#e74c3c'
    ];
    return $colors[$priority] ?? '#95a5a6';
}
?>