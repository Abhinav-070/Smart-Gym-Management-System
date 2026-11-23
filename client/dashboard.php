<?php
session_start();

if (!isset($_SESSION["client_id"])) {
    header("Location: login.php");
    exit;
}

$client_name = $_SESSION["client_name"] ?? "Client";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Client Dashboard | Smart Gym</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .dashboard {
            min-height: 85vh;
            padding: 60px 8%;
        }

        .welcome {
            margin-bottom: 35px;
        }

        .welcome h1 {
            color: #e63946;
            margin-bottom: 10px;
        }

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 25px;
        }

        .dashboard-card {
            background: #1c1c1c;
            padding: 30px;
            border-radius: 10px;
            border-left: 4px solid #e63946;
        }

        .dashboard-card h3 {
            color: #e63946;
            margin-bottom: 12px;
        }

        .dashboard-card p {
            color: #ccc;
        }

        .logout {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 25px;
            background: #e63946;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .logout:hover {
            background: #c1121f;
        }
    </style>
</head>

<body>

<header class="navbar">
    <h2>SMART GYM</h2>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="dashboard">

    <div class="welcome">
        <h1>Welcome, <?= htmlspecialchars($client_name) ?>!</h1>
        <p>Your fitness journey starts here.</p>
    </div>

    <div class="dashboard-cards">

        <a href="profile.php" class="dashboard-card">
        <h3>My Profile</h3>
        <p>View and manage your personal information.</p>
        </a>


        <a href="membership.php" class="dashboard-card">
        <h3>My Membership</h3>
        <p>View your gym membership details.</p>
        </a>


            <a href="workout_plans.php" class="dashboard-card">
            <h3>Workout Plans</h3>
            <p>Check your workout schedule.</p>
            </a>


            <a href="diet_plans.php" class="dashboard-card">
            <h3>Diet Plans</h3>
            <p>View your diet and nutrition plans.</p>
            </a>

            <a href="progress.php" class="dashboard-card">
            <h3>My Progress</h3>
            <p>Track your weight, BMI and fitness improvements.</p>
            </a>

            <a href="diet_plans.php" class="dashboard-card">
            <h3>My Diet Plans</h3>
            <p>View your diet plans and nutrition instructions.</p>
            </a>

    </div>

    <a href="logout.php" class="logout">Logout</a>

</main>

<footer>
    <p>&copy; <?= date("Y") ?> Smart Gym Management System</p>
</footer>

</body>
</html>