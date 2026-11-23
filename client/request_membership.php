
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

$client_id = (int) $_SESSION["client_id"];
$errors = [];
$success = "";

$plans = [
    "Monthly" => 999,
    "Quarterly" => 2499,
    "Half Yearly" => 4499,
    "Yearly" => 7999
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        $errors[] = "Invalid request. Please try again.";
    } else {

        $plan_name = trim($_POST["plan_name"] ?? "");
        $message = trim($_POST["message"] ?? "");
        $payment_method = $_POST["payment_method"] ?? "";
        $payment_result = $_POST["payment_result"] ?? "";

        if (!isset($plans[$plan_name])) {
            $errors[] = "Please select a valid membership plan.";
        }

        if (!in_array($payment_method, ["UPI", "Card", "Cash"], true)) {
            $errors[] = "Please select a valid payment method.";
        }

        if (!in_array($payment_result, ["Success", "Failed"], true)) {
            $errors[] = "Please select a payment result.";
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

                try {
                    $conn->beginTransaction();

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

                    $request_id = (int) $conn->lastInsertId();
                    $amount = $plans[$plan_name];

                    $transaction_id = "DEMO-" . strtoupper(bin2hex(random_bytes(8)));

                    $payment = $conn->prepare(
                        "INSERT INTO payments
                         (request_id, client_id, amount, payment_method,
                          transaction_id, status)
                         VALUES (?, ?, ?, ?, ?, ?)"
                    );

                    $payment->execute([
                        $request_id,
                        $client_id,
                        $amount,
                        $payment_method,
                        $transaction_id,
                        $payment_result
                    ]);

                    if ($payment_result === "Success") {
                        $conn->commit();
                        $success = "Demo payment successful! Membership request submitted. Awaiting admin approval.";
                    } else {
                        $conn->rollBack();
                        $success = "Demo payment failed. No membership request was submitted. Please try again.";
                    }

                } catch (Throwable $e) {

                    if ($conn->inTransaction()) {
                        $conn->rollBack();
                    }

                    $errors[] = "Unable to process payment. Please try again.";
                }
            }
        }
    }
}

$stmt = $conn->prepare(
    "SELECT mr.plan_name, mr.message, mr.status, mr.requested_at,
            p.amount, p.payment_method, p.transaction_id,
            p.status AS payment_status, p.payment_date
     FROM membership_requests mr
     LEFT JOIN payments p ON p.request_id = mr.id
     WHERE mr.client_id = ?
     ORDER BY mr.requested_at DESC"
);

$stmt->execute([$client_id]);
$requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Membership | Smart Gym</title>

<link rel="stylesheet" href="../assets/css/style.css">

<style>
.page-container {
    max-width: 950px;
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
    box-sizing: border-box;
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

.success { color: #65d68a; }
.error { color: #ff7777; }

.demo-notice {
    background: #292929;
    border-left: 4px solid #e63946;
    padding: 14px;
    margin-bottom: 20px;
    color: #ddd;
}

.price-display {
    color: #65d68a;
    font-size: 22px;
    font-weight: bold;
    margin: 10px 0 20px;
}

.request-table {
    width: 100%;
    border-collapse: collapse;
    background: #1a1a1a;
    min-width: 750px;
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
.status-approved,
.status-success { color: #65d68a; }
.status-rejected,
.status-failed { color: #ff7777; }

@media(max-width: 600px) {
    .request-card { padding: 18px; }
    .request-table { font-size: 12px; }
}
</style>
</head>

<body>

<div class="page-container">

    <div class="request-card">

        <h2>Membership & Payment</h2>

        <div class="demo-notice">
            <strong>DEMO PAYMENT MODE</strong><br>
            This is a simulated payment system. No real money will be charged.
        </div>

        <?php if ($success): ?>
            <div class="message success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="message error">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <input type="hidden" name="csrf_token"
                   value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

            <label>Membership Plan</label>

            <select name="plan_name" id="plan_name" required>
                <option value="">Select a plan</option>
                <?php foreach ($plans as $name => $price): ?>
                    <option value="<?= htmlspecialchars($name) ?>"
                        data-price="<?= $price ?>">
                        <?= htmlspecialchars($name) ?> - ₹<?= number_format($price) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div class="price-display">
                Amount: ₹<span id="amount">0</span>
            </div>

            <label>Message (Optional)</label>

            <textarea name="message" rows="3" maxlength="500"
                      placeholder="Additional information..."></textarea>

            <h3 style="color:#e63946;">Payment Details</h3>

            <label>Payment Method</label>

            <select name="payment_method" required>
                <option value="">Select payment method</option>
                <option value="UPI">UPI</option>
                <option value="Card">Card</option>
                <option value="Cash">Cash</option>
            </select>

            <label>Simulate Payment Result</label>

            <select name="payment_result" required>
                <option value="Success">Successful Payment</option>
                <option value="Failed">Failed Payment</option>
            </select>

            <button type="submit">
                Pay & Submit Membership Request
            </button>

        </form>
    </div>

    <div class="request-card">

        <h2>My Membership Requests & Payments</h2>

        <?php if (empty($requests)): ?>

            <p>No membership requests found.</p>

        <?php else: ?>

            <div style="overflow-x:auto;">
                <table class="request-table">
                    <thead>
                        <tr>
                            <th>Plan</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Method</th>
                            <th>Request Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td><?= htmlspecialchars($request["plan_name"]) ?></td>

                            <td>
                                ₹<?= number_format((float)($request["amount"] ?? 0), 2) ?>
                            </td>

                            <td class="status-<?= strtolower($request["payment_status"] ?? "failed") ?>">
                                <?= htmlspecialchars($request["payment_status"] ?? "Not recorded") ?>
                            </td>

                            <td><?= htmlspecialchars($request["payment_method"] ?? "-") ?></td>

                            <td class="status-<?= strtolower($request["status"]) ?>">
                                <?= htmlspecialchars($request["status"]) ?>
                            </td>

                            <td><?= htmlspecialchars($request["requested_at"]) ?></td>
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

<script>
const planSelect = document.getElementById("plan_name");
const amountDisplay = document.getElementById("amount");

planSelect.addEventListener("change", function () {
    const price = this.options[this.selectedIndex].dataset.price || 0;
    amountDisplay.textContent = Number(price).toLocaleString("en-IN");
});
</script>

</body>
</html>
