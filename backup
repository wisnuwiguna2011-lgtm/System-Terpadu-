<?php
session_start();
include "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username='$username' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            // simpan session
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];

            // redirect sesuai role
            switch ($row['role']) {
                case 'admin':
                    header("Location: dashboard_admin.php");
                    break;
                case 'keuangan':
                    header("Location: dashboard_keuangan.php");
                    break;
                case 'kepegawaian':
                    header("Location: dashboard_kepegawaian.php");
                    break;
                case 'arsiparis':
                    header("Location: dashboard_kearsipan.php");
                    break;
                default:
                    header("Location: login.php");
            }
            exit;
        } else {
            echo "❌ Password salah!";
        }
    } else {
        echo "❌ Username tidak ditemukan!";
    }
}
?>
