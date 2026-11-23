
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Dietitians | Smart Gym</title>

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

        .dietitian-form {
            max-width: 600px;
            padding: 30px;
            background: #1c1c1c;
            border-radius: 10px;
            margin-bottom: 45px;
        }

        .dietitian-form label {
            display: block;
            margin: 15px 0 8px;
        }

        .dietitian-form input {
            width: 100%;
            padding: 12px;
            background: #111;
            color: white;
            border: 1px solid #444;
            border-radius: 5px;
        }

        .dietitian-form button {
            width: 100%;
            margin-top: 25px;
            padding: 13px;
            background: #e63946;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .table-container {
            overflow-x: auto;
            background: #1c1c1c;
            border-radius: 8px;
        }

        table {
            width: 100%;
            min-width: 750px;
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

        .back-link {
            display: inline-block;
            margin-top: 25px;
            color: #e63946;
            text-decoration: none;
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
        <a href="trainers.php">Trainers</a>
        <a href="dietitians.php">Dietitians</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="container">

    <h1>Dietitian Management</h1>

    <?php foreach ($errors as $error): ?>
        <div class="message">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endforeach; ?>

    <?php if (($_GET["success"] ?? "") === "added"): ?>
        <div class="message success">Dietitian added successfully!</div>
    <?php elseif (($_GET["success"] ?? "") === "deleted"): ?>
        <div class="message success">Dietitian deleted successfully!</div>
    <?php endif; ?>

    <h2>Add Dietitian</h2>

    <form method="POST" class="dietitian-form">

        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

        <label>Full Name</label>
        <input type="text" name="name" maxlength="100" required>

        <label>Email</label>
        <input type="email" name="email" maxlength="150" required>

        <label>Phone Number</label>
        <input
            type="tel"
            name="phone"
            placeholder="10-digit mobile number"
            pattern="[6-9][0-9]{9}"
            minlength="10"
            maxlength="10"
            inputmode="numeric"
            oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)"
            required
        >

        <label>Password</label>
        <input type="password" name="password"
               minlength="8" maxlength="72" required>

        <button type="submit" name="add_dietitian">
            Add Dietitian
        </button>

    </form>

    <h2>All Dietitians</h2>

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
                        <td colspan="6">No dietitians found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($dietitians as $dietitian): ?>
                        <tr>
                            <td><?= (int)$dietitian["id"] ?></td>
                            <td><?= htmlspecialchars($dietitian["name"]) ?></td>
                            <td><?= htmlspecialchars($dietitian["email"]) ?></td>
                            <td><?= htmlspecialchars($dietitian["phone"]) ?></td>
                            <td><?= htmlspecialchars($dietitian["created_at"]) ?></td>
                            <td>
                                <form method="POST"
                                      onsubmit="return confirm('Delete this dietitian?');">

                                    <input type="hidden" name="csrf_token"
                                           value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

                                    <input type="hidden" name="dietitian_id"
                                           value="<?= (int)$dietitian["id"] ?>">

                                    <button type="submit"
                                            name="delete_dietitian"
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
