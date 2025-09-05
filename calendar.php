<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'educonnect');
mysqli_set_charset($conn, "utf8mb4");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Funktion zum Abrufen der Prüfungen
function getExams($conn) {
    $exams = [];
    $result = $conn->query("SELECT * FROM ueberpruefung");
    while ($row = $result->fetch_assoc()) {
        $exams[] = $row;
    }
    return $exams;
}

// Funktion zum Abrufen der Fächer
function getFaecher($conn) {
    $faecher = [];
    $result = $conn->query("SELECT * FROM faecher");
    while ($row = $result->fetch_assoc()) {
        $faecher[] = $row;
    }
    return $faecher;
}

// Funktion zum Abrufen der Stufen
function getStufen($conn) {
    $stufen = [];
    $result = $conn->query("SELECT * FROM stufe");
    while ($row = $result->fetch_assoc()) {
        $stufen[] = $row;
    }
    return $stufen;
}

// Funktion zum Abrufen der Stunden
function getStunden($conn) {
    $stunden = [];
    $result = $conn->query("SELECT * FROM stunden");
    while ($row = $result->fetch_assoc()) {
        $stunden[] = $row;
    }
    return $stunden;
}

// Funktion zum Abrufen der Prüfungsarten
function getPruefungsarten($conn) {
    $pruefungsarten = [];
    $result = $conn->query("SELECT * FROM pruefungsart");
    while ($row = $result->fetch_assoc()) {
        $pruefungsarten[] = $row;
    }
    return $pruefungsarten;
}

// Funktion zum Abrufen der Lehrer
function getLehrer($conn) {
    $lehrer = [];
    $result = $conn->query("SELECT * FROM lehrer");
    while ($row = $result->fetch_assoc()) {
        $lehrer[] = $row;
    }
    return $lehrer;
}

$wochentag = date('w'); 
$aktuelles_datum = date('d-m-Y'); 
$aktuelle_zeit = date('H:i:s');

$message = '';
$message_type = '';

// Funktion zum Hinzufügen einer Prüfung
if (isset($_POST['add_exam'])) {
    $datum = isset($_POST['datum']) ? $_POST['datum'] : null;
    $fach_id = isset($_POST['fach_id']) ? $_POST['fach_id'] : null;
    $lehrer_id = isset($_POST['lehrer_id']) ? $_POST['lehrer_id'] : null;
    $stufe_id = isset($_POST['stufe_id']) ? $_POST['stufe_id'] : null;
    $beginn_stunde_id = isset($_POST['beginn_stunde_id']) ? $_POST['beginn_stunde_id'] : null;
    $ende_stunde_id = isset($_POST['ende_stunde_id']) ? $_POST['ende_stunde_id'] : null;
    $pruefungs_id = isset($_POST['pruefungs_id']) ? $_POST['pruefungs_id'] : null;
    $beschreibung = isset($_POST['beschreibung']) ? $_POST['beschreibung'] : null;

    if ($datum && $fach_id && $lehrer_id && $stufe_id && $beginn_stunde_id && $ende_stunde_id && $pruefungs_id && $beschreibung) {
        $stmt = $conn->prepare("INSERT INTO ueberpruefung (datum, fach_id, lehrer_id, stufe_id, beginn, ende, pruefungs_id, beschreibung) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siiiiiss", $datum, $fach_id, $lehrer_id, $stufe_id, $beginn_stunde_id, $ende_stunde_id, $pruefungs_id, $beschreibung);
        if ($stmt->execute()) {
            $message = 'Prüfung erfolgreich hinzugefügt.';
            $message_type = 'success';
        } else {
            $message = 'Fehler beim Hinzufügen der Prüfung.';
            $message_type = 'error';
        }
        $stmt->close();
    } else {
        $message = 'Bitte füllen Sie alle Felder aus.';
        $message_type = 'error';
    }
    // Umleitung zur aktuellen Seite
    header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message) . "&message_type=" . $message_type);
    exit();
}

