<?php
// delete.php: verwijdert een gebruiker via ?id=... en stuurt terug naar het overzicht.
session_start();
// Alleen ingelogde gebruikers. LET OP: geen rolcontrole en verwijderen via een GET-link (kwetsbaar voor CSRF); gebruik liever POST + admin-check.
if (!isset($_SESSION['ingelogd'])) {
  header("Location: login.php");
  exit();
}

$conn = require_once "partials/dbconnection.php";

// Id uit de URL; null als het ontbreekt.
$id = $_GET['id'] ?? null;
if ($id) {
  // Prepared statement; bind_param 'i' forceert een geheel getal.
  $stmt = $conn->prepare("DELETE FROM user WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->close();
}

// Terug naar het overzicht met melding.
header("Location: overview.php?deleted=1");
exit();
?>