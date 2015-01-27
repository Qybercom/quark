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
			if (PHP_SAPI != 'cli') echo QuarkService::Select()->Invoke();
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

						$item = QuarkService::Select();

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

					echo QuarkService::Select()->Invoke();
				}
			}
		}
		catch (QuarkArchException $e) {
			self::Log($e->message, $e->lvl);
		}
		catch (QuarkHTTPException $e) {
			self::Log($e->message, $e->lvl);
		}
		catch (QuarkConnectionException $e) {
			self::Log($e->message, $e->lvl);
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
	 * @return array
	 */
	public static function Implementations ($interface) {
		$output = array();
		$classes = get_declared_classes();

		foreach ($classes as $class)
			if (self::is($class, $interface)) $output[] = $class;

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
			if (!isset($_SERVER['SERVER_NAME']))
				throw new QuarkArchException('Could not determine WebHost because $_SERVER[\'SERVER_NAME\'] is not specified');

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

		return func_num_args() == 2 ? $duration : date($duration, $format);
	}

	/**
	 * @param $message
	 * @param string $lvl
	 * @param string $domain
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

spl_autoload_register(function ($class) {
	$file = Quark::NormalizePath(__DIR__ . '/' . substr($class, 6) . '.php', false);

	if (is_file($file))
		include_once $file;
});

spl_autoload_register(function ($class) {
	$file = Quark::NormalizePath(Quark::Host() . '/' . $class . '.php', false);

	if (is_file($file))
		include_once $file;
});

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
	 * @var string
	 */
	private $_mode = Quark::MODE_DEV;

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
	 * @param string $mode
	 * @return null|string
	 */
	public function Mode ($mode = null) {
		return $this->_mode = ($mode === null) ? $this->_mode : $mode;
	}

	/**
	 * @param                    $name
	 * @param IQuarkDataProvider $provider
	 * @param QuarkCredentials   $credentials
	 */
	public function DataProvider ($name, IQuarkDataProvider $provider, QuarkCredentials $credentials) {
		try {
			QuarkModel::Source($name, $provider)->Connect($credentials);
		}
		catch (QuarkConnectionException $e) {
			Quark::Log('Unable to connect \'' . $name . '\'', Quark::LOG_FATAL);
			Quark::Dispatch(Quark::EVENT_CONNECTION_EXCEPTION, array(
				'name' => $name,
				'credentials' => $credentials
			));
		}
	}

	/**
	 * @param IQuarkExtension $extension
	 * @param IQuarkExtensionConfig $config
	 *
	 * @return IQuarkExtension
	 */
	public function Extension (IQuarkExtension $extension, IQuarkExtensionConfig $config = null) {
		$class = get_class($extension);

		try {
			if ($extension == null)
				throw new QuarkArchException(' Provided extension in QuarkConfig is null');

			foreach ($this->_extensions as $item)
				if (get_class($item) == $class) return $item;

			if ($extension instanceof IQuarkConfigurableExtension)
				$extension->Init($config);

			$this->_extensions[] = $extension;
		}
		catch (QuarkConnectionException $e) {
			Quark::Log('Extension connection failure in \'' . $class . '\' ' . $e->message, Quark::LOG_FATAL);
			Quark::Dispatch(Quark::EVENT_CONNECTION_EXCEPTION, array('extension' => $extension));
		}
		catch (QuarkArchException $e) {
			Quark::Log('Extension architecture failure in \'' . $class . '\' ' . $e->message, Quark::LOG_FATAL);
			Quark::Dispatch(Quark::EVENT_ARCH_EXCEPTION, array('extension' => $extension));
		}

		return $extension;
	}

	/**
	 * @param                             $name
	 * @param IQuarkAuthorizationProvider $provider
	 * @param IQuarkAuthorizableModel     $user
	 */
	public function AuthorizationProvider ($name, IQuarkAuthorizationProvider $provider, IQuarkAuthorizableModel $user) {
		try {
			QuarkSession::Init($name, $provider, $user);
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
	 * @var IQuarkService|null
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

		$request->Headers(Quark::Headers());

		$head = (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '')
			. (isset($_SERVER['REQUEST_URI']) ? ' ' . $_SERVER['REQUEST_URI'] : '')
			. (isset($_SERVER['SERVER_PROTOCOL']) ? ' ' . $_SERVER['SERVER_PROTOCOL'] : '')
			. "\r\nHost: " . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . "\r\n\r\n";

		if (substr($request->Header(QuarkDTO::HEADER_CONTENT_TYPE), 0, 19) != 'multipart/form-data') {
			$request->PopulateFrom($head . file_get_contents('php://input'));
			$request->AttachData((object)($_GET + $_POST));
		}
		else {
			$key = $request->Processor()->MimeType();

			if (isset($_POST[$key]))
				$request->PopulateFrom($head . $_POST[$key]);

			$request->AttachData((object)($_GET + $_POST + QuarkFile::FromFiles($_FILES)));

			if (sizeof($_FILES) != 0)
				$request->Data(Quark::Normalize(new \StdClass(), (object)$request->Data(), function ($item) {
					return is_scalar($item) && isset($_FILES[$item])
						? QuarkFile::From($_FILES[$item])
						: $item;
				}));
		}

		if ($this->_service instanceof IQuarkStrongService)
			$request->Data(Quark::Normalize($request->Data(), (object)$this->_service->InputFilter()));

		ob_start();

		$ok = true;
		$output = null;

		if ($this->_service instanceof IQuarkTask) $output = $this->_service->Action($request);
		else {
			$session = new QuarkSession();
			$method = $this->_service instanceof IQuarkAnyService
				? 'Any'
				: ucfirst(strtolower($_SERVER['REQUEST_METHOD']));

			if ($this->_service instanceof IQuarkAuthorizableService) {
				$session = QuarkSession::Get($this->_service->AuthorizationProvider());
				$session->Initialize($request);

				$response->AttachData($session->Trail($response));

				if (!$this->_service->AuthorizationCriteria($request, $session)) {
					$ok = false;
					$output = $this->_service->AuthorizationFailed();
				}
				else {
					if (Quark::is($this->_service, 'Quark\IQuarkSigned' . $method . 'Service')) {
						/**
						 * @note some PHP magic - ::Signature in IQuarkSignedService is static, but call is non static
						 *       explanation - Zend engine translate at compile-time all method calls to static version
						 *       http://stackoverflow.com/a/15756165/2097055
						 */
						$sign = $session->Signature();

						if ($sign == '' || $request->Signature() != $sign) {
							$action = 'SignatureCheckFailedOn' . $method;

							$ok = false;
							$output = $this->_service->$action();
						}
					}
				}
			}

			if ($ok && Quark::is($this->_service, 'Quark\IQuark' . $method . 'Service'))
				$output = $this->_service->$method($request, $session);
		}

		if ($output instanceof QuarkDTO) {
			$response->Headers($output->Headers());
			$response->AttachData($output->Data(), true);
		}
		else $response->AttachData($output, true);

		if (!headers_sent()) {
			$headers = $response->Headers();

			foreach ($headers as $key => $value)
				header($key . ': ' . $value);
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
	 * @return QuarkService
	 *
	 * @throws QuarkArchException
	 * @throws QuarkHTTPException
	 */
	public static function Select () {
		$route = QuarkDTO::ParseRoute($_SERVER['REQUEST_URI']);

		$buffer = array();
		foreach ($route as $item)
			$buffer[] = ucfirst($item);

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
			$service = preg_replace('#\/' . ucfirst(trim($route[$length])) . '$#Uis', '', $service);
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
 * Interface IQuarkConfigurableExtension
 *
 * @package Quark
 */
interface IQuarkConfigurableExtension {
	/**
	 * @param IQuarkExtensionConfig $config
	 *
	 * @return mixed
	 */
	function Init(IQuarkExtensionConfig $config);
}

/**
 * Interface IQuarkExtensionConfig
 *
 * @package Quark
 */
interface IQuarkExtensionConfig { }

/**
 * Class QuarkCredentials
 * @package Quark
 */
class QuarkCredentials {
	private static $_transports = array(
		'tcp' => 'tcp',
		'ssl' => 'ssl',
		'http' => 'tcp',
		'https' => 'ssl',
		'ftp' => 'tcp',
		'ftps' => 'ssl'
	);

	private static $_ports = array(
		'http' => '80',
		'https' => '443',
		'ftp' => '21',
		'ftps' => '22'
	);

	private $_options;

	/**
	 * @var string
	 */
	public $protocol;

	/**
	 * @var string
	 */
	public $username;

	/**
	 * @var string
	 */
	public $password;

	/**
	 * @var string
	 */
	public $host = 'localhost';

	/**
	 * @var integer
	 */
	public $port = 80;

	/**
	 * @var string
	 */
	public $suffix;

	/**
	 * @var string
	 */
	public $token;

	/**
	 * @param string|null $protocol
	 */
	public function __construct ($protocol = null) {
		$this->protocol = $protocol;
	}

	/**
	 * @return QuarkCredentials
	 */
	public function Reset () {
		$this->protocol = null;

		$this->host = 'localhost';
		$this->port = null;

		$this->username = null;
		$this->password = null;

		$this->suffix = null;

		return $this;
	}

	/**
	 * @param $uri
	 * @param bool $web
	 *
	 * @return QuarkCredentials
	 */
	public static function FromURI ($uri, $web = true) {
		$url = Quark::Normalize(
			(object)array(
				'query' => '',
				'scheme' => '',
				'host' => $web ? (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '') : null,
				'port' => $web ? (isset($_SERVER['SERVER_PORT']) ? (int)$_SERVER['SERVER_PORT'] : 80) : null,
				'user' => null,
				'pass' => null,
				'path' => '',
				'fragment' => ''
			),
			(object)parse_url($uri)
		);

		$credentials = new self($url->scheme);
		$credentials->Endpoint($url->host, $url->port);
		$credentials->User($url->user, $url->pass);
		$credentials->Resource(
			$url->path
			. (strlen($url->query) != 0 ? '?' . $url->query : '')
			. $url->fragment
		);

		return $credentials;
	}

	/**
	 * @param $username
	 * @param $password
	 *
	 * @return QuarkCredentials
	 */
	public static function ByAuthCriteria ($username, $password) {
		$credentials = new self();

		$credentials->username = $username;
		$credentials->password = $password;

		return $credentials;
	}

	/**
	 * @param $token
	 *
	 * @return QuarkCredentials
	 */
	public static function ByToken ($token) {
		$credentials = new self();

		$credentials->token = $token;

		return $credentials;
	}

	/**
	 * @param bool $user
	 * @return string
	 */
	public function uri ($user = true) {
		return
			($this->protocol !== null ? $this->protocol : 'http')
			. '://'
			. ($user && $this->username !== null ? $this->username : '')
			. ($user && $this->username !== null && $this->password !== null ? ':' . $this->password : '')
			. ($user && $this->username !== null ? '@' : '')
			. $this->host
			. ($this->port !== null ? ':' . $this->port : '')
			. ($this->suffix !== null ? Quark::NormalizePath('/' . $this->suffix, false) : '')
		;
	}

	/**
	 * @return string
	 */
	public function Socket () {
		return (isset(self::$_transports[$this->protocol])
					? self::$_transports[$this->protocol]
					: 'tcp'
				)
				. '://'
				. gethostbyname($this->host)
				. ':'
				. ($this->port != 80
					? (is_int($this->port)
						? $this->port
						: 80
					)
					: (isset(self::$_ports[$this->protocol])
						? self::$_ports[$this->protocol]
						: 80
					)
				);
	}

	/**
	 * @param string $host
	 * @param integer|null $port
	 *
	 * @return QuarkDTO
	 */
	public function Endpoint ($host, $port = null) {
		$this->host = $host;
		$this->port = $port;

		return $this;
	}

	/**
	 * @param string $username
	 * @param string|null $password
	 *
	 * @return QuarkDTO
	 */
	public function User ($username, $password = null) {
		$this->username = $username;
		$this->password = $password;

		return $this;
	}

	/**
	 * @param string $resource
	 *
	 * @return QuarkDTO
	 */
	public function Resource ($resource) {
		$this->suffix = $resource;

		return $this;
	}

	/**
	 * @param QuarkCredentials $credentials
	 *
	 * @return bool
	 */
	public function Equal (QuarkCredentials $credentials) {
		foreach ($this as $key => $value)
			if ($credentials->$key != $value) return false;

		return true;
	}

	/**
	 * @param array(QuarkCredentials) $credentials
	 *
	 * @return bool
	 */
	public function Used ($credentials = []) {
		if (!is_array($credentials)) return false;
		if (sizeof($credentials) < 2) return false;

		foreach ($credentials as $item)
			if ($item instanceof QuarkCredentials && $this->Equal($item)) return true;

		return false;
	}

	/**
	 * @param mixed $options
	 *
	 * @return mixed
	 */
	public function Options ($options = []) {
		if (func_num_args() == 1)
			$this->_options = $options;

		return $this->_options;
	}
}

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
	function Initialize($name, QuarkDTO $request, $lifetime);

	/**
	 * @param string $name
	 * @param QuarkDTO $response
	 * @param QuarkModel $user
	 *
	 * @return mixed
	 */
	function Trail($name, QuarkDTO $response, QuarkModel $user);

	/**
	 * @param string $name
	 * @param QuarkModel $model
	 * @param $criteria
	 *
	 * @return bool
	 */
	function Login($name, QuarkModel $model, $criteria);

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	function Logout($name);

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	function Signature($name);
}

/**
 * Interface IQuarkAuthorizableService
 * @package Quark
 */
interface IQuarkAuthorizableService {
	/**
	 * @return string
	 */
	function AuthorizationProvider();

	/**
	 * @param QuarkDTO $request
	 * @param QuarkSession $session
	 *
	 * @return bool
	 */
	function AuthorizationCriteria(QuarkDTO $request, QuarkSession $session);

	/**
	 * @return mixed
	 */
	function AuthorizationFailed();
}

/**
 * Interface IQuarkAuthorizableModel
 * @package Quark
 */
interface IQuarkAuthorizableModel {
	/**
	 * @param $criteria
	 *
	 * @return mixed
	 */
	function Authorize($criteria);

	/**
	 * @param IQuarkAuthorizationProvider $provider
	 * @param $request
	 *
	 * @return mixed
	 */
	function RenewSession(IQuarkAuthorizationProvider $provider, $request);
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
	function Any(QuarkDTO $request, QuarkSession $session);
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
	function Get(QuarkDTO $request, QuarkSession $session);
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
	function Post(QuarkDTO $request, QuarkSession $session);
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
	function Processor();
}

/**
 * Interface IQuarkServiceWithCustomProcessor
 *
 * @package Quark
 */
interface IQuarkServiceWithCustomRequestProcessor {
	/**
	 * @return IQuarkIOProcessor
	 */
	function RequestProcessor();
}

/**
 * Interface IQuarkServiceWithCustomProcessor
 *
 * @package Quark
 */
interface IQuarkServiceWithCustomResponseProcessor {
	/**
	 * @return IQuarkIOProcessor
	 */
	function ResponseProcessor();
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
	function InputFilter();
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
	function AllowOrigin();
}

/**
 * Interface IQuarkSignedAnyService
 *
 * @package Quark
 */
interface IQuarkSignedAnyService {
	/**
	 * @return mixed
	 */
	function SignatureCheckFailedOnAny();
}

/**
 * Interface IQuarkSignedGetService
 *
 * @package Quark
 */
interface IQuarkSignedGetService {
	/**
	 * @return mixed
	 */
	function SignatureCheckFailedOnGet();
}

/**
 * Interface IQuarkSignedPostService
 *
 * @package Quark
 */
interface IQuarkSignedPostService {
	/**
	 * @return mixed
	 */
	function SignatureCheckFailedOnPost();
}

/**
 * Class QuarkTask
 *
 * @package Quark
 */
class QuarkTask {
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
		$this->_launched = date('Y-m-d H:i:s');
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
		$this->_launched = date('Y-m-d H:i:s');

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
	 * @param QuarkDTO $request
	 *
	 * @return mixed
	 */
	function Action(QuarkDTO $request);
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
	function LaunchCriteria($previous);
}

/**
 * Class QuarkView
 *
 * @package Quark
 */
class QuarkView {
	/**
	 * @var IQuarkViewModel|null
	 */
	private $_view = null;
	private $_file = '';
	private $_vars = array();
	private $_resources = array();
	private $_html = '';

	private $_null = null;

	/**
	 * @param IQuarkViewModel $view
	 * @param array $vars
	 * @param array $resources
	 *
	 * @throws QuarkArchException
	 */
	public function __construct (IQuarkViewModel $view, $vars = [], $resources = []) {
		$this->_view = $view;
		$this->_file = Quark::NormalizePath(Quark::Host() . '/' . Quark::Config()->Location(QuarkConfig::VIEWS) . '/' . $this->_view->View() . '.php', false);

		if (!is_file($this->_file))
			throw new QuarkArchException('Unknown view file ' . $this->_file);

		$this->Vars($vars);

		$this->_resources = $resources;
	}

	/**
	 * @param $key
	 *
	 * @return mixed
	 */
	public function &__get ($key) {
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
		 * @var IQuarkViewResource|IQuarkForeignViewResource|IQuarkLocalViewResource $resource
		 */
		foreach ($this->_resources as $resource) {
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
			throw new QuarkArchException('ViewModel ' . get_class($this->_view) . ' need to be IQuarkAuthorizableModel');

		return QuarkSession::Get($this->_view->AuthProvider())->User();
	}

	/**
	 * @param IQuarkViewModel $view
	 * @param array $vars
	 * @param array $resources
	 *
	 * @return QuarkView
	 */
	public function Layout (IQuarkViewModel $view, $vars = [], $resources = []) {
		$view = new QuarkView($view, $vars, $resources);
		$view->View($this->Compile());

		return $view;
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
	 * @param array $vars
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
	function View();
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
	function AuthProvider();
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
	function Resources();
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
	function CachedResources();
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
	function Location();

	/**
	 * @return string
	 */
	function Type();
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
	function Dependencies();
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
	function CacheControl();
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
	function RequestDTO();
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
	function Container($location, $content);
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
class QuarkCollection {
	private $_list = array();
	private $_type = null;

	/**
	 * @param object $type
	 */
	public function __construct ($type) {
		$this->_type = $type;
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
	 * @return $this
	 */
	public function Add ($item) {
		if ($item instanceof $this->_type || ($item instanceof QuarkModel && $item->Model() instanceof $this->_type))
			$this->_list[] = $item;

		return $this;
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
}

/**
 * Class QuarkModel
 *
 * @package Quark
 */
class QuarkModel {
	const OPTION_SORT = 'sort';
	const OPTION_EXTRACT = 'extract';
	const OPTION_VALIDATE = 'validate';

	/**
	 * @var IQuarkDataProvider[]
	 */
	private static $_providers = array();

	/**
	 * @param                    $name
	 * @param IQuarkDataProvider $provider
	 *
	 * @return IQuarkDataProvider
	 * @throws QuarkArchException
	 */
	public static function Source ($name, IQuarkDataProvider $provider = null) {
		$args = func_num_args();

		if ($args == 2)
			self::$_providers[$name] = $provider;

		if (!is_scalar($name))
			throw new QuarkArchException('Value [' . print_r($name, true) . '] is not valid data provider name');

		if ($args == 1 && !isset(self::$_providers[$name]))
			throw new QuarkArchException('Data provider ' . print_r($name, true) . ' is not pooled');

		return self::$_providers[$name];
	}

	/**
	 * @param $name
	 *
	 * @return QuarkCredentials
	 * @throws QuarkArchException
	 */
	public static function SourceCredentials ($name) {
		if (!isset(self::$_providers[$name]))
			throw new QuarkArchException('Data provider ' . print_r($name, true) . ' is not pooled');

		$provider = self::$_providers[$name];
		$credentials = $provider->Credentials();

		if (!($credentials instanceof QuarkCredentials))
			throw new QuarkArchException('Data provider ' . get_class($provider) . ' specified invalid QuarkCredentials');

		return $credentials;
	}

	/**
	 * @var IQuarkModel|null
	 */
	private $_model = null;

	public function __construct (IQuarkModel $model, $source = null) {
		/**
		 * Attention!
		 * Cloning need to opposite non-controlled passing by reference
		 */
		$this->_model = new $model();

		if (func_num_args() == 1)
			$source = $model;

		if ($source instanceof QuarkModel)
			$source = $source->Model();

		$this->PopulateWith($source);
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
	 * @param $method
	 * @param $args
	 *
	 * @return mixed
	 */
	public function __call ($method, $args) {
		return call_user_func_array(array($this->_model, $method), $args);
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

		$provider = self::Source($model->DataProvider());

		if (!($provider instanceof IQuarkDataProvider))
			throw new QuarkArchException('Model ' . get_class($model) . ' specified ' . (is_object($provider) ? get_class($provider) : gettype($provider)) . ', which is not a valid IQuarkDataProvider');

		return $provider;
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
				? new QuarkModel($value)
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

		if ($this->_model instanceof IQuarkModelWithBeforeExtract)
			$this->_model->BeforeExtract();

		foreach ($this->_model as $key => $value) {
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

		$backbone = $weak ? $this->_model->Fields() : $fields;

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
	 * @param $options
	 *
	 * @return bool
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
	 * @param $options
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
	 * @return array
	 */
	public static function Find (IQuarkModel $model, $criteria = [], $options = []) {
		$records = array();
		$raw = self::_provider($model)->Find($model, $criteria, $options);

		if ($raw == null)
			return array();

		foreach ($raw as $item)
			$records[] = self::_record($model, $item, $options);

		return $records;
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
	function Fields();

	/**
	 * @return mixed
	 */
	function Rules();
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
	function DataProvider();
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
	function Link($raw);

	/**
	 * @return mixed
	 */
	function Unlink();
}

/**
 * Interface IQuarkStrongModel
 *
 * @package Quark
 */
interface IQuarkStrongModel { }

/**
 * Interface IQuarkModelWithCustomPrimaryKey
 *
 * @package Quark
 */
interface IQuarkModelWithCustomPrimaryKey {
	/**
	 * @return string
	 */
	function PrimaryKey();
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
	function AfterFind($raw);
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
	function OnPopulate($raw);
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
	function BeforeCreate($options);
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
	function BeforeSave($options);
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
	function BeforeRemove($options);
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
	function BeforeValidate();
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
	function BeforeExtract();
}

/**
 * Interface IQuarkDataProvider
 * @package Quark
 */
interface IQuarkDataProvider {
	/**
	 * @param QuarkCredentials $credentials
	 *
	 * @return mixed
	 */
	function Connect(QuarkCredentials $credentials);

	/**
	 * @return QuarkCredentials
	 */
	function Credentials();

	/**
	 * @param IQuarkModel $model
	 *
	 * @return mixed
	 */
	function Create(IQuarkModel $model);

	/**
	 * @param IQuarkModel $model
	 *
	 * @return mixed
	 */
	function Save(IQuarkModel $model);

	/**
	 * @param IQuarkModel $model
	 *
	 * @return mixed
	 */
	function Remove(IQuarkModel $model);

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 *
	 * @return array
	 */
	function Find(IQuarkModel $model, $criteria);

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 *
	 * @return mixed
	 */
	function FindOne(IQuarkModel $model, $criteria);

	/**
	 * @param IQuarkModel $model
	 * @param             $id
	 *
	 * @return mixed
	 */
	function FindOneById(IQuarkModel $model, $id);

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 * @param             $options
	 *
	 * @return mixed
	 */
	function Update(IQuarkModel $model, $criteria, $options);

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 * @param             $options
	 *
	 * @return mixed
	 */
	function Delete(IQuarkModel $model, $criteria, $options);

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 * @param             $limit
	 * @param             $skip
	 *
	 * @return int
	 */
	function Count (IQuarkModel $model, $criteria, $limit, $skip);
}

/**
 * Class QuarkField
 * @package Quark
 */
class QuarkField {
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
 * Class QuarkClient
 * @package Quark
 */
class QuarkClient {
	/**
	 * @var QuarkCredentials
	 */
	private $_credentials = null;

	private $_key = null;
	private $_certificate = null;

	private $_timeout = 3;

	/**
	 * @var QuarkDTO|null
	 */
	private $_request = null;

	/**
	 * @var QuarkDTO|null
	 */
	private $_response = null;

	private $_errorNumber = 0;
	private $_errorString = '';

	/**
	 * @param QuarkCredentials $credentials
	 * @param QuarkDTO $request
	 * @param QuarkDTO $response
	 */
	public function __construct (QuarkCredentials $credentials = null, QuarkDTO $request = null, QuarkDTO $response = null) {
		$this->_credentials = $credentials;
		$this->_request = $request;
		$this->_response = $response;
	}

	/**
	 * @param QuarkCredentials $credentials
	 *
	 * @return QuarkCredentials
	 */
	public function Credentials (QuarkCredentials $credentials = null) {
		if ($credentials != null)
			$this->_credentials = $credentials;

		return $this->_credentials;
	}

	/**
	 * @param int $timeout
	 *
	 * @return int
	 */
	public function Timeout ($timeout = 10) {
		if (func_num_args() != 0)
			$this->_timeout = $timeout;

		return $this->_timeout;
	}

	/**
	 * @param string $key
	 * @param string $certificate
	 */
	public function Sign ($key, $certificate) {
		$this->_key = $key;
		$this->_certificate = $certificate;
	}

	/**
	 * @return int
	 */
	public function ErrorNumber () {
		return $this->_errorNumber;
	}

	/**
	 * @return string
	 */
	public function ErrorString () {
		return $this->_errorString;
	}

	/**
	 * @param QuarkDTO|null $request
	 *
	 * @return QuarkDTO|null
	 */
	public function Request ($request = null) {
		if (func_num_args() == 1)
			$this->_request = $request;

		return $this->_request;
	}

	/**
	 * @param QuarkDTO|null $response
	 *
	 * @return QuarkDTO|null
	 */
	public function Response ($response = null) {
		if (func_num_args() == 1)
			$this->_response = $response;

		return $this->_response;
	}

	/**
	 * @return QuarkClient
	 */
	public function Reset () {
		if ($this->_credentials instanceof QuarkCredentials)
			$this->_credentials->Reset();

		if ($this->_request instanceof QuarkDTO)
			$this->_request->Reset();
		if ($this->_response instanceof QuarkDTO)
			$this->_response->Reset();

		$this->_key = '';
		$this->_certificate = '';

		$this->_errorNumber = 0;
		$this->_errorString = '';

		$this->_timeout = 3;

		return $this;
	}

	/**
	 * @param $method
	 *
	 * @return QuarkDTO
	 * @throws QuarkArchException
	 */
	private function ___request ($method) {
		if (!($this->_request instanceof QuarkDTO))
			$this->_request = new QuarkDTO();

		if (!($this->_response instanceof QuarkDTO))
			$this->_response = new QuarkDTO();

		$stream = stream_context_create();
		stream_context_set_option($stream, 'ssl', 'verify_host', false);
		stream_context_set_option($stream, 'ssl', 'verify_peer', false);

		$socket = @stream_socket_client(
			$this->_credentials->Socket(),
			$this->_errorNumber,
			$this->_errorString,
			$this->_timeout,
			STREAM_CLIENT_CONNECT,
			$stream
		);

		if (!$socket) {
			Quark::Dispatch(Quark::EVENT_CONNECTION_EXCEPTION, array(
				'num' => $this->_errorNumber,
				'description' => $this->_errorString
			));

			return null;
		}

		$this->_request->Header(QuarkDTO::HEADER_HOST, $this->_credentials->host);
		$request = $this->_request->Serialize($method, $this->_credentials->suffix);

		try {
			fwrite($socket, $request);
			$content = stream_get_contents($socket);
			$this->_response->PopulateFrom($content);
			fclose($socket);
		}
		catch (\Exception $e) {
			throw new QuarkArchException('QuarkClient connection error: ' . $e->getMessage());
		}

		return $this->_response;
	}

	/**
	 * @return QuarkDTO|null
	 */
	public function Get () {
		return $this->___request('GET');
	}

	/**
	 * @return QuarkDTO|null
	 */
	public function Post () {
		return $this->___request('POST');
	}
}

/**
 * Class QuarkDTO
 * @package Quark
 */
class QuarkDTO {
	const HEADER_HOST = 'Host';
	const HEADER_CACHE_CONTROL = 'Cache-Control';
	const HEADER_CONTENT_LENGTH = 'Content-Length';
	const HEADER_CONTENT_TYPE = 'Content-Type';
	const HEADER_CONTENT_TRANSFER_ENCODING = 'Content-Transfer-Encoding';
	const HEADER_CONTENT_DISPOSITION = 'Content-Disposition';
	const HEADER_COOKIE = 'Cookie';
	const HEADER_SET_COOKIE = 'Set-Cookie';
	const HEADER_ALLOW_ORIGIN = 'Access-Control-Allow-Origin';

	private $_raw = '';

	private $_method = '';
	private $_version = '';
	private $_statusCode = 0;
	private $_statusText = '';
	private $_query = '';
	private $_route = array();
	private $_headers = array();
	private $_cookies = array();
	private $_data = '';
	private $_files = array();
	private $_boundary = '';
	private $_signature = '';

	/**
	 * @var IQuarkIOProcessor
	 */
	private $_processor = null;

	/**
	 * @param array $headers
	 * @param mixed $data
	 * @param IQuarkIOProcessor $processor
	 */
	public function __construct ($headers = [], $data = '', IQuarkIOProcessor $processor = null) {
		$this->_headers = $headers;
		$this->_data = $data;
		$this->_processor = $processor;
	}

	/**
	 * @param $key
	 *
	 * @return mixed
	 */
	public function __get ($key) {
		return isset($this->_data->$key)
			? $this->_data->$key
			: null;
	}

	/**
	 * @param $key
	 * @param $value
	 */
	public function __set ($key, $value) {
		$this->_data = Quark::Normalize(new \StdClass(), (object)$this->_data);

		$this->_data->$key = $value;
	}

	/**
	 * @return QuarkDTO
	 */
	public function Reset () {
		$this->_raw = '';

		$this->_method = '';
		$this->_version = '';
		$this->_statusCode = 0;
		$this->_statusText = '';
		$this->_query = '';
		$this->_route = array();
		$this->_headers = array();
		$this->_cookies = array();
		$this->_data = '';

		$this->_processor = null;

		return $this;
	}

	/**
	 * @param $http
	 *
	 * @return array
	 */
	private static function _parseHTTP ($http) {
		if (preg_match_all('#^(.*)HTTP\/(.*)\n(.*)\n\s\n(.*)$#Uis', $http, $found, PREG_SET_ORDER) == 0) return null;

		return $found[0];
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
			if (strlen(trim($component)) != 0) $buffer[] = $component;

		$route = $buffer;
		unset($buffer);

		return $route;
	}

	/**
	 * @param $source
	 *
	 * @return $this
	 */
	public function PopulateFrom ($source) {
		$http = self::_parseHTTP($source);

		$this->Raw($source);

		$request = explode(' ', $http[1]);
		$this->Method($request[0]);
		$this->Query(isset($request[1]) ? $request[1] : '');

		if ($http[2]) {
			$head = explode(' ', $http[2]);

			$this->_version = $head[0];
			$this->_statusCode = (int)(isset($head[1]) ? $head[1] : '');
			$this->_statusText = isset($head[2]) ? $head[2] : '';
		}

		$this->Route();

		$header = null;
		$headers = explode("\n", $http[3]);

		foreach ($headers as $head) {
			$header = explode(':', $head);

			if (isset($header[1]))
				$this->_header($header[0], $header[1]);
		}

		$this->AttachData($this->_processor instanceof IQuarkIOProcessor
			? $this->_processor->Decode($http[4])
			: $http[4]
		);

		return $this;
	}

	/**
	 * @param $key
	 * @param $value
	 */
	private function _header ($key, $value) {
		if ($key == QuarkDTO::HEADER_SET_COOKIE) {
			$this->Cookie(QuarkCookie::FromSetCookie($value));

			return;
		}

		if ($key == QuarkDTO::HEADER_COOKIE) {
			$cookie = explode(';', $value);

			foreach ($cookie as $cook)
				$this->Cookie(QuarkCookie::FromCookie($cook));

			return;
		}

		if ($key == QuarkDTO::HEADER_CONTENT_TYPE) {
			preg_match_all('#(.*)\; boundary\=(.*)#', $value, $found, PREG_SET_ORDER);

			$this->_boundary = isset($found[0][2]) ? $found[0][2] : '';
		}

		if (isset($key) && isset($value))
			$this->Header(trim($key), trim($value));
	}

	/**
	 * @param $method
	 * @param $path
	 *
	 * @return string
	 */
	public function Serialize ($method, $path) {
		$payload = $method . ' ' . $path . ' HTTP/1.0' . "\r\n";

		$data = '';
		$dataLength = 0;

		if ($this->_processor instanceof IQuarkIOProcessor) {
			$data = $this->_multipart($this->_data);
			$data = $this->_processor->Encode($data);

			$this->_headers[QuarkDTO::HEADER_CONTENT_TYPE] = $this->_processor->MimeType();

			if (sizeof($this->_files) != 0) {
				$this->_headers[QuarkDTO::HEADER_CONTENT_TYPE] = 'multipart/form-data; boundary=' . $this->_boundary;

				$output = $this->_part($this->_processor->MimeType(), $data);

				foreach ($this->_files as $file)
					$output .= $this->_part($file['depth'], $file['file']);

				$data = $output . '--' . $this->_boundary . "--";
			}

			$dataLength = strlen($data);
		}

		if (!isset($this->_headers[QuarkDTO::HEADER_CONTENT_LENGTH]))
			$this->_headers[QuarkDTO::HEADER_CONTENT_LENGTH] = $dataLength;

		if ($this->_processor instanceof IQuarkIOProcessorWithCustomHeaders)
			$this->_headers = $this->_processor->Headers($this->_headers);

		foreach ($this->_headers as $key => $value)
			$payload .= $key . ': ' . $value . "\r\n";

		$out = $payload . "\r\n" . $data;

		return $out;
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	private function _multipart ($raw) {
		if (!is_array($raw) && !is_object($raw)) return $raw;

		$this->_boundary = 'QuarkBoundary' . Quark::GuID();
		$this->_files = array();

		$output = Quark::Normalize(new \StdClass(), (object)$raw, function ($item, &$def) {
			if (!($def instanceof QuarkFile)) return $def;

			$def = Quark::GuID();

			$this->_files[] = array(
				'file' => $item,
				'depth' => $def
			);

			return $def;
		});

		return $output;
	}

	/**
	 * @param $key
	 * @param mixed $value
	 *
	 * @return string
	 */
	private function _part ($key, $value) {
		$file = $value instanceof QuarkFile;

		/**
		 * Attention!
		 * Here is solution for support custom boundary by Quark: if You does not specify filename to you attachment,
		 * then PHP parser cannot parse your files
		 */

		return
			'--' . $this->_boundary . "\r\n"
			. QuarkDTO::HEADER_CONTENT_DISPOSITION . ': form-data; name="' . $key . '"' . ($file ? '; filename="' . $value->name . '"' : '') . "\r\n"
			. QuarkDTO::HEADER_CONTENT_TYPE . ': ' . ($file ? $value->type : $this->_processor->MimeType()) . "\r\n"
			. "\r\n"
			. ($file ? $value->Content() : $value)
			. "\r\n";
	}

	/**
	 * @param IQuarkIOProcessor $processor
	 *
	 * @return IQuarkIOProcessor
	 */
	public function Processor (IQuarkIOProcessor $processor = null) {
		if ($processor != null)
			$this->_processor = $processor;

		if (!isset($this->_headers[self::HEADER_CONTENT_TYPE]))
			$this->_headers[self::HEADER_CONTENT_TYPE] = $this->_processor->MimeType();

		return $this->_processor;
	}

	/**
	 * @param string $method
	 *
	 * @return string
	 */
	public function Method ($method = '') {
		if (func_num_args() == 1)
			$this->_method = $method;

		return $this->_method;
	}

	/**
	 * @return object
	 */
	public function Status () {
		return (object)array(
			'code' => (int)$this->_statusCode,
			'text' => $this->_statusText
		);
	}

	/**
	 * @param string $query
	 *
	 * @return string
	 */
	public function Query ($query = '') {
		if (func_num_args() == 1)
			$this->_query = $query;

		return $this->_query;
	}

	/**
	 * @param int $id
	 *
	 * @return array|string
	 */
	public function Route ($id = 0) {
		if (sizeof($this->_route) == 0)
			$this->_route = self::ParseRoute($this->_query);

		if (func_num_args() == 1)
			return isset($this->_route[$id]) ? $this->_route[$id] : '';

		return $this->_route;
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
	 * @param array $headers
	 *
	 * @return array
	 */
	public function Headers ($headers = []) {
		if (func_num_args() != 0) {
			$this->_headers = $headers;

			foreach ($this->_headers as $key => $value)
				$this->_header($key, $value);
		}

		return $this->_headers;
	}

	/**
	 * @param $value
	 *
	 * @return QuarkCookie
	 */
	public function Cookie ($value = null) {
		if ($value instanceof QuarkCookie) {
			$this->_cookies[$value->Name()] = $value->Value();

			return $this->_cookies[$value->Name()];
		}

		return isset($this->_cookies[$value])
			? $this->_cookies[$value]
			: null;
	}

	/**
	 * @param array $cookies
	 *
	 * @return array
	 */
	public function Cookies ($cookies = []) {
		if (func_num_args() != 0)
			$this->_cookies = $cookies;

		return $this->_cookies;
	}

	/**
	 * @param $key
	 * @param $value
	 *
	 * @return mixed
	 */
	public function Field ($key, $value) {
		if (func_num_args() == 2)
			$this->_data[$key] = $value;

		return $this->_data[$key];
	}

	/**
	 * @param mixed $data
	 *
	 * @return mixed
	 */
	public function Data ($data = '') {
		if (func_num_args() != 0)
			$this->_data = $data;

		return $this->_data;
	}

	/**
	 * @param array $files
	 *
	 * @return array
	 */
	public function Files ($files = []) {
		if (func_num_args() != 0)
			$this->_files = $files;

		return $this->_files;
	}

	/**
	 * @param mixed $data
	 * @param bool  $string
	 *
	 * @return $this
	 */
	public function AttachData ($data = [], $string = false) {
		$this->_data = $data instanceof QuarkView
			? $data
			: ($string && is_string($data) ? $data : Quark::Normalize($this->_data, $data));

		$this->_signature = isset($this->_data->_signature) ? $this->_data->_signature : '';

		return $this;
	}

	/**
	 * @return string
	 */
	public function Signature () {
		return $this->_signature;
	}

	/**
	 * @param mixed $raw
	 *
	 * @return string
	 */
	public function Raw ($raw = '') {
		if (func_num_args() != 0)
			$this->_raw = $raw;

		return $this->_raw;
	}
}

/**
 * Class QuarkCookie
 *
 * @package Quark
 *
 * @method Name
 * @method Value
 * @method Expires
 * @method MaxAge
 * @method Path
 * @method Domain
 * @method HttpOnly
 * @method Secure
 */
class QuarkCookie {
	private $_name = '';
	private $_value = '';

	private static $__keys = array(
		'expires',
		'MaxAge',
		'path',
		'domain',
		'HttpOnly',
		'secure'
	);

	/**
	 * @param string $name
	 * @param string $value
	 */
	public function __construct ($name = '', $value = '') {
		$this->_name = $name;
		$this->_value = $value;
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
		$item = null;

		$instance = new QuarkCookie();

		foreach ($cookie as $component) {
			$item = explode('=', $component);

			if (isset(self::$__keys[$item[0]]))
				$instance->{ucfirst($item[0])}($item[1]);
			else {
				$instance->Name($item[0]);
				$instance->Value($item[1]);
			}
		}

		return $instance;
	}

	public function __call ($name, $arguments) {
		$key = '_' . strtolower($name);

		if (sizeof($arguments) == 1)
			$this->$key = $arguments[0];

		return $this->$key;
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

	public function Download () { }

	/**
	 * @return array
	 */
	public function Rules () {
		return array(
			QuarkField::Type($this->name, 'string'),
			QuarkField::Type($this->type, 'string'),
			QuarkField::Type($this->size, 'int'),
			QuarkField::Type($this->tmp_name, 'string'),
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
		return new QuarkFile($raw);
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
	function DateTimeFormat();

	/**
	 * @return string
	 */
	function DateFormat();

	/**
	 * @return string
	 */
	function TimeFormat();
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
	 * @param int $status
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
 * @package Quark
 */
class QuarkConnectionException extends QuarkException {
	/**
	 * @var QuarkCredentials
	 */
	public $credentials;

	/**
	 * @param QuarkCredentials $credentials
	 * @param string $lvl
	 */
	public function __construct (QuarkCredentials $credentials, $lvl = Quark::LOG_WARN) {
		$this->lvl = $lvl;
		$this->message = 'Unable to connect to ' . $credentials->uri();

		$this->credentials = $credentials;
	}
}

/**
 * Interface IQuarkIOProcessor
 * @package Quark
 */
interface IQuarkIOProcessor {
	/**
	 * @return string
	 */
	function MimeType();

	/**
	 * @param $data
	 * @return mixed
	 */
	function Encode($data);

	/**
	 * @param $raw
	 * @return mixed
	 */
	function Decode($raw);
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
	function Headers($headers);
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
	function MultipartControl();
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
	public function Encode ($data) { return $data; }

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
	public function Encode ($data) { return json_encode($data); }

	/**
	 * @param $raw
	 * @return mixed
	 */
	public function Decode ($raw) { return json_decode($raw); }
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
		libxml_use_internal_errors(true);
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
		return wddx_deserialize($raw);
	}

	/**
	 * @param $data
	 *
	 * @return mixed
	 */
	public function Encode ($data) {
		return wddx_serialize_value($data);
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
	 * @param string $passphrase
	 * @param string $location
	 */
	public function __construct ($passphrase = '', $location = '') {
		$this->Passphrase($passphrase);
		$this->Location($location);
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
			$this->_location = $location;
		else {
			if (!is_string($this->_location) || !is_file($this->_location))
				throw new QuarkArchException('QuarkCertificate: location is not a valid file');
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
	 * @param string $dim
	 * @param int    $precision
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
	 * @return $this
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