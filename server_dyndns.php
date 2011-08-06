<?php
error_reporting(0);
ini_set('display_errors', '0');

$dbhost='127.0.0.1';
$dbuser='login';
$dbpass='password';
$dbname='database';

$secret='keyboard cat';
$mail_to='mail@somedomain.ru';
                                                       
$allowed = array(
	'me.somedomain.ru' => true
);


function valid_url($url) {
  return (boolean)preg_match ("/^[a-z0-9\.-]+$/i", $url);
}

function response($arr) {
  die(json_encode($arr));
}

$domain = $_GET['domain'];
$subdomain = $_GET['subdomain'];
$ip = $_SERVER['REMOTE_ADDR'];
$res = array('changed' => false);


if($_GET['secret'] == $secret) {
  if(valid_url($domain) && valid_url($subdomain) && $allowed[$subdomain])) {
    @mysql_connect($dbhost, $dbuser, $dbpass) or response($res);
    @mysql_select_db($dbname) or response($res);
    
    $query = "SELECT id FROM soa WHERE origin='". $domain .".'";
    $result = mysql_fetch_assoc(mysql_query($query));
    $soaId = $result['id'];
    if(!$soaId) {
    	$res['error'] = 'SOA not found';
    	response($res);
    }

		$query = "SELECT * FROM rr "
           . "WHERE zone='$soaId' "
           . "AND name='$subdomain'";
           
    $result = mysql_fetch_assoc(mysql_query($query));
    if(!$result) {
    	$res['error'] = 'Cant find subdomain';
    	response($res);
    }
    $res['current'] = $ip;

    if($result['data'] != $ip) {
	    $res['changed'] = true;
	    $res['old'] = $result['data'];
	    
	    $query = "UPDATE rr SET data='". $ip ."' "
	           . "WHERE zone='$soaId' "
	           . "AND name='$subdomain'";
	    @mysql_query($query) or response($res);
	
	    # Update zone serial
	    $query = "UPDATE soa SET serial = (serial+1) "
	           . "WHERE origin = '$domain.'";
	    @mysql_query($query) or response($res);
	
	    # Send email
	    if(!empty($mail_to)) {
	      $subject = 'DNS change: '. $subdomain;
	      $header = 'From: DNSAdmin <dyndns@'. $domain .">\r\n";
	      $body = 'Date of change: '. date("Y-m-d H:i:s") ."\n"
	            . 'Remote address: '. $result['data'] . "->" . $_SERVER['REMOTE_ADDR'] ."\n"
	            . 'Changed record: '. $subdomain .".". $domain ."\n";
	      @mail($mail_to, $subject, $body, $header);
	    }
    }
    response($res);
  }
}

response(array('error' => 'Unathorized'));
