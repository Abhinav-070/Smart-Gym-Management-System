
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description" content="Smart Gym Management System - Personal training, diet planning, membership management and fitness tracking.">

    <title>Smart Gym Management System</title>

    <link rel="stylesheet" href="/assets/css/style.css?v=3">
</head>

<body>

    <header class="navbar">

        <h2>SMART GYM</h2>

        <nav aria-label="Main navigation">
            <a href="/index/">Home</a>
            <a href="#about">About</a>
            <a href="#services">Services</a>
            <a href="#contact">Contact</a>
            <a href="/client/login.php">Login</a>
        </nav>

    </header>

    <main>

        <section class="hero">

            <div class="hero-content">

                <h1>BUILD YOUR BODY</h1>

                <h2>TRANSFORM YOUR LIFE</h2>

                <p>Your fitness journey starts here.</p>

                <div class="hero-buttons">
                    <a href="/client/register.php" class="btn">
                        GET STARTED
                    </a>

                    <a href="#services" class="btn btn-outline">
                        EXPLORE SERVICES
                    </a>
                </div>

            </div>

        </section>

        <section class="section" id="about">

            <h2>ABOUT SMART GYM</h2>

            <p>
                Smart Gym provides a complete fitness management
                experience with professional trainers, personalized
                diet plans and fitness progress tracking.
                Manage your fitness journey through one platform.
            </p>

        </section>

        <section class="section" id="services">

            <h2>OUR SERVICES</h2>

            <div class="services">

                <div class="service-card">
                    <h3>Personal Training</h3>
                    <p>
                        Get professional fitness guidance
                        and structured workout plans.
                    </p>
                </div>

                <div class="service-card">
                    <h3>Diet Planning</h3>
                    <p>
                        Follow personalized diet plans
                        designed to support your fitness goals.
                    </p>
                </div>

                <div class="service-card">
                    <h3>BMI Tracking</h3>
                    <p>
                        Monitor your weight, BMI and
                        fitness progress over time.
                    </p>
                </div>

                <div class="service-card">
                    <h3>Membership Management</h3>
                    <p>
                        Request memberships and track
                        your membership status.
                    </p>
                </div>

            </div>

        </section>

        <section class="section contact-section" id="contact">

            <h2>GET STARTED TODAY</h2>

            <p>
                Take the first step towards a healthier lifestyle.
                Register with Smart Gym and manage your fitness journey.
            </p>

            <a href="/client/register.php" class="btn">
                JOIN SMART GYM
            </a>

        </section>

    </main>

    <footer>

        <p>
            &copy; <?= date("Y") ?>
            Smart Gym Management System. All rights reserved.
        </p>

    </footer>

</body>
</html>
