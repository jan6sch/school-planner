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

// Filterparameter abrufen
$stufe_filter = isset($_GET['stufe']) ? mysqli_real_escape_string($conn, $_GET['stufe']) : '';
$vorname_filter = isset($_GET['vorname']) ? mysqli_real_escape_string($conn, $_GET['vorname']) : '';
$nachname_filter = isset($_GET['nachname']) ? mysqli_real_escape_string($conn, $_GET['nachname']) : '';
$bild_filter = isset($_GET['bild']) ? $_GET['bild'] : '';

// Fehlzeiten abrufen
$query = "SELECT sf.*, s.vorname, s.nachname, st.stufe_name, f.fach_name 
          FROM schueler_fehlzeiten sf 
          JOIN schueler s ON sf.schueler_id = s.schueler_id 
          JOIN faecher f ON sf.fach_id = f.fach_id 
          JOIN stufe st ON s.stufe = st.stufe_id  -- Hier wird die Stufe über die ID abgerufen
          WHERE sf.bestaetigt = 0"; // Nur nicht bestätigte Fehlzeiten

// Filter hinzufügen
if ($stufe_filter) {
    $query .= " AND st.stufe_name LIKE '%$stufe_filter%'"; // Filter auf die Stufe anwenden
}
if ($vorname_filter) {
    $query .= " AND s.vorname LIKE '%$vorname_filter%'";
}
if ($nachname_filter) {
    $query .= " AND s.nachname LIKE '%$nachname_filter%'";
}
if ($bild_filter !== '') {
    if ($bild_filter == '1') {
        $query .= " AND sf.grund IS NOT NULL"; // Bild hochgeladen
    } elseif ($bild_filter == '0') {
        $query .= " AND sf.grund IS NULL"; // Kein Bild hochgeladen
    }
}

$result = mysqli_query($conn, $query);
$fehlzeiten = [];

// Fehlzeiten in ein Array speichern
while ($row = mysqli_fetch_assoc($result)) {
    $fehlzeiten[] = $row;
}

