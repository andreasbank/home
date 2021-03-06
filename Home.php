<?php
/**
 * AVHS Home, Copyright 2014 by Andreas Bank, andreas.bank@axis.com (andreas.mikael.bank@gmail.com)
 * Licensed under GPLv3.
 */

date_default_timezone_set('Europe/Stockholm');

require('Config.php');

class Portal {
  private $id = null;
  private $name = null;
  private $hosts = null;
  private $booked_user = null;
  private $booked_date = null;

  public function __construct($id, $name, $hosts) {
    $this->id = $id;
    $this->name = $name;
    $this->hosts = $hosts;
  }

  public function get_id() {
    return $this->id;
  }

  public function get_name() {
    return $this->name;
  }

  public function add_host($host) {
    $hosts[] = $host;
  }

  public function get_host($index) {
    if(array_key_exists($index, $this->hosts))
      return $this->hosts[$index];
  }

  public function set_booking($booking) {
    $this->booking = $booking;
  }

  public function to_xml($xml_declaration = true) {
    $xml = '';
    if($xml_declaration) {
      $xml = sprintf("<?xml version=\"1.0\" encoding=\"utf-8\"?>\n");
    }
    $xml = sprintf("%s<portal>\n", $xml);
    $xml = sprintf("%s\t<id>%s</id>\n", $xml, $this->id);
    $xml = sprintf("%s\t<name>%s</name>\n", $xml, htmlspecialchars($this->name));
    $xml = sprintf("%s\t<hosts count=\"%s\">\n", $xml, count($this->hosts));
    for($i = 0; $i < count($this->hosts); $i++) {
      $xml = sprintf("%s\t\t<host>%s</host>\n", $xml, $this->hosts[$i]);
    }
    $xml = sprintf("%s\t</hosts>\n", $xml);
    $xml = sprintf("%s</portal>\n", $xml);
    return $xml;
  }

}

class User {
  private $id = null;
  private $username = null;
  private $full_name = null;

  public function __construct($id, $username, $full_name) {
    $this->id = $id;
    $this->username = $username;
    $this->full_name = $full_name;
  }

  public function get_id() {
    return $this->id;
  }

  public function get_username() {
    return $this->username;
  }

  public function get_full_name() {
    return $this->full_name;
  }

  public function to_xml($xml_declaration = true) {
    $xml = '';
    if($xml_declaration) {
      $xml = sprintf("<?xml version=\"1.0\" encoding=\"utf-8\"?>\n");
    }
    $xml = sprintf("%s<user>\n", $xml);
    $xml = sprintf("%s\t<id>%s</id>\n", $xml, $this->id);
    $xml = sprintf("%s\t<username>%s</username>\n", $xml, $this->username);
    $xml = sprintf("%s\t<fullName>%s</fullName>\n", $xml, htmlspecialchars($this->full_name));
    $xml = sprintf("%s</user>\n", $xml);
    return $xml;
  }

}

class Booking {
  private $user_id = null;
  private $portal_id = null;
  private $book_date = null;

  public function __construct($user_id, $portal_id, $book_date) {
    $this->user_id = $user_id;
    $this->portal_id = $portal_id;
    $this->book_date = $book_date;
  }

  public function get_user_id() {
    return $this->user_id;
  }

  public function get_portal_id() {
    return $this->portal_id;
  }

  public function get_book_date() {
    return $this->book_date;
  }

  public function to_xml($xml_declaration = true) {
    $xml = '';
    if($xml_declaration) {
      $xml = sprintf("<?xml version=\"1.0\" encoding=\"utf-8\"?>\n");
    }
    $xml = sprintf("%s<booking>\n", $xml);
    $xml = sprintf("%s\t<userId>%s</userId>\n", $xml, $this->user_id);
    $xml = sprintf("%s\t<portalId>%s</portalId>\n", $xml, $this->portal_id);
    $xml = sprintf("%s\t<bookDate>%s</bookDate>\n", $xml, $this->book_date);
    $xml = sprintf("%s</booking>\n", $xml);
    return $xml;
  }

}


