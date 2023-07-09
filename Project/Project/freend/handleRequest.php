<?php
session_start();
require_once "./db.php";

if (!isset($_SESSION['user'])) {
    echo 'You must be logged in to perform this action.';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sender_id = $_SESSION['user']['id']; 
    $receiver_id = $_POST['receiver_id'];  

    $stmt = $db->prepare("SELECT * FROM friend_requests WHERE sender_id = :sender_id AND receiver_id = :receiver_id");
    $stmt->execute(['sender_id' => $sender_id, 'receiver_id' => $receiver_id]);
    $exists = $stmt->fetch();

    if ($exists) {
        echo 'Friend request already sent.';
        exit;
    }
    $stmt = $db->prepare("INSERT INTO friend_requests (sender_id, receiver_id, status) VALUES (:sender_id, :receiver_id, 0)");
    $stmt->execute(['sender_id' => $sender_id, 'receiver_id' => $receiver_id]);

    echo 'Friend request sent.';
} else {
    echo 'Invalid request method.';
}
?>