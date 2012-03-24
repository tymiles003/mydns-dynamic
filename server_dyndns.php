<?php
error_reporting(0);
ini_set('display_errors', '0');

$dbhost = '127.0.0.1';
$dbuser = 'login';
$dbpass = 'password';
$dbname = 'database';

$secrets = array(
  'keyboard cat' => array(
    'subdomain' => 'dyn',
    'domain' => 'example.com',
    'mailto' => 'me@corpix.ru'
  )
);

// ---------------------------------------- //

function response($arr) {
  die(json_encode($arr));
}

$userSecret = $_POST['secret'];

$setIp = $_SERVER['REMOTE_ADDR'];
$res = array('changed' => false);


if($secret = $secrets[$userSecret]) {
  $subdomain = $secret['subdomain'];
  $domain = $secret['domain'];
  $mailto = $secret['mailto'];

  @mysql_connect($dbhost, $dbuser, $dbpass) or response($res);
  @mysql_select_db($dbname) or response($res);

  $query = "SELECT id FROM soa WHERE origin='$domain.'";
  $result = mysql_fetch_assoc(mysql_query($query));
  $soaId = $result['id'];
  if(!$soaId) {
    $res['error'] = 'SOA not found!';
    response($res);
  }

  $query = "SELECT * FROM rr WHERE zone='$soaId' AND name='$subdomain'";

  $result = mysql_fetch_assoc(mysql_query($query));
  if(!$result) {
    $res['error'] = 'Cant find subdomain!';
    response($res);
  }
  $res['current'] = $setIp;

  if($result['data'] != $setIp) {
    $res['changed'] = true;
    $res['old'] = $result['data'];

    $query = "UPDATE rr SET data='$setIp' WHERE zone='$soaId' AND name='$subdomain'";
    @mysql_query($query) or response($res);

    # Update zone serial
    $query = "UPDATE soa SET serial = (serial+1) WHERE origin = '$domain.'";
    @mysql_query($query) or response($res);

    # Send email
    if(!empty($mailto)) {
      $subject = "DNS change: $subdomain";
      $header = "From: DNSAdmin <dyndns@$domain>\r\n";
      $body = "Date of change: " . date("Y-m-d H:i:s") . "\n"
            . "Remote address: $setIp->{$_SERVER['REMOTE_ADDR']}\n"
            . "Changed record: $subdomain.$domain\n";

      @mail($mailto, $subject, $body, $header);
    }
  }

  response($res);
}

response(array('error' => 'Forbidden'));
