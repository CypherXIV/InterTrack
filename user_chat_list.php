<?php
session_start();
require 'db.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION["user_id"];

try {
    $stmt = $pdo->prepare("SELECT id, username FROM admins ORDER BY username ASC");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error occurred:" . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Chat List</title>
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <div class="admin-user-list">
        <h1>Available Administrators</h1>
        <table class="table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $admin) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($admin['username']); ?></td>
                        <td>
                            <button onclick="openChat(<?php echo $admin['id']; ?>, <?php echo $userId; ?>)" class="btn-start-chat">Start/View Chat</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="dashboard.php" class="btn btn-warning">Back to Dashboard</a>
    </div>
    <script>
        function openChat(adminId, userId) {
            const url = 'chat_init_user.php';
            const params = `adminId=${adminId}&userId=${userId}`;
            const newWindow = window.open(url + '?' + params, 'ChatWindow', 'height=600,width=800');
            if (window.focus) {
                newWindow.focus();
            }
        }
    </script>

</body>

</html>