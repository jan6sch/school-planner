<!DOCTYPE html>
    <html lang='de'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Login</title>
        <link rel="stylesheet" href="educonnect.css">
        
    </head>
<?php
session_start(); 

if (!isset($_POST['submit'])) {
    echo "
    <div class='Login'> 
        <form action='' method='POST'>
            <table>
                <tr>
                    <h1>Anmeldung</h1>
                </tr>
                <tr>
                    <td>E-Mail:</td>
                    <td><input type='email' name='email' placeholder='E-Mail' required></td>
                </tr>
                <tr>
                    <td>Passwort:</td>
                    <td><input type='password' name='password' placeholder='Passwort' required></td>
                </tr>
                <tr>
                    <td>
                        <label>
                            Ich bin kein Roboter
                            <input type='checkbox' required class='large-checkbox'>
                        </label>
                    </td>
                    <td></td>
                </tr>
            </table>
            <br>
            <input type='submit' name='submit' value='Anmelden'>
            <input type='reset' name='reset' value='Zurücksetzen'>
        </form>
    </div>
    ";
} else {
   
    $email = $_POST['email'];
    $password1 = $_POST['password'];

    
    $conn = new mysqli("localhost", "root", "", "educonnect");

    if ($conn->connect_error) {
        echo "Fehler bei der Verbindung zur Datenbank: " . $conn->connect_error;
        exit();
    } else {
       
        $sql_check_email = "SELECT `schueler_id`, `vorname`, `nachname`, `email`, `password`, `schule_id`, `stufe` FROM `schueler` WHERE `email` = '$email'";
        $result = $conn->query($sql_check_email);

      
        $sql_check_email1 = "SELECT `lehrer_id`, `vorname`, `nachname`, `email`, `password`, `rang`, `schule_id` FROM `lehrer` WHERE `email` = '$email'";
        $result1 = $conn->query($sql_check_email1);

        if ($result && $result->num_rows > 0) {
       
            $row = $result->fetch_assoc();
            if ($email===$row['email'] && $password1 === $row['password']) {
              
                $_SESSION['user_id'] = $row['schueler_id'];
                $_SESSION['vorname'] = $row['vorname'];
                $_SESSION['nachname'] = $row['nachname'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['rang'] ='schueler';
                $_SESSION['rang1'] = $row['rang'];
                $_SESSION['schule_id'] = $row['schule_id'];
                $_SESSION['stufe_id'] = $row['stufe'];

                header("Location: mainpage.php");
                exit();
            } else {
                echo "Falsches Passwort für Schüler!";
            }
        } elseif ($result1 && $result1->num_rows > 0) {
            
            $row1 = $result1->fetch_assoc();
            if ($email===$row1['email'] && $password1 === $row1['password']) {
                
                $_SESSION['user_id'] = $row1['lehrer_id'];
                $_SESSION['vorname'] = $row1['vorname'];
                $_SESSION['nachname'] = $row1['nachname'];
                $_SESSION['email'] = $row1['email'];
                $_SESSION['rang'] = 'lehrer';
                $_SESSION['rang1'] = $row1['rang'];
                $_SESSION['schule_id'] = $row1['schule_id'];

                header("Location: mainpage.php");
                exit();
            } else {
                echo "Falsches Passwort für Lehrer!";
            }
        } else {
            echo "Die E-Mail-Adresse ist nicht registriert.";
        }
    }
    $conn->close();
}
?>
