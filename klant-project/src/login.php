<?php
// login.php: inlogpagina. Controleert gebruikersnaam + wachtwoord en zet de sessie.
// Sessie starten om de inlogstatus te kunnen bewaren.
session_start();
$error = "";

// Alleen bij het versturen van het formulier de database benaderen.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $conn = require_once "partials/dbconnection.php";

  // Gebruiker opzoeken op gebruikersnaam (prepared statement).
  $stmt = $conn->prepare("SELECT * FROM user WHERE username = ?");
  $stmt->bind_param("s", $_POST['name']);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $stmt->close();

  // Vergelijk het ingevoerde wachtwoord met de bcrypt-hash.
  if ($row && password_verify($_POST['password'], $row['password'])) {
    
    // Inlogstatus en rol in de sessie. Tip: roep hier session_regenerate_id(true) aan tegen session fixation.
    $_SESSION['ingelogd'] = true;
    $_SESSION['rol'] = $row['rol'];
 
    // Doorsturen op basis van rol: admin naar gebruikersbeheer, anderen naar de voorraad.
    if ($row['rol'] === 'admin') {
      header("Location: overview.php");
    } else {
      header("Location: voorraad.php");
    }
    exit();

  } else {
    // Bewust een algemene melding, zodat niet zichtbaar is of de gebruikersnaam bestaat.
    $error = "Combinatie van gebruikersnaam en wachtwoord klopt niet.";
  }
}
?>
<!-- HTML-gedeelte: loginformulier. -->
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <link rel="stylesheet" href="password.css">
</head>

<body style="background-color: #1c3c30">
  <div id="loginContainer">
    <h2 id="loginTitle">Login</h2>

    <?php if ($error) echo "<p>" . $error . "</p>"; ?>

    <form method="POST" action="login.php">
      <!-- Gebruikersnaam (POST-veld 'name'). -->
      <input type="text" id="inlogUsername" name="name" placeholder="Name" required>
      <br>
      <!-- E-mailveld wordt niet gebruikt bij het inloggen (alleen username + wachtwoord); kan weg of ontbreken. -->
      <input type="email" id="inlogEmail" name="email" placeholder="Email" required>
      <br>
      <input type="password" id="inlogPassword" name="password" placeholder="Password" required>
      <div id="loginRegister">
        <input type="submit" name="knop" value="Verstuur">
        <!-- Verwijst naar register.php; dat bestand is niet meegeleverd. -->
        <button><a href="register.php">register</a></button>
      </div>
    </form>
  </div>
</body>

</html>