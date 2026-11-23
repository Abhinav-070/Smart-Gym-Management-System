<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION["client_id"])) {
    header("Location: login.php");
    exit;
}

$client_id = (int)$_SESSION["client_id"];

$stmt = $conn->prepare(
    "SELECT pr.*, t.name AS trainer_name
     FROM progress_records pr
     JOIN trainers t ON pr.trainer_id = t.id
     WHERE pr.client_id = ?
     ORDER BY pr.recorded_at DESC, pr.id DESC"
);

$stmt->execute([$client_id]);
$records = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Progress | Smart Gym</title>

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

        .table-container {
            overflow-x: auto;
            background: #1c1c1c;
            border-radius: 8px;
        }

        table {
            width: 100%;
            min-width: 850px;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #333;
        }

        th {
            background: #080808;
            color: #e63946;
        }

        td {
            color: #ddd;
        }

        .empty-message {
            padding: 25px;
            background: #1c1c1c;
            border-radius: 8px;
            color: #ddd;
        }

        .back-link {
            display: inline-block;
            margin-top: 25px;
            color: #e63946;
            text-decoration: none;
        }

        .progress-info {
            margin-bottom: 25px;
            color: #ccc;
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
        <a href="progress.php">My Progress</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="container">

    <h1>My Fitness Progress</h1>

    <p class="progress-info">
        Track your fitness journey through records added by your trainer.
    </p>

    <?php if (empty($records)): ?>

        <div class="empty-message">
            No progress records available yet.
        </div>

    <?php else: ?>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Weight (kg)</th>
                        <th>Height (m)</th>
                        <th>BMI</th>
                        <th>Trainer</th>
                        <th>Notes</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($records as $record): ?>
                        <tr>
                            <td><?= htmlspecialchars($record["recorded_at"]) ?></td>

                            <td><?= htmlspecialchars($record["weight"]) ?></td>

                            <td><?= htmlspecialchars($record["height"]) ?></td>

                            <td><?= htmlspecialchars($record["bmi"]) ?></td>

                            <td><?= htmlspecialchars($record["trainer_name"]) ?></td>

                            <td>
                                <?= nl2br(htmlspecialchars($record["notes"] ?? "")) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
