<?php
// test_config.php
require_once 'configuser.php';

echo "✅ configuser.php подключен успешно!<br>";
echo "База данных: " . DB_NAME . "<br>";

$stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
$count = $stmt->fetch();
echo "Всего пользователей: " . $count['count'] . "<br>";

$stmt = $pdo->query("SELECT * FROM users");
$users = $stmt->fetchAll();

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Email</th><th>Роль</th></tr>";
foreach ($users as $user) {
    echo "<tr>";
    echo "<td>{$user['id']}</td>";
    echo "<td>{$user['email']}</td>";
    echo "<td>{$user['role']}</td>";
    echo "</tr>";
}
echo "</table>";
?>