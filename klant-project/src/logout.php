<?php
// logout.php: beeindigt de sessie en stuurt terug naar de login.
session_start();
// Alle sessiegegevens verwijderen.
session_destroy();
header("Location: login.php");
exit();
?>