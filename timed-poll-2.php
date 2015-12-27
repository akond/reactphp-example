<?php

use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\EventLoop\Timer\Timer;
use Zend\Stdlib\ArrayObject;


require "vendor/autoload.php";


class Context
{
	/**
	 * @var Deferred
	 */
	private $deferred;

	private $needs = [];
	private $unsettled = [];
	private $data = [];
	private $requests = [];

	/**
	 * @var Timer
	 */
	private $cancel_timer;
	/**
	 * @var LoopInterface
	 */
	private $loop;
	private $then_callback;
	private $response;


	function __construct ($client , $loop, $response)
	{
		$this->loop = $loop;
		$this->response = $response;
		$this->client = $client ;
	}


	public function must ($url, $alias = '')
	{
		if (empty ($alias))
		{
			$alias = $url;
		}

		$this->needs [$alias] = $url;
		$this->unsettled [$alias] = 1;
		$this->should ($url, $alias);

		return $this;
	}


	public function should ($url, $alias = '')
	{
		if (empty ($alias))
		{
			$alias = $url;
		}

		$request = $this->client->request ('GET', $url);
		$this->requests [] = $request;

		$request->on ('response', function ($response) use ($alias)
		{
			$response->on ('data', function ($data) use ($alias)
			{
				if (empty($data))
				{
					return;
				}
				$this->deferred->notify ([
						'part' => $alias,
						'data' => $data
					]);
			});
		});

		return $this;
	}


	public function then (callable $callback)
	{
		$this->then_callback = $callback;

		return $this;
	}


	public function run ()
	{
		$this->deferred = new Deferred();
		$this->deferred->promise ()->progress (function ($event)
		{
			$this->data [$event ['part']] = $event ['data'];
			unset ($this->unsettled [$event ['part']]);

			if ($this->isEverythingHasBeenReceived ())
			{
				$this->deferred->resolve ();
			}

		})->then (function ()
		{
			$this->loop->cancelTimer ($this->cancel_timer);
		})
			->done (function ()
			{
				$response = call_user_func ($this->then_callback, $this->data);
				$headers = ['Content-Type' => 'text/plain'];

				$this->response->writeHead (200, $headers);
				$this->response->end ($response);
				posix_kill (posix_getpid (), SIGTERM);
			}, function ()
			{
				$headers = ['Content-Type' => 'text/plain'];
				$this->response->writeHead (404, $headers);
				$this->response->end ("Failed");
				posix_kill (posix_getpid (), SIGTERM);
			});

		$this->registerCancelTimer ();
		foreach ($this->requests as $request)
		{
			$request->end ();
		}

		return $this;
	}


	protected function isAllNeedsProvided ()
	{
		var_export($this->unsettled);
		return empty ($this->unsettled);
	}


	protected function isEverythingHasBeenReceived ()
	{
		return count ($this->requests) == count ($this->data);
	}


	protected function registerCancelTimer ()
	{
		$this->cancel_timer = $this->loop->addTimer (.5, function ()
		{
			if (false == $this->isAllNeedsProvided ())
			{
				$this->deferred->reject ();
			}
			echo "resolving by timer\n";
			$this->deferred->resolve ($this->response);
			//posix_kill (posix_getpid (), SIGTERM);
		});
	}
}


/**
 * @param $loop
 */
function registerSignalHandlers ($loop)
{
	$pcntl = new MKraemer\ReactPCNTL\PCNTL($loop);

	$pcntl->on (SIGTERM, function ()
	{
		echo 'Bye' . PHP_EOL;
		die();
	});

	$pcntl->on (SIGINT, function ()
	{
		echo 'Terminated by console' . PHP_EOL;
		die();
	});
}

$loop = React\EventLoop\Factory::create ();
registerSignalHandlers ($loop);
$socket = new React\Socket\Server($loop);
$http = new React\Http\Server($socket);

$loader = new Twig_Loader_Array([
	'index' => '
a = {{ a }}
b = {{ b }}
c = {{ c }}
{% if d %}
d = {{ d }}
{% endif %}
				',
]);
$twig = new Twig_Environment($loader);


$dnsResolverFactory = new React\Dns\Resolver\Factory();
$dnsResolver = $dnsResolverFactory->createCached ('127.0.0.1', $loop);

$factory = new React\HttpClient\Factory();
$client = $factory->create ($loop, $dnsResolver);

$http->on ('request', function ($request, $response) use ($loop, $twig, $client)
{
	$context = new Context($client , $loop, $response);
	$context->must ('http://dev.akond.net/react-php/micro-service.php?a', 'a')
		->must ('http://dev.akond.net/react-php/micro-service.php?b', 'b')
		->must ('http://dev.akond.net/react-php/micro-service.php?c', 'c')
		//->should ('http://dev.akond.net/react-php/micro-service-slow.php', 'd')
		->then (function ($data) use ($twig)
		{
			return 1;

			return $twig->render ('index', $data);
		})->run ();
});

$socket->listen (1337, '10.0.0.1');
$loop->run ();
