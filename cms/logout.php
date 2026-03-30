<?php
session_name('cms_admin');
session_start();
session_destroy();
header('Location: login.php');
exit;
