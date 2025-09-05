<?php
session_start();

// Verbindung zur Datenbank herstellen
$conn = new mysqli('localhost', 'root', '', 'educonnect');
mysqli_set_charset($conn, "utf8mb4");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$schule_id = $_SESSION['schule_id'];


// HTML-Header
echo '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beitritt zum Team</title>
    <style>
        .BKursContainer {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .BKursMessage {
            margin: 20px 0;
            padding: 10px;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="BKursContainer">';

if (isset($_POST['team_id']) && isset($_POST['code'])) {
    $team_id = $_POST['team_id'];
    $user_id = $_SESSION['user_id'];
    $code = $_POST['code'];
    $rang = $_SESSION['rang'];

    // 1. Eintrag in die `beitritt`-Tabelle
    $insert_beitritt_sql = "INSERT INTO beitritt (user_id, rang, team_id) VALUES ($user_id, '$rang', $team_id)";
    if ($conn->query($insert_beitritt_sql) === TRUE) {
        echo '<div class="BKursMessage success">Erfolgreich dem Team beigetreten.</div>';

        // 2. Überprüfung des Stundenplans
        $stundenplan_sql = "SELECT * FROM stundenplan_lehrer WHERE team_id = $team_id";
        $stundenplan_result = $conn->query($stundenplan_sql);

        if ($stundenplan_result->num_rows > 0) {
            // 3. Eintrag in die `stundenplan_schueler`-Tabelle für jede gefundene Stunde
            while ($row = $stundenplan_result->fetch_assoc()) {
                $fach_id = $row['fach_id'];
                $tag_id = $row['tag_id'];
                $stunde_id = $row['stunde_id'];
                $lehrer_id = $row['lehrer_id'];
                $raum_id = $row['raum_id'];

                $insert_stundenplan_sql = "INSERT INTO stundenplan_schueler (schule_id, schueler_id, fach_id, tag_id, stunde_id, lehrer_id, raum_id) 
                                            VALUES ('$schule_id' , '$user_id', '$fach_id', '$tag_id', '$stunde_id', '$lehrer_id', '$raum_id')";
                $conn->query($insert_stundenplan_sql);
            }
            echo '<div class="BKursMessage success">Stundenplan erfolgreich aktualisiert.</div>';
            echo '<a href="mainpage.php" class="BKursLink">Zurück zur Startseite</a>';
        } else {
            echo '<div class="BKursMessage">Keine Stunden für dieses Team gefunden.</div>';
            echo '<a href="mainpage.php" class="BKursLink">Zurück zur Startseite</a>';
        }
    } else {
        echo '<div class="BKursMessage error">Fehler beim Beitritt zum Team: ' . $conn->error . '</div>';
        echo '<a href="auswahl_kurs2.php" class="BKursLink">Zurück zur Startseite</a>';
    }
} else {
    echo '<div class="BKursMessage error">Ungültige Anfrage.</div>';
    echo '<a href="auswahl_kurs2.php" class="BKursLink">Zurück zur Startseite</a>';
}

$conn->close();

echo '    </div>
</body>
</html>';
?>