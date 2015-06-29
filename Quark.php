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

	/**
	 * @var QuarkConfig
	 */
	private static $_config;
	private static $_webHost = '';
	private static $_events = array();
	private static $_gUID = array();
	private static $_hID = '';
	private static $_breaks = array();

	/**
	 * @return bool
	 */
	public static function CLI () {
		return PHP_SAPI == 'cli';
	}

	/**
	 * @return string
	 */
	public static function HostID () {
		if (self::$_hID == '')
			self::$_hID = self::GuID();

		return self::$_hID;
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

		$argc = isset($_SERVER['argc']) ? $_SERVER['argc'] : 0;
		$argv = isset($_SERVER['argv']) ? $_SERVER['argv'] : array();

		$threads = new QuarkThreadSet($argc, $argv);

		if (!self::CLI()) $threads->Thread(new QuarkFPMEnvironmentProvider($argc, $argv))->Invoke();
		elseif($argc > 1) $threads->Thread(new QuarkCLIEnvironmentProvider($argc, $argv))->Invoke();
		else {
			/**
			 * @var IQuarkThread[] $streams
			 */
			$streams = $config->StreamEnvironment();
			$streams[] = new QuarkCLIEnvironmentProvider($argc, $argv);

			$threads->Threads($streams);
			$threads->Pipeline();
		}
	}

	/**
	 * @param $service
	 *
	 * @return string
	 */
	private static function _bundle ($service) {
		return Quark::NormalizePath(Quark::Host() . '/' . Quark::Config()->Location(QuarkConfig::SERVICES) . '/' . $service . 'Service.php', false);
	}

	/**
	 * @param string $uri
	 *
	 * @return IQuarkService
	 *
	 * @throws QuarkArchException
	 * @throws QuarkHTTPException
	 */
	public static function SelectService ($uri) {
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

		return $bundle;
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
	 * @param bool $full
	 * @param bool $secure
	 *
	 * @return string
	 *
	 * @throws QuarkArchException
	 */
	public static function WebHost ($full = true, $secure = false) {
		if (self::$_webHost == '') {
			if (!isset($_SERVER['SERVER_NAME'])) return '';

			if (!isset($_SERVER['SERVER_PROTOCOL']))
				throw new QuarkArchException('Could not determine WebHost because $_SERVER[\'SERVER_PROTOCOL\'] is not specified');

			$server = $_SERVER['SERVER_NAME'];
			$protocol = strtolower(explode('/', $_SERVER['SERVER_PROTOCOL'])[0]);

			if ($protocol != 'http')
				throw new QuarkArchException('Could not determine WebHost because $_SERVER[\'SERVER_PROTOCOL\'] is not valid HTTP protocol');

			$offset = str_replace('index.php', '', $_SERVER['PHP_SELF']);

			return $full ? ($protocol . ($secure ? 's' : '') . '://' . $server . $offset) : $offset;
		}

		return self::$_webHost;
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
	 * @param $event
	 * @param $args
	 */
	public static function Dispatch ($event, $args = null) {
		if (!isset(self::$_events[$event])) return;

		foreach (self::$_events[$event] as $worker) $worker($args);
	}

	/**
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
		$logs = self::NormalizePath(self::Host() . '/' . self::Config()->Location(QuarkConfig::LOGS) . '/');

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
}

spl_autoload_extensions('.php');

Quark::Import(__DIR__, function ($class) { return substr($class, 6); });
Quark::Import(Quark::Host());

/**
 * Class QuarkConfig
 * @package Quark
 */
class QuarkConfig {
	const SERVICES = 'services';
	const VIEWS = 'views';
	const LOGS = 'logs';

	const REQUEST = '_processorRequest';
	const RESPONSE = '_processorResponse';
	const BOTH = '_processorBoth';

	/**
	 * @var IQuarkCulture
	 */
	private $_culture;

	/**
	 * @var int $_alloc
	 */
	private $_alloc = 5;

	/**
	 * @var string
	 */
	private $_mode = Quark::MODE_DEV;

	/**
	 * @var string $_defaultNotFoundStatus
	 */
	private $_defaultNotFoundStatus = QuarkDTO::STATUS_404_NOT_FOUND;

	/**
	 * @var string $_defaultServerErrorStatus
	 */
	private $_defaultServerErrorStatus = QuarkDTO::STATUS_500_SERVER_ERROR;

	/**
	 * @var IQuarkExtension[] $_extensions
	 */
	private $_extensions = array();

	/**
	 * @var IQuarkThread[] $_streams
	 */
	private $_streams = array();

	/**
	 * @var IQuarkIOProcessor
	 */
	private $_processorRequest = null;
	private $_processorResponse = null;
	private $_processorBoth = null;

	/**
	 * @var array
	 */
	private $_location = array(
		self::SERVICES => 'Services',
		self::VIEWS => 'Views',
		self::LOGS => 'logs',
	);

	/**
	 * @param string $mode
	 */
	public function __construct ($mode = Quark::MODE_DEV) {
		$this->_mode = $mode;

		$this->_culture = new QuarkCultureISO();

		$this->_processorRequest = new QuarkFormIOProcessor();
		$this->_processorResponse = new QuarkHTMLIOProcessor();
	}

	/**
	 * @param IQuarkCulture $culture
	 * @return IQuarkCulture|QuarkCultureISO
	 */
	public function Culture (IQuarkCulture $culture = null) {
		return $this->_culture = ($culture === null) ? $this->_culture : $culture;
	}

	/**
	 * @param int $mb
	 *
	 * @return int
	 */
	public function Alloc ($mb = 0) {
		if (func_num_args() != 0)
			$this->_alloc = $mb;

		return $this->_alloc;
	}

	/**
	 * @param string $mode
	 * @return null|string
	 */
	public function Mode ($mode = null) {
		return $this->_mode = ($mode === null) ? $this->_mode : $mode;
	}

	/**
	 * @param $name
	 * @param IQuarkDataProvider $provider
	 * @param QuarkURI $uri
	 */
	public function DataProvider ($name, IQuarkDataProvider $provider, QuarkURI $uri) {
		try {
			QuarkModel::Source($name, new QuarkModelSource($provider, $uri));
		}
		catch (\Exception $e) {
			Quark::Log('Unable to connect \'' . $name . '\'', Quark::LOG_FATAL);
			Quark::Dispatch(Quark::EVENT_CONNECTION_EXCEPTION, array(
				'name' => $name,
				'uri' => $uri
			));
		}
	}

	/**
	 * @param string $name
	 * @param IQuarkExtensionConfig $config
	 *
	 * @return IQuarkExtensionConfig
	 */
	public function Extension ($name, IQuarkExtensionConfig $config = null) {
		try {
			if (func_num_args() == 2)
				$this->_extensions[$name] = $config;

			return isset($this->_extensions[$name]) ? $this->_extensions[$name] : null;
		}
		catch (\Exception $e) {
			Quark::Log('Unable to config extension of \'' . $name . '\'', Quark::LOG_FATAL);
			Quark::Dispatch(Quark::EVENT_COMMON_EXCEPTION, array(
				'name' => $name
			));

			return null;
		}
	}

	/**
	 * @param                             $name
	 * @param IQuarkAuthorizationProvider $provider
	 * @param IQuarkAuthorizableModel     $user
	 *
	 * @return QuarkSession
	 */
	public function AuthorizationProvider ($name, IQuarkAuthorizationProvider $provider = null, IQuarkAuthorizableModel $user = null) {
		try {
			if (func_num_args() == 3)
				QuarkSession::Init($name, $provider, $user);

			return QuarkSession::Get($name);
		}
		catch (\Exception $e) {
			Quark::Log('Unable to init session \'' . $name . '\' with ' . get_class($provider) . ' and ' . get_class($user), Quark::LOG_FATAL);
			Quark::Dispatch(Quark::EVENT_COMMON_EXCEPTION, array(
				'name' => $name,
				'provider' => $provider,
				'user' => $user
			));

			return null;
		}
	}

	/**
	 * @param QuarkStreamEnvironmentProvider $provider
	 *
	 * @return IQuarkThread[]
	 */
	public function StreamEnvironment (QuarkStreamEnvironmentProvider $provider = null) {
		if (func_num_args() != 0)
			$this->_streams[] = $provider;

		return $this->_streams;
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
	 * @param string $status
	 *
	 * @return string
	 */
	public function DefaultNotFoundStatus ($status = '') {
		if (func_num_args() == 1)
			$this->_defaultNotFoundStatus = $status;

		return $this->_defaultNotFoundStatus;
	}

	/**
	 * @param string $status
	 *
	 * @return string
	 */
	public function DefaultServerErrorStatus ($status = '') {
		if (func_num_args() == 1)
			$this->_defaultServerErrorStatus = $status;

		return $this->_defaultServerErrorStatus;
	}

	/**
	 * @param string $direction
	 * @param IQuarkIOProcessor $processor
	 *
	 * @return IQuarkIOProcessor
	 */
	public function Processor ($direction, IQuarkIOProcessor $processor = null) {
		if (func_num_args() == 2) {
			if ($direction != self::BOTH) $this->$direction = $processor;
			else {
				$this->_processorRequest = $processor;
				$this->_processorResponse = $processor;
				$this->_processorBoth = $processor;
			}
		}

		return is_string($direction) ? $this->$direction : null;
	}
}

/**
 * Class QuarkFPMEnvironmentProvider
 *
 * @package Quark
 */
class QuarkFPMEnvironmentProvider implements IQuarkThread {
	/**
	 * @return mixed
	 */
	public function Thread () {
		$service = new QuarkService(
			$_SERVER['REQUEST_URI'],
			Quark::Config()->Processor(QuarkConfig::REQUEST),
			Quark::Config()->Processor(QuarkConfig::RESPONSE)
		);

		$uri = QuarkURI::FromURI($_SERVER['REQUEST_URI']);
		$service->Input()->URI($uri);
		$service->Output()->URI($uri);

		if ($service->Service() instanceof IQuarkServiceWithAccessControl)
			$service->Output()->Header(QuarkDTO::HEADER_ALLOW_ORIGIN, $service->Service()->AllowOrigin());

		$headers = array();

		foreach ($_SERVER as $name => $value) {
			$name = str_replace('CONTENT_', 'HTTP_CONTENT_', $name);

			if (substr($name, 0, 5) == 'HTTP_')
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
		}

		$output = null;

		$type = $service->Input()->Processor()->MimeType();
		$body = file_get_contents('php://input');

		$service->Input()->Method($_SERVER['REQUEST_METHOD']);
		$service->Input()->Headers($headers);
		$service->Input()->Merge($service->Input()->Processor()->Decode(strlen(trim($body)) != 0 ? $body : (isset($_POST[$type]) ? $_POST[$type] : '')));
		$service->Input()->Merge((object)($_GET + $_POST));

		if (isset($_POST[$type]))
			unset($_POST[$type]);

		$files = QuarkFile::FromFiles($_FILES);
		$post = QuarkObject::Normalize(new \StdClass(), $service->Input()->Data(), function ($item) use ($files) {
			foreach ($files as $key => $value)
				if ($key == $item) return $value;

			return $item;
		});

		$service->Input()->Merge($post);
		$service->Input()->Merge((object)$files);

		if ($service->Service() instanceof IQuarkServiceWithRequestBackbone)
			$service->Input()->Data(QuarkObject::Normalize($service->Input()->Data(), $service->Service()->RequestBackbone()));

		$method = $service instanceof IQuarkAnyService
			? 'Any'
			: ucfirst(strtolower($_SERVER['REQUEST_METHOD']));

		$output = $service->Authorize($method);

		if ($output === null && strlen(trim($method)) != 0 && QuarkObject::is($service->Service(), 'Quark\IQuark' . $method . 'Service'))
			$output = $service->Service()->$method($service->Input(), $service->Session());

		echo 'test1';
		if ($output instanceof QuarkView) {
			echo $output->Compile();
		}
		else {
			$service->Output()->Merge($output);

			$headers = explode("\r\n", $service->Output()->SerializeHeaders());

			foreach ($headers as $header) header($header);

			echo $service->Output()->Processor()->Encode($service->Output()->Data());
		}

		echo 'test2';

		return true;
	}

	/**
	 * @return mixed
	 */
	public function Thread1 () {
		/**
		 * @var IQuarkAuthorizableService|IQuarkServiceWithCustomProcessor|IQuarkServiceWithCustomRequestProcessor|IQuarkServiceWithCustomResponseProcessor|IQuarkServiceWithAccessControl|IQuarkServiceWithRequestBackbone|IQuarkService $service
		 */
		$service = Quark::SelectService($_SERVER['REQUEST_URI']);

		$request = new QuarkDTO();
		$request->Processor(Quark::Config()->Processor(QuarkConfig::REQUEST));
		$response = new QuarkDTO();
		$response->Processor(Quark::Config()->Processor(QuarkConfig::RESPONSE));

		if ($service instanceof IQuarkServiceWithCustomProcessor) {
			$request->Processor($service->Processor());
			$response->Processor($service->Processor());
			$response->Header(QuarkDTO::HEADER_CONTENT_TYPE, $response->Processor()->MimeType());
		}

		if ($service instanceof IQuarkServiceWithCustomRequestProcessor)
			$response->Processor($service->RequestProcessor());

		if ($service instanceof IQuarkServiceWithCustomResponseProcessor) {
			$response->Processor($service->ResponseProcessor());
			$response->Header(QuarkDTO::HEADER_CONTENT_TYPE, $response->Processor()->MimeType());
		}

		if ($service instanceof IQuarkServiceWithAccessControl)
			$response->Header(QuarkDTO::HEADER_ALLOW_ORIGIN, $service->AllowOrigin());

		$headers = array();

		foreach ($_SERVER as $name => $value) {
			$add = false;

			if (substr($name, 0, 5) == 'HTTP_') {
				$name = substr($name, 5);
				$add = true;
			}

			if (substr($name, 0, 8) == 'CONTENT_')
				$add = true;

			if ($add)
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $name))))] = $value;
		}

		ob_start();

		$ok = true;
		$output = null;

		$type = $request->Processor()->MimeType();
		$body = file_get_contents('php://input');

		$request->Method($_SERVER['REQUEST_METHOD']);
		$request->URI(QuarkURI::FromURI($_SERVER['REQUEST_URI']));
		$request->Headers($headers);
		$request->Merge($request->Processor()->Decode(strlen(trim($body)) != 0 ? $body : (isset($_POST[$type]) ? $_POST[$type] : '')));
		$request->Merge((object)($_GET + $_POST));

		if (isset($_POST[$type]))
			unset($_POST[$type]);

		$files = QuarkFile::FromFiles($_FILES);
		$post = QuarkObject::Normalize(new \StdClass(), $request->Data(), function ($item) use ($files) {
			foreach ($files as $key => $value)
				if ($key == $item) return $value;

			return $item;
		});

		$request->Merge($post);
		$request->Merge((object)$files);

		if ($service instanceof IQuarkServiceWithRequestBackbone)
			$request->Data(QuarkObject::Normalize($request->Data(), $service->RequestBackbone()));

		$session = new QuarkSession();
		$method = $service instanceof IQuarkAnyService
			? 'Any'
			: ucfirst(strtolower($_SERVER['REQUEST_METHOD']));

		if ($service instanceof IQuarkAuthorizableLiteService) {
			$session = QuarkSession::Get($service->AuthorizationProvider($request));
			$session->Initialize($request);
			$response->Merge($session->Trail($response));

			if ($service instanceof IQuarkAuthorizableService) {
				$criteria = $service->AuthorizationCriteria($request, $session);

				if ($criteria !== true) {
					$ok = false;
					$output = $service->AuthorizationFailed($request, $criteria);
				}
				else {
					if (QuarkObject::is($service, 'Quark\IQuarkSigned' . $method . 'Service')) {
						$sign = $session->Signature();

						if ($sign == '' || $request->Signature() != $sign) {
							$action = 'SignatureCheckFailedOn' . $method;
							$ok = false;
							$output = $service->$action($request);
						}
					}
				}
			}
		}

		if ($ok && strlen(trim($method)) != 0 && QuarkObject::is($service, $a = 'Quark\IQuark' . $method . 'Service'))
			$output = $service->$method($request, $session);

		if ($output instanceof QuarkView) {
			echo $output->Compile();
		}
		else {
			if ($output instanceof QuarkDTO) $response = $output;
			else $response->Merge($output, true);

			if (!headers_sent()) {
				header($_SERVER['SERVER_PROTOCOL'] . ' ' . $response->Status());

				$headers = $response->Headers();
				$cookies = $response->Cookies();

				foreach ($headers as $key => $value)
					header($key . ': ' . $value);

				foreach ($cookies as $cookie)
					header(QuarkDTO::HEADER_SET_COOKIE . ': ' . $a = $cookie->Serialize(), false);
			}

			echo $response->Processor()->Encode($response->Data());
		}

		echo ob_get_clean();
	}

	/**
	 * @param \Exception $exception
	 *
	 * @return mixed
	 */
	public function ExceptionHandler (\Exception $exception) {
		if ($exception instanceof QuarkArchException)
			return Quark::Log($exception->message, $exception->lvl);

		if ($exception instanceof QuarkConnectionException)
			return Quark::Log($exception->message, $exception->lvl);

		if ($exception instanceof QuarkHTTPException) {
			ob_start();
			header($_SERVER['SERVER_PROTOCOL'] . ' ' . Quark::Config()->DefaultNotFoundStatus());
			echo ob_get_clean();

			return Quark::Log('[' . $_SERVER['REQUEST_URI'] . '] ' . $exception->message , $exception->lvl);
		}

		if ($exception instanceof \Exception)
			return Quark::Log('Common exception: ' . $exception->getMessage() . "\r\n at " . $exception->getFile() . ':' . $exception->getLine(), Quark::LOG_FATAL);

		return true;
	}
}

