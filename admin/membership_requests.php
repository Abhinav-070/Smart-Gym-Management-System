
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
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        $errors[] = "Invalid request.";
    } else {

        $request_id = filter_input(INPUT_POST, "request_id", FILTER_VALIDATE_INT);
        $status = $_POST["status"] ?? "";

        if (!$request_id || !in_array($status, ["Approved", "Rejected"], true)) {
            $errors[] = "Invalid request details.";
        } else {

            try {
                $conn->beginTransaction();

                $stmt = $conn->prepare(
                    "SELECT mr.id, mr.client_id, mr.plan_name, mr.status,
                            p.amount, p.payment_method, p.status AS payment_status
                     FROM membership_requests mr
                     LEFT JOIN payments p ON p.request_id = mr.id
                     WHERE mr.id = ?
                     FOR UPDATE"
                );

                $stmt->execute([$request_id]);
                $request = $stmt->fetch();

                if (!$request || $request["status"] !== "Pending") {
                    throw new Exception("Request not found or already processed.");
                }

                if ($status === "Approved") {

                    if ($request["payment_status"] !== "Success") {
                        throw new Exception("Cannot approve a request without successful payment.");
                    }

                    $plan_durations = [
                        "Monthly" => 1,
                        "Quarterly" => 3,
                        "Half Yearly" => 6,
                        "Yearly" => 12
                    ];

                    $months = $plan_durations[$request["plan_name"]] ?? null;

                    if (!$months) {
                        throw new Exception("Invalid membership plan.");
                    }

                    $amount = (float) $request["amount"];

                    if ($amount <= 0) {
                        throw new Exception("Invalid payment amount.");
                    }

                    $start_date = date("Y-m-d");

                    $end_date = date(
                        "Y-m-d",
                        strtotime("+$months months", strtotime($start_date))
                    );

                    $insert = $conn->prepare(
                        "INSERT INTO memberships
                         (client_id, plan_name, start_date, end_date,
                          amount, payment_status)
                         VALUES (?, ?, ?, ?, ?, 'Paid')"
                    );

                    $insert->execute([
                        $request["client_id"],
                        $request["plan_name"],
                        $start_date,
                        $end_date,
                        $amount
                    ]);
                }

                $update = $conn->prepare(
                    "UPDATE membership_requests
                     SET status = ?
                     WHERE id = ? AND status = 'Pending'"
                );

                $update->execute([$status, $request_id]);

                if ($update->rowCount() !== 1) {
                    throw new Exception("This request has already been processed.");
                }

                $conn->commit();

                $success = "Membership request " . strtolower($status) . " successfully.";

            } catch (Throwable $e) {

                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }

                $errors[] = $e->getMessage();
            }
        }
    }
}

$stmt = $conn->query(
    "SELECT mr.*,
            c.name AS client_name,
            c.email,
            c.phone,
            p.amount,
            p.payment_method,
            p.transaction_id,
            p.status AS payment_status
     FROM membership_requests mr
     JOIN clients c ON mr.client_id = c.id
     LEFT JOIN payments p ON p.request_id = mr.id
     ORDER BY
        CASE WHEN mr.status = 'Pending' THEN 0 ELSE 1 END,
        mr.requested_at DESC"
);

$requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Membership Requests | Smart Gym</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .page-container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 20px;
        }

        .page-container h2 {
            color: #e63946;
            margin-bottom: 20px;
        }

        .table-container {
            overflow-x: auto;
            background: #1a1a1a;
            border-radius: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }

        th, td {
            padding: 13px;
            border: 1px solid #333;
            text-align: left;
        }

        th {
            background: #e63946;
            color: white;
        }

        .status-pending {
            color: #ffc107;
        }

        .status-approved,
        .status-success {
            color: #65d68a;
        }

        .status-rejected,
        .status-failed {
            color: #ff7777;
        }

        .action-form {
            display: flex;
            gap: 8px;
        }

        .action-form button {
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            color: white;
            cursor: pointer;
        }

        .approve {
            background: #198754;
        }

        .reject {
            background: #dc3545;
        }

        .message {
            padding: 12px;
            margin-bottom: 15px;
            background: #292929;
            border-radius: 5px;
        }

        .success {
            color: #65d68a;
        }

        .error {
            color: #ff7777;
        }
    </style>
</head>

<body>

<div class="page-container">

    <h2>Membership Requests</h2>

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

    <div class="table-container">

        <table>
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Plan</th>
                    <th>Amount</th>
                    <th>Payment Method</th>
                    <th>Payment Status</th>
                    <th>Transaction ID</th>
                    <th>Message</th>
                    <th>Requested Date</th>
                    <th>Request Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

                <?php if (empty($requests)): ?>

                    <tr>
                        <td colspan="12">No membership requests found.</td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($requests as $request): ?>

                        <tr>
                            <td><?= htmlspecialchars($request["client_name"]) ?></td>

                            <td><?= htmlspecialchars($request["email"]) ?></td>

                            <td><?= htmlspecialchars($request["phone"]) ?></td>

                            <td><?= htmlspecialchars($request["plan_name"]) ?></td>

                            <td>
                                <?= $request["amount"] !== null
                                    ? "₹" . number_format((float)$request["amount"], 2)
                                    : "-" ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($request["payment_method"] ?? "-") ?>
                            </td>

                            <td class="status-<?= strtolower($request["payment_status"] ?? "failed") ?>">
                                <?= htmlspecialchars($request["payment_status"] ?? "Not Paid") ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($request["transaction_id"] ?? "-") ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($request["message"] ?? "-") ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($request["requested_at"]) ?>
                            </td>

                            <td class="status-<?= strtolower($request["status"]) ?>">
                                <?= htmlspecialchars($request["status"]) ?>
                            </td>

                            <td>

                                <?php if ($request["status"] === "Pending"): ?>

                                    <form method="POST" class="action-form">

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="request_id"
                                            value="<?= (int)$request["id"] ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="status"
                                            value="Approved"
                                            class="approve"
                                            onclick="return confirm('Approve this paid membership request?')"
                                        >
                                            Approve
                                        </button>

                                        <button
                                            type="submit"
                                            name="status"
                                            value="Rejected"
                                            class="reject"
                                            onclick="return confirm('Reject this membership request?')"
                                        >
                                            Reject
                                        </button>

                                    </form>

                                <?php else: ?>

                                    -

                                <?php endif; ?>

                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>
        </table>

    </div>

    <p style="margin-top:20px;">
        <a href="dashboard.php" style="color:#e63946;">
            Back to Dashboard
        </a>
    </p>

</div>

</body>
</html>
