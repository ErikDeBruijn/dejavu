<?php

$pair = "ETHBTC";


if(strlen($argv[1])) 
    $pair = $argv[1];


$rk = new RdKafka\Consumer();
$rk->setLogLevel(LOG_DEBUG);
$rk->addBrokers("localhost");

$topic = $rk->newTopic("candlestick.$pair.1m");
$topic->consumeStart(0, RD_KAFKA_OFFSET_BEGINNING);

$rk_p = new RdKafka\Producer();
$rk_p->setLogLevel(LOG_DEBUG);
$rk_p->addBrokers("localhost");


ini_set('trader.real_precision', 10);

// $topic->consumeStart(0, rd_kafka_offset_tail(200));
define("CS_CLOSE_PRICE",4);
define("CS_CLOSE_TIME", 6);

function produce_indicators($kafka,$topic, $payload) {
    $prod_topic = $kafka->newTopic($topic);
    $prod_topic->produce(RD_KAFKA_PARTITION_UA, 0, $payload/*, $key*/);
    $kafka->poll(0);

}
while (true) {
    $n++;
    // The first argument is the partition (again).
    // The second argument is the timeout.
    $msg = $topic->consume(0, 1000);
    if(!is_object($msg)) {
        continue;
    }
    if ($msg->err) {
        // -191 is when you reach the tail (most recent msg)
        if($msg->err != -191) {
            echo "Error ".$msg->err.": ".$msg->errstr(), "\n";
            break;
        }
        if($msg->err == -191) {
            echo "Arrived at most recent event.\n";
            sleep(0.1);
            continue;
        }
    } else {
		// echo $msg->payload, "\n";

		$candle = json_decode($msg->payload);
		// Sanity check: time should always go UP.
		if($candle[0] < $prev_candle) {
			echo "hmmm... current candle ($candle[0]) older than previous ($prev_candle).\n";
			continue;
		}
		$prev_candle = $candle[0];
		echo "$n: ".$msg->payload."\n";
   
   
   
		//Add CS_CLOSE_TIME to indicators
			//$indicators['CS_CLOSE_TIME'] = $candle[CS_CLOSE_TIME];

   
   		//Add MACD to indicators
			// trader_macd
			// array trader_macd ( array $real [, int $fastPeriod [, int $slowPeriod [, int $signalPeriod ]]] )
			// 
   		
   		//Classify Candlesticks
			//Trader has many classifiers of the candlesticks. I think we should create an array with candlesticks, and then test with all CDL functions if the pattern applies. Eg.:
			// array trader_cdl2crows ( array $open , array $high , array $low , array $close )
			//This function requires the candlestick data, and will return an array. However, I don't know how to test it yet.
   		
			//$indicators['classified_candlestick'] = $candlestick_classification;
			// 
   
   		//Add Percentiles to indicators --> Creates an array of percentiles at a certain point
			// 		$percentile_period = 100;
			// 		$percentile_array[] = $candle[CS_CLOSE_PRICE];
			// 		if(count($percentile_array) > $percentile_period) {
			// 
			// 			array_shift($ema_array);
			// 			//finish from here
			// 		}
   
   		//Determines at what percentile value the price current is
   		
   
   
		//Add EMA (8-21) to indicators 
		$ema_max_period = 21;
		$ema_min_period = 8;
		$ema_array[] = $candle[CS_CLOSE_PRICE];
		if(count($ema_array) > $ema_max_period) {

			array_shift($ema_array);
			$ema = array();

			for($ema_period = $ema_min_period; $ema_period<=$ema_max_period; $ema_period++;){
				$ema[$ema_period] = end(trader_ema($ema_array,$ema_period));
			} 
		
			echo print_r($ema,true)." cnt=".count($ema_array)."\n";
			$indicators['CS_CLOSE_TIME'] = $candle[CS_CLOSE_TIME];   //place this to other section
			$indicators['ema'] = $ema;
			produce_indicators($rk_p, "indicators.".$pair.".1m", json_encode($indicators)); //place this to other section
			sleep(0.1); //place this to other section
		}
	
		//Produce indicators
			//produce_indicators($rk_p, "indicators.".$pair.".1m", json_encode($indicators)); //place this to other section
			//sleep(0.1); //place this to other section
    }
}


echo "Done consuming messages.";