class Home {
  private $fake_ldap = false;
  private $session_time = 604800; // one week
  private $cookie_name = COOKIE_NAME;
  private $mysql_host = MYSQL_HOST;
  private $mysql_port = MYSQL_PORT;
  private $mysql_username = MYSQL_USERNAME;
  private $mysql_password = MYSQL_PASSWORD;
  private $mysql_database = DATABASE_NAME;
  private $mysql_users_table = USERS_TABLE;
  private $mysql_sessions_table = SESSIONS_TABLE;
  private $mysql_portals_table = PORTALS_TABLE;
  private $mysql_bookings_table = BOOKINGS_TABLE;
  private $h_mysql = null; //mysql handler

  private $username = null;
  private $full_name = null;

  public function __construct() {
    $this->init_mysql();
  }

  public function __destruct() {
    $this->close_mysql();
  }

  public function init_mysql() {
    if(null == $this->h_mysql) {
      $this->h_mysql = new mysqli($this->mysql_host, $this->mysql_username, $this->mysql_password, $this->mysql_database);
      if($this->h_mysql->connect_errno) {
        printf("Error connecting to the database (%s):\n\n%s", $this->h_mysql->connect_errno, $this->h_mysql->connect_error);
        exit(1);
      }
    }
  }

  public function close_mysql() {
    $this->h_mysql->close();
    $this->h_mysql = null;
  }

  public function reconnect_mysql() {
    $this->close_mysql();
    $this->init_mysql();
  }

  public function get_cookie_name() {
    return $this->cookie_name;
  }

  public function get_username() {
    if(isset($_COOKIE[$this->cookie_name]) && !empty($_COOKIE[$this->cookie_name])) {
      $this->username = $this->find_username_from_session_id($_COOKIE[$this->cookie_name]);
    }
    return $this->username;
  }

  public function get_full_username() {
    if(!isset($this->full_name) || empty($this->full_name)) {
      if(isset($this->username) && !empty($this->username)) {
        $this->get_username();
        $this->full_name = $this->find_user_full_name($this->username);
      }
      else {
        throw new Exception('Could not retrieve users full name');
      }
    }
    return $this->full_name;
  }

  /**
   * Used for SQL querys that read the database (SELECT)
   *
   * @return A single tuple-like result (array: result["column name"]), not a result set
   */
  public function query($query, $reconnect = false) {
    if($reconnect && $this->h_mysql)
      $this->reconnect_mysql();
    $unfetched = $this->h_mysql->query($query);
    if($this->h_mysql->error) {
      throw new Exception($this->h_mysql->error);
    }
    if(false == $unfetched) {
      throw new Exception("Home::query(): [\$query=\"".$query."\"][\$reconnect=".($reconnect?"true":"false")."] ".mysql_error(), 1);
      return false;
    }
    if(true === $unfetched){
      return true;
    }
    $results = array();
    $fetched = null;
    while($fetched = $unfetched->fetch_array()) {
      $results[] = $fetched;
    }
    $unfetched->free();
    return $results;
  }

  /**
   *  Used for SQL querys that execute procedures (call)
   *
   *  @return A single result (string), not a result set.
   */
  public function query_procedure($query, $reconnect = true) {
    try {
      $this->query($query, $reconnect);
      $result = $this->query("select @tmp_var");
    } catch(Exception $e) {
      throw new Exception(sprintf("Home::query_procedure(): [\$query=\"%s\"][\$reconnect=%s] %s", $query, $reconnect ? "true" : "false", $e->getMessage()), $e->getCode());
    }
    return $result[0][0];
  }

  /**
   * Creates a new user in the system/database and returns the new ID.
   */
  public function create_user($username, $full_name) {
    $result = null;
    try {
      $result = $this->query_procedure("call create_user('".$username."', '".$full_name."', @tmp_var)");
      if(isset($result[0]) || $result[0] == null) {
        throw new Exception("User already exists!", 1);
      }
    } catch(Exception $e) {
      throw $e;
    }
    return $result[0];
  }

  /**
   * Retrieves the user ID from the cookie or a given username.
   */
  public function find_user_id($username = null) {
    try {
      if($username == null) {
        if(!isset($_COOKIE[$this->cookie_name]))
          throw new Exception("Cannot retrieve user ID, no username given and/or no session exists", 1);
        $user_id = $this->query_procedure("call find_user_id_from_session('".$_COOKIE[$this->cookie_name]."', @tmp_var)", true);
        if($user_id == null || $user_id == "") {
          throw new Exception("Cannot retrieve user ID, no user for existing session exists (expired session?)", 1);
        }
        return $user_id;
      }
      $user_id = $this->query_procedure("call find_user_id('".$username."', @tmp_var)");
      if($user_id == null || $user_id == "") {
        throw new Exception("Cannot retrieve user ID, no user with username '".$username."' exists", 1);
      }
      return $user_id;
    } catch(Exception $e) {
      throw $e;
    }
  }

