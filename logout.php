
  <?php
session_start(); // Session starten
session_unset(); // Alle Session-Variablen leeren
session_destroy(); // Die Session beenden
header("Location: index.php"); // Weiterleitung zur Login-Seite
exit();
?>

