<?php
namespace Quark;

/**
 * Class Quark
 *
 * This package contains main functionality for Quark PHP framework
 *
 * @package Quark
 *
 * @version 1.0.1
 * @author Alex Furnica
 */
class Quark {
	const MODE_DEV = 'dev';
	const MODE_PRODUCTION = 'production';

	const LOG_OK = ' ok ';
	const LOG_INFO = 'info';
	const LOG_WARN = 'warn';
	const LOG_FATAL = 'fatal';

	const EVENT_ARCH_EXCEPTION = 'Quark.Exception.Arch';
	const EVENT_HTTP_EXCEPTION = 'Quark.Exception.HTTP';
	const EVENT_CONNECTION_EXCEPTION = 'Quark.Exception.Connection';
	const EVENT_COMMON_EXCEPTION = 'Quark.Exception.Common';

	const UNIT_BYTE = 1;
	const UNIT_KILOBYTE = 1024;
	const UNIT_MEGABYTE = 1048576;
	const UNIT_GIGABYTE = 1073741824;

	/**
	 * @var QuarkConfig
	 */
	private static $_config;

	/**
	 * @var array $_events
	 */
	private static $_events = array();

	/**
	 * @var string[] $_gUID
	 */
	private static $_gUID = array();

	/**
	 * @var string[] $_tUID
	 */
	private static $_tUID = array();

	/**
	 * @var string[] $_breaks
	 */
	private static $_breaks = array();

	/**
	 * @var IQuarkEnvironmentProvider[] $_environment
	 */
	private static $_environment = array();

	/**
	 * @var IQuarkStackable[] $_stack
	 */
	private static $_stack = array();

	/**
	 * @var IQuarkContainer[] $_containers
	 */
	private static $_containers = array();

	/**
	 * @var IQuarkTickable[] $_tick
	 */
	private static $_tick = array();

	/**
	 * @return bool
	 */
	public static function CLI () {
		return PHP_SAPI == 'cli';
	}

	/**
	 * @return QuarkConfig
	 */
	public static function Config () {
		if (self::$_config == null)
			self::$_config = new QuarkConfig();

		return self::$_config;
	}

	/**
	 * @param QuarkConfig $config
	 *
	 * @throws QuarkArchException
	 */
	public static function Run (QuarkConfig $config) {
		self::$_config = $config;
		self::$_tUID = self::GuID();

		$argc = isset($_SERVER['argc']) ? $_SERVER['argc'] : 0;
		$argv = isset($_SERVER['argv']) ? $_SERVER['argv'] : array();

		$threads = new QuarkThreadSet($argc, $argv);

		self::Environment(self::CLI()
			? new QuarkCLIEnvironmentProvider($argc, $argv)
			: new QuarkFPMEnvironmentProvider($argc, $argv)
		);

		$threads->Threads(self::$_environment);

		$after = function () {
			self::ContainerFree();
			self::$_tUID = self::GuID();

			gc_collect_cycles();
		};

		if (!self::CLI() || ($argc > 1 || $argc == 0)) $threads->Invoke($after);
		else $threads->Pipeline(self::$_config->Tick(), $after);
	}

	/**
	 * @param $host
	 *
	 * @return string
	 */
	public static function IP ($host) {
		return gethostbyname($host);
	}

	/**
	 * @return string
	 */
	public static function EntryPoint () {
		return $_SERVER['PHP_SELF'];
	}

	/**
	 * @param bool $endSlash = true
	 *
	 * @return string
	 */
	public static function Host ($endSlash = true) {
		return self::NormalizePath(getcwd(), $endSlash);
	}

	/**
	 * @param bool $full = true
	 *
	 * @return string
	 */
	public static function WebHost ($full = true) {
		return str_replace('?', '', self::$_config->WebHost()->URI($full));
	}

	/**
	 * @param string $path
	 * @param bool $endSlash = true
	 *
	 * @return string
	 */
	public static function NormalizePath ($path, $endSlash = true) {
		return is_scalar($path)
			? trim(preg_replace('#(/+)#', '/', str_replace('\\', '/', $path))
				. ($endSlash && (strlen($path) != 0 && $path[strlen($path) - 1] != '/') ? '/' : ''))
			: ($path instanceof QuarkFile ? $path->location : '');
	}

	/**
	 * @param string $path
	 * @param bool $endSlash = false
	 *
	 * @return string
	 */
	public static function SanitizePath ($path, $endSlash = false) {
		return self::NormalizePath(str_replace('./', '/', str_replace('../', '/', $path)), $endSlash);
	}

	/**
	 * @param $event
	 * @param $listener
	 * @param $unique
	 */
	public static function On ($event, $listener, $unique = false) {
		if (!isset(self::$_events[$event]))
			self::$_events[$event] = array();

		if ($unique)
			self::$_events[$event] = array();

		self::$_events[$event][] = $listener;
	}

	/**
	 * @param $event
	 * @param $listener
	 */
	public static function Off ($event, $listener) {
		if (!isset(self::$_events[$event])) return;

		$workers = array();

		foreach (self::$_events[$event] as $worker) {
			if ($worker == $listener) continue;

			$workers[] = $worker;
		}

		self::$_events[$event] = $workers;
	}

	/**
	 * @param string $event
	 *
	 * @return bool
	 */
	public static function Dispatch ($event) {
		if (!isset(self::$_events[$event])) return false;

		foreach (self::$_events[$event] as $worker)
			call_user_func_array($worker, array_slice(func_get_args(), 1));

		return true;
	}

	/**
	 * Global unique ID
	 *
	 * @param string $salt
	 *
	 * @return string
	 */
	public static function GuID ($salt = '') {
		$hash = sha1(rand(1, 1000) . QuarkDate::Now()->DateTime() . rand(1000, 1000000) . $salt);

		if (in_array($hash, self::$_gUID, true)) return self::GuID($salt);

		self::$_gUID[] = $hash;
		return $hash;
	}

	/**
	 * Tick ID
	 */
	public static function TuID () {
		return self::$_tUID;
	}

	/**
	 * @param IQuarkEnvironmentProvider $provider
	 *
	 * @return IQuarkEnvironmentProvider[]
	 */
	public static function Environment (IQuarkEnvironmentProvider $provider = null) {
		if ($provider) {
			if (!$provider->Multiple())
				foreach (self::$_environment as $environment)
					if ($environment instanceof $provider) return self::$_environment;

			if ($provider->UsageCriteria())
				self::$_environment[] = $provider;
		}

		return self::$_environment;
	}

	/**
	 * @param string $name
	 * @param IQuarkStackable $component
	 *
	 * @return IQuarkStackable|null
	 */
	public static function Component ($name, $component = null) {
		if (!$component)
			return self::Stack($name);

		try {
			return self::Stack($name, $component);
		}
		catch (\Exception $e) {
			Quark::Log('Unable to config \'' . $name . '\'', Quark::LOG_FATAL);
		}

		return null;
	}

	/**
	 * @param string $name
	 * @param IQuarkStackable $object
	 *
	 * @return IQuarkStackable|null
	 */
	public static function Stack ($name, IQuarkStackable $object = null) {
		if (func_num_args() == 2)
			self::$_stack[$name] = $object;

		return isset(self::$_stack[$name]) ? self::$_stack[$name] : null;
	}

	/**
	 * @param IQuarkStackable $type
	 *
	 * @return IQuarkStackable[]
	 */
	public static function StackOf (IQuarkStackable $type) {
		$out = array();

		foreach (self::$_stack as $object)
			if ($object instanceof $type)
				$out[] = $object;

		return $out;
	}

	/**
	 * @param IQuarkContainer $container
	 */
	public static function Container (IQuarkContainer $container) {
		self::$_containers[] = $container;
	}

	/**
	 * @param IQuarkPrimitive $primitive
	 *
	 * @return IQuarkContainer|null
	 */
	public static function ContainerOf (IQuarkPrimitive $primitive) {
		$class = get_class($primitive);

		foreach (self::$_containers as $container) {
			if (!$primitive || get_class($container->Primitive()) != $class) continue;

			$container->Primitive($primitive);
			return $container;
		}

		return null;
	}

	/**
	 * Free associated containers
	 */
	public static function ContainerFree () {
		self::$_containers = array();
	}

	/**
	 * @param string $name
	 * @param IQuarkTickable $object
	 *
	 * @return IQuarkTickable
	 */
	public static function Tickable ($name, IQuarkTickable $object = null) {
		if (func_num_args() == 2 && (!isset(self::$_tick[$name]) || !self::$_tick[$name]->Exclusive()))
			self::$_tick[$name] = $object;

		return isset(self::$_tick[$name]) ? self::$_tick[$name] : null;
	}

	/**
	 * @param string $path
	 * @param callable $process
	 *
	 * @return bool
	 */
	public static function Import ($path, callable $process = null) {
		if (!is_string($path)) return false;

		spl_autoload_register(function ($class) use ($path, $process) {
			if ($process != null)
				$class = $process($class);

			$file = Quark::NormalizePath($path . '/' . $class . '.php', false);

			if (file_exists($file))
				/** @noinspection PhpIncludeInspection */
				include_once $file;
		});

		return true;
	}

	/**
	 * @param string $message
	 * @param string $lvl
	 * @param string $domain = 'application'
	 *
	 * @return int|bool
	 */
	public static function Log ($message, $lvl = self::LOG_INFO, $domain = 'application') {
		$logs = self::NormalizePath(self::Host() . '/' . self::Config()->Location(QuarkConfig::RUNTIME) . '/');

		if (!is_dir($logs)) mkdir($logs);

		return file_put_contents(
			$logs . $domain . '.log',
			'[' . $lvl . '] ' . QuarkDate::Now() . ' ' . $message . "\r\n",
			FILE_APPEND | LOCK_EX
		);
	}

	/**
	 * @param mixed $needle
	 * @param string $domain = 'application'
	 *
	 * @return int|bool
	 */
	public static function Trace ($needle, $domain = 'application') {
		return self::Log('[' . gettype($needle) . '] ' . print_r($needle, true), self::LOG_INFO, $domain);
	}

	/**
	 * @param string $branch = 'main'
	 * @param string $domain = 'application'
	 *
	 * @return int|bool
	 */
	public static function BreakPoint ($branch = 'main', $domain = 'application') {
		self::$_breaks[$branch] = isset(self::$_breaks[$branch]) ? ++self::$_breaks[$branch] : 0;

		return self::Log('[TRACE ' . $branch . ':' . self::$_breaks[$branch] . ']', self::LOG_INFO, $domain);
	}

	/**
	 * @param int $unit = self::UNIT_KILOBYTE
	 * @param int $precision = 3
	 *
	 * @return string
	 */
	public static function MemoryUsage ($unit = self::UNIT_KILOBYTE, $precision = 2) {
		$str = self::MemoryUnit($unit);

		return "[Quark] Memory usage:\r\n" .
			' - current:      ' . round(\memory_get_usage() / $unit, $precision) . $str . "\r\n" .
			' - current.real: ' . round(\memory_get_usage(true) / $unit, $precision) . $str . "\r\n" .
			' - peak:         ' . round(\memory_get_peak_usage() / $unit, $precision) . $str . "\r\n" .
			' - peak.real:    ' . round(\memory_get_peak_usage(true) / $unit, $precision) . $str . "\r\n";
	}

	/**
	 * @param $value
	 *
	 * @return string
	 */
	public static function MemoryUnit ($value) {
		switch ($value) {
			case self::UNIT_BYTE: return 'B'; break;
			case self::UNIT_KILOBYTE: return 'KB'; break;
			case self::UNIT_MEGABYTE: return 'MB'; break;
			case self::UNIT_GIGABYTE: return 'GB'; break;
		}

		return '-';
	}
}

spl_autoload_extensions('.php');

Quark::Import(__DIR__, function ($class) { return substr($class, 6); });
Quark::Import(Quark::Host());

/**
 * Class QuarkConfig
 *
 * @package Quark
 */
class QuarkConfig {
	const SERVICES = 'services';
	const VIEWS = 'views';
	const RUNTIME = 'runtime';

	/**
	 * @var IQuarkCulture
	 */
	private $_culture;

	/**
	 * @var int $_alloc = 5 (megabytes)
	 */
	private $_alloc = 5;

	/**
	 * @var int $_tick = 10000 (microseconds)
	 */
	private $_tick = QuarkThreadSet::TICK;

	/**
	 * @var string $_mode = Quark::MODE_DEV
	 */
	private $_mode = Quark::MODE_DEV;

	/**
	 * @var array
	 */
	private $_location = array(
		self::SERVICES => 'Services',
		self::VIEWS => 'Views',
		self::RUNTIME => 'runtime',
	);

	/**
	 * @var QuarkURI $_webHost
	 */
	private $_webHost;

	/**
	 * @var QuarkURI|string $_cluster
	 */
	private $_cluster = QuarkStreamEnvironmentProvider::URI_CONTROLLER_INTERNAL;

	/**
	 * @param string $mode
	 */
	public function __construct ($mode = Quark::MODE_DEV) {
		$this->_mode = $mode;
		$this->_culture = new QuarkCultureISO();
		$this->_webHost = new QuarkURI();
		$this->_cluster = new QuarkURI();

		if (isset($_SERVER['SERVER_PROTOCOL']))
			$this->_webHost->scheme = $_SERVER['SERVER_PROTOCOL'];

		if (isset($_SERVER['SERVER_NAME']))
			$this->_webHost->host = $_SERVER['SERVER_NAME'];

		if (isset($_SERVER['SERVER_PORT']))
			$this->_webHost->port = $_SERVER['SERVER_PORT'];
	}

	/**
	 * @param IQuarkCulture $culture
	 *
	 * @return IQuarkCulture|QuarkCultureISO
	 */
	public function Culture (IQuarkCulture $culture = null) {
		return $this->_culture = ($culture === null) ? $this->_culture : $culture;
	}

	/**
	 * @param int $mb = 5 (megabytes)
	 *
	 * @return int
	 */
	public function Alloc ($mb = 5) {
		if (func_num_args() != 0)
			$this->_alloc = $mb;

		return $this->_alloc;
	}

	/**
	 * @param int $ms = 10000 (microseconds)
	 *
	 * @return int
	 */
	public function Tick ($ms = QuarkThreadSet::TICK) {
		if (func_num_args() != 0)
			$this->_tick = $ms;

		return $this->_tick;
	}

	/**
	 * @param string $mode = Quark::MODE_DEV
	 * @return string
	 */
	public function Mode ($mode = Quark::MODE_DEV) {
		if (func_num_args() != 0)
			$this->_mode = $mode;

		return $this->_mode;
	}

	/**
	 * @param string $name
	 * @param IQuarkAuthorizationProvider $provider
	 * @param IQuarkAuthorizableModel $user
	 *
	 * @return QuarkSession
	 */
	public function AuthorizationProvider ($name, IQuarkAuthorizationProvider $provider = null, IQuarkAuthorizableModel $user = null) {
		return Quark::Component($name, func_num_args() != 3 ? null : new QuarkSessionSource($name, $provider, $user));
	}

	/**
	 * @param string $name
	 * @param IQuarkDataProvider $provider
	 * @param QuarkURI $uri
	 *
	 * @return QuarkModelSource
	 */
	public function DataProvider ($name, IQuarkDataProvider $provider = null, QuarkURI $uri = null) {
		return Quark::Component($name, func_num_args() == 1 ? null : new QuarkModelSource($name, $provider, $uri));
	}

	/**
	 * @param string $name
	 * @param IQuarkExtensionConfig $config
	 *
	 * @return IQuarkExtensionConfig
	 */
	public function Extension ($name, IQuarkExtensionConfig $config = null) {
		return Quark::Component($name, $config);
	}

	/**
	 * @param IQuarkEnvironmentProvider $provider
	 *
	 * @return IQuarkEnvironmentProvider[]
	 */
	public function Environment (IQuarkEnvironmentProvider $provider = null) {
		return Quark::Environment($provider);
	}

	/**
	 * @param string $component
	 * @param string $location
	 *
	 * @return string
	 */
	public function Location ($component, $location = '') {
		if (func_num_args() == 2)
			$this->_location[$component] = $location;

		return isset($this->_location[$component]) ? $this->_location[$component] : '';
	}

	/**
	 * @param QuarkURI|string $uri
	 *
	 * @return QuarkURI
	 */
	public function WebHost ($uri = '') {
		if (func_num_args() != 0)
			$this->_webHost = QuarkURI::FromURI($uri);

		return $this->_webHost;
	}

	/**
	 * @param QuarkURI|string $uri
	 *
	 * @return QuarkURI
	 */
	public function ClusterController ($uri = '') {
		if (func_num_args() != 0)
			$this->_cluster = QuarkURI::FromURI($uri);

		return $this->_cluster;
	}
}

/**
 * Interface IQuarkStackable
 *
 * @package Quark
 */
interface IQuarkStackable {
	/**
	 * @return string
	 */
	public function Name();
}

/**
 * Interface IQuarkTickable
 *
 * @package Quark
 */
interface IQuarkTickable {
	/**
	 * @return bool
	 */
	public function Exclusive();
}

/**
 * Interface IQuarkEnvironmentProvider
 *
 * @package Quark
 */
interface IQuarkEnvironmentProvider extends IQuarkThread {
	/**
	 * @return bool
	 */
	public function Multiple();

	/**
	 * @return bool
	 */
	public function UsageCriteria();
}

/**
 * Class QuarkFPMEnvironmentProvider
 *
 * @package Quark
 */
class QuarkFPMEnvironmentProvider implements IQuarkEnvironmentProvider {
	const PROCESSOR_REQUEST = '_processorRequest';
	const PROCESSOR_RESPONSE = '_processorResponse';
	const PROCESSOR_BOTH = '_processorBoth';

	private $_statusNotFound = QuarkDTO::STATUS_404_NOT_FOUND;
	private $_statusServerError = QuarkDTO::STATUS_500_SERVER_ERROR;

	/**
	 * @var IQuarkIOProcessor
	 */
	private $_processorRequest = null;
	private $_processorResponse = null;
	private $_processorBoth = null;

	/**
	 * @return bool
	 */
	public function Multiple () { return false; }

	/**
	 * @return bool
	 */
	public function UsageCriteria () {
		return !Quark::CLI();
	}

	/**
	 * @param string $status
	 *
	 * @return string
	 */
	public function DefaultNotFoundStatus ($status = '') {
		if (func_num_args() == 1)
			$this->_statusNotFound = $status;

		return $this->_statusNotFound;
	}

	/**
	 * @param string $status
	 *
	 * @return string
	 */
	public function DefaultServerErrorStatus ($status = '') {
		if (func_num_args() == 1)
			$this->_statusServerError = $status;

		return $this->_statusServerError;
	}

	/**
	 * @param string $direction
	 * @param IQuarkIOProcessor $processor
	 *
	 * @return IQuarkIOProcessor
	 */
	public function Processor ($direction, IQuarkIOProcessor $processor = null) {
		if (func_num_args() == 2) {
			if ($direction != self::PROCESSOR_BOTH) $this->$direction = $processor;
			else {
				$this->_processorRequest = $processor;
				$this->_processorResponse = $processor;
				$this->_processorBoth = $processor;
			}
		}

		return is_string($direction) ? $this->$direction : null;
	}

	/**
	 * @return mixed
	 */
	public function Thread () {
		$service = new QuarkService(
			$_SERVER['REQUEST_URI'],
			$this->_processorRequest,
			$this->_processorResponse
		);

		$uri = QuarkURI::FromURI($_SERVER['REQUEST_URI']);
		$service->Input()->URI($uri);
		$service->Output()->URI($uri);

		$remote = QuarkURI::FromEndpoint($_SERVER['REMOTE_ADDR'], $_SERVER['REMOTE_PORT']);
		$service->Input()->Remote($remote);
		$service->Output()->Remote($remote);

		if ($service->Service() instanceof IQuarkServiceWithAccessControl)
			$service->Output()->Header(QuarkDTO::HEADER_ALLOW_ORIGIN, $service->Service()->AllowOrigin());

		$headers = array();

		if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']))
			$_SERVER['HTTP_AUTHORIZATION'] = QuarkDTO::HTTPBasicAuthorization($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);

		foreach ($_SERVER as $name => $value) {
			$authType = '';
			$authBasic = 0;
			$authDigest = 0;

			$name = str_replace('CONTENT_', 'HTTP_CONTENT_', $name);
			$name = str_replace('PHP_AUTH_DIGEST', 'HTTP_AUTHORIZATION', $name, $authDigest);

			if ($authBasic != 0)
				$authType = 'Basic ';

			if ($authDigest != 0)
				$authType = 'Digest ';

			if (substr($name, 0, 5) == 'HTTP_')
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = ($name == 'HTTP_AUTHORIZATION' ? $authType : '') . $value;
		}

		$output = null;

		ob_start();

		$service->Input()->Method(ucfirst(strtolower($_SERVER['REQUEST_METHOD'])));
		$service->Input()->Headers($headers);

		$input = array_replace_recursive(
			$_GET,
			$_POST,
			QuarkFile::FromFiles($_FILES),
			(array)json_decode(json_encode($service->Input()->Processor()->Decode(file_get_contents('php://input'))), true),
			array()
		);

		$service->Input()->Merge((object)$input);

		if (isset($_POST[$service->Input()->Processor()->MimeType()]))
			$service->Input()->Merge($service->Input()->Processor()->Decode($_POST[$service->Input()->Processor()->MimeType()]));

		$service->Pipe(true);

		echo $ok = $service->Output()->SerializeResponseBody();

		$service->Output()->Header(QuarkDTO::HEADER_CONTENT_LENGTH, ob_get_length());
		$headers = $service->Output()->SerializeResponseHeadersToArray();

		foreach ($headers as $header)
			header($header);

		ob_end_flush();

		return true;
	}

	/**
	 * @param \Exception $exception
	 *
	 * @return mixed
	 */
	public function ExceptionHandler (\Exception $exception) {
		if ($exception instanceof QuarkArchException)
			return $this->_status($exception, $this->_statusServerError);

		if ($exception instanceof QuarkConnectionException)
			return $this->_status($exception, $this->_statusServerError);

		if ($exception instanceof QuarkHTTPException)
			return $this->_status($exception, $this->_statusNotFound);

		if ($exception instanceof \Exception)
			return Quark::Log('Common exception: ' . $exception->getMessage() . "\r\n at " . $exception->getFile() . ':' . $exception->getLine(), Quark::LOG_FATAL);

		return true;
	}

	/**
	 * @param QuarkException $exception
	 * @param string $status
	 *
	 * @return bool|int
	 */
	private function _status ($exception, $status) {
		ob_start();
		header($_SERVER['SERVER_PROTOCOL'] . ' ' . $status);
		ob_end_flush();

		return Quark::Log('[' . $_SERVER['REQUEST_URI'] . '] ' . $exception->message , $exception->lvl);
	}
}

/**
 * Class QuarkCLIEnvironmentProvider
 *
 * @package Quark
 */
class QuarkCLIEnvironmentProvider implements IQuarkEnvironmentProvider {
	/**
	 * @var QuarkTask[] $_tasks
	 */
	private $_tasks = array();

	/**
	 * @param int   $argc = 0
	 * @param array $argv = []
	 */
	public function __construct ($argc = 0, $argv = []) {
		if (!Quark::CLI() || $argc > 1) return;

		$dir = new \RecursiveDirectoryIterator(Quark::Host());
		$fs = new \RecursiveIteratorIterator($dir);

		foreach ($fs as $file) {
			/**
			 * @var \FilesystemIterator $file
			 */

			if ($file->isDir() || !strstr($file->getFilename(), 'Service.php')) continue;

			$class = QuarkObject::ClassIn($file->getPathname());

			/**
			 * @var IQuarkService $service
			 */
			$service = new $class();

			if ($service instanceof IQuarkScheduledTask)
				$this->_tasks[] = new QuarkTask($service);

			unset($service);
		}
	}

	/**
	 * @return bool
	 */
	public function Multiple () { return false; }

	/**
	 * @return bool
	 */
	public function UsageCriteria () {
		return Quark::CLI();
	}

	/**
	 * @param int $argc
	 * @param array $argv
	 *
	 * @return mixed
	 *
	 * @throws QuarkArchException
	 * @throws QuarkHTTPException
	 */
	public function Thread ($argc = 0, $argv = []) {
		if ($argc > 1) {
			if ($argv[1] == QuarkTask::PREDEFINED) {
				if (!isset($argv[2]))
					throw new QuarkArchException('Predefined scenario not selected');

				$class = '\\Quark\\Scenarios\\' . $argv[2];

				if (!class_exists($class))
					throw new QuarkArchException('Unknown predefined scenario ' . $class);

				$service = new $class();
			}
			else $service = (new QuarkService('/' . $argv[1]))->Service();

			if (!($service instanceof IQuarkTask))
				throw new QuarkArchException('Class ' . get_class($service) . ' is not an IQuarkTask');

			$service->Task($argc, $argv);
		}
		else {
			foreach ($this->_tasks as $task)
				$task->Launch($argc, $argv);
		}
	}

	/**
	 * @param \Exception $exception
	 *
	 * @return mixed
	 */
	public function ExceptionHandler (\Exception $exception) {
		return QuarkException::ExceptionHandler($exception);
	}
}

/**
 * Interface IQuarkTransportProvider
 *
 * @package Quark
 */
interface IQuarkTransportProvider {
	/**
	 * @param QuarkClient $client
	 *
	 * @return bool
	 */
	public function OnConnect(QuarkClient $client);

	/**
	 * @param QuarkClient $client
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function OnData(QuarkClient $client, $data);

	/**
	 * @param QuarkClient $client
	 *
	 * @return mixed
	 */
	public function OnClose(QuarkClient $client);
}

/**
 * Interface IQuarkIntermediateTransportProvider
 *
 * @package Quark
 */
interface IQuarkIntermediateTransportProvider {
	/**
	 * @param IQuarkTransportProvider $protocol
	 *
	 * @return IQuarkTransportProvider
	 */
	public function Protocol (IQuarkTransportProvider $protocol);
}

/**
 * Class QuarkStreamEnvironmentProvider
 *
 * @package Quark
 */
class QuarkStreamEnvironmentProvider implements IQuarkEnvironmentProvider, IQuarkClusterNode, IQuarkClusterController {
	const URI_NODE_INTERNAL = QuarkServer::TCP_ALL_INTERFACES_RANDOM_PORT;
	const URI_NODE_EXTERNAL = 'ws://0.0.0.0:25000';
	const URI_CONTROLLER_INTERNAL = 'tcp://0.0.0.0:25800';
	const URI_CONTROLLER_EXTERNAL = 'ws://0.0.0.0:25900';

	const EVENT_BROADCAST = 'event.broadcast';
	const EVENT_EVENT = 'event.event';

	/**
	 * @var string $_connect
	 */
	private $_connect;

	/**
	 * @var string $_close
	 */
	private $_close;

	/**
	 * @var string $_unknown
	 */
	private $_unknown;

	/**
	 * @var QuarkClusterNode $_cluster
	 */
	private $_cluster;

	/**
	 * @var QuarkClusterController $_controller
	 */
	private $_controller;

	/**
	 * @var QuarkDTO $_dto
	 */
	private $_dto;

	/**
	 * @param IQuarkTransportProvider $transport
	 * @param QuarkURI|string $external = self::URI_NODE_EXTERNAL
	 * @param QuarkURI|string $internal = self::URI_NODE_INTERNAL
	 * @param string $connect = ''
	 * @param string $close = ''
	 * @param string $unknown = ''
	 */
	public function __construct (IQuarkTransportProvider $transport = null, $external = self::URI_NODE_EXTERNAL, $internal = self::URI_NODE_INTERNAL, $connect = '', $close = '', $unknown = '') {
		$this->_dto = new QuarkDTO(new QuarkJSONIOProcessor());

		Quark::On(self::EVENT_BROADCAST, function ($http, $data, $url) {
			$payload = array(
				'url' => $url,
				'data' => $data instanceof QuarkDTO ? $data->Data() : $data
			);

			if ($http) self::ControllerCommand('broadcast', $payload);
			else $this->_cluster->Broadcast($this->_pack($payload));
		}, true);

		if (func_num_args() == 0 || !Quark::CLI() || $_SERVER['argc'] > 1) return;

		$this->_cluster = new QuarkClusterNode($this, $transport, $external, $internal);

		$this->StreamConnect($connect);
		$this->StreamClose($close);
		$this->StreamUnknown($unknown);

		Quark::On(self::EVENT_EVENT, function ($sender, $url) {
			$clients = $this->_cluster->Server()->Clients();

			foreach ($clients as $client) {
				$session = QuarkSession::Restore($client->Session());

				$out = $sender($session);

				if (!$out) continue;

				$out = array(
					'event' => $url,
					'data' => $out instanceof QuarkDTO ? $out->Data() : $out
				);

				if ($session->Authorized())
					$out['session'] = $session->ID()->Extract();

				$client->Send($this->_pack($out));
				$session->Output();
			}

			unset($out, $session, $client, $clients);
		});
	}

	/**
	 * @return bool
	 */
	public function Multiple () { return true; }

	/**
	 * @return bool
	 */
	public function UsageCriteria () {
		return Quark::CLI() && $_SERVER['argc'] == 1;
	}

	/**
	 * @param string $uri
	 *
	 * @return string
	 */
	public function StreamConnect ($uri = '') {
		if (func_num_args() != 0)
			$this->_connect = $uri;

		return $this->_connect;
	}

