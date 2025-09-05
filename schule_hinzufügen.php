<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schule Hinzufügen</title>
    <link rel="stylesheet" href="educonnect.css">
    <link rel="shortcut icon" href="picture.ico" type="image/x-icon">
</head>
<body>

<?php 
session_start();

if (!isset($_SESSION['rang1']) || $_SESSION['rang1'] < 6 ) {
    header("Location: index.php");
    exit();
}

$message = ""; 

if (isset($_POST['submit'])) {
    $schule_name = $_POST['schule_name']; 
    $bundesland = $_POST['bundesland'];
    $land = $_POST['land'];
    $hex_wert = $_POST['hex_wert'];

    
    $conn = mysqli_connect("localhost", "root", "", "educonnect");
    mysqli_set_charset($conn, "utf8mb4");
    if (!isset($_SESSION['rang1']) || $_SESSION['rang1'] < 7) {
        header("Location: index.php");
        exit();
    }

    if (!$conn) {
        die("Verbindung zur Datenbank fehlgeschlagen: " . mysqli_connect_error());
    }

    
    $sql = "INSERT INTO schulen (schule_name, bundesland, land, hex_wert) VALUES ('$schule_name', '$bundesland', '$land', '$hex_wert')";

    if (mysqli_query($conn, $sql)) {
        $message = "Schule erfolgreich hinzugefügt.";  
    } else {
        $message = "Fehler: " . mysqli_error($conn);  
    }

    mysqli_close($conn);
}
?>

<h1>Schule Hinzufügen</h1>
<form action="" method="POST">
    <table>
        <tr>
            <td>Schulname:</td>
            <td><input type="text" name="schule_name" required></td>
        </tr>
        <tr>
<td>Bundesland:</td>
<td>
<select name="bundesland" required>
            <option value="" disabled selected>Bitte wählen</option>
            <optgroup label="Deutschland">
            <option value="Baden-Württemberg">Baden-Württemberg</option>
            <option value="Bayern">Bayern</option>
            <option value="Berlin">Berlin</option>
            <option value="Brandenburg">Brandenburg</option>
            <option value="Bremen">Bremen</option>
            <option value="Hamburg">Hamburg</option>
            <option value="Hessen">Hessen</option>
            <option value="Mecklenburg-Vorpommern">Mecklenburg-Vorpommern</option>
            <option value="Niedersachsen">Niedersachsen</option>
            <option value="Nordrhein-Westfalen">Nordrhein-Westfalen</option>
            <option value="Rheinland-Pfalz">Rheinland-Pfalz</option>
            <option value="Saarland">Saarland</option>
            <option value="Sachsen">Sachsen</option>
            <option value="Sachsen-Anhalt">Sachsen-Anhalt</option>
            <option value="Schleswig-Holstein">Schleswig-Holstein</option>
            <option value="Thüringen">Thüringen</option>
            </optgroup>
            <optgroup label="Österreich">
            <option value="Burgenland">Burgenland</option>
            <option value="Kärnten">Kärnten</option>
            <option value="Niederösterreich">Niederösterreich</option>
            <option value="Oberösterreich">Oberösterreich</option>
            <option value="Salzburg">Salzburg</option>
            <option value="Steiermark">Steiermark</option>
            <option value="Tirol">Tirol</option>
            <option value="Vorarlberg">Vorarlberg</option>
            <option value="Wien">Wien</option>
            </optgroup>
            <optgroup label="Schweiz">
            <option value="Aargau">Aargau</option>
            <option value="Appenzell Innerrhoden">Appenzell Innerrhoden</option>
            <option value="Appenzell Ausserrhoden">Appenzell Ausserrhoden</option>
            <option value="Basel-Landschaft">Basel-Landschaft</option>
            <option value="Basel-Stadt">Basel-Stadt</option>
            <option value="Freiburg">Freiburg</option>
            <option value="Genf">Genf</option>
            <option value="Glarus">Glarus</option>
            <option value="Jura">Jura</option>
            <option value="Luzern">Luzern</option>
            <option value="Neuenburg">Neuenburg</option>
            <option value="Nidwalden">Nidwalden</option>
            <option value="Obwalden">Obwalden</option>
            <option value="Schaffhausen">Schaffhausen</option>
            <option value="Solothurn">Solothurn</option>
            <option value="Schwyz">Schwyz</option>
            <option value="Thurgau">Thurgau</option>
            <option value="Uri">Uri</option>
            <option value="Wallis">Wallis</option>
            <option value="Zug">Zug</option>
            <option value="Zürich">Zürich</option>
            </optgroup>
</select>
</td>
</tr>
        <tr>
            <td>Land:</td>
            <td>
                <select name="land" required>
                    <option value="" disabled selected>Bitte wählen</option>
                    <option value="Deutschland">Deutschland</option>
                    <option value="Österreich">Österreich</option>
                    <option value="Schweiz">Schweiz</option>
                </select>
            </td>
        </tr>
        <tr>
            <td>Schulfarbe:</td>
            <td><input type="color" name="hex_wert" value="#FFFFFF" required></td>
        </tr>
        <tr>
            <td><input type="submit" name="submit" value="Hinzufügen"></td>
        </tr>
    </table>
</form>

<?php if ($message): ?>
    <p><?php echo $message; ?></p>
<?php endif; ?>

</body>
<?php 
        if($_SESSION['rang1'] === '7'){
            
        }else{
            include 'navigation.php';
        }
    ?>
</html>