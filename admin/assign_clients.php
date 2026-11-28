
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
$success = "";

if (isset($_GET["success"])) {
    $success = "Client assigned successfully!";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"] ?? "")) {
        $errors[] = "Invalid request. Please try again.";
    } else {

        // Assign client
        if (isset($_POST["assign_client"])) {

            $client_id = filter_var($_POST["client_id"] ?? "", FILTER_VALIDATE_INT);
            $trainer_id = filter_var($_POST["trainer_id"] ?? "", FILTER_VALIDATE_INT);

            if (!$client_id || !$trainer_id) {
                $errors[] = "Please select a valid client and trainer.";
            } else {
                try {
                    $stmt = $conn->prepare(
                        "INSERT INTO trainer_clients (trainer_id, client_id)
                         VALUES (?, ?)"
                    );

                    $stmt->execute([$trainer_id, $client_id]);

                    header("Location: assign_clients.php?success=1");
                    exit;

                } catch (PDOException $e) {
                    if ($e->getCode() === "23000") {
                        $errors[] = "This client is already assigned to this trainer.";
                    } else {
                        $errors[] = "Unable to assign client. Please try again.";
                    }
                }
            }
        }

        // Remove assignment
        if (isset($_POST["remove_assignment"])) {

            $assignment_id = filter_var(
                $_POST["assignment_id"] ?? "",
                FILTER_VALIDATE_INT
            );

            if ($assignment_id) {
                $stmt = $conn->prepare(
                    "DELETE FROM trainer_clients WHERE id = ?"
                );
                $stmt->execute([$assignment_id]);
            }

            header("Location: assign_clients.php");
            exit;
        }
    }
}

// Fetch clients
$clients = $conn->query(
    "SELECT id, name, email FROM clients ORDER BY name"
)->fetchAll();

// Fetch trainers
$trainers = $conn->query(
    "SELECT id, name, specialization FROM trainers ORDER BY name"
)->fetchAll();

// Fetch assignments
$assignments = $conn->query(
    "SELECT tc.id, c.name AS client_name, c.email,
            t.name AS trainer_name, t.specialization,
            tc.assigned_at
     FROM trainer_clients tc
     JOIN clients c ON tc.client_id = c.id
     JOIN trainers t ON tc.trainer_id = t.id
     ORDER BY tc.id DESC"
)->fetchAll();

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Assign Clients | Smart Gym Admin</title>

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