  /**
   * Retrieves the users session ID from either the cookie or a given user ID.
   */
  public function find_session_id($user_id = null) {
    try {
      if($user_id == null) {
        if(!isset($_COOKIE[$this->cookie_name]))
          throw new Exception("Cannot retrieve session ID, no user ID given or no session exists", 1);
        return $_COOKIE[$this->cookie_name];
      }
      return $this->query_procedure(sprintf("call find_user_session_id('%s', @tmp_var)", $user_id));
    } catch(Exception $e) {
      throw $e;
    }
  }

  /**
   * Returns a string containing the username of the current user.
   * Uses either a given session ID or the cookie.
   */
  public function find_username_from_session_id($session_id = null) {
    try {
      if(!$session_id) {
        if(!$_COOKIE[$this->cookie_name])
          throw new Exception("Cannot retrieve username, no session ID given and/or no session exists", 1);
        $session_id = $_COOKIE[$this->cookie_name];
      }
      $result =  $this->query_procedure(sprintf("call find_username_from_session_id('%s', @tmp_var)", $session_id));
      return $result;
    } catch(Exception $e) {
      throw $e;
    }
  }

  /**
   * Returns a string containing the username of the user id.
   */
  public function find_username_from_user_id($user_id = null) {
    try {
      $result =  $this->query_procedure(sprintf("call find_username_from_user_id('%s', @tmp_var)", $user_id));
      return $result;
    } catch(Exception $e) {
      throw $e;
    }
  }

  /**
   * Returns a User object
   */
  public function find_user($user_id = null) {
    try {
      if(null == $user_id) {
        $user_id = $this->find_user_id();
      }
      $result = $this->query(sprintf("call find_user('%s')", $user_id), true);
      $result = new User($result[0]['id'],
                         $result[0]['username'],
                         $result[0]['full_name']);
      return $result;
    } catch(Exception $e) {
      throw $e;
    }
  }

  /**
   * Returns aa array of User objects
   */
  public function get_users() {
    try {
      $result_set = $this->query(sprintf("call find_users()"), true);
      $result_set_len = count($result_set);
      $result_array = array();
      for($i = 0; $i < $result_set_len; $i++) {
        $result_array[] = new User($result_set[$i]['id'],
                                   $result_set[$i]['username'],
                                   $result_set[$i]['full_name']);
      }
      return $result_array;
    } catch(Exception $e) {
      throw $e;
    }
  }

  /**
   * Creates a new session in the system/database and returns the new ID.
   */
  public function login($username, $password) {
    $result = null;
    $ip = $_SERVER['REMOTE_ADDR'];
    try {
      $this->query("call cleanup_sessions()");
      $this->ldap_login($username, $password);
      $session_id = $this->query_procedure("call login('".$username."', '".$ip."', @tmp_var)");
      if(isset($session_id) && $session_id == -1) {
        //User is already logged in
        $session_id = $this->find_session_id($this->find_user_id($username));
      }
      if(isset($session_id) && $session_id == -2) {
        //User does not exist and full name doesn't exist
        $this->create_user($username, "Unknown");
        $session_id = $this->query_procedure("call login('".$username."', '".$ip."', @tmp_var)");
        if($session_id < 0)
          throw new Exception("User could not log in [SQLErrno: ".$session_id."]", 1);
      }
    } catch(Exception $e) {
      throw $e;
    }
    if(!isset($_COOKIE[$this->cookie_name])) {
      $this->set_cookie($session_id);
    }
    return $session_id;
  }

    /**
     * ldapLogin()
     * override of findUserfull_name()
     */
  private function ldap_login($username, $password) {
      try {
        return $this->find_user_full_name($username, $password);
      } catch(Exception $e) {
        throw $e;
      }
    }

