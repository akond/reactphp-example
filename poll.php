<?php

use React\Promise\Deferred;

require "vendor/autoload.php";

$loop = React\EventLoop\Factory::create();
echo get_class ($loop);
echo "\n";

$dnsResolverFactory = new React\Dns\Resolver\Factory();
//$dnsResolver = $dnsResolverFactory->createCached('8.8.8.8', $loop);
$dnsResolver = $dnsResolverFactory->createCached('127.0.0.1', $loop);

$factory = new React\HttpClient\Factory();
$client = $factory->create($loop, $dnsResolver);
for ($i = 0; $i < 3; $i++)
{
//	echo get_class ($client), "\n";
//	$request = $client->request('GET', 'http://10.0.0.1:6789/micro-service.php');
	$request = $client->request('GET', 'http://dev.akond.net/react-php/micro-service.php');
	
	$request->on('response', function ($response) {

   		$response->on ('data', function ($x) {
   			echo "x = $x\n";
   		});

		if (0)
    	$response->on('data', function ($data, $response) {
    		if (0)
	    	if (false == is_object ($data))
    		{
	    		var_dump ($data);
	    	}
    		else 
    		{
				$string = '';
				while (false == $data->eof())
				{
					$string .= $data->read (1024);
				}
				echo $string, "\n\n";
			}

		});
	});

	$request->end();
}

if (0)
{
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
}



$loop->run();

