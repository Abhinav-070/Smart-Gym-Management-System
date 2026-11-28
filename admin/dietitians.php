
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

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"] ?? "")) {
        $errors[] = "Invalid request. Please try again.";
    }

    // Add dietitian
    elseif (isset($_POST["add_dietitian"])) {

        $name = trim($_POST["name"] ?? "");
        $email = strtolower(trim($_POST["email"] ?? ""));
        $phone = trim($_POST["phone"] ?? "");
        $password = $_POST["password"] ?? "";

        if (
            !preg_match("/^[\p{L}]+(?:[ '\-][\p{L}]+)*$/u", $name) ||
            strlen($name) < 3 || strlen($name) > 100
        ) {
            $errors[] = "Enter a valid dietitian name.";
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
            $errors[] = "Enter a valid email address.";
        }

        if (!preg_match('/^[6-9][0-9]{9}$/', $phone)) {
            $errors[] = "Enter a valid 10-digit Indian mobile number.";
        }

        if (
            strlen($password) < 8 || strlen($password) > 72 ||
            !preg_match("/[A-Z]/", $password) ||
            !preg_match("/[a-z]/", $password) ||
            !preg_match("/[0-9]/", $password) ||
            !preg_match("/[^a-zA-Z0-9]/", $password)
        ) {
            $errors[] = "Password must contain uppercase, lowercase, number and special character (8–72 characters).";
        }

        if (empty($errors)) {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare(
                    "INSERT INTO dietitians (name, email, phone, password)
                     VALUES (?, ?, ?, ?)"
                );

                $stmt->execute([$name, $email, $phone, $hash]);

                header("Location: dietitians.php?success=added");
                exit;

            } catch (PDOException $e) {
                if ($e->getCode() === "23000") {
                    $errors[] = "This email is already registered.";
                } else {
                    $errors[] = "Unable to add dietitian. Please try again.";
                }
            }
        }
    }

    // Delete dietitian
    elseif (isset($_POST["delete_dietitian"])) {

        $dietitian_id = filter_var(
            $_POST["dietitian_id"] ?? "",
            FILTER_VALIDATE_INT
        );

        if ($dietitian_id) {
            $stmt = $conn->prepare(
                "DELETE FROM dietitians WHERE id = ?"
            );
            $stmt->execute([$dietitian_id]);
        }

        header("Location: dietitians.php?success=deleted");
        exit;
    }
}

$dietitians = $conn->query(
    "SELECT id, name, email, phone, created_at
     FROM dietitians
     ORDER BY id DESC"
)->fetchAll();