    /**
     * findUserfull_name()
     * Retrieves the full name associated with the given username, if it exists.
     * Providing a password will make this function login the given username instead
     * and return the full name.
     */
  public function find_user_full_name($username, $password = null) {
      $full_name = null;
       // If the logged in user requests their own full
       // name then (if possible) skipp the SQL request
      if(null != $this->username &&
         $this->username == $username &&
         null != $this->full_name &&
         null == $password) {
        return $this->full_name;
      }
      $result = $this->query_procedure(sprintf("call find_full_name_from_username('%s', @tmp_var)", $username));
      if($result != null && $result != "") {
        $full_name = $result;
      }
      else if(!$password && ($full_name == null || $full_name == '')) {
        $full_name = 'Unknown';
      }
      if(!$password) {
        $this->full_name = $full_name;
        return $this->full_name;
      }
      $ldapconn = @ldap_connect('ldap://dsse01.se.axis.com:3268'); //no SSL
      //$ldapconn = ldap_connect('ldaps://dsse01.se.axis.com:3269'); // SSL
      //ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
      //ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
      if($this->fake_ldap) {
        $this->username = $username;
        $this->query(sprintf("call update_or_create_user('%s', '%s', @tmp_var)", $username, $full_name));
        return true;
      }
      elseif($ldapconn) {
        $ldapbind = @ldap_bind($ldapconn, sprintf("AXISNET\\%s", $username), $password);
        if ($ldapbind) {
          $this->username = $username;
          if(!$full_name || $full_name == 'Unknown') {
            $filter = sprintf("(sAMAccountName=%s)", $username);
            $fields = array('name');
            $search = ldap_search($ldapconn, 'DC=axis,DC=com', $filter, $fields);
            $result = ldap_get_entries($ldapconn, $search);
            if($result[0]['name'][0] != null && $result[0]['name'][0] != "") {
              $full_name = $result[0]['name'][0];
            }
            else {
              $full_name = 'Unknown';
            }
            ldap_unbind($ldapconn);
            $this->query(sprintf("call update_or_create_user('%s', '%s', @tmp_var)", $username, $full_name));
          }
          return true;
        }
        else {
          $errno = ldap_errno($ldapconn);
          $errstr = ldap_err2str($errno);
          throw new Exception(sprintf("LDAP-bind error: %s", $errstr), $errno);
        }
      }
      else {
        $errno = ldap_errno($ldapconn);
        $errstr = ldap_err2str($errno);
        throw new Exception(sprintf("LDAP-connect error: %s", $errstr), $errno);
      }
    }

  /**
   * Removes the users session and cookie.
   */
  public function logout ($id) {
    setcookie($this->cookie_name, '', time() - 3600, '/');
    try {
      $this->query(sprintf("call logout('%s');", $id));
    } catch(Exception $e) {
      throw $e;
    }
  }

  /**
   * Sets a cookie.
   */
  private function set_cookie($session_id) {
    if(!setcookie($this->cookie_name, $session_id, time() + $this->session_time, '/')) {
      throw new Exception('Setting of cookie failed. To be able to log in you must enable cookies for this site', 1);
    }
  }

  /**
   * Sets a cookie.
   */
  private function remove_cookie() {
    if(!setcookie($this->cookie_name, 'null', time() - $this->session_time, "/")) {
      throw new Exception('Removing the cookie failed. This process requires enabling cookies for this site', 1);
    }
  }

  public function get_portals_array() {
    $result_set = $this->query('call get_portals();');
    $result_set_len = count($result_set);
    $result_array = array();
    if($result_set_len > 0) {
      for($i = 0; $i < $result_set_len; $i++) {
        $hosts = explode(',', $result_set[$i]['ip']);
        $result_array[] = new Portal($result_set[$i]['id'], $result_set[$i]['name'], $hosts, null, null);
      }
    }
    return $result_array;
  }

  public function find_portal_by_name($portals, $name) {
    for($i=0; $i<count($portals); $i++) {
      if($portals[$i]->get_name() == $name) {
        return $portals[$i];
      }
    }
    return null;
  }

  public function get_bookings() {
    $result_set = $this->query('call get_bookings();', true);
    $result_set_len = count($result_set);
    $result_array = array();
    if($result_set_len > 0) {
      for($i = 0; $i < $result_set_len; $i++) {
        $result_array[] = new Booking($result_set[$i]['user_id'],
                                     $result_set[$i]['portal_id'],
                                     $result_set[$i]['book_date']);
      }
    }
    return $result_array;
  }