/**
 * Class QuarkCLIEnvironmentProvider
 *
 * @package Quark
 */
class QuarkCLIEnvironmentProvider implements IQuarkThread {
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
			else $service = Quark::SelectService('/' . $argv[1]);

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
		if ($exception instanceof QuarkException)
			Quark::Log($exception->message, $exception->lvl);
	}
}

/**
 * Interface IQuarkTransportProvider
 *
 * @package Quark
 */
interface IQuarkTransportProvider {
	/**
	 * @param QuarkURI         $uri
	 * @param QuarkCertificate $certificate
	 *
	 * @return mixed
	 */
	public function Setup(QuarkURI $uri, QuarkCertificate $certificate = null);

	/**
	 * @param QuarkClient $client
	 * @param QuarkClient[] $clients
	 *
	 * @return bool
	 */
	public function OnConnect(QuarkClient $client, $clients);

	/**
	 * @param QuarkClient $client
	 * @param QuarkClient[] $clients
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function OnData(QuarkClient $client, $clients, $data);

	/**
	 * @param QuarkClient $client
	 * @param QuarkClient[] $clients
	 *
	 * @return mixed
	 */
	public function OnClose(QuarkClient $client, $clients);
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
 * Interface IQuarkTransportProviderClient
 *
 * @package Quark\Extensions\Quark
 */
interface IQuarkTransportProviderClient extends IQuarkTransportProvider {
	/**
	 * @param QuarkClient $client
	 *
	 * @return mixed
	 */
	public function Client(QuarkClient $client);
}

/**
 * Interface IQuarkTransportProviderServer
 *
 * @package Quark
 */
interface IQuarkTransportProviderServer extends IQuarkTransportProvider {
	/**
	 * @param QuarkServer $server
	 *
	 * @return mixed
	 */
	public function Server(QuarkServer $server);
}

/**
 * Class QuarkStreamEnvironmentProvider
 *
 * @package Quark
 */
class QuarkStreamEnvironmentProvider implements IQuarkThread, IQuarkClusterNode {
	/**
	 * @var IQuarkStreamConnect $_connect
	 */
	private $_connect;

	/**
	 * @var IQuarkStreamClose $_close
	 */
	private $_close;

	/**
	 * @var IQuarkStreamUnknown $_unknown
	 */
	private $_unknown;

	/**
	 * @var QuarkClusterNode $_cluster
	 */
	private $_cluster;

	/**
	 * @param QuarkURI|string $external
	 * @param IQuarkTransportProviderServer $transport
	 * @param string $connect = ''
	 * @param string $close = ''
	 * @param string $unknown = ''
	 */
	public function __construct ($external, IQuarkTransportProviderServer $transport, $connect = '', $close = '', $unknown = '') {
		if (!Quark::CLI() || $_SERVER['argc'] > 1) return;

		$this->_cluster = new QuarkClusterNode($this, $external, $transport);

		$this->StreamConnect($connect);
		$this->StreamClose($close);
		$this->StreamUnknown($unknown);
	}

	/**
	 * @param string $uri
	 *
	 * @return IQuarkService|IQuarkStreamConnect
	 */
	public function StreamConnect ($uri = '') {
		if (func_num_args() != 0)
			$this->_connect = Quark::SelectService($uri);

		return $this->_connect;
	}

	/**
	 * @param string $uri
	 *
	 * @return IQuarkService|IQuarkStreamClose
	 */
	public function StreamClose ($uri = '') {
		if (func_num_args() != 0)
			$this->_close = Quark::SelectService($uri);

		return $this->_close;
	}