// Funktion zum Bearbeiten einer Prüfung
if (isset($_POST['edit_exam'])) {
    $exam_id = isset($_POST['exam_id']) ? $_POST['exam_id'] : null;
    $datum = isset($_POST['datum']) ? $_POST['datum'] : null;
    $fach_id = isset($_POST['fach_id']) ? $_POST['fach_id'] : null;
    $lehrer_id = isset($_POST['lehrer_id']) ? $_POST['lehrer_id'] : null;
    $stufe_id = isset($_POST['stufe_id']) ? $_POST['stufe_id'] : null;
    $beginn_stunde_id = isset($_POST['beginn_stunde_id']) ? $_POST['beginn_stunde_id'] : null;
    $ende_stunde_id = isset($_POST['ende_stunde_id']) ? $_POST['ende_stunde_id'] : null;
    $pruefungs_id = isset($_POST['pruefungs_id']) ? $_POST['pruefungs_id'] : null;
    $beschreibung = isset($_POST['beschreibung']) ? $_POST['beschreibung'] : null;

    if ($exam_id && $datum && $fach_id && $lehrer_id && $stufe_id && $beginn_stunde_id && $ende_stunde_id && $pruefungs_id && $beschreibung) {
        $stmt = $conn->prepare("UPDATE ueberpruefung SET datum = ?, fach_id = ?, lehrer_id = ?, stufe_id = ?, beginn = ?, ende = ?, pruefungs_id = ?, beschreibung = ? WHERE ueberpruefung_id = ?");
        $stmt->bind_param("siiiiissi", $datum, $fach_id, $lehrer_id, $stufe_id, $beginn_stunde_id, $ende_stunde_id, $pruefungs_id, $beschreibung, $exam_id);
        if ($stmt->execute()) {
            $message = 'Prüfung erfolgreich bearbeitet.';
            $message_type = 'success';
        } else {
            $message = 'Fehler beim Bearbeiten der Prüfung.';
            $message_type = 'error';
        }
        $stmt->close();
    } else {
        $message = 'Bitte füllen Sie alle Felder aus.';
        $message_type = 'error';
    }
    // Umleitung zur aktuellen Seite
    header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message) . "&message_type=" . $message_type);
    exit();
}

// Nachrichten anzeigen
if (isset($_GET['message']) && isset($_GET['message_type'])) {
    $message = urldecode($_GET['message']);
    $message_type = $_GET['message_type'];
}

// Hole den aktuellen Monat und das aktuelle Jahr
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Berechne den vorherigen und nächsten Monat
$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $month + 1;
$next_year = $year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

// Hole die Prüfungen für den aktuellen Monat und das aktuelle Jahr
$sql = "SELECT ueberpruefung.*, faecher.fach_name, lehrer.vorname, lehrer.nachname, stunden_beginn.beginn, stunden_ende.ende, pruefungsart.art, stufe.stufe_name 
        FROM ueberpruefung 
        JOIN faecher ON ueberpruefung.fach_id = faecher.fach_id 
        JOIN lehrer ON ueberpruefung.lehrer_id = lehrer.lehrer_id 
        JOIN stunden AS stunden_beginn ON ueberpruefung.beginn = stunden_beginn.stunde_id
        JOIN stunden AS stunden_ende ON ueberpruefung.ende = stunden_ende.stunde_id  
        JOIN pruefungsart ON ueberpruefung.pruefungs_id = pruefungsart.pruefungs_id
        JOIN stufe ON ueberpruefung.stufe_id = stufe.stufe_id
        WHERE MONTH(datum) = $month AND YEAR(datum) = $year";
$result = mysqli_query($conn, $sql);
$exams = [];
while ($row = mysqli_fetch_assoc($result)) {
    $exams[date('Y-m-d', strtotime($row['datum']))][] = $row;
}

    $faecher = getFaecher($conn);
    $stufen = getStufen($conn);
    $stunden = getStunden($conn);
    $pruefungsarten = getPruefungsarten($conn);
    $lehrer = getLehrer($conn);

include 'navigation.php';
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="calendar.css">
    <title>Kalender</title>
