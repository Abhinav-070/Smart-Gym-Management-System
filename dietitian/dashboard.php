<?php
session_start();

require_once "../config/database.php";

if (!isset($_SESSION["dietitian_id"])) {
    header("Location: login.php");
    exit;
}

$stmt = $conn->prepare(
    "SELECT name, email, phone FROM dietitians WHERE id = ?"
);
$stmt->execute([$_SESSION["dietitian_id"]]);
$dietitian = $stmt->fetch();

if (!$dietitian) {
    session_unset();
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

    <title>Dietitian Dashboard | Smart Gym</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .dashboard-container {
            padding: 50px 5%;
            min-height: 85vh;
        }

        .welcome {
            margin-bottom: 35px;
        }

        .welcome h1 {
            color: #e63946;
            margin-bottom: 10px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 25px;
        }

        .dashboard-card {
            background: #1c1c1c;
            padding: 30px;
            border-radius: 10px;
            border: 1px solid #333;
        }

        .dashboard-card h3 {
            color: #e63946;
            margin-bottom: 15px;
        }

        .dashboard-card p {
            color: #ccc;
            margin-bottom: 20px;
        }

        .dashboard-card a {
            display: inline-block;
            padding: 10px 18px;
            background: #e63946;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .profile-info {
            background: #1c1c1c;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 35px;
        }

        .profile-info p {
            margin: 10px 0;
            color: #ddd;
        }
    </style>
</head>

<body>

<header class="navbar">
    <h2>SMART GYM</h2>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="diet_plans.php">Diet Plans</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="dashboard-container">

    <div class="welcome">
        <h1>Welcome, <?= htmlspecialchars($dietitian["name"]) ?>!</h1>
        <p>Manage your clients' nutrition and diet plans.</p>
    </div>

    <div class="profile-info">
        <h3>My Profile</h3>

        <p>
            <strong>Name:</strong>
            <?= htmlspecialchars($dietitian["name"]) ?>
        </p>

        <p>
            <strong>Email:</strong>
            <?= htmlspecialchars($dietitian["email"]) ?>
        </p>

        <p>
            <strong>Phone:</strong>
            <?= htmlspecialchars($dietitian["phone"]) ?>
        </p>
    </div>

    <div class="dashboard-grid">

        <div class="dashboard-card">
            <h3>Manage Diet Plans</h3>
            <p>Create and manage nutrition plans for gym clients.</p>
            <a href="diet_plans.php">Manage Plans</a>
        </div>

        <div class="dashboard-card">
            <h3>View Clients</h3>
            <p>View registered gym clients.</p>
            <a href="clients.php">View Clients</a>
        </div>

        <div class="dashboard-card">
        <h3>My Profile</h3>
        <p>Update your personal details and password.</p>
        <a href="profile.php">Manage Profile</a>
        </div>

    </div>

</main>

<footer>
    <p>&copy; <?= date("Y") ?> Smart Gym Management System</p>
</footer>

</body>
</html>
