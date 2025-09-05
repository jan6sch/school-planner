<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Noten</title>
    <link rel="stylesheet" href="educonnect.css">
    <link rel="shortcut icon" href="picture.ico" type="image/x-icon">
</head>
<body>

<div class="nav-box">
    <a href="noten.php" class="active">Noten eintragen</a>
    <a href="noten_anzeigelehrer.php">Noten Übersicht Lehrer</a>
</div>
<?php
session_start(); 

if (!isset($_SESSION['rang1']) || $_SESSION['rang1'] < 1) {
    header("Location: index.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "educonnect");
mysqli_set_charset($conn, "utf8mb4");

if (!$conn) {
    die("Verbindung zur Datenbank fehlgeschlagen: " . mysqli_connect_error());
}

if (!isset($_SESSION['schule_id'])) {
    die("Schule nicht ausgewählt. Bitte melden Sie sich an.");
}

$sql_schueler = "SELECT schueler_id, vorname, nachname FROM schueler WHERE schule_id = '{$_SESSION['schule_id']}'";
$res_schueler = mysqli_query($conn, $sql_schueler);

$sql_faecher = "SELECT fach_id, fach_name FROM faecher WHERE fach_name != 'Freistunde'";
$res_faecher = mysqli_query($conn, $sql_faecher);

$sql_pruefungsarten = "SELECT pruefungs_id, art FROM pruefungsart";
$res_pruefungsarten = mysqli_query($conn, $sql_pruefungsarten);

echo "
<h1>Noten eingeben</h1>
<form action='' method='POST'>
    <table>
        <tr>
            <td>Schüler Auswahl</td>
            <td>
                <select name='Schüler' id='schueler' required>
                    <option value='' disabled selected> Bitte wählen</option>";
                    while ($row = mysqli_fetch_assoc($res_schueler)) {
                        echo "<option value='" . $row['schueler_id'] . "'>" . $row['vorname'] . " " . $row['nachname'] . "</option>";
                    }
                echo "</select>
            </td>
        </tr>
        <tr>
            <td>Fach</td>
            <td>
                <select name='Fach' required>
                    <option value='' disabled selected> Bitte wählen </option>";
                    while ($row = mysqli_fetch_assoc($res_faecher)) {
                        echo "<option value='" . $row['fach_id'] . "'>" . $row['fach_name'] . "</option>";
                    }
                echo "</select>
            </td>
        </tr>
        <tr>
            <td>Überprüfungsart</td>
            <td>
                <select name='art' required>
                    <option value='' disabled selected> Bitte wählen</option>";
                    if (mysqli_num_rows($res_pruefungsarten) > 0) {
                        while ($row = mysqli_fetch_assoc($res_pruefungsarten)) {
                            echo "<option value='" . $row['pruefungs_id'] . "'>" . $row['art'] . "</option>";
                        }
                    } else {
                        echo "<option value=''>Keine Prüfungsarten gefunden</option>";
                    }
                echo "</select>
            </td>
        </tr>
        <tr>
            <td>Noten</td>
            <td><input type='number' name='Note' placeholder='0-15' max='15' min='0' required/></td>
        </tr>
        <tr>
            <td>Gewichtung (in %)</td>
            <td><input type='number' name='gewichtung' placeholder='0-100%' max='100' min='0' required/></td>
        </tr>
        <tr>
            <td>Bemerkung</td>
            <td><textarea name='Bemerkung' rows='4' placeholder='Bemerkung'></textarea></td>
        </tr>
        <tr>
            <td class='notenseite'><input type='submit' value='Hinzufügen' name='submit_noten'/></td>
            <td class= 'notenseite'><input type='reset' value='Zurücksetzen' name='clear'/></td>
        </tr>
    </table>
</form>
";

if (isset($_POST['submit_noten'])) {
    $schueler_id = $_POST['Schüler'];
    $fach_id = $_POST['Fach'];
    $pruefungs_id = $_POST['art'];
    $note = $_POST['Note'];
    $gewichtung = $_POST['gewichtung'];
    $bemerkung = $_POST['Bemerkung'];

     
    $lehrer_id = $_SESSION['user_id'];  

    $sql_noten = "INSERT INTO noten (schueler_id, fach_id, pruefungs_id, note, gewichtung, bemerkung, lehrer_id) 
                  VALUES ('$schueler_id', '$fach_id', '$pruefungs_id', '$note', '$gewichtung', '$bemerkung', '$lehrer_id')";
    
    if (mysqli_query($conn, $sql_noten)) {
        echo "Noten erfolgreich hinzugefügt.";
    } else {
        echo "Fehler: " . mysqli_error($conn);
    }
}

mysqli_close($conn);  
?>
</body><?php include 'navigation.php'; ?>
</html>