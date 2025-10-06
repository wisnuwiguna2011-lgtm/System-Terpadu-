<?php
include "config.php";

$admin_user = "admin";
$admin_pass = password_hash("admin123", PASSWORD_DEFAULT);
$admin_role = "admin";

$sql = "INSERT INTO users (username, password, role) VALUES ('$admin_user', '$admin_pass', '$admin_role')";

if ($conn->query($sql) === TRUE) {
    echo "User admin berhasil dibuat!";
} else {
    echo "Error: " . $conn->error;
}
?>
