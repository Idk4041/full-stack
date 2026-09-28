<?php
// update.php: gebruiker bewerken (?id=...). Wachtwoord laten leeg = ongewijzigd.
session_start();
// Niet ingelogd: naar de login. LET OP: geen adminrol-check.
if (!isset($_SESSION['ingelogd'])) {
  header("Location: login.php");
  exit();
}

// Databaseverbinding.
$conn = require_once "partials/dbconnection.php";

// Id uit de URL; zonder id terug naar het overzicht.
$id = $_GET['id'] ?? null;
if (!$id) {
  header("Location: overview.php");
  exit();
}

// Hier worden meerdere fouten verzameld in een array (beter dan het patroon in create.php).
$errors = [];

// huidige gegevens ophalen
// Huidige gegevens ophalen om het formulier vooraf in te vullen.
$stmt = $conn->prepare("SELECT * FROM user WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Onbekende id: terug naar het overzicht.
if (!$user) {
  header("Location: overview.php");
  exit();
}

// Voorinvullen met de bestaande waarden.
$username = $user['username'];
$email = $user['email'];
$rol = $user['rol'];

// Formulier verwerkt: nieuwe waarden overnemen en valideren.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Invoer overnemen; bij een fout blijft de ingevoerde waarde in het formulier staan.
  $username = $_POST['username'];
  $email = $_POST['email'];
  $password = $_POST['password'];
  $rol = $_POST['rol'];

  // Validatie gebruikersnaam.
  if (strlen($username) < 5) {
    $errors[] = "Gebruikersnaam moet minimaal 5 tekens zijn.";
  }
  if (empty($email)) {
    $errors[] = "E-mailadres is verplicht.";
  }
  // Wachtwoord is optioneel; alleen controleren als er iets is ingevuld.
  if (!empty($password) && strlen($password) < 8) {
    $errors[] = "Wachtwoord moet minimaal 8 tekens zijn.";
  }

  // Alleen opslaan zonder fouten.
  if (empty($errors)) {
    // Twee varianten: mét nieuw (gehasht) wachtwoord of zonder.
    if (!empty($password)) {
      $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $conn->prepare("UPDATE user SET username = ?, email = ?, password = ?, rol = ? WHERE id = ?");
      $stmt->bind_param("ssssi", $username, $email, $hashedPassword, $rol, $id);
    } else {
      $stmt = $conn->prepare("UPDATE user SET username = ?, email = ?, rol = ? WHERE id = ?");
      $stmt->bind_param("sssi", $username, $email, $rol, $id);
    }
    $stmt->execute();
    $stmt->close();

    // Klaar: terug naar het overzicht.
    header("Location: overview.php?updated=1");
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
  <title>Update</title>
  <link rel="stylesheet" href="password.css">
</head>

<body style="background-color: #1c3c30">
  <div id="loginContainer">
    <h2 id="loginTitle">Gebruiker aanpassen</h2>

    <!-- Foutmeldingen tonen. -->
    <?php if (!empty($errors)) { ?>
      <div style="color: #ffb3b3; margin-bottom: 10px;">
        <?php foreach ($errors as $error) {
          echo "<p>" . $error . "</p>";
        } ?>
      </div>
    <?php } ?>

    <!-- Formulier post naar dezelfde pagina met dezelfde id. Waarden worden met htmlspecialchars ge-escaped. -->
    <form method="POST" action="update.php?id=<?php echo $id; ?>">
      <input type="text" id="inlogUsername" name="username" placeholder="Username"
        value="<?php echo htmlspecialchars($username); ?>" required>
      <br>
      <input type="email" id="inlogEmail" name="email" placeholder="Email"
        value="<?php echo htmlspecialchars($email); ?>" required>
      <br>
      <input type="password" id="inlogPassword" name="password" placeholder="Nieuw wachtwoord (leeg = ongewijzigd)">
      <br>
      <!-- De huidige rol wordt voorgeselecteerd. -->
      <select name="rol">
        <option value="gebruiker" <?php if ($rol === "gebruiker") echo "selected"; ?>>Gebruiker</option>
        <option value="werknemer" <?php if ($rol === "werknemer") echo "selected"; ?>>Werknemer</option>
        <option value="admin" <?php if ($rol === "admin") echo "selected"; ?>>Admin</option>
      </select>
      <div id="loginRegister">
        <input type="submit" value="Opslaan">
        <button><a href="overview.php">Terug</a></button>
      </div>
    </form>
  </div>
</body>

</html>