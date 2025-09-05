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
$query = "SELECT sf.*, f.fach_name, sf.time AS datum 
FROM schueler_fehlzeiten sf 
JOIN faecher f ON sf.fach_id = f.fach_id 
WHERE sf.schueler_id = $schueler_id AND sf.bestaetigt IN ('0', '3')
";
$result = mysqli_query($conn, $query);
$fehlzeiten = [];

$sql = "SELECT * FROM schueler WHERE schueler_id = $schueler_id";
$result1 = mysqli_query($conn, $sql);
$schueler = mysqli_fetch_assoc($result1);

// Fehlzeiten in ein Array speichern
while ($row = mysqli_fetch_assoc($result)) {
    $fehlzeiten[] = $row;
}

$vorname = $schueler['vorname']; // Vorname des Schülers
$nachname = $schueler['nachname']; // Nachname des Schülers

// Variablen für Ausgaben
$messages = [];

// Bild hochladen oder Fehlzeit löschen
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete_fehlzeit'])) {
        $fehlzeit_id_to_delete = $_POST['fehlzeit_id_to_delete'];

        // Fehlzeit aus der Datenbank löschen
        $delete_query = "DELETE FROM schueler_fehlzeiten WHERE fehlzeit_id = $fehlzeit_id_to_delete";
        if (mysqli_query($conn, $delete_query)) {
            $_SESSION['messages'][] = "Fehlzeit erfolgreich gelöscht!";
            // Seite neu laden
            header("Location: " . $_SERVER['PHP_SELF']);
            exit(); // Beenden Sie das Skript nach der Weiterleitung
        } else {
            $_SESSION['messages'][] = "Fehler beim Löschen der Fehlzeit: " . mysqli_error($conn);
        }
    } else {
        // Der Rest des Codes für das Hochladen von Bildern
        $fehlzeit_id = $_POST['fehlzeit_id']; // ID der Fehlzeit
        $lehrer_id = $_POST['lehrer_id']; 
        $stunde_id = $_POST['stunde_id']; 
        $fach_id = $_POST['fach_id']; 
        $zeit = $_POST['zeit']; 

        // Bildinformationen
        $klasse = $schueler['stufe']; // Klasse des Schülers

        // Fachname abrufen
        $fach_name_query = "SELECT fach_name FROM faecher WHERE fach_id = $fach_id";
        $fach_name_result = mysqli_query($conn, $fach_name_query);
        $fach_name_row = mysqli_fetch_assoc($fach_name_result);
        $fach_name = $fach_name_row['fach_name']; // Fachname setzen

        // Bildname generieren
        $datum = date('Y-m-d', strtotime($fehlzeiten[0]['datum'])); // Datum der Fehlzeit
        $bild_name = $vorname . '_' . $nachname . '_' . $fach_name . '_' . $datum . '_' . time();

        // Verzeichnisse für die Entschuldigungen
        $upload_dir = 'entschuldigungen/'; // Allgemeines Verzeichnis
        $class_dir = $upload_dir . $klasse . '/'; // Verzeichnis für die Klasse

        // Überprüfen, ob das allgemeine Verzeichnis existiert, und erstellen, falls nicht
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true); // Verzeichnis erstellen
        }

        // Überprüfen, ob das Klassenverzeichnis existiert, und erstellen, falls nicht
        if (!is_dir($class_dir)) {
            mkdir($class_dir, 0755, true); // Verzeichnis erstellen
        }

        // Überprüfen, ob bereits ein Bild hochgeladen wurde
        $query_check = "SELECT grund FROM schueler_fehlzeiten WHERE fehlzeit_id = $fehlzeit_id";
        $result_check = mysqli_query($conn, $query_check);
        $check_row = mysqli_fetch_assoc($result_check);

        // Bild löschen
        if (isset($_POST['delete_image'])) {
            if ($check_row['grund'] && file_exists($check_row['grund'])) {
                // Bild löschen
                unlink($check_row['grund']);
                
                // Bildpfad in der Datenbank zurücksetzen
                $query = "UPDATE schueler_fehlzeiten SET grund = NULL WHERE fehlzeit_id = $fehlzeit_id";
                mysqli_query($conn, $query);
                $_SESSION['messages'][] = "Bild erfolgreich gelöscht!";
            } else {
                $_SESSION['messages'][] = "Kein Bild zum Löschen gefunden.";
            }
        } else {
            // Bild hochladen
            if ($check_row['grund']) {
                $_SESSION['messages'][] = "Ein Bild wurde bereits hochgeladen. Das Hochladen eines weiteren Bildes ist nicht möglich.";
            } else {
                if (isset($_FILES['bild']) && $_FILES['bild']['error'] == UPLOAD_ERR_OK) {
                    $upload_file = $class_dir . $bild_name . '.' . pathinfo($_FILES['bild']['name'], PATHINFO_EXTENSION);
                    if (move_uploaded_file($_FILES['bild']['tmp_name'], $upload_file)) {
                        // Bildpfad in der Datenbank speichern
                        $query = "UPDATE schueler_fehlzeiten SET grund = '$upload_file' WHERE fehlzeit_id = $fehlzeit_id";
                        mysqli_query($conn, $query);
                        $_SESSION['messages'][] = "Bild erfolgreich hochgeladen!";
                    } else {
                        $_SESSION['messages'][] = "Fehler beim Hochladen des Bildes.";
                    }
                } elseif (isset($_FILES['bild2']) && $_FILES['bild2']['error'] == UPLOAD_ERR_OK) {
                    $upload_file = $class_dir . $bild_name . '.' . pathinfo($_FILES['bild2']['name'], PATHINFO_EXTENSION);
                    if (move_uploaded_file($_FILES['bild2']['tmp_name'], $upload_file)) {
                        // Bildpfad in der Datenbank speichern
                        $query = "UPDATE schueler_fehlzeiten SET grund = '$upload_file' WHERE fehlzeit_id = $fehlzeit_id";
                        mysqli_query($conn, $query);
                        $_SESSION['messages'][] = "Bild erfolgreich hochgeladen!";
                    } else {
                        $_SESSION['messages'][] = "Fehler beim Hochladen des Bildes.";
                    }
                }
            }
        }
        // Seite neu laden, um die Nachrichten anzuzeigen
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}


