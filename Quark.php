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
	private static $_host = '';
	private static $_webHost = '';
	private static $_events = array();
	private static $_gUID = array();

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
	 */
	public static function Run (QuarkConfig $config) {
		self::$_config = $config;

		try {
			if (PHP_SAPI != 'cli') echo QuarkService::Select($_SERVER['REQUEST_URI'])->Invoke();
			else {
				if (!isset($_SERVER['argv']) || !isset($_SERVER['argc']))
					throw new QuarkArchException('Quark CLI mode need $argv and $argc, which are missing. Check your PHP configuration.');

				if ($_SERVER['argc'] == 1) {
					$tasks = array();

					$dir = new \RecursiveDirectoryIterator(self::Host());
					$fs = new \RecursiveIteratorIterator($dir);

					foreach ($fs as $file) {
						/**
						 * @var \FilesystemIterator $file
						 */

						if ($file->isDir() || !strstr($file->getFilename(), '.php')) continue;

						$location = $file->getPathname();
						include_once $location;
						$class = self::ClassIn($location);
						if (!self::is($class, 'Quark\IQuarkScheduledTask', true)) continue;

						$_SERVER['REQUEST_URI'] = self::NormalizePath($class, false);
						$_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], 9, strlen($_SERVER['REQUEST_URI']) - 16);

						$item = QuarkService::Select($_SERVER['REQUEST_URI']);

						if ($item->Service() instanceof IQuarkScheduledTask)
							$tasks[] = new QuarkTask($item);
					}

					$work = true;

					self::Log('Start scheduled tasks. Tasks in queue: ' . sizeof($tasks));

					/**
					 * @var QuarkTask $task
					 */
					while ($work)
						foreach ($tasks as $task)
							$work = $task->Launch();
				}
				else {
					if ($_SERVER['argc'] <= 1 || strlen(trim($_SERVER['argv'][1])) == 0)
						$_SERVER['argv'][1] = '/';

					$_SERVER['REQUEST_URI'] = $_SERVER['argv'][1][0] != '/' ? '/' . $_SERVER['argv'][1] : $_SERVER['argv'][1];

					if ($_SERVER['argc'] < 3)
						echo QuarkService::Select($_SERVER['REQUEST_URI'])->Invoke();
					elseif ($_SERVER['argv'][2] == QuarkTask::PREDEFINED) {
						$class = /*'Quark\\Scenarios\\' .*/ $_SERVER['argv'][1];
						$task = new $class();

						if (!($task instanceof IQuarkTask))
							throw new QuarkArchException('Class ' . $class . ' is not a IQuarkTask');

						echo (new QuarkService($task))->Invoke();
					}
					else throw new QuarkArchException("Unresolved condition of task running.\r\n"
						. ' ARGC:[' . $_SERVER['argc'] . "]\r\n"
						. ' ARGV:' . print_r($_SERVER['argv'], true) . "\r\n"
					);
				}
			}
		}
		catch (QuarkArchException $e) {
			self::Log($e->message, $e->lvl);
		}
		catch (QuarkConnectionException $e) {
			self::Log($e->message, $e->lvl);
		}
		catch (QuarkHTTPException $e) {
			self::HTTPStatus($config->DefaultNotFoundStatus());
			self::Log('[' . $_SERVER['REQUEST_URI'] . '] ' . $e->message , $e->lvl);
		}
		catch (\Exception $e) {
			self::Log('Common exception: ' . $e->getMessage() . "\r\n at " . $e->getFile() . ':' . $e->getLine(), self::LOG_FATAL);
		}
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
				self::Log('Class "' . $class . '" does not exists', self::LOG_WARN);

			return false;
		}

		$faces = class_implements($class);

		foreach ($interface as $face)
			if (in_array($face, $faces, true)) return true;

		return false;
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
		return is_string($file) ? '\\' . str_replace('/', '\\', str_replace(self::Host(), '', str_replace('.php', '', self::NormalizePath($file, false)))) : '';
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
	 * @return string
	 *
	 * @throws QuarkArchException
	 */
	public static function Host () {
		if (self::$_host == '') {
			if (PHP_SAPI == 'cli')
				return self::$_host = self::NormalizePath(dirname($_SERVER['PHP_SELF']));

			if (!isset($_SERVER['DOCUMENT_ROOT']))
				throw new QuarkArchException('Cannot determine document root. Please check Your PHP and web server configuration');

			self::$_host = self::NormalizePath($_SERVER['DOCUMENT_ROOT']);
		}

		return self::$_host;
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
	 *
	 * @return string
	 */
	public static function URLOf ($path) {
		$path = self::NormalizePath($path, false);

		return self::WebHost() . (strlen($path) != 0 && $path[0] == '/' ? substr($path, 1) : $path);
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
	 * @param $path
	 * @param $endSlash
	 * @return string
	 */
	public static function NormalizePath ($path, $endSlash = true) {
		return is_scalar($path)
			? trim(preg_replace('#(/+)#', '/', str_replace('\\', '/', $path))
				. ($endSlash && (strlen($path) != 0 && $path[strlen($path) - 1] != '/') ? '/' : ''))
			: ($path instanceof QuarkFile ? $path->location : '');
	}

	/**
	 * @param $path
	 * @return string
	 */
	public static function SanitizePath ($path) {
		return self::NormalizePath(str_replace('./', '/', str_replace('../', '/', $path)), false);
	}

	/**
	 * @param string $file
	 * @return string
	 */
	public static function FileExtension ($file) {
		return array_reverse(explode('.', $file))[0];
	}

	/**
	 * @param array $source
	 *
	 * Algorithm got from http://php.net/manual/en/function.getallheaders.php#84262 and adopted for Quark infrastructure
	 *
	 * @return array
	 */
	public static function Headers ($source = []) {
		$output = array();

		if (func_num_args() == 0 || !is_array($source))
			$source = $_SERVER;

		foreach ($source as $name => $value) {
			if (substr($name, 0, 5) == 'HTTP_')
				$output[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;

			if (substr($name, 0, 8) == 'CONTENT_')
				$output[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $name))))] = $value;
		}

		return $output;
	}

	/**
	 * @param $url
	 */
	public static function Redirect ($url) {
		header('Location: ' . $url);
		exit();
	}

	/**
	 * @param string $status
	 *
	 * @return string
	 */
	public static function HTTPStatus ($status = QuarkDTO::STATUS_200_OK) {
		header('HTTP/1.0 ' . $status);
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
		$hash = sha1(rand(1, 1000) . date('Y-m-d H:i:s') . rand(1000, 1000000) . $salt);

		if (in_array($hash, self::$_gUID)) return self::GuID($salt);

		self::$_gUID[] = $hash;
		return $hash;
	}

	/**
	 * @param string $start
	 * @param string $end
	 * @param string $format
	 *
	 * @return bool|int|string
	 */
	public static function DateInterval ($start, $end, $format = '') {
		if (!QuarkField::DateTime($start)) return false;
		if (!QuarkField::DateTime($end)) return false;

		$start = strtotime($start);
		$end = strtotime($end);

		$duration = $end - $start;

		return func_num_args() == 2 ? $duration : gmdate($duration, $format);
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

			if (is_file($file))
				include_once $file;
		});

		return true;
	}

	/**
	 * @param $message
	 * @param string $lvl
	 * @param string $domain = 'application'
	 * @return int|bool
	 */
	public static function Log ($message, $lvl = self::LOG_INFO, $domain = 'application') {
		$logs = self::NormalizePath(self::Host() . '/' . self::Config()->Location(QuarkConfig::LOGS) . '/');

		if (!is_dir($logs)) mkdir($logs);

		return file_put_contents(
			$logs . $domain . '.log',
			'[' . $lvl . '] ' . date(self::Config()->Culture()->DateTimeFormat()) . ' ' . $message . "\r\n",
			FILE_APPEND | LOCK_EX
		);
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
	 * @var array
	 */
	private $_extensions = array();

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
	 * @param                    $name
	 * @param IQuarkDataProvider $provider
	 * @param QuarkURI   $uri
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
		}
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

		return $this->$direction;
	}
}

