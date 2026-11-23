
<?php
session_start();

require_once "../config/database.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$errors = [];
$success = "";

// CSRF protection
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Update complaint
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $token)) {
        $errors[] = "Invalid request. Please try again.";
    } else {

        $complaint_id = filter_var(
            $_POST["complaint_id"] ?? null,
            FILTER_VALIDATE_INT
        );

        $status = $_POST["status"] ?? "";
        $admin_response = trim($_POST["admin_response"] ?? "");

        $allowed_statuses = [
            "Pending",
            "In Progress",
            "Resolved"
        ];

        if (!$complaint_id) {
            $errors[] = "Invalid complaint.";
        }

        if (!in_array($status, $allowed_statuses, true)) {
            $errors[] = "Invalid complaint status.";
        }

        if (strlen($admin_response) > 5000) {
            $errors[] = "Response cannot exceed 5000 characters.";
        }

        if (empty($errors)) {

            // Update only an existing complaint
            $update = $conn->prepare("
                UPDATE complaints
                SET status = ?,
                    admin_response = ?
                WHERE id = ?
            ");

            $update->execute([
                $status,
                $admin_response !== "" ? $admin_response : null,
                $complaint_id
            ]);

            if ($update->rowCount() > 0) {
                $success = "Complaint updated successfully.";
            } else {
                $check = $conn->prepare("
                    SELECT id FROM complaints WHERE id = ?
                ");
                $check->execute([$complaint_id]);

                if ($check->fetch()) {
                    $success = "No changes were made.";
                } else {
                    $errors[] = "Complaint not found.";
                }
            }
        }
    }
}

// Search and filter
$search = trim($_GET["search"] ?? "");
$filter_status = $_GET["status"] ?? "";

$allowed_filters = [
    "",
    "Pending",
    "In Progress",
    "Resolved"
];

if (!in_array($filter_status, $allowed_filters, true)) {
    $filter_status = "";
}

$sql = "
    SELECT
        c.id,
        c.subject,
        c.message,
        c.admin_response,
        c.status,
        c.created_at,
        c.updated_at,
        cl.name AS client_name,
        cl.email AS client_email,
        cl.phone AS client_phone
    FROM complaints c
    JOIN clients cl ON cl.id = c.client_id
    WHERE 1 = 1
";

$params = [];

if ($search !== "") {
    $sql .= "
        AND (
            cl.name LIKE ?
            OR cl.email LIKE ?
            OR c.subject LIKE ?
            OR c.message LIKE ?
        )
    ";

    $search_term = "%" . $search . "%";

    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($filter_status !== "") {
    $sql .= " AND c.status = ?";
    $params[] = $filter_status;
}

$sql .= "
    ORDER BY
        CASE
            WHEN c.status = 'Pending' THEN 1
            WHEN c.status = 'In Progress' THEN 2
            ELSE 3
        END,
        c.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Complaint statistics
$stats_stmt = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'Pending') AS pending,
        SUM(status = 'In Progress') AS in_progress,
        SUM(status = 'Resolved') AS resolved
    FROM complaints
");

$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Complaints | Smart Gym Admin</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .page {
            max-width: 1200px;
            margin: auto;
            padding: 45px 20px;
        }

        h1, h2 {
            color: #e63946;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }

        .stat-card {
            background: #1c1c1c;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #e63946;
        }

        .stat-card h3 {
            color: #e63946;
        }

        .stat-card p {
            color: white;
            font-size: 25px;
            font-weight: bold;
        }

        .card {
            background: #1c1c1c;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            border-left: 4px solid #e63946;
        }

        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 25px;
        }

        input, select, textarea {
            padding: 11px;
            background: #111;
            color: white;
            border: 1px solid #444;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .filters input {
            flex: 1;
            min-width: 200px;
        }

        .filters select {
            min-width: 160px;
        }

        textarea {
            width: 100%;
            min-height: 110px;
            resize: vertical;
        }

        button {
            padding: 11px 22px;
            background: #e63946;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #c1121f;
        }

        .message {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .success {
            background: #183c27;
            color: #65d68a;
        }

        .error {
            background: #442020;
            color: #ff7070;
        }

        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 5px;
            font-weight: bold;
        }

        .pending {
            background: #443819;
            color: #ffc107;
        }

        .in-progress {
            background: #263b52;
            color: #79baff;
        }

        .resolved {
            background: #183c27;
            color: #65d68a;
        }

        .details {
            background: #252525;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }

        .muted {
            color: #bbb;
        }

        label {
            display: block;
            margin: 15px 0 8px;
            color: white;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #e63946;
            text-decoration: none;
        }

        @media (max-width: 600px) {
            .filters {
                flex-direction: column;
            }

            .filters input,
            .filters select,
            .filters button {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<header class="navbar">
    <h2>SMART GYM</h2>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="page">

    <a href="dashboard.php" class="back">← Back to Dashboard</a>

    <h1>Complaint Management</h1>

    <p class="muted">
        Review client complaints, provide responses and manage their status.
    </p>

    <?php if ($success): ?>
        <div class="message success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <div class="message error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endforeach; ?>

    <section class="stats">

        <div class="stat-card">
            <h3>Total Complaints</h3>
            <p><?= (int) ($stats["total"] ?? 0) ?></p>
        </div>

        <div class="stat-card">
            <h3>Pending</h3>
            <p><?= (int) ($stats["pending"] ?? 0) ?></p>
        </div>

        <div class="stat-card">
            <h3>In Progress</h3>
            <p><?= (int) ($stats["in_progress"] ?? 0) ?></p>
        </div>

        <div class="stat-card">
            <h3>Resolved</h3>
            <p><?= (int) ($stats["resolved"] ?? 0) ?></p>
        </div>

    </section>

    <form method="GET" class="filters">

        <input
            type="text"
            name="search"
            placeholder="Search client or complaint..."
            value="<?= htmlspecialchars($search) ?>"
        >

        <select name="status">
            <option value="">All Statuses</option>

            <option value="Pending"
                <?= $filter_status === "Pending" ? "selected" : "" ?>>
                Pending
            </option>

            <option value="In Progress"
                <?= $filter_status === "In Progress" ? "selected" : "" ?>>
                In Progress
            </option>

            <option value="Resolved"
                <?= $filter_status === "Resolved" ? "selected" : "" ?>>
                Resolved
            </option>
        </select>

        <button type="submit">Search</button>

        <a href="complaints.php" class="back">Reset</a>

    </form>

    <h2>Client Complaints</h2>

    <?php if ($complaints): ?>

        <?php foreach ($complaints as $complaint): ?>

            <section class="card">

                <h2>
                    <?= htmlspecialchars($complaint["subject"]) ?>
                </h2>

                <p>
                    <strong>Client:</strong>
                    <?= htmlspecialchars($complaint["client_name"]) ?>
                </p>

                <p class="muted">
                    <?= htmlspecialchars($complaint["client_email"]) ?>
                    |
                    <?= htmlspecialchars($complaint["client_phone"]) ?>
                </p>

                <div class="details">

                    <strong>Complaint Details</strong>

                    <p>
                        <?= nl2br(htmlspecialchars($complaint["message"])) ?>
                    </p>

                </div>

                <?php
                    $status_class = match ($complaint["status"]) {
                        "Resolved" => "resolved",
                        "In Progress" => "in-progress",
                        default => "pending"
                    };
                ?>

                <p>
                    <strong>Status:</strong>

                    <span class="status <?= $status_class ?>">
                        <?= htmlspecialchars($complaint["status"]) ?>
                    </span>
                </p>

                <p class="muted">
                    Submitted:
                    <?= htmlspecialchars($complaint["created_at"]) ?>
                </p>

                <form method="POST">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>"
                    >

                    <input
                        type="hidden"
                        name="complaint_id"
                        value="<?= (int) $complaint["id"] ?>"
                    >

                    <label>Admin Response</label>

                    <textarea
                        name="admin_response"
                        maxlength="5000"
                        placeholder="Write your response..."
                    ><?= htmlspecialchars($complaint["admin_response"] ?? "") ?></textarea>

                    <label>Complaint Status</label>

                    <select name="status" required>

                        <option value="Pending"
                            <?= $complaint["status"] === "Pending" ? "selected" : "" ?>>
                            Pending
                        </option>

                        <option value="In Progress"
                            <?= $complaint["status"] === "In Progress" ? "selected" : "" ?>>
                            In Progress
                        </option>

                        <option value="Resolved"
                            <?= $complaint["status"] === "Resolved" ? "selected" : "" ?>>
                            Resolved
                        </option>

                    </select>

                    <br><br>

                    <button type="submit">Update Complaint</button>

                </form>

            </section>

        <?php endforeach; ?>

    <?php else: ?>

        <section class="card">
            <p class="muted">No complaints found.</p>
        </section>

    <?php endif; ?>

</main>

</body>
</html>
