<?php
SESSION_start();
 
$conn = new Mysqli('localhost', 'root', '', 'educonnect');
mysqli_set_charset($conn, "utf8mb4");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
 
$wochentag = date('w'); 
$aktuelles_datum = date('Y-m-d'); 

$rang1 = $_SESSION['rang1'];

$_SESSION['letzte_seite'] = 'mainpage.php';
unset($_SESSION['news_kategorie']);
 
$rang = $_SESSION['rang'];

if($rang1 === '7'){
    header('Location: admin.php');
    exit();
}

if ($rang === 'admin' || $rang === 'schulleiter') {
    // Admins und Schulleiter sind in der "lehrer"-Tabelle
    $id_feld = "lehrer_id";
    $rang_tabelle = "lehrer";
} else {
    // Schüler oder Lehrer haben ihre eigene Tabelle
    $id_feld = $rang . "_id"; // Ergibt "schueler_id" oder "lehrer_id"
    $rang_tabelle = $rang; // Tabelle ist "schueler" oder "lehrer"
}
 
$user_id = $_SESSION['user_id'];
$schule_id = $_SESSION['schule_id'];

 
 
 
$schueler_query = "SELECT `vorname`, `nachname` FROM $rang_tabelle WHERE `$id_feld` = '$user_id'";
 
$schueler = mysqli_query($conn, $schueler_query);
 
if ($schueler) {
    $row = mysqli_fetch_assoc($schueler);
 
    if ($row) {
        $vorname = $row['vorname'];
        $nachname = $row['nachname'];
 
        $_SESSION['vorname'] = $vorname;
        $_SESSION['nachname'] = $nachname;
 
        $firstLetter_vorname = strtoupper($vorname[0]);
        $firstLetter_nachname = strtoupper($nachname[0]);
 
        $namenkürzel = $firstLetter_vorname . $firstLetter_nachname;
 
        // Hintergrundfarbe bestimmen
        $firstLetter = strtoupper($firstLetter_vorname);
 
        if ($firstLetter >= 'A' && $firstLetter <= 'B') {
            $backgroundColor = "#3498db"; // Blau
        } elseif ($firstLetter >= 'C' && $firstLetter <= 'D') {
            $backgroundColor = "#e74c3c"; // Rot
        } elseif ($firstLetter >= 'E' && $firstLetter <= 'F') {
            $backgroundColor = "#2ecc71"; // Grün
        } elseif ($firstLetter >= 'G' && $firstLetter <= 'H') {
            $backgroundColor = "#f1c40f"; // Gelb
        } elseif ($firstLetter >= 'I' && $firstLetter <= 'J') {
            $backgroundColor = "#9b59b6"; // Lila
        } elseif ($firstLetter >= 'K' && $firstLetter <= 'L') {
            $backgroundColor = "#1abc9c"; // Türkis
        } elseif ($firstLetter >= 'M' && $firstLetter <= 'N') {
            $backgroundColor = "#ff5733"; // Orange
        } elseif ($firstLetter >= 'O' && $firstLetter <= 'P') {
            $backgroundColor = "#8e44ad"; // Dunkellila
        } elseif ($firstLetter >= 'Q' && $firstLetter <= 'R') {
            $backgroundColor = "#2c3e50"; // Dunkelblau
        } elseif ($firstLetter >= 'S' && $firstLetter <= 'T') {
            $backgroundColor = "#d35400"; // Dunkelorange
        } elseif ($firstLetter >= 'U' && $firstLetter <= 'V') {
            $backgroundColor = "#16a085"; // Dunkeltürkis
        } elseif ($firstLetter >= 'W' && $firstLetter <= 'X') {
            $backgroundColor = "#c0392b"; // Dunkelrot
        } else {
            $backgroundColor = "#7f8c8d"; // Grau für Y und Z
        }
    }}
   
    $_SESSION['profil_kürzel'] = $namenkürzel;
    $_SESSION['profil_farbe'] = $backgroundColor;
 
    $fehlzeiten_query = "SELECT `lehrer_id`, `beginn`, `ende` FROM `lehrer_fehlzeiten` WHERE `datum` = '$aktuelles_datum'";
    $fehlzeiten_result = mysqli_query($conn, $fehlzeiten_query);
    $fehlende_lehrer = [];
     
    if ($fehlzeiten_result && $fehlzeiten_result->num_rows > 0) {
        while ($row = $fehlzeiten_result->fetch_assoc()) {
            $fehlende_lehrer[] = [
                'lehrer_id' => $row['lehrer_id'],
                'beginn' => $row['beginn'],
                'ende' => $row['ende']
            ];
        }
    }
    if($rang === 'schueler'){
        $stufe_id = $_SESSION['stufe_id']; // Diese Zeile ist nur für Schüler relevant
 
 
// Stundenplan mit Raumverlegung abrufen
$stundenplan_query = "
 
    SELECT
        sp.stunde_id,
        st.stunde,
        sp.fach_id,
        sp.lehrer_id,
        f.fach_name,
        r.raum_bezeichnung AS standard_raum,
        rv.raum_bezeichnung AS geaendert_raum,
        TIME_FORMAT(st.beginn, '%H:%i') AS beginn,
        TIME_FORMAT(st.ende, '%H:%i') AS ende,
        IF(rv.raum_id IS NOT NULL, 1, 0) AS raum_geaendert -- Markierung für geänderte Räume
    FROM
        stundenplan_schueler sp
    LEFT JOIN
        faecher f ON sp.fach_id = f.fach_id
    LEFT JOIN
        stunden st ON sp.stunde_id = st.stunde_id AND st.schule_id = '$schule_id' -- Stunden werden anhand der Schule geladen
    LEFT JOIN
        raum r ON sp.raum_id = r.raum_id -- Standardraum
    LEFT JOIN
        (
            SELECT rv.raum_id, rv.stunde_id, rv.lehrer_id, raum.raum_bezeichnung
            FROM raum_verlegung rv
            JOIN raum ON rv.raum_id = raum.raum_id
            WHERE rv.datum = '$aktuelles_datum'
        ) rv ON sp.lehrer_id = rv.lehrer_id AND sp.stunde_id = rv.stunde_id
    WHERE
        sp.schueler_id = '$user_id'
        AND sp.tag_id = '$wochentag'
    ORDER BY
        st.stunde ASC
";
 
$stundenplan_result = mysqli_query($conn, $stundenplan_query);
 
$stundenplan = [];
while ($row = $stundenplan_result->fetch_assoc()) {
    $stundenplan[] = $row;
}}
 
