<?php
/**
 * VHS Home, Copyright 2014 by Andreas Bank, andreas.bank@axis.com (andreas.mikael.bank@gmail.com)
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

  public function __construct($id, $name, $hosts, $booked_user, $booked_date) {
    $this->id = $id;
    $this->name = $name;
    $this->hosts = $hosts;
    $this->booked_user = $booked_user;
    $this->booked_date = $booked_date;
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

  public function get_booked_user() {
    return $this->booked_user;
  }

  public function get_booked_date() {
    return $this->booked_date;
  }

  public function to_xml($xml_declaration = true) {
    $xml = '';
    if($xml_declaration) {
      $xml = sprintf("<?xml version=\"1.0\" encoding=\"utf-8\"?>\n");
    }
    $xml = sprintf("%s<portal>\n", $xml);
    $xml = sprintf("%s\t<id>%s</id>\n", $xml, $this->id);
    $xml = sprintf("%s\t<name>%s</name>\n", $xml, htmlspecialchars($this->name));
    $xml = sprintf("%s\t<hosts count=\"%s\">\t", $xml, count($this->hosts));
    for($i = 0; $i < count($this->hosts); $i++) {
      $xml = sprintf("%s\t\t<host>%s</host>\n", $xml, $this->hosts[$i]);
    }
    $xml = sprintf("%s\t</hosts>\n", $xml);
    $xml = sprintf("%s\t<bookedUser>", $xml);
    if(null != $this->booked_user) {
      $xml = sprintf("%s\n\t\t<id>%s</id>\n", $xml, $this->booked_user->get_id());
      $xml = sprintf("%s\t\t<username>%s</username>\n", $xml, $this->booked_user->get_username());
      $xml = sprintf("%s\t\t<fullName>%s</fullName>\n\t", $xml, htmlspecialchars($this->booked_user->get_full_name()));
    }
    $xml = sprintf("%s</bookedUser>\n", $xml);
    $xml = sprintf("%s\t<bookedDate>%s</bookedDate>\n", $xml, $this->booked_date);
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

class Home {
  private $fake_ldap = true;
  private $session_time = 604800; // en vecka
  private $cookie_name = 'axis-home';
  private $mysql_host = 'localhost';
  private $mysql_username = 'root';
  private $mysql_password = 'rootpass02';
  private $mysql_database = 'devicemanagement';
  private $mysql_users_table = 'users';
  private $mysql_sessions_table = 'sessions';
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

  public function get_portals_array_with_bookings() {
    $result_set = $this->query('call get_portals_with_bookings();', true);
    $result_set_len = count($result_set);
    $result_array = array();
    if($result_set_len > 0) {
      for($i = 0; $i < $result_set_len; $i++) {
        $hosts = explode(',', $result_set[$i]['ip']);
        $user = null;
        if(null != $result_set[$i]['user_id']) {
          $user = $this->find_user($result_set[$i]['user_id']);
        }
        $result_array[] = new Portal($result_set[$i]['id'],
                                     $result_set[$i]['name'],
                                     $hosts,
                                     $user,
                                     $result_set[$i]['book_date']);
      }
    }
    return $result_array;
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
//DISABLED SECTION
elseif(false || !isset($_COOKIE['axis-home']) || empty($_COOKIE['axis-home'])) {
  printf($html_header);
?>
<form method="post" action="">
<input name="action" type="hidden" value="do_login" />
<table>
  <tr>
    <td>user:</td>
    <td><input name="username" "type="text" value="" /></td>
    <td rowspan="2"><input style="height:100%;" type="submit" value="Login!" /></td>
  </tr>
  <tr>
    <td>pass:</td>
    <td><input name="password" type="password" value="" /></td>
  </tr>
</table>
</form>
<?
  printf($html_footer);
  exit;
}
//DISABLED SECTION
elseif(false) {
  printf("%sLogged in as %s\n", $html_header, $home->find_user_full_name($home->get_username()));
  printf("<script type=\"text/javascript\">\n\t<!--\n\tusername = \"%s\"\n\t-->\n</script>\n", $home->get_username());
?>
<form method="post" action="">
  <input name="action" type="hidden" value="do_logout" />
  <input type="submit" value="Logout!">
</form>
<?
  //get all portals
  $portals = $home->get_portals_array_with_bookings();
  printf("<table class=\"main_table\" style=\"width: 100%%, height: 100%%\">\n");
  //list in a nice table-like view:
  //    -green are bookable
  //    -blue are the booked ones by self
  //    -yellow are booked by others
  //    -red are overbooked
  //    -gray are broken or disabled
  $table_print = "\t</tr>\n\t<tr>\n";
  $table_pointer = "";
  printf("\t<tr>\n");
  for($i = 0; $i < count($portals); $i++) {
    if($i % 5 == 0) {
      printf("%s", $table_pointer);
      $table_pointer = &$table_print;
    }
    $book_user_id = $portals[$i]->get_booked_user();
    $is_booked = $book_user_id != null ? true : false;
    $book_username = $is_booked ? $home->find_username_from_user_id($book_user_id) : null;
    $is_own = $book_username == $home->get_username();
    $book_full_user_name = "";
    if($is_booked) {
      $book_full_user_name = $home->find_user_full_name($book_username);
    }
    // TODO: fix overdue (red color)
    //$is_overdue = 
    printf("\t\t<td class=\"main_table_td\">\n\t\t\t<table border=\"0\" class=\"env_box %s\">\n", ($is_booked ? ($is_own ? 'blue' : 'orange') : 'green'));
    printf("\t\t\t\t<tr>\n\t\t\t\t\t<td colspan=\"2\" class=\"env_name\">\n\t\t\t\t\t\t%s\n\t\t\t\t\t</td>\n\t\t\t\t</tr>\n", $portals[$i]->get_name());
    printf("\t\t\t\t<tr>\n\t\t\t\t\t<td>\n\t\t\t\t\t\t%s\n\t\t\t\t\t</td>\n", $portals[$i]->get_host(0));
    printf("\t\t\t\t\t<td rowspan=\"3\" class=\"centered\">\n\t\t\t\t\t\t<input type=\"button\" value=\"Book!\" />\n\t\t\t\t\t</td>\n\t\t\t\t</tr>\n", $portals[$i]->get_booked_user());
    printf("\t\t\t\t<tr>\n\t\t\t\t\t<td>\n\t\t\t\t\t\t%s\n\t\t\t\t\t</td>\n\t\t\t\t</tr>\n", ($portals[$i]->get_host(1)?$portals[$i]->get_host(1):'&nbsp;'));
    printf("\t\t\t\t<tr>\n\t\t\t\t\t<td>\n\t\t\t\t\t\t%s\n\t\t\t\t\t</td>\n\t\t\t\t</tr>\n", ($is_booked ? $book_full_user_name : '&nbsp;'));
    printf("\t\t\t</table>\n\t\t</td>\n");
  }
  printf("\t</tr>\n</table>\n");
  printf($html_footer);
}

