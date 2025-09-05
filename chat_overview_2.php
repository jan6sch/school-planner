<?php
session_start();

$conn = new mysqli('localhost', 'root', '', 'educonnect');
mysqli_set_charset($conn, "utf8mb4");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$wochentag = date('w');
$aktuelles_datum = date('Y-m-d');

// Angenommene Session-Variablen
$user_id = $_SESSION['user_id'];
$rang = $_SESSION['rang'];

// Teams abrufen, einschließlich des hex_werts
$teams_query = "SELECT t.team_id, t.team_name, t.hex_wert FROM teams t 
JOIN beitritt b ON t.team_id = b.team_id 
WHERE b.user_id = $user_id AND b.rang = '$rang'";
$teams_result = $conn->query($teams_query);

$admin_teams_query = "SELECT team_id, team_name, hex_wert FROM teams WHERE lehrer_id = $user_id";
$admin_teams_result = $conn->query($admin_teams_query);


// Chatverlauf abrufen und nach ungelesenen Nachrichten sortieren
$chat_query = "
    SELECT 
        CASE 
            WHEN c.sender_id = $user_id THEN c.empfaenger_id 
            ELSE c.sender_id 
        END AS chat_partner,
        CASE 
            WHEN c.sender_id = $user_id THEN c.rang_empfaenger 
            ELSE c.rang_sender 
        END AS rang_partner,
        MAX(c.gesendet_am) AS letzte_nachricht,
        SUM(CASE WHEN c.gelesen = 1 AND c.sender_id != $user_id THEN 1 ELSE 0 END) AS ungelesene_count,
        MAX(CASE WHEN c.gelesen = 1 AND c.empfaenger_id = $user_id AND c.rang_empfaenger = '$rang' THEN 1 ELSE 0 END) AS priorisiert
    FROM chat c
    WHERE (c.sender_id = $user_id AND c.rang_sender = '$rang') OR (c.empfaenger_id = $user_id AND c.rang_empfaenger = '$rang')
    GROUP BY chat_partner, rang_partner

    UNION ALL

    SELECT 
        $user_id AS chat_partner,
        '$rang' AS rang_partner,
        NULL AS letzte_nachricht,
        0 AS ungelesene_count,
        0 AS priorisiert
    FROM DUAL  -- DUAL wird für eine reine `SELECT`-Abfrage ohne echte Tabelle verwendet
    WHERE NOT EXISTS (
        SELECT 1 FROM chat 
        WHERE (sender_id = $user_id AND empfaenger_id = $user_id AND rang_sender = '$rang' AND rang_empfaenger = '$rang')
    )
    
    ORDER BY 
        CASE WHEN chat_partner = $user_id AND rang_partner = '$rang' THEN 0 ELSE 1 END,
        priorisiert DESC,
        letzte_nachricht DESC";

$chat_result = $conn->query($chat_query);

if (!$chat_result) {
    die("SQL-Fehler: " . $conn->error);
}

// Suchanfrage verarbeiten
$search_result = [];
if (isset($_POST['search'])) {
    $search_term = $conn->real_escape_string($_POST['search']);
    
    $search_query = "
        SELECT schueler_id AS id, vorname, nachname, 'schueler' AS rang 
        FROM schueler 
        WHERE schule_id = " . $_SESSION['schule_id'] . " 
        AND CONCAT(vorname, ' ', nachname) LIKE '%$search_term%'
        
        UNION ALL
        
        SELECT lehrer_id AS id, vorname, nachname, 'lehrer' AS rang 
        FROM lehrer 
        WHERE schule_id = " . $_SESSION['schule_id'] . " 
        AND CONCAT(vorname, ' ', nachname) LIKE '%$search_term%'
    ";
    
    $search_result = $conn->query($search_query);
}

// Chatnachrichten laden
$empfaenger_id = isset($_GET['empfaenger_id']) ? $_GET['empfaenger_id'] : null;
$empfaenger_rang = isset($_GET['empfaenger_rang']) ? $_GET['empfaenger_rang'] : null; // Rang des Empfängers
$chat_messages_result = null;

if ($empfaenger_id && $empfaenger_rang) {
    // Ungelesene Nachrichten als gelesen markieren
    $update_query = "UPDATE chat SET gelesen = 0 WHERE sender_id = $empfaenger_id AND rang_sender = '$empfaenger_rang' AND empfaenger_id = $user_id  AND rang_empfaenger = '$rang'";
    $conn->query($update_query);

    $chat_messages_query = "SELECT * FROM chat 
                            WHERE (sender_id = $user_id AND empfaenger_id = $empfaenger_id AND rang_sender = '$rang' AND rang_empfaenger = '$empfaenger_rang') 
                            OR (sender_id = $empfaenger_id AND empfaenger_id = $user_id AND rang_sender = '$empfaenger_rang' AND rang_empfaenger = '$rang') 
                            ORDER BY gesendet_am ASC";
    $chat_messages_result = $conn->query($chat_messages_query);
}

