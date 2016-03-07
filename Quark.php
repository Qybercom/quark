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
 *
 * @grandfather Furnica Alexandru Dumitru, agronomist, Deputy Chairman of the executive committee Vulcăneşti (Фурника Александр Дмитриевич, агроном, заместитель председателя райисполкома Вулканешты)
 * @grandmother Furnica Nina Feodorovna, biology teacher, teaching experience 49 years (Фурника Нина Фёдоровна, учитель биологии, преподавательский стаж 49 лет)
 * @mom Furnica Tatiana Alexandru, music teacher, teaching experience 28 years (Фурника Татьяна Александровна, учитель музыки, преподавательский стаж 28 лет)
 * @me Furnica Alexandru Dumitru, web programmer since 2009 (Фурника Александр Дмитриевич, веб-программист с 2009 года)
 */
class Quark {
	const MODE_DEV = 'dev';
	const MODE_PRODUCTION = 'production';

	const LOG_OK = ' ok ';
	const LOG_INFO = 'info';
	const LOG_WARN = 'warn';
	const LOG_FATAL = 'fatal';

	const UNIT_BYTE = 1;
	const UNIT_KILOBYTE = 1024;
	const UNIT_MEGABYTE = 1048576;
	const UNIT_GIGABYTE = 1073741824;

	/**
	 * @var QuarkConfig $_config
	 */
	private static $_config;

	/**
	 * @var IQuarkEnvironment[] $_environment
	 */
	private static $_environment = array();

	/**
	 * @var IQuarkEnvironment $_currentEnvironment
	 */
	private static $_currentEnvironment;

	/**
	 * @var IQuarkStackable[] $_stack
	 */
	private static $_stack = array();

	/**
	 * @var IQuarkContainer[] $_containers
	 */
	private static $_containers = array();

	/**
	 * @var null $_null = null
	 */
	private static $_null = null;

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

		$argc = isset($_SERVER['argc']) ? $_SERVER['argc'] : 0;
		$argv = isset($_SERVER['argv']) ? $_SERVER['argv'] : array();

		$threads = new QuarkThreadSet($argc, $argv);

		self::Environment(self::CLI()
			? new QuarkCLIEnvironment($argc, $argv)
			: new QuarkFPMEnvironment($argc, $argv)
		);

		$threads->Threads(self::$_environment);

		$threads->On(QuarkThreadSet::EVENT_AFTER_INVOKE, function () {
			$timers = QuarkTimer::Timers();

			foreach ($timers as $timer)
				if ($timer) $timer->Invoke();

			self::ContainerFree();
		});

