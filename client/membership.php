<?php
session_start();

require_once "../config/database.php";

if (!isset($_SESSION["client_id"])) {
    header("Location: login.php");
    exit;
}

$client_id = $_SESSION["client_id"];

$stmt = $conn->prepare(
    "SELECT * FROM memberships
     WHERE client_id = ?
     ORDER BY created_at DESC"
);

$stmt->execute([$client_id]);
$memberships = $stmt->fetchAll();

$today = date("Y-m-d");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Membership | Smart Gym</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .membership-container {
            padding: 60px 8%;
            min-height: 80vh;
        }

        .membership-container h1 {
            color: #e63946;
            margin-bottom: 30px;
        }

        .membership-card {
            background: #1c1c1c;
            padding: 30px;
            margin-bottom: 20px;
            border-radius: 10px;
            border-left: 4px solid #e63946;
        }

        .membership-card h2 {
            color: #e63946;
            margin-bottom: 20px;
        }

        .membership-card p {
            margin: 12px 0;
            color: #ccc;
        }

        .empty-message {
            background: #1c1c1c;
            padding: 25px;
            border-radius: 8px;
            color: #ccc;
        }

        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-active,
        .status-paid {
            background: #183c27;
            color: #65d68a;
        }

        .status-expired {
            background: #442020;
            color: #ff7070;
        }

        .status-upcoming,
        .status-pending {
            background: #443819;
            color: #ffc107;
        }

        /* Expiry alerts */
        .alert {
            padding: 14px 18px;
            border-radius: 6px;
            margin: 15px 0;
            font-weight: 500;
        }

        .alert-warning {
            background: #443819;
            color: #ffc107;
            border: 1px solid #80651c;
        }

        .alert-danger {
            background: #442020;
            color: #ff7070;
            border: 1px solid #803535;
        }

        /* Request membership button */
        .request-btn {
            display: inline-block;
            background: #e63946;
            color: white;
            padding: 12px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
        }

        .request-btn:hover {
            background: #c62836;
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
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="membership-container">

    <h1>My Membership</h1>

    <?php if (empty($memberships)): ?>

        <div class="empty-message">
            <p>You don't have any membership plans yet.</p>
            <p>Please contact the gym administrator or request a membership.</p>
        </div>

    <?php else: ?>

        <?php foreach ($memberships as $membership): ?>

            <?php
            $start_date = $membership["start_date"];
            $end_date = $membership["end_date"];
            $payment_status = $membership["payment_status"] ?? "Pending";

            // Calculate membership status
            if ($today < $start_date) {
                $membership_status = "Upcoming";
            } elseif ($today > $end_date) {
                $membership_status = "Expired";
            } else {
                $membership_status = "Active";
            }

            // Calculate remaining days
            $days_remaining = (int) floor(
                (strtotime($end_date) - strtotime($today)) / 86400
            );
            ?>

            <div class="membership-card">

                <h2>
                    <?= htmlspecialchars($membership["plan_name"]) ?>
                </h2>

                <p>
                    <strong>Start Date:</strong>
                    <?= htmlspecialchars($start_date) ?>
                </p>

                <p>
                    <strong>Expiry Date:</strong>
                    <?= htmlspecialchars($end_date) ?>
                </p>

                <p>
                    <strong>Amount:</strong>
                    ₹<?= number_format((float)$membership["amount"], 2) ?>
                </p>

                <p>
                    <strong>Payment Status:</strong>

                    <span class="status-badge status-<?= strtolower(htmlspecialchars($payment_status)) ?>">
                        <?= htmlspecialchars($payment_status) ?>
                    </span>
                </p>

                <p>
                    <strong>Membership Status:</strong>

                    <span class="status-badge status-<?= strtolower($membership_status) ?>">
                        <?= htmlspecialchars($membership_status) ?>
                    </span>
                </p>

                <!-- Expiry alerts -->
                <?php if (
                    $membership_status === "Active" &&
                    $payment_status === "Paid" &&
                    $days_remaining >= 0 &&
                    $days_remaining <= 7
                ): ?>

                    <div class="alert alert-warning">
                        Your membership expires in
                        <?= $days_remaining ?> day(s).
                        Please renew soon.
                    </div>

                <?php elseif ($membership_status === "Expired"): ?>

                    <div class="alert alert-danger">
                        Your membership has expired.
                        Please request a renewal.
                    </div>

                <?php endif; ?>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>

    <div style="margin: 20px 0;">
        <a href="request_membership.php" class="request-btn">
            + Request Membership
        </a>
    </div>

</main>

<footer>
    <p>&copy; <?= date("Y") ?> Smart Gym Management System</p>
</footer>

</body>
</html>
