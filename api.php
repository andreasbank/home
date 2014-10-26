<?php
/**
 * VHS Home, Copyright 2014 by Andreas Bank, andreas.bank@axis.com (andreas.mikael.bank@gmail.com)
 * Licensed under GPLv3.
 */

date_default_timezone_set('Europe/Stockholm');

require('Home.php');

$result = "";
$action = array_key_exists('action', $_POST) && !empty($_POST['action']) ||
          array_key_exists('action', $_GET) && !empty($_GET['action']) ||
          null;

switch($action) {
case 'getLoggedInUserInfo':
  $user = $home->find_user();
  $result = sprintf("<?xml version=\"1.0\" encoding=\"utf-8\"?>\n");
  $result = sprintf("%s<users>\n", $result);
  $result = sprintf("%s%s", $result, $user->to_xml(false));
  $result = sprintf("%s</users>\n", $result);
  break;

case 'findPortalsWithBookings':
  $portals = $home->get_portals_array_with_bookings();
  $result = sprintf("<?xml version=\"1.0\" encoding=\"utf-8\"?>\n");
  $result = sprintf("%s<portals>\n", $result);
  for($i = 0; $i < count($portals); $i++) {
    $result = sprintf("%s%s", $result, $portal[$i]->to_xml(false));
  }
  $result = sprintf("%s</portals>\n", $result);
  break;

case 'do_login':
  if(!isset($_POST['username']) || empty($_POST['username']) ||
     !isset($_POST['password']) || empty($_POST['password'])) {
     printf('No username or password provided!');
     exit;
  }
  try {
    $home->login($_POST['username'], $_POST['password']);
  } catch(Exception $e) {
    printf("[%d] %s", $e->getCode(), $e->getMessage());
    exit(0);
  }
  header("Location: ./");
  break;

case 'do_logout':
  try {
    $user_id = $home->find_username_from_session_id($_COOKIE[$home->get_cookie_name()]);
    $home->logout($user_id);
  } catch(Exception $e) {
    printf("[%d] %s", $e->getCode(), $e->getMessage());
    exit(0);
  }
  header("Location: ./");
  break;

}

header("Content-type: application/xml");
printf("%s", $result);
