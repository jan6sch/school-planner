<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Kalender</title>
    <link rel="stylesheet" href="calendar_mainpage.css">
</head>
<body>
    <a href="calendar.php">
<?php
// Überprüfen, ob der Benutzer angemeldet ist
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit();
    }

// Aktuelles Datum auf Deutsch
setlocale(LC_TIME, 'de_DE.UTF-8');
    if (isset($_GET['month']) && isset($_GET['year'])) {
        $month = $_GET['month'];
        $year = $_GET['year'];
    } else {
        $month = date('m');
        $year = date('Y');
    }

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

// Erster Tag des Monats
    $first_day = mktime(0, 0, 0, $month, 1, $year);
    $days_in_month = date('t', $first_day);
    $month_name = strftime('%B', $first_day); // Monat auf Deutsch
    $day_of_week = date('N', $first_day); // 1 (Montag) bis 7 (Sonntag)

// Überprüfen, ob der Benutzer angemeldet ist bzw. ob der Rang stimmt
    $rank = $_SESSION['rank'] ?? null;
    $vorname = $_SESSION['vorname'] ?? 'Benutzer';
    $nachname = $_SESSION['nachname'] ?? '';

// Anzahl der leeren Tage
    $blank_days = $day_of_week - 1;

// Die aktuellen Klausuren abrufen
    $sql = "SELECT ueberpruefung.*, faecher.fach_name, lehrer.vorname, lehrer.nachname, stunden_beginn.beginn, stunden_ende.ende, pruefungsart.art, stufe.stufe_name 
            FROM ueberpruefung 
            JOIN faecher ON ueberpruefung.fach_id = faecher.fach_id 
            JOIN lehrer ON ueberpruefung.lehrer_id = lehrer.lehrer_id 
            JOIN stunden AS stunden_beginn ON ueberpruefung.beginn = stunden_beginn.stunde_id
            JOIN stunden AS stunden_ende ON ueberpruefung.ende = stunden_ende.stunde_id  
            JOIN pruefungsart ON ueberpruefung.pruefungs_id = pruefungsart.pruefungs_id
            JOIN stufe ON ueberpruefung.stufe_id = stufe.stufe_id
            WHERE MONTH(datum) = '$month' AND YEAR(datum) = '$year'";
    $result = mysqli_query($conn, $sql);
    $exams = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $exams[date('Y-m-d', strtotime($row['datum']))][] = $row;
        }
?>

<div class="calendar-container">
    <div class="calendar-header">
        <h1><?php echo $month_name . ' ' . $year; ?></h1>
        <div>
            <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>">Letzter Monat</a>
            <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>">Nächster Monat</a>
        </div>
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
            for ($i = 0; $i < $blank_days; $i++) {
                echo '<td></td>';
            }

            for ($day = 1; $day <= $days_in_month; $day++) {
                $current_date = date('Y-m-d', mktime(0, 0, 0, $month, $day, $year));
                $class = ($current_date == date('Y-m-d')) ? 'today' : '';
                $class .= isset($exams[$current_date]) ? ' has-exam' : '';
                echo "<td class='$class'>$day";
                // Hier ist die Hoverbox, bei der ich das Design nicht hinbekommen habe. Vielleicht könnt ihr das ja nochmal überarbeiten        
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
</a>
</body>
</html>
