<?php
session_start();

// Verbindung zur Datenbank herstellen
$conn = mysqli_connect('localhost', 'root', '', 'educonnect');

if (!$conn) {
    die("Verbindung zur Datenbank fehlgeschlagen: " . mysqli_connect_error());
}

require_once 'Mobile_Detect.php'; // Pfad zur Mobile_Detect.php anpassen
$detect = new Mobile_Detect;

$rang = $_SESSION['rang']; 
if($rang != 'schueler') {
    header('Location: mainpage.php');
    exit();
}
else{
    $schueler_id = $_SESSION['user_id'];
}

// Fehlzeiten abrufen
$query = "SELECT sf.*, f.fach_name 
          FROM schueler_fehlzeiten sf 
          JOIN faecher f ON sf.fach_id = f.fach_id 
          WHERE sf.schueler_id = $schueler_id
          ORDER BY sf.time DESC";
$result = mysqli_query($conn, $query);
$fehlzeiten = [];

$sql = "SELECT * FROM schueler WHERE schueler_id = $schueler_id";
$result1 = mysqli_query($conn, $sql);
$schueler = mysqli_fetch_assoc($result1);

// Fehlzeiten in ein Array speichern und Gesamtminuten berechnen
$gesamtMinuten = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $fehlzeiten[] = $row;
    $gesamtMinuten += $row['zeit']; // Hier wird die Zeit in Minuten addiert
}

$vorname = $schueler['vorname']; // Vorname des Schülers
$nachname = $schueler['nachname']; // Nachname des Schülers

// Berechnung der Unterrichtsstunden und verbleibenden Minuten
$unterrichtsstunden = floor($gesamtMinuten / 45); // Ganze Stunden
$verbleibendeMinuten = $gesamtMinuten % 45; // Verbleibende Minuten

// HTML-Ausgabe
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alle Fehlzeiten</title>
    <link rel="stylesheet" href="fehlzeiten.css">
    <link rel="shortcut icon" href="picture.ico" type="image/x-icon">
</head>
<body class='SFehlzeitenAlleBody'>

<h1 class='SFehlzeitenAlleH1'>Alle Fehlzeiten</h1>
<h2 class='SFehlzeitenAlleH2'><?php echo htmlspecialchars($vorname) . ' ' . htmlspecialchars($nachname); ?></h2>

<!-- Anzeige der Fehlzeiten -->
<div style="text-align: center; margin: 20px 0;">
    <p>Bereits versäumte Unterrichtsstunden: <strong><?php echo $unterrichtsstunden; ?></strong> und <strong><?php echo $verbleibendeMinuten; ?></strong> Minuten</p>
    <p>Gesamte Fehlzeit in Minuten: <strong><?php echo $gesamtMinuten; ?></strong></p>
</div>

<a href="fehlzeiten.php" class="lehrerFehlzeitenButton">Zurück</a>

<div class="SFehlzeitenAlleTableResponsive">
    <table class="SFehlzeitenAlleTable">
        <thead>
            <tr>
                <th class='SFehlzeitenAlleTh'>Fach</th>
                <th class='SFehlzeitenAlleTh'>Fehlzeit (Minuten)</th>
                <th class='SFehlzeitenAlleTh'>Datum</th>
                <th class='SFehlzeitenAlleTh'>Bild</th>
                <th class='SFehlzeitenAlleTh'>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($fehlzeiten)): ?>
                <tr>
                    <td colspan="5" class='SFehlzeitenAlleTd'>Keine Fehlzeiten gefunden.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($fehlzeiten as $fehlzeit): ?>
                    <tr>
                        <td class='SFehlzeitenAlleTd'><?php echo htmlspecialchars($fehlzeit['fach_name']); ?></td>
                        <td class='SFehlzeitenAlleTd'><?php echo htmlspecialchars($fehlzeit['zeit']); ?></td>
                        <td class='SFehlzeitenAlleTd'><?php echo date("d.m.Y", strtotime($fehlzeit['time'])); ?></td>
                        <td class='SFehlzeitenAlleTd'>
                            <?php if ($fehlzeit['grund']): ?>
                                <a href="<?php echo htmlspecialchars($fehlzeit['grund']); ?>" target="_blank">Bild anzeigen</a>
                            <?php else: ?>
                                Kein Bild hochgeladen
                            <?php endif; ?>
                        </td>
                        <td class='SFehlzeitenAlleTd <?php echo $fehlzeit['bestaetigt'] == 2 ? 'red-text' : ''; ?>'>
                            <?php 
                            if ($fehlzeit['bestaetigt'] == 2) {
                                echo 'Nicht entschuldigt';
                            } elseif ($fehlzeit['bestaetigt'] == 1) {
                                echo 'Bestätigt';
                            } else {
                                echo 'Nicht bestätigt';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
<?php include 'navigation.php'; ?>
</html>

<?php
// Verbindung schließen
mysqli_close($conn);
?>