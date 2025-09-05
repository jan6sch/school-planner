<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kurs Auswahl</title>
    <link rel="stylesheet" href="team_insert.css">
</head>
<body>

 
    <div class="AKursContainer">
        <?php
            session_start();


            
            // Verbindung zur Datenbank herstellen
            $conn = new mysqli('localhost', 'root', '', 'educonnect');
            mysqli_set_charset($conn, "utf8mb4");
            
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            // Beispielhafte Session-Daten für den Schüler
             

            // Überprüfen, ob der Code eingegeben wurde
            if (isset($_POST['code'])) {
                $input_code = $_POST['code'];

                // SQL-Abfrage mit JOIN, um Lehrer- und Fachinformationen zu erhalten
                $sql = "
                    SELECT t.team_id, t.team_name, t.code_id, l.vorname AS lehrer_vorname, l.nachname AS lehrer_nachname, f.fach_name
                    FROM teams t
                    JOIN lehrer l ON t.lehrer_id = l.lehrer_id
                    JOIN faecher f ON t.fach_id = f.fach_id
                    WHERE t.schule_id = ".$_SESSION['schule_id']." 
                    AND t.stufe_id = ".$_SESSION['stufe_id']." 
                    AND t.code_id = '$input_code'
                ";
                $result = $conn->query($sql);
                if (!$result) {
                    die("SQL-Fehler: " . $conn->error);
                }
                
                if ($result->num_rows > 0) {
                    // Teams anzeigen
                    while($row = $result->fetch_assoc()) {
                        // Überprüfen, ob der Schüler bereits dem Team beigetreten ist
                        $user_id = $_SESSION['user_id'];
                        $team_id = $row['team_id'];
                        $rang = $_SESSION['rang'];

                        $check_sql = "SELECT * FROM beitritt WHERE `user_id` = '$user_id' AND `team_id` = '$team_id' AND `rang` = '$rang'";
                        $check_result = $conn->query($check_sql);

                        if ($check_result->num_rows > 0) {
                            echo "<p>Sie sind bereits Mitglied des Teams '".$row['team_name']."'.</p>";
                        } else {
                            echo "<form action='beitritt_kurs.php' method='post' class='AKursForm'>";
                            echo "<h1>Möchten Sie dem Team beitreten?</h1>";
                            echo "<input type='hidden' name='team_id' value='".$row['team_id']."'>";
                            echo "<input type='hidden' name='code' value='".$row['code_id']."'>";
                            echo "
                                <table class='AKursTable'>
                                    <tr>
                                        <td>Team Name:</td>
                                        <td>".$row['team_name']."</td>
                                    </tr>
                                    <tr>
                                        <td>Lehrer:</td>
                                        <td>".$row['lehrer_vorname']." ".$row['lehrer_nachname']."</td>
                                    </tr>
                                    <tr>
                                        <td>Fach:</td>
                                        <td>".$row['fach_name']."</td>
                                    </tr>
                                </table>
                            ";
                            echo "<input type='submit' value='Jetzt Beitreten' class='AKursButton'>";
                            echo "</form>";
                        }
                    }
                } else {
                    echo "Ungültiger Code oder keine Teams gefunden.";
                }
            } else {
                // Wenn kein Code eingegeben wurde, das Eingabeformular anzeigen
                echo "
                    <form action='' method='post' class='AKursForm'>
                        <input type='number' name='code' placeholder='Code' required>
                        <input type='submit' value='Code eingeben' class='AKursButton'>
                    </form>
                ";
                echo "<br><br>Oder möchten sie aus einem Team austreten?<br> <a href='teams_austritt.php'>Hier klicken</a>";
            }
        ?>
    </div>
    <?php include 'navigation.php'; ?>
</body>
</html>