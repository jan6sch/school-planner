<?php
session_start();

// Verbindung zur Datenbank herstellen
$conn = mysqli_connect('localhost', 'root', '', 'educonnect');

if (!$conn) {
    die("Verbindung zur Datenbank fehlgeschlagen: " . mysqli_connect_error());
}

$rang = $_SESSION['rang']; 
if($rang != 'schueler') {
    header('Location: mainpage.php');
    exit();
}
else{
    $schueler_id = $_SESSION['user_id'];
}

// Tage abrufen
$query_tage = "SELECT * FROM tage";
$result_tage = mysqli_query($conn, $query_tage);
$tage = mysqli_fetch_all($result_tage, MYSQLI_ASSOC);

// Stundenplan abrufen, wenn ein Datum ausgewählt ist
$stundenplan = [];
if (isset($_POST['datum'])) {
    $datum = $_POST['datum'];
    $dayOfWeek = date('N', strtotime($datum)); // 1 (Montag) bis 7 (Sonntag)
    $deutschesDatum = date("d.m.Y", strtotime($datum));

    $query_tag_id = "SELECT tag_id FROM tage WHERE tag_id = $dayOfWeek";
    $result_tag_id = mysqli_query($conn, $query_tag_id);
    $tag = mysqli_fetch_assoc($result_tag_id);
    
    if ($tag) {
        $tag_id = $tag['tag_id'];
        
        $query_stundenplan = "SELECT s.*, f.fach_name, l.lehrer_id 
                               FROM stundenplan_schueler s 
                               JOIN faecher f ON s.fach_id = f.fach_id 
                               JOIN lehrer l ON s.lehrer_id = l.lehrer_id 
                               WHERE s.schueler_id = $schueler_id AND s.tag_id = $tag_id";
        $result_stundenplan = mysqli_query($conn, $query_stundenplan);
        $stundenplan = mysqli_fetch_all($result_stundenplan, MYSQLI_ASSOC);
    }
}

// Fächer zusammenfassen
$fach_options = [];
if (!empty($stundenplan)) {
    foreach ($stundenplan as $stunde) {
        $fach_id = $stunde['fach_id'];
        if (!isset($fach_options[$fach_id])) {
            $fach_options[$fach_id] = [
                'fach_name' => $stunde['fach_name'],
                'stunde_id' => $stunde['stunde_id'],
                'lehrer_id' => $stunde['lehrer_id'],
                'anzahl_stunden' => 1
            ];
        } else {
            $fach_options[$fach_id]['anzahl_stunden']++;
        }
    }
}

