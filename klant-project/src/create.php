<?php
// create.php: admin-pagina om een nieuwe gebruiker aan te maken (username, e-mail, wachtwoord, rol).
session_start();
// Alleen ingelogde gebruikers. LET OP: de rol wordt hier niet gecontroleerd, dus elke ingelogde gebruiker kan gebruikers aanmaken (ook admins).
if (!isset($_SESSION['ingelogd'])) {
  header("Location: login.php");
  exit();
}

// Databaseverbinding.
$conn = require_once "partials/dbconnection.php";
$error = "";

// Formulier verwerken.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Invoer ophalen (zonder trim/validatie van rol; rol zou gecontroleerd moeten worden tegen een vaste lijst).
  $username = $_POST['username'];
  $email = $_POST['email'];
  $password = $_POST['password'];
  $rol = $_POST['rol'];

  // Validaties. LET OP: elke check overschrijft $error, en de insert in de else-tak hangt alleen af van het e-mailveld.
  // Gevolg: een te korte gebruikersnaam of wachtwoord blokkeert het aanmaken niet. Beter: verzamel fouten in een array en insert alleen als die leeg is.
  if (strlen($username) < 5) {
    $error = "Gebruikersnaam moet minimaal 5 tekens zijn.";
  }
  if (empty($password) || strlen($password) < 8) {
    $error = "Wachtwoord moet minimaal 8 tekens zijn.";
  }
  if (empty($email)) {
    $error = "E-mailadres is verplicht.";
  } else {
    // Wachtwoord hashen met bcrypt (PASSWORD_DEFAULT). Het wachtwoord zelf wordt nooit opgeslagen.
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    // Gebruiker opslaan met een prepared statement.
    $stmt = $conn->prepare("INSERT INTO user (username, email, password, rol) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $hashedPassword, $rol);
    $stmt->execute();
    $stmt->close();
    // Terug naar het overzicht met bevestigingsvlag; exit() stopt de uitvoering.
    header("Location: overview.php?created=1");
    exit();
  }
}

?>
<!-- HTML-gedeelte: formulier. -->
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

    <!-- Foutmelding tonen (vaste teksten, dus hier veilig; gebruik htmlspecialchars als dit ooit gebruikersinvoer bevat). -->
    <?php if ($error)
      echo "<p>" . $error . "</p>"; ?>

    <!-- Aanmaakformulier. -->
    <form method="POST" action="create.php">
      <input type="text" name="username" placeholder="Username" required>
      <br>
      <input type="email" name="email" placeholder="Email" required>
      <br>
      <input type="password" name="password" placeholder="Password" required>
      <br>
      <!-- Beschikbare rollen: gebruiker, werknemer, admin. -->
      <select name="rol">
        <option value="gebruiker">Gebruiker</option>
        <option value="werknemer">Werknemer</option>
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