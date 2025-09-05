<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Noten Übersicht Lehrer</title>
    <link rel="stylesheet" href="educonnect.css">
    <link rel="shortcut icon" href="picture.ico" type="image/x-icon">
</head>
<body>

    <div class="nav-box">
        <a href="noten.php">Noten eintragen</a>
        <a href="noten_anzeigelehrer.php" class="active">Noten Übersicht Lehrer</a>
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

    
    $sql_noten = "SELECT n.schueler_id, s.vorname, s.nachname, n.note, n.gewichtung, f.fach_name, pa.art 
                  FROM noten n 
                  JOIN schueler s ON n.schueler_id = s.schueler_id 
                  JOIN faecher f ON n.fach_id = f.fach_id 
                  JOIN pruefungsart pa ON n.pruefungs_id = pa.pruefungs_id 
                  WHERE s.schule_id = '{$_SESSION['schule_id']}'";
    $res_noten = mysqli_query($conn, $sql_noten);

    $sql_einzelnoten = "SELECT n.schueler_id, s.vorname, s.nachname, n.note, n.gewichtung, n.bemerkung, f.fach_name, pa.art, n.freigabe
    FROM noten n
    JOIN schueler s ON n.schueler_id = s.schueler_id
    JOIN faecher f ON n.fach_id = f.fach_id 
    JOIN pruefungsart pa ON n.pruefungs_id = pa.pruefungs_id 
    WHERE s.schule_id = '{$_SESSION['schule_id']}'";

    $res_einzelnoten = mysqli_query($conn, $sql_einzelnoten);
    if (!$res_einzelnoten) {
    die("Fehler bei der Abfrage der Einzelnoten: " . mysqli_error($conn));
    }

   
    $durchschnitt = [];
    $noten_liste = [];


    while ($row = mysqli_fetch_assoc($res_noten)) {
        $schueler_id = $row['schueler_id'];
        if (!isset($durchschnitt[$schueler_id])) {
            $durchschnitt[$schueler_id] = ['sum' => 0, 'count' => 0, 'vorname' => $row['vorname'], 'nachname' => $row['nachname']];
            $noten_liste[$schueler_id] = [];  
        }
        $durchschnitt[$schueler_id]['sum'] += $row['note'] * ($row['gewichtung'] / 100);
        $durchschnitt[$schueler_id]['count'] += $row['gewichtung'];
        $noten_liste[$schueler_id][] = $row['fach_name'] . ", " . $row['note'];  
    }

    echo "<h1>Noten Übersicht</h1>";
    echo "<table border='1'>
            <tr>
                <th>Vorname</th>
                <th>Nachname</th>
                <th>Durchschnitt</th>
                <th>Fach, Noten</th>
                <th>Freigabe</th>
            </tr>";

    foreach ($durchschnitt as $id => $data) {
        $avg = $data['count'] > 0 ? $data['sum'] / ($data['count'] / 100) : 0;
        $noten_str = implode("<br>", $noten_liste[$id]); // Noten als Zeilenumbruch getrennte Liste
        echo "<tr>
                <td>{$data['vorname']}</td>
                <td>{$data['nachname']}</td>
                <td>" . number_format($avg, 2) . "</td>
                <td>$noten_str</td>
                <td>
                    <form action='' method='POST'>
                        <input type='hidden' name='schueler_id' value='$id'>
                        <input type='submit' name='freigeben' value='Freigeben'>
                    </form>
                </td>
              </tr>";
    }
    echo "</table>";

    echo "<h1>Einzelnoten Freigabe</h1>";
    echo "<table border='1'>
            <tr>
                <th>Vorname</th>
                <th>Nachname</th>
                <th>Fach, Note, Gewichtung</th>
                <th>Bemerkung</th>
                <th>Freigabe</th>
            </tr>";

    while ($row1 = mysqli_fetch_assoc($res_einzelnoten))  {

        echo "<tr>
                <td>{$row1['vorname']}</td>
                <td>{$row1['nachname']}</td>
                <td>{$row1['fach_name']}, {$row1['note']}, {$row1['gewichtung']}</td>
                <td>{$row1['bemerkung']}</td>
                
                <td>
                    <form action='' method='POST'>
                        <input type='hidden' name='schueler_id' value='$id'>";

                    if( $row1['freigabe'] == 0 ){
                        echo"<input type='submit' name='freigeben' value='Freigeben'>";
                    }
                    else{
                        echo"
                        <input type='submit' name='zurücksetzen' value='zurücksetzen'>";
                    }
                        

                    
                    echo"
                    </form>
                </td>
              </tr>";
    }
    echo "</table>";



    
    $sql_stufen = "SELECT DISTINCT stufe FROM schueler WHERE schule_id = '{$_SESSION['schule_id']}'";
    $res_stufen = mysqli_query($conn, $sql_stufen);
 
    echo "<h2>Freigabe für gesamte Stufe</h2>";
    echo "<form action='' method='POST'>
            <label for='stufe '>Stufe wählen:</label>
            <select name='stufe' id='stufe' required>
                <option value=''>Bitte wählen</option>";
                while ($row = mysqli_fetch_assoc($res_stufen)) {
                    echo "<option value='" . $row['stufe'] . "'>" . $row['stufe'] . "</option>";
                }
    echo "  </select>
            <input type='submit' name='freigeben_stufe' value='Freigeben'>
          </form>";

 
    if (isset($_POST['freigeben'])) {
        $schueler_id = $_POST['schueler_id'];
        $sql_freigeben = "UPDATE noten SET freigabe = 1 WHERE schueler_id = '$schueler_id'";
        if (mysqli_query($conn, $sql_freigeben)) {
            echo "Noten für Schüler mit ID $schueler_id wurden freigegeben.";
        } else {
            echo "Fehler beim Freigeben der Noten: " . mysqli_error($conn);
        }
    }

   
    if (isset($_POST['freigeben_stufe'])) {
        $stufe = $_POST['stufe'];
        $sql_freigeben_stufe = "UPDATE noten n 
                                 JOIN schueler s ON n.schueler_id = s.schueler_id 
                                 SET n.freigabe = 1 
                                 WHERE s.stufe = '$stufe' AND s.schule_id = '{$_SESSION['schule_id']}'";
        if (mysqli_query($conn, $sql_freigeben_stufe)) {
            echo "Noten für alle Schüler der Stufe $stufe wurden freigegeben.";
        } else {
            echo "Fehler beim Freigeben der Noten für die Stufe: " . mysqli_error($conn);
        }
    }

    mysqli_close($conn);  
    ?>
</body><?php include 'navigation.php'; ?>
</html>