<?php
// 11/11/2016 Elen elensnor@gmail.com
// CDEK API
function convstr($str) {
	return iconv("windows-1251//TRANSLIT","UTF-8",$str);
}
function convstrw($str) {
	return iconv("UTF-8","windows-1251//TRANSLIT",$str);
}

function post_request($url, $dom, $referer='') {
	//	echo $url;
	//$dom=convstrw($dom);
	
    
	//echo "\n";
	//echo mb_detect_encoding($dom);
	//echo "\n";
	//echo $dom."\n";
	
	
	//$dom=convstrw($dom);
	
	
	//echo "\n".mb_detect_encoding($dom);
	
	$data = array('xml_request'=>$dom);
	$data = http_build_query($data);
	
    // parse the given URL
    $urla = parse_url($url);
	//print_r($urla);
 

 
    //if ($url['scheme'] != 'http') { 
    //   die('Error: Only HTTP request are supported !');
    //}
 
    // extract host and path:
    $host = $urla['host'];
    $path = $urla['path'];
 
	$fp = fsockopen($host, 80, $errno, $errstr, 30);
	//$fp = fsockopen("ssl:{$host}", 433, $errno, $errstr, 30);
	//$fp = fsockopen("ssl://integration.cdek.ru", 433, $errno, $errstr, 30);
 
    if ($fp){
        // send the request headers:
        fputs($fp, "POST {$path} HTTP/1.1\r\n");
        fputs($fp, "Host: {$host}\r\n");
 
        if ($referer != '')
            fputs($fp, "Referer: {$referer}\r\n");
 
        fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
        fputs($fp, "Content-length: ". strlen($data) ."\r\n");
        fputs($fp, "Connection: close\r\n\r\n");
        fputs($fp, $data);
 
        $result = ''; 
        while(!feof($fp)) {
            // receive the results of the request
            $result .= fgets($fp, 1024);
        }
    }
    else { 
		echo "{$errstr} ({$errno})";
        return array(
            'status' => 'err', 
            'error' => "$errstr ($errno)"
        );
    }
 
    // close the socket connection:
    fclose($fp);  
	echo "<!--".$result."-->";
    // split the result header from the content
    $result = explode("\r\n\r\n", $result, 2);
	//print_r($result);
 
    $header = isset($result[0]) ? $result[0] : '';
    $content = isset($result[1]) ? $result[1] : '';
 
	//echo $content; 
 
    // return as structured array:
    return array(
        'status' => 'ok',
        'header' => $header,
        'content' => $content
    );
}

function post_request_trass($url, $dom, $referer='') {
	$data = array('xml_request'=>$dom);
	$data = http_build_query($data);
	
    // parse the given URL
    $urla = parse_url($url);

    // extract host and path:
    $host = $urla['host'];
    $path = $urla['path'];
 
	$fp = fsockopen($host, 80, $errno, $errstr, 30);
	//$fp = fsockopen("ssl:{$host}", 433, $errno, $errstr, 30);
	//$fp = fsockopen("ssl://integration.cdek.ru", 433, $errno, $errstr, 30);
 
    if ($fp){
        // send the request headers:
        fputs($fp, "POST {$path} HTTP/1.1\r\n");
        fputs($fp, "Host: {$host}\r\n");
 
        if ($referer != '')
            fputs($fp, "Referer: {$referer}\r\n");
 
        fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
        fputs($fp, "Content-length: ". strlen($data) ."\r\n");
        fputs($fp, "Connection: close\r\n\r\n");
        fputs($fp, $data);
 
        $result = ''; 
        while(!feof($fp)) {
            // receive the results of the request
            $result .= fgets($fp, 1024);
        }
    }
    else { 
		echo "{$errstr} ({$errno})";
        return array(
            'status' => 'err', 
            'error' => "$errstr ($errno)"
        );
    }
 
    // close the socket connection:
    fclose($fp);  
	echo "<!--".$result."-->";
	return array('status' => 'ok',
				'content' => $result);
}

function post_request_pdf($url, $dom, $fid=0,$UploadDir,$referer="") {
	//	echo $url;
    $data = array('xml_request'=>$dom);
	$data = http_build_query($data);
	//echo mb_detect_encoding($dom);
	//print_r($data);
	//echo "<br>".mb_detect_encoding($data);
    // parse the given URL
    $urla = parse_url($url);
	//print_r($urla);
 

 
    //if ($url['scheme'] != 'http') { 
    //   die('Error: Only HTTP request are supported !');
    //}
 
    // extract host and path:
    $host = $urla['host'];
    $path = $urla['path'];
 
	$fp = fsockopen($host, 80, $errno, $errstr, 30);
 
    if ($fp){
        // send the request headers:
        fputs($fp, "POST {$path} HTTP/1.1\r\n");
        fputs($fp, "Host: {$host}\r\n");
 
        if ($referer != '')
            fputs($fp, "Referer: {$referer}\r\n");
 
        fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
        fputs($fp, "Content-length: ". strlen($data) ."\r\n");
        fputs($fp, "Connection: close\r\n\r\n");
        fputs($fp, $data);
 
        $result = ''; 
        while(!feof($fp)) {
            // receive the results of the request
            //$result .= fgets($fp,4096);
            $result .= fgets($fp);
        }
    }
    else { 
        return array(
            'status' => 'err', 
            'error' => "$errstr ($errno)"
        );
    }
 
    // close the socket connection:
    fclose($fp);
	echo "<br><br>";
	$tmp = explode("\r\n\r\n", $result, 2);	
	print_r($tmp);
 
	$name=explode("\"",$tmp[0]);
	$file=$fid.".pdf";
	file_put_contents($UploadDir.$name[1], $tmp[1]);
	return $fid;
 
    // return as structured array:
    //return array(
    //    'status' => 'ok',
    //    'content' => $result
    //);
}

function get_request($url, $data, $referer='') {
	//echo $url."?".$data."<br>";
 	$xml = file_get_contents($url."?".$data);
	//print_r($xml);
    // return as structured array:
    return array(
        'status' => 'ok',
        'header' => "",
        'content' => $xml
    );
	

}
?>