
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

// Add trainer
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_trainer"])) {

    if (!hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"] ?? "")) {
        $errors[] = "Invalid request. Please try again.";
    } else {
        $name = trim($_POST["name"] ?? "");
        $email = strtolower(trim($_POST["email"] ?? ""));
        $phone = trim($_POST["phone"] ?? "");
        $specialization = trim($_POST["specialization"] ?? "");
        $password = $_POST["password"] ?? "";

        if (
            !preg_match("/^[\p{L}]+(?:[ '\-][\p{L}]+)*$/u", $name) ||
            strlen($name) < 3 || strlen($name) > 100
        ) {
            $errors[] = "Enter a valid trainer name.";
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
            $errors[] = "Enter a valid email address.";
        }

        if (!preg_match('/^[6-9][0-9]{9}$/', $phone)) {
            $errors[] = "Enter a valid 10-digit Indian mobile number.";
        }

        if ($specialization === "" || strlen($specialization) > 100) {
            $errors[] = "Enter a valid specialization.";
        }

        if (
            strlen($password) < 8 || strlen($password) > 72 ||
            !preg_match("/[A-Z]/", $password) ||
            !preg_match("/[a-z]/", $password) ||
            !preg_match("/[0-9]/", $password) ||
            !preg_match("/[^a-zA-Z0-9]/", $password)
        ) {
            $errors[] = "Password must be 8–72 characters and include uppercase, lowercase, number and special character.";
        }

        if (empty($errors)) {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare(
                    "INSERT INTO trainers (name, email, phone, specialization, password)
                    VALUES (?, ?, ?, ?, ?)"
                );

                $stmt->execute([
                    $name,
                    $email,
                    $phone,
                    $specialization,
                    $hash
                ]);

                header("Location: trainers.php?success=added");
                exit;

            } catch (PDOException $e) {
                if ($e->getCode() === "23000") {
                    $errors[] = "This email is already registered.";
                } else {
                    $errors[] = "Unable to add trainer. Please try again.";
                }
            }
        }
    }
}

// Delete trainer
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_trainer"])) {

    if (!hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"] ?? "")) {
        $errors[] = "Invalid request. Please try again.";
    } else {
        $trainer_id = filter_var(
            $_POST["trainer_id"] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($trainer_id) {
            try {
                $delete = $conn->prepare("DELETE FROM trainers WHERE id = ?");
                $delete->execute([$trainer_id]);
            } catch (PDOException $e) {
                $errors[] = "Unable to delete trainer. Trainer may be assigned to clients.";
            }
        }

        if (empty($errors)) {
            header("Location: trainers.php?success=deleted");
            exit;
        }
    }
}

