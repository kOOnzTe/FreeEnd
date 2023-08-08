<?php
session_start();

require_once './db.php';

$sender_id = $_POST['sender_id'];
$action = $_POST['action'];

if ($action == 'accept') {
    $status = 2;
} else if ($action == 'decline') {
    $status = 1;
} 

$stmt = $db->prepare("UPDATE friend_requests SET status = ? WHERE sender_id = ? AND receiver_id = ?");
if ($stmt->execute([$status, $sender_id, $_SESSION['user']['id']])) {
    echo "Friend request updated successfully.";
} else {
    echo "Failed to update friend request.";
}
?>