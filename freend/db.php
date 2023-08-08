<?php
$dsn = "mysql:host=localhost;dbname=freend" ;
$dbuser = "root" ;
$dbpass = "root" ;

try {
  $db = new PDO($dsn, $dbuser, $dbpass) ;
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION) ;
} catch( PDOException $ex) {
    echo "<p>Connection Error:</p>" ;
    echo "<p>", $ex->getMessage(), "</p>" ;
    exit ;
}