<?php
/**
 * Copyright (C) 2014 by Andreas Bank <andreas.mikael.bank@gmail.com>
 *
 * install.php
 * The system database-installer script.
 * Creates the database, its tables and the needed stored procedures.
 */
$htmlHeader = "<!DOCTYPE html>\n<html xmlns=\"http://www.w3.org/1999/xhtml\" lang=\"sv\" xml:lang=\"sv\">\n" .
				"<head>\n\t<title>Home installation</title>\n".
				"\t<style type=\"text/css\">\n\ttd {\n\t\ttext-align:left;\n\t}\n\t</style>\n</head>\n<body style=\"text-align: center;\">\n";
$htmlFooter = "</body>\n</html>";
$sessionTime = 4*60*60; //(4h*60m*60s=)4h to stay logged in
$sessionLogTime = 365*12*60*60; // 365 days to keep session information in log

function getIP () {
	if (getenv("HTTP_CLIENT_IP")) {
		$ip = getenv("HTTP_CLIENT_IP");
	}
	elseif (getenv("HTTP_X_FORWARDED_FOR")) {
		$ip = getenv("HTTP_X_FORWARDED_FOR");
	}
	elseif (getenv("REMOTE_ADDR")) {
		$ip = getenv("REMOTE_ADDR");
	}
	else {
		$ip = "0.0.0.0";
	}
	return $ip;
}

