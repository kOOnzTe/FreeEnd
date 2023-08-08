<?php
    
    setcookie("PHPSESSID", "", 1, "/"); 
    session_destroy(); 

    header("Location: index.php");

