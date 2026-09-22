<?php

$conn = require_once "partials/dbconnection.php";

$error = "";
$geplaatsteBestelling = null;

// Voorraad per product/kleur ophalen.
$voorraadPerProduct = [];
$stockResult = $conn->query("SELECT `soort leer` AS product, kleur, gewicht FROM voorraad WHERE bestelling IS NULL");
while ($row = $stockResult->fetch_assoc()) {
  $product = $row['product'];
  $kleur = $row['kleur'];
  $gewicht = (float) $row['gewicht'];

  if (!isset($voorraadPerProduct[$product])) {
    $voorraadPerProduct[$product] = [];
  }
  if (!isset($voorraadPerProduct[$product][$kleur])) {
    $voorraadPerProduct[$product][$kleur] = 0;
  }
  $voorraadPerProduct[$product][$kleur] += $gewicht;
}

// Bestaande klanten ophalen voor de keuzelijst.
$klanten = [];
$klantenResult = $conn->query("SELECT klantid, bedrijfsnaam, telefoonnummer, email FROM klant ORDER BY bedrijfsnaam");
while ($row = $klantenResult->fetch_assoc()) {
  $klanten[$row['klantid']] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $klantKeuze = $_POST['klant_keuze'] ?? '';
  $bedrijfsnaam = trim($_POST['bedrijfsnaam'] ?? '');
  $telefoonnummer = trim($_POST['telefoonnummer'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $product = $_POST['product'] ?? '';
  $kleur = $_POST['kleur'] ?? '';
  $hoeveelheid = trim($_POST['hoeveelheid'] ?? '');
  $besteldatum = date('Y-m-d');

  $beschikbaar = $voorraadPerProduct[$product][$kleur] ?? null;

  $isNieuweKlant = ($klantKeuze === 'nieuw');
  $isBestaandeKlant = ($klantKeuze !== '' && $klantKeuze !== 'nieuw' && isset($klanten[$klantKeuze]));

  if ($klantKeuze === '' || (!$isNieuweKlant && !$isBestaandeKlant)) {
    $error = "Kies een klant uit de lijst of voeg een nieuwe klant toe.";
  } elseif ($isNieuweKlant && $bedrijfsnaam === '') {
    $error = "Bedrijfsnaam is verplicht.";
  } elseif ($isNieuweKlant && ($telefoonnummer === '' || !ctype_digit($telefoonnummer))) {
    $error = "Vul een geldig telefoonnummer in (alleen cijfers).";
  } elseif ($isNieuweKlant && strlen($telefoonnummer) > 10) {
    $error = "Telefoonnummer is te lang: gebruik maximaal 10 cijfers, zonder spaties of +31.";
  } elseif ($isNieuweKlant && ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
    $error = "Vul een geldig e-mailadres in.";
  } elseif ($product === '' || !isset($voorraadPerProduct[$product])) {
    $error = "Kies een geldig product.";
  } elseif ($kleur === '' || $beschikbaar === null) {
    $error = "Kies een geldige kleur voor dit product.";
  } elseif ($hoeveelheid === '' || !is_numeric($hoeveelheid) || (float) $hoeveelheid <= 0) {
    $error = "Vul een geldige hoeveelheid in kg in.";
  } elseif ((float) $hoeveelheid > $beschikbaar) {
    $error = "Er is niet genoeg voorraad: maximaal " . number_format($beschikbaar, 2) . " kg beschikbaar voor deze combinatie.";
  } else {
    $hoeveelheidKg = number_format((float) $hoeveelheid, 2, '.', '');

    if ($isBestaandeKlant) {
      $klantId = (int) $klantKeuze;
      $bedrijfsnaamWeergave = $klanten[$klantKeuze]['bedrijfsnaam'];
    } else {
      // Nieuwe klant: bestaat er al iemand met dit e-mailadres? Zo ja, gegevens bijwerken.
      $stmt = $conn->prepare("SELECT klantid FROM klant WHERE email = ?");
      $stmt->bind_param("s", $email);
      $stmt->execute();
      $klantResult = $stmt->get_result();
      $klantRow = $klantResult->fetch_assoc();
      $stmt->close();

      if ($klantRow) {
        $klantId = $klantRow['klantid'];
        $stmt = $conn->prepare("UPDATE klant SET bedrijfsnaam = ?, telefoonnummer = ? WHERE klantid = ?");
        $stmt->bind_param("sii", $bedrijfsnaam, $telefoonnummer, $klantId);
        $stmt->execute();
        $stmt->close();
      } else {
        $stmt = $conn->prepare("INSERT INTO klant (bedrijfsnaam, telefoonnummer, email) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $bedrijfsnaam, $telefoonnummer, $email);
        $stmt->execute();
        $klantId = $stmt->insert_id;
        $stmt->close();
      }
      $bedrijfsnaamWeergave = $bedrijfsnaam;
    }

    $status = "nieuw";
    $stmt = $conn->prepare("INSERT INTO bestellingen (klant, product, kleur, hoeveelheid, status, besteldatum) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $klantId, $product, $kleur, $hoeveelheidKg, $status, $besteldatum);
    $stmt->execute();
    $nieuwId = $stmt->insert_id;
    $stmt->close();

    // Alleen de geplaatste bestelling tonen
    $geplaatsteBestelling = [
      'idbestelling' => $nieuwId,
      'bedrijfsnaam' => $bedrijfsnaamWeergave,
      'product' => $product,
      'kleur' => $kleur,
      'hoeveelheid' => $hoeveelheidKg,
      'status' => $status,
      'besteldatum' => $besteldatum,
    ];
  }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bestellen</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

  <h1>Bestelling plaatsen</h1>

  <?php if ($error) echo "<p style='color:red;'>" . htmlspecialchars($error) . "</p>"; ?>

  <?php if ($geplaatsteBestelling) { ?>
    <div style="border:1px solid #408A71; padding:15px; margin-bottom:20px;">
      <h2>Bedankt voor je bestelling!</h2>
      <p>Bestelnummer: <strong><?php echo htmlspecialchars($geplaatsteBestelling['idbestelling']); ?></strong></p>
      <table border="1" cellpadding="6" cellspacing="0">
        <tr><th>Bedrijf</th><td><?php echo htmlspecialchars($geplaatsteBestelling['bedrijfsnaam']); ?></td></tr>
        <tr><th>Product</th><td><?php echo htmlspecialchars($geplaatsteBestelling['product']); ?></td></tr>
        <tr><th>Kleur</th><td><?php echo htmlspecialchars($geplaatsteBestelling['kleur']); ?></td></tr>
        <tr><th>Hoeveelheid</th><td><?php echo htmlspecialchars($geplaatsteBestelling['hoeveelheid']); ?> kg</td></tr>
        <tr><th>Status</th><td><?php echo htmlspecialchars(ucfirst($geplaatsteBestelling['status'])); ?></td></tr>
        <tr><th>Besteldatum</th><td><?php echo htmlspecialchars($geplaatsteBestelling['besteldatum']); ?></td></tr>
      </table>
      <p><a href="bestellen.php">Nog een bestelling plaatsen</a></p>
    </div>
  <?php } else { ?>

    <?php if (empty($voorraadPerProduct)) { ?>
      <p>Er is momenteel geen voorraad beschikbaar om te bestellen.</p>
    <?php } else { ?>

    <form method="POST" action="bestellen.php" id="bestelForm">
      <label>Klant</label><br>
      <select id="klantKeuze" name="klant_keuze" required>
        <option value="">-- Kies klant --</option>
        <option value="nieuw">+ Nieuwe klant toevoegen</option>
        <?php foreach ($klanten as $klantid => $klant) { ?>
          <option value="<?php echo htmlspecialchars($klantid); ?>"><?php echo htmlspecialchars($klant['bedrijfsnaam']); ?></option>
        <?php } ?>
      </select>
      <br><br>

      <div id="nieuweKlantVelden" style="display:none;">
        <label>Bedrijfsnaam</label><br>
        <input type="text" name="bedrijfsnaam" placeholder="Bedrijfsnaam">
        <br><br>

        <label>Telefoonnummer</label><br>
        <input type="text" name="telefoonnummer" placeholder="Telefoonnummer">
        <br><br>

        <label>E-mailadres</label><br>
        <input type="email" name="email" placeholder="E-mailadres">
        <br><br>
      </div>

      <label>Product</label><br>
      <select id="product" name="product" required>
        <option value="">-- Kies product --</option>
        <?php foreach (array_keys($voorraadPerProduct) as $productNaam) { ?>
          <option value="<?php echo htmlspecialchars($productNaam); ?>"><?php echo htmlspecialchars($productNaam); ?></option>
        <?php } ?>
      </select>
      <br><br>

      <label>Kleur</label><br>
      <select id="kleur" name="kleur" required>
        <option value="">-- Eerst product kiezen --</option>
      </select>
      <br><br>

      <label>Hoeveelheid (kg)</label><br>
      <input type="number" id="hoeveelheid" name="hoeveelheid" step="0.01" min="0.01" placeholder="Hoeveelheid in kg" required>
      <span id="beschikbaarText" style="margin-left:10px; font-style:italic;"></span>
      <br><br>

      <p style="font-style:italic;">De besteldatum wordt automatisch op vandaag gezet.</p>

      <input type="submit" value="Bestellen">
    </form>

    <script>
      const voorraadData = <?php echo json_encode($voorraadPerProduct); ?>;

      const klantSelect = document.getElementById('klantKeuze');
      const nieuweKlantVelden = document.getElementById('nieuweKlantVelden');
      const bedrijfsnaamInput = nieuweKlantVelden.querySelector('input[name="bedrijfsnaam"]');
      const telefoonInput = nieuweKlantVelden.querySelector('input[name="telefoonnummer"]');
      const emailInput = nieuweKlantVelden.querySelector('input[name="email"]');

      klantSelect.addEventListener('change', function () {
        const isNieuw = this.value === 'nieuw';
        nieuweKlantVelden.style.display = isNieuw ? 'block' : 'none';
        bedrijfsnaamInput.required = isNieuw;
        telefoonInput.required = isNieuw;
        emailInput.required = isNieuw;
      });

      const productSelect = document.getElementById('product');
      const kleurSelect = document.getElementById('kleur');
      const hoeveelheidInput = document.getElementById('hoeveelheid');
      const beschikbaarText = document.getElementById('beschikbaarText');

      productSelect.addEventListener('change', function () {
        const product = this.value;

        kleurSelect.innerHTML = '<option value="">-- Kies kleur --</option>';
        hoeveelheidInput.value = '';
        hoeveelheidInput.removeAttribute('max');
        beschikbaarText.textContent = '';

        if (product && voorraadData[product]) {
          Object.keys(voorraadData[product]).forEach(function (kleur) {
            const option = document.createElement('option');
            option.value = kleur;
            option.textContent = kleur;
            kleurSelect.appendChild(option);
          });
        }
      });

      kleurSelect.addEventListener('change', function () {
        const product = productSelect.value;
        const kleur = this.value;

        hoeveelheidInput.value = '';

        if (product && kleur && voorraadData[product] && voorraadData[product][kleur] !== undefined) {
          const beschikbaar = voorraadData[product][kleur];
          hoeveelheidInput.max = beschikbaar;
          beschikbaarText.textContent = 'Beschikbaar: ' + beschikbaar.toFixed(2) + ' kg';
        } else {
          hoeveelheidInput.removeAttribute('max');
          beschikbaarText.textContent = '';
        }
      });
    </script>

    <?php } ?>

  <?php } ?>

</body>
</html>