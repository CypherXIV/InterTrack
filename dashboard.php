<?php
session_start();
require "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$userName = isset($_SESSION["username"])
    ? $_SESSION["username"]
    : "Unknown User";
$studentId = isset($_SESSION["student_id"])
    ? $_SESSION["student_id"]
    : "Unknown Student ID";
$userId = $_SESSION["user_id"];

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $totalHoursQuery = $pdo->prepare(
        "SELECT total_hours FROM users WHERE id = ?"
    );
    $totalHoursQuery->execute([$userId]);
    $totalHours = $totalHoursQuery->fetchColumn();

    $loggedHoursQuery = $pdo->prepare(
        "SELECT SUM(hours) AS total_logged_hours FROM log_entries WHERE user_id = ?"
    );
    $loggedHoursQuery->execute([$userId]);
    $loggedHours = $loggedHoursQuery->fetchColumn() ?: 0;

    $remainingHours = max(0, $totalHours - $loggedHours);

    $logEntriesQuery = $pdo->prepare(
        "SELECT le.*, lc.comment, a.username AS admin_username
    FROM log_entries le
    LEFT JOIN log_comments lc ON le.id = lc.log_entry_id
    LEFT JOIN admins a ON lc.admin_id = a.id
    WHERE le.user_id = ? ORDER BY le.date DESC"
    );
    $logEntriesQuery->execute([$userId]);
    $logEntries = $logEntriesQuery->fetchAll();
} catch (PDOException $e) {
    error_log("PDOException - " . $e->getMessage(), 0);
    $_SESSION["message"] = "An error occurred while fetching data.";
    header("Location: dashboard.php");
    exit();
}

function handleDocumentUpload($file)
{
    if ($file && $file["error"] == UPLOAD_ERR_OK) {
        $uploadDir = "uploads/";
        $fileName = uniqid() . "-" . basename($file["name"]);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($file["tmp_name"], $targetPath)) {
            return $targetPath;
        } else {
            $_SESSION["message"] = "Error uploading file.";
            return null;
        }
    }
    return null;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["delete"])) {
        $entryId = $_POST["delete"];
        try {
            $stmt = $pdo->prepare("DELETE FROM log_entries WHERE id = ?");
            $stmt->execute([$entryId]);
            $_SESSION["message"] = "Entry deleted successfully!";
        } catch (PDOException $e) {
            $_SESSION["message"] = "Error deleting entry: " . $e->getMessage();
        }
    } else {
        $date = isset($_POST["date"]) ? trim($_POST["date"]) : null;
        $hours = filter_input(
            INPUT_POST,
            "hours",
            FILTER_SANITIZE_NUMBER_FLOAT,
            FILTER_FLAG_ALLOW_FRACTION
        );
        $description = isset($_POST["description"])
            ? filter_var($_POST["description"], FILTER_SANITIZE_SPECIAL_CHARS)
            : null;
        $documentPath = handleDocumentUpload($_FILES["document"]);

        if (!empty($_GET["entryId"])) {
            $entryId = $_GET["entryId"];
            try {
                $updateStmt = $pdo->prepare(
                    "UPDATE log_entries SET date = ?, hours = ?, description = ?, document_path = ? WHERE id = ? AND user_id = ?"
                );
                $updateStmt->execute([
                    $date,
                    $hours,
                    $description,
                    $documentPath,
                    $entryId,
                    $_SESSION["user_id"],
                ]);
                $_SESSION["message"] = "Entry updated successfully!";
            } catch (PDOException $e) {
                $_SESSION["message"] =
                    "Error updating entry: " . $e->getMessage();
            }
        } else {
            if ($date && $hours && $description) {
                try {
                    $insertStmt = $pdo->prepare(
                        "INSERT INTO log_entries (user_id, date, hours, description, document_path) VALUES (?, ?, ?, ?, ?)"
                    );
                    $insertStmt->execute([
                        $_SESSION["user_id"],
                        $date,
                        $hours,
                        $description,
                        $documentPath,
                    ]);
                    $_SESSION["message"] = "New entry added successfully!";
                } catch (PDOException $e) {
                    $_SESSION["message"] =
                        "Error adding new entry: " . $e->getMessage();
                }
            } else {
                $_SESSION["message"] = "All fields are required.";
            }
        }
    }

    header("Location: dashboard.php");
    exit();
}

