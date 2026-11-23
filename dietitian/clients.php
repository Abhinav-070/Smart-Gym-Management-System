<?php
session_start();

require_once "../config/database.php";

if (!isset($_SESSION["dietitian_id"])) {
    header("Location: login.php");
    exit;
}

$search = trim($_GET["search"] ?? "");

if ($search !== "") {
    $stmt = $conn->prepare(
        "SELECT id, name, email, phone, created_at
         FROM clients
         WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?
         ORDER BY id DESC"
    );

    $term = "%" . $search . "%";
    $stmt->execute([$term, $term, $term]);
} else {
    $stmt = $conn->query(
        "SELECT id, name, email, phone, created_at
         FROM clients
         ORDER BY id DESC"
    );
}

$clients = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>View Clients | Smart Gym</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .container {
            padding: 50px 5%;
            min-height: 85vh;
        }

        h1 {
            color: #e63946;
            margin-bottom: 25px;
        }

        .search-form {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            max-width: 500px;
        }

        .search-form input {
            flex: 1;
            min-width: 0;
            padding: 12px;
            background: #1c1c1c;
            color: white;
            border: 1px solid #444;
            border-radius: 5px;
        }

        .search-form button {
            padding: 12px 20px;
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
            min-width: 700px;
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

        .back-link {
            display: inline-block;
            margin-top: 25px;
            color: #e63946;
            text-decoration: none;
        }

        .empty {
            padding: 20px;
            text-align: center;
            color: #ccc;
        }
    </style>
</head>

<body>

<header class="navbar">
    <h2>SMART GYM</h2>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="diet_plans.php">Diet Plans</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="container">

    <h1>Registered Clients</h1>

    <form method="GET" class="search-form">
        <input
            type="search"
            name="search"
            placeholder="Search by name, email or phone"
            value="<?= htmlspecialchars($search) ?>"
        >
        <button type="submit">Search</button>
    </form>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Registered Date</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($clients)): ?>
                    <tr>
                        <td colspan="5" class="empty">
                            No clients found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($clients as $client): ?>
                        <tr>
                            <td><?= (int)$client["id"] ?></td>
                            <td><?= htmlspecialchars($client["name"]) ?></td>
                            <td><?= htmlspecialchars($client["email"]) ?></td>
                            <td><?= htmlspecialchars($client["phone"]) ?></td>
                            <td><?= htmlspecialchars($client["created_at"]) ?></td>
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
