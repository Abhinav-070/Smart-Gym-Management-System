
<?php
session_start();

require_once "../config/database.php";

if (isset($_SESSION["dietitian_id"])) {
    header("Location: dashboard.php");
    exit;
}

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {
        $errors[] = "Email and password are required.";
    } else {
        $stmt = $conn->prepare(
            "SELECT id, name, email, password
            FROM dietitians WHERE email = ? LIMIT 1"
        );

        $stmt->execute([$email]);
        $dietitian = $stmt->fetch();

        if ($dietitian && password_verify($password, $dietitian["password"])) {

            session_regenerate_id(true);

            $_SESSION["dietitian_id"] = $dietitian["id"];
            $_SESSION["dietitian_name"] = $dietitian["name"];

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

    <title>Dietitian Login | Smart Gym</title>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>

<body>

<div class="auth-container">

    <form class="auth-form" method="POST">

        <h2>Dietitian Login</h2>
        <p>Access your dietitian account</p>

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
            type="email"
            name="email"
            placeholder="Email Address"
            autocomplete="username"
            required
        >

        <div class="password-field">
            <input
                type="password"
                name="password"
                id="dietitian_password"
                placeholder="Password"
                autocomplete="current-password"
                required
            >

            <button
                type="button"
                class="toggle-password"
                onclick="togglePassword('dietitian_password', this)"
                aria-label="Show password"
            >👁</button>
        </div>

        <button type="submit" class="auth-btn">
            Login
        </button>

    </form>

</div>

<script src="../assets/js/script.js"></script>

</body>
</html>
