<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Noten Übersicht Schüler</title>
    <link rel="stylesheet" href="educonnect.css">
    <link rel="shortcut icon" href="picture.ico" type="image/x-icon">
</head>
<body>

<?php
session_start(); 

$conn = mysqli_connect("localhost", "root", "", "educonnect");
mysqli_set_charset($conn, "utf8mb4");

if (!isset($_SESSION['rang']) || $_SESSION['rang'] !== 'schueler') {
    header("Location: .php");
    exit();
}

if (!$conn) {
    die("Verbindung zur Datenbank fehlgeschlagen: " . mysqli_connect_error());
}

if (!isset($_SESSION['user_id'])) {
    echo "<h2>Schüler nicht angemeldet. Bitte melden Sie sich an.</h2>";
    exit();
}

 
$sql_noten = "SELECT n.note, n.gewichtung, f.fach_name, pr.art, n.freigabe, l.vorname AS lehrer_vorname, l.nachname AS lehrer_nachname, n.bemerkung
              FROM noten n 
              JOIN faecher f ON n.fach_id = f.fach_id 
              JOIN pruefungsart pr ON n.pruefungs_id = pr.pruefungs_id 
              JOIN lehrer l ON n.lehrer_id = l.lehrer_id 
              WHERE n.schueler_id = '{$_SESSION['user_id']}' AND n.freigabe = '1'"; 


$res_noten = mysqli_query($conn, $sql_noten);

if (!$res_noten) {
    die("Fehler bei der Abfrage der Einzelnoten: " . mysqli_error($conn));
} 

 
$durchschnitt_sum = 0;
$gesamt_gewichtung = 0;

echo "<h1>Noten Übersicht</h1>";
echo "<table border='1'>
        <tr> 
            <th>Lehrer</th>
            <th>Fach</th>
            <th>Note</th>
            <th>Prüfungsart</th>
            <th>Gewichtung (%)</th>
            <th>Bemerkung</th>
        </tr>";

while ($row = mysqli_fetch_assoc($res_noten)) {
    $note = $row['note'];
    $gewichtung = $row['gewichtung'];
    $fach_name = $row['fach_name'];
    $lehrer_name = $row['lehrer_vorname'] . " " . $row['lehrer_nachname'];
    $bemerkung = $row['bemerkung'];

    echo "<tr>
            <td>$lehrer_name</td>
            <td>$fach_name</td>
            <td>$note</td>
            <td>{$row['art']}</td>
            <td>$gewichtung</td>
            <td>$bemerkung</td>
          </tr>";

    
    $durchschnitt_sum += $note * ($gewichtung / 100);
    $gesamt_gewichtung += $gewichtung;
}

echo "</table>";

 
$gesamt_durchschnitt = $gesamt_gewichtung > 0 ? $durchschnitt_sum / ($gesamt_gewichtung / 100) : 0;
echo "<h2>Gesamtdurchschnitt: " . number_format($gesamt_durchschnitt, 2) . "</h2>";

mysqli_close($conn);  
?>
</body><?php include 'navigation.php'; ?>
</html>