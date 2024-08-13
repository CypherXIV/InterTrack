<?php
require 'db.php';
require 'vendor/autoload.php';
session_start();

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
  header('Location: login.php');
  exit;
}

try {
  $stmt = $pdo->query("SELECT id, username, student_id, field, total_hours, setup_completed FROM users ORDER BY id ASC");
  $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  die("Error occurred:" . $e->getMessage());
}

if (isset($_GET["logout"]) && $_GET["logout"] == "1") {
  $_SESSION = [];

  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
      session_name(),
      '',
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

if (isset($_POST['export_csv'])) {
  $stmt = $pdo->prepare("SELECT id, username, student_id, field, total_hours FROM users ORDER BY id ASC");
  $stmt->execute();
  $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=data.csv');

  $output = fopen('php://output', 'w');

  if (!empty($results)) {
    fputcsv($output, array_keys($results[0]), ",");
  }

  foreach ($results as $row) {
    fputcsv($output, $row);
  }

  fclose($output);
  exit();
}

?>



<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - User List</title>
  <link rel="stylesheet" href="css/styles.css">
</head>

<body>
  <div class="admin-user-list">
    <h2>User List</h2>
    <h1>
      <button id="exportBtn">Export to CSV</button>
    </h1>
    <div class="logout-text">Click here to <a href="admin_user_list.php?logout=1" class="btn btn-warning logout-button">log out</a>. </div>

    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Username</th>
          <th>Student ID</th>
          <th>Field</th>
          <th>Total Hours</th>
          <th>Setup Completed</th>
          <th>View Log Entries</th>
          <th>Start Chat</th>
        </tr>
      </thead>
      <tbody> <?php foreach ($users as $user) : ?> <tr>
            <td> <?php echo htmlspecialchars($user['id']); ?> </td>
            <td>
              <span class="editable" onclick="toggleEdit(this)"> <?php echo htmlspecialchars($user['username']); ?> </span>
              <input type="text" class="edit-field" value="
											
											<?php echo htmlspecialchars($user['username']); ?>" style="display:none;">
              <button onclick="saveData(this.previousElementSibling, 
												
												<?php echo $user['id']; ?>, 'username')" style="display:none;">Confirm </button>
            </td>
            <td>
              <span class="editable" onclick="toggleEdit(this)"> <?php echo htmlspecialchars($user['student_id']); ?> </span>
              <input type="text" class="edit-field" pattern="^[A-Za-z]\d{8}$" value="
												
												<?php echo htmlspecialchars($user['student_id']); ?>" style="display:none;">
              <button onclick="saveData(this.previousElementSibling, 
													
													<?php echo $user['id']; ?>, 'student_id')" style="display:none;">Confirm </button>
            </td>
            <td>
              <span class="editable" onclick="toggleEdit(this)"> <?php echo htmlspecialchars($user['field']); ?> </span>
              <input type="text" class="edit-field" value="
													
													<?php echo htmlspecialchars($user['field']); ?>" style="display:none;">
              <button onclick="saveData(this.previousElementSibling, 
														
														<?php echo $user['id']; ?>, 'field')" style="display:none;">Confirm </button>
            </td>
            <td>
              <span class="editable" onclick="toggleEdit(this)"> <?php echo htmlspecialchars($user['total_hours']); ?> </span>
              <input type="text" class="edit-field" pattern="^\d{1,3}(\.\d{1,2})?$" value="
														
														<?php echo htmlspecialchars($user['total_hours']); ?>" style="display:none;">
              <button onclick="saveData(this.previousElementSibling, 
															
															<?php echo $user['id']; ?>, 'total_hours')" style="display:none;">Confirm </button>
            </td>
            <td>
              <span class="editable" onclick="toggleEdit(this)"> <?php echo htmlspecialchars($user['setup_completed']); ?> </span>
              <input type="number" class="edit-field" min="0" max="1" value="
															
															<?php echo htmlspecialchars($user['setup_completed']); ?>" style="display:none;">
              <button onclick="saveData(this.previousElementSibling, 
																
																<?php echo $user['id']; ?>, 'setup_completed')" style="display:none;">Confirm </button>
            </td>
            <td>
              <button onclick="openModal(
																
																<?php echo $user['id']; ?>)" class="btn-view-logs">View Log Entries </button>
            </td>
            <td>
              <button onclick="openChat(<?php echo htmlspecialchars($user['id']); ?>)" class="btn btn-start-chat">Start Chat</button>
            </td>
          </tr> <?php endforeach; ?> </tbody>
    </table>
  </div>
  <div id="logEntriesModal" class="modal" style="display:none;">
    <div class="modal-content">
      <h4>User Log Entries</h4>
      <div id="modalBody">Loading...</div>
    </div>
    <div class="modal-footer">
      <button onclick="closeModal()" class="modal-close btn">Close</button>
    </div>
  </div>
  <script>
    function toggleEdit(element) {
      let input = element.nextElementSibling;
      let confirmButton = input.nextElementSibling;
      input.style.display = 'inline-block';
      confirmButton.style.display = 'inline-block';
      input.value = input.value.trim();
      input.focus();
    }

    function saveData(input, userId, field) {
      let value = input.value.trim();

      if (value === '') {
        alert('This field cannot be left empty.');
        input.focus();
        return;
      }

      if (!input.checkValidity()) {
        alert('Please enter a valid value.');
        input.focus();
        return;
      }

      let dataToSend = JSON.stringify({
        id: userId,
        field: field,
        value: value
      });

      fetch('update_field.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: dataToSend
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            let span = input.previousElementSibling;
            let confirmButton = input.nextElementSibling;
            span.innerHTML = value;
            span.style.display = 'block';
            input.style.display = 'none';
            confirmButton.style.display = 'none';
          } else {
            alert('Error updating data');
          }
        })
        .catch(error => {
          alert('Failed to update: ' + error.message);
        });
    }


    function openModal(userId) {
      const url = 'get_user_logs.php';
      const params = `userId=${userId}`;
      const newWindow = window.open(url + '?' + params, 'User Log Entries', 'height=600,width=800');
      if (window.focus) {
        newWindow.focus();
      }
    }

    function openChat(userId) {
      const url = 'chat_init_admin.php';
      const params = `userId=${userId}`;
      const newWindow = window.open(url + '?' + params, 'ChatWindow', 'height=600,width=800');
      if (window.focus) {
        newWindow.focus();
      }
    }
  </script>
  <script>
    document.getElementById('exportBtn').addEventListener('click', function() {
      fetch('admin_user_list.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: 'export_csv=true'
        })
        .then(response => response.blob())
        .then(blob => {
          const url = window.URL.createObjectURL(blob);
          const a = document.createElement('a');
          a.style.display = 'none';
          a.href = url;
          a.download = 'user_list.csv';
          document.body.appendChild(a);
          a.click();
          window.URL.revokeObjectURL(url);
        })
        .catch(error => console.error('Error:', error));
    });
  </script>
</body>

</html>