	/**
	 * @param string $uri
	 *
	 * @return IQuarkService|IQuarkStreamConnect
	 */
	public function StreamUnknown ($uri = '') {
		if (func_num_args() != 0)
			$this->_unknown = Quark::SelectService($uri);

		return $this->_unknown;
	}

	/**
	 * @param QuarkURI|string $uri
	 *
	 * @return QuarkURI
	 */
	public function ClusterController ($uri = '') {
		if ($this->_cluster == null) return new QuarkURI();

		if (func_num_args() != 0)
			$this->_cluster->Controller(QuarkURI::FromURI($uri));

		return $this->_cluster->Controller();
	}

	/**
	 * @return mixed
	 */
	public function Thread () {
		return $this->_cluster->Pipe();
	}

	/**
	 * @param \Exception $exception
	 *
	 * @return mixed
	 */
	public function ExceptionHandler (\Exception $exception) {
		if ($exception instanceof QuarkException)
			Quark::Log($exception->message, $exception->lvl);
	}

	/**
	 * @param QuarkClient $controller
	 * @param QuarkServer $server
	 * @param QuarkPeer $network
	 *
	 * @return mixed
	 */
	public function ControllerConnect (QuarkClient $controller, QuarkServer $server, QuarkPeer $network) {
		$internal = $network->Server()->ConnectionURI();

		if ($internal->host == QuarkServer::ALL_INTERFACES)
			$internal->host = QuarkServer::ExternalIPOf($internal->host);

		$this->_cmd('state', array(
			'internal' => $internal->URI(),
			'external' => $server->URI()->URI()
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
		$cli = QuarkJSONIOProcessor::PackageStack($data);

		foreach ($cli as $cmd) {
			$json = json_decode($cmd);

			if (!$json) throw new QuarkArchException('Malformed controller command ' . $cmd);

			if (isset($json->event) && $json->event == 'nodes') {
				if (isset($json->data) && is_array($json->data)) {
					foreach ($json->data as $node)
						$this->_cluster->Node(QuarkURI::FromURI($node->internal));
				}
			}

			if (isset($json->cmd)) switch ($json->cmd) {
				case 'broadcast':
					// TODO: hook for `broadcast` controller command
					break;

				case 'stop':
					// TODO: hook for `stop` controller command
					break;

				default:
					// TODO: hook for default controller command
					break;
			}
		}
	}

	/**
	 * @param QuarkClusterNode $cluster
	 * @param QuarkClient[] $clients
	 * @param QuarkClient $client
	 * @param string $data
	 * @param bool $local
	 *
	 * @return mixed
	 * @throws QuarkArchException
	 * @throws QuarkHTTPException
	 */
	public function OnData (QuarkClusterNode $cluster, $clients, QuarkClient $client, $data, $local) {
		$json = json_decode($data);
		$endpoint = $client->URI()->URI();

		if (!$json)
			throw new QuarkArchException('Client ' . $endpoint . ' sent invalid json: ' . $json);

		if (!isset($json->url))
			throw new QuarkArchException('Client ' . $endpoint . ' sent unknown url');

		try {
			$service = Quark::SelectService($json->url);
		}
		catch (QuarkHTTPException $e) {
			if ($this->_unknown instanceof IQuarkStreamUnknown)
				$this->_out($this->_unknown, $this->_unknown->StreamUnknown($client, $clients, $json->url), $client);
			else throw $e;

			return false;
		}

		$out = false;
		$data = new QuarkObject(isset($json->data) ? $json->data : null);

		if ($local == true && $service instanceof IQuarkStream)
			$out = $service->Stream($cluster, $data, $clients, $client);

		if ($local == false && $service instanceof IQuarkStreamNetwork)
			$out = $service->StreamNetwork($cluster, $data, $clients);

		if ($this->_out($service, $out, $client)) return true;

		throw new QuarkArchException('Class ' . get_class($service) . '  is not a stream');
	}

	/**
	 * @param QuarkClient $node
	 * @param QuarkClient[] $nodes
	 *
	 * @return mixed
	 */
	public function NodeConnect (QuarkClient $node, $nodes) {
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
	 * @param QuarkClient $client
	 * @param QuarkClient[] $clients
	 *
	 * @return mixed
	 * @throws QuarkArchException
	 */
	public function ClientConnect (QuarkClient $client, $clients) {
		$this->_cmd('state', array(
			'clients' => sizeof($clients)
		));

		if ($this->_connect instanceof IQuarkStreamConnect)
			$this->_out($this->_connect, $this->_connect->StreamConnect($client, $clients), $client);

		throw new QuarkArchException('Class ' . get_class($this->_connect) . ' is not an IQuarkStreamConnect');
	}

	/**
	 * @param QuarkClient $client
	 * @param QuarkClient[] $clients
	 *
	 * @return mixed
	 * @throws QuarkArchException
	 */
	public function ClientClose (QuarkClient $client, $clients) {
		$this->_cmd('state', array(
			'clients' => sizeof($clients)
		));

		if ($this->_close instanceof IQuarkStreamClose)
			$this->_out($this->_close, $this->_close->StreamClose($client, $clients), $client);

		throw new QuarkArchException('Class ' . get_class($this->_close) . ' is not an IQuarkStreamClose');
	}

	/**
	 * @param IQuarkService $stream
	 * @param $message
	 * @param QuarkClient $client
	 *
	 * @return bool
	 * @throws QuarkArchException
	 */
	private function _out (IQuarkService $stream, $message, QuarkClient $client) {
		if ($message === null) return true;

		$message = json_encode($message);

		if (!$message || strlen($message) == 0)
			throw new QuarkArchException('Stream of ' . get_class($stream) . ' returned invalid json: ' . $message);

		$client->Send($message);
		return true;
	}

	/**
	 * @param string $name
	 * @param array $data
	 */
	private function _cmd ($name = '', $data = []) {
		$this->_cluster->Control(json_encode(array(
			'cmd' => $name,
			'state' => $data
		)));
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
interface IQuarkExtensionConfig { }

/**
 * Interface IQuarkAuthProvider
 *
 * @package Quark
 */
interface IQuarkAuthorizationProvider {
	/**
	 * @param string $name
	 * @param QuarkDTO $request
	 * @param $lifetime
	 *
	 * @return mixed
	 */
	public function Initialize($name, QuarkDTO $request, $lifetime);

	/**
	 * @param string $name
	 * @param QuarkDTO $response
	 * @param QuarkModel $user
	 *
	 * @return mixed
	 */
	public function Trail($name, QuarkDTO $response, QuarkModel $user);

	/**
	 * @param string $name
	 * @param QuarkModel $model
	 * @param $criteria
	 *
	 * @return bool
	 */
	public function Login($name, QuarkModel $model, $criteria);

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function Logout($name);

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	public function Signature($name);
}

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
 * Interface IQuarkAuthorizableModel
 *
 * @package Quark
 */
interface IQuarkAuthorizableModel {
	/**
	 * @param $criteria
	 *
	 * @return mixed
	 */
	public function Authorize($criteria);

	/**
	 * @param IQuarkAuthorizationProvider $provider
	 * @param $request
	 *
	 * @return mixed
	 */
	public function RenewSession(IQuarkAuthorizationProvider $provider, $request);
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
interface IQuarkService { }

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
class QuarkTask {
	const PREDEFINED = '--quark';

	/**
	 * @var IQuarkService|IQuarkTask|IQuarkScheduledTask $_service
	 */
	private $_service = null;

	/**
	 * @var QuarkDate $_launched
	 */
	private $_launched = '';

	/**
	 * @param IQuarkService $service
	 */
	public function __construct (IQuarkService $service) {
		$this->_service = $service;
		$this->_launched = QuarkDate::Now();
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
interface IQuarkScheduledTask {
	/**
	 * @param QuarkDate $previous
	 *
	 * @return bool
	 */
	public function LaunchCriteria(QuarkDate $previous);
}

/**
 * Class QuarkThreadSet
 *
 * @package Quark
 */
class QuarkThreadSet {
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
	 * @return bool|mixed
	 */
	public function Invoke () {
		$run = true;

		foreach ($this->_threads as $thread) {
			if (!($thread instanceof IQuarkThread)) continue;

			try {
				$run = call_user_func_array(array($thread, 'Thread'), $this->_args);
			}
			catch (\Exception $e) {
				$run = $thread->ExceptionHandler($e);
			}
		}

		return $run;
	}

	/**
	 * @param int $sleep = 1 (microseconds)
	 */
	public function Pipeline ($sleep = 1) {
		self::Queue(function () {
			return $this->Invoke();
		}, $sleep);
	}

	/**
	 * @param callable $pipe
	 * @param int $sleep = 1 (microseconds)
	 */
	public static function Queue (callable $pipe, $sleep = 1) {
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
	 * @param QuarkClusterNode $cluster
	 * @param QuarkObject $data
	 * @param QuarkClient[] $clients
	 * @param QuarkClient $client
	 *
	 * @return mixed
	 */
	public function Stream(QuarkClusterNode $cluster, QuarkObject $data, $clients, $client);
}

/**
 * Interface IQuarkStreamNetwork
 *
 * @package Quark
 */
interface IQuarkStreamNetwork extends IQuarkService {
	/**
	 * @param QuarkClusterNode $cluster
	 * @param QuarkObject $data
	 * @param QuarkClient[] $clients
	 *
	 * @return mixed
	 */
	public function StreamNetwork(QuarkClusterNode $cluster, QuarkObject $data, $clients);
}

/**
 * Interface IQuarkStreamConnect
 *
 * @package Quark
 */
interface IQuarkStreamConnect extends IQuarkService {
	/**
	 * @param QuarkClient $client
	 * @param QuarkClient[] $clients
	 *
	 * @return mixed
	 */
	public function StreamConnect(QuarkClient $client, $clients);
}

/**
 * Interface IQuarkStreamClose
 *
 * @package Quark
 */
interface IQuarkStreamClose extends IQuarkService {
	/**
	 * @param QuarkClient $client
	 * @param QuarkClient[] $clients
	 *
	 * @return mixed
	 */
	public function StreamClose(QuarkClient $client, $clients);
}

/**
 * Interface IQuarkStreamUnknown
 *
 * @package Quark
 */
interface IQuarkStreamUnknown extends IQuarkService {
	/**
	 * @param QuarkClient $client
	 * @param QuarkClient[] $clients
	 * @param string $url
	 *
	 * @return mixed
	 */
	public function StreamUnknown(QuarkClient $client, $clients, $url);
}

/**
 * Class QuarkService
 *
 * @package Quark
 */
class QuarkService {
	/**
	 * @var IQuarkService|IQuarkServiceWithAccessControl|IQuarkServiceWithRequestBackbone $_service
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
		$this->_input->Processor($input);
		$this->_output = new QuarkDTO();
		$this->_output->Processor($output);

		if ($this->_service instanceof IQuarkServiceWithCustomProcessor) {
			$this->_input->Processor($this->_service->Processor());
			$this->_output->Processor($this->_service->Processor());
		}

		if ($this->_service instanceof IQuarkServiceWithCustomRequestProcessor)
			$this->_output->Processor($this->_service->RequestProcessor());

		if ($this->_service instanceof IQuarkServiceWithCustomResponseProcessor)
			$this->_output->Processor($this->_service->ResponseProcessor());

		$this->_session = new QuarkSession();
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
	 * @param string $method
	 *
	 * @return mixed|null
	 */
	public function Authorize ($method = '') {
		if (!($this->_service instanceof IQuarkAuthorizableLiteService)) return null;

		$this->_session = QuarkSession::Get($this->_service->AuthorizationProvider($this->_input));
		$this->_session->Initialize($this->_input);

		$this->_output->Merge($this->_session->Trail($this->_output));

		if (!($this->_service instanceof IQuarkAuthorizableService)) return null;

		$criteria = $this->_service->AuthorizationCriteria($this->_input, $this->_session);

		if ($criteria !== true)
			return $this->_service->AuthorizationFailed($this->_input, $criteria);

		if (!QuarkObject::is($this->_service, 'Quark\IQuarkSigned' . $method . 'Service')) return null;

		$sign = $this->_session->Signature();

		if ($sign != '' && $this->_input->Signature() == $sign) return null;

		$action = 'SignatureCheckFailedOn' . $method;
		return $this->_service->$action($this->_input);
	}
}

/**
 * Class QuarkContainerBehavior
 *
 * @package Quark
 */
trait QuarkContainerBehavior {
	/**
	 * @var IQuarkContainer $_container
	 */
	private $_container;

	/**
	 * @param IQuarkContainer $container
	 *
	 * @return IQuarkContainer
	 */
	public function __container (IQuarkContainer $container = null) {
		if (func_num_args() != 0)
			$this->_container = $container;

		return $this->_container;
	}

	/**
	 * @param $method
	 * @param $args
	 *
	 * @return mixed
	 */
	private function _call ($method, $args) {
		return call_user_func_array(array($this->_container, $method), $args);
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
 * Interface IQuarkContainer
 *
 * @package Quark
 */
interface IQuarkContainer { }

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
				$def = !empty($source[$i]) ? $source[$i] : $backbone[$i];
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
					$def = !empty($source->$key) ? $source->$key : $value;

					@$output->$key = self::Normalize($iterator($value, $def, $key), $def, $iterator);
				}

				unset($key, $value, $def);
			}
		}

		return $output;
	}

	/**
	 * @param $source
	 *
	 * @return bool
	 */
	public static function isAssociative ($source) {
		return is_array($source) && sizeof(array_filter(array_keys($source), 'is_string')) != 0;
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
	 * @param IQuarkContainer $container
	 * @param                 $child
	 */
	public static function Container (IQuarkContainer $container, $child) {
		if (method_exists($child, '__container'))
			$child->__container($container);
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

		QuarkObject::Container($this, $this->_view);
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
				$res = QuarkSource::FromFile($location);

				if ($obfuscate && $resource->CacheControl())
					$res->Obfuscate();

				$content = $res->Source();

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

		$provider = QuarkSession::Get($this->_view->AuthProvider());

		$sign = $provider->Signature();

		if (!is_string($sign))
			throw new QuarkArchException('AuthProvider ' . get_class($provider) . ' specified non-string Signature');

		return $field ? '<input type="hidden" name="_signature" value="' . $sign . '" />' : $sign;
	}

	/**
	 * @return QuarkModel
	 * @throws QuarkArchException
	 */
	public function User () {
		if (!($this->_view instanceof IQuarkAuthorizableViewModel))
			throw new QuarkArchException('ViewModel ' . get_class($this->_view) . ' need to be IQuarkAuthorizableViewModel');

		return QuarkSession::Get($this->_view->AuthProvider())->User();
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
			$this->_vars = QuarkObject::Normalize(new \StdClass(), (object)$params);

		return $this->_vars;
	}

	/**
	 * @return string
	 */
	public function Compile () {
		foreach ($this->_vars as $name => $value)
			$$name = $value;

		ob_start();
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
}

/**
 * Interface IQuarkViewModel
 *
 * @package Quark
 */
interface IQuarkViewModel {
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
	public function Location () {
		// TODO: Implement Location() method.
	}

	/**
	 * @return string
	 */
	public function Type () {
		// TODO: Implement Type() method.
	}

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
	 * @return string
	 */
	public function HTML () {
		return '<script type="text/javascript">' . $this->_code . '</script>';
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
}

/**
 * Class QuarkModelSource
 *
 * @package Quark
 */
class QuarkModelSource {
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
	 * @param IQuarkDataProvider $provider
	 * @param QuarkURI           $uri
	 */
	public function __construct (IQuarkDataProvider $provider, QuarkURI $uri) {
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
		return call_user_func_array(array($this->_provider, $method), $args);
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
	 * @var QuarkModelSource[]
	 */
	private static $_providers = array();

	/**
	 * @param string $name
	 * @param QuarkModelSource $source
	 *
	 * @return QuarkModelSource
	 * @throws QuarkArchException
	 */
	public static function Source ($name, QuarkModelSource $source = null) {
		$args = func_num_args();

		if ($args == 2)
			self::$_providers[$name] = $source;

		if (!is_scalar($name))
			throw new QuarkArchException('Value [' . print_r($name, true) . '] is not valid data provider name');

		if ($args == 1 && !isset(self::$_providers[$name]))
			throw new QuarkArchException('Data provider ' . print_r($name, true) . ' is not pooled');

		return self::$_providers[$name];
	}

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

		QuarkObject::Container($this, $this->_model);
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
	 *
	 * @return IQuarkDataProvider
	 * @throws QuarkArchException
	 */
	private static function _provider (IQuarkModel $model) {
		if (!($model instanceof IQuarkModelWithDataProvider))
			throw new QuarkArchException('Attempt to get data provider of model ' . get_class($model) . ' which is not defined as IQuarkModelWithDataProvider');

		$name = $model->DataProvider();
		$source = self::Source($name);

		if ($source == null)
			throw new QuarkArchException('Model source for model ' . get_class($model) . ' is not connected');

		return $source->Connect();
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
	 //* @param             $options
	 *
	 * @return IQuarkModel
	 */
	private static function _import (IQuarkModel $model, $source/*, $options = []*/) {
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
			? $property->Link(QuarkObject::isAssociative($value) ? (object)$value : $value)
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
	 * @param mixed       $data
	 * @param array       $options
	 *
	 * @return QuarkModel|\StdClass
	 */
	private static function _record (IQuarkModel $model, $data, $options = []) {
		if ($data == null) return null;

		$output = new QuarkModel($model, $data);

		$model = $output->Model();

		if ($model instanceof IQuarkModelWithAfterFind)
			$model->AfterFind($data);

		if (isset($options[self::OPTION_EXTRACT]) && $options[self::OPTION_EXTRACT] !== false)
			$output = $options[self::OPTION_EXTRACT] === true
				? $output->Extract()
				: $output->Extract($options[self::OPTION_EXTRACT]);

		return $output;
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
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return QuarkCollection
	 */
	public static function Find (IQuarkModel $model, $criteria = [], $options = []) {
		$records = array();
		$raw = self::_provider($model)->Find($model, $criteria, $options);

		if ($raw != null)
			foreach ($raw as $item)
				$records[] = self::_record($model, $item, $options);

		return isset($options[self::OPTION_EXTRACT])
			? $records
			: new QuarkCollection($model, $records);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return QuarkModel|null
	 */
	public static function FindOne (IQuarkModel $model, $criteria = [], $options = []) {
		return self::_record($model, self::_provider($model)->FindOne($model, $criteria, $options), $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $id
	 * @param $options
	 *
	 * @return QuarkModel|null
	 */
	public static function FindOneById (IQuarkModel $model, $id, $options = []) {
		return self::_record($model, self::_provider($model)->FindOneById($model, $id, $options), $options);
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
interface IQuarkModel {
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
	 * @param             $criteria
	 *
	 * @return array
	 */
	public function Find(IQuarkModel $model, $criteria);

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 *
	 * @return mixed
	 */
	public function FindOne(IQuarkModel $model, $criteria);

	/**
	 * @param IQuarkModel $model
	 * @param             $id
	 *
	 * @return mixed
	 */
	public function FindOneById(IQuarkModel $model, $id);

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
	 *
	 * @return int
	 */
	public function Count (IQuarkModel $model, $criteria, $limit, $skip);
}

/**
 * Class QuarkField
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
		$this->Timezone($timezone, false);
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
 * Class QuarkSession
 *
 * @package Quark
 */
class QuarkSession implements IQuarkLinkedModel {
	/**
	 * @var QuarkSession[] $_pool
	 */
	private static $_pool = array();

	/**
	 * @var string $_name
	 */
	private $_name = '';

	/**
	 * @var IQuarkAuthorizationProvider $_provider
	 */
	private $_provider;

	/**
	 * @var IQuarkModel|IQuarkAuthorizableModel $_model
	 */
	private $_model;

	/**
	 * @var QuarkModel $_user
	 */
	private $_user;

	/**
	 * @param string $name
	 * @param IQuarkAuthorizationProvider $provider
	 * @param IQuarkAuthorizableModel $model
	 */
	public function __construct ($name = '', IQuarkAuthorizationProvider $provider = null, IQuarkAuthorizableModel $model = null) {
		$this->_name = $name;
		$this->_provider = $provider;
		$this->_model = $model;
	}

	/**
	 * @param string $name
	 * @param IQuarkAuthorizationProvider $provider
	 * @param IQuarkAuthorizableModel $user
	 */
	public static function Init ($name, IQuarkAuthorizationProvider $provider, IQuarkAuthorizableModel $user) {
		self::$_pool[$name] = new self($name, $provider, $user);
	}

	/**
	 * @param $name
	 *
	 * @return QuarkSession
	 * @throws QuarkArchException
	 */
	public static function Get ($name) {
		$output = null;

		foreach (self::$_pool as $provider)
			if ($provider->Name() == $name) $output = $provider;

		if ($output == null)
			throw new QuarkArchException('Session ' . print_r($name, true) . ' is not pooled');

		return $output;
	}

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	public function Name ($name = '') {
		if (func_num_args() == 1)
			$this->_name = $name;

		return $this->_name;
	}

	/**
	 * @param IQuarkAuthorizationProvider $provider
	 *
	 * @return IQuarkAuthorizationProvider
	 */
	public function Provider (IQuarkAuthorizationProvider $provider = null) {
		if (func_num_args() == 1)
			$this->_provider = $provider;

		return $this->_provider;
	}

	/**
	 * @param mixed $criteria
	 *
	 * @return bool
	 */
	public function Login ($criteria) {
		if (!$this->_model || !$this->_provider) return false;

		$user = $this->_model->Authorize($criteria);
		if ($user == null) return false;

		$login = $this->_provider->Login($this->_name, $user, $criteria);
		if (!$login && $login !== null) return false;

		$this->_user = $user;

		return true;
	}

	/**
	 * @return bool
	 */
	public function Logout () {
		if ($this->_provider == null) return true;

		$this->_user = null;
		$logout = $this->_provider->Logout($this->_name);

		return $logout || $logout === null;
	}

	/**
	 * @return QuarkModel
	 */
	public function User () {
		return $this->_user;
	}

	/**
	 * @param QuarkDTO $request
	 * @param int $lifetime
	 *
	 * @return mixed
	 */
	public function Initialize (QuarkDTO $request, $lifetime = 0) {
		if (!$this->_model || !$this->_provider) return null;

		$request = $this->_provider->Initialize($this->_name, $request, $lifetime);
		if (!$request && $request !== null) return $request;

		if (is_array($request))
			$request = QuarkObject::Normalize(new \StdClass(), $request);

		$user = $this->_model->RenewSession($this->_provider, $request);
		if ($user == null) return null;

		return $this->_user = $user instanceof QuarkModel ? $user : new QuarkModel($this->_model, $user);
	}

	/**
	 * @param QuarkDTO $response
	 *
	 * @return mixed
	 */
	public function Trail (QuarkDTO $response) {
		return $this->_user != null ? $this->_provider->Trail($this->_name, $response, $this->_user) : null;
	}

	/**
	 * @return string
	 */
	public function Signature () {
		return $this->_provider->Signature($this->_name);
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Link ($raw) {
		//$out = new QuarkCollection(new self());
	}

	/**
	 * @return mixed
	 */
	public function Unlink () {
		// TODO: Implement Unlink() method.
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
	 * @var int $_timeout
	 */
	private $_timeout = 30;

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
	 * @var bool $ip
	 */
	public $ip = true;

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
		$uri->scheme = $this->URI()->scheme;

		if ($face && $uri->host == QuarkServer::ALL_INTERFACES)
			$uri->host = QuarkServer::ExternalIPOf(is_bool($face) ? $uri->host : $face);

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
		if (func_num_args() == 1 && $transport != null) {
			$this->_transport = $transport;
			$this->_transport->Setup($this->_uri, $this->_certificate);
		}

		return $this->_transport;
	}

	/**
	 * @param int $timeout = 30
	 *
	 * @return int
	 */
	public function Timeout ($timeout = 30) {
		if (func_num_args() == 1 && is_int($timeout))
			$this->_timeout = $timeout;

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
			return stream_socket_shutdown($socket, STREAM_SHUT_RDWR);
		}
		catch (\Exception $e) {
			return self::_err($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * @param     $msg
	 * @param int $number
	 *
	 * @return bool
	 */
	private static function _err ($msg, $number = 0) {
		Quark::Dispatch(Quark::EVENT_CONNECTION_EXCEPTION, array(
			'num' => $number,
			'description' => $msg
		));

		return false;
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
 * @package Quark\Extensions\Quark
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
	 * @param string $uri
	 * @param IQuarkTransportProvider $transport
	 * @param QuarkCertificate $certificate
	 * @param int $timeout = 30
	 * @param bool $block = true
	 */
	public function __construct ($uri = '', IQuarkTransportProvider $transport = null, QuarkCertificate $certificate = null, $timeout = 30, $block = true) {
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
			$this->_uri->Socket($this->ip),
			$this->_errorNumber,
			$this->_errorString,
			$this->_timeout,
			STREAM_CLIENT_CONNECT,
			$stream
		);

		if (!$this->_socket)
			return self::_err($this->_errorString, $this->_errorNumber);

		stream_set_blocking($this->_socket, (int)$this->_blocking);

		$this->_connected = true;
		$this->_remote = QuarkURI::FromURI($this->ConnectionURI(true));

		return true;
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
		return $this->_receive($this->_socket, $mode == self::MODE_STREAM, $max, $offset);
	}

	/**
	 * @return bool
	 */
	public function Close () {
		$this->_connected = false;
		return $this->_close($this->_socket);
	}

	/**
	 * @return bool
	 */
	public function Pipe () {
		$data = $this->Receive();

		if ($data)
			$this->_transport->OnData($this, null, $data);

		return true;
	}

	/**
	 * @return mixed
	 * @throws QuarkArchException
	 */
	public function Action () {
		if ($this->_transport instanceof IQuarkTransportProviderClient)
			return $this->_transport->Client($this);

		throw new QuarkArchException('QuarkClient: Transport is not an IQuarkTransportProviderClient');
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

		$uri = QuarkURI::FromURI($address);
		$uri->scheme = $scheme;

		$client->Socket($socket);
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
	 * @param bool $block = true
	 *
	 * @return bool
	 */
	public function Blocking ($block = true) {
		if (func_num_args() != 0)
			$this->_blocking = $block;

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
}

/**
 * Class QuarkServer
 *
 * @package Quark
 */
class QuarkServer {
	use QuarkNetwork;

	const ALL_INTERFACES = '0.0.0.0';

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
	 * @param int $timeout = 30
	 */
	public function __construct ($uri = '', IQuarkTransportProvider $transport = null, QuarkCertificate $certificate = null, $timeout = 30) {
		$this->URI(QuarkURI::FromURI($uri));
		$this->Transport($transport);
		$this->Certificate($certificate);
		$this->Timeout($timeout);
	}

	/**
	 * @return bool
	 */
	public function Bind () {
		$stream = stream_context_create();

		if ($this->_certificate == null) {
			stream_context_set_option($stream, 'ssl', 'verify_host', false);
			stream_context_set_option($stream, 'ssl', 'verify_peer', false);
		}
		else {
			stream_context_set_option($stream, 'ssl', 'local_cert', $this->_certificate->Location());
			stream_context_set_option($stream, 'ssl', 'passphrase', $this->_certificate->Passphrase());
		}

		$this->_socket = @stream_socket_server(
			$this->_uri->Socket($this->ip),
			$this->_errorNumber,
			$this->_errorString,
			STREAM_SERVER_BIND|STREAM_SERVER_LISTEN,
			$stream
		);

		if (!$this->_socket)
			return self::_err($this->_errorString, $this->_errorNumber);

		stream_set_blocking($this->_socket, 0);

		$this->_read = array($this->_socket);
		$this->_run = true;

		return true;
	}

	/**
	 * Loop wrapper for infinite socket processing
	 */
	public function Listen () {
		while ($this->_run)
			$this->Pipe();
	}

	/**
	 * @return bool
	 */
	public function Pipe () {
		if ($this->_socket == null) return true;

		if (sizeof($this->_read) == 0)
			$this->_read = array($this->_socket);

		if (stream_select($this->_read, $this->_write, $this->_except, 0) === false) return true;

		if (in_array($this->_socket, $this->_read, true)) {
			$socket = stream_socket_accept($this->_socket, $this->_timeout, $address);
			$client = QuarkClient::ForServer($socket, $address, $this->URI()->scheme);
			$client->Remote(QuarkURI::FromURI($this->ConnectionURI()));

			$accept = $this->_transport->OnConnect($client, $this->_clients);

			if ($accept || $accept === null) {
				$this->_clients[] = $client;
				unset($this->_read[array_search($this->_socket, $this->_read, true)]);
			}
		}

		$this->_read = array();

		foreach ($this->_clients as $key => &$client) {
			$data = $client->Receive(QuarkClient::MODE_BUCKET);

			if (feof($client->Socket())) {
				unset($this->_clients[$key]);

				$this->_transport->OnClose($client, $this->_clients);
				$client->Close();

				continue;
			}

			if ($data !== false)
				$this->_transport->OnData($client, $this->_clients, $data);

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
	 * @return mixed
	 * @throws QuarkArchException
	 */
	public function Action () {
		if ($this->_transport instanceof IQuarkTransportProviderServer)
			return $this->_transport->Server($this);

		throw new QuarkArchException('QuarkServer: Transport is not an IQuarkTransportProviderServer');
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

	/**
	 * @param $host
	 *
	 * @return string
	 */
	public static function ExternalIPOf ($host) {
		return gethostbyname(gethostbyaddr($host));
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
	 * @param QuarkURI|string $uri
	 * @param bool $unique = true
	 * @param bool $loopBack = false
	 *
	 * @return bool
	 */
	public function Peer ($uri = null, $unique = true, $loopBack = false) {
		if (!$uri) return false;

		$server = $this->_server->ConnectionURI();
		$server->host = QuarkServer::ExternalIPOf($server->host);

		if ($uri instanceof QuarkURI)
			$uri = $uri->URI();

		if ($uri == ':///') return false;

		if (!$loopBack && $uri == $server) return false;
		if ($unique && $this->Has($uri)) return false;

		$peer = new QuarkClient($uri, $this->_transport, null, 30, false);
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
	 * @param QuarkClient|QuarkURI|string $peer
	 *
	 * @return bool
	 */
	public function Has ($peer) {
		if ($peer instanceof QuarkClient && $peer->ConnectionURI() != null)
			$peer = $peer->ConnectionURI()->URI();

		if ($peer instanceof QuarkURI)
			$peer = $peer->URI();

		$all = $this->_server->URI()->host == QuarkServer::ALL_INTERFACES;
		$external = QuarkServer::ExternalIPOf($this->_server->ConnectionURI()->host);

		foreach ($this->_peers as $item) {
			$uri = $item->ConnectionURI(true);

			if ($uri == null) continue;
			if ($all) $uri->host = $external;
			if ($uri->URI() == $peer) return true;
		}

		return false;
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
	 * @var string $_key = ''
	 */
	private $_key = '';

	/**
	 * @var int $_weight = 1
	 */
	private $_weight = 1;

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
	 * @param QuarkURI|string $external
	 * @param QuarkURI|string $controller
	 * @param IQuarkTransportProvider|IQuarkIntermediateTransportProvider $transport
	 * @param string $key
	 * @param string $weight
	 * @param QuarkURI|string $internal
	 */
	public function __construct (IQuarkClusterNode $node, $external, IQuarkTransportProvider $transport = null, $controller = '', $key = '', $weight = '', $internal = 'tcp://0.0.0.0:0') {
		$this->_key = $key;
		$this->_weight = $weight;
		$this->_node = $node;

		if ($transport instanceof IQuarkIntermediateTransportProvider)
			$transport->Protocol($this);

		$this->_server = new QuarkServer($external, $transport);
		$this->_network = new QuarkPeer($this, $internal);
		$this->_controller = new QuarkClient($controller, $this, null, 5, false);
	}

	/**
	 * @param QuarkURI $uri
	 *
	 * @return QuarkURI
	 */
	public function Controller (QuarkURI $uri = null) {
		if (func_num_args() != 0)
			$this->_controller->URI($uri);

		return $this->_controller->URI();
	}

	/**
	 * @param QuarkURI|string $internal
	 * @param QuarkURI|string $external
	 *
	 * @return QuarkClusterNode
	 */
	public function Interfaces ($internal = '', $external = '') {
		if ($internal != '')
			$this->_network->URI(QuarkURI::FromURI($internal));

		if ($external != '')
			$this->_server->URI(QuarkURI::FromURI($external));

		return $this;
	}

	/**
	 * @return QuarkServer
	 */
	public function Server () {
		return $this->_server;
	}

	/**
	 * @return QuarkPeer
	 */
	public function Network () {
		return $this->_network;
	}

	/**
	 * @return bool
	 */
	public function Bind () {
		$run = true;

		if (!$this->_server->Running()) {
			echo "[cluster.start]\r\n";
			$run &= $this->_server->Bind();
			echo ' - server:     ', $this->_server->ConnectionURI(),"\r\n";
		}

		if (!$this->_network->Running()) {
			$run &= $this->_network->Bind();
			echo ' - network:    ', $this->_network->Server()->ConnectionURI(),"\r\n";
		}

		if (!$this->_controller->Connected()) {
			$run &= $this->_controller->Connect();
			echo ' - controller: ', $this->_controller->ConnectionURI(),"\r\n\r\n";

			if ($run)
				$this->_node->ControllerConnect($this->_controller, $this->_server, $this->_network);
		}

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
		$this->_node->OnData($this, $this->_server->Clients(), new QuarkClient(), $data, false);
		return $this->_network->Broadcast($data);
	}

	/**
	 * @param QuarkURI $node
	 * @param bool $unique = true
	 * @param bool $loopBack = false
	 *
	 * @return bool
	 */
	public function Node (QuarkURI $node, $unique = true, $loopBack = false) {
		$ok = $this->_network->Peer($node, $unique, $loopBack);
		if ($ok)
			echo '[cluster.node] ', $this->_network->Server()->ConnectionURI(), ' ', $node, "\r\n";
		return $ok;
	}

	/**
	 * @param QuarkURI[] $nodes
	 * @param bool $unique = true
	 * @param bool $loopBack = false
	 *
	 * @return QuarkClient[]|bool
	 */
	public function Nodes ($nodes = [], $unique = true, $loopBack = false) {
		if (func_num_args() == 0)
			return $this->_network->Peers();

		return $this->_network->Peers($nodes, $unique, $loopBack);
	}

	/**
	 * @param QuarkURI $uri
	 * @param QuarkCertificate $certificate
	 *
	 * @return mixed
	 */
	public function Setup (QuarkURI $uri, QuarkCertificate $certificate = null) { }

	/**
	 * @param QuarkClient   $client
	 * @param QuarkClient[] $clients
	 *
	 * @return bool
	 */
	public function OnConnect (QuarkClient $client, $clients) {
		$connection = $client->ConnectionURI();
		$server = $this->_server->ConnectionURI(false, $connection->host);
		$network = $this->_network->Server()->ConnectionURI(false, true);

		if ($connection->URI() == $server->URI())
			$this->_node->ClientConnect($client, $clients);

		if ($connection->URI() == $network->URI())
			$this->_node->NodeConnect($client, $this->_network->Peers());
	}

	/**
	 * @param QuarkClient $client
	 * @param QuarkClient[] $clients
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function OnData (QuarkClient $client, $clients, $data) {
		$remote = $client->Remote();
		$remote->host = QuarkServer::ExternalIPOf($remote->host);

		$controller = $this->_controller->ConnectionURI(true);
		$server = $this->_server->ConnectionURI(false, true);
		$network = $this->_network->Server()->ConnectionURI(false, true);

		switch ($remote->URI()) {
			case $controller->URI():
				$this->_node->ControllerData($this->_controller, $data);
				break;

			case $server->URI():
				$this->_node->OnData($this, $clients, $client, $data, true);
				break;

			case $network->URI():
				$this->_node->OnData($this, $this->_server->Clients(), $client, $data, false);
				break;

			default:
				break;
		}
	}

	/**
	 * @param QuarkClient   $client
	 * @param QuarkClient[] $clients
	 *
	 * @return mixed
	 */
	public function OnClose (QuarkClient $client, $clients) {
		if ($client == $this->_controller) return;

		$this->_node->ClientClose($client, $clients);
	}

	/**
	 * @return bool
	 */
	public function Pipe () {
		return
			$this->Bind() &&
			$this->_server->Pipe() &&
			$this->_controller->Pipe() &&
			$this->_network->Pipe();
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
	 * @param QuarkServer $server
	 * @param QuarkPeer $network
	 *
	 * @return mixed
	 */
	public function ControllerConnect(QuarkClient $controller, QuarkServer $server, QuarkPeer $network);

	/**
	 * @param QuarkClient $controller
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function ControllerData(QuarkClient $controller, $data);

	/**
	 * @param QuarkClusterNode $cluster
	 * @param QuarkClient[] $clients
	 * @param QuarkClient $client
	 * @param string $data
	 * @param bool $local
	 *
	 * @return mixed
	 */
	public function OnData(QuarkClusterNode $cluster, $clients, QuarkClient $client, $data, $local);

	/**
	 * @param QuarkClient $node
	 * @param QuarkClient[] $nodes
	 *
	 * @return mixed
	 */
	public function NodeConnect(QuarkClient $node, $nodes);

	/**
	 * @param QuarkClient $client
	 * @param QuarkClient[] $clients
	 *
	 * @return mixed
	 */
	public function ClientConnect(QuarkClient $client, $clients);

	/**
	 * @param QuarkClient $client
	 * @param QuarkClient[] $clients
	 *
	 * @return mixed
	 */
	public function ClientClose(QuarkClient $client, $clients);
}

/**
 * Class QuarkURI
 *
 * @package Quark\Extensions\Quark
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
	 * @param string $uri
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
	 * @param string $path
	 * @param bool $full = true
	 * @param bool $secure = false
	 *
	 * @return string
	 */
	public static function Of ($path, $full = true, $secure = false) {
		$path = Quark::NormalizePath($path, false);

		return Quark::WebHost($full, $secure) . (strlen($path) != 0 && $path[0] == '/' ? substr($path, 1) : $path);
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
		return
			($this->scheme !== null ? $this->scheme : 'http')
			. '://'
			. ($this->user !== null ? $this->user . ($this->pass !== null ? ':' . $this->pass : '') . '@' : '')
			. $this->host
			. ($this->port !== null ? ':' . $this->port : '')
			. ($this->path !== null ? Quark::NormalizePath('/' . $this->path, false) : '')
			. ($full ? '/?' . $this->query : '');
	}

	/**
	 * @param bool $ip
	 *
	 * @return string|bool
	 */
	public function Socket ($ip = true) {
		$dns = dns_get_record($this->host, DNS_A);

		if ($dns === false) return false;

		return (isset(self::$_transports[$this->scheme]) ? self::$_transports[$this->scheme] : 'tcp')
		. '://'
		. ($ip ? gethostbyname($this->host) : $this->host)
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
}

/**
 * Class QuarkDTO
 *
 * @package Quark\Extensions\Quark
 */
class QuarkDTO {
	const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';

	const HEADER_HOST = 'Host';
	const HEADER_CACHE_CONTROL = 'Cache-Control';
	const HEADER_CONTENT_LENGTH = 'Content-Length';
	const HEADER_CONTENT_TYPE = 'Content-Type';
	const HEADER_CONTENT_TRANSFER_ENCODING = 'Content-Transfer-Encoding';
	const HEADER_CONTENT_DISPOSITION = 'Content-Disposition';
	const HEADER_CONTENT_DESCRIPTION = 'Content-Description';
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
	const HEADER_WWW_AUTHENTICATE = 'WWW-Authenticate';

	const STATUS_200_OK = '200 OK';
	const STATUS_401_UNAUTHORIZED = '401 Unauthorized';
	const STATUS_403_FORBIDDEN = '403 Forbidden';
	const STATUS_404_NOT_FOUND = '404 Not Found';
	const STATUS_500_SERVER_ERROR = '500 Server Error';

	const CONNECTION_KEEP_ALIVE = 'keep-alive';
	const CONNECTION_UPGRADE = 'Upgrade';

	const UPGRADE_WEBSOCKET = 'websocket';

	/**
	 * @var string $_raw
	 */
	private $_raw = '';

	/**
	 * @var IQuarkIOProcessor $_processor
	 */
	private $_processor = null;

	/**
	 * @var QuarkURI $_uri
	 */
	private $_uri = null;

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
	 * @var string $_boundary
	 */
	private $_boundary = '';

	/**
	 * @var mixed $_data
	 */
	private $_data = '';

	/**
	 * @var mixed $_textData
	 */
	private $_textData = '';

	/**
	 * @var string $_signature
	 */
	private $_signature = '';

	/**
	 * @param $key
	 *
	 * @return mixed
	 */
	public function &__get ($key) {
		return $this->_data->$key;
	}

	/**
	 * @param $key
	 * @param $value
	 */
	public function __set ($key, $value) {
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
		$this->Processor($processor == null ? new QuarkPlainIOProcessor() : $processor);
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
	 * @param bool $all
	 * @param callable $head
	 *
	 * @return string
	 */
	private function _serialize ($all = true, callable $head) {
		$query = '';

		$this->_processor = new QuarkMultipartIOProcessor($this->_processor, $this->_boundary);
		$this->_raw = $this->_processor->Encode($this->Data(), $this->_textData);

		if ($all) {
			if ($this->_uri != null) $query .= $head();

			$query .= $this->SerializeHeaders();
		}

		return $this->_raw = $query . $this->_raw;
	}

	/**
	 * @param bool $all
	 *
	 * @return string
	 */
	public function Serialize ($all = true) {
		$this->_headers[self::HEADER_HOST] = $this->_uri->host;
		$this->_headers[self::HEADER_COOKIE] = QuarkCookie::SerializeCookies($this->_cookies);

		return $this->_serialize($all, function () {
			return $this->_method . ' ' . $this->_uri->Query() . ' HTTP/1.0' . "\r\n";
		});
	}

	/**
	 * @return string
	 */
	public function SerializeHeaders () {
		$query = '';

		$this->_headers[self::HEADER_CONTENT_TYPE] = $this->_processor->MimeType() . '; charset=utf-8';
		$this->_headers[self::HEADER_CONTENT_LENGTH] = strlen($this->_raw);

		foreach ($this->_headers as $key => $value)
			$query .= $key . ': ' . $value . "\r\n";

		return $query . "\r\n";
	}

	/**
	 * @param string $raw
	 *
	 * @return QuarkDTO
	 */
	public function Unserialize ($raw = '') {
		if (func_num_args() != 0)
			$this->_raw = $raw;

		if (preg_match_all('#^HTTP\/(.*)\n(.*)\n\s\n(.*)$#Uis', $this->_raw, $found, PREG_SET_ORDER) == 0) return null;

		$http = $found[0];

		$status = explode(' ', $http[1]);

		if (sizeof($status) > 1)
			$this->_status = $status[1];

		$this->ParseHeaders($http[2]);
		$this->Merge($this->_processor->Decode($http[3]));

		return $this;
	}

	/**
	 * @param bool $all
	 *
	 * @return string
	 */
	public function SerializeResponse ($all = true) {
		$this->_headers[self::HEADER_SET_COOKIE] = QuarkCookie::SerializeCookies($this->_cookies);

		return $this->_serialize($all, function () {
			return 'HTTP/1.0 ' . $this->_status . "\r\n";
		});
	}

	/**
	 * @param string $raw
	 * @param bool   $secure
	 *
	 * @return QuarkDTO
	 */
	public function UnserializeRequest ($raw = '', $secure = false) {
		$this->_raw = $raw;

		if (preg_match_all('#^(.*) (.*) HTTP\/(.*)\n(.*)\n\s\n(.*)$#Uis', $raw . "\r\n", $found, PREG_SET_ORDER) == 0) return null;

		$http = $found[0];

		$this->Method($http[1]);
		$this->ParseHeaders($http[4]);
		$this->URI(QuarkURI::FromURI('http' . ($secure ? 's' : '') . '://' . $this->Header(QuarkDTO::HEADER_HOST) . $http[2]));
		$this->Data($http[5]);

		return $this;
	}

	/**
	 * @param string $source
	 */
	public function ParseHeaders ($source = '') {
		if (preg_match_all('#(.*)\: (.*)\n#Uis', $source . "\r\n", $headers, PREG_SET_ORDER) == 0) return;

		$cookies = array();

		foreach ($headers as $header) {
			$key = trim($header[1]);
			$value = trim($header[2]);

			if ($key == self::HEADER_SET_COOKIE) $cookies[] = $value;
			else $this->_headers[$key] = $value;
		}

		foreach ($cookies as $cookie)
			$this->_cookies[] = QuarkCookie::FromSetCookie($cookie);
	}

	/**
	 * @param IQuarkIOProcessor $processor
	 *
	 * @return IQuarkIOProcessor
	 */
	public function Processor (IQuarkIOProcessor $processor = null) {
		if (func_num_args() == 1 && $processor != null) {
			$this->_processor = $processor;
			$this->_headers[self::HEADER_CONTENT_TYPE] = $processor->MimeType();
		}

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
			$this->_headers = $headers;

			if (isset($headers[self::HEADER_COOKIE]))
				$this->_cookies = QuarkCookie::FromCookie($headers[self::HEADER_COOKIE]);

			if (isset($headers[self::HEADER_SET_COOKIE]))
				$this->_cookies = QuarkCookie::FromSetCookie($headers[self::HEADER_SET_COOKIE]);
		}

		return $this->_headers;
	}

	/**
	 * @param $key
	 * @param $value
	 *
	 * @return mixed
	 */
	public function Header ($key, $value = null) {
		if (func_num_args() == 2)
			$this->_headers[$key] = $value;

		if ($key == self::HEADER_COOKIE)
			$this->_cookies = QuarkCookie::FromCookie($value);

		if ($key == self::HEADER_SET_COOKIE)
			$this->_cookies[] = QuarkCookie::FromSetCookie($value);

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
	 */
	public function Cookie (QuarkCookie $cookie) {
		if ($cookie == null) return;

		$this->_cookies[] = $cookie;
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
		if (func_num_args() == 1)
			$this->_data = $data;

		return $this->_data;
	}

	/**
	 * @param mixed $textData
	 *
	 * @return mixed
	 */
	public function TextData ($textData = []) {
		if (func_num_args() == 1)
			$this->_textData = $textData;

		return $this->_textData;
	}

	/**
	 * @param mixed $raw
	 *
	 * @return mixed
	 */
	public function Raw ($raw = []) {
		if (func_num_args() == 1)
			$this->_raw = $raw;

		return $this->_raw;
	}

	/**
	 * @param mixed $data
	 *
	 * @return QuarkDTO
	 */
	public function Merge ($data = []) {
		if ($data instanceof QuarkView) $this->_data = $data;
		elseif ($data instanceof QuarkDTO) {
			$this->_status = $data->Status();
			$this->_method = $data->Method();
			$this->_data = $data->Data();
			$this->_headers = $data->Headers();
			$this->_boundary = $data->Boundary();
			$this->_cookies = $data->Cookies();
			$this->_processor = $data->Processor();
			$this->_raw = $data->Raw();
			$this->_signature = $data->Signature();
			$this->_textData = $data->TextData();
			$this->_uri = $data->URI() == null ? $this->_uri : $data->URI();
		}
		else {
			if (is_string($this->_data)) {
				if (is_string($data)) $this->_data .= $data;
				else {
					$this->_textData = $this->_data;
					$this->_data = QuarkObject::Normalize($this->_data, $data);
				}
			}
			else {
				if (is_string($data)) $this->_textData .= $data;
				else {
					$this->_data = QuarkObject::Normalize($this->_data, $data);
					$this->_signature = isset($this->_data->_signature) ? $this->_data->_signature : '';
				}
			}
		}

		return $this;
	}

	/**
	 * @return string
	 */
	public function Signature () {
		return $this->_signature;
	}
}

/**
 * Class QuarkHTTPTransportClient
 *
 * @package Quark\Extensions\Quark
 */
class QuarkHTTPTransportClient implements IQuarkTransportProviderClient {
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
	public function __construct (QuarkDTO $request, QuarkDTO $response) {
		$this->_request = $request;
		$this->_response = $response;
	}

	/**
	 * @param QuarkURI         $uri
	 * @param QuarkCertificate $certificate
	 *
	 * @return mixed
	 */
	public function Setup (QuarkURI $uri, QuarkCertificate $certificate = null) {
		$this->_request->URI($uri);
	}

	/**
	 * @param QuarkClient $client
	 *
	 * @return mixed
	 */
	public function Client (QuarkClient $client) {
		if (!$client->Connect()) return false;

		$this->_response->URI($this->_request->URI());
		$this->_response->Method($this->_request->Method());

		$client->Send($request = $this->_request->Serialize());
		$this->_response->Unserialize($response = $client->Receive());
		$client->Close();

		return $this->_response;
	}

	/**
	 * @param QuarkClient $client
	 * @param QuarkClient[] $clients
	 *
	 * @return bool
	 */
	public function OnConnect (QuarkClient $client, $clients) {
		// TODO: Implement OnConnect() method.
	}

	/**
	 * @param QuarkClient $client
	 * @param QuarkClient[] $clients
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function OnData (QuarkClient $client, $clients, $data) {
		// TODO: Implement OnData() method.
	}

	/**
	 * @param QuarkClient $client
	 * @param QuarkClient[] $clients
	 *
	 * @return mixed
	 */
	public function OnClose (QuarkClient $client, $clients) {
		// TODO: Implement OnClose() method.
	}
}

/**
 * Class QuarkHTTPTransportServer
 *
 * @package Quark
 */
class QuarkHTTPTransportServer implements IQuarkTransportProviderServer {
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
	 * @param QuarkClient[] $clients
	 *
	 * @return bool
	 */
	public function OnConnect (QuarkClient $client, $clients) {
		$this->_protocol->OnConnect($client, $clients);
	}

	/**
	 * @param QuarkClient $client
	 * @param QuarkClient[] $clients
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function OnData (QuarkClient $client, $clients, $data) {
		$this->_protocol->OnData($client, $clients, $data);
	}

	/**
	 * @param QuarkClient $client
	 * @param QuarkClient[] $clients
	 *
	 * @return mixed
	 */
	public function OnClose (QuarkClient $client, $clients) {
		$this->_protocol->OnClose($client, $clients);
	}

	/**
	 * @param QuarkServer $server
	 *
	 * @return mixed
	 */
	public function Server (QuarkServer $server) {
		if (!$server->Bind()) return false;

		$server->Listen();

		return true;
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
	public $name = '';
	public $value = '';
	public $expires = '';
	public $MaxAge = '';
	public $path = '';
	public $domain = '';
	public $HttpOnly = '';
	public $secure = '';

	/**
	 * @param string $name
	 * @param string $value
	 */
	public function __construct ($name = '', $value = '') {
		$this->name = $name;
		$this->value = $value;
	}

	/**
	 * @return string
	 */
	public function __toString () {
		return $this->value;
	}

	/**
	 * @param string $header
	 *
	 * @return QuarkCookie[]
	 */
	public static function FromCookie ($header) {
		$out = array();
		$cookies = explode(';', $header);

		foreach ($cookies as $raw) {
			$cookie = explode('=', $raw);

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
	public static function FromSetCookie ($header) {
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
		$main = $this->name . '=' . $this->value;

		if (!$full) return $main;
		else {
			$out = $main;

			foreach ($this as $field => $value)
				if (strlen(trim($value)) != 0)
					$out .= '; ' . $field . '=' . $value;

			return $out;
		}
	}
}

/**
 * Class QuarkFile
 *
 * @package Quark
 */
class QuarkFile implements IQuarkModel, IQuarkStrongModel, IQuarkLinkedModel, IQuarkModelWithDataProvider {
	const LOCAL_FS = 'LocalFS';

	public $_location = '';
	public $location = '';
	public $name = '';
	public $type = '';
	public $tmp_name = '';
	public $size = 0;
	public $extension = '';
	public $isDir = false;

	private $_content = '';
	private $_loaded = false;

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
	 */
	public function __construct ($location = '') {
		if (func_num_args() != 0)
			$this->Location($location);
	}

	/**
	 * @param string $location
	 *
	 * @return string
	 */
	public function Location ($location = '') {
		if (func_num_args() == 1) {
			$real = realpath($location);

			$this->location = Quark::NormalizePath($real ? $real : $location, false);
			$this->name = array_reverse(explode('/', $this->location))[0];

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
		return is_file($this->location);
	}

	/**
	 * @param $location
	 *
	 * @return QuarkFile
	 * @throws QuarkArchException
	 */
	public function Load ($location) {
		$this->Location($location);

		if (!$this->Exists())
			throw new QuarkArchException('Invalid file path "' . $this->location . '"');

		if (file_exists($this->_location) && memory_get_usage() <= Quark::Config()->Alloc() * 1024 * 1024)
			$this->Content(file_get_contents($this->location));

		return $this;
	}

	/**
	 * @return bool
	 */
	public function SaveContent () {
		return file_put_contents($this->location, $this->_content) != 0;
	}

	/**
	 * @param bool $full = true
	 * @param bool $secure = false
	 *
	 * @return string
	 */
	public function WebLocation ($full = true, $secure = false) {
		return QuarkURI::Of(Quark::SanitizePath(str_replace(Quark::Host(false), '', $this->location)), $full, $secure);
	}

	/**
	 * @param string $content
	 *
	 * @return string
	 */
	public function Content ($content = '') {
		if (func_num_args() == 1) {
			$this->_content = $content;
			$this->_loaded = true;
		}

		return $this->_content;
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
			QuarkField::MinLength($this->name, 1),
			QuarkField::MinLength($this->tmp_name, 2)
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
	 * @return mixed
	 */
	public function DataProvider () {
		return QuarkModel::Source(self::LOCAL_FS);
	}

	/**
	 * @param array $files
	 *
	 * @return array
	 */
	public static function CollectionFrom ($files = []) {
		if (!is_array($files)) return array();

		$output = array();

		foreach ($files as $file)
			$output[] = self::From($file);

		return $output;
	}

	/**
	 * @param $file
	 *
	 * @return QuarkFile
	 */
	public static function From ($file) {
		return (new QuarkModel(new QuarkFile(), $file))->Model();
	}

	/**
	 * @param $files
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
}

/**
 * Class QuarkArchException
 * @package Quark
 */
class QuarkArchException extends QuarkException {
	/**
	 * @param string $message
	 */
	public function __construct ($message) {
		$this->lvl = Quark::LOG_FATAL;
		$this->message = $message;
	}
}

/**
 * Class QuarkHTTPException
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
}

/**
 * Interface IQuarkIOProcessorWithCustomHeaders
 *
 * @package Quark
 */
interface IQuarkIOProcessorWithCustomHeaders {
	/**
	 * @param array $headers
	 *
	 * @return array
	 */
	public function Headers($headers);
}

/**
 * Interface IQuarkIOProcessorWithMultipartControl
 *
 * @package Quark
 */
interface IQuarkIOProcessorWithMultipartControl {
	/**
	 * @return bool
	 */
	public function MultipartControl();
}

/**
 * Class QuarkPlainIOProcessor
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
}

/**
 * Class QuarkHTMLIOProcessor
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
}

/**
 * Class QuarkMultipartProcessor
 *
 * @package Quark
 */
class QuarkMultipartIOProcessor implements IQuarkIOProcessor {
	const DISPOSITION_FORM_DATA = 'form-data';
	const DISPOSITION_ATTACHMENT = 'attachment';

	const MIME_FORM_DATA = 'form-data';
	const MIME_MIXED = 'mixed';

	const TRANSFER_ENCODING_BINARY = 'binary';
	const TRANSFER_ENCODING_BASE64 = 'base64';

	/**
	 * @var IQuarkIOProcessor $_processor
	 */
	private $_processor;

	/**
	 * @var string $_boundary
	 */
	private $_boundary = '';

	/**
	 * @var string $_mime
	 */
	private $_mime = '';

	/**
	 * @var string $_encoding
	 */
	private $_encoding = '';

	/**
	 * @var string $_disposition
	 */
	private $_disposition = '';

	/**
	 * @var bool $_original
	 */
	private $_original = true;

	/**
	 * @param IQuarkIOProcessor $processor
	 * @param string            $boundary
	 *
	 * @return QuarkMultipartIOProcessor
	 */
	public static function ForFormData (IQuarkIOProcessor $processor, $boundary = '') {
		$processor = new self($processor, $boundary);
		$processor->_mime = self::MIME_FORM_DATA;
		$processor->_encoding = self::TRANSFER_ENCODING_BINARY;
		$processor->_disposition = self::DISPOSITION_FORM_DATA;

		return $processor;
	}

	/**
	 * @param IQuarkIOProcessor $processor
	 * @param string            $boundary
	 *
	 * @return QuarkMultipartIOProcessor
	 */
	public static function ForAttachment (IQuarkIOProcessor $processor, $boundary = '') {
		$processor = new self($processor, $boundary);
		$processor->_mime = self::MIME_MIXED;
		$processor->_encoding = self::TRANSFER_ENCODING_BASE64;
		$processor->_disposition = self::DISPOSITION_ATTACHMENT;

		return $processor;
	}

	/**
	 * @param IQuarkIOProcessor $processor
	 * @param string $boundary
	 */
	public function __construct (IQuarkIOProcessor $processor, $boundary = '') {
		$this->_processor = $processor;
		$this->_boundary = func_num_args() == 2 ? $boundary : 'QuarkBoundary' . Quark::GuID();

		$this->_mime = $processor instanceof self ? $processor->_mime :  self::MIME_FORM_DATA;
		$this->_encoding = $processor instanceof self ? $processor->_encoding :  self::TRANSFER_ENCODING_BINARY;
		$this->_disposition = $processor instanceof self ? $processor->_disposition :  self::DISPOSITION_FORM_DATA;
	}

	/**
	 * @return string
	 */
	public function MimeType () {
		return $this->_original ? $this->_processor->MimeType() : 'multipart/' . $this->_mime . '; boundary=' . $this->_boundary;
	}

	/**
	 * @param $data
	 * @param string $text
	 *
	 * @return mixed
	 */
	public function Encode ($data, $text = '') {
		$files = array();

		$output = is_scalar($data)
			? $data
			: QuarkObject::Normalize(new \StdClass(), (object)$data, function ($item, &$def) use (&$files) {
				if (!($def instanceof QuarkFile)) return $def;

				$def = Quark::GuID();

				$files[] = array(
					'file' => $item,
					'depth' => $def
				);

				return $def;
			});

		$out = $this->_processor->Encode($output);

		if (sizeof($files) != 0) {
			$this->_original = false;
			$output = $this->_part(
				$this->_processor->MimeType(),
				func_num_args() == 2 ? $text : $out,
				$this->_disposition == self::DISPOSITION_ATTACHMENT
			);

			foreach ($files as $file)
				$output .= $this->_part($file['depth'], $file['file']);

			$out = $output . '--' . $this->_boundary . '--';
		}
		else {
			if (func_num_args() == 1 && $this->_encoding == self::TRANSFER_ENCODING_BASE64)
				$out = base64_encode($out);
		}

		return $out;
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Decode ($raw) {
		// TODO: Implement Decode() method.
	}

	/**
	 * @param $key
	 * @param mixed $value
	 * @param bool $main
	 *
	 * @return string
	 */
	private function _part ($key, $value, $main = false) {
		$file = $value instanceof QuarkFile;
		$contents = $file ? $value->Content() : $value;

		/**
		 * Attention!
		 * Here is solution for support custom boundary by Quark: if You does not specify filename to you attachment,
		 * then PHP parser cannot parse your files
		 */

		return
			'--' . $this->_boundary . "\r\n"
			. ($main ? '' : QuarkDTO::HEADER_CONTENT_DISPOSITION . ': ' . $this->_disposition
				. ($this->_disposition == self::DISPOSITION_FORM_DATA ? '; name="' . $key . '"' : '')
				. ($file ? '; filename="' . $value->name . '"' : '')
				. "\r\n"
			)
			. QuarkDTO::HEADER_CONTENT_TYPE . ': ' . ($file ? $value->type : $this->_processor->MimeType()) . "\r\n"
			. QuarkDTO::HEADER_CONTENT_TRANSFER_ENCODING . ': ' . $this->_encoding . "\r\n"
			. "\r\n"
			. ($this->_encoding == self::TRANSFER_ENCODING_BASE64 ? base64_encode($contents) : $contents)
			. "\r\n";
	}
}

/**
 * Class QuarkJSONIOProcessor
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
	 * @param string $json
	 *
	 * @return array
	 */
	public static function PackageStack ($json) {
		return explode('}-{', str_replace('}{', '}}-{{', $json));
	}
}

/**
 * Class QuarkXMLIOProcessor
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
 * @package Quark\Tools
 */
class QuarkSource {
	private $_location = '';
	private $_type = '';
	private $_source = '';
	private $_size = 0;
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
	 * @param string $source
	 * @param array $trim
	 */
	public function __construct ($source = '', $trim = array()) {
		$this->Load($source);
		$this->_trim = func_num_args() == 2 ? $trim : self::$__trim;
	}

	/**
	 * @param $file
	 *
	 * @return QuarkSource
	 * @throws QuarkArchException
	 */
	public static function FromFile ($file) {
		$source = new self();

		if (!$source->Load($file))
			throw new QuarkArchException('There is no source file at ' . (string)$file);

		return $source;
	}

	/**
	 * @param string $file
	 * @return string
	 */
	public static function FileExtension ($file) {
		return array_reverse(explode('.', $file))[0];
	}

	/**
	 * @param $source
	 *
	 * @return bool
	 */
	public function Load ($source) {
		if (!is_file($source)) return false;

		$this->_location = $source;
		$this->_type = self::FileExtension($source);
		$this->_source = file_get_contents($source);
		$this->_size();

		return true;
	}

	/**
	 * @param $destination
	 *
	 * @return bool
	 */
	public function Save ($destination) {
		if (!is_file($destination)) return false;

		file_put_contents($destination, $this->_source);
		return true;
	}

	/**
	 * @return string
	 */
	public function Location () {
		return $this->_location;
	}

	/**
	 * @return string
	 */
	public function Type () {
		return $this->_type;
	}

	/**
	 * @return string
	 */
	public function Source () {
		return $this->_source;
	}

	/**
	 * @return float|int
	 */
	public function Size () {
		return $this->_size;
	}

	/**
	 * @param string $dim = 'k'
	 * @param int    $precision = 3
	 */
	private function _size ($dim = 'k', $precision = 3) {
		$size = strlen($this->_source);

		switch ($dim) {
			default: break;
			case 'b': break;
			case 'k': $size = $size / 1024; break;
			case 'm': $size = $size / 1024 / 1024; break;
			case 'g': $size = $size / 1024 / 1024 / 1024; break;
			case 't': $size = $size / 1024 / 1024 / 1024 / 1024; break;
		}

		if ($dim != 'b')
			$size = round($size, $precision);

		$this->_size = $size;
	}

	/**
	 * @return QuarkSource
	 */
	public function Obfuscate () {
		$this->_source = self::ObfuscateString($this->_source, $this->_trim);
		$this->_size();

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