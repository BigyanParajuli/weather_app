<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $locationId = $_POST['location_id'];

    // Remove the favorite location from the database
    $query = "DELETE FROM favorite_locations WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $locationId, $userId);
    $stmt->execute();

    header("Location: index.php");
    exit();
} else {
    header("Location: index.php");
    exit();
}
?>