
<?php
session_start();

require_once "../config/database.php";

if (!isset($_SESSION["trainer_id"])) {
    header("Location: login.php");
    exit;
}

$trainer_id = $_SESSION["trainer_id"];
$success = "";
$error = "";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$stmt = $conn->prepare("
    SELECT c.id, c.name
    FROM clients c
    INNER JOIN trainer_clients tc ON tc.client_id = c.id
    WHERE tc.trainer_id = ?
    ORDER BY c.name
");
$stmt->execute([$trainer_id]);
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        $error = "Invalid request. Please try again.";
    } else {

        $client_id = filter_var(
            $_POST["client_id"] ?? null,
            FILTER_VALIDATE_INT
        );

        $title = trim($_POST["title"] ?? "");
        $description = trim($_POST["description"] ?? "");

        $check = $conn->prepare("
            SELECT id FROM trainer_clients
            WHERE trainer_id = ? AND client_id = ?
        ");
        $check->execute([$trainer_id, $client_id]);

        if (!$client_id || !$check->fetch()) {
            $error = "Please select a valid assigned client.";
        } elseif ($title === "" || strlen($title) > 150) {
            $error = "Enter a valid video title (maximum 150 characters).";
        } elseif (
            !isset($_FILES["video"]) ||
            $_FILES["video"]["error"] !== UPLOAD_ERR_OK
        ) {
            $error = "Please select a video file.";
        } else {

            $file = $_FILES["video"];
            $max_size = 100 * 1024 * 1024;

            if ($file["size"] > $max_size) {
                $error = "Video must be smaller than 100 MB.";
            } else {

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file["tmp_name"]);

                $allowed_types = [
                    "video/mp4" => "mp4",
                    "video/webm" => "webm"
                ];

                if (!isset($allowed_types[$mime])) {
                    $error = "Only MP4 and WebM videos are allowed.";
                } else {

                    $upload_dir = dirname(__DIR__) .
                        "/uploads/workout_videos/";

                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    $filename = bin2hex(random_bytes(20)) .
                        "." . $allowed_types[$mime];

                    $destination = $upload_dir . $filename;

                    if (move_uploaded_file($file["tmp_name"], $destination)) {

                        try {
                            $insert = $conn->prepare("
                                INSERT INTO workout_videos
                                (trainer_id, client_id, title, description, video_filename)
                                VALUES (?, ?, ?, ?, ?)
                            ");

                            $insert->execute([
                                $trainer_id,
                                $client_id,
                                $title,
                                $description,
                                $filename
                            ]);

                            $success = "Workout video uploaded successfully!";
                            $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

                        } catch (PDOException $e) {
                            if (is_file($destination)) {
                                unlink($destination);
                            }
                            $error = "Unable to save video details.";
                        }

                    } else {
                        $error = "Video upload failed. Check folder permissions.";
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Workout Video | Smart Gym</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        body {
            background: #111;
            color: #fff;
            font-family: Arial, sans-serif;
        }

        .upload-wrapper {
            max-width: 750px;
            margin: 55px auto;
            padding: 20px;
        }

        .upload-header {
            margin-bottom: 25px;
        }

        .upload-header h1 {
            font-size: 30px;
            margin-bottom: 10px;
        }

        .upload-header h1 span {
            color: #e63946;
        }

        .upload-header p {
            color: #aaa;
            font-size: 14px;
        }

        .upload-card {
            background: #1c1c1c;
            border: 1px solid #333;
            border-left: 4px solid #e63946;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            margin-bottom: 9px;
            font-weight: bold;
            font-size: 14px;
            color: #eee;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 13px 15px;
            background: #111;
            border: 1px solid #444;
            border-radius: 7px;
            color: #fff;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #e63946;
        }

        .form-group select option {
            background: #1c1c1c;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 110px;
        }

        .file-box {
            background: #151515;
            border: 1px dashed #e63946;
            border-radius: 9px;
            padding: 22px;
            text-align: center;
        }

        .file-box input {
            border: none;
            background: transparent;
            padding: 10px 0;
        }

        .file-box p {
            color: #999;
            font-size: 12px;
            margin: 8px 0 0;
        }

        .upload-btn {
            width: 100%;
            padding: 14px;
            background: #e63946;
            color: #fff;
            border: none;
            border-radius: 7px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .upload-btn:hover {
            background: #c92d3a;
            transform: translateY(-2px);
        }

        .message {
            padding: 13px 16px;
            border-radius: 7px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .success-message {
            background: #183c27;
            color: #65d68a;
            border: 1px solid #28623e;
        }

        .error-message {
            background: #442020;
            color: #ff7070;
            border: 1px solid #703333;
        }

        .empty-state {
            text-align: center;
            padding: 35px 20px;
            background: #151515;
            border-radius: 8px;
            color: #aaa;
        }

        .back-link {
            display: inline-block;
            margin-top: 22px;
            color: #e63946;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            .upload-wrapper {
                margin: 25px auto;
                padding: 15px;
            }

            .upload-card {
                padding: 20px;
            }

            .upload-header h1 {
                font-size: 24px;
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

<div class="upload-wrapper">

    <div class="upload-header">
        <h1>Workout <span>Video Upload</span></h1>
        <p>Share workout demonstrations and training videos with your assigned clients.</p>
    </div>

    <div class="upload-card">

        <?php if ($success): ?>
            <div class="message success-message">
                ✓ <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error-message">
                ⚠ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (count($clients) > 0): ?>

            <form method="POST" enctype="multipart/form-data">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>"
                >

                <div class="form-group">
                    <label for="client_id">Select Client</label>

                    <select name="client_id" id="client_id" required>
                        <option value="">Choose an assigned client</option>

                        <?php foreach ($clients as $client): ?>
                            <option value="<?= (int)$client["id"] ?>">
                                <?= htmlspecialchars($client["name"]) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="form-group">
                    <label for="title">Video Title</label>

                    <input
                        type="text"
                        id="title"
                        name="title"
                        maxlength="150"
                        placeholder="e.g. Beginner Chest Workout"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="description">Video Description</label>

                    <textarea
                        id="description"
                        name="description"
                        placeholder="Describe the workout, exercises or instructions..."
                    ></textarea>
                </div>

                <div class="form-group">
                    <label for="video">Select Workout Video</label>

                    <div class="file-box">
                        <input
                            type="file"
                            id="video"
                            name="video"
                            accept="video/mp4,video/webm"
                            required
                        >

                        <p>Supported formats: MP4, WebM | Maximum size: 100 MB</p>
                    </div>
                </div>

                <button type="submit" class="upload-btn">
                    Upload Workout Video
                </button>

            </form>

        <?php else: ?>

            <div class="empty-state">
                <h3>No Assigned Clients</h3>
                <p>You need to have clients assigned by the admin before uploading videos.</p>
            </div>

        <?php endif; ?>

    </div>

    <a href="dashboard.php" class="back-link">
        ← Back to Dashboard
    </a>

</div>

</body>
</html>
