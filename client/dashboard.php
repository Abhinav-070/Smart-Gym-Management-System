
<?php
session_start();

if (!isset($_SESSION["client_id"])) {
    header("Location: login.php");
    exit;
}

$client_name = $_SESSION["client_name"] ?? "Client";
$safe_name = htmlspecialchars($client_name, ENT_QUOTES, 'UTF-8');
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Client Dashboard | Smart Gym</title>

<link rel="stylesheet" href="../assets/css/style.css">

<style>
body {
    background: #090909;
}

.client-layout {
    min-height: 100vh;
    display: flex;
    background:
        linear-gradient(rgba(5,5,5,.88), rgba(5,5,5,.94)),
        url('../uploads/client-bg.jpg') center/cover fixed;
}

/* SIDEBAR */

.sidebar {
    width: 250px;
    min-width: 250px;
    background: rgba(10,10,10,.95);
    border-right: 1px solid #292929;
    padding: 30px 20px;
    display: flex;
    flex-direction: column;
}

.logo {
    font-size: 23px;
    font-weight: 900;
    letter-spacing: 2px;
    color: #fff;
    margin-bottom: 45px;
}

.logo span {
    color: #e63946;
}

.sidebar-label {
    color: #777;
    font-size: 11px;
    letter-spacing: 2px;
    margin: 10px 12px 15px;
}

.sidebar a {
    text-decoration: none;
    color: #aaa;
    padding: 12px 14px;
    border-radius: 8px;
    margin-bottom: 6px;
    font-size: 14px;
    transition: .25s;
}

.sidebar a:hover,
.sidebar a.active {
    color: white;
    background: rgba(230,57,70,.15);
    border-left: 3px solid #e63946;
}

.sidebar .logout-link {
    margin-top: auto;
    color: #ff7777;
}

/* MAIN CONTENT */

.main-content {
    flex: 1;
    min-width: 0;
    padding: 30px 4%;
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
    color: #fff;
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

.welcome {
    padding: 45px 35px;
    margin: 30px 0;
    border: 1px solid #393939;
    border-radius: 16px;
    background:
        linear-gradient(90deg, rgba(0,0,0,.92), rgba(0,0,0,.45)),
        url('../uploads/client-bg.jpg') center/cover;
}

.welcome .eyebrow {
    color: #e63946;
    font-size: 12px;
    letter-spacing: 3px;
    font-weight: bold;
}

.welcome h1 {
    font-size: clamp(28px, 4vw, 43px);
    margin: 12px 0;
    line-height: 1.2;
}

.welcome h1 span {
    color: #e63946;
}

.welcome p {
    color: #bbb;
    font-size: 15px;
}

/* SECTION HEADING */

.section-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 35px 0 20px;
}

.section-heading h2 {
    font-size: 21px;
}

.section-heading span {
    color: #e63946;
}

/* DASHBOARD GRID */

.dashboard-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 18px;
}

.dashboard-card {
    display: block;
    min-height: 165px;
    padding: 23px;
    text-decoration: none;
    color: white;
    background: rgba(23,23,23,.94);
    border: 1px solid #303030;
    border-radius: 13px;
    transition: .3s;
}

