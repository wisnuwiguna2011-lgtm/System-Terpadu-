<?php
function logActivity($conn, $user_id, $username, $activity) {
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, username, activity) VALUES (?,?,?)");
    $stmt->bind_param("iss", $user_id, $username, $activity);
    $stmt->execute();
    $stmt->close();
}
