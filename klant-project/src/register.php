<?php
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $conn = require_once "partials/dbconnection.php";

  $username = $_POST['username'];
  $email = $_POST['email'];
  $password = $_POST['password'];

  if (strlen($username) < 5) {
    $error = "Gebruikersnaam moet minimaal 5 tekens zijn.";
  } elseif (strlen($password) < 8) {
    $error = "Wachtwoord moet minimaal 8 tekens zijn.";
  } else {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO user (username, password, email) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $hashedPassword, $email);
    $stmt->execute();
    $stmt->close();

    header("Location: login.php?registered=1");
    exit();
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register</title>
  <link rel="stylesheet" href="password.css">
</head>

<body style="background-color: #1c3c30">
  <div id="loginContainer">
    <h2 id="loginTitle">Register</h2>

    <?php if ($error) echo "<p>" . $error . "</p>"; ?>

    <form method="POST" action="register.php">
      <input type="text" id="inlogUsername" name="username" placeholder="Username" required>
      <br>
      <input type="email" id="inlogEmail" name="email" placeholder="Email" required>
      <br>
      <input type="password" id="inlogPassword" name="password" placeholder="Password" required>
      <div id="loginRegister">
        <input type="submit" value="Register">
        <button><a href="login.php">Login</a></button>
      </div>
    </form>
  </div>
</body>

</html>