// Bild löschen, Bildpfad löschen oder akzeptieren
if (isset($_POST['filter'])) {
    $fehlzeit_id = $_POST['fehlzeit_id']; // ID der Fehlzeit
    $action = $_POST['action']; // Aktion (löschen, akzeptieren oder Bildpfad löschen)

    if ($action == 'accept') {
        // Fehlzeit akzeptieren
        $query = "UPDATE schueler_fehlzeiten SET bestaetigt = 1 WHERE fehlzeit_id = $fehlzeit_id";
        mysqli_query($conn, $query);
        echo "Fehlzeit erfolgreich akzeptiert!";
    } elseif ($action == 'delete') {
        // Bildpfad abrufen, um das Bild zu löschen
        $query = "SELECT grund FROM schueler_fehlzeiten WHERE fehlzeit_id = $fehlzeit_id";
        $result = mysqli_query($conn, $query);
        $row = mysqli_fetch_assoc($result);
        $bild_path = $row['grund'];

        // Bild löschen
        if (file_exists($bild_path)) {
            unlink($bild_path); // Bild von der Festplatte löschen
        }

        // Fehlzeit aus der Datenbank löschen
        $query = "DELETE FROM schueler_fehlzeiten WHERE fehlzeit_id = $fehlzeit_id";
        mysqli_query($conn, $query);
        echo "Fehlzeit erfolgreich gelöscht!";
    } elseif ($action == 'delete_image') {
        // Bildpfad abrufen, um das Bild zu löschen
        $query = "SELECT grund FROM schueler_fehlzeiten WHERE fehlzeit_id = $fehlzeit_id";
        $result = mysqli_query($conn, $query);
        $row = mysqli_fetch_assoc($result);
        $bild_path = $row['grund'];

        // Bild löschen
        if (file_exists($bild_path)) {
            unlink($bild_path); // Bild von der Festplatte löschen
        }

        // Bildpfad in der Datenbank löschen
        $query = "UPDATE schueler_fehlzeiten SET grund = NULL WHERE fehlzeit_id = $fehlzeit_id";
        mysqli_query($conn, $query);
        echo "Bild erfolgreich gelöscht!";
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
    <link rel="stylesheet" href="fehlzeiten.css">
    <link rel="shortcut icon" href="picture.ico" type="image/x-icon">
</head>
<body>

<h1 class="lehrerFehlzeitenh1">Fehlzeiten der Schüler</h1>

<!-- Filterformular -->
<div id="filterForm" style="<?php echo ($stufe_filter || $vorname_filter || $nachname_filter || $bild_filter !== '') ? 'display: block;' : ''; ?>">
    <form method="GET" action="">
        <label for="stufe">Stufe:</label>
        <input type="text" name="stufe" id="stufe" value="<?php echo isset($_GET['stufe']) ? htmlspecialchars($_GET['stufe']) : ''; ?>"><br>

        <label for="vorname">Vorname:</label>
        <input type="text" name="vorname" id="vorname" value="<?php echo isset($_GET['vorname']) ? htmlspecialchars($_GET['vorname']) : ''; ?>"><br>

        <label for="nachname">Nachname:</label>
        <input type="text" name="nachname" id="nachname" value="<?php echo isset($_GET['nachname']) ? htmlspecialchars($_GET['nachname']) : ''; ?>"><br>

        <label for="bild">Bild hochgeladen:</label>
        <select name="bild" id="bild">
            <option value="">Alle</option>
            <option value="1" <?php echo isset($_GET['bild']) && $_GET['bild'] == '1' ? 'selected' : ''; ?>>Nein</option>
            <option value="0" <?php echo isset($_GET['bild']) && $_GET['bild'] == '0' ? 'selected' : ''; ?>>Ja</option>
        </select><br>

        <button type="submit" name="filter">Filtern</button>
        <button type="button" id="resetButton">Löschen</button>
    </form>
</div>

<a href="unterricht_edoc.php" class="lehrerFehlzeitenButton">Zurück</a>
<a href="lehrer_fehlzeiten_alle.php" class="lehrerFehlzeitenButton">Alles Anzeigen</a>
<!-- Button zum Anzeigen/Ausblenden des Filterformulars -->
<button id="toggleFilterButton" class="lehrerFehlzeitenButton">Filter anzeigen</button>

<table class="lehrerFehlzeitenTable">
    <thead>
        <tr>
            <th>Stufe</th>
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
            <tr>
                <td><?php echo htmlspecialchars($fehlzeit['stufe_name']); ?></td>
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
                    <form action="" method="POST">
                        <input type="hidden" name="fehlzeit_id" value="<?php echo $fehlzeit['fehlzeit_id']; ?>">
                        <input type="checkbox" id="confirm_<?php echo $fehlzeit['fehlzeit_id']; ?>" class="lehrerFehlzeitenCheckbox">
                        <label for="confirm_<?php echo $fehlzeit['fehlzeit_id']; ?>">Aktion bestätigen</label><br>
                        <button type="submit" name="action" value="accept" class="lehrerFehlzeitenButton" disabled>Akzeptieren</button>
                        <button type="submit" name="action" value="delete" class="lehrerFehlzeitenButton" disabled>Löschen</button>
                        <button type="submit" name="action" value="delete_image" class="lehrerFehlzeitenButton" disabled>Bild löschen</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
    // Funktion zum Anzeigen/Ausblenden des Filterformulars
    document.getElementById('toggleFilterButton').addEventListener('click', function() {
        var filterForm = document.getElementById('filterForm');
        if (filterForm.style.display === 'none' || filterForm.style.display === '') {
            filterForm.style.display = 'block';
            this.textContent = 'Filter ausblenden'; // Button-Text ändern
        } else {
            filterForm.style.display = 'none';
            this.textContent = 'Filter anzeigen'; // Button-Text zurücksetzen
        }
    });

    // Funktion zum Zurücksetzen der Filter
    document.getElementById('resetButton').addEventListener('click', function() {
        // Alle Eingabefelder zurücksetzen
        document.getElementById('stufe').value = '';
        document.getElementById('vorname').value = '';
        document.getElementById('nachname').value = '';
        document.getElementById('bild').selectedIndex = 0; // Zurücksetzen auf "Alle"
        
        // Filterformular sichtbar halten
        document.getElementById('filterForm').style.display = 'block';
    });

    // Alle Checkboxen und Buttons abrufen
    const checkboxes = document.querySelectorAll('.lehrerFehlzeitenCheckbox');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const buttons = this.closest('form').querySelectorAll('.lehrerFehlzeitenButton');
            buttons.forEach(button => {
                button.disabled = !this.checked; // Buttons aktivieren/deaktivieren
            });
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