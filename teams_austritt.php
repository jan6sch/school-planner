<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Austritt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .TAustrittContainer {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .TAustrittForm {
            display: flex;
            flex-direction: column;
            margin-bottom: 15px;
        }
        .TAustrittForm input[type="submit"] {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        .TAustrittForm input[type="submit"]:hover {
            background-color: #c82333;
        }
        @media (max-width: 600px) {
            .TAustrittContainer {
                padding: 10px;
            }
            .TAustrittForm input[type="submit"] {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="TAustrittContainer">
        <h1>Teams, aus denen Sie austreten können</h1>
        <?php
        session_start();

        // Verbindung zur Datenbank herstellen
        $conn = new mysqli('localhost', 'root', '', 'educonnect');
        mysqli_set_charset($conn, "utf8mb4");
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $user_id = $_SESSION['user_id'];
        $rang = $_SESSION['rang'];

        // Abfrage der Teams, aus denen der Schüler austreten kann
        $sql = "SELECT t.team_id, t.team_name FROM beitritt b JOIN teams t ON b.team_id = t.team_id WHERE b.user_id = $user_id AND b.rang = '$rang'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<form action='austritt_kurs.php' method='post' class='TAustrittForm'>";
                echo "<input type='hidden' name='team_id' value='".$row['team_id']."'>";
                echo "<p>Team: ".$row['team_name']."</p>";
                echo "<input type='submit' value ='Austreten'>";
                echo "</form>";
            }
        } else {
            echo "<p>Sie sind in keinem Team.</p>";
        }

        $conn->close();
        ?>
    </div>
</body>
</html>