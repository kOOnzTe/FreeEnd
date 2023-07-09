<?php
session_start();

require_once 'db.php'; 

extract($_POST);

$userId =  $_SESSION['user']['id'];


$stmt = $db->prepare("
    INSERT INTO comments (post_id, user_id, username, comment) 
    VALUES (?, ?, ?, ?)
");

if ($stmt->execute([$post_id, $userId, $username, $comment])) {
    
} else {
    
}

echo json_encode([
    'comment' => $comment,
    'name' => $username
]);

?>