// Get trainers
$trainers = $conn->query(
    "SELECT id, name, email, phone, specialization, created_at
    FROM trainers ORDER BY id DESC"
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
<title>Trainers | Smart Gym Admin</title>
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

/* MAIN */
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
    gap: 15px;
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

.alert ul {
    margin: 0;
    padding-left: 20px;
}

/* PANEL */
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

/* FORM */
.trainer-form {
    display: grid;
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap: 18px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group.full {
    grid-column: 1 / -1;
}

.form-group label {
    color: #ccc;
    font-size: 13px;
    font-weight: 600;
}

.form-group input {
    width: 100%;
    padding: 13px 14px;
    background: #101010;
    border: 1px solid #333;
    color: #fff;
    border-radius: 9px;
    outline: none;
    font-size: 14px;
}

.form-group input:focus {
    border-color: #e63946;
    box-shadow: 0 0 0 3px rgba(230,57,70,.10);
}

.form-group input::placeholder {
    color: #666;
}

.password-note {
    font-size: 11px;
    color: #777;
    line-height: 1.6;
}

.primary-btn {
    border: 0;
    background: #e63946;
    color: #fff;
    padding: 13px 20px;
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

/* TABLE */
.table-wrapper {
    overflow-x: auto;
    border: 1px solid #292929;
    border-radius: 12px;
}

table {
    width: 100%;
    min-width: 850px;
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

.trainer-name {
    font-weight: 700;
    color: #fff;
}

.trainer-id {
    color: #777;
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

.delete-btn {
    padding: 8px 12px;
    background: rgba(230,57,70,.12);
    border: 1px solid rgba(230,57,70,.25);
    color: #ff737d;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: .2s;
}

.delete-btn:hover {
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

    .trainer-form {
        grid-template-columns: 1fr;
    }

    .form-group.full {
        grid-column: auto;
    }

    .panel-header h2 {
        font-size: 17px;
    }

    .primary-btn {
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
        <a href="trainers.php" class="active">Trainers</a>
        <a href="dietitians.php">Dietitians</a>
        <a href="assign_clients.php">Assign Clients</a>
        <a href="complaints.php">Complaints</a>
        <a href="reports.php">Reports</a>
        <a href="logout.php">Logout</a>
    </nav>
</aside>

<!-- MAIN CONTENT -->
<main class="admin-main">

    <div class="topbar">
        <div class="topbar-label">ADMIN PANEL / <strong>TRAINER MANAGEMENT</strong></div>
    </div>

    <div class="page-heading">
        <h1>Trainer Management</h1>
        <p>Manage your gym trainers and their professional details.</p>
    </div>

    <!-- ALERTS -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (($_GET["success"] ?? "") === "added"): ?>
        <div class="alert alert-success">Trainer added successfully.</div>
    <?php elseif (($_GET["success"] ?? "") === "deleted"): ?>
        <div class="alert alert-success">Trainer deleted successfully.</div>
    <?php endif; ?>

    <!-- ADD TRAINER -->
    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>Add New Trainer</h2>
                <p>Enter trainer details to create a trainer account.</p>
            </div>
        </div>

        <form method="POST" class="trainer-form">

            <input type="hidden" name="csrf_token"
                   value="<?= e($_SESSION["csrf_token"]) ?>">

            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name"
                       placeholder="Enter full name" maxlength="100" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                       placeholder="trainer@example.com" maxlength="150" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone"
                       placeholder="10-digit mobile number"
                       pattern="[6-9][0-9]{9}"
                       minlength="10" maxlength="10"
                       inputmode="numeric"
                       oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)"
                       required>
            </div>

            <div class="form-group">
                <label for="specialization">Specialization</label>
                <input type="text" id="specialization" name="specialization"
                       placeholder="e.g. Weight Training"
                       maxlength="100" required>
            </div>

            <div class="form-group full">
                <label for="password">Trainer Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Create a strong password"
                       minlength="8" maxlength="72" required>
                <span class="password-note">
                    Must contain uppercase, lowercase, number and special character.
                </span>
            </div>

            <div class="form-group full">
                <button type="submit" name="add_trainer" class="primary-btn">
                    + Add Trainer
                </button>
            </div>

        </form>
    </section>

    <!-- TRAINERS TABLE -->
    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>All Trainers</h2>
                <p>Registered trainers in the gym management system.</p>
            </div>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Trainer</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Specialization</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (empty($trainers)): ?>
                    <tr>
                        <td colspan="6" class="empty-state">No trainers found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($trainers as $trainer): ?>
                        <tr>
                            <td class="trainer-id">#<?= (int)$trainer["id"] ?></td>
                            <td class="trainer-name"><?= e($trainer["name"]) ?></td>
                            <td><?= e($trainer["email"]) ?></td>
                            <td><?= e($trainer["phone"]) ?></td>
                            <td>
                                <span class="specialization-badge">
                                    <?= e($trainer["specialization"]) ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST"
                                      onsubmit="return confirm('Are you sure you want to delete this trainer?');">

                                    <input type="hidden" name="csrf_token"
                                           value="<?= e($_SESSION["csrf_token"]) ?>">

                                    <input type="hidden" name="trainer_id"
                                           value="<?= (int)$trainer["id"] ?>">

                                    <button type="submit" name="delete_trainer"
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
    </section>

</main>

<footer class="admin-footer">
    &copy; <?= date("Y") ?> Smart Gym Management System
</footer>

</div>

</body>
</html>
