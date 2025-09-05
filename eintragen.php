<?php
session_start();
$lehrer_id = $_SESSION['user_id'];
$conn = new mysqli('localhost', 'root', '', 'educonnect');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($_POST['fehlzeiten'] as $schueler_id => $zeit) {
        if ($zeit > 0) {
            $sql = "INSERT INTO schueler_fehlzeiten (schueler_id, zeit, grund, datum) VALUES (?, ?, 'Abwesend', CURDATE())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $schueler_id, $zeit);
            $stmt->execute();
        }
    }
    echo "Fehlzeiten erfolgreich eingetragen.";
}
?>