if(isset($_GET['testMysql'])) {
	if(@mysql_connect($_GET['h'], $_GET['u'], $_GET['p']) == false) {
		//header("Content-type: text/html; charset=utf-8", true);
		echo "<span style=\"color:red;\">Failed! Wrong credentials!</span>";
	}
	else {
		//header("Content-type: text/html; charset=utf-8", true);
		echo "<span style=\"color:green;\">Success! You can now continue to the next step!</span>";
		mysql_close();
	}
}
else if(isset($_POST['mysql'])) {
	$errorMessage = "\nAn error has occurred:<br />\n";
	// TODO: make use port
	if(@mysql_connect($_POST['mysql_host'], $_POST['mysql_username'], $_POST['mysql_password']) == false)
		echo $htmlHeader.$errorMessage."(connect)".mysql_error()."\n".$htmlFooter;
	else if(@mysql_query("create database if not exists `".$_POST['database_name']."`") == false)
		echo $htmlHeader.$errorMessage."(create db)".mysql_error()."\n".$htmlFooter;
	else if(@mysql_select_db($_POST['database_name']) == false)
		echo $htmlHeader.$errorMessage."(select)".mysql_error()."\n".$htmlFooter;
	// main tables
	else if(mysql_query("create table if not exists `".$_POST['users_table']."` (" .
							"`id` integer, ".
							"`username` varchar(40), " .
							"`full_name` varchar(255), ".
							"primary key(id)) engine=innodb") == false) {
		echo $htmlHeader.$errorMessage."(user)".mysql_error()."\n".$htmlFooter;
	}
	else if(mysql_query("create table if not exists `".$_POST['sessions_table']."` (" .
							"`id` varchar(255) primary key, " .
							"`user` integer not null, " .
							"`login_time` datetime not null, " .
							"`last_activity` datetime not null, ".
							"`logged_out` tinyint, ".
							"`ip` varchar(15), ".
							"foreign key(user) references ".$_POST['users_table']."(id) on delete cascade on update cascade) ".
							"engine=innodb") == false) {
		echo $htmlHeader.$errorMessage."(sessions_table)".mysql_error()."\n".$htmlFooter;
	}
	else if(mysql_query("create table if not exists `".$_POST['bookings_table']."` (" .
							"`user_id` integer not null, " .
							"`portal_id` integer(11) not null, " .
							"`book_date` datetime not null, ".
							"foreign key(user_id) references ".$_POST['users_table']."(id) on delete cascade on update cascade, ".
							"foreign key(portal_id) references ".$_POST['portals_table']."(id) on delete cascade on update cascade, ".
							"primary key(user_id, portal_id)) engine=innodb") == false) {
		echo $htmlHeader.$errorMessage."(bookings_table)".mysql_error()."\n".$htmlFooter;
	}

	// stored procedures

	// createTemporaryTable()
	else if(mysql_query(	"create procedure `create_temporary_table`(in `table_name` varchar(255))\n".
				"begin\n".
				"	declare stmt text;\n".
				"	set @stmt = concat('drop temporary table if exists temp_table_home');\n".
				"	prepare dropstmt from @stmt;\n".
				"	execute dropstmt;\n".
				"	deallocate prepare dropstmt;\n".
				"	set @stmt = concat('create temporary table temp_table_home select id from ',table_name);\n".
				"	prepare createstmt from @stmt;\n".
				"	execute createstmt;\n".
				"	deallocate prepare createstmt;\n".
				"end;"
				) == false) {
		echo $htmlHeader.$errorMessage."(createTemporaryTable())".mysql_error()."\n".$htmlFooter;
	}
	// find_free_id_from_temp()
	else if(@mysql_query(	"create procedure `find_free_id_from_temp`(in `table_name` varchar(255), inout `free_id` int)\n".
				"begin\n".
				"	declare done int default 0;\n".
				"	declare temp_id int default 0;\n".
				"	declare i int default 0;\n".
				"	declare id_cursor cursor for\n".
				"		select id from temp_table_home order by id asc;\n".
				"	declare continue handler for not found set done=1;\n".
				"	set free_id=0;\n".
				"	open id_cursor;\n".
				"	repeat\n".
				"		fetch id_cursor into temp_id;\n".
				"		if not done and i<temp_id then\n".
				"			set free_id=i;\n".
				"			set done=1;\n".
				"		end if;\n".
				"		set i=i+1;\n".
				"	until done end repeat;\n".
				"	close id_cursor;\n".
				"	if free_id=0 then\n".
				"		set i=i-1;\n".
				"		set free_id=i;\n".
				"	end if;\n".
				"end"
				) == false) {
		echo $htmlHeader.$errorMessage."(find_free_id_from_temp())".mysql_error()."\n".$htmlFooter;
	}
	// find_free_id()
	else if(@mysql_query(	"create procedure `find_free_id`(in `table_name` varchar(255), inout `free_id` int)\n".
				"begin\n".
				"	call createTemporaryTable(table_name);\n".
				"	call find_free_id_from_temp(table_name, free_id);\n".
				"end"
				) == false) {
		echo $htmlHeader.$errorMessage."(find_free_id())".mysql_error()."\n".$htmlFooter;
	}
	// find_user_id()
	else if(@mysql_query(	"create procedure `find_user_id`(in `var_username` varchar(255), out `var_user_id` int)\n".
				"begin\n".
				"	select id into var_user_id from ".$_POST['users_table']." where username = var_username;\n".
				"end"
				) == false) {
		echo $htmlHeader.$errorMessage."(find_user_id())".mysql_error()."\n".$htmlFooter;
	}
	// find_user()
	else if(@mysql_query(	"create procedure `find_user`(in `var_user_id` int)\n".
				"begin\n".
				"	select * from ".$_POST['users_table']." where id=var_user_id;\n".
				"end"
				) == false) {
		echo $htmlHeader.$errorMessage."(find_user())".mysql_error()."\n".$htmlFooter;
	}
	// find_user_session_id()
	else if(@mysql_query(	"create procedure `find_user_session_id`(	in `var_user_id` varchar(255),\n".
				"					inout `var_session_id` varchar(255))\n".
				"begin\n".
				"	declare var_count_id int default 0;\n".
				"	select count(*) as countId, `id` into var_count_id, var_session_id\n".
				"		from `".$_POST['sessions_table']."`\n".
				"		where `user`=var_user_id\n".
				"		and `logged_out`=0;\n".
				"	if var_count_id=0 then\n".
				"		set var_session_id=-1;\n".
				"	elseif var_count_id>1 then\n".
				"		set var_count_id=var_count_id-1;\n".
				"		set @tmp_stmt = concat('update `".$_POST['sessions_table']."` set\n".
				"			`logged_out`=1,\n".
				"			`last_activity`=NOW()\n".
				"			where `user`=',var_user_id,'\n".
				"			and `logged_out`=0\n".
				"			order by `last_activity` asc\n".
				"			limit ',var_count_id,';');\n".
				"		prepare stmtl from @tmp_stmt;\n".
				"		execute stmtl;\n".
				"		deallocate prepare stmtl;\n".
				"		set max_sp_recursion_depth = 1;\n".
				"		call find_user_session_id(var_user_id, var_session_id);\n".
				"		set max_sp_recursion_depth = 0;\n".
				"	end if;\n".
				"end"
				) == false) {
		echo $htmlHeader.$errorMessage."(find_user_session_id())".mysql_error()."\n".$htmlFooter;
	}
	// find_user_session()
	else if(@mysql_query(	"create procedure `find_user_session`(in `var_user_id` varchar(255))\n".
				"begin\n".
				"	select * from `".$_POST['sessions_table']."`\n".
				"		where `user`=var_user_id\n".
				"		and `logged_out`=0;\n".
				"end"
				) == false) {
		echo $htmlHeader.$errorMessage."(find_user_session())".mysql_error()."\n".$htmlFooter;
	}
	// find_user_id_from_session()
	else if(@mysql_query(	"create procedure `find_user_id_from_session`(in `var_session_id` varchar(255), out `var_user_id` int)\n".
				"begin\n".
				"	set var_user_id=null;\n".
				"	select `user` into var_user_id\n".
				"		from `".$_POST['sessions_table']."`\n".
				"		where id=var_session_id;\n".
				"end"
				) == false) {
		echo $htmlHeader.$errorMessage."(find_user_id_from_session())".mysql_error()."\n".$htmlFooter;
	}
	// find_username_from_session_id()
	else if(@mysql_query(	"create procedure `find_username_from_session_id`(in `var_session_id` varchar(255), out `var_username` varchar(255))\n".
				"begin\n".
				"	set var_username=null;\n".
				"	select u.`username` into var_username\n".
				"		from ".$_POST['users_table']." u, ".$_POST['sessions_table']." s\n".
				"		where s.`id`=var_session_id\n".
				"		and s.`user`=u.`id`;\n".
				"end"
				) == false) {
		echo $htmlHeader.$errorMessage."(find_username_from_session_id())".mysql_error()."\n".$htmlFooter;
	}
	// find_username_from_user_id()
	else if(@mysql_query(	"create procedure `find_username_from_user_id`(in `var_user_id` int, out `var_username` varchar(255))\n".
				"begin\n".
				"	set var_username=null;\n".
				"	select `username` into var_username from ".$_POST['users_table']." where `id`=var_user_id;\n".
				"end"
				) == false) {
		echo $htmlHeader.$errorMessage."(find_username_from_user_id())".mysql_error()."\n".$htmlFooter;
	}
	// find_full_name_from_username()
	else if(@mysql_query(	"create procedure `find_full_name_from_username`(in `var_username` varchar(255), out `var_full_name` varchar(255))\n".
				"begin\n".
				"	set var_full_name=null;\n".
				"	select `full_name` into var_full_name from ".$_POST['users_table']." where `username`=var_username;\n".
				"end"
				) == false) {
		echo $htmlHeader.$errorMessage."(find_full_name_from_username())".mysql_error()."\n".$htmlFooter;
	}
	// find_full_name_from_id()
	else if(@mysql_query(	"create procedure `find_full_name_from_id`(in `var_user_id` int, out `var_full_name` varchar(255))\n".
				"begin\n".
				"	set var_full_name=null;\n".
				"	select `full_name` into var_full_name from ".$_POST['users_table']." where `id`=var_user_id;\n".
				"end"
				) == false) {
		echo $htmlHeader.$errorMessage."(find_full_name_from_id())".mysql_error()."\n".$htmlFooter;
	}
	// create_session_id()
	else if(@mysql_query(	"create procedure `create_session_id`(inout `var_session_id` varchar(255))\n".
				"begin\n".
				"	select md5(concat(rand(),now())) into var_session_id;\n".
				"end"
				) == false) {
		echo $htmlHeader.$errorMessage."(create_session_id())".mysql_error()."\n".$htmlFooter;
	}
	// create_session()
	else if(@mysql_query(	"create procedure `create_session`(in `var_user_id` int, in `var_ip_addr` varchar(255), out `var_session_id` varchar(255))\n".
				"begin\n".
				"	call create_session_id(var_session_id);\n".
				"	insert into ".$_POST['sessions_table']." values(\n".
				"		var_session_id,\n".
				"		var_user_id,\n".
				"		NOW(),\n".
				"		NOW(),\n".
				"		'0',\n".
				"		var_ip_addr);\n".
				"end"
				) == false) {
		echo $htmlHeader.$errorMessage."(create_session())".mysql_error()."\n".$htmlFooter;
	}
	// update_session()
	else if(@mysql_query(	"create procedure `update_session`(in `var_id` varchar(255), in `var_logged_out` tinyint(1))\n".
				"begin\n".
				"	update ".$_POST['sessions_table']." set\n".
				"	last_activity=NOW(),\n".
				"	logged_out=var_logged_out\n".
				"	where id=var_id;\n".
				"end"
				) == false) {
		echo $htmlHeader.$errorMessage."(update_session())".mysql_error()."\n".$htmlFooter;
	}
	// create_user()
	else if(@mysql_query(	"create procedure `create_user`(in `var_username` varchar(255), in `var_full_name` varchar(255), inout `var_user_id` int)\n".
				"begin\n".
				"	set var_user_id=null;\n".
				"	call find_user_id(var_username, var_user_id);\n".
				"	if var_user_id is null then\n".
				"		call find_free_id('".$_POST['users_table']."', var_user_id);\n".
				"		insert into ".$_POST['users_table']." values(\n".
				"		var_user_id,\n".
				"		var_username,\n".
				"		var_full_name);\n".
				"	end if;\n".
				"end"
				) == false) {
		echo $htmlHeader.$errorMessage."(create_user())".mysql_error()."\n".$htmlFooter;
	}
	// remove_user()
	else if(@mysql_query(	"create procedure `remove_user`(in `var_username` varchar(255))\n".
				"begin\n".
				"	delete from ".$_POST['users_table']." where username=var_username;\n".
				"end"
				) == false) {
		echo $htmlHeader.$errorMessage."(remove_user())".mysql_error()."\n".$htmlFooter;
	}
	// update_user()
	else if(@mysql_query(	"create procedure `update_user`(in `var_user_id` int, in `var_username` varchar(255), in `var_full_name` varchar(255))\n".
				"begin\n".
				"	update ".$_POST['users_table']." set\n".
				"	username=var_username,\n".
				"	full_name=var_full_name\n".
				"	where id=var_user_id;\n".
				"end"
				) == false) {
		echo $htmlHeader.$errorMessage."(update_user())".mysql_error()."\n".$htmlFooter;
	}
	// update_or_create_user()
	else if(@mysql_query(	"create procedure `update_or_create_user`(in `var_username` varchar(255), in `var_full_name` varchar(255), out `var_user_id` int)\n".
				"begin\n".
				"	call find_user_id(var_username, var_user_id);\n".
				"	if var_user_id is null then\n".
				"		call create_user(var_username, var_full_name, var_user_id);\n".
				"	else\n".
				"		call update_user(var_user_id, var_username, var_full_name);\n".
				"	end if;\n".
				"end"
				) == false) {
		echo $htmlHeader.$errorMessage."(update_or_create_user())".mysql_error()."\n".$htmlFooter;
	}

	// cleanupSession()()
	else if(@mysql_query(	"create procedure `cleanup_sessions`()\n".
				"begin\n".
				"	delete from `".$_POST['sessions_table']."`\n".
				"		where last_activity<(select CURDATE()-INTERVAL ".$sessionLogTime." SECOND);\n".
				"	update `".$_POST['sessions_table']."`\n".
				"		set logged_out='1'\n".
				"		where last_activity<(select CURDATE()-INTERVAL ".$sessionTime." SECOND);\n".
				"end"
				) == false) {
		echo $htmlHeader.$errorMessage."(cleanup_sessions())".mysql_error()."\n".$htmlFooter;
	}
	// login()
	else if(@mysql_query(	"create procedure `login`(in `var_username` varchar(255),\n".
				"				in var_ip varchar(255),\n".
				"				out `var_session_id` varchar(255))\n".
				"begin\n".
				"	declare var_count_id int;\n".
				"	declare var_user_id int;\n".
				"	call cleanup_sessions();\n".
				"	call find_user_id(var_username, var_user_id);\n".
				"	if var_user_id is not null then\n".
				"		call find_user_session_id(var_user_id, var_session_id);\n".
				"		if var_session_id=-1 then\n".
				"			call create_session(var_user_id, var_ip, var_session_id);\n".
				"		else\n".
				"			set var_session_id=-1;\n".
				"		end if;\n".
				"	else\n".
				"		set var_session_id=-2;\n".
				"	end if;\n".
				"end"
				) == false) {
		echo $htmlHeader.$errorMessage."(login())".mysql_error()."\n".$htmlFooter;
	}
	// logout()
	else if(@mysql_query(	"create procedure `logout`(in `var_user_id` int)\n".
				"begin\n".
				"	declare var_session_id varchar(255);\n".
				"	call find_user_session_id(var_user_id, var_session_id);\n".
				"	if var_session_id>=0 then\n".
				"		call update_session(var_session_id, 1);\n".
				"	end if;\n".
				"end"
				) == false) {
		echo $htmlHeader.$errorMessage."(logout())".mysql_error()."\n".$htmlFooter;
	}
	// user_exists()
	else if(@mysql_query(	"create procedure `user_exists`(inout `var_user_id` int)\n".
				"begin\n".
				"	declare   tmp_id int;\n".
				"	select id into   tmp_id\n".
				"		from `".$_POST['users_table']."`\n".
				"		where id=var_user_id;\n".
				"	if   tmp_id is null then\n".
				"		set var_user_id=-1;\n".
				"	end if;\n".
				"end"
				) == false) {
		echo $htmlHeader.$errorMessage."(user_exists())".mysql_error()."\n".$htmlFooter;
	}
	// get_portals()
	else if(@mysql_query(	"create procedure `get_portals`()\n".
				"begin\n".
				"	select * from `".$_POST['portals_table']."`;\n".
				"end"
				) == false) {
		echo $htmlHeader.$errorMessage."(get_portals())".mysql_error()."\n".$htmlFooter;
	}
	// get_portals_with_bookings()
	else if(@mysql_query(	"create procedure `get_portals_with_bookings`()\n".
				"begin\n".
				"	select *\n".
				"	from `".$_POST['portals_table']."` p\n".
				"	left join `".$_POST['bookings_table']."` b\n".
				"	on p.id=b.portal_id;`\n".
				"end"
				) == false) {
		echo $htmlHeader.$errorMessage."(get_portals_with_bookings())".mysql_error()."\n".$htmlFooter;
	}
	// get_bookings()
	else if(@mysql_query(	"create procedure `get_bookings`()\n".
				"begin\n".
				"	select * from `".$_POST['bookings_table']."`;\n".
				"end"
				) == false) {
		echo $htmlHeader.$errorMessage."(get_bookings())".mysql_error()."\n".$htmlFooter;
	}
	else {
		// problems in linux with access to create the configuration file
		// make sure the folder is writeable
		if (!is_dir("../config") && !mkdir("../config", 0755)) {
			echo $htmlHeader."\nThe installation failed! Could not create folder (config)!<br />\nYou can try to create the folder manually and try again.".$htmlFooter;
			exit(1);
		}
		$file = fopen("..Config.php", "w");
		if($file != null) {
			// global settings
			$writeBuffer = "<?php\r\n";
			$writeBuffer .= "/**\r\n * This file is automatically generated by the instal/setup process.\r\n * Please do not alter it manually!\r\n";
			$writeBuffer .= " * DATE GENERATED: ".date("Y-m-d H:m:s")."\r\n */\r\n";
			$writeBuffer = sprintf("define('MYSQL_HOST', '%s');\r\n", $_POST['mysql_host']);
			$writeBuffer = sprintf("define('MYSQL_USERNAME', '%s');\r\n", $_POST['mysql_username']);
			$writeBuffer = sprintf("define('MYSQL_PASSWORD', '%s');\r\n", $_POST['mysql_password']);
			$writeBuffer = sprintf("define('MYSQL_PORT', '%s');\r\n", $_POST['mysql_port']);
			$writeBuffer = sprintf("define('DATABASE_NAME', '%s');\r\n", $_POST['database_name']);
			$writeBuffer = sprintf("define('USERS_TABLE', '%s');\r\n", $_POST['users_table']);
			$writeBuffer = sprintf("define('SESSIONS_TABLE', '%s');\r\n", $_POST['sessions_table']);
			$writeBuffer = sprintf("define('BOOKINGS_TABLE', '%s');\r\n", $_POST['bookings_table']);
			$writeBuffer = sprintf("define('PORTALS_TABLE', '%s');\r\n", $_POST['portals_table']);
			$writeBuffer = sprintf("define('COOKIE_NAME', '%s');\r\n?>\r\n", $_POST['cookie_name']);
			fwrite($file, $writeBuffer);
			fclose($file);
			echo $htmlHeader."\nThe system is now installed, but is not configured.<br />\n".
			"To configure the system using the default settings <a href=\"./install_data.php\">press here</a>.".$htmlFooter;
		}
		else {
			echo $htmlHeader."\nThe installation failed! Could not create the configuration file (conf.php)!\n".$htmlFooter;
		}
	}
}
else if(!isset($_GET['phase'])) {
	echo $htmlHeader;?>
	<script type="text/javascript">
		<!--
		function getXmlHttpRequestObject () {
			if (window.XMLHttpRequest)
				return new XMLHttpRequest();
			else if (window.ActiveXObject)
				return new ActiveXObject("Microsoft.XMLHTTP");
			else return false;
		}
		
		var conn = getXmlHttpRequestObject();

		function handleResults(theDiv, theUrl) {
			var receivedData;
			if (conn.readyState == 4) {
				receivedData = conn.responseText;
				document.getElementById(theDiv).innerHTML = receivedData;
			}
			else {
					document.getElementById(theDiv).innerHTML = "["+conn.readyState+"] in progress...";
				}
		}
	
		function ajaxIt (theDiv, theUrl) {
			if (conn === false) document.getElementById(theDiv).innerHTML = "Could not create the XMLHttpRequest";
			else
				if (conn.readyState == 4 || conn.readyState == 0) {
					// kan göras om till encoded och POST
					conn.open("GET", theUrl+"&h="+document.getElementById("h").value+"&u="+document.getElementById("u").value+"&p="+document.getElementById("p").value, true);
					conn.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=utf-8');
					conn.onreadystatechange = function () { handleResults(theDiv, theUrl); }
					conn.send(null);
				}
		}
		-->
	</script>
	<p>
		Welcome to the installation of Home!<br />
		The installation requires that you have read and write<br />
		permittions to the MySQL server.<br />
		This process collects information<br />
		and creates the database and associated tables,<br />
		and creates the configuration file(conf.php) for you.<br />
		<span style="font-style: italic;"><b>WARNING:</b><br />
		If you abort the process the collected information will be lost!<br />
		If you redo the whole process the existing configuration file will<br />
		be overwritten(but not the admin acount)!</span>
	</p>
	<h1>Step 1 - MySQL information:</h1>
	<form method="post" action="install.php?phase=2">
	<table style="margin: auto; border: solid 1px black;">
		<tr>
			<td>MySQL server address/hostname:</td>
			<td><input type="input" id="h" name="mysql_host" value="localhost" /></td>
		</tr>
		<tr>
			<td>MySQL server port (TODO: use in test):</td>
			<td><input type="input" id="port" name="mysql_port" value="3306" /></td>
		</tr>
		<tr>
			<td>MySQL server username:</td>
			<td><input type="input" id="u" name="mysql_username" value="root" /></td>
		</tr>
		<tr>
			<td>MySQL server password:</td>
			<td><input type="password" id="p" name="mysql_password" value="" /></td>
		</tr>
		<tr>
			<td colspan="2" style="text-align: center;">
				<input onclick="javascript:ajaxIt('progressTag', 'install.php?testMysql=1');" type="button" value="Test MySQL" />&nbsp;<input type="submit" value="Next" />
			</td>
		</tr>
	</table>
	</form>
	MySQL test:	<span id="progressTag">MySQL has not been tested!</span>
<?php
	echo $htmlFooter;
}
else if($_GET['phase'] == 2) {
	echo $htmlHeader;
?>
	<script type="text/javascript">
		function checkField() {
			return true;
		}
	</script>
	<h1>Step 2 - MySQL-structure:</h1>
	<p>
		It is not recomended to alter any information for the tables.<br />
		Valid characters are [a-z], [A-Z], [0-9], '-', and '_'.
	</p>
	<form method="post" action="install.php?phase=3">
	<table style="margin: auto; border: solid 1px black;">
		<tr><td colspan=2" style="font-weight:bold;">General settings:</td></tr>
		<tr>
			<td>MySQL database name:</td>
			<td><input type="input" name="database_name" value="devicemanagement" /></td>
		</tr>
		<tr>
			<td>Table 'portals' (will not be created, just referenced):</td>
			<td><input type="input" name="portals_table" value="portals" /></td>
		</tr>		<tr>
			<td>Cookie name:</td>
			<td><input type="input" name="cookie_name" value="axis-home" /></td>
		</tr>
		<tr>
			<td>Table 'sessions':</td>
			<td><input type="input" name="sessions_table" value="sessions" /></td>
		</tr>
		<tr>
			<td>Table 'users':</td>
			<td><input type="input" name="users_table" value="users" /></td>
		</tr>
		<tr>
		<tr>
			<td>Table 'bookings':</td>
			<td><input type="input" name="bookings_table" value="bookings" /></td>
		</tr>
		<tr>
			<td colspan="2" style="text-align: center;">
				<input type="submit" name="mysql" value="Create tables and config file" />
				<input type="hidden" name="mysql_host" value="<?php echo $_POST['mysql_host']?>" />
				<input type="hidden" name="mysql_port" value="<?php echo $_POST['mysql_port']?>" />
				<input type="hidden" name="mysql_username" value="<?php echo $_POST['mysql_username']?>" />
				<input type="hidden" name="mysql_password" value="<?php echo $_POST['mysql_password']?>" />
			</td>
		</tr>
	</table>
	</form>
<?php
	echo $htmlFooter;
}
?>
