<?php
session_start();

$conn = new mysqli('localhost', 'root', '', 'educonnect');
mysqli_set_charset($conn, "utf8mb4");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$rang = $_SESSION['rang'];

// Team-ID aus der URL abrufen
$team_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Überprüfen, ob der Benutzer im Team ist
$membership_query = "
    SELECT user_id, team_id, rang 
    FROM beitritt 
    WHERE team_id = $team_id 
    AND user_id = $user_id 
    AND rang = '$rang'
    UNION
    SELECT lehrer_id AS user_id, team_id, 'lehrer' AS rang 
    FROM teams 
    WHERE team_id = $team_id 
    AND lehrer_id = $user_id";
$membership_result = $conn->query($membership_query);

if ($membership_result->num_rows == 0) {
    header("Location: chat_overview_2.php");
    exit();
}

if($team_id){
    $chat_team_gelesen_query = "
    DELETE FROM chat_team_gelesen_id 
    WHERE empfaenger_id = $user_id 
    AND chat_team_id IN (
        SELECT ct.chat_team_id 
        FROM chat_team ct 
        WHERE ct.team_id = $team_id
    ) 
    AND rang = '$rang'";
    $conn->query($chat_team_gelesen_query);
}

// Team-Informationen abrufen
$team_query = "SELECT * FROM teams WHERE team_id = $team_id";
$team_result = $conn->query($team_query);
$team = $team_result->fetch_assoc();

// Überprüfen, ob das Team existiert
if (!$team) {
    die("Team nicht gefunden.");
}

$gelese_delete_query = "DELETE FROM chat_team_gelesen_id WHERE chat_team_id = $team_id AND empfaenger_id = $user_id AND rang = '$rang'";
$conn->query($gelese_delete_query);

// Chatnachrichten für das Team abrufen
$chat_messages_query = "
    SELECT ct.*, 
           CASE 
               WHEN ct.rang = 'lehrer' THEN 
                   (SELECT CONCAT(vorname, ' ', nachname) FROM lehrer WHERE lehrer_id = ct.sender_id)
               WHEN ct.rang = 'schueler' THEN 
                   (SELECT CONCAT(vorname, ' ', nachname) FROM schueler WHERE schueler_id = ct.sender_id)
           END AS sender_name
    FROM chat_team ct 
    WHERE ct.team_id = $team_id 
    ORDER BY ct.gesendet_am ASC";
$chat_messages_result = $conn->query($chat_messages_query);

// Nachricht senden
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message'])) {
    $message = $_POST['message'];
    $file_path = null;

    // Datei-Upload verarbeiten
    if (isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'file_team/'; // Hauptverzeichnis für Dateien
        $team_dir = $upload_dir . $team_id . '/'; // Neuer Ordner für die team_id
        $file_name = basename($_FILES['file']['name']);
        $target_file = $team_dir . uniqid() . '_' . $file_name; // Zielpfad für die Datei

        // Verzeichnis für die team_id erstellen, falls es nicht existiert
        if (!is_dir($team_dir)) {
            mkdir($team_dir, 0755, true); // Erstelle das Verzeichnis mit den entsprechenden Berechtigungen
        }

        // Datei verschieben
        if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
            $file_path = $target_file; // Dateipfad speichern
        } else {
            echo "Fehler beim Hochladen der Datei.";
        }
    }

    // Nachricht in die Datenbank einfügen
    $insert_query = "INSERT INTO chat_team (sender_id, rang, team_id, gesendet_am, nachricht, file) VALUES ($user_id, '$rang', $team_id, NOW(), '$message', '$file_path')";
    $conn->query($insert_query);
    header("Location: team.php?id=$team_id"); // Umleitung, um den Chatverlauf zu aktualisieren
    exit();
}

// Paginierung für Dateien
$limit = 10; // Anzahl der Dateien pro Seite
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$file_query = "SELECT * FROM chat_team WHERE team_id = $team_id AND file IS NOT NULL ORDER BY gesendet_am DESC LIMIT $limit OFFSET $offset";
$file_result = $conn->query($file_query);

// Gesamtanzahl der Dateien abrufen
$total_files_query = "SELECT COUNT(*) as total FROM chat_team WHERE team_id = $team_id AND file IS NOT NULL";
$total_files_result = $conn->query($total_files_query);
$total_files = $total_files_result->fetch_assoc()['total'];
$total_pages = ceil($total_files / $limit);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($team['team_name']); ?> - Team Chat</title>
    <link rel="stylesheet" href="styles_chat.css">
