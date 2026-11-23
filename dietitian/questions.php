
<?php
session_start();

require_once "../config/database.php";

if (!isset($_SESSION["dietitian_id"])) {
    header("Location: login.php");
    exit;
}

$dietitian_id = (int) $_SESSION["dietitian_id"];

$errors = [];
$success = "";

// CSRF protection
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Submit response
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $token)) {
        $errors[] = "Invalid request. Please try again.";
    } else {

        $question_id = filter_var(
            $_POST["question_id"] ?? null,
            FILTER_VALIDATE_INT
        );

        $response = trim($_POST["response"] ?? "");

        if (!$question_id) {
            $errors[] = "Invalid question.";
        }

        if ($response === "") {
            $errors[] = "Please enter your response.";
        } elseif (strlen($response) > 2000) {
            $errors[] = "Response cannot exceed 2000 characters.";
        }

        if (empty($errors)) {

            $update = $conn->prepare("
                UPDATE dietitian_questions
                SET response = ?,
                    status = 'Answered',
                    responded_at = CURRENT_TIMESTAMP
                WHERE id = ?
                  AND dietitian_id = ?
                  AND status = 'Pending'
            ");

            $update->execute([
                $response,
                $question_id,
                $dietitian_id
            ]);

            if ($update->rowCount() === 1) {
                $success = "Response submitted successfully.";
            } else {
                $errors[] = "Question not found or already answered.";
            }
        }
    }
}

// Get questions addressed to this dietitian
$stmt = $conn->prepare("
    SELECT
        dq.id,
        dq.question,
        dq.response,
        dq.status,
        dq.created_at,
        dq.responded_at,
        c.name AS client_name,
        c.email AS client_email
    FROM dietitian_questions dq
    JOIN clients c ON c.id = dq.client_id
    WHERE dq.dietitian_id = ?
    ORDER BY
        CASE WHEN dq.status = 'Pending' THEN 0 ELSE 1 END,
        dq.created_at DESC
");

$stmt->execute([$dietitian_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Client Questions | Smart Gym</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .page {
            max-width: 1000px;
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

        .muted {
            color: #bbb;
        }

        textarea {
            width: 100%;
            min-height: 120px;
            padding: 12px;
            box-sizing: border-box;
            background: #111;
            color: white;
            border: 1px solid #444;
            border-radius: 5px;
            resize: vertical;
        }

        button {
            margin-top: 12px;
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

        .pending {
            color: #ffc107;
            font-weight: bold;
        }

        .answered {
            color: #65d68a;
            font-weight: bold;
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

    <h1>Client Questions</h1>
    <p class="muted">
        View nutrition questions from clients and provide your responses.
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

    <?php if ($questions): ?>

        <?php foreach ($questions as $item): ?>

            <section class="card">

                <h2><?= htmlspecialchars($item["client_name"]) ?></h2>

                <p class="muted">
                    <?= htmlspecialchars($item["client_email"]) ?>
                </p>

                <p><strong>Question:</strong></p>

                <p>
                    <?= nl2br(htmlspecialchars($item["question"])) ?>
                </p>

                <p class="muted">
                    Submitted: <?= htmlspecialchars($item["created_at"]) ?>
                </p>

                <?php if ($item["status"] === "Pending"): ?>

                    <p class="pending">Pending Response</p>

                    <form method="POST">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>"
                        >

                        <input
                            type="hidden"
                            name="question_id"
                            value="<?= (int) $item["id"] ?>"
                        >

                        <label for="response_<?= (int) $item["id"] ?>">
                            <strong>Your Response</strong>
                        </label>

                        <textarea
                            id="response_<?= (int) $item["id"] ?>"
                            name="response"
                            maxlength="2000"
                            placeholder="Write your nutrition guidance..."
                            required
                        ></textarea>

                        <button type="submit">Submit Response</button>

                    </form>

                <?php else: ?>

                    <p class="answered">Response Submitted</p>

                    <p>
                        <?= nl2br(htmlspecialchars($item["response"] ?? "")) ?>
                    </p>

                    <small class="muted">
                        Responded:
                        <?= htmlspecialchars($item["responded_at"] ?? "") ?>
                    </small>

                <?php endif; ?>

            </section>

        <?php endforeach; ?>

    <?php else: ?>

        <section class="card">
            <p class="muted">No client questions available.</p>
        </section>

    <?php endif; ?>

</main>

</body>
</html>
