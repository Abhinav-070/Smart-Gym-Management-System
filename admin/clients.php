
<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$errors = [];

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Delete client
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_client"])) {

    if (!hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"] ?? "")) {
        $errors[] = "Invalid request. Please try again.";
    } else {

        $client_id = filter_var($_POST["client_id"] ?? null, FILTER_VALIDATE_INT);

        if (!$client_id || $client_id < 1) {
            $errors[] = "Invalid client selected.";
        } else {

            $check = $conn->prepare("SELECT id FROM clients WHERE id = ?");
            $check->execute([$client_id]);

            if (!$check->fetch()) {
                $errors[] = "Client not found.";
            } else {

                $delete = $conn->prepare("DELETE FROM clients WHERE id = ?");
                $delete->execute([$client_id]);

                header("Location: clients.php?success=deleted");
                exit;
            }
        }
    }
}

// Search clients
$search = trim($_GET["search"] ?? "");

if ($search !== "") {
    $stmt = $conn->prepare(
        "SELECT id, name, email, phone, created_at
         FROM clients
         WHERE name LIKE ?
         OR email LIKE ?
         OR phone LIKE ?
         ORDER BY id DESC"
    );

    $term = "%" . $search . "%";
    $stmt->execute([$term, $term, $term]);
} else {
    $stmt = $conn->query(
        "SELECT id, name, email, phone, created_at
         FROM clients ORDER BY id DESC"
    );
}

$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Manage Clients | Smart Gym</title>

<link rel="stylesheet" href="../assets/css/style.css">

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #090909;
    color: #fff;
    font-family: Arial, sans-serif;
}

/* Layout */
.admin-layout {
    min-height: 100vh;
}

/* Sidebar */
.admin-sidebar {
    width: 250px;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    padding: 30px 18px;
    background: rgba(12,12,12,.97);
    border-right: 1px solid #292929;
    z-index: 1000;
    overflow-y: auto;
}

.admin-logo {
    text-align: center;
    color: #e63946;
    font-size: 21px;
    font-weight: bold;
    letter-spacing: 1px;
    margin-bottom: 40px;
}

.sidebar-label {
    color: #777;
    font-size: 11px;
    letter-spacing: 2px;
    margin: 25px 12px 12px;
    text-transform: uppercase;
}

.admin-sidebar nav {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.admin-sidebar nav a {
    display: block;
    text-decoration: none;
    color: #aaa;
    padding: 13px 15px;
    border-radius: 7px;
    font-size: 14px;
    transition: .25s;
}

.admin-sidebar nav a:hover,
.admin-sidebar nav a.active {
    color: #fff;
    background: rgba(230,57,70,.15);
    border-left: 3px solid #e63946;
}

.admin-sidebar nav a.logout {
    color: #ff6b75;
    margin-top: 15px;
}

/* Main */
.admin-main {
    margin-left: 250px;
    width: calc(100% - 250px);
    min-height: 100vh;
    padding: 35px;
    padding-bottom: 100px;
    background:
        linear-gradient(rgba(8,8,8,.92), rgba(8,8,8,.96)),
        url("../uploads/admin-bg.jpg") center/cover fixed;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    gap: 15px;
}

.topbar h2 {
    margin: 0;
    font-size: 22px;
}

.admin-tag {
    background: #191919;
    border: 1px solid #333;
    padding: 9px 15px;
    border-radius: 30px;
    color: #ccc;
    font-size: 12px;
}

/* Page heading */
.page-heading {
    margin-bottom: 25px;
}

.page-heading h1 {
    font-size: 29px;
    margin: 0 0 10px;
}

.page-heading h1 span {
    color: #e63946;
}

.page-heading p {
    color: #888;
    margin: 0;
}

/* Alerts */
.message {
    padding: 15px 18px;
    margin-bottom: 20px;
    border-radius: 8px;
    border: 1px solid #743038;
    background: #30191c;
    color: #ffb3b8;
}

.message ul {
    margin: 0;
    padding-left: 20px;
}

.message.success {
    border-color: #245c37;
    background: #142b1c;
    color: #a8e6b8;
}

/* Search */
.clients-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 22px;
}

.client-count {
    color: #999;
    font-size: 14px;
}

.client-count strong {
    color: #e63946;
    font-size: 18px;
}

.search-form {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    width: 100%;
    max-width: 650px;
}

.search-form input {
    flex: 1;
    min-width: 200px;
    padding: 12px 15px;
    background: #171717;
    border: 1px solid #353535;
    color: white;
    border-radius: 7px;
    outline: none;
}

.search-form input:focus {
    border-color: #e63946;
}

.search-form button {
    padding: 12px 22px;
    background: #e63946;
    color: white;
    border: none;
    border-radius: 7px;
    cursor: pointer;
    font-weight: bold;
    transition: .2s;
}

.search-form button:hover {
    background: #c1121f;
}

.reset-btn {
    padding: 12px 18px;
    display: inline-flex;
    align-items: center;
    background: #292929;
    border: 1px solid #383838;
    color: #ddd;
    border-radius: 7px;
    text-decoration: none;
}

.reset-btn:hover {
    background: #383838;
}

/* Table */
.table-container {
    background: rgba(20,20,20,.95);
    border: 1px solid #2c2c2c;
    border-radius: 12px;
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 850px;
    border-collapse: collapse;
}

thead {
    background: #101010;
}

