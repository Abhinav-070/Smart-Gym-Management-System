
<?php
session_start();

if (!isset($_SESSION["client_id"])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BMI Calculator | Smart Gym</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .bmi-container {
            min-height: 85vh;
            padding: 50px 5%;
        }

        .bmi-container h1 {
            text-align: center;
            color: #e63946;
            margin-bottom: 15px;
        }

        .bmi-description {
            text-align: center;
            color: #ccc;
            margin-bottom: 35px;
        }

        .bmi-card {
            max-width: 500px;
            margin: auto;
            padding: 35px;
            background: #1c1c1c;
            border-radius: 10px;
            border: 1px solid #333;
        }

        .bmi-card label {
            display: block;
            margin: 18px 0 8px;
            color: white;
        }

        .bmi-card input {
            width: 100%;
            padding: 13px;
            background: #080808;
            border: 1px solid #444;
            border-radius: 5px;
            color: white;
            font-size: 16px;
        }

        .bmi-card input:focus {
            outline: none;
            border-color: #e63946;
        }

        .bmi-card button {
            width: 100%;
            padding: 13px;
            margin-top: 25px;
            background: #e63946;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        .bmi-card button:hover {
            background: #c1121f;
        }

        .bmi-result {
            display: none;
            margin-top: 25px;
            padding: 20px;
            background: #080808;
            border: 1px solid #444;
            border-radius: 8px;
            text-align: center;
        }

        .bmi-value {
            font-size: 38px;
            font-weight: bold;
            color: #e63946;
            margin: 10px 0;
        }

        .bmi-category {
            font-size: 20px;
            font-weight: bold;
            color: white;
        }

        .bmi-note {
            color: #aaa;
            font-size: 13px;
            margin-top: 15px;
            line-height: 1.6;
        }

        .bmi-error {
            color: #ff7070;
            margin-top: 15px;
            text-align: center;
        }

        .back-link {
            display: block;
            text-align: center;
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
        <a href="profile.php">Profile</a>
        <a href="logout.php">Logout</a>
    </nav>

</header>

<main class="bmi-container">

    <h1>BMI CALCULATOR</h1>

    <p class="bmi-description">
        Calculate your Body Mass Index and check your BMI category.
    </p>

    <div class="bmi-card">

        <form id="bmiForm">

            <label for="weight">Weight (kg)</label>

            <input
                type="number"
                id="weight"
                placeholder="Enter your weight"
                min="1"
                max="500"
                step="0.1"
                required
            >

            <label for="height">Height (cm)</label>

            <input
                type="number"
                id="height"
                placeholder="Enter your height"
                min="50"
                max="250"
                step="0.1"
                required
            >

            <button type="submit">
                CALCULATE BMI
            </button>

        </form>

        <div class="bmi-error" id="error"></div>

        <div class="bmi-result" id="result">

            <h3>Your BMI</h3>

            <div class="bmi-value" id="bmiValue"></div>

            <div class="bmi-category" id="bmiCategory"></div>

            <p class="bmi-note">
                BMI is a screening measure, not a medical diagnosis.
                Adult BMI categories may not be suitable for children,
                teenagers or pregnant people.
            </p>

        </div>

    </div>

    <a href="dashboard.php" class="back-link">
        ← Back to Dashboard
    </a>

</main>

<footer>
    <p>&copy; <?= date("Y") ?> Smart Gym Management System</p>
</footer>

<script>

document.getElementById("bmiForm").addEventListener("submit", function(event) {

    event.preventDefault();

    const weight = parseFloat(document.getElementById("weight").value);
    const height = parseFloat(document.getElementById("height").value);

    const error = document.getElementById("error");
    const result = document.getElementById("result");

    error.textContent = "";
    result.style.display = "none";

    if (
        !Number.isFinite(weight) ||
        !Number.isFinite(height) ||
        weight <= 0 ||
        height <= 0 ||
        weight > 500 ||
        height < 50 ||
        height > 250
    ) {
        error.textContent = "Please enter valid weight and height values.";
        return;
    }

    const heightInMeters = height / 100;
    const bmi = weight / (heightInMeters * heightInMeters);

    let category = "";

    if (bmi < 18.5) {
        category = "Underweight";
    } else if (bmi < 25) {
        category = "Normal weight";
    } else if (bmi < 30) {
        category = "Overweight";
    } else {
        category = "Obesity";
    }

    document.getElementById("bmiValue").textContent = bmi.toFixed(2);
    document.getElementById("bmiCategory").textContent = category;

    result.style.display = "block";

});

</script>

</body>
</html>
