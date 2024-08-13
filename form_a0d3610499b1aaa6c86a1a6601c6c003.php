<?php
require 'db.php';
session_start();
$errorMessage = '';

if (isset($_POST['checkExistence'])) {
    $username = $_POST['username'] ?? '';
    $facultyId = $_POST['facultyId'] ?? '';
    $response = ['exists' => false];

    if (!empty($username) || !empty($facultyId)) {
        $query = "SELECT id FROM admins WHERE username = ? OR faculty_id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$username, $facultyId]);
        if ($stmt->fetch()) {
            $response['exists'] = true;
        }
    }
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["register"])) {
    $username = trim($_POST["username"]) . " (admin)";
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $facultyId = trim($_POST["facultyId"]);
    $secretCode = trim($_POST["secretCode"]);
    $pattern = '/^[A-Za-z]\d{8}$/';

    if (!preg_match($pattern, $facultyId)) {
        $_SESSION["message"] = "Invalid faculty ID format. Please use the format of one letter followed by 8 digits.";
        header("Location: form_a0d3610499b1aaa6c86a1a6601c6c003.php?error=invalidfacultyid");
        exit();
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT id FROM admin_registration_codes WHERE secret_code = :secret_code AND used = false");
        $stmt->bindParam(':secret_code', $secretCode);
        $stmt->execute();
        $code = $stmt->fetch();

        if (!$code) {
            $pdo->rollBack();
            $_SESSION["message"] = "Invalid or already used secret code.";
            header("Location: form_a0d3610499b1aaa6c86a1a6601c6c003.php?error=invalidcode");
            exit();
        }

        $updateStmt = $pdo->prepare("UPDATE admin_registration_codes SET used = true WHERE id = :id");
        $updateStmt->bindParam(':id', $code['id']);
        $updateStmt->execute();

        $insertStmt = $pdo->prepare("INSERT INTO admins (username, password, faculty_id) VALUES (:username, :password, :facultyId)");
        $insertStmt->bindParam(':username', $username);
        $insertStmt->bindParam(':password', $password);
        $insertStmt->bindParam(':facultyId', $facultyId);
        $insertStmt->execute();

        $pdo->commit();
        header("Location: form_a0d3610499b1aaa6c86a1a6601c6c003.php?success=true");
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $errorMessage = "Registration failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <title>Admin Registration</title>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#username, #facultyId").on("blur", function() {
                var username = $('#username').val();
                var facultyId = $('#facultyId').val();
                if (username.length > 0 || facultyId.length > 0) {
                    $.ajax({
                        url: 'form_a0d3610499b1aaa6c86a1a6601c6c003.php',
                        type: 'POST',
                        data: {
                            checkExistence: true,
                            username: username,
                            facultyId: facultyId
                        },
                        success: function(data) {
                            var response = JSON.parse(data);
                            if (response.exists) {
                                alert('Username or Faculty ID already exists.');
                                $('#username, #facultyId').val('');
                            }
                        }
                    });
                }
            });
        });
    </script>
</head>

<body class="background_image">
    <div class="container">
        <div class="col-md-6 offset-md-3 form-overlay">
            <?php if (!empty($_SESSION['message'])) : ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($_SESSION['message']); ?>
                    <?php unset($_SESSION['message']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['success'])) : ?>
                <div class="alert alert-success" role="alert">
                    Registration successful. You can now log in.
                </div>
            <?php endif; ?>
            <h2 class="mb-3 text-center text-white">Admin Registration</h2>
            <form action="form_a0d3610499b1aaa6c86a1a6601c6c003.php" method="post">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" name="username" id="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" name="password" id="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="facultyId">Faculty ID:</label>
                    <input type="text" name="facultyId" id="facultyId" class="form-control" required pattern="^[A-Za-z]\d{8}$">
                </div>
                <div class="form-group">
                    <label for="secretCode">Secret Code:</label>
                    <input type="text" name="secretCode" id="secretCode" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary" name="register">Register Admin</button>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js" integrity="sha384-ZzWtVE/fwJy3YwNRc2tB4lP5t4R3UsJkSD6vQeJowzih+VDRszTz/pbpp4jMx5xj" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8sh+Wy4Ck4SOF4y4Ck4C2DgHfViXydVeLm+JDM" crossorigin="anonymous"></script>
</body>

</html>