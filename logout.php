<?php
require_once __DIR__ . '/includes/functions.php';
session_unset();
session_destroy();
session_start();
setFlash("You have been signed out of your student session.", "info");
header("Location: step1_auth.php");
exit;
