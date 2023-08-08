<?php
require_once './db.php'; 

session_start();

$userId = $_SESSION['user']['id'];
$postId = intval($_POST['post_id']);
$interactionType = $_POST['interaction_type'];

$stmt = $db->prepare("
    INSERT INTO likes (post_id, user_id, interaction_type) 
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE 
    interaction_type = VALUES(interaction_type)
");

$stmt->execute([$postId, $userId, $interactionType]);

echo json_encode([
    'type' => $interactionType
]);
?>