else if($rang === 'lehrer'){

    $stufe_id = 0;
    $stundenplan_query = "
 
   SELECT
        sp.stunde_id,
        st.stunde,
        sp.fach_id,
        f.fach_name,
        r.raum_bezeichnung AS standard_raum,
        rv.raum_bezeichnung AS geaendert_raum,
        TIME_FORMAT(st.beginn, '%H:%i') AS beginn,
        TIME_FORMAT(st.ende, '%H:%i') AS ende,
        IF(rv.raum_id IS NOT NULL, 1, 0) AS raum_geaendert -- Markierung für geänderte Räume
    FROM
        stundenplan_lehrer sp
    LEFT JOIN
        faecher f ON sp.fach_id = f.fach_id
    LEFT JOIN
        stunden st ON sp.stunde_id = st.stunde_id AND st.schule_id = '$schule_id' -- Stunden werden anhand der Schule geladen
    LEFT JOIN
        raum r ON sp.raum_id = r.raum_id -- Standardraum
    LEFT JOIN
        (
            SELECT rv.raum_id, rv.stunde_id, rv.lehrer_id, raum.raum_bezeichnung
            FROM raum_verlegung rv
            JOIN raum ON rv.raum_id = raum.raum_id
            WHERE rv.datum = '$aktuelles_datum'
        ) rv ON sp.lehrer_id = rv.lehrer_id AND sp.stunde_id = rv.stunde_id
    WHERE
        sp.lehrer_id = '$user_id'
 
        AND sp.tag_id = '$wochentag'
    ORDER BY
        st.stunde ASC
";
 
$stundenplan_result = mysqli_query($conn, $stundenplan_query);
 
$stundenplan = [];
$stundenplan_result = mysqli_query($conn, $stundenplan_query);
 
if (!$stundenplan_result) {
    die("Fehler in der Abfrage: " . mysqli_error($conn));
}
 
while ($row = $stundenplan_result->fetch_assoc()) {
    $stundenplan[] = $row;
}
}
 
