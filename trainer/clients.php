<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION["trainer_id"])) {
    header("Location: login.php");
    exit;
}

$trainer_id = (int) $_SESSION["trainer_id"];

$stmt = $conn->prepare(
    "SELECT c.id, c.name, c.email, c.phone, tc.assigned_at
     FROM trainer_clients tc
     JOIN clients c ON tc.client_id = c.id
     WHERE tc.trainer_id = ?
     ORDER BY tc.assigned_at DESC"
);

$stmt->execute([$trainer_id]);
$clients = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Clients | Smart Gym</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .container {
            padding: 50px 5%;
            min-height: 85vh;
        }

        h1 {
            color: #e63946;
            margin-bottom: 25px;
        }

        .table-container {
            overflow-x: auto;
            background: #1c1c1c;
            border-radius: 8px;
        }

        table {
            width: 100%;
            min-width: 650px;
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
    </style>
</head>

<body>

<header class="navbar">
    <h2>SMART GYM TRAINER</h2>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="clients.php">My Clients</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="container">

    <h1>My Assigned Clients</h1>

    <?php if (empty($clients)): ?>

        <div class="empty-message">
            No clients have been assigned to you yet.
        </div>

    <?php else: ?>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Assigned Date</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($clients as $client): ?>
                        <tr>
                            <td><?= (int)$client["id"] ?></td>
                            <td><?= htmlspecialchars($client["name"]) ?></td>
                            <td><?= htmlspecialchars($client["email"]) ?></td>
                            <td><?= htmlspecialchars($client["phone"]) ?></td>
                            <td><?= htmlspecialchars($client["assigned_at"]) ?></td>
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
