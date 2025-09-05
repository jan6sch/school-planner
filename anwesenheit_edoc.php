<?php
session_start();

// Verbindung zur Datenbank herstellen
$conn = mysqli_connect('localhost', 'root', '', 'educonnect');

if (!$conn) {
    die("Verbindung zur Datenbank fehlgeschlagen: " . mysqli_connect_error());
}

// Lehrer ID und Rang aus der Session
$lehrer_id = $_SESSION['user_id']; // Beispielwert, sollte aus der Session kommen
$rang = $_SESSION['rang'];

// Zugriffskontrolle
if ($rang != 'lehrer') {
    die("Kein Zugriff");
}

// Überprüfen, ob die POST-Daten gesetzt sind
if (isset($_POST['fach_id']) && isset($_POST['stunde_id'])) {
    $fach_id = $_POST['fach_id'];          // Fach-ID von der vorherigen Seite
    $stunde_id = $_POST['stunde_id'];      // Stunde-ID von der vorherigen Seite
} else {
    die("Fach-ID oder Stunde-ID nicht gesetzt.");
}

// Aktuellen Wochentag ermitteln
$wochentag = date('w'); // 0 (Sonntag) bis 6 (Samstag)

// Schüler im Kurs abrufen
$query = "SELECT DISTINCT s.schueler_id, s.vorname, s.nachname 
          FROM stundenplan_schueler sps 
          JOIN schueler s ON sps.schueler_id = s.schueler_id 
          WHERE sps.lehrer_id = $lehrer_id AND sps.fach_id = $fach_id AND sps.tag_id = $wochentag";

$result = mysqli_query($conn, $query);

// Überprüfen, ob die Abfrage erfolgreich war
if (!$result) {
    die("Fehler bei der Abfrage: " . mysqli_error($conn));
}

$schueler = [];
while ($row = mysqli_fetch_assoc($result)) {
    $schueler[] = $row;
}

// Überprüfen, ob Anwesenheit bereits gespeichert wurde
$query = "SELECT * FROM anwesenheits_protokoll 
          WHERE lehrer_id = $lehrer_id AND fach_id = $fach_id AND datum = CURDATE()";

$result = mysqli_query($conn, $query);
$anwesenheitGespeichert = mysqli_num_rows($result) > 0;

// Fehlzeiten abrufen, falls bereits gespeichert
$fehlzeiten = [];

$query = "SELECT * FROM schueler_fehlzeiten 
          WHERE lehrer_id = $lehrer_id AND fach_id = $fach_id AND stunde_id = $stunde_id";

$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $fehlzeiten[$row['schueler_id']] = $row;
}

// Variablen für die Bestätigungsmeldung
$message = '';
$showForm = true;

// Anwesenheit speichern oder bearbeiten
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['anwesenheit'])) {
    foreach ($_POST['anwesenheit'] as $schueler_id => $data) {
        $anwesenheit = $data['anwesenheit']; // 'anwesend' oder 'abwesend'
        $fehlzeit = isset($data['fehlzeit']) ? (int)$data['fehlzeit'] : 0; // Minuten, falls abwesend
        $bestaetigt = isset($data['fehlzeit_bestaetigt']) ? '1' : '0'; // Checkbox-Wert

        if ($anwesenheit == 'abwesend') {
            // Abwesenheit in der Tabelle schueler_fehlzeiten speichern oder aktualisieren
            if (isset($fehlzeiten[$schueler_id])) {
                // Update der Fehlzeiten
                $query = "UPDATE schueler_fehlzeiten 
                          SET zeit = $fehlzeit, bestaetigt = '$bestaetigt'
                          WHERE schueler_id = $schueler_id AND lehrer_id = $lehrer_id AND fach_id = $fach_id AND stunde_id = $stunde_id";
                mysqli_query($conn, $query);
            } else {
                // Neue Fehlzeit einfügen
                $query = "INSERT INTO schueler_fehlzeiten (schueler_id, lehrer_id, stunde_id, fach_id, zeit, time, bestaetigt) 
                          VALUES ($schueler_id, $lehrer_id, $stunde_id, $fach_id, $fehlzeit, NOW(), '$bestaetigt')";
                mysqli_query($conn, $query);
            }

            // Anwesenheit in der Protokoll-Tabelle speichern (falls noch nicht vorhanden)
            $query = "SELECT * FROM anwesenheits_protokoll 
                      WHERE lehrer_id = $lehrer_id AND fach_id = $fach_id AND stunde_id = $stunde_id AND datum = CURDATE()";
            $result = mysqli_query($conn, $query);
            if (mysqli_num_rows($result) == 0) {
                $query = "INSERT INTO anwesenheits_protokoll (lehrer_id, fach_id, stunde_id, datum) 
                          VALUES ($lehrer_id, $fach_id, $stunde_id, CURDATE())";
                mysqli_query($conn, $query);
            }
        } else {
            // Wenn der Schüler als anwesend markiert wird, die Fehlzeit löschen
            if (isset($fehlzeiten[$schueler_id])) {
                $query = "DELETE FROM schueler_fehlzeiten 
                          WHERE schueler_id = $schueler_id AND lehrer_id = $lehrer_id AND fach_id = $fach_id AND stunde_id = $stunde_id";
                mysqli_query($conn, $query);
            }
        }
    }

    // Bestätigungsmeldung setzen und Formular ausblenden
    $message = "Anwesenheit erfolgreich gespeichert!";
    $showForm = false;
}

