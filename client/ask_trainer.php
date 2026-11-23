
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

// Get assigned trainer
$stmt = $conn->prepare("
    SELECT t.id, t.name, t.email, t.specialization
    FROM trainer_clients tc
    JOIN trainers t ON t.id = tc.trainer_id
    WHERE tc.client_id = ?
    LIMIT 1
");
$stmt->execute([$client_id]);
$trainer = $stmt->fetch(PDO::FETCH_ASSOC);

// Submit question
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!$trainer) {
        $errors[] = "No trainer has been assigned to you yet.";
    } else {

        $question = trim($_POST["question"] ?? "");

        if ($question === "") {
            $errors[] = "Please enter your question.";
        } elseif (strlen($question) < 5 || strlen($question) > 2000) {
            $errors[] = "Question must be between 5 and 2000 characters.";
        }

        if (empty($errors)) {

            $insert = $conn->prepare("
                INSERT INTO trainer_questions
                (client_id, trainer_id, question)
                VALUES (?, ?, ?)
            ");

            $insert->execute([
                $client_id,
                $trainer["id"],
                $question
            ]);

            $success = "Your question has been sent successfully.";
        }
    }
}

// Get previous questions and responses
$history_stmt = $conn->prepare("
    SELECT tq.question, tq.response, tq.status, tq.created_at,
           tq.responded_at, t.name AS trainer_name
    FROM trainer_questions tq
    JOIN trainers t ON t.id = tq.trainer_id
    WHERE tq.client_id = ?
    ORDER BY tq.created_at DESC
");
$history_stmt->execute([$client_id]);
$questions = $history_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ask Your Trainer | Smart Gym</title>

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

        textarea {
            width: 100%;
            min-height: 130px;
            padding: 12px;
            border: 1px solid #444;
            border-radius: 5px;
            background: #111;
            color: white;
            resize: vertical;
            box-sizing: border-box;
        }

        button {
            margin-top: 15px;
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

        .status {
            color: #ffc107;
            font-weight: bold;
        }

        .answered {
            color: #65d68a;
        }

        .back {
            display: inline-block;
            margin-bottom: 25px;
            color: #e63946;
            text-decoration: none;
        }

        .muted {
            color: #bbb;
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

    <h1>Ask Your Trainer</h1>
    <p class="muted">Send your fitness questions and view your trainer's responses.</p>

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

        <h2>Your Assigned Trainer</h2>

        <?php if ($trainer): ?>

            <p>
                <strong>Name:</strong>
                <?= htmlspecialchars($trainer["name"]) ?>
            </p>

            <p>
                <strong>Specialization:</strong>
                <?= htmlspecialchars($trainer["specialization"]) ?>
            </p>

            <form method="POST">

                <label for="question">Your Question</label>

                <textarea
                    name="question"
                    id="question"
                    maxlength="2000"
                    minlength="5"
                    placeholder="Type your fitness question here..."
                    required
                ></textarea>

                <button type="submit">Send Question</button>

            </form>

        <?php else: ?>

            <p class="muted">
                No trainer has been assigned to you yet.
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
                    Trainer: <?= htmlspecialchars($item["trainer_name"]) ?>
                </p>

                <p class="muted">
                    Submitted:
                    <?= htmlspecialchars($item["created_at"]) ?>
                </p>

                <?php if ($item["status"] === "Answered"): ?>

                    <div class="response">
                        <p class="answered"><strong>Trainer's Response</strong></p>

                        <p>
                            <?= nl2br(htmlspecialchars($item["response"] ?? "")) ?>
                        </p>

                        <small class="muted">
                            <?= htmlspecialchars($item["responded_at"] ?? "") ?>
                        </small>
                    </div>

                <?php else: ?>

                    <p class="status">Waiting for trainer's response</p>

                <?php endif; ?>

            </section>

        <?php endforeach; ?>

    <?php else: ?>

        <section class="card">
            <p class="muted">You haven't asked any questions yet.</p>
        </section>

    <?php endif; ?>

</main>

</body>
</html>
