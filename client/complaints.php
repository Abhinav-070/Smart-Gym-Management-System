
<?php
session_start();

require_once "../config/database.php";

if (!isset($_SESSION["client_id"])) {
    header("Location: login.php");
    exit;
}

$client_id = (int) $_SESSION["client_id"];

$errors = [];
$success = "";

// CSRF protection
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Submit complaint
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $token)) {
        $errors[] = "Invalid request. Please try again.";
    } else {

        $subject = trim($_POST["subject"] ?? "");
        $message = trim($_POST["message"] ?? "");

        if ($subject === "") {
            $errors[] = "Please enter a complaint subject.";
        } elseif (strlen($subject) > 150) {
            $errors[] = "Subject cannot exceed 150 characters.";
        }

        if ($message === "") {
            $errors[] = "Please describe your complaint.";
        } elseif (strlen($message) < 10 || strlen($message) > 5000) {
            $errors[] = "Complaint must be between 10 and 5000 characters.";
        }

        if (empty($errors)) {

            $insert = $conn->prepare("
                INSERT INTO complaints (client_id, subject, message)
                VALUES (?, ?, ?)
            ");

            $insert->execute([
                $client_id,
                $subject,
                $message
            ]);

            $success = "Your complaint has been submitted successfully.";
            $_POST = [];
        }
    }
}

// Get client's complaints
$stmt = $conn->prepare("
    SELECT id, subject, message, admin_response,
           status, created_at, updated_at
    FROM complaints
    WHERE client_id = ?
    ORDER BY created_at DESC
");

$stmt->execute([$client_id]);
$complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Complaints | Smart Gym</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .page {
            max-width: 950px;
            margin: auto;
            padding: 50px 20px;
        }

        h1, h2 {
            color: #e63946;
        }

        .card {
            background: #1c1c1c;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            border-left: 4px solid #e63946;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: white;
        }

        input, textarea {
            width: 100%;
            padding: 12px;
            box-sizing: border-box;
            background: #111;
            color: white;
            border: 1px solid #444;
            border-radius: 5px;
            margin-bottom: 18px;
        }

        textarea {
            min-height: 130px;
            resize: vertical;
        }

        button {
            padding: 12px 25px;
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

        .response {
            background: #252525;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
        }

        .muted {
            color: #bbb;
        }

        .back {
            display: inline-block;
            margin-bottom: 25px;
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
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="page">

    <a href="dashboard.php" class="back">← Back to Dashboard</a>

    <h1>Complaint Management</h1>

    <p class="muted">
        Submit your complaints and track their status.
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

    <section class="card">

        <h2>Submit a Complaint</h2>

        <form method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>"
            >

            <label for="subject">Subject</label>

            <input
                type="text"
                name="subject"
                id="subject"
                maxlength="150"
                value="<?= htmlspecialchars($_POST["subject"] ?? "") ?>"
                placeholder="Enter complaint subject"
                required
            >

            <label for="message">Complaint Details</label>

            <textarea
                name="message"
                id="message"
                minlength="10"
                maxlength="5000"
                placeholder="Describe your complaint..."
                required
            ><?= htmlspecialchars($_POST["message"] ?? "") ?></textarea>

            <button type="submit">Submit Complaint</button>

        </form>

    </section>

    <h2>My Complaints</h2>

    <?php if ($complaints): ?>

        <?php foreach ($complaints as $complaint): ?>

            <section class="card">

                <h2>
                    <?= htmlspecialchars($complaint["subject"]) ?>
                </h2>

                <p>
                    <?= nl2br(htmlspecialchars($complaint["message"])) ?>
                </p>

                <?php
                    $status_class = match ($complaint["status"]) {
                        "Resolved" => "resolved",
                        "In Progress" => "in-progress",
                        default => "pending"
                    };
                ?>

                <p>
                    Status:
                    <span class="status <?= $status_class ?>">
                        <?= htmlspecialchars($complaint["status"]) ?>
                    </span>
                </p>

                <p class="muted">
                    Submitted:
                    <?= htmlspecialchars($complaint["created_at"]) ?>
                </p>

                <?php if (!empty($complaint["admin_response"])): ?>

                    <div class="response">

                        <strong>Admin Response</strong>

                        <p>
                            <?= nl2br(htmlspecialchars($complaint["admin_response"])) ?>
                        </p>

                        <small class="muted">
                            Updated:
                            <?= htmlspecialchars($complaint["updated_at"]) ?>
                        </small>

                    </div>

                <?php else: ?>

                    <p class="muted">Waiting for administrator response.</p>

                <?php endif; ?>

            </section>

        <?php endforeach; ?>

    <?php else: ?>

        <section class="card">
            <p class="muted">You haven't submitted any complaints yet.</p>
        </section>

    <?php endif; ?>

</main>

</body>
</html>
