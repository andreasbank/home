<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *
 *  Copyright(C) 2014 by Andreas Bank, andreas.mikael.bank@gmail.com
 *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

$procedures = array(
  'create_temporary_table',
  'find_free_id_from_temp',
  'find_free_id',
  'find_user_id',
  'find_user',
  'find_user_session_id',
  'find_user_session',
  'find_user_idFromSession',
  'find_username_from_session_id',
  'find_username_from_user_id',
  'find_full_name_from_username',
  'find_full_name_from_id',
  'create_session_id',
  'create_session',
  'update_session',
  'create_user',
  'remove_user',
  'update_user',
  'update_or_create_user',
  'cleanup_sessions',
  'login',
  'logout',
  'user_exists'
);
$htmlHeader = "<!DOCTYPE html>\n<html xmlns=\"http://www.w3.org/1999/xhtml\" lang=\"sv\" xml:lang=\"sv\">\n" .
        "<head>\n\t<title>Uninstall Home</title>\n".
        "\t<style type=\"text/css\">\n\ttd {\n\t\ttext-align:left;\n\t}\n\t</style>\n</head>\n<body style=\"text-align: center;\">\n";
$htmlFooter = "</body>\n</html>";

if(isset($_POST['clearMysql'])) {
  echo $htmlHeader;
  $errorMessage = "\nan error occurred:<br />\n";
  if(@mysql_connect($_POST['mysqlHost'], $_POST['mysqlUsername'], $_POST['mysqlPassword']) == false) {
    echo $errorMessage." (connect) ".mysql_error()."\n".$htmlFooter;
  }
  else {
    if(@mysql_query("use `".$_POST['mysqlDatabase']."`") == false) {
      echo $errorMessage."(use devicemanagement) ".mysql_error()."\n";
    }
    else {
      if(@mysql_query("drop table `".$_POST['mysqlBookingsTable']."`") == false)
        echo $errorMessage."(drop bookings table) ".mysql_error()."<br />>\n";
      if(@mysql_query("drop table `".$_POST['mysqlSessionsTable']."`") == false)
        echo $errorMessage."(drop sessions table) ".mysql_error()."<br />\n";
      if(@mysql_query("drop table `".$_POST['mysqlUsersTable']."`") == false)
        echo $errorMessage."(drop users table) ".mysql_error()."<br />\n";
      for($loop_len=count($procedures); $loop_len>0; $loop_len--) {
        if(@mysql_query("drop procedure `".$procedures[$loop_len-1]."`") == false)
          echo $errorMessage."(drop procedure `".$procedures[$loop_len]."`) ".mysql_error()."<br />\n";
      }
      echo "<span style=\"color:green;\">Success! If no error has been reported above then all Home data has been removed!</span>";
      mysql_close();
    }
  }
  echo $htmlFooter;
}
else {
  echo $htmlHeader;
?>
  <p>
    Welcome to the uninstallation of Home!<br />
    The uninstallation requires read/write privilegies<br />
    to the MySQL server.<br />
    This process will remove the Home database and all its data.<br />
    <span style="font-style: italic; color: red;"><b>WARNING:</b><br />
    This process cannot be undone!</span>
  </p>
  <h1>Step 1 - MySQL information:</h1>
  <form method="post" action="uninstall.php">
  <table style="margin: auto; border: solid 1px black;">
    <tr>
      <td>MySQL address:</td>
      <td><input type="input" id="h" name="mysqlHost" value="localhost" /></td>
    </tr>
    <tr>
      <td>MySQL username:</td>
      <td><input type="input" id="u" name="mysqlUsername" value="root" /></td>
    </tr>
    <tr>
      <td>MySQL password:</td>
      <td><input type="password" id="p" name="mysqlPassword" value="p" /></td>
    </tr>
    <tr>
      <td>Databasens name:</td>
      <td><input type="input" id="p" name="mysqlDatabase" value="devicemanagement" /></td>
    </tr>
    <tr>
      <td>Table 'users':</td>
      <td><input type="input" id="p" name="mysqlUsersTable" value="users" /></td>
    </tr>
    <tr>
      <td>Table 'users':</td>
      <td><input type="input" id="p" name="mysqlSessionsTable" value="sessions" /></td>
    </tr>
    <tr>
      <td>Table 'bookings':</td>
      <td><input type="input" id="p" name="mysqlBookingsTable" value="bookings" /></td>
    </tr>
    <tr>
      <td colspan="2" style="text-align: center;">
        <input type="submit" name="clearMysql" value="Uninstall" />
      </td>
    </tr>
  </table>
  </form>
<?php
  echo $htmlFooter;
}
?>
