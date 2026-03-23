<?php
// view_request.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'configuser.php';

$request_id = $_GET['id'] ?? 0;

// Получаем данные заявки (только свои)
$stmt = $pdo->prepare("
    SELECT r.*, u.email as user_email, u.full_name as user_name
    FROM requests r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.id = ? AND r.user_id = ?
");
$stmt->execute([$request_id, $_SESSION['user_id']]);
$request = $stmt->fetch();

if (!$request) {
    header('Location: my_requests.php');
    exit;
}

// Обработка добавления комментария
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $comment = trim($_POST['comment'] ?? '');
    
    if (!empty($comment)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO request_comments (request_id, user_id, user_role, comment) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$request_id, $_SESSION['user_id'], $_SESSION['user_role'], $comment]);
            
            $success = "Комментарий добавлен!";
        } catch (PDOException $e) {
            $error = "Ошибка при добавлении комментария: " . $e->getMessage();
        }
    }
}

// Получаем комментарии
$stmt = $pdo->prepare("
    SELECT c.*, u.full_name as user_name, u.email as user_email
    FROM request_comments c
    LEFT JOIN users u ON c.user_id = u.id
    WHERE c.request_id = ?
    ORDER BY c.created_at ASC
");
$stmt->execute([$request_id]);
$comments = $stmt->fetchAll();

// Получаем историю статусов
$stmt = $pdo->prepare("
    SELECT * FROM request_status_history 
    WHERE request_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$request_id]);
$history = $stmt->fetchAll();

// Функции для статусов
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

