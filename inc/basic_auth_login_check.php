<?php

/**
 * \file
 * \brief Basic Authentication Login Check
 */

$user = $_SERVER['PHP_AUTH_USER'];
$pass = $_SERVER['PHP_AUTH_PW'];
$expected_user = $_ENV['BASIC_AUTH_USER'];
$expected_pass = $_ENV['BASIC_AUTH_PASSWORD'];

$validated = ($user == $expected_user) && ($pass == $expected_pass);

if (isset($expected_pass) && !$validated) {
  header('WWW-Authenticate: Basic realm="Learning with texts"');
  header('HTTP/1.0 401 Unauthorized');
  die ("Not authorized");
}

?>
