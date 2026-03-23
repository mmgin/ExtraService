<?php

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: admin_login.php');
    exit();
}


$stmt = $pdo->query("
    SELECT r.*, u.username, u.full_name, u.email 
    FROM requests r 
    JOIN users u ON r.user_id = u.id 
    ORDER BY r.created_at DESC
");
$requests = $stmt->fetchAll();


$stmt = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'Новая'");
$new_count = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM requests");
$total_count = $stmt->fetchColumn();

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $request_id = $_POST['request_id'];
    $status = $_POST['status'];
    $comment = $_POST['comment'] ?? '';
    
    $stmt = $pdo->prepare("UPDATE requests SET status = ?, admin_comment = ? WHERE id = ?");
    $stmt->execute([$status, $comment, $request_id]);
    header('Location: admin_dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background: #f0f2f5;
        }
        .header {
            background: #1a1a2e;
            color: white;
            padding: 1rem 0;
        }
        .nav {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            padding: 5px 10px;
            border-radius: 5px;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card h3 {
            font-size: 2rem;
            color: #1a1a2e;
            margin-bottom: 5px;
        }
        .table {
            width: 100%;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .table th {
            background: #1a1a2e;
            color: white;
            padding: 15px;
            text-align: left;
        }
        .table td {
            padding: 15px;
            border-bottom: 1px solid #ddd;
        }
        .table tr:hover {
            background: #f5f5f5;
        }
        .status {
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 0.9rem;
        }
        .status.new { background: #f39c12; color: white; }
        .status.work { background: #3498db; color: white; }
        .status.done { background: #27ae60; color: white; }
        .btn {
            padding: 5px 10px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        .btn:hover {
            background: #764ba2;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 400px;
        }
        .modal-content select, .modal-content textarea {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .footer {
            background: #1a1a2e;
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 50px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="nav">
            <div class="logo">👑 Админ-панель</div>
            <div class="nav-links">
                <a href="index.php">Главная</a>
                <a href="knowledge_base.php">База знаний</a>
                <a href="contacts.php">Контакты</a>
                <a href="logout.php">Выйти</a>
            </div>
        </div>
    </div>

    <div class="container">
        <h1>Добро пожаловать, <?php echo h($_SESSION['username']); ?></h1>
        
        <div class="stats">
            <div class="stat-card">
                <h3><?php echo $total_count; ?></h3>
                <p>Всего заявок</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $new_count; ?></h3>
                <p>Новых заявок</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count($requests); ?></h3>
                <p>Заявок в работе</p>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Пользователь</th>
                    <th>Email</th>
                    <th>Заголовок</th>
                    <th>Статус</th>
                    <th>Дата</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($requests as $req): ?>
                <tr>
                    <td>#<?php echo $req['id']; ?></td>
                    <td><?php echo h($req['full_name'] ?: $req['username']); ?></td>
                    <td><?php echo h($req['email']); ?></td>
                    <td><?php echo h($req['title']); ?></td>
                    <td>
                        <?php
                        $status_class = 'new';
                        if($req['status'] == 'В работе') $status_class = 'work';
                        if($req['status'] == 'Решена') $status_class = 'done';
                        ?>
                        <span class="status <?php echo $status_class; ?>">
                            <?php echo h($req['status']); ?>
                        </span>
                    </td>
                    <td><?php echo date('d.m.Y H:i', strtotime($req['created_at'])); ?></td>
                    <td>
                        <button class="btn" onclick="openModal(<?php echo $req['id']; ?>, '<?php echo $req['status']; ?>', '<?php echo addslashes($req['admin_comment']); ?>')">
                            Обработать
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>


    <div class="modal" id="requestModal">
        <div class="modal-content">
            <h3>Обработка заявки</h3>
            <form method="POST">
                <input type="hidden" name="request_id" id="request_id">
                
                <label>Статус:</label>
                <select name="status" id="status">
                    <option value="Новая">Новая</option>
                    <option value="В работе">В работе</option>
                    <option value="Решена">Решена</option>
                </select>
                
                <label>Комментарий:</label>
                <textarea name="comment" id="comment" rows="4" placeholder="Ответ для пользователя..."></textarea>
                
                <button type="submit" name="update_status" class="btn">Сохранить</button>
                <button type="button" class="btn" style="background: #95a5a6;" onclick="closeModal()">Отмена</button>
            </form>
        </div>
    </div>

    <div class="footer">
        <p>© 2025 Служба поддержки. Все права защищены.</p>
    </div>

    <script>
        function openModal(id, status, comment) {
            document.getElementById('request_id').value = id;
            document.getElementById('status').value = status;
            document.getElementById('comment').value = comment;
            document.getElementById('requestModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('requestModal').style.display = 'none';
        }
    </script>
</body>
</html>