<?php
    require_once "./db.php";
    require_once "./Upload.php";

    session_start(); 

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $text = $_POST['text'];

        $upload = new Upload('imagename', 'images');  

        $imageName = $upload->file();
        if ($upload->error()) {
            die('Error uploading image file: ' . $upload->error());
        }

        $sender_id = $_SESSION['user']['id']; 
        $stmt = $db->prepare("INSERT INTO posts (user_id, text, image) VALUES (?, ?, ?)");
        $stmt->execute([$sender_id, $text, $imageName]);

        echo json_encode([
            'content' => $text,
            'image' => $imageName
        ]);
    }
?>
