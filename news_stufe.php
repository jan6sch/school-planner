<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News_stufe hinzufügen</title>
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
    <h1>Stufen News hinzufügen</h1>
    <form action="" method="POST">
        <div class="nav-container">
            <div class="nav-box">
                <a href="news_allgemein.php">News Allgemein</a>
                <a href="news_land.php">News Land</a>
                <a href="news_stufe.php" class="active">News Stufe</a>
                <a href="news_update_delete.php">News Bearbeiten/Löschen</a>
            </div>
        </div>

        <input type="hidden" name="news_type" value="news_stufe"> 

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

        <label for="stufe_id">Stufe:</label>
        <select name="stufe_id" id="stufe_id" required>
            <?php
            $sql_stufe = "SELECT * FROM stufe";
            $result1 = $conn->query($sql_stufe);

            if ($result1->num_rows > 0) {
                while ($row = $result1->fetch_assoc()) {
                    echo "<option value='" . $row['stufe_id'] . "'>" . $row['stufe_name'] . "</option>";
                }
            } else {
                echo "<option value=''>Keine Stufen gefunden</option>";
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
        <input type="reset" value="Zurücksetzen">
    </form>

    <?php
    if (isset($_POST['submit'])) {
        $news_type = $_POST['news_type'];
        $schule_id = $_POST['schule_id'];
        $stufe = $_POST['stufe_id'];
        $titel = $_POST['titel'];
        $inhalt = $_POST['inhalt'];
        $start_datum = $_POST['start_datum'];
        $end_datum = $_POST['end_datum'];

         
        $conn = new mysqli("localhost", "root", "", "educonnect");

         
        if ($conn->connect_error) {
            die("Verbindung zur Datenbank fehlgeschlagen: " . $conn->connect_error);
        }

  
        $sql_update = "UPDATE news_stufe SET wichtigkeit = 0 WHERE wichtigkeit = 1";
        $conn->query($sql_update);

        
        $sql_insert = "INSERT INTO news_stufe (schule_id, stufe_id, titel, inhalt, start_datum, end_datum, wichtigkeit) 
                       VALUES ($schule_id, $stufe, '$titel', '$inhalt', '$start_datum', '$end_datum', 1)";

         
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