<?php
session_start();
if (!isset($_SESSION['ingelogd'])) {
  header("Location: login.php");
  exit();
}

$conn = require_once "partials/dbconnection.php";


$zoekterm = trim($_GET['zoek'] ?? '');
$statusFilter = $_GET['status'] ?? '';


$statussen = [];
$statusResult = $conn->query("SELECT DISTINCT status FROM bestellingen ORDER BY status");
while ($row = $statusResult->fetch_assoc()) {
  $statussen[] = $row['status'];
}


$sql = "SELECT v.idvoorraad, v.gewicht, v.kleur, v.dikte, v.`soort leer` AS soort_leer,
               v.prijs, b.idbestelling, b.status AS bestelling_status, k.bedrijfsnaam
        FROM voorraad v
        LEFT JOIN bestellingen b ON v.bestelling = b.idbestelling
        LEFT JOIN klant k ON b.klant = k.klantid
        WHERE 1=1";

$params = [];
$types = "";


if ($zoekterm !== '') {
  $sql .= " AND (v.kleur LIKE ? OR v.`soort leer` LIKE ? OR k.bedrijfsnaam LIKE ?)";
  $like = "%" . $zoekterm . "%";
  $params[] = $like;
  $params[] = $like;
  $params[] = $like;
  $types .= "sss";
}


if ($statusFilter !== '') {
  $sql .= " AND b.status = ?";
  $params[] = $statusFilter;
  $types .= "s";
}

$sql .= " ORDER BY v.idvoorraad";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
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

  <form method="GET" action="voorraad.php" style="margin-bottom: 15px;">
    <input
      type="text"
      name="zoek"
      placeholder="Zoek op kleur, soort leer of klant..."
      value="<?php echo htmlspecialchars($zoekterm); ?>"
    >

    <select name="status">
      <option value="">Alle statussen</option>
      <?php foreach ($statussen as $status) { ?>
        <option value="<?php echo htmlspecialchars($status); ?>" <?php if ($statusFilter === $status) echo "selected"; ?>>
          <?php echo htmlspecialchars(ucfirst($status)); ?>
        </option>
      <?php } ?>
    </select>

    <input type="submit" value="Filteren">
    <a href="voorraad.php">Reset</a>
  </form>

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