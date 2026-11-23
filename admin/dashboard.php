
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
     WHERE start_date <= ? AND end_date >= ?"
);
$active_memberships_stmt->execute([$today, $today]);
$active_memberships = $active_memberships_stmt->fetchColumn();

// Expired memberships
$expired_stmt = $conn->prepare(
    "SELECT COUNT(*) FROM memberships WHERE end_date < ?"
);
$expired_stmt->execute([$today]);
$expired_memberships = $expired_stmt->fetchColumn();

// Upcoming memberships
$upcoming_stmt = $conn->prepare(
    "SELECT COUNT(*) FROM memberships WHERE start_date > ?"
);
$upcoming_stmt->execute([$today]);
$upcoming_memberships = $upcoming_stmt->fetchColumn();

// Pending payments
$pending_payments = $conn->query(
    "SELECT COUNT(*) FROM memberships
     WHERE payment_status = 'Pending'"
)->fetchColumn();

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | Smart Gym</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #090909;
            color: #fff;
            font-family: Arial, sans-serif;
        }

        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .admin-sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 30px 18px;
            background: rgba(12, 12, 12, 0.97);
            border-right: 1px solid #292929;
            z-index: 1000;
            overflow-y: auto;
        }

        .admin-logo {
            color: #e63946;
            font-size: 21px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 40px;
            letter-spacing: 1px;
        }

        .admin-sidebar .sidebar-label {
            color: #777;
            font-size: 11px;
            letter-spacing: 2px;
            margin: 25px 12px 12px;
            text-transform: uppercase;
        }

        .admin-sidebar nav {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .admin-sidebar nav a {
            display: block;
            padding: 13px 15px;
            color: #aaa;
            text-decoration: none;
            border-radius: 7px;
            transition: 0.25s;
            font-size: 14px;
        }

        .admin-sidebar nav a:hover,
        .admin-sidebar nav a.active {
            color: #fff;
            background: rgba(230, 57, 70, 0.15);
            border-left: 3px solid #e63946;
        }

        .admin-sidebar nav a.logout {
            color: #ff6b75;
            margin-top: 20px;
        }

        /* Main content */
        .admin-main {
            margin-left: 250px;
            width: calc(100% - 250px);
            min-height: 100vh;
            padding: 40px;
            padding-bottom: 100px;
            background:
                linear-gradient(rgba(8, 8, 8, 0.91), rgba(8, 8, 8, 0.96)),
                url("../uploads/admin-bg.jpg") center/cover fixed;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 35px;
        }

        .topbar h2 {
            margin: 0;
            font-size: 22px;
        }

        .admin-tag {
            padding: 9px 16px;
            border: 1px solid #383838;
            border-radius: 30px;
            color: #ddd;
            font-size: 13px;
            background: rgba(25, 25, 25, 0.8);
        }

        .welcome {
            padding: 35px;
            margin-bottom: 35px;
            border: 1px solid #303030;
            border-radius: 14px;
            background: linear-gradient(
                120deg,
                rgba(35, 20, 21, 0.95),
                rgba(20, 20, 20, 0.88)
            );
        }

        .welcome h1 {
            margin: 0 0 12px;
            font-size: 30px;
            color: #fff;
        }

        .welcome h1 span {
            color: #e63946;
        }

        .welcome p {
            color: #aaa;
            margin: 0;
        }

        .section-title {
            font-size: 19px;
            margin: 35px 0 20px;
            padding-left: 12px;
            border-left: 3px solid #e63946;
        }

        /* Management cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 18px;
        }

        .dashboard-card {
            display: block;
            min-height: 145px;
            padding: 24px;
            background: rgba(25, 25, 25, 0.94);
            border: 1px solid #303030;
            border-radius: 12px;
            text-decoration: none;
            color: white;
            transition: 0.3s;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            border-color: #e63946;
            background: #202020;
        }

        .card-icon {
            font-size: 25px;
            margin-bottom: 16px;
        }

        .dashboard-card h3 {
            color: #fff;
            font-size: 16px;
            margin: 0 0 10px;
        }

        .dashboard-card p {
            color: #999;
            font-size: 13px;
            line-height: 1.6;
            margin: 0;
        }

        /* Statistics */
        .stat-card {
            display: block;
            padding: 24px;
            text-align: left;
            text-decoration: none;
            color: white;
            background: rgba(25, 25, 25, 0.95);
            border: 1px solid #303030;
            border-top: 3px solid #e63946;
            border-radius: 12px;
            transition: 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            background: #202020;
        }

        .stat-card h3 {
            color: #aaa;
            font-size: 13px;
            font-weight: normal;
            margin: 0 0 15px;
        }

        .stat-number {
            font-size: 34px;
            font-weight: bold;
            color: #e63946;
        }

        .admin-footer {
            position: fixed;
            bottom: 0;
            left: 250px;
            right: 0;
            padding: 15px;
            text-align: center;
            background: rgba(8, 8, 8, 0.96);
            border-top: 1px solid #292929;
            color: #777;
            font-size: 12px;
            z-index: 900;
            backdrop-filter: blur(10px);
        }

        @media (max-width: 900px) {
            .admin-layout {
                display: block;
            }

            .admin-sidebar {
                position: relative;
                width: 100%;
                height: auto;
                padding: 15px;
            }

            .admin-logo {
                margin-bottom: 15px;
            }

            .admin-sidebar .sidebar-label {
                display: none;
            }

            .admin-sidebar nav {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
            }

            .admin-sidebar nav a {
                padding: 10px;
                font-size: 12px;
            }

            .admin-main {
                margin-left: 0;
                width: 100%;
                padding: 25px 5%;
            }

            .admin-footer {
                left: 0;
            }
        }

        @media (max-width: 600px) {
            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .welcome {
                padding: 25px;
            }

            .welcome h1 {
                font-size: 24px;
            }

            .dashboard-cards {
                grid-template-columns: 1fr;
            }

            .stat-number {
                font-size: 28px;
            }
        }
    </style>
</head>

<body>

<div class="admin-layout">

    <aside class="admin-sidebar">

        <div class="admin-logo">SMART GYM</div>

        <div class="sidebar-label">Main Menu</div>

        <nav>
            <a href="dashboard.php" class="active">▦ Dashboard</a>
        </nav>

        <div class="sidebar-label">Management</div>

        <nav>
            <a href="clients.php">♙ Manage Clients</a>
            <a href="memberships.php">▤ Memberships</a>
            <a href="trainers.php">♟ Trainers</a>
            <a href="dietitians.php">✚ Dietitians</a>
            <a href="assign_clients.php">⇄ Assign Clients</a>
            <a href="complaints.php">⚑ Complaints</a>
            <a href="reports.php">▥ Reports</a>
        </nav>

        <div class="sidebar-label">Account</div>

        <nav>
            <a href="logout.php" class="logout">↪ Logout</a>
        </nav>

    </aside>

    <main class="admin-main">

        <div class="topbar">
            <h2>Admin Dashboard</h2>
            <div class="admin-tag"> Administrator</div>
        </div>

        <section class="welcome">
            <h1>Welcome back, <span><?= e($admin_name) ?></span></h1>
            <p>Manage your gym operations and monitor membership activity.</p>
        </section>

        <h2 class="section-title">Gym Management</h2>

        <div class="dashboard-cards">

            <a href="clients.php" class="dashboard-card">
                <div class="card-icon">♙</div>
                <h3>Manage Clients</h3>
                <p>View and manage registered clients.</p>
            </a>

            <a href="memberships.php" class="dashboard-card">
                <div class="card-icon">▤</div>
                <h3>Manage Memberships</h3>
                <p>Manage membership plans and requests.</p>
            </a>

            <a href="trainers.php" class="dashboard-card">
                <div class="card-icon">♟</div>
                <h3>Manage Trainers</h3>
                <p>Add, view and manage gym trainers.</p>
            </a>

            <a href="dietitians.php" class="dashboard-card">
                <div class="card-icon">✚</div>
                <h3>Manage Dietitians</h3>
                <p>Manage gym dietitian accounts.</p>
            </a>

            <a href="assign_clients.php" class="dashboard-card">
                <div class="card-icon">⇄</div>
                <h3>Assign Clients</h3>
                <p>Assign clients to trainers.</p>
            </a>

            <a href="complaints.php" class="dashboard-card">
                <div class="card-icon">⚑</div>
                <h3>Complaints</h3>
                <p>Review client complaints and responses.</p>
            </a>

            <a href="reports.php" class="dashboard-card">
                <div class="card-icon">▥</div>
                <h3>Reports</h3>
                <p>View gym management reports.</p>
            </a>

        </div>

        <h2 class="section-title">Membership Overview</h2>

        <div class="dashboard-cards">

            <a href="memberships.php" class="stat-card">
                <h3>Total Memberships</h3>
                <div class="stat-number"><?= (int)$total_memberships ?></div>
            </a>

            <a href="memberships.php" class="stat-card">
                <h3>Active Memberships</h3>
                <div class="stat-number"><?= (int)$active_memberships ?></div>
            </a>

            <a href="memberships.php" class="stat-card">
                <h3>Expired Memberships</h3>
                <div class="stat-number"><?= (int)$expired_memberships ?></div>
            </a>

            <a href="memberships.php" class="stat-card">
                <h3>Upcoming Memberships</h3>
                <div class="stat-number"><?= (int)$upcoming_memberships ?></div>
            </a>

            <a href="memberships.php?status=Pending" class="stat-card">
                <h3>Pending Payments</h3>
                <div class="stat-number"><?= (int)$pending_payments ?></div>
            </a>

        </div>

    </main>

</div>

<footer class="admin-footer">
    &copy; <?= date("Y") ?> Smart Gym Management System
</footer>

</body>
</html>
