
<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

$errors = [];
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        $errors[] = "Invalid request.";
    } else {

        $request_id = filter_input(INPUT_POST, "request_id", FILTER_VALIDATE_INT);
        $status = $_POST["status"] ?? "";

        if (!$request_id || !in_array($status, ["Approved", "Rejected"], true)) {
            $errors[] = "Invalid request details.";
        } else {

            try {
                $conn->beginTransaction();

                $stmt = $conn->prepare(
                    "SELECT mr.id, mr.client_id, mr.plan_name, mr.status,
                            p.amount, p.payment_method, p.status AS payment_status
                     FROM membership_requests mr
                     LEFT JOIN payments p ON p.request_id = mr.id
                     WHERE mr.id = ?
                     FOR UPDATE"
                );

                $stmt->execute([$request_id]);
                $request = $stmt->fetch();

                if (!$request || $request["status"] !== "Pending") {
                    throw new Exception("Request not found or already processed.");
                }

                if ($status === "Approved") {

                    if ($request["payment_status"] !== "Success") {
                        throw new Exception("Cannot approve a request without successful payment.");
                    }

                    $plan_durations = [
                        "Monthly" => 1,
                        "Quarterly" => 3,
                        "Half Yearly" => 6,
                        "Yearly" => 12
                    ];

                    $months = $plan_durations[$request["plan_name"]] ?? null;

                    if (!$months) {
                        throw new Exception("Invalid membership plan.");
                    }

                    $amount = (float)$request["amount"];

                    if ($amount <= 0) {
                        throw new Exception("Invalid payment amount.");
                    }

                    $start_date = date("Y-m-d");

                    $end_date = date(
                        "Y-m-d",
                        strtotime("+$months months", strtotime($start_date))
                    );

                    $insert = $conn->prepare(
                        "INSERT INTO memberships
                         (client_id, plan_name, start_date, end_date,
                          amount, payment_status)
                         VALUES (?, ?, ?, ?, ?, 'Paid')"
                    );

                    $insert->execute([
                        $request["client_id"],
                        $request["plan_name"],
                        $start_date,
                        $end_date,
                        $amount
                    ]);
                }

                $update = $conn->prepare(
                    "UPDATE membership_requests
                     SET status = ?
                     WHERE id = ? AND status = 'Pending'"
                );

                $update->execute([$status, $request_id]);

                if ($update->rowCount() !== 1) {
                    throw new Exception("This request has already been processed.");
                }

                $conn->commit();

                $success = "Membership request " . strtolower($status) . " successfully.";

            } catch (Throwable $e) {

                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }

                $errors[] = $e->getMessage();
            }
        }
    }
}

$stmt = $conn->query(
    "SELECT mr.*,
            c.name AS client_name,
            c.email,
            c.phone,
            p.amount,
            p.payment_method,
            p.transaction_id,
            p.status AS payment_status
     FROM membership_requests mr
     JOIN clients c ON mr.client_id = c.id
     LEFT JOIN payments p ON p.request_id = mr.id
     ORDER BY
        CASE WHEN mr.status = 'Pending' THEN 0 ELSE 1 END,
        mr.requested_at DESC"
);

$requests = $stmt->fetchAll();

$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;

