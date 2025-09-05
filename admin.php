<?php
session_start();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADMIN</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="shortcut icon" href="picture.ico" type="image/x-icon">
</head>
<body>
    <div class="profil-container">
        <h1>Hallo ADMIN</h1>
        <form action="schule_hinzufügen.php" method="get">
            <button type="submit" class="action-button">Schulen Hinzufügen</button>
        </form>
        <form action="schueler_hinzufügen.php" method="get">
            <button type="submit" class="action-button">Schüler/Lehrer Hinzufügen</button>
        </form>
        <form action="news_allgemein.php" method="get">
            <button type="submit" class="action-button">News Bearbeiten</button>
        </form>
    </div>

    <form action="logout.php" method="post">
        <button type="submit" class="logout-button">Logout</button>
    </form>

</body>
</html>