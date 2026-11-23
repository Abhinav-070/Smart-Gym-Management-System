<?php
require_once "../config/database.php";

$errors = [];

$name = "";
$email = "";
$phone = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = strtolower(trim($_POST["email"] ?? ""));
    $phone = trim($_POST["phone"] ?? "");

    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    // Name validation
    if (empty($name)) {
        $errors[] = "Full name is required.";
    } elseif (strlen($name) < 3 || strlen($name) > 50) {
        $errors[] = "Name must be between 3 and 50 characters.";
    } elseif (!preg_match("/^[\p{L}]+(?:[ '\-][\p{L}]+)*$/u", $name)) {
        $errors[] = "Name can contain only letters, spaces, hyphens and apostrophes.";
    }

    // Email validation
    if (empty($email)) {
        $errors[] = "Email address is required.";
    } elseif (strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email address.";
    }

    // Phone validation
    if (empty($phone)) {
        $errors[] = "Phone number is required.";
    } elseif (!preg_match("/^[6-9][0-9]{9}$/", $phone)) {
        $errors[] = "Enter a valid 10-digit Indian mobile number.";
    }

    // Password validation
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8 || strlen($password) > 72) {
        $errors[] = "Password must contain 8 to 72 characters.";
    } elseif (!preg_match("/[A-Z]/", $password)) {
        $errors[] = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match("/[a-z]/", $password)) {
        $errors[] = "Password must contain at least one lowercase letter.";
    } elseif (!preg_match("/[0-9]/", $password)) {
        $errors[] = "Password must contain at least one number.";
    } elseif (!preg_match("/[^a-zA-Z0-9]/", $password)) {
        $errors[] = "Password must contain at least one special character.";
    }

    // Confirm password
    if (empty($confirm_password)) {
        $errors[] = "Please confirm your password.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Save registration
    if (empty($errors)) {
        try {
            $check = $conn->prepare(
                "SELECT id FROM clients WHERE email = ? LIMIT 1"
            );
            $check->execute([$email]);

            if ($check->fetch()) {
                $errors[] = "This email is already registered.";
            } else {
                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $insert = $conn->prepare(
                    "INSERT INTO clients (name, email, phone, password)
                     VALUES (?, ?, ?, ?)"
                );

                $insert->execute([
                    $name,
                    $email,
                    $phone,
                    $hashed_password
                ]);

                header("Location: login.php?registered=success");
                exit;
            }

        } catch (PDOException $e) {
            if ($e->getCode() === "23000") {
                $errors[] = "This email is already registered.";
            } else {
                $errors[] = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Client Registration | Smart Gym</title>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>

<body>

<div class="auth-container">

    <form class="auth-form" method="POST">

        <h2>Create Account</h2>
        <p>Join Smart Gym today</p>

        <?php if (!empty($errors)): ?>
            <div class="message">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <input
            type="text"
            name="name"
            placeholder="Full Name"
            value="<?= htmlspecialchars($name) ?>"
            maxlength="50"
            autocomplete="name"
            required
        >

        <input
            type="email"
            name="email"
            placeholder="Email Address"
            value="<?= htmlspecialchars($email) ?>"
            maxlength="150"
            autocomplete="email"
            required
        >

        <input
            type="tel"
            name="phone"
            placeholder="Mobile Number"
            value="<?= htmlspecialchars($phone) ?>"
            maxlength="10"
            pattern="[6-9][0-9]{9}"
            autocomplete="tel"
            required
        >

        <div class="password-field">
            <input
                type="password"
                name="password"
                id="password"
                placeholder="Password"
                minlength="8"
                maxlength="72"
                autocomplete="new-password"
                required
            >

    <button type="button" class="toggle-password"
            onclick="togglePassword('password', this)">
        👁
    </button>
        </div>

        <div class="password-field">
            <input
                type="password"
                name="confirm_password"
                id="confirm_password"
                placeholder="Confirm Password"
                minlength="8"
                maxlength="72"
                autocomplete="new-password"
                required
            >

    <button type="button" class="toggle-password"
            onclick="togglePassword('password', this)">
        👁
    </button>
        </div>

        <button type="submit" class="auth-btn">
            Register
        </button>

        <div class="auth-link">
            Already have an account?
            <a href="login.php">Login</a>
        </div>

    </form>

</div>

<script src="../assets/js/script.js"></script>

</body>
</html>