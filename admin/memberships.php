<?php
session_start();

require_once "../config/database.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$errors = [];

// CSRF protection
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Search and filters
$search = trim($_GET["search"] ?? "");
$filter_status = $_GET["status"] ?? "";
$filter_membership = $_GET["membership_status"] ?? "";

// Allowed filter values
$allowed_statuses = ["Paid", "Pending"];
$allowed_membership_statuses = ["Active", "Expired", "Upcoming"];

if (!in_array($filter_status, $allowed_statuses, true)) {
    $filter_status = "";
}

if (!in_array($filter_membership, $allowed_membership_statuses, true)) {
    $filter_membership = "";
}

// Count pending membership requests
$pending_stmt = $conn->query(
    "SELECT COUNT(*) FROM membership_requests WHERE status = 'Pending'"
);

$pending_requests = (int)$pending_stmt->fetchColumn();

// Assign membership
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["assign_membership"])) {

    if (!hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"] ?? "")) {
        $errors[] = "Invalid request. Please try again.";
    } else {

        $client_id = filter_var(
            $_POST["client_id"] ?? null,
            FILTER_VALIDATE_INT
        );

        $plan_name = trim($_POST["plan_name"] ?? "");
        $start_date = $_POST["start_date"] ?? "";
        $end_date = $_POST["end_date"] ?? "";
        $amount = $_POST["amount"] ?? "";
        $payment_status = $_POST["payment_status"] ?? "";

        if (!$client_id) {
            $errors[] = "Please select a client.";
        }

        $allowed_plans = [
            "Monthly",
            "Quarterly",
            "Half Yearly",
            "Yearly"
        ];

        if (!in_array($plan_name, $allowed_plans, true)) {
            $errors[] = "Select a valid membership plan.";
        }

        $start = DateTime::createFromFormat("!Y-m-d", $start_date);
        $end = DateTime::createFromFormat("!Y-m-d", $end_date);

        if (!$start || $start->format("Y-m-d") !== $start_date) {
            $errors[] = "Enter a valid start date.";
        }

        if (!$end || $end->format("Y-m-d") !== $end_date) {
            $errors[] = "Enter a valid expiry date.";
        } elseif ($start && $end <= $start) {
            $errors[] = "Expiry date must be after the start date.";
        }

        if (
            !is_numeric($amount) ||
            (float)$amount < 0 ||
            (float)$amount > 99999999.99
        ) {
            $errors[] = "Enter a valid membership amount.";
        }

        if (!in_array($payment_status, ["Paid", "Pending"], true)) {
            $errors[] = "Select a valid payment status.";
        }

        if (empty($errors)) {

            $check = $conn->prepare(
                "SELECT id FROM clients WHERE id = ?"
            );

            $check->execute([$client_id]);

            if (!$check->fetch()) {
                $errors[] = "Selected client does not exist.";
            } else {

                $insert = $conn->prepare(
                    "INSERT INTO memberships
                    (client_id, plan_name, start_date, end_date, amount, payment_status)
                    VALUES (?, ?, ?, ?, ?, ?)"
                );

                $insert->execute([
                    $client_id,
                    $plan_name,
                    $start_date,
                    $end_date,
                    $amount,
                    $payment_status
                ]);

                header("Location: memberships.php?success=assigned");
                exit;
            }
        }
    }
}

// Delete membership
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_membership"])) {

    if (!hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"] ?? "")) {
        $errors[] = "Invalid request. Please try again.";
    } else {

        $membership_id = filter_var(
            $_POST["membership_id"] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$membership_id) {
            $errors[] = "Invalid membership selected.";
        } else {

            $delete = $conn->prepare(
                "DELETE FROM memberships WHERE id = ?"
            );

            $delete->execute([$membership_id]);

            if ($delete->rowCount() === 0) {
                $errors[] = "Membership not found.";
            } else {
                header("Location: memberships.php?success=deleted");
                exit;
            }
        }
    }
}

// Get clients
$clients = $conn->query(
    "SELECT id, name, email
     FROM clients
     ORDER BY name ASC"
)->fetchAll();

// Get memberships with search and filters
$sql = "SELECT
            m.*,
            c.name AS client_name,
            c.email AS client_email
        FROM memberships m
        INNER JOIN clients c ON m.client_id = c.id
        WHERE 1=1";

$params = [];