		if (!self::CLI() || ($argc > 1 || $argc == 0)) $threads->Invoke();
		else $threads->Pipeline(self::$_config->Tick());
	}

	/**
	 * @param string $host
	 *
	 * @return string
	 */
	public static function IP ($host) {
		return gethostbyname($host);
	}

	/**
	 * @param string $ip = ''
	 *
	 * @return mixed
	 */
	public static function IPInfo ($ip = '') {
		return QuarkHTTPClient::To('http://ipinfo.io/' . $ip, QuarkDTO::ForGET(), new QuarkDTO(new QuarkJSONIOProcessor()))->Data();
	}

	/**
	 * http://mycrimea.su/partners/web/access/ipsearch.php
	 *
	 * @param int $mask = 24
	 *
	 * @return string
	 */
	public static function CIDR ($mask = 24) {
		return long2ip(pow(2, 32) - pow(2, (32 - $mask)));
	}

	/**
	 * @return string
	 */
	public static function HostIP () {
		return self::IP(php_uname('n'));
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
	 * @return string
	 */
	public static function WebHost () {
		return self::$_config->WebHost()->URI(false);
	}

	/**
	 * @param string $path
	 *
	 * @return string
	 */
	public static function WebLocation ($path) {
		$uri = Quark::WebHost() . str_replace(Quark::Host(), '/', Quark::NormalizePath($path, false));
		return str_replace(':::', '://', str_replace('//', '/', str_replace('://', ':::', $uri)));
	}

	/**
	 * @param string $url
	 *
	 * @return string
	 */
	public static function FSLocation ($url) {
		return Quark::Host() . str_replace(Quark::WebHost(), '', $url);
	}

	/**
	 * @param string $path
	 * @param bool $endSlash = true
	 *
	 * @return string
	 */
	public static function NormalizePath ($path, $endSlash = true) {
		return is_scalar($path)
			? trim(preg_replace('#/+#', '/', self::RealPath(str_replace('\\', '/', $path))))
				. ($endSlash && (strlen($path) != 0 && $path[strlen($path) - 1] != '/') ? '/' : '')
			: ($path instanceof QuarkFile ? $path->location : '');
	}

	/**
	 * @param string $path
	 *
	 * https://stackoverflow.com/a/4050444/2097055
	 *
	 * @return string
	 */
	public static function RealPath ($path) {
		$absolutes = array();
		$route = explode('/', str_replace('\\', '/', $path));

		foreach ($route as $part) {
			if ('.'  == $part) continue;
			if ('..' == $part) array_pop($absolutes);
			else $absolutes[] = $part;
		}

		return implode('/', $absolutes);
	}

	/**
	 * Date unique ID
	 *
	 * @return string
	 */
	public static function DuID () {
		$micro = explode(' ', microtime());
		return gmdate('YmdHis', $micro[1]) . substr($micro[0], strpos($micro[0], '.'));
	}

	/**
	 * Global unique ID
	 *
	 * @return string
	 */
	public static function GuID () {
		return sha1(self::DuID());
	}

	/**
	 * @param IQuarkEnvironment $provider = null
	 *
	 * @return IQuarkEnvironment[]
	 */
	public static function &Environment (IQuarkEnvironment $provider = null) {
		if ($provider) {
			if (!$provider->Multiple())
				foreach (self::$_environment as $environment)
					if ($environment instanceof $provider) return self::$_environment;

			self::$_environment[] = $provider;
		}

		return self::$_environment;
	}

	/**
	 * @param IQuarkEnvironment $provider = null
	 *
	 * @return IQuarkEnvironment
	 */
	public static function &CurrentEnvironment (IQuarkEnvironment $provider = null) {
		if (func_num_args() != 0)
			self::$_currentEnvironment = $provider;

		return self::$_currentEnvironment;
	}

	/**
	 * @param string $name
	 * @param IQuarkStackable $component = null
	 *
	 * @return IQuarkStackable
	 *
	 * @throws QuarkArchException
	 */
	public static function &Component ($name, IQuarkStackable $component = null) {
		if (!$component)
			return self::Stack($name);

		return self::Stack($name, $component);
	}

	/**
	 * @param string $name
	 * @param IQuarkStackable $object = null
	 *
	 * @return IQuarkStackable
	 *
	 * @throws QuarkArchException
	 */
	public static function &Stack ($name, IQuarkStackable $object = null) {
		if (func_num_args() == 2 && $object != null) {
			$object->Stacked($name);
			self::$_stack[$name] = $object;
		}

		if (!isset(self::$_stack[$name]))
			throw new QuarkArchException('Stackable object for ' . $name . ' does not stacked');

		return self::$_stack[$name];
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
	public static function Container (IQuarkContainer &$container) {
		self::$_containers[spl_object_hash($container->Primitive())] = $container;
	}

	/**
	 * @param string $id
	 *
	 * @return IQuarkContainer|null
	 */
	public static function &ContainerOf ($id) {
		if (!isset(self::$_containers[$id]))
			return self::$_null;

		return self::$_containers[$id];
	}

	/**
	 * Free associated containers
	 */
	public static function ContainerFree () {
		self::$_containers = array();
	}

	/**
	 * @param string $path
	 * @param callable $process = null
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
	 * @param string $lvl = self::LOG_INFO
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
	 * @var IQuarkCulture $_culture
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
	 * @var QuarkModel|IQuarkApplicationSettingsModel $_settings
	 */
	private $_settings;

	/**
	 * @var array $_location
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
	 * @var QuarkURI $_clusterControllerListen
	 */
	private $_clusterControllerListen;

	/**
	 * @var QuarkURI $_clusterControllerConnect
	 */
	private $_clusterControllerConnect;

	/**
	 * @var QuarkURI $_clusterMonitor
	 */
	private $_clusterMonitor;

	/**
	 * @var string $_clusterKey
	 */
	private $_clusterKey;

	/**
	 * @var QuarkURI $_selfHosted
	 */
	private $_selfHosted;

	/**
	 * @var bool $_allowIndexFallback = false
	 */
	private $_allowIndexFallback = false;

	/**
	 * @param string $mode = Quark::MODE_DEV
	 */
	public function __construct ($mode = Quark::MODE_DEV) {
		$this->_mode = $mode;
		$this->_culture = new QuarkCultureISO();
		$this->_webHost = new QuarkURI();

		$this->_clusterControllerListen = QuarkURI::FromURI(QuarkStreamEnvironment::URI_CONTROLLER_INTERNAL);
		$this->_clusterControllerConnect = $this->_clusterControllerListen->ConnectionString();
		$this->_clusterMonitor = QuarkURI::FromURI(QuarkStreamEnvironment::URI_CONTROLLER_EXTERNAL);
		$this->_selfHosted = QuarkURI::FromURI(QuarkFPMEnvironment::SELF_HOSTED);

		if (isset($_SERVER['SERVER_PROTOCOL']))
			$this->_webHost->scheme = $_SERVER['SERVER_PROTOCOL'];

		if (isset($_SERVER['SERVER_NAME']))
			$this->_webHost->host = $_SERVER['SERVER_NAME'];

		if (isset($_SERVER['SERVER_PORT']))
			$this->_webHost->port = $_SERVER['SERVER_PORT'];

		if (isset($_SERVER['DOCUMENT_ROOT']))
			$this->_webHost->path = Quark::NormalizePath(str_replace($_SERVER['DOCUMENT_ROOT'], '', Quark::Host()));
	}

	/**
	 * @param IQuarkCulture $culture = null
	 *
	 * @return IQuarkCulture|QuarkCultureISO
	 */
	public function &Culture (IQuarkCulture $culture = null) {
		if (func_num_args() != 0 && $culture != null)
			$this->_culture = $culture;

		return $this->_culture;
	}

	/**
	 * @param int $mb = 5 (megabytes)
	 *
	 * @return int
	 */
	public function &Alloc ($mb = 5) {
		if (func_num_args() != 0)
			$this->_alloc = $mb;

		return $this->_alloc;
	}

	/**
	 * @param int $ms = 10000 (microseconds)
	 *
	 * @return int
	 */
	public function &Tick ($ms = QuarkThreadSet::TICK) {
		if (func_num_args() != 0)
			$this->_tick = $ms;

		return $this->_tick;
	}

	/**
	 * @param string $mode = Quark::MODE_DEV
	 *
	 * @return string
	 */
	public function &Mode ($mode = Quark::MODE_DEV) {
		if (func_num_args() != 0)
			$this->_mode = $mode;

		return $this->_mode;
	}

	/**
	 * @param string $name
	 * @param IQuarkStackable $object = null
	 * @param string $message = ''
	 *
	 * @return IQuarkStackable
	 *
	 * @throws QuarkArchException
	 */
	private function _component ($name, IQuarkStackable $object = null, $message = '') {
		try {
			return Quark::Component($name, $object);
		}
		catch (\Exception $e) {
			throw new QuarkArchException($message . '. Additional : ' . $e->getMessage());
		}
	}

	/**
	 * @param string $name
	 * @param IQuarkAuthorizationProvider $provider = null
	 * @param IQuarkAuthorizableModel $user = null
	 *
	 * @return QuarkSession
	 */
	public function AuthorizationProvider ($name, IQuarkAuthorizationProvider $provider = null, IQuarkAuthorizableModel $user = null) {
		return $this->_component(
			$name,
			func_num_args() != 3 ? null : new QuarkSessionSource($name, $provider, $user),
			'AuthorizationProvider for key ' . $name . ' does not configured'
		);
	}

	/**
	 * @param string $name
	 * @param IQuarkDataProvider $provider = null
	 * @param QuarkURI $uri = null
	 *
	 * @return QuarkModelSource
	 */
	public function DataProvider ($name, IQuarkDataProvider $provider = null, QuarkURI $uri = null) {
		return $this->_component(
			$name,
			func_num_args() == 3 && $provider != null && $uri != null ? new QuarkModelSource($name, $provider, $uri) : null,
			'DataProvider for key ' . $name . ' does not configured'
		);
	}

	/**
	 * @param string $name
	 * @param IQuarkExtensionConfig $config = null
	 *
	 * @return IQuarkExtensionConfig
	 */
	public function Extension ($name, IQuarkExtensionConfig $config = null) {
		return $this->_component(
			$name,
			$config,
			'Extension for key ' . $name . ' does not configured'
		);
	}

	/**
	 * @param IQuarkEnvironment $provider = null
	 *
	 * @return IQuarkEnvironment[]
	 */
	public function Environment (IQuarkEnvironment $provider = null) {
		return Quark::Environment($provider);
	}

	/**
	 * @param IQuarkApplicationSettingsModel $model = null
	 *
	 * @return QuarkModel|IQuarkApplicationSettingsModel
	 */
	public function &ApplicationSettings (IQuarkApplicationSettingsModel $model = null) {
		if (func_num_args() != 0 && $model != null)
			$this->_settings = new QuarkModel($model);
		else $this->_loadSettings();

		return $this->_settings;
	}

	/**
	 * @return bool
	 */
	private function _loadSettings () {
		$settings = QuarkModel::FindOne($this->_settings->Model(), $this->_settings->LoadCriteria());

		if ($settings == null || !($settings->Model() instanceof IQuarkApplicationSettingsModel)) return false;

		$this->_settings = $settings;
		return true;
	}

	/**
	 * @param $data
	 *
	 * @return bool
	 */
	public function UpdateApplicationSettings ($data = null) {
		if ($this->_settings == null) return false;

		$ok = $this->_loadSettings();

		if (func_num_args() != 0)
			$this->_settings->PopulateWith($data);

		return $ok ? $this->_settings->Save() : $this->_settings->Create();
	}

	/**
	 * @param string $path
	 *
	 * @return bool
	 */
	public function SharedResource ($path = '') {
		return Quark::Import($path);
	}

	/**
	 * @param string $component
	 * @param string $location = ''
	 *
	 * @return string
	 */
	public function Location ($component, $location = '') {
		if (func_num_args() == 2)
			$this->_location[$component] = $location;

		return isset($this->_location[$component]) ? $this->_location[$component] : '';
	}

	/**
	 * @param QuarkURI|string $uri = ''
	 *
	 * @return QuarkURI
	 */
	public function &WebHost ($uri = '') {
		if (func_num_args() != 0)
			$this->_webHost = QuarkURI::FromURI($uri);

		return $this->_webHost;
	}

	/**
	 * @param QuarkURI|string $uri = ''
	 *
	 * @return QuarkURI
	 */
	public function &ClusterControllerListen ($uri = '') {
		if (func_num_args() != 0)
			$this->_clusterControllerListen = QuarkURI::FromURI($uri);

		return $this->_clusterControllerListen;
	}

	/**
	 * @param QuarkURI|string $uri = ''
	 *
	 * @return QuarkURI
	 */
	public function &ClusterControllerConnect ($uri = '') {
		if (func_num_args() != 0)
			$this->_clusterControllerConnect = QuarkURI::FromURI($uri);

		return $this->_clusterControllerConnect;
	}

	/**
	 * @param QuarkURI|string $uri = ''
	 *
	 * @return QuarkURI
	 */
	public function &ClusterMonitor ($uri = '') {
		if (func_num_args() != 0)
			$this->_clusterMonitor = QuarkURI::FromURI($uri);

		return $this->_clusterMonitor;
	}

	/**
	 * @param string $key = ''
	 *
	 * @return string
	 */
	public function &ClusterKey ($key = '') {
		if (func_num_args() != 0)
			$this->_clusterKey = $key;

		return $this->_clusterKey;
	}

	/**
	 * @param QuarkURI|string $uri = ''
	 *
	 * @return QuarkURI
	 */
	public function &SelfHostedFPM ($uri = '') {
		if (func_num_args() != 0)
			$this->_selfHosted = QuarkURI::FromURI($uri);

		return $this->_selfHosted;
	}

	/**
	 * @param bool $allow = false
	 *
	 * @return bool
	 */
	public function AllowIndexFallback ($allow = false) {
		if (func_num_args() != 0)
			$this->_allowIndexFallback = $allow;

		return $this->_allowIndexFallback;
	}
}

/**
 * Interface IQuarkStackable
 *
 * @package Quark
 */
interface IQuarkStackable {
	/**
	 * @param string $name
	 */
	public function Stacked($name);
}

/**
 * Interface IQuarkEnvironment
 *
 * @package Quark
 */
interface IQuarkEnvironment extends IQuarkThread {
	/**
	 * @return bool
	 */
	public function Multiple();
}

/**
 * Trait QuarkEvent
 *
 * @package Quark
 */
trait QuarkEvent {
	/**
	 * @var array $_events
	 */
	private $_events = array();

	/**
	 * @param string $event
	 * @param callable $callback
	 */
	public function On ($event, callable $callback) {
		if (!isset($this->_events[$event]))
			$this->_events[$event] = array();

		$this->_events[$event][] = $callback;
	}

	/**
	 * @param string $event
	 *
	 * @return bool
	 */
	public function Trigger ($event) {
		return $this->TriggerArgs($event, array_slice(func_get_args(), 1));
	}

	/**
	 * @param string $name
	 * @param array $args
	 *
	 * @return bool
	 */
	public function TriggerArgs ($name, $args) {
		if (!isset($this->_events[$name])) return true;

		foreach ($this->_events[$name] as $w => &$worker)
			call_user_func_array($worker, $args);

		return true;
	}

	/**
	 * @param string $name
	 *
	 * @return callable[]
	 */
	public function &EventWorkers ($name) {
		if (!isset($this->_events[$name]))
			$this->_events[$name] = array();

		return $this->_events[$name];
	}

	/**
	 * @param $name
	 * @param IQuarkEventable $eventable
	 */
	public function Delegate ($name, IQuarkEventable $eventable) {
		$this->_events[$name] = $eventable->EventWorkers($name);
	}
}

/**
 * Interface IQuarkEventable
 *
 * @package Quark
 */
interface IQuarkEventable {
	/**
	 * @param string $event
	 * @param callable $callback
	 */
	public function On($event, callable $callback);

	/**
	 * All specified arguments after $event will be applied to callback
	 *
	 * @param string $event
	 *
	 * @return bool
	 */
	public function Trigger($event);

	/**
	 * @param string $event
	 *
	 * @return callable[]
	 */
	public function EventWorkers($event);
}

/**
 * Class QuarkFPMEnvironment
 *
 * @package Quark
 */
class QuarkFPMEnvironment implements IQuarkEnvironment {
	const SELF_HOSTED = 'http://127.0.0.1:25080';

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
	 * @param string $status = ''
	 *
	 * @return string
	 */
	public function DefaultNotFoundStatus ($status = '') {
		if (func_num_args() == 1)
			$this->_statusNotFound = $status;

		return $this->_statusNotFound;
	}

	/**
	 * @param string $status = ''
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
	 * @param IQuarkIOProcessor $processor = null
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
		Quark::CurrentEnvironment($this);

		$offset = Quark::Config()->WebHost()->path;

		$service = new QuarkService(
			substr($_SERVER['REQUEST_URI'], ($offset != '' ? (int)strpos($_SERVER['REQUEST_URI'], $offset) : 0) + strlen($offset)),
			$this->_processorRequest,
			$this->_processorResponse
		);

		$uri = QuarkURI::FromURI(Quark::NormalizePath($_SERVER['REQUEST_URI'], false));
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

		$service->Input()->Method(ucfirst(strtolower($_SERVER['REQUEST_METHOD'])));
		$service->Input()->Headers($headers);

		QuarkView::ExpectedLanguage($service->Input()->ExpectedLanguage());

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

		ob_start();

		echo QuarkHTTPServer::ServicePipeline($service, $input);

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
			return $this->_status($exception, $exception->Status(), $exception->log);

		if ($exception instanceof \Exception)
			return Quark::Log('Common exception: ' . $exception->getMessage() . "\r\n at " . $exception->getFile() . ':' . $exception->getLine(), Quark::LOG_FATAL);

		return true;
	}

	/**
	 * @param QuarkException $exception
	 * @param string $status
	 * @param string $log = ''
	 *
	 * @return bool|int
	 */
	private function _status ($exception, $status, $log = '') {
		ob_start();
		header($_SERVER['SERVER_PROTOCOL'] . ' ' . $status);
		ob_end_flush();

		return Quark::Log('[' . $_SERVER['REQUEST_URI'] . '] ' . (func_num_args() == 3 ? $log : $exception->message), $exception->lvl);
	}
}

/**
 * Class QuarkCLIEnvironment
 *
 * @package Quark
 */
class QuarkCLIEnvironment implements IQuarkEnvironment {
	/**
	 * @var QuarkTask[] $_tasks
	 */
	private $_tasks = array();

	/**
	 * @var string $_start = null
	 */
	private $_start = null;

	/**
	 * @var bool $_started = false
	 */
	private $_started = false;

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
	 * @param string $uri = ''
	 *
	 * @return string
	 */
	public function ApplicationStart ($uri = '') {
		if (func_num_args() != 0)
			$this->_start = $uri;

		return $this->_start;
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
		Quark::CurrentEnvironment($this);

		if ($argc > 1) {
			if ($argv[1] == QuarkTask::PREDEFINED) {
				if (!isset($argv[2]))
					throw new QuarkArchException('Predefined scenario not selected');

				$class = '\\Quark\\Scenarios\\' . str_replace('/', '\\', $argv[2]);

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
			if (!$this->_started && $this->_start !== null) {
				$this->_started = true;
				$service = (new QuarkService('/' . $this->_start))->Service();

				if (!($service instanceof IQuarkApplicationStartTask))
					throw new QuarkArchException('Class ' . get_class($service) . ' is not an IQuarkApplicationStartTask');

				$service->ApplicationStartTask($argc, $argv);
			}

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
 * Interface IQuarkNetworkTransport
 *
 * @package Quark
 */
interface IQuarkNetworkTransport {
	/**
	 * @param QuarkClient &$client
	 *
	 * @return mixed
	 */
	public function EventConnect(QuarkClient &$client);

	/**
	 * @param QuarkClient &$client
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function EventData(QuarkClient &$client, $data);

	/**
	 * @param QuarkClient &$client
	 *
	 * @return mixed
	 */
	public function EventClose(QuarkClient &$client);

	/**
	 * @param string $data
	 *
	 * @return string
	 */
	public function Send($data);
}

/**
 * Interface IQuarkNetworkProtocol
 *
 * @package Quark
 */
interface IQuarkNetworkProtocol {
	/**
	 * @return IQuarkNetworkTransport
	 */
	public function Transport();

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
interface IQuarkExtensionConfig extends IQuarkStackable {
	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance();
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
}

/**
 * Interface IQuarkAuthorizableServiceWithAuthentication
 *
 * @package Quark
 */
interface IQuarkAuthorizableServiceWithAuthentication extends IQuarkAuthorizableService {
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
 * Interface IQuarkHTTPService
 *
 * @package Quark
 */
interface IQuarkHTTPService extends IQuarkService { }

/**
 * Interface IQuarkAnyService
 *
 * @package Quark
 */
interface IQuarkAnyService extends IQuarkHTTPService {
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
interface IQuarkGetService extends IQuarkHTTPService {
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
interface IQuarkPostService extends IQuarkHTTPService {
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
 * Interface IQuarkSignedService
 *
 * @package Quark
 */
interface IQuarkSignedService { }

/**
 * Interface IQuarkSignedAnyService
 *
 * @package Quark
 */
interface IQuarkSignedAnyService extends IQuarkSignedService {
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
interface IQuarkSignedGetService extends IQuarkSignedService {
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
interface IQuarkSignedPostService extends IQuarkSignedService {
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
		if ($this->_service instanceof IQuarkScheduledTask && !$this->_service->LaunchCriteria($this->_launched)) return true;

		$out = true;

		try {
			$this->_service->Task($argc, $argv);
		}
		catch (\Exception $e) {
			$out = QuarkException::ExceptionHandler($e);
		}

		$this->_launched = QuarkDate::Now();

		return $out;
	}

	/**
	 * @param array $args
	 * @param string $queue
	 * @param IQuarkNetworkProtocol $protocol
	 *
	 * @return mixed
	 *
	 * @throws QuarkArchException
	 */
	public function AsyncLaunch ($args = [], $queue = self::QUEUE, IQuarkNetworkProtocol $protocol = null) {
		if (!($this->_service instanceof IQuarkTask))
			throw new QuarkArchException('Trying to async launch service ' . ($this->_service ? get_class($this->_service) : 'null') . ' which is not an IQuarkTask');

		array_unshift($args, Quark::EntryPoint(), $this->Name());

		$out = $this->_service instanceof IQuarkAsyncTask
			? $this->_service->OnLaunch(sizeof($args), $args)
			: null;

		$this->_io->Data($args);

		$client = new QuarkClient($queue, ($protocol ? $protocol->Transport() : $this->Transport()), null, 30);

		$client->On(QuarkClient::EVENT_CONNECT, function (QuarkClient $client) {
			$this->_io->Data(array(
				'task' => get_class($this->_service),
				'args' => $this->_io->Data()
			));

			return $client->Send($this->_io->SerializeRequestBody()) && $client->Close();
		});

		if (!$client->Connect()) return false;

		return $out;
	}

	/**
	 * @param QuarkURI|string $listen = self::QUEUE
	 * @param IQuarkNetworkProtocol $protocol = null
	 * @param int $tick = QuarkThreadSet::TICK (microseconds)
	 *
	 * @return bool
	 */
	public static function AsyncQueue ($listen = self::QUEUE, IQuarkNetworkProtocol $protocol = null, $tick = QuarkThreadSet::TICK) {
		$task = new QuarkTask();
		$server = new QuarkServer($listen, $protocol ? $protocol->Transport() : $task->Transport());

		/** @noinspection PhpUnusedParameterInspection
		 */
		$server->On(QuarkClient::EVENT_DATA, function (QuarkClient $client, $data) use (&$task) {
			$json = $task->_io->Processor()->Decode($data);

			if (!isset($json->task) || !isset($json->args)) return;

			$args = (array)$json->args;
			$class = $json->task;
			$service = new $class();

			if ($service instanceof IQuarkTask)
				$service->Task(sizeof($args), $args);

			unset($service, $class, $args, $json, $task);
		});

		if (!$server->Bind()) return false;

		QuarkThreadSet::Queue(function () use ($server) {
			return $server->Pipe();
		}, $tick);

		return true;
	}

	/**
	 * @return IQuarkNetworkTransport
	 */
	public function Transport () {
		return new QuarkTCPNetworkTransport(array($this->_io->Processor(), 'Batch'));
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
 * Interface IQuarkApplicationStartTask
 *
 * @package Quark
 */
interface IQuarkApplicationStartTask extends IQuarkService {
	/**
	 * @param int $argc
	 * @param array $argv
	 *
	 * @return mixed
	 */
	public function ApplicationStartTask($argc, $argv);
}

/**
 * Class QuarkThreadSet
 *
 * @package Quark
 */
class QuarkThreadSet {
	const TICK = 10000;

	const EVENT_BEFORE_INVOKE = 'invoke.before';
	const EVENT_AFTER_INVOKE = 'invoke.after';

	use QuarkEvent;

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

		$this->Trigger(self::EVENT_BEFORE_INVOKE);

		foreach ($this->_threads as &$thread) {
			if (!($thread instanceof IQuarkThread) || !$thread->UsageCriteria()) continue;

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

		$this->Trigger(self::EVENT_AFTER_INVOKE);

		return (bool)$run;
	}

	/**
	 * @param int $sleep = self::TICK (microseconds)
	 */
	public function Pipeline ($sleep = self::TICK) {
		self::Queue(function () { return $this->Invoke(); }, $sleep);
	}

	/**
	 * @param callable $pipe
	 * @param int $sleep = self::TICK (microseconds)
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
 * Class QuarkTimer
 *
 * @package Quark
 */
class QuarkTimer {
	const ONE_SECOND = 1;
	const ONE_MINUTE = 60;
	const ONE_HOUR = 3600;

	/**
	 * @var QuarkTimer[] $_timers
	 */
	private static $_timers = array();

	/**
	 * @var int $_time
	 */
	private $_time;

	/**
	 * @var callable $_callback
	 */
	private $_callback;

	/**
	 * @var QuarkDate $_last
	 */
	private $_last;

	/**
	 * @var string $_id
	 */
	private $_id;

	/**
	 * @var null $_null = null
	 */
	private static $_null = null;

	/**
	 * @param int $time (seconds)
	 * @param callable(QuarkTimer, ..$) $callback
	 * @param int $offset = 0
	 * @param string $id = ''
	 */
	public function __construct ($time, callable $callback, $offset = 0, $id = '') {
		$this->_time = $time > $offset ? $time - $offset : $time;
		$this->_callback = $callback;
		$this->_last = QuarkDate::Now();
		$this->_id = func_num_args() == 4 ? $id : Quark::GuID();

		self::$_timers[] = $this;
	}

	/**
	 * @param int $time = 0
	 *
	 * @return int
	 */
	public function Time ($time = 0) {
		if (func_num_args() != 0)
			$this->_time = $time;

		return $this->_time;
	}

	/**
	 * @param callable $callback = null
	 *
	 * @return callable
	 */
	public function Callback (callable $callback = null) {
		if (func_num_args() != 0)
			$this->_callback = $callback;

		return $this->_callback;
	}

	/**
	 * @return QuarkDate
	 */
	public function Last () {
		return $this->_last;
	}

	/**
	 * @return string
	 */
	public function ID () {
		return $this->_id;
	}

	/**
	 * Invoke timer callback
	 */
	public function Invoke () {
		$now = QuarkDate::Now();

		if (!$this->_last->Expired($now, $this->_time)) return;

		$this->_last = $now;

		call_user_func_array($this->_callback, array(&$this) + func_get_args());
	}

	/**
	 * Destroy timer
	 */
	public function Destroy () {
		foreach (self::$_timers as $i => &$timer)
			if ($timer->_id == $this->_id)
				unset(self::$_timers[$i]);
	}

	/**
	 * @return QuarkTimer[]
	 */
	public static function Timers () {
		return self::$_timers;
	}

	/**
	 * @param string $id
	 *
	 * @return QuarkTimer
	 */
	public static function &Get ($id) {
		foreach (self::$_timers as $i => &$timer)
			if ($timer->_id == $id) return $timer;

		return self::$_null;
	}
}

/**
 * Interface IQuarkThread
 *
 * @package Quark
 */
interface IQuarkThread {
	/**
	 * @return bool
	 */
	public function UsageCriteria();

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
	 *
	 * @return mixed
	 */
	public function Stream(QuarkDTO $request, QuarkSession $session);
}

/**
 * Interface IQuarkStreamNetwork
 *
 * @package Quark
 */
interface IQuarkStreamNetwork extends IQuarkService {
	/**
	 * @param QuarkDTO $request
	 *
	 * @return mixed
	 */
	public function StreamNetwork(QuarkDTO $request);
}

/**
 * Interface IQuarkStreamConnect
 *
 * @package Quark
 */
interface IQuarkStreamConnect extends IQuarkService {
	/**
	 * @param QuarkSession $session
	 *
	 * @return mixed
	 */
	public function StreamConnect(QuarkSession $session);
}

/**
 * Interface IQuarkStreamClose
 *
 * @package Quark
 */
interface IQuarkStreamClose extends IQuarkService {
	/**
	 * @param QuarkSession $session
	 *
	 * @return mixed
	 */
	public function StreamClose(QuarkSession $session);
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
	 *
	 * @return mixed
	 */
	public function StreamUnknown(QuarkDTO $request, QuarkSession $session);
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
	 * @param QuarkCluster $cluster
	 *
	 * @return mixed
	 */
	public function ControllerStream(QuarkDTO $request, QuarkCluster $cluster);
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
 * Trait QuarkServiceBehavior
 *
 * @package Quark
 */
trait QuarkServiceBehavior {
	use QuarkContainerBehavior;

	/** @noinspection PhpUnusedPrivateMethodInspection
	 * @return QuarkModel
	 */
	private function _envelope () {
		return new QuarkService($this);
	}

	/**
	 * @param IQuarkService $service = null
	 *
	 * @return string
	 */
	public function URL (IQuarkService $service = null) {
		return $this->__call('URL', func_get_args());
	}

	/**
	 * @return QuarkDTO
	 */
	public function Input () {
		return $this->__call('Input', func_get_args());
	}
}

/**
 * Trait QuarkStreamBehavior
 *
 * @package Quark
 */
trait QuarkStreamBehavior {
	use QuarkServiceBehavior;

	/**
	 * @param QuarkDTO|object|array $data
	 * @param IQuarkStreamNetwork $service = null
	 *
	 * @return bool
	 */
	public function Broadcast ($data, IQuarkStreamNetwork $service = null) {
		$env = Quark::CurrentEnvironment();
		$url = $this->URL($service);

		if ($env instanceof QuarkStreamEnvironment) $out = $env->BroadcastNetwork($url, $data);
		else $out = QuarkStreamEnvironment::ControllerCommand(
			QuarkStreamEnvironment::COMMAND_BROADCAST,
			QuarkStreamEnvironment::Payload(QuarkStreamEnvironment::PACKAGE_REQUEST, $url, $data)
		);

		unset($url, $env, $service, $data);

		return $out;
	}

	/**
	 * @param callable(QuarkSession $client) $sender = null
	 *
	 * @return bool
	 *
	 * @throws QuarkArchException
	 */
	public function Event (callable $sender = null) {
		$env = Quark::CurrentEnvironment();

		if ($env instanceof QuarkStreamEnvironment) return $env->BroadcastLocal($this->URL(), $sender);
		else throw new QuarkArchException('QuarkStreamBehavior: the `Event` method cannot be called in a non-stream environment');
	}

	/**
	 * @return QuarkCluster
	 */
	public function &Cluster () {
		$env = Quark::CurrentEnvironment();

		if ($env instanceof QuarkStreamEnvironment)
			return $env->Cluster();

		return $this->_null;
	}
}

/**
 * Trait QuarkCLIBehavior
 *
 * @package Quark
 */
trait QuarkCLIBehavior {
	/**
	 * @var array $-shellOutput = []
	 */
	private $_shellOutput = array();

	/**
	 * @param string $command = ''
	 * @param string[] &$output = []
	 * @param int &$status = 0
	 *
	 * @return bool
	 */
	public function Shell ($command = '', &$output = [], &$status = 0) {
		if (strlen($command) == 0) return false;

		exec($command, $output, $status);
		$this->_shellOutput = $output;

		return $status == 0;
	}

	/**
	 * @return array
	 */
	public function ShellOutput () {
		return $this->_shellOutput;
	}

	/**
	 * @param IQuarkAsyncTask $task
	 * @param array $args = []
	 * @param string $queue = QuarkTask::QUEUE
	 * @param IQuarkNetworkProtocol $protocol = null
	 *
	 * @return mixed
	 *
	 * @throws QuarkArchException
	 */
	public function AsyncTask (IQuarkAsyncTask $task, $args = [], $queue = QuarkTask::QUEUE, IQuarkNetworkProtocol $protocol = null) {
		$cmd = new QuarkTask($task);

		return $cmd->AsyncLaunch($args, $queue, $protocol);
	}
}

/**
 * Class QuarkService
 *
 * @package Quark
 */
class QuarkService implements IQuarkContainer {
	/**
	 * @var IQuarkService|IQuarkAuthorizableService|IQuarkServiceWithAccessControl|IQuarkServiceWithRequestBackbone $_service
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
	 * @param string $uri = ''
	 *
	 * @return IQuarkService
	 *
	 * @throws QuarkArchException
	 * @throws QuarkHTTPException
	 */
	public static function Resolve ($uri = '') {
		if ($uri == 'index.php') $uri = '';

		$route = QuarkURI::FromURI(Quark::NormalizePath($uri), false);
		$path = QuarkURI::ParseRoute($route->path);

		$buffer = array();

		foreach ($path as $item)
			if (strlen(trim($item)) != 0)
				$buffer[] = ucfirst(trim($item));

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

		if (Quark::Config()->AllowIndexFallback() && !file_exists($path)) {
			$service = 'Index';
			$path = self::_bundle($service);
		}

		if (!file_exists($path))
			throw QuarkHTTPException::ForStatus(QuarkDTO::STATUS_404_NOT_FOUND, 'Unknown service file ' . $path);

		$class = str_replace('/', '\\', '/Services/' . $service . 'Service');
		$bundle = new $class();

		if (!($bundle instanceof IQuarkService))
			throw new QuarkArchException('Class ' . $class . ' is not an IQuarkService');

		unset($class, $length, $path, $service, $index, $route);

		return $bundle;
	}

	/**
	 * @param IQuarkService|string $uri
	 * @param IQuarkIOProcessor $input = null
	 * @param IQuarkIOProcessor $output = null
	 *
	 * @throws QuarkArchException
	 * @throws QuarkHTTPException
	 */
	public function __construct ($uri, IQuarkIOProcessor $input = null, IQuarkIOProcessor $output = null) {
		if ($uri instanceof IQuarkService) {
			$this->_service = $uri;
			$class = get_class($this->_service);
			$uri = substr(substr($class, 8), 0, -7);
		}
		else $this->_service = self::Resolve($uri);

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
			$this->_input->Processor($this->_service->RequestProcessor());

		if ($this->_service instanceof IQuarkServiceWithCustomResponseProcessor)
			$this->_output->Processor($this->_service->ResponseProcessor());

		Quark::Container($this);
	}

	/**
	 * @return IQuarkPrimitive
	 */
	public function &Primitive () {
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
	 * @param IQuarkService $service = null
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
	 * @param bool $checkSignature = false
	 *
	 * @return bool
	 *
	 * @throws QuarkArchException
	 */
	public function Authorize ($checkSignature = false) {
		if (!($this->_service instanceof IQuarkAuthorizableService)) return true;

		$service = get_class($this->_service);
		$provider = $this->_service->AuthorizationProvider($this->_input);

		if ($provider == null)
			throw new QuarkArchException('Service ' . $service . ' does not specified AuthorizationProvider');

		$this->_session = QuarkSession::Init($provider, $this->_input);

		if (!($this->_service instanceof IQuarkAuthorizableServiceWithAuthentication) && $this->_session != null) return true;

		$criteria = $this->_service->AuthorizationCriteria($this->_input, $this->_session);

		if ($criteria !== true) {
			$this->_output->Merge($this->_service->AuthorizationFailed($this->_input, $criteria));

			return false;
		}

		if (!$checkSignature) return true;
		if (!($this->_service instanceof IQuarkSignedService)) return true;

		$method = ucfirst(strtolower($this->_input->Method()));
		$action = 'SignatureCheckFailedOn' . $method;

		if (!method_exists($this->_service, $action)) return true;

		$sign = $this->_session->Signature();

		if ($sign != '' && $this->_input->Signature() == $sign) return true;

		$this->_output->Merge($this->_service->$action($this->_input));

		return false;
	}

	/**
	 * @param string $method
	 * @param array $args = []
	 * @param bool $session = false
	 *
	 * @throws QuarkArchException
	 */
	public function Invoke ($method, $args = [], $session = false) {
		$empty = $this->_session == null;

		if ($empty)
			$this->_session = new QuarkSession();

		if ($session)
			$args[] = &$this->_session;

		if (!method_exists($this->_service, $method))
			throw new QuarkArchException('Method ' . $method . ' is not allowed for service ' . get_class($this->_service));

		$output = call_user_func_array(array(&$this->_service, $method), $args);

		$this->_output->Merge($output);

		if ($this->_service instanceof IQuarkAuthorizableService && !$empty)
			$this->_output->Merge($this->_session->Output(), false);
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
 * Trait QuarkContainerBehavior
 *
 * @package Quark
 */
trait QuarkContainerBehavior {
	/**
	 * @var null $_null = null
	 */
	protected $_null = null;

	/**
	 * @return IQuarkContainer
	 */
	private function _envelope () {
		return null;
	}

	/**
	 * @param $method
	 * @param $args
	 *
	 * @return mixed
	 */
	public function __call ($method, $args) {
		/**
		 * @var IQuarkPrimitive|QuarkContainerBehavior $this
		 */
		$container = Quark::ContainerOf($this->ObjectID());

		if ($container == null)
			$container = $this->_envelope();

		return method_exists($container, $method)
			? call_user_func_array(array($container, $method), $args)
			: null;
	}

	/**
	 * @return string
	 */
	public function ObjectID () {
		return spl_object_hash($this);
	}

	/**
	 * @return IQuarkEnvironment
	 */
	public function &CurrentEnvironment () {
		return Quark::CurrentEnvironment();
	}

	/**
	 * @param IQuarkEnvironment $provider
	 *
	 * @return bool
	 */
	public function EnvironmentIs (IQuarkEnvironment $provider) {
		return $this->CurrentEnvironment() instanceof $provider;
	}

	/**
	 * @return bool
	 */
	public function EnvironmentIsFPM () {
		return $this->CurrentEnvironment() instanceof QuarkFPMEnvironment;
	}

	/**
	 * @return bool
	 */
	public function EnvironmentIsStream () {
		return $this->CurrentEnvironment() instanceof QuarkStreamEnvironment;
	}

	/**
	 * @return bool
	 */
	public function EnvironmentIsCLI () {
		return $this->CurrentEnvironment() instanceof QuarkCLIEnvironment;
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
	 * @return IQuarkPrimitive|QuarkContainerBehavior
	 */
	public function &Primitive();
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
	 * @param object|array $source = null
	 * @param object|array $min = null
	 * @param object|array $max = null
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
	 * @param mixed $backbone = []
	 * @param callable $iterator = null
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
	 * @param string $key = ''
	 * @param $parent = null
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
	 * @param $source
	 * @param $type
	 *
	 * @return bool
	 */
	public static function IsArrayOf ($source, $type) {
		if (!self::isIterative($source)) return false;

		$scalar = is_scalar($type);
		$typeof = gettype($type);

		foreach ($source as $item) {
			if ($scalar && gettype($item) != $typeof) return false;
			if (!$scalar && !($item instanceof $source)) return false;
		}

		return true;
	}

	/**
	 * @param $class
	 * @param string|array $interface = ''
	 * @param bool $silent = false
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
	 * @param callable $filter = null
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
	 * @param string $file
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
	 * @param $default = null
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
	 * @param object|array $source = null
	 *
	 * @return object|array
	 */
	public function Source ($source = null) {
		if (func_num_args() != 0)
			$this->_source = $source;

		return $this->_source;
	}

	/**
	 * @param object|array $min = null
	 *
	 * @return object|array
	 */
	public function Minimal ($min = null) {
		if (func_num_args() != 0)
			$this->_min = $min;

		return $this->_min;
	}

	/**
	 * @param object|array $max = null
	 *
	 * @return object|array
	 */
	public function Maximal ($max = null) {
		if (func_num_args() != 0)
			$this->_max = $max;

		return $this->_max;
	}

	/**
	 * @param callable $builder = null
	 *
	 * @return object
	 */
	public function Build ($builder = null) {
		$builder();
		return new \StdClass();
	}
}

/**
 * Trait QuarkViewBehavior
 *
 * @package Quark
 */
trait QuarkViewBehavior {
	use QuarkContainerBehavior;

	/** @noinspection PhpUnusedPrivateMethodInspection
	 * @return QuarkModel
	 */
	private function _envelope () {
		return new QuarkView($this);
	}

	/**
	 * @param IQuarkViewModel $view = null
	 *
	 * @return mixed
	 */
	public function Child (IQuarkViewModel $view = null) {
		return $this->__call('Child', func_get_args());
	}

	/**
	 * @param IQuarkViewModel $view = null
	 *
	 * @return mixed
	 */
	public function Layout (IQuarkViewModel $view = null) {
		return $this->__call('Layout', func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function User () {
		return $this->__call('User', func_get_args());
	}

	/**
	 * @param string $uri
	 * @param bool $signed = false
	 *
	 * @return string
	 */
	public function Link ($uri, $signed = false) {
		return $this->__call('Link', func_get_args());
	}

	/**
	 * @param bool $field = true
	 *
	 * @return mixed
	 */
	public function Signature ($field = true) {
		return $this->__call('Signature', func_get_args());
	}

	/**
	 * @param string $name = ''
	 *
	 * @return IQuarkExtension
	 */
	public function Extension ($name = '') {
		return $this->__call('Extension', func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function Compile () {
		return $this->__call('Compile', func_get_args());
	}
}

/**
 * Class QuarkView
 *
 * @package Quark
 */
class QuarkView implements IQuarkContainer {
	const FIELD_ERROR_TEMPLATE = '<div class="quark-message warn fa fa-warning"><p class="content">{error}</p></div>';

	/**
	 * @var IQuarkViewModel|IQuarkViewModelWithResources $_view = null
	 */
	private $_view = null;

	/**
	 * @var IQuarkViewModel|IQuarkViewModelWithResources $_child = null
	 */
	private $_child = null;

	/**
	 * @var QuarkView $_layout = null
	 */
	private $_layout = null;

	/**
	 * @var string $_file = ''
	 */
	private $_file = '';

	/**
	 * @var object|array $_vars = []
	 */
	private $_vars = array();

	/**
	 * @var IQuarkViewResource[] $_resources = []
	 */
	private $_resources = array();

	/**
	 * @var string $_html = ''
	 */
	private $_html = '';

	/**
	 * @var bool $_inline = false
	 */
	private $_inline = false;

	/**
	 * @var null $_null = null
	 */
	private $_null = null;

	/**
	 * @var string $_language = QuarkLocalizedString::LANGUAGE_ANY
	 */
	private $_language = QuarkLocalizedString::LANGUAGE_ANY;

	/**
	 * @var string $_languageExpected = QuarkLocalizedString::LANGUAGE_ANY
	 */
	private static $_languageExpected = QuarkLocalizedString::LANGUAGE_ANY;

	/**
	 * @param IQuarkViewModel|QuarkViewBehavior $view
	 * @param QuarkDTO|object|array $vars = []
	 * @param IQuarkViewResource[] $resources = []
	 *
	 * @throws QuarkArchException
	 */
	public function __construct (IQuarkViewModel $view = null, $vars = [], $resources = []) {
		if ($view == null) return;

		$this->_view = $view;
		$this->_file = Quark::NormalizePath(Quark::Host() . '/' . Quark::Config()->Location(QuarkConfig::VIEWS) . '/' . $this->_view->View() . '.php', false);

		if (!is_file($this->_file))
			$this->_file = $this->_view->View();

		if (!is_file($this->_file))
			throw new QuarkArchException('Unknown view file ' . $this->_file);

		$vars = $this->Vars($vars);

		foreach ($vars as $key => $value)
			$this->_view->$key = $value;

		$this->_resources = $resources;
		$this->_language = self::$_languageExpected;

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
		if ($this->_view != null && method_exists($this->_view, $method))
			return call_user_func_array(array($this->_view, $method), $args);

		if ($this->_child != null && method_exists($this->_child, $method))
			return call_user_func_array(array($this->_child, $method), $args);

		if ($this->_layout != null && method_exists($this->_layout->ViewModel(), $method))
			return call_user_func_array(array($this->_layout, $method), $args);

		throw new QuarkArchException('Method ' . $method . ' not exists in ' . get_class($this->_view) . ' environment');
	}

	/**
	 * @param bool $obfuscate = true
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
	 * @param bool $inline = false
	 *
	 * @return bool
	 */
	public function InlineStyles ($inline = false) {
		if (func_num_args() != 0)
			$this->_inline = $inline;

		return $this->_inline;
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function ResourceList () {
		if ($this->_view instanceof IQuarkViewModelWithResources) {
			$resources = $this->_view->Resources();

			if (!is_array($resources))
				return $this->_resources;

			foreach ($resources as $resource)
				$this->_resource($resource);
		}

		if ($this->_view instanceof IQuarkViewModelWithComponents) {
			$css = $this->_view->ViewStylesheet();
			$js = $this->_view->ViewController();

			if ($css !== null) {
				if ($css instanceof IQuarkViewResource)
					$this->_resource($css);
				else $this->_resources[] = QuarkProjectViewResource::CSS($css);
			}

			if ($js !== null) {
				if ($js instanceof IQuarkViewResource)
					$this->_resource($js);
				else $this->_resources[] = QuarkProjectViewResource::JS($js);
			}
		}

		return $this->_resources;
	}

	/**
	 * @param IQuarkViewResource|IQuarkViewResourceWithDependencies $resource
	 *
	 * @return QuarkView
	 *
	 * @throws QuarkArchException
	 */
	private function _resource (IQuarkViewResource $resource) {
		if ($resource instanceof IQuarkViewResourceWithDependencies)
			$this->_resource_dependencies($resource->Dependencies(), 'ViewResource ' . get_class($resource) . ' specified invalid value for `Dependencies`. Expected array of IQuarkViewResource.');

		if (!$this->_resource_loaded($resource))
			$this->_resources[] = $resource;

		if ($resource instanceof IQuarkViewResourceWithBackwardDependencies)
			$this->_resource_dependencies($resource->BackwardDependencies(), 'ViewResource ' . get_class($resource) . ' specified invalid value for `BackwardDependencies`. Expected array of IQuarkViewResource.');

		return $this;
	}

	/**
	 * @param IQuarkViewResource[] $resources
	 * @param string $error
	 *
	 * @throws QuarkArchException
	 */
	private function _resource_dependencies ($resources = [], $error = '') {
		if (!is_array($resources))
			throw new QuarkArchException($error);

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

	/**
	 * @param IQuarkViewResource $dependency
	 *
	 * @return bool
	 */
	private function _resource_loaded (IQuarkViewResource $dependency) {
		if ($dependency instanceof IQuarkMultipleViewResource) return false;

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
	 * @param string $uri
	 * @param bool $signed = false
	 *
	 * @return string
	 */
	public function Link ($uri, $signed = false) {
		return Quark::WebLocation($uri . ($signed ? QuarkURI::AppendQuery($uri, array(
				QuarkDTO::KEY_SIGNATURE => $this->Signature(false)
			)) : ''));
	}

	/**
	 * @param bool $field = true
	 *
	 * @return string
	 */
	public function Signature ($field = true) {
		$sign = QuarkSession::Current() ? QuarkSession::Current()->Signature() : '';

		return $field ? '<input type="hidden" name="' . QuarkDTO::KEY_SIGNATURE . '" value="' . $sign . '" />' : $sign;
	}

	/**
	 * @return QuarkConfig
	 */
	public function Config () {
		return Quark::Config();
	}

	/**
	 * @param string $name = ''
	 *
	 * @return IQuarkExtension
	 */
	public function Extension ($name = '') {
		$ext = Quark::Config()->Extension($name);

		return $ext ? $ext->ExtensionInstance() : null;
	}

	/**
	 * @return QuarkModel
	 */
	public function User () {
		return QuarkSession::Current() ? QuarkSession::Current()->User() : null;
	}

	/**
	 * @param IQuarkViewModel $view = null
	 * @param array|object $vars = []
	 * @param array $resources = []
	 *
	 * @return QuarkView
	 */
	public function Layout (IQuarkViewModel $view = null, $vars = [], $resources = []) {
		if (func_num_args() != 0 && $view != null) {
			$this->_layout = new QuarkView($view, $vars, $resources);
			$this->_layout->View($this->Compile());
			$this->_layout->Child($this->_view);
			$this->_language = $this->_layout->Language();
		}

		return $this->_layout;
	}

	/**
	 * @param string $html = ''
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
	 * @param array|object $vars = []
	 *
	 * @return QuarkView
	 */
	public static function InLayout (IQuarkViewModel $view, IQuarkViewModel $layout, $vars = []) {
		$inline = new QuarkView($view, $vars);

		return $inline->Layout($layout, $vars, $inline->ResourceList());
	}

	/**
	 * @param QuarkDTO|object|array $params
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
	 * @param string $language = QuarkLocalizedString::LANGUAGE_ANY
	 *
	 * @return string
	 */
	public function Language ($language = QuarkLocalizedString::LANGUAGE_ANY) {
		if (func_num_args() != 0)
			$this->_language = $language;

		return $this->_language;
	}

	/**
	 * @param string $language
	 *
	 * @return string
	 */
	public static function ExpectedLanguage ($language = QuarkLocalizedString::LANGUAGE_ANY) {
		if (func_num_args() != 0)
			self::$_languageExpected = $language;

		return self::$_languageExpected;
	}

	/**
	 * @param QuarkModel $model
	 * @param string $field
	 * @param string $template = self::FIELD_ERROR_TEMPLATE
	 *
	 * @return string
	 */
	public function FieldError (QuarkModel $model = null, $field = '', $template = self::FIELD_ERROR_TEMPLATE) {
		$errors = $model->RawValidationErrors();

		foreach ($errors as $error)
			if ($error->Key() == $field)
				return str_replace('{error}', $error->Value()->Of($this->_language), $template);

		return '';
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
		$out = ob_get_clean();

		if ($this->_inline) {
			if (preg_match_all('#id="(.*)"#Uis', $out, $ids, PREG_SET_ORDER)) {
				foreach ($ids as $id) {
					$css = '';

					if (preg_match_all('#\#' . $id[1] . '{(.*)}#Uis', $out, $id_css, PREG_SET_ORDER)) {

						foreach ($id_css as $id_c) {
							$css .= $id_c[1];
							$out = str_replace('#' . $id[1] . '{' . $id_c[1] . '}', '', $out);
						}
					}

					$out = str_replace('id="' . $id[1] . '"', 'id="' . $id[1] . '" style="' . $css . '"', $out);
				}
			}
		}

		return $out;
	}

	/**
	 * @param IQuarkViewModel $view = null
	 *
	 * @return IQuarkViewModel
	 */
	public function ViewModel (IQuarkViewModel $view = null) {
		if (func_num_args() == 1)
			$this->_view = $view;

		return $this->_view;
	}

	/**
	 * @param IQuarkViewModel $view = null
	 *
	 * @return IQuarkViewModel
	 */
	public function Child (IQuarkViewModel $view = null) {
		if (func_num_args() == 1)
			$this->_child = $view;

		return $this->_child;
	}

	/**
	 * @return IQuarkPrimitive
	 */
	public function &Primitive () {
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
 * Interface IQuarkViewModelWithResources
 *
 * @package Quark
 */
interface IQuarkViewModelWithResources extends IQuarkViewModel {
	/**
	 * @return IQuarkViewResource[]
	 */
	public function Resources();
}

/**
 * Interface IQuarkViewModelWithComponents
 *
 * @package Quark
 */
interface IQuarkViewModelWithComponents extends IQuarkViewModel {
	/**
	 * @return IQuarkViewResource|string
	 */
	public function ViewStylesheet();

	/**
	 * @return IQuarkViewResource|string
	 */
	public function ViewController();
}

/**
 * Interface IQuarkViewResource
 *
 * @package Quark
 */
interface IQuarkViewResource {
	/**
	 * @return IQuarkViewResourceType
	 */
	public function Type();

	/**
	 * @return string
	 */
	public function Location();
}

/**
 * Interface IQuarkViewResourceWithDependencies
 *
 * @package Quark
 */
interface IQuarkViewResourceWithDependencies {
	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies();
}

/**
 * Interface IQuarkViewResourceWithBackwardDependencies
 *
 * @package Quark
 */
interface IQuarkViewResourceWithBackwardDependencies {
	/**
	 * @return IQuarkViewResource[]
	 */
	public function BackwardDependencies();
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
 * Interface IQuarkMultipleViewResource
 *
 * @package Quark
 */
interface IQuarkMultipleViewResource { }

/**
 * Class QuarkProjectViewResource
 *
 * @package Quark
 */
class QuarkProjectViewResource implements IQuarkViewResource, IQuarkLocalViewResource {
	/**
	 * @var IQuarkViewResourceType $_type = null
	 */
	private $_type = null;

	/**
	 * @var string $_location = ''
	 */
	private $_location = '';

	/**
	 * @var bool $_minimize = true
	 */
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

	/**
	 * @param string $location
	 *
	 * @return QuarkProjectViewResource
	 */
	public static function CSS ($location) {
		return new self($location, new QuarkCSSViewResourceType());
	}

	/**
	 * @param string $location
	 *
	 * @return QuarkProjectViewResource
	 */
	public static function JS ($location) {
		return new self($location, new QuarkJSViewResourceType());
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
 * Trait QuarkInlineViewResource
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
	 * @var QuarkModel[]|array $_list = []
	 */
	private $_list = array();

	/**
	 * @var $_type  = null
	 */
	private $_type = null;

	/**
	 * @var int $_index = 0
	 */
	private $_index = 0;

	/**
	 * @param object $type
	 * @param array $source = []
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
			$this->_list[] = $item instanceof QuarkModel ? $item : new QuarkModel($item);

		return $this;
	}

	/**
	 * @return QuarkCollection
	 */
	public function Reverse () {
		$this->_list = array_reverse($this->_list);

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
		if ($iterator == null)
			$iterator = function (QuarkModel $item = null) { return $item ? $item->Model() : null; };

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
		if (!$this->_type($value)) return;

		if ($offset === null) $this->_list[] = $value;
		else $this->_list[(int)$offset] = $value;
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
		unset($this->_list[(int)$offset]);
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
 * Trait QuarkModelBehavior
 *
 * @package Quark
 */
trait QuarkModelBehavior {
	use QuarkContainerBehavior;

	/** @noinspection PhpUnusedPrivateMethodInspection
	 * @return QuarkModel
	 */
	private function _envelope () {
		return new QuarkModel($this);
	}

	/**
	 * @return string
	 */
	public function Pk () {
		return $this->__call('PrimaryKey', func_get_args());
	}

	/**
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function Create ($options = []) {
		return $this->__call('Create', func_get_args());
	}

	/**
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function Save ($options = []) {
		return $this->__call('Save', func_get_args());
	}

	/**
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function Remove ($options = []) {
		return $this->__call('Remove', func_get_args());
	}

	/**
	 * @return bool
	 */
	public function Validate () {
		return $this->__call('Validate', func_get_args());
	}

	/**
	 * @param $source
	 *
	 * @return QuarkModel
	 */
	public function PopulateWith ($source) {
		return $this->__call('PopulateWith', func_get_args());
	}

	/**
	 * @param array $fields
	 * @param bool  $weak
	 *
	 * @return \StdClass
	 */
	public function Extract ($fields = null, $weak = false) {
		return $this->__call('Extract', func_get_args());
	}

	/**
	 * @return QuarkModelSource
	 */
	public function Source () {
		return $this->__call('Source', func_get_args());
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

	/**
	 * @var $_connection
	 */
	private $_connection;

	/**
	 * @var QuarkURI $_uri
	 */
	private $_uri;

	/**
	 * @var string $_name = ''
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
	 * @param string $name
	 */
	public function Stacked ($name) { }

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
	public function &Connect () {
		$this->_connection = $this->_provider->Connect($this->_uri);

		return $this->_provider;
	}

	/**
	 * @return mixed
	 */
	public function &Connection () {
		return $this->_connection;
	}

	/**
	 * @param IQuarkDataProvider $provider
	 *
	 * @return IQuarkDataProvider
	 */
	public function &Provider (IQuarkDataProvider $provider = null) {
		if (func_num_args() != 0)
			$this->_provider = $provider;

		return $this->_provider;
	}

	/**
	 * @param QuarkURI $uri
	 *
	 * @return QuarkURI
	 */
	public function &URI (QuarkURI $uri = null) {
		if (func_num_args() != 0)
			$this->_uri = $uri;

		return $this->_uri;
	}

	/**
	 * @param string $name
	 * @param IQuarkDataProvider $provider
	 * @param QuarkURI $uri
	 *
	 * @return QuarkModelSource|IQuarkStackable
	 */
	public static function Register ($name, IQuarkDataProvider $provider, QuarkURI $uri) {
		return Quark::Component($name, new self($name, $provider, $uri));
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

	const OPTION_USER_OPTIONS = '___user___';

	/**
	 * @var IQuarkModel|QuarkModelBehavior $_model = null
	 */
	private $_model = null;

	/**
	 * @var QuarkKeyValuePair[] $_errors
	 */
	private $_errors = array();

	/**
	 * @var QuarkKeyValuePair[] $_errorFlux
	 */
	private static $_errorFlux = array();

	/**
	 * @param IQuarkModel|QuarkModelBehavior $model
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
	 * @return IQuarkModel|QuarkModelBehavior
	 */
	public function Model () {
		return $this->_model;
	}

	/**
	 * @return IQuarkPrimitive
	 */
	public function &Primitive () {
		return $this->_model;
	}

	/**
	 * @param QuarkURI|string $uri = ''
	 *
	 * @return QuarkModelSource|IQuarkDataProvider
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

		if ($this->_model instanceof IQuarkModelWithAfterPopulate) {
			$out = $this->_model->AfterPopulate($source);

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

		try {
			$source = Quark::Stack($name);
		}
		catch (\Exception $e) {
			$source = null;
		}

		if (!($source instanceof QuarkModelSource))
			throw new QuarkArchException('Model source for model ' . get_class($model) . ' is not connected');

		if ($uri)
			$source->URI(QuarkURI::FromURI($uri));

		return func_num_args() == 1 ? $source->Connect() : $source;
	}

	/**
	 * @param string $key
	 * @param string $value = ''
	 *
	 * @return array
	 */
	public static function StructureFromKey ($key, $value = '') {
		$structure = explode('.', $key);

		return array($structure[0] => sizeof($structure) == 1
			? $value
			: self::StructureFromKey(substr($key, strpos($key, '.') + 1), $value)
		);
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
	 * @param IQuarkModel|QuarkModelBehavior $model
	 * @param $source
	 *
	 * @return IQuarkModel|QuarkModelBehavior
	 */
	private static function _import (IQuarkModel $model, $source) {
		if (!is_array($source) && !is_object($source)) return $model;

		$fields = $model->Fields();

		if ($model instanceof IQuarkModelWithDataProvider) {
			/**
			 * @var IQuarkModel $model
			 */
			$ppk = self::_provider($model)->PrimaryKey($model);

			if ($ppk instanceof QuarkKeyValuePair) {
				$pk = $ppk->Key();

				if (!isset($model->$pk))
					$fields[$pk] = $ppk->Value();
			}
		}

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
	 * @param IQuarkModel|QuarkModelBehavior $model
	 * @param $options
	 *
	 * @return IQuarkModel|QuarkModelBehavior|bool
	 */
	private static function _export (IQuarkModel $model, $options = []) {
		$fields = $model->Fields();

		if (!isset($options[self::OPTION_VALIDATE]))
			$options[self::OPTION_VALIDATE] = true;

		if ($options[self::OPTION_VALIDATE] && !self::_validate($model)) return false;

		$output = self::_normalize($model);

		foreach ($model as $key => $value) {
			if (!QuarkObject::PropertyExists($fields, $key) && $model instanceof IQuarkStrongModel) {
				unset($output->$key);
				continue;
			}

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
	 * @param IQuarkModel|QuarkModelBehavior $model
	 * @param bool $check = true
	 *
	 * @return bool|array
	 */
	private static function _validate (IQuarkModel $model, $check = true) {
		QuarkField::FlushValidationErrors();

		if ($model instanceof IQuarkNullableModel && sizeof((array)$model) == 0) return true;

		$output = clone $model;

		if ($model instanceof IQuarkStrongModel) {
			$fields = $model->Fields();

			if (is_array($fields) || is_object($fields))
				foreach ($fields as $key => $field) {
					if (isset($model->$key)) continue;

					$output->$key = $field instanceof IQuarkModel
						? QuarkModel::Build($field, empty($model->$key) ? null : $model->$key)
						: $field;
				}
		}

		if ($output instanceof IQuarkModelWithBeforeValidate && $output->BeforeValidate() === false) return false;

		$valid = $check ? QuarkField::Rules($output->Rules()) : $output->Rules();
		self::$_errorFlux = array_merge(self::$_errorFlux, QuarkField::FlushValidationErrors());

		foreach ($output as $key => $value)
			if ($value instanceof QuarkModel)
				$valid &= $value->Validate();

		return $valid;
	}

	/**
	 * @param IQuarkModel|QuarkModelBehavior $model
	 * @param mixed $data
	 * @param array $options
	 * @param callable $after = null
	 *
	 * @return QuarkModel|QuarkModelBehavior|\StdClass
	 */
	private static function _record (IQuarkModel $model, $data, $options = [], callable $after = null) {
		if ($data == null) return null;

		$output = new QuarkModel($model, $data);

		$model = $output->Model();

		if ($model instanceof IQuarkModelWithAfterFind)
			$model->AfterFind($data, $options);

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
	 * @return IQuarkModel|QuarkModelBehavior|bool
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
			$out = $model->BeforeExtract($fields, $weak);

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
		$validate = self::_validate($this->_model);
		$this->_errors = self::$_errorFlux;

		return $validate;
	}

	/**
	 * @return bool[]
	 */
	public function ValidationRules () {
		return self::_validate($this->_model, false);
	}

	/**
	 * @return QuarkKeyValuePair[]
	 */
	public function RawValidationErrors () {
		return $this->_errors;
	}

	/**
	 * @param string $language = QuarkLocalizedString::LANGUAGE_ANY
	 *
	 * @return string[]
	 */
	public function ValidationErrors ($language = QuarkLocalizedString::LANGUAGE_ANY) {
		$out = array();

		foreach ($this->_errors as $error)
			$out[] = $error->Value()->Of($language);

		return $out;
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
		$ok = QuarkObject::is($this->_model, 'Quark\IQuarkModelWith' . $hook)
			? $this->_model->$hook($options)
			: true;

		if ($ok !== null && !$ok) return false;

		$model = self::_export(clone $this->_model, $options);
		$this->_errors = self::$_errorFlux;

		if (!$model) return false;

		$ok = $model instanceof IQuarkModelWithAfterExport
			? $model->AfterExport($name, $options)
			: true;

		if ($ok !== null && !$ok) return false;

		$out = self::_provider($model)->$name($model, $options);

		$this->PopulateWith($model);

		$hook = 'After' . $name;
		$ok = QuarkObject::is($model, 'Quark\IQuarkModelWith' . $hook)
			? $model->$hook($options)
			: true;

		if ($ok !== null && !$ok) return false;

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
		if ($this->_model == null) return null;

		$pk = self::_provider($this->_model)->PrimaryKey($this->_model)->Key();

		if ($this->_model instanceof IQuarkModelWithCustomPrimaryKey)
			$pk = $this->_model->PrimaryKey();

		return (string)$this->$pk;
	}

	/**
	 * @param IQuarkModel|QuarkModelBehavior $model
	 * @param $criteria
	 * @param $options
	 * @param callable(QuarkModel $model) $after = null
	 *
	 * @return QuarkCollection|array
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
	 * @param IQuarkModel|QuarkModelBehavior $model
	 * @param $criteria
	 * @param $options
	 * @param callable(QuarkModel $model) $after = null
	 *
	 * @return QuarkModel|\StdClass
	 */
	public static function FindOne (IQuarkModel $model, $criteria = [], $options = [], callable $after = null) {
		return self::_record($model, self::_provider($model)->FindOne($model, $criteria, $options), $options, $after);
	}

	/**
	 * @param IQuarkModel|QuarkModelBehavior $model
	 * @param $id
	 * @param $options
	 * @param callable(QuarkModel $model) $after = null
	 *
	 * @return QuarkModel|\StdClass
	 */
	public static function FindOneById (IQuarkModel $model, $id, $options = [], callable $after= null) {
		return self::_record($model, self::_provider($model)->FindOneById($model, $id, $options), $options, $after);
	}

	/**
	 * @param IQuarkModel|QuarkModelBehavior $model
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
	 * @param IQuarkModel|QuarkModelBehavior $model
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
	 * @param IQuarkModel|QuarkModelBehavior $model
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
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function AfterFind($raw, $options);
}

/**
 * Interface IQuarkModelWithAfterPopulate
 *
 * @package Quark
 */
interface IQuarkModelWithAfterPopulate {
	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function AfterPopulate($raw);
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
 * Interface IQuarkModelWithAfterCreate
 *
 * @package Quark
 */
interface IQuarkModelWithAfterCreate {
	/**
	 * @param $options
	 *
	 * @return mixed
	 */
	public function AfterCreate($options);
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
 * Interface IQuarkModelWithAfterSave
 *
 * @package Quark
 */
interface IQuarkModelWithAfterSave {
	/**
	 * @param $options
	 *
	 * @return mixed
	 */
	public function AfterSave($options);
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
 * Interface IQuarkModelWithAfterRemove
 *
 * @package Quark
 */
interface IQuarkModelWithAfterRemove {
	/**
	 * @param $options
	 *
	 * @return mixed
	 */
	public function AfterRemove($options);
}
/**
 * Interface IQuarkModelWithAfterExport
 *
 * @package Quark
 */
interface IQuarkModelWithAfterExport {
	/**
	 * @param $operation
	 * @param $options
	 *
	 * @return mixed
	 */
	public function AfterExport($operation, $options);
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
	 * @param array $fields
	 * @param bool $weak
	 *
	 * @return mixed
	 */
	public function BeforeExtract($fields, $weak);
}

/**
 * Interface IQuarkApplicationSettingsModel
 *
 * @package Quark
 */
interface IQuarkApplicationSettingsModel extends IQuarkModel, IQuarkModelWithDataProvider {
	/**
	 * @return array
	 */
	public function LoadCriteria();
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
	 * @return QuarkKeyValuePair
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
	 * @var QuarkKeyValuePair[] $_errors
	 */
	private static $_errors = array();

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

	/**
	 * @param bool $rule
	 * @param QuarkLocalizedString $message
	 *
	 * @return bool
	 */
	public static function Assert ($rule, QuarkLocalizedString $message = null) {
		if (!$rule && $message != null)
			self::$_errors[] = new QuarkKeyValuePair('', $message);

		return $rule;
	}

	/**
	 * @param bool $rule
	 * @param string $field
	 * @param QuarkLocalizedString $message
	 *
	 * @return bool
	 */
	public static function AssertField ($rule, $field = '', QuarkLocalizedString $message = null) {
		if (!$rule && $message != null)
			self::$_errors[] = new QuarkKeyValuePair($field, $message);

		return $rule;
	}

	/**
	 * @param string $language = QuarkLocalizedString::LANGUAGE_ANY
	 *
	 * @return string[]
	 */
	public static function ValidationErrors ($language = QuarkLocalizedString::LANGUAGE_ANY) {
		$out = array();

		foreach (self:: $_errors as $error)
			$out[] = $error->Value()->Of($language);

		return $out;
	}

	/**
	 * @return QuarkKeyValuePair[]
	 */
	public static function FlushValidationErrors () {
		$errors = self::$_errors;
		self::$_errors = array();

		return $errors;
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

	/**
	 * @var object $values = null
	 */
	public $values = null;

	/**
	 * @var string $default = self::LANGUAGE_ANY
	 */
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

		$default = $this->default;

		return isset($this->values->$language)
			? (string)$this->values->$language
			: (isset($this->values->$default) ? $this->values->$default : '');
	}

	/**
	 * @param array|object $dictionary
	 * @param string $default = self::LANGUAGE_ANY
	 *
	 * @return QuarkLocalizedString
	 */
	public static function Dictionary ($dictionary = [], $default = self::LANGUAGE_ANY) {
		if (!is_array($dictionary) && !is_object($dictionary)) return null;

		$str = new self('', self::LANGUAGE_ANY, $default);
		$str->values = (object)$dictionary;

		return $str;
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
class QuarkDate implements IQuarkModel, IQuarkLinkedModel, IQuarkModelWithAfterPopulate, IQuarkModelWithBeforeExtract {
	const NOW = 'now';
	const NOW_FULL = 'Y-m-d H:i:s.u';
	const GMT = 'UTC';
	const CURRENT = '';
	const UNKNOWN_YEAR = '0000';

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
	 * cloning behavior
	 */
	public function __clone () {
		$this->_date = clone $this->_date;
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
			$this->_date = new \DateTime($value);

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
	 * @return int
	 */
	public function Timestamp () {
		return $this->_date->getTimestamp();
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
	 * @param bool $copy = false
	 *
	 * @return QuarkDate
	 */
	public function Offset ($offset, $copy = false) {
		$out = $copy ? clone $this : $this;
		$out->_date->modify($offset);

		return $out;
	}

	/**
	 * @param QuarkDate $from
	 * @param int $offset = 0
	 *
	 * @return bool
	 */
	public function Expired (QuarkDate $from = null, $offset = 0) {
		if ($from == null)
			$from = self::Now();

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
	 * @return string
	 */
	public static function Microtime () {
		return str_pad(explode(' ', microtime())[0] * 1000000, 6, '0');
	}

	/**
	 * @return string
	 */
	public static function NowUSec () {
		return date('Y-m-d H:i:s') . '.' . self::Microtime();
	}

	/**
	 * @return string
	 */
	public static function NowUSecGMT () {
		return gmdate('Y-m-d H:i:s') . '.' . self::Microtime();
	}

	/**
	 * @param string $format
	 *
	 * @return QuarkDate
	 */
	public static function Now ($format = '') {
		return self::FromFormat($format, self::NowUSec());
	}

	/**
	 * @param string $format
	 *
	 * @return QuarkDate
	 */
	public static function GMTNow ($format = '') {
		return self::FromFormat($format, self::NowUSec(), self::GMT);
	}

	/**
	 * @param string $date
	 *
	 * @return QuarkDate
	 */
	public static function Of ($date) {
		return new self(null, $date);
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
	public function Fields () { }

	/**
	 * @return mixed
	 */
	public function Rules () { }

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
	public function AfterPopulate ($raw) {
		$this->Value($raw);
	}

	/**
	 * @param array $fields
	 * @param bool $weak
	 *
	 * @return mixed
	 */
	public function BeforeExtract ($fields, $weak) {
		return $this->DateTime();
	}
}

/**
 * Class QuarkGenericModel
 *
 * @package Quark
 */
class QuarkGenericModel implements IQuarkModel {
	/**
	 * @return mixed
	 */
	public function Fields () { }

	/**
	 * @return mixed
	 */
	public function Rules () { }

	/**
	 * @param IQuarkModel $model
	 *
	 * @return QuarkModel
	 */
	public function To (IQuarkModel $model) {
		return new QuarkModel($model, $this);
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
		$this->_name = $name;
		$this->_provider = $provider;
		$this->_user = $user;
	}

	/**
	 * @param string $name
	 */
	public function Stacked ($name) { }

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
}

/**
 * Class QuarkSession
 *
 * @package Quark
 */
class QuarkSession {
	/**
	 * @var QuarkSession $_current
	 */
	private static $_current;

	/**
	 * @var QuarkModel|IQuarkAuthorizableModel $user
	 */
	private $_user;

	/**
	 * @var QuarkSessionSource $_source
	 */
	private $_source;

	/**
	 * @var QuarkDTO $_output
	 */
	private $_output;

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
	 * @param QuarkSessionSource $source = null
	 */
	public function __construct (QuarkSessionSource $source = null) {
		if (func_num_args() == 0) return;

		$this->_source = clone $source;
		self::$_current = &$this;
	}

	/**
	 * @return QuarkModel|IQuarkAuthorizableModel
	 */
	public function &User () {
		return $this->_user;
	}

	/**
	 * @return QuarkKeyValuePair
	 */
	public function ID () {
		return $this->_output ? $this->_output->AuthorizationProvider() : null;
	}

	/**
	 * @return string
	 */
	public function Signature () {
		return $this->_output ? $this->_output->Signature() : '';
	}

	/**
	 * @param QuarkDTO $input
	 *
	 * @return bool
	 */
	public function Input (QuarkDTO $input) {
		$data = $this->_source->Provider()->Session($this->_source->Name(), $this->_source->User(), $input);
		if ($data == null) return false;

		$this->_user = $this->_source->User()->Session($this->_source->Name(), $data->Data());
		if ($this->_user == null) return false;

		$data->Data(null);
		$this->_output = $data;

		return $this->_user != null;
	}

	/**
	 * @param QuarkModel $user
	 * @param $criteria = []
	 * @param int $lifetime = 0
	 *
	 * @return bool
	 * @throws QuarkArchException
	 */
	public function ForUser (QuarkModel $user, $criteria = [], $lifetime = 0) {
		$user = $user->Model();

		if (!($user instanceof IQuarkAuthorizableModel))
			throw new QuarkArchException('Model ' . get_class($user) . ' is not an IQuarkAuthorizableModel');

		$data = $this->_source->Provider()->Login($this->_source->Name(), $user, $criteria, $lifetime);
		if ($data == null) return false;

		$this->_user = $this->_source->User()->Login($this->_source->Name(), $criteria, $lifetime);
		if ($this->_user == null) return false;

		$this->_output = $data;

		return $this->_user != null;
	}

	/**
	 * @param $criteria
	 * @param $lifetime = 0
	 *
	 * @return bool
	 */
	public function Login ($criteria, $lifetime = 0) {
		$this->_user = $this->_source->User()->Login($this->_source->Name(), $criteria, $lifetime);
		if ($this->_user == null) return false;

		/**
		 * @var IQuarkAuthorizableModel $user
		 */
		$user = $this->_user->Model();

		$data = $this->_source->Provider()->Login($this->_source->Name(), $user, $criteria, $lifetime);
		if ($data == null) return false;

		$this->_output = $data;

		return $this->_user != null;
	}

	/**
	 * @return bool
	 */
	public function Logout () {
		if ($this->ID() == null) return false;

		$logout = $this->_source->User()->Logout($this->_source->Name(), $this->ID());
		if ($logout === false) return false;

		/**
		 * @var IQuarkAuthorizableModel $user
		 */
		$user = $this->_user->Model();

		$data = $this->_source->Provider()->Logout($this->_source->Name(), $user, $this->ID());
		if ($data == null) return false;

		$this->_output = $data;
		$this->_user = null;

		return true;
	}

	/**
	 * @return QuarkDTO
	 */
	public function Output () {
		return $this->_output;
	}

	/**
	 * @param string $provider
	 * @param QuarkDTO $input
	 *
	 * @return QuarkSession
	 *
	 * @throws QuarkArchException
	 */
	public static function Init ($provider, QuarkDTO $input) {
		/**
		 * @var QuarkSessionSource $source
		 */
		$source = Quark::Stack($provider);

		if ($source == null) return null;

		$session = new self($source);
		$session->Input($input);

		return $session;
	}

	/**
	 * @param QuarkKeyValuePair $id
	 *
	 * @return QuarkSession
	 */
	public static function Get (QuarkKeyValuePair $id = null) {
		if ($id == null) return null;

		/**
		 * @var QuarkSessionSource $source
		 */
		$source = Quark::Stack($id->Key());

		if ($source == null) return null;

		$input = new QuarkDTO();
		$input->AuthorizationProvider($id);

		$session = new self($source);
		$session->Input($input);

		return $session;
	}

	/**
	 * @return QuarkSession
	 */
	public static function &Current () {
		return self::$_current;
	}

	/**
	 * Destructor
	 */
	public function __destruct () {
		unset($this->_user);
		unset($this->_source);
		unset($this->_output);
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
	 * @param IQuarkAuthorizableModel $model
	 * @param QuarkDTO $input
	 *
	 * @return QuarkDTO
	 */
	public function Session($name, IQuarkAuthorizableModel $model, QuarkDTO $input);

	/**
	 * @param string $name
	 * @param IQuarkAuthorizableModel $model
	 * @param $criteria
	 * @param $lifetime
	 *
	 * @return QuarkDTO
	 */
	public function Login($name, IQuarkAuthorizableModel $model, $criteria, $lifetime);

	/**
	 * @param string $name
	 * @param IQuarkAuthorizableModel $model
	 * @param QuarkKeyValuePair $id
	 *
	 * @return QuarkDTO
	 */
	public function Logout($name, IQuarkAuthorizableModel $model, QuarkKeyValuePair $id);
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
	 * @return QuarkModel|IQuarkAuthorizableModel
	 */
	public function Login($name, $criteria, $lifetime);

	/**
	 * @param string $name
	 * @param QuarkKeyValuePair $id
	 *
	 * @return bool
	 */
	public function Logout($name, QuarkKeyValuePair $id);

}

/**
 * Class QuarkKeyValuePair
 *
 * @package Quark
 */
class QuarkKeyValuePair {
	/**
	 * @var $_key
	 */
	private $_key;

	/**
	 * @var $_value
	 */
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

	/**
	 * @param array $field
	 *
	 * @return QuarkKeyValuePair
	 */
	public static function FromField ($field = []) {
		if (!is_array($field) && !is_object($field)) return null;

		$field = (array)$field;
		$pair = each($field);

		return new self($pair['key'], $pair['value']);
	}
}

/**
 * Trait QuarkNetwork
 *
 * @package Quark
 */
trait QuarkNetwork {
	use QuarkEvent;

	/**
	 * @var QuarkURI $_uri
	 */
	private $_uri;

	/**
	 * @var IQuarkNetworkTransport $_transport
	 */
	private $_transport;

	/**
	 * @var QuarkCertificate $_certificate
	 */
	private $_certificate;

	/**
	 * @var int $_timeout = 0
	 */
	private $_timeout = 0;

	/**
	 * @var int $_timeoutSend = 1 (microseconds)
	 */
	private $_timeoutSend = 1;

	/**
	 * @var bool $_blocking = true
	 */
	private $_blocking = true;

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
	 * @return string
	 */
	public function __toString () {
		return $this->_uri->URI();
	}

	/**
	 * @param resource $socket
	 * http://php.net/manual/ru/function.stream-socket-shutdown.php#109982
	 *
	 * @return bool
	 */
	public static function SocketClose ($socket) {
		return $socket ? stream_socket_shutdown($socket, STREAM_SHUT_RDWR) : false;
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
	 * @param IQuarkNetworkTransport $transport
	 *
	 * @return IQuarkNetworkTransport
	 */
	public function Transport (IQuarkNetworkTransport $transport = null) {
		if (func_num_args() == 1 && $transport != null)
			$this->_transport = $transport;

		return $this->_transport;
	}

	/**
	 * @param IQuarkNetworkProtocol &$protocol
	 */
	public function Protocol (IQuarkNetworkProtocol &$protocol) {
		$this->On(QuarkClient::EVENT_CONNECT, array(&$protocol, QuarkClient::EVENT_CONNECT));
		$this->On(QuarkClient::EVENT_DATA, array(&$protocol, QuarkClient::EVENT_DATA));
		$this->On(QuarkClient::EVENT_CLOSE, array(&$protocol, QuarkClient::EVENT_CLOSE));
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

		if ($uri == null) return null;

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
	 * @param int $timeout = 0 (microseconds)
	 *
	 * @return int
	 */
	public function TimeoutSend ($timeout = 0) {
		if (func_num_args() != 0)
			$this->_timeoutSend = $timeout;

		return $this->_timeoutSend;
	}

	/**
	 * @param bool|int $block = true
	 *
	 * @return bool
	 */
	public function Blocking ($block = true) {
		if (func_num_args() != 0) {
			$this->_blocking = (bool)$block;

			if ($this->_socket)
				stream_set_blocking($this->_socket, (int)$block);
		}

		return $this->_blocking;
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
class QuarkClient implements IQuarkEventable {
	const EVENT_ERROR_CONNECT = 'ErrorConnect';

	const EVENT_CONNECT = 'OnConnect';
	const EVENT_DATA = 'OnData';
	const EVENT_CLOSE = 'OnClose';

	use QuarkNetwork;

	/**
	 * @var bool $_connected
	 */
	private $_connected = false;

	/**
	 * @var QuarkURI $_remote
	 */
	private $_remote;

	/**
	 * @var QuarkKeyValuePair $_session
	 */
	private $_session;

	/**
	 * @var int $_rps
	 */
	private $_rps = 0;

	/**
	 * @var int $_rpsCount
	 */
	private $_rpsCount = 0;

	/**
	 * @var QuarkTimer $_rpsTimer
	 */
	private $_rpsTimer;

	/**
	 * @param QuarkURI|string $uri
	 * @param IQuarkNetworkTransport $transport
	 * @param QuarkCertificate $certificate
	 * @param int $timeout = 0
	 * @param bool $block = true
	 */
	public function __construct ($uri = '', IQuarkNetworkTransport $transport = null, QuarkCertificate $certificate = null, $timeout = 0, $block = true) {
		$this->URI(QuarkURI::FromURI($uri));
		$this->Transport($transport);
		$this->Certificate($certificate);
		$this->Timeout($timeout);
		$this->Blocking($block);

		$this->_rpsTimer = new QuarkTimer(QuarkTimer::ONE_SECOND, function () {
			$this->_rps = $this->_rpsCount;
			$this->_rpsCount = 0;
		});
	}

	/**
	 * @return bool
	 */
	public function Connect () {
		if ($this->_uri == null || $this->_uri->IsNull())
			return $this->TriggerArgs(self::EVENT_ERROR_CONNECT, array('QuarkClient URI is null'));

		$stream = stream_context_create();

		if ($this->_certificate == null) {
			stream_context_set_option($stream, 'ssl', 'verify_host', false);
			stream_context_set_option($stream, 'ssl', 'verify_peer', false);
			stream_context_set_option($stream, 'ssl', 'verify_peer_name', false);
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

		if (!$this->_socket || $this->_errorNumber != 0 || $this->ConnectionURI() == $this->ConnectionURI(true)) {
			$this->Close(false);
			$this->TriggerArgs(self::EVENT_ERROR_CONNECT, array('QuarkClient cannot connect to ' . $this->_uri->URI() . ' (' . $this->_uri->Socket() . ')'));

			return false;
		}

		$this->Blocking($this->_blocking);
		$this->Timeout($this->_timeout);

		$this->_connected = true;
		$this->_remote = QuarkURI::FromURI($this->ConnectionURI(true));

		if ($this->_transport instanceof IQuarkNetworkTransport)
			$this->_transport->EventConnect($this);

		return true;
	}

	/**
	 * @param string $data
	 *
	 * @return bool
	 */
	public function Send ($data) {
		$out = $this->_socket && $this->_transport instanceof IQuarkNetworkTransport
			? @fwrite($this->_socket, $this->_transport->Send($data))
			: false;

		usleep($this->_timeoutSend);

		return $out;
	}

	/**
	 * @param int $max = -1
	 *
	 * @return bool|string
	 */
	public function Receive ($max = -1) {
		if ($this->Closed())
			return $this->Close();

		if (!$this->_socket)
			return false;

		$data = stream_get_contents($this->_socket, $max);

		return strlen($data) != 0 ? $data : false;
	}

	/**
	 * @param int $max = -1
	 *
	 * @return bool
	 */
	public function Pipe ($max = -1) {
		$data = $this->Receive($max);

		return is_string($data) && $this->_transport instanceof IQuarkNetworkTransport
			? $this->_transport->EventData($this, $data)
			: false;
	}

	/**
	 * @param bool $event = true
	 *
	 * @return bool
	 */
	public function Close ($event = true) {
		$this->_connected = false;

		if ($event && $this->_transport instanceof IQuarkNetworkTransport)
			$this->_transport->EventClose($this);

		$this->_remote = null;
		$this->_transport = null;
		$this->_rps = 0;
		$this->_rpsTimer = null;

		return self::SocketClose($this->_socket);
	}

	/**
	 * Trigger `Connect` event
	 */
	public function TriggerConnect () {
		$this->TriggerArgs(QuarkClient::EVENT_CONNECT, array(&$this));
	}

	/**
	 * Trigger `Data` event
	 *
	 * @param $data
	 */
	public function TriggerData ($data) {
		$this->_rpsCount++;
		$this->_rpsTimer->Invoke();

		$this->TriggerArgs(QuarkClient::EVENT_DATA, array(&$this, $data));
	}

	/**
	 * Trigger `Close` event
	 */
	public function TriggerClose () {
		$this->TriggerArgs(QuarkClient::EVENT_CLOSE, array(&$this));
	}

	/**
	 * @param IQuarkNetworkTransport $transport
	 * @param resource $socket
	 * @param string $address
	 * @param string $scheme
	 *
	 * @return QuarkClient
	 */
	public static function ForServer (IQuarkNetworkTransport $transport, $socket, $address, $scheme) {
		$uri = QuarkURI::FromURI($address);
		$uri->scheme = $scheme;

		$client = new self($uri, clone $transport);

		$client->Socket($socket);

		$client->Blocking(false);
		$client->Timeout(0);
		$client->Connected(true);

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
		return !$this->_socket || (feof($this->_socket) === true && $this->_connected);
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
	 * @param QuarkKeyValuePair $session
	 *
	 * @return QuarkKeyValuePair
	 */
	public function &Session (QuarkKeyValuePair $session = null) {
		if (func_num_args() != 0)
			$this->_session = $session;

		return $this->_session;
	}

	/**
	 * @return int
	 */
	public function RPS () {
		return $this->_rps;
	}
}

/**
 * Class QuarkServer
 *
 * @package Quark
 */
class QuarkServer implements IQuarkEventable {
	const ALL_INTERFACES = '0.0.0.0';
	const TCP_ALL_INTERFACES_RANDOM_PORT = 'tcp://0.0.0.0:0';

	const EVENT_ERROR_LISTEN = 'ErrorListen';

	use QuarkNetwork;

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
	 * @param IQuarkNetworkTransport $transport
	 * @param QuarkCertificate $certificate
	 * @param int $timeout = 0
	 */
	public function __construct ($uri = '', IQuarkNetworkTransport $transport = null, QuarkCertificate $certificate = null, $timeout = 0) {
		$this->URI(QuarkURI::FromURI($uri));
		$this->Transport($transport);
		$this->Certificate($certificate);
		$this->Timeout($timeout);
	}

	/**
	 * @return bool
	 */
	public function Bind () {
		if ($this->_uri == null || $this->_uri->IsNull())
			return $this->TriggerArgs(self::EVENT_ERROR_LISTEN, array('QuarkServer URI is null'));

		$stream = stream_context_create();

		if ($this->_certificate == null) {
			stream_context_set_option($stream, 'ssl', 'verify_host', false);
			stream_context_set_option($stream, 'ssl', 'verify_peer', false);
			stream_context_set_option($stream, 'ssl', 'verify_peer_name', false);
		}
		else {
			stream_context_set_option($stream, 'ssl', 'local_cert', $this->_certificate->Location());
			stream_context_set_option($stream, 'ssl', 'passphrase', $this->_certificate->Passphrase());
		}

		$this->_socket = @stream_socket_server(
			$this->_uri->Socket(),
			$this->_errorNumber,
			$this->_errorString,
			STREAM_SERVER_BIND|STREAM_SERVER_LISTEN,
			$stream
		);

		if (!$this->_socket) {
			$this->TriggerArgs(self::EVENT_ERROR_LISTEN, array('QuarkServer cannot listen to ' . $this->_uri->URI() . ' (' . $this->_uri->Socket() . ')'));

			return false;
		}

		$this->Blocking(0);
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

			$client = QuarkClient::ForServer($this->_transport, $socket, $address, $this->URI()->scheme);
			$client->Remote(QuarkURI::FromURI($this->ConnectionURI()));
			$client->TimeoutSend($this->_timeoutSend);

			$client->Delegate(QuarkClient::EVENT_CONNECT, $this);
			$client->Delegate(QuarkClient::EVENT_DATA, $this);
			$client->Delegate(QuarkClient::EVENT_CLOSE, $this);

			$client->Transport()->EventConnect($client);

			$this->_clients[] = $client;

			unset($socket, $address, $client);
		}

		$this->_read = array();
		$this->_write = array();
		$this->_except = array();

		foreach ($this->_clients as $key => &$client) {
			if ($client->Closed()) {
				unset($this->_clients[$key]);
				$client->Close();
				continue;
			}

			$client->Pipe();
		}

		unset($key, $client);

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
		self::SocketClose($this->_socket);

		return $this;
	}

	/**
	 * @return QuarkClient[]
	 */
	public function &Clients () {
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

	/**
	 * @param string $data
	 * @param callable(QuarkClient $client) $filter = null
	 *
	 * @return bool
	 */
	public function Broadcast ($data, callable $filter = null) {
		$ok = true;

		foreach ($this->_clients as $i => &$client) {
			if ($filter && !$filter($client)) continue;

			$ok &= $client->Send($data);
		}

		return $ok;
	}
}

/**
 * Class QuarkPeer
 *
 * @package Quark
 */
class QuarkPeer {
	/**
	 * @var IQuarkPeer $_protocol
	 */
	private $_protocol;

	/**
	 * @var QuarkServer $_server
	 */
	private $_server;

	/**
	 * @var QuarkClient[] $_peers
	 */
	private $_peers = array();

	/**
	 * @param IQuarkPeer &$protocol
	 * @param QuarkURI|string $bind
	 * @param QuarkURI[]|string[] $connect
	 */
	public function __construct (IQuarkPeer &$protocol = null, $bind = '', $connect = []) {
		$this->_protocol = $protocol;
		$this->_server = new QuarkServer($bind, $this->_protocol->NetworkTransport());
		$this->_server->On(QuarkClient::EVENT_CONNECT, array(&$this->_protocol, 'NetworkServerConnect'));
		$this->_server->On(QuarkClient::EVENT_DATA, array(&$this->_protocol, 'NetworkServerData'));
		$this->_server->On(QuarkClient::EVENT_CLOSE, array(&$this->_protocol, 'NetworkServerClose'));

		$this->Peers($connect);
	}

	/**
	 * @return string
	 */
	public function __toString () {
		return $this->_server->URI()->URI();
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

		$peer = new QuarkClient($uri, $this->_protocol->NetworkTransport(), null, 0, false);
		$peer->On(QuarkClient::EVENT_CONNECT, array(&$this->_protocol, 'NetworkClientConnect'));
		$peer->On(QuarkClient::EVENT_DATA, array(&$this->_protocol, 'NetworkClientData'));
		$peer->On(QuarkClient::EVENT_CLOSE, array(&$this->_protocol, 'NetworkClientClose'));

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
	public function &Peers ($peers = [], $unique = true, $loopBack = false) {
		if (func_num_args() != 0 && is_array($peers))
			foreach ($peers as $peer)
				$this->Peer($peer, $unique, $loopBack);

		return $this->_peers;
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
 * Interface IQuarkPeer
 *
 * @package Quark
 */
interface IQuarkPeer {
	// NodeNetwork
	/**
	 * @return IQuarkNetworkTransport
	 */
	public function NetworkTransport();

	// NodeNetworkClient
	/**
	 * @param QuarkClient $node
	 *
	 * @return mixed
	 */
	public function NetworkClientConnect(QuarkClient $node);

	/**
	 * @param QuarkClient $node
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function NetworkClientData(QuarkClient $node, $data);

	/**
	 * @param QuarkClient $node
	 *
	 * @return mixed
	 */
	public function NetworkClientClose(QuarkClient $node);

	// NodeNetworkServer
	/**
	 * @param QuarkClient $node
	 *
	 * @return mixed
	 */
	public function NetworkServerConnect(QuarkClient $node);

	/**
	 * @param QuarkClient $node
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function NetworkServerData(QuarkClient $node, $data);

	/**
	 * @param QuarkClient $node
	 *
	 * @return mixed
	 */
	public function NetworkServerClose(QuarkClient $node);
}

/**
 * Class QuarkCluster
 *
 * @package Quark\NetworkTransports
 */
class QuarkCluster {
	/**
	 * @var IQuarkCluster $_cluster
	 */
	private $_cluster;

	/**
	 * @var QuarkServer $_server
	 */
	private $_server;

	/**
	 * @var QuarkPeer $_network
	 */
	private $_network;

	/**
	 * @var QuarkClient|QuarkServer $_controller
	 */
	private $_controller;

	/**
	 * @var QuarkServer $_terminal
	 */
	private $_terminal;

	/**
	 * @param IQuarkCluster &$cluster
	 */
	public function __construct (IQuarkCluster &$cluster = null) {
		$this->_cluster = $cluster;
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
	 * @return QuarkClient|QuarkServer
	 */
	public function &Controller () {
		return $this->_controller;
	}

	/**
	 * @return QuarkServer
	 */
	public function &Terminal () {
		return $this->_terminal;
	}

	/**
	 * @param string $data
	 *
	 * @return bool
	 */
	public function Broadcast ($data) {
		if ($this->_controller instanceof QuarkServer)
			return $this->_controller->Broadcast($data);

		$this->_cluster->NetworkServerData(null, $data);
		return $this->_network->Broadcast($data);
	}

	/**
	 * @param string $data
	 *
	 * @return bool
	 */
	public function Control ($data) {
		return $this->_controller instanceof QuarkServer
			? $this->_cluster->ControllerServerData(new QuarkClient(), $data)
			: $this->_controller->Send($data);
	}

	/**
	 * @return QuarkClient[]
	 */
	public function Nodes () {
		return $this->_controller instanceof QuarkServer
			? $this->_controller->Clients()
			: $this->_network->Server()->Clients();
	}

	/**
	 * @param IQuarkCluster &$cluster
	 * @param QuarkURI|string $external
	 * @param QuarkURI|string $internal
	 * @param QuarkURI|string $controller
	 *
	 * @return QuarkCluster
	 */
	public static function NodeInstance (IQuarkCluster &$cluster, $external, $internal, $controller = '') {
		$node = new self($cluster);

		$node->_server = new QuarkServer($external, $cluster->ClientTransport());
		$node->_server->On(QuarkClient::EVENT_CONNECT, array(&$cluster, 'ClientConnect'));
		$node->_server->On(QuarkClient::EVENT_DATA, array(&$cluster, 'ClientData'));
		$node->_server->On(QuarkClient::EVENT_CLOSE, array(&$cluster, 'ClientClose'));

		$node->_network = new QuarkPeer($cluster, $internal);

		$node->_controller = new QuarkClient($controller, $cluster->ControllerTransport());
		$node->_controller->On(QuarkClient::EVENT_CONNECT, array(&$cluster, 'ControllerClientConnect'));
		$node->_controller->On(QuarkClient::EVENT_DATA, array(&$cluster, 'ControllerClientData'));
		$node->_controller->On(QuarkClient::EVENT_CLOSE, array(&$cluster, 'ControllerClientClose'));

		return $node;
	}

	/**
	 * @return bool
	 */
	public function NodeBind () {
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
	 * @return bool
	 * @throws QuarkArchException
	 */
	public function NodePipe () {
		$run = $this->NodeBind() &&
			$this->_server->Pipe();
			$this->_network->Pipe();
			$this->_controller->Pipe();

		if (!$this->_server->Running())
			throw new QuarkArchException('Cluster server not started. Expected address ' . $this->_server);

		if (!$this->_network->Running())
			throw new QuarkArchException('Cluster peering not started. Expected address ' . $this->_network);

		return $run;
	}

	/**
	 * @param IQuarkCluster &$cluster
	 * @param QuarkURI|string $external
	 * @param QuarkURI|string $internal
	 *
	 * @return QuarkCluster
	 */
	public static function ControllerInstance (IQuarkCluster &$cluster, $external, $internal) {
		$controller = new self($cluster);

		$controller->_controller = new QuarkServer($internal, $cluster->ControllerTransport());
		$controller->_controller->On(QuarkClient::EVENT_CONNECT, array(&$cluster, 'ControllerServerConnect'));
		$controller->_controller->On(QuarkClient::EVENT_DATA, array(&$cluster, 'ControllerServerData'));
		$controller->_controller->On(QuarkClient::EVENT_CLOSE, array(&$cluster, 'ControllerServerClose'));

		$controller->_terminal = new QuarkServer($external, $cluster->TerminalTransport());
		$controller->_terminal->On(QuarkClient::EVENT_CONNECT, array(&$cluster, 'TerminalConnect'));
		$controller->_terminal->On(QuarkClient::EVENT_DATA, array(&$cluster, 'TerminalData'));
		$controller->_terminal->On(QuarkClient::EVENT_CLOSE, array(&$cluster, 'TerminalClose'));

		return $controller;
	}

	/**
	 * @return bool
	 * @throws QuarkArchException
	 */
	public function ControllerBind () {
		if ($this->_controller instanceof QuarkClient)
			throw new QuarkArchException('Cluster controller not started. Controller in client mode.');

		$run = true;

		if (!$this->_controller->Running())
			$run = $this->_controller->Bind();

		if (!$this->_terminal->Running())
			$run = $this->_terminal->Bind();

		return $run;
	}

	/**
	 * @return bool
	 * @throws QuarkArchException
	 */
	public function ControllerPipe () {
		if ($this->_controller instanceof QuarkClient)
			throw new QuarkArchException('Cluster controller not started. Controller in client mode.');

		$run = $this->ControllerBind() &&
			$this->_controller->Pipe() &&
			$this->_terminal->Pipe();

		if (!$this->_controller->Running())
			throw new QuarkArchException('Cluster controller not started. Expected address ' . $this->_controller);

		if (!$this->_terminal->Running())
			throw new QuarkArchException('Cluster terminal not started. Expected address ' . $this->_terminal);

		return $run;
	}
}

/**
 * Interface IQuarkCluster
 *
 * @package Quark\NetworkTransports
 */
interface IQuarkCluster extends IQuarkPeer {
	// NodeServer
	/**
	 * @return IQuarkNetworkTransport
	 */
	public function ClientTransport();

	/**
	 * @param QuarkClient $client
	 *
	 * @return mixed
	 */
	public function ClientConnect(QuarkClient $client);

	/**
	 * @param QuarkClient $client
	 * @param string $data
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

	// ControllerNetwork
	/**
	 * @return IQuarkNetworkTransport
	 */
	public function ControllerTransport();

	// ControllerNetworkClient
	/**
	 * @param QuarkClient $controller
	 *
	 * @return mixed
	 */
	public function ControllerClientConnect(QuarkClient $controller);

	/**
	 * @param QuarkClient $controller
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function ControllerClientData(QuarkClient $controller, $data);

	/**
	 * @param QuarkClient $controller
	 *
	 * @return mixed
	 */
	public function ControllerClientClose(QuarkClient $controller);

	// ControllerNetworkServer
	/**
	 * @param QuarkClient $node
	 *
	 * @return mixed
	 */
	public function ControllerServerConnect(QuarkClient $node);

	/**
	 * @param QuarkClient $node
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function ControllerServerData(QuarkClient $node, $data);

	/**
	 * @param QuarkClient $node
	 *
	 * @return mixed
	 */
	public function ControllerServerClose(QuarkClient $node);

	// ControllerTerminal
	/**
	 * @return IQuarkNetworkTransport
	 */
	public function TerminalTransport();

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
 * Class QuarkStreamEnvironment
 *
 * @package Quark\NetworkTransports
 */
class QuarkStreamEnvironment implements IQuarkEnvironment, IQuarkCluster {
	const URI_NODE_INTERNAL = QuarkServer::TCP_ALL_INTERFACES_RANDOM_PORT;
	const URI_NODE_EXTERNAL = 'ws://0.0.0.0:25000';
	const URI_CONTROLLER_INTERNAL = 'tcp://0.0.0.0:25800';
	const URI_CONTROLLER_EXTERNAL = 'ws://0.0.0.0:25900';

	const PACKAGE_REQUEST = 'url';
	const PACKAGE_RESPONSE = 'response';
	const PACKAGE_EVENT = 'event';
	const PACKAGE_COMMAND = 'cmd';

	const COMMAND_STATE = 'state';
	const COMMAND_BROADCAST = 'broadcast';
	const COMMAND_ANNOUNCE = 'announce';
	const COMMAND_AUTHORIZE = 'authorize';
	const COMMAND_INFRASTRUCTURE = 'infrastructure';
	const COMMAND_ENDPOINT = 'endpoint';

	use QuarkEvent;

	/**
	 * @var QuarkCluster $_cluster
	 */
	private $_cluster;

	/**
	 * @var IQuarkNetworkTransport $_transportClient
	 */
	private $_transportClient;

	/**
	 * @var IQuarkNetworkTransport $_transportTerminal
	 */
	private $_transportTerminal;

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
	 * @var QuarkJSONIOProcessor $_json
	 */
	private static $_json;

	/**
	 * Private constructor
	 */
	private function __construct () {
		if (self::$_json == null)
			self::$_json = new QuarkJSONIOProcessor();
	}

	/**
	 * @param string $name
	 * @param string $data
	 *
	 * @return bool
	 */
	public static function ControllerCommand ($name = '', $data = '') {
		$client = new QuarkClient(Quark::Config()->ClusterControllerConnect(), self::TCPProtocol());

		$client->On(QuarkClient::EVENT_CONNECT, function (QuarkClient $client) use (&$name, &$data) {
			$client->Send(self::Package(self::PACKAGE_COMMAND, $name, $data, null, true));
			$client->Close();
		});

		$ok = $client->Connect();

		unset($client);

		return $ok;
	}

	/**
	 * @return array
	 */
	private function _node () {
		$internal = $this->_cluster->Network()->Server()->ConnectionURI();
		$internal->host = Quark::HostIP();

		$external = $this->_cluster->Server()->URI();
		$external->host = Quark::HostIP();

		$clients = $this->_cluster->Server()->Clients();
		$frontend = array();
		$rps = 0;
		$num = sizeof($clients);

		foreach ($clients as $i => &$client) {
			$rps += $client->RPS();
			$frontend[] = array(
				'uri' => $client->URI()->URI(),
				'rps' => $client->RPS()
			);
		}

		unset($i, $client, $clients);

		$peers = $this->_cluster->Network()->Server()->Clients();
		$backend = array();

		foreach ($peers as $i => &$peer)
			$backend[] = $peer->URI()->URI();

		unset($i, $peer, $peers);

		return array(
			'uri' => array(
				'internal' => $internal->URI(),
				'external' => $external->URI()
			),
			'clients' => $frontend,
			'peers' => $backend,
			'rps' => $num == 0 ? 0 : $rps / $num
		);
	}

	/**
	 *
	 */
	private function _announce () {
		return $this->_cluster->Control(self::Package(
			self::PACKAGE_COMMAND,
			self::COMMAND_ANNOUNCE,
			$this->_node(), null, true
		));
	}

	/**
	 * @return array
	 */
	private function _infrastructure () {
		$data = array();
		$nodes = $this->_cluster->Controller()->Clients();

		foreach ($nodes as $i => &$node) {
			if (!isset($node->state) || !isset($node->signature)) continue;

			$data[] = $node->state;
		}

		unset($i, $node, $nodes);

		return $data;
	}

	/**
	 * @return array
	 */
	private function _monitor () {
		return $this->_cluster->Terminal()->Broadcast(self::Package(
			self::PACKAGE_COMMAND,
			self::COMMAND_INFRASTRUCTURE,
			$this->_infrastructure(), null, true
		), function (QuarkClient $terminal) {
			return isset($terminal->signature) && $terminal->signature == Quark::Config()->ClusterKey();
		});
	}

	/**
	 * @param string $source
	 * @param string $cmd
	 * @param callable $callback = null
	 * @param bool $signature = true
	 *
	 * @return bool
	 */
	private function _cmd ($source, $cmd, callable $callback = null, $signature = true) {
		if ($callback == null) return false;

		$json = self::$_json->Decode($source);

		if (!isset($json->cmd) || $json->cmd != $cmd) return false;
		if (!isset($json->data)) return false;
		if ($signature && (!isset($json->signature) || $json->signature != Quark::Config()->ClusterKey())) return false;

		$callback($json->data, isset($json->signature) ? $json->signature : null);
		unset($json);

		return true;
	}

	/**
	 * @param string $url
	 * @param string $method
	 * @param QuarkClient $client = null
	 * @param array|object $input = null
	 * @param array|object $session = null
	 */
	private function _pipe ($url, $method, QuarkClient &$client = null, $input = null, $session = null) {
		$service = null;
		$connected = $client instanceof QuarkClient;

		try {
			$service = new QuarkService($url, new QuarkJSONIOProcessor(), new QuarkJSONIOProcessor());
		}
		catch (QuarkHTTPException $e) {
			if ($this->_unknown != '')
				$service = new QuarkService($this->_unknown, new QuarkJSONIOProcessor(), new QuarkJSONIOProcessor());
		}

		if ($service != null) {
			if ($input !== null)
				$service->Input()->Data($input);

			if ($session != null) {
				$service->Input()->AuthorizationProvider(QuarkKeyValuePair::FromField($session));

				if ($connected)
					$client->Session($service->Input()->AuthorizationProvider());
			}

			if ($connected)
				$service->Input()->Remote($client->URI());

			if (!$connected || $service->Authorize())
				$service->Invoke($method, $input !== null ? array($service->Input()) : array(), $connected);

			$session = $service->Session();

			if ($connected) {
				$client->Session($session->ID());
				$client->Send(self::Package(self::PACKAGE_RESPONSE, $service->URL(), $service->Output()->Data(), $session));
			}
		}

		unset($session, $service, $connected, $input, $client, $method, $url);
	}

	/**
	 * @param string $method
	 * @param string $data
	 * @param bool $signature = false
	 * @param QuarkClient $client = null
	 */
	private function _pipeData ($method, $data, $signature = false, QuarkClient &$client = null) {
		$json = self::$_json->Decode($data);

		if ($json && isset($json->url) && ($signature ? (isset($json->signature) && $json->signature == Quark::Config()->ClusterKey()) : true))
			$this->_pipe($json->url, $method, $client, isset($json->data) ? $json->data : null, isset($json->session) ? $json->session : null);

		unset($json, $client, $data, $method);
	}

	/**
	 * @param IQuarkNetworkTransport $transport
	 * @param QuarkURI|string $external = self::URI_NODE_EXTERNAL
	 * @param QuarkURI|string $internal = self::URI_NODE_INTERNAL
	 * @param QuarkURI|string $controller = ''
	 *
	 * @return QuarkStreamEnvironment
	 */
	public static function ClusterNode (IQuarkNetworkTransport $transport, $external = self::URI_NODE_EXTERNAL, $internal = self::URI_NODE_INTERNAL, $controller = '') {
		$stream = new self();

		$stream->_transportClient = $transport;
		$stream->_cluster = QuarkCluster::NodeInstance($stream, $external, $internal, !$controller ? Quark::Config()->ClusterControllerConnect() : $controller);

		return $stream;
	}

	/**
	 * @param IQuarkNetworkTransport $transport
	 * @param QuarkURI|string $external = self::URI_CONTROLLER_EXTERNAL
	 * @param QuarkURI|string $internal = self::URI_CONTROLLER_INTERNAL
	 *
	 * @return QuarkStreamEnvironment
	 */
	public static function ClusterController (IQuarkNetworkTransport $transport, $external = self::URI_CONTROLLER_EXTERNAL, $internal = self::URI_CONTROLLER_INTERNAL) {
		$stream = new self();

		$stream->_transportTerminal = $transport;
		$stream->_cluster = QuarkCluster::ControllerInstance($stream, $external, $internal);

		return $stream;
	}

	/**
	 * @return QuarkTCPNetworkTransport
	 */
	public static function TCPProtocol () {
		if (self::$_json == null)
			self::$_json = new QuarkJSONIOProcessor();

		return new QuarkTCPNetworkTransport(array(&self::$_json, 'Batch'));
	}

	/**
	 * @param string $type = self::PACKAGE_REQUEST
	 * @param string $url = ''
	 * @param QuarkDTO|object|array $data = []
	 * @param QuarkSession $session = null
	 * @param bool $signature = false
	 *
	 * @return string
	 */
	public static function Payload ($type = self::PACKAGE_REQUEST, $url = '', $data = [], QuarkSession $session = null, $signature = false) {
		$payload = array(
			$type => $url,
			'data' => $data instanceof QuarkDTO ? $data->Data() : $data
		);

		if ($session && $session->ID())
			$payload['session'] = $session->ID()->Extract();

		if ($signature)
			$payload['signature'] = Quark::Config()->ClusterKey();

		return $payload;
	}

	/**
	 * @param string $type = PACKAGE_SERVICE
	 * @param string $url
	 * @param QuarkDTO|object|array $data
	 * @param QuarkSession $session = null
	 * @param bool $signature = false
	 *
	 * @return string
	 */
	public static function Package ($type, $url, $data, QuarkSession $session = null, $signature = false) {
		return self::$_json->Encode(self::Payload($type, $url, $data, $session, $signature));
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
	 * @param QuarkURI|string $uri = ''
	 *
	 * @return QuarkURI
	 */
	public function ServerURI ($uri = '') {
		if (func_num_args() != 0)
			$this->_cluster->Server()->URI(QuarkURI::FromURI($uri));

		return $this->_cluster->Server()->URI();
	}

	/**
	 * @param QuarkURI|string $uri = ''
	 *
	 * @return QuarkURI
	 */
	public function ControllerURI ($uri = '') {
		if (func_num_args() != 0)
			$this->_cluster->Controller()->URI(QuarkURI::FromURI($uri));

		return $this->_cluster->Controller()->URI();
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
	 * @return mixed
	 */
	public function Thread () {
		if (!$this->_cluster) return true;

		Quark::CurrentEnvironment($this);

		return $this->_cluster->NodePipe();
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
	 * @param string $url
	 * @param QuarkDTO|object|array $payload
	 *
	 * @return bool
	 */
	public function BroadcastNetwork ($url, $payload) {
		return $this->_cluster->Broadcast(self::Package(self::PACKAGE_REQUEST, $url, $payload, null, true));
	}

	/**
	 * @param string $url
	 * @param callable(QuarkSession $client) $sender
	 *
	 * @return bool
	 */
	public function BroadcastLocal ($url, callable &$sender = null) {
		$ok = true;
		$clients = $this->_cluster->Server()->Clients();

		foreach ($clients as $i => &$client) {
			$session = QuarkSession::Get($client->Session());

			$data = $sender ? call_user_func_array($sender, array(&$session)) : null;

			if ($data)
				$ok &= $client->Send(self::Package(self::PACKAGE_EVENT, $url, $data, $session));

			unset($data, $session);
		}

		unset($out, $session, $i, $client, $clients, $sender);

		return $ok;
	}

	/**
	 * @return QuarkCluster
	 */
	public function &Cluster () {
		return $this->_cluster;
	}

	/**
	 * @return IQuarkNetworkTransport
	 */
	public function &ClientTransport () {
		return $this->_transportClient;
	}

	/**
	 * @param QuarkClient $client
	 *
	 * @return mixed
	 */
	public function ClientConnect (QuarkClient $client) {
		echo '[cluster.node.client.connect] ', $client, ' -> ', $this->_cluster->Server(), "\r\n";

		$this->_announce();
		$this->_pipe($this->_connect, 'StreamConnect', $client);
	}

	/**
	 * @param QuarkClient $client
	 * @param string $data
	 *
	 * @return mixed
	 *
	 * @throws QuarkArchException
	 */
	public function ClientData (QuarkClient $client, $data) {
		$this->_pipeData('Stream', $data, false, $client);
	}

	/**
	 * @param QuarkClient $client
	 *
	 * @return mixed
	 */
	public function ClientClose (QuarkClient $client) {
		echo '[cluster.node.client.close] ', $client, ' -> ', $this->_cluster->Server(), "\r\n";

		$this->_announce();
		$this->_pipe($this->_connect, 'StreamClose', $client, null, $client->Session() ? $client->Session()->Extract() : null);
	}

	/**
	 * @return IQuarkNetworkTransport
	 */
	public function NetworkTransport () {
		return self::TCPProtocol();
	}

	/**
	 * @param QuarkClient $node
	 *
	 * @return mixed
	 */
	public function NetworkClientConnect (QuarkClient $node) {
		echo '[cluster.node.node.client.connect] ', $this->_cluster->Network()->Server(), ' <- ', $node, "\r\n";
	}

	/**
	 * @param QuarkClient $node
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function NetworkClientData (QuarkClient $node, $data) {
		// TODO: Implement NetworkClientData() method.
	}

	/**
	 * @param QuarkClient $node
	 *
	 * @return mixed
	 */
	public function NetworkClientClose (QuarkClient $node) {
		echo '[cluster.node.node.client.close] ', $this->_cluster->Network()->Server(), ' <- ', $node, "\r\n";
	}

	/**
	 * @param QuarkClient $node
	 *
	 * @return mixed
	 */
	public function NetworkServerConnect (QuarkClient $node) {
		echo '[cluster.node.node.server.connect] ', $node, ' -> ', $this->_cluster->Network()->Server(), "\r\n";

		$this->_announce();
	}

	/**
	 * @param QuarkClient $node
	 * @param string $data
	 *
	 * @return mixed
	 *
	 * @throws QuarkArchException
	 */
	public function NetworkServerData (QuarkClient $node = null, $data) {
		$this->_pipeData('StreamNetwork', $data, true);
	}

	/**
	 * @param QuarkClient $node
	 *
	 * @return mixed
	 */
	public function NetworkServerClose (QuarkClient $node) {
		echo '[cluster.node.node.server.close] ', $node, ' -> ', $this->_cluster->Network()->Server(), "\r\n";

		$this->_announce();
	}

	/**
	 * @return IQuarkNetworkTransport
	 */
	public function ControllerTransport () {
		return self::TCPProtocol();
	}

	/**
	 * @param QuarkClient $controller
	 *
	 * @return mixed
	 */
	public function ControllerClientConnect (QuarkClient $controller) {
		echo '[cluster.node.controller.connect] ', $this->_cluster->Controller(), ' <- ', $controller, "\r\n";

		$this->_announce();
	}

	/**
	 * @param QuarkClient $controller
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function ControllerClientData (QuarkClient $controller, $data) {
		$this->_cmd($data, self::COMMAND_ANNOUNCE, function ($node) {
			if (!isset($node->internal) || !isset($node->external)) return;

			$this->_cluster->Network()->Peer($node->internal);
		});

		$this->_cmd($data, self::COMMAND_BROADCAST, function ($payload) {
			if (!isset($payload->url) || !isset($payload->data)) return;

			$this->_cluster->Broadcast(self::Package(self::PACKAGE_REQUEST, $payload->url, $payload->data, null, true));
		});
	}

	/**
	 * @param QuarkClient $controller
	 *
	 * @return mixed
	 */
	public function ControllerClientClose (QuarkClient $controller) {
		echo '[cluster.node.controller.close] ', $this->_cluster->Controller(), ' <- ', $controller, "\r\n";
	}

	/**
	 * @param QuarkClient $node
	 *
	 * @return mixed
	 */
	public function ControllerServerConnect (QuarkClient $node) {
		echo '[cluster.controller.node.connect] ', $node, ' -> ', $this->_cluster->Controller(), "\r\n";

		$this->_monitor();
	}

	/**
	 * @param QuarkClient $node
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function ControllerServerData (QuarkClient $node, $data) {
		$this->_cmd($data, self::COMMAND_BROADCAST, function ($payload) {
			if (!isset($payload->url) || !isset($payload->data)) return;

			$this->_cluster->Broadcast(self::Package(self::PACKAGE_COMMAND, self::COMMAND_BROADCAST, $payload, null, true));
		});

		$this->_cmd($data, self::COMMAND_ANNOUNCE, function ($state, $signature) use (&$node) {
			if (!isset($state->uri->internal) || !isset($state->uri->external)) return;
			if (!isset($state->clients) || !is_array($state->clients)) return;
			if (!isset($state->peers) || !is_array($state->peers)) return;

			/**
			 * @var \StdClass $node
			 */
			$node->state = $state;
			$node->signature = $signature;

			$this->_monitor();
		});
	}

	/**
	 * @param QuarkClient $node
	 *
	 * @return mixed
	 */
	public function ControllerServerClose (QuarkClient $node) {
		echo '[cluster.controller.node.close] ', $node, ' -> ', $this->_cluster->Controller(), "\r\n";

		$this->_monitor();
	}

	/**
	 * @return IQuarkNetworkTransport
	 */
	public function &TerminalTransport () {
		return $this->_transportTerminal;
	}

	/**
	 * @param QuarkClient $terminal
	 *
	 * @return mixed
	 */
	public function TerminalConnect (QuarkClient $terminal) {
		echo '[cluster.controller.terminal.connect] ', $terminal, ' -> ', $this->_cluster->Terminal(), "\r\n";
	}

	/**
	 * @param QuarkClient $terminal
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function TerminalData (QuarkClient $terminal, $data) {
		/** @noinspection PhpUnusedParameterInspection */
		$this->_cmd($data, self::COMMAND_AUTHORIZE, function ($client, $signature) use (&$terminal) {
			/**
			 * @var \StdClass|QuarkClient $terminal
			 */
			$terminal->signature = $signature;
			$terminal->Send(self::Package(
				self::PACKAGE_COMMAND,
				self::COMMAND_INFRASTRUCTURE,
				$this->_infrastructure(), null, true
			));
		});

		$this->_cmd($data, self::COMMAND_ENDPOINT, function () use (&$terminal) {
			$nodes = $this->_infrastructure();

			/**
			 * @var \StdClass $endpoint
			 */
			$endpoint = sizeof($nodes) != 0 ? $nodes[0] : null;

			$terminal->Send(self::Package(
				self::PACKAGE_COMMAND,
				self::COMMAND_ENDPOINT,
				$endpoint == null ? null : $endpoint->external, null, true
			));

			$terminal->Close();
		}, false);
	}

	/**
	 * @param QuarkClient $terminal
	 *
	 * @return mixed
	 */
	public function TerminalClose (QuarkClient $terminal) {
		echo '[cluster.controller.terminal.close] ', $terminal, ' -> ', $this->_cluster->Terminal(), "\r\n";
	}
}

/**
 * Class QuarkURI
 *
 * @package Quark
 */
class QuarkURI {
	const SCHEME_HTTP = 'http';
	const SCHEME_HTTPS = 'https';

	const HOST_LOCALHOST = '127.0.0.1';
	const HOST_ALL_INTERFACES = '0.0.0.0';

	/**
	 * @var string $scheme
	 */
	public $scheme;

	/**
	 * @var string $user
	 */
	public $user;

	/**
	 * @var string $pass
	 */
	public $pass;

	/**
	 * @var string $host
	 */
	public $host;

	/**
	 * @var string|int $port
	 */
	public $port;

	/**
	 * @var string $query
	 */
	public $query;

	/**
	 * @var string $path
	 */
	public $path;

	/**
	 * @var string $fragment
	 */
	public $fragment;

	/**
	 * @var string|array $options
	 */
	public $options;

	/**
	 * @var array $_route;
	 */
	private $_route = array();

	/**
	 * @var array $_transports
	 */
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

	/**
	 * @var array $_ports
	 */
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
	 * @param QuarkURI|string|null $uri = ''
	 * @param bool $local
	 *
	 * @return QuarkURI|null
	 */
	public static function FromURI ($uri = '', $local = true) {
		if ($uri == null) $uri = '';
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
	 * @param bool $endSlash = false
	 *
	 * @return QuarkURI
	 */
	public static function FromFile ($location = '', $endSlash = false) {
		$uri = new self();
		$uri->path = Quark::NormalizePath($location, $endSlash);
		return $uri;
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
		return $this->Hostname()
			. ($this->path !== null ? Quark::NormalizePath('/' . $this->path, false) : '')
			. ($full ? '/?' . $this->query : '');
	}

	/**
	 * @return string
	 */
	public function Hostname () {
		if (strpos(strtolower($this->scheme), strtolower('HTTP/')) !== false)
			$this->scheme = 'http';

		return
			($this->scheme !== null ? $this->scheme : 'http')
			. '://'
			. ($this->user !== null ? $this->user . ($this->pass !== null ? ':' . $this->pass : '') . '@' : '')
			. $this->host
			. ($this->port !== null && $this->port != 80 ? ':' . $this->port : '');
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
		$route = explode('/', trim(Quark::NormalizePath(preg_replace('#\.php$#Uis', '', $query), false)));
		$buffer = array();

		foreach ($route as $component)
			if (strlen(trim($component)) != 0) $buffer[] = trim($component);

		$route = $buffer;
		unset($buffer);

		return $route;
	}

	/**
	 * @param string $uri = ''
	 * @param array $query = []
	 * @param bool $weak = false
	 *
	 * @return string
	 */
	public static function AppendQuery ($uri = '', $query = [], $weak = false) {
		$params = http_build_query($query);

		return $weak && strlen($params) == 0
			? ''
			: (strpos($uri, '?') !== false ? '&' : '?') . $params;
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

	/**
	 * @param string $host
	 *
	 * @return bool
	 */
	public function IsHost ($host = '') {
		return $this->host == $host;
	}

	/**
	 * @return bool
	 */
	public function IsHostLocal () {
		return $this->host == self::HOST_LOCALHOST || $this->host == Quark::HostIP();
	}

	/**
	 * Formats of `$network`:
	 *  - CIDR  192.168.0.0/24
	 *  - CIDR  192.168.0.0/255.255.255.0
	 *  - Range 192.168.1.0-192.168.1.254
	 *
	 * https://pgregg.com/blog/2009/04/php-algorithms-determining-if-an-ip-is-within-a-specific-range/
	 * http://mycrimea.su/partners/web/access/ipsearch.php
	 *
	 * @param string $ip
	 * @param string $network
	 *
	 * @return bool
	 */
	public static function IsHostFromNetwork ($ip = '', $network = '') {
		$ip = ip2long($ip);

		if ($ip === false) return false;

		if (strstr($network, '/')) {
			$net = explode('/', $network);

			if (sizeof($net) < 2) return false;

			$network = ip2long($net[0]);
			$mask = ip2long(strpos($net[1], '.') !== false
				? Quark::IP(str_replace('*', '0', $net[1]))
				: Quark::CIDR($net[1])
			);

			return (($ip & $mask) == $network);
		}

		if (strstr($network, '-')) {
			$net = explode('-', $network);

			if (sizeof($net) < 2) return false;

			$min = ip2long(Quark::IP(str_replace('*', '0', $net[0])));
			$max = ip2long(Quark::IP(str_replace('*', '0', $net[1])));

			return $ip >= $min && $ip <= $max;
		}

		return false;
	}

	/**
	 * Info provided by http://ipinfo.io Free plan limit 1000 daily requests
	 *
	 * @param string $state
	 * @param bool $allowLocalhost = true
	 *
	 * @return bool
	 */
	public function IsHostState ($state = '', $allowLocalhost = true) {
		$ip = Quark::IPInfo($this->host);

		if (!isset($ip->country)) {
			if ($allowLocalhost && $this->IsHostLocal()) return true;
			else return false;
		}

		return $ip->country == $state;
	}

	/**
	 * @return string
	 */
	public function ConnectionString () {
		$uri = clone $this;

		if ($uri->host == self::HOST_ALL_INTERFACES)
			$uri->host = Quark::HostIP();

		return $uri->URI();
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
	const HTTP_PROTOCOL_REQUEST = '#^(.*) (.*) (.*)\n(.*)\n\s\n(.*)$#Uis';
	const HTTP_PROTOCOL_RESPONSE = '#^(.*) (.*)\n(.*)\n\s\n(.*)$#Uis';

	const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';
	const METHOD_PUT = 'PUT';
	const METHOD_PATCH = 'PATCH';
	const METHOD_DELETE = 'DELETE';

	const HEADER_HOST = 'Host';
	const HEADER_ACCEPT = 'Accept';
	const HEADER_ACCEPT_LANGUAGE = 'Accept-Language';
	const HEADER_ACCEPT_ENCODING = 'Accept-Encoding';
	const HEADER_ACCEPT_RANGES = 'Accept-Ranges';
	const HEADER_CACHE_CONTROL = 'Cache-Control';
	const HEADER_CONTENT_LENGTH = 'Content-Length';
	const HEADER_CONTENT_TYPE = 'Content-Type';
	const HEADER_CONTENT_TRANSFER_ENCODING = 'Content-Transfer-Encoding';
	const HEADER_CONTENT_DISPOSITION = 'Content-Disposition';
	const HEADER_CONTENT_DESCRIPTION = 'Content-Description';
	const HEADER_CONTENT_LANGUAGE = 'Content-Language';
	const HEADER_COOKIE = 'Cookie';
	const HEADER_CONNECTION = 'Connection';
	const HEADER_ETAG = 'ETag';
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
	const HEADER_KEEP_ALIVE = 'Keep-Alive';
	const HEADER_LAST_MODIFIED = 'Last-Modified';
	const HEADER_SERVER = 'Server';
	const HEADER_DATE = 'Date';
	const HEADER_WWW_AUTHENTICATE = 'WWW-Authenticate';

	const STATUS_200_OK = '200 OK';
	const STATUS_302_FOUND = '302 Found';
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

	const RANGES_BYTES = 'bytes';

	const KEY_AUTHORIZATION = '_a';
	const KEY_SIGNATURE = '_s';

	const RESPONSE_BUFFER = 4096;

	/**
	 * @var string $_raw = ''
	 */
	private $_raw = '';

	/**
	 * @var string $_rawData = ''
	 */
	private $_rawData = '';

	/**
	 * @var IQuarkIOProcessor $_processor = null
	 */
	private $_processor = null;

	/**
	 * @var string $_protocol = self::HTTP_VERSION_1_0
	 */
	private $_protocol = self::HTTP_VERSION_1_0;

	/**
	 * @var QuarkURI $_uri = null
	 */
	private $_uri = null;

	/**
	 * @var QuarkURI $_remote = null
	 */
	private $_remote = null;

	/**
	 * @var string $_status = self::STATUS_200_OK
	 */
	private $_status = self::STATUS_200_OK;

	/**
	 * @var string $_method = ''
	 */
	private $_method = '';

	/**
	 * @var array $_headers = []
	 */
	private $_headers = array();

	/**
	 * @var QuarkCookie[] $_cookies = []
	 */
	private $_cookies = array();

	/**
	 * @var QuarkLanguage[] $_languages = []
	 */
	private $_languages = array();

	/**
	 * @var string $_agent = ''
	 */
	private $_agent = '';

	/**
	 * @var string $_boundary = ''
	 */
	private $_boundary = '';

	/**
	 * @var string $_encoding
	 */
	private $_encoding = self::TRANSFER_ENCODING_BINARY;

	/**
	 * @var bool $_multipart = false
	 */
	private $_multipart = false;

	/**
	 * @var int|string $_length = 0
	 */
	private $_length = 0;

	/**
	 * @var string $_charset = self:: CHARSET_UTF8
	 */
	private $_charset = self:: CHARSET_UTF8;

	/**
	 * @var mixed $_data = ''
	 */
	private $_data = '';

	/**
	 * @var QuarkFile[] $_files = []
	 */
	private $_files = array();

	/**
	 * @var QuarkKeyValuePair $_authorization = null
	 */
	private $_authorization = null;

	/**
	 * @var QuarkKeyValuePair $_session = null
	 */
	private $_session = null;

	/**
	 * @var string $_signature = ''
	 */
	private $_signature = '';

	/**
	 * @var bool $_fullControl = false
	 */
	private $_fullControl = false;

	/**
	 * @var null $_null = null
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
		$response->Status(self::STATUS_302_FOUND);
		$response->Header(self::HEADER_LOCATION, $url);
		return $response;
	}

	/**
	 * @param string $status
	 *
	 * @return QuarkDTO
	 */
	public static function ForStatus ($status) {
		$response = new self();
		$response->Status($status);
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
	 * @param bool $status = true
	 *
	 * @return QuarkDTO
	 */
	public function Merge ($data = [], $processor = true, $status = true) {
		if (!($data instanceof QuarkDTO)) $this->MergeData($data);
		else {
			$this->_method = $data->Method();
			$this->_boundary = $data->Boundary();
			$this->_headers += $data->Headers();
			$this->_cookies += $data->Cookies();
			$this->_languages += $data->Languages();
			$this->_uri = $data->URI() == null ? $this->_uri : $data->URI();
			$this->_remote = $data->Remote() == null ? $this->_remote : $data->Remote();
			$this->_charset = $data->Charset();

			if ($status)
				$this->_status = $data->Status();

			if ($processor)
				$this->_processor = $data->Processor();

			$this->MergeData($data->Data());
		}

		$auth = self::KEY_AUTHORIZATION;
		$sign = self::KEY_SIGNATURE;

		if (isset($this->_data->$auth))
			$this->AuthorizationProvider(QuarkKeyValuePair::FromField($this->_data->$auth));

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
		if ($this->_data instanceof QuarkView || $data === null) return $this->_data;

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
			$this->_method = strtoupper(trim($method));

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
			$this->_status = trim($code . (func_num_args() == 2 && is_scalar($text) ? ' ' . $text : ''));

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

			case self::HEADER_CONTENT_LANGUAGE:
				$this->_languages = QuarkLanguage::FromContentLanguage($value);
				break;

			case self::HEADER_CONTENT_LENGTH:
				$this->_length = $value;
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
	 * @param int $quantity = 0
	 *
	 * @return QuarkLanguage
	 */
	public function GetLanguageByQuantity ($quantity = 0) {
		return isset($this->_languages[$quantity]) ? $this->_languages[$quantity] : null;
	}

	/**
	 * @param int $quantity = 0
	 *
	 * @return string
	 */
	public function ExpectedLanguage ($quantity = 0) {
		$language = $this->GetLanguageByQuantity($quantity);

		return $language == null ? QuarkLocalizedString::LANGUAGE_ANY : $language->Name();
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

			$sign = self::KEY_SIGNATURE;

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
	 * @param string $raw
	 *
	 * @return string
	 */
	public function Raw ($raw = '') {
		if (func_num_args() != 0)
			$this->_raw = $raw;

		return $this->_raw;
	}

	/**
	 * @param string $raw
	 *
	 * @return string
	 */
	public function RawData ($raw = '') {
		if (func_num_args() != 0)
			$this->_rawData = $raw;

		return $this->_rawData;
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
	 * @param bool $fullControl = false
	 *
	 * @return bool
	 */
	public function FullControl ($fullControl = false) {
		if (func_num_args() != 0)
			$this->_fullControl = $fullControl;

		return $this->_fullControl;
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

			$this->_data = (object)$this->_data;

			$auth = self::KEY_AUTHORIZATION;
			$sign = self::KEY_SIGNATURE;

			// get keys from GET params
			$this->AuthorizationProvider(isset($this->_data->$auth) ? QuarkKeyValuePair::FromField($this->_data->$auth) : null);
			$this->Signature(isset($this->_data->$sign) ? $this->_data->$sign : '');

			if ($this->_processor == null)
				$this->_processor = new QuarkFormIOProcessor();

			$this->_unserializeHeaders($found[4]);
			$this->_unserializeBody($found[5]);

			// re-fill keys, if they are transported in body
			$this->AuthorizationProvider(isset($this->_data->$auth) ? QuarkKeyValuePair::FromField($this->_data->$auth) : $this->AuthorizationProvider());
			$this->Signature(isset($this->_data->$sign) ? $this->_data->$sign : $this->Signature());

			$this->_rawData = $found[5];
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

		if (preg_match(self::HTTP_PROTOCOL_RESPONSE, substr($raw, 0, self::RESPONSE_BUFFER), $found)) {
			$this->_rawData = $found[4] != '' ? substr($raw, strpos($raw, $found[4])) : '';

			$this->Protocol($found[1]);
			$this->Status($found[2]);

			if ($this->_processor == null)
				$this->_processor = new QuarkHTMLIOProcessor();

			$this->_unserializeHeaders($found[3]);
			$this->_unserializeBody($this->_rawData);
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
		if ($client && $this->_uri == null) return $str ? '' : array();

		$this->_serializeBody($client);

		$headers = array($client
			? $this->_method . ' ' . $this->_uri->Query() . ' ' . $this->_protocol
			: $this->_protocol . ' ' . $this->_status
		);

		$typeSet = isset($this->_headers[self::HEADER_CONTENT_TYPE]);
		$typeValue = $typeSet ? $this->_headers[self::HEADER_CONTENT_TYPE] : '';

		if (!isset($this->_headers[self::HEADER_AUTHORIZATION]) && $this->_authorization != null)
			$this->_headers[self::HEADER_AUTHORIZATION] = $this->_authorization->Key() . ' ' . $this->_authorization->Value();

		if (!$this->_fullControl) {
			if (!isset($this->_headers[self::HEADER_CONTENT_LENGTH]))
				$this->_headers[self::HEADER_CONTENT_LENGTH] = $this->_length;

			$this->_headers[self::HEADER_CONTENT_TYPE] = $typeSet
				? $typeValue
				: ($this->_multipart
					? ($client ? self::MULTIPART_FORM_DATA : self::MULTIPART_MIXED) . '; boundary=' . $this->_boundary
					: $this->_processor->MimeType() . '; charset=' . $this->_charset
				);
		}

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
			elseif ($this->_data instanceof QuarkFile) {
				$this->Header(QuarkDTO::HEADER_CONTENT_TYPE, $this->_data->type);
				$out = $this->_data->Content();
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
			$this->_rawData = $out;
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
		if (!$this->_multipart || strpos($raw, '--' . $this->_boundary) === false) {
			$this->_data = QuarkObject::Normalize($this->_data, $this->_processor->Decode($raw));
		}
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
}

/**
 * Class QuarkTCPNetworkTransport
 *
 * @package Quark
 */
class QuarkTCPNetworkTransport implements IQuarkNetworkTransport {
	/**
	 * @var string $buffer
	 */
	private $_buffer;

	/**
	 * @var callable $_divider
	 */
	private $_divider;

	/**
	 * @param callable $divider
	 */
	public function __construct (callable $divider = null) {
		$this->Divider($divider);
	}

	/**
	 * @param callable $divider
	 *
	 * @return callable
	 */
	public function Divider (callable $divider = null) {
		if (func_num_args() != 0)
			$this->_divider = $divider;

		return $this->_divider;
	}

	/**
	 * @param QuarkClient &$client
	 *
	 * @return mixed
	 */
	public function EventConnect (QuarkClient &$client) {
		$client->TriggerConnect();
	}

	/**
	 * @param QuarkClient &$client
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function EventData (QuarkClient &$client, $data) {
		if ($this->_divider == null) {
			$client->TriggerData($data);
			return;
		}

		$this->_buffer .= $data;

		$parts = call_user_func_array($this->_divider, array(&$this->_buffer));
		$size = sizeof($parts);

		$this->_buffer = '';

		if ($size > 1) {
			$this->_buffer = $parts[$size - 1];
			unset($parts[$size - 1]);
		}

		unset($size);

		foreach ($parts as $i => &$part)
			$client->TriggerData($part);

		unset($i, $part, $parts);
	}

	/**
	 * @param QuarkClient &$client
	 *
	 * @return mixed
	 */
	public function EventClose (QuarkClient &$client) {
		$client->TriggerClose();
	}

	/**
	 * @param string $data
	 *
	 * @return string
	 */
	public function Send ($data) {
		return $data;
	}
}

/**
 * Class QuarkHTTPClient
 *
 * @package Quark
 */
class QuarkHTTPClient {
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
	 * @param QuarkURI|string $uri
	 * @param QuarkDTO $request
	 * @param QuarkDTO $response
	 * @param QuarkCertificate $certificate
	 * @param int $timeout = 10
	 *
	 * @return QuarkDTO|bool
	 */
	public static function To ($uri, QuarkDTO $request, QuarkDTO $response = null, QuarkCertificate $certificate = null, $timeout = 10) {
		$http = new self($request, $response);
		$client = new QuarkClient($uri, new QuarkTCPNetworkTransport(), $certificate, $timeout);

		$client->On(QuarkClient::EVENT_CONNECT, function (QuarkClient $client) use (&$http) {
			if ($http->_request == null) return false;

			if ($http->_response == null)
				$http->_response = new QuarkDTO();

			$http->_request->URI($client->URI());
			$http->_response->URI($client->URI());

			$http->_request->Remote($client->ConnectionURI(true));
			$http->_response->Remote($client->ConnectionURI(true));

			$http->_response->Method($http->_request->Method());

			$request = $http->_request->SerializeRequest();

			return $client->Send($request);
		});

		$client->On(QuarkClient::EVENT_DATA, function (QuarkClient $client, $data) use (&$http) {
			$http->_response->UnserializeResponse($data);

			return $client->Close();
		});

		if (!$client->Connect()) return false;

		$client->Pipe();

		return $http->Response();
	}

	/**
	 * @param QuarkURI|string $uri
	 * @param QuarkDTO $request
	 * @param QuarkDTO $response
	 * @param QuarkCertificate $certificate
	 * @param int $timeout = 10
	 *
	 * @return QuarkFile
	 */
	public static function Download ($uri, QuarkDTO $request = null, QuarkDTO $response = null, QuarkCertificate $certificate = null, $timeout = 10) {
		if ($request == null)
			$request = QuarkDTO::ForGET();

		$out = self::To($uri, $request, $response, $certificate, $timeout);

		if (!$out || $out->Status() != QuarkDTO::STATUS_200_OK) return null;

		$file = new QuarkFile();

		$uri = ($uri instanceof QuarkURI ? $uri : QuarkURI::FromURI($uri));

		$name = array_reverse($uri->Route())[0];

		$file->Content($out->RawData());
		$file->type = QuarkFile::MimeOf($file->Content());
		$file->extension = QuarkFile::ExtensionByMime($file->type);
		$file->name = $name . (strpos($name, '.') === false ? $file->extension : '');

		return $file;
	}
}

/**
 * Class QuarkHTTPServer
 *
 * @package Quark
 */
class QuarkHTTPServer {
	const DEFAULT_ADDR = 'http://127.0.0.1:80';

	/**
	 * @var QuarkServer $_server
	 */
	private $_server;

	/**
	 * @var callable $_request
	 */
	private $_request;

	/**
	 * @param QuarkURI|string $uri = self::DEFAULT_ADDR
	 * @param callable(QuarkDTO $request):string $request = null
	 * @param QuarkCertificate $certificate = null
	 * @param int $timeout = 0
	 */
	public function __construct ($uri = self::DEFAULT_ADDR, callable $request = null, QuarkCertificate $certificate = null, $timeout = 0) {
		$this->_server = new QuarkServer($uri, new QuarkTCPNetworkTransport(), $certificate, $timeout);

		$this->_server->On(QuarkClient::EVENT_DATA, function (QuarkClient $client, $data) {
			$request = new QuarkDTO();
			$request->UnserializeRequest($data);

			$client->Send(call_user_func_array($this->_request, array(&$request)));
		});

		$this->Request($request);
	}

	/**
	 * @return bool
	 */
	public function Bind () {
		return $this->_server->Bind();
	}

	/**
	 * @return bool
	 */
	public function Pipe () {
		return $this->_server->Pipe();
	}

	/**
	 * @param callable $request = null
	 *
	 * @return callable
	 */
	public function &Request (callable $request = null) {
		if (func_num_args() != 0 && $request != null)
			$this->_request = $request;

		return $this->_request;
	}

	/**
	 * @param QuarkService $service
	 * @param array $input
	 *
	 * @return string
	 * @throws QuarkArchException
	 */
	public static function ServicePipeline (QuarkService &$service, &$input = []) {
		$method = ucfirst(strtolower($service->Input()->Method()));

		if (!($service->Service() instanceof IQuarkHTTPService))
			throw new QuarkArchException('Method ' . $method . ' is not allowed for service ' . get_class($service->Service()));

		if (!method_exists($service->Service(), $method) && $service->Service() instanceof IQuarkAnyService)
			$method = 'Any';

		ob_start();

		if ($service->Authorize(true))
			$service->Invoke($method, $input !== null ? array($service->Input()) : array(), true);

		echo $service->Output()->SerializeResponseBody();
		$length = ob_get_length();

		if ($length !== false)
			$service->Output()->Header(QuarkDTO::HEADER_CONTENT_LENGTH, $length);

		return ob_get_clean();
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

	const TYPE_APPLICATION_OCTET_STREAM = 'application/octet-stream';

	const MODE_DEFAULT = null;
	const MODE_ANYONE = 0777;
	const MODE_GROUP = 0771;
	const MODE_USER = 0711;

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
	 * @param string $location
	 * @warning memory leak in native `finfo_file` realization
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
	 * @param string $content
	 *
	 * @return mixed
	 */
	public static function MimeOf ($content) {
		if (!$content) return false;

		$info = finfo_open(FILEINFO_MIME_TYPE);
		$type = finfo_buffer($info, $content);
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
	 * @return string
	 */
	public function __toString () {
		return $this->WebLocation();
	}

	/**
	 * @param string $location
	 * @param string $name
	 *
	 * @return string
	 */
	public function Location ($location = '', $name = '') {
		if (func_num_args() != 0) {
			$this->location = $location;
			$this->name = $name ? $name : array_reverse(explode('/', (string)$this->location))[0];
			$this->parent = str_replace($this->name, '', $this->location);
		}

		return $this->location;
	}

	/**
	 * @warning memory leak in native `file_exists` realization
	 *
	 * @return bool
	 */
	public function Exists () {
		return is_file($this->location);
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
			$this->type = self::MimeOf($this->_content);
			$this->_loaded = true;
		}

		return $this;
	}

	/**
	 * @param int $mode = self::MODE_DEFAULT
	 */
	private function _followParent ($mode = self::MODE_DEFAULT) {
		if (!is_dir($this->parent) && !is_file($this->parent))
			mkdir($this->parent, $mode, true);
	}

	/**
	 * @param int $mode = self::MODE_DEFAULT
	 * http://php.net/manual/ru/function.mkdir.php#114960
	 *
	 * @return bool
	 */
	public function SaveContent ($mode = self::MODE_DEFAULT) {
		$this->_followParent($mode);

		return file_put_contents($this->location, $this->_content, LOCK_EX) !== false;
	}

	/**
	 * @return string
	 */
	public function WebLocation () {
		return Quark::WebLocation($this->location);
	}

	/**
	 * @param string $content
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
	 * @param bool $mime = true
	 * @param int $mode = self::MODE_DEFAULT
	 *
	 * @return bool
	 */
	public function Upload ($mime = true, $mode = self::MODE_DEFAULT) {
		if ($mime) {
			$ext = self::ExtensionByMime(self::Mime($this->tmp_name));
			$this->location .= $ext ? '.' . $ext : '';
		}

		$this->_followParent($mode);

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
	 * @return QuarkCultureCustom|QuarkCultureISO
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
			return Quark::Log($exception->message, $exception->lvl) != Quark::LOG_FATAL && $exception->lvl;

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
	 * @var string $_log = ''
	 */
	public $log = '';

	/**
	 * @param int $status = 500
	 * @param string $message
	 * @param string $log = ''
	 */
	public function __construct ($status = 500, $message = '', $log = '') {
		$this->lvl = Quark::LOG_FATAL;
		$this->message = $message;

		$this->status = $status;
		$this->log = func_num_args() == 3 ? $log : $message;
	}

	/**
	 * @return string
	 */
	public function Status () {
		return trim($this->status . ' ' . $this->message);
	}

	/**
	 * @param string $status
	 * @param string $log = ''
	 *
	 * @return QuarkHTTPException
	 */
	public static function ForStatus ($status, $log = '') {
		$exception = new self();
		$exception->status = $status;
		$exception->log = $log;

		return $exception;
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
	 *
	 * @return string
	 */
	public function Encode($data);

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Decode($raw);

	/**
	 * @param string $raw
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
	 *
	 * @return string
	 */
	public function Encode ($data) { return is_scalar($data) ? (string)$data : ''; }

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Decode ($raw) { return $raw; }

	/**
	 * @param string $raw
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
	const TYPE_KEY = 'text/html';

	/**
	 * @return string
	 */
	public function MimeType () { return 'text/html'; }

	/**
	 * @param $data
	 *
	 * @return string
	 */
	public function Encode ($data) {
		if ($data instanceof QuarkView)
			return $data->Compile();

		if (is_string($data)) return $data;

		$data = (array)$data;

		return isset($data[self::TYPE_KEY]) ? $data[self::TYPE_KEY] : '';
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Decode ($raw) { return $raw; }

	/**
	 * @param string $raw
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
	 *
	 * @return string
	 */
	public function Encode ($data) {
		return is_array($data) ? http_build_query($data) : '';
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
	 * @param string $raw
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
	 *
	 * @return string
	 */
	public function Encode ($data) { return \json_encode($data); }

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Decode ($raw) { return \json_decode($raw); }

	/**
	 * @param string $raw
	 *
	 * @return mixed
	 */
	public function Batch ($raw) {
		$raw = substr($raw, 0, 8192);
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
	 *
	 * @return string
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
	 * @param string $raw
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
	 * @return string
	 */
	public function Encode ($data) {
		return \wddx_serialize_value($data);
	}

	/**
	 * @param string $raw
	 *
	 * @return mixed
	 */
	public function Batch ($raw) { return $raw; }
}

/**
 * Class QuarkCertificate
 *
 * @property string $countryName
 * @property string $stateOrProvinceName
 * @property string $localityName
 * @property string $organizationName
 * @property string $organizationalUnitName
 * @property string $commonName
 * @property string $emailAddress
 *
 * @package Quark
 */
class QuarkCertificate extends QuarkFile {
	/**
	 * @var array $_allowed
	 */
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

	/**
	 * @var string $_passphrase = ''
	 */
	private $_passphrase = '';

	/**
	 * @var string $_error = ''
	 */
	private $_error = '';

	/**
	 * @param string $location
	 * @param string $passphrase
	 */
	public function __construct ($location = '', $passphrase = '') {
		parent::__construct($location);
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
	 * @return string
	 */
	public function Error () {
		return $this->_error;
	}

	/**
	 * @return bool
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

		$this->_error = openssl_error_string();
		$this->_content = implode($pem);

		return $this->_error == '';
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

		return $this->_provider->Escape($field);
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
				$value = $this->Condition($rule, ' AND ');

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
	/**
	 * @var array $_trim = []
	 */
	private $_trim = array();

	/**
	 * @var array $__trim
	 */
	private static $__trim = array(
		',',';','?',':',
		'(',')','{','}','[',']',
		'+','*','/',
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