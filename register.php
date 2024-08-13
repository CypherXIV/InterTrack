<?php
session_start();
require "db.php";

$_SESSION["message"] = 'Hi! Welcome to InterActive! Please enter your account username and password! Have a wonderful day! If you do have an account, please resort to the "Login here." link below.';

if (isset($_POST['checkExistence'])) {
    $username = $_POST['username'] ?? '';
    $studentId = $_POST['studentId'] ?? '';
    $response = ['exists' => false];

    if (!empty($username) || !empty($studentId)) {
        $query = "SELECT id FROM users WHERE username = ? OR student_id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$username, $studentId]);
        if ($stmt->fetch()) {
            $response['exists'] = true;
        }
    }
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['checkExistence'])) {
    $username = trim($_POST["username"]);
    $password = password_hash(trim($_POST["password"]), PASSWORD_DEFAULT);
    $role = 'student';
    $studentId = trim($_POST["studentId"]);

    $pattern = '/^[A-Za-z]\d{8}$/';

    if (!preg_match($pattern, $studentId)) {
        $_SESSION["message"] = "Invalid student ID format. Please use the format of one letter followed by 8 digits.";
        header("Location: register.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, student_id) VALUES (:username, :password, :role, :studentId)");
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":password", $password);
        $stmt->bindParam(":role", $role);
        $stmt->bindParam(":studentId", $studentId);

        $stmt->execute();
        $_SESSION["message"] = "User registered successfully. Please log in.";
        header("Location: login.php");
        exit();
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) {
            $_SESSION["message"] = "An account with this username or student ID already exists.";
        } else {
            $_SESSION["message"] = "An error occurred: " . $e->getMessage();
        }
        header("Location: register.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
    <link rel="stylesheet" href="css/styles.css">
    <title>Registration</title>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#username, #studentId").on("blur", function() {
                var username = $('#username').val();
                var studentId = $('#studentId').val();
                if (username.length > 0 || studentId.length > 0) {
                    $.ajax({
                        url: 'register.php',
                        type: 'POST',
                        data: {
                            checkExistence: true,
                            username: username,
                            studentId: studentId
                        },
                        success: function(data) {
                            var response = JSON.parse(data);
                            if (response.exists) {
                                alert('Username or Student ID already exists.');
                                $('#username, #studentId').val('');
                            }
                        }
                    });
                }
            });
        });
    </script>
</head>

<body class="background_image">
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-6 offset-md-3 form-overlay">
                <?php if (isset($_SESSION['message'])) : ?>
                    <div class="alert alert-info text-center fade show" role="alert">
                        <?php echo $_SESSION['message']; ?>
                        <?php unset($_SESSION['message']); ?>
                    </div>
                <?php endif; ?>
                <h2 class="mb-3 text-white text-center">Registration Form</h2>
                <form action="register.php" method="post">
                    <div class="form-group">
                        <label class="text-white" for="username">Username:</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label class="text-white" for="password">Password:</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label class="text-white" for="studentId">Student ID:</label>
                        <input type="text" class="form-control" id="studentId" name="studentId" pattern="^[A-Za-z]\d{8}$" required>
                    </div>
                    <button type="submit" class="btn btn-primary" name="register">Register</button>
                    <div class="mt-3 text-white text-center">
                        Already have an account? <a href="login.php">Login here</a>.
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>