// Bild hochladen
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message'])) {
    $message = $_POST['message'];
    $file_path = null;

    // Datei-Upload verarbeiten
    if (isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'file_chat/';
        $file_name = basename($_FILES['file']['name']);
        $target_file = $upload_dir . uniqid() . '_' . $file_name;

        // Verzeichnis erstellen, falls es nicht existiert
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Datei verschieben
        if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
            $file_path = $target_file; // Dateipfad speichern
        } else {
            echo "Fehler beim Hochladen der Datei.";
        }
    }

    // Nachricht in die Datenbank einfügen
    $insert_query = "INSERT INTO chat (sender_id, rang_sender, empfaenger_id, rang_empfaenger, nachricht, gelesen, file) VALUES ($user_id, '$rang', $empfaenger_id, '$empfaenger_rang', '$message', 1, '$file_path')";
    $conn->query($insert_query);
    header("Location: chat_overview_2.php?empfaenger_id=$empfaenger_id&empfaenger_rang=$empfaenger_rang"); // Umleitung, um den Chatverlauf zu aktualisieren
    exit();
}

// Nachricht löschen
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_message_id'])) {
    $message_id = $_POST['delete_message_id'];
    $delete_query = "DELETE FROM chat WHERE chat_id = $message_id AND sender_id = " . $_SESSION['user_id'] . " AND gelesen = 1";
    $conn->query($delete_query);
    header("Location: chat_overview_2.php?empfaenger_id=" . $_POST['empfaenger_id'] . "&empfaenger_rang=$empfaenger_rang");
    exit();
}

if (isset($empfaenger_id) && isset($empfaenger_rang)) {
    if ($empfaenger_rang == 'schueler') {
        $sql_name = "SELECT vorname, nachname FROM schueler WHERE schueler_id = $empfaenger_id";
        $result_name = $conn->query($sql_name);
        $name = $result_name->fetch_assoc();
    } elseif ($empfaenger_rang == 'lehrer') {
        $sql_name = "SELECT vorname, nachname FROM lehrer WHERE lehrer_id = $empfaenger_id";
        $result_name = $conn->query($sql_name);
        $name = $result_name->fetch_assoc();
    }
}


?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Chat und Teams</title>
    <link rel="stylesheet" href="styles_chat.css">
    <script src="https://unpkg.com/boxicons@2.1.4/dist/boxicons.js"></script>
