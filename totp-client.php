<?php
require('config.php');
require('totp.php');

$ga = new PHPGangsta_GoogleAuthenticator();
$code = $ga->getCode(TOTP_Secret);
echo $code;
