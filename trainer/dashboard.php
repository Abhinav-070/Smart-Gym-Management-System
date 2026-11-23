
<?php
session_start();

require_once "../config/database.php";

if (!isset($_SESSION["trainer_id"])) {
    header("Location: login.php");
    exit;
}

$trainer_id = $_SESSION["trainer_id"];

$stmt = $conn->prepare(
    "SELECT name, email, phone, specialization
     FROM trainers WHERE id = ?"
);

$stmt->execute([$trainer_id]);
$trainer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trainer) {
    $_SESSION = [];
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

<title>Trainer Dashboard | Smart Gym</title>

<link rel="stylesheet" href="../assets/css/style.css">

<style>
body {
    background: #090909;
}

/* LAYOUT */

.trainer-layout {
    min-height: 100vh;
    display: flex;
    background:
        linear-gradient(rgba(5,5,5,.88), rgba(5,5,5,.94)),
        url('../uploads/trainer-bg.jpg') center/cover fixed;
}

/* SIDEBAR */

.trainer-sidebar {
    width: 250px;
    min-width: 250px;
    min-height: 100vh;
    background: rgba(10,10,10,.96);
    border-right: 1px solid #292929;
    padding: 30px 20px;
    display: flex;
    flex-direction: column;
}

.trainer-logo {
    font-size: 23px;
    font-weight: 900;
    letter-spacing: 2px;
    color: white;
    margin-bottom: 40px;
}

.trainer-logo span {
    color: #e63946;
}

.trainer-sidebar .sidebar-label {
    color: #777;
    font-size: 11px;
    letter-spacing: 2px;
    margin: 10px 12px 15px;
}

.trainer-sidebar nav {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.trainer-sidebar nav a {
    display: block;
    text-decoration: none;
    color: #aaa;
    padding: 12px 14px;
    border-radius: 8px;
    font-size: 14px;
    transition: .25s;
}

.trainer-sidebar nav a:hover,
.trainer-sidebar nav a.active {
    color: white;
    background: rgba(230,57,70,.15);
    border-left: 3px solid #e63946;
}

.trainer-sidebar nav .logout-link {
    margin-top: 25px;
    color: #ff7777;
}

/* MAIN CONTENT */

.trainer-main {
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

.trainer-welcome {
    padding: 42px 35px;
    margin: 30px 0;
    border: 1px solid #393939;
    border-radius: 16px;
    background:
        linear-gradient(90deg, rgba(0,0,0,.92), rgba(0,0,0,.45)),
        url('../uploads/trainer-bg.jpg') center/cover;
}

.eyebrow {
    color: #e63946;
    font-size: 12px;
    letter-spacing: 3px;
    font-weight: bold;
}

.trainer-welcome h1 {
    font-size: clamp(28px, 4vw, 43px);
    margin: 12px 0;
    line-height: 1.2;
}

.trainer-welcome h1 span {
    color: #e63946;
}

.trainer-welcome p {
    color: #bbb;
    font-size: 15px;
}

/* PROFILE INFORMATION */

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

.dashboard-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 18px;
}

.trainer-card {
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

.trainer-card:hover {
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

.trainer-card h3 {
    color: white;
    font-size: 16px;
    margin-bottom: 7px;
}

.trainer-card p {
    color: #999;
    font-size: 13px;
    line-height: 1.6;
}

/* FIXED FOOTER */

.trainer-footer {
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
    .trainer-layout {
        flex-direction: column;
    }

    .trainer-sidebar {
        width: 100%;
        min-width: 0;
        min-height: auto;
        padding: 17px;
        border-right: none;
        border-bottom: 1px solid #292929;
    }

    .trainer-logo {
        margin-bottom: 15px;
    }

    .trainer-sidebar .sidebar-label {
        display: none;
    }

    .trainer-sidebar nav {
        flex-direction: row;
        overflow-x: auto;
        padding-bottom: 5px;
    }

    .trainer-sidebar nav a {
        white-space: nowrap;
    }

    .trainer-sidebar nav .logout-link {
        margin-top: 0;
    }

    .trainer-footer {
        left: 0;
    }
}

@media(max-width: 500px) {
    .trainer-main {
        padding: 20px 5% 85px;
    }

    .trainer-welcome {
        padding: 30px 20px;
    }

    .profile-details {
        grid-template-columns: 1fr;
    }

    .dashboard-cards {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<div class="trainer-layout">

    <!-- SIDEBAR -->
    <aside class="trainer-sidebar">

        <div class="trainer-logo">
            SMART<span>GYM.</span>
        </div>

        <p class="sidebar-label">TRAINER MENU</p>

        <nav>
            <a href="dashboard.php" class="active">⌂ &nbsp; Dashboard</a>
            <a href="clients.php">♙ &nbsp; My Clients</a>
            <a href="workout_plans.php">◆ &nbsp; Workout Plans</a>
            <a href="progress.php">▥ &nbsp; Progress Tracking</a>
            <a href="questions.php">✉ &nbsp; Client Questions</a>
            <a href="upload_video.php">▶ &nbsp; Workout Videos</a>
            <a href="logout.php" class="logout-link">↪ &nbsp; Logout</a>
        </nav>

    </aside>

    <!-- MAIN CONTENT -->
    <main class="trainer-main">

        <header class="topbar">
            <div>
                <p>SMART GYM / TRAINER PORTAL</p>
                <strong>Trainer Dashboard</strong>
            </div>

            <span class="role-label">TRAINER ACCOUNT</span>
        </header>

        <!-- WELCOME -->
        <section class="trainer-welcome">

            <div class="eyebrow">TRAINER WORKSPACE</div>

            <h1>
                Welcome back,<br>
                <span><?= e($trainer["name"]) ?></span>
            </h1>

            <p>
                Guide your clients. Build stronger habits.
                Track every fitness achievement.
            </p>

        </section>

        <!-- TRAINER INFORMATION -->
        <div class="section-heading">
            Your <span>Information</span>
        </div>

        <section class="profile-details">

            <div class="profile-item">
                <small>Full Name</small>
                <strong><?= e($trainer["name"]) ?></strong>
            </div>

            <div class="profile-item">
                <small>Email Address</small>
                <strong><?= e($trainer["email"]) ?></strong>
            </div>

            <div class="profile-item">
                <small>Phone Number</small>
                <strong><?= e($trainer["phone"]) ?></strong>
            </div>

            <div class="profile-item">
                <small>Specialization</small>
                <strong><?= e($trainer["specialization"]) ?></strong>
            </div>

        </section>

        <!-- TRAINER WORKSPACE -->
        <div class="section-heading">
            Training <span>Workspace</span>
        </div>

        <section class="dashboard-cards">

            <a href="clients.php" class="trainer-card">
                <div class="card-icon">👥</div>
                <h3>My Clients</h3>
                <p>View and manage your assigned gym clients.</p>
            </a>

            <a href="workout_plans.php" class="trainer-card">
                <div class="card-icon">🏋️</div>
                <h3>Workout Plans</h3>
                <p>Create and manage client workout plans.</p>
            </a>

            <a href="progress.php" class="trainer-card">
                <div class="card-icon">📈</div>
                <h3>Progress Tracking</h3>
                <p>Monitor your clients' fitness progress.</p>
            </a>

            <a href="questions.php" class="trainer-card">
                <div class="card-icon">💬</div>
                <h3>Client Questions</h3>
                <p>View questions and respond to your clients.</p>
            </a>

            <a href="upload_video.php" class="trainer-card">
                <div class="card-icon">▶️</div>
                <h3>Workout Videos</h3>
                <p>Upload workout videos for your assigned clients.</p>
            </a>

        </section>

    </main>

</div>

<footer class="trainer-footer">
    © <?= date("Y") ?> Smart Gym Management System. All rights reserved.
</footer>

</body>
</html>
