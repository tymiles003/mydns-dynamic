<?php

$host = 'https://somedomain.ru/dyndns.php';
$username = 'user';
$password = 'password';
$secret = 'keyboard cat';

$params = array(
	array(
		'secret' => $secret,
		'domain' => 'somadomain.ru',
		'subdomain' => 'me'
	)
);

foreach($params as $param) {
	$c = curl_init();
	               
	curl_setopt_array($c, array(
	  CURLOPT_URL => $host.'?'.http_build_query($param),
	  CURLOPT_HEADER => false,
	  CURLOPT_USERPWD => $username . ":" . $password,
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_SSL_VERIFYPEER => false,
	  
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

