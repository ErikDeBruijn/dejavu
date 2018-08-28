<?php

$pair = "ETHBTC";
if(strlen($argv[1])) 
    $pair = $argv[1];


$rk = new RdKafka\Consumer();
$rk->setLogLevel(LOG_DEBUG);
$rk->addBrokers("localhost");

$topic1 = $rk->newTopic("candlestick.$pair.1m");
$topic1->consumeStart(0, RD_KAFKA_OFFSET_BEGINNING);

$topic2 = $rk->newTopic("indicators.$pair.1m");
$topic2->consumeStart(0, RD_KAFKA_OFFSET_BEGINNING);


$rk_p = new RdKafka\Producer();
$rk_p->setLogLevel(LOG_DEBUG);
$rk_p->addBrokers("localhost");


ini_set('trader.real_precision', 10);


while (true) {
    $n++;
    // The first argument is the partition (again).
    // The second argument is the timeout.
    $indicator = $topic1->consume(0, 1000);
    if(!is_object($indicator)) {
        continue;
    }
    if ($indicator->err) {
        // -191 is when you reach the tail (most recent indicator)
        if($indicator->err != -191) {
            echo "Error ".$indicator->err.": ".$indicator->errstr(), "\n";
            break;
        }
        if($indicator->err == -191) {
            echo "Arrived at most recent event.\n";
            sleep(0.1);
            continue;
        }
    } else {
        $indicator_data = json_decode($indicator->payload);
        print_r($indicator_data);
        // $indicator_data['CS_CLOSE_TIME'];
    }
}


echo "Done consuming messages.";
