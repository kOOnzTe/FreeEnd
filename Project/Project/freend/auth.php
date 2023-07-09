<?php

if( isset($_SESSION["user"])){
    $_SESSION["message"] = "Unauthorized User";
    header("Location: timeline.php");
    exit; 
  }