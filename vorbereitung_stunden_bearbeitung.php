<?php
session_start();

// Verbindung zur Datenbank herstellen
$conn = new mysqli('localhost', 'root', '', 'educonnect');
mysqli_set_charset($conn, "utf8mb4");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Überprüfen, ob der Lehrer angemeldet ist
if ($_SESSION['rang'] !== 'lehrer') {
    die("Zugriff verweigert.");
}

// Vorbereitungen bearbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_preparation'])) {
    $vorbereitung_id = $_POST['vorbereitung_id'];
    $title = $_POST['title'];
    $beschreibung = $_POST['beschreibung'];
    $zeitpunkt = $_POST['zeitpunkt'];

    $sql = "UPDATE vorbereitung_stunden SET title = '$title', beschreibung = '$beschreibung', zeitpunkt = '$zeitpunkt' WHERE vorbereitung_id = '$vorbereitung_id'";
    $conn->query($sql);
    header("Location: vorbereitung_stunden.php"); // Zurück zur Hauptseite
    exit();
}

// Vorbereitungen abrufen
if (isset($_GET['id'])) {
    $vorbereitung_id = $_GET['id'];
    $sql = "SELECT * FROM vorbereitung_stunden WHERE vorbereitung_id = '$vorbereitung_id'";
    $result = $conn->query($sql);
    $vorbereitung = $result->fetch_assoc();

    if (!$vorbereitung) {
        die("Vorbereitung nicht gefunden.");
    }
} else {
    die("Keine ID angegeben.");
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Vorbereitung bearbeiten</title>
    <link rel="stylesheet" href="VS.css">
</head>
<body>
    <h1>Vorbereitung bearbeiten</h1>
    <form method="POST">
        <table>
            <tr>
                <th><label for="title">Titel:</label></th>
                <td><input type="text" name="title" value="<?php echo htmlspecialchars($vorbereitung['title']); ?>" required></td>
            </tr>
            <tr>
                <th><label for="beschreibung">Beschreibung:</label></th>
                <td><textarea name="beschreibung" required><?php echo htmlspecialchars($vorbereitung['beschreibung']); ?></textarea></td>
            </tr>
            <tr>
                <th><label for="zeitpunkt">Zeitpunkt:</label></th>
                <td><input type="date" name="zeitpunkt" value="<?php echo htmlspecialchars($vorbereitung['zeitpunkt']); ?>" required></td>
            </tr>
        </table>
        <input type="hidden" name="vorbereitung_id" value="<?php echo $vorbereitung['vorbereitung_id']; ?>">
        <button type="submit" name="edit_preparation">Speichern</button>
    </form>
</body>
</html>