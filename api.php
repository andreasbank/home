<?php
/**
 * VHS Home, Copyright 2014 by Andreas Bank, andreas.bank@axis.com (andreas.mikael.bank@gmail.com)
 * Licensed under GPLv3.
 */

date_default_timezone_set('Europe/Stockholm');

require('Home.php');

$result = "";
$action = null;

if(array_key_exists('action', $_POST) && !empty($_POST['action'])) {
  $action = $_POST['action'];
}
elseif(array_key_exists('action', $_GET) && !empty($_GET['action'])) {
  $action = $_GET['action'];
}

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
    $result = sprintf("%s%s", $result, $portals[$i]->to_xml(false));
  }
  $result = sprintf("%s</portals>\n", $result);
  break;

case 'getUsers':
  $users = $home->get_users();
  $result = sprintf("<?xml version=\"1.0\" encoding=\"utf-8\"?>\n");
  $result = sprintf("%s<users>\n", $result);
  for($i = 0; $i < count($users); $i++) {
    $result = sprintf("%s%s", $result, $users[$i]->to_xml(false));
  }
  $result = sprintf("%s</users>\n", $result);
  break;

case 'getPortals':
  $portals = $home->get_portals_array_with_bookings();
  $result = sprintf("<?xml version=\"1.0\" encoding=\"utf-8\"?>\n");
  $result = sprintf("%s<portals>\n", $result);
  for($i = 0; $i < count($portals); $i++) {
    $result = sprintf("%s%s", $result, $portals[$i]->to_xml(false));
  }
  $result = sprintf("%s</portals>\n", $result);
  break;

case 'getBookings':
  $bookings = $home->get_bookings();
  $result = sprintf("<?xml version=\"1.0\" encoding=\"utf-8\"?>\n");
  $result = sprintf("%s<bookings>\n", $result);
  for($i = 0; $i < count($bookings); $i++) {
    $result = sprintf("%s%s", $result, $bookings[$i]->to_xml(false));
  }
  $result = sprintf("%s</bookings>\n", $result);
  break;

case 'doBook':
  try {
    if(isset($_GET['user_id']) && isset($_GET['environment_id'])) {
      $_POST['user_id'] = $_GET['user_id'];
      $_POST['environment_id'] = $_GET['environment_id'];
    }
    if(!isset($_POST['user_id']) || !isset($_POST['environment_id'])) {
      throw new Exception('Username or environment name not set');
    }
    $bookResult = $home->book($_POST['user_id'], $_POST['environment_id']);
    $result = sprintf("<?xml version=\"1.0\" encoding=\"utf-8\"?>\n");
    $result = sprintf("%s<bookResult>%s</bookResult>\n", $result, $bookResult ? 'Success' : 'Fail');
  } catch(Exception $e) {
    printf("Error: [%d] %s", $e->getCode(), $e->getMessage());
    exit(0);
  }
  break;

case 'doUnbook':
  try {
    if(isset($_GET['environment_id'])) {
      $_POST['environment_id'] = $_GET['environment_id'];
    }
    if(!isset($_POST['environment_id'])) {
      throw new Exception('Environment ID not set');
    }
    $unbookResult = $home->unbook($_POST['environment_id']);
    $result = sprintf("<?xml version=\"1.0\" encoding=\"utf-8\"?>\n");
    $result = sprintf("%s<unbookResult>%s</unbookResult>\n", $result, $unbookResult ? 'Success' : 'Fail');
  } catch(Exception $e) {
    printf("Error: [%d] %s", $e->getCode(), $e->getMessage());
    exit(0);
  }
  break;

case 'getBuildPermittion':
  if(isset($_GET['username']) && isset($_GET['environment_name'])) {
    $_POST['username'] = $_GET['username'];
    $_POST['environment_name'] = $_GET['environment_name'];
  }
  $buildPermittion = $home->get_build_permittion($_POST['username'], $_POST['environment_name']);
  $result = sprintf("<?xml version=\"1.0\" encoding=\"utf-8\"?>\n");
  $result = sprintf("%s<buildPermittion>%s</buildPermittion>\n", $result, $buildPermittion ? 'Yes' : 'No');
  break;

case 'doLogin':
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

case 'doLogout':
  try {
    $user_id = $home->find_username_from_session_id($_COOKIE[$home->get_cookie_name()]);
    $home->logout($user_id);
  } catch(Exception $e) {
    printf("[%d] %s", $e->getCode(), $e->getMessage());
    exit(0);
  }
  header("Location: ./");
  break;

default:
  printf('Unrecognized action');
  break;

}

header("Content-type: application/xml");
printf("%s", $result);
