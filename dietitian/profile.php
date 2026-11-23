
<?php
session_start();

require_once "../config/database.php";

if (!isset($_SESSION["dietitian_id"])) {
    header("Location: login.php");
    exit;
}

$dietitian_id = $_SESSION["dietitian_id"];
$errors = [];
$success = "";

$stmt = $conn->prepare(
    "SELECT id, name, email, phone, password
     FROM dietitians WHERE id = ?"
);
$stmt->execute([$dietitian_id]);
$dietitian = $stmt->fetch();

if (!$dietitian) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"] ?? "")) {
        $errors[] = "Invalid request. Please try again.";
    } else {

        $name = trim($_POST["name"] ?? "");
        $phone = trim($_POST["phone"] ?? "");
        $current_password = $_POST["current_password"] ?? "";
        $new_password = $_POST["new_password"] ?? "";
        $confirm_password = $_POST["confirm_password"] ?? "";

        if (
            strlen($name) < 3 ||
            strlen($name) > 100 ||
            !preg_match("/^[\p{L}]+(?:[ '\-][\p{L}]+)*$/u", $name)
        ) {
            $errors[] = "Enter a valid name.";
        }

        if (!preg_match('/^[6-9][0-9]{9}$/', $phone)) {
            $errors[] = "Enter a valid 10-digit Indian mobile number.";
        }

        $change_password = (
            $current_password !== "" ||
            $new_password !== "" ||
            $confirm_password !== ""
        );

        if ($change_password) {

            if ($current_password === "") {
                $errors[] = "Enter your current password.";
            } elseif (!password_verify($current_password, $dietitian["password"])) {
                $errors[] = "Current password is incorrect.";
            }

            if (
                strlen($new_password) < 8 ||
                strlen($new_password) > 72 ||
                !preg_match("/[A-Z]/", $new_password) ||
                !preg_match("/[a-z]/", $new_password) ||
                !preg_match("/[0-9]/", $new_password) ||
                !preg_match("/[^a-zA-Z0-9]/", $new_password)
            ) {
                $errors[] = "New password must contain uppercase, lowercase, number and special character (8–72 characters).";
            }

            if ($new_password !== $confirm_password) {
                $errors[] = "New password and confirmation do not match.";
            }
        }

        if (empty($errors)) {

            if ($change_password) {

                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare(
                    "UPDATE dietitians
                     SET name = ?, phone = ?, password = ?
                     WHERE id = ?"
                );

                $stmt->execute([
                    $name,
                    $phone,
                    $password_hash,
                    $dietitian_id
                ]);

            } else {

                $stmt = $conn->prepare(
                    "UPDATE dietitians
                     SET name = ?, phone = ?
                     WHERE id = ?"
                );

                $stmt->execute([
                    $name,
                    $phone,
                    $dietitian_id
                ]);
            }

            $_SESSION["dietitian_name"] = $name;

            header("Location: profile.php?success=updated");
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

    <title>My Profile | Smart Gym</title>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">

    <style>
        .profile-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: #1c1c1c;
            border-radius: 10px;
        }

        .profile-container h2 {
            color: #e63946;
            margin-bottom: 25px;
        }

        .profile-container label {
            display: block;
            margin: 15px 0 8px;
        }

        .profile-container input {
            width: 100%;
            padding: 12px;
            background: #111;
            color: white;
            border: 1px solid #444;
            border-radius: 5px;
        }

        .profile-container input[readonly] {
            color: #999;
            cursor: not-allowed;
        }

        .profile-container button {
            width: 100%;
            margin-top: 25px;
            padding: 13px;
            background: #e63946;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            background: #442024;
            color: #ffb3b8;
            border-radius: 5px;
        }

        .success {
            background: #183c27;
            color: #a8e6b8;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #e63946;
            text-decoration: none;
        }
    </style>
</head>

<body>

<header class="navbar">
    <h2>SMART GYM</h2>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="diet_plans.php">Diet Plans</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="profile-container">

    <h2>My Profile</h2>

    <?php if (($_GET["success"] ?? "") === "updated"): ?>
        <div class="message success">
            Profile updated successfully!
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="message">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST">

        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

        <label>Full Name</label>
        <input type="text" name="name"
               value="<?= htmlspecialchars($_POST["name"] ?? $dietitian["name"]) ?>"
               maxlength="100" required>

        <label>Email Address</label>
        <input type="email"
               value="<?= htmlspecialchars($dietitian["email"]) ?>"
               readonly>

        <label>Phone Number</label>
        <input type="tel" name="phone"
               value="<?= htmlspecialchars($_POST["phone"] ?? $dietitian["phone"]) ?>"
               pattern="[6-9][0-9]{9}"
               minlength="10" maxlength="10"
               inputmode="numeric"
               oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)"
               required>

        <h3 style="color:#e63946; margin-top:30px;">
            Change Password
        </h3>

        <label>Current Password</label>
        <input type="password" name="current_password"
               autocomplete="current-password">

        <label>New Password</label>
        <input type="password" name="new_password"
               minlength="8" maxlength="72"
               autocomplete="new-password">

        <label>Confirm New Password</label>
        <input type="password" name="confirm_password"
               minlength="8" maxlength="72"
               autocomplete="new-password">

        <button type="submit">Update Profile</button>

    </form>

    <a href="dashboard.php" class="back-link">
        ← Back to Dashboard
    </a>

</div>

</body>
</html>
