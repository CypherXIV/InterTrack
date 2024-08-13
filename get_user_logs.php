<?php
require 'db.php';
session_start();

$userId = $_GET['userId'] ?? 0;
$adminId = $_SESSION['admin_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $logEntryId = $_POST['log_entry_id'] ?? null;
    $comment = trim($_POST['comment']) ?? '';

    if (isset($_POST['comment'], $_POST['log_entry_id'])) {
        $checkStmt = $pdo->prepare("SELECT id FROM log_comments WHERE log_entry_id = ?");
        $checkStmt->execute([$logEntryId]);
        $existingComment = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingComment) {
            $updateStmt = $pdo->prepare("UPDATE log_comments SET comment = ?, admin_id = ? WHERE id = ?");
            $updateStmt->execute([$comment, $adminId, $existingComment['id']]);
        } else {
            $insertStmt = $pdo->prepare("INSERT INTO log_comments (log_entry_id, comment, admin_id) VALUES (?, ?, ?)");
            $insertStmt->execute([$logEntryId, $comment, $adminId]);
        }
    } elseif (isset($_POST['delete_comment'])) {
        $commentId = $_POST['delete_comment'];
        $deleteStmt = $pdo->prepare("DELETE FROM log_comments WHERE id = ?");
        $deleteStmt->execute([$commentId]);
    }
    header("Location: get_user_logs.php?userId={$userId}");
    exit();
}

$userStmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

$logStmt = $pdo->prepare("SELECT * FROM log_entries WHERE user_id = ?");
$logStmt->execute([$userId]);
$entries = $logStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Log Entries</title>
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <div class="admin-user-list">
        <h1>Log Entries for User ID: <?php echo htmlspecialchars_decode($userId); ?> (<?php echo htmlspecialchars($user['username']); ?>)</h1>
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Hours</th>
                    <th>Description</th>
                    <th>Comments</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $entry) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars_decode($entry['date']); ?></td>
                        <td><?php echo htmlspecialchars_decode($entry['hours']); ?></td>
                        <td><?php echo htmlspecialchars_decode($entry['description']); ?></td>
                        <td>
                            <?php
                            $commentStmt = $pdo->prepare("
                            SELECT lc.id, lc.comment, a.username AS admin_username
                            FROM log_comments lc
                            JOIN admins a ON lc.admin_id = a.id
                            WHERE lc.log_entry_id = ?
                        ");
                            $commentStmt->execute([$entry['id']]);
                            $comment = $commentStmt->fetch(PDO::FETCH_ASSOC);
                            if ($comment) {
                                echo '<p>' . htmlspecialchars_decode($comment['comment']) . ' by ' . htmlspecialchars_decode($comment['admin_username']) . '</p>';
                            } else {
                                echo 'No comment yet';
                            }
                            ?>
                        </td>
                        <td>
                            <form method="POST" action="get_user_logs.php?userId=<?php echo htmlspecialchars_decode($userId); ?>">
                                <input type="hidden" name="log_entry_id" value="<?php echo $entry['id']; ?>">
                                <input type="text" name="comment" placeholder="Enter your comment here" required>
                                <button type="submit">Submit Comment</button>
                            </form>
                            <?php if ($comment) : ?>
                                <form method="POST" action="get_user_logs.php?userId=<?php echo htmlspecialchars_decode($userId); ?>">
                                    <input type="hidden" name="delete_comment" value="<?php echo $comment['id']; ?>">
                                    <button type="submit" class="btn btn-danger">Delete Comment</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button onclick="window.close();" class="btn btn-warning">Close Window</button>
    </div>
</body>

</html>