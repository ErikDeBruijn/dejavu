<?php

$rk = new RdKafka\Producer();
$rk->setLogLevel(LOG_DEBUG);
$rk->addBrokers("127.0.0.1");



include 'settings.php';

//get list of symbols
$xinfo = json_decode(file_get_contents("https://api.binance.com/api/v1/exchangeInfo"), true);
$pairs = [];
foreach($xinfo["symbols"] as $sym) $pairs[] = $sym["symbol"];
foreach($pairs as $pair){
	$endofsymbol = substr($pair, -3);
	if($endofsymbol == "BTC"){
		$pairs_temp[]=$pair;
	}
}
$pairs = $pairs_temp;


//$intervals = array("1h","30m","15m","5m","1m");
$interval = "1m";
$intervals = array($interval);
foreach($pairs as $pair){
	echo "======== PAIR: $pair ============\n";
	$topic = $rk->newTopic("candlestick.".$pair.".".$interval);

	$pairdir = BASE_PATH . "/historical_data/$pair";
	$pairfiles = scandir($pairdir, 1);
	print_r($pairfiles);
	if($pairfiles[0] == ".."){
		$ts_last = 1483243199000; //1.1.2017 milisec;
		$ts_ms = round(microtime(true)*1000); //current GMT UNIX time in miliseconds
		$ts_last = $ts_ms - (3600 * 24 * 7 * 1000); // last week
	}else{
		$exploded_file = explode("_", $pairfiles[0]);
		$ts_last = substr($exploded_file[3],0,13);
	}
	
	foreach ($intervals as $int)
	{	

		$ts_ms = round(microtime(true)*1000); //current GMT UNIX time in miliseconds
		$qty = 10;
		
		while ($ts_last < $ts_ms && $qty > 0)
		{	
			$filename = BASE_PATH . "/historical_data/$pair/binance_".$pair."_".$int."_".$ts_last.".json";
			echo gmdate("Y-m-d\TH:i:s\Z", $ts_last/1000) . "\n";
			$string = file_get_contents("https://api.binance.com/api/v1/klines?symbol=".$pair."&interval=".$int."&startTime=".$ts_last);
			//print_r($http_response_header);//global variable, always filled
			if ( $http_response_header[0] != "HTTP/1.1 200 OK") {echo $string . "\n" . $http_response_header[0]; exit;}
			$json = json_decode($string, true);
			//json trim [ /not first/
			if ($ts_last != 1483243199000) $string = substr($string, 1);
			$ts_last = $json[count($json)-1][6]; $ts_last++;
			$qty = count($json);
			echo $qty  . "\n";
			foreach ($json as $json_row) {
				$msg = json_encode($json_row);
				echo "producing msg: ".$msg."\n";
				$topic->produce(RD_KAFKA_PARTITION_UA, 0, $msg/*, $key*/);
			}

			//json trim ] add, /not last, should have less than 500, 0.2% chance/
			if ($qty == 500) {$string .= "\n";}
			file_put_contents($filename, $string, FILE_APPEND | LOCK_EX);
			flush();
			ob_flush();
			usleep(200000);
			set_time_limit (60);
		}
		echo "Done! " . $filename . "\n";
	}
}
