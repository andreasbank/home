<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *
 *	Copyright(C) 2013 by Andreas Bank, andreas.mikael.bank@gmail.com
 *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

$htmlHeader = "<!DOCTYPE html>\n<html xmlns=\"http://www.w3.org/1999/xhtml\" lang=\"sv\" xml:lang=\"sv\">\n" .
				"<head>\n\t<title>Initialization of Home</title>\n".
				"\t<style type=\"text/css\">\n\ttd {\n\t\ttext-align:left;\n\t}\n\t</style>\n</head>\n<body style=\"text-align: center;\">\n";
$htmlFooter = "</body>\n</html>";
$errorMessage = "\nAn error has occured:<br />\n";

//and here we go
if(isset($_POST['install'])) {
	if(@mysql_connect($_POST['mysqlHost'], $_POST['mysqlUsername'], $_POST['mysqlPassword']) == false)
		echo $htmlHeader.$errorMessage.mysql_error()."\n".$htmlFooter;
	else if(@mysql_query("create database if not exists `".$_POST['mysqlDatabase']."`") == false)
		echo $htmlHeader.$errorMessage.mysql_error()."\n".$htmlFooter;
	else if(@mysql_select_db($_POST['mysqlDatabase']) == false)
		echo $htmlHeader.$errorMessage.mysql_error()."\n".$htmlFooter;
	else if(@mysql_query("update `users` set full_name='Andreas Bank' where username='andreab'") == false) {
		echo $htmlHeader.$errorMessage."(u:0)\n".mysql_error()."\n".$htmlFooter;
	}
	else if(@mysql_query("insert into `users` values
	('0', 'andreab', 'Andreas Bank'),
	('1', 'pamelaa', 'Pamela Andersson')") == false) {
		echo $htmlHeader.$errorMessage."(u:1)\n".mysql_error()."\n".$htmlFooter;
	}
	else if(@mysql_query("insert into `sessions` values
	('0', '0', '2011-10-01 00:01', '2011-10-01 13:01', 1, '127.0.0.1'),
	('1', '0', '2012-09-04 10:23', '".date('Y-m-d G:i:s')."', 0, '127.0.0.1'),
	('2', '1', '2012-09-01 00:02', '2012-01-01 00:02', 0, '127.0.0.1')") == false) {
		echo $htmlHeader.$errorMessage."(s:2)\n".mysql_error()."\n".$htmlFooter;
	}
	else {
		echo $htmlHeader."Success!".$htmlFooter;
	}
}
else {
	echo $htmlHeader;
?>
	<p>
		Welcome to the initialization of Home!<br />
		This process requires read/write privilegies<br />
		to the MySQL server.<br />
		This process will install default data to the Home database.<br />
		<span style="font-style: italic; color: red;">
			Do you like fishsticks?
		</span>
	</p>
	<h1>Step 1 - MySQL information:</h1>
	<form method="post" action="install_data.php">
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
			<td>Database name:</td>
			<td><input type="input" id="p" name="mysqlDatabase" value="devicemanagement" /></td>
		</tr>
		<tr>
			<td colspan="2" style="text-align: center;">
				<input type="submit" name="install" value="Initialize" />
			</td>
		</tr>
	</table>
	</form>
<?php
	echo $htmlFooter;
}
?>
