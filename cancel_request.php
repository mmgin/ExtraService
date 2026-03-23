<?php
// api/cancel_request.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

require_once '../config.php';

$data = json_decode(file_get_contents('php://input'), true);
$request_id = $data['id'] ?? 0;
$user_id = $_SESSION['user_id'];

try {
    // Проверяем, что заявка принадлежит пользователю и имеет статус pending
    $stmt = $pdo->prepare("
        SELECT status FROM requests 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$request_id, $user_id]);
    $request = $stmt->fetch();
    
    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Заявка не найдена']);
        exit;
    }
    
    if ($request['status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Нельзя отменить заявку в статусе ' . $request['status']]);
        exit;
    }
    
    // Обновляем статус
    $stmt = $pdo->prepare("
        UPDATE requests SET status = 'cancelled' 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$request_id, $user_id]);
    
    // Добавляем запись в историю
    $stmt = $pdo->prepare("
        INSERT INTO request_status_history (request_id, old_status, new_status, changed_by, changed_by_type) 
        VALUES (?, 'pending', 'cancelled', ?, 'user')
    ");
    $stmt->execute([$request_id, $user_id]);
    
    echo json_encode(['success' => true, 'message' => 'Заявка отменена']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных']);
}
?>