/* PANELS */
.panel {
    background: rgba(19,19,19,.94);
    border: 1px solid #292929;
    border-radius: 16px;
    padding: 25px;
    margin-bottom: 28px;
    box-shadow: 0 12px 35px rgba(0,0,0,.18);
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 23px;
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

/* ASSIGNMENT FORM */
.assignment-form {
    display: grid;
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 9px;
}

.form-group label {
    color: #ccc;
    font-size: 13px;
    font-weight: 600;
}

.form-group select {
    width: 100%;
    padding: 14px;
    background: #101010;
    border: 1px solid #333;
    color: #fff;
    border-radius: 9px;
    outline: none;
    font-size: 14px;
    cursor: pointer;
}

.form-group select:focus {
    border-color: #e63946;
    box-shadow: 0 0 0 3px rgba(230,57,70,.10);
}

.form-group select option {
    background: #151515;
    color: #fff;
}

.primary-btn {
    grid-column: 1 / -1;
    border: 0;
    background: #e63946;
    color: #fff;
    padding: 14px 20px;
    border-radius: 9px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: .2s;
}

.primary-btn:hover {
    background: #c92d3a;
    transform: translateY(-1px);
}

/* ASSIGNMENT SUMMARY */
.assignment-count {
    display: inline-block;
    padding: 7px 13px;
    background: rgba(230,57,70,.12);
    border: 1px solid rgba(230,57,70,.25);
    color: #ff929a;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
}

/* TABLE */
.table-wrapper {
    overflow-x: auto;
    border: 1px solid #292929;
    border-radius: 12px;
}

table {
    width: 100%;
    min-width: 800px;
    border-collapse: collapse;
}

thead {
    background: #111;
}

th {
    padding: 16px;
    text-align: left;
    color: #e63946;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .7px;
    white-space: nowrap;
}

td {
    padding: 15px 16px;
    color: #ddd;
    font-size: 13px;
    border-top: 1px solid #262626;
}

tbody tr {
    transition: .2s;
}

tbody tr:hover {
    background: rgba(255,255,255,.025);
}

.client-name,
.trainer-name {
    font-weight: 700;
    color: #fff;
}

.specialization-badge {
    display: inline-block;
    padding: 6px 10px;
    background: rgba(230,57,70,.10);
    border: 1px solid rgba(230,57,70,.2);
    border-radius: 20px;
    color: #ff929a;
    font-size: 12px;
    white-space: nowrap;
}

.remove-btn {
    padding: 8px 13px;
    background: rgba(230,57,70,.12);
    border: 1px solid rgba(230,57,70,.25);
    color: #ff737d;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: .2s;
}

.remove-btn:hover {
    background: #e63946;
    color: #fff;
}

.empty-state {
    text-align: center;
    color: #777;
    padding: 35px;
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

    .panel {
        padding: 17px;
    }

    .assignment-form {
        grid-template-columns: 1fr;
    }

    .primary-btn {
        grid-column: auto;
        width: 100%;
    }

    .panel-header h2 {
        font-size: 17px;
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
        <a href="assign_clients.php" class="active">Assign Clients</a>
        <a href="complaints.php">Complaints</a>
        <a href="reports.php">Reports</a>
        <a href="logout.php">Logout</a>
    </nav>
</aside>

<!-- MAIN -->
<main class="admin-main">

    <div class="topbar">
        <div class="topbar-label">
            ADMIN PANEL / <strong>CLIENT ASSIGNMENTS</strong>
        </div>
    </div>

    <div class="page-heading">
        <h1>Client Assignments</h1>
        <p>Assign clients to trainers and manage existing assignments.</p>
    </div>

    <!-- MESSAGES -->
    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endforeach; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif; ?>

    <!-- NEW ASSIGNMENT -->
    <section class="panel">

        <div class="panel-header">
            <div>
                <h2>New Assignment</h2>
                <p>Select a client and trainer to create an assignment.</p>
            </div>
        </div>

        <form method="POST" class="assignment-form">

            <input type="hidden" name="csrf_token"
                   value="<?= e($_SESSION["csrf_token"]) ?>">

            <div class="form-group">
                <label for="client_id">Select Client</label>

                <select name="client_id" id="client_id" required>
                    <option value="">Choose Client</option>

                    <?php foreach ($clients as $client): ?>
                        <option value="<?= (int)$client["id"] ?>">
                            <?= e($client["name"]) ?> (<?= e($client["email"]) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="trainer_id">Select Trainer</label>

                <select name="trainer_id" id="trainer_id" required>
                    <option value="">Choose Trainer</option>

                    <?php foreach ($trainers as $trainer): ?>
                        <option value="<?= (int)$trainer["id"] ?>">
                            <?= e($trainer["name"]) ?> - <?= e($trainer["specialization"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" name="assign_client" class="primary-btn">
                + Assign Client
            </button>

        </form>
    </section>

    <!-- CURRENT ASSIGNMENTS -->
    <section class="panel">

        <div class="panel-header">
            <div>
                <h2>Current Assignments</h2>
                <p>Overview of clients assigned to trainers.</p>
            </div>

            <span class="assignment-count">
                <?= count($assignments) ?> Assignments
            </span>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Email</th>
                        <th>Trainer</th>
                        <th>Specialization</th>
                        <th>Assigned Date</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (empty($assignments)): ?>
                    <tr>
                        <td colspan="6" class="empty-state">
                            No assignments found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($assignments as $assignment): ?>
                        <tr>
                            <td class="client-name">
                                <?= e($assignment["client_name"]) ?>
                            </td>

                            <td><?= e($assignment["email"]) ?></td>

                            <td class="trainer-name">
                                <?= e($assignment["trainer_name"]) ?>
                            </td>

                            <td>
                                <span class="specialization-badge">
                                    <?= e($assignment["specialization"]) ?>
                                </span>
                            </td>

                            <td><?= e($assignment["assigned_at"]) ?></td>

                            <td>
                                <form method="POST"
                                      onsubmit="return confirm('Remove this assignment?');">

                                    <input type="hidden" name="csrf_token"
                                           value="<?= e($_SESSION["csrf_token"]) ?>">

                                    <input type="hidden" name="assignment_id"
                                           value="<?= (int)$assignment["id"] ?>">

                                    <button type="submit"
                                            name="remove_assignment"
                                            class="remove-btn">
                                        Remove
                                    </button>
                                </form>
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
    &copy; <?= date("Y") ?> Smart Gym Management System
</footer>

</div>

</body>
</html>
