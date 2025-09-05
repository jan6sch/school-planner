<?php
session_start();

// Verbindung zur Datenbank herstellen
$conn = mysqli_connect('localhost', 'root', '', 'educonnect');

if (!$conn) {
    die("Verbindung zur Datenbank fehlgeschlagen: " . mysqli_connect_error());
}

// Lehrer ID aus der Session
$lehrer_id = $_SESSION['user_id']; // Beispielwert, sollte aus der Session kommen
$rang = $_SESSION['rang'];

if($rang != 'lehrer'){
    die("Kein Zugriff");
}

// Fehlzeiten abrufen
$query = "SELECT sf.*, s.vorname, s.nachname, f.fach_name 
          FROM schueler_fehlzeiten sf 
          JOIN schueler s ON sf.schueler_id = s.schueler_id 
          JOIN faecher f ON sf.fach_id = f.fach_id
          ";
$result = mysqli_query($conn, $query);
$fehlzeiten = [];

// Fehlzeiten in ein Array speichern
while ($row = mysqli_fetch_assoc($result)) {
    $fehlzeiten[] = $row;
}

// Bild löschen, Bildpfad löschen oder akzeptieren
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fehlzeit_id = $_POST['fehlzeit_id']; // ID der Fehlzeit
    $action = $_POST['action']; // Aktion (löschen, akzeptieren oder Bildpfad löschen)

    if ($action == 'delete') {
        // Bildpfad abrufen, um das Bild zu löschen
        $query = "SELECT grund FROM schueler_fehlzeiten WHERE fehlzeit_id = $fehlzeit_id";
        $result = mysqli_query($conn, $query);
        $row = mysqli_fetch_assoc($result);
        
        // Überprüfen, ob der Bildpfad existiert
        if ($row && isset($row['grund'])) {
            $bild_path = $row['grund'];

            // Bild löschen, wenn es existiert
            if (file_exists($bild_path)) {
                unlink($bild_path); // Bild von der Festplatte löschen
            }
        }

        // Fehlzeit aus der Datenbank löschen
        $query = "DELETE FROM schueler_fehlzeiten WHERE fehlzeit_id = $fehlzeit_id";
        mysqli_query($conn, $query);

        echo "Fehlzeit und Bild erfolgreich gelöscht!";
    }

    // Entschuldigen Button
    if ($action == 'entschuldigen') {
        $query = "UPDATE schueler_fehlzeiten SET bestaetigt = 1 WHERE fehlzeit_id = $fehlzeit_id";
        mysqli_query($conn, $query);
        echo "Fehlzeit erfolgreich entschuldigt!";
    }
}

// HTML-Ausgabe
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fehlzeiten der Schüler</title>
    <link rel="stylesheet" href="LFA.css">
    <link rel="shortcut icon" href="picture.ico" type="image/x-icon">
</head>
<body>

<h1 class="fehlzeitenTitle">Fehlzeiten der Schüler</h1>
<a href="lehrer_fehlzeiten.php" class="fehlzeitenButton">Zurück</a>

<table class="fehlzeitenTable">
    <thead>
        <tr>
            <th>Vorname</th>
            <th>Nachname</th>
            <th>Fach</th>
            <th>Fehlzeit (Minuten)</th>
            <th>Datum</th>
            <th>Bild</th>
            <th>Aktion</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($fehlzeiten as $fehlzeit): ?>
            <tr style="color: <?php echo $fehlzeit['bestaetigt'] == 2 ? 'red' : 'black'; ?>;">
                <td><?php echo htmlspecialchars($fehlzeit['vorname']); ?></td>
                <td><?php echo htmlspecialchars($fehlzeit['nachname']); ?></td>
                <td><?php echo htmlspecialchars($fehlzeit['fach_name']); ?></td>
                <td><?php echo htmlspecialchars($fehlzeit['zeit']); ?></td>
                <td><?php echo date("d.m.Y", strtotime($fehlzeit['time'])); ?></td>
                <td>
                    <?php if ($fehlzeit['grund']): ?>
                        <a href="<?php echo htmlspecialchars($fehlzeit['grund']); ?>" target="_blank">Bild anzeigen</a>
                    <?php else: ?>
                        Kein Bild hochgeladen
                    <?php endif; ?>
                </td>
                <td>
                    <form action="" method="POST" style="display:inline;">
                        <input type="hidden" name="fehlzeit_id" value="<?php echo $fehlzeit['fehlzeit_id']; ?>">
                        <input type="checkbox" id="confirm_<?php echo $fehlzeit['fehlzeit_id']; ?>" class="lehrerFehlzeitenalleCheckbox">
                        <label for="confirm_<?php echo $fehlzeit['fehlzeit_id']; ?>">Aktion bestätigen</label><br>
                        <button type="submit" name="action" value="delete" class="fehlzeitenActionButton" disabled>Löschen</button>
                        <?php if ($fehlzeit['bestaetigt'] == 2): ?>
                            <button type="submit" name="action" value="entschuldigen" class="entschuldigenButton" disabled>Entschuldigen</button>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
    // Alle Checkboxen abrufen
    const checkboxes = document.querySelectorAll('.lehrerFehlzeitenalleCheckbox');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // Buttons im gleichen Formular wie die Checkbox abrufen
            const form = this.closest('form');
            const deleteButton = form.querySelector('.fehlzeitenActionButton');
            const entschuldigenButton = form.querySelector('.entschuldigenButton');

            // Beide Buttons aktivieren/deaktivieren
            const isChecked = this.checked; // Status der Checkbox
            deleteButton.disabled = !isChecked; // Löschen-Button aktivieren/deaktivieren
            if (entschuldigenButton) {
                entschuldigenButton.disabled = !isChecked; // Entschuldigen-Button aktivieren/deaktivieren
            }
        });
    });
</script>

</body>
<?php include 'navigation.php'; ?>
</html>

<?php
// Verbindung schließen
mysqli_close($conn);
?>