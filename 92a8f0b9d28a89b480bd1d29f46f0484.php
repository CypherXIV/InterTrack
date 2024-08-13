<?php
require 'db.php';

$numberOfCodes = 10;
$codes = [];

for ($i = 0; $i < $numberOfCodes; $i++) {
    $codes[] = bin2hex(random_bytes(8));
}

try {
    $stmt = $pdo->prepare("INSERT INTO admin_registration_codes (secret_code) VALUES (:secret_code)");

    foreach ($codes as $code) {
        $stmt->bindParam(':secret_code', $code);
        $stmt->execute();
    }

    echo "Codes generated and stored successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
