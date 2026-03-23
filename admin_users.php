<?php
// admin_users.php
session_start();
require_once 'configuser.php';

// Проверяем авторизацию администратора
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: admin_login.php');
    exit;
}

// Получаем параметры фильтрации
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? 'all';

// Построение запроса
$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (email LIKE ? OR full_name LIKE ? OR phone LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($role_filter !== 'all') {
    $sql .= " AND role = ?";
    $params[] = $role_filter;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Статистика пользователей
$stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$role_stats = [];
while ($row = $stmt->fetch()) {
    $role_stats[$row['role']] = $row['count'];
}

// Обработка действий с пользователями
$action_message = '';
$action_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = $_POST['user_id'] ?? 0;
    
    if ($action === 'toggle_status') {
        // Изменение статуса активности
        $stmt = $pdo->prepare("SELECT is_active FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if ($user) {
            $new_status = $user['is_active'] ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            $stmt->execute([$new_status, $user_id]);
            $action_message = "Статус пользователя успешно изменен!";
        }
    } elseif ($action === 'change_role') {
        // Изменение роли
        $new_role = $_POST['new_role'] ?? '';
        if (in_array($new_role, ['user', 'admin'])) {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$new_role, $user_id]);
            $action_message = "Роль пользователя успешно изменена!";
            
            // Если меняем роль текущего пользователя, обновляем сессию
            if ($user_id == $_SESSION['user_id']) {
                $_SESSION['user_role'] = $new_role;
            }
        }
    } elseif ($action === 'delete_user') {
        // Удаление пользователя (только если это не текущий администратор)
        if ($user_id != $_SESSION['user_id']) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $action_message = "Пользователь успешно удален!";
        } else {
            $action_error = "Вы не можете удалить самого себя!";
        }
    }
    
    // Обновляем список пользователей
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
}

function getStatusText($is_active) {
    return $is_active ? 'Активен' : 'Заблокирован';
}

