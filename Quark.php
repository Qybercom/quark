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
	 * @var mixed
	 */
	private static $_append;

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

			$isService = self::is($service, 'Quark\IQuark' . ucfirst($method) . 'Service');

			if (!self::is($service, 'Quark\IQuarkAuthorizableService')) {
				if ($isService)
					echo $worker->$method($input);
			}
			else {
				$providers = $worker->AuthorizationProviders();

				if (!is_array($providers))
					throw new QuarkArchException('Authorization providers are not specified for authorizable service');

				foreach ($providers as $i => $provider)
					if ($provider instanceof IQuarkAuthorizationProvider) $provider->Initialize($input);

				$auth = true;
				$criteria = $worker->AuthorizationCriteria($input);

				if (is_array($criteria))
					foreach ($criteria as $i => $rule) $auth = $auth && $rule;

				if (!$auth) {
					echo $worker->AuthorizationFailed();
					exit();
				}

				foreach ($providers as $i => $provider)
					if ($provider instanceof IQuarkAuthorizationProvider)
						self::$_append = $provider->Trail(self::$_append);

				if ($isService)
					echo $worker->$method($input);
			}

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
	 * @param $providers
	 * @param $callback
	 *
	 * @throws QuarkArchException
	 */
	private static function _auth ($providers, $callback) {
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

		return $processor->Encode(array_merge_recursive((array)$data, (array)self::$_append));
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

		if (is_object($class))
			$class = get_class($class);

		if (!class_exists($class)) {
			self::Log('Class "' . $class . '" does not exists', self::LOG_WARN);
			return false;
		}

		$faces = class_implements($class);

		foreach ($interface as $i => $face)
			if (in_array($face, $faces, true)) return true;

		return false;
	}

	/**
	 * @param $target
	 *
	 * @return bool
	 */
	public static function ClassName ($target) {
		if (!is_object($target)) return false;

		$class = get_class($target);
		$ns = explode('\\', $class);

		return $ns[sizeof($ns) - 1];
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
		$file = $name;

		if (is_array($name)) {
			$file = self::$_service;
			$params = $name;
		}

		$file = self::NormalizePath($_SERVER['DOCUMENT_ROOT'] . '/' . self::PATH_VIEWS . '/' . $file . '.php', false);

		if (!is_file($file)) {
			self::Log('Unknown view file ' . $file, self::LOG_WARN);
			exit();
		}

		foreach ($params as $key => $value)
			$$key = $value;

		ob_start();
		include $file;
		return ob_get_clean();
	}

	/**
	 * @param       $layout
	 * @param       $name
	 * @param array $params
	 *
	 * @return string
	 */
	public static function ViewInLayout ($layout, $name, $params = []) {
		if (is_array($name)) {
			$params = $name;
			$name = self::$_service;
		}

		return self::View($layout, $params + array(
			'view' => self::View($name, $params)
		));
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
	 *
	 * @return QuarkCredentials
	 */
	public static function FromURI ($uri) {
		$url = parse_url($uri);

		$query = Quark::valueForKey($url, 'query', Quark::KEY_TYPE_ARRAY);

		$credentials = new self(Quark::valueForKey($url, 'scheme', Quark::KEY_TYPE_ARRAY));
		$credentials->host = Quark::valueForKey($url, 'host', Quark::KEY_TYPE_ARRAY);
		$credentials->port = Quark::valueForKey($url, 'port', Quark::KEY_TYPE_ARRAY);
		$credentials->username = Quark::valueForKey($url, 'user', Quark::KEY_TYPE_ARRAY);
		$credentials->password = Quark::valueForKey($url, 'pass', Quark::KEY_TYPE_ARRAY);
		$credentials->suffix
			= Quark::valueForKey($url, 'path', Quark::KEY_TYPE_ARRAY)
			. (strlen($query) != 0 ? '?' . $query : '')
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
	 * @param $request
	 *
	 * @return mixed
	 */
	function Initialize($request);

	/**
	 * @param $response
	 * @return mixed
	 */
	function Trail($response);

	/**
	 * @param IQuarkAuthorizableModel $model
	 *
	 * @return IQuarkAuthorizationProvider
	 */
	static function Setup(IQuarkAuthorizableModel $model);

	/**
	 * @param IQuarkAuthorizableModel $model
	 * @param $credentials
	 *
	 * @return bool
	 */
	static function Login(IQuarkAuthorizableModel $model, $credentials);

	/**
	 * @return IQuarkAuthorizableModel
	 */
	static function User();

	/**
	 * @return bool
	 */
	static function Logout();
}

/**
 * Interface IQuarkAuthorizableService
 * @package Quark
 */
interface IQuarkAuthorizableService {
	/**
	 * @param $request
	 *
	 * @return array
	 */
	function AuthorizationCriteria($request);

	/**
	 * @return mixed
	 */
	function AuthorizationFailed();

	/**
	 * @return array
	 */
	function AuthorizationProviders();
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
	 * @param IQuarkAuthorizationProvider
	 * @param $request
	 *
	 * @return mixed
	 */
	function RenewSession(IQuarkAuthorizationProvider $provider, $request);
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
 * Class QuarkModel
 *
 * @package Quark
 */
class QuarkModel {
	/**
	 * @var IQuarkModel|IQuarkStrongModel $_model
	 */
	private $_model;

	/**
	 * @param IQuarkModel $model
	 * @param mixed       $source
	 */
	public function __construct (IQuarkModel $model, $source = []) {
		$this->_model = $model;

		if (func_num_args() == 2)
			$this->PopulateWith($source);
	}

	/**
	 * @param $key
	 *
	 * @return mixed
	 */
	public function __get ($key) {
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
	 * @return IQuarkModel
	 */
	public function Model () {
		return $this->_model;
	}

	/**
	 * @return bool
	 */
	public function Validate () {
		return QuarkField::Rules($this->_model->Rules());
	}

	/**
	 * @return $this
	 */
	public function Canonize () {
		$this->_model = self::_canonize($this->_model);

		return $this;
	}

	/**
	 * @param $source
	 * @return QuarkModel
	 */
	public function PopulateWith ($source) {
		if (!is_array($source) && !is_object($source)) return $this;

		foreach ($source as $key => $value)
			$this->_model->$key = $value;

		$this->Canonize();

		return $this;
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return IQuarkDataProvider
	 * @throws QuarkArchException
	 */
	private static function _provider (IQuarkModel $model) {
		$provider = $model->DataProvider();

		if (!($provider instanceof IQuarkDataProvider))
			throw new QuarkArchException((string)$provider . ' is not a IQuarkDataProvider');

		return $provider;
	}

	/**
	 * @param IQuarkModel|IQuarkModelWithAfterFind $model
	 * @param $raw
	 *
	 * @return null|QuarkModel
	 */
	private static function _record ($model, $raw) {
		$class = get_class($model);
		/**
		 * @var IQuarkModel|IQuarkModelWithAfterFind $model
		 */
		$model = new $class();

		if ($raw == null) return null;

		$buffer = Quark::is($model, 'Quark\IQuarkModelWithAfterFind')
			? $model->AfterFind($raw)
			: $raw;

		if ($buffer == null) return null;

		return new QuarkModel($model, $buffer);
	}

	/**
	 * @param IQuarkModel|IQuarkStrongModel $model
	 *
	 * @return \StdClass
	 */
	private static function _canonize ($model) {
		if (!Quark::is($model, 'Quark\\IQuarkStrongModel')) return $model;

		$output = new \StdClass();
		$fields = $model->Fields();

		foreach($fields as $key => $format)
			$output->$key = $model->$key;

		return $output;
	}

	/**
	 * @param array $options
	 *
	 * @return bool
	 */
	private function _validate ($options = []) {
		if (!isset($options['validate']))
			$options['validate'] = true;

		if (!$options['validate']) return true;

		return $this->Validate();
	}

	/**
	 * @param $source
	 *
	 * @return array|null|\StdClass
	 */
	public static function Extract ($source) {
		if (is_array($source)) {
			$output = array();

			foreach ($source as $i => $item)
				$output[] = self::Extract($item);

			return $output;
		}
		else {
			if (!($source instanceof QuarkModel)) return null;

			$output = new \StdClass();
			$item = $source->Model();

			foreach ($item as $key => $value)
				$output->$key = $value;

			return $output;
		}
	}

	/**
	 * @param $options
	 * @return mixed
	 */
	public function Create ($options = []) {
		if (!$this->_validate($options)) return false;

		return self::_provider($this->_model)->Create(self::_canonize($this->_model), $options);
	}

	/**
	 * @param $options
	 * @return mixed
	 */
	public function Save ($options = []) {
		if (!$this->_validate($options)) return false;

		return self::_provider($this->_model)->Save(self::_canonize($this->_model), $options);
	}

	/**
	 * @param $options
	 * @return mixed
	 */
	public function Remove ($options = []) {
		return self::_provider($this->_model)->Remove(self::_canonize($this->_model), $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 * @return mixed
	 */
	public static function Find (IQuarkModel $model, $criteria = [], $options = []) {
		$records = array();
		$raw = self::_provider($model)->Find($model, $criteria, $options);

		foreach ($raw as $i => $item)
			$records[] = self::_record($model, $item);

		return $records;
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 * @return mixed
	 */
	public static function FindOne (IQuarkModel $model, $criteria = [], $options = []) {
		return self::_record($model, self::_provider($model)->FindOne($model, $criteria, $options));
	}

	/**
	 * @param IQuarkModel $model
	 * @param $id
	 * @param $options
	 * @return mixed
	 */
	public static function FindOneById (IQuarkModel $model, $id, $options = []) {
		return self::_record($model, self::_provider($model)->FindOneById($model, $id, $options));
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 * @return mixed
	 */
	public static function Update (IQuarkModel $model, $criteria = [], $options = []) {
		return self::_provider($model)->Update(self::_canonize($model), $criteria, $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $limit
	 * @param $skip
	 * @param $options
	 * @return int
	 */
	public static function Count (IQuarkModel $model, $criteria = [], $limit = 0, $skip = 0, $options = []) {
		return self::_provider($model)->Count(self::_canonize($model), $criteria, $limit, $skip, $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 * @return mixed
	 */
	public static function Delete (IQuarkModel $model, $criteria = [], $options = []) {
		return self::_provider($model)->Delete(self::_canonize($model), $criteria, $options);
	}
}

/**
 * Interface IQuarkDataProvider
 * @package Quark
 */
interface IQuarkDataProvider {
	/**
	 * @return array
	 */
	static function SourcePool();

	/**
	 * @param $name
	 *
	 * @return QuarkCredentials
	 */
	static function SourceGet($name);

	/**
	 * @param $name
	 * @param QuarkCredentials $credentials
	 */
	static function SourceSet($name, QuarkCredentials $credentials);

	/**
	 * @param IQuarkModel $model
	 * @return mixed
	 */
	function Create(IQuarkModel $model);

	/**
	 * @param IQuarkModel $model
	 * @return mixed
	 */
	function Save(IQuarkModel $model);

	/**
	 * @param IQuarkModel $model
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
	 * @return IQuarkModel
	 */
	function FindOne(IQuarkModel $model, $criteria);

	/**
	 * @param IQuarkModel $model
	 * @param             $id
	 *
	 * @return IQuarkModel
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
 * Interface IQuarkModel
 * @package Quark
 */
interface IQuarkModel {
	/**
	 * @return IQuarkDataProvider
	 */
	function DataProvider();

	/**
	 * @return array
	 */
	function Rules();
}

/**
 * Interface IQuarkStrongModel
 * @package Quark
 */
interface IQuarkStrongModel {
	/**
	 * @return array
	 */
	function Fields();
}

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
	public static function Collection ($key, $model, $nullable = false) {
		if ($nullable && $key === null) return true;

		if (!is_array($key)) return false;

		foreach ($key as $i => $item)
			if (!($item instanceof $model)) return false;

		return true;
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
 * Class QuarkClient
 * @package Quark
 */
class QuarkClient {
	const HEADER_CACHE_CONTROL = 'Cache-Control';
	const HEADER_CONTENT_LENGTH = 'Content-Length';
	const HEADER_CONTENT_TYPE = 'Content-Type';
	const HEADER_COOKIE = 'Cookie';
	const HEADER_HOST = 'Host';
	const HEADER_SET_COOKIE = 'Set-Cookie';

	/**
	 * @var QuarkCredentials
	 */
	private $_credentials = null;

	private $_key = null;
	private $_certificate = null;

	private $_timeout = 3;

	/**
	 * @var QuarkClientDTO|null
	 */
	private $_request = null;

	/**
	 * @var QuarkClientDTO|null
	 */
	private $_response = null;

	private $_errorNumber = 0;
	private $_errorString = '';

	/**
	 * @param QuarkCredentials $credentials
	 * @param QuarkClientDTO $request
	 * @param QuarkClientDTO $response
	 */
	public function __construct (QuarkCredentials $credentials = null, QuarkClientDTO $request = null, QuarkClientDTO $response = null) {
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
	 * @param QuarkClientDTO|null $request
	 *
	 * @return QuarkClientDTO|null
	 */
	public function Request ($request = null) {
		if (func_num_args() == 1)
			$this->_request = $request;

		return $this->_request;
	}

	/**
	 * @param QuarkClientDTO|null $response
	 *
	 * @return QuarkClientDTO|null
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

		if ($this->_request instanceof QuarkClientDTO)
			$this->_request->Reset();
		if ($this->_response instanceof QuarkClientDTO)
			$this->_response->Reset();

		$this->_key = '';
		$this->_certificate = '';

		$this->_errorNumber = 0;
		$this->_errorString = '';

		$this->_timeout = 3;

		return $this;
	}

	/**
	 * @param string $method
	 *
	 * @return QuarkClientDTO|null
	 */
	private function ___request ($method) {
		if (!($this->_request instanceof QuarkClientDTO)) return null;
		if (!($this->_response instanceof QuarkClientDTO)) return null;

		$stream = stream_context_create();
		stream_context_set_option($stream, 'ssl', 'verify_host', false);
		stream_context_set_option($stream, 'ssl', 'verify_peer', false);

		$socket = stream_socket_client(
			$this->_credentials->Socket(),
			$this->_errorNumber,
			$this->_errorString,
			$this->_timeout,
			STREAM_CLIENT_CONNECT,
			$stream
		);
		print_r($this->_credentials);
		if (!$socket) return null;
		echo "\r\n\r\n\r\n\r\n";
		$this->_request->Header(self::HEADER_HOST, $this->_credentials->host);
		echo $request =  $this->_request->Serialize($method, $this->_credentials->uri());
		try {
			fwrite($socket, $request);
			echo $content = stream_get_contents($socket);
			$this->_response->PopulateFromHTTPResponse($content);
			fclose($socket);
		}
		catch (\Exception $e) {
			print_r($e);
		}

		return $this->_response;
	}

	/**
	 * @return QuarkClientDTO|null
	 */
	public function Get () {
		return $this->___request('GET');
	}

	/**
	 * @return QuarkClientDTO|null
	 */
	public function Post () {
		return $this->___request('POST');
	}
}

/**
 * Class QuarkClientDTO
 * @package Quark
 */
class QuarkClientDTO {
	private $_raw = '';

	private $_headers = array();
	private $_cookies = array();
	private $_data = '';

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
	 * @return QuarkClientDTO
	 */
	public function Reset () {
		$this->_raw = '';

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
	 * @param $http
	 *
	 * @return QuarkClientDTO
	 */
	public static function FromHTTPRequest ($http) {
		$dto = new QuarkClientDTO();
		$dto->PopulateFromHTTPRequest($http);

		return $dto;
	}

	/**
	 * @param $http
	 *
	 * @return QuarkClientDTO
	 */
	public static function FromHTTPResponse ($http) {
		$dto = new QuarkClientDTO();
		$dto->PopulateFromHTTPResponse($http);

		return $dto;
	}

	/**
	 * @param $http
	 *
	 * @return QuarkClientDTO|null
	 */
	public function PopulateFromHTTPRequest ($http) {
		$request = self::_parseHTTP($http);

		$this->Raw($http);

		$header = array();
		$headers = explode("\n", $request[3]);

		foreach ($headers as $i => $head) {
			$header = explode(':', $head);

			if ($header[0] == QuarkClient::HEADER_SET_COOKIE) {
				$this->Cookie(QuarkCookie::FromSetCookie($header[0]));

				continue;
			}

			$this->Header($header[0], trim($header[1]));
		}

		$this->_data = $this->_processor->Decode($request[4]);

		return $this;
	}

	/**
	 * @param $http
	 *
	 * @return QuarkClientDTO|null
	 */
	public function PopulateFromHTTPResponse ($http) {
		$response = self::_parseHTTP($http);

		$this->Raw($http);

		$header = array();
		$cookie = array();
		$headers = explode("\n", $response[3]);

		foreach ($headers as $h => $head) {
			$header = explode(':', $head);

			if ($header[0] == QuarkClient::HEADER_COOKIE) {
				$cookie = explode(';', $header[1]);

				foreach ($cookie as $c => $cook)
					$this->Cookie(QuarkCookie::FromCookie($cook));

				continue;
			}

			$this->Header($header[0], trim($header[1]));
		}

		$this->_data = $this->_processor->Decode($response[4]);

		return $this;
	}

	/**
	 * @param $method
	 * @param $path
	 *
	 * @return string
	 */
	public function Serialize ($method, $path) {
		$payload
			= ' ' . $method
			. ' '
			. $path
			. ' HTTP/1.0'
			. "\r\n";

		$data = $this->_processor->Encode($this->_data);
		$dataLength = strlen($data);

		if ($dataLength != 0 && !isset($this->_headers[QuarkClient::HEADER_CONTENT_LENGTH]))
			$this->_headers[QuarkClient::HEADER_CONTENT_LENGTH] = $dataLength;

		foreach ($this->_headers as $key => $value)
			$payload .= $key . ': ' . $value . "\r\n";

		return $payload . "\r\n" . $data;
	}

	/**
	 * @param IQuarkIOProcessor $processor
	 *
	 * @return IQuarkIOProcessor
	 */
	public function Processor (IQuarkIOProcessor $processor = null) {
		if ($processor != null)
			$this->_processor = $processor;

		if (!isset($this->_headers['Content-Type']))
			$this->_headers['Content-Type'] = $this->_processor->MimeType();

		return $this->_processor;
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

		return $this->_headers[$key];
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
	 * @return array
	 */
	public function Data ($data = '') {
		if (func_num_args() != 0)
			$this->_data = $data;

		return $this->_data;
	}

	/**
	 * @param mixed $raw
	 *
	 * @return string
	 */
	public function Raw ($raw) {
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
	private $_expires = null;
	private $_MaxAge = null;
	private $_path = null;
	private $_domain = null;
	private $_HttpOnly = false;
	private $_secure = false;

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
		$item = array();

		$instance = new QuarkCookie();

		foreach ($cookie as $i => $component) {
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
	 *
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
	 *
	 * @return mixed
	 */
	public function Encode ($data) {
		/*$xml = new \SimpleXMLElement('<root/>');
		array_walk_recursive($data, array($xml, 'addChild'));
		return $xml->asXML();*/
		return '<toast launch="">
  <visual lang="en-US">
    <binding template="ToastImageAndText01">
      <image id="1" src="World" />
      <text id="1">Hello</text>
    </binding>
  </visual>
</toast>';
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Decode ($raw) {
		return new \SimpleXMLElement($raw);
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

	private $_passphrase = '';

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
	 * @param string $target
	 *
	 * @return array|string
	 */
	public function Generate ($target = '') {
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
		$pem = implode($pem);

		$target = Quark::NormalizePath($target, false);

		if (func_num_args() == 1 && is_file($target))
			file_put_contents($target, $pem);

		return $pem;
	}
}