
<?php
session_start();

require_once "../config/database.php";

if (!isset($_SESSION["dietitian_id"])) {
    header("Location: login.php");
    exit;
}

$stmt = $conn->prepare(
    "SELECT name, email, phone FROM dietitians WHERE id = ?"
);

$stmt->execute([$_SESSION["dietitian_id"]]);
$dietitian = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dietitian) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Dietitian Dashboard | Smart Gym</title>

<link rel="stylesheet" href="../assets/css/style.css">

<style>
body {
    background: #090909;
}

/* LAYOUT */

.dietitian-layout {
    min-height: 100vh;
    display: flex;
    background:
        linear-gradient(rgba(5,5,5,.88), rgba(5,5,5,.94)),
        url('../uploads/dietitian-bg.jpg') center/cover fixed;
}

/* SIDEBAR */

.dietitian-sidebar {
    width: 250px;
    min-width: 250px;
    min-height: 100vh;
    background: rgba(10,10,10,.96);
    border-right: 1px solid #292929;
    padding: 30px 20px;
    display: flex;
    flex-direction: column;
}

.dietitian-logo {
    font-size: 23px;
    font-weight: 900;
    letter-spacing: 2px;
    color: white;
    margin-bottom: 40px;
}

.dietitian-logo span {
    color: #e63946;
}

.dietitian-sidebar .sidebar-label {
    color: #777;
    font-size: 11px;
    letter-spacing: 2px;
    margin: 10px 12px 15px;
}

