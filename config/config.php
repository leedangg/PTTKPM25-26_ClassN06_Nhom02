<?php
session_start();
define('BASE_URL', '/KiemThu');

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

