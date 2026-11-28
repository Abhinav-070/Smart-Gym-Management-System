
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

$errors = [];

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$membership_id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$membership_id) {
    header("Location: memberships.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM memberships WHERE id = ?");
$stmt->execute([$membership_id]);
$membership = $stmt->fetch();

if (!$membership) {
    header("Location: memberships.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"] ?? "")) {
        $errors[] = "Invalid request. Please try again.";
    } else {

        $amount = $_POST["amount"] ?? "";
        $payment_status = $_POST["payment_status"] ?? "";

        if (!is_numeric($amount) || $amount < 0 || $amount > 99999999.99) {
            $errors[] = "Enter a valid amount.";
        }

        if (!in_array($payment_status, ["Paid", "Pending"], true)) {
            $errors[] = "Select a valid payment status.";
        }

        if (empty($errors)) {

            $update = $conn->prepare(
                "UPDATE memberships
                 SET amount = ?, payment_status = ?
                 WHERE id = ?"
            );

            $update->execute([
                $amount,
                $payment_status,
                $membership_id
            ]);

            header("Location: memberships.php?success=updated");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Membership | Smart Gym</title>

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

        .page-heading {
            margin-bottom: 30px;
        }

        .page-heading h1 {
            margin: 0 0 10px;
            font-size: 29px;
        }

        .page-heading h1 span {
            color: #e63946;
        }

        .page-heading p {
            color: #999;
            font-size: 14px;
            margin: 0;
        }

        /* Form panel */
        .form-panel {
            max-width: 650px;
            padding: 30px;
            background: rgba(25, 25, 25, 0.96);
            border: 1px solid #303030;
            border-radius: 13px;
        }

        .membership-info {
            padding: 18px;
            margin-bottom: 25px;
            background: rgba(230, 57, 70, 0.08);
            border: 1px solid rgba(230, 57, 70, 0.25);
            border-radius: 8px;
        }

        .membership-info small {
            display: block;
            color: #999;
            font-size: 11px;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .membership-info strong {
            color: #fff;
            font-size: 17px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 9px;
            margin-bottom: 22px;
        }

        .form-group label {
            color: #ccc;
            font-size: 13px;
            font-weight: bold;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 14px;
            background: #101010;
            color: #fff;
            border: 1px solid #3a3a3a;
            border-radius: 7px;
            outline: none;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #e63946;
            box-shadow: 0 0 0 2px rgba(230, 57, 70, 0.12);
        }

        .form-group select option {
            background: #111;
            color: #fff;
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            background: #e63946;
            color: #fff;
            border: none;
            border-radius: 7px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.25s;
        }

        .submit-btn:hover {
            background: #c92f3b;
            transform: translateY(-2px);
        }

        .message {
            padding: 14px 18px;
            margin-bottom: 20px;
            border-radius: 8px;
            background: rgba(110, 35, 42, 0.35);
            border: 1px solid #71343a;
            color: #ffb3b8;
            font-size: 13px;
        }

        .back-link {
            display: inline-block;
            margin-top: 25px;
            color: #e63946;
            text-decoration: none;
            font-size: 13px;
        }

        .back-link:hover {
            color: #ff7b84;
        }

        /* Footer */
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

        /* Responsive */
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

            .page-heading h1 {
                font-size: 24px;
            }

            .form-panel {
                padding: 20px;
            }
        }
    </style>
</head>

<body>

<div class="admin-layout">

    <!-- Sidebar -->
    <aside class="admin-sidebar">

        <div class="admin-logo">SMART GYM</div>

        <div class="sidebar-label">Main Menu</div>

        <nav>
            <a href="dashboard.php">▦ Dashboard</a>
        </nav>

        <div class="sidebar-label">Management</div>

        <nav>
            <a href="clients.php">♙ Manage Clients</a>
            <a href="memberships.php" class="active">▤ Memberships</a>
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

    <!-- Main Content -->
    <main class="admin-main">

        <div class="topbar">
            <h2>Membership Management</h2>
            <div class="admin-tag">Administrator</div>
        </div>

        <section class="page-heading">
            <h1>Edit <span>Membership</span></h1>
            <p>Update the membership payment details.</p>
        </section>

        <section class="form-panel">

            <h2 style="font-size:19px; margin:0 0 22px;">
                Edit Membership Payment
            </h2>

            <?php if (!empty($errors)): ?>
                <div class="message">
                    <?php foreach ($errors as $error): ?>
                        <p><?= e($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="membership-info">
                <small>Membership Plan</small>
                <strong><?= e($membership["plan_name"]) ?></strong>
            </div>

            <form method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($_SESSION["csrf_token"]) ?>"
                >

                <div class="form-group">
                    <label for="amount">Amount (₹)</label>

                    <input
                        type="number"
                        id="amount"
                        name="amount"
                        min="0"
                        max="99999999.99"
                        step="0.01"
                        value="<?= e($membership["amount"]) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="payment_status">Payment Status</label>

                    <select
                        id="payment_status"
                        name="payment_status"
                        required
                    >
                        <option
                            value="Pending"
                            <?= $membership["payment_status"] === "Pending" ? "selected" : "" ?>
                        >
                            Pending
                        </option>

                        <option
                            value="Paid"
                            <?= $membership["payment_status"] === "Paid" ? "selected" : "" ?>
                        >
                            Paid
                        </option>
                    </select>
                </div>

                <button type="submit" class="submit-btn">
                    Update Membership
                </button>

            </form>

            <a href="memberships.php" class="back-link">
                ← Back to Memberships
            </a>

        </section>

    </main>

</div>

<footer class="admin-footer">
    &copy; <?= date("Y") ?> Smart Gym Management System
</footer>

</body>
</html>
