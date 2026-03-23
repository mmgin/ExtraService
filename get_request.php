<?php
// api/get_request.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

require_once '../config.php';

$request_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT * FROM requests 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$request_id, $user_id]);
    $request = $stmt->fetch();
    
    if ($request) {
        echo json_encode(['success' => true, 'request' => $request]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Заявка не найдена']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных']);
}
?>