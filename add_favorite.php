<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $location = $_POST['location'];

    // Insert the favorite location into the database
    $query = "INSERT INTO favorite_locations (user_id, location) VALUES (?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $userId, $location);
    $stmt->execute();

    header("Location: index.php");
    exit();
} else {
    header("Location: index.php");
    exit();
}
?>