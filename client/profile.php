<?php
session_start();

require_once "../config/database.php";

if (!isset($_SESSION["client_id"])) {
    header("Location: login.php");
    exit;
}

$client_id = $_SESSION["client_id"];
$errors = [];
$success = "";

$stmt = $conn->prepare(
    "SELECT name, email, phone FROM clients WHERE id = ?"
);
$stmt->execute([$client_id]);
$client = $stmt->fetch();

if (!$client) {
    session_destroy();
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $phone = trim($_POST["phone"] ?? "");

    if (empty($name)) {
        $errors[] = "Name is required.";
    } elseif (
        strlen($name) < 3 ||
        strlen($name) > 50 ||
        !preg_match("/^[\p{L}]+(?:[ '\-][\p{L}]+)*$/u", $name)
    ) {
        $errors[] = "Enter a valid name (3–50 characters).";
    }

    if (!preg_match("/^[6-9][0-9]{9}$/", $phone)) {
        $errors[] = "Enter a valid 10-digit Indian mobile number.";
    }

    if (empty($errors)) {
        $update = $conn->prepare(
            "UPDATE clients SET name = ?, phone = ? WHERE id = ?"
        );

        $update->execute([$name, $phone, $client_id]);

        $_SESSION["client_name"] = $name;

        $success = "Profile updated successfully!";

        $stmt->execute([$client_id]);
        $client = $stmt->fetch();
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
</head>

<body>

<header class="navbar">
    <h2>SMART GYM</h2>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="profile.php">My Profile</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="auth-container">

    <form class="auth-form" method="POST">

        <h2>My Profile</h2>
        <p>View and update your personal details</p>

        <?php if ($success): ?>
            <div class="message">
                <?= htmlspecialchars($success) ?>
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

        <label>Full Name</label>
        <input
            type="text"
            name="name"
            value="<?= htmlspecialchars($client["name"]) ?>"
            maxlength="50"
            required
        >

        <label>Email Address</label>
        <input
            type="email"
            value="<?= htmlspecialchars($client["email"]) ?>"
            readonly
        >

        <label>Phone Number</label>
        <input
            type="tel"
            name="phone"
            value="<?= htmlspecialchars($client["phone"]) ?>"
            maxlength="10"
            pattern="[6-9][0-9]{9}"
            required
        >

        <button type="submit" class="auth-btn">
            Update Profile
        </button>

        <div class="auth-link">
            <a href="dashboard.php">Back to Dashboard</a>
        </div>

    </form>

</div>

</body>
</html>