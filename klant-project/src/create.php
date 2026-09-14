<?php
session_start();
if (!isset($_SESSION['ingelogd'])) {
  header("Location: login.php");
  exit();
}

$conn = require_once "partials/dbconnection.php";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = $_POST['username'];
  $email = $_POST['email'];
  $password = $_POST['password'];
  $rol = $_POST['rol'];

  if (strlen($username) < 5) {
    $error = "Gebruikersnaam moet minimaal 5 tekens zijn.";
  }
  if (empty($password) || strlen($password) < 8) {
    $error = "Wachtwoord moet minimaal 8 tekens zijn.";
  }
  if (empty($email)) {
    $error = "E-mailadres is verplicht.";
  } else {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO user (username, email, password, rol) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $hashedPassword, $rol);
    $stmt->execute();
    $stmt->close();
    header("Location: overview.php?created=1");
    exit();
  }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create</title>
  <link rel="stylesheet" href="password.css">
</head>

<body style="background-color: #1c3c30">
  <div id="loginContainer">
    <h2 id="loginTitle">Nieuwe gebruiker</h2>

    <?php if ($error)
      echo "<p>" . $error . "</p>"; ?>

    <form method="POST" action="create.php">
      <input type="text" name="username" placeholder="Username" required>
      <br>
      <input type="email" name="email" placeholder="Email" required>
      <br>
      <input type="password" name="password" placeholder="Password" required>
      <br>
      <select name="rol">
        <option value="gebruiker">Gebruiker</option>
        <option value="admin">Admin</option>
      </select>
      <div id="loginRegister">
        <input type="submit" value="Aanmaken">
        <button><a href="overview.php">Terug</a></button>
      </div>
    </form>
  </div>
</body>

</html>