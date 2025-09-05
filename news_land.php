<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News_land hinzufügen</title>
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
    <h1>Landes News hinzufügen</h1>
    <form action="" method="POST">
            <div class="nav-container">
                <div class="nav-box">
                    <a href="news_allgemein.php">News Allgemein</a>
                    <a href="news_land.php" class="active">News Land</a>
                    <a href="news_stufe.php">News Stufe</a>
                    <a href="news_update_delete.php">News Bearbeiten/Löschen</a>
                </div>
            </div>

    <?php

    
    if (!isset($_SESSION['rang1']) || $_SESSION['rang1'] < 6) {
        header("Location: index.php");
        exit();
    }
    
    ?>

    

        <input type="hidden" name="news_type" value="news_land"> 

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
        <input type="reset" value="Zurücksetzen">
    </form>

    <?php
    if (isset($_POST['submit'])) {
        $news_type = $_POST['news_type'];
        $titel = $_POST['titel'];
        $inhalt = $_POST['inhalt'];
        $start_datum = $_POST['start_datum'];
        $end_datum = $_POST['end_datum'];

         
        $conn = new mysqli("localhost", "root", "", "educonnect");

         
        if ($conn->connect_error) {
            die("Verbindung zur Datenbank fehlgeschlagen: " . $conn->connect_error);
        }

         
        $sql_update = "UPDATE news_land SET wichtigkeit = 0 WHERE wichtigkeit = 1";
        if ($conn->query($sql_update) === FALSE) {
            echo "Fehler beim Aktualisieren der Wichtigkeit: " . $conn->error;
        }

        
        $sql_insert = "INSERT INTO news_land (titel, inhalt, start_datum, end_datum, wichtigkeit) 
                       VALUES ('$titel', '$inhalt', '$start_datum', '$end_datum', 1)";  

        
        if ($conn->query($sql_insert) === TRUE) {
            echo "Die Nachricht wurde erfolgreich hinzugefügt.";
        } else {
            echo "Fehler beim Hinzufügen der Nachricht: " . $conn->error;
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