$admin_name = $_SESSION["admin_name"] ?? "Admin";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Dietitians | Smart Gym</title>

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
            color: #fff;
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
            font-size: 29px;
            margin: 0 0 10px;
        }

        .page-heading h1 span {
            color: #e63946;
        }

        .page-heading p {
            color: #999;
            font-size: 14px;
            margin: 0;
        }

        .section-title {
            font-size: 19px;
            margin: 35px 0 20px;
            padding-left: 12px;
            border-left: 3px solid #e63946;
        }

        /* Form */
        .form-panel {
            max-width: 750px;
            padding: 28px;
            background: rgba(25, 25, 25, 0.95);
            border: 1px solid #303030;
            border-radius: 13px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 9px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            color: #ccc;
            font-size: 13px;
            font-weight: bold;
        }

        .form-group input {
            width: 100%;
            padding: 13px 14px;
            background: #101010;
            color: #fff;
            border: 1px solid #3a3a3a;
            border-radius: 7px;
            outline: none;
            font-size: 14px;
            transition: 0.25s;
        }

        .form-group input:focus {
            border-color: #e63946;
            box-shadow: 0 0 0 2px rgba(230, 57, 70, 0.12);
        }

        .form-group input::placeholder {
            color: #666;
        }

        .form-hint {
            color: #777;
            font-size: 11px;
            line-height: 1.5;
        }

        .submit-btn {
            width: 100%;
            margin-top: 25px;
            padding: 14px;
            background: #e63946;
            color: white;
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

        /* Alerts */
        .message {
            padding: 14px 18px;
            margin-bottom: 15px;
            border-radius: 8px;
            background: rgba(110, 35, 42, 0.35);
            border: 1px solid #71343a;
            color: #ffb3b8;
            font-size: 13px;
        }

        .success {
            background: rgba(24, 60, 39, 0.55);
            border-color: #28633d;
            color: #a8e6b8;
        }

        /* Dietitian list */
        .list-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 18px;
        }

        .list-header h2 {
            margin: 0;
        }

        .count-badge {
            padding: 8px 13px;
            background: rgba(230, 57, 70, 0.13);
            border: 1px solid rgba(230, 57, 70, 0.4);
            color: #ff7b84;
            border-radius: 20px;
            font-size: 12px;
        }

        .table-container {
            overflow-x: auto;
            background: rgba(25, 25, 25, 0.96);
            border: 1px solid #303030;
            border-radius: 12px;
        }

        table {
            width: 100%;
            min-width: 800px;
            border-collapse: collapse;
        }

        th, td {
            padding: 17px 16px;
            text-align: left;
            border-bottom: 1px solid #303030;
            font-size: 13px;
        }

        th {
            background: #111;
            color: #e63946;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        td {
            color: #ccc;
        }

        tbody tr {
            transition: 0.2s;
        }

        tbody tr:hover {
            background: rgba(255, 255, 255, 0.025);
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .dietitian-name {
            color: #fff;
            font-weight: bold;
        }

        .delete-btn {
            padding: 8px 13px;
            background: rgba(230, 57, 70, 0.12);
            color: #ff7b84;
            border: 1px solid rgba(230, 57, 70, 0.4);
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: 0.2s;
        }

        .delete-btn:hover {
            background: #e63946;
            color: #fff;
        }

        .empty-state {
            text-align: center;
            padding: 35px !important;
            color: #888;
        }

        .back-link {
            display: inline-block;
            margin-top: 28px;
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

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-panel {
                padding: 20px;
            }

            .list-header {
                align-items: flex-start;
                flex-direction: column;
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
            <a href="memberships.php">▤ Memberships</a>
            <a href="trainers.php">♟ Trainers</a>
            <a href="dietitians.php" class="active">✚ Dietitians</a>
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
            <h2>Dietitian Management</h2>
            <div class="admin-tag">Administrator</div>
        </div>

        <section class="page-heading">
            <h1>Manage <span>Dietitians</span></h1>
            <p>Add and manage the dietitian accounts in your gym.</p>
        </section>

        <!-- Alerts -->
        <?php foreach ($errors as $error): ?>
            <div class="message">
                <?= e($error) ?>
            </div>
        <?php endforeach; ?>

        <?php if (($_GET["success"] ?? "") === "added"): ?>
            <div class="message success">
                Dietitian added successfully!
            </div>
        <?php elseif (($_GET["success"] ?? "") === "deleted"): ?>
            <div class="message success">
                Dietitian deleted successfully!
            </div>
        <?php endif; ?>

        <!-- Add Dietitian -->
        <h2 class="section-title">Add New Dietitian</h2>

        <section class="form-panel">

            <form method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($_SESSION["csrf_token"]) ?>"
                >

                <div class="form-grid">

                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            maxlength="100"
                            placeholder="Enter full name"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            maxlength="150"
                            placeholder="Enter email address"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            placeholder="10-digit mobile number"
                            pattern="[6-9][0-9]{9}"
                            minlength="10"
                            maxlength="10"
                            inputmode="numeric"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            minlength="8"
                            maxlength="72"
                            placeholder="Create a strong password"
                            required
                        >
                        <span class="form-hint">
                            Use uppercase, lowercase, a number and a special character.
                        </span>
                    </div>

                </div>

                <button type="submit" name="add_dietitian" class="submit-btn">
                    + Add Dietitian
                </button>

            </form>

        </section>

        <!-- Dietitian List -->
        <h2 class="section-title">Dietitian Directory</h2>

        <div class="list-header">
            <h2>All Dietitians</h2>

            <span class="count-badge">
                <?= count($dietitians) ?> Registered
            </span>
        </div>

        <div class="table-container">

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (empty($dietitians)): ?>

                        <tr>
                            <td colspan="6" class="empty-state">
                                No dietitians found.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($dietitians as $dietitian): ?>

                            <tr>
                                <td><?= (int)$dietitian["id"] ?></td>

                                <td class="dietitian-name">
                                    <?= e($dietitian["name"]) ?>
                                </td>

                                <td><?= e($dietitian["email"]) ?></td>

                                <td><?= e($dietitian["phone"]) ?></td>

                                <td><?= e($dietitian["created_at"]) ?></td>

                                <td>
                                    <form
                                        method="POST"
                                        onsubmit="return confirm('Are you sure you want to delete this dietitian?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e($_SESSION["csrf_token"]) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="dietitian_id"
                                            value="<?= (int)$dietitian["id"] ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="delete_dietitian"
                                            class="delete-btn"
                                        >
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

</div>

<footer class="admin-footer">
    &copy; <?= date("Y") ?> Smart Gym Management System
</footer>

</body>
</html>
