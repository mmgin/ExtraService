<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $category = $_POST['category'] ?? '';
        $priority = $_POST['priority'] ?? 'medium';
        $created_by = $_POST['created_by'] ?? 'Аноним';
        
        if (empty($title) || empty($description) || empty($category)) {
            throw new Exception('Все поля обязательны для заполнения');
        }
        
        $sql = "INSERT INTO requests (title, description, category, priority, created_by, status) 
                VALUES (?, ?, ?, ?, ?, 'new')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $description, $category, $priority, $created_by]);
        
        echo json_encode([
            'success' => true,
            'id' => $pdo->lastInsertId(),
            'message' => 'Заявка успешно создана'
        ]);
        
    } catch(Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не поддерживается']);
}
?>