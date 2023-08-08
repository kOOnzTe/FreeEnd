<?php

    session_start();

    require_once "./db.php";

    extract($_POST);
    var_dump($_FILES);
    $rs = $db->prepare("select * from user where email = ?");
    $rs->execute([$email]);
    if( $rs->rowCount() === 1){ 
        $user = $rs->fetch(PDO::FETCH_ASSOC);
        var_dump($user);
        if ( password_verify($password, $user["password"])){
            $_SESSION["user"] = $user; 
            header("Location: timeline.php");
            exit;
        }
    }else{
        echo "no user with that email address";
    }

    $_SESSION["message"] = "Login is failed! Check your credentials!";
    header("Location: index.php");
