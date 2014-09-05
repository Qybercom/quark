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

	const PATH_APP = '/';
	const PATH_SERVICES = '/services/';
	const PATH_MODELS = '/models/';
	const PATH_VIEWS = '/views/';
	const PATH_LOGS = '/logs/';

	const LOG_OK = ' ok ';
	const LOG_INFO = 'info';
	const LOG_WARN = 'warn';
	const LOG_FATAL = 'fatal';

	const EVENT_ARCH_EXCEPTION = 'Quark.Exception.Arch';
	const EVENT_HTTP_EXCEPTION = 'Quark.Exception.HTTP';
	const EVENT_CONNECTION_EXCEPTION = 'Quark.Exception.Connection';
	const EVENT_COMMON_EXCEPTION = 'Quark.Exception.Common';

	const KEY_TYPE_OBJECT = 'key.object';
	const KEY_TYPE_ARRAY = 'key.array';

	private static $_service = '/';
	private static $_events = array();

	/**
	 * @var IQuarkIOProcessor
	 */
	private static $_processor;

	/**
	 * @var QuarkConfig
	 */
	private static $_config;

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
		self::$_processor = new QuarkHTMLIOProcessor();

		$method = strtolower($_SERVER['REQUEST_METHOD']);
		$query = preg_replace('#(((\/)*)((\?|\&)(.*)))*#', '', $_SERVER['REQUEST_URI']);

		$static = self::NormalizePath($query, false);

		if (is_file($static))
			echo file_get_contents($static);

		$tree = explode('/', self::NormalizePath(preg_replace('#\.php#Uis', '', $query)));
		$route = array();

		foreach ($tree as $i => $node)
			if (strlen(trim($node)) != 0) $route[] = ucfirst($node);

		$length = sizeof($route);

		if ($length == 0) {
			$route[] = 'Index';
			$length++;
		}

		self::$_service = implode('/', $route);

		$route[$length - 1] .= 'Service.php';
		$service = implode('/', $route);

		$path = self::NormalizePath($_SERVER['DOCUMENT_ROOT'] . '/' . self::PATH_SERVICES . '/' . $service, false);

		try {
			if (!is_file($path))
				throw new QuarkHTTPException(404, 'Unknown service file ' . $path);

			include $path;

			$service = '\\Services\\' . str_replace('/', '\\', str_replace('.php', '', $service));

			if (!class_exists($service) || !self::is($service, 'Quark\IQuarkService'))
				throw new QuarkArchException(500, 'Unknown service class ' . $service);

			/**
			 * @var $worker IQuarkService|IQuarkGetService|IQuarkPostService|IQuarkAuthorizableService|IQuarkServiceWithCustomProcessor|IQuarkServiceWithRequestPreprocessor
			 */
			$worker = new $service();

			if (self::is($service, 'Quark\IQuarkServiceWithCustomProcessor')) self::$_processor = $worker->Processor();

			$input = self::$_processor->Decode(file_get_contents('php://input'));

			if (self::is($service, 'Quark\IQuarkBroadcastService')) $worker->Request($input);

			if (self::is($service, 'Quark\IQuarkAuthorizableService') && !self::Access($worker->AuthorizationCriteria())) {
				echo $worker->AuthorizationFailed();
				exit();
			}

			if (self::is($service, 'Quark\IQuark' . ucfirst($method) . 'Service')) echo $worker->$method($input);
		}
		catch (QuarkArchException $e) {
			self::Log($e->message, $e->lvl);
			self::Dispatch(self::EVENT_ARCH_EXCEPTION, $e);
		}
		catch (QuarkHTTPException $e) {
			self::Log($e->message, $e->lvl);
			self::Dispatch(self::EVENT_HTTP_EXCEPTION, $e);
		}
		catch (QuarkConnectionException $e) {
			self::Log($e->message, $e->lvl);
			self::Dispatch(self::EVENT_CONNECTION_EXCEPTION, $e);
		}
		catch (\Exception $e) {
			self::Log($e->getMessage(), self::LOG_FATAL);
			self::Dispatch(self::EVENT_COMMON_EXCEPTION, $e);
		}
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

		foreach (self::$_events[$event] as $i => $worker) {
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

		foreach (self::$_events[$event] as $i => $worker) $worker($args);
	}

	/**
	 * @param $data
	 * @param $processor IQuarkIOProcessor
	 * @return mixed
	 * @throws QuarkArchException
	 */
	public static function Response ($data, $processor = null) {
		if ($processor == null)
			$processor = self::$_processor;

		if (!self::is($processor, 'Quark\IQuarkIOProcessor'))
			throw new QuarkArchException('Unknown IO processor for response ' . print_r($processor, true));

		return $processor->Encode($data);
	}

	/**
	 * @param $needle
	 * @param array $values
	 * @return null
	 */
	public static function enum ($needle, $values = []) {
		if (!is_array($values)) $values = array();

		return in_array($needle, $values) ? $needle : null;
	}

	/**
	 * @param $source
	 * @param array $backbone
	 * @return array
	 */
	public static function DataArray ($source, $backbone = []) {
		if (!is_array($source)) $source = array();
		if (!is_array($backbone)) $backbone = array();

		$output = array();

		if (sizeof($backbone) == 0) $backbone = $source;

		foreach ($backbone as $key => $value) {
			if (!isset($source[$key])) $output[$key] = $value;
			else {
				if (is_array($value)) $output[$key] = self::DataArray($source[$key], $value);
				else $output[$key] = $source[$key];
			}
		}

		return $output;
	}

	/**
	 * @param $source
	 * @param array $backbone
	 * @return array
	 */
	public static function DataObject ($source, $backbone = []) {
		$source = json_decode(json_encode($source));

		if (!is_object($source)) $source = new \StdClass();
		if (!is_array($backbone)) $backbone = array();

		$output = new \StdClass();

		foreach ($backbone as $key => $value) {
			if (!isset($source->$key)) $output->$key = $value;
			else {
				if (is_array($value)) $output->$key = self::DataArray($source->$key, $value);
				else $output->$key = $source->$key;
			}
		}

		return $output;
	}

	/**
	 * @param $source
	 * @param $name
	 * @param $type
	 * @return mixed
	 */
	public static function valueForKey ($source, $name, $type = self::KEY_TYPE_OBJECT) {
		$output = null;

		switch ($type) {
			case self::KEY_TYPE_OBJECT:
				$output = is_object($source) && isset($source->$name) ? $source->$name : null;
				break;

			case self::KEY_TYPE_ARRAY:
				$output = is_array($source) && isset($source[$name]) ? $source[$name] : null;
				break;

			default:
				$output = null;
				break;
		}

		return $output;
	}

	/**
	 * @param $class
	 * @param string|array $interface
	 * @return bool
	 */
	public static function is ($class, $interface = '') {
		if (!is_array($interface))
			$interface = array($interface);

		$faces = class_implements($class);

		foreach ($interface as $i => $face)
			if (in_array($face, $faces, true)) return true;

		return false;
	}

	/**
	 * @param $interface
	 * @return array
	 */
	public static function Implementations ($interface) {
		$output = array();
		$classes = get_declared_classes();

		foreach ($classes as $i => $class)
			if (self::is($class, $interface)) $output[] = $class;

		return $output;
	}

	/**
	 * @param $path
	 * @param $endSlash
	 * @return string
	 */
	public static function NormalizePath ($path, $endSlash = true) {
		return preg_replace('#(/+)#', '/', str_replace('\\', '/', $path))
			. ($endSlash && (strlen($path) != 0 && $path[strlen($path) - 1] != '/') ? '/' : '');
	}

	/**
	 * @param $path
	 * @return string
	 */
	public static function SanitizePath ($path) {
		return preg_replace('#(((\.*){1-2}/)+)#', '/', $path);
	}

	/**
	 * @param $name
	 * @param array $params
	 * @return string
	 */
	public static function View ($name, $params = []) {
		$view = $name;

		if (is_array($name)) {
			$view = self::$_service;
			$params = $name;
		}

		$view = self::NormalizePath($_SERVER['DOCUMENT_ROOT'] . '/' . self::PATH_VIEWS . '/' . $view . '.php', false);

		if (!is_file($view)) {
			self::Log('Unknown view file ' . $view, self::LOG_WARN);
			exit();
		}

		foreach ($params as $key => $value)
			$$key = $value;

		ob_start();
		include $view;
		return ob_get_clean();
	}

	/**
	 * @param IQuarkAuthorizableModel $user
	 * @return IQuarkAuthorizableModel
	 */
	public static function User (IQuarkAuthorizableModel $user = null) {
		if ($user != null)
			$_SESSION['user'] = $user;

		return isset($_SESSION['user']) ? $_SESSION['user']->RenewSession() : null;
	}

	/**
	 * @param IQuarkAuthorizableDataProvider $provider
	 * @return bool
	 */
	public static function Login (IQuarkAuthorizableDataProvider $provider) {
		$user = $provider->Authenticate();
		$class = get_class($user);
		if ($user == null || !self::is($class, 'Quark\IQuarkAuthorizableModel')) return false;

		@session_start();

		$_SESSION['user'] = $user;

		return true;
	}

	/**
	 * @return bool
	 */
	public static function Logout () {
		if (!isset($_SESSION['user'])) return false;

		unset($_SESSION['user']);
		session_destroy();

		return true;
	}

	/**
	 * @param array $roles
	 * @return bool
	 */
	public static function Access ($roles) {
		if (!is_array($roles)) return true;

		$ok = true;

		foreach ($roles as $i => $role) $ok = $ok && $role;

		return $ok;
	}

	/**
	 * @param $url
	 */
	public static function Redirect ($url) {
		header('Location: ' . $url);
		exit();
	}

	/**
	 * @param $message
	 * @param string $lvl
	 * @param string $domain
	 * @return int|bool
	 */
	public static function Log ($message, $lvl = self::LOG_INFO, $domain = 'application') {
		$logs = self::NormalizePath($_SERVER['DOCUMENT_ROOT'] . '/' . self::PATH_LOGS . '/');

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
	$quark = Quark::NormalizePath(__DIR__ . '/' . str_replace('Quark', '', $class) . '.php', false);
	$app = Quark::NormalizePath($_SERVER['DOCUMENT_ROOT'] . '/' . str_replace('Quark', '', $class) . '.php', false);
	
	$file = $quark;
	
	if (!file_exists($quark)) {
		if (!file_exists($app))
			throw new QuarkArchException('Class file ' . $quark . ' is invalid class path');
		
		$file = $app;
	}
	
	include $file;
});

