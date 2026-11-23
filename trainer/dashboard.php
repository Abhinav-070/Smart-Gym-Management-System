<?php
session_start();

require_once "../config/database.php";

if (!isset($_SESSION["trainer_id"])) {
    header("Location: login.php");
    exit;
}

$trainer_id = $_SESSION["trainer_id"];

$stmt = $conn->prepare(
    "SELECT name, email, phone, specialization
     FROM trainers WHERE id = ?"
);

$stmt->execute([$trainer_id]);
$trainer = $stmt->fetch();

if (!$trainer) {
    $_SESSION = [];
    session_destroy();
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Trainer Dashboard | Smart Gym</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .dashboard {
            padding: 60px 8%;
            min-height: 85vh;
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
            display: block;
            padding: 30px;
            background: #1c1c1c;
            border-radius: 10px;
            border-left: 4px solid #e63946;
            color: white;
            text-decoration: none;
            transition: 0.3s;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
        }

        .dashboard-card h3 {
            color: #e63946;
            margin-bottom: 12px;
        }

        .dashboard-card p {
            color: #ccc;
        }

        .profile-details {
            background: #1c1c1c;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 35px;
        }

        .profile-details p {
            margin: 12px 0;
            color: #ccc;
        }
    </style>
</head>

<body>

<header class="navbar">
    <h2>SMART GYM TRAINER</h2>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="dashboard">

    <div class="welcome">
        <h1>Welcome, <?= htmlspecialchars($trainer["name"]) ?>!</h1>
        <p>Manage your training activities here.</p>
    </div>

    <div class="profile-details">
        <h2>Trainer Information</h2>

        <p><strong>Name:</strong>
            <?= htmlspecialchars($trainer["name"]) ?></p>

        <p><strong>Email:</strong>
            <?= htmlspecialchars($trainer["email"]) ?></p>

        <p><strong>Phone:</strong>
            <?= htmlspecialchars($trainer["phone"]) ?></p>

        <p><strong>Specialization:</strong>
            <?= htmlspecialchars($trainer["specialization"]) ?></p>
    </div>

    <div class="dashboard-cards">

        <a href="clients.php" class="dashboard-card">
            <h3>My Clients</h3>
            <p>View your assigned gym clients.</p>
        </a>

        <a href="workout_plans.php" class="dashboard-card">
            <h3>Workout Plans</h3>
            <p>Manage client workout plans.</p>
        </a>

        <a href="progress.php" class="dashboard-card">
            <h3>Progress Tracking</h3>
            <p>Monitor client fitness progress.</p>
        </a>

    </div>

</main>

<footer>
    <p>&copy; <?= date("Y") ?> Smart Gym Management System</p>
</footer>

</body>
</html>