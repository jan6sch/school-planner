<?php
session_start();

// Verbindung zur Datenbank herstellen
$conn = new mysqli('localhost', 'root', '', 'educonnect');
mysqli_set_charset($conn, "utf8mb4");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// HTML-Header
echo '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Austritt aus Team</title>
    <style>
        .TAustrittKursContainer {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .TAustrittKursMessage {
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
    <div class="TAustrittKursContainer">';

if (isset($_POST['team_id'])) {
    $team_id = $_POST['team_id'];
    $user_id = $_SESSION['user_id'];
    $rang = $_SESSION['rang'];

    // 1. Löschen des Eintrags aus der `beitritt`-Tabelle
    $delete_beitritt_sql = "DELETE FROM beitritt WHERE user_id = $user_id AND team_id = $team_id AND rang = '$rang'";
    if ($conn->query($delete_beitritt_sql) === TRUE) {
        echo '<div class="TAustrittKursMessage success">Erfolgreich aus dem Team ausgetreten.</div>';

        // 2. Überprüfung des Stundenplans, um die relevanten Einträge zu löschen
        $stundenplan_sql = "SELECT fach_id, tag_id, stunde_id, lehrer_id FROM stundenplan_lehrer WHERE team_id = $team_id";
        $stundenplan_result = $conn->query($stundenplan_sql);

        if ($stundenplan_result->num_rows > 0) {
            // 3. Löschen der Einträge aus der `stundenplan_schueler`-Tabelle
            while ($row = $stundenplan_result->fetch_assoc()) {
                $fach_id = $row['fach_id'];
                $tag_id = $row['tag_id'];
                $stunde_id = $row['stunde_id'];
                $lehrer_id = $row['lehrer_id'];

                $delete_stundenplan_sql = "DELETE FROM stundenplan_schueler WHERE schueler_id = '$user_id' AND fach_id = '$fach_id' AND tag_id = '$tag_id' AND stunde_id = '$stunde_id' AND lehrer_id = '$lehrer_id'";
                $conn->query($delete_stundenplan_sql);
            }
            echo '<div class="TAustrittKursMessage success">Stundenplan erfolgreich aktualisiert.</div>';
        } else {
            echo '<div class="TAustrittKursMessage">Keine Stunden für dieses Team gefunden.</div>';
        }
    } else {
        echo '<div class="TAustrittKursMessage error">Fehler beim Austritt aus dem Team: ' . $conn->error . '</div>';
    }
} else {
    echo '<div class="TAustrittKursMessage error">Ungültige Anfrage.</div>';
}

$conn->close();

echo '    </div>
</body>
</html>';
?>