<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config.php';

$folders = $conn->query("SELECT * FROM folders");
while($row = $folders->fetch_assoc()) {
    print_r($row);
}