</head>
<body>
<?php include 'navigation.php'; ?>
    <div class="container">
        <div class="teams">
            <h2>Meine Teams</h2>
            <?php 
            if($rang == 'lehrer'){
            while ($team1 = $admin_teams_result->fetch_assoc()): ?>
                <button class="team-button" style="background-color: <?php echo htmlspecialchars($team1['hex_wert']); ?>;" onclick="window.location.href='team.php?id=<?php echo $team1['team_id']; ?>'">
                    <?php
                        $sql_team_strong1 = "
                        SELECT 
                            cgi.chat_team_gelesen_id, 
                            cgi.chat_team_id, 
                            cgi.empfaenger_id, 
                            cgi.rang, 
                            cgi.gelesen 
                        FROM 
                            chat_team_gelesen_id  cgi 
                        JOIN 
                            chat_team ct ON cgi.chat_team_id = ct.chat_team_id 
                        WHERE 
                            cgi.empfaenger_id = $user_id 
                            AND cgi.rang = '$rang' 
                            AND ct.team_id = " . $team1['team_id'];
                        $result_team_strong1 = $conn->query($sql_team_strong1);
                        if($result_team_strong1->num_rows > 0){
                            echo "<strong>"
                            .htmlspecialchars($team1['team_name'])
                            ."</strong>";
                        }
                        else{
                            echo htmlspecialchars($team1['team_name']);
                        }

                    ?>
                </button>
            <?php endwhile; 
            }
            ?>
            <?php while ($team = $teams_result->fetch_assoc()): ?>
                <button class="team-button" style="background-color: <?php echo htmlspecialchars($team['hex_wert']); ?>;" onclick="window.location.href='team.php?id=<?php echo $team['team_id']; ?>'">
                    <?php
                        $sql_team_strong = "
                        SELECT 
                            cgi.chat_team_gelesen_id, 
                            cgi.chat_team_id, 
                            cgi.empfaenger_id, 
                            cgi.rang, 
                            cgi.gelesen 
                        FROM 
                            chat_team_gelesen_id cgi 
                        JOIN 
                            chat_team ct ON cgi.chat_team_id = ct.chat_team_id 
                        WHERE 
                            cgi.empfaenger_id = $user_id 
                            AND cgi.rang = '$rang' 
                            AND ct.team_id = " . $team['team_id'];
                        $result_team_strong = $conn->query($sql_team_strong);
                        if($result_team_strong->num_rows > 0){
                            echo "<strong>"
                            .htmlspecialchars($team['team_name'])
                            ."</strong>";
                        }
                        else{
                            echo htmlspecialchars($team['team_name']);
                        }

                    ?>
                </button>
            <?php endwhile; ?>
        </div>

        <div class="chat">
            <div class="searsch">
                <div class="search-bar">
                    <form method="POST" class="search-form">
                        <input type="text" name="search" placeholder="Suche nach Schülern..." required>
                        <button type="submit">Suchen</button>
                    </form>
                </div>

                <div class="search-results">
                    <h3>Suchergebnisse</h3>
                    <ul>
                        <?php if ($search_result): ?>
                            <?php while ($schueler = $search_result->fetch_assoc()): ?>
                                <li onclick="loadChat(<?php echo $schueler['id']; ?>, '<?php echo $schueler['rang']; ?>')" style="cursor: pointer;">
                                    <?php echo htmlspecialchars($schueler['vorname'] . ' ' . $schueler['nachname']); ?>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li>Keine Schüler gefunden.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <div class="chat-users">
                <h3>Letzte Chats</h3>
                <ul>
                    <?php while ($chat = $chat_result->fetch_assoc()): ?>
                        <li onclick="loadChat(<?php echo $chat['chat_partner']; ?>, '<?php echo $chat['rang_partner']; ?>')" style="cursor: pointer;">
                            <?php
                            // Namen des Chatpartners abrufen
                            $partner_id = $chat['chat_partner'];
                            $partner_rang = $chat['rang_partner'];
                            if($partner_rang == 'schueler'){
                                $partner_query = "SELECT vorname, nachname FROM schueler WHERE schueler_id = $partner_id";
                            }
                            elseif($partner_rang == 'lehrer'){
                                $partner_query = "SELECT vorname, nachname FROM lehrer WHERE lehrer_id = $partner_id";
                            }
                            else{
                                echo "Fehler";
                            }
                            $partner_result = $conn->query($partner_query);
                            $partner = $partner_result->fetch_assoc();

                            // Überprüfen, ob es ungelesene Nachrichten gibt
                            $is_ungelesen = $chat['ungelesene_count'] > 0;

                            // Name des Chatpartners ausgeben, fett drucken, wenn ungelesene Nachrichten vorhanden sind
                            if ($is_ungelesen) {
                                echo '<strong>' . htmlspecialchars($partner['vorname'] . ' ' . $partner['nachname']) . '</strong>';
                            } else {
                                echo htmlspecialchars($partner['vorname'] . ' ' . $partner['nachname']);
                            }
                            ?>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>

            <div class="chat-window" id="chat-window">
                <h3>
                    <?php
                    if (isset($empfaenger_id)) {
                        if ($empfaenger_id == $user_id && $empfaenger_rang == $rang) {
                            echo "Deine Notizen";
                        } else {
                            echo "Chatverlauf mit " . htmlspecialchars($name['vorname'] . ' ' . $name['nachname']);
                        }
                    }
                    ?>
                </h3>
                <div id="chat-messages">
                    <?php if ($chat_messages_result): ?>
                        <?php while ($chat = $chat_messages_result->fetch_assoc()): ?>
                            <div class="message <?php echo ($chat['sender_id'] == $user_id && $chat['rang_sender'] == $rang) ? 'right' : 'left'; ?>">
                                <?php
                                // Überprüfen, ob der Absender ein Schüler oder ein Lehrer ist
                                if ($chat['sender_id'] != $user_id || $chat['rang_sender'] != $rang) {
                                    if ($chat['rang_sender'] == 'schueler') {
                                        // Absender ist ein Schüler
                                        $schueler_query = "SELECT vorname, nachname FROM schueler WHERE schueler_id = " . $chat['sender_id'];
                                        $schueler_result = $conn->query($schueler_query);
                                        $schueler = $schueler_result->fetch_assoc();
                                        ?>
                                        <small><strong><?php echo htmlspecialchars($schueler['vorname'] . ' ' . $schueler['nachname']); ?></strong></small><br>
                                    <?php } elseif ($chat['rang_sender'] == 'lehrer') {
                                        // Absender ist ein Lehrer
                                        $lehrer_query = "SELECT vorname, nachname FROM lehrer WHERE lehrer_id = " . $chat['sender_id'];
                                        $lehrer_result = $conn->query($lehrer_query);
                                        $lehrer = $lehrer_result->fetch_assoc();
                                        ?>
                                        <small><strong><?php echo htmlspecialchars($lehrer['vorname'] . ' ' . $lehrer['nachname']); ?></strong></small><br>
                                    <?php }
                                } else { ?>
                                    <small><strong>Ich</strong></small><br>
                                <?php } ?>
                                
                                <?php echo nl2br(htmlspecialchars($chat['nachricht'])); ?>
                                
                                <?php if ($chat['file']): ?>
                                    <div>
                                        <a href="<?php echo htmlspecialchars($chat['file']); ?>" download>Datei herunterladen</a>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="timestamp">
                                    <?php
                                    $heute = date('Y-m-d');
                                    $nachricht_datum = date('Y-m-d', strtotime($chat['gesendet_am']));
                                    $datum_anzeige = ($nachricht_datum == $heute) ? 'Heute' : date('l, j. F Y', strtotime($chat['gesendet_am']));
                                    ?>
                                    <small><?php echo $datum_anzeige; ?> - <?php echo date('H:i', strtotime($chat['gesendet_am'])); ?></small>
                                    
                                    <?php if ($chat['sender_id'] == $user_id): ?>
                                        <?php if ($chat['gelesen'] == 1): ?>
                                            <box-icon name='check' class='icon-check'></box-icon>
                                        <?php else: ?>
                                            <box-icon name='check-double' class='icon-check-square'></box-icon>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php if ($chat['sender_id'] == $user_id && $chat['gelesen'] == 1): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="delete_message_id" value="<?php echo $chat['chat_id']; ?>">
                                            <input type="hidden" name="empfaenger_id" value="<?php echo $empfaenger_id; ?>">
                                            <button type="submit" style="background: none; border: none; cursor: pointer;">
                                                <box-icon name='trash'></box-icon>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>Wählen Sie einen Chat aus, um den Verlauf anzuzeigen.</p>
                    <?php endif; ?>
                </div>
                <?php if ($empfaenger_id): ?>
                    <form method="POST" enctype="multipart/form-data">
                        <textarea id="chat-input" name="message" placeholder="Nachricht eingeben..." required></textarea>
                        
                        <label for="file-upload" style="cursor: pointer;">
                            <box-icon name='file-find'></box-icon>
                            <span id="file-selected" style="display: none; font-size: 0.8em; color: red;">1</span>
                        </label>
                        <input type="file" id="file-upload" name="file" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt" style="display: none;" onchange="showFileName()">

                        <input type="hidden" name="empfaenger_id" value="<?php echo $empfaenger_id; ?>">
                        <button type="submit">Senden</button>

                        <span id="file-name" style="margin-left: 10px;"></span>
                        <button type="button" id="remove-file" style="display: none; margin-left: 10px;" onclick="removeFile()">Löschen</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function loadChat(empfaenger_id, empfaenger_rang) {
            window.location.href = 'chat_overview_2.php?empfaenger_id=' + empfaenger_id + '&empfaenger_rang=' + empfaenger_rang;
        }

        function showFileName() {
            const fileInput = document.getElementById('file-upload');
            const fileName = document.getElementById('file-name');
            const fileSelected = document.getElementById('file-selected');
            const removeFileButton = document.getElementById('remove-file');
            if (fileInput.files.length > 0) {
                fileName.textContent = fileInput.files[0].name;
                fileSelected.style.display = 'inline';
                removeFileButton.style.display = 'inline';
            } else {
                fileName.textContent = '';
                fileSelected.style.display = 'none';
                removeFileButton.style.display = 'none';
            }
        }

        function removeFile() {
            const fileInput = document.getElementById('file-upload');
            const fileName = document.getElementById('file-name');
            const fileSelected = document.getElementById('file-selected');
            const removeFileButton = document.getElementById('remove-file');
            fileInput.value = '';
            fileName.textContent = '';
            fileSelected.style.display = 'none';
            removeFileButton.style.display = 'none';
        }

        function scrollToBottom() {
            const chatMessages = document.getElementById('chat-messages');
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        <?php if ($empfaenger_id && $empfaenger_rang): ?>
            window.addEventListener('load', scrollToBottom);
        <?php endif; ?>
    </script>
</body>
</html>

<?php
$conn->close();
?>