.dietitian-sidebar nav {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.dietitian-sidebar nav a {
    display: block;
    text-decoration: none;
    color: #aaa;
    padding: 12px 14px;
    border-radius: 8px;
    font-size: 14px;
    transition: .25s;
}

.dietitian-sidebar nav a:hover,
.dietitian-sidebar nav a.active {
    color: white;
    background: rgba(230,57,70,.15);
    border-left: 3px solid #e63946;
}

.dietitian-sidebar nav .logout-link {
    margin-top: 25px;
    color: #ff7777;
}

/* MAIN CONTENT */

.dietitian-main {
    flex: 1;
    min-width: 0;
    padding: 30px 4% 85px;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    padding-bottom: 25px;
    border-bottom: 1px solid #292929;
}

.topbar p {
    color: #aaa;
    font-size: 13px;
}

.topbar strong {
    color: white;
}

.role-label {
    background: rgba(230,57,70,.15);
    border: 1px solid rgba(230,57,70,.4);
    color: #ff7a83;
    padding: 8px 15px;
    border-radius: 30px;
    font-size: 12px;
}

/* WELCOME */

.dietitian-welcome {
    padding: 42px 35px;
    margin: 30px 0;
    border: 1px solid #393939;
    border-radius: 16px;
    background:
        linear-gradient(90deg, rgba(0,0,0,.92), rgba(0,0,0,.45)),
        url('../uploads/dietitian-bg.jpg') center/cover;
}

.eyebrow {
    color: #e63946;
    font-size: 12px;
    letter-spacing: 3px;
    font-weight: bold;
}

.dietitian-welcome h1 {
    font-size: clamp(28px, 4vw, 43px);
    margin: 12px 0;
    line-height: 1.2;
}

.dietitian-welcome h1 span {
    color: #e63946;
}

.dietitian-welcome p {
    color: #bbb;
    font-size: 15px;
}

/* PROFILE */

.section-heading {
    margin: 35px 0 20px;
    font-size: 21px;
}

.section-heading span {
    color: #e63946;
}

.profile-details {
    background: rgba(23,23,23,.95);
    border: 1px solid #303030;
    border-radius: 13px;
    padding: 25px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.profile-item {
    padding: 12px 15px;
    border-left: 3px solid #e63946;
    background: #111;
    border-radius: 7px;
}

.profile-item small {
    display: block;
    color: #888;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 6px;
}

.profile-item strong {
    color: #eee;
    font-size: 14px;
    overflow-wrap: anywhere;
}

/* DASHBOARD CARDS */

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 18px;
}

.dietitian-card {
    display: block;
    min-height: 165px;
    padding: 23px;
    text-decoration: none;
    color: white;
    background: rgba(23,23,23,.95);
    border: 1px solid #303030;
    border-radius: 13px;
    transition: .3s;
}

.dietitian-card:hover {
    transform: translateY(-5px);
    border-color: #e63946;
    background: #202020;
    box-shadow: 0 12px 30px rgba(230,57,70,.10);
}

.card-icon {
    width: 43px;
    height: 43px;
    display: grid;
    place-items: center;
    background: rgba(230,57,70,.13);
    border-radius: 10px;
    font-size: 21px;
    margin-bottom: 17px;
}

.dietitian-card h3 {
    color: white;
    font-size: 16px;
    margin-bottom: 7px;
}

.dietitian-card p {
    color: #999;
    font-size: 13px;
    line-height: 1.6;
}

/* FIXED FOOTER */

.dietitian-footer {
    position: fixed;
    bottom: 0;
    left: 250px;
    right: 0;
    padding: 15px 20px;
    text-align: center;
    background: rgba(8,8,8,.96);
    border-top: 1px solid #292929;
    color: #777;
    font-size: 12px;
    z-index: 100;
    backdrop-filter: blur(10px);
}

/* RESPONSIVE */

@media(max-width: 900px) {
    .dietitian-layout {
        flex-direction: column;
    }

    .dietitian-sidebar {
        width: 100%;
        min-width: 0;
        min-height: auto;
        padding: 17px;
        border-right: none;
        border-bottom: 1px solid #292929;
    }

    .dietitian-logo {
        margin-bottom: 15px;
    }

    .dietitian-sidebar .sidebar-label {
        display: none;
    }

    .dietitian-sidebar nav {
        flex-direction: row;
        overflow-x: auto;
        padding-bottom: 5px;
    }

    .dietitian-sidebar nav a {
        white-space: nowrap;
    }

    .dietitian-sidebar nav .logout-link {
        margin-top: 0;
    }

    .dietitian-footer {
        left: 0;
    }
}

@media(max-width: 500px) {
    .dietitian-main {
        padding: 20px 5% 85px;
    }

    .dietitian-welcome {
        padding: 30px 20px;
    }

    .profile-details {
        grid-template-columns: 1fr;
    }

    .dashboard-grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<div class="dietitian-layout">

    <!-- SIDEBAR -->
    <aside class="dietitian-sidebar">

        <div class="dietitian-logo">
            SMART<span>GYM.</span>
        </div>

        <p class="sidebar-label">DIETITIAN MENU</p>

        <nav>
            <a href="dashboard.php" class="active">⌂ &nbsp; Dashboard</a>
            <a href="diet_plans.php">🥗 &nbsp; Manage Diet Plans</a>
            <a href="clients.php">♙ &nbsp; View Clients</a>
            <a href="questions.php">✉ &nbsp; Client Questions</a>
            <a href="profile.php">◉ &nbsp; My Profile</a>
            <a href="logout.php" class="logout-link">↪ &nbsp; Logout</a>
        </nav>

    </aside>

    <!-- MAIN CONTENT -->
    <main class="dietitian-main">

        <header class="topbar">
            <div>
                <p>SMART GYM / DIETITIAN PORTAL</p>
                <strong>Nutrition Dashboard</strong>
            </div>

            <span class="role-label">DIETITIAN ACCOUNT</span>
        </header>

        <!-- WELCOME -->
        <section class="dietitian-welcome">

            <div class="eyebrow">NUTRITION & WELLNESS</div>

            <h1>
                Welcome back,<br>
                <span><?= e($dietitian["name"]) ?></span>
            </h1>

            <p>
                Build healthier habits. Create better nutrition plans.
                Help every client reach their fitness goals.
            </p>

        </section>

        <!-- PROFILE INFORMATION -->
        <div class="section-heading">
            Your <span>Information</span>
        </div>

        <section class="profile-details">

            <div class="profile-item">
                <small>Full Name</small>
                <strong><?= e($dietitian["name"]) ?></strong>
            </div>

            <div class="profile-item">
                <small>Email Address</small>
                <strong><?= e($dietitian["email"]) ?></strong>
            </div>

            <div class="profile-item">
                <small>Phone Number</small>
                <strong><?= e($dietitian["phone"]) ?></strong>
            </div>

        </section>

        <!-- WORKSPACE -->
        <div class="section-heading">
            Nutrition <span>Workspace</span>
        </div>

        <section class="dashboard-grid">

            <a href="diet_plans.php" class="dietitian-card">
                <div class="card-icon">🥗</div>
                <h3>Manage Diet Plans</h3>
                <p>Create and manage nutrition plans for gym clients.</p>
            </a>

            <a href="clients.php" class="dietitian-card">
                <div class="card-icon">👥</div>
                <h3>View Clients</h3>
                <p>View registered gym clients and their details.</p>
            </a>

            <a href="questions.php" class="dietitian-card">
                <div class="card-icon">💬</div>
                <h3>Client Questions</h3>
                <p>Answer nutrition questions submitted by clients.</p>
            </a>

            <a href="profile.php" class="dietitian-card">
                <div class="card-icon">👤</div>
                <h3>My Profile</h3>
                <p>Update your personal details and password.</p>
            </a>

        </section>

    </main>

</div>

<footer class="dietitian-footer">
    © <?= date("Y") ?> Smart Gym Management System. All rights reserved.
</footer>

</body>
</html>
