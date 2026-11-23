
<?php
session_start();

require_once "../config/database.php";

if (!isset($_SESSION["client_id"])) {
    header("Location: login.php");
    exit;
}

$client_id = $_SESSION["client_id"];

$stmt = $conn->prepare("
    SELECT
        w.id,
        w.title,
        w.description,
        w.uploaded_at,
        t.name AS trainer_name
    FROM workout_videos w
    INNER JOIN trainers t ON t.id = w.trainer_id
    WHERE w.client_id = ?
    ORDER BY w.uploaded_at DESC
");

$stmt->execute([$client_id]);
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Workout Videos | Smart Gym</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        body {
            background: #111;
            color: #fff;
            font-family: Arial, sans-serif;
        }

        .videos-wrapper {
            max-width: 1100px;
            margin: 45px auto;
            padding: 20px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 30px;
        }

        .page-header h1 span {
            color: #e63946;
        }

        .page-header p {
            color: #aaa;
        }

        .videos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 22px;
        }

        .video-card {
            background: #1c1c1c;
            border: 1px solid #333;
            border-left: 4px solid #e63946;
            border-radius: 10px;
            padding: 23px;
            transition: 0.3s;
        }

        .video-card:hover {
            transform: translateY(-4px);
            border-color: #e63946;
        }

        .video-icon {
            width: 48px;
            height: 48px;
            background: #442020;
            color: #ff7070;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 18px;
        }

        .video-card h3 {
            color: #fff;
            margin-bottom: 12px;
            font-size: 19px;
        }

        .video-description {
            color: #aaa;
            font-size: 14px;
            line-height: 1.6;
            min-height: 45px;
            overflow-wrap: anywhere;
        }

        .video-meta {
            border-top: 1px solid #333;
            margin-top: 18px;
            padding-top: 15px;
            color: #999;
            font-size: 13px;
            line-height: 1.9;
        }

        .watch-btn {
            display: block;
            text-align: center;
            text-decoration: none;
            background: #e63946;
            color: white;
            padding: 12px;
            border-radius: 6px;
            margin-top: 20px;
            font-weight: bold;
            transition: 0.3s;
        }

        .watch-btn:hover {
            background: #c92d3a;
        }

        .empty-state {
            background: #1c1c1c;
            border: 1px solid #333;
            border-radius: 10px;
            padding: 55px 20px;
            text-align: center;
        }

        .empty-state h3 {
            color: #e63946;
            margin-bottom: 12px;
        }

        .empty-state p {
            color: #aaa;
        }

        .back-link {
            display: inline-block;
            margin-top: 25px;
            color: #e63946;
            text-decoration: none;
        }

        @media (max-width: 600px) {
            .videos-wrapper {
                margin: 25px auto;
                padding: 15px;
            }

            .page-header h1 {
                font-size: 25px;
            }
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

<div class="videos-wrapper">

    <div class="page-header">
        <h1> <span> My Workout Videos</span></h1>
        <p>Watch training videos shared by your assigned trainer.</p>
    </div>

    <?php if (count($videos) > 0): ?>

        <div class="videos-grid">

            <?php foreach ($videos as $video): ?>

                <div class="video-card">

                    <div class="video-icon">▶</div>

                    <h3>
                        <?= htmlspecialchars($video["title"]) ?>
                    </h3>

                    <p class="video-description">
                        <?= nl2br(htmlspecialchars($video["description"] ?? "")) ?>
                    </p>

                    <div class="video-meta">
                        <div>
                            Trainer:
                            <?= htmlspecialchars($video["trainer_name"]) ?>
                        </div>

                        <div>
                            Uploaded:
                            <?= htmlspecialchars(date(
                                "d M Y",
                                strtotime($video["uploaded_at"])
                            )) ?>
                        </div>
                    </div>

                    <a
                        class="watch-btn"
                        href="watch_video.php?id=<?= (int)$video["id"] ?>"
                    >
                        ▶ Watch Video
                    </a>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="empty-state">
            <h3>No Workout Videos Yet</h3>
            <p>Your trainer hasn't uploaded any workout videos for you yet.</p>
        </div>

    <?php endif; ?>

    <a href="dashboard.php" class="back-link">
        ← Back to Dashboard
    </a>

</div>

</body>
</html>
