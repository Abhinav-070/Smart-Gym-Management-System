
<?php
session_start();

require_once "../config/database.php";

if (!isset($_SESSION["client_id"])) {
    header("Location: login.php");
    exit;
}

$client_id = $_SESSION["client_id"];

$video_id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$video_id) {
    http_response_code(400);
    exit("Invalid video request.");
}

$stmt = $conn->prepare("
    SELECT
        w.video_filename,
        w.title,
        w.description,
        w.uploaded_at,
        t.name AS trainer_name
    FROM workout_videos w
    INNER JOIN trainers t ON t.id = w.trainer_id
    WHERE w.id = ? AND w.client_id = ?
    LIMIT 1
");

$stmt->execute([$video_id, $client_id]);
$video = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$video) {
    http_response_code(404);
    exit("Video not found.");
}

$filename = basename($video["video_filename"]);

$video_path = realpath(
    dirname(__DIR__) . "/uploads/workout_videos/" . $filename
);

$upload_dir = realpath(
    dirname(__DIR__) . "/uploads/workout_videos/"
);

if (
    !$video_path ||
    !$upload_dir ||
    strpos($video_path, $upload_dir . DIRECTORY_SEPARATOR) !== 0 ||
    !is_file($video_path)
) {
    http_response_code(404);
    exit("Video file not found.");
}

$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

$mime_types = [
    "mp4" => "video/mp4",
    "webm" => "video/webm"
];

if (!isset($mime_types[$extension])) {
    http_response_code(415);
    exit("Unsupported video format.");
}

if (isset($_GET["stream"])) {
    header("Content-Type: " . $mime_types[$extension]);
    header("Content-Length: " . filesize($video_path));
    header("Content-Disposition: inline; filename=\"workout-video." . $extension . "\"");
    header("X-Content-Type-Options: nosniff");
    header("Accept-Ranges: none");

    readfile($video_path);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($video["title"]) ?> | Smart Gym</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        body {
            background: #111;
            color: #fff;
            font-family: Arial, sans-serif;
        }

        .watch-wrapper {
            max-width: 1000px;
            margin: 45px auto;
            padding: 20px;
        }

        .watch-header {
            margin-bottom: 25px;
        }

        .watch-header h1 {
            font-size: 30px;
            margin-bottom: 10px;
        }

        .watch-header h1 span {
            color: #e63946;
        }

        .watch-header p {
            color: #aaa;
        }

        .video-container {
            background: #1c1c1c;
            border: 1px solid #333;
            border-left: 4px solid #e63946;
            border-radius: 12px;
            padding: 20px;
        }

        .video-player {
            width: 100%;
            display: block;
            max-height: 560px;
            background: #000;
            border-radius: 8px;
        }

        .video-details {
            padding: 22px 5px 5px;
        }

        .video-details h2 {
            font-size: 23px;
            margin-bottom: 15px;
        }

        .video-details p {
            color: #aaa;
            line-height: 1.7;
            overflow-wrap: anywhere;
        }

        .video-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 25px;
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px solid #333;
            color: #999;
            font-size: 14px;
        }

        .back-btn {
            display: inline-block;
            margin-top: 25px;
            padding: 12px 20px;
            background: #e63946;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            transition: 0.3s;
        }

        .back-btn:hover {
            background: #c92d3a;
        }

        @media (max-width: 600px) {
            .watch-wrapper {
                margin: 25px auto;
                padding: 15px;
            }

            .watch-header h1 {
                font-size: 24px;
            }

            .video-container {
                padding: 12px;
            }

            .video-details h2 {
                font-size: 19px;
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

<div class="watch-wrapper">

    <div class="watch-header">
        <h1>Workout <span>Video Player</span></h1>
        <p>Follow your trainer's instructions and stay consistent.</p>
    </div>

    <div class="video-container">

        <video class="video-player" controls playsinline preload="metadata">
            <source
                src="watch_video.php?id=<?= (int)$video_id ?>&stream=1"
                type="<?= htmlspecialchars($mime_types[$extension]) ?>"
            >

            Your browser does not support video playback.
        </video>

        <div class="video-details">

            <h2><?= htmlspecialchars($video["title"]) ?></h2>

            <?php if (!empty($video["description"])): ?>
                <p>
                    <?= nl2br(htmlspecialchars($video["description"])) ?>
                </p>
            <?php endif; ?>

            <div class="video-meta">

                <span>
                    Trainer:
                    <?= htmlspecialchars($video["trainer_name"]) ?>
                </span>

                <span>
                    Uploaded:
                    <?= htmlspecialchars(date(
                        "d M Y",
                        strtotime($video["uploaded_at"])
                    )) ?>
                </span>

            </div>

        </div>

    </div>

    <a href="workout_videos.php" class="back-btn">
        ← Back to Workout Videos
    </a>

</div>

</body>
</html>
