<?php
session_start();
if (!isset($_SESSION['ingelogd'])) {
  header("Location: login.php");
  exit();
}

$conn = require_once "partials/dbconnection.php";

$id = $_GET['id'] ?? null;
if ($id) {
  $stmt = $conn->prepare("DELETE FROM user WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->close();
}

header("Location: overview.php?deleted=1");
exit();
?>