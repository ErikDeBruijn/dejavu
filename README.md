# Deja Vu

Deja Vu is a framework that allows both backtesting and real-time processing of events (e.g. price movements, Tweets, etc.) to result in actions (e.g. buy/sell a coin or stock, turn on an electricity consuming device, charge a battery, etc.).

When backtesting, essentially, all actions are simulated and you can infer from historical information how a policy or set of policies would have worked in the past and subsequently decide to run the exact same policies real time.

# How to setup
Setting up the PHP bindings to the client for kafka:
```
# git clone https://github.com/arnaud-lb/php-rdkafka.git
# cd php-rdkafka/
# phpize
# apt-get install librdkafka-dev
# ./configure
#  make all -j 5
# sudo make install
# echo extension=rdkafka.so > /etc/php/7.2/mods-available/rdkafka.ini
# phpenmod rdkafka
```

You can now use rdkafka. More information:
* Documentation is here: https://arnaud-lb.github.io/php-rdkafka/phpdoc/book.rdkafka.html
* Github here: https://github.com/arnaud-lb/php-rdkafka

Setting up Kafka:
```
# apt-get install openjdk-8-jdk-headless kafkacat
# useradd kafka -m
# adduser kafka sudo
```

Save the following to `/etc/systemd/system/kafka.service`:
```
[Unit]
Requires=zookeeper.service
After=zookeeper.service

[Service]
Type=simple
User=kafka
ExecStart=/bin/sh -c '/home/kafka/kafka/bin/kafka-server-start.sh /home/kafka/kafka/config/server.properties > /home/kafka/kafka/kafka.log 2>&1'
ExecStop=/home/kafka/kafka/bin/kafka-server-stop.sh
Restart=on-abnormal

[Install]
WantedBy=multi-user.target
```

And `zookeeper.service`:
```
[Unit]
Requires=network.target remote-fs.target
After=network.target remote-fs.target

[Service]
Type=simple
User=kafka
ExecStart=/home/kafka/kafka/bin/zookeeper-server-start.sh /home/kafka/kafka/config/zookeeper.properties
ExecStop=/home/kafka/kafka/bin/zookeeper-server-stop.sh
Restart=on-abnormal

[Install]
WantedBy=multi-user.target
```

Then run `systemctl enable kafka` and `systemctl restart kafka`
