<?php
session_start();
require_once "./auth.php";
if (!empty($_POST)) 
{

    extract($_POST);

    require_once "./db.php";
    require_once "./Upload.php";

    $re = '/(\w+)@((?:\w+\.){1,3}(?:com|tr))/iu';

    if (preg_match($re, $email) === 0) 
    {
        $_SESSION["message"] = "Please enter valid email!";
        $_SESSION["email"] = $email;
    }

    $upload = new Upload("profile", "images");

    $rs = $db->prepare("insert into user (name, email, password, profile) values (?,?,?,?)");
    $rs->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $upload->file()]); 

    header("Location: index.php");

    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Free-end</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <link rel="stylesheet" href="css/index.css">
</head>

<body>
    <nav class="all-nav">
        <div class="header-all">
            <div class="project-name">FREE-END</div>
        </div>
    </nav>
    <div class="container">

        <p class="reg-text">Register from here:</p>

        <form action="" method="post" enctype="multipart/form-data">
            <div class="input-field">
                <i class="material-icons prefix icon-white">person</i>
                <input name="username" type="text" class="validate" value="<?= isset($username) ? filter_var($username, FILTER_SANITIZE_FULL_SPECIAL_CHARS) : "" ?>" placeholder="name lastname">
            </div>

            <div class="input-field">

                <i class="material-icons prefix icon-white">
                    account_box</i>

                <input name="email" type="text" class="validate" value="<?= isset($email) ? filter_var($email, FILTER_SANITIZE_FULL_SPECIAL_CHARS) : "" ?>" placeholder="e-mail">
            </div>
            <div class="input-field">
                <i class="material-icons prefix icon-white">vpn_key</i>
            
                <input name="password" type="text" class="validate" placeholder="password">
            </div>

            <div class="file-field input-field">
                <i class="material-icons prefix icon-white icon-attach">attach_file</i>

                <div class="btn button-file button-image-profile">
                    <span class="file-text ">Image</span>

                    <input type="file" name="profile">
                </div>

                <div class="file-path-wrapper">
                    <input class="file-path validate" type="text">
                </div>
            </div>
            <div class="center center2">

                <button class="btn button-image-profile" type="submit" name="action">Register
                </button>
            </div>
        </form>
    </div>
    <?php
    if (isset($_SESSION["message"])) {
        $err = $_SESSION["message"];
        echo "<script> M.toast({html: '$err'}); </script>";
        unset($_SESSION["message"]);
    }
    ?>
</body>

</html>