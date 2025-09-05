<?php
session_start();

// Verbindung zur Datenbank herstellen
$conn = mysqli_connect('localhost', 'root', '', 'educonnect');

if (!$conn) {
    die("Verbindung zur Datenbank fehlgeschlagen: " . mysqli_connect_error());
}

$rang = $_SESSION['rang']; 
if($rang != 'lehrer') {
    header('Location: mainpage.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lehrer_id = $_SESSION['user_id'];
    $datum = $_POST['datum'];
    $grund = $_POST['grund'];
    $beginn = $_POST['beginn'];
    $ende = $_POST['ende'];

    $sql = "INSERT INTO lehrer_fehlzeiten (lehrer_id, datum, grund, beginn, ende) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "issss", $lehrer_id, $datum, $grund, $beginn, $ende);

    if (mysqli_stmt_execute($stmt)) {
        echo "Krankmeldung erfolgreich eingereicht.";
    } else {
        echo "Fehler: " . mysqli_error($conn);
    }

    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Krankmeldung für Lehrer</title>
    <link rel="stylesheet" href="styles_chat.css">
    <link rel="shortcut icon" href="picture.ico" type="image/x-icon">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f1f2f6;
            margin-top: 100px;
        }
    </style>
</head>
<body>
    <h1>Krankmeldung</h1>
    <form method="post" action="">
        <label for="datum">Datum:</label>
        <input type="date" name="datum" required><br>

        <label for="grund">Grund:</label>
        <textarea name="grund" required></textarea><br>

        <label for="beginn">Beginn:</label>
        <input type="time" name="beginn" required><br>

        <label for="ende">Ende:</label>
        <input type="time" name="ende" required><br>

        <input type="submit" value="Krankmeldung einreichen">
    </form>
</body>
<?php include 'navigation.php'; ?>
</html>