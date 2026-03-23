<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Authorization');

require_once '../database.php';

function getUserFromToken($pdo) {
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? '';
    
    if (!preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
        return null;
    }
    
    $token = $matches[1];
    
    $stmt = $pdo->prepare("
        SELECT u.* FROM users u
        JOIN user_sessions s ON u.id = s.user_id
        WHERE s.session_token = ? AND s.expires_at > NOW()
    ");
    $stmt->execute([$token]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

try {
    $user = getUserFromToken($pdo);
    
    if (!$user) {
        throw new Exception('Неавторизован');
    }
    
    unset($user['password']);
    
    echo json_encode([
        'success' => true,
        'user' => $user
    ]);
    
} catch(Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>