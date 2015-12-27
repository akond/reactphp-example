<?php
use React\Promise\Deferred;


require "vendor/autoload.php";

$deferred = new Deferred();
$deferred->promise ()->then (function ()
{
	echo "then\n";
})->always(function () {
	echo "always\n";
})->progress(function () {
	echo "progress\n";
});


$deferred->notify();
$deferred->reject();
$deferred->resolve();
