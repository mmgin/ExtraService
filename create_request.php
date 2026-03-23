<?php
// api/create_request.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

require_once '../config.php';

$user_id = $_SESSION['user_id'];
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$category = $_POST['category'] ?? '';
$priority = $_POST['priority'] ?? 'medium';

$errors = [];

if (empty($title)) {
    $errors[] = 'Тема обязательна';
} elseif (strlen($title) < 3) {
    $errors[] = 'Тема должна содержать минимум 3 символа';
}

if (empty($description)) {
    $errors[] = 'Описание обязательно';
} elseif (strlen($description) < 10) {
    $errors[] = 'Описание должно содержать минимум 10 символов';
}

if (empty($category)) {
    $errors[] = 'Категория обязательна';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO requests (user_id, title, description, category, priority, status) 
        VALUES (?, ?, ?, ?, ?, 'pending')
    ");
    
    $stmt->execute([$user_id, $title, $description, $category, $priority]);
    $request_id = $pdo->lastInsertId();
    
    // Добавляем запись в историю
    $stmt = $pdo->prepare("
        INSERT INTO request_status_history (request_id, old_status, new_status, changed_by, changed_by_type) 
        VALUES (?, NULL, 'pending', ?, 'user')
    ");
    $stmt->execute([$request_id, $user_id]);
    
    echo json_encode(['success' => true, 'message' => 'Заявка успешно создана', 'id' => $request_id]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>