else {
    header('Location: index.php');
}
 
 
// Nachrichten für die spezifische Schule
$news_query = "SELECT `titel` FROM `news_allgemein` WHERE `schule_id` = $schule_id AND '$aktuelles_datum' BETWEEN `start_datum` AND `end_datum` AND `wichtigkeit` = 1";
$news_allgemein_result = mysqli_query($conn, $news_query);
 
// Nachrichten für die spezifische Stufe
$news_schule_query = "SELECT `titel` FROM `news_stufe` WHERE `schule_id` = $schule_id AND '$aktuelles_datum' BETWEEN `start_datum` AND `end_datum` AND `wichtigkeit` = 1 AND `stufe_id` = '$stufe_id'";
$news_schule_result = mysqli_query($conn, $news_schule_query);
 
// Nachrichten für das Land
$schule_query = "SELECT `schule_id`, `schule_name`, `land`, `bundesland` FROM `schulen` WHERE `schule_id` = $schule_id";
$schule_result = $conn->query($schule_query);

$schule_name = null;
$bundesland = null;
$land = null;

if ($schule_result && $schule_result->num_rows > 0) {
    $schule_data = $schule_result->fetch_assoc();
    $schule_name = $schule_data['schule_name'];
    $bundesland = $schule_data['bundesland']; // Diese Zeile fehlte
    $land = $schule_data['land'];
}

 
 
$news_land_query = "SELECT `titel` FROM `news_land` WHERE `land` = '$land' AND '$aktuelles_datum' BETWEEN `start_datum` AND `end_datum` AND `wichtigkeit` = 1";
$news_land_result = mysqli_query($conn, $news_land_query);
 
 
// Ferien abrufen (nach Bundesland gefiltert)
$ferien_query = "SELECT `ferien_name`, `anfangsdatum`, `enddatum` FROM `ferien` WHERE '$aktuelles_datum' BETWEEN `anfangsdatum` AND `enddatum` AND `bundesland` = '$bundesland'";
$ferien_result = mysqli_query($conn, $ferien_query);
$ferien_name = null;
 
if ($ferien_result && $ferien_result->num_rows > 0) {
    $ferien_data = $ferien_result->fetch_assoc();
    $ferien_name = $ferien_data['ferien_name'];
}








if ($rang === 'schueler') {
    // Schüler sehen nur Teams, in denen sie Mitglied sind
    $sql = "SELECT teams.*, stufe.stufe_name, faecher.fach_name, lehrer.vorname, lehrer.nachname, teams.hex_wert 
            FROM teams 
            JOIN beitritt ON teams.team_id = beitritt.team_id 
            JOIN stufe ON teams.stufe_id = stufe.stufe_id 
            JOIN faecher ON teams.fach_id = faecher.fach_id
            LEFT JOIN lehrer ON teams.lehrer_id = lehrer.lehrer_id
            WHERE beitritt.user_id = ? AND teams.schule_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $schule_id);

} elseif ($rang === 'lehrer') {
    // Lehrer sehen nur Teams, die sie betreuen
    $sql = "SELECT teams.*, stufe.stufe_name, faecher.fach_name, teams.hex_wert 
            FROM teams 
            JOIN stufe ON teams.stufe_id = stufe.stufe_id 
            JOIN faecher ON teams.fach_id = faecher.fach_id
            WHERE teams.schule_id = ? AND teams.lehrer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $schule_id, $user_id);
} else {
    $teams_result = null; // Falls der Benutzer nicht Lehrer oder Schüler ist
}

// Falls eine Abfrage existiert, führen wir sie aus
if (isset($stmt)) {
    $stmt->execute();
    $teams_result = $stmt->get_result();
}



if (!isset($_SESSION['user_id']) || !isset($_SESSION['rang'])) {
    header("Location: index.php");
    exit();
}
?>
 
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <link rel="shortcut icon" href="picture.ico" type="image/x-icon">
    <title>Mainpage</title>