// Bild hochladen und Fehlzeit eintragen
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $erklaerung = $_POST['erklaerung'] ?? '';
    $bestaetigt = 3;
    $datum = $_POST['datum'];

    // Überprüfen, ob der Schüler sich für den ganzen Tag krankmeldet
    if (isset($_POST['whole_day']) && $_POST['whole_day'] == '1') {
        foreach ($fach_options as $fach_id => $fach) {
            if ($fach['fach_name'] !== 'Freistunde') {
                $stunde_id = $fach['stunde_id'];
                $lehrer_id = $fach_options[$fach_id]['lehrer_id'];
                $zeit = $fach['anzahl_stunden'] * 45;

                // Überprüfung auf bestehenden Eintrag
                $check_query = "SELECT * FROM schueler_fehlzeiten 
                                WHERE schueler_id = $schueler_id 
                                AND lehrer_id = $lehrer_id 
                                AND stunde_id = $stunde_id 
                                AND fach_id = $fach_id 
                                AND DATE(time) = DATE('$datum')";
                $check_result = mysqli_query($conn, $check_query);

                if (mysqli_num_rows($check_result) == 0) {
                    // Bild hochladen (optional)
                    $upload_file = null; // Initialisieren des Dateipfads
                    if (isset($_FILES['bild']) && $_FILES['bild']['error'] == UPLOAD_ERR_OK) {
                        $upload_dir = 'entschuldigungen/';
                        $sql = "SELECT stufe FROM schueler WHERE schueler_id = $schueler_id";
                        $result = mysqli_query($conn, $sql);
                        $klasse = mysqli_fetch_assoc($result)['stufe'];
                        $bild_name = time() . '_' . basename($_FILES['bild']['name']);
                        $upload_dir = $upload_dir . $klasse . '/';

                        // Überprüfen, ob das allgemeine Verzeichnis existiert, und erstellen, falls nicht
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true); // Verzeichnis erstellen
                        }

                        if (move_uploaded_file($_FILES['bild']['tmp_name'], $upload_file)) {
                            // Dateipfad in das Feld 'grund' einfügen
                            $query_insert = "INSERT INTO schueler_fehlzeiten (schueler_id, lehrer_id, stunde_id, fach_id, zeit, grund, erklaerung, bestaetigt, time) 
                                             VALUES ($schueler_id, $lehrer_id, $stunde_id, $fach_id, $zeit, '$upload_file', '$erklaerung', $bestaetigt, '$datum')";
                            mysqli_query($conn, $query_insert);
                        }
                    } else {
                        // Wenn kein Bild hochgeladen wurde, bleibt 'grund' leer
                        $query_insert = "INSERT INTO schueler_fehlzeiten (schueler_id, lehrer_id, stunde_id, fach_id, zeit, erklaerung, bestaetigt, time) 
                                         VALUES ($schueler_id, $lehrer_id, $stunde_id, $fach_id, $zeit, '$erklaerung', $bestaetigt, '$datum')";
                        mysqli_query($conn, $query_insert);
                    }
                } else {
                    // Eintrag existiert bereits, also aktualisieren wir die Felder
                    $existing_entry = mysqli_fetch_assoc($check_result);
                    $existing_grund = $existing_entry['grund'];
                    $existing_erklaerung = $existing_entry['erklaerung'];

                    // Update nur, wenn die Felder leer sind
                    if (empty($existing_grund) && !empty($upload_file)) {
                        $update_query = "UPDATE schueler_fehlzeiten 
                                         SET grund = '$upload_file' 
                                         WHERE fehlzeit_id = " . $existing_entry['fehlzeit_id'];
                        mysqli_query($conn, $update_query);
                    }

                    // Update der Erklärung, wenn sie leer ist
                    if (empty($existing_erklaerung) && !empty($erklaerung)) {
                        $update_query = "UPDATE schueler_fehlzeiten 
                                         SET erklaerung = '$erklaerung' 
                                         WHERE fehlzeit_id = " . $existing_entry['fehlzeit_id'];
                        mysqli_query($conn, $update_query);
                    }

                    echo "Eintrag für diesen Tag existiert bereits!";
                }
            }
        }
        echo "Sie haben sich für den ganzen Tag krankgemeldet!";
    } else {
        $fach_id = $_POST['fach_id'];
        $stunde_id = $fach_options[$fach_id]['stunde_id'];
        $lehrer_id = $fach_options[$fach_id]['lehrer_id'];
        $zeit = $_POST['zeit'];

        // Überprüfung auf bestehenden Eintrag
        $check_query = "SELECT * FROM schueler_fehlzeiten 
                        WHERE schueler_id = $schueler_id 
                        AND lehrer_id = $lehrer_id 
                        AND stunde_id = $stunde_id 
                        AND fach_id = $fach_id 
                        AND DATE(time) = DATE('$datum')";
        $check_result = mysqli_query($conn, $check_query);

        if (mysqli_num_rows($check_result) == 0) {
            // Bild hochladen (optional)
            $upload_file = null; // Initialisieren des Dateipfads
            if (isset($_FILES['bild']) && $_FILES['bild']['error'] == UPLOAD_ERR_OK) {
                $sqlKlasse=" SELECT stufe FROM schueler WHERE schueler_id = $schueler_id";
                $resultKlasse = mysqli_query($conn, $sqlKlasse);
                $klasse = mysqli_fetch_assoc($resultKlasse)['stufe'];
                $upload_dir = 'entschuldigungen/';
                $class_dir = $upload_dir . $klasse . '/';

                if (!is_dir($class_dir)) {
                    mkdir($class_dir, 0755, true);
                }

                $bild_name = time() . '_' . basename($_FILES['bild']['name']);
                $upload_file = $class_dir . $bild_name;

                if (move_uploaded_file($_FILES['bild']['tmp_name'], $upload_file)) {
                    $query_insert = "INSERT INTO schueler_fehlzeiten (schueler_id, lehrer_id, stunde_id, fach_id, zeit, grund, erklaerung, bestaetigt, time) 
                                     VALUES ($schueler_id, $lehrer_id, $stunde_id, $fach_id, $zeit, '$upload_file', '$erklaerung', $bestaetigt, '$datum')";
                    mysqli_query($conn, $query_insert);
                    echo "Fehlzeit erfolgreich eingetragen!";
                } else {
                    echo "Fehler beim Hochladen des Bildes.";
                }
            } else {
                // Wenn kein Bild hochgeladen wurde, bleibt 'grund' leer
                $query_insert = "INSERT INTO schueler_fehlzeiten (schueler_id, lehrer_id, stunde_id, fach_id, zeit, erklaerung, bestaetigt, time) 
                                 VALUES ($schueler_id, $lehrer_id, $stunde_id, $fach_id, $zeit, '$erklaerung', $bestaetigt, '$datum')";
                mysqli_query($conn, $query_insert);
                echo "Fehlzeit erfolgreich eingetragen!";
            }
        } else {
            // Eintrag existiert bereits, also aktualisieren wir die Felder
            $existing_entry = mysqli_fetch_assoc($check_result);
            $existing_grund = $existing_entry['grund'];
            $existing_erklaerung = $existing_entry['erklaerung'];

            // Update nur, wenn die Felder leer sind
            if (empty($existing_grund) && !empty($upload_file)) {
                $update_query = "UPDATE schueler_fehlzeiten 
                                 SET grund = '$upload_file' 
                                 WHERE fehlzeit_id = " . $existing_entry['fehlzeit_id'];
                mysqli_query($conn, $update_query);
            }

            // Update der Erklärung, wenn sie leer ist
            if (empty($existing_erklaerung) && !empty($erklaerung)) {
                $update_query = "UPDATE schueler_fehlzeiten 
                                 SET erklaerung = '$erklaerung' 
                                 WHERE fehlzeit_id = " . $existing_entry['fehlzeit_id'];
                mysqli_query($conn, $update_query);
            }

            echo "Eintrag für diesen Tag existiert bereits!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abwesenheit melden</title>
    <link rel="stylesheet" href="fehlzeiten.css">
    <link rel="shortcut icon" href="picture.ico" type="image/x-icon">
    <script>
        function toggleMinutesInput(checkbox) {
            const minutesInput = document.getElementById('zeit');
            const fachSelect = document.getElementById('fach_id');
            if (checkbox.checked) {
                minutesInput.value = ''; // Setze die Minuten auf leer
                minutesInput.disabled = true; // Deaktiviere das Minutenfeld
                fachSelect.disabled = true; // Deaktiviere das Fachfeld
            } else {
                minutesInput.disabled = false; // Aktiviere das Minutenfeld
                fachSelect.disabled = false; // Aktiviere das Fachfeld
            }
        }
    </script>
</head>
<body class="SAbwesenheitBody">

<h1 class="SAbwesenheitH1">Abwesenheit melden</h1>

<a href="fehlzeiten.php" class="SAbwesenheitLink">Zurück</a>

<form action="" method="POST" enctype="multipart/form-data" class="SAbwesenheitForm">
    <label for="datum" class="SAbwesenheitLabel">Datum:</label>
    <input type="date" name="datum" id="datum" required onchange="this.form.submit()" class="SAbwesenheitInput">
</form>

<?php if (!empty($stundenplan)): ?>
    <h2 class="SAbwesenheitH2">Stundenplan für <?php echo htmlspecialchars($deutschesDatum); ?></h2>
    <table class="SAbwesenheitTable">
        <thead>
            <tr>
                <th class="SAbwesenheitTh">Stunde</th>
                <th class="SAbwesenheitTh">Fach</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($stundenplan as $stunde): ?>
                <tr>
                    <td class="SAbwesenheitTd"><?php echo htmlspecialchars($stunde['stunde_id']); ?></td>
                    <td class="SAbwesenheitTd"><?php echo htmlspecialchars($stunde['fach_name']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <form action="" method="POST" enctype="multipart/form-data" class="SAbwesenheitForm">
        <input type="hidden" name="datum" value="<?php echo $datum; ?>">
        
        <label for="whole_day" class="SAbwesenheitLabel">Für den ganzen Tag krankmelden:</label>
        <input type="checkbox" name="whole_day" id="whole_day" value="1" onclick="toggleMinutesInput(this)">

        <label for="fach_id" class="SAbwesenheitLabel">Fach:</label>
        <select name="fach_id" id="fach_id" required class="SAbwesenheitSelect">
            <option value="">Wählen Sie ein Fach</option>
            <?php
            foreach ($fach_options as $id => $fach): ?>
                <?php if ($fach['fach_name'] !== 'Freistunde'): ?>
                    <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($fach['fach_name']); ?></option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>

        <label for="zeit" class="SAbwesenheitLabel">Fehlzeit (Minuten):</label>
        <input type="number" name="zeit" id="zeit" required class="SAbwesenheitInput">

        <label for="erklaerung" class="SAbwesenheitLabel">Erklärung (optional):</label>
        <textarea name="erklaerung" id="erklaerung" class="SAbwesenheitTextarea"></textarea>

        <label for="bild" class="SAbwesenheitLabel">Bild hochladen (optional):</label>
        <input type="file" name="bild" id="bild" accept="image/*" class="SAbwesenheitFile">

        <button type="submit" name="submit" class="SAbwesenheitButton">Abwesenheit melden</button>
    </form>
<?php endif; ?>

</body>
<?php include 'navigation.php'; ?>
 
</html>

<?php
// Verbindung schließen
mysqli_close($conn);
?>