// HTML-Ausgabe
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anwesenheitsverwaltung</title>
    <link rel="stylesheet" href="fehlzeiten.css">
    <link rel="shortcut icon" href="picture.ico" type="image/x-icon">
</head>
<body>
    <h1>Anwesenheitsverwaltung</h1>

    <p><a href="unterricht_edoc.php" id='anwesenheitbuttontop'>Zurück zur Klassenübersicht</a></p>

    <?php if ($message):?><p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <?php if ($showForm): ?>
        <?php if ($anwesenheitGespeichert): ?>
            <p>Die Anwesenheit wurde bereits für heute gespeichert. Sie können die Fehlzeiten bearbeiten.</p>
        <?php endif; ?>
        
        <form method="POST">
        <?php foreach ($schueler as $s): ?>
            <div class="student-row">
                <label class='labelanwesenheit'><?php echo htmlspecialchars($s['vorname'] . ' ' . $s['nachname']); ?></label>
                <div class="student-info">
                    <input type="hidden" name="fach_id" value="<?php echo $fach_id ?>">
                    <input type="hidden" name="stunde_id" value="<?php echo $stunde_id ?>">
                    <div>
                        <input type="radio" name="anwesenheit[<?php echo $s['schueler_id']; ?>][anwesenheit]" value="anwesend" <?php echo isset($fehlzeiten[$s['schueler_id']]) ? '' : 'checked'; ?>>Anwesend
                        <input type="radio" name="anwesenheit[<?php echo $s['schueler_id']; ?>][anwesenheit]" value="abwesend" <?php echo isset($fehlzeiten[$s['schueler_id']]) ? 'checked' : ''; ?>>Abwesend
                    </div>
                    <div>
                        <input type="number" name="anwesenheit[<?php echo $s['schueler_id']; ?>][fehlzeit]" placeholder="Minuten" min="0" value="<?php echo isset($fehlzeiten[$s['schueler_id']]) ? $fehlzeiten[$s['schueler_id']]['zeit'] : ''; ?>">
                    </div>
                </div>
                <?php if (isset($fehlzeiten[$s['schueler_id']]) && $fehlzeiten[$s['schueler_id']]['erklaerung'] != ''): ?>
                    <div style="margin-left: 10px; color: #555;">
                        <strong>Erklärung:</strong> <?php echo htmlspecialchars($fehlzeiten[$s['schueler_id']]['erklaerung']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($fehlzeiten[$s['schueler_id']]) && !empty($fehlzeiten[$s['schueler_id']]['grund'])): ?>
                    <a href="<?php echo htmlspecialchars($fehlzeiten[$s['schueler_id']]['grund']); ?>" target="_blank" class="image-button">Bild anzeigen</a>
                <?php endif; ?>
                </div>
                <div style="margin-left: 10px; color: #555;">
                <?php if (isset($fehlzeiten[$s['schueler_id']]) && 
                    (!empty($fehlzeiten[$s['schueler_id']]['grund']) || $fehlzeiten[$s['schueler_id']]['erklaerung'] != '')): ?>
                    <input type='checkbox' name='anwesenheit[<?php echo $s['schueler_id']; ?>][fehlzeit_bestaetigt]' value='1' <?php echo $fehlzeiten[$s['schueler_id']]['bestaetigt'] == 1 ? 'checked' : ''; ?>> Fehlzeit bestätigt
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
            <button type="submit" class="button">Anwesenheit speichern</button>
        </form>
    <?php endif; ?>

</body>
<?php include 'navigation.php'; ?>
</html>

<?php
// Verbindung schließen
mysqli_close($conn);
?>