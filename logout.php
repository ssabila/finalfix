<?php
require_once 'includes/auth.php';

$auth = new Auth();
$auth->logout();
redirect('index.php');
?>
