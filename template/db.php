<?php
$servername = "lrgs.ftsm.ukm.my";
$username = "a194789";
$password = "littlepinksheep";
$dbname = "a194789";

// Create connection
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    //echo "Connection successful"; // For debugging
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit(); // Ensure the script stops if the connection fails
}

// Place your function here, inside PHP tags
function calculateGrade($total) {
    if ($total >= 85) return ['A', 4.00];
    elseif ($total >= 75) return ['A-', 3.67];
    elseif ($total >= 65) return ['B+', 3.33];
    elseif ($total >= 60) return ['B', 3.00];
    elseif ($total >= 55) return ['B-', 2.67];
    elseif ($total >= 50) return ['C+', 2.33];
    elseif ($total >= 45) return ['C', 2.00];
    elseif ($total >= 40) return ['C-', 1.67];
    elseif ($total >= 35) return ['D+', 1.33];
    elseif ($total >= 30) return ['D', 1.00];
    else return ['E', 0.00];
}
?>