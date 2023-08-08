<?php
require_once 'db.php';
session_start();

$last_timestamp = isset($_POST['lastPost']) ? $_POST['lastPost'] : 0;
$friendIds = isset($_SESSION['friendIds']) ? $_SESSION['friendIds'] : [];

if (!empty($friendIds)) {
    $inQuery = implode(',', array_fill(0, count($friendIds), '?'));

    $postsStmt = $db->prepare("
        SELECT * 
        FROM posts 
        WHERE user_id IN ($inQuery) AND timestamp < ? 
        ORDER BY timestamp DESC 
        LIMIT 10
    ");

    $params = array_merge($friendIds, [$last_timestamp]);
    $postsStmt->execute($params);

    $posts = $postsStmt->fetchAll(PDO::FETCH_ASSOC);


    foreach ($posts as &$post) {
        $commentsStmt = $db->prepare("
            SELECT * 
            FROM comments 
            WHERE post_id = ? 
            ORDER BY timestamp DESC
        ");
        $commentsStmt->execute([$post['id']]);
        $post['comments'] = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("
            SELECT * FROM user 
            WHERE id = ?
        ");
        $stmt->execute([$post['user_id']]);
        $post['owner'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode($posts);
} else {
    echo json_encode([]); 
}