</head>
<body>
<?php include 'navigation.php'; ?>
<div class="main-container">
    <div class="content-top_mainpage">
        <div class="content-left_mainpage">
            <div class="schule-content-left_mainpage"><?php echo htmlspecialchars($schule_name ?? 'Unbekannte Schule'); ?></div>
            <div class="datum-content-left_mainpage">
                <?php
                setlocale(LC_TIME, 'de_DE.UTF-8');
                echo strftime('%A, %d.%m.%Y');
                ?>
            </div>
            <form method="POST" action="news_session.php">
    <button type="submit" name="news_kategorie" value="ALLGEMEIN" class="news-box">
        <h3>Allgemeine Infos</h3>
        <?php if ($news_allgemein_result && $news_allgemein_result->num_rows > 0): ?>
            <?php while ($news = $news_allgemein_result->fetch_assoc()): ?>
                <div class="news-title"><?php echo htmlspecialchars($news['titel']); ?></div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="news-hint">Keine allgemeinen News verfügbar.</div>
        <?php endif; ?>
    </button>
</form>

<form method="POST" action="news_session.php">
    <button type="submit" name="news_kategorie" value="STUFE" class="news-box">
        <h3>Stufennachrichten</h3>
        <?php if ($news_schule_result && $news_schule_result->num_rows > 0): ?>
            <?php while ($news = $news_schule_result->fetch_assoc()): ?>
                <div class="news-title"><?php echo htmlspecialchars($news['titel']); ?></div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="news-hint">Keine stufenbezogenen News verfügbar.</div>
        <?php endif; ?>
    </button>
</form>

<form method="POST" action="news_session.php">
    <button type="submit" name="news_kategorie" value="LAND" class="news-box">
        <h3>Landesnachrichten</h3>
        <?php if ($news_land_result && $news_land_result->num_rows > 0): ?>
            <?php while ($news = $news_land_result->fetch_assoc()): ?>
                <div class="news-title"><?php echo htmlspecialchars($news['titel']); ?></div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="news-hint">Keine landesbezogenen News verfügbar.</div>
        <?php endif; ?>
    </button>
