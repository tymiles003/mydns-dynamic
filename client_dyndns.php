<?php

$host = 'https://somedomain.ru/dyndns.php';
$secret = 'keyboard cat';

// ------------------------------------------ //

$params = array(
  array(
    'secret' => $secret,
  )
);

foreach($params as $param) {
  $c = curl_init();

  curl_setopt_array($c, array(
    CURLOPT_URL => $host,
    CURLOPT_HEADER => false,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,

    CURLOPT_POST => count($params),
    CURLOPT_POSTFIELDS => http_build_query($param),

    CURLOPT_VERBOSE => false,
  ));

  $res = curl_exec($c);
  curl_close($c);
  $res = json_decode($res);

  if($res) {
    if($res->changed) {
      if(exec('which notify-send')) exec("DISPLAY=:0 notify-send -t 10 -u low 'Dynamic DNS' 'IP обновлён\\n{$res->current}' -i /usr/share/icons/Humanity/actions/48/rotate.svg");
    }
  }
}

