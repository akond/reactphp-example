<?php
use  React\SocketClient\Connector;
use  Zend\Http\Response;
require "vendor/autoload.php";



$loop = React\EventLoop\Factory::create();
$dnsResolverFactory = new React\Dns\Resolver\Factory();
$dnsResolver = $dnsResolverFactory->createCached('127.0.0.1', $loop);

$connector = new Connector ($loop, $dnsResolver);
$connectorRepeater = new \ConnectionManager\Extra\ConnectionManagerTimeout ($connector, $loop, 1);
for ($i = 0; $i < 3; $i ++)
{
$connectorRepeater->create('dev.akond.net', 80)->then(function ($stream) {

    $stream->on('data', function ($data, $stream) {
    if (empty ($data))
    {
    	return;
    }
    	$r = Response::fromString ($data);
    	
   	echo $r->getContent ();
    });
    
    if (0)
    $stream->on('close', function ($stream) {
    	echo 'closed';
    });    

    $stream->write (<<<'txt'
GET /react-php/micro-service.php HTTP/1.0
Host: dev.akond.net


txt
);
	
//    $stream->end();
});
}
//echo "Done\n";
$loop->run ();