foreach ($requests as $request) {
    if ($request["status"] === "Pending") {
        $pending_count++;
    } elseif ($request["status"] === "Approved") {
        $approved_count++;
    } elseif ($request["status"] === "Rejected") {
        $rejected_count++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Membership Requests | Smart Gym</title>

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
            margin-bottom: 28px;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: #151515;
            border: 1px solid #303030;
            border-radius: 11px;
            padding: 22px;
        }

        .stat-card p {
            color: #999;
            margin: 0 0 12px;
            font-size: 13px;
        }

        .stat-card h2 {
            margin: 0;
            font-size: 28px;
            color: #fff;
        }

        .stat-card.pending {
            border-left: 4px solid #ffc107;
        }

        .stat-card.approved {
            border-left: 4px solid #28a745;
        }

        .stat-card.rejected {
            border-left: 4px solid #e63946;
        }

        .message {
            padding: 15px 18px;
            margin-bottom: 20px;
            background: #191919;
            border: 1px solid #333;
            border-radius: 8px;
        }

        .success {
            color: #65d68a;
            border-left: 4px solid #28a745;
        }

        .error {
            color: #ff7777;
            border-left: 4px solid #e63946;
        }

        .requests-panel {
            background: #111;
            border: 1px solid #303030;
            border-radius: 12px;
            overflow: hidden;
        }

        .panel-header {
            padding: 20px 22px;
            border-bottom: 1px solid #303030;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .panel-header h2 {
            margin: 0;
            font-size: 18px;
            color: #fff;
        }

        .request-count {
            color: #aaa;
            font-size: 13px;
        }

        .table-container {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 1450px;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px 13px;
            text-align: left;
            border-bottom: 1px solid #292929;
            font-size: 13px;
            vertical-align: middle;
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

        .status-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            white-space: nowrap;
        }

        .status-pending {
            color: #ffc107;
            background: rgba(255,193,7,0.12);
        }

        .status-approved,
        .status-success {
            color: #65d68a;
            background: rgba(40,167,69,0.12);
        }

        .status-rejected,
        .status-failed {
            color: #ff7777;
            background: rgba(220,53,69,0.12);
        }

        .status-not-paid {
            color: #aaa;
            background: #292929;
        }

        .action-form {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .action-form button {
            border: none;
            padding: 9px 12px;
            border-radius: 5px;
            color: #fff;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
            transition: 0.2s;
        }

        .approve {
            background: #198754;
        }

        .approve:hover {
            background: #146c43;
        }

        .reject {
            background: #dc3545;
        }

        .reject:hover {
            background: #b02a37;
        }

        .processed {
            color: #888;
        }

        .empty-state {
            padding: 45px 20px;
            text-align: center;
            color: #888;
        }

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

            .page-header h1 {
                font-size: 24px;
            }

            .admin-main {
                padding: 22px 12px 100px;
            }

            .panel-header {
                padding: 16px;
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
            <a href="membership_requests.php" class="active">Membership Requests</a>
            <a href="complaints.php">Complaints</a>
            <a href="reports.php">Reports</a>
            <a href="logout.php" class="logout">Logout</a>
        </nav>

    </aside>

    <main class="admin-main">

        <div class="page-header">
            <div>
                <h1>Membership Requests</h1>
                <p>Review payments and manage client membership requests.</p>
            </div>

            <a href="dashboard.php" class="back-link">
                Back to Dashboard
            </a>
        </div>

        <?php if ($success): ?>
            <div class="message success">
                <?= e($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="message error">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">

            <div class="stat-card pending">
                <p>Pending Requests</p>
                <h2><?= $pending_count ?></h2>
            </div>

            <div class="stat-card approved">
                <p>Approved Requests</p>
                <h2><?= $approved_count ?></h2>
            </div>

            <div class="stat-card rejected">
                <p>Rejected Requests</p>
                <h2><?= $rejected_count ?></h2>
            </div>

        </div>

        <section class="requests-panel">

            <div class="panel-header">
                <h2>All Membership Requests</h2>

                <span class="request-count">
                    <?= count($requests) ?> Total Requests
                </span>
            </div>

            <div class="table-container">

                <table>
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Plan</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Payment Status</th>
                            <th>Transaction ID</th>
                            <th>Message</th>
                            <th>Requested Date</th>
                            <th>Request Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (empty($requests)): ?>

                            <tr>
                                <td colspan="12" class="empty-state">
                                    No membership requests found.
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($requests as $request): ?>

                                <?php
                                    $payment_status = $request["payment_status"] ?? "";
                                    $payment_class = $payment_status
                                        ? "status-" . strtolower($payment_status)
                                        : "status-not-paid";

                                    $request_class = "status-" . strtolower($request["status"]);
                                ?>

                                <tr>

                                    <td class="client-name">
                                        <?= e($request["client_name"]) ?>
                                    </td>

                                    <td><?= e($request["email"]) ?></td>

                                    <td><?= e($request["phone"]) ?></td>

                                    <td><?= e($request["plan_name"]) ?></td>

                                    <td>
                                        <?= $request["amount"] !== null
                                            ? "₹" . number_format((float)$request["amount"], 2)
                                            : "-" ?>
                                    </td>

                                    <td>
                                        <?= e($request["payment_method"] ?? "-") ?>
                                    </td>

                                    <td>
                                        <span class="status-badge <?= e($payment_class) ?>">
                                            <?= e($payment_status ?: "Not Paid") ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= e($request["transaction_id"] ?? "-") ?>
                                    </td>

                                    <td>
                                        <?= e($request["message"] ?? "-") ?>
                                    </td>

                                    <td>
                                        <?= e($request["requested_at"]) ?>
                                    </td>

                                    <td>
                                        <span class="status-badge <?= e($request_class) ?>">
                                            <?= e($request["status"]) ?>
                                        </span>
                                    </td>

                                    <td>

                                        <?php if ($request["status"] === "Pending"): ?>

                                            <form method="POST" class="action-form">

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= e($_SESSION["csrf_token"]) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="request_id"
                                                    value="<?= (int)$request["id"] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    name="status"
                                                    value="Approved"
                                                    class="approve"
                                                    onclick="return confirm('Approve this paid membership request?')"
                                                >
                                                    Approve
                                                </button>

                                                <button
                                                    type="submit"
                                                    name="status"
                                                    value="Rejected"
                                                    class="reject"
                                                    onclick="return confirm('Reject this membership request?')"
                                                >
                                                    Reject
                                                </button>

                                            </form>

                                        <?php else: ?>

                                            <span class="processed">Processed</span>

                                        <?php endif; ?>

                                    </td>

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
