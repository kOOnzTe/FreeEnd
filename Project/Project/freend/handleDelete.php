<?php
session_start();

require_once 'db.php';


$userId = $_SESSION['user']['id'];

if(isset($_POST['post_id'])) {
    $postId = $_POST['post_id'];
    $stmt = $db->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
    $stmt->execute([$postId, $userId]);
}

if(isset($_POST['friend_id'])) {
    $friend_id = $_POST['friend_id'];
    $stmt = $db->prepare("UPDATE friend_requests SET status = 1 WHERE sender_id = ? AND receiver_id = ?");
    $stmt->execute([ $friend_id, $userId]);
}


?>