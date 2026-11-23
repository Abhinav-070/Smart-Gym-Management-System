<?php
session_start();

require_once "../config/database.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

// Dashboard statistics
$statistics = [];

$statistics["clients"] = $conn->query(
    "SELECT COUNT(*) FROM clients"
)->fetchColumn();

$statistics["trainers"] = $conn->query(
    "SELECT COUNT(*) FROM trainers"
)->fetchColumn();

$statistics["dietitians"] = $conn->query(
    "SELECT COUNT(*) FROM dietitians"
)->fetchColumn();

$statistics["memberships"] = $conn->query(
    "SELECT COUNT(*) FROM memberships
     WHERE start_date <= CURDATE()
     AND end_date >= CURDATE()"
)->fetchColumn();

$statistics["workout_plans"] = $conn->query(
    "SELECT COUNT(*) FROM workout_plans"
)->fetchColumn();

$statistics["diet_plans"] = $conn->query(
    "SELECT COUNT(*) FROM diet_plans"
)->fetchColumn();

$statistics["revenue"] = $conn->query(
    "SELECT COALESCE(SUM(amount), 0)
     FROM memberships
     WHERE payment_status = 'Paid'"
)->fetchColumn();

// Recent client registrations
$recent_clients = $conn->query(
    "SELECT name, email, created_at
     FROM clients
     ORDER BY created_at DESC
     LIMIT 5"
)->fetchAll();

// Recent memberships
$recent_memberships = $conn->query(
    "SELECT c.name AS client_name, m.plan_name,
            m.amount, m.payment_status, m.created_at
     FROM memberships m
     INNER JOIN clients c ON m.client_id = c.id
     ORDER BY m.created_at DESC
     LIMIT 5"
)->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gym Reports | Smart Gym</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .container {
            padding: 45px 5%;
            min-height: 85vh;
        }

        h1, h2 {
            color: #e63946;
            margin-bottom: 25px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 45px;
        }

        .stat-card {
            background: #1c1c1c;
            border: 1px solid #333;
            border-radius: 10px;
            padding: 25px;
        }

        .stat-card h3 {
            color: #aaa;
            font-size: 15px;
            margin-bottom: 15px;
        }

        .stat-card p {
            color: #e63946;
            font-size: 28px;
            font-weight: bold;
        }

        .table-container {
            overflow-x: auto;
            background: #1c1c1c;
            border-radius: 8px;
            margin-bottom: 40px;
        }

        table {
            width: 100%;
            min-width: 600px;
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

        .back-link {
            display: inline-block;
            margin-top: 15px;
            color: #e63946;
            text-decoration: none;
        }
    </style>
</head>

<body>

<header class="navbar">
    <h2>SMART GYM ADMIN</h2>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="clients.php">Clients</a>
        <a href="memberships.php">Memberships</a>
        <a href="reports.php">Reports</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="container">

    <h1>Gym Reports</h1>

    <div class="stats-grid">

        <div class="stat-card">
            <h3>Total Clients</h3>
            <p><?= (int)$statistics["clients"] ?></p>
        </div>

        <div class="stat-card">
            <h3>Total Trainers</h3>
            <p><?= (int)$statistics["trainers"] ?></p>
        </div>

        <div class="stat-card">
            <h3>Total Dietitians</h3>
            <p><?= (int)$statistics["dietitians"] ?></p>
        </div>

        <div class="stat-card">
            <h3>Active Memberships</h3>
            <p><?= (int)$statistics["memberships"] ?></p>
        </div>

        <div class="stat-card">
            <h3>Workout Plans</h3>
            <p><?= (int)$statistics["workout_plans"] ?></p>
        </div>

        <div class="stat-card">
            <h3>Diet Plans</h3>
            <p><?= (int)$statistics["diet_plans"] ?></p>
        </div>

        <div class="stat-card">
            <h3>Total Paid Membership Revenue</h3>
            <p>₹<?= number_format((float)$statistics["revenue"], 2) ?></p>
        </div>

    </div>

    <h2>Recent Client Registrations</h2>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Client Name</th>
                    <th>Email</th>
                    <th>Registration Date</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($recent_clients)): ?>
                    <tr>
                        <td colspan="3">No client registrations found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_clients as $client): ?>
                        <tr>
                            <td><?= htmlspecialchars($client["name"]) ?></td>
                            <td><?= htmlspecialchars($client["email"]) ?></td>
                            <td><?= htmlspecialchars($client["created_at"]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <h2>Recent Memberships</h2>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Plan</th>
                    <th>Amount</th>
                    <th>Payment Status</th>
                    <th>Date</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($recent_memberships)): ?>
                    <tr>
                        <td colspan="5">No memberships found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_memberships as $membership): ?>
                        <tr>
                            <td><?= htmlspecialchars($membership["client_name"]) ?></td>
                            <td><?= htmlspecialchars($membership["plan_name"]) ?></td>
                            <td>₹<?= number_format((float)$membership["amount"], 2) ?></td>
                            <td><?= htmlspecialchars($membership["payment_status"]) ?></td>
                            <td><?= htmlspecialchars($membership["created_at"]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <a href="dashboard.php" class="back-link">
        ← Back to Dashboard
    </a>

</main>

<footer>
    <p>&copy; <?= date("Y") ?> Smart Gym Management System</p>
</footer>

</body>
</html>