	/**
	 * @param string $uri
	 *
	 * @return string
	 */
	public function StreamClose ($uri = '') {
		if (func_num_args() != 0)
			$this->_close = $uri;

		return $this->_close;
	}

	/**
	 * @param string $uri
	 *
	 * @return string
	 */
	public function StreamUnknown ($uri = '') {
		if (func_num_args() != 0)
			$this->_unknown = $uri;

		return $this->_unknown;
	}

	/**
	 * @return mixed
	 */
	public function Thread () {
		if (!$this->_cluster) return true;

		if (!$this->_cluster->Controller()->Connected())
			$this->_cluster->Controller()->URI(QuarkURI::FromURI(Quark::Config()->ClusterController()));

		return $this->_cluster->Pipe();
	}

	/**
	 * @param \Exception $exception
	 *
	 * @return mixed
	 */
	public function ExceptionHandler (\Exception $exception) {
		return QuarkException::ExceptionHandler($exception);
	}

	/**
	 * @param QuarkClient $controller
	 *
	 * @return mixed
	 */
	public function ControllerConnect (QuarkClient $controller) {
		echo '[cluster.node.controller.connect] ', $controller->ConnectionURI(), ' ', $controller->ConnectionURI(true), "\r\n";

		$internal = $this->_cluster->Network()->Server()->ConnectionURI();
		$internal->host = Quark::IP(null);

		$external = $this->_cluster->Server()->URI();
		$external->host = Quark::IP(null);

		$this->_cmd('state', array(
			'internal' => $internal->URI(),
			'external' => $external->URI()
		));
	}

	/**
	 * @param QuarkClient $controller
	 * @param string $data
	 *
	 * @return mixed
	 * @throws QuarkArchException
	 */
	public function ControllerData (QuarkClient $controller, $data) {
		$this->_dto->BatchUnserialize($data, function ($json) use ($controller) {
			if (isset($json->event)) {
				if ($json->event == 'nodes' && isset($json->data)) {
					if (is_array($json->data)) {
						foreach ($json->data as $node) {
							if (!isset($node->internal)) continue;

							$this->_cluster->Node($node->internal);
						}
					}
				}

				if ($json->event == 'broadcast' && isset($json->data))
					$this->NodeData($controller, json_encode(array(
						'url' => $json->data->url,
						'data' => $json->data->data
					)));
			}

			// TODO: controller stream
		});
	}

	/**
	 * @param QuarkClient $controller
	 *
	 * @return mixed
	 */
	public function ControllerClose (QuarkClient $controller) {
		echo '[cluster.node.controller.close] ', $controller->ConnectionURI(),"\r\n";
	}

	/**
	 * @param QuarkClient $node
	 *
	 * @return mixed
	 */
	public function NodeConnect (QuarkClient $node) {
		echo '[cluster.node.node.connect] ', $this->_cluster->Network()->Server()->ConnectionURI(), ' ', "\r\n";

		$peers = array();
		$network = $this->_cluster->Nodes();

		foreach ($network as $peer) {
			$uri = $peer->ConnectionURI(true);

			if ($uri)
				$peers[] = $uri->URI();
		}

		$this->_cmd('state', array('peers' => $peers));
	}

	/**
	 * @param QuarkClient $node
	 * @param $data
	 *
	 * @return mixed
	 *
	 * @throws QuarkArchException
	 * @throws QuarkHTTPException
	 */
	public function NodeData (QuarkClient $node, $data) {
		$json = json_decode($data);
		$endpoint = $node->URI()->URI();

		if (!$json)
			throw new QuarkArchException('Node ' . $endpoint . ' sent invalid json: ' . $data . '. ' . json_last_error_msg(), Quark::LOG_WARN);

		if (!isset($json->url))
			throw new QuarkArchException('Node ' . $endpoint . ' sent unknown url', Quark::LOG_WARN);

		try {
			$this->_pipe(null, $json, 'StreamNetwork', function (QuarkService $service) {
				return array(
					$service->Input(),
					$this->_cluster
				);
			}, false);
		}
		catch (QuarkHTTPException $e) {
			$this->_pipe(null, array('url' => $this->_unknown), 'StreamUnknown');
		}
	}

	/**
	 * @param QuarkClient $node
	 *
	 * @return mixed
	 */
	public function NodeClose (QuarkClient $node) {
		echo "[cluster.node.node.close]\r\n";
	}

	/**
	 * @param QuarkClient $client
	 *
	 * @return mixed
	 * @throws QuarkArchException
	 */
	public function ClientConnect (QuarkClient $client) {
		echo '[cluster.node.client.connect] ', $client->ConnectionURI(true), "\r\n";

		$this->_cmd('state', array(
			'clients' => sizeof($this->_cluster->Server()->Clients())
		));

		$this->_pipe($client, array('url' => $this->_connect), 'StreamConnect', function (QuarkService $service) {
			return array(
				$service->Session(),
				$this->_cluster
			);
		});
	}

	/**
	 * @param QuarkClient $client
	 * @param $data
	 *
	 * @return mixed
	 *
	 * @throws QuarkArchException
	 * @throws QuarkHTTPException
	 */
	public function ClientData (QuarkClient $client, $data) {
		if (strlen($data) == 0) return;

		$json = json_decode($data);
		$endpoint = $client->URI()->URI();

		if (!$json)
			throw new QuarkArchException('Client ' . $endpoint . ' sent invalid json: ' . $data . '. ' . json_last_error_msg(), Quark::LOG_WARN);

		if (!isset($json->url))
			throw new QuarkArchException('Client ' . $endpoint . ' sent unknown url', Quark::LOG_WARN);

		try {
			$this->_pipe($client, $json);
		}
		catch (QuarkHTTPException $e) {
			$this->_pipe($client, array('url' => $this->_unknown), 'StreamUnknown');
		}
	}

	/**
	 * @param QuarkClient $client
	 *
	 * @return mixed
	 * @throws QuarkArchException
	 */
	public function ClientClose (QuarkClient $client) {
		echo "[cluster.node.client.close]\r\n";

		$this->_cmd('state', array(
			'clients' => sizeof($this->_cluster->Server()->Clients())
		));

		$this->_pipe($client, array('url' => $this->_close), 'StreamClose', function (QuarkService $service) {
			return array(
				$service->Session(),
				$this->_cluster
			);
		});
	}

	/**
	 * @param QuarkClient $client
	 * @param $json
	 * @param string $method = 'Stream'
	 * @param callable(QuarkService $service) $args
	 * @param bool $auth = true
	 *
	 * @throws QuarkArchException
	 */
	private function _pipe (QuarkClient $client = null, $json = [], $method = 'Stream', callable $args = null, $auth = true) {
		$json = (object)$json;

		$service = new QuarkService($json->url, new QuarkJSONIOProcessor(), new QuarkJSONIOProcessor(), false);

		if ($client)
			$service->Input()->Remote($client->URI());

		if (isset($json->data))
			$service->Input()->Data($json->data);

		if (isset($json->session) && QuarkObject::isAssociative($json->session)) {
			$session = (object)each($json->session);
			$provider = new QuarkKeyValuePair($session->key, $session->value);

			$service->Input()->AuthorizationProvider($provider);
		}

		$out = $service->Pipe(
			false,
			$method,
			$auth,
			function (QuarkService $service) use ($client, $args) {
				if ($client instanceof QuarkClient)
					$client->Session($service->Session()->ID());

				return $args ? $args($service) : $service->Arguments(array($this->_cluster));
			})
			->Output()
			->Data();

		$session = $service->Session()->ID();

		//if ($client instanceof QuarkClient && $service->Session()->Authorized())
			//$client->Session($session);

		if ($out) {
			$output = array(
				'response' => $service->URL(),
				'data' => $out
			);

			if ($session != null)
				$output['session'] = $session->Extract();

			if ($client)
				$client->Send($service->Output()->Processor()->Encode($output));
		}

		unset($out, $output, $session, $provider, $json, $service);
	}

	/**
	 * @param string $name
	 * @param array $data
	 */
	private function _cmd ($name = '', $data = []) {
		$this->_cluster->Control($this->_pack(array(
			'cmd' => $name,
			'data' => $data
		)));
	}

	/**
	 * @param string $name
	 * @param string $data
	 *
	 * @return bool
	 */
	public static function ControllerCommand ($name = '', $data = '') {
		$client = new QuarkClient(Quark::Config()->ClusterController());

		$client->OnConnect(function (QuarkClient $client) use ($name, $data) {
			$client->Send(json_encode(array(
				'cmd' => $name,
				'data' => $data
			)));
		});

		return $client->Connect();
	}

	/**
	 * @param IQuarkTransportProvider $terminal
	 * @param string $external
	 * @param string $internal
	 *
	 * @return bool
	 */
	public function ClusterController (IQuarkTransportProvider $terminal, $external = self::URI_CONTROLLER_EXTERNAL, $internal = self::URI_CONTROLLER_INTERNAL) {
		$this->_controller = new QuarkClusterController($this, $terminal, $external, $internal);

		if (!$this->_controller->Bind()) return false;

		QuarkThreadSet::Queue(function () {
			$this->_controller->Pipe();
		});

		return true;
	}

	/**
	 * @param QuarkClient $node
	 *
	 * @return mixed
	 */
	public function NodeClientConnect (QuarkClient $node) {
		echo '[cluster.controller.node.connect] ' . $node->ConnectionURI(true),"\r\n";
		/**
		 * @var \StdClass $node
		 */
		$node->state = new \StdClass();
	}

	/**
	 * @param QuarkClient $node
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function NodeClientData (QuarkClient $node, $data) {
		$this->_dto->BatchUnserialize($data, function ($json) use ($node) {
			if (!isset($json->cmd)) return;

			/**
			 * @var \StdClass $node
			 */
			if ($json->cmd == 'state') {
				if (isset($json->data->internal))
					$node->state->internal = (string)$json->data->internal;

				if (isset($json->data->external))
					$node->state->external = (string)$json->data->external;

				if (isset($json->data->clients))
					$node->state->clients = (int)$json->data->clients;

				if (isset($json->data->peers))
					$node->state->peers = (array)$json->data->peers;

				$this->_event('nodes', $this->_nodes());
				$this->_infrastructure();
			}

			if ($json->cmd == 'broadcast' && isset($json->data))
				$this->_event('broadcast', $json->data);
		});
	}

	/**
	 * @param QuarkClient $node
	 *
	 * @return mixed
	 */
	public function NodeClientClose (QuarkClient $node) {
		echo '[cluster.controller.node.close] ' . $node->ConnectionURI(true),"\r\n";
		$this->_infrastructure();
	}

	/**
	 * @param QuarkClient $terminal
	 *
	 * @return mixed
	 */
	public function TerminalConnect (QuarkClient $terminal) {
		echo '[cluster.controller.terminal.connect] ' . $terminal->ConnectionURI(),"\r\n";

		$this->_infrastructure();
	}

	/**
	 * @param QuarkClient $terminal
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function TerminalData (QuarkClient $terminal, $data) {
		// TODO: Implement TerminalData() method.
	}

	/**
	 * @param QuarkClient $terminal
	 *
	 * @return mixed
	 */
	public function TerminalClose (QuarkClient $terminal) {
		echo '[cluster.controller.terminal.close] ' . $terminal->ConnectionURI(),"\r\n";
	}

	/**
	 * @param array $data
	 *
	 * @return string
	 */
	private function _pack ($data = []) {
		$this->_dto->Raw('');
		$this->_dto->Data($data);

		return $this->_dto->SerializeResponseBody();
	}

	/**
	 * @param $name
	 * @param array $data
	 *
	 * @return bool
	 */
	private function _event ($name, $data = []) {
		return $this->_controller->Broadcast($this->_pack(array(
			'event' => $name,
			'data' => $data
		)));
	}

	/**
	 * @return array
	 */
	private function _nodes () {
		$nodes = $this->_controller->Network()->Clients();
		$out = array();

		foreach ($nodes as $node) {
			/**
			 * @var \StdClass $node
			 */
			$out[] = $node->state;
		}

		return $out;
	}

	/**
	 * @return bool
	 */
	private function _infrastructure () {
		$terminals = $this->_controller->Terminal()->Clients();
		$out = true;

		foreach ($terminals as $terminal)
			$out &= $terminal->Send($this->_pack(array(
				'event' => 'nodes',
				'data' => $this->_nodes()
			)));

		return $out;
	}
}

/**
 * Interface IQuarkExtension
 *
 * @package Quark
 */
interface IQuarkExtension { }

/**
 * Interface IQuarkExtensionConfig
 *
 * @package Quark
 */
interface IQuarkExtensionConfig extends IQuarkStackable { }

/**
 * Interface IQuarkAuthorizableLiteService
 *
 * @package Quark
 */
interface IQuarkAuthorizableLiteService {
	/**
	 * @param QuarkDTO $request
	 *
	 * @return string
	 */
	public function AuthorizationProvider(QuarkDTO $request);
}

/**
 * Interface IQuarkAuthorizableService
 *
 * @package Quark
 */
interface IQuarkAuthorizableService extends IQuarkAuthorizableLiteService {
	/**
	 * @param QuarkDTO $request
	 * @param QuarkSession $session
	 *
	 * @return bool|mixed
	 */
	public function AuthorizationCriteria(QuarkDTO $request, QuarkSession $session);

	/**
	 * @param QuarkDTO $request
	 * @param $criteria
	 *
	 * @return mixed
	 */
	public function AuthorizationFailed(QuarkDTO $request, $criteria);
}

/**
 * Interface IQuarkAuthorizableModelWithSessionKey
 *
 * @package Quark
 */
interface IQuarkAuthorizableModelWithSessionKey {
	/**
	 * @return string
	 */
	public function SessionKey();
}

/**
 * Interface IQuarkService
 *
 * @package Quark
 */
interface IQuarkService extends IQuarkPrimitive { }

/**
 * Interface IQuarkAnyService
 *
 * @package Quark
 */
interface IQuarkAnyService extends IQuarkService {
	/**
	 * @param QuarkDTO $request
	 * @param QuarkSession $session
	 *
	 * @return mixed
	 */
	public function Any(QuarkDTO $request, QuarkSession $session);
}

/**
 * Interface IQuarkGetService
 *
 * @package Quark
 */
interface IQuarkGetService extends IQuarkService {
	/**
	 * @param QuarkDTO $request
	 * @param QuarkSession $session
	 *
	 * @return mixed
	 */
	public function Get(QuarkDTO $request, QuarkSession $session);
}

/**
 * Interface IQuarkPostService
 *
 * @package Quark
 */
interface IQuarkPostService extends IQuarkService {
	/**
	 * @param QuarkDTO $request
	 * @param QuarkSession $session
	 *
	 * @return mixed
	 */
	public function Post(QuarkDTO $request, QuarkSession $session);
}

/**
 * Interface IQuarkServiceWithCustomProcessor
 *
 * @package Quark
 */
interface IQuarkServiceWithCustomProcessor {
	/**
	 * @return IQuarkIOProcessor
	 */
	public function Processor();
}

/**
 * Interface IQuarkServiceWithCustomRequestProcessor
 *
 * @package Quark
 */
interface IQuarkServiceWithCustomRequestProcessor {
	/**
	 * @return IQuarkIOProcessor
	 */
	public function RequestProcessor();
}

/**
 * Interface IQuarkServiceWithCustomResponseProcessor
 *
 * @package Quark
 */
interface IQuarkServiceWithCustomResponseProcessor {
	/**
	 * @return IQuarkIOProcessor
	 */
	public function ResponseProcessor();
}

/**
 * Interface IQuarkServiceWithRequestBackbone
 *
 * @package Quark
 */
interface IQuarkServiceWithRequestBackbone {
	/**
	 * @return array
	 */
	public function RequestBackbone();
}

/**
 * Interface IQuarkStrongService
 *
 * @package Quark
 */
interface IQuarkStrongService { }

/**
 * Interface IQuarkServiceWithAccessControl
 *
 * @package Quark
 */
interface IQuarkServiceWithAccessControl {
	/**
	 * @return string
	 */
	public function AllowOrigin();
}

/**
 * Interface IQuarkSignedAnyService
 *
 * @package Quark
 */
interface IQuarkSignedAnyService {
	/**
	 * @param QuarkDTO $request
	 *
	 * @return mixed
	 */
	public function SignatureCheckFailedOnAny(QuarkDTO $request);
}

/**
 * Interface IQuarkSignedGetService
 *
 * @package Quark
 */
interface IQuarkSignedGetService {
	/**
	 * @param QuarkDTO $request
	 *
	 * @return mixed
	 */
	public function SignatureCheckFailedOnGet(QuarkDTO $request);
}

/**
 * Interface IQuarkSignedPostService
 *
 * @package Quark
 */
interface IQuarkSignedPostService {
	/**
	 * @param QuarkDTO $request
	 *
	 * @return mixed
	 */
	public function SignatureCheckFailedOnPost(QuarkDTO $request);
}

/**
 * Class QuarkTask
 *
 * @package Quark
 */
class QuarkTask implements IQuarkTransportProvider {
	const PREDEFINED = '--quark';
	const QUEUE = 'tcp://127.0.0.1:25500';

	/**
	 * @var IQuarkService|IQuarkTask|IQuarkScheduledTask $_service
	 */
	private $_service = null;

	/**
	 * @var QuarkDate $_launched
	 */
	private $_launched = '';

	/**
	 * @var bool $_client = true
	 */
	private $_client = true;

	/**
	 * @var QuarkDTO $_io
	 */
	private $_io;

	/**
	 * @param IQuarkService $service
	 */
	public function __construct (IQuarkService $service = null) {
		$this->_client = func_num_args() != 0;
		$this->_io = new QuarkDTO(new QuarkJSONIOProcessor());

		if (!$this->_client) return;

		$this->_service = $service;
		$this->_launched = QuarkDate::Now();
	}

	/**
	 * @return string
	 */
	public function Name () {
		return str_replace('Service', '', get_class($this->_service));
	}

	/**
	 * @param int $argc
	 * @param array $argv
	 *
	 * @return bool
	 */
	public function Launch ($argc, $argv) {
		if (!$this->_service->LaunchCriteria($this->_launched)) return true;

		$this->_service->Task($argc, $argv);
		$this->_launched = QuarkDate::Now();

		return true;
	}

	/**
	 * @param array $args
	 * @param string $queue
	 * @param IQuarkTransportProvider $protocol
	 *
	 * @return mixed
	 *
	 * @throws QuarkArchException
	 */
	public function AsyncLaunch ($args = [], $queue = self::QUEUE, IQuarkTransportProvider $protocol = null) {
		if (!($this->_service instanceof IQuarkTask))
			throw new QuarkArchException('Trying to async launch service ' . ($this->_service ? get_class($this->_service) : 'null') . ' which is not an IQuarkTask');

		array_unshift($args, Quark::EntryPoint(), $this->Name());

		$out = $this->_service instanceof IQuarkAsyncTask
			? $this->_service->OnLaunch(sizeof($args), $args)
			: null;

		$this->_io->Data($args);

		$client = new QuarkClient($queue, ($protocol ? $protocol : $this), null, 30);

		if (!$client->Connect()) return false;

		return $out;
	}

	/**
	 * @param QuarkURI|string $listen
	 * @param IQuarkTransportProvider $protocol
	 * @param int $tick = 10000 (microseconds)
	 *
	 * @return bool
	 */
	public static function AsyncQueue ($listen = self::QUEUE, IQuarkTransportProvider $protocol = null, $tick = QuarkThreadSet::TICK) {
		$server = new QuarkServer($listen, $protocol ? $protocol : new QuarkTask());

		if (!$server->Bind()) return false;

		QuarkThreadSet::Queue(function () use ($server) {
			return $server->Pipe();
		}, $tick);

		return true;
	}

	/**
	 * @param QuarkClient $client
	 *
	 * @return bool
	 */
	public function OnConnect (QuarkClient $client) {
		if (!$this->_client) return true;

		$this->_io->Data(array(
			'task' => get_class($this->_service),
			'args' => $this->_io->Data()
		));

		return $client->Send($this->_io->SerializeRequestBody()) && $client->Close();
	}

	/**
	 * @param QuarkClient $client
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function OnData (QuarkClient $client, $data) {
		$this->_io->BatchUnserialize($data, function ($json) {
			if (!isset($json->task) || !isset($json->args)) return;

			$args = (array)$json->args;
			$class = $json->task;
			$task = new $class();

			if ($task instanceof IQuarkTask)
				$task->Task(sizeof($args), $args);
		});
	}

	/**
	 * @param QuarkClient $client
	 *
	 * @return mixed
	 */
	public function OnClose (QuarkClient $client) { }
}

/**
 * Interface IQuarkTask
 *
 * @package Quark
 */
interface IQuarkTask extends IQuarkService {
	/**
	 * @param int $argc
	 * @param array $argv
	 *
	 * @return mixed
	 */
	public function Task($argc, $argv);
}

/**
 * Interface IQuarkScheduledTask
 *
 * @package Quark
 */
interface IQuarkScheduledTask extends IQuarkTask {
	/**
	 * @param QuarkDate $previous
	 *
	 * @return bool
	 */
	public function LaunchCriteria(QuarkDate $previous);
}

/**
 * Interface IQuarkAsyncTask
 *
 * @package Quark
 */
interface IQuarkAsyncTask extends IQuarkTask {
	/**
	 * @param int $argc
	 * @param array $argv
	 *
	 * @return mixed
	 */
	public function OnLaunch($argc, $argv);
}

/**
 * Class QuarkThreadSet
 *
 * @package Quark
 */
class QuarkThreadSet {
	const TICK = 10000;

	/**
	 * @var IQuarkThread[] $_threads
	 */
	private $_threads;
	private $_args = array();

	/**
	 * ThreadSet constructor
	 */
	public function __construct () {
		$this->_args = func_get_args();
	}

	/**
	 * @param IQuarkThread $thread
	 *
	 * @return QuarkThreadSet
	 */
	public function Thread (IQuarkThread $thread) {
		$this->_threads[] = $thread;

		return $this;
	}

	/**
	 * @param IQuarkThread[] $threads
	 *
	 * @return IQuarkThread[]
	 */
	public function Threads ($threads = []) {
		if (func_num_args() != 0 && is_array($threads))
			$this->_threads = $threads;

		return $this->_threads;
	}

	/**
	 * @param callable $after
	 *
	 * @return bool|mixed
	 */
	public function Invoke (callable $after = null) {
		$run = true;

		foreach ($this->_threads as $thread) {
			if (!($thread instanceof IQuarkThread)) continue;

			try {
				$run_tmp = call_user_func_array(array($thread, 'Thread'), $this->_args);
				$run_tmp = $run_tmp === null || $run_tmp;
			}
			catch (\Exception $e) {
				$run_tmp = $thread->ExceptionHandler($e);
			}

			$run &= $run_tmp;
		}

		unset($thread);

		if ($after) $after();

		return (bool)$run;
	}

	/**
	 * @param int $sleep = 10000 (microseconds)
	 * @param callable $after
	 */
	public function Pipeline ($sleep = self::TICK, callable $after = null) {
		self::Queue(function () use ($after) {
			return $this->Invoke($after);
		}, $sleep);
	}

	/**
	 * @param callable $pipe
	 * @param int $sleep = 10000 (microseconds)
	 */
	public static function Queue (callable $pipe, $sleep = self::TICK) {
		$run = true;

		while ($run) {
			$result = $pipe();

			$run = $result !== false;
			usleep($sleep);
		}
	}
}

/**
 * Interface IQuarkThread
 *
 * @package Quark
 */
interface IQuarkThread {
	/**
	 * @return mixed
	 */
	public function Thread();

	/**
	 * @param \Exception $exception
	 *
	 * @return mixed
	 */
	public function ExceptionHandler(\Exception $exception);
}

/**
 * Interface IQuarkStream
 *
 * @package Quark
 */
interface IQuarkStream extends IQuarkService {
	/**
	 * @param QuarkDTO $request
	 * @param QuarkSession $session
	 * @param QuarkClusterNode $cluster
	 *
	 * @return mixed
	 */
	public function Stream(QuarkDTO $request, QuarkSession $session, QuarkClusterNode $cluster);
}

/**
 * Interface IQuarkStreamNetwork
 *
 * @package Quark
 */
interface IQuarkStreamNetwork extends IQuarkService {
	/**
	 * @param QuarkDTO $request
	 * @param QuarkClusterNode $cluster
	 *
	 * @return mixed
	 */
	public function StreamNetwork(QuarkDTO $request, QuarkClusterNode $cluster);
}

/**
 * Interface IQuarkStreamConnect
 *
 * @package Quark
 */
interface IQuarkStreamConnect extends IQuarkService {
	/**
	 * @param QuarkSession $session
	 * @param QuarkClusterNode $cluster
	 *
	 * @return mixed
	 */
	public function StreamConnect(QuarkSession $session, QuarkClusterNode $cluster);
}

/**
 * Interface IQuarkStreamClose
 *
 * @package Quark
 */
interface IQuarkStreamClose extends IQuarkService {
	/**
	 * @param QuarkSession $session
	 * @param QuarkClusterNode $cluster
	 *
	 * @return mixed
	 */
	public function StreamClose(QuarkSession $session, QuarkClusterNode $cluster);
}

/**
 * Interface IQuarkStreamUnknown
 *
 * @package Quark
 */
interface IQuarkStreamUnknown extends IQuarkService {
	/**
	 * @param QuarkDTO $request
	 * @param QuarkSession $session
	 * @param QuarkClusterNode $cluster
	 *
	 * @return mixed
	 */
	public function StreamUnknown(QuarkDTO $request, QuarkSession $session, QuarkClusterNode $cluster);
}

/**
 * Interface IQuarkControllerStreamConnect
 *
 * @package Quark
 */
interface IQuarkControllerStreamConnect extends IQuarkService {
	public function ControllerStreamConnect();
}

/**
 * Interface IQuarkControllerStream
 *
 * @package Quark
 */
interface IQuarkControllerStream extends IQuarkService {
	/**
	 * @param QuarkDTO $request
	 * @param QuarkClusterNode $cluster
	 *
	 * @return mixed
	 */
	public function ControllerStream(QuarkDTO $request, QuarkClusterNode $cluster);
}

/**
 * Interface IQuarkControllerStreamClose
 *
 * @package Quark
 */
interface IQuarkControllerStreamClose extends IQuarkService {
	public function ControllerStreamClose();
}

/**
 * Class QuarkServiceBehavior
 *
 * @package Quark
 */
trait QuarkServiceBehavior {
	use QuarkContainerBehavior;

	/**
	 * @param IQuarkService $service
	 *
	 * @return string
	 */
	public function URL (IQuarkService $service = null) {
		return $this->_call('URL', func_get_args());
	}

	/**
	 * @return QuarkDTO
	 */
	public function Input () {
		return $this->_call('Input', func_get_args());
	}

	/**
	 * @return string
	 */
	public function WebHost () {
		$uri = $this->Input()->URI();

		return str_replace($uri->path, '', $uri->URI(false));
	}
}

/**
 * Class QuarkStreamBehavior
 *
 * @package Quark
 */
trait QuarkStreamBehavior {
	use QuarkServiceBehavior;

	/**
	 * @param QuarkDTO|object|array $data
	 * @param IQuarkStreamNetwork $service
	 *
	 * @return bool
	 */
	public function Broadcast ($data, IQuarkStreamNetwork $service = null) {
		return Quark::Dispatch(QuarkStreamEnvironmentProvider::EVENT_BROADCAST, $this->Input()->AuthorizationProvider() && $this->Input()->AuthorizationProvider()->Value() === false, $data, $this->URL($service));
	}

	/**
	 * @param callable(QuarkSession $client) $sender
	 *
	 * @return bool
	 */
	public function Event (callable $sender = null) {
		return Quark::Dispatch(QuarkStreamEnvironmentProvider::EVENT_EVENT, $sender, $this->URL());
	}
}

/**
 * Class QuarkService
 *
 * @package Quark
 */
class QuarkService implements IQuarkContainer {
	/**
	 * @var IQuarkService|IQuarkAuthorizableLiteService|IQuarkServiceWithAccessControl|IQuarkServiceWithRequestBackbone $_service
	 */
	private $_service;

	/**
	 * @var QuarkDTO $_input
	 */
	private $_input;

	/**
	 * @var QuarkDTO $_output
	 */
	private $_output;

	/**
	 * @var QuarkSession $_session
	 */
	private $_session;

	/**
	 * @param string $service
	 *
	 * @return string
	 */
	private static function _bundle ($service) {
		return Quark::NormalizePath(Quark::Host() . '/' . Quark::Config()->Location(QuarkConfig::SERVICES) . '/' . $service . 'Service.php', false);
	}

