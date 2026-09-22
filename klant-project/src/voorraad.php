<?php
session_start();
if (!isset($_SESSION['ingelogd'])) {
  header("Location: login.php");
  exit();
}

$conn = require_once "partials/dbconnection.php";
$magBeheren = in_array($_SESSION['rol'], ['werknemer', 'admin']);
$actie = $_GET['actie'] ?? '';

//  Verwijderen
if ($actie === 'verwijderen') {
  if (!$magBeheren) {
    header("Location: voorraad.php?error=geenrechten");
    exit();
  }
  $id = $_GET['id'] ?? null;
  if ($id) {
    $stmt = $conn->prepare("DELETE FROM voorraad WHERE idvoorraad = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
  }
  header("Location: voorraad.php?deleted=1");
  exit();
}

//  Bewerken
if ($actie === 'bewerken') {
  if (!$magBeheren) {
    header("Location: voorraad.php?error=geenrechten");
    exit();
  }

  $id = $_GET['id'] ?? null;
  if (!$id) {
    header("Location: voorraad.php");
    exit();
  }

  $stmt = $conn->prepare("SELECT * FROM voorraad WHERE idvoorraad = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $product = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$product) {
    header("Location: voorraad.php");
    exit();
  }

  $soortenLeer = [];
  $res = $conn->query("SELECT DISTINCT `soort leer` FROM voorraad ORDER BY `soort leer`");
  while ($r = $res->fetch_assoc()) $soortenLeer[] = $r['soort leer'];

  $kleuren = [];
  $res = $conn->query("SELECT DISTINCT kleur FROM voorraad ORDER BY kleur");
  while ($r = $res->fetch_assoc()) $kleuren[] = $r['kleur'];

  $bewerkError = "";
  $soortLeer = $product['soort leer'];
  $kleur = $product['kleur'];
  $dikte = rtrim($product['dikte'], 'mm');
  $gewicht = rtrim($product['gewicht'], 'kg');
  $prijs = $product['prijs'];

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $soortLeer = trim($_POST['soort_leer'] ?? '');
    $kleur = trim($_POST['kleur'] ?? '');
    $dikte = trim($_POST['dikte'] ?? '');
    $gewicht = trim($_POST['gewicht'] ?? '');
    $prijs = trim($_POST['prijs'] ?? '');

    $fouten = [
      [!in_array($soortLeer, $soortenLeer, true), "Kies een geldige soort leer."],
      [!in_array($kleur, $kleuren, true), "Kies een geldige kleur."],
      [!is_numeric($dikte) || (float) $dikte <= 0, "Vul een geldige dikte in mm in."],
      [!is_numeric($gewicht) || (float) $gewicht <= 0, "Vul een geldig gewicht in kg in."],
      [!ctype_digit($prijs) || (int) $prijs <= 0, "Vul een geldige prijs in (heel getal)."],
    ];
    foreach ($fouten as [$faalt, $bericht]) {
      if ($faalt) { $bewerkError = $bericht; break; }
    }

    if (!$bewerkError) {
      $dikteStr = number_format((float) $dikte, 1, '.', '') . 'mm';
      $gewichtStr = number_format((float) $gewicht, 1, '.', '') . 'kg';
      $prijsInt = (int) $prijs;

      $stmt = $conn->prepare("UPDATE voorraad SET gewicht = ?, kleur = ?, dikte = ?, `soort leer` = ?, prijs = ? WHERE idvoorraad = ?");
      $stmt->bind_param("ssssii", $gewichtStr, $kleur, $dikteStr, $soortLeer, $prijsInt, $id);
      $stmt->execute();
      $stmt->close();

      header("Location: voorraad.php?updated=1");
      exit();
    }
  }
}

//  Overzicht (lijst + zoeken/filteren) 
if ($actie === '') {
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
}
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
  <?php if ($magBeheren): ?> | <a href="product.php">Product toevoegen</a><?php endif; ?>

  <?php if ($actie === 'bewerken'): ?>

    <h1>Product #<?= htmlspecialchars($id) ?> bewerken</h1>

    <?php if ($bewerkError): ?><p style="color:red;"><?= htmlspecialchars($bewerkError) ?></p><?php endif; ?>

    <form method="POST" action="voorraad.php?actie=bewerken&id=<?= htmlspecialchars($id) ?>">
      <label>Soort leer</label><br>
      <select name="soort_leer" required>
        <?php foreach ($soortenLeer as $soort): ?>
          <option value="<?= htmlspecialchars($soort) ?>" <?= $soort === $soortLeer ? 'selected' : '' ?>><?= htmlspecialchars($soort) ?></option>
        <?php endforeach; ?>
      </select>
      <br><br>

      <label>Kleur</label><br>
      <select name="kleur" required>
        <?php foreach ($kleuren as $k): ?>
          <option value="<?= htmlspecialchars($k) ?>" <?= $k === $kleur ? 'selected' : '' ?>><?= htmlspecialchars($k) ?></option>
        <?php endforeach; ?>
      </select>
      <br><br>

      <label>Dikte (mm)</label><br>
      <input type="number" name="dikte" step="0.1" min="0.1" value="<?= htmlspecialchars($dikte) ?>" required>
      <br><br>

      <label>Gewicht (kg)</label><br>
      <input type="number" name="gewicht" step="0.1" min="0.1" value="<?= htmlspecialchars($gewicht) ?>" required>
      <br><br>

      <label>Prijs (euro)</label><br>
      <input type="number" name="prijs" step="1" min="1" value="<?= htmlspecialchars($prijs) ?>" required>
      <br><br>

      <input type="submit" value="Opslaan">
    </form>

  <?php else: ?>

    <h1>Voorraad overzicht</h1>

    <?php if (isset($_GET['updated'])): ?><p style="color:green;">Product is bijgewerkt.</p><?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?><p style="color:green;">Product is verwijderd.</p><?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'geenrechten'): ?><p style="color:red;">Je hebt geen rechten voor deze actie.</p><?php endif; ?>

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
        <?php if ($magBeheren): ?><th>Actie</th><?php endif; ?>
      </tr>
      <?php
      if ($result->num_rows === 0) {
        $colspan = $magBeheren ? 10 : 9;
        echo "<tr><td colspan='$colspan'>Geen voorraad gevonden</td></tr>";
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
          if ($magBeheren) {
            echo "<td>";
            echo "<a href='voorraad.php?actie=bewerken&id=" . $row['idvoorraad'] . "'>Bewerken</a> ";
            echo "<a href='voorraad.php?actie=verwijderen&id=" . $row['idvoorraad'] . "' onclick=\"return confirm('Weet je zeker dat je dit product wilt verwijderen?');\">Verwijderen</a>";
            echo "</td>";
          }
          echo "</tr>";
        }
      }
      $stmt->close();
      ?>
    </table>

  <?php endif; ?>

</body>
</html>