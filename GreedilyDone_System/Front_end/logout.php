<?php
session_start();

// Burahin lahat ng session data
$_SESSION = array();

// Sirain ang session
session_destroy();

// I-redirect pabalik sa login page
header("Location: login.php");
exit;
?>