	/**
	 * @param string $uri
	 * @param IQuarkIOProcessor $input
	 * @param IQuarkIOProcessor $output
	 *
	 * @throws QuarkArchException
	 * @throws QuarkHTTPException
	 */
	public function __construct ($uri, IQuarkIOProcessor $input = null, IQuarkIOProcessor $output = null) {
		$route = QuarkURI::FromURI(Quark::NormalizePath($uri), false);
		$path = QuarkURI::ParseRoute($route->path);

		$buffer = array();

		foreach ($path as $item)
			if (strlen(trim($item)) != 0) $buffer[] = ucfirst(trim($item));

		$route = $buffer;
		unset($buffer);
		$length = sizeof($route);
		$service = $length == 0 ? 'Index' : implode('/', $route);
		$path = self::_bundle($service);

		while ($length > 0) {
			if (is_file($path)) break;

			$index = self::_bundle($service . '\\Index');

			if (is_file($index)) {
				$service .= '\\Index';
				$path = $index;

				break;
			}

			$length--;
			$service = preg_replace('#\/' . preg_quote(ucfirst(trim($route[$length]))) . '$#Uis', '', $service);
			$path = self::_bundle($service);
		}

		if (!file_exists($path))
			throw new QuarkHTTPException(404, 'Unknown service file ' . $path);

		$class = str_replace('/', '\\', '/Services/' . $service . 'Service');
		$bundle = new $class();

		if (!($bundle instanceof IQuarkService))
			throw new QuarkArchException('Class ' . $class . ' is not an IQuarkService');

		$this->_service = $bundle;

		$this->_input = new QuarkDTO();
		$this->_input->Processor($input ? $input : new QuarkFormIOProcessor());
		$this->_output = new QuarkDTO();
		$this->_output->Processor($output ? $output : new QuarkHTMLIOProcessor());

		$this->_input->URI(QuarkURI::FromURI(Quark::NormalizePath($uri, false), false));

		if ($this->_service instanceof IQuarkServiceWithCustomProcessor) {
			$this->_input->Processor($this->_service->Processor());
			$this->_output->Processor($this->_service->Processor());
		}

		if ($this->_service instanceof IQuarkServiceWithCustomRequestProcessor)
			$this->_output->Processor($this->_service->RequestProcessor());

		if ($this->_service instanceof IQuarkServiceWithCustomResponseProcessor)
			$this->_output->Processor($this->_service->ResponseProcessor());

		$this->_session = new QuarkSession();

		Quark::Container($this);
	}

	/**
	 * @param IQuarkPrimitive $primitive
	 *
	 * @return IQuarkPrimitive
	 */
	public function Primitive (IQuarkPrimitive $primitive = null) {
		if (func_num_args() != 0)
			$this->_service = $primitive;

		return $this->_service;
	}

	/**
	 * @return IQuarkService|IQuarkServiceWithAccessControl|IQuarkServiceWithRequestBackbone
	 */
	public function &Service () {
		return $this->_service;
	}

	/**
	 * @return QuarkDTO
	 */
	public function &Input () {
		return $this->_input;
	}

	/**
	 * @return QuarkDTO
	 */
	public function &Output () {
		return $this->_output;
	}

	/**
	 * @return QuarkSession
	 */
	public function &Session () {
		return $this->_session;
	}

	/**
	 * @param IQuarkService $service
	 *
	 * @return string
	 */
	public function URL (IQuarkService $service = null) {
		return $service ? self::URLOf($service) : $this->_input->URI()->Query();
	}

	/**
	 * @param IQuarkService $service
	 *
	 * @return string
	 */
	public static function URLOf (IQuarkService $service) {
		return Quark::NormalizePath(str_replace('Service', '', str_replace('Services', '', get_class($service))), false);
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	public function Arguments ($args = []) {
		return array_merge(array($this->_input, $this->_session), $args);
	}

	/**
	 * @param bool $http
	 * @param string $method
	 * @param bool $auth = true
	 * @param callable $beforeInvoke
	 *
	 * @return QuarkService
	 * @throws QuarkArchException
	 */
	public function Pipe ($http, $method = '', $auth = true, callable $beforeInvoke = null) {
		$output = null;
		$method = $method
			? $method
			: ($this->_service instanceof IQuarkAnyService
				? 'Any'
				: ucfirst(strtolower($this->_input->Method()))
			);

		if ($this->_service instanceof IQuarkServiceWithRequestBackbone)
			$this->_input->Data(QuarkObject::Normalize($this->_input->Data(), $this->_service->RequestBackbone()));

		if ($auth && $this->_service instanceof IQuarkAuthorizableLiteService) {
			if (!$this->_input->AuthorizationProvider())
				$this->_input->AuthorizationProvider(new QuarkKeyValuePair($this->_service->AuthorizationProvider($this->_input), false));

			/**
			 * @var QuarkSessionSource[] $sessions
			 */
			$sessions = Quark::StackOf(new QuarkSessionSource());

			foreach ($sessions as $session) {
				if (!$session->Recognize($this->_input)) continue;

				$this->_session = new QuarkSession($session);
				break;
			}

			unset($session, $sessions);

			if (!$this->_session)
				throw new QuarkArchException('Authorization provider specified by ' . get_class($this->_service) . ' does not recognized');

			$this->_session->Input($this->_input);

			Quark::Tickable(QuarkSession::TICKABLE_KEY, $this->_session);

			if ($this->_service instanceof IQuarkAuthorizableService) {
				$criteria = $this->_service->AuthorizationCriteria($this->_input, $this->_session);

				if ($criteria !== true)
					$output = $this->_service->AuthorizationFailed($this->_input, $criteria);
				else {
					if (QuarkObject::is($this->_service, 'Quark\IQuarkSigned' . $method . ($http ? 'Service' : ''))) {
						$sign = $this->_session->Signature();

						if ($sign == '' || $this->_input->Signature() != $sign) {
							$action = 'SignatureCheckFailedOn' . $method;
							$output = $this->_service->$action($this->_input);
						}
					}
				}
			}
		}

		$args = $this->Arguments();

		if ($beforeInvoke)
			$args = $beforeInvoke($this);

		if ($output === null)
			$output = strlen(trim($method)) != 0 && QuarkObject::is($this->_service, 'Quark\IQuark' . $method . ($http ? 'Service' : ''))
				? call_user_func_array(array($this->_service, $method), $args)
				: null;

		$this->_output->Merge($output);
		$this->_output->Merge($this->_session->Output(), false);

		return $this;
	}

	/**
	 * reset service
	 */
	public function __destruct () {
		unset($this->_service);
		unset($this->_session);
		unset($this->_input);
		unset($this->_output);
	}
}

/**
 * Class QuarkContainerBehavior
 *
 * @package Quark
 */
trait QuarkContainerBehavior {
	/**
	 * @param $method
	 * @param $args
	 *
	 * @return mixed
	 */
	private function _call ($method, $args) {
		/**
		 * @var IQuarkPrimitive $this
		 */
		$container = Quark::ContainerOf($this);

		return method_exists($container, $method)
			? call_user_func_array(array($container, $method), $args)
			: null;
	}

	/**
	 * @param $method
	 * @param $args
	 *
	 * @return mixed
	 */
	public function __call ($method, $args) {
		return $this->_call($method, $args);
	}
}

/**
 * Interface IQuarkPrimitive
 *
 * @package Quark
 */
interface IQuarkPrimitive { }

/**
 * Interface IQuarkContainer
 *
 * @package Quark
 */
interface IQuarkContainer {
	/**
	 * @param IQuarkPrimitive $primitive
	 *
	 * @return IQuarkPrimitive
	 */
	public function Primitive(IQuarkPrimitive $primitive = null);
}

/**
 * Class QuarkObject
 *
 * @package Quark
 */
class QuarkObject {
	private $_source;

	private $_min;
	private $_max;

	private $_null = null;

	/**
	 * @param object|array $source
	 * @param object|array $min
	 * @param object|array $max
	 */
	public function __construct ($source = null, $min = null, $max = null) {
		$this->Source($source);

		$this->Minimal($min);
		$this->Maximal($max);
	}

	/**
	 * @param $key
	 *
	 * @return mixed
	 */
	public function __get ($key) {
		return isset($this->_source->$key) ? $this->_source->$key : $this->_null;
	}

	/**
	 * @param $key
	 * @param $value
	 */
	public function __set ($key, $value) {
		$this->_source->$key = $value;
	}

	/**
	 * @param $key
	 *
	 * @return bool
	 */
	public function __isset ($key) {
		return isset($this->_source->$key);
	}

	/**
	 * @param $name
	 */
	public function __unset ($name) {
		unset($this->_source->$name);
	}

	/**
	 * @param mixed $source
	 * @param mixed $backbone
	 * @param callable $iterator
	 *
	 * @return mixed
	 */
	public static function Normalize ($source, $backbone = [], callable $iterator = null) {
		if ($iterator == null)
			$iterator = function ($item) { return $item; };

		$output = $source;

		if (self::isIterative($backbone)) {
			$i = 0;
			$size = sizeof($backbone);
			$output = array();

			if (!is_array($source))
				$source = array();

			while ($i < $size) {
				$def = isset($source[$i]) ? $source[$i] : $backbone[$i];
				$output[] = self::Normalize($iterator(isset($source[$i]) ? $source[$i] : $def, $def, $i), $def, $iterator);

				$i++;
			}

			unset($i, $size, $def);
		}
		else {
			if (is_scalar($backbone)) $output = $source;
			else {
				if (!is_object($output))
					$output = new \StdClass();

				if ($backbone == null) return $source;

				foreach ($backbone as $key => $value) {
					$def = isset($source->$key) ? $source->$key : $value;

					@$output->$key = self::Normalize($iterator($value, $def, $key), $def, $iterator);
				}

				unset($key, $value, $def);
			}
		}

		return $output;
	}

	/**
	 * @param $source
	 * @param callable $iterator
	 * @param string $key
	 * @param $parent
	 */
	public static function Walk (&$source, callable $iterator, $key = '', &$parent = null) {
		if (self::isIterative($source)) {
			$i = 0;
			$size = sizeof($source);

			while ($i < $size) {
				self::Walk($source[$i], $iterator, $key . ($key == '' ? $i : '[' . $i . ']'), $source);

				$i++;
			}

			unset($i, $size);
		}
		else {
			if ($source instanceof QuarkFile)
				$source = new QuarkModel($source);

			if (is_scalar($source) || $source === null) $iterator($key, $source, $parent);
			elseif ($source instanceof QuarkModel) $iterator($key, $source->Model(), $parent);
			else {
				foreach ($source as $k => $v) {
					self::Walk($v, $iterator, $key . ($key == '' ? $k : '[' . $k . ']'), $source);
				}
			}

			unset($k, $v);
		}
	}

	/**
	 * @return mixed
	 */
	public static function Merge () {
		$args = func_get_args();

		if (sizeof($args) == 0) return null;
		if (sizeof($args) == 1) return $args[0];

		return self::Normalize($args[1], $args[0]);
	}

	/**
	 * @param $source
	 *
	 * @return bool
	 */
	public static function isAssociative ($source) {
		return is_object($source) || is_array($source) && sizeof(array_filter(array_keys($source), 'is_string')) != 0;
	}

	/**
	 * @param $source
	 *
	 * @return bool
	 */
	public static function isIterative ($source) {
		return is_array($source) && (sizeof($source) == 0 || sizeof(array_filter(array_keys($source), 'is_int')) != 0);
	}

	/**
	 * @param $class
	 * @param string|array $interface
	 * @param bool $silent
	 *
	 * @return bool
	 */
	public static function is ($class, $interface = '', $silent = false) {
		if (!is_array($interface))
			$interface = array($interface);

		if (is_object($class))
			$class = get_class($class);

		if (!class_exists($class)) {
			if (!$silent)
				Quark::Log('Class "' . $class . '" does not exists', Quark::LOG_WARN);

			return false;
		}

		$faces = class_implements($class);

		foreach ($interface as $face)
			if (in_array($face, $faces, true)) return true;

		return false;
	}

	/**
	 * @param $interface
	 * @param callable $filter
	 *
	 * @return array
	 */
	public static function Implementations ($interface, callable $filter = null) {
		$output = array();
		$classes = get_declared_classes();

		foreach ($classes as $class)
			if (self::is($class, $interface) && ($filter != null ? $filter($class) : true)) $output[] = $class;

		return $output;
	}

	/**
	 * @param $target
	 *
	 * @return string
	 */
	public static function ClassOf ($target) {
		return is_object($target) ? array_reverse(explode('\\', get_class($target)))[0] : null;
	}

	/**
	 * @param $file
	 *
	 * @return string
	 */
	public static function ClassIn ($file) {
		return is_string($file) ? '\\' . str_replace('/', '\\', str_replace(Quark::Host(), '', str_replace('.php', '', Quark::NormalizePath($file, false)))) : '';
	}

	/**
	 * @param string $class
	 *
	 * @return object
	 */
	public static function Of ($class) {
		return new $class;
	}

	/**
	 * @param $source
	 *
	 * @return array
	 */
	public static function Properties ($source) {
		return is_object($source)
			? array_intersect(
				get_object_vars($source),
				get_class_vars(get_class($source))
			)
			: array();
	}

	/**
	 * @param $source
	 * @param $name
	 * @param $default
	 *
	 * @return mixed
	 */
	public static function Property ($source, $name, $default = null) {
		if (is_object($source))
			return isset($source->$name) ? $source->$name : $default;

		if (is_array($source))
			return isset($source[$name]) ? $source[$name] : $default;

		return $default;
	}

	/**
	 * @param $source
	 * @param $name
	 *
	 * @return bool
	 */
	public static function PropertyExists ($source, $name) {
		if (is_object($source))
			return isset($source->$name);

		if (is_array($source))
			return isset($source[$name]);

		return false;
	}

	/**
	 * @param object|array $source
	 *
	 * @return object|array
	 */
	public function Source ($source = null) {
		if (func_num_args() != 0)
			$this->_source = $source;

		return $this->_source;
	}

	/**
	 * @param object|array $min
	 *
	 * @return object|array
	 */
	public function Minimal ($min = null) {
		if (func_num_args() != 0)
			$this->_min = $min;

		return $this->_min;
	}

	/**
	 * @param object|array $max
	 *
	 * @return object|array
	 */
	public function Maximal ($max = null) {
		if (func_num_args() != 0)
			$this->_max = $max;

		return $this->_max;
	}

	/**
	 * @param callable $builder
	 *
	 * @return object
	 */
	public function Build ($builder = null) {
		$builder();
		return new \StdClass();
	}
}

/**
 * Class QuarkViewBehavior
 *
 * @package Quark
 */
trait QuarkViewBehavior {
	use QuarkContainerBehavior;

