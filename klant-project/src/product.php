<?php
// product.php: werknemers/admins voegen een nieuw leerproduct toe aan de tabel `voorraad`.
session_start();
// Niet ingelogd: naar de login.
if (!isset($_SESSION['ingelogd'])) {
  header("Location: login.php");
  exit();
}
// Alleen werknemer of admin; anderen terug naar de voorraad.
if (!in_array($_SESSION['rol'], ['werknemer', 'admin'])) {
  header("Location: voorraad.php?error=geenrechten");
  exit();
}

// Databaseverbinding.
$conn = require_once "partials/dbconnection.php";
$error = "";
$succes = false;

// Bestaande soorten leer en kleuren ophalen voor de keuzelijsten.
// Bestaande soorten leer uit de voorraad voor de keuzelijst (ook gebruikt als whitelist).
$soortenLeer = [];
$res = $conn->query("SELECT DISTINCT `soort leer` FROM voorraad ORDER BY `soort leer`");
while ($r = $res->fetch_assoc()) $soortenLeer[] = $r['soort leer'];

// Bestaande kleuren, idem. Nieuwe soorten/kleuren kunnen dus alleen ontstaan als ze al ergens in de voorraad staan.
$kleuren = [];
$res = $conn->query("SELECT DISTINCT kleur FROM voorraad ORDER BY kleur");
while ($r = $res->fetch_assoc()) $kleuren[] = $r['kleur'];

// Formulier verwerken.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Invoer ophalen en trimmen.
  $soortLeer = trim($_POST['soort_leer'] ?? '');
  $kleur = trim($_POST['kleur'] ?? '');
  $dikte = trim($_POST['dikte'] ?? '');
  $gewicht = trim($_POST['gewicht'] ?? '');
  $prijs = trim($_POST['prijs'] ?? '');

  // Validatieregels als [voorwaarde-faalt, melding]; in_array met strict=true controleert tegen de whitelist.
  $fouten = [
    [!in_array($soortLeer, $soortenLeer, true), "Kies een geldige soort leer."],
    [!in_array($kleur, $kleuren, true), "Kies een geldige kleur."],
    [!is_numeric($dikte) || (float) $dikte <= 0, "Vul een geldige dikte in mm in."],
    [!is_numeric($gewicht) || (float) $gewicht <= 0, "Vul een geldig gewicht in kg in."],
    [!ctype_digit($prijs) || (int) $prijs <= 0, "Vul een geldige prijs in (heel getal)."],
  ];
  // Toon de eerste fout en stop.
  foreach ($fouten as [$faalt, $bericht]) {
    if ($faalt) { $error = $bericht; break; }
  }

  // Geen fouten: waarden omzetten naar het opslagformaat.
  if (!$error) {
    // Dikte en gewicht worden als tekst met eenheid opgeslagen (bv. '2.5mm', '1.5kg') omdat de kolommen varchar zijn.
    $dikteStr = number_format((float) $dikte, 1, '.', '') . 'mm';
    $gewichtStr = number_format((float) $gewicht, 1, '.', '') . 'kg';
    $prijsInt = (int) $prijs;
    // Kolom `klant` is NOT NULL maar niet relevant voor voorraad; lege string als workaround.
    $klant = ""; // kolom `klant` is NOT NULL maar hier niet relevant; leeg laten.

    // Product opslaan; `soort leer` heeft backticks door de spatie in de kolomnaam.
    $stmt = $conn->prepare("INSERT INTO voorraad (klant, gewicht, kleur, dikte, `soort leer`, prijs) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $klant, $gewichtStr, $kleur, $dikteStr, $soortLeer, $prijsInt);
    $stmt->execute();
    $stmt->close();

    // Toon na de insert een succesmelding.
    $succes = true;
  }
}
?>
<!-- HTML-gedeelte: formulier. -->
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Product toevoegen</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

  <a href="voorraad.php">Terug naar voorraad</a> | <a href="logout.php">Uitloggen</a>

  <h1>Nieuw product toevoegen aan voorraad</h1>

  <!-- Fout- en succesmeldingen. -->
  <?php if ($error): ?><p style="color:red;"><?= htmlspecialchars($error) ?></p><?php endif; ?>
  <?php if ($succes): ?><p style="color:green;">Product is toegevoegd aan de voorraad.</p><?php endif; ?>

  <!-- Formulier voor een nieuw product. -->
  <form method="POST" action="product.php">
    <label>Soort leer</label><br>
    <select name="soort_leer" required>
      <option value="">-- Kies soort leer --</option>
      <?php foreach ($soortenLeer as $soort): ?>
        <option value="<?= htmlspecialchars($soort) ?>"><?= htmlspecialchars($soort) ?></option>
      <?php endforeach; ?>
    </select>
    <br><br>

    <label>Kleur</label><br>
    <select name="kleur" required>
      <option value="">-- Kies kleur --</option>
      <?php foreach ($kleuren as $k): ?>
        <option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($k) ?></option>
      <?php endforeach; ?>
    </select>
    <br><br>

    <label>Dikte (mm)</label><br>
    <input type="number" name="dikte" step="0.1" min="0.1" placeholder="bv. 2.5" required>
    <br><br>

    <label>Gewicht (kg)</label><br>
    <input type="number" name="gewicht" step="0.1" min="0.1" placeholder="bv. 1.5" required>
    <br><br>

    <label>Prijs (euro)</label><br>
    <input type="number" name="prijs" step="1" min="1" placeholder="bv. 45" required>
    <br><br>

    <input type="submit" value="Toevoegen">
  </form>

</body>
</html>