if (isset($_GET["logout"]) && $_GET["logout"] == "1") {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            "",
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();

    header("Location: login.php");
    exit();
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Log Capstone Hours </title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>

<body class="background_image">
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="form-overlay"> <?php if (isset($_SESSION['message'])) : ?> <div class="alert alert-info text-center"> <?php echo htmlspecialchars($_SESSION['message']);
                                                                                                                                    unset($_SESSION['message']); ?> </div> <?php endif; ?> <h2 class="text-center text-white"> Log Your Capstone Practice Hours</h2>
                    <div class="hours-info text-center text-white mb-4">
                        <p>Name: <?php echo htmlspecialchars($userName); ?> </p>
                        <p>Student ID: <?php echo htmlspecialchars($studentId); ?> </p>
                        <p>Total Hours Required: <?php echo htmlspecialchars($totalHours); ?> </p>
                        <p>Hours Logged: <?php echo htmlspecialchars($loggedHours); ?> </p>
                        <p>Remaining Hours: <?php echo htmlspecialchars($remainingHours); ?> </p>
                    </div>
                    <form id="logEntryForm" action="dashboard.php" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label class="text-white" for="date">Date (YYYY-MM-DD)</label>
                            <input type="text" class="form-control" id="date" name="date" required pattern="^(19|20)\d\d-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])$" title="Date must be in YYYY-MM-DD format">
                        </div>
                        <div class="form-group">
                            <label class="text-white" for="hours">Hours Worked (e.g., 5.0, 12.5)</label>
                            <input type="text" class="form-control" id="hours" name="hours" required pattern="\d{1,2}(\.\d)?" title="Enter a number up to two digits followed by one decimal place">
                        </div>
                        <div class="form-group">
                            <label class="text-white" for="description">Description of Activities</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        </div>
                        <!-- <div class="form-group">
                                <label class="text-white" for="document">Upload Document (Optional)</label>
                                <input type="file" class="form-control-file" id="document" name="document">
                            </div>
                            <div id="currentDocumentSection" class="text-white" style="display: none;"> Current Document: <a id="currentDocumentLink" href="#" target="_blank">View</a>
                            </div>-->
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                        <div class="text-center mt-3">
                            <button class="btn btn-info" type="button" data-toggle="collapse" data-target="#sidebar" aria-expanded="false" aria-controls="sidebar"> Toggle Past Entries </button>
                            <a href="dashboard.php?logout=1" class="btn btn-warning" style="margin-top: 10px;">Log Out</a>
                            <a href="user_chat_list.php" class="btn btn-secondary" style="margin-top: 10px;">Access User Chats</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div id="sidebar" class="collapse">
            <div class="card card-body">
                <div class="text-center mt-3">
                    <button class="btn btn-info" type="button" data-toggle="collapse" data-target="#sidebar" aria-expanded="false" aria-controls="sidebar">Collapse</button>
                </div>
                <h4 class="text-center">Past Log Entries</h4>
                <?php foreach ($logEntries as $entry) : ?>
                    <div class="log-entry">
                        <p>Date: <?php echo htmlspecialchars($entry['date']); ?></p>
                        <p>Hours: <?php echo htmlspecialchars($entry['hours']); ?></p>
                        <p>Description: <?php echo htmlspecialchars_decode($entry['description'], ENT_QUOTES); ?></p>
                        <?php if (!empty($entry['comment'])) : ?>
                            <p class="comment">Comment: <?php echo htmlspecialchars($entry['comment']) . ' - posted by ' . htmlspecialchars($entry['admin_username']); ?></p>
                        <?php else : ?>
                            <p class="comment">No comments yet.</p>
                        <?php endif; ?>
                        <?php if (!empty($entry['comment'])) : ?>
                        <?php else : ?>
                            <button class="btn btn-primary edit-btn" data-id="<?php echo $entry['id']; ?>" data-date="<?php echo $entry['date']; ?>" data-hours="<?php echo $entry['hours']; ?>" data-description="<?php echo htmlspecialchars_decode(htmlspecialchars($entry['description'], ENT_QUOTES, 'UTF-8')); ?>" data-document-path="<?php echo htmlspecialchars($entry['document_path']); ?>">Edit</button>
                            <form method="POST" action="dashboard.php" onsubmit="return confirm('Are you sure you want to delete this entry? This cannot be undone.');">
                                <input type="hidden" name="delete" value="<?php echo $entry['id']; ?>">
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js">
    </script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js">
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let currentlyEditingId = null;
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const entryId = this.getAttribute('data-id');
                    if (currentlyEditingId === entryId) {
                        document.getElementById('logEntryForm').reset();
                        document.getElementById('logEntryForm').action = 'dashboard.php';
                        currentlyEditingId = null;
                        const currentDocumentSection = document.getElementById('currentDocumentSection');
                        currentDocumentSection.style.display = 'none';
                    } else {
                        currentlyEditingId = entryId;
                        const date = this.getAttribute('data-date').trim();
                        const hours = parseFloat(this.getAttribute('data-hours')).toFixed(1);
                        const description = this.getAttribute('data-description');
                        const documentPath = this.getAttribute('data-document-path').trim();
                        document.getElementById('date').value = date;
                        document.getElementById('hours').value = hours;
                        document.getElementById('description').value = description;
                        document.getElementById('logEntryForm').action = 'dashboard.php?entryId=' + entryId;
                        const currentDocumentSection = document.getElementById('currentDocumentSection');
                        const currentDocumentLink = document.getElementById('currentDocumentLink');
                        if (documentPath && documentPath !== "null" && documentPath.trim() !== "") {
                            currentDocumentSection.style.display = 'block';
                            currentDocumentLink.href = documentPath;
                            currentDocumentLink.textContent = 'View Current Document';
                        } else {
                            currentDocumentSection.style.display = 'none';
                        }
                    }
                });
            });
        });
    </script>
</body>

</html>