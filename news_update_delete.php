<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News Bearbeiten/Löschen</title>
    <link rel="stylesheet" href="educonnect.css">
    <link rel="shortcut icon" href="picture.ico" type="image/x-icon">
</head>
<body>
<?php
    session_start();
    $rang1 = $_SESSION['rang1'];
    if($rang1 === '7'){
        echo"<a href='admin.php'>Zurück zur Adminseite</a>";
    }
    ?>
    <h1>News Bearbeiten/Löschen</h1>
    <div class="nav-box">
        <a href="news_allgemein.php">News Allgemein</a>
        <a href="news_land.php">News Land</a>
        <a href="news_stufe.php">News Stufe</a>
        <a href="news_update_delete.php" class="active">News Bearbeiten/Löschen</a>
    </div>

    <?php

    if (!isset($_SESSION['rang1']) || $_SESSION['rang1'] < 6 ) {
        header("Location: index.php");
        exit();
    }

    $conn = new mysqli("localhost", "root", "", "educonnect");
    mysqli_set_charset($conn, "utf8mb4");

    if ($conn->connect_error) {
        die("Verbindung zur Datenbank fehlgeschlagen: " . $conn->connect_error);
    }

     
    if (isset($_POST['delete'])) {
        $news_id = $_POST['news_id'];
        $news_type = $_POST['news_type'];

         
        $sql_delete = "DELETE FROM $news_type WHERE news_id = $news_id";
        if ($conn->query($sql_delete) === TRUE) {
            echo "<p>Die Nachricht wurde erfolgreich gelöscht.</p>";
        } else {
            echo "Fehler beim Löschen der Nachricht: " . $conn->error;
        }
    }

     
    if (isset($_POST['edit'])) {
        $news_id = $_POST['news_id'];
        $news_type = $_POST['news_type'];

         
        $sql_edit = "SELECT * FROM $news_type WHERE news_id = $news_id";
        $result_edit = $conn->query($sql_edit);

        if ($result_edit->num_rows > 0) {
            $row_edit = $result_edit->fetch_assoc();
            echo "<h2>Nachricht bearbeiten</h2>";
            echo "<form action='' method='POST'>";
            echo "<input type='hidden' name='news_id' value='" . $row_edit['news_id'] . "'>";
            echo "<input type='hidden' name='news_type' value='" . $news_type . "'>";
            echo "<label for='titel'>Titel:</label>";
            echo "<input type='text' name='titel' value='" . $row_edit['titel'] . "' required>";
            echo "<label for='inhalt'>Inhalt:</label>";
            echo "<textarea name='inhalt' required>" . $row_edit['inhalt'] . "</textarea>";
            echo "<label for='start_datum'>Startdatum:</label>";
            echo "<input type='date' name='start_datum' value='" . $row_edit['start_datum'] . "' required>";
            echo "<label for='end_datum'>Enddatum:</label>";
            echo "<input type='date' name='end_datum' value='" . $row_edit['end_datum'] . "' required>";
            echo "<input type='submit' name='update' value='Aktualisieren'>";
            echo "<input type='submit' name='set_wichtigkeit' value='Nachricht auf Wichtig setzen'>";
            echo "</form>";
        } else {
            echo "<p>Nachricht nicht gefunden.</p>";
        }
    }

     
    if (isset($_POST['update'])) {
        $news_id = $_POST['news_id'];
        $news_type = $_POST['news_type'];
        $titel = $_POST['titel'];
        $inhalt = $_POST['inhalt'];
        $start_datum = $_POST[' start_datum'];
        $end_datum = $_POST['end_datum'];

         
        $sql_update = "UPDATE $news_type SET titel = '$titel', inhalt = '$inhalt', start_datum = '$start_datum', end_datum = '$end_datum' WHERE news_id = $news_id";
        if ($conn->query($sql_update) === TRUE) {
            echo "Die Nachricht wurde erfolgreich aktualisiert.";
        } else {
            echo "Fehler beim Aktualisieren der Nachricht: " . $conn->error;
        }
    }

     
    if (isset($_POST['set_wichtigkeit'])) {
        $news_id = $_POST['news_id'];
        $news_type = $_POST['news_type'];

         
        $sql_reset = "UPDATE $news_type SET wichtigkeit = 0 WHERE wichtigkeit = 1";
        $conn->query($sql_reset);

         
        $sql_set_wichtigkeit = "UPDATE $news_type SET wichtigkeit = 1 WHERE news_id = $news_id";
        if ($conn->query($sql_set_wichtigkeit) === TRUE) {
            echo "Die Wichtigkeit wurde erfolgreich auf 1 gesetzt.";
        } else {
            echo "Fehler beim Setzen der Wichtigkeit: " . $conn->error;
        }
    }
    ?>

    <h2>Allgemeine Nachrichten</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Titel</th>
            <th>Aktionen</th>
        </tr>
        <?php
        $sql_allgemein = "SELECT * FROM news_allgemein";
        $result_allgemein = $conn->query($sql_allgemein);

        if ($result_allgemein->num_rows > 0) {
            while ($row = $result_allgemein->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['news_id'] . "</td>";
                echo "<td>" . $row['titel'] . "</td>";
                echo "<td>
                        <form action='' method='POST' style='display:inline;'>
                            <input type='hidden' name='news_id' value='" . $row['news_id'] . "'>
                            <input type='hidden' name='news_type' value='news_allgemein'>
                            <input type='submit' name='edit' value='Bearbeiten'>
                        </form>
                        <form action='' method='POST' style='display:inline;'>
                            <input type='hidden' name='news_id' value='" . $row['news_id'] . "'>
                            <input type='hidden' name='news_type' value='news_allgemein'>
                            <input type='submit' name='delete' value='Löschen' onclick='return confirm(\"Sind Sie sicher, dass Sie diese Nachricht löschen möchten?\");'>
                        </form>
                      </td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='3'>Keine Nachrichten gefunden.</td></tr>";
        }
        ?>
    </table>

    <h2>Stufen Nachrichten</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Titel</th>
            <th>Aktionen</th>
        </tr>
        <?php
        $sql_stufe = "SELECT * FROM news_stufe";
        $result_stufe = $conn->query($sql_stufe);

        if ($result_stufe->num_rows > 0) {
            while ($row = $result_stufe->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['news_id'] . "</td>";
                echo "<td>" . $row['titel'] . "</td>";
                echo "<td>
                        <form action='' method='POST' style='display:inline;'>
                            <input type='hidden' name='news_id' value='" . $row['news_id'] . "'>
                            <input type='hidden' name='news_type' value='news_stufe'>
                            <input type='submit' name='edit' value='Bearbeiten'>
                        </form>
                        <form action='' method='POST' style='display:inline;'>
                            <input type='hidden' name='news_id' value='" . $row['news_id'] . "'>
                            <input type='hidden' name='news_type' value='news_stufe'>
                            <input type='submit' name='delete' value='Löschen' onclick='return confirm(\"Sind Sie sicher, dass Sie diese Nachricht löschen möchten?\");'>
                        </form>
                      </td>";
                echo "</ tr>";
            }
        } else {
            echo "<tr><td colspan='3'>Keine Nachrichten gefunden.</td></tr>";
        }
        ?>
    </table>

    <h2>Land Nachrichten</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Titel</th>
            <th>Aktionen</th>
        </tr>
        <?php
        $sql_land = "SELECT * FROM news_land";
        $result_land = $conn->query($sql_land);

        if ($result_land->num_rows > 0) {
            while ($row = $result_land->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['news_id'] . "</td>";
                echo "<td>" . $row['titel'] . "</td>";
                echo "<td>
                        <form action='' method='POST' style='display:inline;'>
                            <input type='hidden' name='news_id' value='" . $row['news_id'] . "'>
                            <input type='hidden' name='news_type' value='news_land'>
                            <input type='submit' name='edit' value='Bearbeiten'>
                        </form>
                        <form action='' method='POST' style='display:inline;'>
                            <input type='hidden' name='news_id' value='" . $row['news_id'] . "'>
                            <input type='hidden' name='news_type' value='news_land'>
                            <input type='submit' name='delete' value='Löschen' onclick='return confirm(\"Sind Sie sicher, dass Sie diese Nachricht löschen möchten?\");'>
                        </form>
                      </td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='3'>Keine Nachrichten gefunden.</td></tr>";
        }
        ?>
    </table>

    <?php
    $conn->close();
    ?>
</body>
    <?php 
        if($_SESSION['rang1'] === '7'){
            
        }else{
            include 'navigation.php';
        }
    ?>
</html>