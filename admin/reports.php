
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

function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gym Reports | Smart Gym</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #090909;
            color: #f5f5f5;
            font-family: Arial, sans-serif;
        }

        .admin-layout {
            min-height: 100vh;
        }

        /* Sidebar */

        .admin-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            padding: 28px 18px;
            background: #111;
            border-right: 1px solid #292929;
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar-brand {
            color: #fff;
            font-size: 23px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 35px;
        }

        .sidebar-brand span {
            color: #e63946;
        }

        .sidebar-menu {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .sidebar-menu a {
            display: block;
            padding: 13px 15px;
            color: #bbb;
            text-decoration: none;
            border-radius: 7px;
            transition: 0.2s;
            font-size: 14px;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: #251416;
            color: #fff;
            border-left: 3px solid #e63946;
        }

        .sidebar-menu a.logout {
            color: #ff7777;
            margin-top: 15px;
        }

        /* Main content */

        .admin-main {
            margin-left: 250px;
            width: calc(100% - 250px);
            min-height: 100vh;
            padding: 35px;
            padding-bottom: 100px;
            background:
                linear-gradient(rgba(0,0,0,0.88), rgba(0,0,0,0.94)),
                url("../uploads/admin-bg.jpg") center/cover fixed;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
        }

        .page-header h1 {
            margin: 0 0 8px;
            font-size: 29px;
            color: #fff;
        }

        .page-header p {
            margin: 0;
            color: #999;
            font-size: 14px;
        }

        .back-link {
            display: inline-block;
            padding: 11px 17px;
            background: #202020;
            color: #fff;
            border: 1px solid #383838;
            border-radius: 7px;
            text-decoration: none;
            font-size: 13px;
        }

        .back-link:hover {
            background: #e63946;
            border-color: #e63946;
        }

        /* Statistics */

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: #151515;
            border: 1px solid #303030;
            border-radius: 11px;
            padding: 23px;
            transition: 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            border-color: #e63946;
        }

        .stat-card h3 {
            color: #999;
            font-size: 14px;
            font-weight: normal;
            margin: 0 0 15px;
        }

        .stat-card p {
            color: #e63946;
            font-size: 27px;
            font-weight: bold;
            margin: 0;
            overflow-wrap: anywhere;
        }

        .stat-card.revenue {
            grid-column: span 2;
        }

        /* Report tables */

        .report-section {
            margin-bottom: 30px;
            background: #111;
            border: 1px solid #303030;
            border-radius: 12px;
            overflow: hidden;
        }

        .section-header {
            padding: 20px 22px;
            border-bottom: 1px solid #303030;
        }

        .section-header h2 {
            color: #fff;
            font-size: 18px;
            margin: 0;
        }

        .section-header span {
            color: #e63946;
        }

        .table-container {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 600px;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #292929;
            font-size: 13px;
        }

        th {
            background: #1d1d1d;
            color: #e63946;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            white-space: nowrap;
        }

        td {
            color: #ccc;
        }

        tbody tr:hover {
            background: #191919;
        }

        .client-name {
            color: #fff;
            font-weight: bold;
        }

        .payment-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .payment-paid {
            color: #65d68a;
            background: rgba(40,167,69,0.12);
        }

        .payment-pending {
            color: #ffc107;
            background: rgba(255,193,7,0.12);
        }

        .payment-failed {
            color: #ff7777;
            background: rgba(220,53,69,0.12);
        }

        .empty-state {
            padding: 30px;
            text-align: center;
            color: #888;
        }

        /* Footer */

        .admin-footer {
            position: fixed;
            bottom: 0;
            left: 250px;
            width: calc(100% - 250px);
            padding: 15px;
            text-align: center;
            background: rgba(10,10,10,0.96);
            border-top: 1px solid #292929;
            color: #777;
            font-size: 12px;
            z-index: 900;
        }

        /* Responsive */

        @media (max-width: 1000px) {
            .admin-sidebar {
                position: relative;
                width: 100%;
                height: auto;
                padding: 18px;
            }

            .sidebar-brand {
                margin-bottom: 18px;
            }

            .sidebar-menu {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
            }

            .sidebar-menu a {
                padding: 10px 12px;
            }

            .admin-main {
                margin-left: 0;
                width: 100%;
                padding: 25px 18px 100px;
            }

            .admin-footer {
                left: 0;
                width: 100%;
            }
        }

        @media (max-width: 650px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .stat-card.revenue {
                grid-column: span 1;
            }

            .page-header h1 {
                font-size: 24px;
            }

            .admin-main {
                padding: 22px 12px 100px;
            }
        }
    </style>
</head>

<body>

<div class="admin-layout">

    <aside class="admin-sidebar">

        <div class="sidebar-brand">
            SMART <span>GYM</span>
        </div>

        <nav class="sidebar-menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="clients.php">Clients</a>
            <a href="memberships.php">Memberships</a>
            <a href="trainers.php">Trainers</a>
            <a href="dietitians.php">Dietitians</a>
            <a href="assign_clients.php">Assign Clients</a>
            <a href="membership_requests.php">Membership Requests</a>
            <a href="complaints.php">Complaints</a>
            <a href="reports.php" class="active">Reports</a>
            <a href="logout.php" class="logout">Logout</a>
        </nav>

    </aside>

    <main class="admin-main">

        <div class="page-header">
            <div>
                <h1>Gym Reports</h1>
                <p>Overview of gym activity, memberships and revenue.</p>
            </div>

            <a href="dashboard.php" class="back-link">
                Back to Dashboard
            </a>
        </div>

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

            <div class="stat-card revenue">
                <h3>Total Paid Membership Revenue</h3>
                <p>₹<?= number_format((float)$statistics["revenue"], 2) ?></p>
            </div>

        </div>

        <!-- Recent Clients -->

        <section class="report-section">

            <div class="section-header">
                <h2><span>01.</span> Recent Client Registrations</h2>
            </div>

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
                                <td colspan="3" class="empty-state">
                                    No client registrations found.
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($recent_clients as $client): ?>

                                <tr>
                                    <td class="client-name">
                                        <?= e($client["name"]) ?>
                                    </td>

                                    <td><?= e($client["email"]) ?></td>

                                    <td><?= e($client["created_at"]) ?></td>
                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>
                </table>

            </div>

        </section>

        <!-- Recent Memberships -->

        <section class="report-section">

            <div class="section-header">
                <h2><span>02.</span> Recent Memberships</h2>
            </div>

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
                                <td colspan="5" class="empty-state">
                                    No memberships found.
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($recent_memberships as $membership): ?>

                                <?php
                                    $payment_class = "payment-" .
                                        strtolower($membership["payment_status"]);
                                ?>

                                <tr>
                                    <td class="client-name">
                                        <?= e($membership["client_name"]) ?>
                                    </td>

                                    <td><?= e($membership["plan_name"]) ?></td>

                                    <td>
                                        ₹<?= number_format((float)$membership["amount"], 2) ?>
                                    </td>

                                    <td>
                                        <span class="payment-badge <?= e($payment_class) ?>">
                                            <?= e($membership["payment_status"]) ?>
                                        </span>
                                    </td>

                                    <td><?= e($membership["created_at"]) ?></td>
                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>
                </table>

            </div>

        </section>

    </main>

    <footer class="admin-footer">
        &copy; <?= date("Y") ?> Smart Gym Management System. All rights reserved.
    </footer>

</div>

</body>
</html>
