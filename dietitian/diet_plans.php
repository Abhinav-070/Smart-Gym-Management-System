
<?php
session_start();

require_once "../config/database.php";

if (!isset($_SESSION["dietitian_id"])) {
    header("Location: login.php");
    exit;
}

$dietitian_id = $_SESSION["dietitian_id"];

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"] ?? "")) {
        $errors[] = "Invalid request. Please try again.";
    }

    // Add diet plan
    elseif (isset($_POST["add_plan"])) {

        $client_id = filter_var($_POST["client_id"] ?? "", FILTER_VALIDATE_INT);
        $plan_name = trim($_POST["plan_name"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $daily_calories = trim($_POST["daily_calories"] ?? "");
        $start_date = $_POST["start_date"] ?? "";
        $end_date = $_POST["end_date"] ?? "";

        if (!$client_id) {
            $errors[] = "Please select a valid client.";
        } else {
            $stmt = $conn->prepare("SELECT id FROM clients WHERE id = ?");
            $stmt->execute([$client_id]);

            if (!$stmt->fetch()) {
                $errors[] = "Selected client does not exist.";
            }
        }

        if ($plan_name === "" || strlen($plan_name) > 150) {
            $errors[] = "Enter a valid plan name (maximum 150 characters).";
        }

        if ($description === "") {
            $errors[] = "Diet plan description is required.";
        }

        if (
            $daily_calories !== "" &&
            (!ctype_digit($daily_calories) ||
             (int)$daily_calories < 500 ||
             (int)$daily_calories > 10000)
        ) {
            $errors[] = "Daily calories must be between 500 and 10000.";
        }

        $start = DateTime::createFromFormat("!Y-m-d", $start_date);
        $end = DateTime::createFromFormat("!Y-m-d", $end_date);

        if (!$start || $start->format("Y-m-d") !== $start_date) {
            $errors[] = "Enter a valid start date.";
        }

        if (!$end || $end->format("Y-m-d") !== $end_date) {
            $errors[] = "Enter a valid end date.";
        }

        if ($start && $end && $end < $start) {
            $errors[] = "End date cannot be before start date.";
        }

        if (empty($errors)) {

            $stmt = $conn->prepare(
                "INSERT INTO diet_plans
                (dietitian_id, client_id, plan_name, description,
                 daily_calories, start_date, end_date)
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $dietitian_id,
                $client_id,
                $plan_name,
                $description,
                $daily_calories === "" ? null : (int)$daily_calories,
                $start_date,
                $end_date
            ]);

            header("Location: diet_plans.php?success=added");
            exit;
        }
    }

    // Delete diet plan
    elseif (isset($_POST["delete_plan"])) {

        $plan_id = filter_var($_POST["plan_id"] ?? "", FILTER_VALIDATE_INT);

        if ($plan_id) {
            $stmt = $conn->prepare(
                "DELETE FROM diet_plans
                 WHERE id = ? AND dietitian_id = ?"
            );

            $stmt->execute([$plan_id, $dietitian_id]);
        }

        header("Location: diet_plans.php?success=deleted");
        exit;
    }
}

// Fetch clients
$clients = $conn->query(
    "SELECT id, name FROM clients ORDER BY name ASC"
)->fetchAll();

// Fetch diet plans
$stmt = $conn->prepare(
    "SELECT dp.*, c.name AS client_name
     FROM diet_plans dp
     INNER JOIN clients c ON dp.client_id = c.id
     WHERE dp.dietitian_id = ?
     ORDER BY dp.id DESC"
);

$stmt->execute([$dietitian_id]);
$plans = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Diet Plans | Smart Gym</title>

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

        .plan-form {
            max-width: 650px;
            padding: 30px;
            background: #1c1c1c;
            border-radius: 10px;
            margin-bottom: 45px;
        }

        .plan-form label {
            display: block;
            margin: 15px 0 8px;
        }

        .plan-form input,
        .plan-form select,
        .plan-form textarea {
            width: 100%;
            padding: 12px;
            background: #111;
            color: white;
            border: 1px solid #444;
            border-radius: 5px;
        }

        .plan-form textarea {
            min-height: 120px;
            resize: vertical;
        }

        .plan-form button {
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
            min-width: 850px;
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
    <h2>SMART GYM</h2>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="clients.php">Clients</a>
        <a href="diet_plans.php">Diet Plans</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="container">

    <h1>Diet Plan Management</h1>

    <?php foreach ($errors as $error): ?>
        <div class="message">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endforeach; ?>

    <?php if (($_GET["success"] ?? "") === "added"): ?>
        <div class="message success">Diet plan added successfully!</div>
    <?php elseif (($_GET["success"] ?? "") === "deleted"): ?>
        <div class="message success">Diet plan deleted successfully!</div>
    <?php endif; ?>

    <h2>Create Diet Plan</h2>

    <form method="POST" class="plan-form">

        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

        <label>Select Client</label>
        <select name="client_id" required>
            <option value="">-- Select Client --</option>

            <?php foreach ($clients as $client): ?>
                <option value="<?= (int)$client["id"] ?>">
                    <?= htmlspecialchars($client["name"]) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Plan Name</label>
        <input type="text" name="plan_name" maxlength="150"
               placeholder="e.g. Weight Loss Diet" required>

        <label>Diet Description</label>
        <textarea name="description"
                  placeholder="Enter meal schedule and diet instructions..."
                  required></textarea>

        <label>Daily Calories (Optional)</label>
        <input type="number" name="daily_calories"
               min="500" max="10000"
               placeholder="e.g. 2000">

        <label>Start Date</label>
        <input type="date" name="start_date" required>

        <label>End Date</label>
        <input type="date" name="end_date" required>

        <button type="submit" name="add_plan">
            Create Diet Plan
        </button>

    </form>

    <h2>All Diet Plans</h2>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client</th>
                    <th>Plan Name</th>
                    <th>Daily Calories</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($plans)): ?>
                    <tr>
                        <td colspan="7">No diet plans found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($plans as $plan): ?>
                        <tr>
                            <td><?= (int)$plan["id"] ?></td>
                            <td><?= htmlspecialchars($plan["client_name"]) ?></td>
                            <td><?= htmlspecialchars($plan["plan_name"]) ?></td>
                            <td>
                                <?= $plan["daily_calories"] !== null
                                    ? (int)$plan["daily_calories"] . " kcal"
                                    : "Not specified" ?>
                            </td>
                            <td><?= htmlspecialchars($plan["start_date"]) ?></td>
                            <td><?= htmlspecialchars($plan["end_date"]) ?></td>
                            <td>
                                <form method="POST"
                                      onsubmit="return confirm('Delete this diet plan?');">

                                    <input type="hidden" name="csrf_token"
                                           value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

                                    <input type="hidden" name="plan_id"
                                           value="<?= (int)$plan["id"] ?>">

                                    <button type="submit"
                                            name="delete_plan"
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