  public function get_portals_array_with_bookings() {
    $result_set = $this->query('call get_portals_with_bookings();', true);
    $result_set_len = count($result_set);
    $result_array = array();
    if($result_set_len > 0) {
      for($i = 0; $i < $result_set_len; $i++) {
        $hosts = explode(',', $result_set[$i]['ip']);
        $result_array[] = new Portal($result_set[$i]['id'],
                                     $result_set[$i]['name'],
                                     $hosts);
      }
    }
    return $result_array;
  }

  /**
   * Returns a Portal object
   */
  public function get_environment_by_name($environment_name) {
    try {
      if(null == $environment_name) {
        throw new Exception('Missing environment name argument');
      }
      $result = $this->query(sprintf("call find_environment_by_name('%s')", $environment_name), true);
      $environment = null;
      if(count($result) > 0) {
        $hosts = explode(',', $result[0]['ip']);
        $environment = new Portal($result[0]['id'],
                             $result[0]['name'],
                             $hosts);
      }
      return $environment;
    } catch(Exception $e) {
      throw $e;
    }
  }

  /**
   * Returns a Booking object
   */
  public function get_booking_by_environment_name($environment_name) {
    try {
      if(null == $environment_name) {
        throw new Exception('Missing environment name argument');
      }
      $result = $this->query(sprintf("call find_booking_by_environment_name('%s')", $environment_name), true);
      $booking = null;
      if(count($result) > 0) {
        $booking = new Booking($result[0]['user_id'],
                               $result[0]['portal_id'],
                               $result[0]['book_date']);
      }
      return $booking;
    } catch(Exception $e) {
      throw $e;
    }
  }

  /**
   * Check if a given user has booked a given environment
   */
  public function get_build_permission($username, $environment_name) {
    if(empty($username) || empty($environment_name)) {
      throw new Exception('Missing username or/and environment name argument');
    }
    $environment = $this->get_environment_by_name($environment_name);
    if(!empty($environment)) {
      $booking = $this->get_booking_by_environment_name($environment_name);
      if(!empty($booking)) {
        $user_id = $this->find_user_id($username);
        if($booking->get_user_id() == $user_id) {
          return true;
        }
      }
    }
    return false;
  }

  /**
   * Book an environment
   */
  public function book($user_id, $environment_id) {
    try {
      if((empty($user_id) && $user_id != 0) || (empty($environment_id) && $environment_id != 0)) {
        throw new Exception('Missing user ID and/or environment ID argument');
      }
      $result = $this->query(sprintf("call book('%s', '%s')", $user_id, $environment_id), true);
    } catch(Exception $e) {
      // TODO: see if it is errno 1062 and check int instead
      if(false === strstr($e->getMessage(), 'Duplicate')) {
        return false;
      }
      throw $e;
    }
    return true;
  }

  /**
   * Unbook/return an environment
   */
  public function unbook($environment_id) {
    try {
      if(empty($environment_id) && $environment_id != 0) {
        throw new Exception('No environment ID given');
      }
      $result = $this->query(sprintf("call unbook('%s')", $environment_id), true);
    } catch(Exception $e) {
      // TODO: see if it is errno 1062 and check int instead
      if(false === strstr($e->getMessage(), 'Duplicate')) {
        return false;
      }
      throw $e;
    }
    return true;
  }

}

$home = new Home();
$html_header = "<!DOCTYPE html>\n<html>\n<head>\n\t<meta charset=\"UTF-8\">\n\t<title>Home (mockup)</title>\n";
$html_header = sprintf("%s\t<link rel=\"stylesheet\" href=\"style.css\" type=\"text/css\" />\n", $html_header);
$html_header = sprintf("%s</head>\n<body>\n", $html_header);
$html_footer = "</body>\n</html>\n";
if(isset($_POST['action']) && $_POST['action'] == 'do_login') {
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
}
elseif(isset($_POST['action']) && $_POST['action'] == 'do_logout') {
  try {
    $user_id = $home->find_username_from_session_id($_COOKIE[$home->get_cookie_name()]);
    $home->logout($user_id);
  } catch(Exception $e) {
    printf("[%d] %s", $e->getCode(), $e->getMessage());
    exit(0);
  }
  header("Location: ./");
}

