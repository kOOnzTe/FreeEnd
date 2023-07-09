<?php
session_start();
require_once "./auth.php";
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Free-end</title>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link rel="stylesheet" href="css/index.css">
</head>

<body>
  <nav class="all-nav">
    <div class="header-all">
      <div class="project-name">FREE-END</div>
    </div>
  </nav>
  <div class="container">
    <p class="log-text-2">Login from here:</p>
    <form action="login.php" method="POST">
      <div class="input-field input">
        <i class="material-icons prefix icon-white">account_box</i>
        <input name="email" type="text" class="validate" value="<?= isset($email) ? filter_var($email, FILTER_SANITIZE_FULL_SPECIAL_CHARS) : "" ?>" placeholder="example@gmail.com">
      </div>
      <div class="input-field input">
        <i class="material-icons prefix icon-white-2">vpn_key</i>
        <input name="password" type="password" placeholder="example-password" class="validate" value="">
      </div>
      <div class="center">
        <button class="button" type="submit" name="action">Login</button>
        <button class="button">
          <a href="register.php" class="button-text" type="submit" name="action">
            Register
          </a>
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

  <script>
    $(function() {

    })
  </script>
</body>

</html>