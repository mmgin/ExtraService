<?php
// admin_view_request.php
session_start();
require_once 'configuser.php';

// Проверяем авторизацию администратора
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: admin_login.php');
    exit;
}

$request_id = $_GET['id'] ?? 0;

// Получаем данные заявки
$stmt = $pdo->prepare("
    SELECT r.*, u.email as user_email, u.full_name as user_name, u.phone as user_phone
    FROM requests r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.id = ?
");
$stmt->execute([$request_id]);
$request = $stmt->fetch();

if (!$request) {
    header('Location: admin_requests.php');
    exit;
}

// Обработка изменения статуса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $new_status = $_POST['status'] ?? '';
    $admin_comment = trim($_POST['admin_comment'] ?? '');
    
    if ($new_status && in_array($new_status, ['pending', 'in_progress', 'completed', 'rejected', 'cancelled'])) {
        try {
            $old_status = $request['status'];
            
            // Обновляем статус заявки
            $stmt = $pdo->prepare("UPDATE requests SET status = ?, admin_comment = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$new_status, $admin_comment, $request_id]);
            
            // Если статус "выполнена", добавляем дату выполнения
            if ($new_status === 'completed') {
                $stmt = $pdo->prepare("UPDATE requests SET completed_at = NOW() WHERE id = ?");
                $stmt->execute([$request_id]);
            }
            
            // Добавляем в историю
            $stmt = $pdo->prepare("
                INSERT INTO request_status_history (request_id, old_status, new_status, changed_by, changed_by_role) 
                VALUES (?, ?, ?, ?, 'admin')
            ");
            $stmt->execute([$request_id, $old_status, $new_status, $_SESSION['user_id']]);
            
            $success = "Статус заявки успешно обновлен!";
            
            // Обновляем данные заявки
            $stmt = $pdo->prepare("
                SELECT r.*, u.email as user_email, u.full_name as user_name, u.phone as user_phone
                FROM requests r
                LEFT JOIN users u ON r.user_id = u.id
                WHERE r.id = ?
            ");
            $stmt->execute([$request_id]);
            $request = $stmt->fetch();
            
        } catch (PDOException $e) {
            $error = "Ошибка при обновлении статуса: " . $e->getMessage();
        }
    }
}

// Получаем историю статусов
$stmt = $pdo->prepare("
    SELECT * FROM request_status_history 
    WHERE request_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$request_id]);
$history = $stmt->fetchAll();

// Получаем комментарии
$stmt = $pdo->prepare("
    SELECT * FROM request_comments 
    WHERE request_id = ? 
    ORDER BY created_at ASC
");
$stmt->execute([$request_id]);
$comments = $stmt->fetchAll();

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
    <title>Просмотр заявки #<?php echo $request_id; ?> - Админ панель</title>
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
            max-width: 1200px;
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
        
        .status-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .status-form select,
        .status-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            margin-top: 10px;
        }
        
        .status-form textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn-update {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 15px;
            font-size: 14px;
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
            <a href="admin_dashboard.php" class="logo">ExtraService - Админ панель</a>
            <div class="nav-links">
                <a href="admin_dashboard.php">Главная</a>
                <a href="admin_requests.php">Все заявки</a>
                <a href="admin_users.php">Пользователи</a>
            </div>
            <div class="user-menu">
                <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Администратор'); ?></span>
                <form method="POST" action="logout.php" style="margin: 0;">
                    <button type="submit" class="logout-btn">Выйти</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="container">
        <a href="admin_requests.php" class="back-link">← Назад к списку заявок</a>
        
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
                    <h3>👤 Информация о пользователе</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Имя</label>
                            <div class="value"><?php echo htmlspecialchars($request['user_name'] ?: 'Не указано'); ?></div>
                        </div>
                        <div class="info-item">
                            <label>Email</label>
                            <div class="value"><?php echo htmlspecialchars($request['user_email']); ?></div>
                        </div>
                        <div class="info-item">
                            <label>Телефон</label>
                            <div class="value"><?php echo htmlspecialchars($request['user_phone'] ?: 'Не указан'); ?></div>
                        </div>
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
                    <div class="description-box" style="background: #e8f5e9; border-left: 3px solid #27ae60;">
                        <?php echo nl2br(htmlspecialchars($request['admin_comment'])); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="info-section">
                    <h3>⚙️ Управление заявкой</h3>
                    <form method="POST" class="status-form">
                        <label>Изменить статус:</label>
                        <select name="status">
                            <option value="pending" <?php echo $request['status'] == 'pending' ? 'selected' : ''; ?>>⏳ Ожидает обработки</option>
                            <option value="in_progress" <?php echo $request['status'] == 'in_progress' ? 'selected' : ''; ?>>🔄 В работе</option>
                            <option value="completed" <?php echo $request['status'] == 'completed' ? 'selected' : ''; ?>>✅ Выполнена</option>
                            <option value="rejected" <?php echo $request['status'] == 'rejected' ? 'selected' : ''; ?>>❌ Отклонена</option>
                            <option value="cancelled" <?php echo $request['status'] == 'cancelled' ? 'selected' : ''; ?>>🚫 Отменена</option>
                        </select>
                        
                        <label style="margin-top: 15px;">Комментарий (ответ пользователю):</label>
                        <textarea name="admin_comment" placeholder="Введите ответ или комментарий для пользователя..."><?php echo htmlspecialchars($request['admin_comment'] ?? ''); ?></textarea>
                        
                        <input type="hidden" name="action" value="update_status">
                        <button type="submit" class="btn-update">Обновить статус</button>
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
                                <small>Кем: <?php echo $item['changed_by_role'] == 'admin' ? 'Администратор' : 'Пользователь'; ?></small>
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