</form>
        </div>
        <a href="stundenplan.php">
    <div class="content-right_mainpage">
        <?php if ($ferien_name): ?>
            <div class="ferien">Keine Schule: <?php echo $ferien_name; ?></div>
        <?php endif; ?>
        <table>
            <tr>
                <th>Stunde</th>
                <th>Fach</th>
                <th>Raum</th>
                <th>Beginn</th>
                <th>Ende</th>
            </tr>
            <?php foreach ($stundenplan as $eintrag): ?>
                <?php
                    $krank = false;
                    foreach ($fehlende_lehrer as $lehrer) {
                        if (
                            $eintrag['lehrer_id'] == $lehrer['lehrer_id'] &&
                            (
                                ($eintrag['beginn'] >= $lehrer['beginn'] && $eintrag['beginn'] <= $lehrer['ende']) ||
                                ($eintrag['ende'] >= $lehrer['beginn'] && $eintrag['ende'] <= $lehrer['ende'])
                            )
                        ) {
                            $krank = true;
                            break;
                        }
                    }

                    // Falls Ferien sind, soll alles gelb werden
                    $klasse = $ferien_name ? 'ferien-markiert' : ($krank ? 'krank' : '');
                ?>
                <tr>
                    <td><?php echo $eintrag['stunde']; ?></td>
                    <td class="<?php echo $klasse; ?>"><?php echo $eintrag['fach_name']; ?></td>
                    <td class="<?php echo ($eintrag['raum_geaendert'] ? 'raum-geaendert' : ''); ?>">
                        <?php echo $eintrag['raum_geaendert'] ? $eintrag['geaendert_raum'] : $eintrag['standard_raum']; ?>
                    </td>
                    <td><?php echo $eintrag['beginn']; ?></td>
                    <td><?php echo $eintrag['ende']; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</a>

    </div>
    <div class="down-under_mainpage">
        <div class="navigation_mainpage">
            <a href="auswahl_kurs2.php"><div class="teams_mainpage"><i class='bx bxs-group'></i> TEAMS</div></a>
            <a href="news.php"><div class="news_mainpage"><i class='bx bxs-news'></i> NEWS</div></a>
            <a href="chat_overview_2.php"><div class="chat_mainpage"><i class='bx bxs-message-dots'></i> CHAT</div></a>
            <a href="<?php
            if($_SESSION['rang'] == 'schueler'){
                echo "fehlzeiten.php";
            } elseif($_SESSION['rang'] == 'lehrer'){
                echo "unterricht_edoc.php";
            }
        ?>"><div class="edocs_mainpage"><i class='bx bxs-ghost'></i> E-DOCS</div></a>
            <a href=" <?php
                if($_SESSION['rang'] == 'schueler'){
                    echo "noten_anzeigeschueler.php";
                } elseif($_SESSION['rang'] == 'lehrer'){
                    echo "noten.php";
                }
            ?>"><div class="noten_mainpage"><i class='bx bxs-star'></i> MEINE NOTEN</div></a>
            <a href="stundenplan.php"><div class="stundenplan_mainpage"><i class='bx bxs-calendar'></i> STUNDENPLAN</div></a>
        </div>
        <href="calendar.php">
            <div class="kalender_mainpage">
                <?php include 'calendar_mainpage.php'; ?>
            </div>   
    </div>
    <?php if ($teams_result->num_rows > 0): ?>
        <div id="teams" class="team-auflistung_mainpage">
            <?php while ($team = $teams_result->fetch_assoc()): ?>
                <div class="team-box" onclick="window.location.href='team.php?id=<?= $team['team_id']; ?>'" 
                     style="background-color: <?= htmlspecialchars($team['hex_wert']); ?>;">
                    <div>
                        <div class="team-header"><?= htmlspecialchars($team['team_name']); ?></div>
                        <?php if ($rang === 'schueler'): ?>
                            <div class="team-subtext">Lehrer: <?= htmlspecialchars($team['vorname'] . " " . $team['nachname']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="team-description">
                        <?= htmlspecialchars($team['beschreibung']); ?>
                    </div>
                    <div class="team-footer">
                        <span><?= ($team['fach_name']); ?></span>
                        <span><?= ($team['stufe_name']); ?></span>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p class="no-teams">Keine Teams gefunden.</p>
    <?php endif; ?>

    </div>
</div>
</body>
</html>
<style>
        .team-auflistung_mainpage {
    display: flex;
    flex-wrap: wrap;
    gap: 20px; /* Etwas mehr Abstand zwischen den Boxen */
    justify-content: flex-start;
    background-color: #f0f0f0;
    height: auto;
    width: 97.5%;
    margin-top: 3vw;
    
    border-radius: 20px;
  padding: 20px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.team-box {
    width: 250px;
    height: 250px; /* Größeres Quadrat */
    background-color: #f0f0f0;
    border-radius: 10px;
    padding: 15px;
    text-align: left;
    cursor: pointer;
    transition: transform 0.2s;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
    box-sizing: border-box;
}

.team-box:hover {
    transform: scale(1.05);
}

.team-header {
    font-size: 20px; /* Größere Überschrift */
    font-weight: bold;
}

.team-subtext {
    font-size: 20px; /* Größerer Text für den Lehrer */
    color: rgba(0, 0, 0, 0.7);
}

.team-description {
    flex-grow: 1;
    font-size: 16px; /* Größerer Beschreibungstext */
    line-height: 1.4; /* Mehr Zeilenhöhe für bessere Lesbarkeit */
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 4; /* Eine Zeile mehr, da größere Box */
    -webkit-box-orient: vertical;
}

.team-footer {
    display: flex;
    justify-content: space-between;
    font-size: 18px; /* Größerer Text für die Fußzeile */
    font-weight: bold;
}

.no-teams {
    text-align: left;
    font-size: 20px;
    color: red;
    margin-top: 20px;
}
.ferien-markiert {
  background-color: rgb(255, 153, 153) !important;
  /* Gelb für Ferien */
  color: black !important;
  font-weight: bold;
}
.ferien-markiert:hover{
    background-color: rgb(255, 107, 107) !important;
  /* Rot für Krankheitsfälle */
  color: black !important;
  font-weight: bold;
}


.krank {
  background-color: rgb(255, 153, 153) !important;
  /* Rot für Krankheitsfälle */
  color: black !important;
  
}

.krank:hover {
  background-color: rgb(255, 107, 107) !important;
  /* Rot für Krankheitsfälle */
  color: black !important;
  
}





    </style>