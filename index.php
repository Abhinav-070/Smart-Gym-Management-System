<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Smart Gym Management System</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <header class="navbar">

        <h2>SMART GYM</h2>

        <nav>
            <a href="index.php">Home</a>
            <a href="#about">About</a>
            <a href="#services">Services</a>
            <a href="#contact">Contact</a>
            <a href="client/login.php">Login</a>
        </nav>

    </header>


    <main class="hero">

        <div class="hero-content">

            <h1>BUILD YOUR BODY</h1>

            <h2>TRANSFORM YOUR LIFE</h2>

            <p>Your fitness journey starts here.</p>

            <a href="client/register.php" class="btn">
                GET STARTED
            </a>

        </div>

    </main>


    <section class="section" id="about">

        <h2>ABOUT SMART GYM</h2>

        <p>
            Smart Gym provides a complete fitness management
            experience with professional trainers, diet plans
            and fitness tracking.
        </p>

    </section>


    <section class="section" id="services">

        <h2>OUR SERVICES</h2>

        <div class="services">

            <div class="service-card">
                <h3>Personal Training</h3>
                <p>Professional fitness guidance.</p>
            </div>

            <div class="service-card">
                <h3>Diet Planning</h3>
                <p>Personalized diet recommendations.</p>
            </div>

            <div class="service-card">
                <h3>BMI Tracking</h3>
                <p>Monitor your fitness progress.</p>
            </div>

        </div>

    </section>


    <footer id="contact">

        <p>
            &copy; <?php echo date("Y"); ?>
            Smart Gym Management System.
        </p>

    </footer>

</body>
</html>