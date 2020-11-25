<?php
// 12.07.2018 Elen
// генерация штрих кода


include_once ("../../../../vars.inc.php");
include_once ("utils.php");
include_once ("utils-conv.php");
include_once ("utils-mysqli.php");
include_once ("utils-template.php");
session_start();

function addZero($str,$count) {
	while (strlen($str)<$count) {
		$str="0".$str;
	}
	return $str;
}
function addBu ($bu) {
	$bu1="";
	switch ($bu) {
		case "a": $bu1="1"; break;
		case "A": $bu1="1"; break;
		case "b": $bu1="2"; break;
		case "B": $bu1="2"; break;
		case "c": $bu1="3"; break;
		case "C": $bu1="3"; break;
		case "d": $bu1="4"; break;
		case "D": $bu1="4"; break;
		case "e": $bu1="5"; break;
		case "E": $bu1="5"; break;
		case "f": $bu1="6"; break;
		case "F": $bu1="6"; break;
		case "g": $bu1="7"; break;
		case "G": $bu1="7"; break;
		case "k": $bu1="8"; break;
		case "z": $bu1="9"; break;
		case convstrw("а"): $bu1="1"; break;
		case convstrw("А"): $bu1="1"; break;
		case convstrw("б"): $bu1="2"; break;
		case convstrw("Б"): $bu1="2"; break;
		case convstrw("в"): $bu1="3"; break;
		case convstrw("г"): $bu1="4"; break;
		case convstrw("д"): $bu1="5"; break;
		case convstrw("е"): $bu1="6"; break;
		case convstrw("с"): $bu1="7"; break;
		case convstrw("у"): $bu1="8"; break;
		default:break;
	}
	return $bu1;
}

function createCode($articul) {
	$str="225";
	$str1="";
	
	//225 000 110 676  (для артикула – 0-11067)
	//225 300 003 856 (для артикула – 0-385с)
	if (strpos($articul,"-") == true) {
		$tmp=explode("-", $articul);
		//print_r($tmp);
		if (is_numeric($tmp[1])) {
			$str=$str.addZero($tmp[1],9);
		} else {
			$bu=substr($tmp[1],-1,1);
			$bu1=addBu($bu);
			$str=$str.$bu1.addZero((int)$tmp[1],8);
		}
	} else {
		if (is_numeric($articul)) {
			$str=$str.addZero($articul,9);
		} else {
			$bu=substr($articul,-1,1);
			$bu1=addBu($bu);
			$str=$str.$bu1.addZero((int)$articul,8);
		}
		
	}
	
	$tmp=str_split($str);
	$sum=0;
	for ($i=0;$i<12;$i++) {
		$sum=$sum + ((($i%2)==1) ? $tmp[$i]*3 : $tmp[$i]);
		//echo $sum."<br>";
	}
	$end=10-($sum%10);
	//echo $end;
	
	$str=$str.(($end==10) ? "0" : $end);
	
	return $str;
}

function checkShtrihcode($con, $code, $id) {
	$sql = "SELECT Message_ID FROM Message57 WHERE shtrihcode LIKE '{$code}' AND NOT Message_ID={$id}";
	$result=db_query($con, $sql);
	$num = mysqli_num_rows($result)."<br>";
	return $num;
}

function saveShtrihcode($con, $code, $id) {
	$sql="UPDATE Message57 SET shtrihcode='".$code."' WHERE Message_ID=".$id;
	echo $sql."<br>";
	if (db_query($con,$sql)) {
		echo "Update {$id}<br>";
	} else {
		die("error");
	}
}

$incoming = parse_incoming();

isLogged();

$con=db_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD,$MYSQL_DB_NAME);

// ------------------------------------------------------------------------------------------------
//echo printHeader("Штрих код");
//echo printTopMenu($con);

$sql="SELECT Message_ID,ItemID,shtrihcode FROM Message57  ORDER BY Message_ID ASC";
echo $sql."<br>";
$result=db_query($con, $sql);
while ($row = mysqli_fetch_array($result)) {
	if (strlen($row['shtrihcode'])<7) {
		//if (($row['Message_ID']!=346)&&($row['Message_ID']!=446)&&($row['Message_ID']!=501)) {
//		if ($row['Message_ID']!=446) {
			echo $row['Message_ID']." | ".$row['ItemID']."<br>";
			$code=createCode(trim($row['ItemID']));
			echo $code."<br>";
			
			$num = checkShtrihcode($con, $code, $row['Message_ID']);
			echo $num."<br>";
			if ($num>0) die();
			
			saveShtrihcode($con,$code,$row['Message_ID']);
		//}
	}
}

//</body>
//</html>
db_close($con);
?>
