<?php
session_start();

require_once "../config/database.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$admin_name = $_SESSION["admin_name"] ?? "Admin";

$today = date("Y-m-d");

// Total memberships
$total_memberships = $conn->query(
    "SELECT COUNT(*) FROM memberships"
)->fetchColumn();

// Active memberships
$active_memberships_stmt = $conn->prepare(
    "SELECT COUNT(*) FROM memberships
     WHERE start_date <= ?
     AND end_date >= ?"
);
$active_memberships_stmt->execute([$today, $today]);
$active_memberships = $active_memberships_stmt->fetchColumn();

// Expired memberships
$expired_stmt = $conn->prepare(
    "SELECT COUNT(*) FROM memberships
     WHERE end_date < ?"
);
$expired_stmt->execute([$today]);
$expired_memberships = $expired_stmt->fetchColumn();

// Upcoming memberships
$upcoming_stmt = $conn->prepare(
    "SELECT COUNT(*) FROM memberships
     WHERE start_date > ?"
);
$upcoming_stmt->execute([$today]);
$upcoming_memberships = $upcoming_stmt->fetchColumn();

// Pending payments
$pending_payments = $conn->query(
    "SELECT COUNT(*) FROM memberships
     WHERE payment_status = 'Pending'"
)->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | Smart Gym</title>

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

        .section-title {
            color: #e63946;
            margin: 35px 0 20px;
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
            background: #252525;
        }

        .dashboard-card h3 {
            color: #e63946;
            margin-bottom: 12px;
        }

        .dashboard-card p {
            color: #ccc;
        }

        .stat-card {
            display: block;
            padding: 25px;
            background: #1c1c1c;
            border-radius: 10px;
            border-top: 3px solid #e63946;
            text-align: center;
            color: white;
        }

        .stat-card h3 {
            color: #ccc;
            font-size: 15px;
            margin-bottom: 12px;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #e63946;
        }

.stat-card.pending,
.stat-card.active,
.stat-card.expired,
.stat-card.upcoming {
    border-top-color: #e63946;
}

.stat-card.pending .stat-number,
.stat-card.active .stat-number,
.stat-card.expired .stat-number,
.stat-card.upcoming .stat-number {
    color: #e63946;
}

.stat-card {
    text-decoration: none;
    transition: 0.3s;
}

.stat-card:hover {
    transform: translateY(-5px);
    background: #252525;
}
        @media (max-width: 600px) {
            .dashboard {
                padding: 35px 5%;
            }

            .stat-card {
                padding: 20px;
            }

            .stat-number {
                font-size: 26px;
            }
        }

            .stat-card {
                text-decoration: none;
        }
    </style>
</head>

<body>

<header class="navbar">
    <h2>SMART GYM ADMIN</h2>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="dashboard">

    <div class="welcome">
        <h1>Welcome, <?= htmlspecialchars($admin_name) ?>!</h1>
        <p>Manage your gym operations from here.</p>
    </div>



    <!-- Admin Management -->
    <h2 class="section-title">Gym Management</h2>

    <div class="dashboard-cards">

        <a href="clients.php" class="dashboard-card">
            <h3>Manage Clients</h3>
            <p>View and manage registered clients.</p>
        </a>

        <a href="memberships.php" class="dashboard-card">
            <h3>Manage Memberships</h3>
            <p>Assign and manage membership plans.</p>
        </a>

        <a href="trainers.php" class="dashboard-card">
            <h3>Manage Trainers</h3>
            <p>Add, view and manage gym trainers.</p>
        </a>

        <a href="dietitians.php" class="dashboard-card">
            <h3>Manage Dietitians</h3>
            <p>Add and manage gym dietitian accounts.</p>
        </a>

        <a href="assign_clients.php" class="dashboard-card">
            <h3>Assign Clients</h3>
            <p>Assign clients to trainers and manage assignments.</p>
        </a>

        <a href="reports.php" class="dashboard-card">
            <h3>Reports</h3>
            <p>View gym management reports.</p>
        </a>

    </div>

<!-- Membership Statistics -->
<h2 class="section-title">Membership Overview</h2>

<div class="dashboard-cards">

    <a href="memberships.php" class="stat-card">
        <h3>Total Memberships</h3>
        <div class="stat-number">
            <?= (int)$total_memberships ?>
        </div>
    </a>

    <a href="memberships.php" class="stat-card active">
        <h3>Active Memberships</h3>
        <div class="stat-number">
            <?= (int)$active_memberships ?>
        </div>
    </a>

    <a href="memberships.php" class="stat-card expired">
        <h3>Expired Memberships</h3>
        <div class="stat-number">
            <?= (int)$expired_memberships ?>
        </div>
    </a>

    <a href="memberships.php" class="stat-card upcoming">
        <h3>Upcoming Memberships</h3>
        <div class="stat-number">
            <?= (int)$upcoming_memberships ?>
        </div>
    </a>

    <a href="memberships.php?status=Pending" class="stat-card pending">
        <h3>Pending Payments</h3>
        <div class="stat-number">
            <?= (int)$pending_payments ?>
        </div>
    </a>

</div>

</main>

<footer>
    <p>&copy; <?= date("Y") ?> Smart Gym Management System</p>
</footer>

</body>
</html>