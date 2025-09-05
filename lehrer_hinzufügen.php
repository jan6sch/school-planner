<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lehrer Hinzufügen</title>
    <link rel="stylesheet" href="educonnect.css">
    <link rel="shortcut icon" href="picture.ico" type="image/x-icon">
</head>

<body>


    <h1>Lehrer Hinzufügen</h1>
    <div class="nav-container">
        <div class="nav-box">
            <a href="schueler_hinzufügen.php" class="">Schüler</a>
            <a href="lehrer_hinzufügen.php" class="active">Lehrer</a>
        </div>
    </div>
    
    
    <?php
    session_start();
    $conn = new mysqli("localhost", "root", "", "educonnect");
    
    

    if (!isset($_SESSION['rang1']) || $_SESSION['rang1'] < 7) {
        header("Location: index.php");
        exit();
    }

    if ($conn->connect_error) {
        echo "Fehler bei der Verbindung zur Datenbank: " . $conn->connect_error;
        exit();
    }
    
    $sql_schule = "SELECT * FROM schulen ";
    $result = $conn->query($sql_schule);

    $sql_rang = "SELECT * FROM rang ";
    $result1 = $conn->query($sql_rang);

    if (!isset($_POST['submit'])) {
        echo "
        <div class='hinzufügen'> 
        <form action='' method='POST'>
            <table>
                <tr>
                    <td>Schule ID :</td>
                    <td><select name='schule_id' id='schule' required>";

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . $row['schule_id'] . "'>" . $row['schule_name'] . "</option>";
                        }
                    } else {
                        echo "<option value=''>Keine Schulen gefunden</option>";
                    }

                    echo" </select></td>
                </tr>
                <tr>
                    <td>Vorname:</td>
                    <td><input type='text' name='vorname' placeholder='Vorname' required></td>
                </tr>
                <tr>
                    <td>Nachname:</td>
                    <td><input type='text' name='nachname' placeholder='Nachname' required></td>
                </tr>
                <tr>
                    <td>E-Mail:</td>
                    <td><input type='email' name='email' placeholder='E-Mail' required></td>
                </tr>
                <tr>
                    <td>Rang:</td>
                    <td><select name='rang_id' id='rang_name' required>";

                    if ($result1->num_rows > 0) {
                        while ($row = $result1->fetch_assoc()) {
                            echo "<option value='" . $row['rang_id'] . "'>" . $row['rang_name'] . "</option>";
                        }
                    } else {
                        echo "<option value=''>Kein Ränge gefunden</option>";
                    }

                    echo" </select></td>
                </tr>
                <tr>
                    <td>Passwort:</td>
                    <td><input type='password' name='password' placeholder='Passwort' required></td>
                </tr>
            </table>
            <br>
            <input type='submit' name='submit' value='Hinzufügen'>
            <input type='reset' name='reset' value='Zurücksetzen'>
        </form>
        </div>
        ";
    } else {
        $schule_id = $_POST['schule_id'];
        $vorname = $_POST['vorname'];
        $nachname = $_POST['nachname'];
        $email = $_POST['email'];
        $rang = $_POST['rang_id'];
        $password = $_POST['password'];

        $sql_insert = "INSERT INTO `lehrer`(`schule_id`, `vorname`, `nachname`, `email`, `rang`, `password`) VALUES ($schule_id, '$vorname', '$nachname', '$email', $rang, '$password')";
        
        if ($conn->query($sql_insert) === TRUE) {
            echo "Der Lehrer wurde hinzugefügt";
        } else {
            echo "Fehler beim Hinzufügen des Lehrers: " . $conn->error;
        }
    }
    $conn->close();
    ?>
</body>
<?php 
        if($_SESSION['rang1'] === '7'){
            
        }else{
            include 'navigation.php';
        }
    ?>
</html>