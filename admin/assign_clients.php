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

if (isset($_GET["success"])) {
    $success = "Client assigned successfully!";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"] ?? "")) {
        $errors[] = "Invalid request. Please try again.";
    } else {

        // Assign client
        if (isset($_POST["assign_client"])) {

            $client_id = filter_var($_POST["client_id"] ?? "", FILTER_VALIDATE_INT);
            $trainer_id = filter_var($_POST["trainer_id"] ?? "", FILTER_VALIDATE_INT);

            if (!$client_id || !$trainer_id) {
                $errors[] = "Please select a valid client and trainer.";
            } else {
                try {
                    $stmt = $conn->prepare(
                        "INSERT INTO trainer_clients (trainer_id, client_id)
                         VALUES (?, ?)"
                    );

                    $stmt->execute([$trainer_id, $client_id]);

                    header("Location: assign_clients.php?success=1");
                    exit;

                } catch (PDOException $e) {
                    if ($e->getCode() === "23000") {
                        $errors[] = "This client is already assigned to this trainer.";
                    } else {
                        $errors[] = "Unable to assign client. Please try again.";
                    }
                }
            }
        }

        // Remove assignment
        if (isset($_POST["remove_assignment"])) {

            $assignment_id = filter_var(
                $_POST["assignment_id"] ?? "",
                FILTER_VALIDATE_INT
            );

            if ($assignment_id) {
                $stmt = $conn->prepare(
                    "DELETE FROM trainer_clients WHERE id = ?"
                );
                $stmt->execute([$assignment_id]);
            }

            header("Location: assign_clients.php");
            exit;
        }
    }
}

$clients = $conn->query(
    "SELECT id, name, email FROM clients ORDER BY name"
)->fetchAll();

$trainers = $conn->query(
    "SELECT id, name, specialization FROM trainers ORDER BY name"
)->fetchAll();

$assignments = $conn->query(
    "SELECT tc.id, c.name AS client_name, c.email,
            t.name AS trainer_name, t.specialization,
            tc.assigned_at
     FROM trainer_clients tc
     JOIN clients c ON tc.client_id = c.id
     JOIN trainers t ON tc.trainer_id = t.id
     ORDER BY tc.id DESC"
)->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Assign Clients | Smart Gym</title>

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

        .assignment-form {
            max-width: 600px;
            padding: 30px;
            background: #1c1c1c;
            border-radius: 10px;
            margin-bottom: 45px;
        }

        .assignment-form label {
            display: block;
            margin: 15px 0 8px;
        }

        .assignment-form select {
            width: 100%;
            padding: 12px;
            background: #111;
            color: white;
            border: 1px solid #444;
            border-radius: 5px;
        }

        .assignment-form button {
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
            min-width: 750px;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
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
    <h2>SMART GYM ADMIN</h2>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="clients.php">Clients</a>
        <a href="memberships.php">Memberships</a>
        <a href="trainers.php">Trainers</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="container">

    <h1>Assign Clients to Trainers</h1>

    <?php foreach ($errors as $error): ?>
        <div class="message">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endforeach; ?>

    <?php if ($success): ?>
        <div class="message success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <h2>New Assignment</h2>

    <form method="POST" class="assignment-form">

        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

        <label>Select Client</label>

        <select name="client_id" required>
            <option value="">Choose Client</option>

            <?php foreach ($clients as $client): ?>
                <option value="<?= (int)$client["id"] ?>">
                    <?= htmlspecialchars($client["name"]) ?>
                    (<?= htmlspecialchars($client["email"]) ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <label>Select Trainer</label>

        <select name="trainer_id" required>
            <option value="">Choose Trainer</option>

            <?php foreach ($trainers as $trainer): ?>
                <option value="<?= (int)$trainer["id"] ?>">
                    <?= htmlspecialchars($trainer["name"]) ?>
                    - <?= htmlspecialchars($trainer["specialization"]) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" name="assign_client">
            Assign Client
        </button>

    </form>

    <h2>Current Assignments</h2>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Email</th>
                    <th>Trainer</th>
                    <th>Specialization</th>
                    <th>Assigned Date</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($assignments)): ?>
                    <tr>
                        <td colspan="6">No assignments found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($assignments as $assignment): ?>
                        <tr>
                            <td><?= htmlspecialchars($assignment["client_name"]) ?></td>
                            <td><?= htmlspecialchars($assignment["email"]) ?></td>
                            <td><?= htmlspecialchars($assignment["trainer_name"]) ?></td>
                            <td><?= htmlspecialchars($assignment["specialization"]) ?></td>
                            <td><?= htmlspecialchars($assignment["assigned_at"]) ?></td>
                            <td>
                                <form method="POST"
                                      onsubmit="return confirm('Remove this assignment?');">

                                    <input type="hidden" name="csrf_token"
                                           value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

                                    <input type="hidden" name="assignment_id"
                                           value="<?= (int)$assignment["id"] ?>">

                                    <button type="submit"
                                            name="remove_assignment"
                                            class="delete-btn">
                                        Remove
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
