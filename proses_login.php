<?php
session_start();
include 'config.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if ($username && $password) {
    $sql = "SELECT * FROM users WHERE username = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['username'] = $row['username'];
            $_SESSION['role']     = $row['role'];
            header("Location: dashboard.php");
            exit;
        } else {
            header("Location: login.php?error=wrong_pass");
            exit;
        }
    } else {
        header("Location: login.php?error=no_user");
        exit;
    }
} else {
    header("Location: login.php?error=empty_fields");
    exit;
}