</head>
<body>
    
    <div class="calendar-container">
        <h1>Kalender</h1>
        <p class="current-datetime"><?php echo strftime('%A', strtotime($aktuelles_datum)); ?>, <?php echo $aktuelles_datum; ?> - <?php echo $aktuelle_zeit; ?></p>
        <div id="calendar">
            <div class="month-navigation">
                <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>">Letzter Monat</a>
                <span style="font-size: 1.5em"><?php echo strftime('%B %Y', mktime(0, 0, 0, $month, 1, $year)); ?></span>
                <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>">Nächster Monat</a>
            </div>
            <table>
                <tr>
                    <th>Montag</th>
                    <th>Dienstag</th>
                    <th>Mittwoch</th>
                    <th>Donnerstag</th>
                    <th>Freitag</th>
                    <th>Samstag</th>
                    <th>Sonntag</th>
                </tr>
                <tr>
                    <?php
                    // Anzahl der leeren Tage
                    $first_day = mktime(0, 0, 0, $month, 1, $year);
                    $day_of_week = date('N', $first_day); // 1 (Montag) bis 7 (Sonntag)
                    $blank_days = $day_of_week - 1;
                    $days_in_month = date('t', $first_day);

                    for ($i = 0; $i < $blank_days; $i++) {
                        echo '<td></td>';
                    }

                    for ($day = 1; $day <= $days_in_month; $day++) {
                        $current_date = date('Y-m-d', mktime(0, 0, 0, $month, $day, $year));
                        $class = ($current_date == date('Y-m-d')) ? 'today' : '';
                        $class .= isset($exams[$current_date]) ? ' has-exam' : '';
                        echo "<td class='$class'>$day";
                        // Tooltip mit Prüfungsinformationen
                        if (isset($exams[$current_date])) {
                            echo "<div class='tooltip'>";
                            foreach ($exams[$current_date] as $exam) {
                                echo "<div class='exam-info'>";
                                echo "<strong>Fach:</strong> {$exam['fach_name']}<br>";
                                echo "<strong>Lehrer:</strong> {$exam['vorname']} {$exam['nachname']}<br>";
                                echo "<strong>Stufe:</strong> {$exam['stufe_name']}<br>";
                                echo "<strong>Prüfungsart:</strong> {$exam['art']}<br>";
                                echo "<strong>Beginn:</strong> {$exam['beginn']}<br>";
                                echo "<strong>Ende:</strong> {$exam['ende']}<br>";
                                echo "<strong>Beschreibung:</strong> {$exam['beschreibung']}<br>";
                                echo "<form method='GET' style='display:inline;'>";
                                echo "<input type='hidden' name='edit_exam_id' value='{$exam['ueberpruefung_id']}'>";
                                if ($_SESSION['rang'] == 'lehrer') {
                                    echo "<form method='GET' style='display:inline;'>";
                                    echo "<input type='hidden' name='edit_exam_id' value='{$exam['ueberpruefung_id']}'>";
                                    echo "<br><button type='submit' class='edit-button'>Bearbeiten</button>";
                                    echo "</form>";
                                }
                                echo "</form>";
                                echo "</div>";
                            }
                            echo "</div>";
                        }
                        echo "</td>";

                        if (($day + $blank_days) % 7 == 0) {
                            echo '</tr><tr>';
                        }
                    }

                    while (($day + $blank_days) % 7 != 1) {
                        echo '<td></td>';
                        $day++;
                    }
                    ?>
                </tr>
            </table>
        </div>
        <?php 
            // Formular zum Hinzufügen einer Prüfung für Lehrer
            if ($_SESSION['rang'] == 'lehrer'): 
        ?>
            <div class="add-exam-form">
                <h2>Prüfung hinzufügen</h2>
                <form method="POST">
                    <label for="datum"><b>Datum:</label>
                    <input type="date" id="datum" name="datum" required>
                    <label for="fach_id">Fach:</label>
                    <select id="fach_id" name="fach_id" required>
                        <?php foreach ($faecher as $fach): ?>
                            <option value="<?php echo $fach['fach_id']; ?>"><?php echo $fach['fach_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="lehrer_id">Lehrer:</label>
                    <select id="lehrer_id" name="lehrer_id" required>
                        <?php foreach ($lehrer as $lehr): ?>
                            <option value="<?php echo $lehr['lehrer_id']; ?>"><?php echo $lehr['vorname'] . ' ' . $lehr['nachname']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="stufe_id">Stufe:</label>
                    <select id="stufe_id" name="stufe_id" required>
                        <?php foreach ($stufen as $stufe): ?>
                            <option value="<?php echo $stufe['stufe_id']; ?>"><?php echo $stufe['stufe_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="beginn_stunde_id">Beginn Stunde:</label>
                    <select id="beginn_stunde_id" name="beginn_stunde_id" required>
                        <?php foreach ($stunden as $stunde): ?>
                            <option value="<?php echo $stunde['stunde_id']; ?>"><?php echo $stunde['stunde'] . ' (' . $stunde['beginn'] . ' - ' . $stunde['ende'] . ')'; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="ende_stunde_id">Ende Stunde:</label>
                    <select id="ende_stunde_id" name="ende_stunde_id" required>
                        <?php foreach ($stunden as $stunde): ?>
                            <option value="<?php echo $stunde['stunde_id']; ?>"><?php echo $stunde['stunde'] . ' (' . $stunde['beginn'] . ' - ' . $stunde['ende'] . ')'; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="pruefungs_id">Prüfungsart:</label>
                    <select id="pruefungs_id" name="pruefungs_id" required>
                        <?php foreach ($pruefungsarten as $pruefungsart): ?>
                            <option value="<?php echo $pruefungsart['pruefungs_id']; ?>"><?php echo $pruefungsart['art']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="beschreibung">Beschreibung:</label>
                    <input type="text" id="beschreibung" name="beschreibung" required>
                    <button type="submit" name="add_exam">Hinzufügen</b></button>
                </form>
                <?php if ($message): ?>
                    <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php
        // Formular zum Bearbeiten einer Prüfung (nicht wundern wegen dem Namen unten "add-exam-form", das ist nur ein Klassenname, da das Design für das Bearbeiten nicht funktioniert hat)
        if ($_SESSION['rang'] == 'lehrer' && isset($_GET['edit_exam_id'])) {
            $edit_exam_id = $_GET['edit_exam_id'];
            $stmt = $conn->prepare("SELECT * FROM ueberpruefung WHERE ueberpruefung_id = ?");
            $stmt->bind_param("i", $edit_exam_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $exam_to_edit = $result->fetch_assoc();
            $stmt->close();
            if ($exam_to_edit):
        ?>
        
            <div class="add-exam-form">
                <h2>Prüfung bearbeiten</h2>
                <form method="POST">
                    <input type="hidden" name="exam_id" value="<?php echo $exam_to_edit['ueberpruefung_id']; ?>">
                    <label for="datum"><b>Datum:</label>
                    <input type="date" id="datum" name="datum" value="<?php echo $exam_to_edit['datum']; ?>" required>
                    <label for="fach_id">Fach:</label>
                    <select id="fach_id" name="fach_id" required>
                        <?php foreach ($faecher as $fach): ?>
                            <option value="<?php echo $fach['fach_id']; ?>" <?php echo ($fach['fach_id'] == $exam_to_edit['fach_id']) ? 'selected' : ''; ?>><?php echo $fach['fach_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="lehrer_id">Lehrer:</label>
                    <select id="lehrer_id" name="lehrer_id" required>
                        <?php foreach ($lehrer as $lehr): ?>
                            <option value="<?php echo $lehr['lehrer_id']; ?>" <?php echo ($lehr['lehrer_id'] == $exam_to_edit['lehrer_id']) ? 'selected' : ''; ?>><?php echo $lehr['vorname'] . ' ' . $lehr['nachname']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="stufe_id">Stufe:</label>
                    <select id="stufe_id" name="stufe_id" required>
                        <?php foreach ($stufen as $stufe): ?>
                            <option value="<?php echo $stufe['stufe_id']; ?>" <?php echo ($stufe['stufe_id'] == $exam_to_edit['stufe_id']) ? 'selected' : ''; ?>><?php echo $stufe['stufe_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="beginn_stunde_id">Beginn Stunde:</label>
                    <select id="beginn_stunde_id" name="beginn_stunde_id" required>
                        <?php foreach ($stunden as $stunde): ?>
                            <option value="<?php echo $stunde['stunde_id']; ?>" <?php echo ($stunde['stunde_id'] == $exam_to_edit['beginn']) ? 'selected' : ''; ?>><?php echo $stunde['stunde'] . ' (' . $stunde['beginn'] . ' - ' . $stunde['ende'] . ')'; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="ende_stunde_id">Ende Stunde:</label>
                    <select id="ende_stunde_id" name="ende_stunde_id" required>
                        <?php foreach ($stunden as $stunde): ?>
                            <option value="<?php echo $stunde['stunde_id']; ?>" <?php echo ($stunde['stunde_id'] == $exam_to_edit['ende']) ? 'selected' : ''; ?>><?php echo $stunde['stunde'] . ' (' . $stunde['beginn'] . ' - ' . $stunde['ende'] . ')'; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="pruefungs_id">Prüfungsart:</label>
                    <select id="pruefungs_id" name="pruefungs_id" required>
                        <?php foreach ($pruefungsarten as $pruefungsart): ?>
                            <option value="<?php echo $pruefungsart['pruefungs_id']; ?>" <?php echo ($pruefungsart['pruefungs_id'] == $exam_to_edit['pruefungs_id']) ? 'selected' : ''; ?>><?php echo $pruefungsart['art']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="beschreibung">Beschreibung:</label>
                    <input type="text" id="beschreibung" name="beschreibung" value="<?php echo $exam_to_edit['beschreibung']; ?>" required>
                    <button type="submit" name="edit_exam">Bearbeiten</button>
                </form>
            </div>
        <?php
            endif;
        }
        ?>
    </div>
</body>
</html>

