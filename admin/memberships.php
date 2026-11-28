
<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$errors = [];

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Search and filters
$search = trim($_GET["search"] ?? "");
$filter_status = $_GET["status"] ?? "";
$filter_membership = $_GET["membership_status"] ?? "";

$allowed_statuses = ["Paid", "Pending"];
$allowed_membership_statuses = ["Active", "Expired", "Upcoming"];

if (!in_array($filter_status, $allowed_statuses, true)) {
    $filter_status = "";
}

if (!in_array($filter_membership, $allowed_membership_statuses, true)) {
    $filter_membership = "";
}

// Pending membership requests
$pending_stmt = $conn->query(
    "SELECT COUNT(*) FROM membership_requests WHERE status = 'Pending'"
);
$pending_requests = (int)$pending_stmt->fetchColumn();

// Assign membership
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["assign_membership"])) {

    if (!hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"] ?? "")) {
        $errors[] = "Invalid request. Please try again.";
    } else {

        $client_id = filter_var($_POST["client_id"] ?? null, FILTER_VALIDATE_INT);
        $plan_name = trim($_POST["plan_name"] ?? "");
        $start_date = $_POST["start_date"] ?? "";
        $end_date = $_POST["end_date"] ?? "";
        $amount = $_POST["amount"] ?? "";
        $payment_status = $_POST["payment_status"] ?? "";

        if (!$client_id || $client_id < 1) {
            $errors[] = "Please select a valid client.";
        }

        $allowed_plans = ["Monthly", "Quarterly", "Half Yearly", "Yearly"];

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
            !is_finite((float)$amount) ||
            (float)$amount < 0 ||
            (float)$amount > 99999999.99
        ) {
            $errors[] = "Enter a valid membership amount.";
        }

        if (!in_array($payment_status, $allowed_statuses, true)) {
            $errors[] = "Select a valid payment status.";
        }

        if (empty($errors)) {

            $check = $conn->prepare("SELECT id FROM clients WHERE id = ?");
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

        $membership_id = filter_var($_POST["membership_id"] ?? null, FILTER_VALIDATE_INT);

        if (!$membership_id || $membership_id < 1) {
            $errors[] = "Invalid membership selected.";
        } else {

            $delete = $conn->prepare("DELETE FROM memberships WHERE id = ?");
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

// Clients for dropdown
$clients = $conn->query(
    "SELECT id, name, email FROM clients ORDER BY name ASC"
)->fetchAll();

// Get memberships
$sql = "SELECT m.*, c.name AS client_name, c.email AS client_email
         FROM memberships m
         INNER JOIN clients c ON m.client_id = c.id
         WHERE 1=1";

$params = [];

if ($search !== "") {
    $sql .= " AND (c.name LIKE ? OR c.email LIKE ? OR m.plan_name LIKE ?)";
    $search_term = "%" . $search . "%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($filter_status !== "") {
    $sql .= " AND m.payment_status = ?";
    $params[] = $filter_status;
}

if ($filter_membership === "Active") {
    $sql .= " AND m.start_date <= CURDATE() AND m.end_date >= CURDATE()";
} elseif ($filter_membership === "Expired") {
    $sql .= " AND m.end_date < CURDATE()";
} elseif ($filter_membership === "Upcoming") {
    $sql .= " AND m.start_date > CURDATE()";
}

$sql .= " ORDER BY m.id DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$memberships = $stmt->fetchAll(PDO::FETCH_ASSOC);

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Membership Management | Smart Gym</title>
<link rel="stylesheet" href="../assets/css/style.css">

<style>
* { box-sizing: border-box; }

body {
    margin: 0;
    background: #090909;
    color: #fff;
    font-family: Arial, sans-serif;
}

/* Sidebar */
.admin-sidebar {
    width: 250px;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    padding: 30px 18px;
    background: rgba(12,12,12,.97);
    border-right: 1px solid #292929;
    z-index: 1000;
    overflow-y: auto;
}

.admin-logo {
    text-align: center;
    color: #e63946;
    font-size: 21px;
    font-weight: bold;
    letter-spacing: 1px;
    margin-bottom: 40px;
}

.sidebar-label {
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
    text-decoration: none;
    color: #aaa;
    padding: 13px 15px;
    border-radius: 7px;
    font-size: 14px;
    transition: .25s;
}

.admin-sidebar nav a:hover,
.admin-sidebar nav a.active {
    color: white;
    background: rgba(230,57,70,.15);
    border-left: 3px solid #e63946;
}

.admin-sidebar nav a.logout {
    color: #ff6b75;
    margin-top: 15px;
}

/* Main area */
.admin-main {
    margin-left: 250px;
    width: calc(100% - 250px);
    min-height: 100vh;
    padding: 35px;
    padding-bottom: 110px;
    background:
        linear-gradient(rgba(8,8,8,.92), rgba(8,8,8,.96)),
        url("../uploads/admin-bg.jpg") center/cover fixed;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 30px;
}

.topbar h2 {
    margin: 0;
    font-size: 22px;
}

.admin-tag {
    background: #191919;
    border: 1px solid #333;
    padding: 9px 15px;
    border-radius: 30px;
    color: #ccc;
    font-size: 12px;
}

.page-heading {
    margin-bottom: 25px;
}

.page-heading h1 {
    margin: 0 0 10px;
    font-size: 29px;
}

.page-heading h1 span {
    color: #e63946;
}

.page-heading p {
    color: #888;
    margin: 0;
}

/* Messages */
.message {
    padding: 15px 18px;
    margin-bottom: 20px;
    border-radius: 8px;
    background: #30191c;
    color: #ffb3b8;
    border: 1px solid #743038;
}

.message.success {
    background: #142b1c;
    color: #a8e6b8;
    border-color: #245c37;
}

/* Requests button */
.request-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: #e63946;
    color: white;
    padding: 12px 18px;
    border-radius: 7px;
    text-decoration: none;
    font-size: 14px;
    font-weight: bold;
    transition: .2s;
}

.request-btn:hover {
    background: #c1121f;
    transform: translateY(-2px);
}

.request-count {
    background: #111;
    padding: 3px 9px;
    border-radius: 20px;
    font-size: 12px;
}

/* Form card */
.panel {
    background: rgba(20,20,20,.95);
    border: 1px solid #303030;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
}

.section-heading {
    font-size: 19px;
    margin: 0 0 22px;
    padding-left: 12px;
    border-left: 3px solid #e63946;
}

.membership-form {
    display: grid;
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap: 18px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 9px;
}

.form-group label {
    color: #bbb;
    font-size: 13px;
}

.membership-form input,
.membership-form select,
.filter-form input,
.filter-form select {
    width: 100%;
    padding: 12px 14px;
    background: #111;
    border: 1px solid #353535;
    color: white;
    border-radius: 7px;
    outline: none;
}

.membership-form input:focus,
.membership-form select:focus,
.filter-form input:focus,
.filter-form select:focus {
    border-color: #e63946;
}

.form-submit {
    grid-column: 1 / -1;
    width: 100%;
    padding: 13px;
    background: #e63946;
    border: none;
    color: white;
    border-radius: 7px;
    font-weight: bold;
    cursor: pointer;
    transition: .2s;
}

.form-submit:hover,
.filter-form button:hover {
    background: #c1121f;
}

/* Filters */
.filter-form {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.filter-form input {
    flex: 1;
    min-width: 190px;
}

.filter-form select {
    width: auto;
    min-width: 150px;
}

.filter-form button {
    padding: 12px 20px;
    background: #e63946;
    color: white;
    border: none;
    border-radius: 7px;
    cursor: pointer;
}

.reset-btn {
    padding: 12px 18px;
    background: #292929;
    border: 1px solid #383838;
    color: #ddd;
    border-radius: 7px;
    text-decoration: none;
}

/* Table */
.table-container {
    background: rgba(20,20,20,.95);
    border: 1px solid #2c2c2c;
    border-radius: 12px;
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 1050px;
    border-collapse: collapse;
}

thead {
    background: #101010;
}

th {
    padding: 16px;
    text-align: left;
    color: #e63946;
    font-size: 12px;
    text-transform: uppercase;
    white-space: nowrap;
}

td {
    padding: 15px;
    border-top: 1px solid #292929;
    color: #ccc;
    font-size: 13px;
}

tbody tr:hover {
    background: #222;
}

.client-name {
    color: white;
    font-weight: bold;
}

.client-email {
    color: #888;
    font-size: 12px;
}

.amount {
    color: #fff;
    font-weight: bold;
    white-space: nowrap;
}

/* Status badges */
.status-badge {
    display: inline-block;
    padding: 6px 11px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: bold;
    white-space: nowrap;
}

.status-paid,
.status-active {
    background: #183c27;
    color: #65d68a;
}

.status-pending,
.status-upcoming {
    background: #443819;
    color: #ffc107;
}

.status-expired {
    background: #442020;
    color: #ff7070;
}

/* Action buttons */
.edit-btn {
    display: inline-block;
    padding: 8px 12px;
    margin-bottom: 6px;
    background: #193c2a;
    border: 1px solid #286640;
    color: #78d89a;
    border-radius: 6px;
    text-decoration: none;
}

.delete-btn {
    padding: 8px 12px;
    background: rgba(230,57,70,.12);
    border: 1px solid #6c2a30;
    color: #ff6b75;
    border-radius: 6px;
    cursor: pointer;
}

.delete-btn:hover {
    background: #e63946;
    color: white;
}

.empty-message {
    padding: 35px;
    text-align: center;
    color: #888;
}

/* Back link */
.back-link {
    display: inline-block;
    margin-top: 25px;
    color: #e63946;
    text-decoration: none;
}

/* Fixed footer */
.admin-footer {
    position: fixed;
    bottom: 0;
    left: 250px;
    right: 0;
    padding: 15px;
    text-align: center;
    background: rgba(8,8,8,.96);
    border-top: 1px solid #292929;
    color: #777;
    font-size: 12px;
    z-index: 900;
    backdrop-filter: blur(10px);
}

/* Responsive */
@media(max-width:900px) {
    .admin-sidebar {
        position: relative;
        width: 100%;
        height: auto;
        padding: 15px;
    }

    .admin-logo {
        margin-bottom: 15px;
    }

    .sidebar-label {
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

@media(max-width:600px) {
    .topbar {
        flex-direction: column;
        align-items: flex-start;
    }

    .page-heading h1 {
        font-size: 24px;
    }

    .panel {
        padding: 18px;
    }

    .membership-form {
        grid-template-columns: 1fr;
    }

    .form-submit {
        grid-column: auto;
    }

    .filter-form {
        flex-direction: column;
        align-items: stretch;
    }

    .filter-form input,
    .filter-form select,
    .filter-form button,
    .reset-btn {
        width: 100%;
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

<!-- Main -->
<main class="admin-main">

    <div class="topbar">
        <h2>Admin Panel</h2>
        <div class="admin-tag">● Administrator</div>
    </div>

    <div class="page-heading">
        <h1>Membership <span>Management</span></h1>
        <p>Assign memberships and manage payments and member subscriptions.</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="message">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (($_GET["success"] ?? "") === "assigned"): ?>
        <div class="message success">Membership assigned successfully!</div>
    <?php elseif (($_GET["success"] ?? "") === "deleted"): ?>
        <div class="message success">Membership deleted successfully!</div>
    <?php elseif (($_GET["success"] ?? "") === "updated"): ?>
        <div class="message success">Membership updated successfully!</div>
    <?php endif; ?>

    <a href="membership_requests.php" class="request-btn">
        + Membership Requests
        <?php if ($pending_requests > 0): ?>
            <span class="request-count"><?= $pending_requests ?></span>
        <?php endif; ?>
    </a>

    <!-- Assign Membership -->
    <section class="panel" style="margin-top:25px;">

        <h2 class="section-heading">Assign Membership</h2>

        <form method="POST" class="membership-form">

            <input type="hidden" name="csrf_token"
                   value="<?= e($_SESSION["csrf_token"]) ?>">

            <div class="form-group">
                <label>Select Client</label>
                <select name="client_id" required>
                    <option value="">Choose Client</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?= (int)$client["id"] ?>">
                            <?= e($client["name"]) ?> (<?= e($client["email"]) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Membership Plan</label>
                <select name="plan_name" required>
                    <option value="">Choose Plan</option>
                    <option value="Monthly">Monthly</option>
                    <option value="Quarterly">Quarterly</option>
                    <option value="Half Yearly">Half Yearly</option>
                    <option value="Yearly">Yearly</option>
                </select>
            </div>

            <div class="form-group">
                <label>Start Date</label>
                <input type="date" name="start_date" required>
            </div>

            <div class="form-group">
                <label>Expiry Date</label>
                <input type="date" name="end_date" required>
            </div>

            <div class="form-group">
                <label>Amount (₹)</label>
                <input type="number" name="amount"
                       min="0" max="99999999.99" step="0.01" required>
            </div>

            <div class="form-group">
                <label>Payment Status</label>
                <select name="payment_status" required>
                    <option value="Pending">Pending</option>
                    <option value="Paid">Paid</option>
                </select>
            </div>

            <button type="submit" name="assign_membership" class="form-submit">
                Assign Membership
            </button>

        </form>

    </section>

    <!-- All memberships -->
    <h2 class="section-heading">All Memberships</h2>

    <form method="GET" class="filter-form">

        <input type="text" name="search"
               placeholder="Search client, email or plan..."
               value="<?= e($search) ?>">

        <select name="status">
            <option value="">All Payment Statuses</option>
            <option value="Paid" <?= $filter_status === "Paid" ? "selected" : "" ?>>Paid</option>
            <option value="Pending" <?= $filter_status === "Pending" ? "selected" : "" ?>>Pending</option>
        </select>

        <select name="membership_status">
            <option value="">All Membership Statuses</option>
            <option value="Active" <?= $filter_membership === "Active" ? "selected" : "" ?>>Active</option>
            <option value="Expired" <?= $filter_membership === "Expired" ? "selected" : "" ?>>Expired</option>
            <option value="Upcoming" <?= $filter_membership === "Upcoming" ? "selected" : "" ?>>Upcoming</option>
        </select>

        <button type="submit">Filter</button>
        <a href="memberships.php" class="reset-btn">Reset</a>

    </form>

    <div class="table-container">

        <?php if (empty($memberships)): ?>
            <div class="empty-message">No memberships found.</div>
        <?php else: ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client</th>
                    <th>Plan</th>
                    <th>Start Date</th>
                    <th>Expiry</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
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

                $payment_status = $membership["payment_status"] ?? "Pending";
                ?>

                <tr>
                    <td>#<?= (int)$membership["id"] ?></td>

                    <td>
                        <div class="client-name"><?= e($membership["client_name"]) ?></div>
                        <div class="client-email"><?= e($membership["client_email"]) ?></div>
                    </td>

                    <td><?= e($membership["plan_name"]) ?></td>
                    <td><?= e($membership["start_date"]) ?></td>
                    <td><?= e($membership["end_date"]) ?></td>

                    <td class="amount">
                        ₹<?= number_format((float)$membership["amount"], 2) ?>
                    </td>

                    <td>
                        <span class="status-badge status-<?= strtolower(e($payment_status)) ?>">
                            <?= e($payment_status) ?>
                        </span>
                    </td>

                    <td>
                        <span class="status-badge status-<?= strtolower(e($membership_status)) ?>">
                            <?= e($membership_status) ?>
                        </span>
                    </td>

                    <td>
                        <a href="edit_membership.php?id=<?= (int)$membership["id"] ?>"
                           class="edit-btn">Edit</a>

                        <form method="POST"
                              onsubmit="return confirm('Delete this membership?');">

                            <input type="hidden" name="csrf_token"
                                   value="<?= e($_SESSION["csrf_token"]) ?>">

                            <input type="hidden" name="membership_id"
                                   value="<?= (int)$membership["id"] ?>">

                            <button type="submit" name="delete_membership"
                                    class="delete-btn">Delete</button>
                        </form>
                    </td>
                </tr>

            <?php endforeach; ?>
            </tbody>
        </table>

        <?php endif; ?>

    </div>

    <a href="dashboard.php" class="back-link">← Back to Dashboard</a>

</main>
</div>

<footer class="admin-footer">
    &copy; <?= date("Y") ?> Smart Gym Management System
</footer>

</body>
</html>
