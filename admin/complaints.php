
<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$errors = [];
$success = "";

// CSRF protection
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Update complaint
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $token)) {
        $errors[] = "Invalid request. Please try again.";
    } else {

        $complaint_id = filter_var(
            $_POST["complaint_id"] ?? null,
            FILTER_VALIDATE_INT
        );

        $status = $_POST["status"] ?? "";
        $admin_response = trim($_POST["admin_response"] ?? "");

        $allowed_statuses = [
            "Pending",
            "In Progress",
            "Resolved"
        ];

        if (!$complaint_id) {
            $errors[] = "Invalid complaint.";
        }

        if (!in_array($status, $allowed_statuses, true)) {
            $errors[] = "Invalid complaint status.";
        }

        if (strlen($admin_response) > 5000) {
            $errors[] = "Response cannot exceed 5000 characters.";
        }

        if (empty($errors)) {

            $update = $conn->prepare("
                UPDATE complaints
                SET status = ?,
                    admin_response = ?
                WHERE id = ?
            ");

            $update->execute([
                $status,
                $admin_response !== "" ? $admin_response : null,
                $complaint_id
            ]);

            if ($update->rowCount() > 0) {
                $success = "Complaint updated successfully.";
            } else {
                $check = $conn->prepare("
                    SELECT id FROM complaints WHERE id = ?
                ");
                $check->execute([$complaint_id]);

                if ($check->fetch()) {
                    $success = "No changes were made.";
                } else {
                    $errors[] = "Complaint not found.";
                }
            }
        }
    }
}

// Search and filter
$search = trim($_GET["search"] ?? "");
$filter_status = $_GET["status"] ?? "";

$allowed_filters = [
    "",
    "Pending",
    "In Progress",
    "Resolved"
];

if (!in_array($filter_status, $allowed_filters, true)) {
    $filter_status = "";
}

$sql = "
    SELECT
        c.id,
        c.subject,
        c.message,
        c.admin_response,
        c.status,
        c.created_at,
        c.updated_at,
        cl.name AS client_name,
        cl.email AS client_email,
        cl.phone AS client_phone
    FROM complaints c
    JOIN clients cl ON cl.id = c.client_id
    WHERE 1 = 1
";

$params = [];

if ($search !== "") {
    $sql .= "
        AND (
            cl.name LIKE ?
            OR cl.email LIKE ?
            OR c.subject LIKE ?
            OR c.message LIKE ?
        )
    ";

    $search_term = "%" . $search . "%";

    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($filter_status !== "") {
    $sql .= " AND c.status = ?";
    $params[] = $filter_status;
}

$sql .= "
    ORDER BY
        CASE
            WHEN c.status = 'Pending' THEN 1
            WHEN c.status = 'In Progress' THEN 2
            ELSE 3
        END,
        c.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Complaint statistics
$stats_stmt = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'Pending') AS pending,
        SUM(status = 'In Progress') AS in_progress,
        SUM(status = 'Resolved') AS resolved
    FROM complaints
");

$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Complaints | Smart Gym Admin</title>

<link rel="stylesheet" href="../assets/css/style.css">

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #090909;
    color: #f5f5f5;
    font-family: Arial, Helvetica, sans-serif;
}

.admin-layout {
    min-height: 100vh;
}

/* SIDEBAR */
.admin-sidebar {
    width: 250px;
    position: fixed;
    inset: 0 auto 0 0;
    z-index: 1000;
    background: rgba(10,10,10,.97);
    border-right: 1px solid #242424;
    padding: 28px 18px;
    overflow-y: auto;
}

.admin-brand {
    font-size: 22px;
    font-weight: 900;
    letter-spacing: 1.5px;
    color: #fff;
    margin: 0 0 35px;
}

.admin-brand span {
    color: #e63946;
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
    border-radius: 9px;
    font-size: 14px;
    transition: .2s;
}

.admin-sidebar nav a:hover,
.admin-sidebar nav a.active {
    background: rgba(230,57,70,.12);
    color: #fff;
    border-left: 3px solid #e63946;
}