.dashboard-card:hover {
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

.dashboard-card h3 {
    color: #fff;
    font-size: 16px;
    margin-bottom: 7px;
}

.dashboard-card p {
    color: #999;
    font-size: 13px;
    line-height: 1.6;
}

/* FOOTER */

.dashboard-footer {
    padding: 30px 0 10px;
    margin-top: 35px;
    border-top: 1px solid #292929;
    color: #777;
    font-size: 12px;
}

/* RESPONSIVE */

@media(max-width: 900px) {
    .client-layout {
        flex-direction: column;
    }

    .sidebar {
        width: 100%;
        min-width: 0;
        padding: 17px;
        border-right: none;
        border-bottom: 1px solid #292929;
    }

    .logo {
        margin-bottom: 15px;
    }

    .sidebar-label {
        display: none;
    }

    .sidebar nav {
        display: flex;
        gap: 7px;
        overflow-x: auto;
        padding-bottom: 5px;
    }

    .sidebar a {
        white-space: nowrap;
        margin: 0;
    }

    .sidebar .logout-link {
        margin-top: 0;
    }
}

@media(max-width: 500px) {
    .main-content {
        padding: 20px 5%;
    }

    .welcome {
        padding: 30px 20px;
    }

    .dashboard-cards {
        grid-template-columns: 1fr;
    }

    .topbar {
        align-items: flex-start;
    }
}
</style>
</head>

<body>

<div class="client-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="logo">SMART<span>GYM</span></div>

        <p class="sidebar-label">MAIN MENU</p>

        <nav>
            <a href="dashboard.php" class="active">⌂ &nbsp; Dashboard</a>
            <a href="profile.php">◉ &nbsp; My Profile</a>
            <a href="membership.php">▣ &nbsp; Membership</a>
            <a href="workout_plans.php">◆ &nbsp; Workout Plans</a>
            <a href="diet_plans.php">♥ &nbsp; Diet Plans</a>
            <a href="progress.php">▥ &nbsp; My Progress</a>
            <a href="bmi_calculator.php">⚖ &nbsp; BMI Calculator</a>
            <a href="ask_trainer.php">✉ &nbsp; Ask Trainer</a>
            <a href="ask_dietitian.php">✉ &nbsp; Ask Dietitian</a>
            <a href="complaints.php">⚑ &nbsp; Complaints</a>
            <a href="workout_videos.php">▶ &nbsp; Workout Videos</a>
            <a href="logout.php" class="logout-link">↪ &nbsp; Logout</a>
        </nav>

    </aside>

    <!-- MAIN -->
    <main class="main-content">

        <header class="topbar">
            <div>
                <p>SMART GYM / CLIENT PORTAL</p>
                <strong>Member Dashboard</strong>
            </div>

            <span class="role-label">CLIENT ACCOUNT</span>
        </header>

        <!-- WELCOME -->
        <section class="welcome">

            <div class="eyebrow">YOUR FITNESS JOURNEY</div>

            <h1>Welcome back,<br>
                <span><?= $safe_name ?></span>
            </h1>

            <p>
                Stay consistent. Stay strong. Make every workout count.
            </p>

        </section>

        <!-- FEATURES -->
        <div class="section-heading">
            <h2>Your <span>Fitness Hub</span></h2>
        </div>

        <section class="dashboard-cards">

            <a href="profile.php" class="dashboard-card">
                <div class="card-icon">👤</div>
                <h3>My Profile</h3>
                <p>View and manage your personal information.</p>
            </a>

            <a href="membership.php" class="dashboard-card">
                <div class="card-icon">💳</div>
                <h3>My Membership</h3>
                <p>View your membership and subscription details.</p>
            </a>

            <a href="workout_plans.php" class="dashboard-card">
                <div class="card-icon">🏋️</div>
                <h3>Workout Plans</h3>
                <p>Follow your assigned workout schedule.</p>
            </a>

            <a href="diet_plans.php" class="dashboard-card">
                <div class="card-icon">🥗</div>
                <h3>My Diet Plans</h3>
                <p>Explore your nutrition and diet instructions.</p>
            </a>

            <a href="progress.php" class="dashboard-card">
                <div class="card-icon">📈</div>
                <h3>My Progress</h3>
                <p>Track your weight, BMI and fitness improvements.</p>
            </a>

            <a href="bmi_calculator.php" class="dashboard-card">
                <div class="card-icon">⚖️</div>
                <h3>BMI Calculator</h3>
                <p>Calculate your Body Mass Index.</p>
            </a>

            <a href="ask_trainer.php" class="dashboard-card">
                <div class="card-icon">💬</div>
                <h3>Ask Your Trainer</h3>
                <p>Ask questions and receive trainer guidance.</p>
            </a>

            <a href="ask_dietitian.php" class="dashboard-card">
                <div class="card-icon">🥑</div>
                <h3>Ask a Dietitian</h3>
                <p>Get answers to your nutrition questions.</p>
            </a>

            <a href="complaints.php" class="dashboard-card">
                <div class="card-icon">📩</div>
                <h3>My Complaints</h3>
                <p>Submit complaints and track their status.</p>
            </a>

            <a href="workout_videos.php" class="dashboard-card">
                <div class="card-icon">▶️</div>
                <h3>Workout Videos</h3>
                <p>Watch videos uploaded by your trainer.</p>
            </a>

        </section>

        <footer class="dashboard-footer">
            © <?= date("Y") ?> Smart Gym Management System. All rights reserved.
        </footer>

    </main>

</div>

</body>
</html>