	/**
	 * @param IQuarkViewModel $view = null
	 *
	 * @return mixed
	 */
	public function Child (IQuarkViewModel $view = null) {
		return $this->_call('Child', func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function User () {
		return $this->_call('User', func_get_args());
	}

	/**
	 * @param bool $field = true
	 *
	 * @return mixed
	 */
	public function Signature ($field = true) {
		return $this->_call('Signature', func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function Compile () {
		return $this->_call('Compile', func_get_args());
	}
}

/**
 * Class QuarkView
 *
 * @package Quark
 */
class QuarkView implements IQuarkContainer {
	/**
	 * @var IQuarkViewModel|IQuarkViewModelWithResources|IQuarkViewModelWithCachedResources|null
	 */
	private $_view = null;
	private $_child = null;
	/**
	 * @var QuarkView $_layout
	 */
	private $_layout = null;
	private $_file = '';
	private $_vars = array();
	private $_resources = array();
	private $_html = '';

	/**
	 * @var QuarkSession $_session
	 */
	private $_session;

	private $_null = null;

	/**
	 * @param IQuarkViewModel $view
	 * @param array|object $vars
	 * @param array $resources
	 *
	 * @throws QuarkArchException
	 */
	public function __construct (IQuarkViewModel $view, $vars = [], $resources = []) {
		$this->_view = $view;
		$this->_file = Quark::NormalizePath(Quark::Host() . '/' . Quark::Config()->Location(QuarkConfig::VIEWS) . '/' . $this->_view->View() . '.php', false);

		if (!is_file($this->_file))
			throw new QuarkArchException('Unknown view file ' . $this->_file);

		$vars = $this->Vars($vars);

		foreach ($vars as $key => $value)
			$this->_view->$key = $value;

		$this->_resources = $resources;
		$this->_session = Quark::Tickable(QuarkSession::TICKABLE_KEY);

		Quark::Container($this);
	}

	/**
	 * @param $key
	 *
	 * @return mixed
	 */
	public function __get ($key) {
		return isset($this->_view->$key)
			? $this->_view->$key
			: (isset($this->_layout->$key) ? $this->_layout->$key : $this->_null);
	}

	/**
	 * @param $key
	 * @param $value
	 */
	public function __set ($key, $value) {
		$this->_view->$key = $value;
	}

	/**
	 * @param $key
	 *
	 * @return bool
	 */
	public function __isset ($key) {
		return isset($this->_view->$key) || isset($this->_layout->$key);
	}

	/**
	 * @param $name
	 */
	public function __unset ($name) {
		unset($this->_view->$name);
	}

	/**
	 * @param $method
	 * @param $args
	 *
	 * @return mixed
	 * @throws QuarkArchException
	 */
	public function __call ($method, $args) {
		if (method_exists($this->_view, $method))
			return call_user_func_array(array($this->_view, $method), $args);

		if (method_exists($this->_layout->ViewModel(), $method))
			return call_user_func_array(array($this->_layout, $method), $args);

		throw new QuarkArchException('Method ' . $method . ' not exists in ' . get_class($this->_view) . ' environment');
	}

	/**
	 * @param bool $obfuscate
	 *
	 * @return string
	 */
	public function Resources ($obfuscate = true) {
		$out = '';
		$type = null;
		$res = null;
		$location = null;
		$content = null;

		$this->ResourceList();

		/**
		 * @var IQuarkViewResource|IQuarkForeignViewResource|IQuarkLocalViewResource|IQuarkInlineViewResource $resource
		 */
		foreach ($this->_resources as $resource) {
			if ($resource instanceof IQuarkInlineViewResource) {
				$out .= $obfuscate && $resource instanceof IQuarkLocalViewResource && $resource->CacheControl()
					? QuarkSource::ObfuscateString($resource->HTML())
					: $resource->HTML();

				continue;
			}

			$type = $resource->Type();

			if (!($type instanceof IQuarkViewResourceType)) continue;

			$location = $resource->Location();
			$content = '';

			if ($resource instanceof IQuarkForeignViewResource) { }

			if ($resource instanceof IQuarkLocalViewResource) {
				$res = new QuarkSource($location, true);

				if ($obfuscate && $resource->CacheControl())
					$res->Obfuscate();

				$content = $res->Content();

				$location = '';
			}

			$out .= $type->Container($location, $content);
		}

		return $out;
	}

	/**
	 * @return array
	 */
	public function ResourceList () {
		if (!($this->_view instanceof IQuarkViewModelWithResources)) return $this->_resources;

		$resources = $this->_view->Resources();

		foreach ($resources as $resource)
			$this->_resource($resource);

		return $this->_resources;
	}

	/**
	 * @param IQuarkViewResource $resource
	 *
	 * @return QuarkView
	 */
	private function _resource (IQuarkViewResource $resource) {
		if ($resource instanceof IQuarkViewResourceWithDependencies) {
			$resources = $resource->Dependencies();

			/**
			 * @var IQuarkViewResource $dependency
			 */
			foreach ($resources as $dependency) {
				if ($dependency == null) continue;

				if ($dependency instanceof IQuarkViewResourceWithDependencies) $this->_resource($dependency);
				if ($this->_resource_loaded($dependency)) continue;

				$this->_resources[] = $dependency;
			}
		}

		if (!$this->_resource_loaded($resource))
			$this->_resources[] = $resource;

		return $this;
	}

	/**
	 * @param IQuarkViewResource $dependency
	 *
	 * @return bool
	 */
	private function _resource_loaded (IQuarkViewResource $dependency) {
		$class = get_class($dependency);
		$location = $dependency->Location();

		/**
		 * @var IQuarkViewResource $resource
		 */
		foreach ($this->_resources as $resource)
			if (get_class($resource) == $class && $resource->Location() == $location) return true;

		return false;
	}

	/**
	 * @param bool $field = true
	 *
	 * @return string
	 * @throws QuarkArchException
	 */
	public function Signature ($field = true) {
		if (!($this->_view instanceof IQuarkAuthorizableViewModel)) return '';

		$sign = $this->_session->Signature();

		//if (!is_string($sign))
			//throw new QuarkArchException('AuthProvider ' . get_class($provider) . ' specified non-string Signature');

		return $field ? '<input type="hidden" name="' . QuarkDTO::SIGNATURE . '" value="' . $sign . '" />' : $sign;
	}

	/**
	 * @return QuarkModel
	 * @throws QuarkArchException
	 */
	public function User () {
		if (!($this->_view instanceof IQuarkAuthorizableViewModel))
			throw new QuarkArchException('ViewModel ' . get_class($this->_view) . ' need to be IQuarkAuthorizableViewModel');

		/**
		 * @var QuarkSession $provider
		 */
		//$provider = Quark::Stack($this->_view->AuthProvider());

		return $this->_session->User();
	}

	/**
	 * @param IQuarkViewModel $view
	 * @param array|object $vars
	 * @param array $resources
	 *
	 * @return QuarkView
	 */
	public function Layout (IQuarkViewModel $view = null, $vars = [], $resources = []) {
		if (func_num_args() != 0) {
			$this->_layout = new QuarkView($view, $vars, $resources);
			$this->_layout->View($this->Compile());
			$this->_layout->Child($this->_view);
		}

		return $this->_layout;
	}

	/**
	 * @param string $html
	 *
	 * @return string
	 */
	public function View ($html = '') {
		if (func_num_args() == 1)
			$this->_html = $html;

		return $this->_html;
	}

	/**
	 * @param IQuarkViewModel $view
	 * @param IQuarkViewModel $layout
	 * @param array|object $vars
	 *
	 * @return QuarkView
	 */
	public static function InLayout (IQuarkViewModel $view, IQuarkViewModel $layout, $vars = []) {
		$inline = new QuarkView($view, $vars);

		return $inline->Layout($layout, $vars, $inline->ResourceList());
	}

	/**
	 * @param array $params
	 *
	 * @return array
	 */
	public function Vars ($params = []) {
		if (func_num_args() == 1)
			$this->_vars = $params instanceof QuarkDTO
				? $params->Data()
				: QuarkObject::Normalize(new \StdClass(), (object)$params);

		return $this->_vars;
	}

	/**
	 * @return string
	 */
	public function Compile () {
		foreach ($this->_vars as $name => $value)
			$$name = $value;

		ob_start();
		/** @noinspection PhpIncludeInspection */
		include $this->_file;
		return ob_get_clean();
	}

	/**
	 * @param IQuarkViewModel $view
	 *
	 * @return IQuarkViewModel
	 */
	public function ViewModel (IQuarkViewModel $view = null) {
		if (func_num_args() == 1)
			$this->_view = $view;

		return $this->_view;
	}

	/**
	 * @param IQuarkViewModel $view
	 *
	 * @return IQuarkViewModel
	 */
	public function Child (IQuarkViewModel $view = null) {
		if (func_num_args() == 1)
			$this->_child = $view;

		return $this->_child;
	}

	/**
	 * @param IQuarkPrimitive $primitive
	 *
	 * @return IQuarkPrimitive
	 */
	public function Primitive (IQuarkPrimitive $primitive = null) {
		if (func_num_args() != 0)
			$this->_view = $primitive;

		return $this->_view;
	}
}

/**
 * Interface IQuarkViewModel
 *
 * @package Quark
 */
interface IQuarkViewModel extends IQuarkPrimitive {
	/**
	 * @return string
	 */
	public function View();
}

/**
 * Interface IQuarkAuthorizableViewModel
 *
 * @package Quark
 */
interface IQuarkAuthorizableViewModel {
	/**
	 * @return string
	 */
	public function AuthProvider();
}

/**
 * Interface IQuarkViewModelWithResources
 *
 * @package Quark
 */
interface IQuarkViewModelWithResources extends IQuarkViewModel {
	/**
	 * @return array
	 */
	public function Resources();
}

/**
 * Interface IQuarkViewModelWithCachedResources
 *
 * @package Quark
 */
interface IQuarkViewModelWithCachedResources extends IQuarkViewModel {
	/**
	 * @return array
	 */
	public function CachedResources();
}

/**
 * Interface IQuarkViewResource
 *
 * @package Quark
 */
interface IQuarkViewResource {
	/**
	 * @return string
	 */
	public function Location();

	/**
	 * @return IQuarkViewResourceType;
	 */
	public function Type();
}

/**
 * Interface IQuarkViewResourceWithDependencies
 *
 * @package Quark
 */
interface IQuarkViewResourceWithDependencies {
	/**
	 * @return array
	 */
	public function Dependencies();
}

/**
 * Interface IQuarkLocalViewResource
 *
 * @package Quark
 */
interface IQuarkLocalViewResource {
	/**
	 * @return bool
	 */
	public function CacheControl();
}

/**
 * Interface IQuarkForeignViewResource
 *
 * @package Quark
 */
interface IQuarkForeignViewResource {
	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO();
}

/**
 * Interface IQuarkInlineViewResource
 *
 * @package Quark
 */
interface IQuarkInlineViewResource {
	/**
	 * @return string
	 */
	public function HTML();
}

/**
 * Class QuarkProjectViewResource
 *
 * @package Quark
 */
class QuarkProjectViewResource implements IQuarkViewResource, IQuarkLocalViewResource {
	/**
	 * @var string
	 */
	private $_type = '';
	private $_location = '';
	private $_minimize = true;

	/**
	 * @param string $location
	 * @param IQuarkViewResourceType $type
	 * @param bool $minimize = true
	 */
	public function __construct ($location, IQuarkViewResourceType $type, $minimize = true) {
		$this->_location = $location;
		$this->_type = $type;
		$this->_minimize = $minimize;
	}

	/**
	 * @return IQuarkViewResourceType
	 */
	public function Type () {
		return $this->_type;
	}

	/**
	 * @return string
	 */
	public function Location () {
		return $this->_location;
	}

	/**
	 * @return bool
	 */
	public function CacheControl () {
		return $this->_minimize;
	}
}

/**
 * Class QuarkLocalCoreJSViewResource
 *
 * @package Quark
 */
class QuarkLocalCoreJSViewResource implements IQuarkViewResource, IQuarkLocalViewResource {
	/**
	 * @return IQuarkViewResourceType
	 */
	public function Type () {
		return new QuarkJSViewResourceType();
	}

	/**
	 * @return string
	 */
	public function Location () {
		return __DIR__ . '/Quark.js';
	}

	/**
	 * @return bool
	 */
	public function CacheControl () {
		return true;
	}
}

/**
 * Class QuarkLocalCoreCSSViewResource
 *
 * @package Quark
 */
class QuarkLocalCoreCSSViewResource implements IQuarkViewResource, IQuarkLocalViewResource {
	/**
	 * @return IQuarkViewResourceType
	 */
	public function Type () {
		return new QuarkCSSViewResourceType();
	}

	/**
	 * @return string
	 */
	public function Location () {
		return __DIR__ . '/Quark.css';
	}

	/**
	 * @return bool
	 */
	public function CacheControl () {
		return true;
	}
}

/**
 * Class QuarkInlineViewResource
 *
 * @package Quark
 */
trait QuarkInlineViewResource {
	/**
	 * @var string $_code
	 */
	private $_code = '';

	/**
	 * @param string $code
	 */
	public function __construct ($code = '') {
		$this->_code = $code;
	}

	/**
	 * @return string
	 */
	public function Location () { }

	/**
	 * @return string
	 */
	public function Type () { }

	/**
	 * @return bool
	 */
	public function CacheControl () {
		return true;
	}
}

/**
 * Class QuarkInlineCSSViewResource
 *
 * @package Quark
 */
class QuarkInlineCSSViewResource implements IQuarkViewResource, IQuarkLocalViewResource, IQuarkInlineViewResource {
	use QuarkInlineViewResource;

	/**
	 * @return string
	 */
	public function HTML () {
		return '<style type="text/css">' . $this->_code . '</style>';
	}
}

/**
 * Class QuarkInlineJSViewResource
 *
 * @package Quark
 */
class QuarkInlineJSViewResource implements IQuarkViewResource, IQuarkLocalViewResource, IQuarkInlineViewResource {
	use QuarkInlineViewResource;

	/**
	 * @info EXTERNAL_FRAGMENT need to suppress the PHPStorm 8+ invalid spell check
	 * @return string
	 */
	public function HTML () {
		return '<script type="text/javascript">var EXTERNAL_FRAGMENT;' . $this->_code . '</script>';
	}
}

/**
 * Interface IQuarkViewResourceType
 *
 * @package Quark
 */
interface IQuarkViewResourceType {
	/**
	 * @param $location
	 * @param $content
	 *
	 * @return string
	 */
	public function Container($location, $content);
}

/**
 * Class QuarkCSSViewResourceType
 *
 * @package Quark
 */
class QuarkCSSViewResourceType implements IQuarkViewResourceType {
	/**
	 * @param $location
	 * @param $content
	 *
	 * @return string
	 */
	public function Container ($location, $content) {
		return strlen($location) != 0
			? '<link rel="stylesheet" type="text/css" href="' . $location . '" />'
			: '<style type="text/css">' . $content . '</style>';
	}
}

/**
 * Class QuarkJSViewResourceType
 *
 * @package Quark
 */
class QuarkJSViewResourceType implements IQuarkViewResourceType {
	/**
	 * @param $location
	 * @param $content
	 *
	 * @return string
	 */
	public function Container ($location, $content) {
		return '<script type="text/javascript"' . (strlen($location) != 0 ? ' src="' . $location . '"' : '') . '>' . $content . '</script>';
	}
}

/**
 * Class QuarkCollection
 *
 * @package Quark
 */
class QuarkCollection implements \Iterator, \ArrayAccess, \Countable {
	/**
	 * @var QuarkModel[]|array $_list
	 */
	private $_list = array();
	private $_type = null;
	private $_index = 0;

	/**
	 * @param object $type
	 * @param array $source
	 */
	public function __construct ($type, $source = []) {
		$this->_type = $type;
		$this->PopulateWith($source);
	}

	/**
	 * @return mixed
	 */
	public function Type () {
		return $this->_type;
	}

	/**
	 * @param $item
	 *
	 * @return bool
	 */
	private function _type ($item) {
		return $item instanceof $this->_type || ($item instanceof QuarkModel && $item->Model() instanceof $this->_type);
	}

	/**
	 * @param $item
	 *
	 * @return QuarkCollection
	 */
	public function Add ($item) {
		if ($this->_type($item))
			$this->_list[] = $item;

		return $this;
	}

	/**
	 * @param          $needle
	 * @param callable $compare
	 *
	 * @return bool
	 */
	public function In ($needle, callable $compare) {
		foreach ($this->_list as $item)
			if ($compare($item, $needle)) return true;

		return false;
	}

	/**
	 * @param array $source
	 * @param callable $iterator
	 *
	 * @return QuarkCollection
	 */
	public function PopulateWith ($source, callable $iterator = null) {
		if (!is_array($source)) return $this;

		if ($iterator == null)
			$iterator = function ($item) { return $item; };

		foreach ($source as $item)
			$this->Add($iterator($item));

		return $this;
	}

	/**
	 * @param callable $iterator
	 *
	 * @return array
	 */
	public function Collection (callable $iterator = null) {
		if ($iterator == null) return $this->_list;

		$output = array();

		foreach ($this->_list as $item)
			$output[] = $iterator($item);

		return $output;
	}

	/**
	 * @param $fields
	 * @param $weak
	 *
	 * @return array
	 */
	public function Extract ($fields = null, $weak = false) {
		if (!($this->_type instanceof IQuarkModel)) return $this->_list;

		$out = array();

		foreach ($this->_list as $item)
			$out[] = $item->Extract($fields, $weak);

		return $out;
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Return the current element
	 *
	 * @link http://php.net/manual/en/iterator.current.php
	 * @return mixed Can return any type.
	 */
	public function current () {
		return $this->_list[$this->_index];
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Move forward to next element
	 *
	 * @link http://php.net/manual/en/iterator.next.php
	 * @return void Any returned value is ignored.
	 */
	public function next () {
		$this->_index++;
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Return the key of the current element
	 *
	 * @link http://php.net/manual/en/iterator.key.php
	 * @return mixed scalar on success, or null on failure.
	 */
	public function key () {
		return $this->_index;
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Checks if current position is valid
	 *
	 * @link http://php.net/manual/en/iterator.valid.php
	 * @return boolean The return value will be casted to boolean and then evaluated.
	 *       Returns true on success or false on failure.
	 */
	public function valid () {
		return isset($this->_list[$this->_index]);
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Rewind the Iterator to the first element
	 *
	 * @link http://php.net/manual/en/iterator.rewind.php
	 * @return void Any returned value is ignored.
	 */
	public function rewind () {
		$this->_index = 0;
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Whether a offset exists
	 *
	 * @link http://php.net/manual/en/arrayaccess.offsetexists.php
	 *
	 * @param mixed $offset <p>
	 *                      An offset to check for.
	 *                      </p>
	 *
	 * @return boolean true on success or false on failure.
	 * </p>
	 * <p>
	 * The return value will be casted to boolean if non-boolean was returned.
	 */
	public function offsetExists ($offset) {
		return isset($this->_list[$offset]);
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Offset to retrieve
	 *
	 * @link http://php.net/manual/en/arrayaccess.offsetget.php
	 *
	 * @param mixed $offset <p>
	 *                      The offset to retrieve.
	 *                      </p>
	 *
	 * @return mixed Can return all value types.
	 */
	public function offsetGet ($offset) {
		return $this->_list[$offset];
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Offset to set
	 *
	 * @link http://php.net/manual/en/arrayaccess.offsetset.php
	 *
	 * @param mixed $offset <p>
	 *                      The offset to assign the value to.
	 *                      </p>
	 * @param mixed $value  <p>
	 *                      The value to set.
	 *                      </p>
	 *
	 * @return void
	 */
	public function offsetSet ($offset, $value) {
		if ($this->_type($value))
			$this->_list[$offset] = $value;
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Offset to unset
	 *
	 * @link http://php.net/manual/en/arrayaccess.offsetunset.php
	 *
	 * @param mixed $offset <p>
	 *                      The offset to unset.
	 *                      </p>
	 *
	 * @return void
	 */
	public function offsetUnset ($offset) {
		unset($this->_list[$offset]);
	}

	/**
	 * (PHP 5 &gt;= 5.1.0)<br/>
	 * Count elements of an object
	 *
	 * @link http://php.net/manual/en/countable.count.php
	 * @return int The custom count as an integer.
	 *       </p>
	 *       <p>
	 *       The return value is cast to an integer.
	 */
	public function count () {
		return sizeof($this->_list);
	}
}

/**
 * Class QuarkModelBehavior
 *
 * @package Quark
 */
trait QuarkModelBehavior {
	use QuarkContainerBehavior;

	/**
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function Create ($options = []) {
		return $this->_call('Create', func_get_args());
	}

	/**
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function Save ($options = []) {
		return $this->_call('Save', func_get_args());
	}

	/**
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function Remove ($options = []) {
		return $this->_call('Remove', func_get_args());
	}

	/**
	 * @return bool
	 */
	public function Validate () {
		return $this->_call('Validate', func_get_args());
	}

	/**
	 * @param $source
	 *
	 * @return QuarkModel
	 */
	public function PopulateWith ($source) {
		return $this->_call('PopulateWith', func_get_args());
	}

	/**
	 * @param array $fields
	 * @param bool  $weak
	 *
	 * @return \StdClass
	 */
	public function Extract ($fields = null, $weak = false) {
		return $this->_call('Extract', func_get_args());
	}

	/**
	 * @return QuarkModelSource
	 */
	public function Source () {
		return $this->_call('Source', func_get_args());
	}
}

/**
 * Class QuarkModelSource
 *
 * @package Quark
 */
class QuarkModelSource implements IQuarkStackable {
	/**
	 * @var IQuarkDataProvider $_provider
	 */
	private $_provider;
	private $_connection;

	/**
	 * @var QuarkURI $_uri
	 */
	private $_uri;

	/**
	 * @var string $_name
	 */
	private $_name = '';

	/**
	 * @param string $name
	 * @param IQuarkDataProvider $provider
	 * @param QuarkURI $uri
	 */
	public function __construct ($name, IQuarkDataProvider $provider, QuarkURI $uri) {
		$this->_name = $name;
		$this->_provider = $provider;
		$this->_uri = $uri;
	}

	/**
	 * @param $method
	 * @param $args
	 *
	 * @return mixed
	 */
	public function __call ($method, $args) {
		return method_exists($this->_provider, $method)
			? call_user_func_array(array($this->_provider, $method), $args)
			: null;
	}

	/**
	 * @return IQuarkDataProvider
	 */
	public function Connect () {
		$this->_connection = $this->_provider->Connect($this->_uri);

		return $this->_provider;
	}

	/**
	 * @return mixed
	 */
	public function Connection () {
		return $this->_connection;
	}

	/**
	 * @return string
	 */
	public function Name () {
		return $this->_name;
	}

	/**
	 * @param IQuarkDataProvider $provider
	 *
	 * @return IQuarkDataProvider
	 */
	public function Provider (IQuarkDataProvider $provider = null) {
		if (func_num_args() != 0)
			$this->_provider = $provider;

		return $this->_provider;
	}

	/**
	 * @param QuarkURI $uri
	 *
	 * @return QuarkURI
	 */
	public function URI (QuarkURI $uri = null) {
		if (func_num_args() != 0)
			$this->_uri = $uri;

		return $this->_uri;
	}
}

/**
 * Class QuarkModel
 *
 * @package Quark
 */
class QuarkModel implements IQuarkContainer {
	const OPTION_SORT = 'sort';
	const OPTION_SKIP = 'skip';
	const OPTION_LIMIT = 'limit';

	const OPTION_COLLECTION = 'collection';
	const OPTION_FIELDS = 'fields';

	const OPTION_EXTRACT = 'extract';
	const OPTION_VALIDATE = 'validate';

	/**
	 * @var IQuarkModel|QuarkModelBehavior|null
	 */
	private $_model = null;

	/**
	 * @param IQuarkModel $model
	 * @param $source
	 */
	public function __construct (IQuarkModel $model, $source = null) {
		/**
		 * Attention!
		 * Cloning need to opposite non-controlled passing by reference
		 */
		$this->_model = clone $model;

		if (func_num_args() == 1)
			$source = $model;

		if ($source instanceof QuarkModel)
			$source = $source->Model();

		$this->PopulateWith($source);

		Quark::Container($this);
	}

	/**
	 * @param $key
	 *
	 * @return mixed
	 */
	public function &__get ($key) {
		return $this->_model->$key;
	}

	/**
	 * @param $key
	 * @param $value
	 */
	public function __set ($key, $value) {
		$this->_model->$key = $value;
	}

	/**
	 * @param $key
	 *
	 * @return bool
	 */
	public function __isset ($key) {
		return isset($this->_model->$key);
	}

	/**
	 * @param $name
	 */
	public function __unset ($name) {
		unset($this->_model->$name);
	}

	/**
	 * @param $method
	 * @param $args
	 *
	 * @throws QuarkArchException
	 * @return mixed
	 */
	public function __call ($method, $args) {
		if (method_exists($this->_model, $method))
			return call_user_func_array(array($this->_model, $method), $args);

		$provider = self::_provider($this->_model);
		array_unshift($args, $this->_model);

		if (method_exists($provider, $method))
			return call_user_func_array(array($provider, $method), $args);

		throw new QuarkArchException('Method ' . $method . ' not found in model or provider');
	}

	/**
	 * @return string
	 */
	public function __toString () {
		return method_exists($this->_model, '__toString') ? (string)$this->_model : '';
	}

	/**
	 * @return IQuarkModel
	 */
	public function Model () {
		return $this->_model;
	}

	/**
	 * @param IQuarkPrimitive $primitive
	 *
	 * @return IQuarkPrimitive
	 */
	public function Primitive (IQuarkPrimitive $primitive = null) {
		if (func_num_args() != 0)
			$this->_model = $primitive;

		return $this->_model;
	}

	/**
	 * @param QuarkURI|string $uri
	 *
	 * @return QuarkModelSource
	 * @throws QuarkArchException
	 */
	public function Source ($uri = '') {
		return isset($this->_model) ? self::_provider($this->_model, $uri) : null;
	}

	/**
	 * @param $source
	 *
	 * @return QuarkModel
	 */
	public function PopulateWith ($source) {
		$this->_model = self::_import($this->_model, $source);

		if ($this->_model instanceof IQuarkModelWithOnPopulate) {
			$out = $this->_model->OnPopulate($source);

			if ($out === false)
				$this->_model = null;
		}

		return $this;
	}

	/**
	 * @param IQuarkModel $model
	 * @param QuarkURI|string $uri
	 *
	 * @return QuarkModelSource|IQuarkDataProvider
	 * @throws QuarkArchException
	 */
	private static function _provider (IQuarkModel $model, $uri = '') {
		if (!($model instanceof IQuarkModelWithDataProvider))
			throw new QuarkArchException('Attempt to get data provider of model ' . get_class($model) . ' which is not defined as IQuarkModelWithDataProvider');

		$name = $model->DataProvider();

		$source = Quark::Stack($name);

		if (!($source instanceof QuarkModelSource))
			throw new QuarkArchException('Model source for model ' . get_class($model) . ' is not connected');

		if ($uri)
			$source->URI(QuarkURI::FromURI($uri));

		return func_num_args() == 1 ? $source->Connect() : $source;
	}

	/**
	 * @param $model
	 * @param $field
	 *
	 * @return QuarkModel|null
	 */
	public static function Build ($model, $field) {
		return $field == null && $model instanceof IQuarkNullableModel
			? null
			: new QuarkModel($model, $field);
	}

	/**
	 * @param IQuarkModel $model
	 * @param array       $fields
	 *
	 * @return IQuarkModel
	 */
	private static function _normalize (IQuarkModel $model, $fields = []) {
		if (func_num_args() == 1 || (!is_array($fields) && !is_object($fields)))
			$fields = $model->Fields();

		$output = clone $model;

		if (!is_array($fields) && !is_object($fields)) return $output;

		foreach ($fields as $key => $field) {
			if (isset($model->$key)) {
				if (is_scalar($field) && is_scalar($model->$key))
					settype($model->$key, gettype($field));

				$output->$key = $model->$key;
			}
			else $output->$key = $field instanceof IQuarkModel
				? QuarkModel::Build($field, empty($model->$key) ? null : $model->$key)
				: $field;
		}

		return $output;
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $source
	 *
	 * @return IQuarkModel
	 */
	private static function _import (IQuarkModel $model, $source) {
		if (!is_array($source) && !is_object($source)) return $model;

		$fields = $model->Fields();

		foreach ($source as $key => $value) {
			if (!QuarkObject::PropertyExists($fields, $key) && $model instanceof IQuarkStrongModel) continue;

			$property = QuarkObject::Property($fields, $key, $value);

			if ($property instanceof QuarkCollection) {
				$class = get_class($property->Type());

				$model->$key = $property->PopulateWith($value, function ($item) use ($class) {
					return self::_link(new $class(), $item);
				});
			}
			else $model->$key = self::_link($property, $value);
		}

		unset($key, $value);

		return self::_normalize($model);
	}

	/**
	 * @param $property
	 * @param $value
	 *
	 * @return mixed|QuarkModel
	 */
	private static function _link ($property, $value) {
		return $property instanceof IQuarkLinkedModel
			? ($value instanceof QuarkModel ? $value : $property->Link(QuarkObject::isAssociative($value) ? (object)$value : $value))
			: ($property instanceof IQuarkModel ? new QuarkModel($property, $value) : $value);
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $options
	 *
	 * @return IQuarkModel
	 */
	private static function _export (IQuarkModel $model, $options = []) {
		$fields = $model->Fields();

		if (!isset($options[self::OPTION_VALIDATE]))
			$options[self::OPTION_VALIDATE] = true;

		if ($options[self::OPTION_VALIDATE] && !self::_validate($model)) return false;

		$output = self::_normalize($model);

		foreach ($model as $key => $value) {
			if (!QuarkObject::PropertyExists($fields, $key) && $model instanceof IQuarkStrongModel) continue;

			if ($value instanceof QuarkCollection) {
				$output->$key = $value->Collection(function ($item) {
					return self::_unlink($item);
				});
			}
			else $output->$key = self::_unlink($value);
		}

		unset($key, $value);

		return $output;
	}

	/**
	 * @param $value
	 *
	 * @return mixed|IQuarkModel
	 */
	private static function _unlink ($value) {
		if ($value instanceof QuarkModel)
			$value = self::_export($value->Model());

		return $value instanceof IQuarkLinkedModel ? $value->Unlink() : $value;
	}

	/**
	 * @param IQuarkModel $model
	 * @param bool $check = true
	 *
	 * @return bool|array
	 *
	 * TODO: validate sub-models
	 */
	private static function _validate (IQuarkModel $model, $check = true) {
		if ($model instanceof IQuarkNullableModel && sizeof((array)$model) == 0) return true;
		if ($model instanceof IQuarkModelWithBeforeValidate && $model->BeforeValidate() === false) return false;

		return $check ? QuarkField::Rules($model->Rules()) : $model->Rules();
	}

	/**
	 * @param IQuarkModel $model
	 * @param mixed $data
	 * @param array $options
	 * @param callable $after = null
	 *
	 * @return QuarkModel|\StdClass
	 */
	private static function _record (IQuarkModel $model, $data, $options = [], callable $after = null) {
		if ($data == null) return null;

		$output = new QuarkModel($model, $data);

		$model = $output->Model();

		if ($model instanceof IQuarkModelWithAfterFind)
			$model->AfterFind($data);

		if ($after) {
			$buffer = $after($output);

			if ($buffer === false) return null;
			if ($buffer !== null) $output = $buffer;
		}

		if (is_array($options) && isset($options[self::OPTION_EXTRACT]) && $options[self::OPTION_EXTRACT] !== false)
			$output = $options[self::OPTION_EXTRACT] === true
				? $output->Extract()
				: $output->Extract($options[self::OPTION_EXTRACT]);

		return $output;
	}

	/**
	 * @return IQuarkModel
	 */
	public function Export () {
		return self::_export($this->_model);
	}

	/**
	 * @param array $fields
	 * @param bool  $weak
	 *
	 * @return \StdClass
	 */
	public function Extract ($fields = null, $weak = false) {
		$output = new \StdClass();

		$model = clone $this->_model;

		if ($model instanceof IQuarkModelWithBeforeExtract) {
			$out = $model->BeforeExtract();

			if ($out !== null)
				return $out;
		}

		foreach ($model as $key => $value) {
			$property = QuarkObject::Property($fields, $key, null);

			$output->$key = $value instanceof QuarkModel
				? $value->Extract($property)
				: ($value instanceof QuarkCollection
					? $value->Collection(function ($item) use ($property) {
						return $item instanceof QuarkModel ? $item->Extract($property) : $item;
					})
					: $value);
		}

		if (func_num_args() == 0 || $fields === null) return $output;

		$buffer = new \StdClass();
		$property = null;

		$backbone = $weak ? $model->Fields() : $fields;

		foreach ($backbone as $field => $rule) {
			if (property_exists($output, $field))
				$buffer->$field = QuarkObject::Property($output, $field, null);

			if ($weak && !isset($fields[$field])) continue;
			else {
				if (is_string($rule) && property_exists($output, $rule))
					$buffer->$rule = QuarkObject::Property($output, $rule, null);
			}
		}

		return $buffer;
	}

	/**
	 * @return bool
	 */
	public function Validate () {
		return self::_validate($this->_model);
	}

	/**
	 * @return array|bool
	 */
	public function ValidationRules () {
		return self::_validate($this->_model, false);
	}

	/**
	 * @param string $name
	 * @param array $options
	 *
	 * @return bool
	 */
	private function _op ($name, $options = []) {
		$name = ucfirst(strtolower($name));

		$hook = 'Before' . $name;
		$model = self::_export(clone $this->_model, $options);

		if (!$model) return false;

		$ok = QuarkObject::is($model, 'Quark\IQuarkModelWith' . $hook)
			? $model->$hook($options)
			: true;

		if ($ok !== null && !$ok) return false;

		$out = self::_provider($model)->$name($model, $options);
		$this->PopulateWith($model);

		return $out;
	}

	/**
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function Create ($options = []) {
		return $this->_op('Create', $options);
	}

	/**
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function Save ($options = []) {
		return $this->_op('Save', $options);
	}

	/**
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function Remove ($options = []) {
		return $this->_op('Remove', $options);
	}

	/**
	 * @return mixed
	 * @throws QuarkArchException
	 */
	public function PrimaryKey () {
		$pk = self::_provider($this->_model)->PrimaryKey($this->_model);

		if ($this->_model instanceof IQuarkModelWithCustomPrimaryKey)
			$pk = $this->_model->PrimaryKey();

		return $this->$pk;
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 * @param callable(QuarkModel) $after = null
	 *
	 * @return QuarkCollection
	 */
	public static function Find (IQuarkModel $model, $criteria = [], $options = [], callable $after = null) {
		$records = array();
		$raw = self::_provider($model)->Find($model, $criteria, $options);

		if ($raw != null)
			foreach ($raw as $item)
				$records[] = self::_record($model, $item, $options, $after);

		return isset($options[self::OPTION_EXTRACT])
			? $records
			: new QuarkCollection($model, $records);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 * @param callable(QuarkModel) $after = null
	 *
	 * @return QuarkModel|null
	 */
	public static function FindOne (IQuarkModel $model, $criteria = [], $options = [], callable $after = null) {
		return self::_record($model, self::_provider($model)->FindOne($model, $criteria, $options), $options, $after);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $id
	 * @param $options
	 * @param callable(QuarkModel) $after = null
	 *
	 * @return QuarkModel|null
	 */
	public static function FindOneById (IQuarkModel $model, $id, $options = [], callable $after= null) {
		return self::_record($model, self::_provider($model)->FindOneById($model, $id, $options), $options, $after);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $limit
	 * @param $skip
	 * @param $options
	 *
	 * @return int
	 */
	public static function Count (IQuarkModel $model, $criteria = [], $limit = 0, $skip = 0, $options = []) {
		return (int)self::_provider($model)->Count($model, $criteria, $limit, $skip, $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return mixed
	 */
	public static function Update (IQuarkModel $model, $criteria = [], $options = []) {
		$model = self::_export($model, $options);

		if (!$model) return false;

		$ok = $model instanceof IQuarkModelWithBeforeSave
			? $model->BeforeSave($options)
			: true;

		return ($ok || $ok === null) ? self::_provider($model)->Update($model, $criteria, $options) : false;
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return mixed
	 */
	public static function Delete (IQuarkModel $model, $criteria = [], $options = []) {
		$model = self::_export($model, $options);

		if (!$model) return false;

		$ok = $model instanceof IQuarkModelWithBeforeRemove
			? $model->BeforeRemove($options)
			: true;

		return ($ok || $ok === null) ? self::_provider($model)->Delete($model, $criteria, $options) : false;
	}
}

/**
 * Interface IQuarkModel
 *
 * @package Quark
 */
interface IQuarkModel extends IQuarkPrimitive {
	/**
	 * @return mixed
	 */
	public function Fields();

	/**
	 * @return mixed
	 */
	public function Rules();
}

/**
 * Interface IQuarkModelWithDataProvider
 *
 * @package Quark
 */
interface IQuarkModelWithDataProvider {
	/**
	 * @return string
	 */
	public function DataProvider();
}

/**
 * Interface IQuarkLinkedModel
 *
 * @package Quark
 */
interface IQuarkLinkedModel {
	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Link($raw);

	/**
	 * @return mixed
	 */
	public function Unlink();
}

/**
 * Interface IQuarkStrongModel
 *
 * @package Quark
 */
interface IQuarkStrongModel { }

/**
 * Interface IQuarkNullableModel
 *
 * @package Quark
 */
interface IQuarkNullableModel { }

/**
 * Interface IQuarkModelWithCustomPrimaryKey
 *
 * @package Quark
 */
interface IQuarkModelWithCustomPrimaryKey {
	/**
	 * @return string
	 */
	public function PrimaryKey();
}

/**
 * Interface IQuarkModelWithAfterFind
 *
 * @package Quark
 */
interface IQuarkModelWithAfterFind {
	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function AfterFind($raw);
}

/**
 * Interface IQuarkModelWithOnPopulate
 *
 * @package Quark
 */
interface IQuarkModelWithOnPopulate {
	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function OnPopulate($raw);
}

/**
 * Interface IQuarkModelWithBeforeSave
 *
 * @package Quark
 */
interface IQuarkModelWithBeforeCreate {
	/**
	 * @param $options
	 *
	 * @return mixed
	 */
	public function BeforeCreate($options);
}

/**
 * Interface IQuarkModelWithBeforeSave
 *
 * @package Quark
 */
interface IQuarkModelWithBeforeSave {
	/**
	 * @param $options
	 *
	 * @return mixed
	 */
	public function BeforeSave($options);
}

/**
 * Interface IQuarkModelWithBeforeRemove
 *
 * @package Quark
 */
interface IQuarkModelWithBeforeRemove {
	/**
	 * @param $options
	 *
	 * @return mixed
	 */
	public function BeforeRemove($options);
}

/**
 * Interface IQuarkModelWithBeforeValidate
 *
 * @package Quark
 */
interface IQuarkModelWithBeforeValidate {
	/**
	 * @return mixed
	 */
	public function BeforeValidate();
}

/**
 * Interface IQuarkModelWithBeforeExtract
 *
 * @package Quark
 */
interface IQuarkModelWithBeforeExtract {
	/**
	 * @return mixed
	 */
	public function BeforeExtract();
}

/**
 * Interface IQuarkDataProvider
 *
 * @package Quark
 */
interface IQuarkDataProvider {
	/**
	 * @param QuarkURI $uri
	 *
	 * @return mixed
	 */
	public function Connect(QuarkURI $uri);

	/**
	 * @param IQuarkModel $model
	 *
	 * @return mixed
	 */
	public function Create(IQuarkModel $model);

	/**
	 * @param IQuarkModel $model
	 *
	 * @return mixed
	 */
	public function Save(IQuarkModel $model);

	/**
	 * @param IQuarkModel $model
	 *
	 * @return mixed
	 */
	public function Remove(IQuarkModel $model);

	/**
	 * @param IQuarkModel $model
	 *
	 * @return string
	 */
	public function PrimaryKey (IQuarkModel $model);

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 * @param             $options
	 *
	 * @return array
	 */
	public function Find(IQuarkModel $model, $criteria, $options);

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 * @param             $options
	 *
	 * @return mixed
	 */
	public function FindOne(IQuarkModel $model, $criteria, $options);

	/**
	 * @param IQuarkModel $model
	 * @param             $id
	 * @param             $options
	 *
	 * @return mixed
	 */
	public function FindOneById(IQuarkModel $model, $id, $options);

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 * @param             $options
	 *
	 * @return mixed
	 */
	public function Update(IQuarkModel $model, $criteria, $options);

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 * @param             $options
	 *
	 * @return mixed
	 */
	public function Delete(IQuarkModel $model, $criteria, $options);

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 * @param             $limit
	 * @param             $skip
	 * @param             $options
	 *
	 * @return int
	 */
	public function Count (IQuarkModel $model, $criteria, $limit, $skip, $options);
}

/**
 * Class QuarkField
 *
 * @package Quark
 */
class QuarkField {
	const TYPE_BOOL = 'bool';
	const TYPE_INT = 'int';
	const TYPE_FLOAT = 'float';
	const TYPE_STRING = 'string';

	const TYPE_ARRAY = 'array';
	const TYPE_OBJECT = 'object';

	const TYPE_RESOURCE = 'resource';
	const TYPE_NULL = 'null';

	/**
	 * @param      $key
	 * @param bool $nullable
	 *
	 * @return bool
	 */
	public static function Valid ($key, $nullable = false) {
		if ($nullable && $key == null) return true;

		if ($key instanceof IQuarkModel)
			$key = new QuarkModel($key);

		return $key instanceof QuarkModel ? $key->Validate() : false;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $nullable
	 * @return bool
	 */
	public static function Type ($key, $value, $nullable = false) {
		if ($nullable && $key == null) return true;

		$comparator = 'is_' . $value;

		return $comparator($key);
	}

	/**
	 * @param      $key
	 * @param      $value
	 * @param bool $sever
	 * @param bool $nullable
	 *
	 * @return bool
	 */
	public static function Eq ($key, $value, $sever = false, $nullable = false) {
		if ($nullable && $key == null) return true;

		return $sever ? $key === $value : $key == $value;
	}

	/**
	 * @param      $key
	 * @param      $value
	 * @param bool $sever
	 * @param bool $nullable
	 *
	 * @return bool
	 */
	public static function Ne ($key, $value, $sever = false, $nullable = false) {
		if ($nullable && $key == null) return true;

		return $sever ? $key !== $value : $key != $value;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $nullable
	 * @return bool
	 */
	public static function Lt ($key, $value, $nullable = false) {
		if ($nullable && $key == null) return true;

		return $key < $value;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $nullable
	 * @return bool
	 */
	public static function Gt ($key, $value, $nullable = false) {
		if ($nullable && $key == null) return true;

		return $key > $value;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $nullable
	 * @return bool
	 */
	public static function Lte ($key, $value, $nullable = false) {
		if ($nullable && $key == null) return true;

		return $key <= $value;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $nullable
	 * @return bool
	 */
	public static function Gte ($key, $value, $nullable = false) {
		if ($nullable && $key == null) return true;

		return $key >= $value;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $nullable
	 * @return bool
	 */
	public static function MinLengthInclusive ($key, $value, $nullable = false) {
		if ($nullable && $key == null) return true;

		return is_array($key) ? sizeof($key) >= $value : strlen((string)$key) >= $value;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $nullable
	 * @return bool
	 */
	public static function MinLength ($key, $value, $nullable = false) {
		if ($nullable && $key == null) return true;

		return is_array($key) ? sizeof($key) > $value : strlen((string)$key) > $value;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $nullable
	 * @return bool
	 */
	public static function Length ($key, $value, $nullable = false) {
		if ($nullable && $key == null) return true;

		return is_array($key) ? sizeof($key) == $value : strlen((string)$key) == $value;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $nullable
	 * @return bool
	 */
	public static function MaxLength ($key, $value, $nullable = false) {
		if ($nullable && $key == null) return true;

		return is_array($key) ? sizeof($key) < $value : strlen((string)$key) < $value;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $nullable
	 * @return bool
	 */
	public static function MaxLengthInclusive ($key, $value, $nullable = false) {
		if ($nullable && $key == null) return true;

		return is_array($key) ? sizeof($key) <= $value : strlen((string)$key) <= $value;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $nullable
	 * @return bool|int
	 */
	public static function Match ($key, $value, $nullable = false) {
		if ($nullable && $key == null) return true;

		return preg_match($value, $key);
	}

	/**
	 * @param       $key
	 * @param array $values
	 * @param bool  $nullable
	 *
	 * @return bool
	 */
	public static function Enum ($key, $values = [], $nullable = false) {
		if ($nullable && $key == null) return true;

		return is_array($values) && in_array($key, $values, true);
	}

	/**
	 * @param string $type
	 * @param mixed $key
	 * @param bool $nullable
	 * @param null $culture
	 * @return bool
	 */
	private static function _dateTime ($type, $key, $nullable = false, $culture = null) {
		if ($nullable && $key == null) return true;

		if ($culture == null)
			$culture = Quark::Config()->Culture();

		$format = $type . 'Format';

		/**
		 * code snippet from http://php.net/manual/ru/function.checkdate.php#113205
		 */
		$date = \DateTime::createFromFormat($culture->$format(), $key);

		return $date && $date->format($culture->$format()) == $key;
	}

	/**
	 * @param $key
	 * @param bool $nullable
	 * @param null $culture
	 * @return bool|int
	 */
	public static function DateTime ($key, $nullable = false, $culture = null) {
		return self::_dateTime('DateTime', $key, $nullable, $culture);
	}

	/**
	 * @param $key
	 * @param bool $nullable
	 * @param null $culture
	 * @return bool
	 */
	public static function Date ($key, $nullable = false, $culture = null) {
		return self::_dateTime('Date', $key, $nullable, $culture);
	}

	/**
	 * @param $key
	 * @param bool $nullable
	 * @param null $culture
	 * @return bool
	 */
	public static function Time ($key, $nullable = false, $culture = null) {
		return self::_dateTime('Time', $key, $nullable, $culture);
	}

	/**
	 * @param      $key
	 * @param bool $nullable
	 *
	 * @return bool
	 */
	public static function Email ($key, $nullable = false) {
		if ($nullable && $key == null) return true;
		if (!is_string($key)) return false;

		return preg_match('#(.*)\@(.*)#Uis', $key);
	}

	/**
	 * @param      $key
	 * @param bool $nullable
	 *
	 * @return bool
	 */
	public static function Phone ($key, $nullable = false) {
		if ($nullable && $key == null) return true;
		if (!is_string($key)) return false;

		return preg_match('#^\+[0-9]#', $key);
	}

	/**
	 * @param $key
	 * @param $values
	 * @param bool $nullable
	 * @return bool
	 */
	public static function In ($key, $values, $nullable = false) {
		if ($nullable && $key == null) return true;

		return in_array($key, $values, true);
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $field
	 *
	 * @return bool
	 */
	public static function Unique (IQuarkModel $model, $field) {
		return QuarkModel::Count($model, array($field => $model->$field)) == 0;
	}

	/**
	 * @param $key
	 * @param $model
	 * @param bool $nullable
	 *
	 * @return bool
	 */
	public static function CollectionOf ($key, $model, $nullable = false) {
		if ($nullable && $key == null) return true;

		if (!is_array($key)) return false;

		foreach ($key as $item)
			if (!($item instanceof $model)) return false;

		return true;
	}

	/**
	 * @param $rules
	 * @return bool
	 */
	public static function Rules ($rules) {
		if (!is_array($rules))
			return $rules == null ? true : (bool)$rules;

		$ok = true;

		foreach ($rules as $rule)
			$ok = $ok && $rule;

		return $ok;
	}
}

/**
 * Class QuarkLocalizedString
 *
 * @package Quark
 */
class QuarkLocalizedString implements IQuarkModel, IQuarkLinkedModel {
	const LANGUAGE_ANY = '*';
	const LANGUAGE_EN_US = 'en-US';
	const LANGUAGE_RU_RU = 'ru-RU';
	const LANGUAGE_MD = 'md';

	public $values = '';
	public $default = self::LANGUAGE_ANY;

	/**
	 * @param string $value
	 * @param string $language = self::LANGUAGE_ANY
	 * @param string $default = self::LANGUAGE_ANY
	 */
	public function __construct ($value = '', $language = self::LANGUAGE_ANY, $default = self::LANGUAGE_ANY) {
		$this->values = new \StdClass();
		$this->default = $default;

		if (func_num_args() != 0 && is_scalar($value))
			$this->values->$language = $value;
	}

	/**
	 * @return string
	 */
	public function __toString () {
		return $this->Of($this->default);
	}

	/**
	 * @param string $language
	 * @param string $value
	 *
	 * @return string
	 */
	public function Of ($language, $value = '') {
		if (func_num_args() == 2 && is_scalar($value))
			$this->values->$language = (string)$value;

		return isset($this->values->$language) ? (string)$this->values->$language : '';
	}

	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'values' => '',
			'default' => ''
		);
	}

	/**
	 * @return mixed
	 */
	public function Rules () {
		return array(
			QuarkField::Type($this->default, QuarkField::TYPE_STRING)
		);
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Link ($raw) {
		return new QuarkModel($this, json_decode($raw));
	}

	/**
	 * @return mixed
	 */
	public function Unlink () {
		return json_encode(array(
			'values' => $this->values,
			'default' => $this->default
		));
	}
}

/**
 * Class QuarkDate
 *
 * @package Quark
 */
class QuarkDate implements IQuarkModel, IQuarkLinkedModel, IQuarkModelWithOnPopulate, IQuarkModelWithBeforeExtract {
	const NOW = 'now';
	const GMT = 'UTC';
	const CURRENT = '';

	/**
	 * @var IQuarkCulture|QuarkCultureISO $_culture
	 */
	private $_culture;

	/**
	 * @var \DateTime $_date
	 */
	private $_date;

	/**
	 * @param IQuarkCulture $culture
	 * @param string $value = self::NOW
	 * @param string $timezone = self::CURRENT
	 */
	public function __construct (IQuarkCulture $culture = null, $value = self::NOW, $timezone = self::CURRENT) {
		$this->_culture = $culture ? $culture : Quark::Config()->Culture();
		$this->Value($value);
		$this->Timezone($timezone);
	}

	/**
	 * @return string
	 */
	public function __toString () {
		return $this->DateTime();
	}

	/**
	 * @param IQuarkCulture $culture
	 *
	 * @return IQuarkCulture|QuarkCultureISO
	 */
	public function Culture (IQuarkCulture $culture = null) {
		if (func_num_args() != 0)
			$this->_culture = $culture;

		return $this->_culture;
	}

	/**
	 * @param string $value
	 *
	 * @return \DateTime
	 */
	public function Value ($value = '') {
		if (func_num_args() != 0 && is_string($value))
			$this->_date = new \DateTime($value, new \DateTimeZone('UTC'));

		return $this->_date;
	}

	/**
	 * @param string $timezone
	 *
	 * @return string
	 */
	public function Timezone ($timezone = self::CURRENT) {
		if ($this->_date == null)
			$this->Value('now');

		if (func_num_args() != 0 && $timezone != self::CURRENT)
			$this->_date->setTimezone(new \DateTimeZone($timezone));

		return $this->_date->getTimezone()->getName();
	}

	/**
	 * @return string
	 */
	public function DateTime () {
		return $this->_date->format($this->_culture->DateTimeFormat());
	}

	/**
	 * @return string
	 */
	public function Date () {
		return $this->_date->format($this->_culture->DateFormat());
	}

	/**
	 * @return string
	 */
	public function Time () {
		return $this->_date->format($this->_culture->TimeFormat());
	}

	/**
	 * @param QuarkDate $with
	 *
	 * @return int
	 */
	public function Interval (QuarkDate $with = null) {
		if ($with == null) return 0;

		$start = $this->_date->getTimestamp();
		$end = $with->Value()->getTimestamp();

		return $end - $start;
	}

	/**
	 * @param string $offset
	 *
	 * @return QuarkDate
	 */
	public function Offset ($offset) {
		$this->_date->modify($offset);

		return $this;
	}

	/**
	 * @param QuarkDate $from
	 * @param int $offset = 0
	 *
	 * @return bool
	 */
	public function Expired (QuarkDate $from = null, $offset = 0) {
		if ($from == null)
			$from = new self();

		return $this->Interval($from) > $offset;
	}

	/**
	 * @param string $format
	 *
	 * @return string
	 */
	public function Format ($format = '') {
		return $this->_date->format($format);
	}

	/**
	 * @param string $format
	 *
	 * @return QuarkDate
	 */
	public static function Now ($format = '') {
		return self::FromFormat($format);
	}

	/**
	 * @param string $format
	 *
	 * @return QuarkDate
	 */
	public static function GMTNow ($format = '') {
		return self::FromFormat($format, self::NOW, self::GMT);
	}

	/**
	 * @param string $date
	 *
	 * @return QuarkDate
	 */
	public static function GMTOf ($date) {
		return new self(null, $date, self::GMT);
	}

	/**
	 * @param string $format
	 * @param string $value = self::NOW
	 * @param string $timezone = self::CURRENT
	 *
	 * @return QuarkDate
	 */
	public static function FromFormat ($format, $value = self::NOW, $timezone = self::CURRENT) {
		return new self(QuarkCultureCustom::Format($format), $value, $timezone);
	}

	/**
	 * @param string $timezone
	 *
	 * @return int
	 */
	public static function TimezoneOffset ($timezone) {
		return (new \DateTimeZone($timezone))->getOffset(self::GMTNow()->Value());
	}

	/**
	 * @param int $time
	 *
	 * @return string
	 */
	public static function FancyTime ($time) {
		$offset = $time / 3600;

		$hours = floor($offset);
		$minutes = ($offset - $hours) * 60;
		$seconds = ($minutes - floor($minutes)) * 60;

		$dir = $hours >= 0;
		$one = abs($hours) < 10;

		$hours = ($dir ? '+' : '-') . ($one ? '0' : '') . abs($hours);

		if ($minutes < 10)
			$minutes = '0' . $minutes;

		if ($seconds < 10)
			$seconds = '0' . $seconds;

		return $hours . ':' . $minutes . ':' . $seconds;
	}

	/**
	 * @return mixed
	 */
	public function Fields () {
		// TODO: Implement Fields() method.
	}

	/**
	 * @return mixed
	 */
	public function Rules () {
		// TODO: Implement Rules() method.
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Link ($raw) {
		return new QuarkModel($this, $raw);
	}

	/**
	 * @return mixed
	 */
	public function Unlink () {
		return $this->DateTime();
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function OnPopulate ($raw) {
		$this->Value($raw);
	}

	/**
	 * @return mixed
	 */
	public function BeforeExtract () {
		return $this->DateTime();
	}
}

/**
 * Class QuarkSessionSource
 *
 * @package Quark
 */
class QuarkSessionSource implements IQuarkStackable {
	/**
	 * @var string $_name
	 */
	private $_name = '';

	/**
	 * @var IQuarkAuthorizationProvider $_provider
	 */
	private $_provider;

	/**
	 * @var IQuarkAuthorizableModel $_user
	 */
	private $_user;

	/**
	 * @param string $name
	 * @param IQuarkAuthorizationProvider $provider
	 * @param IQuarkAuthorizableModel $user
	 */
	public function __construct ($name = '', IQuarkAuthorizationProvider $provider = null, IQuarkAuthorizableModel $user = null) {
		if (func_num_args() == 0) return;

		$this->_name = $name;
		$this->_provider = $provider;
		$this->_user = $user;

		new QuarkModel($this->_user);
	}

	/**
	 * @return string
	 */
	public function &Name () {
		return $this->_name;
	}

	/**
	 * @return IQuarkAuthorizationProvider
	 */
	public function &Provider () {
		return $this->_provider;
	}

	/**
	 * @return IQuarkAuthorizableModel
	 */
	public function &User () {
		return $this->_user;
	}

	/**
	 * @param QuarkDTO $input
	 *
	 * @return bool
	 */
	public function Recognize (QuarkDTO $input) {
		return $input->AuthorizationProvider()->Key() == $this->_name || $this->_provider->Recognize($this->_name, $this->_user, $input);
	}
}

/**
 * Class QuarkSession
 *
 * @package Quark
 */
class QuarkSession implements IQuarkTickable {
	const TICKABLE_KEY = 'Quark.QuarkSession';

	/**
	 * @var QuarkModel|IQuarkAuthorizableModel $_user
	 */
	private $_user;

	/**
	 * @var QuarkSessionSource $_source
	 */
	private $_source;

	/**
	 * @var null $_null
	 */
	private $_null = null;

	/**
	 * @param $key
	 *
	 * @return mixed
	 */
	public function __get ($key) {
		return isset($this->_user->$key) ? $this->_user->$key : $this->_null;
	}

	/**
	 * @param $key
	 * @param $value
	 */
	public function __set ($key, $value) {
		$this->_user->$key = $value;
	}

	/**
	 * @param $key
	 *
	 * @return bool
	 */
	public function __isset ($key) {
		return isset($this->_user->$key);
	}

	/**
	 * @param $name
	 */
	public function __unset ($name) {
		unset($this->_user->$name);
	}

	/**
	 * @param QuarkSessionSource $source
	 */
	public function __construct (QuarkSessionSource $source = null) {
		if (func_num_args() == 0) return;

		$this->_source = clone $source;
	}

	/**
	 * @return bool
	 */
	public function Exclusive () {
		return true;
	}

	/**
	 * @param QuarkDTO $input
	 *
	 * @return bool
	 */
	public function Input (QuarkDTO $input) {
		if (!$this->_source) return false;

		$session = $this->_source->Provider()->Input(
			$this->_source->Name(),
			$this->_source->User(),
			$input,
			$input->AuthorizationProvider()->Value() === false
		);

		if (!$session && $session !== null) return false;

		$this->_user = $this->_source->User()->Session($this->_source->Name(), $this->_source->Provider()->User());

		return $this->_user != null;
	}

	/**
	 * @param $criteria
	 * @param int $lifetime = 0 (seconds)
	 *
	 * @return bool
	 */
	public function Login ($criteria, $lifetime = 0) {
		if (!$this->_source) return false;

		$user = $this->_source->User()->Login($this->_source->Name(), $criteria, $lifetime);

		if (!$user) return false;

		$this->_user = $user;
		$this->_source->Provider()->User($this->_user);

		$login = $this->_source->Provider()->Login($criteria, $lifetime);

		return $this->_user != null
			? $login || $login === null
			: false;
	}

	/**
	 * @return bool
	 */
	public function Logout () {
		if (!$this->_source || !$this->_user) return false;

		$logout = $this->_user->Logout($this->_source->Name());

		if ($logout === null) $logout = true;
		if (!$logout) return false;

		$logout = $this->_source->Provider()->Logout();

		if ($logout === null) $logout = true;
		if (!$logout) return false;

		$this->_user = null;

		return true;
	}

	/**
	 * @return QuarkDTO
	 */
	public function Output () {
		return $this->_source ? $this->_source->Provider()->Output() : null;
	}

	/**
	 * @return string
	 */
	public function Signature () {
		return $this->_source ? $this->_source->Provider()->Signature() : '';
	}

	/**
	 * @param QuarkModel $user
	 *
	 * @return QuarkModel
	 */
	public function &User (QuarkModel $user = null) {
		if (func_num_args() != 0)
			$this->_user = $user;

		return $this->_user;
	}

	/**
	 * @return bool
	 */
	public function Authorized () {
		return $this->_user != null;
	}

	/**
	 * @return QuarkKeyValuePair
	 */
	public function ID () {
		$output = $this->_source && $this->_source->Provider() instanceof IQuarkAuthorizationProvider
			? $this->_source->Provider()->Output()
			: null;

		return $output instanceof QuarkDTO
			? $output->AuthorizationProvider()
			: null;
	}

	/**
	 * @param string $name
	 *
	 * @return QuarkSession
	 */
	public static function Get ($name) {
		/**
		 * @var QuarkSessionSource $source
		 */
		$source = Quark::Stack($name);

		return new QuarkSession($source);
	}

	/**
	 * @param QuarkKeyValuePair $id
	 *
	 * @return QuarkSession
	 */
	public static function Restore (QuarkKeyValuePair $id = null) {
		if (!$id)
			return new self();

		$session = self::Get($id->Key());

		$input = new QuarkDTO();
		$input->AuthorizationProvider($id);

		$session->Input($input);

		return $session;
	}
}

/**
 * Interface IQuarkAuthorizationProvider
 *
 * @package Quark
 */
interface IQuarkAuthorizationProvider {
	/**
	 * @param string $name
	 * @param IQuarkAuthorizableModel $user
	 * @param QuarkDTO $input
	 *
	 * @return bool
	 */
	public function Recognize($name, IQuarkAuthorizableModel $user, QuarkDTO $input);

	/**
	 * @param string $name
	 * @param IQuarkAuthorizableModel $user
	 * @param QuarkDTO $input
	 * @param bool $http
	 *
	 * @return bool
	 */
	public function Input($name, IQuarkAuthorizableModel $user, QuarkDTO $input, $http);

	/**
	 * @param $criteria
	 * @param int $lifetime (seconds)
	 *
	 * @return bool
	 */
	public function Login($criteria, $lifetime);

	/**
	 * @param QuarkModel $user
	 *
	 * @return QuarkModel
	 */
	public function User(QuarkModel $user = null);

	/**
	 * @return bool
	 */
	public function Logout();

	/**
	 * @return QuarkDTO
	 */
	public function Output();

	/**
	 * @return string
	 */
	public function Signature();
}

/**
 * Interface IQuarkAuthorizableModel
 *
 * @package Quark
 */
interface IQuarkAuthorizableModel extends IQuarkModel {
	/**
	 * @param string $name
	 * @param $session
	 *
	 * @return mixed
	 */
	public function Session($name, $session);

	/**
	 * @param string $name
	 * @param $criteria
	 * @param int $lifetime (seconds)
	 *
	 * @return QuarkModel
	 */
	public function Login($name, $criteria, $lifetime);

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function Logout($name);

}

/**
 * Class QuarkKeyValuePair
 *
 * @package Quark
 */
class QuarkKeyValuePair {
	private $_key;
	private $_value;

	/**
	 * @param $key
	 * @param $value
	 */
	public function __construct ($key = '', $value = '') {
		$this->_key = $key;
		$this->_value = $value;
	}

	/**
	 * @param $key
	 *
	 * @return mixed
	 */
	public function Key ($key = '') {
		if (func_num_args() != 0)
			$this->_key = $key;

		return $this->_key;
	}

	/**
	 * @param $value
	 *
	 * @return mixed
	 */
	public function Value ($value = '') {
		if (func_num_args() != 0)
			$this->_value = $value;

		return $this->_value;
	}

	/**
	 * @return QuarkCookie
	 */
	public function ToCookie () {
		return new QuarkCookie($this->_key, $this->_value);
	}

	/**
	 * @return object
	 */
	public function Extract () {
		return (object)array($this->_key => $this->_value);
	}
}

/**
 * Trait QuarkNetwork
 *
 * @package Quark
 */
trait QuarkNetwork {
	/**
	 * @var IQuarkTransportProvider $_transport
	 */
	private $_transport;

	/**
	 * @var QuarkURI $_uri
	 */
	private $_uri;

	/**
	 * @var QuarkCertificate $_certificate
	 */
	private $_certificate;

	/**
	 * @var int $_timeout = 0
	 */
	private $_timeout = 0;

	/**
	 * @var resource $_socket
	 */
	private $_socket;

	/**
	 * @var int $_errorNumber
	 */
	private $_errorNumber = 0;

	/**
	 * @var string $_errorString
	 */
	private $_errorString = '';

	/**
	 * @param QuarkURI $uri
	 *
	 * @return QuarkURI
	 */
	public function URI (QuarkURI $uri = null) {
		if (func_num_args() == 1 && $uri != null)
			$this->_uri = $uri;

		return $this->_uri;
	}

	/**
	 * @param bool $remote = false
	 * @param bool|string $face = false
	 *
	 * @return QuarkURI
	 */
	public function ConnectionURI ($remote = false, $face = false) {
		if (!$this->_socket) return null;

		$uri = QuarkURI::FromURI(stream_socket_get_name($this->_socket, $remote));

		if (!$uri) return null;

		$uri->scheme = $this->_uri->scheme;

		if ($face && $uri->host == QuarkServer::ALL_INTERFACES)
			$uri->host = Quark::IP(is_bool($face) ? $uri->host : $face);

		return $uri;

	}

	/**
	 * @param QuarkCertificate $certificate
	 *
	 * @return QuarkCertificate
	 */
	public function Certificate (QuarkCertificate $certificate = null) {
		if (func_num_args() == 1 && $certificate != null)
			$this->_certificate = $certificate;

		return $this->_certificate;
	}

	/**
	 * @param IQuarkTransportProvider $transport
	 *
	 * @return IQuarkTransportProvider
	 */
	public function Transport (IQuarkTransportProvider $transport = null) {
		if (func_num_args() == 1 && $transport != null)
			$this->_transport = $transport;

		return $this->_transport;
	}

	/**
	 * @param int $timeout = 0
	 *
	 * @return int
	 */
	public function Timeout ($timeout = 0) {
		if (func_num_args() == 1 && is_int($timeout)) {
			$this->_timeout = $timeout;

			if ($this->_socket)
				stream_set_timeout($this->_socket, $this->_timeout);
		}

		return $this->_timeout;
	}

	/**
	 * @param $socket
	 *
	 * @return mixed
	 */
	public function Socket ($socket = null) {
		if (func_num_args() == 1)
			$this->_socket = $socket;

		return $this->_socket;
	}

	/**
	 * @param resource $socket
	 * @param string $data
	 *
	 * @return bool
	 */
	private function _send ($socket, $data) {
		try {
			$out = @fwrite($socket, $data) !== false;

			return $out;
		}
		catch (\Exception $e) {
			return self::_err($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * @param resource $socket
	 * @param bool $stream
	 * @param int $max
	 * @param int $offset
	 *
	 * @return mixed
	 */
	private function _receive ($socket, $stream = true, $max = -1, $offset = -1) {
		try {
			return $socket
				? ($stream
					? stream_get_contents($socket, $max, $offset)
					: ($max == -1 ? fgets($socket) : fgets($this->_socket, $max))
				)
				: false;
		}
		catch (\Exception $e) {
			return self::_err($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * @param resource $socket
	 * http://php.net/manual/ru/function.stream-socket-shutdown.php#109982
	 *
	 * @return bool
	 */
	private function _close ($socket) {
		try {
			return $socket ? stream_socket_shutdown($socket, STREAM_SHUT_RDWR) : false;
		}
		catch (\Exception $e) {
			return self::_err($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * @param string $msg
	 * @param int $number
	 *
	 * @return bool
	 */
	private static function _err ($msg, $number = 0) {
		return Quark::Dispatch(Quark::EVENT_CONNECTION_EXCEPTION, $number, $msg) && false;
	}

	/**
	 * @param bool $text
	 *
	 * @return string|object
	 */
	public function Error ($text = false) {
		return $text
			? $this->_errorNumber . ': ' . $this->_errorString
			: (object)array(
				'num' => $this->_errorNumber,
				'msg' => $this->_errorString
			);
	}
}

/**
 * Class QuarkClient
 *
 * @package Quark
 */
class QuarkClient {
	const MODE_STREAM = 'stream';
	const MODE_BUCKET = 'bucket';

	use QuarkNetwork;

	/**
	 * @var bool $_connected
	 */
	private $_connected = false;
	private $_blocking = true;
	private $_send;

	/**
	 * @var QuarkURI $_remote
	 */
	private $_remote;

	/**
	 * @var QuarkKeyValuePair $_session
	 */
	private $_session;

	/**
	 * @var callable $_onConnect
	 */
	private $_onConnect;

	/**
	 * @var callable $_onData
	 */
	private $_onData;

	/**
	 * @var callable $_onClose
	 */
	private $_onClose;

	/**
	 * @param QuarkURI|string $uri
	 * @param IQuarkTransportProvider $transport
	 * @param QuarkCertificate $certificate
	 * @param int $timeout = 0
	 * @param bool $block = true
	 */
	public function __construct ($uri = '', IQuarkTransportProvider $transport = null, QuarkCertificate $certificate = null, $timeout = 0, $block = true) {
		$this->URI(QuarkURI::FromURI($uri));
		$this->Transport($transport);
		$this->Certificate($certificate);
		$this->Timeout($timeout);
		$this->Blocking($block);
	}

	/**
	 * @return bool
	 */
	public function Connect () {
		if ($this->_uri->IsNull())
			return self::_err('QuarkClient URI is null', 125000);

		$stream = stream_context_create();

		if ($this->_certificate == null) {
			stream_context_set_option($stream, 'ssl', 'verify_host', false);
			stream_context_set_option($stream, 'ssl', 'verify_peer', false);
		}
		else {
			stream_context_set_option($stream, 'ssl', 'local_cert', $this->_certificate->Location());
			stream_context_set_option($stream, 'ssl', 'passphrase', $this->_certificate->Passphrase());
		}

		$this->_socket = @stream_socket_client(
			$this->_uri->Socket(),
			$this->_errorNumber,
			$this->_errorString,
			$this->_timeout,
			STREAM_CLIENT_CONNECT,
			$stream
		);

		if (!$this->_socket || $this->_errorNumber != 0)
			return self::_err($this->_errorString, $this->_errorNumber);

		$this->Blocking($this->_blocking);
		$this->Timeout($this->_timeout);

		if ($this->ConnectionURI() == $this->ConnectionURI(true)) return $this->Close(false);

		$this->_connected = true;
		$this->_remote = QuarkURI::FromURI($this->ConnectionURI(true));
		$this->_on('Connect');

		return true;
	}

	/**
	 * @param string $hook
	 * @param array $data
	 *
	 * @return mixed
	 */
	private function _on ($hook, $data = []) {
		$args = array($this);

		if (func_num_args() == 2)
			$args[] = $data;

		$method = $this->{'_on' . $hook};

		if (is_callable($method))
			call_user_func_array($method, $args);

		if ($this->_transport instanceof IQuarkTransportProvider)
			call_user_func_array(array($this->_transport, 'On' . $hook), $args);
	}

	/**
	 * @param callable $connect
	 *
	 * @return callable
	 */
	public function OnConnect (callable $connect = null) {
		if (func_num_args() != 0)
			$this->_onConnect = $connect;

		return $this->_onConnect;
	}

	/**
	 * @param callable $data
	 *
	 * @return callable
	 */
	public function OnData (callable $data = null) {
		if (func_num_args() != 0)
			$this->_onData = $data;

		return $this->_onData;
	}

	/**
	 * @param callable $close
	 *
	 * @return callable
	 */
	public function OnClose (callable $close = null) {
		if (func_num_args() != 0)
			$this->_onClose = $close;

		return $this->_onClose;
	}

	/**
	 * @param string $data
	 *
	 * @return bool
	 */
	public function Send ($data) {
		if (is_callable($this->_send)) {
			$send = $this->_send;
			$data = $send($data);
		}

		return $this->_send($this->_socket, $data);
	}

	/**
	 * @param string $mode
	 * @param int $max
	 * @param int $offset
	 *
	 * @return mixed
	 */
	public function Receive ($mode = self::MODE_STREAM, $max = -1, $offset = -1) {
		return $this->Closed() ? $this->Close() : $this->_receive($this->_socket, $mode == self::MODE_STREAM, $max, $offset);
	}

	/**
	 * @param bool $event = true
	 *
	 * @return bool
	 */
	public function Close ($event = true) {
		$this->_connected = false;

		if ($event)
			$this->_on('Close');

		return $this->_close($this->_socket);
	}

	/**
	 * @return bool
	 */
	public function Pipe () {
		$data = $this->Receive();

		if ($data)
			$this->_on('Data', $data);

		return true;
	}

	/**
	 * @param resource $socket
	 * @param string $address
	 * @param string $scheme
	 *
	 * @return QuarkClient
	 */
	public static function ForServer ($socket, $address, $scheme) {
		$client = new self();

		$client->Socket($socket);

		$client->Blocking(false);
		$client->Timeout(0);

		$uri = QuarkURI::FromURI($address);
		$uri->scheme = $scheme;

		$client->URI($uri);

		return $client;
	}

	/**
	 * @param bool $connected = true
	 *
	 * @return bool
	 */
	public function Connected ($connected = true) {
		if (func_num_args() != 0)
			$this->_connected = $connected;

		return $this->_connected;
	}

	/**
	 * @return bool
	 */
	public function Closed () {
		return $this->_socket && (feof($this->_socket) === true && $this->_connected);
	}

	/**
	 * @param bool $block = true
	 *
	 * @return bool
	 */
	public function Blocking ($block = true) {
		if (func_num_args() != 0) {
			$this->_blocking = $block;

			if ($this->_socket)
				stream_set_blocking($this->_socket, (int)$block);
		}

		return $this->_blocking;
	}

	/**
	 * @param QuarkURI|string $uri
	 *
	 * @return QuarkURI
	 */
	public function Remote ($uri = '') {
		if (func_num_args() != 0)
			$this->_remote = $uri instanceof QuarkURI ? $uri : QuarkURI::FromURI($uri);

		return $this->_remote;
	}

	/**
	 * @param callable $send
	 */
	public function BeforeSend (callable $send) {
		$this->_send = $send;
	}

	/**
	 * @param QuarkKeyValuePair $session
	 *
	 * @return QuarkKeyValuePair
	 */
	public function Session (QuarkKeyValuePair $session = null) {
		if (func_num_args() != 0)
			$this->_session = $session;

		return $this->_session;
	}
}

/**
 * Class QuarkServer
 *
 * @package Quark
 */
class QuarkServer {
	use QuarkNetwork;

	const ALL_INTERFACES = '0.0.0.0';
	const TCP_ALL_INTERFACES_RANDOM_PORT = 'tcp://0.0.0.0:0';

	/**
	 * @var bool $_run
	 */
	private $_run = false;

	private $_read = array();
	private $_write = array();
	private $_except = array();

	/**
	 * @var QuarkClient[] $_clients
	 */
	private $_clients = array();

	/**
	 * @param QuarkURI|string $uri
	 * @param IQuarkTransportProvider $transport
	 * @param QuarkCertificate $certificate
	 * @param int $timeout = 0
	 */
	public function __construct ($uri = '', IQuarkTransportProvider $transport = null, QuarkCertificate $certificate = null, $timeout = 0) {
		$this->URI(QuarkURI::FromURI($uri));
		$this->Transport($transport);
		$this->Certificate($certificate);
		$this->Timeout($timeout);
	}

	/**
	 * @return bool
	 */
	public function Bind () {
		if ($this->_uri->IsNull())
			return self::_err('QuarkServer URI is null', 125000);

		$stream = stream_context_create();

		if ($this->_certificate == null) {
			stream_context_set_option($stream, 'ssl', 'verify_host', false);
			stream_context_set_option($stream, 'ssl', 'verify_peer', false);
		}
		else {
			stream_context_set_option($stream, 'ssl', 'local_cert', $this->_certificate->Location());
			stream_context_set_option($stream, 'ssl', 'passphrase', $this->_certificate->Passphrase());
		}

		$this->_socket = stream_socket_server(
			$this->_uri->Socket(),
			$this->_errorNumber,
			$this->_errorString,
			STREAM_SERVER_BIND|STREAM_SERVER_LISTEN,
			$stream
		);

		if (!$this->_socket)
			return self::_err($this->_errorString, $this->_errorNumber);

		stream_set_blocking($this->_socket, 0);
		$this->Timeout(0);

		$this->_read = array($this->_socket);
		$this->_run = true;

		return true;
	}

	/**
	 * @return bool
	 */
	public function Pipe () {
		if ($this->_socket == null) return false;

		if (sizeof($this->_read) == 0)
			$this->_read = array($this->_socket);

		if (stream_select($this->_read, $this->_write, $this->_except, 0, 0) === false) return true;

		if (in_array($this->_socket, $this->_read, true)) {
			$socket = stream_socket_accept($this->_socket, $this->_timeout, $address);
			$client = QuarkClient::ForServer($socket, $address, $this->URI()->scheme);
			$client->Remote(QuarkURI::FromURI($this->ConnectionURI()));

			$accept = $this->_transport->OnConnect($client);

			if ($accept || $accept === null) {
				$this->_clients[] = $client;
				unset($this->_read[array_search($this->_socket, $this->_read, true)]);
			}
		}

		$this->_read = array();

		foreach ($this->_clients as $key => &$client) {
			$data = $client->Receive(QuarkClient::MODE_BUCKET);

			if ($data !== false)
				$this->_transport->OnData($client, $data);

			if (feof($client->Socket())) {
				unset($this->_clients[$key]);

				$this->_transport->OnClose($client);
				$client->Close();

				continue;
			}

			$this->_read[] = $client->Socket();
		}

		unset($key, $client);

		$this->_read[] = $this->_socket;

		return true;
	}

	/**
	 * @return bool
	 */
	public function Running () {
		return $this->_run;
	}

	/**
	 * @return QuarkServer
	 */
	public function Stop () {
		$this->_run = false;

		return $this;
	}

	/**
	 * @return QuarkClient[]
	 */
	public function Clients () {
		return $this->_clients;
	}

	/**
	 * @param QuarkClient $client
	 *
	 * @return bool
	 */
	public function Has (QuarkClient $client) {
		foreach ($this->_clients as $item)
			if ($item->ConnectionURI()->URI() == $client->ConnectionURI()->URI()) return true;

		return false;
	}
}

/**
 * Class QuarkPeer
 *
 * @package Quark
 */
class QuarkPeer {
	/**
	 * @var IQuarkTransportProvider $_transport
	 */
	private $_transport;

	/**
	 * @var QuarkServer $_server
	 */
	private $_server;

	/**
	 * @var QuarkClient[] $_peers
	 */
	private $_peers = array();

	/**
	 * @param IQuarkTransportProvider $transport
	 * @param QuarkURI|string $bind
	 * @param QuarkURI[]|string[] $connect
	 */
	public function __construct (IQuarkTransportProvider $transport = null, $bind = '', $connect = []) {
		$this->_transport = $transport;
		$this->_server = new QuarkServer($bind, $this->_transport);

		$this->Peers($connect);
	}

	/**
	 * @param QuarkURI $uri
	 *
	 * @return QuarkURI
	 */
	public function URI (QuarkURI $uri = null) {
		if (func_num_args() != 0)
			$this->_server->URI($uri);

		return $this->_server->URI();
	}

	/**
	 * @return bool
	 */
	public function Bind () {
		return $this->_server->Bind();
	}

	/**
	 * @param QuarkClient|QuarkURI|string $peer
	 *
	 * @return bool
	 */
	public function Has ($peer) {
		if ($peer instanceof QuarkClient && $peer->ConnectionURI() != null)
			$peer = $peer->ConnectionURI()->URI();

		$peer = QuarkURI::FromURI($peer);

		if (!$peer) return false;

		foreach ($this->_peers as $item) {
			$uri = $item->ConnectionURI(true, $peer->host);

			if ($uri == null) continue;
			if ($uri->URI() == $peer) return true;
		}

		return false;
	}

	/**
	 * @param QuarkURI|string $uri
	 * @param bool $unique = true
	 * @param bool $loopBack = false
	 *
	 * @return bool
	 */
	public function Peer ($uri = null, $unique = true, $loopBack = false) {
		$uri = QuarkURI::FromURI($uri);

		if (!$uri) return false;

		$server = $this->_server->ConnectionURI(false, $uri->host)->URI();
		$uri = $uri->URI();

		if ($uri == ':///') return false;
		if (!$loopBack && $uri == $server) return false;
		if ($unique && $this->Has($uri)) return false;

		$peer = new QuarkClient($uri, $this->_transport, null, 0, false);
		$ok = $peer->Connect();

		$this->_peers[] = $peer;

		return $ok;
	}

	/**
	 * @param QuarkURI[]|string[] $peers
	 * @param bool $unique = true
	 * @param bool $loopBack = false
	 *
	 * @return QuarkClient[]|bool
	 */
	public function Peers ($peers = [], $unique = true, $loopBack = false) {
		if (func_num_args() == 0) return $this->_peers;
		if (!is_array($peers)) return false;

		$ok = true;

		foreach ($peers as $peer)
			$ok = $this->Peer($peer, $unique, $loopBack);

		return $ok;
	}

	/**
	 * @param string $data
	 *
	 * @return bool
	 */
	public function Broadcast ($data = '') {
		$ok = true;

		foreach ($this->_peers as $peer)
			$ok &= $peer->Send($data);

		return $ok;
	}

	/**
	 * @return bool
	 */
	public function Pipe () {
		$ok = $this->_server->Pipe();

		foreach ($this->_peers as $peer)
			$ok &= $peer->Pipe();

		return $ok;
	}

	/**
	 * @return bool
	 */
	public function Running () {
		return $this->_server->Running();
	}

	/**
	 * @return QuarkServer
	 */
	public function Server () {
		return $this->_server;
	}
}

/**
 * Class QuarkClusterNode
 *
 * @package Quark
 */
class QuarkClusterNode implements IQuarkTransportProvider {
	/**
	 * @var IQuarkClusterNode $_node
	 */
	private $_node;

	/**
	 * @var QuarkServer $_server
	 */
	private $_server;

	/**
	 * @var QuarkPeer $_network
	 */
	private $_network;

	/**
	 * @var QuarkClient $_controller
	 */
	private $_controller;

	/**
	 * @param IQuarkClusterNode $node
	 * @param IQuarkTransportProvider|IQuarkIntermediateTransportProvider $transport
	 * @param QuarkURI|string $external
	 * @param QuarkURI|string $internal
	 * @param QuarkURI|string $controller
	 */
	public function __construct (IQuarkClusterNode $node, IQuarkTransportProvider $transport, $external, $internal = QuarkServer::TCP_ALL_INTERFACES_RANDOM_PORT, $controller = '') {
		$this->_node = $node;

		if ($transport instanceof IQuarkIntermediateTransportProvider)
			$transport->Protocol($this);

		$this->_server = new QuarkServer($external, $transport);
		$this->_network = new QuarkPeer($this, $internal);
		$this->_controller = new QuarkClient($controller, $this, null, 0, false);
	}

	/**
	 * @return QuarkServer
	 */
	public function &Server () {
		return $this->_server;
	}

	/**
	 * @return QuarkPeer
	 */
	public function &Network () {
		return $this->_network;
	}

	/**
	 * @return QuarkClient
	 */
	public function &Controller () {
		return $this->_controller;
	}

	/**
	 * @return bool
	 *
	 * @throws QuarkArchException
	 */
	public function Pipe () {
		$run = $this->Bind() &&
		$this->_server->Pipe() &&
		$this->_controller->Pipe() &&
		$this->_network->Pipe();

		if (!$this->_server->Running())
			throw new QuarkArchException('Cluster server not started. Expected address ' . $this->_server->URI());

		if (!$this->_network->Running())
			throw new QuarkArchException('Cluster peering not started. Expected address ' . $this->_network->URI());

		return $run;
	}

	/**
	 * @return bool
	 */
	public function Bind () {
		$run = true;

		if (!$this->_server->Running())
			$run = $this->_server->Bind();

		if (!$this->_network->Running())
			$this->_network->Bind();

		if (!$this->_controller->Connected())
			$this->_controller->Connect();

		return $run;
	}

	/**
	 * @param string $data
	 */
	public function Control ($data = '') {
		$this->_controller->Send($data);
	}

	/**
	 * @param string $data
	 *
	 * @return bool
	 */
	public function Broadcast ($data) {
		$this->_node->NodeData(new QuarkClient(), $data);
		return $this->_network->Broadcast($data);
	}

	/**
	 * @param QuarkURI|string $node
	 * @param bool $unique = true
	 * @param bool $loopBack = false
	 *
	 * @return bool
	 */
	public function Node ($node, $unique = true, $loopBack = false) {
		return $this->_network->Peer($node, $unique, $loopBack);
	}

	/**
	 * @param QuarkURI[]|string[] $nodes
	 * @param bool $unique = true
	 * @param bool $loopBack = false
	 *
	 * @return QuarkClient[]|bool
	 */
	public function Nodes ($nodes = [], $unique = true, $loopBack = false) {
		return call_user_func_array(array($this->_network, 'Peers'), func_get_args());
	}

	/**
	 * @param $event
	 * @param QuarkClient $client
	 * @param array $data
	 *
	 * @return mixed
	 */
	private function _switch ($event, QuarkClient $client, $data = []) {
		$args = array_slice(func_get_args(), 1);
		$connection = $client->ConnectionURI();

		$server = $this->_server->ConnectionURI(false, $connection->host);
		$network = $this->_network->Server()->ConnectionURI(false, $connection->host);
		$controller = $this->_controller->ConnectionURI(false);

		if ($server && $connection->URI() == $server->URI())
			return call_user_func_array(array($this->_node, 'Client' . $event), $args);

		if ($network && $connection->URI() == $network->URI())
			return call_user_func_array(array($this->_node, 'Node' . $event), $args);

		if ($controller && $connection->URI() == $controller->URI())
			return call_user_func_array(array($this->_node, 'Controller' . $event), $args);

		return null;
	}

	/**
	 * @param QuarkClient $client
	 *
	 * @return bool
	 */
	public function OnConnect (QuarkClient $client) {
		$this->_switch('Connect', $client);
	}

	/**
	 * @param QuarkClient $client
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function OnData (QuarkClient $client, $data) {
		$this->_switch('Data', $client, $data);
	}

	/**
	 * @param QuarkClient $client
	 *
	 * @return mixed
	 */
	public function OnClose (QuarkClient $client) {
		$this->_switch('Close', $client);
	}
}

/**
 * Interface IQuarkClusterNode
 *
 * @package Quark
 */
interface IQuarkClusterNode {
	/**
	 * @param QuarkClient $controller
	 *
	 * @return mixed
	 */
	public function ControllerConnect(QuarkClient $controller);

	/**
	 * @param QuarkClient $controller
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function ControllerData(QuarkClient $controller, $data);

	/**
	 * @param QuarkClient $controller
	 *
	 * @return mixed
	 */
	public function ControllerClose(QuarkClient $controller);

	/**
	 * @param QuarkClient $node
	 *
	 * @return mixed
	 */
	public function NodeConnect(QuarkClient $node);

	/**
	 * @param QuarkClient $node
	 * @param $data
	 *
	 * @return mixed
	 */
	public function NodeData(QuarkClient $node, $data);

	/**
	 * @param QuarkClient $node
	 *
	 * @return mixed
	 */
	public function NodeClose(QuarkClient $node);

	/**
	 * @param QuarkClient $client
	 *
	 * @return mixed
	 */
	public function ClientConnect(QuarkClient $client);

	/**
	 * @param QuarkClient $client
	 * @param $data
	 *
	 * @return mixed
	 */
	public function ClientData(QuarkClient $client, $data);

	/**
	 * @param QuarkClient $client
	 *
	 * @return mixed
	 */
	public function ClientClose(QuarkClient $client);
}

/**
 * Class QuarkClusterController
 *
 * @package Quark
 */
class QuarkClusterController implements IQuarkTransportProvider {
	/**
	 * @var IQuarkClusterController $_controller
	 */
	private $_controller;

	/**
	 * @var QuarkServer $_network
	 */
	private $_network;

	/**
	 * @var QuarkServer $_terminal
	 */
	private $_terminal;

	/**
	 * @param IQuarkClusterController $controller
	 * @param IQuarkTransportProvider|IQuarkIntermediateTransportProvider $terminal
	 * @param QuarkURI|string $external
	 * @param QuarkURI|string $internal
	 */
	public function __construct (IQuarkClusterController $controller, IQuarkTransportProvider $terminal, $external, $internal) {
		$this->_controller = $controller;

		if ($terminal instanceof IQuarkIntermediateTransportProvider)
			$terminal->Protocol($this);

		$this->_network = new QuarkServer($internal, $this);
		$this->_terminal = new QuarkServer($external, $terminal);
	}

	/**
	 * @return QuarkServer
	 */
	public function &Network () {
		return $this->_network;
	}

	/**
	 * @return QuarkServer
	 */
	public function &Terminal () {
		return $this->_terminal;
	}

	/**
	 * @return bool
	 */
	public function Pipe () {
		return $this->Bind() &&
		$this->_network->Pipe() &&
		$this->_terminal->Pipe();
	}

	/**
	 * @return bool
	 */
	public function Bind () {
		$run = true;

		if (!$this->_network->Running())
			$this->_network->Bind();

		if (!$this->_terminal->Running())
			$this->_terminal->Bind();

		return $run;
	}

	/**
	 * @param string $data
	 *
	 * @return bool
	 */
	public function Broadcast ($data) {
		$nodes = $this->_network->Clients();

		foreach ($nodes as $node)
			$node->Send($data);
	}

	/**
	 * @param $event
	 * @param QuarkClient $client
	 * @param array $data
	 *
	 * @return mixed
	 */
	private function _switch ($event, QuarkClient $client, $data = []) {
		$args = array_slice(func_get_args(), 1);
		$connection = $client->ConnectionURI();

		$network = $this->_network->ConnectionURI(false, $connection->host);
		$terminal = $this->_terminal->ConnectionURI(false, $connection->host);

		if ($network && $connection->URI() == $network->URI())
			return call_user_func_array(array($this->_controller, 'NodeClient' . $event), $args);

		if ($terminal && $connection->URI() == $terminal->URI())
			return call_user_func_array(array($this->_controller, 'Terminal' . $event), $args);

		return null;
	}

	/**
	 * @param QuarkClient $client
	 *
	 * @return bool
	 */
	public function OnConnect (QuarkClient $client) {
		$this->_switch('Connect', $client);
	}

	/**
	 * @param QuarkClient $client
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function OnData (QuarkClient $client, $data) {
		$this->_switch('Data', $client, $data);
	}

	/**
	 * @param QuarkClient $client
	 *
	 * @return mixed
	 */
	public function OnClose (QuarkClient $client) {
		$this->_switch('Close', $client);
	}
}

/**
 * Interface IQuarkClusterController
 *
 * @package Quark
 */
interface IQuarkClusterController {
	/**
	 * @param QuarkClient $node
	 *
	 * @return mixed
	 */
	public function NodeClientConnect(QuarkClient $node);

	/**
	 * @param QuarkClient $node
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function NodeClientData(QuarkClient $node, $data);

	/**
	 * @param QuarkClient $node
	 *
	 * @return mixed
	 */
	public function NodeClientClose(QuarkClient $node);

	/**
	 * @param QuarkClient $terminal
	 *
	 * @return mixed
	 */
	public function TerminalConnect(QuarkClient $terminal);

	/**
	 * @param QuarkClient $terminal
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function TerminalData(QuarkClient $terminal, $data);

	/**
	 * @param QuarkClient $terminal
	 *
	 * @return mixed
	 */
	public function TerminalClose(QuarkClient $terminal);
}

/**
 * Class QuarkURI
 *
 * @package Quark
 */
class QuarkURI {
	const SCHEME_HTTP = 'http';
	const SCHEME_HTTPS = 'https';

	public $scheme;
	public $user;
	public $pass;
	public $host;
	public $port;
	public $query;
	public $path;
	public $fragment;
	public $options;

	/**
	 * @var array $_route;
	 */
	private $_route = array();

	private static $_transports = array(
		'tcp' => 'tcp',
		'ssl' => 'ssl',
		'ftp' => 'tcp',
		'ftps' => 'ssl',
		'ssh' => 'ssl',
		'scp' => 'ssl',
		'http' => 'tcp',
		'https' => 'ssl',
		'ws' => 'tcp',
		'wss' => 'ssl',
	);

	private static $_ports = array(
		'ftp' => '21',
		'ftps' => '22',
		'ssh' => '22',
		'scp' => '22',
		'http' => '80',
		'https' => '443'
	);

	/**
	 * @return string
	 */
	public function __toString () {
		return $this->URI();
	}

	/**
	 * @param QuarkURI|string $uri
	 * @param bool $local
	 *
	 * @return QuarkURI|null
	 */
	public static function FromURI ($uri, $local = true) {
		if ($uri instanceof QuarkURI) return $uri;
		if (!is_string($uri)) return null;

		$rand = false;

		$pass = Quark::GuID();
		$uri = str_replace(':0@', $pass, $uri);

		if (strstr($uri, ':0')) {
			$rand = true;
			$uri = str_replace(':0', '', $uri);
		}

		$uri = str_replace($pass, ':0@', $uri);

		$url = parse_url($uri);

		if ($url === false) return null;

		$out = new self();

		foreach ($url as $key => $value)
			$out->$key = $value;

		if ($rand)
			$out->port = 0;

		if ($local) {
			if (!isset($url['scheme'])) $out->scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : '';
			if (!isset($url['host'])) $out->host = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
		}

		return $out;
	}

	/**
	 * @param string $host
	 * @param int $port
	 *
	 * @return QuarkURI
	 */
	public static function FromEndpoint ($host, $port = null) {
		$uri = new self();
		$uri->Endpoint($host, $port);
		return $uri;
	}

	/**
	 * @param string $location
	 *
	 * @return QuarkURI
	 */
	public static function FromFile ($location = '') {
		$uri = new self();
		$uri->path = Quark::NormalizePath($location);
		return $uri;
	}

	/**
	 * @param string $path
	 * @param bool $full = true
	 *
	 * @return string
	 */
	public static function Of ($path, $full = true) {
		$path = Quark::NormalizePath($path, false);

		return Quark::WebHost($full) . $path;
	}

	/**
	 * @param string $scheme
	 */
	public function __construct ($scheme = '') {
		if (func_num_args() == 1)
			$this->scheme = (string)$scheme;
	}

	/**
	 * @param bool $full
	 *
	 * @return string
	 */
	public function URI ($full = false) {
		if (strpos(strtolower($this->scheme), strtolower('HTTP/')) !== false)
			$this->scheme = 'http';

		return
			($this->scheme !== null ? $this->scheme : 'http')
			. '://'
			. ($this->user !== null ? $this->user . ($this->pass !== null ? ':' . $this->pass : '') . '@' : '')
			. $this->host
			. ($this->port !== null && $this->port != 80 ? ':' . $this->port : '')
			. ($this->path !== null ? Quark::NormalizePath('/' . $this->path, false) : '')
			. ($full ? '/?' . $this->query : '');
	}

	/**
	 * @return string|bool
	 */
	public function Socket () {
		return (isset(self::$_transports[$this->scheme]) ? self::$_transports[$this->scheme] : 'tcp')
		. '://'
		. $this->host
		. ':'
		. (is_int($this->port) ? $this->port : (isset(self::$_ports[$this->scheme]) ? self::$_ports[$this->scheme] : 80));
	}

	/**
	 * @param string $host
	 * @param integer|null $port
	 *
	 * @return QuarkURI
	 */
	public function Endpoint ($host, $port = null) {
		$this->host = $host;

		if (func_num_args() == 2 || $port !== null)
			$this->port = $port;

		return $this;
	}

	/**
	 * @param string $username
	 * @param string|null $password
	 *
	 * @return QuarkURI
	 */
	public function User ($username, $password = null) {
		$this->user = $username;

		if (func_num_args() == 2)
			$this->pass = $password;

		return $this;
	}

	/**
	 * @param string $resource
	 *
	 * @return string
	 */
	public function Resource ($resource = '') {
		if (func_num_args() == 1)
			$this->path= $resource;

		return $this->path;
	}

	/**
	 * @return string
	 */
	public function Query () {
		return Quark::NormalizePath($this->path . (strlen(trim($this->query)) == 0 ? '' : '?' . $this->query) . $this->fragment, false);
	}

	/**
	 * @param int $id
	 *
	 * @return array|string
	 */
	public function Route ($id = 0) {
		if (sizeof($this->_route) == 0)
			$this->_route = self::ParseRoute($this->path);

		if (func_num_args() == 1)
			return isset($this->_route[$id]) ? $this->_route[$id] : '';

		return $this->_route;
	}

	/**
	 * @param string $source
	 *
	 * @return array
	 */
	public static function ParseRoute ($source = '') {
		if (!is_string($source)) return array();

		$query = preg_replace('#(((\/)*)((\?|\&)(.*)))*#', '', $source);
		$route = explode('/', trim(Quark::NormalizePath(preg_replace('#\.php$#Uis', '', $query))));
		$buffer = array();

		foreach ($route as $component)
			if (strlen(trim($component)) != 0) $buffer[] = trim($component);

		$route = $buffer;
		unset($buffer);

		return $route;
	}

	/**
	 * @param array $query
	 */
	public function Params ($query = []) {
		$this->query = http_build_query((array)$query);
	}

	/**
	 * @param QuarkURI $uri
	 *
	 * @return bool
	 */
	public function Equal (QuarkURI $uri) {
		foreach ($this as $key => $value)
			if ($uri->$key != $value) return false;

		return true;
	}

	/**
	 * @return bool
	 */
	public function IsNull () {
		return !$this->host && $this->port === null;
	}
}

/**
 * Class QuarkDTO
 *
 * @package Quark
 */
class QuarkDTO {
	const HTTP_VERSION_1_0 = 'HTTP/1.0';
	const HTTP_VERSION_1_1 = 'HTTP/1.1';
	const HTTP_VERSION_2_0 = 'HTTP/2.0';
	CONST HTTP_PROTOCOL_REQUEST = '#^(.*) (.*) (.*)\n(.*)\n\s\n(.*)$#Uis';
	const HTTP_PROTOCOL_RESPONSE = '#^(.*) (.*)\n(.*)\n\s\n(.*)$#Uis';

	const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';

	const HEADER_HOST = 'Host';
	const HEADER_ACCEPT = 'Accept';
	const HEADER_ACCEPT_LANGUAGE = 'Accept-Language';
	const HEADER_ACCEPT_ENCODING = 'Accept-Encoding';
	const HEADER_CACHE_CONTROL = 'Cache-Control';
	const HEADER_CONTENT_LENGTH = 'Content-Length';
	const HEADER_CONTENT_TYPE = 'Content-Type';
	const HEADER_CONTENT_TRANSFER_ENCODING = 'Content-Transfer-Encoding';
	const HEADER_CONTENT_DISPOSITION = 'Content-Disposition';
	const HEADER_CONTENT_DESCRIPTION = 'Content-Description';
	const HEADER_CONTENT_LANGUAGE = 'Content-Language';
	const HEADER_COOKIE = 'Cookie';
	const HEADER_CONNECTION = 'Connection';
	const HEADER_SET_COOKIE = 'Set-Cookie';
	const HEADER_ALLOW_ORIGIN = 'Access-Control-Allow-Origin';
	const HEADER_AUTHORIZATION = 'Authorization';
	const HEADER_EXPIRES = 'Expires';
	const HEADER_PRAGMA = 'Pragma';
	const HEADER_UPGRADE = 'Upgrade';
	const HEADER_SEC_WEBSOCKET_KEY = 'Sec-WebSocket-Key';
	const HEADER_SEC_WEBSOCKET_EXTENSIONS = 'Sec-WebSocket-Extensions';
	const HEADER_SEC_WEBSOCKET_ACCEPT = 'Sec-WebSocket-Accept';
	const HEADER_SEC_WEBSOCKET_PROTOCOL = 'Sec-WebSocket-Protocol';
	const HEADER_LOCATION = 'Location';
	const HEADER_USER_AGENT = 'User-Agent';
	const HEADER_WWW_AUTHENTICATE = 'WWW-Authenticate';

	const STATUS_200_OK = '200 OK';
	const STATUS_401_UNAUTHORIZED = '401 Unauthorized';
	const STATUS_403_FORBIDDEN = '403 Forbidden';
	const STATUS_404_NOT_FOUND = '404 Not Found';
	const STATUS_500_SERVER_ERROR = '500 Server Error';

	const CONNECTION_KEEP_ALIVE = 'keep-alive';
	const CONNECTION_UPGRADE = 'Upgrade';

	const UPGRADE_WEBSOCKET = 'websocket';

	const DISPOSITION_INLINE = 'inline';
	const DISPOSITION_FORM_DATA = 'form-data';
	const DISPOSITION_ATTACHMENT = 'attachment';

	const MULTIPART_FORM_DATA = 'multipart/form-data';
	const MULTIPART_MIXED = 'multipart/mixed';
	const MULTIPART_ALTERNATIVE = 'multipart/alternative';
	const MULTIPART_RELATED = 'multipart/related';

	const TRANSFER_ENCODING_BINARY = 'binary';
	const TRANSFER_ENCODING_BASE64 = 'base64';

	const CHARSET_UTF8 = 'utf-8';

	const SIGNATURE = '_s';

	/**
	 * @var string $_raw
	 */
	private $_raw = '';

	/**
	 * @var IQuarkIOProcessor $_processor
	 */
	private $_processor = null;

	/**
	 * @var string $_protocol
	 */
	private $_protocol = self::HTTP_VERSION_1_0;

	/**
	 * @var QuarkURI $_uri
	 */
	private $_uri = null;

	/**
	 * @var QuarkURI $_remote
	 */
	private $_remote = null;

	/**
	 * @var string $_status
	 */
	private $_status = self::STATUS_200_OK;

	/**
	 * @var string $_method
	 */
	private $_method = '';

	/**
	 * @var array $_headers
	 */
	private $_headers = array();

	/**
	 * @var QuarkCookie[] $_cookies
	 */
	private $_cookies = array();

	/**
	 * @var QuarkLanguage[] $_languages
	 */
	private $_languages = array();

	/**
	 * @var string $_agent
	 */
	private $_agent = '';

	/**
	 * @var string $_boundary
	 */
	private $_boundary = '';

	/**
	 * @var string $_encoding
	 */
	private $_encoding = self::TRANSFER_ENCODING_BINARY;

	/**
	 * @var bool $_multipart
	 */
	private $_multipart = false;

	/**
	 * @var int $_length
	 */
	private $_length = 0;

	/**
	 * @var string $_charset
	 */
	private $_charset = self:: CHARSET_UTF8;

	/**
	 * @var mixed $_data
	 */
	private $_data = '';

	/**
	 * @var QuarkFile[] $_files
	 */
	private $_files = array();

	/**
	 * @var QuarkKeyValuePair $_authorization
	 */
	private $_authorization = null;

	/**
	 * @var QuarkKeyValuePair $_session
	 */
	private $_session = null;

	/**
	 * @var string $_signature
	 */
	private $_signature = '';

	/**
	 * @var null $_null
	 */
	private $_null = null;

	/**
	 * @param $key
	 *
	 * @return mixed
	 */
	public function &__get ($key) {
		if (is_scalar($this->_data) || !$this->_data)
			return $this->_null;

		if (is_array($this->_data))
			$this->_data = (object)$this->_data;

		return $this->_data->$key;
	}

	/**
	 * @param $key
	 * @param $value
	 */
	public function __set ($key, $value) {
		if (!$this->_data)
			$this->_data = new \StdClass();

		$this->_data->$key = $value;
	}

	/**
	 * @param $key
	 *
	 * @return bool
	 */
	public function __isset ($key) {
		return isset($this->_data->$key);
	}

	/**
	 * @param $name
	 */
	public function __unset ($name) {
		unset($this->_data->$name);
	}

	/**
	 * @param IQuarkIOProcessor $processor
	 * @param QuarkURI  $uri
	 * @param string $method
	 * @param string $boundary
	 */
	public function __construct (IQuarkIOProcessor $processor = null, QuarkURI $uri = null, $method = '', $boundary = '') {
		$this->Processor($processor == null ? new QuarkHTMLIOProcessor() : $processor);
		$this->URI($uri);
		$this->Method($method);
		$this->Boundary(func_num_args() == 4 ? $boundary : 'QuarkBoundary' . Quark::GuID());
	}

	/**
	 * @param IQuarkIOProcessor $processor
	 * @param QuarkURI          $uri
	 *
	 * @return QuarkDTO
	 */
	public static function ForGET (IQuarkIOProcessor $processor = null, QuarkURI $uri = null) {
		return new self($processor, $uri, self::METHOD_GET);
	}

	/**
	 * @param IQuarkIOProcessor $processor
	 * @param QuarkURI          $uri
	 *
	 * @return QuarkDTO
	 */
	public static function ForPOST (IQuarkIOProcessor $processor = null, QuarkURI $uri = null) {
		return new self($processor, $uri, self::METHOD_POST);
	}

	/**
	 * @param $url
	 *
	 * @return QuarkDTO
	 */
	public static function ForRedirect ($url) {
		$response = new self();
		$response->Header(self::HEADER_LOCATION, $url);
		return $response;
	}

	/**
	 * @param string $username
	 * @param string $password
	 *
	 * @return string
	 */
	public static function HTTPBasicAuthorization ($username = '', $password = '') {
		return base64_encode($username . ':' . $password);
	}

	/**
	 * @param mixed $data
	 * @param bool $processor = true
	 *
	 * @return QuarkDTO
	 */
	public function Merge ($data = [], $processor = true) {
		if (!($data instanceof QuarkDTO)) $this->MergeData($data);
		else {
			$this->_status = $data->Status();
			$this->_method = $data->Method();
			$this->_boundary = $data->Boundary();
			$this->_headers += $data->Headers();
			$this->_cookies += $data->Cookies();
			$this->_languages += $data->Languages();
			$this->_uri = $data->URI() == null ? $this->_uri : $data->URI();
			$this->_remote = $data->Remote() == null ? $this->_remote : $data->Remote();
			$this->_charset = $data->Charset();

			if ($processor)
				$this->_processor = $data->Processor();

			$this->MergeData($data->Data());
		}

		$sign = self::SIGNATURE;

		if (isset($this->_data->$sign))
			$this->Signature($this->_data->$sign);

		return $this;
	}

	/**
	 * @param $data
	 *
	 * @return mixed
	 */
	public function MergeData ($data) {
		if ($this->_data instanceof QuarkView) return $this->_data;

		if (is_string($data) && is_string($this->_data)) $this->_data .= $data;
		else $this->_data = QuarkObject::Merge($this->_data, $data);

		return $this->_data;
	}

	/**
	 * @param string $protocol
	 *
	 * @return string
	 */
	public function Protocol ($protocol = '') {
		if (func_num_args() != 0)
			$this->_protocol = $protocol;

		return $this->_protocol;
	}

	/**
	 * @param IQuarkIOProcessor $processor
	 *
	 * @return IQuarkIOProcessor
	 */
	public function Processor (IQuarkIOProcessor $processor = null) {
		if (func_num_args() == 1 && $processor != null)
			$this->_processor = $processor;

		return $this->_processor;
	}

	/**
	 * @param QuarkURI $uri
	 *
	 * @return QuarkURI
	 */
	public function URI (QuarkURI $uri = null) {
		if (func_num_args() == 1 && $uri != null)
			$this->_uri = $uri;

		return $this->_uri;
	}

	/**
	 * @param QuarkURI $uri
	 *
	 * @return QuarkURI
	 */
	public function Remote (QuarkURI $uri = null) {
		if (func_num_args() == 1 && $uri != null)
			$this->_remote = $uri;

		return $this->_remote;
	}

	/**
	 * @param string $method
	 *
	 * @return string
	 */
	public function Method ($method = '') {
		if (func_num_args() == 1 && is_string($method))
			$this->_method = strtoupper($method);

		return $this->_method;
	}

	/**
	 * @param int|string 	$code = 0
	 * @param string 		$text = 'OK'
	 *
	 * @return string
	 */
	public function Status ($code = 0, $text = 'OK') {
		if (func_num_args() != 0 && is_scalar($code))
			$this->_status = $code . (func_num_args() == 2 && is_scalar($text) ? ' ' . $text : '');

		return $this->_status;
	}

	/**
	 * @param array $headers
	 *
	 * @return array
	 */
	public function Headers ($headers = []) {
		if (func_num_args() == 1 && is_array($headers)) {
			$assoc = QuarkObject::isAssociative($headers);

			foreach ($headers as $key => $value) {
				if (!$assoc) {
					$header = explode(': ', $value);
					$key = $header[0];
					$value = isset($header[1]) ? $header[1] : '';
				}

				$this->Header($key, $value);
			}
		}

		return $this->_headers;
	}

	/**
	 * @param $key
	 * @param $value
	 *
	 * @return mixed
	 */
	public function Header ($key, $value = '') {
		$value = trim($value);

		if (func_num_args() == 2)
			$this->_headers[$key] = $value;

		switch ($key) {
			case self::HEADER_AUTHORIZATION:
				if (preg_match('#^(.*) (.*)$#Uis', $value, $auth))
					$this->_authorization = new QuarkKeyValuePair($auth[1], $auth[2]);
				break;

			case self::HEADER_COOKIE:
				$this->_cookies = QuarkCookie::FromCookie($value);
				break;

			case self::HEADER_SET_COOKIE:
				$this->_cookies[] = QuarkCookie::FromSetCookie($value);
				break;

			case self::HEADER_ACCEPT_LANGUAGE:
				$this->_languages = QuarkLanguage::FromAcceptLanguage($value);
				break;

			case self::HEADER_CONTENT_LENGTH:
				$this->_length = $value;
				break;

			case self::HEADER_CONTENT_LANGUAGE:
				$this->_languages = QuarkLanguage::FromContentLanguage($value);
				break;

			case self::HEADER_CONTENT_TYPE:
				$type = explode('; charset=', $value);
				$boundary = explode('; boundary=', $value);

				if (sizeof($type) == 2)
					$this->_charset = $type[1];

				if (sizeof($boundary) == 2)
					$this->_boundary = $boundary[1];

				$this->_multipart = strpos($type[0], 'multipart/') !== false;
				break;

			default: break;
		}

		return isset($this->_headers[$key]) ? $this->_headers[$key] : null;
	}

	/**
	 * @param QuarkCookie[] $cookies
	 *
	 * @return QuarkCookie[]
	 */
	public function Cookies ($cookies = []) {
		if (func_num_args() == 1 && is_array($cookies))
			$this->_cookies = $cookies;

		return $this->_cookies;
	}

	/**
	 * @param QuarkCookie $cookie
	 *
	 * @return QuarkDTO
	 */
	public function Cookie (QuarkCookie $cookie) {
		if ($cookie != null)
			$this->_cookies[] = $cookie;

		return $this;
	}

	/**
	 * @param string $name
	 *
	 * @return QuarkCookie|null
	 */
	public function GetCookieByName ($name = '') {
		foreach ($this->_cookies as $cookie)
			if ($cookie->name == $name) return $cookie;

		return null;
	}

	/**
	 * @param QuarkLanguage[] $languages
	 *
	 * @return QuarkLanguage[]
	 */
	public function Languages ($languages = []) {
		if (func_num_args() != 0)
			$this->_languages = $languages;

		return $this->_languages;
	}

	/**
	 * @param QuarkLanguage $language
	 *
	 * @return QuarkDTO
	 */
	public function Language (QuarkLanguage $language) {
		if ($language != null)
			$this->_languages[] = $language;

		return $this;
	}

	/**
	 * @param string $name
	 *
	 * @return QuarkLanguage|null
	 */
	public function GetLanguageByName ($name = '') {
		foreach ($this->_languages as $language)
			if ($language->Is($name)) return $language;

		return null;
	}

	/**
	 * @param string $agent
	 *
	 * @return string
	 */
	public function UserAgent ($agent = '') {
		if (func_num_args() != 0)
			$this->_agent = $agent;

		return $this->_agent;
	}

	/**
	 * @param string $boundary
	 *
	 * @return string
	 */
	public function Boundary ($boundary = '') {
		if (func_num_args() == 1 && is_scalar($boundary))
			$this->_boundary = (string)$boundary;

		return $this->_boundary;
	}

	/**
	 * @param mixed $data
	 *
	 * @return mixed
	 */
	public function Data ($data = []) {
		if (func_num_args() != 0) {
			$this->_data = $data;

			$sign = self::SIGNATURE;

			if (isset($this->_data->$sign))
				$this->Signature($this->_data->$sign);
		}

		return $this->_data;
	}

	/**
	 * @return QuarkFile[]
	 */
	public function Files () {
		return $this->_files;
	}

	/**
	 * @param mixed $raw
	 *
	 * @return mixed
	 */
	public function Raw ($raw = []) {
		if (func_num_args() != 0)
			$this->_raw = $raw;

		return $this->_raw;
	}

	/**
	 * @param QuarkKeyValuePair $auth
	 *
	 * @return QuarkKeyValuePair
	 */
	public function Authorization (QuarkKeyValuePair $auth = null) {
		if (func_num_args() != 0)
			$this->_authorization = $auth;

		return $this->_authorization;
	}

	/**
	 * @param QuarkKeyValuePair $session
	 *
	 * @return QuarkKeyValuePair
	 */
	public function AuthorizationProvider (QuarkKeyValuePair $session = null) {
		if (func_num_args() != 0)
			$this->_session = $session;

		return $this->_session;
	}

	/**
	 * @param string $signature
	 *
	 * @return string
	 */
	public function Signature ($signature = '') {
		if (func_num_args() != 0)
			$this->_signature = $signature;

		return $this->_signature;
	}

	/**
	 * @param string $encoding = self::TRANSFER_ENCODING_BINARY
	 *
	 * @return string
	 */
	public function Encoding ($encoding = self::TRANSFER_ENCODING_BINARY) {
		if (func_num_args() != 0)
			$this->_encoding = $encoding;

		return $this->_encoding;
	}

	/**
	 * @param string $charset = self::CHARSET_UTF8
	 *
	 * @return string
	 */
	public function Charset ($charset = self::CHARSET_UTF8) {
		if (func_num_args() != 0)
			$this->_charset = $charset;

		return $this->_charset;
	}

	/**
	 * @return string
	 */
	public function SerializeRequest () {
		return $this->_serializeHeaders(true, true) . "\r\n\r\n" . $this->_serializeBody(true);
	}

	/**
	 * @return string
	 */
	public function SerializeRequestBody () {
		return $this->_serializeBody(true);
	}

	/**
	 * @return string
	 */
	public function SerializeRequestHeaders () {
		return $this->_serializeHeaders(true, true);
	}

	/**
	 * @return array
	 */
	public function SerializeRequestHeadersToArray () {
		return $this->_serializeHeaders(true, false);
	}

	/**
	 * @return string
	 */
	public function SerializeResponse () {
		return $this->_serializeHeaders(false, true) . "\r\n\r\n" . $this->_serializeBody(false);
	}

	/**
	 * @return string
	 */
	public function SerializeResponseBody () {
		return $this->_serializeBody(false);
	}

	/**
	 * @return string
	 */
	public function SerializeResponseHeaders () {
		return $this->_serializeHeaders(false, true);
	}

	/**
	 * @return array
	 */
	public function SerializeResponseHeadersToArray () {
		return $this->_serializeHeaders(false, false);
	}

	/**
	 * @param string $raw
	 *
	 * @return QuarkDTO
	 */
	public function UnserializeRequest ($raw = '') {
		$this->_raw = $raw;

		if (preg_match(self::HTTP_PROTOCOL_REQUEST, $raw, $found)) {
			$this->Method($found[1]);
			$this->URI(QuarkURI::FromURI($found[2]));
			$this->Protocol($found[3]);

			parse_str($this->URI()->query, $this->_data);

			$sign = self::SIGNATURE;
			$this->Signature(isset($this->_data->$sign) ? $this->_data->$sign : '');

			if ($this->_processor == null)
				$this->_processor = new QuarkFormIOProcessor();

			$this->_unserializeHeaders($found[4]);
			$this->_unserializeBody($found[5]);
		}

		return $this;
	}

	/**
	 * @param string $raw
	 *
	 * @return QuarkDTO
	 */
	public function UnserializeRequestBody ($raw = '') {
		return $this->_unserializeBody($raw);
	}

	/**
	 * @param string $raw
	 *
	 * @return QuarkDTO
	 */
	public function UnserializeRequestHeaders ($raw = '') {
		return $this->_unserializeHeaders($raw);
	}

	/**
	 * @param string $raw
	 *
	 * @return QuarkDTO
	 */
	public function UnserializeResponse ($raw = '') {
		$this->_raw = $raw;

		if (preg_match(self::HTTP_PROTOCOL_RESPONSE, $raw, $found)) {
			$this->Protocol($found[1]);
			$this->Status($found[2]);

			if ($this->_processor == null)
				$this->_processor = new QuarkHTMLIOProcessor();

			$this->_unserializeHeaders($found[3]);
			$this->_unserializeBody($found[4]);
		}

		return $this;
	}

	/**
	 * @param string $raw
	 *
	 * @return QuarkDTO
	 */
	public function UnserializeResponseBody ($raw = '') {
		return $this->_unserializeBody($raw);
	}

	/**
	 * @param string $raw
	 *
	 * @return string
	 */
	public function UnserializeResponseHeaders ($raw = '') {
		return $this->_unserializeHeaders($raw);
	}

	/**
	 * @param bool $client
	 * @param bool $str
	 *
	 * @return string|array
	 */
	private function _serializeHeaders ($client, $str) {
		$this->_serializeBody($client);

		$headers = array($client
			? $this->_method . ' ' . $this->_uri->Query() . ' ' . $this->_protocol
			: $this->_protocol . ' ' . $this->_status
		);

		$typeSet = isset($this->_headers[self::HEADER_CONTENT_TYPE]);
		$typeValue = $typeSet ? $this->_headers[self::HEADER_CONTENT_TYPE] : '';

		if (!isset($this->_headers[self::HEADER_AUTHORIZATION]) && $this->_authorization != null)
			$this->_headers[self::HEADER_AUTHORIZATION] = $this->_authorization->Key() . ' ' . $this->_authorization->Value();

		if (!isset($this->_headers[self::HEADER_CONTENT_LENGTH]))
			$this->_headers[self::HEADER_CONTENT_LENGTH] = $this->_length;

		$this->_headers[self::HEADER_CONTENT_TYPE] = $typeSet
			? $typeValue
			: ($this->_multipart
				? ($client ? self::MULTIPART_FORM_DATA : self::MULTIPART_MIXED) . '; boundary=' . $this->_boundary
				: $this->_processor->MimeType() . '; charset=' . $this->_charset
			);

		if ($client) {
			$this->_headers[self::HEADER_HOST] = $this->_uri->host;

			if (sizeof($this->_cookies) != 0)
				$this->_headers[self::HEADER_COOKIE] = QuarkCookie::SerializeCookies($this->_cookies);

			if (sizeof($this->_languages) != 0)
				$this->_headers[self::HEADER_ACCEPT_LANGUAGE] = QuarkLanguage::SerializeAcceptLanguage($this->_languages);
		}
		else {
			foreach ($this->_cookies as $cookie)
				$headers[] = self::HEADER_SET_COOKIE . ': ' . $cookie->Serialize(true);

			if (sizeof($this->_languages) != 0)
				$this->_headers[self::HEADER_CONTENT_LANGUAGE] = QuarkLanguage::SerializeContentLanguage($this->_languages);
		}

		foreach ($this->_headers as $key => $value)
			$headers[] = $key . ': ' . $value;

		return $str ? implode("\r\n", $headers) : $headers;
	}

	/**
	 * @param bool $client
	 *
	 * @return string
	 */
	private function _serializeBody ($client) {
		if ($this->_raw == '') {
			if ($this->_data instanceof QuarkView) {
				$this->_processor = new QuarkHTMLIOProcessor();
				$out = $this->_data->Compile();
			}
			else {
				$out = '';

				QuarkObject::Walk($this->_data, function ($key, $value) use (&$out, $client) {
					$this->_multipart |= $value instanceof QuarkFile;

					if ($this->_processor instanceof QuarkFormIOProcessor || ($value instanceof QuarkFile))
						$out .= $this->_serializePart($key, $value, $client
							? self::DISPOSITION_FORM_DATA
							: ($this->_multipart
								? self::DISPOSITION_ATTACHMENT
								: self::DISPOSITION_INLINE
							)
						);
				});

				if (!$this->_multipart) $out = $this->_processor->Encode($this->_data);
				else {
					if (!($this->_processor instanceof QuarkFormIOProcessor))
						$out = $this->_serializePart(
								$this->_processor->MimeType(),
								$this->_processor->Encode($this->_data),
								$client ? self::DISPOSITION_FORM_DATA : self::DISPOSITION_INLINE
							) . $out;

					$out = $out . '--' . $this->_boundary . '--';
				}
			}

			if (!$this->_multipart && $this->_encoding == self::TRANSFER_ENCODING_BASE64)
				$out = base64_encode($out);

			$this->_length = strlen($out);
			$this->_raw = $out;
		}

		return $this->_raw;
	}

	/**
	 * @param $key
	 * @param mixed $value
	 * @param string $disposition
	 *
	 * @return string
	 */
	private function _serializePart ($key, $value, $disposition) {
		$file = $value instanceof QuarkFile;
		$contents = $file ? $value->Load()->Content() : $value;

		if ($file)
			$this->_files[] = new QuarkModel($value);

		return
			'--' . $this->_boundary . "\r\n"
			. (!$file && $this->_processor instanceof QuarkFormIOProcessor ? '' : (self::HEADER_CONTENT_TYPE . ': ' . ($file ? $value->type : $this->_processor->MimeType()) . "\r\n"))
			. (self::HEADER_CONTENT_DISPOSITION . ': ' . $disposition
				. ($disposition == self::DISPOSITION_FORM_DATA ? '; name="' . $key . '"' : '')
				. ($file ? '; filename="' . $value->name . '"' : '')
				. "\r\n"
			)
			. ($file ? self::HEADER_CONTENT_TRANSFER_ENCODING . ': ' . $this->_encoding . "\r\n" : '')
			. "\r\n"
			. ($file && $this->_encoding == self::TRANSFER_ENCODING_BASE64 ? base64_encode($contents) : $contents)
			. "\r\n";
	}

	/**
	 * @param string $raw
	 *
	 * @return QuarkDTO
	 */
	private function _unserializeHeaders ($raw) {
		if (preg_match_all('#(.*)\: (.*)\n#Uis', $raw . "\r\n", $headers, PREG_SET_ORDER))
			foreach ($headers as $header)
				$this->Header($header[1], $header[2]);

		return $this;
	}

	/**
	 * @param string $raw
	 *
	 * @return QuarkDTO
	 */
	private function _unserializeBody ($raw) {
		if (!$this->_multipart || strpos($raw, '--' . $this->_boundary) === false)
			$this->_data = $this->_processor->Decode($raw);
		else {
			$parts = explode('--' . $this->_boundary, $raw);

			foreach ($parts as $part)
				$this->_unserializePart($part);
		}

		return $this;
	}

	/**
	 * @param $raw
	 *
	 * @return QuarkDTO
	 */
	private function _unserializePart ($raw) {
		if (preg_match('#^(.*)\n\s\n(.*)$#Uis', $raw, $found)) {
			$head = array();

			if (preg_match_all('#(.*)\: (.*)\n#Uis', trim($raw) . "\r\n", $headers, PREG_SET_ORDER))
				foreach ($headers as $header)
					$head[$header[1]] = trim($header[2]);

			if (isset($head[self::HEADER_CONTENT_DISPOSITION])) {
				$value = $head[self::HEADER_CONTENT_DISPOSITION];
				$position = explode(';', $value)[0];

				preg_match('#name\=(.*)\;#Uis', $value . ';', $name);
				preg_match('#filename\=(.*)\;#Uis', $value . ';', $file);

				$name = isset($name[1]) ? $name[1] : '';
				$file = isset($file[1]) ? $file[1] : '';

				if ($name == $this->_processor->MimeType())
					$this->MergeData($this->_processor->Decode($found[2]));

				$fs = null;

				if ($file != '') {
					if (isset($head[self::HEADER_CONTENT_TRANSFER_ENCODING]) && $head[self::HEADER_CONTENT_TRANSFER_ENCODING] == self::TRANSFER_ENCODING_BASE64)
						$found[2] = base64_decode($found[2]);

					/**
					 * @var QuarkFile $fs
					 */
					$fs = new QuarkModel(new QuarkFile());
					$fs->Content($found[2]);

					$this->_files[] = $fs;
				}

				if ($position == 'form-data') {
					parse_str($name, $storage);

					array_walk_recursive($storage, function (&$item) use ($found, $fs) {
						$item = $fs ? $fs : $found[2];
					});

					$this->MergeData($storage);
				}
			}
		}

		return $this;
	}

	/**
	 * @param $raw
	 * @param callable $process
	 */
	public function BatchUnserialize ($raw, callable $process) {
		$batch = $this->_processor->Batch($raw);

		foreach ($batch as $part)
			$process($this->_processor->Decode($part));
	}
}

/**
 * Class QuarkHTTPTransportClient
 *
 * @package Quark
 */
class QuarkHTTPTransportClient implements IQuarkTransportProvider {
	/**
	 * @var QuarkDTO $_request
	 */
	private $_request;

	/**
	 * @var QuarkDTO $_response
	 */
	private $_response;

	/**
	 * @param QuarkDTO $request
	 * @param QuarkDTO $response
	 */
	public function __construct (QuarkDTO $request, QuarkDTO $response = null) {
		$this->_request = $request;
		$this->_response = $response;
	}

	/**
	 * @param QuarkDTO $request
	 *
	 * @return QuarkDTO
	 */
	public function Request (QuarkDTO $request = null) {
		if (func_num_args() != 0)
			$this->_request = $request;

		return $this->_request;
	}

	/**
	 * @param QuarkDTO $response
	 *
	 * @return QuarkDTO
	 */
	public function Response (QuarkDTO $response = null) {
		if (func_num_args() != 0)
			$this->_response = $response;

		return $this->_response;
	}

	/**
	 * @param QuarkClient $client
	 *
	 * @return bool
	 */
	public function OnConnect (QuarkClient $client) {
		if ($this->_request == null) return false;

		if ($this->_response == null)
			$this->_response = new QuarkDTO();

		$this->_request->URI($client->URI());
		$this->_response->URI($client->URI());

		$this->_request->Remote($client->ConnectionURI(true));
		$this->_response->Remote($client->ConnectionURI(true));

		$this->_response->Method($this->_request->Method());

		$client->Send($request = $this->_request->SerializeRequest());
		$this->_response->UnserializeResponse($response = $client->Receive());

		return $client->Close();
	}

	/**
	 * @param QuarkClient $client
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function OnData (QuarkClient $client, $data) {
		// TODO: Implement OnData() method.
	}

	/**
	 * @param QuarkClient $client
	 *
	 * @return mixed
	 */
	public function OnClose (QuarkClient $client) {
		// TODO: Implement OnClose() method.
	}

	/**
	 * @param QuarkURI|string $uri
	 * @param QuarkDTO $request
	 * @param QuarkDTO $response
	 * @param QuarkCertificate $certificate
	 * @param int $timeout = 10
	 *
	 * @return QuarkDTO|bool
	 */
	public static function To ($uri, QuarkDTO $request, QuarkDTO $response = null, QuarkCertificate $certificate = null, $timeout = 10) {
		$client = new QuarkClient($uri, new QuarkHTTPTransportClient($request, $response), $certificate, $timeout);

		if (!$client->Connect()) return false;

		/**
		 * @var QuarkHTTPTransportClient $transport
		 */
		$transport = $client->Transport();

		return $transport->Response();
	}
}

/**
 * Class QuarkHTTPTransportServer
 *
 * @package Quark
 */
class QuarkHTTPTransportServer implements IQuarkTransportProvider, IQuarkIntermediateTransportProvider {
	/**
	 * @var QuarkURI $_uri
	 */
	private $_uri;

	/**
	 * @var QuarkCertificate $_certificate
	 */
	private $_certificate;

	/**
	 * @var IQuarkTransportProvider
	 */
	private $_protocol;

	/**
	 * @param IQuarkTransportProvider $protocol
	 */
	public function __construct (IQuarkTransportProvider $protocol = null) {
		$this->_protocol = $protocol;
	}

	/**
	 * @param QuarkURI $uri
	 * @param QuarkCertificate $certificate
	 *
	 * @return mixed
	 */
	public function Setup (QuarkURI $uri, QuarkCertificate $certificate = null) {
		$this->_uri = $uri;
		$this->_certificate = $certificate;
	}

	/**
	 * @param QuarkClient $client
	 *
	 * @return bool
	 */
	public function OnConnect (QuarkClient $client) {
		$this->_protocol->OnConnect($client);
	}

	/**
	 * @param QuarkClient $client
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function OnData (QuarkClient $client, $data) {
		$this->_protocol->OnData($client, $data);
	}

	/**
	 * @param QuarkClient $client
	 *
	 * @return mixed
	 */
	public function OnClose (QuarkClient $client) {
		$this->_protocol->OnClose($client);
	}

	/**
	 * @param IQuarkTransportProvider $protocol
	 *
	 * @return IQuarkTransportProvider
	 */
	public function Protocol (IQuarkTransportProvider $protocol = null) {
		if (func_num_args() != 0)
			$this->_protocol = $protocol;

		return $this->_protocol;
	}

	public function ServicePipeline (IQuarkService $service) {

	}
}

/**
 * Class QuarkCookie
 *
 * @package Quark
 */
class QuarkCookie {
	const EXPIRES_FORMAT = 'D, d-M-Y H:i:s';
	const EXPIRES_SESSION = 0;

	public $name = '';
	public $value = '';
	public $expires = '';
	public $MaxAge = 0;
	public $path = '/';
	public $domain = '';
	public $HttpOnly = '';
	public $secure = '';

	/**
	 * @param string $name
	 * @param string $value
	 * @param int $lifetime = self::EXPIRES_SESSION
	 */
	public function __construct ($name = '', $value = '', $lifetime = self::EXPIRES_SESSION) {
		$this->name = $name;
		$this->value = $value;

		$this->Lifetime($lifetime);
	}

	/**
	 * @return string
	 */
	public function __toString () {
		return $this->value;
	}

	/**
	 * @param int $seconds = 0
	 *
	 * @return int
	 */
	public function Lifetime ($seconds = 0) {
		if (func_num_args() != 0) {
			$this->MaxAge = $seconds;

			if ($seconds == 0) {
				$this->expires = '';
				return 0;
			}

			$expires = QuarkDate::GMTNow();
			$expires->Offset('+' . $seconds . ' seconds');

			$this->expires = $expires->Format(self::EXPIRES_FORMAT);
		}

		return QuarkDate::GMTNow()->Interval(QuarkDate::GMTOf($this->expires));
	}

	/**
	 * @param string $header
	 *
	 * @return QuarkCookie[]
	 */
	public static function FromCookie ($header = '') {
		$out = array();
		$cookies = array_merge(explode(',', $header), explode(';', $header));

		foreach ($cookies as $raw) {
			$cookie = explode('=', trim($raw));

			if (sizeof($cookie) == 2)
				$out[] = new QuarkCookie($cookie[0], $cookie[1]);
		}

		return $out;
	}

	/**
	 * @param string $header
	 *
	 * @return QuarkCookie
	 */
	public static function FromSetCookie ($header = '') {
		$cookie = explode(';', $header);

		$instance = new QuarkCookie();

		foreach ($cookie as $component) {
			$item = explode('=', $component);

			$key = trim($item[0]);
			$value = isset($item[1]) ? trim($item[1]) : '';

			if (isset($instance->$key)) $instance->$key = $value;
			else {
				$instance->name = $key;
				$instance->value = $value;
			}
		}

		return $instance;
	}

	/**
	 * @param array $cookies
	 *
	 * @return string
	 */
	public static function SerializeCookies ($cookies = []) {
		$out = '';

		foreach ($cookies as $cookie)
			$out .= $cookie->name . '=' . $cookie->value . '; ';

		return substr($out, 0, strlen($out) - 2);
	}

	/**
	 * @param bool $full
	 *
	 * @return string
	 */
	public function Serialize ($full = false) {
		$out = $this->name . '=' . $this->value;

		if (!$full) return $out;
		else {
			foreach ($this as $field => $value)
				if (strlen(trim($value)) != 0 && $field != 'name' && $field != 'value')
					$out .= '; ' . $field . '=' . $value;

			return $out;
		}
	}
}

/**
 * Class QuarkLanguage
 *
 * @package Quark
 */
class QuarkLanguage {
	const EN_EN = 'en-EN';
	const EN_GB = 'en-GB';
	const EN_US = 'en-US';
	const RU_RU = 'ru-RU';
	const MD_MD = 'md-MD';

	private $_name = '';
	private $_quantity = 1;
	private $_location = '';

	/**
	 * @param string $name
	 * @param int $quantity = 1
	 * @param string $location
	 */
	public function __construct ($name = '', $quantity = 1, $location = '') {
		$this->_name = $name;
		$this->_quantity = $quantity;
		$this->_location = strtoupper(func_num_args() == 3
			? $location
			: array_reverse(explode('-', $name))[0]
		);
	}

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	public function Name ($name = '') {
		if (func_num_args() != 0)
			$this->_name = $name;

		return $this->_name;
	}

	/**
	 * @param int $quantity
	 *
	 * @return int
	 */
	public function Quantity ($quantity = 1) {
		if (func_num_args() != 0)
			$this->_quantity = $quantity;

		return $this->_quantity;
	}

	/**
	 * @param string $location
	 *
	 * @return string
	 */
	public function Location ($location = '') {
		if (func_num_args() != 0)
			$this->_location = strtoupper($location);

		return $this->_location;
	}

	/**
	 * @param string $language
	 * @param bool $strict = false
	 *
	 * @return bool
	 */
	public function Is ($language, $strict = false) {
		return $this->_name == $language
		|| $strict
			? false
			: ($this->_location == strtoupper($language));
	}

	/**
	 * @param string $header
	 *
	 * @return QuarkLanguage[]
	 */
	public static function FromAcceptLanguage ($header = '') {
		$out = array();
		$languages = explode(',', $header);

		foreach ($languages as $raw) {
			$language = explode(';', $raw);
			$loc = explode('-', $language[0]);
			$q = explode('=', sizeof($language) == 1 ? 'q=1' : $language[1]);

			$out[] = new QuarkLanguage($language[0], array_reverse($q)[0], array_reverse($loc)[0]);
		}

		return $out;
	}

	/**
	 * @param string $header
	 *
	 * @return QuarkLanguage[]
	 */
	public static function FromContentLanguage ($header = '') {
		$out = array();
		$languages = explode(',', $header);

		foreach ($languages as $raw)
			$out[] = new QuarkLanguage(trim($raw));

		return $out;
	}

	/**
	 * @param QuarkLanguage[] $languages
	 *
	 * @return string
	 */
	public static function SerializeAcceptLanguage ($languages = []) {
		if (!is_array($languages)) return '';

		$out = array();

		/**
		 * @var QuarkLanguage[] $languages
		 */
		foreach ($languages as $language)
			$out[] = $language->Name() . ';q=' . $language->Quantity();

		return implode(',', $out);
	}

	/**
	 * @param QuarkLanguage[] $languages
	 *
	 * @return string
	 */
	public static function SerializeContentLanguage ($languages = []) {
		if (!is_array($languages)) return '';

		$out = array();

		/**
		 * @var QuarkLanguage[] $languages
		 */
		foreach ($languages as $language)
			$out[] = $language->Name();

		return implode(',', $out);
	}
}

/**
 * Class QuarkFile
 *
 * @package Quark
 */
class QuarkFile implements IQuarkModel, IQuarkStrongModel, IQuarkLinkedModel {
	const LOCAL_FS = 'LocalFS';

	public $location = '';
	public $name = '';
	public $type = '';
	public $tmp_name = '';
	public $size = 0;
	public $extension = '';
	public $isDir = false;
	public $parent = '';

	protected $_content = '';
	protected $_loaded = false;

	/**
	 * @return string
	 */
	public function __toString () {
		return $this->WebLocation(false);
	}

	/**
	 * @param $location
	 *
	 * @return mixed
	 */
	public static function Mime ($location) {
		if (!$location) return false;

		$info = finfo_open(FILEINFO_MIME_TYPE);
		$type = finfo_file($info, $location);
		finfo_close($info);

		return $type;
	}

	/**
	 * @param $mime
	 *
	 * @return string
	 */
	public static function ExtensionByMime ($mime) {
		$extension = array_reverse(explode('/', $mime));

		if ($extension[0] == 'jpeg')
			$extension[0] = 'jpg';

		return sizeof($extension) == 2 && substr_count($extension[0], '-') == 0 ? $extension[0] : null;
	}

	/**
	 * @param string $location
	 * @param bool $load = false
	 */
	public function __construct ($location = '', $load = false) {
		if (func_num_args() != 0)
			$this->Location($location);

		if ($load)
			$this->Load();
	}

	/**
	 * @param string $location
	 * @param string $name
	 *
	 * @return string
	 */
	public function Location ($location = '', $name = '') {
		if (func_num_args() != 0) {
			$real = realpath($location);

			$this->location = Quark::NormalizePath($real ? $real : $location, false);
			$this->name = $name ? $name : array_reverse(explode('/', $this->location))[0];
			$this->parent = str_replace($this->name, '', $this->location);

			if ($this->Exists()) {
				$this->type = self::Mime($this->location);
				$this->extension = self::ExtensionByMime($this->type);
			}
		}

		return $this->location;
	}

	/**
	 * @return bool
	 */
	public function Exists () {
		return is_file($this->location) && file_exists($this->location);
	}

	/**
	 * @param string $location
	 *
	 * @return QuarkFile
	 * @throws QuarkArchException
	 */
	public function Load ($location = '') {
		if ($this->tmp_name)
			$this->Location($this->tmp_name, $this->name);

		if (func_num_args() != 0)
			$this->Location($location);

		if (!$this->Exists())
			throw new QuarkArchException('Invalid file path "' . $this->location . '"');

		if (memory_get_usage() <= Quark::Config()->Alloc() * 1024 * 1024) {
			$this->Content(file_get_contents($this->location));
			$this->_loaded = true;
		}

		return $this;
	}

	/**
	 * @return bool
	 */
	public function SaveContent () {
		return file_put_contents($this->location, $this->_content, LOCK_EX) !== false;
	}

	/**
	 * @return string
	 */
	public function WebLocation () {
		return QuarkURI::Of(Quark::SanitizePath(str_replace(Quark::Host(false), '', $this->location)), false);
	}

	/**
	 * @param string|true $content
	 *
	 * @return string
	 */
	public function Content ($content = '') {
		if (func_num_args() == 1) {
			$this->_content = $content;
			$this->size = strlen($this->_content);
		}

		return $this->_content;
	}

	/**
	 * @param int $unit = Quark::UNIT_MEGABYTE
	 * @param int $precision = 2
	 *
	 * @return float
	 */
	public function Size ($unit = Quark::UNIT_MEGABYTE, $precision = 2) {
		return round($this->size / $unit, $precision);
	}

	/**
	 * @param bool $mime
	 *
	 * @return bool
	 */
	public function Upload ($mime = true) {
		if ($mime) {
			$ext = self::ExtensionByMime(self::Mime($this->tmp_name));
			$this->location .= $ext ? '.' . $ext : '';
		}

		return is_file($this->tmp_name) && is_dir(dirname($this->location)) && rename($this->tmp_name, $this->location);
	}

	/**
	 * @return QuarkDTO
	 */
	public function Download () {
		$response = new QuarkDTO(new QuarkPlainIOProcessor());

		$response->Header(QuarkDTO::HEADER_CONTENT_TYPE, $this->type);
		$response->Header(QuarkDTO::HEADER_CONTENT_DISPOSITION, 'attachment; filename="' . $this->name . '"');

		if (!$this->_loaded)
			$this->Content(file_get_contents($this->location));

		$response->Data($this->_content);

		return $response;
	}

	/**
	 * @return array
	 */
	public function Rules () {
		return array(
			QuarkField::Type($this->name, QuarkField::TYPE_STRING),
			QuarkField::Type($this->type, QuarkField::TYPE_STRING),
			QuarkField::Type($this->size, QuarkField::TYPE_INT),
			QuarkField::Type($this->tmp_name, QuarkField::TYPE_STRING),
			QuarkField::MinLength($this->name, 1)
		);
	}

	/**
	 * @return array
	 */
	public function Fields () {
		return array(
			'_location' => '',
			'location' => '',
			'name' => '',
			'extension' => '',
			'type' => '',
			'size' => 0,
			'isDir' => false,
			'tmp_name' => ''
		);
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Link ($raw) {
		return $raw ? new QuarkFile($raw) : null;
	}

	/**
	 * @return mixed
	 */
	public function Unlink () {
		return $this->location;
	}

	/**
	 * @param array $files
	 *
	 * @return array
	 */
	public static function FromFiles ($files) {
		$output = array();

		foreach ($files as $name => $file) {
			$buffer = array();
			$output[$name] = array();
			$simple = true;

			foreach ($file as $key => $value) {
				if (!is_array($value)) $buffer[$key] = $value;
				else {
					array_walk_recursive($value, function ($item) use (&$buffer, $key) {
						$buffer[$key] = $item;
					});

					$output[$name] = $value;
					$simple = false;
				}
			}

			if ($simple) $output[$name] = new QuarkModel(new QuarkFile(), $buffer);
			else array_walk_recursive($output, function (&$item) use ($buffer) {
				$item = new QuarkModel(new QuarkFile(), $buffer);
			});
		}

		return $output;
	}
}

/**
 * Interface IQuarkCulture
 *
 * @package Quark
 */
interface IQuarkCulture {
	/**
	 * @return string
	 */
	public function DateTimeFormat();

	/**
	 * @return string
	 */
	public function DateFormat();

	/**
	 * @return string
	 */
	public function TimeFormat();
}

/**
 * Class QuarkCultureISO
 *
 * @package Quark
 */
class QuarkCultureISO implements IQuarkCulture {
	/**
	 * @return string
	 */
	public function DateTimeFormat () { return 'Y-m-d H:i:s'; }

	/**
	 * @return string
	 */
	public function DateFormat () { return 'Y-m-d'; }

	/**
	 * @return string
	 */
	public function TimeFormat () { return 'H:i:s'; }
}

/**
 * Class QuarkCultureRU
 *
 * @package Quark
 */
class QuarkCultureRU implements IQuarkCulture {
	/**
	 * @return string
	 */
	public function DateTimeFormat () { return 'd.m.Y H:i:s'; }

	/**
	 * @return string
	 */
	public function DateFormat () { return 'd.m.Y'; }

	/**
	 * @return string
	 */
	public function TimeFormat () { return 'H:i:s'; }
}

/**
 * Class QuarkCultureCustom
 *
 * @package Quark
 */
class QuarkCultureCustom implements IQuarkCulture {
	private $_dateTime;
	private $_date;
	private $_time;

	/**
	 * @param string $dateTime
	 * @param string $date
	 * @param string $time
	 */
	public function __construct ($dateTime = '', $date = '', $time = '') {
		$this->_dateTime = $dateTime;
		$this->_date = $date;
		$this->_time = $time;
	}

	/**
	 * @param $format
	 *
	 * @return QuarkCultureCustom
	 */
	public static function Format ($format) {
		if ($format == null)
			return new QuarkCultureISO();

		$dateTime = explode(' ', $format);

		return new self($format, $dateTime[0], array_reverse($dateTime)[0]);
	}

	/**
	 * @return string
	 */
	public function DateTimeFormat () {
		return $this->_dateTime;
	}

	/**
	 * @return string
	 */
	public function DateFormat () {
		return $this->_date;
	}

	/**
	 * @return string
	 */
	public function TimeFormat () {
		return $this->_time;
	}
}

/**
 * Class QuarkException
 *
 * @package Quark
 */
abstract class QuarkException extends \Exception {
	/**
	 * @var string
	 */
	public $lvl = Quark::LOG_WARN;

	/**
	 * @var string
	 */
	public $message = 'QuarkException';

	/**
	 * @param \Exception $exception
	 *
	 * @return bool|int
	 */
	public static function ExceptionHandler (\Exception $exception) {
		if ($exception instanceof QuarkException)
			return $exception->lvl != Quark::LOG_FATAL && Quark::Log($exception->message, $exception->lvl);

		if ($exception instanceof \Exception)
			return Quark::Log('Common exception: ' . $exception->getMessage() . "\r\n at " . $exception->getFile() . ':' . $exception->getLine(), Quark::LOG_FATAL);

		return true;
	}
}

/**
 * Class QuarkArchException
 *
 * @package Quark
 */
class QuarkArchException extends QuarkException {
	/**
	 * @param string $message
	 * @param string $lvl = Quark::LOG_FATAL
	 */
	public function __construct ($message, $lvl = Quark::LOG_FATAL) {
		$this->lvl = $lvl;
		$this->message = $message;
	}
}

/**
 * Class QuarkHTTPException
 *
 * @package Quark
 */
class QuarkHTTPException extends QuarkException {
	/**
	 * @var int
	 */
	public $status = 500;

	/**
	 * @param int $status = 500
	 * @param string $message
	 */
	public function __construct ($status = 500, $message = '') {
		$this->lvl = Quark::LOG_FATAL;
		$this->message = $message;

		$this->status = $status;
	}
}

/**
 * Class QuarkConnectionException
 *
 * @package Quark
 */
class QuarkConnectionException extends QuarkException {
	/**
	 * @var QuarkURI
	 */
	public $uri;

	/**
	 * @param QuarkURI $uri
	 * @param string $lvl
	 */
	public function __construct (QuarkURI $uri, $lvl = Quark::LOG_WARN) {
		$this->lvl = $lvl;
		$this->message = 'Unable to connect to ' . $uri->URI();

		$this->uri = $uri;
	}
}

/**
 * Interface IQuarkIOProcessor
 *
 * @package Quark
 */
interface IQuarkIOProcessor {
	/**
	 * @return string
	 */
	public function MimeType();

	/**
	 * @param $data
	 * @return mixed
	 */
	public function Encode($data);

	/**
	 * @param $raw
	 * @return mixed
	 */
	public function Decode($raw);

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Batch($raw);
}

/**
 * Class QuarkPlainIOProcessor
 *
 * @package Quark
 */
class QuarkPlainIOProcessor implements IQuarkIOProcessor {
	/**
	 * @return string
	 */
	public function MimeType () { return 'plain/text'; }

	/**
	 * @param $data
	 * @return mixed
	 */
	public function Encode ($data) { return is_scalar($data) ? (string)$data : ''; }

	/**
	 * @param $raw
	 * @return mixed
	 */
	public function Decode ($raw) { return $raw; }

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Batch ($raw) { return $raw; }
}

/**
 * Class QuarkHTMLIOProcessor
 *
 * @package Quark
 */
class QuarkHTMLIOProcessor implements IQuarkIOProcessor {
	/**
	 * @return string
	 */
	public function MimeType () { return 'text/html'; }

	/**
	 * @param $data
	 * @return mixed
	 */
	public function Encode ($data) {
		if ($data instanceof QuarkView)
			return $data->Compile();

		return is_string($data) ? $data : '';
	}

	/**
	 * @param $raw
	 * @return mixed
	 */
	public function Decode ($raw) { return $raw; }

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Batch ($raw) { return $raw; }
}

/**
 * Class QuarkFormIOProcessor
 *
 * @package Quark
 */
class QuarkFormIOProcessor implements IQuarkIOProcessor {
	/**
	 * @return string
	 */
	public function MimeType () {
		return 'application/x-www-form-urlencoded';
	}

	/**
	 * @param $data
	 * @return mixed
	 */
	public function Encode ($data) {
		return http_build_query($data);
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Decode ($raw) {
		$data = array();

		parse_str($raw, $data);

		return $data;
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Batch ($raw) { return $raw; }
}

/**
 * Class QuarkJSONIOProcessor
 *
 * @package Quark
 */
class QuarkJSONIOProcessor implements IQuarkIOProcessor {
	/**
	 * @return string
	 */
	public function MimeType () { return 'application/json'; }

	/**
	 * @param $data
	 * @return mixed|string
	 */
	public function Encode ($data) { return \json_encode($data); }

	/**
	 * @param $raw
	 * @return mixed
	 */
	public function Decode ($raw) { return \json_decode($raw); }

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Batch ($raw) {
		return explode('}-{', str_replace('}{', '}}-{{', $raw));
	}
}

/**
 * Class QuarkXMLIOProcessor
 *
 * @package Quark
 */
class QuarkXMLIOProcessor implements IQuarkIOProcessor {
	/**
	 * Constructor for QuarkXMLIOProcessor
	 */
	public function __construct () {
		\libxml_use_internal_errors(true);
	}

	/**
	 * @return string
	 */
	public function MimeType () {
		return 'text/xml';
	}

	/**
	 * @param $data
	 * @return mixed
	 */
	public function Encode ($data) {
		try {
			$xml = new \SimpleXMLElement('<root/>');
			$xml = QuarkObject::Normalize($xml, $data);
			return $xml->asXML();
		}
		catch (\Exception $e) {
			return '';
		}
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Decode ($raw) {
		try {
			return new \SimpleXMLElement($raw);
		}
		catch (\Exception $e) {
			return null;
		}
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Batch ($raw) { return $raw; }
}

/**
 * Class QuarkWDDXIOProcessor
 *
 * @package Quark
 */
class QuarkWDDXIOProcessor implements IQuarkIOProcessor {
	/**
	 * @return string
	 */
	public function MimeType () {
		return 'text/xml';
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Decode ($raw) {
		return \wddx_deserialize($raw);
	}

	/**
	 * @param $data
	 *
	 * @return mixed
	 */
	public function Encode ($data) {
		return \wddx_serialize_value($data);
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Batch ($raw) { return $raw; }
}

/**
 * Class QuarkCertificate
 *
 * @param string $countryName
 * @param string $stateOrProvinceName
 * @param string $localityName
 * @param string $organizationName
 * @param string $organizationalUnitName
 * @param string $commonName
 * @param string $emailAddress
 *
 * @package Quark
 */
class QuarkCertificate {
	private static $_allowed = array(
		'countryName',
		'stateOrProvinceName',
		'localityName',
		'organizationName',
		'organizationalUnitName',
		'commonName',
		'emailAddress'
	);

	/**
	 * @return array
	 */
	public static function AllowedDataKeys () {
		return self::$_allowed;
	}

	private $_location = '';
	private $_passphrase = '';
	private $_content = '';

	/**
	 * @param string $location
	 * @param string $passphrase
	 */
	public function __construct ($location = '', $passphrase = '') {
		$this->Location($location);
		$this->Passphrase($passphrase);
	}

	/**
	 * @param string $passphrase
	 *
	 * @return string
	 */
	public function Passphrase ($passphrase = '') {
		if (func_num_args() == 1)
			$this->_passphrase = $passphrase;

		return $this->_passphrase;
	}

	/**
	 * @param string $location
	 *
	 * @return string
	 * @throws QuarkArchException
	 */
	public function Location ($location = '') {
		if (func_num_args() == 1)
			$this->_location = Quark::NormalizePath($location, false);
		else {
			if (!is_string($this->_location) || !is_file($this->_location))
				throw new QuarkArchException('QuarkCertificate: ' . $this->_location . ' is not a valid file');
		}

		return $this->_location;
	}

	/**
	 * @throws QuarkArchException
	 */
	public function Load () {
		$this->_content = file_get_contents($this->Location());
	}

	/**
	 * @throws QuarkArchException
	 */
	public function Save () {
		file_put_contents($this->Location(), $this->_content);
	}

	/**
	 * @return string
	 */
	public function Content () {
		return $this->_content;
	}

	/**
	 * @return array|string
	 */
	public function Generate () {
		$data = array();
		$pem = array();

		foreach ($this as $key => $value)
			if (in_array($key, self::$_allowed, true)) $data[$key] = $value;

		$key = @openssl_pkey_new();
		$cert = @openssl_csr_new($data, $key);
		$cert = @openssl_csr_sign($cert, null, $key, 365);

		@openssl_x509_export($cert, $pem[0]);
		@openssl_pkey_export($key, $pem[1], $this->_passphrase);

		openssl_error_string();
		$this->_content = implode($pem);

		return $pem;
	}
}

/**
 * Class QuarkSQL
 *
 * @package Quark
 */
class QuarkSQL {
	const OPTION_AS = 'option.as';

	const FIELD_COUNT_ALL = 'COUNT(*)';

	/**
	 * @var IQuarkSQLDataProvider $_provider
	 */
	private $_provider;

	/**
	 * @param $path
	 *
	 * @return string
	 */
	public static function DBName ($path) {
		return !is_string($path) || strlen($path) == 0 ? '' : ($path[0] == '/' ? substr($path, 1) : $path);
	}

	/**
	 * @param IQuarkSQLDataProvider $provider
	 */
	public function __construct (IQuarkSQLDataProvider $provider) {
		$this->_provider = $provider;
	}

	/**
	 * @param $model
	 * @param $options
	 * @param $query
	 *
	 * @return mixed
	 */
	public function Query ($model ,$options, $query) {
		$collection = isset($options[QuarkModel::OPTION_COLLECTION])
			? $options[QuarkModel::OPTION_COLLECTION]
			: QuarkObject::ClassOf($model);

		$i = 1;
		$escape = $this->_provider->EscapeChar();
		$query = str_replace(self::Collection($model), $escape . $collection . $escape, $query, $i);

		return $this->_provider->Query($query, $options);
	}

	/**
	 * @param $model
	 *
	 * @return string
	 */
	public static function Collection ($model) {
		return '{collection_' . sha1(print_r($model, true)) . '}';
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return string
	 */
	public static function Pk (IQuarkModel $model) {
		return $model instanceof IQuarkModelWithCustomPrimaryKey ? $model->PrimaryKey() : 'id';
	}

	/**
	 * @param string $field
	 *
	 * @return string
	 */
	public function Field ($field) {
		if (!is_string($field)) return '';

		return '"' . $this->_provider->Escape($field) . '"';
	}

	/**
	 * @param $value
	 *
	 * @return bool|float|int|string
	 */
	public function Value ($value) {
		if (!is_scalar($value)) return null;

		$output = $this->_provider->Escape($value);

		return is_string($value) ? '\'' . $output . '\'' : $output;
	}

	/**
	 * @param        $condition
	 * @param string $glue
	 *
	 * @return string
	 */
	public function Condition ($condition, $glue = '') {
		if (!is_array($condition) || sizeof($condition) == 0) return '';

		$output = array();

		foreach ($condition as $key => $rule) {
			$field = $this->Field($key);
			$value = $this->Value($rule);

			if (is_array($rule))
				$value = self::Condition($rule, ' AND ');

			switch ($field) {
				case '`$lte`': $output[] = '<=' . $value; break;
				case '`$lt`': $output[] = '<' . $value; break;
				case '`$gt`': $output[] = '>' . $value; break;
				case '`$gte`': $output[] = '>=' . $value; break;
				case '`$ne`': $output[] = '<>' . $value; break;

				case '`$and`':
					$value = $this->Condition($rule, ' AND ');
					$output[] = ' (' . $value . ') ';
					break;

				case '`$or`':
					$value = $this->Condition($rule, ' OR ');
					$output[] = ' (' . $value . ') ';
					break;

				case '`$nor`':
					$value = $this->Condition($rule, ' NOT OR ');
					$output[] = ' (' . $value . ') ';
					break;

				default:
					$output[] = !$value ? '' : (is_string($key) ? $field : '') . (is_scalar($rule) ? '=' : '') . $value;
					break;
			}
		}

		return ($glue == '' ? ' WHERE ' : '') . implode($glue == '' ? ' AND ' : $glue, $output);
	}

	/**
	 * @param $options
	 *
	 * @return string
	 */
	private function _cursor ($options) {
		$output = '';

		if (isset($options[QuarkModel::OPTION_LIMIT]))
			$output .= ' LIMIT ' . $this->_provider->Escape($options[QuarkModel::OPTION_LIMIT]);

		if (isset($options[QuarkModel::OPTION_SKIP]))
			$output .= ' OFFSET ' . $this->_provider->Escape($options[QuarkModel::OPTION_SKIP]);

		if (isset($options[QuarkModel::OPTION_SORT]) && is_array($options[QuarkModel::OPTION_SORT])) {
			$output .= ' ORDER BY ';

			foreach ($options[QuarkModel::OPTION_SORT] as $key => $order) {
				switch ($order) {
					case 1: $sort = 'ASC'; break;
					case -1: $sort = 'DESC'; break;
					default: $sort = ''; break;
				}

				$output .= ' ' . $this->Field($key) . ' ' . $sort;
			}
		}

		return $output;
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function Select (IQuarkModel $model, $criteria, $options = []) {
		$fields = '*';

		if (isset($options[QuarkModel::OPTION_FIELDS]) && is_array($options[QuarkModel::OPTION_FIELDS])) {
			$fields = '';
			$count = sizeof($options[QuarkModel::OPTION_FIELDS]);
			$i = 1;

			foreach ($options[QuarkModel::OPTION_FIELDS] as $field) {
				switch ($field) {
					case self::FIELD_COUNT_ALL:
						$key = $field;
						break;

					default:
						$key = $this->Field($field);
						break;
				}

				$fields = $key . ($i == $count || !$key ? '' : ', ');
				$i++;
			}
		}

		return $this->Query(
			$model,
			$options,
			'SELECT ' . $fields . (isset($options[self::OPTION_AS]) ? ' AS ' . $options[self::OPTION_AS] : '') . ' FROM ' . self::Collection($model) . $this->Condition($criteria) . $this->_cursor($options)
		);
	}

	/**
	 * @param IQuarkModel $model
	 * @param array       $options
	 *
	 * @return mixed
	 */
	public function Insert (IQuarkModel $model, $options = []) {
		$keys = array();
		$values = array();

		foreach ($model as $key => $value) {
			$keys[] = $this->Field($key);
			$values[] = $this->Value($value);
		}

		return $this->Query(
			$model,
			$options,
			'INSERT INTO ' . self::Collection($model)
			. ' (' . implode(', ', $keys) . ') '
			. 'VALUES (' . implode(', ', $values) . ')'
		);
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 * @param array       $options
	 *
	 * @return mixed
	 */
	public function Update (IQuarkModel $model, $criteria, $options = []) {
		$fields = array();

		foreach ($model as $key => $value)
			$fields[] = $this->Field($key) . '=' . '\'' . $this->Value($value) . '\'';

		return $this->Query(
			$model,
			$options,
			'UPDATE ' . self::Collection($model) . ' SET ' . implode(', ', $fields) . $this->Condition($criteria) . $this->_cursor($options)
		);
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 * @param             $options
	 *
	 * @return mixed
	 */
	public function Delete (IQuarkModel $model, $criteria, $options) {
		return $this->Query(
			$model,
			$options,
			'DELETE FROM ' . self::Collection($model) . $this->Condition($criteria) . $this->_cursor($options)
		);
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 * @param array       $options
	 *
	 * @return mixed
	 */
	public function Count (IQuarkModel $model, $criteria, $options = []) {
		return $this->Select($model, $criteria, $options + array(
			'fields' => array(self::FIELD_COUNT_ALL)
		));
	}
}

/**
 * Interface IQuarkSQLDataProvider
 *
 * @package Quark
 */
interface IQuarkSQLDataProvider {
	/**
	 * @param string $query
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function Query($query, $options);

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	public function Escape($value);

	/**
	 * @return string
	 */
	public function EscapeChar();
}

/**
 * Class QuarkSource
 *
 * @package Quark
 */
class QuarkSource extends QuarkFile {
	private $_trim = array();

	/**
	 * @var array $__trim
	 */
	private static $__trim = array(
		',',';','?',':',
		'(',')','{','}','[',']',
		'-','+','*','/',
		'>','<','>=','<=','!=','==',
		'=','=>','->',
		'&&', '||'
	);

	/**
	 * @param string[] $trim
	 *
	 * @return string[]
	 */
	public function Trim ($trim = []) {
		if (func_num_args() != 0)
			$this->_trim = $trim;

		return $this->_trim;
	}

	/**
	 * @return QuarkSource
	 */
	public function Obfuscate () {
		$this->_content = self::ObfuscateString($this->_content, $this->_trim);

		return $this;
	}

	/**
	 * @param string $source
	 * @param array  $trim
	 *
	 * @return string
	 */
	public static function ObfuscateString ($source = '', $trim = array()) {
		$trim = func_num_args() == 3 ? $trim : self::$__trim;
		$slash = ':\\\\' . Quark::GuID() . '\\\\';

		$source = str_replace('://', $slash, $source);
		$source = preg_replace('#\/\/(.*)\\n#Uis', '', $source);
		$source = str_replace($slash, '://', $source);
		$source = preg_replace('#\/\*(.*)\*\/#Uis', '', $source);
		$source = str_replace("\r\n", '', $source);
		$source = preg_replace('/\s+/', ' ', $source);
		$source = trim(str_replace('<?phpn', '<?php n', $source));

		foreach ($trim as $rule) {
			$source = str_replace(' ' . $rule . ' ', $rule, $source);
			$source = str_replace(' ' . $rule, $rule, $source);
			$source = str_replace($rule . ' ', $rule, $source);
		}

		return $source;
	}
}