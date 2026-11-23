
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

// Get dietitians
$stmt = $conn->query("
    SELECT id, name
    FROM dietitians
    ORDER BY name ASC
");

$dietitians = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Submit question
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $token)) {
        $errors[] = "Invalid request. Please try again.";
    } else {

        $dietitian_id = filter_var(
            $_POST["dietitian_id"] ?? null,
            FILTER_VALIDATE_INT
        );

        $question = trim($_POST["question"] ?? "");

        if (!$dietitian_id) {
            $errors[] = "Please select a dietitian.";
        }

        if ($question === "") {
            $errors[] = "Please enter your question.";
        } elseif (strlen($question) < 5 || strlen($question) > 2000) {
            $errors[] = "Question must be between 5 and 2000 characters.";
        }

        if (empty($errors)) {

            // Confirm selected dietitian exists
            $check = $conn->prepare("
                SELECT id FROM dietitians WHERE id = ?
            ");
            $check->execute([$dietitian_id]);

            if (!$check->fetch()) {
                $errors[] = "Selected dietitian does not exist.";
            } else {

                $insert = $conn->prepare("
                    INSERT INTO dietitian_questions
                    (client_id, dietitian_id, question)
                    VALUES (?, ?, ?)
                ");

                $insert->execute([
                    $client_id,
                    $dietitian_id,
                    $question
                ]);

                $success = "Your question has been sent successfully.";
            }
        }
    }
}

// Get question history
$history_stmt = $conn->prepare("
    SELECT
        dq.question,
        dq.response,
        dq.status,
        dq.created_at,
        dq.responded_at,
        d.name AS dietitian_name
    FROM dietitian_questions dq
    JOIN dietitians d ON d.id = dq.dietitian_id
    WHERE dq.client_id = ?
    ORDER BY dq.created_at DESC
");

$history_stmt->execute([$client_id]);
$questions = $history_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ask Dietitian | Smart Gym</title>

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
            margin-bottom: 10px;
            color: white;
        }

        select, textarea {
            width: 100%;
            padding: 12px;
            box-sizing: border-box;
            background: #111;
            color: white;
            border: 1px solid #444;
            border-radius: 5px;
            margin-bottom: 15px;
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

        .response {
            background: #252525;
            padding: 15px;
            margin-top: 15px;
            border-radius: 5px;
        }

        .pending {
            color: #ffc107;
            font-weight: bold;
        }

        .answered {
            color: #65d68a;
            font-weight: bold;
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

    <h1>Ask a Dietitian</h1>
    <p class="muted">
        Ask nutrition-related questions and receive professional guidance.
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

        <h2>Submit Your Question</h2>

        <?php if ($dietitians): ?>

            <form method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>"
                >

                <label for="dietitian_id">Select Dietitian</label>

                <select name="dietitian_id" id="dietitian_id" required>
                    <option value="">Choose a dietitian</option>

                    <?php foreach ($dietitians as $dietitian): ?>
                        <option
                            value="<?= (int) $dietitian["id"] ?>"
                            <?= (
                                (string) ($_POST["dietitian_id"] ?? "") ===
                                (string) $dietitian["id"]
                            ) ? "selected" : "" ?>
                        >
                            <?= htmlspecialchars($dietitian["name"]) ?>
                        </option>
                    <?php endforeach; ?>

                </select>

                <label for="question">Your Question</label>

                <textarea
                    name="question"
                    id="question"
                    minlength="5"
                    maxlength="2000"
                    placeholder="Enter your nutrition question..."
                    required
                ><?= htmlspecialchars($_POST["question"] ?? "") ?></textarea>

                <button type="submit">Send Question</button>

            </form>

        <?php else: ?>

            <p class="muted">
                No dietitians are currently available.
                Please contact the administrator.
            </p>

        <?php endif; ?>

    </section>

    <h2>My Questions & Responses</h2>

    <?php if ($questions): ?>

        <?php foreach ($questions as $item): ?>

            <section class="card">

                <p>
                    <strong>Your Question:</strong>
                </p>

                <p><?= nl2br(htmlspecialchars($item["question"])) ?></p>

                <p class="muted">
                    Dietitian:
                    <?= htmlspecialchars($item["dietitian_name"]) ?>
                </p>

                <p class="muted">
                    Submitted:
                    <?= htmlspecialchars($item["created_at"]) ?>
                </p>

                <?php if ($item["status"] === "Answered"): ?>

                    <div class="response">

                        <p class="answered">
                            <strong>Dietitian's Response</strong>
                        </p>

                        <p>
                            <?= nl2br(htmlspecialchars($item["response"] ?? "")) ?>
                        </p>

                        <small class="muted">
                            <?= htmlspecialchars($item["responded_at"] ?? "") ?>
                        </small>

                    </div>

                <?php else: ?>

                    <p class="pending">Waiting for dietitian's response</p>

                <?php endif; ?>

            </section>

        <?php endforeach; ?>

    <?php else: ?>

        <section class="card">
            <p class="muted">You haven't asked any dietitian questions yet.</p>
        </section>

    <?php endif; ?>

</main>

</body>
</html>
