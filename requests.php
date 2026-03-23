<?php

$host = 'localhost';
$dbname = 'extra_service';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}


function getAllRequests($pdo) {
    $stmt = $pdo->query("SELECT * FROM requests ORDER BY created_at DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function getRequestById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM requests WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}


function createRequest($pdo, $data) {
    $sql = "INSERT INTO requests (title, category, priority, status, created_by, description) 
            VALUES (:title, :category, :priority, :status, :created_by, :description)";
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($data);
}


function updateRequest($pdo, $id, $data) {
    $sql = "UPDATE requests SET 
            title = :title, 
            category = :category, 
            priority = :priority, 
            status = :status, 
            description = :description,
            admin_comment = :admin_comment
            WHERE id = :id";
    
    $data['id'] = $id;
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($data);
}


function deleteRequest($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM requests WHERE id = ?");
    return $stmt->execute([$id]);
}


function filterRequests($pdo, $status = null, $priority = null, $category = null) {
    $sql = "SELECT * FROM requests WHERE 1=1";
    $params = [];
    
    if ($status) {
        $sql .= " AND status = ?";
        $params[] = $status;
    }
    
    if ($priority) {
        $sql .= " AND priority = ?";
        $params[] = $priority;
    }
    
    if ($category) {
        $sql .= " AND category = ?";
        $params[] = $category;
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function getRequestsStats($pdo) {
    $stats = [];
    
    
    $stats['total'] = $pdo->query("SELECT COUNT(*) FROM requests")->fetchColumn();
    
 
    $statuses = ['new', 'in_progress', 'resolved', 'closed'];
    foreach ($statuses as $status) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE status = ?");
        $stmt->execute([$status]);
        $stats['by_status'][$status] = $stmt->fetchColumn();
    }
    
   
    $priorities = ['low', 'medium', 'high', 'critical'];
    foreach ($priorities as $priority) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE priority = ?");
        $stmt->execute([$priority]);
        $stats['by_priority'][$priority] = $stmt->fetchColumn();
    }
    
    return $stats;
}
?>