/* MAIN CONTENT */
.admin-main {
    margin-left: 250px;
    min-height: 100vh;
    padding: 35px 40px 110px;
    background:
        linear-gradient(rgba(5,5,5,.90), rgba(5,5,5,.94)),
        url("../uploads/admin-bg.jpg") center/cover fixed;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.topbar-label {
    color: #888;
    font-size: 13px;
}

.topbar-label strong {
    color: #eee;
}

.page-heading {
    margin-bottom: 28px;
}

.page-heading h1 {
    font-size: 30px;
    margin: 0 0 10px;
    font-weight: 800;
}

.page-heading p {
    color: #929292;
    font-size: 14px;
    margin: 0;
}

/* ALERTS */
.alert {
    padding: 14px 17px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 14px;
}

.alert-error {
    background: rgba(230,57,70,.12);
    border: 1px solid rgba(230,57,70,.3);
    color: #ff9ca3;
}

.alert-success {
    background: rgba(40,170,95,.12);
    border: 1px solid rgba(40,170,95,.25);
    color: #91e0ae;
}

/* STATISTICS */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0,1fr));
    gap: 17px;
    margin-bottom: 28px;
}

.stat-card {
    background: rgba(19,19,19,.94);
    border: 1px solid #292929;
    border-radius: 14px;
    padding: 21px;
    transition: .2s;
}

.stat-card:hover {
    transform: translateY(-3px);
    border-color: #555;
}

.stat-label {
    color: #999;
    font-size: 13px;
    margin-bottom: 14px;
}

.stat-value {
    color: #fff;
    font-size: 29px;
    font-weight: 800;
}

.stat-card.pending-card {
    border-left: 3px solid #e6a23c;
}

.stat-card.progress-card {
    border-left: 3px solid #4b9be8;
}

.stat-card.resolved-card {
    border-left: 3px solid #39b878;
}

.stat-card.total-card {
    border-left: 3px solid #e63946;
}

/* FILTER PANEL */
.panel {
    background: rgba(19,19,19,.94);
    border: 1px solid #292929;
    border-radius: 16px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 12px 35px rgba(0,0,0,.18);
}

.panel-header {
    margin-bottom: 20px;
}

.panel-header h2 {
    font-size: 19px;
    margin: 0;
}

.panel-header p {
    color: #888;
    margin: 7px 0 0;
    font-size: 13px;
}

.filters {
    display: grid;
    grid-template-columns: 1fr 210px auto auto;
    gap: 12px;
    align-items: center;
}

.filters input,
.filters select {
    width: 100%;
    padding: 13px 14px;
    background: #101010;
    border: 1px solid #333;
    color: #fff;
    border-radius: 9px;
    outline: none;
    font-size: 14px;
}

.filters input:focus,
.filters select:focus {
    border-color: #e63946;
}

.search-btn {
    padding: 13px 22px;
    background: #e63946;
    color: #fff;
    border: 0;
    border-radius: 9px;
    font-weight: 700;
    cursor: pointer;
    transition: .2s;
}

.search-btn:hover {
    background: #c92d3a;
}

.reset-btn {
    padding: 13px 18px;
    border: 1px solid #444;
    color: #ccc;
    background: transparent;
    border-radius: 9px;
    text-decoration: none;
    text-align: center;
    font-size: 13px;
}

.reset-btn:hover {
    border-color: #e63946;
    color: #fff;
}

/* COMPLAINT CARD */
.complaint-card {
    background: rgba(19,19,19,.96);
    border: 1px solid #292929;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 20px;
}

.complaint-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 15px;
    margin-bottom: 18px;
}

.complaint-top h2 {
    font-size: 19px;
    color: #fff;
    margin: 0;
    line-height: 1.5;
}

.complaint-id {
    color: #777;
    font-size: 12px;
    margin-bottom: 7px;
}

.status {
    display: inline-block;
    padding: 7px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}

.status.pending {
    background: rgba(230,162,60,.13);
    color: #ffc66d;
    border: 1px solid rgba(230,162,60,.25);
}

.status.in-progress {
    background: rgba(75,155,232,.13);
    color: #80bdff;
    border: 1px solid rgba(75,155,232,.25);
}

.status.resolved {
    background: rgba(57,184,120,.13);
    color: #75dfa6;
    border: 1px solid rgba(57,184,120,.25);
}

.client-info {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 20px;
    color: #999;
    font-size: 13px;
    margin-bottom: 18px;
}

.client-info strong {
    color: #ddd;
}

.details {
    background: #101010;
    border: 1px solid #292929;
    padding: 17px;
    border-radius: 10px;
    margin: 15px 0;
}

.details strong {
    color: #e63946;
    font-size: 13px;
}

