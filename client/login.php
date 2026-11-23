<?php
require_once "../config/database.php";

$message = "";

if (isset($_GET["registered"]) && $_GET["registered"] === "success") {
    $message = "Registration successful! Please log in.";
}

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {
        $errors[] = "Please enter your email and password.";
    } else {
        $stmt = $conn->prepare(
            "SELECT id, name, email, password FROM clients WHERE email = ? LIMIT 1"
        );
        $stmt->execute([$email]);
        $client = $stmt->fetch();

        if ($client && password_verify($password, $client["password"])) {
            session_start();

            session_regenerate_id(true);

            $_SESSION["client_id"] = $client["id"];
            $_SESSION["client_name"] = $client["name"];

            header("Location: dashboard.php");
            exit;
        } else {
            $errors[] = "Invalid email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Login | Smart Gym</title>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>

<div class="auth-container">

    <form class="auth-form" method="POST">

        <h2>Welcome Back</h2>
        <p>Login to your Smart Gym account</p>

        <?php if ($message): ?>
            <div class="message">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="message">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <input
            type="email"
            name="email"
            placeholder="Email Address"
            autocomplete="email"
            required
        >

        <div class="password-field">
            <input
                type="password"
                name="password"
                id="login_password"
                placeholder="Password"
                autocomplete="current-password"
                required
            >

            <button
                type="button"
                class="toggle-password"
                onclick="togglePassword('login_password', this)"
                aria-label="Show password"
            >👁</button>
        </div>

        <button type="submit" class="auth-btn">
            Login
        </button>

        <div class="auth-link">
            Don't have an account?
            <a href="register.php">Register</a>
        </div>

    </form>

</div>

<script src="../assets/js/script.js"></script>

</body>
</html>