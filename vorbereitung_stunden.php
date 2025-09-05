<?php
session_start();

$wochentag = date('w');
$aktuelles_datum = date('Y-m-d');

$user_id = $_SESSION['user_id'];
$schule_id = $_SESSION['schule_id'];

// Verbindung zur Datenbank herstellen
$conn = new mysqli('localhost', 'root', '', 'educonnect');
mysqli_set_charset($conn, "utf8mb4");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Überprüfen, ob der Lehrer angemeldet ist
if ($_SESSION['rang'] !== 'lehrer') {
    die("Zugriff verweigert.");
}

// Überprüfen, ob der Lehrer angemeldet ist
if ($_SESSION['rang'] !== 'lehrer') {
    die("Zugriff verweigert.");
}

// Neue Vorbereitung hinzufügen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_preparation'])) {
    $stunden_id = $_POST['stunden_id'];
    $title = $_POST['title'];
    $beschreibung = $_POST['beschreibung'];
    $zeitpunkt = $_POST['zeitpunkt'];
    
    $query = "INSERT INTO vorbereitung_stunden (stunden_id, title, beschreibung, zeitpunkt) VALUES ('$stunden_id', '$title', '$beschreibung', '$zeitpunkt')";
    $conn->query($query);
}

// Vorbereitungen abrufen
$lehrer_id = $_SESSION['user_id'];
$heute = date('Y-m-d'); // Aktuelles Datum im Format YYYY-MM-DD
$sql = "SELECT vs.vorbereitung_id, vs.title, vs.beschreibung, vs.zeitpunkt, sl.stundenplan_id, sl.fach_id, s.stunde, t.tag_name 
        FROM vorbereitung_stunden vs 
        JOIN stundenplan_lehrer sl ON vs.stunden_id = sl.stundenplan_id 
        JOIN stunden s ON sl.stunde_id = s.stunde_id 
        JOIN tage t ON sl.tag_id = t.tag_id 
        WHERE sl.lehrer_id = $lehrer_id AND vs.zeitpunkt >= '$heute'";

$result = $conn->query($sql);
$vorbereitungen = $result->fetch_all(MYSQLI_ASSOC);

