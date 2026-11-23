<?php
session_start();

require_once "../config/database.php";

if (!isset($_SESSION["client_id"])) {
    header("Location: login.php");
    exit;
}

$client_id = $_SESSION["client_id"];

$stmt = $conn->prepare(
    "SELECT dp.*, d.name AS dietitian_name
     FROM diet_plans dp
     INNER JOIN dietitians d ON dp.dietitian_id = d.id
     WHERE dp.client_id = ?
     ORDER BY dp.start_date DESC"
);

$stmt->execute([$client_id]);
$plans = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Diet Plans | Smart Gym</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .container {
            padding: 50px 5%;
            min-height: 85vh;
        }

        h1 {
            color: #e63946;
            margin-bottom: 30px;
        }

        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .plan-card {
            background: #1c1c1c;
            padding: 25px;
            border-radius: 10px;
            border: 1px solid #333;
        }

        .plan-card h2 {
            color: #e63946;
            margin-bottom: 15px;
        }

        .plan-card p {
            color: #ddd;
            margin: 12px 0;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .description {
            white-space: pre-wrap;
        }

        .empty-message {
            background: #1c1c1c;
            padding: 25px;
            border-radius: 8px;
            color: #ccc;
        }

        .back-link {
            display: inline-block;
            margin-top: 30px;
            color: #e63946;
            text-decoration: none;
        }
    </style>
</head>

<body>

<header class="navbar">
    <h2>SMART GYM</h2>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="diet_plans.php">My Diet Plans</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="container">

    <h1>My Diet Plans</h1>

    <?php if (empty($plans)): ?>

        <div class="empty-message">
            No diet plans have been assigned to you yet.
        </div>

    <?php else: ?>

        <div class="plans-grid">

            <?php foreach ($plans as $plan): ?>

                <div class="plan-card">

                    <h2><?= htmlspecialchars($plan["plan_name"]) ?></h2>

                    <p>
                        <strong>Dietitian:</strong>
                        <?= htmlspecialchars($plan["dietitian_name"]) ?>
                    </p>

                    <p>
                        <strong>Daily Calories:</strong>
                        <?= $plan["daily_calories"] !== null
                            ? (int)$plan["daily_calories"] . " kcal"
                            : "Not specified" ?>
                    </p>

                    <p>
                        <strong>Start Date:</strong>
                        <?= htmlspecialchars($plan["start_date"]) ?>
                    </p>

                    <p>
                        <strong>End Date:</strong>
                        <?= htmlspecialchars($plan["end_date"]) ?>
                    </p>

                    <p><strong>Diet Instructions:</strong></p>

                    <p class="description">
                        <?= htmlspecialchars($plan["description"]) ?>
                    </p>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

    <a href="dashboard.php" class="back-link">
        ← Back to Dashboard
    </a>

</main>

<footer>
    <p>&copy; <?= date("Y") ?> Smart Gym Management System</p>
</footer>

</body>
</html>
