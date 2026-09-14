<?php
session_start();
if (!isset($_SESSION['ingelogd'])) {
  header("Location: login.php");
  exit();
}

$conn = require_once "partials/dbconnection.php";
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Voorraad</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

  <a href="logout.php">Uitloggen</a>

  <h1>Voorraad overzicht</h1>

  <table border="1" cellpadding="6" cellspacing="0">
    <tr>
      <th>ID</th>
      <th>Gewicht</th>
      <th>Kleur</th>
      <th>Dikte</th>
      <th>Soort leer</th>
      <th>Prijs</th>
      <th>Bestelling</th>
      <th>Status bestelling</th>
      <th>Klant</th>
    </tr>
    <?php
    $sql = "SELECT v.idvoorraad, v.gewicht, v.kleur, v.dikte, v.`soort leer` AS soort_leer,
                   v.prijs, b.idbestelling, b.status AS bestelling_status, k.bedrijfsnaam
            FROM voorraad v
            LEFT JOIN bestellingen b ON v.bestelling = b.idbestelling
            LEFT JOIN klant k ON b.klant = k.klantid
            ORDER BY v.idvoorraad";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
      echo "<tr><td colspan='9'>Geen voorraad gevonden</td></tr>";
    } else {
      while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['idvoorraad']) . "</td>";
        echo "<td>" . htmlspecialchars($row['gewicht']) . "</td>";
        echo "<td>" . htmlspecialchars($row['kleur']) . "</td>";
        echo "<td>" . htmlspecialchars($row['dikte']) . "</td>";
        echo "<td>" . htmlspecialchars($row['soort_leer']) . "</td>";
        echo "<td>" . htmlspecialchars($row['prijs']) . "</td>";
        echo "<td>" . htmlspecialchars($row['idbestelling'] ?? '-') . "</td>";
        echo "<td>" . htmlspecialchars($row['bestelling_status'] ?? '-') . "</td>";
        echo "<td>" . htmlspecialchars($row['bedrijfsnaam'] ?? '-') . "</td>";
        echo "</tr>";
      }
    }
    $stmt->close();
    ?>
  </table>

</body>
</html>