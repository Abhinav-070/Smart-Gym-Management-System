<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION["client_id"])) {
    header("Location: login.php");
    exit;
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$client_id = $_SESSION["client_id"];
$errors = [];
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        $errors[] = "Invalid request. Please try again.";
    } else {

        $plan_name = trim($_POST["plan_name"] ?? "");
        $message = trim($_POST["message"] ?? "");

        $allowed_plans = [
            "Monthly",
            "Quarterly",
            "Half Yearly",
            "Yearly"
        ];

        if (!in_array($plan_name, $allowed_plans, true)) {
            $errors[] = "Please select a valid membership plan.";
        }

        if (strlen($message) > 500) {
            $errors[] = "Message cannot exceed 500 characters.";
        }

        if (empty($errors)) {

            $check = $conn->prepare(
                "SELECT id FROM membership_requests
                 WHERE client_id = ? AND status = 'Pending'
                 LIMIT 1"
            );
            $check->execute([$client_id]);

            if ($check->fetch()) {
                $errors[] = "You already have a pending membership request.";
            } else {

                $stmt = $conn->prepare(
                    "INSERT INTO membership_requests
                     (client_id, plan_name, message)
                     VALUES (?, ?, ?)"
                );

                $stmt->execute([
                    $client_id,
                    $plan_name,
                    $message ?: null
                ]);

                $success = "Membership request submitted successfully.";
            }
        }
    }
}

$stmt = $conn->prepare(
    "SELECT plan_name, message, status, requested_at
     FROM membership_requests
     WHERE client_id = ?
     ORDER BY requested_at DESC"
);
$stmt->execute([$client_id]);
$requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Request | Smart Gym</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .page-container {
            max-width: 850px;
            margin: 40px auto;
            padding: 20px;
        }

        .request-card {
            background: #1a1a1a;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .request-card h2 {
            color: #e63946;
            margin-bottom: 20px;
        }

        .request-card input,
        .request-card select,
        .request-card textarea {
            width: 100%;
            padding: 12px;
            margin: 10px 0 18px;
            background: #111;
            color: white;
            border: 1px solid #444;
            border-radius: 5px;
        }

        .request-card button {
            background: #e63946;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
        }

        .request-card button:hover {
            background: #c82333;
        }

        .message {
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 5px;
            background: #292929;
        }

        .success {
            color: #65d68a;
        }

        .error {
            color: #ff7777;
        }

        .request-table {
            width: 100%;
            border-collapse: collapse;
            background: #1a1a1a;
        }

        .request-table th,
        .request-table td {
            padding: 12px;
            border: 1px solid #333;
            text-align: left;
        }

        .request-table th {
            background: #e63946;
            color: white;
        }

        .status-pending { color: #ffc107; }
        .status-approved { color: #65d68a; }
        .status-rejected { color: #ff7777; }

        @media (max-width: 600px) {
            .request-table {
                font-size: 13px;
            }

            .request-table th,
            .request-table td {
                padding: 8px;
            }
        }
    </style>
</head>

<body>

<div class="page-container">

    <div class="request-card">
        <h2>Request Membership</h2>

        <?php if ($success): ?>
            <div class="message success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="message error">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>"
            >

            <label>Membership Plan</label>

            <select name="plan_name" required>
                <option value="">Select a plan</option>
                <option value="Monthly">Monthly</option>
                <option value="Quarterly">Quarterly</option>
                <option value="Half Yearly">Half Yearly</option>
                <option value="Yearly">Yearly</option>
            </select>

            <label>Message (Optional)</label>

            <textarea
                name="message"
                rows="4"
                maxlength="500"
                placeholder="Write any additional information..."
            ></textarea>

            <button type="submit">Submit Request</button>

        </form>
    </div>

    <div class="request-card">
        <h2>My Membership Requests</h2>

        <?php if (empty($requests)): ?>
            <p>No membership requests found.</p>
        <?php else: ?>

            <div style="overflow-x:auto;">
                <table class="request-table">
                    <thead>
                        <tr>
                            <th>Plan</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?= htmlspecialchars($request["plan_name"]) ?></td>

                                <td>
                                    <?= htmlspecialchars($request["message"] ?? "-") ?>
                                </td>

                                <td>
                                    <span class="status-<?= strtolower($request["status"]) ?>">
                                        <?= htmlspecialchars($request["status"]) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= htmlspecialchars($request["requested_at"]) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </div>

    <p>
        <a href="dashboard.php" style="color:#e63946;">
            Back to Dashboard
        </a>
    </p>

</div>

</body>
</html>