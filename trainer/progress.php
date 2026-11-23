<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION["trainer_id"])) {
    header("Location: login.php");
    exit;
}

$trainer_id = (int)$_SESSION["trainer_id"];

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"] ?? "")) {
        $errors[] = "Invalid request. Please try again.";
    }

    // Add progress record
    elseif (isset($_POST["add_progress"])) {

        $client_id = filter_var($_POST["client_id"] ?? "", FILTER_VALIDATE_INT);
        $weight = filter_var($_POST["weight"] ?? "", FILTER_VALIDATE_FLOAT);
        $height = filter_var($_POST["height"] ?? "", FILTER_VALIDATE_FLOAT);
        $notes = trim($_POST["notes"] ?? "");
        $recorded_at = $_POST["recorded_at"] ?? "";

        if (!$client_id) {
            $errors[] = "Please select a valid client.";
        }

        if ($weight === false || $weight < 1 || $weight > 500) {
            $errors[] = "Enter a valid weight between 1 and 500 kg.";
        }

        if ($height === false || $height < 0.5 || $height > 2.5) {
            $errors[] = "Enter a valid height between 0.5 and 2.5 metres.";
        }

        $date = DateTime::createFromFormat("!Y-m-d", $recorded_at);

        if (!$date || $date->format("Y-m-d") !== $recorded_at ||
            $recorded_at > date("Y-m-d")) {
            $errors[] = "Enter a valid recording date that is not in the future.";
        }

        // Verify trainer-client assignment
        if ($client_id) {
            $check = $conn->prepare(
                "SELECT id FROM trainer_clients
                 WHERE trainer_id = ? AND client_id = ?"
            );
            $check->execute([$trainer_id, $client_id]);

            if (!$check->fetch()) {
                $errors[] = "You can only track your assigned clients.";
            }
        }

        if (empty($errors)) {

            $bmi = round($weight / ($height * $height), 2);

            $stmt = $conn->prepare(
                "INSERT INTO progress_records
                (trainer_id, client_id, weight, height, bmi, notes, recorded_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $trainer_id,
                $client_id,
                $weight,
                $height,
                $bmi,
                $notes,
                $recorded_at
            ]);

            header("Location: progress.php?success=added");
            exit;
        }
    }

    // Delete progress record
    elseif (isset($_POST["delete_progress"])) {

        $record_id = filter_var(
            $_POST["record_id"] ?? "",
            FILTER_VALIDATE_INT
        );

        if ($record_id) {
            $stmt = $conn->prepare(
                "DELETE FROM progress_records
                 WHERE id = ? AND trainer_id = ?"
            );
            $stmt->execute([$record_id, $trainer_id]);
        }

        header("Location: progress.php?success=deleted");
        exit;
    }
}

// Assigned clients
$stmt = $conn->prepare(
    "SELECT c.id, c.name
     FROM trainer_clients tc
     JOIN clients c ON tc.client_id = c.id
     WHERE tc.trainer_id = ?
     ORDER BY c.name"
);
$stmt->execute([$trainer_id]);
$clients = $stmt->fetchAll();