// Search by client name, email or plan
if ($search !== "") {

    $sql .= " AND (
        c.name LIKE ?
        OR c.email LIKE ?
        OR m.plan_name LIKE ?
    )";

    $search_term = "%" . $search . "%";

    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

// Filter by payment status
if ($filter_status !== "") {

    $sql .= " AND m.payment_status = ?";
    $params[] = $filter_status;
}

// Filter by membership status
if ($filter_membership === "Active") {

    $sql .= " AND m.start_date <= CURDATE()
              AND m.end_date >= CURDATE()";

} elseif ($filter_membership === "Expired") {

    $sql .= " AND m.end_date < CURDATE()";

} elseif ($filter_membership === "Upcoming") {

    $sql .= " AND m.start_date > CURDATE()";
}

// Latest memberships first
$sql .= " ORDER BY m.id DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$memberships = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Memberships | Smart Gym</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .container {
            padding: 50px 5%;
            min-height: 85vh;
        }

        h1, h2 {
            color: #e63946;
            margin-bottom: 25px;
        }

        .membership-form {
            max-width: 650px;
            background: #1c1c1c;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 45px;
        }

        .membership-form label {
            display: block;
            margin: 15px 0 8px;
            color: #ddd;
        }

        .membership-form input,
        .membership-form select {
            width: 100%;
            padding: 12px;
            background: #111;
            color: white;
            border: 1px solid #444;
            border-radius: 5px;
        }

        .membership-form button {
            width: 100%;
            margin-top: 25px;
            padding: 13px;
            background: #e63946;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .membership-form button:hover {
            background: #c1121f;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            background: #442024;
            color: #ffb3b8;
        }

        .success {
            background: #183c27;
            color: #a8e6b8;
        }

        .table-container {
            overflow-x: auto;
            background: #1c1c1c;
            border-radius: 8px;
        }

        table {
            width: 100%;
            min-width: 1050px;
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

        .delete-btn {
            padding: 8px 12px;
            background: #e63946;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .edit-btn {
            display: inline-block;
            padding: 8px 12px;
            background: #198754;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 5px;
        }

        .request-btn {
            display: inline-block;
            background: #e63946;
            color: white;
            padding: 12px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
        }

        .request-btn:hover {
            background: #c1121f;
        }

        .request-count {
            display: inline-block;
            background: #111;
            color: white;
            padding: 3px 8px;
            margin-left: 5px;
            border-radius: 20px;
            font-size: 13px;
        }

        .back-link {
            display: inline-block;
            margin-top: 25px;
            color: #e63946;
            text-decoration: none;
        }

        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-paid {
            background: #183c27;
            color: #65d68a;
        }

        .status-pending {
            background: #443819;
            color: #ffc107;
        }

        .status-active {
            background: #183c27;
            color: #65d68a;
        }

        .status-expired {
            background: #442020;
            color: #ff7070;
        }

        .status-upcoming {
            background: #443819;
            color: #ffc107;
        }

        /* Search and filter */
        .filter-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
            align-items: center;
        }

        .filter-form input,
        .filter-form select {
            padding: 11px;
            border-radius: 5px;
            border: 1px solid #444;
            background: #111;
            color: white;
        }

        .filter-form input {
            flex: 1;
            min-width: 200px;
        }

        .filter-form button {
            padding: 11px 18px;
            background: #e63946;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .reset-btn {
            padding: 11px 18px;
            background: #333;
            color: white;
            border-radius: 5px;
            text-decoration: none;
        }

        .filter-form button:hover {
            background: #c1121f;
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
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="container">

    <!-- Membership Requests Button -->
    <div style="margin: 20px 0;">
        <a href="membership_requests.php" class="request-btn">
            + Membership Requests

            <?php if ($pending_requests > 0): ?>
                <span class="request-count">
                    <?= $pending_requests ?>
                </span>
            <?php endif; ?>
        </a>
    </div>

    <h1>Membership Management</h1>

    <?php if (!empty($errors)): ?>
        <div class="message">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (($_GET["success"] ?? "") === "assigned"): ?>
        <div class="message success">
            Membership assigned successfully!
        </div>
    <?php elseif (($_GET["success"] ?? "") === "deleted"): ?>
        <div class="message success">
            Membership deleted successfully!
        </div>
    <?php elseif (($_GET["success"] ?? "") === "updated"): ?>
        <div class="message success">
            Membership updated successfully!
        </div>
    <?php endif; ?>

    <h2>Assign Membership</h2>

    <form method="POST" class="membership-form">

        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

        <label>Select Client</label>

        <select name="client_id" required>
            <option value="">Choose Client</option>

            <?php foreach ($clients as $client): ?>
                <option value="<?= (int)$client["id"] ?>">
                    <?= htmlspecialchars($client["name"]) ?>
                    (<?= htmlspecialchars($client["email"]) ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <label>Membership Plan</label>

        <select name="plan_name" required>
            <option value="">Choose Plan</option>
            <option value="Monthly">Monthly</option>
            <option value="Quarterly">Quarterly</option>
            <option value="Half Yearly">Half Yearly</option>
            <option value="Yearly">Yearly</option>
        </select>

        <label>Start Date</label>
        <input type="date" name="start_date" required>

        <label>Expiry Date</label>
        <input type="date" name="end_date" required>

        <label>Amount (₹)</label>
        <input type="number" name="amount" min="0" max="99999999.99"
               step="0.01" required>

        <label>Payment Status</label>

        <select name="payment_status" required>
            <option value="Pending">Pending</option>
            <option value="Paid">Paid</option>
        </select>

        <button type="submit" name="assign_membership">
            Assign Membership
        </button>

    </form>

    <h2>All Memberships</h2>

    <!-- Search and Filter -->
    <form method="GET" class="filter-form">

        <input
            type="text"
            name="search"
            placeholder="Search client, email or plan..."
            value="<?= htmlspecialchars($search) ?>"
        >

        <select name="status">
            <option value="">All Payment Status</option>
            <option value="Paid" <?= $filter_status === "Paid" ? "selected" : "" ?>>
                Paid
            </option>
            <option value="Pending" <?= $filter_status === "Pending" ? "selected" : "" ?>>
                Pending
            </option>
        </select>

        <button type="submit">Search</button>

        <a href="memberships.php" class="reset-btn">Reset</a>

    </form>

    <div class="table-container">

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client</th>
                    <th>Plan</th>
                    <th>Start Date</th>
                    <th>Expiry Date</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Membership Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

                <?php if (empty($memberships)): ?>
                    <tr>
                        <td colspan="9">No memberships found.</td>
                    </tr>
                <?php else: ?>

                    <?php foreach ($memberships as $membership): ?>

                        <?php
                        $today = date("Y-m-d");

                        if ($today < $membership["start_date"]) {
                            $membership_status = "Upcoming";
                        } elseif ($today > $membership["end_date"]) {
                            $membership_status = "Expired";
                        } else {
                            $membership_status = "Active";
                        }
                        ?>

                        <tr>
                            <td><?= (int)$membership["id"] ?></td>

                            <td>
                                <?= htmlspecialchars($membership["client_name"]) ?>
                                <br>
                                <small><?= htmlspecialchars($membership["client_email"]) ?></small>
                            </td>

                            <td>
                                <?= htmlspecialchars($membership["plan_name"]) ?>
                            </td>

                            <td><?= htmlspecialchars($membership["start_date"]) ?></td>

                            <td><?= htmlspecialchars($membership["end_date"]) ?></td>

                            <td>
                                ₹<?= number_format((float)$membership["amount"], 2) ?>
                            </td>

                            <td>
                                <?php
                                $payment_status = $membership["payment_status"] ?? "Pending";
                                ?>

                                <span class="status-badge status-<?= strtolower(htmlspecialchars($payment_status)) ?>">
                                    <?= htmlspecialchars($payment_status) ?>
                                </span>
                            </td>

                            <td>
                                <span class="status-badge status-<?= strtolower($membership_status) ?>">
                                    <?= htmlspecialchars($membership_status) ?>
                                </span>
                            </td>

                            <td>
                                <a href="edit_membership.php?id=<?= (int)$membership["id"] ?>"
                                   class="edit-btn">
                                    Edit
                                </a>

                                <form method="POST"
                                      onsubmit="return confirm('Delete this membership?');">

                                    <input type="hidden" name="csrf_token"
                                           value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

                                    <input type="hidden" name="membership_id"
                                           value="<?= (int)$membership["id"] ?>">

                                    <button type="submit"
                                            name="delete_membership"
                                            class="delete-btn">
                                        Delete
                                    </button>
                                </form>
                            </td>
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