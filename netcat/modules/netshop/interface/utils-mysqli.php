<?php
// 10/11/2016 mysqli driver


function db_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD,$MYSQL_DB_NAME) {
	$con = mysqli_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD,$MYSQL_DB_NAME);
	if (!$con) {
		die("Could not connect: " . mysqli_connect_errno()."<br>".mysqli_connect_error());
	}
	//character_set_name()	
	mysqli_set_charset($con,"cp1251");
		
	return $con;
}

function db_close($con) {
	mysqli_close($con);
}

function db_query($con,$query) {
	//print_r($con);
	$result=mysqli_query($con,$query);
	if ($result!=false) {
		return $result;
	} else {
		die("Error MySQL request: ".mysqli_error($con)."<br>Query: ".$query);
	}
}

?>