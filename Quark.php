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
	const PATH_SERVICES = '/Services/';
	const PATH_MODELS = '/Models/';
	const PATH_VIEWS = '/Views/';
	const PATH_LOGS = '/logs/';

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
	private static $_events = array();

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
			echo QuarkService::Select()->Invoke();
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
	public static function ClassOf ($target) {
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
	 * @param mixed $source
	 * @param mixed $backbone
	 *
	 * @return \StdClass
	 */
	public static function ToObject ($source, $backbone = []) {
		$output = $source;

		if (!is_object($source)) {
			$output = new \StdClass();

			if (!is_array($source)) return $output;

			foreach ($source as $key => $value)
				$output->$key = $value;
		}

		if (func_num_args() == 2) {
			$backbone = self::ToObject($backbone);

			foreach ($backbone as $key => $value)
				if (!isset($output->$key))
					$output->$key = $value;
		}

		return $output;
	}

	/**
	 * @param       $source
	 * @param array $filter
	 *
	 * @return object|bool
	 */
	public static function Filter ($source, $filter = []) {
		if (!is_object($source) && !is_array($source)) return false;
		if (!is_array($filter)) return $source;

		$output = self::ToObject($source);

		foreach ($source as $key => $value)
			if (in_array($key, $filter))
				$output->$key = $value;

		return $output;
	}

	/**
	 * @param $source
	 *
	 * @return bool
	 */
	public static function isAssoc ($source) {
		return is_array($source) && is_numeric(implode(array_keys($source)));
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

		foreach ($source as $name => $value)
			if (substr($name, 0, 5) == 'HTTP_')
				$output[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;

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
	$quark = Quark::NormalizePath(__DIR__ . '/' . str_replace('Quark\\', '', $class) . '.php', false);
	$app = Quark::NormalizePath($_SERVER['DOCUMENT_ROOT'] . '/' . str_replace('Quark\\', '', $class) . '.php', false);
	
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
	 * @var array
	 */
	private $_extensions = array();

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
	 * @param IQuarkDataProvider $provider
	 * @param                    $name
	 * @param QuarkCredentials   $credentials
	 */
	public function DataSource (IQuarkDataProvider $provider, $name, QuarkCredentials $credentials) {
		try {
			$provider->Source($name, $credentials);
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
	 *
	 * @return IQuarkExtension
	 */
	public function Extension (IQuarkExtension $extension) {
		try {
			if ($extension == null)
				throw new QuarkArchException(' Provided extension in QuarkConfig is null');

			$class = Quark::ClassOf($extension);

			foreach ($this->_extensions as $i => $item)
				if (Quark::ClassOf($item) == $class) return $item;

			$extension->Init();
			$this->_extensions[] = $extension;

			return $extension;
		}
		catch (QuarkConnectionException $e) {
			Quark::Log('Extension connection failure in \'' . Quark::ClassOf($extension) . '\' ' . $e->message, Quark::LOG_FATAL);
			Quark::Dispatch(Quark::EVENT_CONNECTION_EXCEPTION, array('extension' => $extension));
		}
		catch (QuarkArchException $e) {
			Quark::Log('Extension architecture failure in \'' . Quark::ClassOf($extension) . '\' ' . $e->message, Quark::LOG_FATAL);
			Quark::Dispatch(Quark::EVENT_ARCH_EXCEPTION, array('extension' => $extension));
		}
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
	 * @return string
	 * @throws QuarkArchException
	 */
	public function Invoke () {
		$request = new QuarkDTO();
		$request->Processor(new QuarkFormIOProcessor());
		$response = new QuarkDTO();
		$response->Processor(new QuarkHTMLIOProcessor());

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
		$request->PopulateFrom($_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'] . ' ' . $_SERVER['SERVER_PROTOCOL'] . "\r\nHost: " . $_SERVER['HTTP_HOST'] . "\r\n\r\n" . file_get_contents('php://input'));
		$request->AttachData($_GET + $_POST);

		if ($this->_service instanceof IQuarkStrongService)
			$request->Data(Quark::Filter($request->Data(), $this->_service->InputFilter()));

		$ok = true;
		$output = null;

		ob_start();

		if ($this->_service instanceof IQuarkAuthorizableService) {
			$provider = $this->_service->AuthorizationProvider();

			if (!($provider instanceof IQuarkAuthorizationProvider))
				throw new QuarkArchException('Specified provider is not a valid IQuarkAuthorizationProvider');

			$provider->Initialize($request);
			$response->AttachData($provider->Trail($response));

			if (!$this->_service->AuthorizationCriteria($request)) {
				$ok = false;
				$output = $this->_service->AuthorizationFailed();
			}
		}

		if ($ok) {
			$method = $this->_service->method = $this->_service instanceof IQuarkAnyService
				? 'any'
				: strtolower($_SERVER['REQUEST_METHOD']);

			if (Quark::is($this->_service, 'Quark\IQuark' . ucfirst($method) . 'Service'))
				$output = $this->_service->$method($request);
		}

		if ($output instanceof QuarkDTO) {
			$response->Headers($output->Headers());
			$response->AttachData($output->Data());
		}
		else $response->AttachData($output);

		$headers = $response->Headers();

		foreach ($headers as $key => $value)
			header($key . ': ' . $value);

		echo $response->Processor()->Encode($response->Data());

		return ob_get_clean();
	}

	/**
	 * @param $service
	 *
	 * @return string
	 */
	private static function _bundle ($service) {
		return Quark::NormalizePath($_SERVER['DOCUMENT_ROOT'] . '/' . Quark::PATH_SERVICES . '/' . $service . 'Service.php', false);
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
		foreach ($route as $i => $item)
			$buffer[] = ucfirst($item);

		$route = $buffer;
		unset($buffer);

		$length = sizeof($route);
		$service = $length == 0 ? 'Index' : implode('/', $route);
		$path = self::_bundle($service);

		while ($length > 0) {
			if (is_file($path)) break;

			$length--;
			$service = preg_replace('#\/' . ucfirst(trim($route[$length])) . '$#Uis', '', $service);
			$path = self::_bundle($service);
		}

		if (!is_file($path))
			throw new QuarkHTTPException(404, 'Unknown service file ' . $path);

		include $path;

		$class = str_replace('/', '\\', '/Services/' . $service . 'Service');

		if (!class_exists($class))
			throw new QuarkArchException(500, 'Unknown service class ' . $class);

		$bundle = new $class();
		$bundle->route = $route;

		if (!($bundle instanceof IQuarkService))
			throw new QuarkArchException(500, 'Class ' . $class . ' is not an IQuarkService');

		return new QuarkService($bundle);
	}
}

/**
 * Interface IQuarkExtension
 *
 * @package Quark
 */
interface IQuarkExtension {
	/**
	 * @return mixed
	 */
	function Init();
}

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
	 *
	 * @return QuarkCredentials
	 */
	public static function FromURI ($uri) {
		$url = Quark::ToObject(parse_url($uri), array(
			'query' => '',
			'scheme' => '',
			'host' => '',
			'port' => 80,
			'user' => '',
			'pass' => '',
			'path' => '',
			'fragment' => ''
		));

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
	 * @return QuarkModel
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
	 * @return bool
	 */
	function AuthorizationCriteria($request);

	/**
	 * @return mixed
	 */
	function AuthorizationFailed();

	/**
	 * @return IQuarkAuthorizationProvider
	 */
	function AuthorizationProvider();
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
 * @package Quark
 */
interface IQuarkService { }

/**
 * Interface IQuarkAnyService
 * @package Quark
 */
interface IQuarkAnyService extends IQuarkService {
	/**
	 * @param QuarkDTO $request
	 * @return mixed
	 */
	function Any(QuarkDTO $request);
}

/**
 * Interface IQuarkGetService
 * @package Quark
 */
interface IQuarkGetService extends IQuarkService {
	/**
	 * @param QuarkDTO $request
	 * @return mixed
	 */
	function Get(QuarkDTO $request);
}

/**
 * Interface IQuarkPostService
 * @package Quark
 */
interface IQuarkPostService extends IQuarkService {
	/**
	 * @param QuarkDTO $request
	 * @return mixed
	 */
	function Post(QuarkDTO $request);
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
 * Interface IQuarkServiceWithCustomProcessor
 * @package Quark
 */
interface IQuarkServiceWithCustomRequestProcessor extends IQuarkService {
	/**
	 * @return IQuarkIOProcessor
	 */
	function RequestProcessor();
}

/**
 * Interface IQuarkServiceWithCustomProcessor
 * @package Quark
 */
interface IQuarkServiceWithCustomResponseProcessor extends IQuarkService {
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
 * Class QuarkView
 *
 * @package Quark
 */
class QuarkView {
	private $_view = '';
	private $_vars = array();

	/**
	 * @param        $name
	 * @param array  $vars
	 * @throws QuarkArchException
	 */
	public function __construct ($name, $vars = []) {
		if (!is_string($name))
			throw new QuarkArchException('Provided view name is not a string');

		$this->_view = Quark::NormalizePath($_SERVER['DOCUMENT_ROOT'] . '/' . Quark::PATH_VIEWS . '/' . $name . '.php', false);

		if (!is_file($this->_view))
			throw new QuarkArchException('Unknown view file ' . $this->_view);

		$this->Vars($vars);
	}

	/**
	 * @param       $name
	 * @param array $vars
	 *
	 * @return QuarkView
	 */
	public function Layout ($name, $vars = []) {
		$layout = new QuarkView($name, $vars);
		$layout->Vars(array(
			'view' => $this->Compile()
		));

		return $layout;
	}

	/**
	 * @param string $view
	 * @param string $layout
	 * @param array $vars
	 *
	 * @return QuarkView
	 */
	public static function InLayout ($view, $layout, $vars = []) {
		return (new QuarkView($view, $vars))->Layout($layout, $vars);
	}

	/**
	 * @param array $params
	 *
	 * @return array
	 */
	public function Vars ($params = []) {
		if (func_num_args() == 1) {
			$params = Quark::ToObject($params);

			foreach ($params as $key => $value)
				$this->_vars[$key] = $value;
		}

		return $this->_vars;
	}

	/**
	 * @return string
	 */
	public function Compile () {
		foreach ($this->_vars as $name => $value)
			$$name = $value;

		ob_start();
		include $this->_view;
		return ob_get_clean();
	}
}


/**
 * Class QuarkModel
 *
 * @package Quark
 */
class QuarkModel {
	const OPTION_EXTRACT = 'extract';
	const OPTION_VALIDATE = 'validate';

	/**
	 * @var IQuarkModel|IQuarkStrongModel|IQuarkModelWithAfterFind|IQuarkModelWithBeforeCreate|IQuarkModelWithBeforeSave|IQuarkModelWithBeforeRemove|IQuarkModelWithBeforePopulate|IQuarkModelWithInputFilter $_model
	 */
	private $_model;
	public $sign = '';

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
	public function &__get ($key) {
		if (!isset($this->_model->$key)) {
			Quark::Dispatch(Quark::EVENT_ARCH_EXCEPTION, array(
				'model' => $this->_model
			));

			Quark::Log('QuarkModel: Undefined property "' . $key . '" in model ' . Quark::ClassOf($this->_model), Quark::LOG_WARN);

			return null;
		}

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

		$raw = new \StdClass();

		foreach ($source as $key => $value)
			$raw->$key = $value;

		$output = Quark::is($this->_model, 'Quark\IQuarkModelWithBeforePopulate')
			? $this->_model->BeforePopulate($raw)
			: $source;

		if ($output === false) return $this;
		if ($output === null) $output = $source;

		foreach ($output as $key => $value)
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
			throw new QuarkArchException(gettype($provider) . ' is not a valid IQuarkDataProvider');

		return $provider;
	}

	/**
	 * @param IQuarkModel|IQuarkModelWithAfterFind $model
	 * @param $raw
	 * @param $options
	 *
	 * @return null|QuarkModel
	 */
	private static function _record ($model, $raw, $options = []) {
		if ($raw == null) return null;

		/**
		 * Attention!
		 * Here is solution for non-controlled passing-by-reference of $model
		 * In case if You pass it without re-instantiating, it will appear situation when in ::Find method, $model will
		 * refer to SAME object, which is not correct in this AR ORM use case
		 */
		$class = get_class($model);
		$model = new $class();

		$output = new QuarkModel($model, $raw);

		if ($model instanceof IQuarkModelWithAfterFind) {
			if ($model->AfterFind($raw, $options) === false) return null;

			$output->PopulateWith($model);
		}

		if (isset($options[self::OPTION_EXTRACT]) && $options[self::OPTION_EXTRACT] == true)
			$output = self::Extract($output);

		return $output;
	}

	/**
	 * @param IQuarkModel|IQuarkModelWithInputFilter|IQuarkStrongModel $model
	 *
	 * @return object
	 */
	private static function _canonize ($model) {
		if (!Quark::is($model, 'Quark\\IQuarkStrongModel')) return $model;

		$class = get_class($model);
		$output = new $class();
		$fields = $model->Fields();

		if (is_array($fields))
			foreach($fields as $key => $format)
				$output->$key = isset($model->$key) ? $model->$key : $format;

		return $output;
	}

	/**
	 * @param array $options
	 *
	 * @return bool
	 */
	private function _validate ($options = []) {
		if (!isset($options[self::OPTION_VALIDATE]))
			$options[self::OPTION_VALIDATE] = true;

		if (!$options[self::OPTION_VALIDATE]) return true;

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

			if ($item instanceof IQuarkModelWithBeforeExtract)
				$item->BeforeExtract();

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

		$ok = Quark::is($this->_model, 'Quark\IQuarkModelWithBeforeCreate')
			? $this->_model->BeforeCreate($options)
			: true;

		return ($ok || $ok === null) ? self::_provider($this->_model)->Create(self::_canonize($this->_model), $options) : false;
	}

	/**
	 * @param $options
	 * @return mixed
	 */
	public function Save ($options = []) {
		if (!$this->_validate($options)) return false;

		$ok = Quark::is($this->_model, 'Quark\IQuarkModelWithBeforeSave')
			? $this->_model->BeforeSave($options)
			: true;

		return ($ok || $ok === null) ? self::_provider($this->_model)->Save(self::_canonize($this->_model), $options) : false;
	}

	/**
	 * @param $options
	 * @return mixed
	 */
	public function Remove ($options = []) {
		$ok = Quark::is($this->_model, 'Quark\IQuarkModelWithBeforeRemove')
			? $this->_model->BeforeRemove($options)
			: true;

		return ($ok || $ok === null) ? self::_provider($this->_model)->Remove(self::_canonize($this->_model), $options) : false;
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

		if ($raw == null)
			return array();

		foreach ($raw as $i => $item)
			$records[] = self::_record($model, $item, $options);

		return $records;
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 * @return mixed
	 */
	public static function FindOne (IQuarkModel $model, $criteria = [], $options = []) {
		return self::_record($model, self::_provider($model)->FindOne($model, $criteria, $options), $options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $id
	 * @param $options
	 * @return mixed
	 */
	public static function FindOneById (IQuarkModel $model, $id, $options = []) {
		return self::_record($model, self::_provider($model)->FindOneById($model, $id, $options), $options);
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
	 * @return IQuarkDataProvider
	 */
	static function SourceGet($name);

	/**
	 * @param $name
	 * @param QuarkCredentials $credentials
	 */
	static function SourceSet($name, QuarkCredentials $credentials);

	/**
	 * @param                  $name
	 * @param QuarkCredentials $credentials
	 */
	function Source($name, QuarkCredentials $credentials);

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
 * Interface IQuarkModelWithBeforePopulate
 *
 * @package Quark
 */
interface IQuarkModelWithBeforePopulate {
	/**
	 * @param mixed $source
	 *
	 * @return mixed
	 */
	function BeforePopulate($source);
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
	const HEADER_CACHE_CONTROL = 'Cache-Control';
	const HEADER_CONTENT_LENGTH = 'Content-Length';
	const HEADER_CONTENT_TYPE = 'Content-Type';
	const HEADER_COOKIE = 'Cookie';
	const HEADER_HOST = 'Host';
	const HEADER_SET_COOKIE = 'Set-Cookie';
	const HEADER_ALLOW_ORIGIN = 'Access-Control-Allow-Origin';

	private $_raw = '';

	private $_method = '';
	private $_query = '';
	private $_route = array();
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
		$this->_data = Quark::ToObject($this->_data);

		$this->_data->$key = $value;
	}

	/**
	 * @return QuarkDTO
	 */
	public function Reset () {
		$this->_raw = '';

		$this->_method = '';
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
		foreach ($route as $i => $component)
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
		$this->Route();

		$header = array();
		$headers = explode("\n", $http[3]);

		foreach ($headers as $i => $head) {
			$header = explode(':', $head);

			if ($header[0] == QuarkDTO::HEADER_SET_COOKIE) {
				$this->Cookie(QuarkCookie::FromSetCookie($header[0]));

				continue;
			}

			if ($header[0] == QuarkDTO::HEADER_COOKIE) {
				$cookie = explode(';', $header[1]);

				foreach ($cookie as $c => $cook)
					$this->Cookie(QuarkCookie::FromCookie($cook));

				continue;
			}

			if (isset($header[0]) && isset($header[1]))
				$this->Header(trim($header[0]), trim($header[1]));
		}

		$this->_data = $this->_processor instanceof IQuarkIOProcessor
			? $this->_processor->Decode($http[4])
			: $http[4];

		return $this;
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

		if ($this->_processor instanceof IQuarkIOProcessor)
			$data = $this->_processor->Encode($this->_data);

		$dataLength = strlen($data);

		if ($dataLength != 0 && !isset($this->_headers[QuarkDTO::HEADER_CONTENT_LENGTH]))
			$this->_headers[QuarkDTO::HEADER_CONTENT_LENGTH] = $dataLength;

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
	 * @param array $data
	 *
	 * @return QuarkDTO
	 */
	public function AttachData ($data = []) {
		if ($data instanceof QuarkView)
			$this->_data = $data;
		else {
			$data = Quark::ToObject($data);
			$this->_data = Quark::ToObject($this->_data);

			if (!is_array($data) && !is_object($data)) return $this;

			foreach ($data as $key => $value)
				$this->_data->$key = $value;
		}

		return $this;
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