/**
 * Class QuarkConfig
 * @package Quark
 */
class QuarkConfig {
	/**
	 * @var IQuarkCulture
	 */
	private $_culture;

	/**
	 * @var string
	 */
	private $_mode = Quark::MODE_DEV;

	/**
	 * @param string $mode
	 */
	public function __construct ($mode = Quark::MODE_DEV) {
		$this->_mode = $mode;
		$this->_culture = new QuarkCultureISO();
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
	 * @param IQuarkExtensionConfig $config
	 */
	public function Extension (IQuarkExtensionConfig $config = null) {
		$extension = $config->AssignedExtension();

		if (!Quark::is($extension, 'Quark\IQuarkExtension')) return;

		try {
			$extension::Config($config);
		}
		catch (QuarkConnectionException $e) {
			Quark::Log($e->message, $e->lvl);
			Quark::Dispatch(Quark::EVENT_CONNECTION_EXCEPTION, $e);

			if ($e->lvl == Quark::LOG_FATAL) exit();
		}
	}
}

/**
 * Class QuarkCredentials
 * @package Quark
 */
class QuarkCredentials {
	private static $_transports = array(
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
	public $port;

	/**
	 * @var string
	 */
	public $suffix;

	/**
	 * @param string|null $protocol
	 */
	public function __construct ($protocol = null) {
		$this->protocol = $protocol;
	}

	/**
	 * @param $uri
	 *
	 * @return QuarkCredentials
	 */
	public static function FromURI ($uri) {
		$url = parse_url($uri);

		$credentials = new self(Quark::valueForKey($url, 'scheme', Quark::KEY_TYPE_ARRAY));
		$credentials->host = Quark::valueForKey($url, 'host', Quark::KEY_TYPE_ARRAY);
		$credentials->port = Quark::valueForKey($url, 'port', Quark::KEY_TYPE_ARRAY);
		$credentials->username = Quark::valueForKey($url, 'user', Quark::KEY_TYPE_ARRAY);
		$credentials->password = Quark::valueForKey($url, 'pass', Quark::KEY_TYPE_ARRAY);
		$credentials->suffix
			= Quark::valueForKey($url, 'path', Quark::KEY_TYPE_ARRAY)
			. Quark::valueForKey($url, 'query', Quark::KEY_TYPE_ARRAY)
			. Quark::valueForKey($url, 'fragment', Quark::KEY_TYPE_ARRAY);

		return $credentials;
	}

	/**
	 * @return string
	 */
	public function uri () {
		return
			($this->protocol !== null ? $this->protocol : 'http')
			. '://'
			. ($this->username !== null ? $this->username : '')
			. ($this->username !== null && $this->password !== null ? ':' . $this->password : '')
			. ($this->username !== null ? '@' : '')
			. $this->host
			. ($this->port !== null ? ':' . $this->port : '')
			. '/'
			. ($this->suffix !== null ? Quark::NormalizePath($this->suffix, false) : '')
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
				. (isset(self::$_ports[$this->protocol])
					? self::$_ports[$this->protocol]
					: 80
				);
	}

	/**
	 * @param string $host
	 * @param integer|null $port
	 */
	public function Endpoint ($host, $port = null) {
		$this->host = $host;
		$this->port = $port;
	}

	/**
	 * @param string $username
	 * @param string|null $password
	 */
	public function User ($username, $password = null) {
		$this->username = $username;
		$this->password = $password;
	}

	/**
	 * @param string $resource
	 */
	public function Resource ($resource) {
		$this->suffix = $resource;
	}

	/**
	 * @param QuarkCredentials $credentials
	 * @return bool
	 */
	public function Equal (QuarkCredentials $credentials) {
		foreach ($this as $key => $value)
			if ($credentials->$key != $value) return false;

		return true;
	}

	/**
	 * @param array(QuarkCredentials) $credentials
	 * @return bool
	 */
	public function Used ($credentials = []) {
		if (!is_array($credentials)) return false;
		if (sizeof($credentials) < 2) return false;

		foreach ($credentials as $i => $item)
			if ($item instanceof QuarkCredentials && $this->Equal($item)) return true;

		return false;
	}
}

/**
 * Interface IQuarkExtension
 * @package Quark
 */
interface IQuarkExtension {
	/**
	 * @param IQuarkExtensionConfig|null $config
	 * @return mixed
	 */
	static function Config($config);
}

/**
 * Interface IQuarkExtensionConfig
 *
 * @package Quark
 */
interface IQuarkExtensionConfig {
	/**
	 * @return string
	 */
	function AssignedExtension();
}

/**
 * Interface IQuarkService
 * @package Quark
 */
interface IQuarkService { }

/**
 * Interface IQuarkGetService
 * @package Quark
 */
interface IQuarkGetService extends IQuarkService {
	/**
	 * @param mixed $request
	 * @return mixed
	 */
	function Get($request);
}

/**
 * Interface IQuarkPostService
 * @package Quark
 */
interface IQuarkPostService extends IQuarkService {
	/**
	 * @param mixed $request
	 * @return mixed
	 */
	function Post($request);
}

/**
 * Interface IQuarkServiceWithRequestPreprocessor
 * @package Quark
 */
interface IQuarkServiceWithRequestPreprocessor extends IQuarkService {
	/**
	 * @param mixed $request
	 * @return mixed
	 */
	function Request($request);
}

/**
 * Interface IQuarkServiceWithCustomProcessor
 * @package Quark
 */
interface IQuarkServiceWithCustomProcessor extends IQuarkService {
	/**
	 * @return IQuarkIOProcessor
	 */
	function Processor();
}

/**
 * Interface IQuarkModel
 * @package Quark
 */
interface IQuarkModel {
	/**
	 * @param $model
	 * @return mixed
	 */
	function Model($model);