th {
    padding: 17px 16px;
    text-align: left;
    color: #e63946;
    font-size: 12px;
    letter-spacing: .5px;
    text-transform: uppercase;
    white-space: nowrap;
}

td {
    padding: 16px;
    border-top: 1px solid #292929;
    color: #ccc;
    font-size: 13px;
}

tbody tr {
    transition: .2s;
}

tbody tr:hover {
    background: #222;
}

.client-id {
    color: #888;
}

.client-name {
    color: #fff;
    font-weight: bold;
}

.client-email {
    color: #aaa;
}

.delete-btn {
    background: rgba(230,57,70,.12);
    border: 1px solid #6c2a30;
    color: #ff6b75;
    padding: 8px 13px;
    border-radius: 6px;
    cursor: pointer;
    transition: .2s;
}

.delete-btn:hover {
    background: #e63946;
    color: #fff;
}

.empty-message {
    padding: 45px 20px;
    text-align: center;
    color: #888;
}

.empty-message h3 {
    color: #ccc;
    margin-bottom: 8px;
}

/* Footer */
.admin-footer {
    position: fixed;
    bottom: 0;
    left: 250px;
    right: 0;
    padding: 15px;
    text-align: center;
    background: rgba(8,8,8,.96);
    border-top: 1px solid #292929;
    color: #777;
    font-size: 12px;
    z-index: 900;
    backdrop-filter: blur(10px);
}

/* Responsive */
@media(max-width: 900px) {
    .admin-sidebar {
        position: relative;
        width: 100%;
        height: auto;
        padding: 15px;
    }

    .admin-logo {
        margin-bottom: 15px;
    }

    .sidebar-label {
        display: none;
    }

    .admin-sidebar nav {
        flex-direction: row;
        flex-wrap: wrap;
        justify-content: center;
    }

    .admin-sidebar nav a {
        padding: 10px;
        font-size: 12px;
    }

    .admin-main {
        margin-left: 0;
        width: 100%;
        padding: 25px 5%;
    }

    .admin-footer {
        left: 0;
    }
}

@media(max-width: 600px) {
    .topbar {
        flex-direction: column;
        align-items: flex-start;
    }

    .page-heading h1 {
        font-size: 24px;
    }

    .search-form {
        flex-direction: column;
    }

    .search-form input,
    .search-form button,
    .reset-btn {
        width: 100%;
    }
}
</style>
</head>

<body>

<div class="admin-layout">

    <!-- Sidebar -->
    <aside class="admin-sidebar">

        <div class="admin-logo">SMART GYM</div>

        <div class="sidebar-label">Main Menu</div>
        <nav>
            <a href="dashboard.php">▦ Dashboard</a>
        </nav>

        <div class="sidebar-label">Management</div>
        <nav>
            <a href="clients.php" class="active">♙ Manage Clients</a>
            <a href="memberships.php">▤ Memberships</a>
            <a href="trainers.php">♟ Trainers</a>
            <a href="dietitians.php">✚ Dietitians</a>
            <a href="assign_clients.php">⇄ Assign Clients</a>
            <a href="complaints.php">⚑ Complaints</a>
            <a href="reports.php">▥ Reports</a>
        </nav>

        <div class="sidebar-label">Account</div>
        <nav>
            <a href="logout.php" class="logout">↪ Logout</a>
        </nav>

    </aside>

    <!-- Main content -->
    <main class="admin-main">

        <div class="topbar">
            <h2>Admin Panel</h2>
            <div class="admin-tag">● Administrator</div>
        </div>

        <div class="page-heading">
            <h1>Manage <span>Clients</span></h1>
            <p>View, search and manage registered gym members.</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="message">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (($_GET["success"] ?? "") === "deleted"): ?>
            <div class="message success">
                Client deleted successfully!
            </div>
        <?php endif; ?>

        <div class="clients-toolbar">

            <form method="GET" class="search-form">

                <input
                    type="text"
                    name="search"
                    placeholder="Search by name, email or phone..."
                    value="<?= e($search) ?>"
                >

                <button type="submit">Search</button>

                <a href="clients.php" class="reset-btn">Reset</a>

            </form>

            <div class="client-count">
                Clients found: <strong><?= count($clients) ?></strong>
            </div>

        </div>

        <div class="table-container">

            <?php if (empty($clients)): ?>

                <div class="empty-message">
                    <h3>No Clients Found</h3>
                    <p>Try another search term.</p>
                </div>

            <?php else: ?>

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Registered</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($clients as $client): ?>
                            <tr>
                                <td class="client-id">
                                    #<?= (int)$client["id"] ?>
                                </td>

                                <td class="client-name">
                                    <?= e($client["name"]) ?>
                                </td>

                                <td class="client-email">
                                    <?= e($client["email"]) ?>
                                </td>

                                <td>
                                    <?= e($client["phone"]) ?>
                                </td>

                                <td>
                                    <?= e($client["created_at"]) ?>
                                </td>

                                <td>
                                    <form method="POST"
                                          onsubmit="return confirm('Are you sure you want to delete this client?');">

                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?= e($_SESSION["csrf_token"]) ?>">

                                        <input type="hidden"
                                               name="client_id"
                                               value="<?= (int)$client["id"] ?>">

                                        <button type="submit"
                                                name="delete_client"
                                                class="delete-btn">
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

    </main>

</div>

<footer class="admin-footer">
    &copy; <?= date("Y") ?> Smart Gym Management System
</footer>

</body>
</html>