.details p {
    color: #ccc;
    line-height: 1.7;
    font-size: 14px;
    margin: 10px 0 0;
    overflow-wrap: anywhere;
}

.submitted-date {
    color: #777;
    font-size: 12px;
    margin: 15px 0 20px;
}

/* RESPONSE FORM */
.response-form {
    border-top: 1px solid #292929;
    padding-top: 20px;
}

.form-group {
    margin-bottom: 18px;
}

.form-group label {
    display: block;
    margin-bottom: 9px;
    color: #ccc;
    font-size: 13px;
    font-weight: 600;
}

.form-group textarea,
.form-group select {
    width: 100%;
    padding: 13px 14px;
    background: #101010;
    color: #fff;
    border: 1px solid #333;
    border-radius: 9px;
    outline: none;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 14px;
}

.form-group textarea {
    min-height: 115px;
    resize: vertical;
    line-height: 1.6;
}

.form-group textarea:focus,
.form-group select:focus {
    border-color: #e63946;
}

.update-btn {
    padding: 13px 22px;
    background: #e63946;
    color: #fff;
    border: none;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: .2s;
}

.update-btn:hover {
    background: #c92d3a;
    transform: translateY(-1px);
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #888;
}

.empty-state h3 {
    color: #ddd;
    margin-bottom: 8px;
}

/* FOOTER */
.admin-footer {
    position: fixed;
    bottom: 0;
    left: 250px;
    right: 0;
    z-index: 100;
    background: rgba(8,8,8,.96);
    border-top: 1px solid #252525;
    text-align: center;
    padding: 15px;
    color: #777;
    font-size: 12px;
}

/* RESPONSIVE */
@media(max-width: 1100px) {
    .stats-grid {
        grid-template-columns: repeat(2,1fr);
    }

    .filters {
        grid-template-columns: 1fr 1fr;
    }
}

@media(max-width: 1000px) {
    .admin-sidebar {
        width: 100%;
        height: auto;
        position: relative;
        padding: 18px;
        border-right: none;
        border-bottom: 1px solid #252525;
    }

    .admin-brand {
        margin-bottom: 15px;
    }

    .admin-sidebar nav {
        flex-direction: row;
        flex-wrap: wrap;
    }

    .admin-sidebar nav a {
        padding: 10px 12px;
    }

    .admin-main {
        margin-left: 0;
        padding: 25px 20px 110px;
        background-attachment: scroll;
    }

    .admin-footer {
        left: 0;
    }
}

