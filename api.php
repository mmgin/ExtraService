<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

require_once '../config/database.php';

try {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : null;
    $category = isset($_GET['category']) ? $_GET['category'] : null;
    $priority = isset($_GET['priority']) ? $_GET['priority'] : null;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    
    $sql = "SELECT * FROM requests WHERE 1=1";
    $params = [];
    
    if ($category) {
        $sql .= " AND category = ?";
        $params[] = $category;
    }
    
    if ($priority) {
        $sql .= " AND priority = ?";
        $params[] = $priority;
    }
    
    if ($status) {
        $sql .= " AND status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT ?";
        $params[] = $limit;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($requests);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>