</head>
<body>
<?php include 'navigation.php'; ?>
    <div class="container">
        <h2><?php echo htmlspecialchars($team['team_name']); ?></h2>
        <p><?php echo htmlspecialchars($team['beschreibung']); ?></p>

        <div class="chat-window">
            <div id="chat-messages">
                <?php if ($chat_messages_result): ?>
                    <?php while ($chat = $chat_messages_result->fetch_assoc()): 
                        // Überprüfen, ob die Nachricht gelesen wurde
                        $chat_team_id = $chat['chat_team_id'];

                        // Abfrage, um die Benutzer zu erhalten, die die Nachricht gelesen haben
                        $read_users_query = "
                            SELECT b.user_id, b.rang, 
                                CASE 
                                    WHEN b.rang = 'lehrer' THEN 
                                        CONCAT(l.vorname, ' ', l.nachname)
                                    WHEN b.rang = 'schueler' THEN 
                                        CONCAT(s.vorname, ' ', s.nachname)
                                END AS full_name
                            FROM beitritt b 
                            LEFT JOIN lehrer l ON b.user_id = l.lehrer_id AND b.rang = 'lehrer'
                            LEFT JOIN schueler s ON b.user_id = s.schueler_id AND b.rang = 'schueler'
                            JOIN chat_team_gelesen_id g ON b.user_id = g.empfaenger_id 
                            WHERE g.chat_team_id = $chat_team_id 
                            AND b.team_id = $team_id

                            UNION

                            SELECT t.lehrer_id AS user_id, 'lehrer' AS rang, 
                                CONCAT(l.vorname, ' ', l.nachname) AS full_name
                            FROM teams t
                            JOIN lehrer l ON t.lehrer_id = l.lehrer_id
                            WHERE t.team_id = $team_id 
                            AND t.lehrer_id IN (
                                SELECT g.empfaenger_id 
                                FROM chat_team_gelesen_id g 
                                WHERE g.chat_team_id = $chat_team_id
                            )
                        ";

                        $read_users_result = $conn->query($read_users_query);
                        $read_users = [];
                        while ($read_user = $read_users_result->fetch_assoc()) {
                            $read_users[] = $read_user; // Benutzer-ID, Rang und Name speichern
                        }

                        // Abfrage, um alle Benutzer im Team zu erhalten, einschließlich des zuständigen Lehrers
                        $all_users_query = "
                            SELECT b.user_id, b.rang, 
                                CASE 
                                    WHEN b.rang = 'lehrer' THEN 
                                        CONCAT(l.vorname, ' ', l.nachname)
                                    WHEN b.rang = 'schueler' THEN 
                                        CONCAT(s.vorname, ' ', s.nachname)
                                END AS full_name
                            FROM beitritt b 
                            LEFT JOIN lehrer l ON b.user_id = l.lehrer_id AND b.rang = 'lehrer'
                            LEFT JOIN schueler s ON b.user_id = s.schueler_id AND b.rang = 'schueler'
                            WHERE b.team_id = $team_id

                            UNION

                            SELECT t.lehrer_id AS user_id, 'lehrer' AS rang, 
                                CONCAT(l.vorname, ' ', l.nachname) AS full_name
                            FROM teams t
                            JOIN lehrer l ON t.lehrer_id = l.lehrer_id
                            WHERE t.team_id = $team_id
                        ";

                        $all_users_result = $conn->query($all_users_query);
                        $all_users = [];
                        while ($all_user = $all_users_result->fetch_assoc()) {
                            $all_users[] = $all_user; // Alle Benutzer im Team speichern
                        }

                        // Benutzer, die die Nachricht nicht gelesen haben
                        $not_read_users = array_filter($all_users, function($user) use ($read_users) {
                            foreach ($read_users as $read_user) {
                                if ($user['user_id'] == $read_user['user_id']) {
                                    return false; // Benutzer hat die Nachricht gelesen
                                }
                            }
                            return true; // Benutzer hat die Nachricht nicht gelesen
                        });

                        // Benutzerinformationen für den Klick-Effekt vorbereiten
                        $hover_info = '<div class="hover-table" style="display: none;">';
                        $hover_info .= '<table style="border-collapse: collapse; width: 100%;">';
                        $hover_info .= '<tr><th style="border: 1px solid #ccc; padding: 5px;">Gelesen</th></tr>';
                        foreach ($not_read_users as $user) {
                            $hover_info .= '<tr><td style="border: 1px solid #ccc; padding: 5px;">' . htmlspecialchars($user['full_name']) . '</td></tr>';
                        }

                        $hover_info .= '<tr><th style="border: 1px solid #ccc; padding: 5px;">Nicht gelesen</th></tr>';
                        foreach ($read_users as $user) {
                            $hover_info .= '<tr><td style="border: 1px solid #ccc; padding: 5px;">' . htmlspecialchars($user['full_name']) . '</td></tr>';
                        }
                        $hover_info .= '</table></div>';
                        ?>
                        <div class="message <?php echo ($chat['sender_id'] == $user_id) ? 'right' : 'left'; ?>">
                            <small><strong><?php echo htmlspecialchars($chat['sender_name']); ?></strong></small><br>
                            <?php echo "<p>" . nl2br(htmlspecialchars($chat['nachricht'])) . "</p>"; ?>
                            <?php if ($chat['file']): ?>
                                <div>
                                    <a href="<?php echo htmlspecialchars($chat['file']); ?>" download>Datei herunterladen</a>
                                </div>
                            <?php endif; ?>
                            <?php
                                $heute = date('Y-m-d');
                                $nachricht_datum = date('Y-m-d', strtotime($chat['gesendet_am']));
                                $datum_anzeige = ($nachricht_datum == $heute) ? 'Heute' : date('l, j. F Y', strtotime($chat['gesendet_am']));
                            ?>
                            <small class="timestamp" onclick="toggleHoverInfo(this)">
                                <?php echo $datum_anzeige; ?> - <?php echo date('H:i', strtotime($chat['gesendet_am'])); ?>
                            </small>
                            <?php echo $hover_info; // Die Hover-Informationen hier einfügen ?>
                        </div>
                    <?php endwhile; ?>

                    <script>
                    function toggleHoverInfo(element) {
                        const hoverTable = element.nextElementSibling; // Nächstes Element (die Tabelle)
                        if (hoverTable) {
                            // Toggle der Sichtbarkeit
                            if (hoverTable.style.display === 'none') {
                                hoverTable.style.display = 'block'; // Tabelle anzeigen
                            } else {
                                hoverTable.style.display = 'none'; // Tabelle ausblenden
                            }
                        }
                    }
                    </script>
                <?php else: ?>
                    <p>Keine Nachrichten vorhanden.</p>
                <?php endif; ?>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <textarea name="message" placeholder="Nachricht eingeben..." required></textarea>
                <input type="file" name="file" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt,.zip,.rar,.7z,.tar,.gz,.mp3,.mp4,.avi,.mov,.wmv,.flv,.webm,.ppt,.pptx,.xls,.xlsx,.csv,.odt,.ods,.odp,.odg,.ott,.ots,.otp,.otg,.pdf,.txt,.html,.css,.js,.php,.cpp,.java,.py,.sql,.xml,.json,.svg,.mp3,.mp4,.ogg,.wav,.flac,.zip,.rar,.7z,.tar,.gz,.xls,.xlsx,.ppt,.pptx,.odt,.ods,.odp,.odg,.ott,.ots,.otp,.otg,.pdf,.txt,.html,.css,.js,.php,.cpp,.java,.py,.sql,.xml,.json,.svg,.mp3,.mp4,.ogg,.wav,.flac">
                <button type="submit">Senden</button>
            </form>
        </div>
 
        <div class="file-window">
            <h3>Dateien</h3>
            <?php
            if ($file_result->num_rows > 0) {
                echo "<table>
                        <tr>
                            <th>Datei</th>
                            <th>Herunterladen</th>
                            <th>Gesendet am</th>
                            <th>Sender</th>
                            <th>Löschen</th>
                        </tr>";

                while ($file = $file_result->fetch_assoc()) {
                    if ($file['file'] == null) {
                        continue;
                    } else {
                        echo "<tr>
                                <td>" . htmlspecialchars(basename($file['file'])) . "</td>
                                <td><a href='" . htmlspecialchars($file['file']) . "' download>Herunterladen</a></td>
                                <td>" . date('d.m.Y H:i', strtotime($file['gesendet_am'])) . "</td>";
                        if ($file['rang'] == 'lehrer') {
                            $sql_lehrer = "SELECT * FROM lehrer WHERE lehrer_id = " . $file['sender_id'];
                            $result_lehrer = $conn->query($sql_lehrer);
                            $lehrer = $result_lehrer->fetch_assoc();
                            echo "<td>" . htmlspecialchars($lehrer['vorname']) . " " . htmlspecialchars($lehrer['nachname']) . "</td>
                                </tr>";
                        } elseif ($file['rang'] == 'schueler') {
                            $sql_schueler = "SELECT * FROM schueler WHERE schueler_id = " . $file['sender_id'];
                            $result_schueler = $conn->query($sql_schueler);
                            $schueler = $result_schueler->fetch_assoc();
                            echo "<td>" . htmlspecialchars($schueler['vorname']) . " " . htmlspecialchars($schueler['nachname']) . "</td>
                                </tr>";
                        } else {
                            echo "<td>Unbekannt</td></tr>";
                        }
                        if($file['sender_id'] == $user_id || $file['rang'] == $rang){
                            echo "<td>
                                
                            </td></tr>";
                        } else {
                            echo "<td></td></tr>";
                        }
                    }
                }
                echo "</table>";
            } else {
                echo '<p>Keine Dateien vorhanden.</p>';
            }
            ?>

            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="team.php?id=<?php echo $team_id; ?>&page=<?php echo $page - 1; ?>">Vorherige</a>
                <?php endif; ?>
                <?php if ($page < $total_pages): ?>
                    <a href="team.php?id=<?php echo $team_id; ?>&page=<?php echo $page + 1; ?>">Nächste</a>
                <?php endif; ?>
            </div>
        </div>

    <script>
        // Automatisch zum Ende des Chatverlaufs scrollen
        var chatMessages = document.getElementById('chat-messages');
        chatMessages.scrollTop = chatMessages.scrollHeight;
    </script>

</body>
</html>

<?php
$conn->close();
?>