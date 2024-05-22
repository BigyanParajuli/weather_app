<?php
// Common configuration
session_start();
require_once 'config.php';

// User authentication
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $username = $_SESSION['username'];

    // Fetch user's favorite locations from the database
    $query = "SELECT * FROM favorite_locations WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $favoriteLocations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $userId = null;
    $username = null;
    $favoriteLocations = [];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Weather App</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Weather App</h1>

        <?php if ($userId) : ?>
            <div class="user-info">
                <p>Welcome, <?php echo $username; ?>!</p>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>

            <form method="post" action="index.php" class="weather-form">
                <input type="text" id="location" name="location" placeholder="Enter location" required>
                <button type="submit" class="btn">Get Weather</button>
            </form>

            <button type="button" class="btn" id="geolocation-btn">Get Current Location Weather</button>

            <button type="button" class="btn" id="toggle-favorites-btn">Show Favorite Locations</button>

            <div id="favorite-locations-container" style="display: none;">
                <?php if (!empty($favoriteLocations)) : ?>
                    <div class="favorite-locations">
                        <h3>Favorite Locations</h3>
                        <ul>
                            <?php foreach ($favoriteLocations as $location) : ?>
                                <li>
                                    <a href="index.php?location=<?php echo $location['location']; ?>"><?php echo $location['location']; ?></a>
                                    <form method="post" action="remove_favorite.php" class="remove-form">
                                        <input type="hidden" name="location_id" value="<?php echo $location['id']; ?>">
                                        <button type="submit" class="remove-btn">Remove</button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>

            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['lat']) || isset($_GET['location'])) {
                if (isset($_GET['lat'])) {
                    $latitude = $_GET['lat'];
                    $longitude = $_GET['lon'];
                    $apiUrl = "https://api.openweathermap.org/data/2.5/weather?lat={$latitude}&lon={$longitude}&appid={$apiKey}&units=metric";
                } elseif (isset($_GET['location'])) {
                    $location = $_GET['location'];
                    $apiUrl = "https://api.openweathermap.org/data/2.5/weather?q={$location}&appid={$apiKey}&units=metric";
                } else {
                    $location = $_POST['location'];
                    $apiUrl = "https://api.openweathermap.org/data/2.5/weather?q={$location}&appid={$apiKey}&units=metric";
                }

                $response = file_get_contents($apiUrl);
                $data = json_decode($response, true);

                if ($data['cod'] === 200) {
                    $temperature = $data['main']['temp'];
                    $description = $data['weather'][0]['description'];
                    $iconCode = $data['weather'][0]['icon'];
                    $iconUrl = "https://openweathermap.org/img/wn/{$iconCode}@2x.png";
                    $locationName = $data['name'];

                    $forecastApiUrl = "https://api.openweathermap.org/data/2.5/forecast?lat={$data['coord']['lat']}&lon={$data['coord']['lon']}&appid={$apiKey}&units=metric";
                    $forecastResponse = file_get_contents($forecastApiUrl);
                    $forecastData = json_decode($forecastResponse, true);

                    echo "<div class='weather-result'>";
                    echo "<img src='{$iconUrl}' alt='Weather Icon'>";
                    echo "<h2>Weather in {$locationName}</h2>";
                    echo "<p class='temperature'>{$temperature} °C</p>";
                    echo "<p class='description'>{$description}</p>";

                    // Check if the location is already saved as a favorite
                    $query = "SELECT * FROM favorite_locations WHERE user_id = ? AND location = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("is", $userId, $locationName);
                    $stmt->execute();
                    $favoriteResult = $stmt->get_result();

                    if ($favoriteResult->num_rows === 0) {
                        echo "<form method='post' action='add_favorite.php' class='favorite-form'>";
                        echo "<input type='hidden' name='location' value='{$locationName}'>";
                        echo "<button type='submit' class='btn'>Add to Favorites</button>";
                        echo "</form>";
                    }

                    echo "</div>";

                    echo "<div class='weather-forecast'>";
                    echo "<h2>Weather Forecast</h2>";
                    foreach ($forecastData['list'] as $forecast) {
                        $date = date('Y-m-d H:i:s', $forecast['dt']);
                        $forecastTemperature = $forecast['main']['temp'];
                        $forecastDescription = $forecast['weather'][0]['description'];
                        $forecastIconCode = $forecast['weather'][0]['icon'];
                        $forecastIconUrl = "https://openweathermap.org/img/wn/{$forecastIconCode}@2x.png";

                        echo "<div class='forecast-item'>";
                        echo "<p class='date'>{$date}</p>";
                        echo "<img src='{$forecastIconUrl}' alt='Weather Icon'>";
                        echo "<p class='temperature'>{$forecastTemperature} °C</p>";
                        echo "<p class='description'>{$forecastDescription}</p>";
                        echo "</div>";
                    }
                    echo "</div>";
                } else {
                    echo "<p class='error'>Failed to fetch weather data. Please try again.</p>";
                }
            }
            ?>
        <?php else : ?>
            <div class="auth-form">
                <h2>Login</h2>
                <form method="post" action="login.php">
                    <input type="text" id="username" name="username" placeholder="Username" required>
                    <input type="password" id="password" name="password" placeholder="Password" required>
                    <button type="submit" class="btn">Login</button>
                </form>
                <p>Don't have an account? <a href="register.php" class="register-link">Register here</a>.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
    document.getElementById('geolocation-btn').addEventListener('click', function() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                var latitude = position.coords.latitude;
                var longitude = position.coords.longitude;
                window.location.href = 'index.php?lat=' + latitude + '&lon=' + longitude;
            });
        } else {
            alert("Geolocation is not supported by this browser.");
        }
    });

    document.getElementById('toggle-favorites-btn').addEventListener('click', function() {
        var favoritesContainer = document.getElementById('favorite-locations-container');
        if (favoritesContainer.style.display === 'none') {
            favoritesContainer.style.display = 'block';
            this.textContent = 'Hide Favorite Locations';
        } else {
            favoritesContainer.style.display = 'none';
            this.textContent = 'Show Favorite Locations';
        }
    });
    </script>
</body>
</html>