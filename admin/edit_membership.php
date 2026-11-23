
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

$membership_id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$membership_id) {
    header("Location: memberships.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM memberships WHERE id = ?");
$stmt->execute([$membership_id]);
$membership = $stmt->fetch();

if (!$membership) {
    header("Location: memberships.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"] ?? "")) {
        $errors[] = "Invalid request. Please try again.";
    } else {

        $amount = $_POST["amount"] ?? "";
        $payment_status = $_POST["payment_status"] ?? "";

        if (!is_numeric($amount) || $amount < 0 || $amount > 99999999.99) {
            $errors[] = "Enter a valid amount.";
        }

        if (!in_array($payment_status, ["Paid", "Pending"], true)) {
            $errors[] = "Select a valid payment status.";
        }

        if (empty($errors)) {

            $update = $conn->prepare(
                "UPDATE memberships
                 SET amount = ?, payment_status = ?
                 WHERE id = ?"
            );

            $update->execute([
                $amount,
                $payment_status,
                $membership_id
            ]);

            header("Location: memberships.php?success=updated");
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

    <title>Edit Membership | Smart Gym</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 25px;
        }

        .form-box {
            background: #1c1c1c;
            padding: 30px;
            border-radius: 10px;
        }

        h2 {
            color: #e63946;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin: 15px 0 8px;
        }

        input, select {
            width: 100%;
            padding: 12px;
            background: #111;
            color: white;
            border: 1px solid #444;
            border-radius: 5px;
        }

        button {
            width: 100%;
            padding: 13px;
            margin-top: 25px;
            background: #e63946;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .message {
            color: #ff7777;
            background: #442024;
            padding: 12px;
            margin-bottom: 15px;
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

<div class="container">

    <div class="form-box">

        <h2>Edit Membership Payment</h2>

        <?php if ($errors): ?>
            <div class="message">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <p>
            <strong>Membership:</strong>
            <?= htmlspecialchars($membership["plan_name"]) ?>
        </p>

        <form method="POST">

            <input type="hidden" name="csrf_token"
                   value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

            <label>Amount (₹)</label>

            <input type="number"
                   name="amount"
                   min="0"
                   max="99999999.99"
                   step="0.01"
                   value="<?= htmlspecialchars((string)$membership["amount"]) ?>"
                   required>

            <label>Payment Status</label>

            <select name="payment_status" required>
                <option value="Pending"
                    <?= $membership["payment_status"] === "Pending" ? "selected" : "" ?>>
                    Pending
                </option>

                <option value="Paid"
                    <?= $membership["payment_status"] === "Paid" ? "selected" : "" ?>>
                    Paid
                </option>
            </select>

            <button type="submit">Update Membership</button>

        </form>

        <a href="memberships.php" class="back-link">
            ← Back to Memberships
        </a>

    </div>

</div>

</body>
</html>