	/**
	 * @return bool
	 */
	function Validate();

	/**
	 * @param $source
	 * @return IQuarkModel
	 */
	function PopulateWith($source);

	/**
	 * @param $options
	 * @return mixed
	 */
	function Save($options);

	/**
	 * @param $options
	 * @return mixed
	 */
	function Remove($options);

	/**
	 * @param string $model
	 * @param $criteria
	 * @param $options
	 * @return mixed
	 */
	static function Find($model, $criteria, $options);

	/**
	 * @param $model
	 * @param $criteria
	 * @return mixed
	 */
	static function FindOne($model, $criteria);

	/**
	 * @param $model
	 * @param $id
	 * @return mixed
	 */
	static function GetById($model, $id);

	/**
	 * @param $model
	 * @param $criteria
	 * @param $options
	 * @return mixed
	 */
	static function Update($model, $criteria, $options);

	/**
	 * @param $model
	 * @param $criteria
	 * @param $limit
	 * @param $skip
	 * @return int
	 */
	static function Count($model, $criteria, $limit, $skip);

	/**
	 * @param string $model
	 * @param $criteria
	 * @param $options
	 * @return mixed
	 */
	static function Delete($model, $criteria, $options);
}

/**
 * Class QuarkField
 * @package Quark
 */
class QuarkField {
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
	 * @param $key
	 * @param $value
	 * @param bool $sever
	 * @param bool $nullable
	 * @return bool
	 */
	public static function Eq ($key, $value, $sever = false, $nullable = false) {
		if ($nullable && $key === null) return true;

		return $sever ? $key === $value : $key == $value;
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

	public static function Email ($key, $nullable = false) {

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
	 * @param $rules
	 * @return bool
	 */
	public static function Rules ($rules) {
		$ok = true;

		foreach ($rules as $i => $rule)
			$ok = $ok && $rule;

		return $ok;
	}
}

/**
 * Class QuarkRole
 * @package Quark
 */
class QuarkRole {
	const ALL = '*';
	const AUTHENTICATED = '@';
	const BANNED = '~';
	const OWNER = '$';
	const SUPPORT = '?';
	const MODERATOR = '!';
	const ADMIN = '#';

	/**
	 * @return bool
	 */
	public static function Authenticated () {
		return Quark::User() != null;
	}

	/**
	 * @return bool
	 */
	public static function Owner () {
		return false;
	}

	/**
	 * @return bool
	 */
	public static function Administrator () {
		return Quark::User()->SystemRole() == self::ADMIN;
	}
}

/**
 * Interface IQuarkAuthorizableService
 * @package Quark
 */
interface IQuarkAuthorizableService {
	/**
	 * @return array
	 */
	function AuthorizationCriteria();

	/**
	 * @return mixed
	 */
	function AuthorizationFailed();
}

/**
 * Interface IQuarkAuthorizableDataProvider
 * @package Quark
 */
interface IQuarkAuthorizableDataProvider {
	/**
	 * @return IQuarkAuthorizableModel
	 */
	function Authenticate();
}

/**
 * Interface IQuarkAuthorizableModel
 * @package Quark
 */
interface IQuarkAuthorizableModel {
	/**
	 * @return mixed
	 */
	function LoginCriteria();

	/**
	 * @return mixed
	 */
	function SystemRole();

	/**
	 * @return IQuarkAuthorizableModel
	 */
	function RenewSession();
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

	/**
	 * @var IQuarkIOProcessor
	 */
	private $_processor = null;

	private $_key = null;
	private $_certificate = null;

	private $_timeout = 3;

	private $_headers = array();
	private $_data = '';
	private $_raw = '';
	private $_response = '';

	private $_errorNumber = 0;
	private $_errorString = '';

	/**
	 * @param QuarkCredentials $credentials
	 * @param IQuarkIOProcessor $processor
	 */
	public function __construct (QuarkCredentials $credentials = null, IQuarkIOProcessor $processor = null) {
		$this->_credentials = $credentials;
		$this->_processor = $processor == null ? new QuarkPlainIOProcessor() : $processor;
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
	 * @param IQuarkIOProcessor $processor
	 *
	 * @return IQuarkIOProcessor
	 */
	public function Processor (IQuarkIOProcessor $processor = null) {
		if ($processor != null)
			$this->_processor = $processor;

		return $this->_processor;
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
	 * @param $key
	 * @param $value
	 *
	 * @return QuarkClient
	 */
	public function Header ($key, $value) {
		$this->_headers[$key] = $value;

		return $this;
	}

	/**
	 * @param array $headers
	 *
	 * @return array
	 */
	public function Headers ($headers = []) {
		if (func_num_args() != 0)
			$this->_headers = $headers;

		return $this->_headers;
	}

	/**
	 * @param $key
	 * @param $value
	 *
	 * @return QuarkClient
	 */
	public function Field ($key, $value) {
		if (!is_array($this->_data)) return $this;

		$this->_data[$key] = $value;

		return $this;
	}

	/**
	 * @param mixed $data
	 *
	 * @return array
	 */
	public function Data ($data = '') {
		if (func_num_args() != 0)
			$this->_data = $data;

		return $this->_data;
	}

	/**
	 * @return string
	 */
	public function Raw () {
		return $this->_raw;
	}

	/**
	 * @return mixed
	 */
	public function Response () {
		return $this->_response;
	}

	/**
	 * @param string $method
	 * @param callable $processPayload
	 *
	 * @return bool|mixed
	 */
	private function _request ($method, $processPayload) {
		$stream = stream_context_create();

		if ($this->_certificate !== null && $this->_key !== null) {
			stream_context_set_option($stream, 'ssl', 'local_cert', $this->_certificate);
			stream_context_set_option($stream, 'ssl', 'passphrase', $this->_key);
		}

		$socket = @stream_socket_client(
			$this->_credentials->Socket(),
			$this->_errorNumber,
			$this->_errorString,
			$this->_timeout,
			STREAM_CLIENT_CONNECT,
			$stream
		);

		if (!$socket) return false;

		if (!isset($this->_headers['Host']))
			$this->_headers['Host'] = $this->_credentials->host;

		$payload
			= $method
			. ' '
			. $this->_credentials->suffix
			. ' HTTP/1.0'
			. "\r\n";

		foreach ($this->_headers as $key => $value)
			$payload .= $key . ': ' . $value . "\r\n";

		$payload .= "\r\n" . $processPayload();

		fwrite($socket, $payload);
		$this->_raw = stream_get_contents($socket);
		fclose($socket);

		$matches = preg_match_all('#^(HTTP)(.*)\r\n\r\n(.*)$#Uis', $this->_raw, $found, PREG_SET_ORDER);

		$this->_response = $this->_processor->Decode(sizeof($found) != 0 && sizeof($found[0]) == 4 ? $found[0][3] : '');

		return $this->_response;
	}

	/**
	 * @return bool|mixed
	 */
	public function Get () {
		return $this->_request('GET', function () {
			return '';
		});
	}

	/**
	 * @return bool|mixed
	 */
	public function Post () {
		$this->_headers['Content-Length'] = strlen($this->_processor->Encode($this->_data));

		return $this->_request('POST', function () {
			return $this->_processor->Encode($this->_data);
		});
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
	public function Encode ($data) { return $data; }

	/**
	 * @param $raw
	 * @return mixed
	 */
	public function Decode ($raw) { return $raw; }
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