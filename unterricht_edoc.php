<?php
session_start();

// Verbindung zur Datenbank herstellen
$conn = mysqli_connect('localhost', 'root', '', 'educonnect');

if (!$conn) {
    die("Verbindung zur Datenbank fehlgeschlagen: " . mysqli_connect_error());
}

// Lehrer ID und Rang aus der Session
$lehrer_id = $_SESSION['user_id']; // Beispielwert, sollte aus der Session kommen
$rang = $_SESSION['rang'];

// Zugriffskontrolle
if ($rang != 'lehrer') {
    die("Kein Zugriff");
}

// Aktuellen Wochentag ermitteln
$wochentag = date('w'); // 0 (Sonntag) bis 6 (Samstag)

// Schüler im Kurs abrufen
$query = "SELECT sps.stunde_id, s.fach_id, s.fach_name, st.stufe_name, r.raum_bezeichnung AS raum 
          FROM stundenplan_schueler sps 
          JOIN faecher s ON sps.fach_id = s.fach_id 
          JOIN schueler sch ON sps.schueler_id = sch.schueler_id
          JOIN stufe st ON sch.stufe = st.stufe_id  -- Hier wird die Stufe über die ID abgerufen
          JOIN raum r ON sps.raum_id = r.raum_id  
          WHERE sps.lehrer_id = $lehrer_id AND sps.tag_id = $wochentag
          ORDER BY sps.stunde_id"; 

$result = mysqli_query($conn, $query);
$klassen = [];

// Gruppierung der Fächer, Stunden, Klassen und Räume
while ($row = mysqli_fetch_assoc($result)) {
    $fach_id = $row['fach_id'];
    
    if (!isset($klassen[$fach_id])) {
        $klassen[$fach_id] = [
            'fach_name' => $row['fach_name'],
            'stunden' => [],
            'stufen' => [],
            'raeume' => [],
            'anwesenheit_geprueft' => false,
        ];
    }
    if (!in_array($row['stunde_id'], $klassen[$fach_id]['stunden'])) {
        $klassen[$fach_id]['stunden'][] = $row['stunde_id'];
    }
    if (!in_array($row['stufe_name'], $klassen[$fach_id]['stufen'])) {
        $klassen[$fach_id]['stufen'][] = $row['stufe_name'];
    }
    if (!in_array($row['raum'], $klassen[$fach_id]['raeume'])) {
        $klassen[$fach_id]['raeume'][] = $row['raum'];
    }
}

// Anwesenheit prüfen
foreach ($klassen as $fach_id => &$klasse) {
    foreach ($klasse['stunden'] as $stunde_id) {
        $query = "SELECT * FROM anwesenheits_protokoll 
                  WHERE lehrer_id = $lehrer_id AND fach_id = $fach_id AND stunde_id = $stunde_id AND datum = CURDATE()";
        $result = mysqli_query($conn, $query);
        if (mysqli_num_rows($result) > 0) {
            $klasse['anwesenheit_geprueft'] = true;
            break;
        }
    }
}

// Raumverlegungen abrufen
$raum_verlegungen = [];
foreach ($klassen as $fach_id => &$klasse) {
    foreach ($klasse['stunden'] as $stunde_id) {
        $query = "SELECT rv.raum_id AS neuer_raum_id, r.raum_bezeichnung AS neuer_raum 
                  FROM raum_verlegung rv 
                  JOIN raum r ON rv.raum_id = r.raum_id 
                  WHERE rv.stunde_id = $stunde_id AND rv.lehrer_id = $lehrer_id AND rv.datum = CURDATE()";
        $result = mysqli_query($conn, $query);
        if (mysqli_num_rows($result) > 0) {
            while ($verlegung = mysqli_fetch_assoc($result)) {
                $raum_verlegungen[$fach_id][$stunde_id] = $verlegung;
            }
        }
    }
}

// HTML-Ausgabe
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Klassenübersicht</title>
    <link rel="stylesheet" href="fehlzeiten.css">
    <link rel="shortcut icon" href="picture.ico" type="image/x-icon">
</head>
<body>

<h1 class="unterrichtedoctitle">Klassenübersicht für heute</h1>

<a href="lehrer_fehlzeiten.php" class="unterrichtedocbutton">Entschuldigungen prüfen</a>
<a href="vorbereitung_stunden.php" class="unterrichtedocbutton">Unterrichts Vorbereitung</a>

<table class="unterrichtedoctable">
    <thead>
        <tr class="unterrichtedoctr">
            <th class="unterrichtedocth">Fach</th>
            <th class="unterrichtedocth">Stunden</th>
            <th class="unterrichtedocth">Klassen</th>
            <th class="unterrichtedocth">Räume</th>
            <th class="unterrichtedocth">Aktion</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($klassen as $fach_id => $klasse): ?>
            <tr class="unterrichtedoctr">
                <td><?php echo htmlspecialchars($klasse['fach_name']); ?></td>
                <td><?php echo htmlspecialchars(implode(', ', $klasse['stunden'])); ?></td>
                <td><?php echo htmlspecialchars(implode(', ', $klasse['stufen'])); ?></td>
                <td>
                    <?php
                    foreach ($klasse['raeume'] as $raum) {
                        $alter_raum = $raum;
                        $neuer_raum = '';
                        foreach ($klasse['stunden'] as $stunde_id) {
                            if (isset($raum_verlegungen[$fach_id][$stunde_id])) {
                                $neuer_raum = $raum_verlegungen[$fach_id][$stunde_id]['neuer_raum'];
                                break;
                            }
                        }
                        if ($neuer_raum) {
                            echo "<span class='unterrichtedocstrikethrough'>" . htmlspecialchars($alter_raum) . "</span> ";
                            echo "<span class='unterrichtedochighlight'>" . htmlspecialchars($neuer_raum) . "</span><br>";
                        } else {
                            echo htmlspecialchars($raum) . "<br>";
                        }
                    }
                    ?>
                </td>
                <td>
                    <form action="anwesenheit_edoc.php" method="POST">
                        <input type="hidden" name="fach_id" value="<?php echo $fach_id; ?>">
                        <input type="hidden" name="stunde_id" value="<?php echo $klasse['stunden'][0]; ?>">
                        <button type="submit" class="unterrichtedocbutton">Anwesenheit prüfen</button>
                        <?php if ($klasse['anwesenheit_geprueft']): ?>
                            <span class="unterrichtedocstatus unterrichtedocchecked">Anwesenheit bereits geprüft</span>
                        <?php else: ?>
                            <span class="unterrichtedocstatus unterrichtedocnot-checked">Anwesenheit noch nicht geprüft</span>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
<?php include 'navigation.php'; ?>
</html>

<?php
// Verbindung schließen
mysqli_close($conn);
?>