// Progress records
$stmt = $conn->prepare(
    "SELECT pr.*, c.name AS client_name
     FROM progress_records pr
     JOIN clients c ON pr.client_id = c.id
     WHERE pr.trainer_id = ?
     ORDER BY pr.recorded_at DESC, pr.id DESC"
);
$stmt->execute([$trainer_id]);
$records = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Progress Tracking | Smart Gym</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .container {
            padding: 50px 5%;
            min-height: 85vh;
        }

        h1, h2 {
            color: #e63946;
            margin-bottom: 25px;
        }

        .progress-form {
            max-width: 650px;
            padding: 30px;
            background: #1c1c1c;
            border-radius: 10px;
            margin-bottom: 45px;
        }

        .progress-form label {
            display: block;
            margin: 15px 0 8px;
        }

        .progress-form input,
        .progress-form select,
        .progress-form textarea {
            width: 100%;
            padding: 12px;
            background: #111;
            color: white;
            border: 1px solid #444;
            border-radius: 5px;
        }

        .progress-form textarea {
            min-height: 100px;
            resize: vertical;
        }

        .progress-form button {
            width: 100%;
            margin-top: 25px;
            padding: 13px;
            background: #e63946;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .table-container {
            overflow-x: auto;
            background: #1c1c1c;
            border-radius: 8px;
        }

        table {
            width: 100%;
            min-width: 950px;
            border-collapse: collapse;
        }

        th, td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid #333;
        }

        th {
            background: #080808;
            color: #e63946;
        }

        td {
            color: #ddd;
        }

        .delete-btn {
            padding: 8px 12px;
            background: #e63946;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            background: #442024;
            color: #ffb3b8;
        }

        .success {
            background: #183c27;
            color: #a8e6b8;
        }

        .back-link {
            display: inline-block;
            margin-top: 25px;
            color: #e63946;
            text-decoration: none;
        }
    </style>
</head>

<body>

<header class="navbar">
    <h2>SMART GYM TRAINER</h2>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="clients.php">My Clients</a>
        <a href="workout_plans.php">Workout Plans</a>
        <a href="progress.php">Progress</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="container">

    <h1>Client Progress Tracking</h1>

    <?php foreach ($errors as $error): ?>
        <div class="message">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endforeach; ?>

    <?php if (($_GET["success"] ?? "") === "added"): ?>
        <div class="message success">Progress recorded successfully!</div>
    <?php elseif (($_GET["success"] ?? "") === "deleted"): ?>
        <div class="message success">Progress record deleted.</div>
    <?php endif; ?>

    <h2>Record Progress</h2>

    <form method="POST" class="progress-form">

        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

        <label>Select Client</label>
        <select name="client_id" required>
            <option value="">Choose Client</option>
            <?php foreach ($clients as $client): ?>
                <option value="<?= (int)$client["id"] ?>">
                    <?= htmlspecialchars($client["name"]) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Weight (kg)</label>
        <input type="number" name="weight" min="1" max="500"
               step="0.01" required>

        <label>Height (metres)</label>
        <input type="number" name="height" min="0.5" max="2.5"
               step="0.01" placeholder="e.g. 1.75" required>

        <label>Recorded Date</label>
        <input type="date" name="recorded_at"
               max="<?= date('Y-m-d') ?>"
               value="<?= date('Y-m-d') ?>" required>

        <label>Progress Notes</label>
        <textarea name="notes"
                  placeholder="Describe the client's progress..."></textarea>

        <button type="submit" name="add_progress">
            Save Progress
        </button>

    </form>

    <h2>Progress Records</h2>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Weight (kg)</th>
                    <th>Height (m)</th>
                    <th>BMI</th>
                    <th>Date</th>
                    <th>Notes</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="7">No progress records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $record): ?>
                        <tr>
                            <td><?= htmlspecialchars($record["client_name"]) ?></td>
                            <td><?= htmlspecialchars($record["weight"]) ?></td>
                            <td><?= htmlspecialchars($record["height"]) ?></td>
                            <td><?= htmlspecialchars($record["bmi"]) ?></td>
                            <td><?= htmlspecialchars($record["recorded_at"]) ?></td>
                            <td><?= htmlspecialchars($record["notes"] ?? "") ?></td>
                            <td>
                                <form method="POST"
                                      onsubmit="return confirm('Delete this record?');">

                                    <input type="hidden" name="csrf_token"
                                           value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

                                    <input type="hidden" name="record_id"
                                           value="<?= (int)$record["id"] ?>">

                                    <button type="submit" name="delete_progress"
                                            class="delete-btn">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <a href="dashboard.php" class="back-link">
        ← Back to Dashboard
    </a>

</main>

<footer>
    <p>&copy; <?= date("Y") ?> Smart Gym Management System</p>
</footer>

</body>
</html>