function getStatusColor($is_active) {
    return $is_active ? '#27ae60' : '#e74c3c';
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями - Админ панель</title>
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
        
        .nav-links a:hover,
        .nav-links a.active {
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .stat-card.active {
            border-color: #667eea;
            background: #f8f9ff;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-label {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        
        .search-box {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .search-box input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            min-width: 200px;
        }
        
        .search-box button {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .search-box .reset-btn {
            background: #95a5a6;
        }
        
        .users-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f8f9fa;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            color: #666;
            border-bottom: 1px solid #e0e0e0;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            color: white;
        }
        
        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .role-admin {
            background: #667eea20;
            color: #667eea;
        }
        
        .role-user {
            background: #27ae6020;
            color: #27ae60;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn-icon {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }
        
        .btn-status {
            background: #f39c12;
            color: white;
        }
        
        .btn-status:hover {
            background: #e67e22;
        }
        
        .btn-role {
            background: #3498db;
            color: white;
        }
        
        .btn-role:hover {
            background: #2980b9;
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c0392b;
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
        
        .empty-state {
            text-align: center;
            padding: 60px;
            color: #999;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 15px;
            padding: 25px;
            max-width: 400px;
            width: 90%;
        }
        
        .modal-content h3 {
            margin-bottom: 15px;
        }
        
        .modal-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .modal-buttons button {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .btn-confirm {
            background: #e74c3c;
            color: white;
        }
        
        .btn-cancel {
            background: #95a5a6;
            color: white;
        }
        
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .users-table {
                overflow-x: auto;
            }
            
            table {
                min-width: 600px;
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
                <a href="admin_users.php" class="active">Пользователи</a>
            </div>
            <div class="user-menu">
                <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Администратор'); ?></span>
                <form method="POST" action="index.html" style="margin: 0;">
                    <button type="submit" class="logout-btn">Выйти</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="container">
        <?php if ($action_message): ?>
            <div class="success-message"><?php echo $action_message; ?></div>
        <?php endif; ?>
        
        <?php if ($action_error): ?>
            <div class="error-message"><?php echo $action_error; ?></div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card <?php echo $role_filter === 'all' ? 'active' : ''; ?>" onclick="filterByRole('all')">
                <div class="stat-number"><?php echo array_sum($role_stats); ?></div>
                <div class="stat-label">Все пользователи</div>
            </div>
            <div class="stat-card <?php echo $role_filter === 'user' ? 'active' : ''; ?>" onclick="filterByRole('user')">
                <div class="stat-number"><?php echo $role_stats['user'] ?? 0; ?></div>
                <div class="stat-label">Пользователи</div>
            </div>
            <div class="stat-card <?php echo $role_filter === 'admin' ? 'active' : ''; ?>" onclick="filterByRole('admin')">
                <div class="stat-number"><?php echo $role_stats['admin'] ?? 0; ?></div>
                <div class="stat-label">Администраторы</div>
            </div>
        </div>
        
        <div class="search-box">
            <form method="GET" style="display: flex; gap: 10px; width: 100%; flex-wrap: wrap;">
                <input type="hidden" name="role" value="<?php echo $role_filter; ?>">
                <input type="text" name="search" placeholder="Поиск по email, имени или телефону..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">🔍 Поиск</button>
                <?php if ($search): ?>
                    <a href="?role=<?php echo $role_filter; ?>" class="reset-btn" style="background: #95a5a6; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none;">Сбросить</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="users-table">
            <?php if (empty($users)): ?>
                <div class="empty-state">
                    <h3>Нет пользователей</h3>
                    <p>По вашему запросу ничего не найдено.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Имя</th>
                            <th>Телефон</th>
                            <th>Роль</th>
                            <th>Статус</th>
                            <th>Дата регистрации</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>#<?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['full_name'] ?: '—'); ?></td>
                            <td><?php echo htmlspecialchars($user['phone'] ?: '—'); ?></td>
                            <td>
                                <span class="role-badge <?php echo $user['role'] == 'admin' ? 'role-admin' : 'role-user'; ?>">
                                    <?php echo $user['role'] == 'admin' ? 'Администратор' : 'Пользователь'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge" style="background: <?php echo getStatusColor($user['is_active']); ?>">
                                    <?php echo getStatusText($user['is_active']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></td>
                            <td class="action-buttons">
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Изменить статус пользователя?')">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <button type="submit" class="btn-icon btn-status" title="Изменить статус">
                                        <?php echo $user['is_active'] ? '🔒 Заблокировать' : '🔓 Разблокировать'; ?>
                                    </button>
                                </form>
                                
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Изменить роль пользователя?')">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="action" value="change_role">
                                    <input type="hidden" name="new_role" value="<?php echo $user['role'] == 'admin' ? 'user' : 'admin'; ?>">
                                    <button type="submit" class="btn-icon btn-role" title="Изменить роль">
                                        <?php echo $user['role'] == 'admin' ? '👤 Сделать пользователем' : '👑 Сделать админом'; ?>
                                    </button>
                                </form>
                                
                                <button class="btn-icon btn-delete" onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['email']); ?>')" title="Удалить">
                                    🗑️ Удалить
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Модальное окно подтверждения удаления -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3>Подтверждение удаления</h3>
            <p>Вы действительно хотите удалить пользователя <strong id="deleteUserName"></strong>?</p>
            <p style="color: #e74c3c; font-size: 12px;">Это действие нельзя отменить. Все заявки пользователя также будут удалены.</p>
            <form id="deleteForm" method="POST">
                <input type="hidden" name="user_id" id="deleteUserId">
                <input type="hidden" name="action" value="delete_user">
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Отмена</button>
                    <button type="submit" class="btn-confirm">Удалить</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function filterByRole(role) {
            let url = new URL(window.location.href);
            url.searchParams.set('role', role);
            window.location.href = url.toString();
        }
        
        function confirmDelete(userId, userName) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUserName').textContent = userName;
            document.getElementById('deleteModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }
        
        // Закрытие модального окна при клике вне его
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>
</html>