function getPriorityText($priority) {
    $texts = [
        'low' => 'Низкий',
        'medium' => 'Средний',
        'high' => 'Высокий',
        'urgent' => 'Срочный'
    ];
    return $texts[$priority] ?? $priority;
}

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

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заявка #<?php echo $request_id; ?> - <?php echo SITE_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
        }
        
        .header {
            background: white;
            border-bottom: 1px solid #e0e0e0;
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .logo {
            font-size: 20px;
            font-weight: 600;
            color: #667eea;
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            gap: 25px;
        }
        
        .nav-links a {
            color: #666;
            text-decoration: none;
        }
        
        .nav-links a:hover {
            color: #667eea;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logout-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 6px 15px;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .request-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .request-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px 30px;
        }
        
        .request-header h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .request-id {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .request-body {
            padding: 30px;
        }
        
        .info-section {
            margin-bottom: 30px;
        }
        
        .info-section h3 {
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            margin-bottom: 15px;
        }
        
        .info-item label {
            font-weight: 600;
            color: #666;
            display: block;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .info-item .value {
            color: #333;
            font-size: 16px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            color: white;
            font-size: 14px;
            font-weight: 500;
        }
        
        .priority-badge {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .description-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            line-height: 1.6;
            margin-top: 10px;
        }
        
        .admin-comment {
            background: #e8f5e9;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #27ae60;
            margin-top: 10px;
        }
        
        .comments-section {
            margin-top: 30px;
        }
        
        .comment {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        
        .comment-user {
            font-weight: 600;
            color: #667eea;
            margin-bottom: 8px;
        }
        
        .comment-date {
            font-size: 11px;
            color: #999;
            margin-bottom: 8px;
        }
        
        .comment-text {
            color: #333;
            line-height: 1.5;
        }
        
        .comment-admin {
            background: #e3f2fd;
            border-left: 3px solid #2196f3;
        }
        
        .add-comment {
            margin-top: 20px;
        }
        
        .add-comment textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
            min-height: 100px;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 10px;
        }
        
        .history-list {
            list-style: none;
        }
        
        .history-item {
            padding: 12px;
            border-left: 3px solid #667eea;
            background: #f8f9fa;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        
        .history-date {
            font-size: 12px;
            color: #999;
            margin-bottom: 5px;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .btn-cancel {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 15px;
        }
        
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                text-align: center;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-container">
            <a href="user_dashboard.php" class="logo"><?php echo SITE_NAME; ?></a>
            <div class="nav-links">
                <a href="user_dashboard.php">Главная</a>
                <a href="my_requests.php">Мои заявки</a>
                <a href="new_request.php">Новая заявка</a>
            </div>

                <form method="POST" action="logout.php" style="margin: 0;">
                    <button type="submit" class="logout-btn">Выйти</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="container">
        <a href="my_requests.php" class="back-link">← Назад к моим заявкам</a>
        
        <?php if (isset($success)): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="request-card">
            <div class="request-header">
                <div class="request-id">Заявка #<?php echo $request['id']; ?></div>
                <h1><?php echo htmlspecialchars($request['title']); ?></h1>
                <div class="request-date">Создана: <?php echo date('d.m.Y H:i', strtotime($request['created_at'])); ?></div>
            </div>
            
            <div class="request-body">
                <div class="info-section">
                    <h3>📋 Информация о заявке</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Статус</label>
                            <div class="value">
                                <span class="status-badge" style="background: <?php echo getStatusColor($request['status']); ?>">
                                    <?php echo getStatusText($request['status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="info-item">
                            <label>Приоритет</label>
                            <div class="value">
                                <span class="priority-badge" style="background: <?php echo getPriorityColor($request['priority']); ?>20; color: <?php echo getPriorityColor($request['priority']); ?>">
                                    <?php echo getPriorityText($request['priority']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="info-item">
                            <label>Категория</label>
                            <div class="value"><?php echo htmlspecialchars($request['category']); ?></div>
                        </div>
                        <?php if ($request['completed_at']): ?>
                        <div class="info-item">
                            <label>Дата выполнения</label>
                            <div class="value"><?php echo date('d.m.Y H:i', strtotime($request['completed_at'])); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="info-section">
                    <h3>📝 Описание заявки</h3>
                    <div class="description-box">
                        <?php echo nl2br(htmlspecialchars($request['description'])); ?>
                    </div>
                </div>
                
                <?php if ($request['admin_comment']): ?>
                <div class="info-section">
                    <h3>💬 Ответ администратора</h3>
                    <div class="admin-comment">
                        <?php echo nl2br(htmlspecialchars($request['admin_comment'])); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($request['status'] === 'pending'): ?>
                <div class="info-section">
                    <form method="POST" action="cancel_request.php" onsubmit="return confirm('Вы уверены, что хотите отменить эту заявку?')">
                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                        <button type="submit" class="btn-cancel">Отменить заявку</button>
                    </form>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($comments)): ?>
                <div class="info-section comments-section">
                    <h3>💬 Комментарии</h3>
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment <?php echo $comment['user_role'] == 'admin' ? 'comment-admin' : ''; ?>">
                            <div class="comment-user">
                                <?php if ($comment['user_role'] == 'admin'): ?>
                                    👑 Администратор
                                <?php else: ?>
                                    👤 <?php echo htmlspecialchars($comment['user_name'] ?: $comment['user_email']); ?>
                                <?php endif; ?>
                            </div>
                            <div class="comment-date"><?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?></div>
                            <div class="comment-text"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <div class="info-section add-comment">
                    <h3>✍️ Добавить комментарий</h3>
                    <form method="POST">
                        <textarea name="comment" placeholder="Напишите ваш комментарий..."></textarea>
                        <button type="submit" name="add_comment" class="btn-submit">Отправить комментарий</button>
                    </form>
                </div>
                
                <?php if (!empty($history)): ?>
                <div class="info-section">
                    <h3>📜 История изменений</h3>
                    <ul class="history-list">
                        <?php foreach ($history as $item): ?>
                        <li class="history-item">
                            <div class="history-date"><?php echo date('d.m.Y H:i', strtotime($item['created_at'])); ?></div>
                            <div>
                                Статус изменен с 
                                <strong><?php echo getStatusText($item['old_status'] ?: 'создание'); ?></strong> 
                                на 
                                <strong><?php echo getStatusText($item['new_status']); ?></strong>
                                <br>
                                <small>Кем: <?php echo $item['changed_by_role'] == 'admin' ? 'Администратор' : 'Вы'; ?></small>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>