<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION["client_id"])) {
    header("Location: login.php");
    exit;
}

$client_id = (int) $_SESSION["client_id"];

$stmt = $conn->prepare(
    "SELECT wp.*, t.name AS trainer_name, t.specialization
     FROM workout_plans wp
     JOIN trainers t ON wp.trainer_id = t.id
     WHERE wp.client_id = ?
     ORDER BY wp.created_at DESC"
);

$stmt->execute([$client_id]);
$plans = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Workout Plans | Smart Gym</title>

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
            border-left: 4px solid #e63946;
        }

        .plan-card h2 {
            color: #e63946;
            margin-bottom: 15px;
        }

        .plan-card p {
            color: #ddd;
            margin: 12px 0;
            line-height: 1.6;
        }

        .empty-message {
            padding: 25px;
            background: #1c1c1c;
            border-radius: 8px;
            color: #ddd;
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
        <a href="profile.php">My Profile</a>
        <a href="membership.php">Membership</a>
        <a href="workout_plans.php">Workout Plans</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="container">

    <h1>My Workout Plans</h1>

    <?php if (empty($plans)): ?>

        <div class="empty-message">
            No workout plans have been assigned to you yet.
        </div>

    <?php else: ?>

        <div class="plans-grid">

            <?php foreach ($plans as $plan): ?>

                <div class="plan-card">

                    <h2><?= htmlspecialchars($plan["plan_name"]) ?></h2>

                    <p>
                        <strong>Trainer:</strong>
                        <?= htmlspecialchars($plan["trainer_name"]) ?>
                    </p>

                    <p>
                        <strong>Specialization:</strong>
                        <?= htmlspecialchars($plan["specialization"]) ?>
                    </p>

                    <p>
                        <strong>Description:</strong><br>
                        <?= nl2br(htmlspecialchars($plan["description"])) ?>
                    </p>

                    <p>
                        <strong>Duration:</strong>
                        <?= (int)$plan["duration_weeks"] ?> weeks
                    </p>

                    <p>
                        <strong>Start Date:</strong>
                        <?= htmlspecialchars($plan["start_date"]) ?>
                    </p>

                    <p>
                        <strong>End Date:</strong>
                        <?= htmlspecialchars($plan["end_date"]) ?>
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