// Vorbereitungen löschen
if (isset($_GET['delete'])) {
    $vorbereitung_id = $_GET['delete'];
    $conn->query("DELETE FROM vorbereitung_stunden WHERE vorbereitung_id = '$vorbereitung_id'");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Stunden für die Auswahl abrufen
$sql_stunden = "SELECT sl.stundenplan_id, sl.fach_id, s.stunde, t.tag_name 
                FROM stundenplan_lehrer sl 
                JOIN stunden s ON sl.stunde_id = s.stunde_id 
                JOIN tage t ON sl.tag_id = t.tag_id 
                WHERE sl.lehrer_id = ? 
                ORDER BY t.tag_id, s.stunde";
$stmt_stunden = $conn->prepare($sql_stunden);
$stmt_stunden->bind_param("i", $lehrer_id);
$stmt_stunden->execute();
$result_stunden = $stmt_stunden->get_result();
$stunden_options = $result_stunden->fetch_all(MYSQLI_ASSOC);
$stmt_stunden->close();

// Vorbereitungen für das heutige Datum abrufen
$sql_heute = "SELECT vs.title, vs.beschreibung, vs.zeitpunkt 
              FROM vorbereitung_stunden vs 
              WHERE vs.zeitpunkt = ? AND vs.stunden_id IN (SELECT sl.stundenplan_id FROM stundenplan_lehrer sl WHERE sl.lehrer_id = ?)";
$stmt_heute = $conn->prepare($sql_heute);
$stmt_heute->bind_param("si", $heute, $lehrer_id);
$stmt_heute->execute();
$result_heute = $stmt_heute->get_result();
$vorbereitungen_heute = $result_heute->fetch_all(MYSQLI_ASSOC);
$stmt_heute->close();






// Stundenplan-Abfrage für Lehrer
$stundenplan_query = "
SELECT
        sp.stunde_id,
        st.stunde,
        sp.fach_id,
        f.fach_name,
        r.raum_bezeichnung AS standard_raum,
        rv.raum_bezeichnung AS geaendert_raum,
        TIME_FORMAT(st.beginn, '%H:%i') AS beginn,
        TIME_FORMAT(st.ende, '%H:%i') AS ende,
        IF(rv.raum_id IS NOT NULL, 1, 0) AS raum_geaendert -- Markierung für geänderte Räume
    FROM
        stundenplan_lehrer sp
    LEFT JOIN
        faecher f ON sp.fach_id = f.fach_id
    LEFT JOIN
        stunden st ON sp.stunde_id = st.stunde_id AND st.schule_id = '$schule_id' -- Stunden werden anhand der Schule geladen
    LEFT JOIN
        raum r ON sp.raum_id = r.raum_id -- Standardraum
    LEFT JOIN
        (
            SELECT rv.raum_id, rv.stunde_id, rv.lehrer_id, raum.raum_bezeichnung
            FROM raum_verlegung rv
            JOIN raum ON rv.raum_id = raum.raum_id
            WHERE rv.datum = '$aktuelles_datum'
        ) rv ON sp.lehrer_id = rv.lehrer_id AND sp.stunde_id = rv.stunde_id
    WHERE
        sp.lehrer_id = '$user_id'
 
        AND sp.tag_id = '$wochentag'
    ORDER BY
        st.stunde ASC
";

$stundenplan_result = mysqli_query($conn, $stundenplan_query);
$stundenplan = [];
while ($row = $stundenplan_result->fetch_assoc()) {
    $stundenplan[] = $row;
}

// Lehrerfehlzeiten abrufen
$fehlzeiten_query = "SELECT `lehrer_id`, `beginn`, `ende` FROM `lehrer_fehlzeiten` WHERE `datum` = '$aktuelles_datum'";
$fehlzeiten_result = mysqli_query($conn, $fehlzeiten_query);
$fehlende_lehrer = [];

if ($fehlzeiten_result && $fehlzeiten_result->num_rows > 0) {
    while ($row = $fehlzeiten_result->fetch_assoc()) {
        $fehlende_lehrer[] = [
            'lehrer_id' => $row['lehrer_id'],
            'beginn' => $row['beginn'],
            'ende' => $row['ende']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Stundenvorbereitung</title>
    <link rel="stylesheet" href="VS.css">
</head>
<body>
    <h1>Stundenvorbereitung</h1>

    <div class="container">
        <div class="box">
            <h2>Neue Vorbereitung hinzufügen</h2>
            <form method="POST">
                <table>
                    <tr>
                        <th><label for="stunden_id">Stunde auswählen:</label></th>
                        <td><select name="stunden_id" required>
                            <option value="">Bitte wählen</option>
                            <?php
                            // Stunden zusammenfassen, wenn sie direkt nacheinander kommen
                            $grouped_options = [];
                            $current_tag = '';
                            $current_stunde = null;

                            foreach ($stunden_options as $option) {
                                $tag_name = $option['tag_name'];
                                $stunde = $option['stunde'];
                                $stundenplan_id = $option['stundenplan_id'];
                                $fach_id = $option['fach_id'];

                                // Wenn wir einen neuen Tag haben, fügen wir die vorherige Gruppe hinzu
                                if ($current_tag !== $tag_name) {
                                    if ($current_tag !== '') {
                                        // Füge die vorherige Gruppe hinzu
                                        $grouped_options[] = [
                                            'tag_name' => $current_tag,
                                            'stunden' => $current_stunde,
                                            'stundenplan_ids' => $stundenplan_ids
                                        ];
                                    }
                                    // Neue Gruppe beginnen
                                    $current_tag = $tag_name;
                                    $current_stunde = [];
                                    $stundenplan_ids = [];
                                }

                                // Überprüfen, ob die Stunde direkt nach der letzten kommt
                                if ($current_stunde && end($current_stunde) + 1 === $stunde) {
                                    // Füge die Stunde zur aktuellen Gruppe hinzu
                                    $current_stunde[] = $stunde;
                                    $stundenplan_ids[] = $stundenplan_id;
                                } else {
                                    // Neue Stunde hinzufügen
                                    if ($current_stunde) {
                                        // Füge die vorherige Gruppe hinzu
                                        $grouped_options[] = [
                                            'tag_name' => $current_tag,
                                            'stunden' => $current_stunde,
                                            'stundenplan_ids' => $stundenplan_ids
                                        ];
                                    }
                                    // Starte eine neue Gruppe
                                    $current_stunde = [$stunde];
                                    $stundenplan_ids = [$stundenplan_id];
                                }
                            }

                            // Füge die letzte Gruppe hinzu
                            if ($current_tag !== '') {
                                $grouped_options[] = [
                                    'tag_name' => $current_tag,
                                    'stunden' => $current_stunde,
                                    'stundenplan_ids' => $stundenplan_ids
                                ];
                            }

                            // Optionen ausgeben
                            foreach ($grouped_options as $group) {
                                $stunden_range = implode('-', $group['stunden']);
                                echo "<option value='" . implode(',', $group['stundenplan_ids']) . "'>{$group['tag_name']} {$stunden_range}</option>";
                            }
                            ?>
                        </select>
                        </td>
                    </tr>
                <tr>
                    <th><label for="title">Titel:</label></th>
                    <td><input type="text" name="title" required></td>
                </tr>
                <tr>
                    <th><label for="beschreibung">Beschreibung:</label></th>
                    <td><textarea name="beschreibung" required style="resize: none; width: 95%; height: 15vw;"></textarea></td>
                </tr>
                <tr>
                    <th><label for="zeitpunkt">Zeitpunkt:</label></th>
                    <td><input type="date" name="zeitpunkt" required></td>
                </tr>
                <tr>
                    <td colspan="2"><button type="submit" name="add_preparation">Hinzufügen</button></td>
                </tr>
                </table>
            </form>
        </div>

        <div class="box">
            <h2>Stundenplan Heute</h2>
            <a href="stundenplan.php" style="color: black; text-decoration: none;">
            <table>
                <tr>
                    <th>Stunde</th>
                    <th>Fach</th>
                    <th>Raum</th>
                    <th>Beginn</th>
                    <th>Ende</th>
                </tr>
                <?php foreach ($stundenplan as $eintrag): ?>
                    <?php
                        $krank = false;
                        foreach ($fehlende_lehrer as $lehrer) {
                            if (
                                $eintrag['lehrer_id'] == $lehrer['lehrer_id'] &&
                                (
                                    ($eintrag['beginn'] >= $lehrer['beginn'] && $eintrag['beginn'] <= $lehrer['ende']) ||
                                    ($eintrag['ende'] >= $lehrer['beginn'] && $eintrag['ende'] <= $lehrer['ende'])
                                )
                            ) {
                                $krank = true;
                                break;
                            }
                        }
                        $klasse = $krank ? 'krank' : '';
                    ?>
                    <tr class="<?php echo $klasse; ?>">
                        <td><?php echo $eintrag['stunde']; ?></td>
                        <td><?php echo $eintrag['fach_name']; ?></td>
                        <td class="<?php echo ($eintrag['raum_geaendert'] ? 'raum-geaendert' : ''); ?>">
                            <?php echo $eintrag['raum_geaendert'] ? $eintrag['geaendert_raum'] : $eintrag['standard_raum']; ?>
                        </td>
                        <td><?php echo $eintrag['beginn']; ?></td>
                        <td><?php echo $eintrag['ende']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
            </a>
        </div>        
    </div>

    <div class="container">
        <div class="box">
            <h2>Vorbereitungen für heute</h2>
            <table>
                <tr>
                    <th>Titel</th>
                    <th>Beschreibung</th>
                    <th>Zeitpunkt</th>
                </tr>
                <?php if (empty($vorbereitungen_heute)): ?>
                    <tr>
                        <td colspan="3">Keine Vorbereitungen für heute.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($vorbereitungen_heute as $vorbereitung): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($vorbereitung['title']); ?></td>
                            <td><?php echo htmlspecialchars($vorbereitung['beschreibung']); ?></td>
                            <td><?php echo htmlspecialchars($vorbereitung['zeitpunkt']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </table>
        </div>

        <div class="box">
            <h2>Vorbereitungen einsehen</h2>
            <table>
                <tr>
                    <th>Titel</th>
                    <th>Beschreibung</th>
                    <th>Zeitpunkt</th>
                    <th>Aktionen</th>
                </tr>
                <?php foreach ($vorbereitungen as $vorbereitung): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($vorbereitung['title']); ?></td>
                        <td><?php echo htmlspecialchars($vorbereitung['beschreibung']); ?></td>
                        <td><?php 
                            $date = new DateTime($vorbereitung['zeitpunkt']);
                            echo htmlspecialchars($date->format('d.m.Y')); ?></td>
                        <td>
                            <a href="?delete=<?php echo $vorbereitung['vorbereitung_id']; ?>">Löschen</a>
                            <a href="vorbereitung_stunden_bearbeitung.php?id=<?php echo $vorbereitung['vorbereitung_id']; ?>">Bearbeiten</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        
    </div>

</body>
</html>