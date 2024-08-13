<?php
session_start();
require "db.php";

$_SESSION["message"] = 'Hi! Welcome to InterActive! Please enter your account username and password! Have a wonderful day! If you do not have an account, please resort to the "Register here." link below.';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    $stmtAdmin = $pdo->prepare("SELECT id, username, password FROM admins WHERE username = :username");
    $stmtAdmin->bindParam(":username", $username);
    $stmtAdmin->execute();
    $admin = $stmtAdmin->fetch();

    if ($admin && password_verify($password, $admin["password"])) {
        $_SESSION["admin_id"] = $admin["id"];
        $_SESSION["username"] = $admin["username"];
        $_SESSION["is_admin"] = true;
        $_SESSION["message"] = "Admin login successful.";
        header("Location: admin_user_list.php");
        exit();
    }

    $stmtUser = $pdo->prepare("SELECT id, username, password, student_id, setup_completed FROM users WHERE username = :username");
    $stmtUser->bindParam(":username", $username);
    $stmtUser->execute();
    $user = $stmtUser->fetch();

    if ($user && password_verify($password, $user["password"])) {
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["is_admin"] = false;
        $_SESSION["student_id"] = $user["student_id"];

        if ($user["setup_completed"]) {
            $_SESSION["message"] = "Login successful.";
            header("Location: dashboard.php");
        } else {
            $_SESSION["message"] = "Please complete your setup.";
            header("Location: setup.php");
        }
        exit();
    } else {
        $_SESSION["message"] = "Login failed: Incorrect username or password. Please try again.";
        header("Location: login.php");
    }
}
?>


<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <title>Login</title>
</head>

<body class="background_image">
    <div class="container mt-5">
        <div class="col-md-6 offset-md-3 form-overlay">
            <?php if (isset($_SESSION['message'])) : ?>
                <div class="alert alert-info text-center fade show" role="alert">
                    <?php echo $_SESSION['message']; ?>
                </div>
            <?php endif; ?>
            <h2 class="mb-3 text-white text-center">Login Form</h2>
            <form action="login.php" method="post">
                <div class="form-group">
                    <label class="text-white" for="username">Username:</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label class="text-white" for="password">Password:</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary" name="login">Login</button>
                <div class="mt-3 text-white">Don't have an account? <a href="register.php">Register here</a>.</div>
            </form>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>