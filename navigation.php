<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="nav.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <title>navigation</title>
</head>
<body>
<?php

$conn = new Mysqli('localhost', 'root', '', 'educonnect');
mysqli_set_charset($conn, "utf8mb4");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$schule_id = $_SESSION['schule_id'];

$sql_hex_wert = "SELECT hex_wert FROM schulen WHERE schule_id = $schule_id";
$result_hex_wert = $conn->query($sql_hex_wert);
$row_hex_wert = $result_hex_wert->fetch_assoc();
$hex_wert = $row_hex_wert['hex_wert'];
?>
<div class="navigationsleiste_mainpage" 
    <?php
        if(empty($hex_wert)){
            echo ">";
        } else {
            echo "style='background-color: " . htmlspecialchars($hex_wert) . ";'>";
        }
    ?>
    <div class="menu-icon" onclick="toggleMenu()">
        <i class='bx bx-menu'></i>
    </div>
    <a href="mainpage.php" class="home">
        <div class="home-icon"><i class="bx bx-home"></i></div>
    </a>
    <div class="link_navigationsleiste">
    <a href="auswahl_kurs2.php" class= "box-link">Teams</a>
        <a href="chat_overview_2.php" class="box-link">Chat</a>
        <a href="stundenplan.php" class="box-link">Stundenplan</a>
        <a href="
            <?php
                if($_SESSION['rang'] == 'schueler'){
                    echo "noten_anzeigeschueler.php";
                } elseif($_SESSION['rang'] == 'lehrer'){
                    echo "noten.php";
                }
            ?>        
        " class="box-link">Meine Noten</a>
        <a href="
        <?php
            if($_SESSION['rang'] == 'schueler'){
                echo "fehlzeiten.php";
            } elseif($_SESSION['rang'] == 'lehrer'){
                echo "unterricht_edoc.php";
            }
        ?>
        " class="box-link">EDocs</a>
    </div>
    <a href="
        <?php
            if($_SESSION['rang'] == 'schueler'){
                echo "schueler_abwesenheit.php";
            } elseif($_SESSION['rang'] == 'lehrer'){
                echo "lehrer_abwesenheit.php";
            }
        ?>
    " class="fehlzeiten-box_navigationsleiste">
        <div class="fehlzeiten-icon_navigationsleiste"><i class="bx bx-user-x"></i></div>
        <span class="fehlzeiten-text_navigationsleiste">Heute nicht da?</span>
    </a>
    <?php
$conn = new Mysqli('localhost', 'root', '', 'educonnect');
mysqli_set_charset($conn, "utf8mb4");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Kürzel und Hintergrundfarbe aus der Session laden
$namenkürzel = isset($_SESSION['profil_kürzel']) ? $_SESSION['profil_kürzel'] : '??';
$backgroundColor = isset($_SESSION['profil_farbe']) ? $_SESSION['profil_farbe'] : '#7f8c8d';

// Vorname und Nachname aus der Session laden
$vorname = isset($_SESSION['vorname']) ? $_SESSION['vorname'] : 'Maximilian';
$nachname = isset($_SESSION['nachname']) ? $_SESSION['nachname'] : 'Mustermann';

// Suchformular mit individuellen Klassennamen
echo '<div class="SucheProfil_navigationsleiste">
    <div class="search-and-profile">
        <form method="POST">
            <div class="search-box_navigationsleiste">
                
                <input type="text" class="search-input_navigationsleiste" name="search" placeholder="Suchen...">
                <input type="submit" class="search-button_navigationsleiste" value="🔍" name="submit">
            </div>
        </form>

        <!-- Profilbox -->
        <div class="profil-box">
            <a href="profil.php" class="profil-link">
                <div class="profil-kreis" style="background-color: ' . $backgroundColor . ';">
                    ' . $namenkürzel . '
                </div>
            </a>
        </div>
    </div>
</div>';

if (isset($_POST['submit']) && !empty($_POST['search'])) {
    $search_term = mysqli_real_escape_string($conn, $_POST['search']);

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

    if ($search_result->num_rows > 0) {
        echo "<div class='search-results_navigation'>";
        
        while ($row = $search_result->fetch_assoc()) {
            $id = urlencode($row['id']);
            $rang = urlencode($row['rang']);
            $name = htmlspecialchars($row['vorname'] . " " . $row['nachname']);

            echo "<p>
                <a href='chat_overview_2.php?empfaenger_id=$id&empfaenger_rang=$rang'>
                    $name "; 
                    if($rang == 'lehrer'){
                        echo "(Lehrer)";
                    } elseif($rang == 'schueler'){
                        echo "(Schüler)";
                    }  else{
                        
                    }
                    echo "
                </a>
            </p>";
        }
        echo "</div>";
    } else {
        echo "<p>Keine Ergebnisse gefunden.</p>";
    }
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['rang'])) {
    header("Location: index.php");
    exit();
}
?>


</div>


</body>
</html>