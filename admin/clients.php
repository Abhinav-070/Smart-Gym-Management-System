<?php
session_start();

require_once "../config/database.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

// Delete client
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_client"])) {

    $client_id = filter_var(
        $_POST["client_id"] ?? null,
        FILTER_VALIDATE_INT
    );

    if ($client_id) {
        $delete = $conn->prepare("DELETE FROM clients WHERE id = ?");
        $delete->execute([$client_id]);
    }

    header("Location: clients.php");
    exit;
}

// Search clients
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

    <title>Manage Clients | Smart Gym</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .clients-container {
            padding: 50px 5%;
            min-height: 85vh;
        }

        .clients-container h1 {
            color: #e63946;
            margin-bottom: 25px;
        }

        .search-form {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
        }

        .search-form input {
            flex: 1;
            padding: 12px;
            background: #1c1c1c;
            color: white;
            border: 1px solid #444;
            border-radius: 5px;
        }

        .search-form button {
            padding: 12px 22px;
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
            border-collapse: collapse;
            min-width: 750px;
        }

        th, td {
            padding: 16px;
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
            background: #e63946;
            color: white;
            border: none;
            padding: 8px 13px;
            border-radius: 4px;
            cursor: pointer;
        }

        .delete-btn:hover {
            background: #c1121f;
        }

        .empty-message {
            padding: 25px;
            color: #ccc;
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
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="clients-container">

    <h1>Manage Clients</h1>

    <form method="GET" class="search-form">
        <input
            type="text"
            name="search"
            placeholder="Search by name, email or phone..."
            value="<?= htmlspecialchars($search) ?>"
        >

        <button type="submit">Search</button>
    </form>

    <div class="table-container">

        <?php if (empty($clients)): ?>

            <div class="empty-message">
                No clients found.
            </div>

        <?php else: ?>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Registered</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($clients as $client): ?>

                        <tr>
                            <td><?= (int)$client["id"] ?></td>

                            <td>
                                <?= htmlspecialchars($client["name"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($client["email"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($client["phone"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($client["created_at"]) ?>
                            </td>

                            <td>
                                <form method="POST"
                                      onsubmit="return confirm('Are you sure you want to delete this client?');">

                                    <input
                                        type="hidden"
                                        name="client_id"
                                        value="<?= (int)$client["id"] ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="delete_client"
                                        class="delete-btn"
                                    >
                                        Delete
                                    </button>

                                </form>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                </tbody>
            </table>

        <?php endif; ?>

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