/**
 * Class QuarkService
 *
 * @package Quark
 */
class QuarkService {
	const ORIGIN_ANY = '*';

	/**
	 * @var IQuarkService|IQuarkTask|null
	 */
	private $_service = null;

	/**
	 * @param IQuarkService $service
	 */
	public function __construct (IQuarkService $service) {
		$this->_service = $service;
	}

	/**
	 * @return IQuarkService|null
	 */
	public function Service () {
		return $this->_service;
	}

	/**
	 * @return string
	 * @throws QuarkArchException
	 */
	public function Invoke () {
		if (PHP_SAPI == 'cli' && $this->_service instanceof IQuarkTask) return $this->_service->Action();

		$request = new QuarkDTO();
		$request->Processor(Quark::Config()->Processor(QuarkConfig::REQUEST));
		$response = new QuarkDTO();
		$response->Processor(Quark::Config()->Processor(QuarkConfig::RESPONSE));

		if ($this->_service instanceof IQuarkServiceWithCustomProcessor) {
			$request->Processor($this->_service->Processor());
			$response->Processor($this->_service->Processor());
			$response->Header(QuarkDTO::HEADER_CONTENT_TYPE, $response->Processor()->MimeType());
		}

		if ($this->_service instanceof IQuarkServiceWithCustomRequestProcessor)
			$response->Processor($this->_service->RequestProcessor());

		if ($this->_service instanceof IQuarkServiceWithCustomResponseProcessor) {
			$response->Processor($this->_service->ResponseProcessor());
			$response->Header(QuarkDTO::HEADER_CONTENT_TYPE, $response->Processor()->MimeType());
		}

		if ($this->_service instanceof IQuarkServiceWithAccessControl)
			$response->Header(QuarkDTO::HEADER_ALLOW_ORIGIN, $this->_service->AllowOrigin());

		ob_start();

		$ok = true;
		$output = null;

		$type = $request->Processor()->MimeType();
		$body = file_get_contents('php://input');

		$request->Method($_SERVER['REQUEST_METHOD']);
		$request->URI(QuarkURI::FromURI($_SERVER['REQUEST_URI']));
		$request->Headers(Quark::Headers());
		$request->AttachData($request->Processor()->Decode(strlen(trim($body)) != 0 ? $body : (isset($_POST[$type]) ? $_POST[$type] : '')));
		$request->AttachData((object)($_GET + $_POST));

		if (isset($_POST[$type]))
			unset($_POST[$type]);

		$files = QuarkFile::FromFiles($_FILES);

		$post = Quark::Normalize(new \StdClass(), $request->Data(), function ($item) use ($files) {
			foreach ($files as $key => $value)
				if ($key == $item) return $value;

			return $item;
		});

		$request->AttachData($post);
		$request->AttachData((object)$files);

		$session = new QuarkSession();
		$method = $this->_service instanceof IQuarkAnyService
			? 'Any'
			: ucfirst(strtolower($_SERVER['REQUEST_METHOD']));

		if ($this->_service instanceof IQuarkAuthorizableService || $this->_service instanceof IQuarkAuthorizableLiteService) {
			$session = QuarkSession::Get($this->_service->AuthorizationProvider($request));
			$session->Initialize($request);

			$response->AttachData($session->Trail($response));

			if ($this->_service instanceof IQuarkAuthorizableService) {
				$criteria = $this->_service->AuthorizationCriteria($request, $session);

				if ($criteria !== true) {
					$ok = false;
					$output = $this->_service->AuthorizationFailed($request, $criteria);
				}
				else {
					if (Quark::is($this->_service, 'Quark\IQuarkSigned' . $method . 'Service')) {
						$sign = $session->Signature();

						if ($sign == '' || $request->Signature() != $sign) {
							$action = 'SignatureCheckFailedOn' . $method;

							$ok = false;
							$output = $this->_service->$action($request);
						}
					}
				}
			}
		}

		if ($ok && Quark::is($this->_service, $a = 'Quark\IQuark' . $method . 'Service'))
			$output = $this->_service->$method($request, $session);

		if ($output instanceof QuarkDTO) $response = $output;
		else $response->AttachData($output, true);

		if (!headers_sent()) {
			$status = $response->Status();

			if ($status != QuarkDTO::STATUS_200_OK)
				header($_SERVER['SERVER_PROTOCOL'] . ' ' . $status);

			$headers = $response->Headers();
			$cookies = $response->Cookies();

			foreach ($headers as $key => $value)
				header($key . ': ' . $value);

			foreach ($cookies as $cookie)
				header(QuarkDTO::HEADER_SET_COOKIE . ': ' . $a = $cookie->Serialize(), false);
		}

		echo $response->Processor()->Encode($response->Data());

		return ob_get_clean();
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
	 * @return QuarkService
	 *
	 * @throws QuarkArchException
	 * @throws QuarkHTTPException
	 */
	public static function Select ($uri) {
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

		if (!is_file($path))
			throw new QuarkHTTPException(404, 'Unknown service file ' . $path);

		include_once $path;

		$class = str_replace('/', '\\', '/Services/' . $service . 'Service');

		if (!class_exists($class))
			throw new QuarkArchException('Unknown service class ' . $class);

		$bundle = new $class();
		$bundle->route = $route;

		if (!($bundle instanceof IQuarkService))
			throw new QuarkArchException('Class ' . $class . ' is not an IQuarkService');

		return new QuarkService($bundle);
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
 * Interface IQuarkAuthorizableService
 *
 * @package Quark
 */
interface IQuarkAuthorizableService {
	/**
	 * @param QuarkDTO $request
	 *
	 * @return string
	 */
	public function AuthorizationProvider(QuarkDTO $request);

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
 * Interface IQuarkStrongService
 *
 * @package Quark
 */
interface IQuarkStrongService {
	/**
	 * @return array
	 */
	public function InputFilter();
}

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
	 * @var QuarkService $_service
	 */
	private $_service = null;
	private $_launched = '';

	/**
	 * @param QuarkService $service
	 */
	public function __construct (QuarkService $service) {
		$this->_service = $service;
		$this->_launched = gmdate('Y-m-d H:i:s');
	}

	/**
	 * @return bool
	 */
	public function Launch () {
		/**
		 * @var IQuarkTask|IQuarkScheduledTask $service
		 */
		$service = $this->_service->Service();

		if (!$service->LaunchCriteria($this->_launched)) return true;

		$out = $this->_service->Invoke();
		$this->_launched = gmdate('Y-m-d H:i:s');

		if (is_bool($out)) return $out;

		echo $out;
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
	 * @return mixed
	 */
	public function Action();
}

/**
 * Interface IQuarkScheduledTask
 *
 * @package Quark
 */
interface IQuarkScheduledTask {
	/**
	 * @param string $previous
	 *
	 * @return bool
	 */
	public function LaunchCriteria($previous);
}

/**
 * Class QuarkView
 *
 * @package Quark
 */
class QuarkView {
	/**
	 * @var IQuarkViewModel|IQuarkViewModelWithResources|IQuarkViewModelWithCachedResources|null
	 */
	private $_view = null;
	private $_child = null;
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
	}

	/**
	 * @param $key
	 *
	 * @return mixed
	 */
	public function __get ($key) {
		return isset($this->_view->$key) ? $this->_view->$key : $this->_null;
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
		return isset($this->_view->$key);
	}

	/**
	 * @param $method
	 * @param $args
	 *
	 * @return mixed
	 */
	public function __call ($method, $args) {
		return call_user_func_array(array($this->_view, $method), $args);
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
				$out .= $resource->HTML();
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
					$res->Obfuscate(true);

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
	 * @param bool $field
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
	public function Layout (IQuarkViewModel $view, $vars = [], $resources = []) {
		$layout = new QuarkView($view, $vars, $resources);
		$layout->View($this->Compile());
		$layout->Child($this->_view);

		return $layout;
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
			$this->_vars = Quark::Normalize(new \StdClass(), (object)$params);

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
	 * @return string
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

	/**
	 * @param string $location
	 * @param IQuarkViewResourceType $type
	 */
	public function __construct ($location, IQuarkViewResourceType $type) {
		$this->_location = $location;
		$this->_type = $type;
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
		return true;
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
	/**
	 * @var QuarkModel $_container
	 */
	private $_container;

	/**
	 * @param QuarkModel $container
	 *
	 * @return QuarkModel
	 */
	public function __container (QuarkModel $container = null) {
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
class QuarkModel {
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

		if (method_exists($this->_model, '__container'))
			$this->_model->__container($this);
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
	 * @param $value
	 * @param $backbone
	 *
	 * @return QuarkModel|null
	 */
	public static function Build ($value, $backbone) {
		return $value === null && $backbone instanceof IQuarkNullableModel ? null : new QuarkModel($backbone);
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
	 * @param IQuarkModel $model
	 * @param array       $fields
	 *
	 * @return IQuarkModel
	 */
	private static function _normalize (IQuarkModel $model, $fields = []) {
		if (func_num_args() == 1 || (!is_array($fields) && !is_object($fields)))
			$fields = $model->Fields();

		$output = $model;

		if (!is_array($fields) && !is_object($fields)) return $output;

		foreach ($fields as $key => $value) {
			if (isset($model->$key)) {
				if (is_scalar($value) && is_scalar($model->$key))
					settype($model->$key, gettype($value));

				$output->$key = $model->$key;
			}
			else $output->$key = $value instanceof IQuarkModel
				? QuarkModel::Build(empty($model->$key) ? null : $model->$key, $value)
				: $value;
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
			if (!Quark::PropertyExists($fields, $key) && $model instanceof IQuarkStrongModel) continue;

			$property = Quark::Property($fields, $key, $value);

			if ($property instanceof QuarkCollection) {
				$class = get_class($property->Type());

				$model->$key = $property->PopulateWith($value, function ($item) use ($class) {
					return self::_link(new $class(), $item);
				});
			}
			else $model->$key = self::_link($property, $value);
		}

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
			? $property->Link(Quark::isAssociative($value) ? (object)$value : $value)
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
			if (!Quark::PropertyExists($fields, $key) && $model instanceof IQuarkStrongModel) continue;

			if ($value instanceof QuarkCollection) {
				$output->$key = $value->Collection(function ($item) {
					return self::_unlink($item);
				});
			}
			else $output->$key = self::_unlink($value);
		}

		return $output;
	}

	/**
	 * @param $value
	 *
	 * @return mixed|IQuarkModel
	 */
	private static function _unlink ($value) {
		if ($value instanceof QuarkModel)
			$value = $value->Model();

		return $value instanceof IQuarkLinkedModel ? $value->Unlink() : $value;
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return bool
	 */
	private static function _validate (IQuarkModel $model) {
		if ($model instanceof IQuarkModelWithBeforeValidate && $model->BeforeValidate() === false) return false;

		return QuarkField::Rules($model->Rules());
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

		if ($model instanceof IQuarkModelWithBeforeExtract)
			$model->BeforeExtract();

		foreach ($model as $key => $value) {
			$property = Quark::Property($fields, $key, null);

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
				$buffer->$field = Quark::Property($output, $field, null);

			if ($weak && !isset($fields[$field])) continue;
			else {
				if (is_string($rule) && property_exists($output, $rule))
					$buffer->$rule = Quark::Property($output, $rule, null);
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

		$ok = Quark::is($model, 'Quark\IQuarkModelWith' . $hook)
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
		return self::_provider($model)->Count($model, $criteria, $limit, $skip, $options);
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
		if ($nullable && $key === null) return true;

		return $key instanceof QuarkModel ? $key->Validate() : false;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $nullable
	 * @return bool
	 */
	public static function Type ($key, $value, $nullable = false) {
		if ($nullable && $key === null) return true;

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
		if ($nullable && $key === null) return true;

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
		if ($nullable && $key === null) return true;

		return $sever ? $key !== $value : $key != $value;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $nullable
	 * @return bool
	 */
	public static function Lt ($key, $value, $nullable = false) {
		if ($nullable && $key === null) return true;

		return $key < $value;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $nullable
	 * @return bool
	 */
	public static function Gt ($key, $value, $nullable = false) {
		if ($nullable && $key === null) return true;

		return $key > $value;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $nullable
	 * @return bool
	 */
	public static function Lte ($key, $value, $nullable = false) {
		if ($nullable && $key === null) return true;

		return $key <= $value;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $nullable
	 * @return bool
	 */
	public static function Gte ($key, $value, $nullable = false) {
		if ($nullable && $key === null) return true;

		return $key >= $value;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $nullable
	 * @return bool
	 */
	public static function MinLengthInclusive ($key, $value, $nullable = false) {
		if ($nullable && $key === null) return true;

		return is_array($key) ? sizeof($key) >= $value : strlen((string)$key) >= $value;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $nullable
	 * @return bool
	 */
	public static function MinLength ($key, $value, $nullable = false) {
		if ($nullable && $key === null) return true;

		return is_array($key) ? sizeof($key) > $value : strlen((string)$key) > $value;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $nullable
	 * @return bool
	 */
	public static function Length ($key, $value, $nullable = false) {
		if ($nullable && $key === null) return true;

		return is_array($key) ? sizeof($key) == $value : strlen((string)$key) == $value;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $nullable
	 * @return bool
	 */
	public static function MaxLength ($key, $value, $nullable = false) {
		if ($nullable && $key === null) return true;

		return is_array($key) ? sizeof($key) < $value : strlen((string)$key) < $value;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $nullable
	 * @return bool
	 */
	public static function MaxLengthInclusive ($key, $value, $nullable = false) {
		if ($nullable && $key === null) return true;

		return is_array($key) ? sizeof($key) <= $value : strlen((string)$key) <= $value;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $nullable
	 * @return bool|int
	 */
	public static function Match ($key, $value, $nullable = false) {
		if ($nullable && $key === null) return true;

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
		if ($nullable && $key === null) return true;

		return is_array($values) && in_array($key, $values);
	}

	/**
	 * @param string $type
	 * @param mixed $key
	 * @param bool $nullable
	 * @param null $culture
	 * @return bool
	 */
	private static function _dateTime ($type, $key, $nullable = false, $culture = null) {
		if ($nullable && $key === null) return true;

		if ($culture == null)
			$culture = Quark::Config()->Culture();

		$format = $type . 'Format';

		/**
		 * code snippet from http://php.net/manual/ru/function.checkdate.php#113205
		 */
		$date = \DateTime::createFromFormat($culture->$format(), $key);
		$date->setTimezone(new \DateTimeZone('UTC'));

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
		if ($nullable && $key === null) return true;
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
		if ($nullable && $key === null) return true;
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
		if ($nullable && $key === null) return true;

		return in_array($key, $values);
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
		if ($nullable && $key === null) return true;

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
		if (!is_array($rules)) return $rules === null ? true : (bool)$rules;

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
	 * @param string $language self::LANGUAGE_ANY
	 * @param string $default self::LANGUAGE_ANY
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
	 * @param IQuarkAuthorizableModel     $model
	 */
	public function __construct ($name = '', IQuarkAuthorizationProvider $provider = null, IQuarkAuthorizableModel $model = null) {
		$this->_name = $name;
		$this->_provider = $provider;
		$this->_model = $model;
	}

	/**
	 * @param string $name
	 * @param IQuarkAuthorizationProvider $provider
	 * @param IQuarkAuthorizableModel     $user
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
	 * @param int      $lifetime
	 *
	 * @return mixed
	 */
	public function Initialize (QuarkDTO $request, $lifetime = 0) {
		if (!$this->_model || !$this->_provider) return null;

		$request = $this->_provider->Initialize($this->_name, $request, $lifetime);
		if (!$request && $request !== null) return null;

		if (is_array($request))
			$request = Quark::Normalize(new \StdClass(), $request);

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
	 * @var IQuarkTransportProviderClient|IQuarkTransportProviderServer $_transport
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
			$out = fwrite($socket, $data) !== false;

			stream_set_timeout($socket, $this->_timeout);

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
			return $stream
				? stream_get_contents($socket, $max, $offset)
				: ($max == -1 ? fgets($socket) : fgets($this->_socket, $max));
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
	private $_send;

	/**
	 * @param string                  $uri
	 * @param IQuarkTransportProviderClient $transport
	 * @param QuarkCertificate        $certificate
	 * @param int                     $timeout = 30
	 */
	public function __construct ($uri = '', IQuarkTransportProviderClient $transport = null, QuarkCertificate $certificate = null, $timeout = 30) {
		$this->URI(QuarkURI::FromURI($uri));
		$this->Transport($transport);
		$this->Certificate($certificate);
		$this->Timeout($timeout);
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

		$this->_socket = stream_socket_client(
			$this->_uri->Socket($this->ip),
			$this->_errorNumber,
			$this->_errorString,
			$this->_timeout,
			STREAM_CLIENT_CONNECT,
			$stream
		);

		if (!$this->_socket)
			return self::_err($this->_errorString, $this->_errorNumber);

		$this->_connected = true;

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
	 * @return mixed
	 * @throws QuarkArchException
	 */
	public function Action () {
		if ($this->_transport != null)
			return $this->_transport->Client($this);

		throw new QuarkArchException('QuarkClient: Transport is null');
	}

	/**
	 * @param $socket
	 * @param $address
	 *
	 * @return QuarkClient
	 */
	public static function ForServer ($socket, $address) {
		$client = new self();

		$client->Socket($socket);
		$client->URI(QuarkURI::FromURI($address));

		return $client;
	}

	/**
	 * @param bool $connected
	 *
	 * @return bool
	 */
	public function Connected ($connected = true) {
		if (func_num_args() != 0)
			$this->_connected = $connected;

		return $this->_connected;
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

	/**
	 * @var bool $_run
	 */
	private $_run = false;

	/**
	 * @param string                        $uri
	 * @param IQuarkTransportProviderServer $transport
	 * @param QuarkCertificate              $certificate
	 * @param int                           $timeout
	 */
	public function __construct ($uri = '', IQuarkTransportProviderServer $transport = null, QuarkCertificate $certificate = null, $timeout = 30) {
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

		$this->_run = true;

		return true;
	}

	/**
	 *
	 */
	public function Listen () {
		/**
		 * @var QuarkClient[] $clients
		 */
		$clients = array();

		$read = array($this->_socket);
		$write = array();
		$except = array();

		while ($this->_run) {
			if (stream_select($read, $write, $except, $this->_timeout) === false) continue;

			if (in_array($this->_socket, $read)) {
				$socket = stream_socket_accept($this->_socket, $this->_timeout, $address);
				$client = QuarkClient::ForServer($socket, $address);
				$accept = $this->_transport->OnConnect($client, $clients + array($client));

				if ($accept || $accept === null) {
					$clients[] = $client;
					unset($read[array_search($this->_socket, $read, true)]);
				}
			}

			$read = array();

			foreach ($clients as $key => &$client) {
				$data = $client->Receive(QuarkClient::MODE_BUCKET);

				if (feof($client->Socket())) {
					$this->_transport->OnClose($client, $clients);
					$client->Close();

					unset($clients[$key]);
					continue;
				}

				if ($data !== false)
					$this->_transport->OnData($client, $clients, $data);

				$read[] = $client->Socket();
			}

			unset($key, $client);

			$read[] = $this->_socket;
		}
	}

	/**
	 * @return QuarkServer
	 */
	public function Stop () {
		$this->_run = false;

		return $this;
	}

	/**
	 * @return mixed
	 * @throws QuarkArchException
	 */
	public function Action () {
		if ($this->_transport != null)
			return $this->_transport->Server($this);

		throw new QuarkArchException('QuarkServer: Transport is null');
	}
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
	 * @param string $uri
	 * @param bool $local
	 *
	 * @return QuarkURI|null
	 */
	public static function FromURI ($uri, $local = true) {
		if ($uri instanceof QuarkURI) return $uri;
		if (!is_string($uri)) return null;

		$url = parse_url($uri);

		if ($url === false) return null;

		$out = new self();

		foreach ($url as $key => $value)
			$out->$key = $value;

		if ($local) {
			if (!isset($url['scheme'])) $out->scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : '';
			if (!isset($url['host'])) $out->host = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
		}

		return $out;
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

		if (func_num_args() == 2)
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

	const STATUS_200_OK = '200 OK';
	const STATUS_401_UNAUTHORIZED = '401 Unauthorized';
	const STATUS_404_NOT_FOUND = '404 Not Found';

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

	private function _serialize ($all = true, callable $head) {
		$query = '';

		$this->_processor = new QuarkMultipartIOProcessor($this->_processor, $this->_boundary);
		$data = $this->_processor->Encode($this->Data(), $this->_textData);

		if ($all) {
			if ($this->_uri != null) $query .= $head();

			$query .= self::HEADER_CONTENT_LENGTH. ': ' . strlen($data) . "\r\n";

			if (sizeof($this->_cookies) != 0)
				$query .= self::HEADER_COOKIE . ': ' . QuarkCookie::SerializeCookies($this->_cookies) . "\r\n";

			$this->_headers[self::HEADER_CONTENT_TYPE] = $this->_processor->MimeType() . '; charset=utf-8';

			foreach ($this->_headers as $key => $value)
				$query .= $key . ': ' . $value . "\r\n";

			$query .= "\r\n";
		}

		return $this->_raw = $query . $data;
	}

	/**
	 * @param bool $all
	 *
	 * @return string
	 */
	public function Serialize ($all = true) {
		return $this->_serialize($all, function () {
			return $this->_method . ' ' . $this->_uri->Query() . ' HTTP/1.0' . "\r\n"
				. self::HEADER_HOST . ': ' . $this->_uri->host . "\r\n";
		});
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
		$this->AttachData($this->_processor->Decode($http[3]));

		return $this;
	}

	/**
	 * @param bool $all
	 *
	 * @return string
	 */
	public function SerializeResponse ($all = true) {
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

		if (preg_match_all('#^(.*) (.*) HTTP\/(.*)\n(.*)\n\s\n(.*)$#Uis', $raw, $found, PREG_SET_ORDER) == 0) return null;

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
	 * @param int    $code = 0
	 * @param string $text = 'OK'
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
		if (func_num_args() == 1 && is_array($headers))
			$this->_headers = $headers;

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
	public function AttachData ($data = []) {
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
			$this->_uri = $data->URI();
		}
		else {
			if (is_string($this->_data)) {
				if (is_string($data)) $this->_data .= $data;
				else {
					$this->_textData = $this->_data;
					$this->_data = Quark::Normalize($this->_data, $data);
				}
			}
			else {
				if (is_string($data)) $this->_textData .= $data;
				else {
					$this->_data = Quark::Normalize($this->_data, $data);
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

interface IQuarkTransportProvider {
	/**
	 * @param QuarkURI         $uri
	 * @param QuarkCertificate $certificate
	 *
	 * @return mixed
	 */
	public function Setup(QuarkURI $uri, QuarkCertificate $certificate = null);
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

	/**
	 * @param QuarkClient $client
	 * @param QuarkClient[] $clients
	 *
	 * @return bool
	 */
	public function OnConnect($client, $clients);

	/**
	 * @param QuarkClient $client
	 * @param QuarkClient[] $clients
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function OnData($client, $clients, $data);

	/**
	 * @param QuarkClient $client
	 * @param QuarkClient[] $clients
	 *
	 * @return mixed
	 */
	public function OnClose($client, $clients);
}

/**
 * Class QuarkHTTPTransport
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
	 * @param QuarkServer $server
	 *
	 * @return mixed
	 */
	public function Server (QuarkServer $server) {
		// TODO: Implement Server() method.
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
	 * @param $header
	 *
	 * @return QuarkCookie|null
	 */
	public static function FromCookie ($header) {
		if (preg_match_all('#\;#Uis', $header) != 0) return null;

		$cookie = explode('=', $header);

		return new QuarkCookie($cookie[0], $cookie[1]);
	}

	/**
	 * @param $header
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
		if (func_num_args() == 1)
			$this->Load($location);
	}

	/**
	 * @param string $location
	 *
	 * @return string
	 */
	public function Location ($location = '') {
		if (func_num_args() == 1) {
			$this->location = Quark::NormalizePath($location, false);
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
		return file_exists($this->location);
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

		if (memory_get_usage() <= Quark::Config()->Alloc() * 1024 * 1024)
			$this->_content = file_get_contents($this->location);

		return $this;
	}

	/**
	 * @return bool
	 */
	public function Save () {
		return file_put_contents($this->location, $this->_content) != 0;
	}

	/**
	 * @param bool $full
	 *
	 * @return string
	 */
	public function WebLocation ($full = true) {
		return Quark::WebHost($full) . Quark::SanitizePath(str_replace(Quark::Host(), '', $this->location));
	}

	/**
	 * @param string $content
	 *
	 * @return string
	 */
	public function Content ($content = '') {
		if (func_num_args() == 1)
			$this->_content = $content;

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
		try {
			return new QuarkModel(new QuarkFile($raw));
		}
		catch (QuarkArchException $e) {
			Quark::Log($e->message, $e->lvl);
			return null;
		}
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

			if ($simple) $output[$name] = new QuarkModel(new QuarkFile, $buffer);
			else array_walk_recursive($output, function (&$item) use ($buffer) {
				$item = new QuarkModel(new QuarkFile, $buffer);
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
			: Quark::Normalize(new \StdClass(), (object)$data, function ($item, &$def) use (&$files) {
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
			$xml = Quark::Normalize($xml, $data);
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
			if (in_array($key, self::$_allowed)) $data[$key] = $value;

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
			: Quark::ClassOf($model);

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

	private $_trim = array(
		'.',',',';','\'','?',':',
		'(',')','{','}','[',']',
		'-','+','*','/',
		'>','<','>=','<=','!=','==',
		'=','=>','->',
		'&&', '||'
	);

	/**
	 * @param string $source
	 */
	public function __construct ($source = '') {
		$this->Load($source);
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
	 * @param $source
	 *
	 * @return bool
	 */
	public function Load ($source) {
		if (!is_file($source)) return false;

		$this->_location = $source;
		$this->_type = Quark::FileExtension($source);
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
	 * @param bool $css
	 *
	 * @return QuarkSource
	 */
	public function Obfuscate ($css = false) {
		$slash = ':\\\\' . Quark::GuID() . '\\\\';

		$this->_source = str_replace('://', $slash, $this->_source);
		$this->_source = preg_replace('#\/\/(.*)\\n#Uis', '', $this->_source);
		$this->_source = str_replace($slash, '://', $this->_source);
		$this->_source = preg_replace('#\/\*(.*)\*\/#Uis', '', $this->_source);
		$this->_source = str_replace("\r\n", '', $this->_source);
		$this->_source = preg_replace('/\s+/', ' ', $this->_source);
		$this->_source = trim(str_replace('<?phpn', '<?php n', $this->_source));

		foreach ($this->_trim as $rule) {
			$this->_source = str_replace(' ' . $rule . ' ', $rule, $this->_source);

			if (!$css)
				$this->_source = str_replace(' ' . $rule, $rule, $this->_source);

			$this->_source = str_replace($rule . ' ', $rule, $this->_source);
		}

		$this->_size();

		return $this;
	}
}