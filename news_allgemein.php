<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News hinzufügen</title>
    <link rel="stylesheet" href="educonnect.css">
    <link rel="shortcut icon" href="picture.ico" type="image/x-icon">
</head>
<body>
<?php
    session_start();
    $rang1 = $_SESSION['rang1'];
    if($rang1 === '7'){
        echo"<a href='admin.php'>Zurück zur Adminseite</a>";
    }
    ?>
    <h1>News hinzufügen</h1>

    <form action="" method="POST">
        <div class="nav-box">
            <a href="news_allgemein.php" class="active">News Allgemein</a>
            <a href="news_land.php">News Land</a>
            <a href="news_stufe.php">News Stufe</a>
            <a href="news_update_delete.php">News Bearbeiten/Löschen</a>
        </div>
        <br><br>

        <input type="hidden" name="news_type" value="news_allgemein"> 

        <label for="schule_id">Schule:</label>
        <select name="schule_id" id="schule" required>
            <?php

            if (!isset($_SESSION['rang1']) || $_SESSION['rang1'] < 1) {
                header("Location: index.php");
                exit();
            }
            $conn = new mysqli("localhost", "root", "", "educonnect");

            if ($conn->connect_error) {
                die("Verbindung zur Datenbank fehlgeschlagen: " . $conn->connect_error);
            }

            $sql_schule = "SELECT * FROM schulen";
            $result = $conn->query($sql_schule);

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['schule_id'] . "'>" . $row['schule_name'] . "</option>";
                }
            } else {
                echo "<option value=''>Keine Schulen gefunden</option>";
            }
            ?>
        </select>
        <br><br>

        <label for="titel">Titel:</label>
        <input type="text" name="titel" id="titel" required>
        <br><br>

        <label for="inhalt">Inhalt:</label>
        <textarea name="inhalt" id="inhalt" required></textarea>
        <br><br>

        <label for="start_datum">Startdatum:</label>
        <input type="date" name="start_datum" id="start_datum" required>
        <br><br>

        <label for="end_datum">Enddatum:</label>
        <input type="date" name="end_datum" id="end_datum" required>
        <br><br>

        <input type="submit" name="submit" value="Hinzufügen">
    </form>

    <?php
    if (isset($_POST['submit'])) {
        $news_type = $_POST['news_type'];
        $schule_id = $_POST['schule_id'];
        $titel = $_POST['titel'];
        $inhalt = $_POST['inhalt'];
        $start_datum = $_POST['start_datum'];
        $end_datum = $_POST['end_datum'];

        if ($news_type === 'news_allgemein') {
             
            $sql_update = "UPDATE news_allgemein SET wichtigkeit = 0 WHERE wichtigkeit = 1";
            if ($conn->query($sql_update) === FALSE) {
                echo "Fehler beim Aktualisieren der Wichtigkeit: " . $conn->error;
            }

            
            $sql_insert = "INSERT INTO news_allgemein (schule_id, titel, inhalt, start_datum, end_datum, wichtigkeit) 
                           VALUES ($schule_id, '$titel', '$inhalt', '$start_datum', '$end_datum', 1)";

            if ($conn->query($sql_insert) === TRUE) {
                echo "Die Nachricht wurde erfolgreich hinzugefügt.";
            } else {
                echo "Fehler beim Hinzufügen der Nachricht: " . $conn->error . "<br>SQL: " . $sql_insert; 
            }
        }

        $conn->close();
    }
    ?>
    </body>
    <?php 
        if($_SESSION['rang1'] === '7'){
            
        }else{
            include 'navigation.php';
        }
    ?>
</html>