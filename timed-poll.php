<?php

use React\Promise\Deferred;
use React\EventLoop\Timer\Timer;

require "vendor/autoload.php";

$loop = React\EventLoop\Factory::create();

if (0) {
$dnsResolverFactory = new React\Dns\Resolver\Factory();
$dnsResolver = $dnsResolverFactory->createCached('127.0.0.1', $loop);

$factory = new React\HttpClient\Factory();
$client = $factory->create($loop, $dnsResolver);
}

$loop->addPeriodicTimer (.25, function () {
	static $count = 0;
	$count ++;
	
	echo $count, PHP_EOL;
	if ($count == 10) {
		posix_kill(posix_getpid(), SIGTERM);
	}
});

$pcntl = new MKraemer\ReactPCNTL\PCNTL($loop);

$pcntl->on(SIGTERM, function () {
    // Clear some queue
    // Write syslog
    // Do ALL the stuff
    echo 'Bye'.PHP_EOL;
    die();
});

$pcntl->on(SIGINT, function () {
    echo 'Terminated by console'.PHP_EOL;
    die();
});


$loop->run();