// HTML-Ausgabe
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fehlzeiten einsehen</title>
    <link rel="stylesheet" href="fehlzeiten.css">
    <link rel="shortcut icon" href="picture.ico" type="image/x-icon">
</head>
<body class='fehlzeitenbody'>

<h1 class='fehlzeitenh1'>Fehlzeiten einsehen</h1>
<h2 class='fehlzeitenh2'><?php echo htmlspecialchars($vorname) . ' ' . htmlspecialchars($nachname); ?></h2>

<a href="schueler_fehlzeiten_alle.php" class="lehrerFehlzeitenButton">Alles Anzeigen</a><br>
<a href="schueler_abwesenheit.php" class="lehrerFehlzeitenButton">Heute nicht da?</a>

<!-- Ausgabe der Nachrichten -->
<?php if (isset($_SESSION['messages'])): ?>
    <div class="messages">
        <?php foreach ($_SESSION['messages'] as $message): ?>
            <p><?php echo htmlspecialchars($message); ?></p>
        <?php endforeach; ?>
        <?php unset($_SESSION['messages']); // Nachrichten nach der Anzeige löschen ?>
    </div>
<?php endif; ?>

<div class="table-responsive">
    <table class="fehlzeitentable">
        <thead>
            <tr>
                <th class='fehlzeitenth'>Fach</th>
                <th class='fehlzeitenth'>Fehlzeit (Minuten)</th>
                <th class='fehlzeitenth'>Datum</th>
                <th class='fehlzeitenth'>Bild</th>
                <th class='fehlzeitenth'>Status</th>
                <th class='fehlzeitenth'>Aktion</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($fehlzeiten as $fehlzeit): ?>
                <tr>
                    <td class='fehlzeitentd'><?php echo htmlspecialchars($fehlzeit['fach_name']); ?></td>
                    <td class='fehlzeitentd'><?php echo htmlspecialchars($fehlzeit['zeit']); ?></td>
                    <td class='fehlzeitentd'><?php echo date("d.m.Y", strtotime($fehlzeit['datum'])); ?></td>
                    <td class='fehlzeitentd'>
                        <?php if ($fehlzeit['grund']): ?>
                            <a href="<?php echo htmlspecialchars($fehlzeit['grund']); ?>" target="_blank">Bild anzeigen</a>
                        <?php else: ?>
                            Kein Bild hochgeladen
                        <?php endif; ?>
                    </td>
                    <td class='fehlzeitentd'>
                        <?php 
                            if($fehlzeit['bestaetigt'] == 0 or $fehlzeit['bestaetigt'] == 3) {
                                echo 'Noch ausstehend';
                            } 
                            elseif($fehlzeit['bestaetigt'] == 2) {
                                echo 'Nicht bestätigt';
                            }
                            else {
                                echo 'Bestätigt';
                            }
                        ?>
                    </td>
                    <td class='fehlzeitentd'>
                        <form class='fehlzeitenfrom' action="" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="fehlzeit_id" value="<?php echo $fehlzeit['fehlzeit_id']; ?>">
                            <input type="hidden" name="lehrer_id" value="<?php echo $fehlzeit['lehrer_id']; ?>">
                            <input type="hidden" name="stunde_id" value="<?php echo $fehlzeit['stunde_id']; ?>">
                            <input type="hidden" name="fach_id" value="<?php echo $fehlzeit['fach_id']; ?>">
                            <input type="hidden" name="zeit" value="<?php echo $fehlzeit['zeit']; ?>">
                            <?php if (!$fehlzeit['grund']): ?>
                                <?php if ($detect->isMobile()): ?>
                                    <label for="upload-option">Wählen Sie eine Option:</label>
                                    <select id="upload-option" name="upload-option" onchange="toggleFileInput(this.value)">
                                        <option value="camera" selected>Mit Kamera aufnehmen</option>
                                        <option value="file">Aus Dateien hochladen</option>
                                    </select>
                                    <div id="file-inputs">
                                        <input type="file" id="camera-input" name="bild" accept="image/*" capture="camera">
                                        <input type="file" id="file-input" name="bild2" accept="image/*" style="display:none;">
                                    </div>
                                    <script>
                                        function toggleFileInput(value) {
                                            const cameraInput = document.getElementById('camera-input');
                                            const fileInput = document.getElementById('file-input');

                                            if (value === 'camera') {
                                                cameraInput.style.display = 'block';
                                                fileInput.style.display = 'none';
                                            } else {
                                                cameraInput.style.display = 'none';
                                                fileInput.style.display = 'block';
                                            }
                                        }

                                        document.addEventListener('DOMContentLoaded', function() {
                                            toggleFileInput(document.getElementById('upload-option').value);
                                        });
                                    </script>
                                <?php else: ?>
                                    <input type="file" name="bild" accept="image/*" required>
                                <?php endif; ?>

                                <button type="submit">Bild hochladen</button>
                            <?php else: ?>
                                <span>Bild bereits hochgeladen</span>
                                <button type="submit" name="delete_image">Bild löschen</button>
                            <?php endif; ?>
                        </form>
                        <?php
                            if($fehlzeit['bestaetigt'] == 3){
                                ?>
                                    <form action='' method='POST'>
                                        <input type='hidden' name='fehlzeit_id_to_delete' value="<?php echo $fehlzeit['fehlzeit_id']; ?>">
                                        <button type='submit' name='delete_fehlzeit'>Fehlzeit löschen</button>
                                    </form>
                                <?php
                            }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php
                if(empty($fehlzeiten)) {
                    ?>
                        <tr>
                            <td colspan='6' class='fehlzeitenEmtytd'>Hier sieht alles gut aus!</td>
                        </tr>
                    <?php
                }
            ?>
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