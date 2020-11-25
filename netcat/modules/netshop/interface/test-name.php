<?php
$name="- Нож Лунь (дамасская сталь)";
echo $name."<br>";
$name=trim($name);
(substr($name,0,1)=="-") ? $name=substr($name, 1) : "" ;
(substr($name,0,1)==".") ? $name=substr($name, 1) : "" ;
//(substr($name,1,1)==" ") ? $name=substr($name, 1) : "" ;
$name=trim($name);
echo $name;
?>