<?php
session_start();

// Optional: save logout time for shared access
file_put_contents("logout_flag.txt", time());

session_unset();
session_destroy();

header("Location: login.php");
exit();