@media(max-width: 600px) {
    .admin-main {
        padding: 22px 13px 110px;
    }

    .page-heading h1 {
        font-size: 24px;
    }

    .stats-grid {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    .stat-card {
        padding: 15px;
    }

    .stat-value {
        font-size: 24px;
    }

    .panel {
        padding: 17px;
    }

    .filters {
        grid-template-columns: 1fr;
    }

    .complaint-card {
        padding: 17px;
    }

    .complaint-top {
        flex-direction: column;
    }

    .update-btn {
        width: 100%;
    }
}
</style>
</head>

<body>

<div class="admin-layout">

<!-- SIDEBAR -->
<aside class="admin-sidebar">
    <h2 class="admin-brand">SMART GYM <span>ADMIN</span></h2>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="clients.php">Clients</a>
        <a href="memberships.php">Memberships</a>
        <a href="trainers.php">Trainers</a>
        <a href="dietitians.php">Dietitians</a>
        <a href="assign_clients.php">Assign Clients</a>
        <a href="complaints.php" class="active">Complaints</a>
        <a href="reports.php">Reports</a>
        <a href="logout.php">Logout</a>
    </nav>
</aside>

<!-- MAIN CONTENT -->
<main class="admin-main">

    <div class="topbar">
        <div class="topbar-label">
            ADMIN PANEL / <strong>COMPLAINT MANAGEMENT</strong>
        </div>
    </div>

    <div class="page-heading">
        <h1>Complaint Management</h1>
        <p>Review client complaints, provide responses and manage their status.</p>
    </div>

    <!-- ALERTS -->
    <?php if ($success): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endforeach; ?>

    <!-- STATISTICS -->
    <section class="stats-grid">

        <div class="stat-card total-card">
            <div class="stat-label">Total Complaints</div>
            <div class="stat-value"><?= (int)($stats["total"] ?? 0) ?></div>
        </div>

        <div class="stat-card pending-card">
            <div class="stat-label">Pending</div>
            <div class="stat-value"><?= (int)($stats["pending"] ?? 0) ?></div>
        </div>

        <div class="stat-card progress-card">
            <div class="stat-label">In Progress</div>
            <div class="stat-value"><?= (int)($stats["in_progress"] ?? 0) ?></div>
        </div>

        <div class="stat-card resolved-card">
            <div class="stat-label">Resolved</div>
            <div class="stat-value"><?= (int)($stats["resolved"] ?? 0) ?></div>
        </div>

    </section>

    <!-- SEARCH AND FILTER -->
    <section class="panel">

        <div class="panel-header">
            <h2>Search & Filter Complaints</h2>
            <p>Find complaints by client details, subject or message.</p>
        </div>

        <form method="GET" class="filters">

            <input
                type="text"
                name="search"
                placeholder="Search client or complaint..."
                value="<?= e($search) ?>"
            >

            <select name="status">
                <option value="">All Statuses</option>

                <option value="Pending"
                    <?= $filter_status === "Pending" ? "selected" : "" ?>>
                    Pending
                </option>

                <option value="In Progress"
                    <?= $filter_status === "In Progress" ? "selected" : "" ?>>
                    In Progress
                </option>

                <option value="Resolved"
                    <?= $filter_status === "Resolved" ? "selected" : "" ?>>
                    Resolved
                </option>
            </select>

            <button type="submit" class="search-btn">Search</button>

            <a href="complaints.php" class="reset-btn">Reset</a>

        </form>
    </section>

    <!-- COMPLAINT LIST -->
    <div class="page-heading">
        <h1 style="font-size:22px;">Client Complaints</h1>
        <p><?= count($complaints) ?> complaint(s) found</p>
    </div>

    <?php if ($complaints): ?>

        <?php foreach ($complaints as $complaint): ?>

            <?php
                $status_class = match ($complaint["status"]) {
                    "Resolved" => "resolved",
                    "In Progress" => "in-progress",
                    default => "pending"
                };
            ?>

            <section class="complaint-card">

                <div class="complaint-top">
                    <div>
                        <div class="complaint-id">
                            COMPLAINT #<?= (int)$complaint["id"] ?>
                        </div>

                        <h2><?= e($complaint["subject"]) ?></h2>
                    </div>

                    <span class="status <?= $status_class ?>">
                        <?= e($complaint["status"]) ?>
                    </span>
                </div>

                <div class="client-info">
                    <span><strong>Client:</strong> <?= e($complaint["client_name"]) ?></span>
                    <span><?= e($complaint["client_email"]) ?></span>
                    <span><?= e($complaint["client_phone"]) ?></span>
                </div>

                <div class="details">
                    <strong>COMPLAINT DETAILS</strong>
                    <p><?= nl2br(e($complaint["message"])) ?></p>
                </div>

                <p class="submitted-date">
                    Submitted: <?= e($complaint["created_at"]) ?>
                </p>

                <form method="POST" class="response-form">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($_SESSION["csrf_token"]) ?>"
                    >

                    <input
                        type="hidden"
                        name="complaint_id"
                        value="<?= (int)$complaint["id"] ?>"
                    >

                    <div class="form-group">
                        <label>Admin Response</label>

                        <textarea
                            name="admin_response"
                            maxlength="5000"
                            placeholder="Write your response to the client..."
                        ><?= e($complaint["admin_response"] ?? "") ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Complaint Status</label>

                        <select name="status" required>

                            <option value="Pending"
                                <?= $complaint["status"] === "Pending" ? "selected" : "" ?>>
                                Pending
                            </option>

                            <option value="In Progress"
                                <?= $complaint["status"] === "In Progress" ? "selected" : "" ?>>
                                In Progress
                            </option>

                            <option value="Resolved"
                                <?= $complaint["status"] === "Resolved" ? "selected" : "" ?>>
                                Resolved
                            </option>

                        </select>
                    </div>

                    <button type="submit" class="update-btn">
                        Update Complaint
                    </button>

                </form>

            </section>

        <?php endforeach; ?>

    <?php else: ?>

        <section class="panel empty-state">
            <h3>No Complaints Found</h3>
            <p>There are no complaints matching your search or selected filter.</p>
        </section>

    <?php endif; ?>

</main>

<footer class="admin-footer">
    &copy; <?= date("Y") ?> Smart Gym Management System
</footer>

</div>

</body>
</html>
