<?php
session_start();


$_SESSION['letzte_seite'] = 'mainpage.php';

if (!in_array($_SESSION['rang'], ['admin', 'schulleiter', 'lehrer'])) {
    header("Location: index.php");
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'educonnect');
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($_POST['action'] == 'create') {
        $team_name = $_POST['team_name'];
        $beschreibung = $_POST['beschreibung'];
        $hex_wert = $_POST['hex_wert'];
        $stufe_id = $_POST['stufe_id'];
        $fach_id = $_POST['fach_id'];
        $lehrer_id = $_SESSION['user_id'];
        $schule_id = $_SESSION['schule_id'];

        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $hex_wert)) {
            echo "Ungültiger HEX-Wert!";
        } else {
            $stmt = $conn->prepare("INSERT INTO teams (team_name, beschreibung, hex_wert, stufe_id, lehrer_id, schule_id, fach_id) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssiiii", $team_name, $beschreibung, $hex_wert, $stufe_id, $lehrer_id, $schule_id, $fach_id);
            $stmt->execute();
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    } elseif ($_POST['action'] == 'select_team') {
        $_SESSION['team_id'] = $_POST['team_id'];
        $_SESSION['fach_id'] = $_POST['fach_id'];
        header("Location: lehrer_stunden.php");
        exit();
    }
}

$teams_result = $conn->query("SELECT teams.*, stufe.stufe_name, faecher.fach_name, code_id FROM teams 
                              JOIN stufe ON teams.stufe_id = stufe.stufe_id 
                              JOIN faecher ON teams.fach_id = faecher.fach_id
                              WHERE lehrer_id = " . $_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Team Verwaltung</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">

       
</head>

<body>
<?php include 'navigation.php'; ?>
<div class="container_lehrerinsert">
    <div class="form-container_lehrerinsert">
        <h2>Neues Team</h2>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            
            <label for="team_name">Team-Name:</label>
            <input type="text" id="team_name" name="team_name" placeholder="Team-Name" required><br>

            <label for="beschreibung">Beschreibung:</label>
            <textarea id="beschreibung" name="beschreibung" placeholder="Beschreibung"></textarea><br>

            <label for="hex_wert">Farbe wählen:</label>
            <input type="color" id="hex_wert" name="hex_wert" value="#3498db" required>

            <label for="stufe_id">Stufe:</label>
            <select id="stufe_id" name="stufe_id" required>
                <?php
                $stufen_result = $conn->query("SELECT stufe_id, stufe_name FROM stufe");
                while ($row = $stufen_result->fetch_assoc()) {
                    echo "<option value='{$row['stufe_id']}'>{$row['stufe_name']}</option>";
                }
                ?>
            </select><br>

            <label for="fach_id">Fach:</label>
            <select id="fach_id" name="fach_id" required>
                <?php
                $faecher_result = $conn->query("SELECT fach_id, fach_name FROM faecher");
                while ($row = $faecher_result->fetch_assoc()) {
                    echo "<option value='{$row['fach_id']}'>{$row['fach_name']}</option>";
                }
                ?>
            </select><br>

            <button type="submit">Erstellen</button>
        </form>
    </div>

    <div class="teams-container_lehrerinsert">
        <h2>Deine Teams</h2>
        <?php
        while ($row = $teams_result->fetch_assoc()) {
            echo "<div class='team_lehrerinsert' style='background-color: {$row['hex_wert']};'>
                    <form method='POST' style='margin: 0;'>
                        <input type='hidden' name='action' value='select_team'>
                        <input type='hidden' name='team_id' value='{$row['team_id']}'>
                        <input type='hidden' name='fach_id' value='{$row['fach_id']}'>
                        <button type='submit' style='width: 100%; background: none; border: none; color: white; padding: 10px; font-size: 16px; cursor: pointer;'>
                            <div class='team-content_lehrerinsert'>
                                <strong>{$row['team_name']}</strong><br>
                                {$row['beschreibung']} (Stufe: {$row['stufe_name']}, Fach: {$row['fach_name']})
                                <div class = ''>{$row['code_id']}</div>
                            </div>
                            
                        </button>
                    </form>
                  </div>";
        }
        ?>
    </div>
</div>

</body>
</html>

<?php $conn->close(); ?>
