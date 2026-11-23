
<?php
session_start();

// Remove dietitian session data
unset(
    $_SESSION["dietitian_id"],
    $_SESSION["dietitian_name"]
);

// Regenerate session ID
session_regenerate_id(true);

// Redirect to login
header("Location: login.php");
exit;
