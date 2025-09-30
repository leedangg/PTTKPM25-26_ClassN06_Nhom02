<?php
session_start();
session_destroy();
header("Location: /auth/login.php"); // Changed from /KiemThu/auth/login.php
exit();
