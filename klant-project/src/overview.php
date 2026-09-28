<?php
// overview.php: gebruikersbeheer voor admins (overzicht met Edit/Verwijderen).
session_start();
// Niet ingelogd: naar de login.
if (!isset($_SESSION['ingelogd'])) {
  header("Location: login.php");
  exit();
}
// Alleen admins. LET OP: dit stuurt niet-admins naar overview.php zelf door, wat een oneindige redirect-lus geeft. Stuur ze bijvoorbeeld naar voorraad.php.
if ($_SESSION['rol'] !== 'admin') {
  header("Location: overview.php?error=geenrechten");
  exit();
}


// Databaseverbinding.
$conn = require_once "partials/dbconnection.php";
?>
<!-- HTML-gedeelte: tabel met alle gebruikers. -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Overview</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

  <!-- Navigatie: uitloggen en nieuwe gebruiker aanmaken. -->
  <a href="logout.php">Uitloggen</a>
  <a href="create.php">Nieuwe gebruiker aanmaken</a>

  <!-- Gebruikerstabel; de rijen worden door PHP gegenereerd. -->
  <table>
    <tr>
      <th>Username</th>
      <th>Password</th>
      <th>Email</th>
      <th>rol</th>
      <th>Actie</th>
    </tr>
    <?php
    // Alle gebruikers ophalen.
    $stmt = $conn->prepare("SELECT * FROM user");
    $stmt->execute();
    $result = $stmt->get_result();
    // Geen rijen: stop met een melding (dit onderbreekt ook de rest van de pagina).
    if ($result->num_rows === 0)
      exit('No rows');
    // Per gebruiker een tabelrij. LET OP: username/email worden niet met htmlspecialchars ge-escaped (XSS-risico).
    while ($row = $result->fetch_assoc()) {
      echo "<tr>";
      echo "<td>" . $row['username'] . "</td>";
      // De wachtwoord-hash tonen is onnodig en onveilig; verwijder deze kolom.
      echo "<td>" . $row['password'] . "</td>";
      echo "<td>" . $row['email'] . "</td>";
      echo "<td>" . $row['rol'] . "</td>";
      echo "<td>";
      // Link naar bewerken.
      echo "<a href='update.php?id=" . $row['id'] . "'>Edit </a>";
      // Verwijderlink met JavaScript-bevestiging.
      echo "<a href='delete.php?id=" . $row['id'] . "'onclick=\"return confirm('Weet je zeker dat je deze gebruiker wilt verwijderen?');\">Verwijderen</a>";
      echo "</tr>";
    }
    echo "</table>";
    $stmt->close();
    ?>

</body>
</html>