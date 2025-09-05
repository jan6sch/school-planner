<?php
SESSION_start();
$conn = new Mysqli('localhost', 'root', '', 'educonnect');
mysqli_set_charset($conn, "utf8mb4");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$rang = $_SESSION['rang'];
$user_id = $_SESSION['user_id'];
$schule_id = $_SESSION['schule_id'];

if ($rang === 'admin' || $rang === 'schulleiter') {
    $id_feld = "lehrer_id";
    $rang_tabelle = "lehrer";
} else {
    $id_feld = $rang . "_id";
    $rang_tabelle = $rang;
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['rang'])) {
    header("Location: index.php");
    exit();
}
// Benutzerinformationen abrufen
$query = "SELECT vorname, nachname, email" . ($rang_tabelle !== 'lehrer' ? ", stufe" : "") . " FROM $rang_tabelle WHERE $id_feld = '$user_id'";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

// Schuldaten abrufen
$query_schule = "SELECT schule_name, bundesland, land FROM schulen WHERE schule_id = '$schule_id'";
$result_schule = mysqli_query($conn, $query_schule);
$schule = mysqli_fetch_assoc($result_schule);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilseite</title>
    <link rel="stylesheet" href="profil.css"></link>
</head>
<body>
    <div class="profil-container">
        <div class="kuerzel"><?php echo strtoupper(substr($user['vorname'], 0, 1) . substr($user['nachname'], 0, 1)); ?></div>
        <div class="info"><strong>Name:</strong> <?php echo $user['vorname'] . " " . $user['nachname']; ?></div>
        <div class="info"><strong>Email:</strong> <?php echo $user['email']; ?></div>
        <div class="info"><strong>Schule:</strong> <?php echo $schule['schule_name']; ?></div>
        <?php if ($rang_tabelle !== 'lehrer'): ?>
            <div class="info"><strong>Stufe:</strong> <?php echo $user['stufe'] ? $user['stufe'] : 'N/A'; ?></div>
        <?php endif; ?>
        <div class="info"><strong>Land:</strong> <?php echo $schule['land']; ?></div>
        <div class="info"><strong>Bundesland:</strong> <?php echo $schule['bundesland']; ?></div>
        <br><br>
        <?php
        if($_SESSION['rang1'] === '6'){
            echo"
            <a href='schule_hinzufügen.php'>Schulen Hinzufügen</a>
            <a href='schueler_hinzufügen.php'>Schüler Hinzufügen</a>
            ";
        }
        ?>
    </div>

    <form action="logout.php" method="post">
                <button type="submit" class="logout-button">Logout</button>
            </form>

    <?php include 'navigation.php'; ?>
</body>
</html>