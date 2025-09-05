<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schüler Hinzufügen</title>
    <link rel="stylesheet" href="educonnect.css">
    <link rel="shortcut icon" href="picture.ico" type="image/x-icon">
</head>
<body>

    <h1>Schüler Hinzufügen</h1>

    <div class="nav-box">
        <a href="schueler_hinzufügen.php" class="active">Schüler</a>
        <a href="lehrer_hinzufügen.php">Lehrer</a>
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

    $sql_schule = "SELECT * FROM schulen";
    $result = $conn->query($sql_schule);

    $sql_stufe = "SELECT * FROM stufe";
    $result1 = $conn->query($sql_stufe);

    if (!isset($_POST['submit'])) {
        echo "
        <div class='hinzufügen'> 
            <form action='' method='POST'>
                <table>
                    <tr>
                        <td>Schule:</td>
                        <td><select name='schule_id' id='schule' required>";

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='" . $row['schule_id'] . "'>" . $row['schule_name'] . "</option>";
                            }
                        } else {
                            echo "<option value=''>Keine Schulen gefunden</option>";
                        }

                        echo "</select></td>
                    </tr>
                    <tr>
                        <td>Stufe:</td>
                        <td><select name='stufe' placeholder='Stufe' required>";
                        if ($result1->num_rows > 0) {
                            while ($row = $result1->fetch_assoc()) {
                                echo "<option value='" . $row['stufe_id'] . "'>" . $row['stufe_name'] . "</option>";
                            }
                        } else {
                            echo "<option value=''>Keine Stufen gefunden</option>";
                        }
                                       
                        echo" </td>
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
                        <td>Passwort:</td>
                        <td><input type='password' name='password' placeholder='Passwort' required></td>
                    </tr>
                </table>
                <br>
                <input type='submit' name='submit' value='Hinzufügen'>
                <input type='reset' value='Zurücksetzen'>
            </form>
        </div>
        ";
    } else {
        $schule_id = $_POST['schule_id'];
        $stufe = $_POST['stufe'];
        $vorname = $_POST['vorname'];
        $nachname = $_POST['nachname'];
        $email = $_POST['email'];
        $password = $_POST['password'];

        $sql_insert = "INSERT INTO `schueler`(`schule_id`, `stufe`, `vorname`, `nachname`, `email`, `password`) VALUES ($schule_id,'$stufe', '$vorname', '$nachname', '$email', '$password')";

        if ($conn->query($sql_insert) === TRUE) {
            echo "Der Schüler wurde hinzugefügt";
        } else {
            echo "Fehler beim Hinzufügen des Schülers: " . $conn->error;
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