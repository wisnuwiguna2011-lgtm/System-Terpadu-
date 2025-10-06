<?php
session_start();
include "config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username=? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        echo "<pre>";
        echo "DEBUG USER:\n";
        print_r($user);
        echo "\nPassword yang dimasukkan: " . $password;
        echo "\nPassword Verify: ";
        var_dump(password_verify($password, $user['password']));
        echo "</pre>";
    } else {
        echo "⚠️ Username tidak ditemukan di tabel users";
    }
}
?>

<form method="POST">
    <input type="text" name="username" placeholder="username">
    <input type="password" name="password" placeholder="password">
    <button type="submit">TEST LOGIN</button>
</form>
