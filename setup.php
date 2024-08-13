<?php
session_start();
require "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION["user_id"];

$stmt = $pdo->prepare("SELECT setup_completed FROM users WHERE id = ?");
$stmt->execute([$userId]);
$setupCompleted = $stmt->fetchColumn();

if ($setupCompleted) {
    $_SESSION["message"] = "Setup already completed. Redirecting to dashboard.";
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["field"], $_POST["totalHours"]) && is_numeric($_POST["totalHours"])) {
        $field = $_POST["field"];
        $totalHours = floatval($_POST["totalHours"]);

        $stmt = $pdo->prepare("UPDATE users SET field = :field, total_hours = :totalHours, setup_completed = TRUE WHERE id = :userId");
        $stmt->execute([
            ":field" => $field,
            ":totalHours" => $totalHours,
            ":userId" => $userId
        ]);

        $_SESSION["message"] = "Setup completed successfully!";
        header("Location: dashboard.php");
        exit();
    } else {
        $_SESSION["message"] = "Please ensure all fields are correctly filled out.";
        header("Location: setup.php");
        exit();
    }
}

$displayedMessage = "";
if (!empty($_SESSION['message'])) {
    $displayedMessage = $_SESSION['message'];
    unset($_SESSION['message']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Capstone Setup</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
    <link rel="stylesheet" href="css/styles.css">
</head>

<body class="background_image">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="form-overlay">
                    <?php if (!empty($displayedMessage)) : ?>
                        <div class="alert alert-info" role="alert">
                            <?php echo htmlspecialchars($displayedMessage); ?>
                        </div>
                    <?php endif; ?>
                    <h2 class="text-center text-white">Capstone Setup</h2>
                    <p class="text-center text-white mb-4">Please enter your field of study and the total hours required for your capstone project.</p>
                    <form action="setup.php" method="post">
                        <div class="form-group">
                            <label for="field" class="text-white">Field of Study</label>
                            <select class="form-control" id="field" name="field" required>
                                <option value="">--Select your field--</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Engineering">Engineering</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="totalHours" class="text-white">Total Required Hours</label>
                            <input type="number" class="form-control" id="totalHours" name="totalHours" min="1" required>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>