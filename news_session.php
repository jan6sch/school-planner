<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['news_kategorie'])) {
    $_SESSION['news_kategorie'] = $_POST['news_kategorie'];
    header("Location: news.php");
    exit;
}
?>
