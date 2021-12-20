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
	const UNIT_TERABYTE = 1099511627776;
	const UNIT_PETABYTE = 1125899906842624;

	const ALPHABET_ALL = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	const ALPHABET_LETTERS = 'abcdefghijklmnopqrstuvwxyz';
	const ALPHABET_PASSWORD = 'abcdefgpqstxyzABCDEFGHKMNPQRSTXYZ123456789';
	const ALPHABET_PASSWORD_LOW = 'abcdefgpqstxyz123456789';
	const ALPHABET_PASSWORD_LETTERS = 'abcdefgpqstxyz';
	const ALPHABET_BASE64 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';

	const TEMP_FILE_PREFIX = 'qtf';

	const WEB_MANAGEMENT = 'http://127.0.0.1:25052';

	/**
	 * @var bool $_init = false
	 */
	private static $_init = false;

	/**
	 * @var int $_argc = 0
	 */
	private static $_argc = 0;

	/**
	 * @var string[] $_argv = []
	 */
	private static $_argv = array();
	
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
	 * @var string $_currentLanguage = ''
	 */
	private static $_currentLanguage = '';

	/**
	 * @var float $_execTime = 0.0
	 */
	private static $_execTime = 0.0;

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
	 * @return bool
	 */
	public static function _init () {
		if (!self::$_init) {
			if (!ini_get('date.timezone')) {
				ini_set('date.timezone', 'UTC');
				self::Log('Missed "date.timezone" in PHP configuration. UTC used.', self::LOG_WARN);
			}
			
			spl_autoload_extensions('.php');
			
			self::Import(__DIR__, function ($class) { return substr($class, 6); });
			self::Import(self::Host());
			
			self::$_init = true;

			self::$_argc = isset($_SERVER['argc']) ? $_SERVER['argc'] : 0;
			self::$_argv = isset($_SERVER['argv']) ? $_SERVER['argv'] : array();

			self::Environment(self::CLI()
				? new QuarkCLIEnvironment(self::$_argc, self::$_argv)
				: new QuarkFPMEnvironment(self::$_argc, self::$_argv)
			);
		}
		
		return self::$_init;
	}

	/**
	 * @param QuarkConfig $config = null
	 *
	 * @throws QuarkArchException
	 */
	public static function Run (QuarkConfig $config = null) {
		self::$_execTime = microtime(true);
		
		self::$_config = $config ? $config : new QuarkConfig();
		self::$_config->ConfigReady();

		$threads = new QuarkThreadSet(self::$_argc, self::$_argv);

		$threads->Threads(self::$_environment);

		$threads->On(QuarkThreadSet::EVENT_AFTER_INVOKE, function () {
			$timers = QuarkTimer::Timers();

			foreach ($timers as $i => &$timer)
				if ($timer) $timer->Invoke();

			self::ContainerFree();
		});

		if (!self::CLI() || !self::$_environment[0]->EnvironmentQueued()) $threads->Invoke();
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
	 * @param $string
	 * @param bool $values = true
	 *
	 * @return int[]|array
	 */
	public static function StringToBytes ($string, $values = true) {
		$bytes = unpack('C*', $string);

		return $values ? array_values($bytes) : $bytes;
	}

	/**
	 * @param int[] $bytes
	 *
	 * @return string
	 */
	public static function BytesToString ($bytes = []) {
		$out = '';

		foreach ($bytes as $i => &$byte)
			$out .= chr($byte);

		return $out;
	}

	/**
	 * @param string $input = ''
	 * @param int $size = 8
	 *
	 * @return string
	 */
	public static function BitsToString ($input = '', $size = 8) {
		return str_pad(decbin($input), $size, 0, STR_PAD_LEFT);
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
		return self::NormalizePath(realpath(getcwd()), $endSlash);
	}

	/**
	 * @param bool $computed = true
	 *
	 * @return string
	 */
	public static function WebHost ($computed = true) {
		return self::$_config->WebHost()->URI(false, $computed);
	}

	/**
	 * @return string
	 */
	public static function WebOffset () {
		return substr(self::WebHost(), strlen(self::WebHost(false)));
	}

	/**
	 * @param string $path
	 * @param bool $full = true
	 *
	 * @return string
	 */
	public static function WebLocation ($path, $full = true) {
		if (!$full && strlen($path) != 0 && $path[0] == '/')
			$path = Quark::Host() . self::WebOffset() . $path;

		$uri = ($full ? Quark::WebHost() : '') . Quark::NormalizePath(str_replace(Quark::Host(), '/', $path), false);

		return str_replace(':::', '://', str_replace('//', '/', str_replace('://', ':::', $uri)));
	}

	/**
	 * @param string $path
	 * @param bool $full = true
	 *
	 * @return string
	 */
	public static function WebLocationSigned ($path, $full = true) {
		$uri = self::WebLocation($path, $full);

		return $uri . QuarkURI::BuildQuery($uri, array(
			QuarkDTO::KEY_SIGNATURE => QuarkSession::Current() ? QuarkSession::Current()->Signature() : ''
		));
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

		foreach ($route as $i => &$part) {
			if ('.'  == $part) {
				$absolutes[] = '';
				continue;
			}

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
	 * @param string $node = ''
	 *
	 * @return string
	 */
	public static function UuID ($node = '') {
		$id = self::GuID();

		return substr($id, 0, 8)
			. '-' . substr($id, 8, 4)
			. '-' . substr($id, 12, 4)
			. '-' . substr($id, 16, 4)
			. '-' . substr(func_num_args() == 0 ? self::GuID() : str_pad($node, 12, '0'), 0, 12);
	}

	/**
	 * @param int $id
	 * @param string $alphabet = self::ALPHABET_ALL
	 * @param int $base = 2
	 * @param int $mod = PHP_INT_MAX
	 *
	 * @return string
	 */
	public static function TextID ($id, $alphabet = self::ALPHABET_ALL, $base = 2, $mod = PHP_INT_MAX) {
		$number = (string)$id;

		$i = 0;
		$lenNum = strlen($number);
		$out = '';

		while ($i < $lenNum) {
			$result = (string)(((pow($base, (int)$number[$i] < 3 ? 3 : $number[$i]) % $mod) * $base) % $mod);

			$j = 0;
			$lenRes = strlen($result);
			$alphabet = str_shuffle($alphabet);

			while ($j < $lenRes) {
				$out .= $alphabet[$result[$j] % (pow($result[$j], $j) + 1)];
				$j++;
			}

			$i++;
		}

		return $out;
	}

	/**
	 * @param int $length = 10
	 * @param bool $readable = true
	 * @param bool $firstLetter = true
	 *
	 * @return string
	 */
	public static function GeneratePassword ($length = 10, $readable = true, $firstLetter = true) {
		$alphabet = self::ALPHABET_PASSWORD_LETTERS;

		return ($firstLetter ? $alphabet[rand(0, strlen($alphabet) - 1)]: '')
			. substr(
				self::TextID(
					pow($length, $length),
					$readable ? self::ALPHABET_PASSWORD_LOW : self::ALPHABET_ALL
				),
				0,
				$length - (int)$firstLetter
		);
	}

	/**
	 * @param string $pattern = ''
	 * @param string $alphabet = self::ALPHABET_LETTERS
	 *
	 * @return string
	 */
	public static function GenerateByPattern ($pattern = '', $alphabet = self::ALPHABET_LETTERS) {
		if (!preg_match_all('#(\\\?.)(\{([\d]+)\})*#', $pattern, $found, PREG_SET_ORDER)) return '';

		$out = '';
		$last = strlen($alphabet) - 1;

		foreach ($found as $j => &$item) {
			if (!isset($item[3])) {
				$out .= $item[1];
				continue;
			}

			$i = 0;
			$count = $item[3] == '' ? 1 : (int)$item[3];

			while ($i < $count) {
				switch ($item[1]) {
					case '\d': $out .= mt_rand(0, 9); break;
					case '\c': $out .= $alphabet[mt_rand(0, $last)]; break;
					case '\C': $out .= strtoupper($alphabet[mt_rand(0, $last)]); break;
					case '\s': $out .= ' '; break;
					default: $out .= $item[1]; break;
				}

				$i++;
			}
		}

		return $out;
	}

	/**
	 * @param int|float $min = null
	 * @param int|float $max = null
	 *
	 * @return float
	 */
	public static function RandomFloat ($min = null, $max = null) {
		if ($min === null) $min = mt_rand();
		if ($max === null) $max = mt_rand();
		if ($min > $max) $max = $min;

		$min_int = (int)$min;
		$min_float = $min == 0 ? 0 : (int)substr(1 / $min_int, 2);

		$max_int = (int)$max;
		$max_float = $max == 0 ? 0 : (int)substr(1 / $max_int, 2);

		if ($max_float != 0) $max_int -= 1;
		if ($min_int > $max_int) $max_int = $min_int;

		return (float)(mt_rand($min_int, $max_int) . '.' . mt_rand($min_float, $max_float));
	}
	
	/**
	 * @param int $code
	 *
	 * http://stackoverflow.com/a/9878531/2097055
	 * http://il.php.net/manual/en/function.chr.php#88611
	 *
	 * @return string
	 */
	public static function UnicodeChar ($code) {
		return mb_convert_encoding('&#' . intval($code) . ';', 'UTF-8', 'HTML-ENTITIES');
	}

	/**
	 * @param IQuarkEnvironment $provider = null
	 *
	 * @return IQuarkEnvironment[]
	 */
	public static function &Environment (IQuarkEnvironment $provider = null) {
		if ($provider) {
			if (!$provider->EnvironmentMultiple())
				foreach (self::$_environment as $i => &$environment)
					if ($environment instanceof $provider) {
						self::$_environment[$i] = $provider;
						return self::$_environment;
					}

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
	 * @return IQuarkStackable[]
	 */
	public static function &Components () {
		return self::$_stack;
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

		foreach (self::$_stack as $i => &$object)
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
	 * @param IQuarkContainer $container
	 */
	public static function ContainerDispose (IQuarkContainer &$container) {
		unset(self::$_containers[spl_object_hash($container->Primitive())]);
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
	 * @param IQuarkPrimitive $primitive
	 *
	 * @return IQuarkContainer|null
	 */
	public static function ContainerOfInstance (IQuarkPrimitive $primitive) {
		return self::ContainerOf(spl_object_hash($primitive));
	}

	/**
	 * Free associated containers
	 */
	public static function ContainerFree () {
		self::$_containers = array();
	}

	/**
	 * @return IQuarkContainer[]
	 */
	public static function &Containers () {
		return self::$_containers;
	}

	/**
	 * @param string $language = QuarkLanguage::ANY
	 *
	 * @return string
	 */
	public static function CurrentLanguage ($language = QuarkLanguage::ANY) {
		if (func_num_args() != 0)
			self::$_currentLanguage = $language;

		return self::$_currentLanguage;
	}

	/**
	 * @return string
	 */
	public static function CurrentLanguageFamily () {
		return self::$_currentLanguage != QuarkLanguage::ANY && preg_match('#^([a-z]{2})\-[A-Z]{2}$#is', self::$_currentLanguage, $found)
			? strtolower($found[1])
			: '';
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
	 * @param string $extension = ''
	 * @param string $function = ''
	 * @param bool $silent = false
	 *
	 * @return bool
	 *
	 * @throws QuarkArchException
	 */
	public static function Requires ($extension = '', $function = '', $silent = false) {
		if (function_exists($function)) return true;

		if (!$silent) {
			$stack = self::ShortCallStack();
			$caller = isset($stack[4]) ? array_reverse(explode('\\', explode(' ', $stack[3])[0]))[0] : '';

			throw new QuarkArchException('[' . $caller . '] Function "' . $function . '" not found. Please check that "' . $extension . '" extension is configured for your PHP installation.');
		}

		return false;
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
	 * @param QuarkException $e
	 *
	 * @return int|bool
	 */
	public static function LogException (QuarkException $e) {
		return self::Log($e->message, $e->lvl);
	}

	/**
	 * @param bool $args = false
	 * @param bool $trace = true
	 *
	 * @return array|int|bool
	 */
	public static function CallStack ($args = false, $trace = true) {
		$stack = debug_backtrace($args ? DEBUG_BACKTRACE_PROVIDE_OBJECT : DEBUG_BACKTRACE_IGNORE_ARGS);

		return $trace ? self::Trace($stack) : $stack;
	}

	/**
	 * @param bool $trace = false
	 *
	 * @return array|int|bool
	 */
	public static function ShortCallStack ($trace = false) {
		$stack = self::CallStack(false, false);
		$out = array();

		foreach ($stack as $i => &$item)
			$out[]  = (isset($item['class']) ? $item['class'] : '')
					. (isset($item['type']) ? $item['type'] : '')
					. $item['function']
					. ' ('
					. (isset($item['file']) ? $item['file'] : '[file]')
					. ':'
					. (isset($item['line']) ? $item['line'] : '[line]')
					. ')';

		return $trace ? self::Trace($out) : $out;
	}

	/**
	 * @param int $unit = self::UNIT_KILOBYTE
	 * @param int $precision = 2
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
			case self::UNIT_TERABYTE: return 'TB'; break;
			case self::UNIT_PETABYTE: return 'PB'; break;
		}

		return '-';
	}

	/**
	 * @param int $bytes = 0
	 * @param int $precision = 2
	 *
	 * @return QuarkKeyValuePair
	 */
	public static function MemoryCalculate ($bytes = 0, $precision = 2) {
		if     ($bytes >= self::UNIT_PETABYTE) $unit = self::UNIT_PETABYTE;
		elseif ($bytes >= self::UNIT_TERABYTE) $unit = self::UNIT_TERABYTE;
		elseif ($bytes >= self::UNIT_GIGABYTE) $unit = self::UNIT_GIGABYTE;
		elseif ($bytes >= self::UNIT_MEGABYTE) $unit = self::UNIT_MEGABYTE;
		elseif ($bytes >= self::UNIT_KILOBYTE) $unit = self::UNIT_KILOBYTE;
		else $unit = self::UNIT_BYTE;

		return new QuarkKeyValuePair(
			self::MemoryUnit($unit),
			round($bytes / $unit, $precision)
		);
	}

	/**
	 * @return float
	 */
	public static function ExecutionTime () {
		return microtime(true) - self::$_execTime;
	}
	
	/**
	 * @param string[] $names = []
	 * @param string $fallback = ''
	 *
	 * @return string
	 */
	public static function EnvVar ($names = [], $fallback = '') {
		foreach ($names as $name) {
			$out = getenv($name);
			
			if ($out !== false) return $out;
		}
		
		return $fallback;
	}

	/**
	 * @param string $prefix = self::TEMP_FILE_PREFIX
	 * @param string $location = ''
	 *
	 * @return QuarkFile
	 */
	public static function TempFile ($prefix = self::TEMP_FILE_PREFIX, $location = '') {
		return new QuarkFile(tempnam(func_num_args() == 2 ? $location : sys_get_temp_dir(), $prefix));
	}
}

/**
 * Class QuarkConfig
 *
 * @package Quark
 */
class QuarkConfig {
	const INI_QUARK = 'Quark';
	const INI_ROUTES = 'Routes';
	const INI_DEDICATED = 'Dedicated';
	const INI_LOCALIZATION_DETAILS = 'LocalizationDetails';
	const INI_LOCAL_SETTINGS = 'LocalSettings';
	const INI_DATA_PROVIDERS = 'DataProviders';
	const INI_AUTHORIZATION_PROVIDER = 'AuthorizationProvider:';
	const INI_ASYNC_QUEUES = 'AsyncQueues';
	const INI_ENVIRONMENT = 'Environment:';
	const INI_EXTENSION = 'Extension:';
	const INI_CONFIGURATION = 'Configuration:';
	const INI_PHP = 'PHP';

	const SERVICES = 'services';
	const VIEWS = 'views';
	const RUNTIME = 'runtime';
	
	const LANGUAGE_DELIMITER = ',';

	/**
	 * @var IQuarkCulture $_culture = null
	 */
	private $_culture = null;

	/**
	 * @var int $_tick = 10000 (microseconds)
	 */
	private $_tick = QuarkThreadSet::TICK;

	/**
	 * @var string $_mode = Quark::MODE_DEV
	 */
	private $_mode = Quark::MODE_DEV;
	
	/**
	 * @var bool $_allowINIFallback = false
	 */
	private $_allowINIFallback = false;

	/**
	 * @var QuarkModel|IQuarkApplicationSettingsModel $_settingsApp = null
	 */
	private $_settingsApp = null;

	/**
	 * @var object $_settingsLocal = null
	 */
	private $_settingsLocal = null;

	/**
	 * @var string $_ini = ''
	 */
	private $_ini = '';

	/**
	 * @var QuarkFile $_localization = null
	 */
	private $_localization = null;
	
	/**
	 * @var object $_localizationDictionary = null
	 */
	private $_localizationDictionary = null;

	/**
	 * @var bool $_localizationByFamily = true
	 */
	private $_localizationByFamily = true;

	/**
	 * @var string $_localizationExtract = QuarkLocalizedString::EXTRACT_CURRENT
	 */
	private $_localizationExtract = QuarkLocalizedString::EXTRACT_CURRENT;

	/**
	 * @var bool $_localizationParseFailedToAny = false
	 */
	private $_localizationParseFailedToAny = false;

	/**
	 * @var object $_localizationDetails = null
	 */
	private $_localizationDetails = null;

	/**
	 * @var string[] $_localizationDetailsLoaded = []
	 */
	private $_localizationDetailsLoaded = array();
	
	/**
	 * @var string $_localizationDetailsDelimiter = ':'
	 */
	private $_localizationDetailsDelimiter = ':';

	/**
	 * @var string[] $_languages = [QuarkLanguage::ANY]
	 */
	private $_languages = array(QuarkLanguage::ANY);

	/**
	 * @var string $_modelValidation = QuarkModel::CONFIG_VALIDATION_ALL
	 */
	private $_modelValidation = QuarkModel::CONFIG_VALIDATION_ALL;

	/**
	 * @var array|QuarkKeyValuePair[] $_queues = []
	 */
	private $_queues = array();

	/**
	 * @var object $_configuration = null
	 */
	private $_configuration = null;
	
	/**
	 * @var string $_openSSLConfig = ''
	 */
	private $_openSSLConfig = '';

	/**
	 * @var bool $_ready = false
	 */
	private $_ready = false;

	/**
	 * @var callable $_readyCallback = null
	 */
	private $_readyCallback = null;

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
	 * @var string $_streamHost = ''
	 */
	private $_streamHost = '';

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
	 * @var bool $_selfHostedLog = true
	 */
	private $_selfHostedLog = true;

	/**
	 * @var QuarkCertificate $_selfHostedCertificate
	 */
	private $_selfHostedCertificate;

	/**
	 * @var QuarkURI $_webManagement
	 */
	private $_webManagement;

	/**
	 * @var bool $_webManagementLog = true
	 */
	private $_webManagementLog = true;

	/**
	 * @var QuarkCertificate $_webManagementCertificate
	 */
	private $_webManagementCertificate;

	/**
	 * @var bool $_allowIndexFallback = false
	 */
	private $_allowIndexFallback = false;

	/**
	 * @var array $_routes = []
	 */
	private $_routes = array();

	/**
	 * @var array $_dedicated = []
	 */
	private $_dedicated = array();

	/**
	 * @param string $ini = ''
	 */
	public function __construct ($ini = '') {
		$this->_culture = new QuarkCultureISO();
		$this->_webHost = new QuarkURI();

		$this->ClusterControllerListen(QuarkStreamEnvironment::URI_CONTROLLER_INTERNAL);
		$this->ClusterControllerConnect($this->_clusterControllerListen->ConnectionURI()->URI());
		$this->ClusterMonitor(QuarkStreamEnvironment::URI_CONTROLLER_EXTERNAL);
		$this->_selfHosted = QuarkURI::FromURI(QuarkFPMEnvironment::SELF_HOSTED);
		$this->_webManagement = QuarkURI::FromURI(Quark::WEB_MANAGEMENT);

		if (isset($_SERVER['SERVER_PROTOCOL']))
			$this->_webHost->scheme = $_SERVER['SERVER_PROTOCOL'];

		if (isset($_SERVER['SERVER_NAME']))
			$this->_webHost->host = $_SERVER['SERVER_NAME'];

		if (isset($_SERVER['SERVER_PORT']))
			$this->_webHost->port = $_SERVER['SERVER_PORT'];

		if (isset($_SERVER['DOCUMENT_ROOT']))
			$this->_webHost->path = Quark::NormalizePath(str_replace($_SERVER['DOCUMENT_ROOT'], '', Quark::Host()));

		$this->Ini($ini);
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
	 * @param bool $fallback = false
	 *
	 * @return bool
	 */
	public function AllowINIFallback ($fallback = false) {
		if (func_num_args() != 0)
			$this->_allowINIFallback = $fallback;
		
		return $this->_allowINIFallback;
	}

	/**
	 * @param string $key = ''
	 * @param string $value = ''
	 *
	 * @return string
	 */
	public function PHP ($key = '', $value = '') {
		if (func_num_args() == 2)
			ini_set($key, $value);

		return ini_get($key);
	}

	/**
	 * @param string $name
	 * @param IQuarkStackable $object = null
	 * @param string $message = ''
	 *
	 * @return IQuarkStackable|QuarkSessionSource|QuarkModelSource|IQuarkExtensionConfig
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
	 * @return QuarkSessionSource
	 */
	public function AuthorizationProvider ($name, IQuarkAuthorizationProvider $provider = null, IQuarkAuthorizableModel $user = null) {
		return $this->_component(
			$name,
			func_num_args() == 3 ? new QuarkSessionSource($name, $provider, $user) : null,
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
			(func_num_args() == 3 && $provider != null && $uri != null) || (func_num_args() == 2 && $provider != null) ? new QuarkModelSource($name, $provider, $uri) : null,
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
	 * @param string $name
	 * @param QuarkURI $uri = null
	 * @param IQuarkNetworkProtocol $protocol = null
	 *
	 * @return QuarkKeyValuePair
	 */
	public function AsyncQueue ($name, QuarkURI $uri = null, IQuarkNetworkProtocol $protocol = null) {
		if (!isset($this->_queues[$name]) || func_num_args() > 1)
			$this->_queues[$name] = new QuarkKeyValuePair($uri, $protocol);

		return $this->_queues[$name];
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
		if (func_num_args() == 0 || $model == null) $this->_loadSettings();
		else $this->_settingsApp = $this->_ready ? new QuarkModel($model) : $model;

		return $this->_settingsApp;
	}

	/**
	 * @param string $name
	 * @param IQuarkConfiguration $config = null
	 *
	 * @return IQuarkConfiguration
	 */
	public function Configuration ($name, IQuarkConfiguration $config = null) {
		if ($this->_configuration == null)
			$this->_configuration = new \stdClass();

		if ($config != null)
			$this->_configuration->$name = $config;

		return isset($this->_configuration->$name) ? $this->_configuration->$name : null;
	}
	
	/**
	 * https://bugs.php.net/bug.php?id=60157
	 *
	 * @param string $location = ''
	 *
	 * @return string
	 */
	public function OpenSSLConfig ($location = '') {
		// TODO: maybe deprecated, see QuarkCertificate::OpenSSLConfig()
		// UPD1: NOT DEPRECATED, need for OpenSSL auto-discovering
		// UPD2: will be deprecated for R2, because of EnvironmentVariable() feature

		if (func_num_args() != 0) {
			$this->_openSSLConfig = $location;
			putenv('OPENSSL_CONF', $location);
		}
		
		return $this->_openSSLConfig;
	}

	/**
	 * @return bool
	 */
	private function _loadSettings () {
		if ($this->_settingsApp == null) return false;
		
		$criteria = $this->_settingsApp->LoadCriteria();
		$settings = null;

		if ($criteria !== null) $settings = QuarkModel::FindOne($this->_settingsApp->Model(), $criteria);
		else {
			$settings = $this->_settingsApp;

			Quark::Log('[QuarkConfig::_loadSettings] Load criteria for ApplicationSettings is null, so default ' . get_class($this->_settingsApp->Model()) . ' model returned');
		}

		if ($settings == null || !($settings->Model() instanceof IQuarkApplicationSettingsModel)) return false;

		$this->_settingsApp = $settings;
		return true;
	}

	/**
	 * @param $data
	 *
	 * @return bool
	 */
	public function UpdateApplicationSettings ($data = null) {
		if ($this->_settingsApp == null) return false;

		$ok = $this->_loadSettings();

		if (func_num_args() != 0)
			$this->_settingsApp->PopulateWith($data);

		return $ok ? $this->_settingsApp->Save() : $this->_settingsApp->Create();
	}

	/**
	 * @param string $key = ''
	 * @param string $value = ''
	 *
	 * @return mixed
	 */
	public function LocalSettings ($key = '', $value = '') {
		if ($this->_settingsLocal == null)
			$this->_settingsLocal = new \stdClass();

		if (func_num_args() == 2)
			$this->_settingsLocal->$key = $value;

		return isset($this->_settingsLocal->$key) ? $this->_settingsLocal->$key : null;
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
	 * @param string $host = ''
	 *
	 * @return string
	 */
	public function &StreamHost ($host = '') {
		if (func_num_args() != 0)
			$this->_streamHost = $host;
		
		return $this->_streamHost;
	}

	/**
	 * @param QuarkURI|string $listen = ''
	 * @param QuarkURI|string $connect = ''
	 *
	 * @return QuarkConfig
	 */
	public function ClusterController ($listen = '', $connect = '') {
		$this->ClusterControllerListen($listen);
		$this->ClusterControllerConnect($connect);
		
		if (func_num_args() == 1)
			$this->ClusterControllerConnect($this->_clusterControllerListen->ConnectionURI()->URI());
		
		return $this;
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
	 * @param bool $log = true
	 *
	 * @return bool
	 */
	public function &SelfHostedFPMLog ($log = true) {
		if (func_num_args() != 0)
			$this->_selfHostedLog = (bool)$log;

		return $this->_selfHostedLog;
	}

	/**
	 * @param QuarkCertificate|string $certificate = null
	 *
	 * @return QuarkCertificate
	 */
	public function &SelfHostedFPMCertificate ($certificate = null) {
		if (func_num_args() != 0)
			$this->_selfHostedCertificate = QuarkCertificate::FromLocation($certificate);

		return $this->_selfHostedCertificate;
	}

	/**
	 * @param string $passphrase = null
	 *
	 * @return string
	 */
	public function SelfHostedFPMCertificatePassphrase ($passphrase = null) {
		if ($this->_selfHostedCertificate == null) return null;

		if (func_num_args() != 0)
			$this->_selfHostedCertificate->Passphrase($passphrase);

		return $this->_selfHostedCertificate->Passphrase();
	}

	/**
	 * @param QuarkURI|string $uri = ''
	 *
	 * @return QuarkURI
	 */
	public function &WebManagement ($uri = '') {
		if (func_num_args() != 0)
			$this->_webManagement = QuarkURI::FromURI($uri);

		return $this->_webManagement;
	}

	/**
	 * @param bool $log = true
	 *
	 * @return bool
	 */
	public function &WebManagementLog ($log = true) {
		if (func_num_args() != 0)
			$this->_webManagementLog = (bool)$log;

		return $this->_webManagementLog;
	}

	/**
	 * @param QuarkCertificate|string $certificate = null
	 *
	 * @return QuarkCertificate
	 */
	public function &WebManagementCertificate ($certificate = null) {
		if (func_num_args() != 0)
			$this->_webManagementCertificate = QuarkCertificate::FromLocation($certificate);

		return $this->_webManagementCertificate;
	}

	/**
	 * @param string $passphrase = null
	 *
	 * @return string
	 */
	public function WebManagementCertificatePassphrase ($passphrase = null) {
		if ($this->_webManagementCertificate == null) return null;

		if (func_num_args() != 0)
			$this->_webManagementCertificate->Passphrase($passphrase);

		return $this->_webManagementCertificate->Passphrase();
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

	/**
	 * @param string $original = ''
	 * @param string $target = ''
	 *
	 * @return string|null
	 */
	public function Route ($original = '', $target = '') {
		$original = strtolower($original);

		if (func_num_args() == 2)
			$this->_routes[$original] = $target;

		return isset($this->_routes[$original]) ? $this->_routes[$original] : null;
	}

	/**
	 * @return array
	 */
	public function &Routes () {
		return $this->_routes;
	}

	/**
	 * @param string $service = ''
	 * @param string $process = ''
	 *
	 * @return string[]
	 */
	public function Dedicated ($process = '', $service = '') {
		$process = QuarkObject::ConstValue($process);

		if (func_num_args() == 2) {
			if (!isset($this->_dedicated[$process]))
				$this->_dedicated[$process] = array();

			$this->_dedicated[$process][] = $service;
		}

		return isset($this->_dedicated[$process]) ? $this->_dedicated[$process] : array();
	}

	/**
	 * @return string[]
	 */
	public function DedicatedAll () {
		$out = array();

		foreach ($this->_dedicated as $process => &$tasks)
			foreach ($tasks as $i => &$task)
				$out[] = $task;

		return $out;
	}

	/**
	 * @param string $file
	 *
	 * @return string
	 */
	public function Ini ($file = '') {
		if (func_num_args() != 0)
			$this->_ini = $file;
		
		return $this->_ini;
	}

	/**
	 * @param string $file = ''
	 *
	 * @return QuarkFile
	 */
	public function Localization ($file = '') {
		if (func_num_args() != 0)
			$this->_localization = new QuarkFile($file);

		return $this->_localization;
	}
	
	/**
	 * @param string $key
	 *
	 * @return object
	 */
	private function _localization ($key) {
		if ($this->_localizationDictionary == null)
			$this->_localizationDictionary = $this->_localization == null
				? null
				: $this->_localization->Decode(new QuarkINIIOProcessor(), true);

		if (preg_match('#^(.*)' . QuarkRegEx::Escape($this->_localizationDetailsDelimiter) . '.*#i', $key, $found) && !in_array($found[1], $this->_localizationDetailsLoaded)) {
			$domain = $found[1];

			if (isset($this->_localizationDetails->$domain)) {
				$details = QuarkFile::FromLocation($this->_localizationDetails->$domain)->Decode(new QuarkINIIOProcessor(), true);
				$this->_localizationDetailsLoaded[] = $domain;

				if ($this->_localizationDictionary == null)
					$this->_localizationDictionary = new \stdClass();

				if (QuarkObject::isTraversable($details)) {
					foreach ($details as $key => &$block) {
						$outKey = $domain . $this->_localizationDetailsDelimiter . $key;
						$this->_localizationDictionary->$outKey = $block;
					}

					unset($key, $block);
				}
			}
		}

		return $this->_localizationDictionary;
	}

	/**
	 * @param string $key = ''
	 * @param string $language = QuarkLanguage::ANY
	 *
	 * @return bool
	 */
	public function LocalizationExists ($key = '', $language = QuarkLanguage::ANY) {
		$locale = $this->_localization($key);

		return isset($locale->$key->$language);
	}

	/**
	 * @param string $key = ''
	 * @param string $language = QuarkLanguage::ANY
	 * @param string $value = ''
	 *
	 * @return string
	 */
	public function LocalizationOf ($key = '', $language = QuarkLanguage::ANY, $value = '') {
		$locale = $this->_localization($key);
		
		if (func_num_args() == 3) {
			if ($this->_localizationDictionary == null)
				$this->_localizationDictionary = new \stdClass();
			
			if (!isset($this->_localizationDictionary->$key))
				$this->_localizationDictionary->$key = new \stdClass();
			
			$this->_localizationDictionary->$key->$language = $value;
			$locale = $this->_localizationDictionary;
		}

		return isset($locale->$key->$language) ? $locale->$key->$language : '';
	}

	/**
	 * @param string $key = ''
	 * @param array|object $dictionary = []
	 *
	 * @return object
	 */
	public function LocalizationDictionaryOf ($key = '', $dictionary = []) {
		$locale = $this->_localization($key);
		
		if (func_num_args() == 2) {
			if ($this->_localizationDictionary == null)
				$this->_localizationDictionary = new \stdClass();
			
			$this->_localizationDictionary->$key = (object)$dictionary;
			$locale = $this->_localizationDictionary;
		}

		return isset($locale->$key) ? $locale->$key : new \stdClass();
	}

	/**
	 * @param bool $localize = true
	 *
	 * @return bool
	 */
	public function LocalizationByFamily ($localize = true) {
		if (func_num_args() != 0)
			$this->_localizationByFamily = $localize;
		
		return $this->_localizationByFamily;
	}

	/**
	 * @param string $localization = QuarkLocalizedString::EXTRACT_CURRENT
	 *
	 * @return string
	 */
	public function LocalizationExtract ($localization = QuarkLocalizedString::EXTRACT_CURRENT) {
		if (func_num_args() != 0)
			$this->_localizationExtract = QuarkObject::ConstValue($localization);

		return $this->_localizationExtract;
	}

	/**
	 * @param bool $force = false
	 *
	 * @return bool
	 */
	public function LocalizationParseFailedToAny ($force = false) {
		if (func_num_args() != 0)
			$this->_localizationParseFailedToAny = $force;
		
		return $this->_localizationParseFailedToAny;
	}

	/**
	 * @param string $key = ''
	 * @param bool $strict = false
	 *
	 * @return string
	 */
	public function CurrentLocalizationOf ($key = '', $strict = false) {
		$locale = $this->_localization($key);

		$lang_current = Quark::CurrentLanguage();
		$lang_family = Quark::CurrentLanguageFamily();
		$lang_any = QuarkLanguage::ANY;

		if (isset($locale->$key->$lang_current))
			return $locale->$key->$lang_current;

		if (!$strict) {
			if (isset($locale->$key->$lang_family) && $this->_localizationByFamily)
				return $locale->$key->$lang_family;

			if (isset($locale->$key->$lang_any))
				return $locale->$key->$lang_any;
		}

		return '';
	}
	
	/**
	 * @param string $domain = ''
	 * @param string $location = ''
	 *
	 * @return string|null
	 */
	public function LocalizationDetails ($domain = '', $location = '') {
		if ($this->_localizationDetails == null)
			$this->_localizationDetails = new \stdClass();
		
		if (func_num_args() == 2)
			$this->_localizationDetails->$domain = $location;
		
		return isset($this->_localizationDetails->$domain)
			? $this->_localizationDetails->$domain
			: null;
	}
	
	/**
	 * @param string $delimiter = ':'
	 *
	 * @return string
	 */
	public function LocalizationDetailsDelimiter ($delimiter = ':') {
		if (func_num_args() != 0)
			$this->_localizationDetailsDelimiter = $delimiter;
		
		return $this->_localizationDetailsDelimiter;
	}
	
	/**
	 * @param string|string[] $languages = ''
	 * @param string $delimiter = self::LANGUAGE_DELIMITER
	 *
	 * @return string[]
	 */
	public function Languages ($languages = '', $delimiter = self::LANGUAGE_DELIMITER) {
		if (func_num_args() != 0)
			$this->_languages = is_array($languages) ? $languages : explode($delimiter, (string)$languages);
		
		return $this->_languages;
	}

	/**
	 * @param string $mode = QuarkModel::CONFIG_VALIDATION_ALL
	 *
	 * @return string
	 */
	public function ModelValidation ($mode = QuarkModel::CONFIG_VALIDATION_ALL) {
		if (func_num_args() != 0)
			$this->_modelValidation = QuarkObject::ConstValue($mode);
		
		return $this->_modelValidation;
	}

	/**
	 * @param callable $callback = null
	 */
	public function ConfigReady (callable $callback = null) {
		if (func_num_args() != 0) {
			$this->_readyCallback = $callback;
			return;
		}
		
		if (!$this->_ini) return;

		$file = QuarkFile::FromLocation($this->_ini);
		
		if (!$file->Exists() && $this->_allowINIFallback) return;
		
		$ini = $file->Load()->Decode(new QuarkINIIOProcessor());
		$callback = $this->_readyCallback;

		if ($callback != null)
			$callback($this, $ini);

		if (!$ini) return;
		$ini = (array)$ini;

		if (isset($ini[self::INI_PHP]))
			foreach ($ini[self::INI_PHP] as $key => &$value)
				$this->PHP($key, $value);

		if (isset($ini[self::INI_QUARK]))
			foreach ($ini[self::INI_QUARK] as $key => &$value)
				self::_iniOption($this, $key, $value);

		if (isset($ini[self::INI_ROUTES]))
			foreach ($ini[self::INI_ROUTES] as $key => &$value)
				$this->Route($key, $value);

		if (isset($ini[self::INI_DATA_PROVIDERS]))
			foreach ($ini[self::INI_DATA_PROVIDERS] as $key => &$connection) {
				try {
					$component = Quark::Component(QuarkObject::ConstValue($key));

					if ($component instanceof QuarkModelSource)
						$component->URI(QuarkURI::FromURI($connection));
				}
				catch (QuarkArchException $e) {
					Quark::Log('Attempting of configuring unspecified data provider ' . $key . ', by value "' . $connection . '". Possible mistyping of data provider\'s key in INI configuration.', Quark::LOG_WARN);
				}
			}

		if (isset($ini[self::INI_ASYNC_QUEUES]))
			foreach ($ini[self::INI_ASYNC_QUEUES] as $key => &$queue) {
				$name = QuarkObject::ConstValue($key);
				
				if (!isset($this->_queues[$name]))
					$this->_queues[$name] = new QuarkKeyValuePair(null, null);
			
				$this->_queues[$name]->Key(QuarkURI::FromURI($queue));
			}

		if (isset($ini[self::INI_LOCAL_SETTINGS])) {
			if ($this->_settingsLocal == null)
				$this->_settingsLocal = new \stdClass();
			
			foreach ($ini[self::INI_LOCAL_SETTINGS] as $key => &$value)
				$this->_settingsLocal->$key = $value;
		}

		if (isset($ini[self::INI_LOCALIZATION_DETAILS])) {
			if ($this->_localizationDetails == null)
				$this->_localizationDetails = new \stdClass();
			
			foreach ($ini[self::INI_LOCALIZATION_DETAILS] as $key => &$value)
				$this->_localizationDetails->$key = $value;
		}

		if (QuarkObject::isTraversable($this->_configuration))
			foreach ($this->_configuration as $key => &$item) {
				/**
				 * @var IQuarkConfiguration $item
				 */

				$options = self::_ini($ini, self::INI_CONFIGURATION, QuarkObject::ConstValue($key));

				if (QuarkObject::isTraversable($options)) {
					$ready = $item->ConfigurationReady($key, $options);

					if ($ready == true || $ready === null)
						foreach ($options as $name => &$value)
							self::_iniOption($item, $name, $value);
				}
			}

		$environments = Quark::Environment();

		foreach ($environments as $i => &$environment) {
			$options = self::_ini($ini, self::INI_ENVIRONMENT, $environment->EnvironmentName());

			if ($options !== null)
				$environment->EnvironmentOptions($options);
		}

		$components = Quark::Components();

		foreach ($components as $key => &$component) {
			if ($component instanceof QuarkSessionSource) {
				$options = self::_ini($ini, self::INI_AUTHORIZATION_PROVIDER, $component->Name());
				$component->Options($options);
			}

			if ($component instanceof IQuarkExtensionConfig) {
				$options = self::_ini($ini, self::INI_EXTENSION, $component->ExtensionName());

				if ($component instanceof IQuarkExtensionConfigWithForcedOptions || $options !== null)
					$component->ExtensionOptions($options);
			}
		}

		if (isset($ini[self::INI_DEDICATED]))
			foreach ($ini[self::INI_DEDICATED] as $value => &$key)
				$this->Dedicated($key, $value);

		unset($environment, $environments, $extension, $extensions, $options, $callback, $ini, $key, $value);

		$this->_ready = true;

		$this->ApplicationSettings($this->_settingsApp);
	}
	
	/**
	 * @param string $config = ''
	 * @param object $options = null
	 *
	 * @return mixed
	 */
	public function ExtensionOptions ($config = '', $options = null) {
		$extension = $this->Extension($config);
		
		return $extension instanceof IQuarkExtensionConfig && ($extension instanceof IQuarkExtensionConfigWithForcedOptions || $options !== null)
			? $extension->ExtensionOptions($options)
			: null;
	}

	/**
	 * @param object|array $ini
	 * @param string $prefix
	 * @param string $name
	 *
	 * @return object
	 */
	private static function _ini ($ini, $prefix, $name) {
		$key = $prefix . $name;
		
		if (isset($ini[$key]))
			return (object)$ini[$key];

		$name = QuarkObject::ConstByValue($name);
		if (!$name) return null;

		$key = $prefix . $name;

		return isset($ini[$key]) ? (object)$ini[$key] : null;
	}

	/**
	 * @param object $target
	 * @param string $option
	 * @param $value
	 */
	private static function _iniOption ($target, $option, $value) {
		if (method_exists($target, $option)) $target->$option($value);
		else Quark::Log('[QuarkConfig] Unknown property "' . $option . '" of "' . QuarkObject::ClassOf($target) . '" attempted to set by external INI', Quark::LOG_WARN);
	}
}

/**
 * Interface IQuarkConfiguration
 *
 * @package Quark
 */
interface IQuarkConfiguration {
	/**
	 * @param string $key
	 * @param object $ini
	 *
	 * @return bool
	 */
	public function ConfigurationReady($key, $ini);
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
	public function EnvironmentMultiple();

	/**
	 * @return bool
	 */
	public function EnvironmentQueued();

	/**
	 * @return string
	 */
	public function EnvironmentName();

	/**
	 * @param object $ini
	 *
	 * @return mixed
	 */
	public function EnvironmentOptions($ini);
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
	const ENV = 'FPM';

	const SELF_HOSTED = 'http://127.0.0.1:25080';

	const DIRECTION_REQUEST = 'Request';
	const DIRECTION_RESPONSE = 'Response';
	const DIRECTION_BOTH = 'Both';

	/**
	 * @var string $_statusNotFound = QuarkDTO::STATUS_404_NOT_FOUND
	 */
	private $_statusNotFound = QuarkDTO::STATUS_404_NOT_FOUND;

	/**
	 * @var string $_statusServerError = QuarkDTO::STATUS_500_SERVER_ERROR
	 */
	private $_statusServerError = QuarkDTO::STATUS_500_SERVER_ERROR;

	/**
	 * @var IQuarkIOProcessor $_processorRequest = null
	 */
	private $_processorRequest = null;

	/**
	 * @var IQuarkIOProcessor $_processorResponse = null
	 */
	private $_processorResponse = null;

	/**
	 * @var IQuarkIOFilter $_filterRequest = null
	 */
	private $_filterRequest = null;

	/**
	 * @var IQuarkIOFilter $_filterResponse = null
	 */
	private $_filterResponse = null;

	/**
	 * @return bool
	 */
	public function EnvironmentMultiple () { return false; }

	/**
	 * @return bool
	 */
	public function EnvironmentQueued () { return false; }

	/**
	 * @return string
	 */
	public function EnvironmentName () {
		return self::ENV;
	}

	/**
	 * @param object $ini
	 *
	 * @return void
	 */
	public function EnvironmentOptions ($ini) {
		if (isset($ini->DefaultNotFoundStatus))
			$this->DefaultNotFoundStatus($ini->DefaultNotFoundStatus);

		if (isset($ini->DefaultServerErrorStatus))
			$this->DefaultServerErrorStatus($ini->DefaultServerErrorStatus);
	}

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
	 * @param $option
	 * @param $direction
	 * @param $value
	 *
	 * @return IQuarkIOProcessor|IQuarkIOFilter
	 */
	private function _option ($option, $direction, $value = null) {
		if ($value != null) {
			if ($direction != self::DIRECTION_BOTH) {
				$key = '_' . $option . $direction;
				$this->$key = $value;
			}
			else {
				$key = '_' . $option . self::DIRECTION_REQUEST;
				$this->$key = $value;

				$key = '_' . $option . self::DIRECTION_RESPONSE;
				$this->$key = $value;
			}
		}

		$opt = '_' . $option . ($direction == self::DIRECTION_BOTH ? self::DIRECTION_RESPONSE : $direction);

		return is_string($direction) ? $this->$opt : null;
	}

	/**
	 * @param string $direction
	 * @param IQuarkIOProcessor $processor = null
	 *
	 * @return IQuarkIOProcessor
	 */
	public function Processor ($direction, IQuarkIOProcessor $processor = null) {
		return $this->_option('processor', $direction, $processor);
	}

	/**
	 * @param string $direction
	 * @param IQuarkIOFilter $filter = null
	 *
	 * @return IQuarkIOFilter
	 */
	public function Filter ($direction, IQuarkIOFilter $filter = null) {
		return $this->_option('filter', $direction, $filter);
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
			$this->_processorResponse,
			null, null,
			QuarkService::POSTFIX_SERVICE,
			Quark::Config()->Routes()
		);
		
		$service->InputFilter($this->_filterRequest);
		$service->OutputFilter($this->_filterResponse);

		$uri = QuarkURI::FromURI(Quark::NormalizePath($_SERVER['REQUEST_URI'], false));
		$service->Input()->URI($uri);
		$service->Output()->URI($uri);

		$remote = QuarkURI::FromEndpoint($_SERVER['REMOTE_ADDR'], $_SERVER['REMOTE_PORT']);
		$service->Input()->Remote($remote);
		$service->Output()->Remote($remote);

		if ($service->Service() instanceof IQuarkServiceWithAccessControl)
			$service->Output()->Header(QuarkDTO::HEADER_ALLOW_ORIGIN, $service->Service()->AllowOrigin());

		$headers = array();

		$authType = '';
		$authBasic = 0;
		$authDigest = 0;

		if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
			$_SERVER['HTTP_AUTHORIZATION'] = QuarkDTO::HTTPBasicAuthorization($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
			$authBasic = 1;
		}

		foreach ($_SERVER as $name => &$value) {
			$name = str_replace('CONTENT_', 'HTTP_CONTENT_', $name);
			$name = str_replace('PHP_AUTH_DIGEST', 'HTTP_AUTHORIZATION', $name, $authDigest);

			if ($authBasic != 0)
				$authType = 'Basic ';

			if ($authDigest != 0)
				$authType = 'Digest ';

			if (substr($name, 0, 5) == 'HTTP_')
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = ($name == 'HTTP_AUTHORIZATION' ? $authType : '') . $value;
		}

		unset($name, $value);

		$service->Input()->Method(ucfirst(strtolower($_SERVER['REQUEST_METHOD'])));
		$service->Input()->Headers($headers);

		Quark::CurrentLanguage($service->Input()->ExpectedLanguage());

		$service->Input()->Merge((object)$_GET);
		$service->InitProcessors();

		$in = $service->Input()->Processor()->Decode(file_get_contents('php://input'));

		$input = $service->Input()->Processor()->ForceInput() ? $in : array_replace_recursive(
			$_GET,
			$_POST,
			QuarkFile::FromFiles($_FILES),
			(array)json_decode(json_encode($in), true),
			array()
		);

		$service->Input()->Merge((object)$input);

		if (isset($_POST[$service->Input()->Processor()->MimeType()]))
			$service->Input()->Merge($service->Input()->Processor()->Decode($_POST[$service->Input()->Processor()->MimeType()]));

		if ($service->Service() instanceof IQuarkUnbufferedService)
			ob_implicit_flush(1);

		$service->On(QuarkService::EVENT_OUTPUT_HEADERS, function () use (&$service) {
			$headers = $service->Output()->SerializeResponseHeadersToArray();

			foreach ($headers as $i => &$header)
				header($header);

			unset($i, $header, $headers);
		});

		echo $service->Pipeline();

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
	const STATUS_UNKNOWN = '...';
	const DEDICATED_UNKNOWN = '<unknown>';

	use QuarkCLIViewBehavior;

	/**
	 * @var array $_statuses
	 */
	private static $_statuses = array(
		Quark::LOG_INFO => 'INFO',
		Quark::LOG_OK => 'OK',
		Quark::LOG_WARN => 'WARN',
		Quark::LOG_FATAL => 'FAIL'
	);

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
	 * @var string $_name = 'CLI'
	 */
	private $_name = 'CLI';

	/**
	 * @var bool $_queued = false
	 */
	private $_queued = false;

	/**
	 * @param int   $argc = 0
	 * @param array $argv = []
	 */
	public function __construct ($argc = 0, $argv = []) {
		if ($argc <= 1 || $argv[1] == QuarkTask::DEDICATED || $argv[1] == QuarkTask::DEDICATED_ALIAS)
			$this->_queued = true;
	}

	/**
	 * @return bool
	 */
	public function EnvironmentMultiple () { return false; }

	/**
	 * @return bool
	 */
	public function EnvironmentQueued () { return $this->_queued; }

	/**
	 * @return string
	 */
	public function EnvironmentName () {
		return $this->_name;
	}

	/**
	 * @param object $ini
	 *
	 * @return void
	 */
	public function EnvironmentOptions ($ini) {
		if (isset($ini->ApplicationStart))
			$this->ApplicationStart($ini->ApplicationStart);
	}

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
	 * @return void
	 *
	 * @throws QuarkArchException
	 * @throws QuarkHTTPException
	 */
	public function Thread ($argc = 0, $argv = []) {
		Quark::CurrentEnvironment($this);

		$dedicated = $argc > 1 && ($argv[1] == QuarkTask::DEDICATED || $argv[1] == QuarkTask::DEDICATED_ALIAS);

		if ($this->_queued && !$this->_started) {
			$tasks = $dedicated
				? Quark::Config()->Dedicated($argc >= 2 ? $argv[2] : '')
				: Quark::Config()->DedicatedAll();

			$this->_tasks = QuarkService::Suite(Quark::Host(), function ($service) use (&$dedicated, $tasks) {
				if ($dedicated && !QuarkService::URLMatch($service, $tasks)) return false;
				if (!$dedicated && QuarkService::URLMatch($service, $tasks)) return false;

				return $service instanceof IQuarkScheduledTask;
			});
		}

		if ($argc > 1) {
			if ($dedicated) {
				if (!$this->_started) {
					$this->_started = true;

					$this->ShellView(
						'Quark:Dedicated - ' . ($argc > 2 ? $argv[2] : self::DEDICATED_UNKNOWN),
						'Starting Quark dedicated process... '
					);

					$this->ShellLog('Started with ' . sizeof($this->_tasks) . ' tasks', Quark::LOG_OK);
					echo "\r\n";
				}

				foreach ($this->_tasks as $i => &$task)
					$task->Launch($argc, $argv);

				unset($i, $task);
			}
			else {
				if ($argv[1] == QuarkTask::PREDEFINED || $argv[1] == QuarkTask::PREDEFINED_ALIAS) {
					if (!isset($argv[2]))
						throw new QuarkArchException('Predefined scenario not selected');

					try {
						$service = QuarkService::Custom('/' . $argv[2], __DIR__ . '/Scenarios', 'Quark/Scenarios', QuarkService::POSTFIX_PREDEFINED_SCENARIO, false)->Service();
					}
					catch (QuarkHTTPException $e) {
						throw new QuarkArchException('Unknown predefined scenario ' . $e->class);
					}
				}
				else $service = (new QuarkService('/' . $argv[1]))->Service();

				if (!($service instanceof IQuarkTask))
					throw new QuarkArchException('Class ' . get_class($service) . ' is not an IQuarkTask');

				/**
				 * @var QuarkService|IQuarkTask|QuarkCLIBehavior $service
				 */
				if (QuarkObject::Uses($service, 'Quark\\QuarkCLIBehavior'))
					$service->ShellInput($argv);

				$service->Task($argc, $argv);
			}
		}
		else {
			if (!$this->_started) {
				$this->_started = true;

				$this->ShellView(
					'Quark:Main',
					'Starting Quark main process...'
				);

				if ($this->_start !== null) {
					$service = (new QuarkService('/' . $this->_start))->Service();

					if (!($service instanceof IQuarkApplicationStartTask))
						throw new QuarkArchException('Class ' . get_class($service) . ' is not an IQuarkApplicationStartTask');

					$service->ApplicationStartTask($argc, $argv);
				}

				$this->ShellLog('Started with ' . sizeof($this->_tasks) . ' tasks', Quark::LOG_OK);
				echo "\r\n";
			}

			foreach ($this->_tasks as $i => &$task)
				$task->Launch($argc, $argv);

			unset($i, $task);
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

	/**
	 * @param string $uri = ''
	 *
	 * @return QuarkCLIEnvironment
	 */
	public static function WithApplicationStart ($uri = '') {
		$cli = new self();
		$cli->ApplicationStart($uri);

		return $cli;
	}

	/**
	 * @param string $lvl = Quark::LOG_INFO
	 *
	 * @return string
	 */
	public static function ShellStatus ($lvl = Quark::LOG_INFO) {
		return isset(self::$_statuses[$lvl]) ? self::$_statuses[$lvl] : self::STATUS_UNKNOWN;
	}
}

/**
 * Trait QuarkCLIViewBehavior
 *
 * @package Quark
 */
trait QuarkCLIViewBehavior {
	/**
	 * @param string $data = ''
	 * @param QuarkCLIColor $color = null
	 * @param bool $reset = true
	 *
	 * @return string
	 */
	public function ShellLine ($data = '', QuarkCLIColor $color = null, $reset = true) {
		return ($color ? $color->Display() : '') . $data . ($color && $reset ? QuarkCLIColor::Reset()->Display() : '');
	}

	/**
	 * @param string $data = ''
	 * @param bool $newLine = false
	 *
	 * @return string
	 */
	public function ShellLineInfo ($data = '', $newLine = false) {
		return $this->ShellLine($data, new QuarkCLIColor(QuarkCLIColor::CYAN)) . ($newLine ? "\r\n" : '');
	}

	/**
	 * @param string $data = ''
	 * @param bool $newLine = false
	 *
	 * @return string
	 */
	public function ShellLineSuccess ($data = '', $newLine = false) {
		return $this->ShellLine($data, new QuarkCLIColor(QuarkCLIColor::GREEN)) . ($newLine ? "\r\n" : '');
	}

	/**
	 * @param string $data = ''
	 * @param bool $newLine = false
	 *
	 * @return string
	 */
	public function ShellLineWarning ($data = '', $newLine = false) {
		return $this->ShellLine($data, new QuarkCLIColor(QuarkCLIColor::YELLOW)) . ($newLine ? "\r\n" : '');
	}

	/**
	 * @param string $data = ''
	 * @param bool $newLine = false
	 *
	 * @return string
	 */
	public function ShellLineError ($data = '', $newLine = false) {
		return $this->ShellLine($data, new QuarkCLIColor(QuarkCLIColor::RED)) . ($newLine ? "\r\n" : '');
	}

	/**
	 * @param string $message = ''
	 * @param string $lvl = null
	 * @param bool $space = true
	 *
	 * @return bool
	 */
	public function ShellLog ($message = '', $lvl = null, $space = true) {
		echo ($space ? ' ' : ''), $this->ShellLine($message, QuarkCLIColor::ForLog($lvl)), "\r\n";

		return true;
	}

	/**
	 * @param string $title = ''
	 * @param string $content = ''
	 * @param callable $process = null
	 */
	public function ShellView ($title = '', $content = '', callable $process = null) {
		echo "\r\n ",
			$this->ShellLine(' ' . $title . ' ', new QuarkCLIColor(
				QuarkCLIColor::BLACK,
				QuarkCLIColor::WHITE
			), true), ' ',
			($content ? "\r\n " . $content : '');

		if ($process) $process();

		echo "\r\n";
	}

	/**
	 * @param string $title = ''
	 * @param string $ok = ''
	 * @param string $fail = ''
	 * @param callable $process = null
	 *
	 * @return bool
	 */
	public function ShellProcess ($title = '', $ok = '', $fail = '', callable $process = null) {
		if (!$process)
			new QuarkArchException('ShellProcess requires a callable value for $process argument');

		echo $title;

		if ($process()) echo $ok;
		else echo $fail;

		return true;
	}

	/**
	 * @param string $lvl = Quark::LOG_INFO
	 * @param string $append = ''
	 * @param bool $space = true
	 *
	 * @return string
	 */
	public function ShellProcessStatus ($lvl = Quark::LOG_INFO, $append = '', $space = true) {
		return
			($space ? ' ' : '') . $this->ShellLine(QuarkCLIEnvironment::ShellStatus($lvl), QuarkCLIColor::ForLog($lvl)) . "\r\n" .
			($append ? (($space ? ' ' : '') . $append . "\r\n") : '');
	}

	/**
	 * @param string $message = ''
	 *
	 * @throws QuarkArchException
	 */
	public function ShellArchException ($message = '') {
		$this->ShellLog($message, Quark::LOG_FATAL); echo "\r\n";
		throw new QuarkArchException($message);
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
	 * @return string
	 */
	public function ExtensionName();

	/**
	 * @param object $ini
	 *
	 * @return mixed
	 */
	public function ExtensionOptions($ini);

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance();
}

/**
 * Interface IQuarkExtensionConfigWithForcedOptions
 *
 * @package Quark
 */
interface IQuarkExtensionConfigWithForcedOptions extends IQuarkExtensionConfig { }

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
	 * @param QuarkDTO $request
	 *
	 * @return IQuarkIOProcessor
	 */
	public function Processor(QuarkDTO $request);
}

/**
 * Interface IQuarkServiceWithCustomRequestProcessor
 *
 * @package Quark
 */
interface IQuarkServiceWithCustomRequestProcessor {
	/**
	 * @param QuarkDTO $request
	 *
	 * @return IQuarkIOProcessor
	 */
	public function RequestProcessor(QuarkDTO $request);
}

/**
 * Interface IQuarkServiceWithCustomResponseProcessor
 *
 * @package Quark
 */
interface IQuarkServiceWithCustomResponseProcessor {
	/**
	 * @param QuarkDTO $request
	 *
	 * @return IQuarkIOProcessor
	 */
	public function ResponseProcessor(QuarkDTO $request);
}

/**
 * Interface IQuarkPolymorphicService
 *
 * @package Quark
 */
interface IQuarkPolymorphicService {
	/**
	 * @param QuarkDTO $request
	 *
	 * @return IQuarkIOProcessor[]
	 */
	public function Processors(QuarkDTO $request);
}

/**
 * Interface IQuarkServiceWithFilter
 *
 * @package Quark
 */
interface IQuarkServiceWithFilter {
	/**
	 * @param QuarkDTO $dto
	 * @param QuarkSession $session
	 *
	 * @return IQuarkIOFilter
	 */
	public function Filter(QuarkDTO $dto, QuarkSession $session);
}

/**
 * Interface IQuarkServiceWithRequestFilter
 *
 * @package Quark
 */
interface IQuarkServiceWithRequestFilter {
	/**
	 * @param QuarkDTO $output
	 * @param QuarkSession $session
	 *
	 * @return IQuarkIOFilter
	 */
	public function RequestFilter(QuarkDTO $output, QuarkSession $session);
}

/**
 * Interface IQuarkServiceWithResponseFilter
 *
 * @package Quark
 */
interface IQuarkServiceWithResponseFilter {
	/**
	 * @param QuarkDTO $output
	 * @param QuarkSession $session
	 *
	 * @return IQuarkIOFilter
	 */
	public function ResponseFilter(QuarkDTO $output, QuarkSession $session);
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
 * Interface IQuarkServiceWithSignatureControl
 *
 * @package Quark
 */
interface IQuarkServiceWithSignatureControl extends IQuarkSignedService {
	/**
	 * @param QuarkDTO $request
	 * @param QuarkSession $session
	 *
	 * @return bool
	 */
	public function SignatureControl(QuarkDTO $request, QuarkSession $session);
}

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
 * Interface IQuarkUnbufferedService
 *
 * @package Quark
 */
interface IQuarkUnbufferedService extends IQuarkService {
	/**
	 * @return int
	 */
	public function OutputContentLength();
}

/**
 * Class QuarkTask
 *
 * @package Quark
 */
class QuarkTask {
	const PREDEFINED = '--quark';
	const PREDEFINED_ALIAS = '-q';

	const DEDICATED = '--dedicated';
	const DEDICATED_ALIAS = '-d';

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
		return preg_replace('#^Services\\\#Uis', '', get_class($this->_service));
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
	 * @param array $args = []
	 * @param string $queue = self::QUEUE
	 *
	 * @return mixed
	 *
	 * @throws QuarkArchException
	 */
	public function AsyncLaunch ($args = [], $queue = self::QUEUE) {
		if (!($this->_service instanceof IQuarkTask))
			throw new QuarkArchException('Trying to async launch service ' . ($this->_service ? get_class($this->_service) : 'null') . ' which is not an IQuarkTask');

		array_unshift($args, Quark::EntryPoint(), $this->Name());

		$out = $this->_service instanceof IQuarkAsyncTask
			? $this->_service->OnLaunch(sizeof($args), $args)
			: null;

		$this->_io->Data($args);

		if (func_num_args() < 2 && $queue == self::QUEUE)
			Quark::Config()->AsyncQueue($queue, QuarkURI::FromURI($queue), $this->Transport());
		
		$uri = Quark::Config()->AsyncQueue($queue);

		if (!($uri->Key() instanceof QuarkURI))
			throw new QuarkArchException('Trying to connect to async queue ' . $queue . ' which is not set');
		
		$protocol = $uri->Value();
		$client = new QuarkClient($uri->Key(), ($protocol ? $protocol->Transport() : $this->Transport()), null, 30);

		$client->On(QuarkClient::EVENT_CONNECT, function (QuarkClient $client) {
			$this->_io->Data(array(
				'task' => get_class($this->_service),
				'args' => $this->_io->Data()
			));

			$out = $this->_io->SerializeRequestBody();

			return $client->Send($out) && $client->Close();
		});

		if (!$client->Connect()) return false;

		return $out;
	}

	/**
	 * @param string $queue = self::QUEUE
	 *
	 * @return QuarkServer
	 */
	public static function AsyncQueue ($queue = self::QUEUE) {
		$uri = Quark::Config()->AsyncQueue($queue);

		if (!($uri->Key() instanceof QuarkURI)) return null;

		$protocol = $uri->Value();

		$task = new QuarkTask();
		$server = new QuarkServer($uri->Key(), $protocol ? $protocol->Transport() : $task->Transport());

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

		return $server;
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

	/**
	 * @var int $_argc = 0
	 */
	private $_argc = 0;

	/**
	 * @var array $_argv = []
	 */
	private $_argv = array();

	/**
	 * @param int $argc
	 * @param array $argv
	 */
	public function __construct ($argc, $argv) {
		$this->_argc = $argc;
		$this->_argv = $argv;
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
				$run_tmp = call_user_func_array(array($thread, 'Thread'), array(&$this->_argc, &$this->_argv));
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

		if (!$this->_last->Later($now, $this->_time)) return;

		$this->_last = $now;

		call_user_func_array($this->_callback, array(&$this) + func_get_args());
	}

	/**
	 * @return QuarkTimer
	 */
	public function Destroy () {
		foreach (self::$_timers as $i => &$timer)
			if ($timer->_id == $this->_id)
				unset(self::$_timers[$i]);

		return $this;
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
	 * @return QuarkService
	 */
	private function _envelope () {
		return new QuarkService($this);
	}

	/**
	 * @param IQuarkService $service = null
	 * @param bool $query = true
	 * @param bool $fragment = true
	 *
	 * @return string
	 */
	public function URL (IQuarkService $service = null, $query = true, $fragment = true) {
		return $this->__call('URL', func_get_args());
	}

	/**
	 * @return string|bool
	 */
	public function URLOffset () {
		return $this->__call('URLOffset', func_get_args());
	}

	/**
	 * @return QuarkDTO
	 */
	public function Input () {
		return $this->__call('Input', func_get_args());
	}

	/**
	 * @return QuarkSession
	 */
	public function Session () {
		return $this->__call('Session', func_get_args());
	}

	/**
	 * @param string $url = ''
	 * @param string $method = QuarkDTO::METHOD_GET
	 * @param QuarkDTO|object|array $input = []
	 * @param QuarkSession $session = null
	 *
	 * @return mixed
	 */
	public function InvokeURL ($url = '', $method = QuarkDTO::METHOD_GET, $input = [], QuarkSession $session = null) {
		$num = func_num_args();

		$service = new QuarkService($url);
		$service->Input()->Merge($num < 3 ? $this->Input() : $input);
		$service->Session($num < 4 ? $this->Session() : $session);
		$service->Input()->URI(QuarkURI::FromURI($url));
		
		$output = $service->Invoke($method, $input !== null ? array($service->Input()) : array(), true);

		unset($service);

		return $output;
	}

	/**
	 * @param string $internal = ''
	 * @param string $external = ''
	 *
	 * @return IQuarkService
	 *
	 * @throws QuarkArchException
	 */
	public function ListenURL ($internal = '', $external = '') {
		if (!($this instanceof IQuarkService))
			throw new QuarkArchException('[QuarkServiceBehavior::ListenURL] Class "' . $this->ClassName() . '" is not IQuarkService');

		if (func_num_args() == 2)
			Quark::Config()->Route($external, ':' . $internal);

		return QuarkService::Internal(':' . $internal, $this);
	}

	/**
	 * @param string $name = ''
	 * @param string $content = ''
	 *
	 * @return QuarkDTO
	 */
	public function Download ($name = '', $content = '') {
		return QuarkFile::ForTransfer($name, $content)->Download();
	}

	/**
	 * @param IQuarkIOProcessor $processor
	 * @param string $name = ''
	 * @param $data = []
	 *
	 * @return QuarkDTO
	 */
	public function EncodeAndDownload (IQuarkIOProcessor $processor, $name = '', $data = []) {
		return QuarkFile::ForTransfer($name . '.' . QuarkFile::ExtensionByMime($processor->MimeType()), $processor->Encode($data))->Download();
	}

	/**
	 * @param string $content = ''
	 *
	 * @return QuarkDTO
	 */
	public function Render ($content = '') {
		return QuarkFile::ForTransfer($content)->Render();
	}

	/**
	 * @param IQuarkSpecifiedViewResource $resource = null
	 * @param $vars = []
	 * @param IQuarkSpecifiedViewResource[] $dependencies = []
	 * @param bool $minimize = true
	 *
	 * @return QuarkDTO
	 */
	public function RenderResource (IQuarkSpecifiedViewResource $resource = null, $vars = [], $dependencies = [], $minimize = true) {
		return QuarkDTO::ForResource($resource, $vars, $dependencies, $minimize);
	}

	/**
	 * @param string $location = ''
	 * @param string $scope = ''
	 * @param $vars = []
	 * @param IQuarkSpecifiedViewResource[] $dependenies = []
	 * @param bool $minimize = true
	 *
	 * @return QuarkDTO
	 */
	public function RenderServiceWorker ($location = '', $scope = '', $vars = [], $dependencies = [], $minimize = true) {
		return QuarkDTO::ForServiceWorker($location, $scope, $vars, $dependencies, $minimize);
	}

	/**
	 * @param IQuarkService $service = null
	 * @param bool $query = true
	 * @param bool $fragment = true
	 *
	 * @return string
	 */
	public function WebLocation (IQuarkService $service = null, $query = true, $fragment = true) {
		return Quark::WebLocation($this->URL($service, $query, $fragment));
	}

	/**
	 * @param IQuarkService $service = null
	 *
	 * @return string
	 */
	public function WebPath (IQuarkService $service = null) {
		return Quark::WebLocation($this->ServiceURL($service));
	}

	/**
	 * @param IQuarkService $service = null
	 * @param bool $fallbackOriginal = true
	 *
	 * @return string
	 */
	public function ServiceURL (IQuarkService $service = null, $fallbackOriginal = true) {
		$url = $this->URL(func_num_args() != 0 ? $service : $this, false, false);

		if (substr($url, -6) == '/Index')
			$url = substr($url, 0, -6);

		$parts = explode('/', $url);
		$out = array();

		foreach ($parts as $i => &$part)
			$out[] = $fallbackOriginal
				? (preg_match_all('#([A-Z]{1})#', $part) > 1 ? $part : strtolower($part))
				: strtolower(trim(preg_replace('#([A-Z]{1})#', '-$1', $part), '-'));

		return implode('/', $out);
	}

	/**
	 * @return string
	 */
	public function CalledURL () {
		return $this->EnvironmentIsCLI() ? $this->ServiceURL() : $this->URL();
	}
}

/**
 * Trait QuarkCLIBehavior
 *
 * @package Quark
 */
trait QuarkCLIBehavior {
	use QuarkServiceBehavior;
	use QuarkCLIViewBehavior;

	/**
	 * @var array $_shellInput = []
	 */
	private $_shellInput = array();

	/**
	 * @var array $_shellOutput = []
	 */
	private $_shellOutput = array();

	/**
	 * @var QuarkHTTPClient[] $_asyncClients = []
	 */
	private $_asyncClients = array();

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
	 * @param array $argv = []
	 *
	 * @return array
	 */
	public function ShellInput ($argv = []) {
		if (func_num_args() != 0)
			$this->_shellInput = $argv;

		return $this->_shellInput;
	}

	/**
	 * @return array
	 */
	public function ShellOutput () {
		return $this->_shellOutput;
	}

	/**
	 * @param int $id = 0
	 *
	 * @return mixed
	 */
	public function Arg ($id = 0) {
		return isset($this->_shellInput[$id]) ? $this->_shellInput[$id] : null;
	}

	/**
	 * @param bool $flags = false
	 *
	 * @return string[]
	 */
	public function ServiceArgs ($flags = false) {
		if (!isset($this->_shellInput[0]))
			return $this->_shellInput;

		$args = array_slice($this->_shellInput, 1);

		if ($args[0] == QuarkTask::PREDEFINED || $args[0] == QuarkTask::PREDEFINED_ALIAS)
			$args = array_slice($args, 1);

		$args = array_slice($args, 1);

		if (!$flags) {
			foreach ($args as $i => &$arg)
				if (strlen($arg) != 0 && $arg[0] == '-')
					unset($args[$i]);

			unset($i, $arg);
		}

		return array_values($args);
	}

	/**
	 * @param int $id = 0
	 * @param bool $flags = false
	 *
	 * @return string
	 */
	public function ServiceArg ($id = 0, $flags = false) {
		$args = $this->ServiceArgs($flags);

		return isset($args[$id]) ? $args[$id] : null;
	}

	/**
	 * @param string $arg = ''
	 * @param string $flag = ''
	 * @param string $alias = ''
	 * @param string $prefixFlag = '--'
	 * @param string $prefixAlias = '-'
	 *
	 * @return bool
	 */
	private function _isFlag ($arg = '', $flag = '', $alias = '', $prefixFlag = '--', $prefixAlias = '-') {
		$flag = $prefixFlag . $flag;
		$alias = $prefixAlias . $alias;

		return $arg == $flag || ($alias != '' && $arg == $alias);
	}

	/**
	 * @param string $flag = ''
	 * @param string $alias = ''
	 * @param string $prefixFlag = '--'
	 * @param string $prefixAlias = '-'
	 *
	 * @return bool
	 */
	public function HasFlag ($flag = '', $alias = '', $prefixFlag = '--', $prefixAlias = '-') {
		foreach ($this->_shellInput as $i => &$arg)
			if ($this->_isFlag($arg, $flag, $alias, $prefixFlag, $prefixAlias)) return true;
		
		return false;
	}

	/**
	 * @param string $flag = ''
	 * @param string $alias = ''
	 * @param string $prefixFlag = '--'
	 * @param string $prefixAlias = '-'
	 *
	 * @return mixed
	 */
	public function Flag ($flag = '', $alias = '', $prefixFlag = '--', $prefixAlias = '-') {
		$i = 0;
		$size = sizeof($this->_shellInput);

		while ($i < $size) {
			$arg = $this->_shellInput[$i];
			$next = isset($this->_shellInput[$i + 1]) ? $this->_shellInput[$i + 1] : null;

			$ok = $this->_isFlag($arg, $flag, $alias, $prefixFlag, $prefixAlias)
				&& $next !== null
				&& !$this->_isFlag($next, $flag, $alias, $prefixFlag, $prefixAlias);

			if ($ok) return $next;

			$i++;
		}

		return null;
	}

	/**
	 * @param IQuarkAsyncTask $task
	 * @param array $args = []
	 * @param string $queue = QuarkTask::QUEUE
	 *
	 * @return mixed
	 *
	 * @throws QuarkArchException
	 */
	public function AsyncTask (IQuarkAsyncTask $task, $args = [], $queue = QuarkTask::QUEUE) {
		$cmd = new QuarkTask($task);

		return $cmd->AsyncLaunch($args, $queue);
	}

	/**
	 * @param IQuarkAsyncClientProcess $process = null
	 *
	 * @return bool
	 * @throws QuarkArchException
	 */
	public function AsyncClientProcess (IQuarkAsyncClientProcess $process = null) {
		if ($process == null)
			$process = $this;

		if (!($process instanceof IQuarkAsyncClientProcess))
			throw new QuarkArchException('[' . $this->ClassName() . '::AsyncClientProcess] Argument $process expected to be IQuarkAsyncClientProcess');

		$id = $process->AsyncClientProcessID();

		if (!$process->AsyncClientProcessActive($id)) {
			if (!isset($this->_asyncClients[$id]) || $this->_asyncClients[$id] == null) return false;

			$this->_asyncClients[$id]->Client()->Close();
			$process->AsyncClientProcessStop($id);
			unset($this->_asyncClients[$id]);

			return true;
		}

		if (!isset($this->_asyncClients[$id]))
			$this->_asyncClients[$id] = $process->AsyncClientProcessStart($id);

		if (!($this->_asyncClients[$id] instanceof QuarkHTTPClient))
			throw new QuarkArchException('[' . $this->ClassName() . '::AsyncClientProcess] Expected QuarkHTTPClient, got [' . gettype($this->_asyncClients[$id]) . '] ' . print_r($this->_asyncClients[$id], true));

		$this->_asyncClients[$id]->AsyncPipe();

		if (!$this->_asyncClients[$id]->Connected()) {
			$process->AsyncClientProcessStop($id);
			unset($this->_asyncClients[$id]);
		}

		return true;
	}
}

/**
 * Interface IQuarkAsyncClientProcess
 *
 * @package Quark
 */
interface IQuarkAsyncClientProcess {
	/**
	 * @return string
	 */
	public function AsyncClientProcessID();

	/**
	 * @param string $id
	 *
	 * @return bool
	 */
	public function AsyncClientProcessActive($id);

	/**
	 * @param string $id
	 *
	 * @return QuarkHTTPClient
	 */
	public function AsyncClientProcessStart($id);

	/**
	 * @param string $id
	 *
	 * @return mixed
	 */
	public function AsyncClientProcessStop($id);
}

/**
 * Trait QuarkStreamBehavior
 *
 * @package Quark
 */
trait QuarkStreamBehavior {
	use QuarkCLIBehavior;

	/**
	 * @param QuarkDTO|object|array $data
	 * @param string $url = ''
	 *
	 * @return bool
	 */
	public function Broadcast ($data, $url = '') {
		$env = Quark::CurrentEnvironment();
		$url = func_num_args() == 2 ? $url : $this->CalledURL();

		if ($env instanceof QuarkStreamEnvironment) {
			$session = $this->Session();
			$clients = $env->Cluster()->Server()->Clients();

			foreach ($clients as $i => &$client) {
				$connection = $session->Connection();

				if ($connection && $client->ID() == $connection->ID())
					$client->Session($session->ID());
			}

			unset($connection, $clients, $session);

			$out = $env->BroadcastNetwork($url, $data);
		}
		else $out = QuarkStreamEnvironment::ControllerCommand(
			QuarkStreamEnvironment::COMMAND_BROADCAST,
			QuarkStreamEnvironment::Payload(QuarkStreamEnvironment::PACKAGE_REQUEST, $url, $data),
			$env instanceof QuarkCLIEnvironment // TODO: change to ability check, not of direct 'QuarkCLIEnvironment'
		);

		unset($env, $data);

		return $out;
	}

	/**
	 * @param QuarkDTO|object|array $data
	 * @param IQuarkStreamNetwork $service = null
	 *
	 * @return bool
	 */
	public function BroadcastService ($data, IQuarkStreamNetwork $service = null) {
		return $this->Broadcast($data, $this->URL($service));
	}

	/**
	 * @param callable(QuarkSession $client) $sender = null
	 * @param bool $auth = true
	 *
	 * @return bool
	 *
	 * @throws QuarkArchException
	 */
	public function Event (callable $sender = null, $auth = true) {
		$env = Quark::CurrentEnvironment();

		if ($env instanceof QuarkStreamEnvironment) return $env->BroadcastLocal($this->URL(), $sender, $auth);
		else throw new QuarkArchException('QuarkStreamBehavior: the `Event` method cannot be called in a non-stream environment');
	}

	/**
	 * @param string $channel = ''
	 * @param callable(QuarkSession $client) $sender = null
	 * @param bool $auth = true
	 *
	 * @return bool
	 *
	 * @throws QuarkArchException
	 */
	public function ChannelEvent ($channel = '', callable $sender = null, $auth = true) {
		$env = Quark::CurrentEnvironment();

		if ($env instanceof QuarkStreamEnvironment) return $env->BroadcastLocal($this->URL(), $sender, $auth, $channel);
		else throw new QuarkArchException('QuarkStreamBehavior: the `ChannelEvent` method cannot be called in a non-stream environment');
	}

	/**
	 * @param string $url = ''
	 * @param QuarkDTO|object|array $input = []
	 * @param QuarkSession $session = null
	 *
	 * @return mixed
	 */
	public function InvokeStream ($url = '', $input = [], QuarkSession $session = null) {
		$num = func_num_args();

		return $this->InvokeURL(
			$url,
			'Stream',
			$num < 3 ? $this->Input() : $input,
			$num < 4 ? $this->Session() : $session
		);
	}

	/**
	 * @return QuarkCluster
	 */
	public function &Cluster () {
		$env = Quark::CurrentEnvironment();

		if ($env instanceof QuarkStreamEnvironment)
			return $env->Cluster();

		$null = null;
		return $null;
	}
}

/**
 * Class QuarkCLIColor
 *
 * @package Quark
 */
class QuarkCLIColor {
	const BLACK = 0;
	const RED = 1;
	const GREEN = 2;
	const YELLOW = 3;
	const BLUE = 4;
	const MAGENTA = 5;
	const CYAN = 6;
	const WHITE = 7;
	
	/**
	 * @var array
	 */
	private static $_logs = array(
		Quark::LOG_INFO => self::CYAN,
		Quark::LOG_OK => self::GREEN,
		Quark::LOG_WARN => self::YELLOW,
		Quark::LOG_FATAL => self::RED
	);
	
	/**
	 * @param string $lvl = Quark::LOG_INFO
	 *
	 * @return QuarkCLIColor
	 */
	public static function ForLog ($lvl = Quark::LOG_INFO) {
		return new self(isset(self::$_logs[$lvl]) ? self::$_logs[$lvl] : null);
	}
	
	/**
	 * @var int $_color = self::WHITE
	 */
	private $_color = self::WHITE;
	
	/**
	 * @var int $_background
	 */
	private $_background;
	
	/**
	 * @param int $color = self::WHITE
	 * @param int $background = null
	 */
	public function __construct ($color = self::WHITE, $background = null) {
		$this->Color($color);
		$this->Background($background);
	}
	
	/**
	 * @param int $color = self::WHITE
	 *
	 * @return int
	 */
	public function Color ($color = self::WHITE) {
		if (func_num_args() != 0)
			$this->_color = $color;
		
		return $this->_color;
	}
	
	/**
	 * @param int $background = null
	 *
	 * @return int
	 */
	public function Background ($background = null) {
		if (func_num_args() != 0)
			$this->_background = $background;
		
		return $this->_background;
	}
	
	/**
	 * @return string
	 */
	public function Display () {
		return "\033[" . ($this->_color === null ? 0 : '3' . $this->_color . ($this->_background === null ? '' : ';4' . $this->_background)) . 'm';
	}
	
	/**
	 * @return QuarkCLIColor
	 */
	public static function Reset () {
		return new self(null);
	}
}

/**
 * Class QuarkService
 *
 * @package Quark
 */
class QuarkService implements IQuarkContainer {
	const POSTFIX_SERVICE = 'Service';
	const POSTFIX_PREDEFINED_SCENARIO = '';

	const EVENT_OUTPUT_HEADERS = 'service.event.output.headers';
	const EVENT_OUTPUT_BODY = 'service.event.output.body';

	use QuarkEvent;

	/**
	 * @var IQuarkService|IQuarkAuthorizableService|IQuarkServiceWithAccessControl|IQuarkPolymorphicService|IQuarkUnbufferedService $_service
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
	 * @var IQuarkIOFilter $_inputFilter
	 */
	private $_inputFilter;

	/**
	 * @var IQuarkIOFilter $_outputFilter
	 */
	private $_outputFilter;

	public static function Instance ($path = '') {
		// TODO: Use this to instantiate a service from a file
	}

	/**
	 * @param string $service
	 * @param string $base = ''
	 * @param string $postfix = self::POSTFIX_SERVICE
	 *
	 * @return string
	 */
	private static function _bundle ($service, $base = '', $postfix = self::POSTFIX_SERVICE) {
		return Quark::NormalizePath($base . '/' . $service . $postfix . '.php', false);
	}

	/**
	 * @param string $uri = ''
	 * @param string $base = ''
	 * @param string $namespace = ''
	 * @param string $postfix = self::POSTFIX_SERVICE
	 *
	 * @return IQuarkService
	 *
	 * @throws QuarkArchException
	 * @throws QuarkHTTPException
	 */
	public static function Resolve ($uri = '', $base = '', $namespace = '', $postfix = self::POSTFIX_SERVICE) {
		if ($uri == 'index.php') $uri = '';

		$route = QuarkURI::FromURI(Quark::NormalizePath($uri), false);
		$path = QuarkURI::ParseRoute($route->path);

		$buffer = array();

		foreach ($path as $i => &$item) {
			if (strlen(trim($item)) == 0) continue;

			$section = explode('-', trim($item));
			$out = array();

			foreach ($section as $j => &$elem)
				$out[] = ucfirst($elem);

			unset($j, $elem);

			$buffer[] = implode('', $out);
		}

		unset($i, $item);

		$route = $buffer;
		unset($buffer);
		$length = sizeof($route);
		$service = $length == 0 ? 'Index' : implode('/', $route);
		$path = self::_bundle($service, $base, $postfix);

		while ($length > 0) {
			if (is_file($path)) break;

			$index = self::_bundle($service . '\\Index', $base, $postfix);

			if (is_file($index)) {
				$service .= '\\Index';
				$path = $index;

				break;
			}

			$length--;
			$service = preg_replace('#\/' . preg_quote(ucfirst(trim($route[$length]))) . '$#Uis', '', $service);
			$path = self::_bundle($service, $base, $postfix);
		}

		if (Quark::Config()->AllowIndexFallback() && !file_exists($path)) {
			$service = 'Index';
			$path = self::_bundle($service, $base, $postfix);
		}

		if (!file_exists($path))
			throw QuarkHTTPException::ForStatus(QuarkDTO::STATUS_404_NOT_FOUND, 'Unknown service file ' . $path);

		$class = str_replace('/', '\\', ($namespace ? '/' . $namespace : '') . '/' . $service . $postfix);

		unset($length, $path, $service, $index, $route);

		$bundle = new $class();

		if (!($bundle instanceof IQuarkService))
			throw new QuarkArchException('Class ' . $class . ' is not an IQuarkService');

		return $bundle;
	}

	/**
	 * @param IQuarkService|string $uri
	 * @param string $base = null
	 * @param string $namespace = ''
	 * @param string $postfix = ''
	 * @param array $routes = []
	 *
	 * @return QuarkService
	 */
	public static function Custom ($uri, $base = null, $namespace = '', $postfix = '', $routes = []) {
		return new self($uri, null, null, $base, $namespace, $postfix, $routes);
	}

	public static function Suite ($base = '', callable $filter = null) {
		$dir = new \RecursiveDirectoryIterator($base);
		$fs = new \RecursiveIteratorIterator($dir);

		$out = array();

		foreach ($fs as $file) {
			/**
			 * @var \FilesystemIterator $file
			 */
			// TODO: REFACTOR
			if ($file->isDir() || !strstr($file->getFilename(), 'Service.php')) continue;

			$class = QuarkObject::ClassIn($file->getPathname());

			/**
			 * @var IQuarkService $service
			 */
			$service = new $class();

			if ($service instanceof IQuarkService && ($filter == null || $filter($service)))
				$out[] = new QuarkTask($service);

			unset($service);
		}

		return $out;
	}

	/**
	 * @param IQuarkService|string $uri
	 * @param IQuarkIOProcessor $input = null
	 * @param IQuarkIOProcessor $output = null
	 * @param string $base = null
	 * @param string $namespace = null
	 * @param string $postfix = self::POSTFIX_SERVICE
	 * @param array $routes = []
	 *
	 * @throws QuarkArchException
	 * @throws QuarkHTTPException
	 */
	public function __construct ($uri, IQuarkIOProcessor $input = null, IQuarkIOProcessor $output = null, $base = null, $namespace = null, $postfix = self::POSTFIX_SERVICE, $routes = []) {
		if ($uri instanceof IQuarkService) {
			$this->_service = $uri;
			$class = get_class($this->_service);
			$uri = substr(substr($class, 8), 0, -7);
		}
		else {
			$namespace = $namespace !== null ? $namespace : Quark::Config()->Location(QuarkConfig::SERVICES);
			$base = $base !== null ? $base : Quark::Host() . '/' . $namespace;

			if ($routes) {
				$query = strpos($uri, '?');
				$route = $query ? substr($uri, 0, $query) :  substr($uri, 0);

				foreach ($routes as $original => &$target) {
					if (strlen($target) == 0 || !preg_match('#' . $original . '#Uis', $route)) continue;

					$uri = $target;

					if ($target[0] == ':')
						$this->_service = QuarkService::Internal($target);
				}

				unset($target, $original, $routes, $route, $query);
			}

			$this->_service = $this->_service ? $this->_service : self::Resolve($uri, $base, $namespace, $postfix);
		}

		$this->_input = new QuarkDTO();
		$this->_input->Processor($input ? $input : new QuarkFormIOProcessor());
		$this->_output = new QuarkDTO();
		$this->_output->Processor($output ? $output : new QuarkHTMLIOProcessor());
		$this->_input->URI(QuarkURI::FromURI(Quark::NormalizePath($uri, false), false));
		
		Quark::Container($this);
	}

	/**
	 * @return QuarkService
	 */
	public function InitProcessors () {
		if ($this->_service instanceof IQuarkServiceWithCustomProcessor) {
			$processor = $this->_service->Processor($this->_input);

			$this->_input->Processor($processor);
			$this->_output->Processor($processor);
		}

		if ($this->_service instanceof IQuarkServiceWithCustomRequestProcessor)
			$this->_input->Processor($this->_service->RequestProcessor($this->_input));

		if ($this->_service instanceof IQuarkServiceWithCustomResponseProcessor)
			$this->_output->Processor($this->_service->ResponseProcessor($this->_input));

		return $this;
	}

	/**
	 * @return QuarkService
	 */
	public function InitFilters () {
		if ($this->_service instanceof IQuarkServiceWithFilter) {
			$filter = $this->_service->Filter($this->_input, $this->_session);

			$this->_inputFilter = $filter;
			$this->_outputFilter = $filter;
		}

		if ($this->_service instanceof IQuarkServiceWithRequestFilter)
			$this->_inputFilter = $this->_service->RequestFilter($this->_input, $this->_session);

		if ($this->_service instanceof IQuarkServiceWithResponseFilter)
			$this->_outputFilter = $this->_service->ResponseFilter($this->_input, $this->_session);

		return $this;
	}

	/**
	 * @param IQuarkPrimitive $primitive = null
	 *
	 * @return IQuarkPrimitive
	 */
	public function &Primitive (IQuarkPrimitive $primitive = null) {
		if (func_num_args() != 0)
			$this->_service = $primitive;

		return $this->_service;
	}

	/**
	 * @param IQuarkService|IQuarkServiceWithAccessControl|IQuarkPolymorphicService $service = null
	 *
	 * @return IQuarkService|IQuarkServiceWithAccessControl|IQuarkPolymorphicService
	 */
	public function &Service (IQuarkService $service = null) {
		if (func_num_args() != 0)
			$this->_service = $service;

		return $this->_service;
	}

	/**
	 * @param QuarkDTO $input = null
	 *
	 * @return QuarkDTO
	 */
	public function &Input (QuarkDTO $input = null) {
		if (func_num_args() != 0)
			$this->_input = $input;

		return $this->_input;
	}

	/**
	 * @param QuarkDTO $output = null
	 *
	 * @return QuarkDTO
	 */
	public function &Output (QuarkDTO $output = null) {
		if (func_num_args() != 0)
			$this->_output = $output;

		return $this->_output;
	}

	/**
	 * @param QuarkSession $session = null
	 *
	 * @return QuarkSession
	 */
	public function &Session (QuarkSession $session = null) {
		if (func_num_args() != 0)
			$this->_session = $session;
		
		return $this->_session;
	}

	/**
	 * @param IQuarkIOFilter $filter = null
	 *
	 * @return IQuarkIOFilter
	 */
	public function InputFilter (IQuarkIOFilter $filter = null) {
		if (func_num_args() != 0)
			$this->_inputFilter = $filter;
		
		return $this->_inputFilter;
	}

	/**
	 * @param IQuarkIOFilter $filter = null
	 *
	 * @return IQuarkIOFilter
	 */
	public function OutputFilter (IQuarkIOFilter $filter = null) {
		if (func_num_args() != 0)
			$this->_outputFilter = $filter;

		return $this->_outputFilter;
	}

	/**
	 * @param IQuarkService $service = null
	 * @param bool $query = true
	 * @param bool $fragment = true
	 *
	 * @return string
	 */
	public function URL (IQuarkService $service = null, $query = true, $fragment = true) {
		return $service ? self::URLOf($service) : $this->_input->URI()->Query($query, $fragment);
	}
	
	/**
	 * @return string|bool
	 */
	public function URLOffset () {
		$url = $this->URL();
		$service = strtolower($this->URL($this->_service));
		
		if (substr($service, -6) == '/index')
			$service = substr($service, 0, -6);
		
		$length = strlen($service);
		$offset = strtolower(substr($url, 0, $length));
		
		if ($service != $offset) return false;
		$out = substr($url, $length);
		
		return $out === '' ? '/' : $out;
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
	 * @param IQuarkService $service
	 * @param string[] $urls = []
	 *
	 * @return bool
	 */
	public static function URLMatch (IQuarkService $service, $urls = []) {
		$url = self::URLOf($service);

		if (substr($url, -6) == '/Index')
			$url = substr($url, 0, -6);

		foreach ($urls as $i => &$item)
			if (preg_match('#^' . $item . '$#is', $url)) return true;

		return false;
	}

	/**
	 * @param bool $checkSignature = false
	 * @param QuarkClient $connection = null
	 *
	 * @return bool
	 *
	 * @throws QuarkArchException
	 */
	public function Authorize ($checkSignature = false, QuarkClient &$connection = null) {
		if ($connection != null)
			$this->_session = QuarkSession::InitWithConnection($connection);

		if (!($this->_service instanceof IQuarkAuthorizableService)) return true;

		$service = get_class($this->_service);
		$provider = $this->_service->AuthorizationProvider($this->_input);

		if ($provider == null)
			throw new QuarkArchException('Service ' . $service . ' does not specified AuthorizationProvider');

		$this->_session = QuarkSession::Init($provider, $this->_input, $connection);

		if (!($this->_service instanceof IQuarkAuthorizableServiceWithAuthentication) && $this->_session != null) return true;

		$criteria = $this->_service->AuthorizationCriteria($this->_input, $this->_session);

		$this->_output->Merge($this->_session->Output(), false);

		if ($criteria !== true) {
			$this->_output->Merge($this->_service->AuthorizationFailed($this->_input, $criteria), false);

			return false;
		}

		if (!$checkSignature) return true;
		if (!($this->_service instanceof IQuarkSignedService)) return true;
		if ($this->_service instanceof IQuarkServiceWithSignatureControl && $this->_service->SignatureControl($this->_input, $this->_session)) return true;

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
	 * @return mixed
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
		
		$this->InitFilters();

		$morph = $this->_service instanceof IQuarkPolymorphicService;
		$selected = null;

		if ($morph) {
			$processors = $this->_service->Processors($this->_input);

			if (is_array($processors)) {
				foreach ($processors as $i => &$processor) {
					if (!($processor instanceof IQuarkIOProcessor)) continue;
					if ($processor->MimeType() == $this->_input->ExpectedType())
						$selected = $processor;
				}

				unset($i, $processor, $processors);
			}
		}
		
		$this->_filterInput();
		$output = call_user_func_array(array(&$this->_service, $method), $args);

		if ($morph)
			$this->_output->Processor($selected);

		$this->_output->Merge($morph && $selected != null && $output instanceof QuarkView
			? $output->ExtractVars()
			: $output,
			true, true
		);
		$this->_filterOutput();

		if ($this->_service instanceof IQuarkAuthorizableService && !$empty)
			$this->_output->Merge($this->_session->Output(), false, !($output instanceof QuarkDTO));

		return $output;
	}

	/**
	 * @param bool $unbuffered = true
	 *
	 * @return mixed|string
	 *
	 * @throws QuarkArchException
	 */
	public function Pipeline ($unbuffered = true) {
		$method = ucfirst(strtolower($this->_input->Method()));

		if (!($this->_service instanceof IQuarkHTTPService))
			throw new QuarkArchException('Method ' . $method . ' is not allowed for service ' . get_class($this->_service));

		if (!method_exists($this->_service, $method) && $this->_service instanceof IQuarkAnyService)
			$method = 'Any';

		if ($unbuffered && $this->_service instanceof IQuarkUnbufferedService) {
			$this->_output->Header(QuarkDTO::HEADER_CONTENT_LENGTH, $this->_service->OutputContentLength());

			return $this->_pipeline($method, true);
		}
		else {
			ob_start();
			echo $this->_pipeline($method, false);
			$out = ob_get_clean();

			$this->_output->Header(QuarkDTO::HEADER_CONTENT_LENGTH, strlen($out));

			$this->Trigger(self::EVENT_OUTPUT_HEADERS);

			return $out;
		}
	}

	/**
	 * @param string $method = ''
	 * @param bool $unbuffered = true
	 *
	 * @return mixed
	 *
	 * @throws QuarkArchException
	 */
	private function _pipeline ($method = '', $unbuffered = true) {
		$auth = $this->Authorize(true);

		if ($unbuffered)
			$this->Trigger(self::EVENT_OUTPUT_HEADERS);

		if ($auth)
			$this->Invoke($method, array($this->_input), true);

		return $this->_output->SerializeResponseBody();
	}

	/**
	 * @return QuarkService
	 */
	private function _filterInput () {
		$input = null;
		$filter = $this->_inputFilter;
		
		if ($filter instanceof IQuarkIOFilter)
			$input = $filter->FilterInput($this->_input, $this->_session);

		if ($input instanceof QuarkDTO)
			$this->_input = $input;

		return $this;
	}

	/**
	 * @return QuarkService
	 */
	private function _filterOutput () {
		$output = null;
		$filter = $this->_outputFilter;

		if ($filter instanceof IQuarkIOFilter)
			$output = $filter->FilterInput($this->_output, $this->_session);

		if ($output instanceof QuarkDTO)
			$this->_output = $output;

		return $this;
	}

	/**
	 * Reset QuarkService
	 */
	public function __destruct () {
		unset($this->_service, $this->_session, $this->_input, $this->_output, $this->_filterInput, $this->_filterOutput);
	}

	/**
	 * @var array $_internal = []
	 */
	private static $_internal = array();

	/**
	 * @param string $uri = ''
	 * @param IQuarkService $service = null
	 *
	 * @return IQuarkService
	 */
	public static function Internal ($uri = '', IQuarkService $service = null) {
		if (func_num_args() == 2 && $service != null)
			self::$_internal[$uri] = $service;

		return isset(self::$_internal[$uri]) ? self::$_internal[$uri] : null;
	}
}

/**
 * Trait QuarkContainerBehavior
 *
 * @package Quark
 */
trait QuarkContainerBehavior {
	/** @noinspection PhpUnusedPrivateMethodInspection
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
		$container = $this->Container();

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
	 * @return IQuarkContainer
	 */
	public function Container () {
		/**
		 * @var IQuarkPrimitive|QuarkContainerBehavior $this
		 */
		$container = Quark::ContainerOf($this->ObjectID());

		if ($container == null)
			$container = $this->_envelope();

		$container->Primitive($this);
		
		return $container;
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

	/**
	 * @param string $key = ''
	 * @param string $language = QuarkLanguage::ANY
	 *
	 * @return string
	 */
	public function LocalizationOf ($key = '', $language = QuarkLanguage::ANY) {
		return Quark::Config()->LocalizationOf($key, $language);
	}

	/**
	 * @param string $key = ''
	 *
	 * @return object
	 */
	public function LocalizationDictionaryOf ($key = '') {
		return Quark::Config()->LocalizationDictionaryOf($key);
	}

	/**
	 * @param string $key = ''
	 * @param bool $strict = false
	 *
	 * @return string
	 */
	public function CurrentLocalizationOf ($key = '', $strict = false) {
		return Quark::Config()->CurrentLocalizationOf($key, $strict);
	}

	/**
	 * @return string
	 */
	public function CurrentLanguage () {
		return Quark::CurrentLanguage();
	}

	/**
	 * @return QuarkModel|IQuarkApplicationSettingsModel
	 */
	public function ApplicationSettings () {
		return Quark::Config()->ApplicationSettings();
	}

	/**
	 * @param string $key = ''
	 *
	 * @return mixed
	 */
	public function LocalSettings ($key = '') {
		return Quark::Config()->LocalSettings($key);
	}

	/**
	 * @param string $name = ''
	 *
	 * @return QuarkURI
	 */
	public function StreamConnectionURI ($name = '') {
		return QuarkStreamEnvironment::ConnectionURI($name);
	}

	/**
	 * @param string $value
	 *
	 * @return mixed
	 */
	public function ConstByValue ($value) {
		return QuarkObject::ClassConstByValue(get_class($this), $value);
	}

	/**
	 * @param string $const = ''
	 *
	 * @return mixed
	 */
	public function ConstValue ($const = '') {
		return QuarkObject::ClassConstValue(get_class($this), $const);
	}

	/**
	 * @param string $regex = ''
	 *
	 * @return array
	 */
	public function Constants ($regex = '') {
		return QuarkObject::ClassConstants(get_called_class(), $regex);
	}

	/**
	 * @param string $regex = ''
	 *
	 * @return array
	 */
	public static function ClassConstants ($regex = '') {
		return QuarkObject::ClassConstants(get_called_class(), $regex);
	}

	/**
	 * @return string
	 */
	public function ClassName () {
		return QuarkObject::ClassOf($this);
	}

	/**
	 * @param string $source = ''
	 * @param array $data = []
	 *
	 * @return string
	 */
	public function Template ($source = '', $data = []) {
		return QuarkView::TemplateString($source, $data);
	}

	/**
	 * @param string $key = ''
	 * @param array $data = []
	 * @param string $language = QuarkLanguage::ANY
	 *
	 * @return string
	 */
	public function TemplatedLocalizationOf ($key = '', $data = [], $language = QuarkLanguage::ANY) {
		return $this->Template($this->LocalizationOf($key, $language), $data);
	}

	/**
	 * @param string $key = ''
	 * @param array $data = []
	 *
	 * @return object
	 */
	public function TemplatedLocalizationDictionaryOf ($key = '', $data = []) {
		$out = new \stdClass();
		$locales = $this->LocalizationDictionaryOf($key);

		foreach ($locales as $i => &$locale)
			$out->$key = $this->Template($locale, $data);

		unset($i, $locale);

		return $out;
	}

	/**
	 * @param string $key = ''
	 * @param array $data = []
	 * @param bool $strict = false
	 *
	 * @return string
	 */
	public function TemplatedCurrentLocalizationOf ($key = '', $data = [], $strict = false) {
		return $this->Template($this->CurrentLocalizationOf($key, $strict), $data);
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
	 * @param IQuarkPrimitive $primitive = null
	 *
	 * @return IQuarkPrimitive
	 */
	public function &Primitive(IQuarkPrimitive $primitive = null);
}

/**
 * Class QuarkObject
 *
 * @package Quark
 */
class QuarkObject {
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
			elseif ($source instanceof QuarkModel) {
				$model = $source->Model();
				$iterator($key, $model, $parent);
			}
			else {
				foreach ($source as $k => $v)
					self::Walk($v, $iterator, $key . ($key == '' ? $k : '[' . $k . ']'), $source);

				unset($k, $v);
			}

			unset($k, $v);
		}
	}

	/**
	 * @param string[] $paths = []
	 * @param string $prefix = ''
	 *
	 * @return QuarkKeyValuePair[]
	 */
	public static function TreeBuilder ($paths = [], $prefix = '') {
		$out = array();
		$dirs = array();
		$files = array();

		foreach ($paths as $i => &$link) {
			$path = explode('/', $link);

			if (sizeof($path) == 1) $files[] = new QuarkKeyValuePair($prefix . '/' . $link, $link);
			else {
				if (!isset($dirs[$path[0]]))
					$dirs[$path[0]] = array();

				$dirs[$path[0]][] = implode('/', array_slice($path, 1));
			}
		}

		unset($i, $link);

		foreach ($dirs as $key => &$link)
			$out[$key] = self::TreeBuilder($link, $prefix . '/' . $key);

		unset($key, $link);

		foreach ($files as $i => &$file)
			$out[] = $file;

		unset($i, $file);

		return $out;
	}

	/**
	 * @return mixed
	 */
	public static function Merge () {
		$args = func_get_args();

		if (sizeof($args) == 0) return null;
		if (sizeof($args) == 1)
			$args = array(new \stdClass(), $args[0]);
		
		$out = null;
		
		foreach ($args as $i => &$arg) {
			if ($arg === null) continue;

			$iterative = self::isIterative($arg);

			if (is_scalar($arg) || is_null($arg) || (is_object($arg) && !($arg instanceof \stdClass))) {
				$out = $arg;
				continue;
			}

			if ($iterative && sizeof($arg) == 0) {
				$out = (array)$arg;
				continue;
			}

			foreach ($arg as $key => &$value) {
				if ($iterative) {
					if (!is_array($out))
						$out = array();

					$def = isset($out[$key]) ? $out[$key] : null;
					$out[] = self::Merge($def, $value);
				}
				else {
					if (!is_object($out))
						$out = new \stdClass();

					$def = isset($out->$key) ? $out->$key : null;

					if (!empty($key))
						$out->$key = self::Merge($def, $value);
				}
			}
		}

		unset($i, $arg, $iterative, $key, $value, $def, $args);
		
		return $out;
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
	 *
	 * @return bool
	 */
	public static function isTraversable ($source) {
		return is_array($source) || is_object($source);
	}

	/**
	 * https://stackoverflow.com/a/9412313/2097055
	 *
	 * @param $object
	 *
	 * @return bool
	 */
	public static function isEmpty ($object = null) {
		if (!$object || empty($object)) return true;

		$object = (array)$object;

		return empty($object);
	}

	/**
	 * @param $source
	 * @param $type
	 * @param bool $implements = false
	 *
	 * @return bool
	 */
	public static function IsArrayOf ($source, $type, $implements = false) {
		if (!self::isIterative($source)) return false;

		$scalar = is_scalar($type);
		$typeof = gettype($type);

		foreach ($source as $i => &$item) {
			if ($implements) {
				if (!self::is($item, $type)) return false;
			}
			else {
				if ($scalar && gettype($item) != $typeof) return false;
				if (!$scalar && !($item instanceof $type)) return false;
			}
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

		foreach ($interface as &$face)
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

		foreach ($classes as $i => &$class)
			if (self::is($class, $interface) && ($filter != null ? $filter($class) : true)) $output[] = $class;

		unset($i, $class);

		return $output;
	}

	/**
	 * http://stackoverflow.com/a/25900210/2097055
	 * 
	 * @param string|object $class = ''
	 * @param string $trait = ''
	 * @param bool $parents = true
	 *
	 * @return bool
	 */
	public static function Uses ($class = '', $trait = '', $parents = true) {
		if (!is_object($class) && !(is_string($class) && class_exists($class))) return false;

		$tree = $parents ? class_parents($class) : array();
		$tree[] = $class;

		foreach ($tree as $i => &$node) {
			$uses = class_uses($node);

			foreach ($uses as $j => &$use)
				if ($use == $trait) return true;

			unset($j, $use);
		}

		unset($i, $node);

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
	 * @param $value
	 *
	 * @return mixed
	 */
	public static function ConstByValue ($value) {
		$defined = get_defined_constants(true);

		if (!isset($defined['user']) || !is_array($defined['user'])) return null;

		foreach ($defined['user'] as $key => &$val)
			if ($val === $value) return $key;

		return null;
	}

	/**
	 * @param string $const
	 *
	 * @return mixed
	 */
	public static function ConstValue ($const) {
		return defined($const) ? constant($const) : $const;
	}

	/**
	 * @param string|object $class
	 * @param $value
	 *
	 * @return mixed
	 */
	public static function ClassConstByValue ($class, $value) {
		$defined = self::ClassConstants($class);

		foreach ($defined as $key => &$const)
			if ($const == $value) return $key;

		return null;
	}

	/**
	 * @param string|object $class
	 * @param string $const
	 *
	 * @return mixed
	 */
	public static function ClassConstValue ($class, $const) {
		return self::ConstValue((is_object($class) ? get_class($class) : $class) . '::' . $const);
	}

	/**
	 * @param string|object $class
	 * @param string $regex = ''
	 *
	 * @return array
	 */
	public static function ClassConstants ($class, $regex = '') {
		$reflection = new \ReflectionClass($class);

		$constants = $reflection->getConstants();
		if ($regex == '') return $constants;

		$out = array();

		foreach ($constants as $key => &$value)
			if (preg_match($regex, $key))
				$out[$key] = $value;

		return $out;
	}

	/**
	 * @param $value
	 *
	 * @return string
	 */
	public static function Stringify ($value) {
		if (is_bool($value))
			return $value ? 'true' : 'false';

		if (is_null($value)) return 'null';
		if (is_array($value)) return 'array';

		return (string)$value;
	}

	/**
	 * @param $var
	 * @param bool $objectToNull = false
	 *
	 * @return bool|int|float|string|array|object|null
	 */
	public static function DefaultValueOfType ($var, $objectToNull = true) {
		if (is_bool($var)) return false;
		if (is_int($var)) return 0;
		if (is_float($var)) return 0.0;
		if (is_string($var)) return '';
		if (is_array($var)) return array();
		if (is_object($var) && !$objectToNull) return new \stdClass();
		
		return null;
	}

	/**
	 * @param string $input = ''
	 * @param array $fields = []
	 *
	 * @return object
	 */
	public static function FromBinary ($input = '', $fields = []) {
		$format = array();

		foreach ($fields as $key => &$value)
			$format[] = $value . $key;

		return (object)unpack(implode('/', $format), $input);
	}

	/**
	 * @param IQuarkBinaryObject $object = null
	 * @param string $input = ''
	 *
	 * @return IQuarkBinaryObject
	 */
	public static function BinaryPopulate (IQuarkBinaryObject $object = null, $input = '') {
		if ($object == null) return null;

		$obj = self::FromBinary($input, $object->BinaryFields());

		foreach ($obj as $key => &$value)
			$object->$key = $value;

		$object->BinaryPopulate($input);

		return $object;
	}

	/**
	 * @param object $obj = null
	 * @param array $fields = []
	 *
	 * @return string
	 */
	public static function ToBinary ($obj = null, $fields = []) {
		if ($obj == null) return '';

		$out = '';

		foreach ($fields as $key => &$value)
			if (isset($obj->$key))
				$out .= pack($value, $obj->$key);

		return $out;
	}

	/**
	 * @param IQuarkBinaryObject $object = null
	 *
	 * @return string
	 */
	public static function BinaryExtract (IQuarkBinaryObject $object = null) {
		if ($object == null) return '';

		$out = self::ToBinary($object, $object->BinaryFields());

		return $out . $object->BinaryExtract($out);
	}

	/**
	 * @param IQuarkBinaryObject $object = null
	 *
	 * @return bool|int
	 */
	public static function BinaryLength (IQuarkBinaryObject $object = null) {
		return $object == null ? 0 : strlen(self::ToBinary($object, $object->BinaryFields()));
	}
}

/**
 * Interface IQuarkBinaryObject
 *
 * @package Quark
 */
interface IQuarkBinaryObject {
	/**
	 * @return mixed
	 */
	public function BinaryFields();

	/**
	 * @return int
	 */
	public function BinaryLength();

	/**
	 * @param $data
	 *
	 * @return mixed
	 */
	public function BinaryPopulate($data);

	/**
	 * @param $data
	 *
	 * @return mixed
	 */
	public function BinaryExtract($data);
}

/**
 * Trait QuarkBinaryObjectBehavior
 *
 * @package Quark
 */
trait QuarkBinaryObjectBehavior {
	/**
	 * @return int|bool
	 */
	public function BinaryLengthCalculated () {
		/**
		 * @var IQuarkBinaryObject|QuarkBinaryObjectBehavior $this
		 */

		return $this instanceof IQuarkBinaryObject
			? QuarkObject::BinaryLength($this)
			: false;
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
	 * @return QuarkView
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
	 * @param IQuarkViewModel $view
	 * @param array $vars
	 *
	 * @return QuarkView|IQuarkVIewModel
	 */
	public function Nested (IQuarkViewModel $view, $vars = []) {
		return $this->__call('Nested', func_get_args());
	}

	/**
	 * @return QuarkModel|QuarkSessionBehavior|IQuarkAuthorizableModel
	 */
	public function User () {
		return $this->__call('User', func_get_args());
	}

	/**
	 * @param bool $localized = true
	 *
	 * @return string
	 */
	public function Theme ($localized = true) {
		return $this->__call('Theme', func_get_args());
	}

	/**
	 * @param bool $localized = true
	 *
	 * @return string
	 */
	public function ThemeURL ($localized = true) {
		return $this->__call('ThemeURL', func_get_args());
	}

	/**
	 * @param string $resource = ''
	 * @param bool $localized = false
	 *
	 * @return string
	 */
	public function ThemeResource ($resource = '', $localized = false) {
		return $this->__call('ThemeResource', func_get_args());
	}

	/**
	 * @param string $resource = ''
	 * @param bool $localized = false
	 * @param bool $full = false
	 *
	 * @return string
	 */
	public function ThemeResourceURL ($resource = '', $localized = false, $full = false) {
		return $this->__call('ThemeResourceURL', func_get_args());
	}

	/**
	 * @return bool
	 */
	public function Localized () {
		return $this->__call('Localized', func_get_args());
	}

	/**
	 * @param IQuarkViewFragment $fragment
	 *
	 * @return string
	 */
	public function Fragment (IQuarkViewFragment $fragment) {
		return $this->__call('Fragment', func_get_args());
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
	 * @param string $uri
	 * @param bool $signed = false
	 *
	 * @return string
	 */
	public function FullLink ($uri, $signed = false) {
		return $this->__call('FullLink', func_get_args());
	}

	/**
	 * @param string $uri
	 * @param string $button
	 * @param string $method = QuarkDTO::METHOD_POST
	 * @param string $formStyle = self::SIGNED_ACTION_FORM_STYLE
	 *
	 * @return string
	 */
	public function SignedAction ($uri, $button, $method = QuarkDTO::METHOD_POST, $formStyle = QuarkView::SIGNED_ACTION_FORM_STYLE) {
		return $this->__call('SignedAction', func_get_args());
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
	 * @param string $path = ''
	 * @param bool $full = true
	 *
	 * @return string
	 */
	public function WebLocation ($path = '', $full = true) {
		return $this->__call('WebLocation', func_get_args());
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
	 * @param string $language = QuarkLanguage::ANY
	 *
	 * @return string
	 */
	public function Language ($language = QuarkLanguage::ANY) {
		return $this->__call('Language', func_get_args());
	}

	/**
	 * @return string
	 */
	public function Languages () {
		return $this->__call('Languages', func_get_args());
	}

	/**
	 * @return string
	 */
	public function LanguageControlAttributes () {
		return $this->__call('LanguageControlAttributes', func_get_args());
	}

	/**
	 * @param string $language = QuarkLanguage::ANY
	 * @param string[] $languages = []
	 *
	 * @return string
	 */
	public function Localization ($language = QuarkLanguage::ANY, $languages = []) {
		return $this->__call('Localization', func_get_args());
	}

	/**
	 * @param QuarkModel $model = null
	 * @param string $field = ''
	 * @param string $template = QuarkView::FIELD_ERROR_TEMPLATE
	 *
	 * @return string
	 */
	public function FieldError (QuarkModel $model = null, $field = '', $template = QuarkView::FIELD_ERROR_TEMPLATE) {
		return $this->__call('FieldError', func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function Compile () {
		return $this->__call('Compile', func_get_args());
	}

	/**
	 * @param bool $minimize = true
	 * @param bool $bundle = false
	 *
	 * @return string
	 */
	public function ViewModelResourceBundle ($minimize = true, $bundle = false) {
		return $this->__call('Resources', func_get_args());
	}

	/**
	 * @param string $location
	 * @param IQuarkViewResourceType $type
	 * @param bool $minimize = true
	 * @param IQuarkViewResource[] $dependencies = []
	 *
	 * @return QuarkGenericViewResource
	 */
	public function Resource ($location, IQuarkViewResourceType $type, $minimize = true, $dependencies = []) {
		return new QuarkGenericViewResource($location, $type, $minimize, $dependencies);
	}

	/**
	 * @param IQuarkViewResource|string $location
	 * @param bool $minimize = true
	 * @param IQuarkViewResource[] $dependencies = []
	 *
	 * @return QuarkGenericViewResource|IQuarkViewResource
	 */
	public function ResourceCSS ($location, $minimize = true, $dependencies = []) {
		return QuarkGenericViewResource::CSS($location, $minimize, $dependencies);
	}

	/**
	 * @param IQuarkViewResource|string $location
	 * @param bool $minimize = true
	 * @param IQuarkViewResource[] $dependencies = []
	 *
	 * @return QuarkGenericViewResource|IQuarkViewResource
	 */
	public function ResourceJS ($location, $minimize = true, $dependencies = []) {
		return QuarkGenericViewResource::JS($location, $minimize, $dependencies);
	}

	/**
	 * @param string $code = ''
	 * @param bool $minimize = true
	 *
	 * @return QuarkInlineCSSViewResource
	 */
	public function InlineCSS ($code = '', $minimize = true) {
		return new QuarkInlineCSSViewResource($code, $minimize);
	}

	/**
	 * @param string $code = ''
	 * @param bool $minimize = true
	 *
	 * @return QuarkInlineJSViewResource
	 */
	public function InlineJS ($code = '', $minimize = true) {
		return new QuarkInlineJSViewResource($code, $minimize);
	}
}

/**
 * Class QuarkView
 *
 * @package Quark
 */
class QuarkView implements IQuarkContainer {
	const FIELD_ERROR_TEMPLATE = '<div class="quark-message warn fa fa-warning"><p class="content">{error}</p></div>';
	const SIGNED_ACTION_FORM_STYLE = 'display: inline-block; margin: 0; padding: 0; border: none;';
	const DEFAULT_THEME = 'Default';
	const GENERIC_LOCALIZATION = '_any';
	const LII_PARAS = 'paras';
	const LII_WORDS = 'words';
	const LII_BYTES = 'bytes';
	const LII_LISTS = 'lists';
	
	/**
	 * @var IQuarkViewModel|IQuarkViewModelWithResources|IQuarkViewModelWithVariableDiscovering $_view = null
	 */
	private $_view = null;

	/**
	 * @var IQuarkViewModel|IQuarkViewModelWithResources|IQuarkViewModelWithVariableDiscovering $_child = null
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
	 * @var string $_language = QuarkLanguage::ANY
	 */
	private $_language = QuarkLanguage::ANY;
	
	/**
	 * @var string $_theme = ''
	 */
	private $_theme = '';

	/**
	 * @var bool $_localized = false
	 */
	private $_localized = false;

	/**
	 * @param IQuarkViewModel|QuarkViewBehavior $view
	 * @param QuarkDTO|object|array $vars = []
	 * @param IQuarkViewResource[] $resources = []
	 *
	 * @throws QuarkArchException
	 */
	public function __construct (IQuarkViewModel $view = null, $vars = [], $resources = []) {
		if ($view == null) return;

		$this->_language = Quark::CurrentLanguage();
		$this->_view = $view;
		
		$this->Vars($vars);

		if (!($this->_view instanceof IQuarkViewModelInline)) {
			$_file = $this->_file = $this->_localized_theme($this->_language == QuarkLanguage::ANY ? self::GENERIC_LOCALIZATION : $this->_language);

			if (Quark::Config()->LocalizationByFamily() && !is_file($this->_file))
				$_file = $this->_file = $this->_localized_theme(Quark::CurrentLanguageFamily());

			if (!is_file($this->_file))
				$_file = $this->_file = $this->_localized_theme(self::GENERIC_LOCALIZATION);

			if (!is_file($this->_file)) {
				$this->_file = $this->_view->View();
				$this->_theme = '';
			}

			if (!is_file($this->_file))
				throw new QuarkArchException('Unknown view file ' . $this->_file . ' (' . $_file . '). If you specified your view as IQuarkViewModelInTheme or its inheritor, check that theme structure is correct.');
		}

		$this->_resources = $resources;

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
	 * @param bool $minimize = true
	 * @param bool $bundle = false
	 *
	 * @return string
	 */
	public function Resources ($minimize = true, $bundle = false) {
		$out = '';
		$type = null;
		$source = new QuarkSource();

		$this->ResourceList();

		foreach ($this->_resources as $i => &$resource) {
			$source->Unload();
			$min = $minimize && $resource instanceof IQuarkMinimizableViewResource && $resource->Minimize();

			if ($resource instanceof IQuarkInlineViewResource) {
				if ($min)
					$source->Minimize();

				// TODO: refactor!!!
				$buffer = $resource->HTML($min);
				$out .= $bundle ? strip_tags($buffer) : $buffer;
			}

			if ($resource instanceof IQuarkSpecifiedViewResource) {
				$type = $resource->Type();

				if ($type instanceof IQuarkViewResourceType) {
					$source->Location($resource->Location());

					if ($resource instanceof IQuarkForeignViewResource || ($resource instanceof IQuarkViewResourceWithLocationControl && $resource->LocationControl(false))) { }

					if ($resource instanceof IQuarkLocalViewResource || ($resource instanceof IQuarkViewResourceWithLocationControl && $resource->LocationControl(true))) {
						$source->Load();
						$source->Location('');
					}

					if ($min) {
						$source->Trim($type->Trim());
						$source->Minimize();
					}

					$type->ViewResourceTypeContentOnly($bundle && $resource instanceof IQuarkViewResourceWithContent);

					$out .= $bundle ? $type->AfterMinimize($source->Content()) : $type->Container($source->Location(), $type->AfterMinimize($source->Content()));
				}
			}
		}

		unset($i, $resource);

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
		$buffer = array();
		
		if (sizeof($this->_resources) != 0) {
			$buffer = $this->_resources;
			$this->_resources = array();
		}

		if ($this->_view instanceof IQuarkViewModelWithVariableProxy) {
			$vars = $this->_view->ViewVariableProxy($this->_vars);

			if (QuarkObject::isTraversable($vars))
				foreach ($vars as $key => $value)
					$this->_resource(new QuarkProxyJSViewResource($key, $value instanceof QuarkModel ? $value->Extract() : $value));
		}
		
		if ($this->_view instanceof IQuarkViewModelWithResources)
			$this->_resources($this->_view->ViewResources());

		$this->_resources($buffer);

		if ($this->_view instanceof IQuarkViewModelWithComponents) {
			$this->_resource(QuarkGenericViewResource::CSS($this->_view->ViewStylesheet()));
			$this->_resource(QuarkGenericViewResource::JS($this->_view->ViewController()));
		}

		return $this->_resources;
	}

	/**
	 * @param IQuarkViewResource[] $resources
	 *
	 * @return QuarkView
	 */
	private function _resources ($resources = []) {
		if (is_array($resources)) {
			foreach ($resources as $i => &$resource)
				$this->_resource($resource);

			unset($i, $resource, $resources);
		}

		return $this;
	}

	/**
	 * @param IQuarkViewResource|IQuarkViewResourceWithDependencies $resource = null
	 *
	 * @return QuarkView
	 *
	 * @throws QuarkArchException
	 */
	private function _resource (IQuarkViewResource $resource = null) {
		if ($resource == null) return $this;

		if ($resource instanceof IQuarkViewResourceWithDependencies)
			$this->_resource_dependencies($resource->Dependencies(), 'ViewResource ' . get_class($resource) . ' specified invalid value for `Dependencies`. Expected array of IQuarkViewResource.');

		if ($resource instanceof IQuarkCombinedViewResource) {
			$this->_resource(QuarkGenericViewResource::CSS($resource->LocationStylesheet()));
			$this->_resource(QuarkGenericViewResource::JS($resource->LocationController()));
		}

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
		foreach ($resources as $i => &$dependency) {
			if ($dependency == null) continue;

			$this->_resource($dependency);
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

		/**
		 * @var IQuarkViewResource $resource
		 */
		foreach ($this->_resources as $i => &$resource)
			if (get_class($resource) == $class) return true;

		return false;
	}

	/**
	 * @param string $language
	 * 
	 * @return string
	 */
	private function _localized_theme ($language) {
		$this->_theme = Quark::Host() . '/' . Quark::Config()->Location(QuarkConfig::VIEWS);

		if ($this->_view instanceof IQuarkViewModelInTheme) {
			$theme = $this->_view->ViewTheme();

			if ($theme === null)
				$theme = self::DEFAULT_THEME;
			
			$this->_theme .= '/_themes/' . $theme;

			if ($this->_view instanceof IQuarkViewModelInLocalizedTheme) {
				$this->_localized = true;
				$this->_theme .= '/' . str_replace('/', '', str_replace('.', '', $language));
			}
		}

		return Quark::NormalizePath($this->_theme . '/' . $this->_view->View() . '.php', false);
	}
	
	/**
	 * @param IQuarkViewResource[] $resources
	 *
	 * @return QuarkView
	 */
	public function AppendResources ($resources = []) {
		$this->_resources = array_merge($this->_resources, $resources);
		return $this;
	}

	/**
	 * @param bool $localized = true
	 *
	 * @return string
	 */
	public function Theme ($localized = true) {
		return $this->_localized && !$localized
			? substr($this->_theme, 0, strripos($this->_theme, '/'))
			: $this->_theme;
	}

	/**
	 * @param bool $localized = true
	 * @param bool $full = false
	 *
	 * @return string
	 */
	public function ThemeURL ($localized = true, $full = false) {
		return Quark::WebLocation($this->Theme($localized), $full);
	}

	/**
	 * @param string $resource = ''
	 * @param bool $localized = false
	 *
	 * @return string
	 */
	public function ThemeResource ($resource = '', $localized = false) {
		return $this->Theme($localized) . '/' . $resource;
	}

	/**
	 * @param string $resource = ''
	 * @param bool $localized = false
	 * @param bool $full = false
	 *
	 * @return string
	 */
	public function ThemeResourceURL ($resource = '', $localized = false, $full = false) {
		return $this->ThemeURL($localized, $full) . '/' . $resource;
	}

	/**
	 * @return bool
	 */
	public function Localized () {
		return $this->_localized;
	}

	/**
	 * @param IQuarkViewFragment $fragment
	 *
	 * @return string
	 */
	public function Fragment (IQuarkViewFragment $fragment) {
		return $fragment->CompileFragment();
	}

	/**
	 * @param string $uri
	 * @param bool $signed = false
	 *
	 * @return string
	 */
	private function _link ($uri, $signed = false) {
		return $uri . ($signed ? QuarkURI::BuildQuery($uri, array(
			QuarkDTO::KEY_SIGNATURE => $this->Signature(false)
		)) : '');
	}

	/**
	 * @param string $uri
	 * @param bool $signed = false
	 *
	 * @return string
	 */
	public function Link ($uri, $signed = false) {
		return Quark::WebLocation($this->_link($uri, $signed), false);
	}

	/**
	 * @param string $uri
	 * @param bool $signed = false
	 *
	 * @return string
	 */
	public function FullLink ($uri, $signed = false) {
		return Quark::WebLocation($this->_link($uri, $signed), true);
	}

	/**
	 * @param string $uri
	 * @param string $button
	 * @param string $method = QuarkDTO::METHOD_POST
	 * @param string $formStyle = self::SIGNED_ACTION_FORM_STYLE
	 *
	 * @return string
	 */
	public function SignedAction ($uri, $button, $method = QuarkDTO::METHOD_POST, $formStyle = self::SIGNED_ACTION_FORM_STYLE) {
		/** @lang text */
		return '<form action="' . $uri . '" method="' . $method . '" style="' . $formStyle . '">' . $button . $this->Signature() . '</form>';
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
	 * @param string $path = ''
	 * @param bool $full = true
	 *
	 * @return string
	 */
	public function WebLocation ($path = '', $full = true) {
		return Quark::WebLocation($path, $full);
	}

	/**
	 * https://github.com/traviskaufman/node-lipsum
	 *
	 * @param int $amount = 5
	 * @param string $item = self::LII_PARAS
	 * @param bool $start = true
	 *
	 * @return bool|QuarkDTO
	 */
	public function LoremIpsumRaw ($amount = 5, $item = self::LII_PARAS, $start = true) {
		return QuarkHTTPClient::To(
			'http://lipsum.com/feed/json?amount=' . $amount . '&start=' . QuarkObject::Stringify($start) . '&what=' . $item,
			QuarkDTO::ForGET(new QuarkJSONIOProcessor()),
			new QuarkDTO(new QuarkJSONIOProcessor())
		);
	}

	/**
	 * @param int $amount = 5
	 * @param string $item = self::LII_PARAS
	 * @param bool $start = true
	 *
	 * @return string|null
	 */
	public function LoremIpsum ($amount = 5, $item = self::LII_PARAS, $start = true) {
		$json = $this->LoremIpsumRaw($amount, $item, $start);

		/** @noinspection PhpUndefinedFieldInspection */
		if (!isset($json->feed->lipsum)) return null;
		/** @noinspection PhpUndefinedFieldInspection */
		$lipsum = $json->feed->lipsum;

		if ($item == self::LII_WORDS || $item == self::LII_BYTES) return $lipsum;

		$replacement = '<p class="quark-paragraph">$1</p>';

		if ($item == self::LII_LISTS)
			$replacement = '<li>$1</li>';

		$out = trim(preg_replace('#(.*)\n#Uis', $replacement, $lipsum . "\n"));

		if ($item == self::LII_LISTS)
			$out = '<ul class="quark-list">' . $out . '</ul>';

		return $out;
	}

	/**
	 * @param string $name = 'timezone'
	 * @param string $selected = null
	 * @param string $format = QuarkCultureISO::TIME
	 * @param string $class = 'quark-input'
	 * @param string $id = ''
	 *
	 * @return string
	 */
	public function TimezoneSelector ($name = 'timezone', $selected = null, $format = QuarkCultureISO::TIME, $class = 'quark-input', $id = '') {
		$zones = QuarkDate::TimezoneList();
		$out = '<select' . ($class ? ' class="' . $class . '"' : '') . ' ' . ($id ? ' id="' . $id . '"' : '') . ' name=' . $name . '>';

		foreach ($zones as $zone => &$offset)
			$out .= '<option value="' . $zone . '"' . ($selected === $zone ? ' selected="selected"' : '') . '>(UTC ' . $offset->Format($format, true) . ') ' . $zone . '</option>';

		return $out . '</select>';
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
	 * @return QuarkModel|QuarkSessionBehavior|IQuarkAuthorizableModel
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
			$this->_layout = new QuarkView($view, $vars);

			if ($this->_view instanceof IQuarkViewModelWithLayoutResources)
				$this->_layout->_resources($this->_view->ViewLayoutResources());

			if ($this->_view instanceof IQuarkViewModelWithLayoutComponents) {
				$this->_layout->_resource(QuarkGenericViewResource::CSS($this->_view->ViewLayoutStylesheet()));
				$this->_layout->_resource(QuarkGenericViewResource::JS($this->_view->ViewLayoutController()));
			}

			$this->_layout->View($this->Compile());
			$this->_layout->Child($this->_view);
			$this->_layout->_resources($this->ResourceList());
			$this->_layout->_resources($resources);

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
	 * @param array $vars
	 *
	 * @return QuarkView|IQuarkVIewModel
	 */
	public function Nested (IQuarkViewModel $view, $vars = []) {
		$out = new self($view, $vars);
		$this->_resources($out->ResourceList());

		return $out;
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

		return $inline->Layout($layout, $vars);
	}

	/**
	 * @param callable $rendeer = null
	 * @param $vars = []
	 * @param IQuarkViewResource[] $resources = []
	 *
	 * @return QuarkModel|QuarkGenericViewModelInline
	 */
	public static function Inline (callable $rendeer = null, $vars = [], $resources = []) {
		return new self(new QuarkGenericViewModelInline($renderer, $resources), $vars);
	}

	/**
	 * @param IQuarkSpecifiedViewResource $resource = null
	 * @param $vars = []
	 * @param IQuarkSpecifiedViewResource[] $dependencies = []
	 * @param bool $minimize = true
	 *
	 * @return QuarkModel|QuarkGenericViewModelInline
	 */
	public static function InlineResource (IQuarkSpecifiedViewResource $resource = null, $vars = [], $dependencies = [], $minimize = true) {
		return new self(QuarkGenericViewModelInline::ForResource($resource, $dependencies, $minimize), $vars);
	}

	/**
	 * @param string $source = ''
	 * @param array|object $data = []
	 * @param string $prefix = ''
	 *
	 * @return string
	 */
	private static function _tpl ($source = '', $data = [], $prefix = '') {
		if (!QuarkObject::isTraversable($data)) return $source;

		if ($data instanceof QuarkModel)
			$data = $data->Model();

		foreach ($data as $key => $value) {
			$append = $prefix . $key;
			$source = QuarkObject::isTraversable($value) && !is_callable($value)
				? self::_tpl($source, $value, $append . '.')
				: (!is_callable($value)
					? preg_replace('#\{' . $append . '\}#Uisu', $value, $source)
					: preg_replace_callback('#\{' . $append . '\}#Uisu', function ($matches) use (&$value, &$key, &$append) { return $value($matches, $key, $append); }, $source)
				);
		}

		return $source;
	}

	/**
	 * @param string $source = ''
	 * @param array|object $data = []
	 *
	 * @return string
	 */
	public static function TemplateString ($source = '', $data = []) {
		return self::_tpl($source, $data);
	}

	/**
	 * @param QuarkDTO|object|array $params
	 *
	 * @return array
	 */
	public function Vars ($params = []) {
		if (func_num_args() == 1) {
			$vars = $params instanceof QuarkDTO
				? $params->Data()
				: QuarkObject::Merge((object)$params);

			$this->_vars = $vars == null ? (object)array() : $vars;

			if (QuarkObject::IsTraversable($vars))
				foreach ($vars as $key => $value)
					$this->_view->$key = $value;
		}

		return $this->_vars;
	}

	/**
	 * @return mixed
	 */
	public function ExtractVars () {
		if (!QuarkObject::isTraversable($this->_vars)) return $this->_vars;

		$out = new \stdClass();
		$discovering = $this->_view instanceof IQuarkViewModelWithVariableDiscovering;

		foreach ($this->_vars as $key => $value) {
			if ($discovering) $out->$key = $this->_view->ViewVariableDiscovering($key, $value);
			else {
				if ($value instanceof QuarkModel || $value instanceof QuarkCollection) $out->$key = $value->Extract();
				else $out->$key = $value;
			}
		}

		return $out;
	}

	/**
	 * @param string $language = QuarkLanguage::ANY
	 *
	 * @return string
	 */
	public function Language ($language = QuarkLanguage::ANY) {
		if (func_num_args() != 0)
			$this->_language = $language;

		return $this->_language;
	}

	/**
	 * @return string[]
	 */
	public function Languages () {
		return Quark::Config()->Languages();
	}

	/**
	 * @return string
	 */
	public function LanguageControlAttributes () {
		return ' quark-language="' . $this->Language() . '" quark-languages="' . implode(', ', $this->Languages()) . '"';
	}

	/**
	 * @param string $language = QuarkLanguage::ANY
	 * @param string[] $languages = []
	 * 
	 * @return string
	 */
	public function Localization ($language = QuarkLanguage::ANY, $languages = []) {
		$args = func_num_args();

		if ($args == 0) {
			$language = Quark::CurrentLanguage();
			$languages = Quark::Config()->Languages();
		}

		if ($args == 1)
			$languages = Quark::Config()->Languages();

		return ' quark-language="' . $language . '" quark-languages="' . implode(QuarkConfig::LANGUAGE_DELIMITER, $languages) . '" ';
	}

	/**
	 * @param QuarkKeyValuePair[] $menu
	 * @param callable($href, $text) $button = null
	 * @param callable($text) $node = null
	 *
	 * @return string
	 */
	public static function TreeMenu ($menu = [], callable $button = null, callable $node = null) {
		$out = '';
		
		if ($button == null)
			$button = function ($href, $text) { return '<a href="' . $href . '">' . $text . '</a>'; };

		if ($node == null)
			$node = function ($text) { return '<div class="group-name">' . $text . '</div>'; };

		foreach ($menu as $key => &$element) {
			if (!is_array($element)) $out .= $button($element->Key(), $element->Value());
			else {
				$out .= '<div class="quark-button-group">' . $node($key)
					. self::TreeMenu($element, $button, $node)
					. '</div>';
			}

			unset($key, $element);
		}

		return $out;
	}

	/**
	 * @param QuarkModel $model = null
	 * @param string $field = ''
	 * @param string $template = self::FIELD_ERROR_TEMPLATE
	 *
	 * @return string
	 */
	public function FieldError (QuarkModel $model = null, $field = '', $template = self::FIELD_ERROR_TEMPLATE) {
		if ($model == null) return '';

		$errors = $model->RawValidationErrors();
		$out = '';

		foreach ($errors as $i => &$error)
			if ($error->Key() == $field)
				$out .= str_replace('{error}', $error->Value()->Of(Quark::CurrentLanguage()), $template);

		unset($i, $error);

		return $out;
	}

	/**
	 * @return string
	 */
	public function Compile () {
		if ($this->_view instanceof IQuarkViewModelWithVariableProcessing)
			$this->_view->ViewVariableProcessing($this->_vars);

		if ($this->_view instanceof IQuarkViewModelInline)
			return self::TemplateString($this->_view->ViewModelInline(), $this->_vars);

		foreach ($this->_vars as $___name___ => &$___value___)
			$$___name___ = $___value___;

		ob_start();
		/** @noinspection PhpIncludeInspection */
		include $this->_file;
		return ob_get_clean();
	}

	/**
	 * @param IQuarkViewModel $view = null
	 *
	 * @return IQuarkViewModel
	 */
	public function ViewModel (IQuarkViewModel $view = null) {
		if (func_num_args() != 0)
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
	 * @param IQuarkPrimitive $primitive = null
	 *
	 * @return IQuarkPrimitive
	 */
	public function &Primitive (IQuarkPrimitive $primitive = null) {
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
 * Interface IQuarkViewModelInline
 *
 * @package Quark
 */
interface IQuarkViewModelInline extends IQuarkViewModel {
	/**
	 * @return string
	 */
	public function ViewModelInline();
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
 * Interface IQuarkViewModelWithResources
 *
 * @package Quark
 */
interface IQuarkViewModelWithResources extends IQuarkViewModel {
	/**
	 * @return IQuarkViewResource[]
	 */
	public function ViewResources();
}

/**
 * Interface IQuarkViewModelWithLayoutComponents
 *
 * @package Quark
 */
interface IQuarkViewModelWithLayoutComponents extends IQuarkViewModel {
	/**
	 * @return IQuarkViewResource|string
	 */
	public function ViewLayoutStylesheet();

	/**
	 * @return IQuarkViewResource|string
	 */
	public function ViewLayoutController();
}

/**
 * Interface IQuarkViewModelWithLayoutResources
 *
 * @package Quark
 */
interface IQuarkViewModelWithLayoutResources extends IQuarkViewModel {
	/**
	 * @return IQuarkViewResource[]
	 */
	public function ViewLayoutResources();
}

/**
 * Interface IQuarkViewModelWithCustomizableLayout
 *
 * @package Quark
 */
interface IQuarkViewModelWithCustomizableLayout extends IQuarkViewModelWithLayoutComponents, IQuarkViewModelWithLayoutResources { }

/**
 * Interface IQuarkViewModelInTheme
 *
 * @package Quark
 */
interface IQuarkViewModelInTheme {
	/**
	 * @return string
	 */
	public function ViewTheme();
}

/**
 * Interface IQuarkViewModelInLocalizedTheme
 *
 * @package Quark
 */
interface IQuarkViewModelInLocalizedTheme extends IQuarkViewModelInTheme { }

/**
 * Interface IQuarkViewModelWithVariableProcessing
 *
 * @package Quark
 */
interface IQuarkViewModelWithVariableProcessing extends IQuarkViewModel {
	/**
	 * @param $vars
	 *
	 * @return mixed
	 */
	public function ViewVariableProcessing($vars);
}

/**
 * Interface IQuarkViewModelWithVariableDiscovering
 *
 * @package Quark
 */
interface IQuarkViewModelWithVariableDiscovering extends IQuarkViewModel {
	/**
	 * @param string $key
	 * @param $var
	 *
	 * @return mixed
	 */
	public function ViewVariableDiscovering($key, $var);
}

/**
 * Interface IQuarkViewModelWithVariableProxy
 *
 * @package Quark
 */
interface IQuarkViewModelWithVariableProxy extends IQuarkViewModel {
	/**
	 * @param $vars
	 *
	 * @return mixed
	 */
	public function ViewVariableProxy($vars);
}

/**
 * Interface IQuarkViewResource
 *
 * @package Quark
 */
interface IQuarkViewResource { }

/**
 * Interface IQuarkViewSpecifiedResource
 *
 * @package Quark
 */
interface IQuarkSpecifiedViewResource extends IQuarkViewResource {
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
interface IQuarkViewResourceWithDependencies extends IQuarkViewResource {
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
interface IQuarkViewResourceWithBackwardDependencies extends IQuarkViewResource {
	/**
	 * @return IQuarkViewResource[]
	 */
	public function BackwardDependencies();
}

/**
 * Interface IQuarkMinimizableViewResource
 *
 * @package Quark
 */
interface IQuarkMinimizableViewResource {
	/**
	 * @return bool
	 */
	public function Minimize();
}

/**
 * Interface IQuarkLocalViewResource
 *
 * @package Quark
 */
interface IQuarkLocalViewResource extends IQuarkViewResource, IQuarkMinimizableViewResource { }

/**
 * Interface IQuarkForeignViewResource
 *
 * @package Quark
 */
interface IQuarkForeignViewResource extends IQuarkViewResource {
	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO();
}

/**
 * Interface IQuarkViewResourceWithLocationControl
 *
 * @package Quark
 */
interface IQuarkViewResourceWithLocationControl extends IQuarkSpecifiedViewResource, IQuarkMinimizableViewResource {
	/**
	 * @param bool $local
	 *
	 * @return bool
	 */
	public function LocationControl($local);
}

/**
 * Interface IQuarkInlineViewResource
 *
 * @package Quark
 */
interface IQuarkInlineViewResource extends IQuarkViewResource, IQuarkMinimizableViewResource {
	/**
	 * @param bool $minimize
	 *
	 * @return string
	 */
	public function HTML($minimize);
}

/**
 * Interface IQuarkViewResourceWithContent
 *
 * @package Quark
 */
interface IQuarkViewResourceWithContent extends IQuarkViewResource { }

/**
 * Interface IQuarkMultipleViewResource
 *
 * @package Quark
 */
interface IQuarkMultipleViewResource extends IQuarkViewResource { }

/**
 * Interface IQuarkCombinedViewResource
 *
 * @package Quark
 */
interface IQuarkCombinedViewResource extends IQuarkViewResource {
	/**
	 * @return string
	 */
	public function LocationStylesheet();

	/**
	 * @return string
	 */
	public function LocationController();
}

/**
 * Trait QuarkMinimizableViewResourceBehavior
 *
 * @package Quark
 */
trait QuarkMinimizableViewResourceBehavior {
	/**
	 * @var bool $_minimize = true
	 */
	private $_minimize = true;

	/**
	 * @param bool $minimize = true
	 *
	 * @return bool
	 */
	public function Minimize ($minimize = true) {
		if (func_num_args() != 0)
			$this->_minimize = $minimize;

		return $this->_minimize;
	}

	/**
	 * @param string $source = ''
	 * @param string[] $trim = []
	 *
	 * @return string
	 */
	public function MinimizeString ($source = '', $trim = []) {
		return QuarkSource::MinimizeString($source, func_num_args() == 2 ? $trim : explode(' ', QuarkSource::TRIM));
	}
}

/**
 * Class QuarkGenericViewResource
 *
 * @package Quark
 */
class QuarkGenericViewResource implements IQuarkSpecifiedViewResource, IQuarkViewResourceWithLocationControl, IQuarkMultipleViewResource, IQuarkViewResourceWithDependencies {
	use QuarkMinimizableViewResourceBehavior;

	/**
	 * @var IQuarkViewResourceType $_type = null
	 */
	private $_type = null;

	/**
	 * @var string $_location = ''
	 */
	private $_location = '';

	/**
	 * @var IQuarkViewResource[] $_dependencies = []
	 */
	private $_dependencies = array();

	/**
	 * @var bool $_local = true
	 */
	private $_local = true;

	/**
	 * @param string $location
	 * @param IQuarkViewResourceType $type
	 * @param bool $minimize = true
	 * @param IQuarkViewResource[] $dependencies = []
	 * @param bool $local = true
	 */
	public function __construct ($location, IQuarkViewResourceType $type, $minimize = true, $dependencies = [], $local = true) {
		$this->_location = $location;
		$this->_type = $type;
		$this->_minimize = $minimize;
		$this->_dependencies = $dependencies;
		$this->_local = $local;
	}

	/**
	 * @param bool $local = true
	 *
	 * @return bool
	 */
	public function Local ($local = true) {
		if (func_num_args() != 0)
			$this->_local = $local;

		return $this->_local;
	}

	/**
	 * @return IQuarkViewResourceType|QuarkJSViewResourceType|QuarkCSSViewResourceType
	 */
	public function &Type () {
		return $this->_type;
	}

	/**
	 * @return string
	 */
	public function Location () {
		return $this->_location;
	}

	/**
	 * @param bool $local
	 *
	 * @return bool
	 */
	public function LocationControl ($local) {
		return $this->_local;
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return $this->_dependencies;
	}

	/**
	 * @param IQuarkViewResource|string $location
	 * @param bool $minimize = true
	 * @param IQuarkViewResource[] $dependencies = []
	 * @param bool $local = true
	 *
	 * @return QuarkGenericViewResource|IQuarkViewResource
	 */
	public static function CSS ($location, $minimize = true, $dependencies = [], $local = true) {
		return $location instanceof IQuarkViewResource
			? $location
			: ($location === null
				? null
				: new self($location, new QuarkCSSViewResourceType(), $minimize, $dependencies, $local)
			);
	}

	/**
	 * @param IQuarkViewResource|string $location
	 * @param bool $minimize = true
	 * @param IQuarkViewResource[] $dependencies = []
	 * @param bool $local = true
	 *
	 * @return QuarkGenericViewResource|IQuarkViewResource
	 */
	public static function JS ($location, $minimize = true, $dependencies = [], $local = true) {
		return $location instanceof IQuarkViewResource
			? $location
			: ($location === null
				? null
				: new self($location, new QuarkJSViewResourceType(), $minimize, $dependencies, $local)
			);
	}

	/**
	 * @param IQuarkViewResource|string $location = ''
	 * @param IQuarkViewResource[] $dependencies = []
	 *
	 * @return QuarkGenericViewResource|IQuarkViewResource
	 */
	public static function ForeignCSS ($location = '', $dependencies = []) {
		return self::CSS($location, false, $dependencies, false);
	}

	/**
	 * @param IQuarkViewResource|string $location = ''
	 * @param IQuarkViewResource[] $dependencies = []
	 *
	 * @return QuarkGenericViewResource|IQuarkViewResource
	 */
	public static function ForeignJS ($location = '', $dependencies = []) {
		return self::JS($location, false, $dependencies, false);
	}
}

/**
 * Class QuarkLocalCoreCSSViewResource
 *
 * @package Quark
 */
class QuarkLocalCoreCSSViewResource implements IQuarkSpecifiedViewResource, IQuarkLocalViewResource {
	use QuarkMinimizableViewResourceBehavior;

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
}

/**
 * Class QuarkLocalCoreJSViewResource
 *
 * @package Quark
 */
class QuarkLocalCoreJSViewResource implements IQuarkSpecifiedViewResource, IQuarkLocalViewResource, IQuarkViewResourceWithContent {
	use QuarkMinimizableViewResourceBehavior;

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
}

/**
 * Trait QuarkInlineViewResourceBehavior
 *
 * @package Quark
 */
trait QuarkInlineViewResourceBehavior {
	use QuarkMinimizableViewResourceBehavior;

	/**
	 * @var string $_code = ''
	 */
	private $_code = '';

	/**
	 * @param string $code = ''
	 * @param bool $minimize = true
	 */
	public function __construct ($code = '', $minimize = true) {
		$this->_code = $code;
		$this->_minimize = $minimize;
	}

	/**
	 * @return void
	 */
	public function Location () { }

	/**
	 * @return void
	 */
	public function Type () { }
}

/**
 * Class QuarkInlineCSSViewResource
 *
 * @package Quark
 */
class QuarkInlineCSSViewResource implements IQuarkViewResource, IQuarkLocalViewResource, IQuarkInlineViewResource, IQuarkMultipleViewResource {
	use QuarkInlineViewResourceBehavior;

	/**
	 * @param bool $minimize
	 *
	 * @return string
	 */
	public function HTML ($minimize) {
		return '<style type="text/css">' . $this->_code . '</style>';
	}
}

/**
 * Class QuarkInlineJSViewResource
 *
 * @package Quark
 */
class QuarkInlineJSViewResource implements IQuarkViewResource, IQuarkLocalViewResource, IQuarkInlineViewResource, IQuarkMultipleViewResource, IQuarkViewResourceWithContent {
	use QuarkInlineViewResourceBehavior;

	/**
	 * @param bool $minimize
	 *
	 * @return string
	 */
	public function HTML ($minimize) {
		return /** @lang text */'<script type="text/javascript">' . $this->_code . '</script>';
	}
}

/**
 * Class QuarkProxyJSViewResource
 *
 * @package Quark
 */
class QuarkProxyJSViewResource implements IQuarkViewResource, IQuarkLocalViewResource, IQuarkInlineViewResource, IQuarkMultipleViewResource, IQuarkViewResourceWithContent {
	const PROXY_SESSION_VAR = 'session_user';

	use QuarkInlineViewResourceBehavior;

	/**
	 * @param $var
	 * @param $value
	 */
	public function __construct ($var, $value) {
		$this->_code = 'var ' . $var . '=' . \json_encode($value, JSON_UNESCAPED_UNICODE) . ';';
	}

	/**
	 * @param bool $minimize
	 *
	 * @return string
	 */
	public function HTML ($minimize) {
		return /** @lang text */'<script type="text/javascript">' . $this->_code . '</script>';
	}

	/**
	 * @param QuarkModel|IQuarkAuthorizableModel $user = null
	 * @param array $fields = null
	 * @param string $var = self::PROXY_SESSION_VAR
	 *
	 * @return QuarkProxyJSViewResource
	 */
	public static function ForSession (QuarkModel $user = null, $fields = null, $var = self::PROXY_SESSION_VAR) {
		return new self($var, $user == null ? 'null' : $user->Extract($fields));
	}
}

/**
 * Trait QuarkLexingViewResourceBehavior
 *
 * @package Quark
 */
trait QuarkLexingViewResourceBehavior {
	/**
	 * @param string $content = ''
	 * @param bool $full = false
	 *
	 * @return string
	 */
	private static function _htmlTo ($content = '', $full = false) {
		return $full
			? preg_replace(/** @lang text */'#\<\!DOCTYPE html\>\<html\>\<head\>\<title\>\<\/title\>\<style type\=\"text\/css\"\>(.*)\<\/style\>\<\/head\>\<body\>(.*)\<\/body\>\<\/html\>#Uis', '$2', $content)
			: $content;
	}

	/**
	 * @param string $content = ''
	 * @param bool $full = false
	 * @param string $css = ''
	 *
	 * @return string
	 */
	private static function _htmlFrom ($content = '', $full = false, $css = '') {
		return $full
			? '<!DOCTYPE html><html><head><title></title><style type="text/css">' . $css . '</style></head><body>' . $content . '</body></html>'
			: $content;
	}

	/**
	 * @param string $content = ''
	 *
	 * @return string
	 */
	public static function Styles ($content = '') {
		return preg_replace(/** @lang text */'#\<\!DOCTYPE html\>\<html\>\<head\>\<title\>\<\/title\>\<style type\=\"text\/css\"\>(.*)\<\/style\>\<\/head\>\<body\>(.*)\<\/body\>\<\/html\>#Uis', '$1', $content);
	}
}

/**
 * Trait QuarkCombinedViewResourceBehavior
 *
 * @package Quark
 */
trait QuarkCombinedViewResourceBehavior {
	/**
	 * @param bool $minimize = true
	 *
	 * @return QuarkGenericViewResource
	 */
	public function ViewResourceStylesheet ($minimize = true) {
		return $this instanceof IQuarkCombinedViewResource
			? QuarkGenericViewResource::CSS($this->LocationStylesheet(), $minimize)
			: null;
	}

	/**
	 * @param bool $minimize = true
	 *
	 * @return QuarkGenericViewResource
	 */
	public function ViewResourceController ($minimize = true) {
		return $this instanceof IQuarkCombinedViewResource
			? QuarkGenericViewResource::JS($this->LocationController(), $minimize)
			: null;
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

	/**
	 * @return string[]
	 */
	public function Trim();

	/**
	 * @param string $content
	 *
	 * @return string
	 */
	public function AfterMinimize($content);

	/**
	 * @return string
	 */
	public function ViewResourceTypeMIME();

	/**
	 * @param bool $flag
	 *
	 * @return mixed
	 */
	public function ViewResourceTypeContentOnly($flag);
}

/**
 * Class QuarkCSSViewResourceType
 *
 * @package Quark
 */
class QuarkCSSViewResourceType implements IQuarkViewResourceType {
	const MEDIA = 'all|braille|handheld|print|screen|speech|projection|tty|tv';

	const MIME = 'text/css';

	/**
	 * @var string[] $_media = []
	 */
	private $_media = array();

	/**
	 * @var bool $_contentOnly = false
	 */
	private $_contentOnly = false;

	/**
	 * @param string[] $media = null
	 */
	public function __construct ($media = null) {
		$this->Media($media);
	}

	/**
	 * @param string[] $media = null
	 *
	 * @return string[]
	 */
	public function Media ($media = null) {
		if (func_num_args() != 0)
			$this->_media = $media;

		return $this->_media;
	}

	/**
	 * @param $location
	 * @param $content
	 *
	 * @return string
	 */
	public function Container ($location, $content) {
		$media = $this->_media === null || !is_array($this->_media) ? '' : ' ' . implode('|', $this->_media);

		return strlen($location) != 0
			? '<link rel="stylesheet" type="text/css" href="' . $location . '"' . $media . ' />'
			: ($this->_contentOnly ? $content : '<style type="text/css"' . $media . '>' . $content . '</style>');
	}

	/**
	 * @return string[]
	 */
	public function Trim () {
		return QuarkSource::TrimChars(array('.', '-', ']', '['));
	}

	/**
	 * @param string $content
	 *
	 * @return string
	 */
	public function AfterMinimize ($content) {
		$content = str_replace('and(', ' and (', $content);
		$content = str_replace('or(', ' or (', $content);

		return $content;
	}

	/**
	 * @return string
	 */
	public function ViewResourceTypeMIME () {
		return self::MIME;
	}

	/**
	 * @param bool $flag
	 *
	 * @return mixed
	 */
	public function ViewResourceTypeContentOnly ($flag) {
		$this->_contentOnly = $flag;
	}
}

/**
 * Class QuarkJSViewResourceType
 *
 * @package Quark
 */
class QuarkJSViewResourceType implements IQuarkViewResourceType {
	const MIME = 'application/javascript';

	/**
	 * @var bool $_defer = false
	 */
	private $_defer = false;

	/**
	 * @var bool $_async = false
	 */
	private $_async = false;

	/**
	 * @var bool $_contentOnly = false
	 */
	private $_contentOnly = false;

	/**
	 * @param bool $defer = false
	 * @param bool $async = false
	 */
	public function __construct ($defer = false, $async = false) {
		$this->Defer($defer);
		$this->Async($async);
	}

	/**
	 * @param bool $defer = false
	 *
	 * @return bool
	 */
	public function Defer ($defer = false) {
		if (func_num_args() != 0)
			$this->_defer = $defer;

		return $this->_defer;
	}

	/**
	 * @param bool $async = false
	 *
	 * @return bool
	 */
	public function Async ($async = false) {
		if (func_num_args() != 0)
			$this->_async = $async;

		return $this->_async;
	}

	/**
	 * @param $location
	 * @param $content
	 *
	 * @return string
	 */
	public function Container ($location, $content) {
		return $this->_contentOnly ? $content : (
			/** @lang text */'<script type="text/javascript"'
			. (strlen($location) != 0 ? ' src="' . $location . '"' : '')
			. ($this->_defer ? ' defer="defer"' : '')
			. ($this->_async ? ' async="async"' : '')
			. '>' . $content . '</script>');
	}

	/**
	 * @return string[]
	 */
	public function Trim () {
		return QuarkSource::TrimChars(array('.', '-', ']', '['));
	}

	/**
	 * @param string $content
	 *
	 * @return string
	 */
	public function AfterMinimize ($content) {
		return $content;
	}

	/**
	 * @return string
	 */
	public function ViewResourceTypeMIME () {
		return self::MIME;
	}

	/**
	 * @param bool $flag
	 *
	 * @return mixed
	 */
	public function ViewResourceTypeContentOnly ($flag) {
		$this->_contentOnly = $flag;
	}
}

/**
 * Interface IQuarkViewFragment
 *
 * @package Quark
 */
interface IQuarkViewFragment {
	/**
	 * @return string
	 */
	public function CompileFragment();
}

/**
 * Class QuarkGenericViewModelInline
 *
 * @package Quark
 */
class QuarkGenericViewModelInline implements IQuarkViewModelInline, IQuarkViewModelWithResources {
	use QuarkViewBehavior;
	
	/**
	 * @var callable $_renderer = null
	 */
	private $_renderer = null;

	/**
	 * @var IQuarkViewResource[] $_resources = []
	 */
	private $_resources = array();

	/**
	 * @param callable $renderer = null
	 * @param IQuarkViewResource[] $resources = []
	 */
	public function __construct (callable $renderer = null, $resources = []) {
		$this->Renderer($renderer);
		$this->Resources($resources);
	}

	/**
	 * @param callable $renderer = null
	 *
	 * @return callable
	 */
	public function &Renderer (callable $renderer = null) {
		if (func_num_args() != 0)
			$this->_renderer = $renderer;
		
		return $this->_renderer;
	}

	/**
	 * @param IQuarkViewResource[] $resources = []
	 *
	 * @return IQuarkViewResource[]
	 */
	public function &Resources ($resources = []) {
		if (func_num_args() != 0)
			$this->_resources = $resources;

		return $this->_resources;
	}

	/**
	 * @return string
	 */
	public function View () { }

	/**
	 * @return string
	 */
	public function ViewModelInline () {
		$renderer = $this->_renderer;

		return is_callable($renderer) ? $renderer($this) : ($this->_resource ? $this->ViewModelResourceBundle(true, true) : null);
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function ViewResources () {
		return $this->_resources;
	}

	/**
	 * @param IQuarkSpecifiedViewResource $resource = null
	 * @param IQuarkSpecifiedViewResource[] $dependencies = []
	 * @param bool $minimize = true
	 *
	 * @return QuarkGenericViewModelInline
	 */
	public static function ForResource (IQuarkSpecifiedViewResource $resource = null, $dependencies = [], $minimize = true) {
		return $resource == null ? null : new self(
			function (QuarkGenericViewModelInline $view) use (&$minimize) {
				return $view->ViewModelResourceBundle($minimize, true);
			},
			array_merge($dependencies, array($resource))
		);
	}
}

/**
 * Trait QuarkCollectionBehavior
 *
 * @package Quark
 */
trait QuarkCollectionBehavior {
	/**
	 * @var array $_collection = []
	 */
	private $_collection = array();

	/**
	 * @var bool $_secure = true
	 */
	private $_secure = true;
	
	/**
	 * @return array
	 */
	public function &Collection () {
		return $this->_collection;
	}

	/**
	 * @param bool $secure = true
	 *
	 * @return bool
	 */
	public function Secure ($secure = true) {
		if (func_num_args() != 0)
			$this->_secure = $secure;

		return $this->_secure;
	}

	/**
	 * @param callable $iterator = null
	 *
	 * @return $this
	 */
	public function Each (callable $iterator = null) {
		if ($iterator != null) {
			foreach ($this->_collection as $i => &$item)
				$this->_collection[$i] = $iterator($item);

			unset($i, $item, $iterator);
		}

		return $this;
	}
	
	/**
	 * @param $document = null
	 * @param array $query = []
	 *
	 * @return bool
	 */
	public function Match ($document = null, $query = []) {
		if (is_scalar($query) || is_null($query)) return $document == $query;
		if (is_scalar($document) || is_null($document))
			return $this->_matchTarget($document, $query);
		
		$out = true;
		$outChanged = false;
		$iterable = false;
		
		foreach ($query as $key => &$rule) {
			$len = QuarkObject::isTraversable($key)
				? sizeof($key)
				: strlen((string)$key);
			
			if ($len == 0) continue;

			if (preg_match('#\.#', $key)) {
				$nodes = explode('.', $key);
				$parent = $nodes[0];

				if (!isset($document->$parent)) return false;
				
				$newKey = implode('.', array_slice($nodes, 1));
				$iterable = QuarkObject::Uses($document->$parent, 'Quark\\QuarkCollectionBehaviorWithArrayAccess');

				if (is_object($document->$parent) && !$iterable)
					return $this->Match($document->$parent, array($newKey => $rule));

				if (is_array($document->$parent) || $iterable)
					foreach ($document->$parent as $item)
						if ($this->Match($item, array($newKey => $rule))) return true;
				
				continue;
			}
			
			if ($key[0] == '$') {
				$aggregate = str_replace('$', '_aggregate_', $key);
				
				if (method_exists($this, $aggregate))
					$this->_matchOut($out, $outChanged, $this->$aggregate($document, $rule));
				
				continue;
			}

			/**
			 * @note This matcher behaves different from MongoDB
			 *       In case of not existing $property it will return false, insteadof MongoDB behavior which expects true
			 *
			 * @upd $document->$key now uses null check. Need more testing.
			 */
			// TODO: $this->_matchTarget() signature which will serve non-existing keys if $this->MongoDBCompatible() is true
			if (isset($document->$key) || $document->$key === null)
				$this->_matchOut($out, $outChanged, $this->_matchTarget($document->$key, $rule));
		}
		
		return $outChanged ? $out : false;
	}
	
	/**
	 * @param bool $out
	 * @param bool $outChanged
	 * @param bool $state
	 */
	private function _matchOut (&$out, &$outChanged, $state) {
		if (!$outChanged) {
			$out = true;
			$outChanged = true;
		}
		
		$out &= $state;
	}
	
	/**
	 * @param string $role
	 * @param $document
	 * @param array $query
	 * @param $append = null
	 *
	 * @return bool
	 */
	private function _matchDocument ($role, $document, $query, $append = null) {
		$out = true;
		
		foreach ($query as $state => &$expected) {
			$hook = str_replace('$', $role, $state);
			$out &= method_exists($this, $hook)
				? $this->$hook($document, $expected, $append)
				: false;
		}

		unset($state, $expected);
		
		return $out;
	}
	
	/**
	 * @param $target
	 * @param $rule
	 *
	 * @return bool
	 */
	private function _matchTarget ($target, $rule) {
		if (is_scalar($rule) || is_null($rule) || is_object($rule))
			return is_object($target) && !is_object($rule) ? false : $target == $rule; // solution for 502 of incompatible types
		
		$isoDate = null;
		$matcher = '_array_';
				
		if (!is_array($target) && !(QuarkObject::Uses($target, 'Quark\\QuarkCollectionBehaviorWithArrayAccess'))) {
			$date = QuarkField::DateTime($target);
			$isoDate = $date ? QuarkDate::From($target) : null;
			$matcher = '_compare_';
		}
				
		return $this->_matchDocument($matcher, $target, $rule, $isoDate);
	}
	
	/** @noinspection PhpUnusedPrivateMethodInspection
	 *
	 * @param $document
	 * @param array $rule
	 *
	 * @return bool
	 */
	private function _aggregate_and ($document, $rule) {
		$state = true;
		
		foreach ($rule as $i => &$item)
			$state &= $this->Match($document, $item);

		unset($i, $item);
					
		return $state;
	}
	
	/** @noinspection PhpUnusedPrivateMethodInspection
	 *
	 * @param $document
	 * @param array $rule
	 *
	 * @return bool
	 */
	private function _aggregate_nand ($document, $rule) {
		$state = true;
		
		foreach ($rule as $i => &$item)
			$state &= $this->Match($document, $item);

		unset($i, $item);
					
		return !$state;
	}
	
	/** @noinspection PhpUnusedPrivateMethodInspection
	 *
	 * @param $document
	 * @param array $rule
	 *
	 * @return bool
	 */
	private function _aggregate_or ($document, $rule) {
		$state = false;
		
		foreach ($rule as $i => &$item)
			$state |= $this->Match($document, $item);

		unset($i, $item);
					
		return $state;
	}
	
	/** @noinspection PhpUnusedPrivateMethodInspection
	 *
	 * @param $document
	 * @param array $rule
	 *
	 * @return bool
	 */
	private function _aggregate_nor ($document, $rule) {
		$state = false;
		
		foreach ($rule as $i => &$item)
			$state |= $this->Match($document, $item);

		unset($i, $item);
					
		return !$state;
	}
	
	/** @noinspection PhpUnusedPrivateMethodInspection
	 *
	 * @param $document
	 * @param array $rule
	 *
	 * @return bool
	 */
	private function _aggregate_not ($document, $rule) {
		$state = true;
		
		foreach ($rule as $i => &$item)
			$state &= !$this->Match($document, $item);

		unset($i, $item);
					
		return $state;
	}

	/** @noinspection PhpUnusedPrivateMethodInspection
	 *
	 * @param $property
	 * @param $expected
	 *
	 * @return bool
	 */
	private function _array_in ($property, $expected) {
		return is_array($expected) ? sizeof(array_intersect($property, $expected)) != 0 : false;
	}

	/** @noinspection PhpUnusedPrivateMethodInspection
	 *
	 * @param $property
	 * @param $expected
	 *
	 * @return bool
	 */
	private function _array_nin ($property, $expected) {
		return is_array($expected) ? sizeof(array_intersect($property, $expected)) == 0 : false;
	}
	
	/** @noinspection PhpUnusedPrivateMethodInspection
	 *
	 * @param $property
	 * @param $expected
	 *
	 * @return bool
	 */
	private function _array_elemMatch ($property, $expected) {
		$out = false;
		
		foreach ($property as $i => &$item)
			$out |= $this->Match($item, $expected);

		unset($i, $item);
		
		return $out;
	}
	
	/** @noinspection PhpUnusedPrivateMethodInspection
	 *
	 * @note This iterator behaves different from MongoDB: it match that ALL array elements match the criteria
	 *
	 * @param $property
	 * @param $expected
	 *
	 * @return bool
	 */
	private function _array_all ($property, $expected) {
		$out = true;
		
		foreach ($expected as $i => &$item) {
			if (is_scalar($item) || is_null($item)) {
				$out &= in_array($item, $property);
				continue;
			}
			
			$query = $item;
			
			if (isset($item['$elemMatch']))
				$query = $item['$elemMatch'];
			
			foreach ($property as $j => &$entry)
				$out &= $this->Match($entry, $query);

			unset($j, $entry);
		}

		unset($i, $item);
		
		return $out;
	}
	
	/** @noinspection PhpUnusedPrivateMethodInspection
	 *
	 * @param $property
	 * @param $expected
	 *
	 * @return bool
	 */
	private function _array_size ($property, $expected) {
		return $this->Match(sizeof($property), $expected);
	}
	
	/** @noinspection PhpUnusedPrivateMethodInspection
	 *
	 * @param $property
	 * @param $expected
	 *
	 * @return bool
	 */
	private function _compare_eq ($property, $expected) {
		return $property == $expected;
	}
	
	/** @noinspection PhpUnusedPrivateMethodInspection
	 *
	 * @param $property
	 * @param $expected
	 *
	 * @return bool
	 */
	private function _compare_eq_s ($property, $expected) {
		return $property === $expected;
	}
	
	/** @noinspection PhpUnusedPrivateMethodInspection
	 *
	 * @param $property
	 * @param $expected
	 *
	 * @return bool
	 */
	private function _compare_ne ($property, $expected) {
		return $property != $expected;
	}
	
	/** @noinspection PhpUnusedPrivateMethodInspection
	 *
	 * @param $property
	 * @param $expected
	 *
	 * @return bool
	 */
	private function _compare_ne_s ($property, $expected) {
		return $property !== $expected;
	}
	
	/** @noinspection PhpUnusedPrivateMethodInspection
	 *
	 * @param $property
	 * @param $expected
	 *
	 * @return bool
	 */
	private function _compare_in ($property, $expected) {
		return is_array($expected) && is_scalar($property) && in_array($property, $expected);
	}

	/** @noinspection PhpUnusedPrivateMethodInspection
	 *
	 * @param $property
	 * @param $expected
	 *
	 * @return bool
	 */
	private function _compare_nin ($property, $expected) {
		return is_array($expected) && is_scalar($property) && !in_array($property, $expected);
	}

	/** @noinspection PhpUnusedPrivateMethodInspection
	 *
	 * @param $property
	 * @param $expected
	 *
	 * @return bool
	 */
	private function _compare_in_s ($property, $expected) {
		return is_array($expected) && in_array($property, $expected, true);
	}

	/** @noinspection PhpUnusedPrivateMethodInspection
	 *
	 * @param $property
	 * @param $expected
	 *
	 * @return bool
	 */
	private function _compare_nin_s ($property, $expected) {
		return is_array($expected) && !in_array($property, $expected, true);
	}
	
	/** @noinspection PhpUnusedPrivateMethodInspection
	 *
	 * @param $property
	 * @param $expected
	 * @param QuarkDate $date = null
	 *
	 * @return bool
	 */
	private function _compare_lt ($property, $expected, QuarkDate $date = null) {
		return $date ? $date->Earlier(QuarkDate::From($expected)) : $property < $expected;
	}
	
	/** @noinspection PhpUnusedPrivateMethodInspection
	 *
	 * @param $property
	 * @param $expected
	 * @param QuarkDate $date = null
	 *
	 * @return bool
	 */
	private function _compare_lte ($property, $expected, QuarkDate $date = null) {
		return $date ? $date->Earlier(QuarkDate::From($expected)) : $property <= $expected;
	}
	
	/** @noinspection PhpUnusedPrivateMethodInspection
	 *
	 * @param $property
	 * @param $expected
	 * @param QuarkDate $date = null
	 *
	 * @return bool
	 */
	private function _compare_gt ($property, $expected, QuarkDate $date = null) {
		return $date ? $date->Later(QuarkDate::From($expected)) : $property > $expected;
	}
	
	/** @noinspection PhpUnusedPrivateMethodInspection
	 *
	 * @param $property
	 * @param $expected
	 * @param QuarkDate $date = null
	 *
	 * @return bool
	 */
	private function _compare_gte ($property, $expected, QuarkDate $date = null) {
		return $date ? $date->Later(QuarkDate::From($expected)) : $property >= $expected;
	}
	
	/** @noinspection PhpUnusedPrivateMethodInspection
	 *
	 * @param $property
	 * @param $expected
	 *
	 * @return bool
	 */
	private function _compare_regex ($property, $expected) {
		return preg_match($expected, $property);
	}
	
	/** @noinspection PhpUnusedPrivateMethodInspection
	 *
	 * @param $document
	 * @param array $rule
	 *
	 * @return bool
	 */
	private function _compare_not ($document, $rule) {
		return !$this->Match($document, $rule);
	}
	
	/**
	 * @param array $query = []
	 * @param array $options = []
	 *
	 * @return array
	 */
	public function Select ($query = [], $options = []) {
		if (!QuarkObject::isTraversable($query)) return array();
		if (!QuarkObject::isTraversable($this->_collection)) return array();
		
		$out = array();
		
		if (sizeof($query) == 0) $out = $this->_collection;
		else foreach ($this->_collection as $i => &$item) {
			if ($this->Match($item, $query))
				$out[] = $item;
		}
		
		return $this->_slice($out, $options);
	}

	/**
	 * @param array $query = []
	 * @param array $options = []
	 *
	 * @return mixed|null
	 */
	public function SelectOne ($query = [], $options = []) {
		$options[QuarkModel::OPTION_LIMIT] = 1;

		$out = $this->Select($query, $options);

		return sizeof($out) == 0 ? null : $out[0];
	}

	/**
	 * @param array $query = []
	 * @param array $options = []
	 *
	 * @return array
	 */
	public function SelectRandom ($query = [], $options = []) {
		$count = $this->Count($query);

		if (!isset($options[QuarkModel::OPTION_SKIP]))
			$options[QuarkModel::OPTION_SKIP] = mt_rand(0, $count == 0 ? 0 : $count - 1);

		if (!isset($options[QuarkModel::OPTION_LIMIT]))
			$options[QuarkModel::OPTION_LIMIT] = QuarkModel::LIMIT_RANDOM;

		return $this->Select($query, $options);
	}
	
	/**
	 * @param array $list = []
	 * @param array $options = []
	 * @param bool $preserveKeys = null
	 *
	 * @return array
	 */
	private function _slice ($list = [], $options = [], $preserveKeys = null) {
		if (isset($options[QuarkModel::OPTION_SORT]) && QuarkObject::isTraversable($options[QuarkModel::OPTION_SORT])) {
			$sort = $options[QuarkModel::OPTION_SORT];
			
			/**
			 * http://wp-kama.ru/question/php-usort-sortirovka-massiva-po-dvum-polyam
			 */
			usort($list, function ($a, $b) use ($sort) {
				$res = 0;
				
				/** @noinspection PhpUnusedLocalVariableInspection */
				$a = (object)$a;
				/** @noinspection PhpUnusedLocalVariableInspection */
				$b = (object)$b;
		
				foreach ($sort as $key => &$mode) {
					$accessor = str_replace('.', '->', str_replace('->', '', $key));
					
					$elem_a = eval('return isset($a->' . $accessor . ') ? $a->' . $accessor . ' : null;');
					$elem_b = eval('return isset($b->' . $accessor . ') ? $b->' . $accessor . ' : null;');
					
					$dir = $mode;
					
					if (!is_string($elem_a) && !is_string($elem_b)) {
						if ($elem_a == $elem_b) continue;
						$res = $elem_a < $elem_b ? -1 : 1;
					}
					else {
						$elem_a = (string)$elem_a;
						$elem_b = (string)$elem_b;
						
						$nat = isset($mode['$natural']);
						$icase = isset($mode['$icase']);
						
						if (is_array($mode)) {
							if ($nat) $dir = $mode['$natural'];
							if ($icase) $dir = $mode['$icase'];
						}
						
						$res = $nat
							? ($icase ? strnatcasecmp($elem_a, $elem_b) : strnatcmp($elem_a, $elem_b))
							: ($icase ? strcasecmp($elem_a, $elem_b) : strcmp($elem_a, $elem_b));
						
						if ($res == 0) continue;
					}
					
					if ($dir == QuarkModel::SORT_DESC) $res = -$res;
					break;
				}

				unset($key, $mode);
				
				return $res;
			});
		}
		
		$skip = isset($options[QuarkModel::OPTION_SKIP])
			? (int)$options[QuarkModel::OPTION_SKIP]
			: 0;
		
		$limit = isset($options[QuarkModel::OPTION_LIMIT]) && $options[QuarkModel::OPTION_LIMIT] !== null
			? (int)$options[QuarkModel::OPTION_LIMIT]
			: null;
		
		return array_slice($list, $skip, $limit, $preserveKeys);
	}
	
	/**
	 * @param array $query = []
	 * @param array|callable $update
	 * @param array $options = []
	 *
	 * @return int
	 */
	public function Change ($query = [], $update = null, $options = []) {
		if (!QuarkObject::isTraversable($query)) return 0;
		if (!QuarkObject::isTraversable($this->_collection)) return 0;
		
		$size = sizeof($query);
		$change = array();
		
		if (!isset($options[QuarkModel::OPTION_FORCE_DEFINITION]))
			$options[QuarkModel::OPTION_FORCE_DEFINITION] = false;
		
		foreach ($this->_collection as $i => &$item)
			if ($size == 0 || $this->Match($item, $query))
				$change[$i] = $item;
		
		$change = $this->_slice($change, $options, true);
		$keys = array_keys($change);
		
		foreach ($keys as &$i) {
			if (is_callable($update)) $update($this->_collection[$i]);
			else {
				if ($options[QuarkModel::OPTION_FORCE_DEFINITION]) $this->_collection[$i] = $update;
				else $this->_change($i, $update);
			}
		}
		
		return sizeof($change);
	}
	
	/**
	 * @param int $i
	 * @param $update
	 */
	private function _change ($i, $update) {
		if (!QuarkObject::isTraversable($update)) return;

		$modified = false;
		$_val = function ($key, $value) use (&$i, &$modified) {
			$this->_collection[$i]->$key = $value;
			$modified = true;
		};
		
		foreach ($update as $key => &$value) {
			$val = QuarkObject::isAssociative($value) ? (array)$value : $value;
			$modified = false;
			
			if (isset($this->_collection[$i]->$key) && is_numeric($this->_collection[$i]->$key)) {
				if (isset($val['$inc'])) $_val($key, $this->_collection[$i]->$key + $val['$inc']);
				if (isset($val['$mul'])) $_val($key, $this->_collection[$i]->$key * $val['$mul']);
				if (isset($val['$min']) && $val['$min'] > $this->_collection[$i]->$key) $_val($key, $val['$min']);
				if (isset($val['$max']) && $val['$min'] < $this->_collection[$i]->$key) $_val($key, $val['$max']);
			}

			if (isset($val['$currentDate'])) $_val($key, QuarkDate::GMTNow()->Format(QuarkCultureISO::DATETIME));

			/**
			 * @note This operators behaves different from MongoDB
			 *       They are used as VALUES for selected fields, not as top-level modifiers
			 */
			if (!$this->_secure) {
				if (isset($val['$set'])) $_val($key, $val['$set']);

				if (isset($val['$unset'])) {
					unset($this->_collection[$i]->$key);
					$modified = true;
				}

				if (isset($val['$rename'])) {
					$name = $val['$rename'];
					$this->_collection[$i]->$name = $this->_collection[$i]->$key;

					unset($this->_collection[$i]->$key);
					$modified = true;
				}
			}

			if (!$modified)
				$this->_collection[$i]->$key = $value;
		}
	}

	/**
	 * @param array $query = []
	 * @param array|callable $update = null
	 * @param array $options = []
	 * @param int &$changed = 0
	 *
	 * @return QuarkCollectionBehavior
	 */
	public function ChangeAndReturn ($query = [], $update = null, $options = [], &$changed = 0) {
		$changed = $this->Change($query, $update, $options);

		return $this;
	}
	
	/**
	 * @param array $query = []
	 * @param array $options = []
	 * @param bool $preserveKeys = false
	 *
	 * @return int
	 */
	public function Purge ($query = [], $options = [], $preserveKeys = false) {
		if (!QuarkObject::isTraversable($query)) return 0;
		if (!QuarkObject::isTraversable($this->_collection)) return 0;
		
		$size = sizeof($query);
		$purge = array();
		
		foreach ($this->_collection as $i => &$item)
			if ($size == 0 || $this->Match($item, $query))
				$purge[$i] = $item;
		
		$purge = $this->_slice($purge, $options, true);

		/** @noinspection PhpUnusedLocalVariableInspection */
		foreach ($purge as $i => &$item)
			unset($this->_collection[$i]);
		
		if (!$preserveKeys)
			$this->_collection = array_values($this->_collection);
		
		return sizeof($purge);
	}

	/**
	 * @param array $query = []
	 * @param array $options = []
	 * @param bool $preserveKeys = false
	 * @param int &$purged = 0
	 *
	 * @return QuarkCollectionBehavior
	 */
	public function PurgeAndReturn ($query = [], $options = [], $preserveKeys = false, &$purged = 0) {
		$purged = $this->Purge($query, $options, $preserveKeys);

		return $this;
	}
	
	/**
	 * Count elements of an object
	 *
	 * @param array $query = []
	 * @param array $options = []
	 *
	 * @link http://php.net/manual/en/countable.count.php
	 * @return int The custom count as an integer.
	 * </p>
	 * <p>
	 * The return value is cast to an integer.
	 * @since 5.1.0
	 */
	public function Count ($query = [], $options = []) {
		return sizeof(func_num_args() == 0
			? $this->_collection
			: $this->Select($query, $options)
		);
	}

	/**
	 * @param array $query = []
	 * @param array $options = []
	 *
	 * @return bool
	 */
	public function Exists ($query = [], $options = []) {
		return $this->Count($query, $options) != 0;
	}

	/**
	 * @param array $options = []
	 * @param bool $free = false
	 *
	 * @return array
	 */
	public function Aggregate ($options = [], $free = false) {
		$out = $this->_slice($this->_collection, $options);

		if ($free) {
			unset($this->_collection);
			$this->_collection = array();
		}

		return $out;
	}

	/**
	 * @param callable $mapper = null
	 *
	 * @return array
	 */
	public function Map (callable $mapper = null) {
		if ($mapper == null) return null;

		$out = array();

		foreach ($this->_collection as $i => &$item)
			$out[] = $mapper($item, $this);

		return $out;
	}

	/**
	 * @param array $initial = []
	 * @param callable $navigator = null
	 *
	 * @return void
	 */
	public function Navigate ($initial = [], callable $navigator = null) {
		if ($navigator == null) return;

		$item = $this->SelectOne($initial);
		$next = $navigator($item);

		if ($next === null) return;

		$this->Navigate($next, $navigator);
	}
}

/**
 * Interface IQuarkCollectionWithArrayAccess
 *
 * @package Quark
 */
interface IQuarkCollectionWithArrayAccess extends \Iterator, \ArrayAccess, \Countable { }

/**
 * Trait QuarkCollectionBehaviorWithArrayAccess
 *
 * @package Quark
 */
trait QuarkCollectionBehaviorWithArrayAccess {
	use QuarkCollectionBehavior;
	
	/**
	 * @var int $_index = 0
	 */
	private $_index = 0;
	
	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Return the current element
	 *
	 * @link http://php.net/manual/en/iterator.current.php
	 * @return mixed Can return any type.
	 */
	public function current () {
		return $this->_collection[$this->_index];
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
		return isset($this->_collection[$this->_index]);
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
		return isset($this->_collection[$offset]);
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
		return isset($this->_collection[$offset]) ? $this->_collection[$offset] : null;
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
		if ($offset === null) $this->_collection[] = $value;
		else $this->_collection[(int)$offset] = $value;
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
		unset($this->_collection[(int)$offset]);
	}
}

/**
 * Class QuarkCollection
 *
 * @package Quark
 */
class QuarkCollection implements IQuarkCollectionWithArrayAccess {
	use QuarkCollectionBehaviorWithArrayAccess {
		Select as private _select;
		SelectRandom as private _selectRandom;
		ChangeAndReturn as private _changeAndReturn;
		PurgeAndReturn as private _purgeAndReturn;
		Aggregate as private _aggregate;
		Map as MapRaw;
		offsetSet as private _offsetSet;
	}

	/**
	 * @var IQuarkModel|QuarkModelBehavior|mixed $_type  = null
	 */
	private $_type = null;
	
	/**
	 * @var bool $_model = true
	 */
	private $_model = true;

	/**
	 * @var int $_page = 0
	 */
	private $_page = 0;
	
	/**
	 * @var int $_pages = 0
	 */
	private $_pages = 0;

	/**
	 * @var int $_countAll = 0
	 */
	private $_countAll = 0;

	/**
	 * @param IQuarkModel|QuarkModelBehavior|mixed $type
	 * @param array $source = []
	 */
	public function __construct ($type, $source = []) {
		$this->_type = $type;
		$this->_model = $this->_type instanceof IQuarkModel;
		
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
	public function TypeIs ($item) {
		if ($item instanceof QuarkModel)
			$item = $item->Model();

		return $item instanceof $this->_type
			|| ($this->_type instanceof QuarkLazyLink && $this->_type->Model() instanceof $item)
			|| ($this->_type instanceof \stdClass && is_object($item));
	}

	/**
	 * @param $item
	 *
	 * @return bool
	 */
	public function Add ($item) {
		if (!$this->TypeIs($item)) return false;

		$this->_collection[] = !$this->_model || $item instanceof QuarkModel ? $item : new QuarkModel($item);

		return true;
	}

	/**
	 * @param array|object $source = []
	 *
	 * @return QuarkCollection
	 */
	public function AddBySource ($source = []) {
		$this->_collection[] = new QuarkModel($this->_type, $source);

		return $this;
	}

	/**
	 * @param $needle
	 * @param callable $compare
	 *
	 * @return bool
	 */
	public function Remove ($needle, callable $compare) {
		if (!$this->TypeIs($needle)) return false;

		foreach ($this->_collection as $key => &$item)
			if ($compare($item, $needle, $key))
				unset($this->_collection[$key]);

		return true;
	}

	/**
	 * @param $source
	 *
	 * @return QuarkCollection
	 */
	public function Instance ($source) {
		if ($this->_type instanceof IQuarkModel)
			$this->_collection[] = new QuarkModel($this->_type, $source);

		return $this;
	}

	/**
	 * @return QuarkCollection
	 */
	public function Reverse () {
		$this->_collection = array_reverse($this->_collection);

		return $this;
	}
	
	/**
	 * @return QuarkCollection
	 */
	public function Shuffle () {
		shuffle($this->_collection);
		
		return $this;
	}

	/**
	 * @param $needle
	 * @param callable $compare
	 *
	 * @return bool
	 */
	public function In ($needle, callable $compare) {
		foreach ($this->_collection as $key => &$item)
			if ($compare($item, $needle, $key)) return true;

		return false;
	}

	/**
	 * @param array $source
	 * @param callable $iterator = null
	 *
	 * @return QuarkCollection
	 */
	public function PopulateWith ($source, callable $iterator = null) {
		if ($source instanceof QuarkCollection)
			$source = $source->_collection;

		if (is_array($source)) {
			$this->_collection = array();

			foreach ($source as $key => &$item)
				$this->Add($iterator == null ? $item : $iterator($item, $key));
		}

		return $this;
	}

	/**
	 * @param array $source = []
	 *
	 * @return QuarkCollection
	 */
	public function PopulateModelsWith ($source = []) {
		return $this->PopulateWith($source, function ($item) {
			return new QuarkModel($this->_type, $item);
		});
	}

	/**
	 * @param callable $iterator = null
	 *
	 * @return array
	 */
	public function Collection (callable $iterator = null) {
		$output = array();

		foreach ($this->_collection as $key => &$item)
			$output[] = $iterator == null ? $item : $iterator($item, $key);

		return $output;
	}

	/**
	 * @param callable $iterator = null
	 *
	 * @return QuarkCollection
	 */
	public function Filter (callable $iterator = null) {
		$output = new self($this->_type);

		foreach ($this->_collection as $key => &$item)
			if ($iterator == null || $iterator($item, $key))
				$output[] = $item;

		return $output;
	}

	/**
	 * @param array $fields = null
	 * @param bool $weak = false
	 *
	 * @return array
	 */
	public function Extract ($fields = null, $weak = false) {
		if (!($this->_type instanceof IQuarkModel)) return $this->_collection;

		$out = array();

		foreach ($this->_collection as $key => &$item)
			/**
			 * @var QuarkModel $item
			 */
			$out[] = $item->Extract($fields, $weak);

		return $out;
	}

	/**
	 * @return QuarkCollection
	 */
	public function Flush () {
		$this->_collection = array();
		$this->_index = 0;

		return $this;
	}
	
	/**
	 * @param int $page = 0
	 *
	 * @return int
	 */
	public function Page ($page = 0) {
		if (func_num_args() != 0)
			$this->_page = $page;
		
		return $this->_page;
	}
	
	/**
	 * @param int $pages = 0
	 *
	 * @return int
	 */
	public function Pages ($pages = 0) {
		if (func_num_args() != 0)
			$this->_pages = $pages;
		
		return $this->_pages;
	}
	
	/**
	 * @param array $query = []
	 * @param array $options = []
	 *
	 * @return QuarkCollection
	 */
	public function Select ($query = [], $options = []) {
		return new self($this->_type, $this->_select($query, $options));
	}

	/**
	 * @param array $query = []
	 * @param array $options = []
	 *
	 * @return QuarkCollection
	 */
	public function SelectRandom ($query = [], $options = []) {
		return new self($this->_type, $this->_selectRandom($query, $options));
	}

	/**
	 * @param int $page = 1
	 * @param array $query = []
	 * @param array $options = []
	 *
	 * @return QuarkCollection
	 */
	public function SelectByPage ($page = 1, $query = [], $options = []) {
		if (!isset($options[QuarkModel::OPTION_LIMIT]))
			$options[QuarkModel::OPTION_LIMIT] = QuarkModel::LIMIT_PAGED;

		$pages = 1;
		$page = (int)$page;
		if ($page < 1) $page = 1;

		$this->_countAll = $this->Count($query);

		if ($options[QuarkModel::OPTION_LIMIT] != QuarkModel::LIMIT_NO) {
			$options[QuarkModel::OPTION_LIMIT] = (int)$options[QuarkModel::OPTION_LIMIT];

			if ($options[QuarkModel::OPTION_LIMIT] < 1)
				$options[QuarkModel::OPTION_LIMIT] = 1;

			$pages = (int)ceil($this->_countAll / $options[QuarkModel::OPTION_LIMIT]);
		}

		if (!isset($options[QuarkModel::OPTION_SKIP]))
			$options[QuarkModel::OPTION_SKIP] = ($page - 1) * $options[QuarkModel::OPTION_LIMIT];

		$out = $this->Select($query, $options);

		$out->Page($page);
		$out->Pages($pages);

		return $out;
	}

	/**
	 * @param int $count = 0
	 *
	 * @return int
	 */
	public function CountAll ($count = 0) {
		if (func_num_args() != 0)
			$this->_countAll = $count;

		return $this->_countAll;
	}

	/**
	 * @param array $query = []
	 * @param array|callable $update = null
	 * @param array $options = []
	 * @param int &$changed = 0
	 *
	 * @return QuarkCollection
	 */
	public function ChangeAndReturn ($query = [], $update = null, $options = [], &$changed = 0) {
		return $this->_changeAndReturn($query, $update, $options, $changed);
	}

	/**
	 * @param array $query = []
	 * @param array $options = []
	 * @param bool $preserveKeys = false
	 * @param int &$purged = 0
	 *
	 * @return QuarkCollection
	 */
	public function PurgeAndReturn ($query = [], $options = [], $preserveKeys = false, &$purged = 0) {
		return $this->_purgeAndReturn($query, $options, $preserveKeys, $purged);
	}

	/**
	 * @param array $options = []
	 * @param bool $free = false
	 *
	 * @return QuarkCollection
	 */
	public function Aggregate ($options = [], $free = false) {
		return new self($this->_type, $this->_aggregate($options, $free));
	}

	/**
	 * @param IQuarkModel $type = null
	 * @param callable $mapper = null
	 *
	 * @return QuarkCollection
	 */
	public function Map (IQuarkModel $type = null, callable $mapper = null) {
		return $mapper == null ? null : new self(
			$type == null ? $this->_type : $type,
			$this->MapRaw($mapper)
		);
	}
	
	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet ($offset, $value) {
		if (!$this->TypeIs($value)) return;
		
		$this->_offsetSet($offset, $value);
	}

	/**
	 * @return QuarkCollection|IQuarkLinkedModel[]
	 */
	public function RetrieveLazy () {
		$out = new self($this->_type->Model());
		$model = null;

		foreach ($this->_collection as $i => &$item) {
			/**
			 * @var QuarkLazyLink|IQuarkLinkedModel $item
			 * @var QuarkLazyLink $model
			 */
			$model = clone $item->Model();
			$out[] = $model->Retrieve();
		}

		return $out;
	}

	/**
	 * @param QuarkModel|IQuarkLinkedModel $item
	 * @param $value = null
	 *
	 * @return bool
	 */
	public function AddLazy ($item, $value = null) {
		$type = $this->_type->Model();
		$add = $item instanceof QuarkModel ? $item->Model() : $item;
		$typeOk = $add instanceof $type;

		return $typeOk ? $this->Add(new QuarkLazyLink($add, $value, true)) : false;
	}

	/**
	 * @param IQuarkLinkedModel $model
	 * @param $value = null
	 *
	 * @return QuarkCollection|QuarkLazyLink[]|IQuarkLinkedModel[]
	 */
	public static function Lazy (IQuarkLinkedModel $model, $value = null) {
		return new self(new QuarkLazyLink($model, $value));
	}

	/**
	 * @param IQuarkLinkedModel $model
	 * @param int $page = 1
	 * @param int $pages = 1
	 *
	 * @return QuarkCollection|IQuarkModel
	 */
	public static function Empty (IQuarkLinkedModel $model, $page = 1, $pages = 1) {
		$out = new self($model);

		$out->Page($page);
		$out->Pages($pages);

		return $out;
	}

	/**
	 * Reset QuarkCollection
	 */
	public function __destruct () {
		unset($this->_collection, $this->_type);
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
	 * @return QuarkKeyValuePair
	 */
	public function DataProviderPk () {
		$source = $this->Source();

		return $source instanceof QuarkModelSource ? $source->Provider()->PrimaryKey($this->Model()) : null;
	}

	/**
	 * @return IQuarkDataProvider
	 *
	 * @throws QuarkArchException
	 */
	public function DataProviderLink () {
		if (!($this instanceof IQuarkModel))
			throw new QuarkArchException('[QuarkModelBehavior::DataProviderLink] Class ' . get_class($this) . ' is not an IQuarkModel');

		/**
		 * @var IQuarkModel $this
		 */

		return QuarkModel::DataProvider($this);
	}

	/**
	 * @param bool $runtime = true
	 *
	 * @return array|null
	 */
	public function FieldKeys ($runtime = true) {
		return $this->__call('FieldKeys', func_get_args());
	}

	/**
	 * @param string[] $exclude = []
	 * @param bool $runtime = true
	 * 
	 * @return array|null
	 */
	public function FieldValues ($exclude = [], $runtime = true) {
		return $this->__call('FieldValues', func_get_args());
	}

	/**
	 * @param bool $runtime = true
	 *
	 * @return array
	 */
	public function PropertyKeys ($runtime = true) {
		return $this->__call('PropertyKeys', func_get_args());
	}

	/**
	 * @param string[] $exclude = []
	 * @param bool $runtime = true
	 * 
	 * @return array
	 */
	public function PropertyValues ($exclude = [], $runtime = true) {
		return $this->__call('PropertyValues', func_get_args());
	}
	
	/**
	 * @param $default
	 *
	 * @return callable
	 */
	public function Nullable ($default) {
		$store = $default;
		$stored = false;
		
		/** @noinspection PhpUnusedParameterInspection
		 *
		 * @param string $key
		 * @param mixed $value
		 * @param bool $changed
		 *
		 * @return mixed
		 */
		return function ($key, $value, $changed) use ($default, &$store, &$stored) {
			if ($changed) {
				if (is_scalar($value))
					settype($value, gettype($default));
					
				$store = $value;
				$stored = true;
			}
			
			return $stored ? $store : null;
		};
	}

	/**
	 * @param IQuarkLinkedModel $model = null
	 * @param $value = null
	 * @param bool $linked = false
	 *
	 * @return QuarkLazyLink
	 */
	public function LazyLink (IQuarkLinkedModel $model, $value = null, $linked = false) {
		return new QuarkLazyLink($model, func_num_args() > 1 ? $value : '', $linked);
	}

	/**
	 * @param IQuarkLinkedModel $model
	 * @param $value = null
	 *
	 * @return QuarkCollection|QuarkLazyLink[]|IQuarkLinkedModel[]
	 */
	public function LazyLinkCollection (IQuarkLinkedModel $model, $value = null) {
		return QuarkCollection::Lazy($model, $value);
	}

	/**
	 * @return QuarkGuID
	 */
	public function GuID () {
		return new QuarkGuID($this instanceof IQuarkModelWithDataProvider ? $this->DataProvider() : null);
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
	 * @param array $fields = null
	 * @param bool $weak = false
	 *
	 * @return \stdClass
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
	
	/**
	 * @return bool[]
	 */
	public function ValidationRules () {
		return $this->__call('ValidationRules', func_get_args());
	}

	/**
	 * @return QuarkKeyValuePair[]
	 */
	public function RawValidationErrors () {
		return $this->__call('RawValidationErrors', func_get_args());
	}

	/**
	 * @param string $language = QuarkLanguage::ANY
	 * @param bool $fields = false
	 *
	 * @return string[]
	 */
	public function ValidationErrors ($language = QuarkLanguage::ANY, $fields = false) {
		return $this->__call('ValidationErrors', func_get_args());
	}
	
	/**
	 * @return string
	 */
	public function Operation () {
		return $this->__call('Operation', func_get_args());
	}

	/**
	 * @return IQuarkModel
	 */
	public function Model () {
		return $this->__call('Model', func_get_args());
	}

	/**
	 * @return QuarkModel
	 */
	public function User () {
		return QuarkSession::Current() ? QuarkSession::Current()->User() : null;
	}
	
	/**
	 * @param bool $rule
	 * @param QuarkLocalizedString $message = null
	 * @param string $field = ''
	 *
	 * @return bool
	 */
	public function Assert ($rule, QuarkLocalizedString $message = null, $field = '') {
		return QuarkField::Assert($rule, $message, $field);
	}
	
	/**
	 * @param bool $rule
	 * @param string|array $message = ''
	 * @param string $field = ''
	 *
	 * @return bool
	 */
	public function LocalizedAssert ($rule, $message = '', $field = '') {
		return QuarkField::LocalizedAssert($rule, $message, $field);
	}

	/**
	 * @param $key
	 * @param string $regex = ''
	 * @param bool $nullable = false
	 *
	 * @return bool
	 */
	public function PropertyIsConst ($key, $regex = '', $nullable = false) {
		return QuarkField::In($key, array_values($this->Constants($regex)), $nullable);
	}
	
	/**
	 * @param string $field = ''
	 * @param string[] $op = [QuarkModel::OPERATION_CREATE]
	 *
	 * @return bool
	 */
	public function Unique ($field = '', $op = [QuarkModel::OPERATION_CREATE]) {
		return $this instanceof IQuarkModel ? QuarkField::Unique($this, $field, $op) : false;
	}

	/**
	 * @param array $options
	 * @param string $key = ''
	 *
	 * @return mixed
	 */
	public function UserOption ($options, $key = '') {
		return !isset($options[QuarkModel::OPTION_USER_OPTIONS])
			? null
			: (func_num_args() == 2
				? (isset($options[QuarkModel::OPTION_USER_OPTIONS][$key])
					? $options[QuarkModel::OPTION_USER_OPTIONS][$key]
					: null
				)
				: $options[QuarkModel::OPTION_USER_OPTIONS]
			);
	}
}

/**
 * Class QuarkModelWithValidationControl
 *
 * @package Quark
 */
trait QuarkModelWithValidationControl {
	/**
	 * @var bool $_validationControl = null
	 */
	private $_validationControl = null;

	/**
	 * @return bool|null
	 */
	public function ValidationControl () {
		return $this->_validationControl;
	}

	/**
	 * @param bool|null $control = null
	 *
	 * @return IQuarkModel|IQuarkModelWithValidationControl|QuarkModelWithValidationControl
	 */
	public static function WithValidationControl ($control = null) {
		$model = new self();
		$model->_validationControl = $control;

		return $model;
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
	 * @var bool $_connected = false
	 */
	private $_connected = false;

	/**
	 * @param string $name
	 * @param IQuarkDataProvider $provider
	 * @param QuarkURI $uri
	 */
	public function __construct ($name, IQuarkDataProvider $provider, QuarkURI $uri = null) {
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
	 * @return QuarkModelSource
	 *
	 * @throws QuarkArchException
	 */
	public function &Connect () {
		if ($this->_uri == null)
			throw new QuarkArchException('[QuarkModelSource::Connect] Unable to connect data source "' . $this->_name . '": connection URI is null');

		$this->_connection = $this->_provider->Connect($this->_uri);
		$this->_connected = true;

		return $this;
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
	 * @return bool
	 */
	public function &Connected () {
		return $this->_connected;
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
	const OPTION_EXPORT_SUB_MODEL = 'export_sub';
	const OPTION_REVERSE = 'reverse';

	const OPTION_USER_OPTIONS = '___user___';
	const OPTION_FORCE_DEFINITION = '___force_definition___';

	const OPERATION_CREATE = 'Create';
	const OPERATION_SAVE = 'Save';
	const OPERATION_REMOVE = 'Remove';
	const OPERATION_EXPORT = 'Export';
	const OPERATION_VALIDATE = 'Validate';

	const SORT_ASC = 1;
	const SORT_DESC = -1;
	const LIMIT_NO = '___limit_no___';
	const LIMIT_RANDOM = 1;
	const LIMIT_PAGED = 25;

	const CONFIG_VALIDATION_ALL = 'model.validation.all';
	const CONFIG_VALIDATION_STORE = 'model.validation.store';

	/**
	 * @var IQuarkModel|IQuarkStrongModelWithRuntimeFields|QuarkModelBehavior $_model = null
	 */
	private $_model = null;

	/**
	 * @var QuarkKeyValuePair[] $_errors
	 */
	private $_errors = array();

	/**
	 * @var bool $_default = false
	 */
	private $_default = false;

	/**
	 * @var string $_op = ''
	 */
	private $_op = '';

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

		if (func_num_args() == 1) {
			$source = $model;
			$this->_default = true;
		}

		if ($source instanceof QuarkModel)
			$source = $source->Model();

		Quark::Container($this);

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
		$this->_model->$key = $this->Field($key) instanceof IQuarkModel && $value instanceof IQuarkModel ? new QuarkModel($value) : $value;
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
	 * @param $key
	 */
	public function __unset ($key) {
		unset($this->_model->$key);
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
			return call_user_func_array(array(&$this->_model, $method), $args);

		$model = $this->_model == null ? 'null' : get_class($this->_model);

		if ($this->_model instanceof IQuarkModelWithDataProvider) {
			$provider = self::_provider($this->_model)->Provider();
			array_unshift($args, $this->_model);

			if (method_exists($provider, $method))
				return call_user_func_array(array($provider, $method), $args);

			$model .= ' or provider ' . ($provider == null ? 'null' : get_class($provider));
		}

		throw new QuarkArchException('Method ' . $method . ' not found in model ' . $model);
	}

	/**
	 * @return string
	 */
	public function __toString () {
		return method_exists($this->_model, '__toString') ? (string)$this->_model : '';
	}

	/**
	 * @var IQuarkModel|QuarkModelBehavior $model = null
	 *
	 * @return IQuarkModel|QuarkModelBehavior
	 */
	public function &Model ($model = null) {
		if (func_num_args() != 0)
			$this->_model = $model;

		return $this->_model;
	}

	/**
	 * @param IQuarkPrimitive $primitive = null
	 *
	 * @return IQuarkPrimitive
	 */
	public function &Primitive (IQuarkPrimitive $primitive = null) {
		if (func_num_args() != 0)
			$this->_model = $primitive;

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
		if ($this->_model instanceof IQuarkModelWithBeforePopulate) {
			$out = $this->_model->BeforePopulate($source);

			if ($out === false) return $this;
		}

		$this->_model = self::_import($this->_model, $source, $this->_default);

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
		$source = null;

		try {
			$source = Quark::Stack($name);
		}
		catch (\Exception $e) { }

		if (!($source instanceof QuarkModelSource))
			throw new QuarkArchException('Model source for model ' . get_class($model) . ' is not connected');

		if ($uri)
			$source->URI(QuarkURI::FromURI($uri));

		return $uri || !$source->Connected() ? $source->Connect() : $source;
	}

	/**
	 * @param IQuarkModel $model
	 * @param string $uri = ''
	 *
	 * @return IQuarkDataProvider
	 *
	 * @throws QuarkArchException
	 */
	public static function DataProvider (IQuarkModel $model, $uri = '') {
		$source = self::_provider($model, $uri);

		return $source == null ? null : $source->Provider();
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
	 * @param IQuarkModel|IQuarkModelWithCustomCollectionName $model
	 * @param array $options
	 *
	 * @return string
	 */
	public static function CollectionName (IQuarkModel $model = null, $options = []) {
		if (isset($options[QuarkModel::OPTION_COLLECTION]))
			return $options[QuarkModel::OPTION_COLLECTION];

		if ($model instanceof IQuarkModelWithCustomCollectionName) {
			$name = $model->CollectionName();

			if ($name !== null)
				return $name;
		}

		return QuarkObject::ClassOf($model);
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
			: new QuarkModel($model);
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

		if ($model instanceof IQuarkStrongModelWithRuntimeFields)
			$fields = array_replace($fields, (array)$model->RuntimeFields());

		$output = $model;

		if (!is_array($fields) && !is_object($fields)) return $output;
		
		foreach ($fields as $key => &$field) {
			/**
			 * @var mixed|callable $field
			 */
			if (is_int($key) && $field instanceof QuarkKeyValuePair) {
				$fields[$field->Key()] = $field->Value();
				unset($fields[$key]);

				$key = $field->Key();
				$field = $field->Value();
			}

			if ($key == '') continue;
			
			if (isset($model->$key)) {
				if (self::_callableField($field)) $output->$key = $field($key, $model->$key, !is_callable($model->$key));
				else {
					$output->$key = $model->$key;
					
					if (is_scalar($field) && is_scalar($model->$key))
						settype($output->$key, gettype($field));
				}
			}
			else $output->$key = $field instanceof IQuarkModel
				? QuarkModel::Build($field, empty($model->$key) ? null : $model->$key)
				: (self::_callableField($field)
					? $field($key, null, false)
					: $field
				);
		}

		return $output;
	}

	/**
	 * @param IQuarkModel|QuarkModelBehavior $model
	 * @param $source
	 * @param bool $default = false
	 *
	 * @return IQuarkModel|QuarkModelBehavior
	 */
	private static function _import (IQuarkModel $model, $source, $default = false) {
		if (!is_array($source) && !is_object($source)) return $model;

		$fields = (array)$model->Fields();

		if ($model instanceof IQuarkStrongModelWithRuntimeFields)
			$fields = array_replace($fields, (array)$model->RuntimeFields());

		// TODO: investigate the $default behavior: for old and new MongoDB drivers is now useless...
		if (/*!$default && */$model instanceof IQuarkModelWithDataProvider && ($model instanceof IQuarkModelWithManageableDataProvider ? $model->DataProviderForSubModel($source) : true)) {
			/**
			 * @var IQuarkModel $model
			 */
			$ppk = self::_provider($model)->PrimaryKey($model);

			if ($ppk instanceof QuarkKeyValuePair) {
				$pk = $ppk->Key();

				if (!isset($fields[$pk]))
					$fields[$pk] = $ppk->Value();
			}
		}

		$key_type = null;

		foreach ($source as $key => &$value) {
			if ($key == '') continue;
			if (!QuarkObject::PropertyExists($fields, $key) && $model instanceof IQuarkStrongModel) continue;

			$property = QuarkObject::Property($fields, $key, $value);
			$key_type = gettype($property instanceof QuarkLazyLink ? $property->value : null);

			if ($property instanceof QuarkCollection) {
				$class = $property->Type();
				$key_type = gettype($class instanceof QuarkLazyLink ? $class->value : null);

				$model->$key = $property->PopulateWith($value, function ($item) use ($key, $class, $key_type) {
					return self::_link(clone $class, $item, $key, $key_type);
				});
			}
			else $model->$key = self::_link($property, $value, $key, $key_type);
		}

		unset($key, $value);

		return self::_normalize($model);
	}

	/**
	 * @param $property
	 * @param $value
	 * @param $key
	 * @param string $key_type
	 *
	 * @return mixed|QuarkModel
	 */
	private static function _link ($property, $value, $key, $key_type) {
		$value_linked = $value;

		return $property instanceof IQuarkLinkedModel
			? ($value instanceof QuarkModel ? $value : $property->Link(QuarkObject::isAssociative($value) ? (object)$value : ($key_type != 'NULL' && settype($value_linked, $key_type) ? $value_linked : $value)))
			: ($property instanceof IQuarkModel
				? ($property instanceof IQuarkNullableModel && $value == null ? null : new QuarkModel($property, $value))
				: (self::_callableField($property) ? $property($key, $value, true) : $value)
			);
	}

	/**
	 * @param $property
	 *
	 * @return bool
	 */
	private static function _callableField ($property) {
		return !is_scalar($property) && is_callable($property);
	}

	/**
	 * @param IQuarkModel|QuarkModelBehavior $model
	 * @param $options
	 *
	 * @return IQuarkModel|QuarkModelBehavior|bool
	 */
	private static function _export (IQuarkModel $model, $options = []) {
		$fields = self::_normalizeFields($model);
		$forceDefinition = isset($options[self::OPTION_FORCE_DEFINITION]) && $options[self::OPTION_FORCE_DEFINITION];

		if (!isset($options[self::OPTION_VALIDATE]))
			$options[self::OPTION_VALIDATE] = true;

		if (!$forceDefinition && $options[self::OPTION_VALIDATE] && !self::_validate($model)) return false;

		$output = self::_normalize(clone $model);

		foreach ($model as $key => &$value) {
			if ($key == '') continue;

			if (!QuarkObject::PropertyExists($fields, $key) && $model instanceof IQuarkStrongModel) {
				unset($output->$key);
				continue;
			}

			if ($value instanceof QuarkCollection) {
				$output->$key = $value->Collection(function ($item) use ($fields, $key) {
					return self::_unlink(isset($fields[$key]) ? $fields[$key] : null, $item, $key);
				});
			}
			else $output->$key = self::_unlink(isset($fields[$key]) ? $fields[$key] : null, $value, $key);
		}
		
		if ($forceDefinition)
			foreach ($output as $key => &$value)
				if (!isset($model->$key))
					unset($output->$key);

		unset($key, $value);

		return $output;
	}

	/**
	 * @param $property
	 * @param mixed|callable $value
	 * @param $key
	 *
	 * @return mixed|IQuarkModel
	 */
	private static function _unlink ($property, $value, $key) {
		if ($value instanceof QuarkModel)
			$value = self::_export($value->Model());

		return $value instanceof IQuarkLinkedModel
			? $value->Unlink()
			: (self::_callableField($property) ? $property($key, $value, !is_callable($value)) : $value);
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return mixed
	 */
	private static function _normalizeFields (IQuarkModel $model) {
		$fields = $model->Fields();

		if (!QuarkObject::isTraversable($fields)) return $fields;

		foreach ($fields as $key => &$field) {
			if (!is_int($key) || (!$field instanceof QuarkKeyValuePair)) continue;

			$fields[$field->Key()] = $field->Value();
			unset($fields[$key]);
		}
		
		return $fields;
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

		if ($model instanceof IQuarkModelWithValidationControl) {
			$control = $model->ValidationControl();

			if ($control !== null)
				return $control;
		}

		$output = $model;

		if ($model instanceof IQuarkStrongModel) {
			$fields = (array)$model->Fields();

			if ($model instanceof IQuarkStrongModelWithRuntimeFields)
				$fields = array_replace($fields, (array)$model->RuntimeFields());

			if (is_array($fields) || is_object($fields))
				foreach ($fields as $key => $field) {
					if ($key == '' || isset($model->$key)) continue;

					$output->$key = $field instanceof IQuarkModel
						? QuarkModel::Build($field, empty($model->$key) ? null : $model->$key)
						: $field;
				}
		}

		if ($output instanceof IQuarkModelWithBeforeValidate && $output->BeforeValidate() === false) return false;

		$valid = $check ? QuarkField::Rules($model->Rules()) : $model->Rules();
		self::$_errorFlux = array_merge(self::$_errorFlux, QuarkField::FlushValidationErrors());

		foreach ($output as $key => $value) {
			if ($key == '' || !($value instanceof QuarkModel)) continue;

			if ($check) $valid &= $value->Validate(false);
			else $valid[$key] = $value->ValidationRules();
		}

		if ($output instanceof IQuarkModelWithAfterValidate && $output->AfterValidate() === false) return false;

		return $valid;
	}

	/**
	 * @param IQuarkModel|QuarkModelBehavior $model
	 * @param mixed $data
	 * @param array $options
	 * @param callable $after = null
	 *
	 * @return QuarkModel|QuarkModelBehavior|\stdClass
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

		if ($output === null) return null;

		if ($output instanceof QuarkModel && is_array($options) && isset($options[self::OPTION_EXTRACT]) && $options[self::OPTION_EXTRACT] !== false)
			$output = $options[self::OPTION_EXTRACT] === true
				? $output->Extract()
				: $output->Extract($options[self::OPTION_EXTRACT]);

		return $output;
	}

	/**
	 * @param bool $subModel = false
	 * 
	 * @return IQuarkModel|QuarkModelBehavior|bool
	 */
	public function Export ($subModel = false) {
		$model = self::_export($this->_model);

		$ok = $subModel && $model instanceof IQuarkModelWithAfterExport
			? $model->AfterExport(self::OPERATION_EXPORT, array(
				self::OPTION_EXPORT_SUB_MODEL => $subModel
			))
			: true;

		return $ok || $ok === null ? $model : null;
	}

	/**
	 * @param array $fields = null
	 * @param bool $weak = false
	 *
	 * @return \stdClass
	 */
	public function Extract ($fields = null, $weak = false) {
		if ($this->_model instanceof IQuarkPolymorphicModel) {
			$morph = $this->_model->PolymorphicExtract();

			if ($morph !== null) return $morph;
		}

		$output = new \stdClass();

		$model = clone $this->_model;

		if ($model instanceof IQuarkModelWithBeforeExtract) {
			$out = $model->BeforeExtract($fields, $weak);

			if ($out !== null)
				return $out;
		}

		if ($fields == null && $model instanceof IQuarkModelWithDefaultExtract)
			$fields = $model->DefaultExtract($fields, $weak);

		foreach ($model as $key => $value) {
			if ($key == '') continue;

			$property = QuarkObject::Property($fields, $key, null);

			$output->$key = $value instanceof QuarkModel
				? $value->Extract($property)
				: ($value instanceof QuarkCollection
					? $value->Collection(function ($item) use ($property) {
						return $item instanceof QuarkModel ? $item->Extract($property) : $item;
					})
					: $value);
		}

		if ($fields === null) return $output;

		$buffer = new \stdClass();
		$property = null;

		$backbone = (array)($weak ? $model->Fields() : $fields);

		foreach ($backbone as $field => $rule) {
			if (property_exists($output, $field))
				$buffer->$field = QuarkObject::Property($output, $field, null);

			if ($weak && !isset($fields[$field])) continue;
			else {
				if (is_string($rule) && property_exists($output, $rule))
					$buffer->$rule = QuarkObject::Property($output, $rule, null);
			}
		}

		if ($model instanceof IQuarkModelWithAfterExtract) {
			$out = $model->AfterExtract($buffer, $fields, $weak);

			if ($out !== null)
				return $out;
		}

		return $buffer;
	}

	/**
	 * @param bool $root
	 *
	 * @return bool
	 */
	public function Validate ($root = true) {
		$operation = $this->_op;
		$this->_op = self::OPERATION_VALIDATE;

		$validate = self::_validate($this->_model);

		$this->_errors = self::$_errorFlux;
		$this->_op = $operation;

		if ($root) self::$_errorFlux = array();

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
	 * @param string $language = QuarkLanguage::ANY
	 * @param bool $fields = false
	 *
	 * @return string[]
	 */
	public function ValidationErrors ($language = QuarkLanguage::ANY, $fields = false) {
		$out = array();
		$key = null;
		$value = null;

		foreach ($this->_errors as $i => &$error) {
			$key = $error->Key();
			$value = $error->Value()->Of($language);

			if ($fields) {
				if (!isset($out[$key])) $out[$key] = '';

				$out[$key] .= '; ' . $value;
				$out[$key] = trim($out[$key], '; ');
			}
			else $out[] = $value;
		}

		unset($i, $error, $key, $value);

		return $out;
	}

	/**
	 * @return string
	 */
	public function Operation () {
		return $this->_op;
	}

	/**
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function Field ($name) {
		$fields = (object)$this->_model->Fields();

		return isset($fields->$name) ? $fields->$name : null;
	}
	
	/**
	 * @param bool $runtime = true
	 *
	 * @return array|null
	 */
	public function FieldKeys ($runtime = true) {
		$fields = $this->_model->Fields();
		if (!QuarkObject::isAssociative($fields)) return null;
		
		$out = array();
		
		foreach ($fields as $key => &$value)
			$out[] = $key;

		unset($key, $value);
		
		if ($runtime && $this->_model instanceof IQuarkStrongModelWithRuntimeFields) {
			$fields = $this->_model->RuntimeFields();
			
			if (QuarkObject::isAssociative($fields)) {
				foreach ($fields as $key => &$value)
					$out[] = $key;

				unset($key, $value);
			}
		}
		
		return $out;
	}
	
	/**
	 * @param string[] $exclude = []
	 * @param bool $runtime = true
	 * 
	 * @return array|null
	 */
	public function FieldValues ($exclude = [], $runtime = true) {
		$fields = $this->_model->Fields();
		if (!QuarkObject::isAssociative($fields)) return null;
		
		$out = array();
		
		foreach ($fields as $key => &$value)
			if (!in_array($key, $exclude))
				$out[] = $value;

		unset($key, $value);
		
		if ($runtime && $this->_model instanceof IQuarkStrongModelWithRuntimeFields) {
			$fields = $this->_model->RuntimeFields();
			
			if (QuarkObject::isAssociative($fields)) {
				foreach ($fields as $key => &$value)
					$out[] = $value;

				unset($key, $value);
			}
		}
		
		return $out;
	}
	
	/**
	 * @param bool $runtime = true
	 *
	 * @return array
	 */
	public function PropertyKeys ($runtime = true) {
		$out = array();
		$fields = $this->FieldKeys($runtime);
		
		foreach ($this->_model as $key => &$value)
			if (!($this->_model instanceof IQuarkStrongModel) || in_array($key, $fields))
				$out[] = $key;

		unset($key, $value);
		
		return $out;
	}
	
	/**
	 * @param string[] $exclude = []
	 * @param bool $runtime = true
	 * 
	 * @return array
	 */
	public function PropertyValues ($exclude = [], $runtime = true) {
		$out = array();
		$fields = $this->FieldKeys($runtime);
		
		foreach ($this->_model as $key => &$value)
			if (!in_array($key, $exclude) && (!($this->_model instanceof IQuarkStrongModel) || in_array($key, $fields)))
				$out[] = $value;

		unset($key, $value);
		
		return $out;
	}

	/**
	 * @param string $name
	 * @param array $options = []
	 *
	 * @return bool
	 */
	private function _op ($name, $options = []) {
		$name = ucfirst(strtolower($name));
		$this->_op = $name;

		$hook = 'Before' . $name;
		$ok = QuarkObject::is($this->_model, 'Quark\IQuarkModelWith' . $hook)
			? $this->_model->$hook($options)
			: true;

		if ($ok !== null && !$ok) return false;

		if ($name == self::OPERATION_REMOVE && !isset($options[self::OPTION_VALIDATE]) && Quark::Config()->ModelValidation() == self::CONFIG_VALIDATION_STORE)
			$options[self::OPTION_VALIDATE] = false;

		$model = self::_export($this->_model, $options);
		$this->_errors = self::$_errorFlux;
		$this->_op = '';

		if (!$model) return false;

		$ok = $model instanceof IQuarkModelWithAfterExport
			? $model->AfterExport($name, $options)
			: true;

		if ($ok !== null && !$ok) return false;

		$out = self::_provider($model)->$name($model, $options);

		$this->PopulateWith($model);

		$hook = 'After' . $name;
		$ok = QuarkObject::is($this->_model, 'Quark\IQuarkModelWith' . $hook)
			? $this->_model->$hook($options)
			: true;

		if ($ok !== null && !$ok) return false;

		return $out;
	}

	/**
	 * @param array $options = []
	 *
	 * @return mixed
	 */
	public function Create ($options = []) {
		return $this->_op(self::OPERATION_CREATE, $options);
	}

	/**
	 * @param array $options = []
	 *
	 * @return mixed
	 */
	public function Save ($options = []) {
		return $this->_op(self::OPERATION_SAVE, $options);
	}

	/**
	 * @param array $options = []
	 *
	 * @return mixed
	 */
	public function Remove ($options = []) {
		return $this->_op(self::OPERATION_REMOVE, $options);
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
	 * @param $criteria = []
	 * @param array $options = []
	 * @param callable(QuarkModel $model) $after = null
	 *
	 * @return QuarkCollection|array
	 */
	public static function Find (IQuarkModel $model, $criteria = [], $options = [], callable $after = null) {
		$records = array();

		if (isset($options[self::OPTION_LIMIT]) && $options[self::OPTION_LIMIT] == self::LIMIT_NO)
			unset($options[self::OPTION_LIMIT]);

		$raw = self::_provider($model)->Find($model, $criteria, $options);

		if ($raw != null) {
			foreach ($raw as $i => &$item)
				$records[] = self::_record($model, $item, $options, $after);

			unset($i, $item);
		}

		if (isset($options[self::OPTION_REVERSE]))
			$records = array_reverse($records);

		return isset($options[self::OPTION_EXTRACT])
			? $records
			: new QuarkCollection($model, $records);
	}

	/**
	 * @param IQuarkModel|QuarkModelBehavior $model
	 * @param $criteria = []
	 * @param array $options = []
	 * @param callable(QuarkModel $model) $after = null
	 *
	 * @return QuarkModel|\stdClass
	 */
	public static function FindOne (IQuarkModel $model, $criteria = [], $options = [], callable $after = null) {
		return self::_record($model, self::_provider($model)->FindOne($model, $criteria, $options), $options, $after);
	}

	/**
	 * @param IQuarkModel|QuarkModelBehavior $model
	 * @param $id
	 * @param array $options = []
	 * @param callable(QuarkModel $model) $after = null
	 *
	 * @return QuarkModel|\stdClass
	 */
	public static function FindOneById (IQuarkModel $model, $id, $options = [], callable $after= null) {
		return self::_record($model, self::_provider($model)->FindOneById($model, $id, $options), $options, $after);
	}

	/**
	 * @param IQuarkModel|QuarkModelBehavior $model
	 * @param $criteria = []
	 * @param array $options = []
	 *
	 * @return QuarkCollection|array
	 */
	public static function FindRandom (IQuarkModel $model, $criteria = [], $options = []) {
		$count = self::Count($model, $criteria);
		
		if (!isset($options[self::OPTION_SKIP]))
			$options[self::OPTION_SKIP] = mt_rand(0, $count == 0 ? 0 : $count - 1);
		
		if (!isset($options[self::OPTION_LIMIT]))
			$options[self::OPTION_LIMIT] = self::LIMIT_RANDOM;
		
		return self::Find($model, $criteria, $options);
	}
	
	/**
	 * @param IQuarkModel|QuarkModelBehavior $model
	 * @param int $page = 1
	 * @param array $criteria = []
	 * @param array $options = []
	 *
	 * @return QuarkCollection|array
	 */
	public static function FindByPage (IQuarkModel $model, $page = 1, $criteria = [], $options = []) {
		$optionsCount = $options;

		if (!isset($options[self::OPTION_LIMIT]))
			$options[self::OPTION_LIMIT] = self::LIMIT_PAGED;
		
		$pages = 1;
		$page = (int)$page;
		if ($page < 1) $page = 1;

		$count = self::Count($model, $criteria, 0, 0, $optionsCount);
		
		if ($options[self::OPTION_LIMIT] != self::LIMIT_NO) {
			$options[self::OPTION_LIMIT] = (int)$options[self::OPTION_LIMIT];
			
			if ($options[self::OPTION_LIMIT] < 1)
				$options[self::OPTION_LIMIT] = 1;
		
			$pages = (int)ceil($count / $options[self::OPTION_LIMIT]);
		}
		
		if (!isset($options[self::OPTION_SKIP]))
			$options[self::OPTION_SKIP] = ($page - 1) * $options[self::OPTION_LIMIT];
		
		$out = self::Find($model, $criteria, $options);
		
		$out->Page($page);
		$out->Pages($pages);
		$out->CountAll($count);
		
		return $out;
	}

	/**
	 * @param IQuarkModel|QuarkModelBehavior $model
	 * @param $criteria = []
	 * @param int $limit = 0
	 * @param int $skip = 0
	 * @param array $options = []
	 *
	 * @return int
	 */
	public static function Count (IQuarkModel $model, $criteria = [], $limit = 0, $skip = 0, $options = []) {
		return (int)self::_provider($model)->Count($model, $criteria, $limit, $skip, $options);
	}

	/**
	 * @param IQuarkModel|QuarkModelBehavior $model
	 * @param $criteria = []
	 * @param int $limit = 0
	 * @param int $skip = 0
	 * @param array $options = []
	 *
	 * @return bool
	 */
	public static function Exists (IQuarkModel $model, $criteria = [], $limit = 0, $skip = 0, $options = []) {
		return self::Count($model, $criteria, $limit, $skip, $options) != 0;
	}

	/**
	 * @param IQuarkModel|QuarkModelBehavior $model
	 * @param $criteria = []
	 * @param array $options = []
	 *
	 * @return mixed
	 */
	public static function Update (IQuarkModel $model, $criteria = [], $options = []) {
		if (!isset($options[self::OPTION_FORCE_DEFINITION]))
			$options[self::OPTION_FORCE_DEFINITION] = true;
		
		$ok = $model instanceof IQuarkModelWithBeforeSave
			? $model->BeforeSave($options)
			: true;

		$model = self::_export($model, $options);

		return $model && ($ok || $ok === null) ? self::_provider($model)->Update($model, $criteria, $options) : false;
	}

	/**
	 * @param IQuarkModel|QuarkModelBehavior $model
	 * @param $criteria = []
	 * @param $options = []
	 *
	 * @return mixed
	 */
	public static function Delete (IQuarkModel $model, $criteria = [], $options = []) {
		$ok = $model instanceof IQuarkModelWithBeforeRemove
			? $model->BeforeRemove($options)
			: true;

		return ($ok || $ok === null) ? self::_provider($model)->Delete($model, $criteria, $options) : false;
	}

	/**
	 * Reset QuarkModel
	 */
	public function __destruct () {
		unset($this->_model, $this->_errors);
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
 * Interface IQuarkModelWithManageableDataProvider
 *
 * @package Quark
 */
interface IQuarkModelWithManageableDataProvider extends IQuarkModelWithDataProvider {
	/**
	 * @param $source
	 *
	 * @return bool
	 */
	public function DataProviderForSubModel($source);
}

/**
 * Interface IQuarkLinkedModel
 *
 * @package Quark
 */
interface IQuarkLinkedModel extends IQuarkModel {
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
interface IQuarkStrongModel extends IQuarkModel { }

/**
 * Interface IQuarkStrongModelWithRuntimeFields
 *
 * @package Quark
 */
interface IQuarkStrongModelWithRuntimeFields extends IQuarkStrongModel {
	/**
	 * @return mixed
	 */
	public function RuntimeFields();
}

/**
 * Interface IQuarkNullableModel
 *
 * @package Quark
 */
interface IQuarkNullableModel { }

/**
 * Interface IQuarkPolymorphicModel
 *
 * @package Quark
 */
interface IQuarkPolymorphicModel {
	/**
	 * @return mixed
	 */
	public function PolymorphicExtract();
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
	public function PrimaryKey();
}
/**
 * Interface IQuarkModelWithCustomCollectionName
 *
 * @package Quark
 */
interface IQuarkModelWithCustomCollectionName {
	/**
	 * @return string
	 */
	public function CollectionName();
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
 * Interface IQuarkModelWithBeforePopulate
 *
 * @package Quark
 */
interface IQuarkModelWithBeforePopulate {
	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function BeforePopulate($raw);
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
 * Interface IQuarkModelWithValidationControl
 *
 * @package Quark
 */
interface IQuarkModelWithValidationControl {
	/**
	 * @return bool|null
	 */
	public function ValidationControl();
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
 * Interface IQuarkModelWithAfterValidate
 *
 * @package Quark
 */
interface IQuarkModelWithAfterValidate {
	/**
	 * @return mixed
	 */
	public function AfterValidate();
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
 * Interface IQuarkModelWithAfterExtract
 *
 * @package Quark
 */
interface IQuarkModelWithAfterExtract {
	/**
	 * @param $output
	 * @param $fields
	 * @param $weak
	 *
	 * @return mixed
	 */
	public function AfterExtract($output, $fields, $weak);
}

/**
 * Interface IQuarkModelWithDefaultExtract
 *
 * @package Quark
 */
interface IQuarkModelWithDefaultExtract {
	/**
	 * @param array $fields
	 * @param bool $weak
	 *
	 * @return array
	 */
	public function DefaultExtract($fields, $weak);
}

/**
 * Interface IQuarkApplicationSettingsModel
 *
 * @package Quark
 */
interface IQuarkApplicationSettingsModel extends IQuarkModel, IQuarkStrongModel, IQuarkModelWithDataProvider {
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
	 * @param $criteria
	 * @param $options
	 *
	 * @return array
	 */
	public function Find(IQuarkModel $model, $criteria, $options);

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return mixed
	 */
	public function FindOne(IQuarkModel $model, $criteria, $options);

	/**
	 * @param IQuarkModel $model
	 * @param $id
	 * @param $options
	 *
	 * @return mixed
	 */
	public function FindOneById(IQuarkModel $model, $id, $options);

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return mixed
	 */
	public function Update(IQuarkModel $model, $criteria, $options);

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return mixed
	 */
	public function Delete(IQuarkModel $model, $criteria, $options);

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $limit
	 * @param $skip
	 * @param $options
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

	const TYPE_DATE = 'QuarkDate';
	const TYPE_TIMESTAMP = '_timestamp';

	const ASSERT_LESS_THEN = '$lt';
	const ASSERT_LESS_THEN_OR_EQUAL = '$lte';
	const ASSERT_EQUAL = '$eq';
	const ASSERT_GREAT_THEN_OR_EQUAL = '$gte';
	const ASSERT_GREAT_THEN = '$gt';
	const ASSERT_IN = '$in';
	const ASSERT_NOT_EQUAL = '$ne';

	/**
	 * @var QuarkKeyValuePair[] $_errors
	 */
	private static $_errors = array();

	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @var string $_type = self::TYPE_STRING
	 */
	private $_type = self::TYPE_STRING;

	/**
	 * @var string $_value = ''
	 */
	private $_value = '';

	/**
	 * @param string $name = ''
	 * @param string $type = self::TYPE_STRING
	 * @param string $value = ''
	 */
	public function __construct ($name = '', $type = self::TYPE_STRING, $value = '') {
		$this->_name = $name;
		$this->_type = $type;
		$this->_value = $value;
	}

	/**
	 * @param string $name = ''
	 *
	 * @return string
	 */
	public function Name ($name = '') {
		if (func_num_args() != 0)
			$this->_name = $name;

		return $this->_name;
	}

	/**
	 * @param string $type = self::TYPE_STRING
	 *
	 * @return string
	 */
	public function Type ($type = self::TYPE_STRING) {
		if (func_num_args() != 0)
			$this->_type = $type;

		return $this->_type;
	}

	/**
	 * @param string $value = ''
	 *
	 * @return string
	 */
	public function Value ($value = '') {
		if (func_num_args() != 0)
			$this->_value = $value;

		return $this->_value;
	}

	/**
	 * @return string
	 */
	public function StringifyValue () {
		if ($this->_type == self::TYPE_BOOL)
			return $this->_value ? 'true' : 'false';

		if ($this->_type == self::TYPE_INT)
			return $this->_value == 0 ? '0' : (int)$this->_value;

		if ($this->_type == self::TYPE_FLOAT)
			return $this->_value == 0 ? '0.0' : (float)$this->_value;

		if ($this->_type == self::TYPE_DATE)
			return 'new QuarkDate()';

		return $this->_value == 'null' ? 'null' : '\'' . $this->_value . '\'';
	}

	/**
	 * @param $property
	 *
	 * @return string
	 */
	public static function TypeOf ($property) {
		if (is_int($property)) return self::TYPE_INT;
		if (is_float($property)) return self::TYPE_FLOAT;
		if (is_bool($property)) return self::TYPE_BOOL;
		if (is_null($property)) return self::TYPE_NULL;
		if ($property instanceof QuarkDate) return self::TYPE_DATE;

		return self::TYPE_STRING;
	}

	/**
	 * @param $key
	 * @param bool $nullable = false
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
	 * @param string $type
	 * @param bool $nullable = false
	 *
	 * @return bool
	 */
	public static function is ($key, $type, $nullable = false) {
		if ($nullable && $key == null) return true;

		$comparator = 'is_' . $type;

		return $comparator($key);
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $sever = false
	 * @param bool $nullable = false
	 *
	 * @return bool
	 */
	public static function Eq ($key, $value, $sever = false, $nullable = false) {
		if ($nullable && $key == null) return true;

		return $sever ? $key === $value : $key == $value;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $sever = false
	 * @param bool $nullable = false
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
	 * @param bool $nullable = false
	 *
	 * @return bool
	 */
	public static function Lt ($key, $value, $nullable = false) {
		if ($nullable && $key == null) return true;

		return $key < $value;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $nullable = false
	 *
	 * @return bool
	 */
	public static function Gt ($key, $value, $nullable = false) {
		if ($nullable && $key == null) return true;

		return $key > $value;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $nullable = false
	 *
	 * @return bool
	 */
	public static function Lte ($key, $value, $nullable = false) {
		if ($nullable && $key == null) return true;

		return $key <= $value;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $nullable = false
	 *
	 * @return bool
	 */
	public static function Gte ($key, $value, $nullable = false) {
		if ($nullable && $key == null) return true;

		return $key >= $value;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $nullable = false
	 *
	 * @return bool
	 */
	public static function MinLengthInclusive ($key, $value, $nullable = false) {
		if ($nullable && $key == null) return true;

		return is_array($key) ? sizeof($key) >= $value : strlen((string)$key) >= $value;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $nullable = false
	 *
	 * @return bool
	 */
	public static function MinLength ($key, $value, $nullable = false) {
		if ($nullable && $key == null) return true;

		return is_array($key) ? sizeof($key) > $value : strlen((string)$key) > $value;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $nullable = false
	 *
	 * @return bool
	 */
	public static function Length ($key, $value, $nullable = false) {
		if ($nullable && $key == null) return true;

		return is_array($key) ? sizeof($key) == $value : strlen((string)$key) == $value;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $nullable = false
	 *
	 * @return bool
	 */
	public static function MaxLength ($key, $value, $nullable = false) {
		if ($nullable && $key == null) return true;

		return is_array($key) ? sizeof($key) < $value : strlen((string)$key) < $value;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param bool $nullable = false
	 *
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
	 *
	 * @return bool|int
	 */
	public static function Match ($key, $value, $nullable = false) {
		if ($nullable && $key == null) return true;

		return preg_match($value, $key);
	}

	/**
	 * @param $key
	 * @param array $values = []
	 * @param bool $nullable = false
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
	 * @param bool $nullable = false
	 * @param IQuarkCulture $culture = null
	 *
	 * @return bool
	 */
	private static function _dateTime ($type, $key, $nullable = false, IQuarkCulture $culture = null) {
		if ($nullable && $key == null) return true;
		if (!is_string($key)) return false;

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
	 * @param bool $nullable = false
	 * @param IQuarkCulture $culture = null
	 * @return bool|int
	 */
	public static function DateTime ($key, $nullable = false, IQuarkCulture $culture = null) {
		return self::_dateTime('DateTime', $key, $nullable, $culture);
	}

	/**
	 * @param $key
	 * @param bool $nullable = false
	 * @param IQuarkCulture $culture = null
	 * @return bool
	 */
	public static function Date ($key, $nullable = false, IQuarkCulture $culture = null) {
		return self::_dateTime('Date', $key, $nullable, $culture);
	}

	/**
	 * @param $key
	 * @param bool $nullable = false
	 * @param IQuarkCulture $culture = null
	 * @return bool
	 */
	public static function Time ($key, $nullable = false, IQuarkCulture $culture = null) {
		return self::_dateTime('Time', $key, $nullable, $culture);
	}

	/**
	 * @param $key
	 * @param int $minLevel = 1
	 * @param int $maxLevel = -1
	 * @param bool $nullable = false
	 *
	 * @return bool
	 */
	public static function Email ($key, $minLevel = 1, $maxLevel = -1, $nullable = false) {
		if ($nullable && $key == null) return true;
		if (!is_string($key)) return false;

		return preg_match('#^(\S+)\@([^@.]+\.){' . ($minLevel < 0 ? 0 : (int)$minLevel) . ',' . ($maxLevel < 0 ? '' : (int)$maxLevel) . '}$#', $key . '.');
	}

	/**
	 * @param $key
	 * @param bool $nullable = false
	 *
	 * @return bool
	 */
	public static function Phone ($key, $nullable = false) {
		if ($nullable && $key == null) return true;
		if (!is_string($key)) return false;

		return preg_match('#^\+[0-9]+$#', $key);
	}

	/**
	 * https://tools.ietf.org/html/rfc3986#appendix-B
	 * 
	 * @param $key
	 * @param bool $nullable = false
	 *
	 * @return bool
	 */
	public static function URI ($key, $nullable = false) {
		if ($nullable && $key == null) return true;
		if (!is_string($key)) return false;

		return preg_match('#^([a-zA-Z0-9\-\+\.]+)\:\/\/((.*)(\:(.*))?\@)?(([a-zA-Z0-9\.\-]*)|(\[[\d\:]*\]))(\:[\d]*)?\/([a-zA-Z\\\%\&\=\!\#\$\^\(\)\[\]\{\}\~\`]*)#Uis', $key);
	}

	/**
	 * @param $key
	 * @param bool $type = false
	 * @param bool $nullable = false
	 *
	 * @return bool
	 */
	public static function Bool ($key, $type = false, $nullable = false) {
		if ($nullable && $key == null) return true;

		return preg_match('#^(true|false)$#Ui', QuarkObject::Stringify($key)) && ($type ? is_bool($key) : true);
	}

	/**
	 * @param $key
	 * @param bool $type = false
	 * @param bool $nullable = false
	 *
	 * @return bool
	 */
	public static function Int ($key, $type = false, $nullable = false) {
		if ($nullable && $key == null) return true;

		return preg_match('#^([0-9]+)$#', $key) && ($type ? is_int($key) : true);
	}

	/**
	 * @param $key
	 * @param bool $type = false
	 * @param bool $nullable = false
	 *
	 * @return bool
	 */
	public static function Float ($key, $type = false, $nullable = false) {
		if ($nullable && $key == null) return true;

		return preg_match('#^([0-9]+\.[0-9]+)$#', $key) && ($type ? is_float($key) : true);
	}

	/**
	 * @param $key
	 * @param $values
	 * @param bool $nullable = false
	 * 
	 * @return bool
	 */
	public static function In ($key, $values, $nullable = false) {
		if ($nullable && $key == null) return true;

		return in_array($key, $values, true);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $field
	 * @param string[] $op = [QuarkModel::OPERATION_CREATE]
	 *
	 * @return bool
	 */
	public static function Unique (IQuarkModel $model, $field, $op = [QuarkModel::OPERATION_CREATE]) {
		/**
		 * @var QuarkModel $container
		 */
		$container = Quark::ContainerOfInstance($model);

		if ($container == null) {
			Quark::Log('[QuarkField::Unique] Cannot get container of given model instance of ' . get_class($model), Quark::LOG_WARN);
			return false;
		}
		
		return in_array($container->Operation(), $op)
			? QuarkModel::Count($model, array(
				$field => ($model->$field instanceof QuarkModel && $model->$field->Model() instanceof IQuarkLinkedModel
					? $model->$field->Unlink()
					: $model->$field
				)
			)) == 0
			: true;
	}

	/**
	 * @param $key
	 * @param $model
	 * @param bool $nullable = false
	 *
	 * @return bool
	 */
	public static function CollectionOf ($key, $model, $nullable = false) {
		if ($nullable && $key == null) return true;
		if (!is_array($key)) return false;

		foreach ($key as $i => &$item)
			if (!($item instanceof $model)) return false;

		return true;
	}

	/**
	 * @param $rules
	 * 
	 * @return bool
	 */
	public static function Rules ($rules) {
		if (!is_array($rules))
			return $rules == null ? true : (bool)$rules;

		$ok = true;

		foreach ($rules as $i => &$rule)
			$ok = $ok && $rule;

		unset($i, $rule);

		return $ok;
	}

	/**
	 * @param bool $rule
	 * @param QuarkLocalizedString $message = null
	 * @param string $field = ''
	 *
	 * @return bool
	 */
	public static function Assert ($rule, QuarkLocalizedString $message = null, $field = '') {
		if (!$rule && $message != null)
			self::$_errors[] = new QuarkKeyValuePair($field, $message);

		return $rule;
	}
	
	/**
	 * @param bool $rule
	 * @param string|array $message = ''
	 * @param string $field = ''
	 *
	 * @return bool
	 */
	public static function LocalizedAssert ($rule, $message = '', $field = '') {
		return self::Assert(
			$rule,
			is_array($message)
				? QuarkLocalizedString::Dictionary($message)
				: QuarkLocalizedString::DictionaryFromKey($message),
			$field
		);
	}

	/**
	 * @param string $language = QuarkLanguage::ANY
	 * @param bool $fields = false
	 *
	 * @return string[]
	 */
	public static function ValidationErrors ($language = QuarkLanguage::ANY, $fields = false) {
		$out = array();
		$key = null;
		$value = null;

		foreach (self::$_errors as $i => &$error) {
			$key = $error->Key();
			$value = $error->Value()->Of($language);

			if ($fields) {
				$out[$key] .= '; ' . $value;
				$out[$key] = trim($out[$key], '; ');
			}
			else $out[] = $value;
		}

		unset($i, $error, $key, $value);

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

	/**
	 * @param int|string $length = '*'
	 * @param bool $nullTerm = true
	 *
	 * @return string
	 */
	public static function BinaryString ($length = '*', $nullTerm = true) {
		return ($nullTerm ? 'a' : 'A') . $length;
	}

	/**
	 * @param int|string $length = '*'
	 * @param bool $bigEndian = true
	 *
	 * @return string
	 */
	public static function BinaryHex ($length = '*', $bigEndian = true) {
		return ($bigEndian ? 'H' : 'h') . $length;
	}

	/**
	 * @param bool $signed = true
	 *
	 * @return string
	 */
	public static function BinaryChar ($signed = true) {
		return $signed ? 'c' : 'C';
	}

	/**
	 * @param bool $signed = true
	 *
	 * @return string
	 */
	public static function BinaryShortMachine ($signed = true) {
		return $signed ? 's' : 'S';
	}

	/**
	 * @param bool $bigEndian = true
	 *
	 * @return string
	 */
	public static function BinaryShort ($bigEndian = true) {
		return $bigEndian ? 'n' : 'v';
	}

	/**
	 * @param bool $signed = true
	 *
	 * @return string
	 */
	public static function BinaryInteger ($signed = true) {
		return $signed ? 'i' : 'I';
	}

	/**
	 * @param bool $signed = true
	 *
	 * @return string
	 */
	public static function BinaryLongMachine ($signed = true) {
		return $signed ? 'l' : 'L';
	}

	/**
	 * @param bool $bigEndian = true
	 *
	 * @return string
	 */
	public static function BinaryLong ($bigEndian = true) {
		return $bigEndian ? 'N' : 'V';
	}

	/**
	 * @param bool $signed = true
	 *
	 * @return string
	 */
	public static function BinaryLongLongMachine ($signed = true) {
		return $signed ? 'q' : 'Q';
	}

	/**
	 * @param bool $bigEndian = true
	 *
	 * @return string
	 */
	public static function BinaryLongLong ($bigEndian = true) {
		return $bigEndian ? 'J' : 'P';
	}

	/**
	 * @param bool $bigEndian = true
	 *
	 * @return string
	 */
	public static function BinaryFloat ($bigEndian = true) {
		return func_num_args() < 2 ? 'f' : ($bigEndian ? 'G' : 'g');
	}

	/**
	 * @param bool $bigEndian = true
	 *
	 * @return string
	 */
	public static function BinaryDouble ($bigEndian = true) {
		return func_num_args() < 2 ? 'd' : ($bigEndian ? 'E' : 'e');
	}

	/**
	 * @param int|string $length = '*'
	 *
	 * @return string
	 */
	public static function BinaryByteBackup ($length = '*') {
		return 'X' . $length;
	}

	/**
	 * @param int|string $length = '*'
	 *
	 * @return string
	 */
	public static function BinaryNull ($length = '*') {
		return 'x' . $length;
	}

	/**
	 * @param int|string $length = '*'
	 * @param bool $fill = true
	 *
	 * @return string
	 */
	public static function BinaryNullFill ($length = '*', $fill = true) {
		return ($fill ? '@' : 'Z') . $length;
	}
}

/**
 * Class QuarkLocalizedString
 *
 * @package Quark
 */
class QuarkLocalizedString implements IQuarkModel, IQuarkLinkedModel, IQuarkModelWithBeforeExtract {
	const EXTRACT_CURRENT = 'localized.extract.current';
	const EXTRACT_ANY = 'localized.extract.any';
	const EXTRACT_VALUES = 'localized.extract.values';
	const EXTRACT_FULL = 'localized.extract.full';

	/**
	 * @var object $values = null
	 */
	public $values = null;

	/**
	 * @var string $default = QuarkLanguage::ANY
	 */
	public $default = QuarkLanguage::ANY;

	/**
	 * @param string $value
	 * @param string $language = QuarkLanguage::ANY
	 * @param string $default = QuarkLanguage::ANY
	 */
	public function __construct ($value = '', $language = QuarkLanguage::ANY, $default = QuarkLanguage::ANY) {
		$this->values = new \stdClass();
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

		if (isset($this->values->$language))
			return (string)$this->values->$language;

		$family = explode('-', $language);
		$language = $family[0];

		if (isset($this->values->$language))
			return (string)$this->values->$language;

		return isset($this->values->$default) ? $this->values->$default : '';
	}

	/**
	 * @param string $language
	 *
	 * @return bool
	 */
	public function ExistsValue ($language) {
		return isset($this->values->$language);
	}

	/**
	 * @param string $value = ''
	 *
	 * @return string
	 */
	public function Current ($value = '') {
		return $this->Of(Quark::CurrentLanguage(), func_num_args() != 0 && is_scalar($value) ? $value : null);
	}

	/**
	 * @param string $default = ''
	 *
	 * @return string
	 */
	public function CurrentOrDefault ($default = '') {
		$current = $this->Current();

		return $current == '' ? $default : $current;
	}

	/**
	 * @param $data = []
	 *
	 * @return string
	 */
	public function CurrentTemplated ($data = []) {
		return QuarkView::TemplateString($this->Current(), $data);
	}

	/**
	 * @param string $value = ''
	 *
	 * @return string
	 */
	public function Any ($value = '') {
		return $this->Of(QuarkLanguage::ANY, func_num_args() != 0 && is_scalar($value) ? $value : null);
	}

	/**
	 * @return string
	 */
	public function ControlValue () {
		return base64_encode(json_encode($this->values, JSON_UNESCAPED_UNICODE));
	}

	/**
	 * @param callable $assert = null
	 * @param callable $onEmpty = null
	 *
	 * @return bool
	 */
	public function Assert (callable $assert = null, callable $onEmpty = null) {
		if ($assert == null) return true;

		$out = true;
		$empty = true;
		$_empty = null;

		foreach ($this->values as $language => &$value) {
			$ok = $assert($value, $language);
			$out &= $ok === null ? true : $ok;
			$empty = false;
		}

		unset($language, $value);

		if ($empty && $onEmpty != null) {
			$_empty = $onEmpty();
			$_empty = $_empty === null ? true : $_empty;
		}

		return $empty && $onEmpty != null ? $_empty : $out;
	}

	/**
	 * @param array|object $dictionary = []
	 * @param string $default = QuarkLanguage::ANY
	 *
	 * @return QuarkLocalizedString
	 */
	public static function Dictionary ($dictionary = [], $default = QuarkLanguage::ANY) {
		if (!is_array($dictionary) && !is_object($dictionary)) return null;

		$str = new self('', QuarkLanguage::ANY, $default);
		$str->values = (object)$dictionary;

		return $str;
	}
	
	/**
	 * @param string $key = ''
	 *
	 * @return QuarkLocalizedString
	 */
	public static function DictionaryFromKey ($key = '') {
		$locale = Quark::Config()->LocalizationDictionaryOf($key);
		
		if ($locale == null) return null;
		
		$str = new self('', QuarkLanguage::ANY, QuarkLanguage::ANY);
		$str->values = $locale;

		return $str;
	}

	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'values' => new \stdClass(),
			'default' => QuarkLanguage::ANY
		);
	}

	/**
	 * @return void
	 */
	public function Rules () { }

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Link ($raw) {
		if ($raw instanceof QuarkLocalizedString)
			return new QuarkModel($this, $raw);
		
		if (!is_scalar($raw)) return null;
		
		$values = json_decode(strlen($raw) != 0 && $raw[0] == '{' ? $raw : base64_decode($raw));
		
		return new QuarkModel($this, array(
			'values' => json_last_error() == 0
				? $values
				: (Quark::Config()->LocalizationParseFailedToAny()
					? (object)array(QuarkLanguage::ANY => $raw)
					: null
				),
			'default' => $this->default
		));
	}

	/**
	 * @return mixed
	 */
	public function Unlink () {
		return json_encode($this->values, JSON_UNESCAPED_UNICODE);
	}

	/**
	 * @param array $fields
	 * @param bool $weak
	 *
	 * @return mixed
	 */
	public function BeforeExtract ($fields, $weak) {
		$extract = $this->_extract($fields);
		if ($extract !== null) return $extract;

		$extract = $this->_extract(Quark::Config()->LocalizationExtract());
		if ($extract !== null) return $extract;

		return $this->Of($this->default);
	}

	/**
	 * @param $criteria
	 *
	 * @return string|object|null
	 */
	private function _extract ($criteria) {
		switch ($criteria) {
			case self::EXTRACT_CURRENT: return $this->Current(); break;
			case self::EXTRACT_ANY: return $this->Of(QuarkLanguage::ANY); break;
			case self::EXTRACT_VALUES: return $this->values; break;
			case self::EXTRACT_FULL: return $this; break;
			default: break;
		}
		
		return null;
	}
}

/**
 * Class QuarkSecuredString
 *
 * @package Quark
 */
class QuarkSecuredString implements IQuarkModel, IQuarkLinkedModel, IQuarkPolymorphicModel {
	/**
	 * @var string $_val = ''
	 */
	private $_val = '';

	/**
	 * @var string $_key = ''
	 */
	private $_key = '';

	/**
	 * @var array $_rules = []
	 */
	private $_rules = array();

	/**
	 * @var QuarkCipher $_cipher = null
	 */
	private $_cipher = null;

	/**
	 * @var string $_extract = ''
	 */
	private $_extract = '';

	/**
	 * @var bool $_ciphered = false
	 */
	private $_ciphered = false;

	/**
	 * @param string $key = ''
	 * @param array $rules = []
	 * @param IQuarkEncryptionProtocol $cipher = null
	 */
	public function __construct ($key = '', $rules = [], IQuarkEncryptionProtocol $cipher = null) {
		$this->_key = $key;
		$this->_rules = $rules;
		$this->_cipher = new QuarkCipher($cipher ? $cipher : new QuarkOpenSSLCipher());
	}

	/**
	 * @return string
	 */
	public function __toString () {
		return (string)$this->_val;
	}
	
	/**
	 * @return void
	 */
	public function Fields () { }

	/**
	 * @return mixed
	 */
	public function Rules () {
		return $this->_rules;
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Link ($raw) {
		$string = new self($this->_key);
		$string->_val = $raw;
		$string->_ciphered = true;
		$string->_extract = $this->_extract;

		return new QuarkModel($string);
	}

	/**
	 * @return mixed
	 */
	public function Unlink () {
		if ($this->_ciphered)
			$this->Decipher();

		return $this->_cipher->Encrypt($this->_key, $this->_val);
	}

	/**
	 * @return mixed
	 */
	public function PolymorphicExtract () {
		if ($this->_ciphered)
			$this->Decipher();
		
		return (string)($this->_extract
			? $this->_cipher->Encrypt($this->_extract, $this->_val)
			: $this->_val);
	}

	/**
	 * @return string
	 */
	public function Decipher () {
		$out = $this->_cipher->Decrypt($this->_key, $this->_val);
		$this->_ciphered = false;
		
		return $this->_val = $out === false ? $this->_val : $out;
	}

	/**
	 * @param string $keyStore = ''
	 * @param string $keyExtract = ''
	 * @param array $rules = []
	 * @param IQuarkEncryptionProtocol $cipher = null
	 *
	 * @return QuarkSecuredString
	 */
	public static function WithEncryptedExtract ($keyStore = '', $keyExtract = '', $rules = [], IQuarkEncryptionProtocol $cipher = null) {
		$string = new self($keyStore, $rules, $cipher);
		$string->_extract = $keyExtract;

		return $string;
	}
}

/**
 * Class QuarkDate
 *
 * @package Quark
 */
class QuarkDate implements IQuarkModel, IQuarkLinkedModel, IQuarkModelWithAfterPopulate, IQuarkModelWithBeforeExtract {
	const NOW = 'now';
	const UTC = 'UTC';
	const GMT = 'UTC';
	const CURRENT = '';

	const FORMAT_ISO = 'Y-m-d H:i:s';
	const FORMAT_ISO_FULL = 'Y-m-d H:i:s.u';
	const FORMAT_MS_DOS = '___quark_ms_dos___';
	const FORMAT_HTTP_DATE = 'D, d M Y H:i:s'; // https://stackoverflow.com/a/21121453/2097055

	const PRECISE_YEARS = 'Y-01-01 00:00:00';
	const PRECISE_MONTHS = 'Y-m-01 00:00:00';
	const PRECISE_DAYS = 'Y-m-d 00:00:00';
	const PRECISE_HOURS = 'Y-m-d H:00:00';
	const PRECISE_MINUTES = 'Y-m-d H:i:00';
	const PRECISE_SECONDS = 'Y-m-d H:i:s';

	const UNIT_YEAR = 'Y';
	const UNIT_MONTH = 'm';
	const UNIT_DAY = 'd';
	const UNIT_HOUR = 'H';
	const UNIT_MINUTE = 'i';
	const UNIT_SECOND = 's';
	const UNIT_MICROSECOND = 'u';

	const UNKNOWN_YEAR = '0000';

	const LIMIT_UNIX = 1970;
	const LIMIT_MS_DOS = 1980;

	/**
	 * @var IQuarkCulture|QuarkCultureISO $_culture
	 */
	private $_culture;

	/**
	 * @var \DateTime $_date
	 */
	private $_date;

	/**
	 * @var string $_timezone = self::CURRENT
	 */
	private $_timezone = self::CURRENT;
	
	/**
	 * @var bool $_fromTimestamp = false
	 */
	private $_fromTimestamp = false;

	/**
	 * @var bool $_isNull = false
	 */
	private $_isNull = false;

	/**
	 * @var bool $_nullable = false
	 */
	private $_nullable = false;

	/**
	 * @var array $_components
	 */
	private static $_components = array(
		'Y' => '([\d]{4})',
		'm' => '([\d]{2})',
		'd' => '([\d]{2})',
		'H' => '([\d]{2})',
		'i' => '([\d]{2})',
		's' => '([\d]{2})',
		'u' => '([\d]{6})'
	);
	
	/**
	 * @param IQuarkCulture $culture
	 * @param string $value = self::NOW
	 */
	public function __construct (IQuarkCulture $culture = null, $value = self::NOW) {
		$this->_culture = $culture ? $culture : Quark::Config()->Culture();
		$this->Value($value);
	}

	/**
	 * @return string
	 */
	public function __toString () {
		return (string)$this->DateTime();
	}

	/**
	 * cloning behavior
	 */
	public function __clone () {
		if ($this->_date != null)
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
		if (func_num_args() != 0) {
			if (is_numeric($value)) {
				$this->_date = new \DateTime();
				$this->_date->setTimestamp((int)$value);
				$this->_fromTimestamp = true;
			}
			elseif (is_string($value)) {
				try {
					$this->_date = new \DateTime($value);
				}
				catch (\Exception $e) {
					Quark::Log('Can not parse date from given string "' . $value . '". DateTime error: ' . $e->getMessage(), Quark::LOG_WARN);
				}
			}
			else $this->_isNull = true;
		}

		return $this->_date;
	}

	/**
	 * @return string
	 */
	public function Timezone () {
		return $this->_timezone;
	}

	/**
	 * @return string
	 */
	public function DateTime () {
		return $this->_date == null ? null : $this->_date->format($this->_culture->DateTimeFormat());
	}

	/**
	 * @return string
	 */
	public function Date () {
		return $this->_date == null ? null : $this->_date->format($this->_culture->DateFormat());
	}

	/**
	 * @return string
	 */
	public function Time () {
		return $this->_date == null ? null : $this->_date->format($this->_culture->TimeFormat());
	}

	/**
	 * @return int
	 */
	public function Timestamp () {
		return $this->_date == null ? null : $this->_date->getTimestamp();
	}

	/**
	 * @param QuarkDate $with = null
	 * @param bool $interval = false
	 *
	 * @return int|QuarkDateInterval
	 */
	public function Interval (QuarkDate $with = null, $interval = false) {
		if ($with == null) return 0;

		$start = $this->_date->getTimestamp();
		$end = $with->Value()->getTimestamp();
		
		$out = $end - $start;
		
		return $interval ? QuarkDateInterval::FromSeconds($out) : $out;
	}

	/**
	 * @param string|QuarkDateInterval $offset
	 * @param bool $copy = false
	 *
	 * @return QuarkDate
	 */
	public function Offset ($offset, $copy = false) {
		if ($this->_date == null) return null;

		$out = $copy ? clone $this : $this;

		if (!@$out->_date->modify($offset instanceof QuarkDateInterval ? $offset->Modifier() : $offset))
			Quark::Log('[QuarkDate] Invalid value for $offset argument. Error: ' . QuarkException::LastError(), Quark::LOG_WARN);

		return $out;
	}

	/**
	 * @param QuarkDate $then = null
	 * @param int $offset = 0
	 *
	 * @return bool
	 */
	public function Earlier (QuarkDate $then = null, $offset = 0) {
		if ($then == null)
			$then = self::Now();

		return $this->Interval($then) > $offset;
	}

	/**
	 * @param QuarkDate $then = null
	 * @param int $offset = 0
	 *
	 * @return bool
	 */
	public function EarlierEqual (QuarkDate $then = null, $offset = 0) {
		if ($then == null)
			$then = self::Now();

		return $this->Interval($then) >= $offset;
	}

	/**
	 * @param QuarkDate $then = null
	 * @param int $offset = 0
	 *
	 * @return bool
	 */
	public function Equal (QuarkDate $then = null, $offset = 0) {
		if ($then == null)
			$then = self::Now();

		return $this->Interval($then) == $offset;
	}

	/**
	 * @param QuarkDate $then = null
	 * @param int $offset = 0
	 *
	 * @return bool
	 */
	public function LaterEqual (QuarkDate $then = null, $offset = 0) {
		if ($then == null)
			$then = self::Now();

		return $this->Interval($then) <= $offset;
	}

	/**
	 * @param QuarkDate $then = null
	 * @param int $offset = 0
	 *
	 * @return bool
	 */
	public function Later (QuarkDate $then = null, $offset = 0) {
		if ($then == null)
			$then = self::Now();

		return $this->Interval($then) < $offset;
	}

	/**
	 * @param QuarkDate $then = null
	 * @param int $offset = 0
	 *
	 * @return bool
	 */
	public function NotEqual (QuarkDate $then = null, $offset = 0) {
		if ($then == null)
			$then = self::Now();

		return $this->Interval($then) != $offset;
	}

	/**
	 * @param string $format = ''
	 *
	 * @return string
	 */
	public function Format ($format = '') {
		if ($format == self::FORMAT_MS_DOS) {
			$date = explode('-', $this->_date->format('Y-m-d-H-i-s'));
			$limits = array(self::LIMIT_MS_DOS, 1, 1, 0, 0, 0);
			$modifiers = array(self::LIMIT_MS_DOS, 0, 0, 0, 0, 0);
			$offsets = array(25, 21, 16, 11, 5, 1);
			$out = 0;

			foreach ($date as $i => &$component) {
				$sum = (($date[0] < self::LIMIT_MS_DOS ? $limits[$i] : (int)($component[0] == '0' ? $component[1] : $component)) - $modifiers[$i]);
				$out |= $i == 5 ? $sum >> $offsets[$i] : $sum << $offsets[$i];
			}

			unset($i, $component, $date);

			return $out;
		}

		return $this->_date->format($format);
	}

	/**
	 * @param string $level = self::PRECISE_SECONDS
	 *
	 * @return QuarkDate
	 */
	public function Precise ($level = self::PRECISE_SECONDS) {
		$out = clone $this;
		$out->Value($out->Format($level));

		return $out;
	}

	/**
	 * @param string $timezone = self::CURRENT
	 * @param bool $copy = false
	 *
	 * @return QuarkDate
	 */
	public function InTimezone ($timezone = self::CURRENT, $copy = false) {
		$this->_timezone = $timezone;
		$offset = self::TimezoneOffset($timezone);

		return $this->Offset(($offset < 0 ? '-' : '') . $offset . ' seconds', $copy);
	}

	/**
	 * @param bool $store = false
	 *
	 * @return QuarkDate
	 */
	public function AsTimestamp ($store = true) {
		$this->_fromTimestamp = $store;
		
		return $this;
	}

	/**
	 * @param bool $is = false
	 *
	 * @return bool
	 */
	public function IsNull ($is = false) {
		if (func_num_args() != 0)
			$this->_isNull = $is;

		return $this->_isNull;
	}

	/**
	 * @param bool $is = false
	 *
	 * @return $this
	 */
	public function Nullable ($is = false) {
		if (func_num_args() != 0)
			$this->_nullable = $is;

		return $this;
	}

	/**
	 * @param QuarkDate $now = null
	 *
	 * @return QuarkDateInterval[]
	 */
	public function Timezones (QuarkDate &$now = null) {
		return self::TimezoneListByNow($this, $now);
	}

	/**
	 * @param QuarkDate $start
	 * @param QuarkDate $end
	 *
	 * @return bool
	 */
	public function IntervalMatch (QuarkDate &$start, QuarkDate &$end) {
		return $this->Interval($start) < 0 && $this->Interval($end) >= 0;
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
	 * @deprecated
	 *
	 * @return string
	 */
	public static function NowUSecGMT () {
		return gmdate('Y-m-d H:i:s') . '.' . self::Microtime();
	}

	/**
	 * @return string
	 */
	public static function NowUSecUTC () {
		return gmdate('Y-m-d H:i:s') . '.' . self::Microtime();
	}

	/**
	 * @param string $format = ''
	 *
	 * @return QuarkDate
	 */
	public static function Now ($format = '') {
		$date = self::FromFormat($format, self::NowUSec());
		$date->_timezone = self::CURRENT;

		return $date;
	}

	/**
	 * @param string $format = ''
	 *
	 * @return QuarkDate
	 */
	public static function NowUTC ($format = '') {
		$date = self::FromFormat($format, self::NowUSecGMT());
		$date->_timezone = self::GMT;

		return $date;
	}

	/**
	 * @deprecated
	 *
	 * @param string $format = ''
	 *
	 * @return QuarkDate
	 */
	public static function GMTNow ($format = '') {
		$date = self::FromFormat($format, self::NowUSecGMT());
		$date->_timezone = self::GMT;

		return $date;
	}

	/**
	 * @param string $date
	 * @param string $timezone = self::CURRENT
	 *
	 * @return QuarkDate
	 */
	public static function Of ($date, $timezone = self::CURRENT) {
		return (new self(null, $date, $timezone))->InTimezone($timezone);
	}

	/**
	 * @deprecated
	 *
	 * @param string $date
	 * @param bool $ignoreTimezone = false
	 *
	 * @return QuarkDate
	 */
	public static function GMTOf ($date, $ignoreTimezone = false) {
		$out = self::Of($date, self::GMT);

		return $ignoreTimezone && $out != null ? self::GMTOf($out->Format('Y-m-d H:i:s')) : $out;
	}

	/**
	 * @param QuarkDate|string $date
	 * @param string $timezone = self::GMT
	 *
	 * @return QuarkDate
	 */
	public static function From ($date, $timezone = self::GMT) {
		return $date instanceof QuarkDate ? $date : self::Of($date, $timezone);
	}

	/**
	 * @param string $format
	 * @param string $value = self::NOW
	 *
	 * @return QuarkDate
	 */
	public static function FromFormat ($format, $value = self::NOW) {
		return new self(QuarkCultureCustom::Format($format), $value);
	}

	/**
	 * @param int $time = 0
	 *
	 * @return QuarkDate
	 */
	public static function FromTimestamp ($time = 0) {
		$date = new self();
		$date->_date->setTimestamp($time);
		$date->_fromTimestamp = true;

		return $date;
	}

	/**
	 * https://github.com/splitbrain/php-archive/blob/master/src/Zip.php
	 *
	 * @param string $date = ''
	 * @param string $time = ''
	 *
	 * @return QuarkDate
	 */
	public static function FromMSDOSDate ($date = '', $time = '') {
		$year = (($date & 0xFE00) >> 9) + 1980;
		$month = ($date & 0x01E0) >> 5;
		$day = $date & 0x001F;
		$hour = ($time & 0xF800) >> 11;
		$minute = ($time & 0x07E0) >> 5;
		$seconds = ($time & 0x001F) << 1;

		return self::FromTimestamp(mktime($hour, $minute, $seconds, $month, $day, $year));
	}

	/**
	 * @return object
	 */
	public function ToMSDOSDate () {
		$date = dechex($this->Format(QuarkDate::FORMAT_MS_DOS));

		return (object)unpack('vTime/vDate', pack('H*', $date[6] . $date[7] . $date[4] . $date[5] . $date[2] . $date[3] . $date[0] . $date[1]));
	}

	/**
	 * @param string $timezone
	 *
	 * @return int
	 */
	public static function TimezoneOffset ($timezone = self::CURRENT) {
		if ($timezone == self::CURRENT) {
			$timezone = date_default_timezone_get();

			if (!$timezone) {
				date_default_timezone_set(self::GMT);
				$timezone = self::GMT;
			}
		}

		return (new \DateTimeZone($timezone))->getOffset(self::GMTNow()->Value());
	}

	/**
	 * @return QuarkDateInterval[]
	 */
	public static function TimezoneList () {
		$zones = \DateTimeZone::listIdentifiers();
		$out = array();

		foreach ($zones as $i => &$zone)
			$out[$zone] = QuarkDateInterval::FromSeconds(self::TimezoneOffset($zone));

		unset($i, $zone, $zones);

		return $out;
	}

	/**
	 * @param QuarkDate $date = null
	 * @param QuarkDate $now = null
	 *
	 * @return QuarkDateInterval[]
	 */
	public static function TimezoneListByNow (QuarkDate &$date = null, QuarkDate &$now = null) {
		if ($now == null)
			$now = self::GMTNow();

		$out = self::TimezoneList();

		foreach ($out as $zone => &$interval)
			if ($now->InTimezone($zone)->NotEqual($date))
				unset($out[$zone]);

		return $out;
	}

	/**
	 * @param int $offset = 0
	 *
	 * @return QuarkDateInterval[]
	 */
	public static function TimezoneListByOffset ($offset = 0) {
		$out = self::TimezoneList();

		foreach ($out as $zone => &$interval)
			if ($interval->Seconds() != $offset)
				unset($out[$zone]);

		return $out;
	}

	/**
	 * @param QuarkDate $start = null
	 * @param QuarkDate $end = null
	 *
	 * @return QuarkDate
	 */
	public static function Random (QuarkDate &$start = null, QuarkDate &$end = null) {
		if ($start == null) $start = self::FromTimestamp(0);
		if ($end == null) $end = self::FromTimestamp(PHP_INT_MAX);

		return self::FromTimestamp(mt_rand(abs($start->Timestamp()), abs($end->Timestamp())));
	}

	/**
	 * @param string $date = ''
	 * @param string $in = ''
	 * @param string $out = ''
	 *
	 * @return mixed
	 */
	public static function Convert ($date = '', $in = '', $out = '') {
		$replace = array();
		
		$in = preg_replace_callback('#[a-zA-Z]#Uis', function ($item) use(&$replace) {
			if (!isset(self::$_components[$item[0]])) return $item[0];
			
			$replace[$item[0]] = '$' . (sizeof($replace) + 1);
			
			return self::$_components[$item[0]];
		}, $in);
		
		$out = preg_replace_callback('#[a-zA-Z]#Uis', function ($item) use($replace) {
			return isset($replace[$item[0]]) ? $replace[$item[0]] : $item[0];
		}, $out);
		
		return preg_replace('#' . $in . '#Uis', $out, $date);
	}

	/**
	 * @return array
	 */
	public static function Units () {
		// TODO: add formatting support
		return array(
			self::UNIT_YEAR => array(0, 4),
			self::UNIT_MONTH => array(5, 2),
			self::UNIT_DAY => array(8, 2),
			self::UNIT_HOUR => array(11, 2),
			self::UNIT_MINUTE => array(14, 2),
			self::UNIT_SECOND => array(17, 2),
			self::UNIT_MICROSECOND => array(20, 6)
		);
	}

	/**
	 * @return void
	 */
	public function Fields () { }

	/**
	 * @return void
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
		return $this->_nullable && $this->_isNull
			? null
			: ($this->_fromTimestamp ? $this->Timestamp() : $this->DateTime());
	}

	/**
	 * @param $raw
	 *
	 * @return void
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
 * Class QuarkDateInterval
 *
 * @package Quark
 */
class QuarkDateInterval {
	const ROUND_CEIL = 'ceil';
	const ROUND_FLOOR = 'floor';
	
	const UNIT_YEAR = 'year';
	const UNIT_MONTH = 'month';
	const UNIT_DAY = 'day';
	const UNIT_HOUR = 'hour';
	const UNIT_MINUTE = 'minute';
	const UNIT_SECOND = 'second';
	
	const SECONDS_IN_YEAR = 31536000;
	const SECONDS_IN_MONTH = 2678400;
	const SECONDS_IN_WEEK = 604800;
	const SECONDS_IN_DAY = 86400;
	const SECONDS_IN_HOUR = 3600;
	const SECONDS_IN_MINUTE = 60;
	const SECONDS_IN_SECOND = 1;
	
	const MINUTES_IN_YEAR = 525600;
	const MINUTES_IN_MONTH = 44640;
	const MINUTES_IN_WEEK = 10080;
	const MINUTES_IN_DAY = 1440;
	const MINUTES_IN_HOUR = 60;
	const MINUTES_IN_MINUTE = 1;
	
	const HOURS_IN_YEAR = 8760;
	const HOURS_IN_MONTH = 744;
	const HOURS_IN_WEEK = 168;
	const HOURS_IN_DAY = 24;
	const HOURS_IN_HOUR = 1;
	
	const DAYS_IN_YEAR = 365;
	const DAYS_IN_MONTH = 31;
	const DAYS_IN_WEEK = 7;
	const DAYS_IN_DAY = 1;

	const WEEKS_IN_YEAR = 52;
	const WEEKS_IN_MONTH = 4;
	const WEEKS_IN_WEEK = 1;
	
	const MONTHS_IN_YEAR = 12;
	const MONTHS_IN_MONTH = 1;
	
	const YEARS_IN_YEAR = 1;
	
	/**
	 * @var array $_dividers
	 */
	private static $_dividers = array(
		self::UNIT_SECOND => array(
			self::UNIT_YEAR => self::SECONDS_IN_YEAR,
			self::UNIT_MONTH => self::SECONDS_IN_MONTH,
			self::UNIT_DAY => self::SECONDS_IN_DAY,
			self::UNIT_HOUR => self::SECONDS_IN_HOUR,
			self::UNIT_MINUTE => self::SECONDS_IN_MINUTE,
			self::UNIT_SECOND => self::SECONDS_IN_SECOND
		),
		self::UNIT_MINUTE => array(
			self::UNIT_YEAR => self::MINUTES_IN_YEAR,
			self::UNIT_MONTH => self::MINUTES_IN_MONTH,
			self::UNIT_DAY => self::MINUTES_IN_DAY,
			self::UNIT_HOUR => self::MINUTES_IN_HOUR,
			self::UNIT_MINUTE => self::MINUTES_IN_MINUTE
		),
		self::UNIT_HOUR => array(
			self::UNIT_YEAR => self::HOURS_IN_YEAR,
			self::UNIT_MONTH => self::HOURS_IN_MONTH,
			self::UNIT_DAY => self::HOURS_IN_DAY,
			self::UNIT_HOUR => self::HOURS_IN_HOUR
		),
		self::UNIT_DAY => array(
			self::UNIT_YEAR => self::DAYS_IN_YEAR,
			self::UNIT_MONTH => self::DAYS_IN_MONTH,
			self::UNIT_DAY => self::DAYS_IN_DAY
		),
		self::UNIT_MONTH => array(
			self::UNIT_YEAR => self::MONTHS_IN_YEAR,
			self::UNIT_MONTH => self::MONTHS_IN_MONTH
		),
		self::UNIT_YEAR => array(
			self::UNIT_YEAR => self::YEARS_IN_YEAR
		)
	);
	
	/**
	 * @var array $_order
	 */
	private static $_order = array(
		self::UNIT_YEAR => 0,
		self::UNIT_MONTH => 1,
		self::UNIT_DAY => 2,
		self::UNIT_HOUR => 3,
		self::UNIT_MINUTE => 4,
		self::UNIT_SECOND => 5,
	);

	/**
	 * @var array $_units
	 */
	private static $_units = array(
		self::UNIT_YEAR,
		self::UNIT_MONTH,
		self::UNIT_DAY,
		self::UNIT_HOUR,
		self::UNIT_MINUTE,
		self::UNIT_SECOND,
	);
	
	/**
	 * @var int $years = 0
	 */
	public $years = 0;
	
	/**
	 * @var int $months = 0
	 */
	public $months = 0;
	
	/**
	 * @var int $days = 0
	 */
	public $days = 0;
	
	/**
	 * @var int $hours = 0
	 */
	public $hours = 0;
	
	/**
	 * @var int $minutes = 0
	 */
	public $minutes = 0;
	
	/**
	 * @var int $seconds = 0
	 */
	public $seconds = 0;

	/**
	 * @var bool $_positive = true
	 */
	private $_positive = true;
	
	/**
	 * @param int $years = 0
	 * @param int $months = 0
	 * @param int $days = 0
	 * @param int $hours = 0
	 * @param int $minutes = 0
	 * @param int $seconds = 0
	 * @param bool $positive = true
	 */
	public function __construct ($years = 0, $months = 0, $days = 0, $hours = 0, $minutes = 0, $seconds = 0, $positive = true) {
		$this->years = $years;
		$this->months = $months;
		$this->days = $days;
		
		$this->hours = $hours;
		$this->minutes = $minutes;
		$this->seconds = $seconds;
		
		$this->_positive = $positive;
	}
	
	/**
	 * @param bool $ceil = true
	 * 
	 * @return int|float
	 */
	public function Years ($ceil = true) {
		$full = $this->months + $this->days + $this->hours + $this->minutes + $this->seconds;
		
		return $this->years + (int)($ceil && $full != 0);
	}
	
	/**
	 * @param bool $ceil = true
	 * 
	 * @return int|float
	 */
	public function Months ($ceil = true) {
		$full = $this->days + $this->hours + $this->minutes + $this->seconds;
		$months = $this->years * self::MONTHS_IN_YEAR;
		
		return $months + $this->months + (int)($ceil && $full != 0);
	}
	
	/**
	 * @param bool $ceil = true
	 * 
	 * @return int|float
	 */
	public function Days ($ceil = true) {
		$full = $this->hours + $this->minutes + $this->seconds;
		$days = $this->years * self::DAYS_IN_YEAR
			  + $this->months * self::DAYS_IN_MONTH;
		
		return $days + $this->days + (int)($ceil && $full != 0);
	}
	
	/**
	 * @param bool $ceil = true
	 * 
	 * @return int|float
	 */
	public function Hours ($ceil = true) {
		$full = $this->minutes + $this->seconds;
		$hours = $this->years * self::HOURS_IN_YEAR
			   + $this->months * self::HOURS_IN_MONTH
			   + $this->days * self::HOURS_IN_DAY;
		
		return $hours + $this->hours + (int)($ceil && $full != 0);
	}
	
	/**
	 * @param bool $ceil = true
	 * 
	 * @return int|float
	 */
	public function Minutes ($ceil = true) {
		$full = $this->seconds;
		$minutes = $this->years * self::MINUTES_IN_YEAR
				 + $this->months * self::MINUTES_IN_MONTH
				 + $this->days * self::MINUTES_IN_DAY
				 + $this->hours * self::MINUTES_IN_HOUR;
		
		return $minutes + $this->minutes + (int)($ceil && $full != 0);
	}
	
	/**
	 * @return int
	 */
	public function Seconds () {
		$seconds = $this->years * self::SECONDS_IN_YEAR
				 + $this->months * self::SECONDS_IN_MONTH
				 + $this->days * self::SECONDS_IN_DAY
				 + $this->hours * self::SECONDS_IN_HOUR
				 + $this->minutes * self::SECONDS_IN_MINUTE;
		
		return $seconds + $this->seconds;
	}

	/**
	 * @param bool $ceil = true
	 *
	 * @return int|float
	 */
	public function Weeks ($ceil = true) {
		$weeks = $this->Days(false) / 7;

		return $ceil ? ceil($weeks) : $weeks;
	}

	/**
	 * @return bool
	 */
	public function Positive () {
		return $this->_positive;
	}
	
	/**
	 * @param string $format = ''
	 * @param bool $sign = false
	 *
	 * @return string|null
	 */
	public function Format ($format = '', $sign = false) {
		return
			($sign ? ($this->_positive ? '+' : '-') : '') .
			QuarkDate::Convert(
				str_pad(abs($this->years), 4, '0', STR_PAD_LEFT) . '-' .
				str_pad(abs($this->months), 2, '0', STR_PAD_LEFT) . '-' .
				str_pad(abs($this->days), 2, '0', STR_PAD_LEFT) . ' ' .
				str_pad(abs($this->hours), 2, '0', STR_PAD_LEFT) . ':' .
				str_pad(abs($this->minutes), 2, '0', STR_PAD_LEFT) . ':' .
				str_pad(abs($this->seconds), 2, '0', STR_PAD_LEFT),
				'Y-m-d H:i:s',
				$format
			);
	}
	
	/**
	 * @return string
	 */
	public function Modifier () {
		$out = '';

		foreach (self::$_units as $i => &$unit)
			$out .= $this->{$unit . 's'} . ' ' . $unit . 's ';

		unset($i, $unit);

		return $out;
	}

	/**
	 * @param QuarkDateInterval|string $offset = ''
	 *
	 * @return QuarkDateInterval
	 */
	public function Offset ($offset = '') {
		$value = $offset instanceof QuarkDateInterval ? $offset->Modifier() : $offset;
		$units = implode('|', self::$_units);

		if (preg_match_all('#(\-|\+)?(\d+)\s*(' . $units . ')s?#Uis', $value, $modifiers, PREG_SET_ORDER) == 0) return $this;

		$seconds = 0;
		$interval = null;

		foreach ($modifiers as $i => &$modifier) {
			$interval = null;

			if ($modifier[3] == self::UNIT_YEAR) $interval = self::FromYears($modifier[2]);
			if ($modifier[3] == self::UNIT_MONTH) $interval = self::FromMonths($modifier[2]);
			if ($modifier[3] == self::UNIT_DAY) $interval = self::FromDays($modifier[2]);
			if ($modifier[3] == self::UNIT_HOUR) $interval = self::FromHours($modifier[2]);
			if ($modifier[3] == self::UNIT_MINUTE) $interval = self::FromMinutes($modifier[2]);
			if ($modifier[3] == self::UNIT_SECOND) $interval = self::FromSeconds($modifier[2]);

			if ($interval == null) continue;

			$seconds += ($modifier[1] != '-' ? 1 : -1) * $interval->Seconds();
		}

		unset($i, $modifier, $modifiers);

		if ($seconds == 0) return $this;

		$out = self::FromSeconds($this->Seconds() + $seconds);

		$this->years = $out->years;
		$this->months = $out->months;
		$this->days = $out->days;
		$this->hours = $out->hours;
		$this->minutes = $out->minutes;
		$this->seconds = $out->seconds;

		return $this;
	}

	/**
	 * @param int $rate = 1
	 *
	 * @return QuarkDateInterval
	 */
	public function Rate ($rate = 1) {
		return self::FromSeconds($this->Seconds() * $rate);
	}

	/**
	 * @param string $elapsed
	 * @param array $mappings
	 * @param string $unit
	 *
	 * @return string
	 */
	private function _elapsed ($elapsed, $mappings, $unit) {
		return $this->{$unit . 's'} . ' ' . (isset($mappings[$unit]) ? $mappings[$unit] : $unit . 's') . ',' . $elapsed;
	}

	/**
	 * @param string $now = ''
	 * @param array $mappings = []
	 * @param int $units = 1
	 *
	 * @return string
	 */
	public function Elapsed ($now = '', $mappings = [], $units = 1) {
		$elapsed = '';

		if ($this->seconds != 0) $elapsed = $this->_elapsed($elapsed, $mappings, self::UNIT_SECOND);
		if ($this->minutes != 0) $elapsed = $this->_elapsed($elapsed, $mappings, self::UNIT_MINUTE);
		if ($this->hours != 0) $elapsed = $this->_elapsed($elapsed, $mappings, self::UNIT_HOUR);
		if ($this->days != 0) $elapsed = $this->_elapsed($elapsed, $mappings, self::UNIT_DAY);
		if ($this->months != 0) $elapsed = $this->_elapsed($elapsed, $mappings, self::UNIT_MONTH);
		if ($this->years != 0) $elapsed = $this->_elapsed($elapsed, $mappings, self::UNIT_YEAR);

		return $elapsed ? implode(', ', array_slice(explode(',', trim($elapsed, ',')), 0, $units)) : $now;
	}
	
	/**
	 * @param string $interval = ''
	 *
	 * @return QuarkDateInterval
	 */
	public static function FromDate ($interval = '') {
		$date = new \DateTime($interval);
		
		return new self(
			(int)$date->format('Y'),
			(int)$date->format('m'),
			(int)$date->format('d'),
			(int)$date->format('H'),
			(int)$date->format('i'),
			(int)$date->format('s')
		);
	}
	
	/**
	 * @param int $interval = 0
	 *
	 * @return QuarkDateInterval
	 */
	public static function FromYears ($interval = 0) {
		return self::FromUnit(self::UNIT_YEAR, $interval);
	}
	
	/**
	 * @param int $interval = 0
	 *
	 * @return QuarkDateInterval
	 */
	public static function FromMonths ($interval = 0) {
		return self::FromUnit(self::UNIT_MONTH, $interval);
	}
	
	/**
	 * @param int $interval = 0
	 *
	 * @return QuarkDateInterval
	 */
	public static function FromDays ($interval = 0) {
		return self::FromUnit(self::UNIT_DAY, $interval);
	}
	
	/**
	 * @param int $interval = 0
	 *
	 * @return QuarkDateInterval
	 */
	public static function FromHours ($interval = 0) {
		return self::FromUnit(self::UNIT_HOUR, $interval);
	}
	
	/**
	 * @param int $interval = 0
	 *
	 * @return QuarkDateInterval
	 */
	public static function FromMinutes ($interval = 0) {
		return self::FromUnit(self::UNIT_MINUTE, $interval);
	}
	
	/**
	 * @param int $interval = 0
	 *
	 * @return QuarkDateInterval
	 */
	public static function FromSeconds ($interval = 0) {
		return self::FromUnit(self::UNIT_SECOND, $interval);
	}
	
	/**
	 * @param string $unit = self::UNIT_SECOND
	 * @param int $interval = 0
	 *
	 * @return QuarkDateInterval
	 */
	public static function FromUnit ($unit = self::UNIT_SECOND, $interval = 0) {
		$round = $interval < 0 ? self::ROUND_CEIL : self::ROUND_FLOOR;
		$order = isset(self::$_order[$unit]) ? self::$_order[$unit] : -1;
		
		$years   = $order >= 0 ? self::Calculate(self::UNIT_YEAR,   $unit, $round, $interval) : 0;
		$months  = $order >= 1 ? self::Calculate(self::UNIT_MONTH,  $unit, $round, $interval, $years) : 0;
		$days    = $order >= 2 ? self::Calculate(self::UNIT_DAY,    $unit, $round, $interval, $years, $months): 0;
		$hours   = $order >= 3 ? self::Calculate(self::UNIT_HOUR,   $unit, $round, $interval, $years, $months, $days): 0;
		$minutes = $order >= 4 ? self::Calculate(self::UNIT_MINUTE, $unit, $round, $interval, $years, $months, $days, $hours): 0;
		$seconds = $order >= 5 ? self::Calculate(self::UNIT_SECOND, $unit, $round, $interval, $years, $months, $days, $hours, $minutes) : 0;
		
		return new self($years, $months, $days, $hours, $minutes, $seconds, $interval >= 0);
	}
	
	/**
	 * @param string $target = self::UNIT_SECOND
	 * @param string $unit = self::UNIT_SECOND
	 * @param string $round = self::ROUND_CEIL
	 * @param int $interval = 0
	 * @param int $years = 0
	 * @param int $months = 0
	 * @param int $days = 0
	 * @param int $hours = 0
	 * @param int $minutes = 0
	 *
	 * @return int
	 */
	public static function Calculate ($target = self::UNIT_SECOND, $unit = self::UNIT_SECOND, $round = self::ROUND_CEIL, $interval = 0, $years = 0, $months = 0, $days = 0, $hours = 0, $minutes = 0) {
		$args = func_num_args();
		
		return (isset(self::$_dividers[$unit][$target]) ? $round($interval / self::$_dividers[$unit][$target]) : $interval)
			- (
				($args > 4 && isset(self::$_dividers[$target][self::UNIT_YEAR]) ? $years * self::$_dividers[$target][self::UNIT_YEAR] : 0) +
				($args > 5 && isset(self::$_dividers[$target][self::UNIT_MONTH]) ? $months * self::$_dividers[$target][self::UNIT_MONTH] : 0) +
				($args > 6 && isset(self::$_dividers[$target][self::UNIT_DAY]) ? $days * self::$_dividers[$target][self::UNIT_DAY] : 0) +
				($args > 7 && isset(self::$_dividers[$target][self::UNIT_HOUR]) ? $hours * self::$_dividers[$target][self::UNIT_HOUR] : 0) +
				($args > 8 && isset(self::$_dividers[$target][self::UNIT_MINUTE]) ? $minutes * self::$_dividers[$target][self::UNIT_MINUTE] : 0) +
			0);
	}
}

/**
 * Class QuarkGuID
 *
 * @package Quark
 */
class QuarkGuID implements IQuarkModel, IQuarkStrongModel, IQuarkLinkedModel, IQuarkPolymorphicModel {
	/**
	 * @var string $_provider
	 */
	private $_provider;

	/**
	 * @var string|mixed $_value
	 */
	private $_value;

	/**
	 * @return string
	 */
	public function __toString () {
		return (string)$this->_value;
	}

	/**
	 * @param string $provider = null
	 * @param string|mixed $value = null
	 */
	public function __construct ($provider = null, $value = null) {
		$this->Provider($provider);
		$this->Value(func_num_args() != 2 ? $this->Request() : $value);
	}

	/**
	 * @param string $provider = null
	 *
	 * @return string
	 */
	public function Provider ($provider = null) {
		if (func_num_args() != 0)
			$this->_provider = $provider;

		return $this->_provider;
	}

	/**
	 * @param string|mixed $value = null
	 *
	 * @return string|mixed|null
	 */
	public function Value ($value = null) {
		if (func_num_args() != 0)
			$this->_value = $value;

		return $this->_value;
	}

	/**
	 * @return string|mixed|null
	 */
	public function Request () {
		$value = null;

		if ($this->_provider == null) $value = Quark::GuID();
		else {
			$source = Quark::Config()->DataProvider($this->_provider);
			$provider = $source->Provider();

			if ($provider instanceof IQuarkGuIDSynchronizer)
				$value = $provider->GuIDRequest();
		}

		return $value;
	}

	/**
	 * @return void
	 */
	public function Fields () { }

	/**
	 * @return void
	 */
	public function Rules () { }

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Link ($raw) {
		return new QuarkModel(new QuarkGuID($this->_provider, $raw));
	}

	/**
	 * @return mixed
	 */
	public function Unlink () {
		return $this->_value;
	}

	/**
	 * @return mixed
	 */
	public function PolymorphicExtract () {
		return $this->_value;
	}
}

/**
 * Interface IQuarkGuIDSynchronizer
 *
 * @package Quark
 */
interface IQuarkGuIDSynchronizer {
	/**
	 * @return mixed
	 */
	public function GuIDRequest();
}

/**
 * Class QuarkGenericModel
 *
 * @package Quark
 */
class QuarkGenericModel implements IQuarkModel, IQuarkModelWithManageableDataProvider, IQuarkModelWithCustomCollectionName, IQuarkPolymorphicModel {
	use QuarkModelBehavior;

	/**
	 * @var array $_fields = []
	 */
	private $_fields = array();

	/**
	 * @var array $_rules = []
	 */
	private $_rules = array();

	/**
	 * @var callable $_polyMorph = null
	 */
	private $_polyMorph = null;

	/**
	 * @var string $_provider = null
	 */
	private $_provider = null;

	/**
	 * @var string $_collection = ''
	 */
	private $_collection = '';

	/**
	 * @param array $fields = []
	 * @param array $rules = []
	 * @param callable $polyMorph = null
	 */
	public function __construct ($fields = [], $rules = [], callable $polyMorph = null) {
		$this->_fields = $fields;
		$this->_rules = $rules;
		$this->_polyMorph = $polyMorph;
	}
	
	/**
	 * @return mixed
	 */
	public function Fields () {
		return $this->_fields;
	}

	/**
	 * @return mixed
	 */
	public function Rules () {
		return $this->_rules;
	}

	/**
	 * @return string
	 */
	public function DataProvider () {
		return $this->_provider;
	}

	/**
	 * @param $source
	 *
	 * @return bool
	 */
	public function DataProviderForSubModel ($source) {
		return $this->_provider !== null;
	}

	/**
	 * @return mixed
	 */
	public function PolymorphicExtract () {
		$morph = $this->_polyMorph;
		
		return $morph ? $morph($this) : null;
	}

	/**
	 * @return string
	 */
	public function CollectionName () {
		return $this->_collection;
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return QuarkModel
	 */
	public function To (IQuarkModel $model) {
		return new QuarkModel($model, $this);
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return bool|IQuarkModel|QuarkModelBehavior
	 */
	public function ExportGeneric (IQuarkModel $model) {
		return $this->PopulateWith((new QuarkModel($model))->Export(true))->Export();
	}

	/**
	 * @param IQuarkModelWithDataProvider $model = null
	 * @param array $fields = []
	 * @param array $rules = []
	 * @param callable $polyMorph = null
	 *
	 * @return QuarkGenericModel
	 */
	public static function WithDataProvider (IQuarkModelWithDataProvider $model = null, $fields = [], $rules = [], callable $polyMorph = null) {
		if ($model == null) return null;
		
		$out = new self($fields, $rules, $polyMorph);
		$out->_provider = $model->DataProvider();
		$out->_collection = $model instanceof IQuarkModelWithCustomCollectionName
			? $model->CollectionName()
			: QuarkObject::ClassOf($model);
		
		return $out;
	}
}

/**
 * Class QuarkLazyLink
 *
 * @package Quark
 */
class QuarkLazyLink implements IQuarkModel, IQuarkLinkedModel, IQuarkModelWithBeforeExtract {
	/**
	 * @var IQuarkLinkedModel $_model
	 */
	private $_model;

	/**
	 * @var $value
	 */
	public $value;

	/**
	 * @var bool $_linked = false
	 */
	private $_linked = false;

	/**
	 * @param IQuarkLinkedModel $model = null
	 * @param $value = null
	 * @param bool $linked = false
	 */
	public function __construct (IQuarkLinkedModel $model, $value = null, $linked = false) {
		$this->_model = $model;
		$this->_linked = $linked;
		
		$this->value = func_num_args() > 1 ? $value : '';
	}

	/**
	 * @param IQuarkLinkedModel $model = null
	 *
	 * @return IQuarkLinkedModel
	 */
	public function Model (IQuarkLinkedModel $model = null) {
		if ($model != null)
			$this->_model = $model;
		
		return $this->_model;
	}

	/**
	 * @return bool
	 */
	public function Linked () {
		return $this->_linked;
	}

	/**
	 * @return QuarkModel|IQuarkLinkedModel
	 */
	public function Retrieve () {
		$out = $this->_model->Link($this->value);

		$this->_linked = $out != null;

		if ($this->_linked)
			$this->_model = $out;

		return $out;
	}
	
	/**
	 * @return void
	 */
	public function Fields () { }

	/**
	 * @return void
	 */
	public function Rules () { }

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Link ($raw) {
		$this->value = $raw;

		return new QuarkModel($this);
	}

	/**
	 * @return mixed
	 */
	public function Unlink () {
		if ($this->_linked)
			$this->value = $this->_model->Unlink();
			
		return $this->value;
	}

	/**
	 * @param array $fields
	 * @param bool $weak
	 *
	 * @return mixed
	 */
	public function BeforeExtract ($fields, $weak) {
		return $this->value;
	}
}

/**
 * Class QuarkNullable
 *
 * @property $value = ''
 * @property $default = ''
 *
 * @package Quark
 */
class QuarkNullable implements IQuarkModel, IQuarkLinkedModel, IQuarkPolymorphicModel {
	/**
	 * @var bool $_changed = false
	 */
	private $_changed = false;
	
	/**
	 * @param $value
	 */
	public function __construct ($value) {
		$this->value = $value;
		$this->default = $value;
	}
	
	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'value' => $this->value,
			'default' => $this->value
		);
	}
	
	/**
	 * @return void
	 */
	public function Rules () { }
	
	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Link ($raw) {
		$this->_changed = true;
		
		return $this->value = $raw;
	}
	
	/**
	 * @return mixed
	 */
	public function Unlink () {
		return $this->_changed ? $this->value : null;
	}
	
	/**
	 * @return mixed
	 */
	public function PolymorphicExtract () {
		return $this->_changed ? $this->value : null;
	}
}

/**
 * Trait QuarkSessionBehavior
 *
 * @package Quark
 */
trait QuarkSessionBehavior {
	/**
	 * @var object $_rights
	 */
	private $_rights;
	
	/**
	 * @param string $right = ''
	 * @param bool|mixed $value = false
	 *
	 * @return bool|mixed
	 */
	public function Able ($right = '', $value = false) {
		if ($this->_rights == null)
			$this->_rights = new \stdClass();
		
		if (func_num_args() == 2)
			$this->_rights->$right = $value;
		
		return isset($this->_rights->$right) ? $this->_rights->$right : false;
	}
	
	/**
	 * @param string $right = ''
	 * @param $criteria = ''
	 *
	 * @return bool
	 * 
	 * @throws QuarkArchException
	 */
	public function AbleTo ($right = '', $criteria = '') {
		if (!($this instanceof IQuarkAuthorizableModelWithAbilityControl))
			throw new QuarkArchException('[QuarkSessionBehavior::AbleTo] Model ' . get_class($this) . ' is not an IQuarkAuthorizableModelWithAbilityControl');
		
		/**
		 * @var IQuarkAuthorizableModelWithAbilityControl $this
		 */
		
		return $this->AbilityControl($right, $criteria);
	}
	
	/**
	 * @param array|object $rights = []
	 *
	 * @return object
	 */
	public function Rights ($rights = []) {
		if (func_num_args() != 0 && QuarkObject::isTraversable($rights))
			$this->_rights = (object)$rights;
		
		return $this->_rights;
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

	/**
	 * @param object $ini
	 *
	 * @return void
	 */
	public function Options ($ini) {
		$this->_provider->SessionOptions($ini);
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
	 * @var QuarkModel|QuarkSessionBehavior|IQuarkAuthorizableModel $user
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
	 * @var QuarkClient $_connection = null
	 */
	private $_connection = null;

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
		if (!isset($this->_user->$key))
			return $this->_null;

		$field = &$this->_user->$key;

		return $field;
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
	 * @return QuarkModel|QuarkSessionBehavior|IQuarkAuthorizableModel
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
	 * @param bool $extract = false
	 *
	 * @return QuarkSessionBehavior|IQuarkAuthorizableModel|QuarkModelBehavior|\stdClass
	 */
	private function _session ($extract = false) {
		return $this->_user instanceof QuarkModel
			? ($extract ? $this->_user->Extract() : $this->_user->Model())
			: $this->_user;
	}

	/**
	 * @param QuarkDTO $input
	 *
	 * @return bool
	 */
	public function Input (QuarkDTO $input) {
		$data = $this->_source->Provider()->Session($this->_source->Name(), $this->_source->User(), $input);
		$this->_output = $data;

		if ($data == null || $data->AuthorizationPrompt()) return false;

		$this->_user = $this->_source->User()->Session($this->_source->Name(), $data->Data());

		if (!($this->_source->Provider() instanceof IQuarkAuthorizationProviderWithFullOutputControl))
			$this->_output->Data(null);

		return $this->_user != null;
	}

	/**
	 * @param QuarkModel $user = null
	 * @param $criteria = []
	 * @param int $lifetime = 0
	 *
	 * @return bool
	 * @throws QuarkArchException
	 */
	public function ForUser (QuarkModel $user = null, $criteria = [], $lifetime = 0) {
		if ($user == null)
			throw new QuarkArchException('[QuarkSession::ForUser] Given model is null');

		$model = $user->Model();

		if (!($model instanceof IQuarkAuthorizableModel))
			throw new QuarkArchException('[QuarkSession::ForUser] Model ' . get_class($model) . ' is not an IQuarkAuthorizableModel');

		if ($this->_source == null)
			throw new QuarkArchException('[QuarkSession::ForUser] Called session does not have a connected session source. Please check that called service is a IQuarkAuthorizableService or its inheritor.');

		$data = $this->_source->Provider()->Login($this->_source->Name(), $model, $criteria, $lifetime);
		if ($data == null) return false;
		
		$this->_user = $criteria !== null
			? $this->_source->User()->Login($this->_source->Name(), $criteria, $lifetime)
			: $user;
		
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

		$data = $this->_source->Provider()->Login($this->_source->Name(), $this->_session(), $criteria, $lifetime);
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

		$data = $this->_source->Provider()->Logout($this->_source->Name(), $this->_session(), $this->ID());
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
	 * @return QuarkClient
	 */
	public function &Connection () {
		return $this->_connection;
	}

	/**
	 * @return QuarkSessionSource
	 */
	public function &Source () {
		return $this->_source;
	}

	/**
	 * @param $data = []
	 *
	 * @return mixed
	 */
	public function Data ($data = []) {
		return $this->_source->Provider()->SessionData($this->_source->Name(), $this->ID(), $data, func_num_args() != 0);
	}
	
	/**
	 * @return bool
	 * @throws QuarkArchException
	 */
	private function _able () {
		if ($this->_user == null) return false;
		
		if (!QuarkObject::Uses($this->_user->Model(), 'Quark\\QuarkSessionBehavior'))
			throw new QuarkArchException('[QuarkSession::Able] Model ' . get_class($this->_user->Model()) . ' does not uses QuarkSessionBehavior');
		
		return true;
	}
	
	/**
	 * @param string $right = ''
	 *
	 * @return bool|mixed
	 * 
	 * @throws QuarkArchException
	 */
	public function Able ($right = '') {
		return $this->_able() && $this->_user->Able($right);
	}
	
	/**
	 * @param string $right = ''
	 * @param $criteria = ''
	 *
	 * @return bool
	 * 
	 * @throws QuarkArchException
	 */
	public function AbleTo ($right = '', $criteria = '') {
		return $this->_able() && $this->_user->AbleTo($right, $criteria);
	}

	/**
	 * @param string $name
	 *
	 * @return IQuarkAuthorizationProvider
	 * @throws QuarkArchException
	 */
	public static function Provider ($name) {
		$stack = Quark::Stack($name);

		return $stack instanceof QuarkSessionSource ? $stack->Provider() : null;
	}

	/**
	 * @param string $provider
	 * @param QuarkDTO $input
	 * @param QuarkClient $connection = null
	 *
	 * @return QuarkSession
	 *
	 * @throws QuarkArchException
	 */
	public static function Init ($provider, QuarkDTO $input, QuarkClient &$connection = null) {
		/**
		 * @var QuarkSessionSource $source
		 */
		$source = Quark::Stack($provider);
		if ($source == null) return null;

		$session = new self($source);
		$session->Input($input);
		$session->_connection = $connection;

		return $session;
	}

	/**
	 * @param QuarkClient $connection = null
	 *
	 * @return QuarkSession
	 */
	public static function InitWithConnection (QuarkClient &$connection = null) {
		if ($connection == null) return null;

		$session = new self();
		$session->_connection = $connection;

		return $session;
	}

	/**
	 * @param QuarkKeyValuePair $id
	 *
	 * @return QuarkSession
	 */
	public static function Get (QuarkKeyValuePair $id = null) {
		if ($id == null || $id->Key() == null) return null;

		/**
		 * @var QuarkSessionSource $source
		 */
		$source = Quark::Stack($id->Key());
		if ($source == null) return null;

		$input = new QuarkDTO();
		$input->Authorization($id);
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
	 * Reset QuarkSession
	 */
	public function __destruct () {
		unset($this->_user, $this->_source, $this->_output, $this->_connection);
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

	/**
	 * @param string $name
	 * @param QuarkKeyValuePair $id
	 * @param $data
	 * @param bool $commit
	 *
	 * @return bool
	 */
	public function SessionData($name, QuarkKeyValuePair $id, $data, $commit);

	/**
	 * @param object $ini
	 *
	 * @return mixed
	 */
	public function SessionOptions($ini);
}

/**
 * Interface IQuarkAuthorizationProviderWithFullOutputControl
 *
 * @package Quark
 */
interface IQuarkAuthorizationProviderWithFullOutputControl extends IQuarkAuthorizationProvider { }

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
 * Interface IQuarkAuthorizableModelWithRuntimeFields
 *
 * @package Quark
 */
interface IQuarkAuthorizableModelWithRuntimeFields extends IQuarkAuthorizableModel, IQuarkStrongModelWithRuntimeFields { }

/**
 * Interface IQuarkAuthorizableModelWithAbilityControl
 *
 * @package Quark
 */
interface IQuarkAuthorizableModelWithAbilityControl extends IQuarkAuthorizableModel {
	/**
	 * @param string $right
	 * @param $criteria
	 *
	 * @return bool
	 */
	public function AbilityControl($right, $criteria);
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

	/**
	 * @param string $delimiter = ''
	 * @param string $source = ''
	 * @param bool $strict = false
	 *
	 * @return QuarkKeyValuePair
	 */
	public static function ByDelimiter ($delimiter = '', $source = '', $strict = false) {
		$pair = explode($delimiter, $source);
		
		return new self($pair[0], sizeof($pair) == 1
			? ($strict ? '' : $pair[0])
			: $pair[1]
		);
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
	 * @var bool $_blocking = true
	 */
	private $_blocking = true;

	/**
	 * @var $_flags
	 */
	private $_flags;

	/**
	 * @var resource $_socket
	 */
	private $_socket;

	/**
	 * @var bool $_secure = false
	 */
	private $_secure = false;

	/**
	 * @var string $_secureFailureEvent = QuarkClient::EVENT_ERROR_CRYPTOGRAM
	 */
	private $_secureFailureEvent = QuarkClient::EVENT_ERROR_CRYPTOGRAM;

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
	 *
	 * @return resource
	 */
	public function Socket ($socket = null) {
		if (func_num_args() == 1)
			$this->_socket = $socket;

		return $this->_socket;
	}

	/**
	 * @param resource $socket
	 *
	 * http://php.net/manual/ru/function.stream-socket-shutdown.php#109982
	 * https://github.com/reactphp/socket/blob/master/src/Connection.php
	 * http://chat.stackoverflow.com/transcript/message/7727858#7727858
	 *
	 * @return bool
	 */
	public static function SocketClose ($socket) {
		if (!$socket) return false;

		stream_socket_shutdown($socket, STREAM_SHUT_RDWR);
		stream_set_blocking($socket, false);

		return fclose($socket);
	}

	/**
	 * @param int $level = SOL_TCP
	 * @param int $name = 0
	 * @param $value = ''
	 *
	 * @return bool|mixed
	 */
	public function SocketOption ($level = SOL_TCP, $name = 0, $value = '') {
		if (!function_exists('\socket_import_stream')) {
			Quark::Log('[QuarkNetwork] Function \socket_import_stream does not exists. Cannot set ' . QuarkObject::ConstValue($level) . ':' . QuarkObject::ConstValue($name) . '=' . $value . ' to socket', Quark::LOG_WARN);

			return false;
		}

		if (!$this->_socket) return false;

		$socket = \socket_import_stream($this->_socket);
		if (!$socket) return false;

		return func_num_args() == 3
			? \socket_set_option($socket, $level, $name, $value)
			: \socket_get_option($socket, $level, $name);
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
		if (func_num_args() != 0 && $transport != null)
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
	 * @return QuarkURI|null
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
				stream_set_timeout($this->_socket, $this->_timeout, QuarkThreadSet::TICK);
		}

		return $this->_timeout;
	}

	/**
	 * @param $flags = null
	 *
	 * @return mixed
	 */
	public function Flags ($flags = null) {
		if (func_num_args() != 0)
			$this->_flags = $flags;

		return $this->_flags;
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
	 * http://php.net/manual/ru/function.stream-socket-server.php#118419
	 * http://php.net/manual/ru/function.stream-socket-enable-crypto.php#119122
	 *
	 * @param int $method = -1
	 * @param bool $flag = false
	 * @param int $timeout = 30
	 *
	 * @return bool
	 */
	public function Secure ($method = -1, $flag = false, $timeout = 30) {
		if (func_num_args() != 0) {
			if (!$this->_socket) return false;
		
			if (!$this->_blocking) stream_set_blocking($this->_socket, 1);
			stream_set_timeout($this->_socket, $timeout, QuarkThreadSet::TICK);
			
			$secure = @stream_socket_enable_crypto($this->_socket, $flag, $method);
			
			stream_set_timeout($this->_socket, $this->_timeout, QuarkThreadSet::TICK);
			if (!$this->_blocking) stream_set_blocking($this->_socket, 0);

			if (!$secure)
				$this->TriggerArgs($this->_secureFailureEvent, array('QuarkNetwork cannot enable secure transport for ' . $this->_uri->URI() . ' (' . $this->_uri->Socket() . '). Error: ' . QuarkException::LastError()));
			
			$this->_secure = $flag;
		}
		
		return $this->_secure;
	}

	/**
	 * @param string $event = QuarkClient::EVENT_ERROR_CRYPTOGRAM
	 *
	 * @return string
	 */
	private function _secureFailure ($event = QuarkClient::EVENT_ERROR_CRYPTOGRAM) {
		if (func_num_args() != 0)
			$this->_secureFailureEvent = $event;

		return $this->_secureFailureEvent;
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
	const EVENT_ERROR_CRYPTOGRAM = 'ErrorCryptogram';
	const EVENT_ERROR_PROTOCOL = 'ErrorProtocol';

	const EVENT_CONNECT = 'OnConnect';
	const EVENT_DATA = 'OnData';
	const EVENT_CLOSE = 'OnClose';

	const MTU = 1500;

	use QuarkNetwork {
		Secure as private _secure;
	}

	/**
	 * @var int $_timeoutConnect = 0
	 */
	private $_timeoutConnect = 0;

	/**
	 * @var bool $_connected = false
	 */
	private $_connected = false;

	/**
	 * @var QuarkURI $_remote
	 */
	private $_remote;
	
	/**
	 * @var bool $_fromServer = false
	 */
	private $_fromServer = false;

	/**
	 * @var bool $_autoSecure = true
	 */
	private $_autoSecure = true;

	/**
	 * @var QuarkKeyValuePair $_session
	 */
	private $_session;

	/**
	 * @var int $_rps = 0
	 */
	private $_rps = 0;

	/**
	 * @var int $_rpsCount = 0
	 */
	private $_rpsCount = 0;

	/**
	 * @var QuarkTimer $_rpsTimer
	 */
	private $_rpsTimer;

	/**
	 * @var string $_id = ''
	 */
	private $_id = '';

	/**
	 * @var string[] $_channels = []
	 */
	private $_channels = array();

	/**
	 * @return int
	 */
	public static function Crypto () {
		$out = STREAM_CRYPTO_METHOD_TLS_CLIENT;

		if (defined('STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT')) $out |= STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
		if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) $out |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;

		return $out;
	}

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
		$this->Flags(STREAM_CLIENT_CONNECT);
		$this->_secureFailure(self::EVENT_ERROR_CRYPTOGRAM);

		$this->_timeoutConnect = $this->_timeout;

		$this->_rpsTimer = new QuarkTimer(QuarkTimer::ONE_SECOND, function () {
			$this->_rps = $this->_rpsCount;
			$this->_rpsCount = 0;
		});

		$this->_id = Quark::GuID();
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
		
		$socket = $this->_uri->SocketURI();
		$secure = $socket->Secure();
		
		if ($secure)
			$socket->scheme = QuarkURI::WRAPPER_TCP;
		
		$this->_socket = @stream_socket_client(
			$socket->Socket(),
			$this->_errorNumber,
			$this->_errorString,
			$this->_timeoutConnect,
			$this->_flags,
			$stream
		);

		// TODO: Possible to implement Connection URI comparison (QuarkURI comparison)
		/** @noinspection PhpNonStrictObjectEqualityInspection */
		if (!$this->_socket || $this->_errorNumber != 0 || $this->ConnectionURI() == $this->ConnectionURI(true)) {
			$this->Close(false);
			$this->TriggerArgs(self::EVENT_ERROR_CONNECT, array('QuarkClient cannot connect to ' . $this->_uri->URI() . ' (' . $this->_uri->Socket() . '). Error: ' . QuarkException::LastError()));

			return false;
		}

		if ($secure && $this->_autoSecure)
			$this->Secure(true);

		$this->Timeout($this->_timeout);
		$this->Blocking($this->_blocking);

		$this->_connected = true;
		$this->_remote = QuarkURI::FromURI($this->ConnectionURI(true));

		if ($this->_transport instanceof IQuarkNetworkTransport)
			$this->_transport->EventConnect($this);

		return true;
	}
	
	/**
	 * @param bool $flag = false
	 * @param int $timeout = 30
	 *
	 * @return bool
	 */
	public function Secure ($flag = false, $timeout = 30) {
		return func_num_args() != 0
			? $this->_secure($this->_fromServer ? QuarkServer::Crypto() : QuarkClient::Crypto(), $flag, $timeout)
			: $this->_secure();
	}

	/**
	 * @param bool $auto = true
	 *
	 * @return bool
	 */
	public function AutoSecure ($auto = true) {
		if (func_num_args() != 0)
			$this->_autoSecure = $auto;

		return $this->_autoSecure;
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

		return $out;
	}

	/**
	 * @param string $data = ''
	 * @param int $flags = 0
	 * @param string $address = ''
	 *
	 * @return int
	 */
	public function SendTo ($data = '', $flags = 0, $address = '') {
		$out = @stream_socket_sendto($this->_socket, $data, $flags, func_num_args() != 3
			? ($this->_uri->host . ':' . $this->_uri->port)
			: $address
		);

		$error = QuarkException::LastError();
		if ($error) $this->TriggerErrorProtocol($error);

		unset($error, $data);

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

		$data = @stream_get_contents($this->_socket, $max);

		return strlen($data) != 0 ? $data : false;
	}

	/**
	 * @param int $length = self::MTU
	 * @param int $flags = 0
	 * @param string &$address = ''
	 *
	 * @return string
	 */
	public function ReceiveFrom ($length = self::MTU, $flags = 0, &$address = '') {
		$out = @stream_socket_recvfrom($this->_socket, $length, $flags, $address);

		$error = QuarkException::LastError();
		if ($error) $this->TriggerErrorProtocol($error);

		return $out;
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
		if (!$this->_connected) return true;

		$this->_connected = false;

		if ($event && $this->_transport instanceof IQuarkNetworkTransport)
			$this->_transport->EventClose($this);

		$this->_remote = null;
		$this->_rps = 0;

		if ($this->_rpsTimer != null)
			$this->_rpsTimer->Destroy();

		return self::SocketClose($this->_socket);
	}

	/**
	 * @param int $timeout = 0 (seconds)
	 *
	 * @return int
	 */
	public function TimeoutConnect ($timeout = 0) {
		if (func_num_args() != 0)
			$this->_timeoutConnect = $timeout;

		return $this->_timeoutConnect;
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
		if ($this->_rpsTimer != null) {
			$this->_rpsCount++;
			$this->_rpsTimer->Invoke();
		}

		$this->TriggerArgs(QuarkClient::EVENT_DATA, array(&$this, $data));
	}

	/**
	 * Trigger `Close` event
	 */
	public function TriggerClose () {
		$this->TriggerArgs(QuarkClient::EVENT_CLOSE, array(&$this));
	}

	/**
	 * Trigger `ErrorProtocol` event
	 *
	 * @param string $error = ''
	 */
	public function TriggerErrorProtocol ($error = '') {
		$this->TriggerArgs(QuarkClient::EVENT_ERROR_PROTOCOL, array(&$this, $error));
	}

	/**
	 * @param IQuarkNetworkTransport $transport = null
	 * @param resource $socket = null
	 * @param string $address = ''
	 * @param string $scheme = ''
	 *
	 * @return QuarkClient
	 */
	public static function ForServer (IQuarkNetworkTransport $transport = null, $socket = null, $address = '', $scheme = '') {
		$uri = QuarkURI::FromURI($address);

		if (func_num_args() == 4)
			$uri->scheme = $scheme;

		$client = new self($uri, $transport == null ? null : clone $transport);
		$client->_fromServer = true;

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
		return !$this->_socket || (@feof($this->_socket) === true && $this->_connected);
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
	 * @return bool
	 */
	public function FromServer () {
		return $this->_fromServer;
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

	/**
	 * @return string
	 */
	public function ID () {
		return $this->_id;
	}

	/**
	 * @param string|string[] $channel = ''
	 *
	 * @return QuarkClient
	 */
	public function Subscribe ($channel = '') {
		if (is_array($channel)) $this->_channels = array_merge($this->_channels, $channel);
		else $this->_channels[] = $channel;

		return $this;
	}

	/**
	 * @param string $channel = ''
	 *
	 * @return QuarkClient
	 */
	public function Unsubscribe ($channel = '') {
		foreach ($this->_channels as $i => &$c)
			if ($channel == $c)
				unset($this->_channels[$i]);

		unset($i, $c);
		
		return $this;
	}

	/**
	 * @param string $channel = ''
	 * @param bool $strict = false
	 *
	 * @return bool
	 */
	public function Subscribed ($channel = '', $strict = false) {
		return in_array($channel, $this->_channels, $strict);
	}

	/**
	 * @return string[]
	 */
	public function &Channels () {
		return $this->_channels;
	}

	/**
	 * Reset QuarkClient
	 */
	public function __destruct () {
		unset($this->_rpsTimer, $this->_certificate, $this->_channels, $this->_events, $this->_remote, $this->_session, $this->_transport, $this->_socket);
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
	const EVENT_ERROR_CRYPTOGRAM = 'ErrorCryptogram';

	use QuarkNetwork {
		Secure as private _secure;
	}

	/**
	 * @var bool $_run = false
	 */
	private $_run = false;

	/**
	 * @var array $_read = []
	 */
	private $_read = array();

	/**
	 * @var array $_write = []
	 */
	private $_write = array();

	/**
	 * @var array $_except = []
	 */
	private $_except = array();

	/**
	 * @var QuarkClient[] $_clients = []
	 */
	private $_clients = array();

	/**
	 * @return int
	 */
	public static function Crypto () {
		$out = STREAM_CRYPTO_METHOD_TLS_SERVER;

		if (defined('STREAM_CRYPTO_METHOD_TLSv1_1_SERVER')) $out |= STREAM_CRYPTO_METHOD_TLSv1_1_SERVER;
		if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_SERVER')) $out |= STREAM_CRYPTO_METHOD_TLSv1_2_SERVER;

		return $out;
	}

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
		$this->Flags(STREAM_SERVER_LISTEN);
		$this->_secureFailure(self::EVENT_ERROR_CRYPTOGRAM);
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
			stream_context_set_option($stream, 'ssl', 'verify_peer', false);
			stream_context_set_option($stream, 'ssl', 'allow_self_signed', true);
			stream_context_set_option($stream, 'ssl', 'passphrase', $this->_certificate->Passphrase());
		}

		$socket = $this->_uri->SocketURI();

		if ($socket->Secure())
			$socket->scheme = QuarkURI::WRAPPER_TCP;

		$this->_socket = @stream_socket_server(
			$socket->Socket(),
			$this->_errorNumber,
			$this->_errorString,
			$socket->scheme == QuarkURI::WRAPPER_UDP && $this->_flags == STREAM_SERVER_LISTEN
				? STREAM_SERVER_BIND
				: STREAM_SERVER_BIND|$this->_flags,
			$stream
		);

		if (!$this->_socket) {
			$this->TriggerArgs(self::EVENT_ERROR_LISTEN, array('QuarkServer cannot listen to ' . $this->_uri->URI() . ' (' . $this->_uri->Socket() . '). Error: ' . QuarkException::LastError()));

			return false;
		}

		$this->Timeout(0);
		$this->Blocking(0);
		
		if ($socket->scheme == QuarkURI::WRAPPER_TCP)
			$this->SocketOption(SOL_TCP, TCP_NODELAY, true);

		$this->_read = array($this->_socket);
		$this->_run = true;

		return true;
	}
	
	/**
	 * @param bool $flag = false
	 * @param int $timeout = 30
	 *
	 * @return bool
	 */
	public function Secure ($flag = false, $timeout = 30) {
		return func_num_args() != 0
			? $this->_secure(QuarkServer::Crypto(), $flag, $timeout)
			: $this->_secure();
	}

	/**
	 * @return bool
	 */
	public function Pipe () {
		if ($this->_socket == null) return false;

		if (sizeof($this->_read) == 0)
			$this->_read = array($this->_socket);

		$stream = $this->_uri->SocketTransport() == QuarkURI::WRAPPER_TCP;

		if (stream_select($this->_read, $this->_write, $this->_except, 0, 0) === false) return true;

		if (in_array($this->_socket, $this->_read, true)) {
			if ($stream) {
				$socket = stream_socket_accept($this->_socket, $this->_timeout, $address);

				$client = QuarkClient::ForServer($this->_transport, $socket, $address, $this->URI()->scheme);
				$client->Remote(QuarkURI::FromURI($this->ConnectionURI()));

				$client->Delegate(QuarkClient::EVENT_ERROR_CRYPTOGRAM, $this);

				if ($this->_uri->SocketURI()->Secure())
					$client->Secure(true);

				$client->Delegate(QuarkClient::EVENT_CONNECT, $this);
				$client->Delegate(QuarkClient::EVENT_DATA, $this);
				$client->Delegate(QuarkClient::EVENT_CLOSE, $this);
				$client->Transport()->EventConnect($client);

				$this->_clients[] = $client;

				unset($socket, $address, $client);
			}
			else {
				foreach ($this->_read as $key => &$socket) {
					$client = QuarkClient::ForServer($this->_transport, $socket, $this->URI()->Socket());
					$data = $client->ReceiveFrom(QuarkClient::MTU, 0, $address);

					// TODO: need some handling of incorrect interface binding

					if ($data) {
						$client->URI()->Endpoint($address);

						$client->Delegate(QuarkClient::EVENT_DATA, $this);
						$client->Delegate(QuarkClient::EVENT_ERROR_PROTOCOL, $this);

						$client->TriggerData($data);
					}
				}

				unset($key, $socket);
			}
		}

		$this->_read = array();
		$this->_write = array();
		$this->_except = array();

		if ($stream) {
			foreach ($this->_clients as $key => &$client) {
				if ($client->Closed()) {
					unset($this->_clients[$key]);
					$client->Close();
					continue;
				}

				$client->Pipe();
			}

			unset($key, $client);
		}

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
		foreach ($this->_clients as $i => &$item)
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

		unset($i, $client);

		return $ok;
	}

	/**
	 * @param string $group = ''
	 * @param int $interface = 0
	 * @param bool $reuseAddr = true
	 *
	 * @return bool
	 */
	public function MultiCastGroupJoin ($group = '', $interface = 0, $reuseAddr = true) {
		if ($reuseAddr)
			$this->SocketOption(SOL_SOCKET, SO_REUSEADDR, 1);

		return $this->SocketOption(IPPROTO_IP, MCAST_JOIN_GROUP, array(
			'group' => $group,
			'interface' => $interface
		));
	}

	/**
	 * @param string $group = ''
	 * @param int $interface = 0
	 * @param bool $reuseAddr = true
	 *
	 * @return bool
	 */
	public function MultiCastGroupLeave ($group = '', $interface = 0, $reuseAddr = true) {
		if ($reuseAddr)
			$this->SocketOption(SOL_SOCKET, SO_REUSEADDR, 0);

		return $this->SocketOption(IPPROTO_IP, MCAST_LEAVE_GROUP, array(
			'group' => $group,
			'interface' => $interface
		));
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
	 * @var QuarkCertificate $_certificate
	 */
	private $_certificate;

	/**
	 * @param IQuarkPeer &$protocol
	 * @param QuarkURI|string $bind
	 * @param QuarkURI[]|string[] $connect
	 * @param QuarkCertificate $certificate
	 */
	public function __construct (IQuarkPeer &$protocol = null, $bind = '', $connect = [], QuarkCertificate $certificate = null) {
		$this->_protocol = $protocol;
		$this->_server = new QuarkServer($bind, $this->_protocol->NetworkTransport(), $certificate);
		$this->_server->On(QuarkClient::EVENT_CONNECT, array(&$this->_protocol, 'NetworkServerConnect'));
		$this->_server->On(QuarkClient::EVENT_DATA, array(&$this->_protocol, 'NetworkServerData'));
		$this->_server->On(QuarkClient::EVENT_CLOSE, array(&$this->_protocol, 'NetworkServerClose'));
		$this->_server->On(QuarkClient::EVENT_ERROR_CRYPTOGRAM, array(&$this->_protocol, 'NetworkServerErrorCryptogram'));

		$this->Certificate($certificate);
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

		$peer = new QuarkClient($uri, $this->_protocol->NetworkTransport(), $this->_certificate, 0, false);
		$peer->On(QuarkClient::EVENT_CONNECT, array(&$this->_protocol, 'NetworkClientConnect'));
		$peer->On(QuarkClient::EVENT_DATA, array(&$this->_protocol, 'NetworkClientData'));
		$peer->On(QuarkClient::EVENT_CLOSE, array(&$this->_protocol, 'NetworkClientClose'));
		$peer->On(QuarkClient::EVENT_ERROR_CRYPTOGRAM, array(&$this->_protocol, 'NetworkClientErrorCryptogram'));

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
		if (func_num_args() != 0 && is_array($peers)) {
			foreach ($peers as $i => &$peer)
				$this->Peer($peer, $unique, $loopBack);

			unset($i, $peer);
		}

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

	/**
	 * @param $error
	 *
	 * @return mixed
	 */
	public function NetworkClientErrorCryptogram($error);

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

	/**
	 * @param $error
	 *
	 * @return mixed
	 */
	public function NetworkServerErrorCryptogram($error);
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
	 * @var bool $_startedNode = false
	 */
	private $_startedNode = false;

	/**
	 * @var bool $_startedController = false
	 */
	private $_startedController = false;

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
		$node->_server->On(QuarkClient::EVENT_ERROR_CRYPTOGRAM, array(&$cluster, 'ClientErrorCryptogram'));

		$node->_network = new QuarkPeer($cluster, $internal);

		$node->_controller = new QuarkClient($controller, $cluster->ControllerTransport());
		$node->_controller->On(QuarkClient::EVENT_CONNECT, array(&$cluster, 'ControllerClientConnect'));
		$node->_controller->On(QuarkClient::EVENT_DATA, array(&$cluster, 'ControllerClientData'));
		$node->_controller->On(QuarkClient::EVENT_CLOSE, array(&$cluster, 'ControllerClientClose'));
		$node->_controller->On(QuarkClient::EVENT_ERROR_CRYPTOGRAM, array(&$cluster, 'ControllerClientErrorCryptogram'));

		return $node;
	}

	/**
	 * @return bool
	 */
	public function NodeBind () {
		$run = true;

		if (!$this->_startedNode) {
			$start = $this->_cluster->NodeStart($this->_server, $this->_network, $this->_controller);

			if ($start === false) return false;
			$this->_startedNode = true;
		}

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
		$controller->_controller->On(QuarkClient::EVENT_ERROR_CRYPTOGRAM, array(&$cluster, 'ControllerServerErrorCryptogram'));

		$controller->_terminal = new QuarkServer($external, $cluster->TerminalTransport());
		$controller->_terminal->On(QuarkClient::EVENT_CONNECT, array(&$cluster, 'TerminalConnect'));
		$controller->_terminal->On(QuarkClient::EVENT_DATA, array(&$cluster, 'TerminalData'));
		$controller->_terminal->On(QuarkClient::EVENT_CLOSE, array(&$cluster, 'TerminalClose'));
		$controller->_terminal->On(QuarkClient::EVENT_ERROR_CRYPTOGRAM, array(&$cluster, 'TerminalErrorCryptogram'));

		return $controller;
	}

	/**
	 * @return bool
	 * @throws QuarkArchException
	 */
	public function ControllerBind () {
		if ($this->_controller instanceof QuarkClient)
			throw new QuarkArchException('Cluster controller not started. Controller in client mode.');

		if (!$this->_startedController) {
			$start = $this->_cluster->ControllerStart($this->_controller, $this->_terminal);

			if ($start === false) return false;
			$this->_startedController = true;
		}

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
	 * @param QuarkServer $server
	 * @param QuarkPeer $network
	 * @param QuarkClient $controller
	 *
	 * @return mixed
	 */
	public function NodeStart(QuarkServer $server, QuarkPeer $network, QuarkClient $controller);

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

	/**
	 * @param $error
	 *
	 * @return mixed
	 */
	public function ClientErrorCryptogram($error);

	// ControllerNetwork
	/**
	 * @param QuarkServer $controller
	 * @param QuarkServer $terminal
	 *
	 * @return mixed
	 */
	public function ControllerStart(QuarkServer $controller, QuarkServer $terminal);

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

	/**
	 * @param $error
	 *
	 * @return mixed
	 */
	public function ControllerClientErrorCryptogram($error);

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

	/**
	 * @param $error
	 *
	 * @return mixed
	 */
	public function ControllerServerErrorCryptogram($error);

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

	/**
	 * @param $error
	 *
	 * @return mixed
	 */
	public function TerminalErrorCryptogram($error);
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
	 * @var bool $_controllerFromConfig = false
	 */
	private $_controllerFromConfig = false;

	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

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
	 * @param string $action = ''
	 * @param string $message = ''
	 * @param string[] $args = []
	 */
	private function _log ($action = '', $message = '', $args = []) {
		$append = '';

		foreach ($args as $i => &$arg)
			$append .= ' * ' . $arg . "\r\n";

		unset($i, $arg);

		echo '[', date('Y-m-d H:i:s'), '] [', $action, '] ', $message, "\r\n", $append;
	}

	/**
	 * @param string $action
	 * @param string $error
	 */
	private function _errorCryptogram ($action, $error) {
		$this->_log($action, $error, array(
			'This usually happens when mistyping certificate options for stream environment or client requested a non-supported SSL/TLS protocol version. Check your configuration.'
		));
	}

	/**
	 * @var QuarkClient $_controller
	 */
	private static $_controller;

	/**
	 * @param string $name
	 * @param array|object $data
	 * @param bool $persistent = false
	 *
	 * @return bool
	 */
	public static function ControllerCommand ($name = '', $data = [], $persistent = false) {
		if (self::$_controller != null) $ok = self::$_controller->Send(self::Package(self::PACKAGE_COMMAND, $name, $data, null, true));
		else {
			self::$_controller = new QuarkClient(Quark::Config()->ClusterControllerConnect(), self::TCPProtocol());

			self::$_controller->On(QuarkClient::EVENT_CONNECT, function (QuarkClient &$client) use (&$name, &$data, &$persistent) {
				$client->Send(self::Package(self::PACKAGE_COMMAND, $name, $data, null, true));
				if (!$persistent) $client->Close();
			});

			$ok = self::$_controller->Connect();
		}

		if (!$persistent) unset(self::$_controller);

		return $ok;
	}

	/**
	 * @param string $name = ''
	 *
	 * @return QuarkURI
	 */
	public static function ConnectionURI ($name = '') {
		$environment = Quark::Environment();
		$host = Quark::Config()->StreamHost();
		
		foreach ($environment as $i => &$env)
			if ($env instanceof QuarkStreamEnvironment && $env->EnvironmentName() == $name)
				return $host == ''
					? $env->ServerURI()->ConnectionURI()
					: $env->ServerURI()->ConnectionURI($host);

		unset($i, $env);

		return null;
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
	 * @return bool
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
	 * @return bool
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
			if ($this->_unknown)
				$service = new QuarkService($this->_unknown, new QuarkJSONIOProcessor(), new QuarkJSONIOProcessor());
		}

		if ($service != null) {
			$service->Input()->Data($service->Input()->URI()->Params());

			if ($input !== null)
				$service->Input()->MergeData($input);

			if ($session != null) {
				$service->Input()->Authorization(QuarkKeyValuePair::FromField($session));
				$service->Input()->AuthorizationProvider(QuarkKeyValuePair::FromField($session));

				if ($connected)
					$client->Session($service->Input()->AuthorizationProvider());
			}

			if ($connected)
				$service->Input()->Remote($client->URI());

			if (!$connected || $service->Authorize(false, $client))
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

		if ($json && isset($json->url) && ($signature ? (isset($json->signature) && $json->signature == Quark::Config()->ClusterKey()) : true)) {
			if (isset($json->language))
				Quark::CurrentLanguage($json->language);

			$this->_pipe($json->url, $method, $client, isset($json->data) ? $json->data : new \stdClass(), isset($json->session) ? $json->session : null);
		}

		unset($json, $client, $data, $method);
	}

	/**
	 * @param string $name
	 * @param IQuarkNetworkTransport $transport
	 * @param QuarkURI|string $external = self::URI_NODE_EXTERNAL
	 * @param QuarkURI|string $internal = self::URI_NODE_INTERNAL
	 * @param QuarkURI|string $controller = ''
	 *
	 * @return QuarkStreamEnvironment
	 */
	public static function ClusterNode ($name, IQuarkNetworkTransport $transport, $external = self::URI_NODE_EXTERNAL, $internal = self::URI_NODE_INTERNAL, $controller = '') {
		$stream = new self();

		$stream->_name = $name;
		$stream->_transportClient = $transport;
		$stream->_cluster = QuarkCluster::NodeInstance($stream, $external, $internal, !$controller ? Quark::Config()->ClusterControllerConnect() : $controller);

		if (!$controller)
			$stream->_controllerFromConfig = true;

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
	 * @return array
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
	public function NetworkURI ($uri = '') {
		if (func_num_args() != 0)
			$this->_cluster->Network()->URI(QuarkURI::FromURI($uri));

		return $this->_cluster->Network()->URI();
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
	 * @param QuarkCertificate $certificate = null
	 *
	 * @return QuarkCertificate
	 */
	public function ServerCertificate (QuarkCertificate $certificate = null) {
		if (func_num_args() != 0)
			$this->_cluster->Server()->Certificate($certificate);
		
		return $this->_cluster->Server()->Certificate();
	}
	
	/**
	 * @param QuarkCertificate $certificate = null
	 *
	 * @return QuarkCertificate
	 */
	public function NetworkCertificate (QuarkCertificate $certificate = null) {
		if (func_num_args() != 0)
			$this->_cluster->Network()->Certificate($certificate);
		
		return $this->_cluster->Network()->Certificate();
	}
	
	/**
	 * @param QuarkCertificate $certificate = null
	 *
	 * @return QuarkCertificate
	 */
	public function ControllerCertificate (QuarkCertificate $certificate = null) {
		if (func_num_args() != 0)
			$this->_cluster->Controller()->Certificate($certificate);
		
		return $this->_cluster->Controller()->Certificate();
	}

	/**
	 * @return bool
	 */
	public function EnvironmentMultiple () { return true; }

	/**
	 * @return bool
	 */
	public function EnvironmentQueued () { return true; }

	/**
	 * @return string
	 */
	public function EnvironmentName () {
		return $this->_name;
	}

	/**
	 * @param object $ini
	 *
	 * @return void
	 */
	public function EnvironmentOptions ($ini) {
		if (isset($ini->External))
			$this->ServerURI($ini->External);

		if (isset($ini->Internal))
			$this->NetworkURI($ini->Internal);

		if (isset($ini->Controller))
			$this->ControllerURI($ini->Controller);
		
		if (isset($ini->Certificate)) {
			$certificate = new QuarkCertificate($ini->Certificate, isset($ini->CertificatePassphrase) ? $ini->CertificatePassphrase : '');
			
			$this->ServerCertificate($certificate);
			$this->NetworkCertificate($certificate);
			$this->ControllerCertificate($certificate);
		}
		
		if (isset($ini->CertificateExternal))
			$this->ServerCertificate(new QuarkCertificate($ini->CertificateExternal, isset($ini->CertificateExternalPassphrase) ? $ini->CertificateExternalPassphrase : ''));
		
		if (isset($ini->CertificateInternal))
			$this->NetworkCertificate(new QuarkCertificate($ini->CertificateInternal, isset($ini->CertificateInternalPassphrase) ? $ini->CertificateInternalPassphrase : ''));
		
		if (isset($ini->CertificateController))
			$this->ControllerCertificate(new QuarkCertificate($ini->CertificateController, isset($ini->CertificateControllerPassphrase) ? $ini->CertificateControllerPassphrase : ''));
		
		if (isset($ini->StreamConnect))
			$this->StreamConnect($ini->StreamConnect);
		
		if (isset($ini->StreamClose))
			$this->StreamClose($ini->StreamClose);
		
		if (isset($ini->StreamUnknown))
			$this->StreamUnknown($ini->StreamUnknown);
	}

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
	 * @param callable(QuarkSession $client) $sender = null
	 * @param bool $auth = true
	 * @param string|callable(QuarkClient $client) $filter = null
	 *
	 * @return bool
	 */
	public function BroadcastLocal ($url, callable &$sender = null, $auth = true, &$filter = null) {
		$ok = true;
		$clients = $this->_cluster->Server()->Clients();
		$filtered = func_num_args() == 4;

		foreach ($clients as $i => &$client) {
			if ($filtered) {
				if (is_string($filter) && !$client->Subscribed($filter)) continue;
				if (is_callable($filter) && !call_user_func_array($filter, array(&$client))) continue;
			}

			$session = QuarkSession::Get($client->Session());
			if ($auth && ($session == null || $session->User() == null)) continue;
			
			$data = $sender ? call_user_func_array($sender, array(&$session)) : null;

			if ($data !== null)
				$ok &= $client->Send(self::Package(self::PACKAGE_EVENT, $url, $data, $session));

			unset($data, $session);
		}

		unset($out, $session, $i, $client, $clients, $sender, $filter, $filtered);

		return $ok;
	}

	/**
	 * @return QuarkCluster
	 */
	public function &Cluster () {
		return $this->_cluster;
	}

	/**
	 * @param QuarkServer $server
	 * @param QuarkPeer $network
	 * @param QuarkClient $controller
	 *
	 * @return void
	 */
	public function NodeStart (QuarkServer $server, QuarkPeer $network, QuarkClient $controller) {
		$this->ControllerURI(Quark::Config()->ClusterControllerConnect());
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
	 * @return void
	 */
	public function ClientConnect (QuarkClient $client) {
		$this->_log('cluster.node.client.connect', $client . ' -> ' . $this->_cluster->Server());

		$this->_announce();

		if ($this->_connect)
			$this->_pipe($this->_connect, 'StreamConnect', $client);
	}

	/**
	 * @param QuarkClient $client
	 * @param string $data
	 *
	 * @return void
	 *
	 * @throws QuarkArchException
	 */
	public function ClientData (QuarkClient $client, $data) {
		$this->_pipeData('Stream', $data, false, $client);
	}

	/**
	 * @param QuarkClient $client
	 *
	 * @return void
	 */
	public function ClientClose (QuarkClient $client) {
		$this->_log('cluster.node.client.close', $client . ' -> ' . $this->_cluster->Server());

		$this->_announce();

		if ($this->_close)
			$this->_pipe($this->_close, 'StreamClose', $client, null, $client->Session() ? $client->Session()->Extract() : null);
	}

	/**
	 * @param $error
	 *
	 * @return void
	 */
	public function ClientErrorCryptogram ($error) {
		$this->_errorCryptogram('cluster.node.client.error.cryptogram', $error);
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
	 * @return void
	 */
	public function NetworkClientConnect (QuarkClient $node) {
		$this->_log('cluster.node.node.client.connect', $this->_cluster->Network()->Server() . ' <- ' . $node);
	}

	/**
	 * @param QuarkClient $node
	 * @param string $data
	 *
	 * @return void
	 */
	public function NetworkClientData (QuarkClient $node, $data) {
		// TODO: Implement NetworkClientData() method.
	}

	/**
	 * @param QuarkClient $node
	 *
	 * @return void
	 */
	public function NetworkClientClose (QuarkClient $node) {
		$this->_log('cluster.node.node.client.close', $this->_cluster->Network()->Server() . ' <- ' . $node);
	}

	/**
	 * @param $error
	 *
	 * @return void
	 */
	public function NetworkClientErrorCryptogram ($error) {
		$this->_errorCryptogram('cluster.node.node.client.error.cryptogram', $error);
	}

	/**
	 * @param QuarkClient $node
	 *
	 * @return void
	 */
	public function NetworkServerConnect (QuarkClient $node) {
		$this->_log('cluster.node.node.server.connect', $node . ' -> ' . $this->_cluster->Network()->Server());

		$this->_announce();
	}

	/**
	 * @param QuarkClient $node
	 * @param string $data
	 *
	 * @return void
	 *
	 * @throws QuarkArchException
	 */
	public function NetworkServerData (QuarkClient $node = null, $data) {
		$this->_pipeData('StreamNetwork', $data, $node !== null);
	}

	/**
	 * @param QuarkClient $node
	 *
	 * @return void
	 */
	public function NetworkServerClose (QuarkClient $node) {
		$this->_log('cluster.node.node.server.close', $node . ' -> ' . $this->_cluster->Network()->Server());

		$this->_announce();
	}

	/**
	 * @param $error
	 *
	 * @return void
	 */
	public function NetworkServerErrorCryptogram ($error) {
		$this->_errorCryptogram('cluster.node.node.server.error.cryptogram', $error);
	}

	/**
	 * @param QuarkServer $controller
	 * @param QuarkServer $terminal
	 *
	 * @return void
	 */
	public function ControllerStart (QuarkServer $controller, QuarkServer $terminal) {
		// TODO: Implement ControllerStart() method.
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
	 * @return void
	 */
	public function ControllerClientConnect (QuarkClient $controller) {
		$this->_log('cluster.node.controller.connect', $this->_cluster->Controller() . ' <- ' . $controller);

		$this->_announce();
	}

	/**
	 * @param QuarkClient $controller
	 * @param string $data
	 *
	 * @return void
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
	 * @return void
	 */
	public function ControllerClientClose (QuarkClient $controller) {
		$this->_log('cluster.node.controller.close', $this->_cluster->Controller() . ' <- ' . $controller);
	}

	/**
	 * @param $error
	 *
	 * @return void
	 */
	public function ControllerClientErrorCryptogram ($error) {
		$this->_errorCryptogram('cluster.node.controller.error.cryptogram', $error);
	}

	/**
	 * @param QuarkClient $node
	 *
	 * @return void
	 */
	public function ControllerServerConnect (QuarkClient $node) {
		$this->_log('cluster.controller.node.connect', $node . ' -> ' . $this->_cluster->Controller());

		$this->_monitor();
	}

	/**
	 * @param QuarkClient $node
	 * @param string $data
	 *
	 * @return void
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
			 * @var \stdClass $node
			 */
			$node->state = $state;
			$node->signature = $signature;

			$this->_monitor();
		});
	}

	/**
	 * @param QuarkClient $node
	 *
	 * @return void
	 */
	public function ControllerServerClose (QuarkClient $node) {
		$this->_log('cluster.controller.node.close', $node . ' -> ' . $this->_cluster->Controller());

		$this->_monitor();
	}

	/**
	 * @param $error
	 *
	 * @return void
	 */
	public function ControllerServerErrorCryptogram ($error) {
		$this->_errorCryptogram('cluster.controller.node.error.cryptogram', $error);
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
	 * @return void
	 */
	public function TerminalConnect (QuarkClient $terminal) {
		$this->_log('cluster.controller.terminal.connect', $terminal . ' -> ' . $this->_cluster->Terminal());
	}

	/**
	 * @param QuarkClient $terminal
	 * @param string $data
	 *
	 * @return void
	 */
	public function TerminalData (QuarkClient $terminal, $data) {
		/** @noinspection PhpUnusedParameterInspection */
		$this->_cmd($data, self::COMMAND_AUTHORIZE, function ($client, $signature) use (&$terminal) {
			/**
			 * @var \stdClass|QuarkClient $terminal
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
			 * @var \stdClass $endpoint
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
	 * @return void
	 */
	public function TerminalClose (QuarkClient $terminal) {
		$this->_log('cluster.controller.terminal.close', $terminal . ' -> ' . $this->_cluster->Terminal());
	}

	/**
	 * @param $error
	 *
	 * @return void
	 */
	public function TerminalErrorCryptogram ($error) {
		$this->_errorCryptogram('cluster.controller.terminal.error.cryptogram', $error);
	}
}

/**
 * Class QuarkURI
 *
 * @package Quark
 */
class QuarkURI {
	const WRAPPER_TCP = 'tcp';
	const WRAPPER_UDP = 'udp';
	const WRAPPER_SSL = 'tls';
	const WRAPPER_TLS = 'tls';

	const SCHEME_HTTP = 'http';
	const SCHEME_HTTPS = 'https';

	const HOST_LOCALHOST = '127.0.0.1';
	const HOST_ALL_INTERFACES = '0.0.0.0';
	const HOST_NETWORK_192 = '192.168.0.0/16';
	const HOST_NETWORK_172 = '172.16.0.0/12';
	const HOST_NETWORK_10 = '10.0.0.0/8';

	const PORT_ANY = 0;

	const PATTERN_URL = '#([a-zA-Z0-9\-\+\.]+)\:\/\/(([^\s]*?)(\:([^\s]*?))?\@)?([^\s\/\?\:]+)(\:([\d]+))?([^\s\?]+)?(\?([^\s\#]*))?(\#([^\s]*))?#is';

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
	 * @var array $_route;
	 */
	private $_route = array();

	/**
	 * @var array $_transports
	 */
	private static $_transports = array(
		'tcp' => self::WRAPPER_TCP,
		'udp' => self::WRAPPER_UDP,
		'ssl' => self::WRAPPER_SSL,
		'tls' => self::WRAPPER_TLS,
		'ftp' => self::WRAPPER_TCP,
		'ftps' => self::WRAPPER_SSL,
		'ssh' => self::WRAPPER_SSL,
		'scp' => self::WRAPPER_SSL,
		'http' => self::WRAPPER_TCP,
		'https' => self::WRAPPER_SSL,
		'ws' => self::WRAPPER_TCP,
		'wss' => self::WRAPPER_SSL,
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
	 * http://infocisco.ru/cs_subnetting_table2.html
	 *
	 * @var string[] $_networksPrivate = []
	 */
	private static $_networksPrivate = array(
		self::HOST_NETWORK_192,
		self::HOST_NETWORK_172,
		self::HOST_NETWORK_10
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

		foreach ($url as $key => &$value)
			$out->$key = $value;

		unset($key, $value);

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
	 * @param bool $full = false
	 * @param bool $path = true
	 *
	 * @return string
	 */
	public function URI ($full = false, $path = true) {
		return $this->Hostname()
			. ($path && $this->path !== null ? Quark::NormalizePath('/' . $this->path, false) : '')
			. ($full
				? (
					($this->query ? ('?' . $this->query) : '') .
					($this->fragment ? ('#' . $this->fragment) : '')
				)
				: ''
			);
	}

	/**
	 * @param bool $user = true
	 * 
	 * @return string
	 */
	public function Hostname ($user = true) {
		if (strpos(strtolower($this->scheme), strtolower('HTTP/')) !== false)
			$this->scheme = 'http';

		return
			($this->scheme !== null ? $this->scheme : 'http')
			. '://'
			. ($user && $this->user !== null ? $this->user . ($this->pass !== null ? ':' . $this->pass : '') . '@' : '')
			. $this->host
			. ($this->port !== null && $this->port != 80 ? ':' . $this->port : '');
	}

	/**
	 * @param string $scheme = self::WRAPPER_TCP
	 * @param int $port = self::PORT_ANY
	 *
	 * @return string|bool
	 */
	public function Socket ($scheme = self::WRAPPER_TCP, $port = self::PORT_ANY) {
		return (isset(self::$_transports[$this->scheme]) ? self::$_transports[$this->scheme] : $scheme)
		. '://'
		. $this->host
		. ':'
		. (is_int($this->port) ? $this->port : (isset(self::$_ports[$this->scheme]) ? self::$_ports[$this->scheme] : $port));
	}
	
	/**
	 * @return QuarkURI|null
	 */
	public function SocketURI () {
		return self::FromURI($this->Socket());
	}

	/**
	 * @return string|null
	 */
	public function SocketTransport () {
		return isset(self::$_transports[$this->scheme])
			? (
				self::$_transports[$this->scheme] == self::WRAPPER_SSL ||
				self::$_transports[$this->scheme] == self::WRAPPER_TLS
					? self::WRAPPER_TCP
					: self::$_transports[$this->scheme]
			)
			: null;
	}

	/**
	 * @param string $host
	 * @param integer|null $port
	 *
	 * @return QuarkURI
	 */
	public function Endpoint ($host, $port = null) {
		$this->host = $host;

		$delimiter = substr_count($host, ':');

		if ($delimiter != 0) {
			$parts = explode(':', $host);
			$partsCount = sizeof($parts);

			if ($partsCount == 2) {
				$this->host = $parts[0];
				$this->port = $parts[1];
			}
			else {
				// TODO: IPv6 support
			}
		}

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
	 * @param bool $query = true
	 * @param bool $fragment = true
	 *
	 * @return string
	 */
	public function Query ($query = true, $fragment = true) {
		$empty = strlen(trim($this->query)) == 0;

		return Quark::NormalizePath(''
			. $this->path
			. (!$query || $empty ? '' : (($empty || strpos($this->query, '?') !== false ? '' : '?') . $this->query))
			. ($fragment && $this->fragment ? ('#' . $this->fragment) : '')
			, false
		);
	}

	/**
	 * @param array $query = []
	 *
	 * @return QuarkURI
	 */
	public function AppendQuery ($query = []) {
		$this->query .=
			(strlen($this->query) == 0 ? '' : '&') .
			(is_scalar($query) ? $query : http_build_query($query));

		return $this;
	}

	/**
	 * @param array $fragment = []
	 *
	 * @return QuarkURI
	 */
	public function AppendFragment ($fragment = []) {
		$this->fragment .=
			(strlen($this->fragment) == 0 ? '' : '&') .
			(is_scalar($fragment) ? $fragment : http_build_query($fragment));

		return $this;
	}

	/**
	 * @param bool $decode = true
	 *
	 * @return string[]
	 */
	private function _route ($decode = true) {
		if (sizeof($this->_route) == 0)
			$this->_route = self::ParseRoute($this->path, $decode);

		return $this->_route;
	}

	/**
	 * @param int $id = 0
	 * @param bool $decode = true
	 *
	 * @return string[]|string
	 */
	public function Route ($id = 0, $decode = true) {
		$route = $this->_route($decode);

		if (func_num_args() == 1)
			return isset($route[$id]) ? $route[$id] : '';

		return $route;
	}

	/**
	 * @param int $id = 0
	 *
	 * @return string[]|string
	 */
	public function ReverseRoute ($id = 0) {
		$route = array_reverse($this->_route());

		if (func_num_args() == 1)
			return isset($route[$id]) ? $route[$id] : '';

		return $route;
	}

	/**
	 * @param string $source = ''
	 * @param bool $decode = true
	 *
	 * @return array
	 */
	public static function ParseRoute ($source = '', $decode = true) {
		if (!is_string($source)) return array();

		$query = preg_replace('#(((\/)*)((\?|\&)(.*)))*#', '', $source);
		$route = explode('/', trim(Quark::NormalizePath(preg_replace('#\.php$#Uis', '', $query), false)));
		$buffer = array();

		foreach ($route as $i => &$component) {
			$item = trim($component);

			if (strlen($item) != 0)
				$buffer[] = $decode ? urldecode($item) : $item;
		}

		unset($i, $component);

		return $buffer;
	}

	/**
	 * @param string $uri = ''
	 * @param array $query = []
	 * @param bool $weak = false
	 *
	 * @return string
	 */
	public static function BuildQuery ($uri = '', $query = [], $weak = false) {
		$params = http_build_query($query);

		return $weak && strlen($params) == 0
			? ''
			: (strpos($uri, '?') !== false ? '&' : '?') . $params;
	}

	/**
	 * @param string $uri = ''
	 * @param array $query = []
	 * @param bool $weak = true
	 *
	 * @return string
	 */
	public static function Build ($uri = '', $query = [], $weak = true) {
		return $uri . self::BuildQuery($uri, $query, $weak);
	}

	/**
	 * @param string $url
	 * @param $params = []
	 *
	 * @return QuarkURI
	 */
	public function Compose ($url = '', $params = []) {
		$uri = self::FromURI($url);

		$this->scheme = $uri->scheme;
		$this->user = $uri->user;
		$this->pass = $uri->pass;
		$this->host = $uri->host;
		$this->port = $uri->port;
		$this->path = $uri->path;
		$this->query = $uri->query;
		$this->fragment = $uri->fragment;

		$this->ParamsMerge((object)$params);

		return $this;
	}

	/**
	 * @param $query = []
	 * @param int $enc_type = PHP_QUERY_RFC1738|PHP_QUERY_RFC3986
	 * 
	 * @return object
	 */
	public function Params ($query = [], $enc_type = PHP_QUERY_RFC1738) {
		if (func_num_args() != 0)
			$this->query = http_build_query((array)$query, '', '&', $enc_type);
		
		return QuarkObject::Merge($this->Options());
	}

	/**
	 * @param $params = []
	 *
	 * @return QuarkURI
	 */
	public function ParamsMerge ($params = []) {
		if (func_num_args() != 0)
			$this->Params(QuarkObject::Merge($this->Options(), $params));

		return $this;
	}

	/**
	 * @param string $key = ''
	 *
	 * @return array|string|null
	 */
	public function Options ($key = '') {
		parse_str($this->query, $params);

		$params = is_array($params) ? $params : array();
		
		return func_num_args() == 0
			? $params
			: (isset($params[$key]) ? $params[$key] : null);
	}

	/**
	 * @param string $key = ''
	 *
	 * @return object
	 */
	public function RemoveOption ($key = '') {
		$options = $this->Options();

		if (isset($options[$key]))
			unset($options[$key]);

		return $this->Params($options);
	}

	/**
	 * @param QuarkURI $uri
	 *
	 * @return bool
	 */
	public function Equal (QuarkURI $uri) {
		foreach ($this as $key => &$value)
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
	 * @param string $ip = ''
	 *
	 * @return bool
	 */
	public static function IsHostFromPrivateNetworks ($ip = '') {
		foreach (self::$_networksPrivate as $i => &$network)
			if (self::IsHostFromNetwork($ip, $network)) return true;

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
	 * @param string $host = ''
	 *
	 * @return QuarkURI
	 */
	public function ConnectionURI ($host = '') {
		$uri = clone $this;
			$uri->host = func_num_args() != 0
				? $host
				: ($uri->host == self::HOST_ALL_INTERFACES
					? Quark::HostIP()
					: $uri->host
				);

		return $uri;
	}
	
	/**
	 * @return bool
	 */
	public function Secure () {
		return isset(self::$_transports[$this->scheme])
			&& (
				self::$_transports[$this->scheme] == self::WRAPPER_SSL ||
				self::$_transports[$this->scheme] == self::WRAPPER_TLS
			);
	}

	/**
	 * @param string $scheme = ''
	 *
	 * @return string|null
	 */
	public static function TransportOf ($scheme = '') {
		return isset(self::$_transports[$scheme]) ? self::$_transports[$scheme] : null;
	}

	/**
	 * @param string $data = ''
	 * @param bool $uri = false
	 *
	 * @return string[]|QuarkURI[]
	 */
	public static function URLs ($data = '', $uri = false) {
		$out = array();

		if (preg_match_all(self::PATTERN_URL, $data, $found, PREG_SET_ORDER))
			foreach ($found as $i => &$url)
				$out[] = $uri ? self::FromURI($url[0]) : $url[0];

		return $out;
	}

	/**
	 * @param string $data = ''
	 *
	 * @return string
	 */
	public static function Base64Encode ($data = '') {
		return str_replace('=', '', strtr(base64_encode($data), '+/', '-_'));
	}

	/**
	 * @param string $data = ''
	 *
	 * @return string
	 */
	public static function Base64Decode ($data = '') {
		$remainder = strlen($data) % 4;

		if ($remainder)
			$data .= str_repeat('=', 4 - $remainder);

		return base64_decode(strtr($data, '-_', '+/'));
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
	const HTTP_PROTOCOL_REQUEST = '#^([^\r\n]*) ([^\r\n]*) ([^\r\n]*)\r?\n((?:[^\r\n]+\r?\n)*)\r?\n(.*?)#Uis';
	const HTTP_PROTOCOL_RESPONSE = '#^([^\r\n\s]+) ([^\r\n]*)\r?\n((?:[^\r\n]+\r?\n)*)\r?\n(.*)#is';

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
	const HEADER_CONTENT_ENCODING = 'Content-Encoding';
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
	const HEADER_SERVICE_WORKER_ALLOWED = 'Service-Worker-Allowed';

	const STATUS_101_SWITCHING_PROTOCOLS = '101 Switching Protocols';
	const STATUS_200_OK = '200 OK';
	const STATUS_201_CREATED = '201 Created';
	const STATUS_202_ACCEPTED = '202 Accepted';
	const STATUS_302_FOUND = '302 Found';
	const STATUS_400_BAD_REQUEST = '400 Bad Request';
	const STATUS_401_UNAUTHORIZED = '401 Unauthorized';
	const STATUS_403_FORBIDDEN = '403 Forbidden';
	const STATUS_404_NOT_FOUND = '404 Not Found';
	const STATUS_410_GONE = '410 Gone';
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

	const AUTHORIZATION_BASIC = 'Basic';
	const AUTHORIZATION_DIGEST = 'Digest';
	const AUTHORIZATION_BEARER = 'Bearer';

	const KEY_AUTHORIZATION = '_a';
	const KEY_SIGNATURE = '_s';

	const RESPONSE_BUFFER = 4096;

	const USER_AGENT_QUARK = 'QuarkHTTPClient';

	/**
	 * @var string $_raw = ''
	 */
	private $_raw = '';

	/**
	 * @var string $_rawData = ''
	 */
	private $_rawData = '';

	/**
	 * @var callable $_rawProcessor = null
	 */
	private $_rawProcessor = null;

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
	 * @var QuarkMIMEType[] $_types = []
	 */
	private $_types = array();

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
	 * @var callable $_headerControl = null
	 */
	private $_headerControl = null;

	/**
	 * @var bool $_authorizationPrompt = false
	 */
	private $_authorizationPrompt = false;

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
			$this->_data = new \stdClass();

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
	 * @param QuarkURI $uri
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
	 * @param string $method = self::METHOD_GET
	 * @param IQuarkIOProcessor $processor = null
	 * @param mixed $data = []
	 *
	 * @return QuarkDTO
	 */
	public static function ForRequest ($method = self::METHOD_GET, IQuarkIOProcessor $processor = null, $data = []) {
		$dto = new self($processor, null, $method);
		$dto->Data($data);

		return $dto;
	}

	/**
	 * @param IQuarkIOProcessor $processor = null
	 * @param string $status = self::STATUS_200_OK
	 *
	 * @return QuarkDTO
	 */
	public static function ForResponse (IQuarkIOProcessor $processor = null, $status = self::STATUS_200_OK) {
		$out = new self($processor);
		$out->Status($status);

		return $out;
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
	 * @param IQuarkSpecifiedViewResource $resource = null
	 * @param $vars = []
	 * @param IQuarkSpecifiedViewResource[] $dependencies = []
	 * @param bool $minimize = true
	 *
	 * @return QuarkDTO
	 */
	public static function ForResource (IQuarkSpecifiedViewResource $resource = null, $vars = [], $dependencies = [], $minimize = true) {
		$response = self::ForResponse();
		$response->Header(self::HEADER_CONTENT_TYPE, $resource->Type()->ViewResourceTypeMIME());
		$response->Data(QuarkView::InlineResource($resource, $vars, $dependencies, $minimize));

		return $response;
	}

	/**
	 * @param string $location = ''
	 * @param string $scope = ''
	 * @param $vars = []
	 * @param IQuarkSpecifiedViewResource[] $dependencies = []
	 * @param bool $minimize = true
	 *
	 * @return QuarkDTO
	 */
	public static function ForServiceWorker ($location = '', $scope = '', $vars = [], $dependencies = [], $minimize = true) {
		$response = self::ForResource(QuarkGenericViewResource::JS($location), $vars, $dependencies, $minimize);

		$response->Header(self::HEADER_SERVICE_WORKER_ALLOWED, $scope);

		return $response;
	}

	/**
	 * @param string $authenticate = ''
	 *
	 * @return QuarkDTO
	 */
	public static function ForHTTPAuthorizationPrompt ($authenticate = '') {
		$response = self::ForStatus(self::STATUS_401_UNAUTHORIZED);
		$response->AuthorizationPrompt(true);

		if (func_num_args() != 0)
			$response->Header(self::HEADER_WWW_AUTHENTICATE, $authenticate);

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
	 * @param callable $headerControl = null
	 *
	 * @return QuarkDTO
	 */
	public function Merge ($data = [], $processor = true, $status = true, callable $headerControl = null) {
		if (!($data instanceof QuarkDTO)) $this->MergeData($data);
		else {
			$this->_raw = $data->Raw();

			// https://www.php.net/manual/ru/function.array-merge.php#92602
			$this->_method = $data->Method();
			$this->_boundary = $data->Boundary();
			$this->_headers += $data->Headers();
			$this->_cookies = array_merge($this->_cookies, $data->Cookies());
			$this->_languages += $data->Languages();
			$this->_types += $data->Types();
			$this->_uri = $data->URI() == null ? $this->_uri : $data->URI();
			$this->_remote = $data->Remote() == null ? $this->_remote : $data->Remote();
			$this->_charset = $data->Charset();

			if ($status)
				$this->_status = $data->Status();

			if ($processor)
				$this->_processor = $data->Processor();

			if ($headerControl != null)
				$this->_headerControl = $data->HeaderControl();

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
		if (!is_scalar($this->_data) && is_scalar($data)) return $this->_data;

		if (is_string($data) && is_string($this->_data)) $this->_data .= $data;
		elseif ($data instanceof QuarkView) $this->_data = $data;
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
	 * @param $params = []
	 *
	 * @return object
	 */
	public function URIParams ($params = []) {
		if ($this->_uri == null)
			$this->_uri = new QuarkURI();

		return func_num_args() == 0 ? $this->_uri->Params() : $this->_uri->Params($params);
	}

	/**
	 * @param $params = []
	 *
	 * @return QuarkURI
	 */
	public function URIInit ($params = []) {
		if ($this->_uri == null)
			$this->_uri = new QuarkURI();

		return func_num_args() != 0 ? $this->_uri->ParamsMerge($params) : $this->_uri;
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
	 * @param string $method = ''
	 *
	 * @return string
	 */
	public function Method ($method = '') {
		if (func_num_args() == 1 && is_string($method))
			$this->_method = strtoupper(trim($method));

		return $this->_method;
	}

	/**
	 * @param int|string $code = 0
	 * @param string $text = 'OK'
	 *
	 * @return string
	 */
	public function Status ($code = 0, $text = 'OK') {
		if (func_num_args() != 0 && is_scalar($code))
			$this->_status = trim($code . (func_num_args() == 2 && is_scalar($text) ? ' ' . $text : ''));

		return $this->_status;
	}

	/**
	 * @return int
	 */
	public function StatusCode () {
		return (int)substr($this->_status, 0, 3);
	}

	/**
	 * @param array $headers = []
	 *
	 * @return array
	 */
	public function Headers ($headers = []) {
		if (func_num_args() == 1 && is_array($headers)) {
			$assoc = QuarkObject::isAssociative($headers);

			foreach ($headers as $key => &$value) {
				if (!$assoc) {
					$header = explode(': ', $value);
					$key = $header[0];
					$value = isset($header[1]) ? $header[1] : '';
				}

				$this->Header($key, $value);
			}

			unset($key, $value);
		}

		return $this->_headers;
	}

	/**
	 * @param $key
	 * @param $value = ''
	 *
	 * @return mixed
	 */
	public function Header ($key, $value = '') {
		$value = trim($value);

		if (func_num_args() == 2) {
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

					if (sizeof($type) == 2) $this->_charset = $type[1];
					if (sizeof($boundary) == 2) $this->_boundary = $boundary[1];

					$this->_multipart = strpos($type[0], 'multipart/') !== false;

					if (sizeof($this->_types) == 0)
						$this->_types = QuarkMIMEType::FromHeader($value);
					break;

				case self::HEADER_ACCEPT:
					$this->_types = QuarkMIMEType::FromHeader($value);
					break;

				default: break;
			}
		}

		return isset($this->_headers[$key]) ? $this->_headers[$key] : null;
	}

	/**
	 * @param QuarkCookie[] $cookies = []
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
		$this->_cookies[] = $cookie;

		return $this;
	}

	/**
	 * @param string $name = ''
	 *
	 * @return QuarkCookie
	 */
	public function GetCookieByName ($name = '') {
		foreach ($this->_cookies as $cookie)
			if ($cookie->name == $name) return $cookie;

		return null;
	}

	/**
	 * @param QuarkLanguage[] $languages = []
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
		$this->_languages[] = $language;

		return $this;
	}

	/**
	 * @param string $name = QuarkLanguage::ANY
	 * @param bool $strict = false
	 *
	 * @return QuarkLanguage
	 */
	public function GetLanguageByName ($name = QuarkLanguage::ANY, $strict = false) {
		foreach ($this->_languages as $language)
			if ($name == QuarkLanguage::ANY || $language->Is($name, $strict)) return $language;

		return null;
	}

	/**
	 * @param int $quantity = 1
	 *
	 * @return QuarkLanguage
	 */
	public function GetLanguageByQuantity ($quantity = 1) {
		foreach ($this->_languages as $language)
			if ($language->Quantity() == $quantity) return $language;
		
		return null;
	}

	/**
	 * @param int $quantity = 1
	 * @param bool $strict = false
	 *
	 * @return string
	 */
	public function ExpectedLanguage ($quantity = 1, $strict = false) {
		$language = $this->GetLanguageByQuantity($quantity);
		$out = $language == null ? QuarkLanguage::ANY : $language->Name();

		if (!$strict && $out != QuarkLanguage::ANY && !strpos($out, '-'))
			$out .= '-' . strtoupper($out);

		return $out;
	}

	/**
	 * @param string $name = QuarkLanguage::ANY
	 * @param bool $strict = false
	 *
	 * @return bool
	 */
	public function AcceptLanguage ($name = QuarkLanguage::ANY, $strict = false) {
		return $this->GetLanguageByName($name, $strict) != null;
	}

	/**
	 * @param QuarkMIMEType[] $types = []
	 *
	 * @return QuarkMIMEType[]
	 */
	public function Types ($types = []) {
		if (func_num_args() != 0)
			$this->_types = $types;
		
		return $this->_types;
	}

	/**
	 * @param QuarkMIMEType $type
	 *
	 * @return QuarkDTO
	 */
	public function Type (QuarkMIMEType $type) {
		$this->_types[] = $type;

		return $this;
	}

	/**
	 * @param string $name = QuarkMIMEType::ANY
	 * @param bool $strict = false
	 *
	 * @return QuarkMIMEType
	 */
	public function GetTypeByName ($name = QuarkMIMEType::ANY, $strict = false) {
		foreach ($this->_types as $type)
			if ($name == QuarkMIMEType::ANY || $type->Is($name, $strict)) return $type;

		return null;
	}

	/**
	 * @param int $quantity = 1
	 *
	 * @return QuarkMIMEType
	 */
	public function GetTypeByQuantity ($quantity = 1) {
		foreach ($this->_types as $type)
			if ($type->Quantity() == $quantity) return $type;

		return null;
	}

	/**
	 * @param int $quantity = 1
	 *
	 * @return string
	 */
	public function ExpectedType ($quantity = 1) {
		$type = $this->GetTypeByQuantity($quantity);

		return $type == null ? QuarkMIMEType::ANY : $type->Name();
	}

	/**
	 * @param string $type = QuarkMIMEType::ANY
	 * @param bool $strict = false
	 *
	 * @return bool
	 */
	public function AcceptType ($type = QuarkMIMEType::ANY, $strict = false) {
		return $this->GetTypeByName($type, $strict) != null;
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
	 * @return mixed
	 */
	public function UserAgentQuark () {
		return $this->Header(self::HEADER_USER_AGENT, self::USER_AGENT_QUARK);
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
	 * @return string
	 */
	public function RawHeaders () {
		return preg_replace('#\r\n\r\n(.*)#', '', $this->_raw);
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
	 * @param callable $processor = null
	 *
	 * @return callable
	 */
	public function RawProcessor (callable $processor = null) {
		if (func_num_args() != 0)
			$this->_rawProcessor = $processor;

		return $this->_rawProcessor;
	}

	/**
	 * @param string $data = ''
	 *
	 * @return string
	 */
	public function RawProcessorInvoke ($data = '') {
		return $this->_rawProcessor ? call_user_func_array($this->_rawProcessor, array(&$data)) : $data;
	}

	/**
	 * @param string $data = ''
	 *
	 * @return string
	 */
	public function Masquerade ($data = '') {
		return $this->RawHeaders() . "\r\n\r\n" . $data;
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
	 * @param string $username = ''
	 * @param string $password = ''
	 *
	 * @return QuarkKeyValuePair
	 */
	public function AuthorizationBasic ($username = '', $password = '') {
		return $this->Authorization(new QuarkKeyValuePair(
			self::AUTHORIZATION_BASIC,
			self::HTTPBasicAuthorization($username, $password)
		));
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
	 * @param bool $prompt = false
	 *
	 * @return bool
	 */
	public function AuthorizationPrompt ($prompt = false) {
		if (func_num_args() != 0)
			$this->_authorizationPrompt = $prompt;

		return $this->_authorizationPrompt;
	}

	/**
	 * @param callable $headerControl = null
	 *
	 * @return callable
	 */
	public function HeaderControl (callable $headerControl = null) {
		if (func_num_args() != 0)
			$this->_headerControl = $headerControl;

		return $this->_headerControl;
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
	 * @param string $raw = ''
	 * @param bool $batch = false
	 * @param bool $reset = false
	 *
	 * @return QuarkDTO
	 */
	public function UnserializeResponse ($raw = '', $batch = false, $reset = false) {
		$this->_raw = $raw;

		if (preg_match(self::HTTP_PROTOCOL_RESPONSE, $this->_raw, $found)) {
			$this->_rawData = $found[4];

			$this->Protocol($found[1]);
			$this->Status($found[2]);

			if ($this->_processor == null)
				$this->_processor = new QuarkHTMLIOProcessor();

			$this->_unserializeHeaders($found[3]);
			$this->_unserializeBody($this->_rawData, $batch, $reset);
		}

		unset($found);

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

		if (!isset($this->_headers[self::HEADER_CONTENT_LENGTH]))
			$this->_headers[self::HEADER_CONTENT_LENGTH] = $this->_length;

		$this->_headers[self::HEADER_CONTENT_TYPE] = $typeSet
			? $typeValue
			: ($this->_multipart
				? ($client ? self::MULTIPART_FORM_DATA : self::MULTIPART_MIXED) . '; boundary=' . $this->_boundary
				: $this->_processor->MimeType() . '; charset=' . $this->_charset
			);

		if ($client) {
			$this->_headers[self::HEADER_HOST] = $this->_uri->host; // TODO: investigate including of port (RFC for HTTP/1.1)

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

		foreach ($this->_headers as $key => &$value)
			$headers[] = $key . ': ' . $value;

		unset($key, $value);

		if ($this->_headerControl != null) {
			$control = $this->_headerControl;
			$out = $control($headers);

			if (is_array($out))
				$headers = $out;

			unset($out);
		}

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

		if ($file && !$value->Loaded())
			$value->Load();

		$contents = $file ? $value->Content() : $value;

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
		if (preg_match_all('#(.*)\: (.*)\n#Uis', $raw . "\r\n", $headers, PREG_SET_ORDER)) {
			foreach ($headers as $i => &$header)
				$this->Header($header[1], $header[2]);

			unset($i, $header, $headers);
		}

		return $this;
	}

	/**
	 * @param string $raw
	 * @param bool $batch = false
	 * @param bool $reset = false
	 *
	 * @return QuarkDTO
	 */
	private function _unserializeBody ($raw, $batch = false, $reset = false) {
		if (!$this->_multipart || strpos($raw, '--' . $this->_boundary) === false) {
			if (!$batch) $this->_data = QuarkObject::Merge($reset ? null : $this->_data, $this->_processor->Decode($raw));
			else {
				$chunks = $this->_processor->Batch($raw, true);

				foreach ($chunks as $i => &$chunk) {
					$this->_data = QuarkObject::Merge($this->_data, $this->_processor->Decode($chunk));
					usleep(QuarkThreadSet::TICK);
				}

				unset($i, $chunk);
			}
		}
		else {
			$parts = explode('--' . $this->_boundary, $raw);

			foreach ($parts as $i => &$part)
				$this->_unserializePart($part);

			unset($i, $part);
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

			if (preg_match_all('#(.*)\: (.*)\n#Uis', trim($raw) . "\r\n", $headers, PREG_SET_ORDER)) {
				foreach ($headers as $i => &$header)
					$head[$header[1]] = trim($header[2]);

				unset($i, $header, $headers);
			}

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
					
					$fs = new QuarkModel(QuarkFile::ForTransfer(trim($file, '"'), $found[2]));
					$this->_files[] = $fs;
				}

				if ($position == 'form-data') {
					parse_str(trim($name, '"'), $storage);

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
	 * @return void
	 */
	public function EventConnect (QuarkClient &$client) {
		$client->TriggerConnect();
	}

	/**
	 * @param QuarkClient &$client
	 * @param string $data
	 *
	 * @return void
	 */
	public function EventData (QuarkClient &$client, $data) {
		if ($this->_divider == null) {
			$client->TriggerData($data);
			return;
		}

		$this->_buffer .= $data;

		$parts = call_user_func_array($this->_divider, array(&$this->_buffer, true));
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
	 * @return void
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
class QuarkHTTPClient implements IQuarkEventable {
	const EVENT_ASYNC_DATA = 'client.async.data';
	const EVENT_ASYNC_ERROR = 'client.async.error';

	use QuarkEvent;

	/**
	 * @var QuarkClient $_client
	 */
	private $_client;

	/**
	 * @var QuarkDTO $_request
	 */
	private $_request;

	/**
	 * @var QuarkDTO $_response
	 */
	private $_response;

	/**
	 * @var bool $_asyncInit = false
	 */
	private $_asyncInit = false;

	/**
	 * @param QuarkDTO $request
	 * @param QuarkDTO $response
	 */
	public function __construct (QuarkDTO $request, QuarkDTO $response = null) {
		$this->_request = $request;
		$this->_response = $response;

		if ($this->_response != null)
			$this->_request->Header(QuarkDTO::HEADER_ACCEPT, $this->_response->Processor()->MimeType());
	}

	/**
	 * @return QuarkClient
	 */
	public function &Client () {
		return $this->_client;
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
	 * @param callable $callback
	 *
	 * @return QuarkHTTPClient
	 */
	public function AsyncData (callable $callback) {
		$this->On(self::EVENT_ASYNC_DATA, $callback);

		return $this;
	}

	/**
	 * @param int $max = -1
	 *
	 * @return bool
	 */
	public function AsyncPipe ($max = -1) {
		return $this->_client->Pipe($max);
	}

	/**
	 * @return bool
	 */
	public function Connected () {
		return $this->_client->Connected();
	}

	/**
	 * @param QuarkURI|string $uri
	 * @param QuarkDTO $request
	 * @param QuarkDTO $response
	 * @param QuarkCertificate $certificate
	 * @param int $timeout = 10
	 * @param bool $sync = true
	 * @param bool $trace = false
	 *
	 * @return QuarkDTO|bool
	 */
	public static function To ($uri, QuarkDTO $request, QuarkDTO $response = null, QuarkCertificate $certificate = null, $timeout = 10, $sync = true, $trace = false) {
		$http = new self($request, $response);
		$client = new QuarkClient($uri, new QuarkTCPNetworkTransport(), $certificate, $timeout, $sync);

		$client->On(QuarkClient::EVENT_CONNECT, function (QuarkClient $client) use (&$uri, &$http, &$trace) {
			if ($http->_request == null) return false;

			if ($http->_response == null)
				$http->_response = new QuarkDTO();

			$client->URI()->Compose($uri, $http->_request->URI() ? $http->_request->URI()->Options() : array());

			$http->_request->URI($client->URI());
			$http->_response->URI($client->URI());
			
			$http->_request->Remote($client->ConnectionURI(true));
			$http->_response->Remote($client->ConnectionURI(true));

			$http->_response->Method($http->_request->Method());

			$request = $http->_request->SerializeRequest();
			if ($trace) Quark::Trace($request);

			return $client->Send($request);
		});

		$client->On(QuarkClient::EVENT_DATA, function (QuarkClient $client, $response) use (&$http, &$trace) {
			if ($trace) Quark::Trace($response);
			$http->_response->UnserializeResponse($response);

			return $client->Close();
		});

		$client->On(QuarkClient::EVENT_ERROR_CONNECT, function ($error) {
			Quark::Log($error . '. Error: ' . QuarkException::LastError(), Quark::LOG_WARN);
		});

		$client->On(QuarkServer::EVENT_ERROR_CRYPTOGRAM, function ($error) {
			Quark::Log($error . '. Error: ' . QuarkException::LastError(), Quark::LOG_WARN);
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
	 * @param bool $sync = true
	 * @param bool $trace = false
	 *
	 * @return QuarkFile
	 */
	public static function Download ($uri, QuarkDTO $request = null, QuarkDTO $response = null, QuarkCertificate $certificate = null, $timeout = 10, $sync = true, $trace = false) {
		if ($request == null)
			$request = QuarkDTO::ForGET();

		$out = self::To($uri, $request, $response, $certificate, $timeout, $sync, $trace);

		if (!$out || $out->Status() != QuarkDTO::STATUS_200_OK) return null;

		$file = new QuarkFile();

		$uri = ($uri instanceof QuarkURI ? $uri : QuarkURI::FromURI($uri));

		$name = array_reverse($uri->Route())[0];

		$file->Content($out->RawData(), true, true);
		$file->name = $name . (strpos($name, '.') === false ? $file->extension : '');

		return $file;
	}

	/**
	 * @param QuarkURI|string $uri
	 * @param QuarkDTO $request
	 * @param QuarkDTO $response
	 * @param QuarkCertificate $certificate
	 * @param int $timeout = 10
	 * @param bool $trace = false
	 *
	 * @return QuarkHTTPClient
	 */
	public static function AsyncTo ($uri, QuarkDTO $request, QuarkDTO $response = null, QuarkCertificate $certificate = null, $timeout = 10, $trace = false) {
		$http = new self($request, $response);
		$http->_client = new QuarkClient($uri, new QuarkTCPNetworkTransport(), $certificate, $timeout, false);

		$http->_client->On(QuarkClient::EVENT_CONNECT, function (QuarkClient $client) use (&$uri, &$http, &$trace) {
			if ($http->_request == null) return false;

			if ($http->_response == null)
				$http->_response = new QuarkDTO();

			$client->URI()->Compose($uri, $http->_request->URI() ? $http->_request->URI()->Options() : array());

			$http->_request->URI($client->URI());
			$http->_response->URI($client->URI());

			$http->_request->Remote($client->ConnectionURI(true));
			$http->_response->Remote($client->ConnectionURI(true));

			$http->_response->Method($http->_request->Method());

			$request = $http->_request->SerializeRequest();
			if ($trace) Quark::Trace($request);

			return $client->Send($request);
		});

		/** @noinspection PhpUnusedParameterInspection */
		$http->_client->On(QuarkClient::EVENT_DATA, function (QuarkClient $client, $data) use (&$http, &$trace) {
			$data = $http->_asyncInit ? $http->_response->RawProcessorInvoke($data) : $data;
			$chunks = $http->_response->Processor()->Batch($data, !$http->_asyncInit);

			foreach ($chunks as $i => &$chunk) {
				if ($http->_asyncInit) $chunk = $http->_response->Masquerade($chunk);
				else $http->_asyncInit = true;

				if ($trace) Quark::Trace($chunk);

				$http->_response->UnserializeResponse($chunk, false, true);

				if ($http->_response->Status() != QuarkDTO::STATUS_200_OK)
					$http->TriggerArgs(self::EVENT_ASYNC_ERROR, array(&$http->_request, &$http->_response));

				$http->TriggerArgs(self::EVENT_ASYNC_DATA, array(&$http->_response));
			}

			unset($chunk, $i, $chunks, $data);
		});

		$http->_client->On(QuarkClient::EVENT_ERROR_CONNECT, function ($error) {
			Quark::Log($error . '. Error: ' . QuarkException::LastError(), Quark::LOG_WARN);
		});

		$http->_client->On(QuarkServer::EVENT_ERROR_CRYPTOGRAM, function ($error) {
			Quark::Log($error . '. Error: ' . QuarkException::LastError(), Quark::LOG_WARN);
		});

		return $http->_client->Connect() ? $http : null;
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
			$request->Remote($client->ConnectionURI(true));

			$client->Send(call_user_func_array($this->_request, array(&$request, &$client)));
		});

		$this->_server->On(QuarkServer::EVENT_ERROR_LISTEN, function ($error) {
			Quark::Log($error . '. Error: ' . QuarkException::LastError(), Quark::LOG_WARN);
		});

		$this->_server->On(QuarkClient::EVENT_ERROR_CRYPTOGRAM, function ($error) {
			Quark::Log($error . '. Error: ' . QuarkException::LastError() . '. Check that you selected secure transport for listening (e.g.: ssl,https,wss) and public/private keys are corresponding for given certificate.', Quark::LOG_WARN);
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
}

/**
 * Class QuarkHTTPServerHost
 *
 * @package Quark
 */
class QuarkHTTPServerHost implements IQuarkEventable {
	const UNKNOWN_URI = '<uri_unknown>';

	const EVENT_REQUEST = 'event.request';
	const EVENT_RESPONSE = 'event.response';
	const EVENT_HTTP_ERROR = 'event.error';
	const EVENT_HTTP_403 = 'event.403';
	const EVENT_HTTP_404 = 'event.404';
	const EVENT_HTTP_500 = 'event.500';

	use QuarkEvent;

	/**
	 * @var string $_root = ''
	 */
	private $_root = '';

	/**
	 * @var string $_base = ''
	 */
	private $_base = '';

	/**
	 * @var string $_namespace = ''
	 */
	private $_namespace = '';

	/**
	 * @var string[] $_hosts = []
	 */
	private $_hosts = array();

	/**
	 * @var string[] $_allow = []
	 */
	private $_allow = array();

	/**
	 * @var string[] $_deny = []
	 */
	private $_deny = array();

	/**
	 * @var IQuarkIOProcessor $_processorRequest
	 */
	private $_processorRequest;

	/**
	 * @var IQuarkIOProcessor $_processorResponse
	 */
	private $_processorResponse;

	/**
	 * @var QuarkHTTPServer $_server
	 */
	private $_server;

	/**
	 * @param QuarkURI|string $uri = ''
	 * @param string $root = ''
	 * @param string $base = ''
	 * @param string $namespace = ''
	 * @param QuarkCertificate $certificate = null
	 */
	public function __construct ($uri = '', $root = '', $base = '', $namespace = '', QuarkCertificate $certificate = null) {
		$this->_server = new QuarkHTTPServer(
			$uri,
			function (QuarkDTO $request, QuarkClient $client) { return $this->_request($request, $client); },
			$certificate
		);

		$this->Root($root);
		$this->Base($base);
		$this->NamespaceRoot($namespace);

		$this->ProcessorRequest(new QuarkFormIOProcessor());
		$this->ProcessorResponse(new QuarkHTMLIOProcessor());
	}

	/**
	 * http://stackoverflow.com/a/684005/2097055
	 *
	 * @param QuarkDTO $request
	 * @param QuarkClient $client
	 *
	 * @return string
	 */
	private function _request (QuarkDTO $request, QuarkClient $client) {
		$this->TriggerArgs(self::EVENT_REQUEST, array(&$request));

		$host = $request->Header(QuarkDTO::HEADER_HOST);
		if (sizeof($this->_hosts) != 0 && !preg_match('#' . implode('|', $this->_hosts) . '#Uis', $host)) {
			$client->Close();
			return null;
		}

		$uri = $request->URI();
		$query = $uri instanceof QuarkURI ? $uri->Query() : self::UNKNOWN_URI;

		if (sizeof($this->_allow) != 0 && !preg_match('#' . implode('|', $this->_allow) . '#Uis', $request->Remote()->host)) {
			$response = QuarkDTO::ForStatus(QuarkDTO::STATUS_403_FORBIDDEN);
			$out = $response->SerializeResponse();
			$this->TriggerArgs(self::EVENT_HTTP_ERROR, array(&$request, &$response));
			$this->TriggerArgs(self::EVENT_HTTP_403, array(&$request, &$response));
		}
		else {
			try {
				if (sizeof($this->_deny) != 0 && preg_match('#' . implode('|', $this->_deny) . '#Uis', $query)) {
					$response = QuarkDTO::ForStatus(QuarkDTO::STATUS_403_FORBIDDEN);
					$out = $response->SerializeResponse();
					$this->TriggerArgs(self::EVENT_HTTP_ERROR, array(&$request, &$response));
					$this->TriggerArgs(self::EVENT_HTTP_403, array(&$request, &$response));
				}
				else {
					$file = isset($uri->path) ? new QuarkFile($this->_root . $uri->path) : null;

					if ($file != null && $file->Exists()) {
						$file->Load();

						$response = new QuarkDTO();
						$response->Data($file);

						$out = $response->SerializeResponse();
					}
					else {
						$service = QuarkService::Custom($query, $this->_base, $this->_namespace, QuarkService::POSTFIX_SERVICE);

						$service->InitProcessors();
						$service->Input()->UnserializeRequest($request->Raw());

						$body = $service->Pipeline(false);

						if ($service->Output()->Header(QuarkDTO::HEADER_LOCATION)) {
							$response = QuarkDTO::ForRedirect($service->Output()->Header(QuarkDTO::HEADER_LOCATION));
							$response->Merge($service->Session()->Output(), true, false);

							$out = $response->SerializeResponse();
						}
						else {
							$out = $service->Output()->SerializeResponseHeaders() . "\r\n\r\n" . $body;
							$response = $service->Output();
						}

						unset($body, $service);
					}
				}
			}
			catch (QuarkHTTPException $e) {
				$response = QuarkDTO::ForStatus($e->Status());
				$out = $response->SerializeResponse();
				$this->TriggerArgs(self::EVENT_HTTP_ERROR, array(&$request, &$response, &$e));
			}
			catch (\Exception $e) {
				$response = QuarkDTO::ForStatus(QuarkDTO::STATUS_500_SERVER_ERROR);
				$out = $response->SerializeResponse();
				$this->TriggerArgs(self::EVENT_HTTP_ERROR, array(&$request, &$response, &$e));
				$this->TriggerArgs(self::EVENT_HTTP_500, array(&$request, &$response));
			}
		}

		$this->TriggerArgs(self::EVENT_RESPONSE, array(&$request, &$response, &$out));

		unset($file, $response, $request);

		return $out;
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
	 * @param string $root = ''
	 *
	 * @return string
	 */
	public function Root ($root = '') {
		if (func_num_args() != 0)
			$this->_root = $root;

		return $this->_root;
	}

	/**
	 * @param string $base = ''
	 *
	 * @return string
	 */
	public function Base ($base = '') {
		if (func_num_args() != 0)
			$this->_base = $base;

		return $this->_base;
	}

	/**
	 * @param string $namespace = ''
	 *
	 * @return string
	 */
	public function NamespaceRoot ($namespace = '') {
		if (func_num_args() != 0)
			$this->_namespace = $namespace;

		return $this->_namespace;
	}

	/**
	 * @param string[] $hosts = []
	 *
	 * @return string[]
	 */
	public function Hosts ($hosts = []) {
		if (func_num_args() != 0)
			$this->_hosts = $hosts;

		return $this->_hosts;
	}

	/**
	 * @param string[] $allow = []
	 *
	 * @return string[]
	 */
	public function Allow ($allow = []) {
		if (func_num_args() != 0)
			$this->_allow = $allow;

		return $this->_allow;
	}

	/**
	 * @param string $host = ''
	 *
	 * @return QuarkHTTPServerHost
	 */
	public function AllowHost ($host = '') {
		$this->_allow[] = str_replace('.', '\\.', str_replace('*', '\d+', $host));

		return $this;
	}

	/**
	 * @param string[] $deny = []
	 *
	 * @return string[]
	 */
	public function Deny ($deny = []) {
		if (func_num_args() != 0)
			$this->_deny = $deny;

		return $this->_deny;
	}

	/**
	 * @param IQuarkIOProcessor $processor = null
	 *
	 * @return IQuarkIOProcessor
	 */
	public function ProcessorRequest (IQuarkIOProcessor $processor = null) {
		if (func_num_args() == 1 && $processor != null)
			$this->_processorRequest = $processor;

		return $this->_processorRequest;
	}

	/**
	 * @param IQuarkIOProcessor $processor = null
	 *
	 * @return IQuarkIOProcessor
	 */
	public function ProcessorResponse (IQuarkIOProcessor $processor = null) {
		if (func_num_args() == 1 && $processor != null)
			$this->_processorResponse = $processor;

		return $this->_processorResponse;
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

	const SAME_SITE_STRICT = 'Strict';
	const SAME_SITE_LAX = 'Lax';
	const SAME_SITE_NONE = 'None';

	/**
	 * @var string $name = ''
	 */
	public $name = '';

	/**
	 * @var string $value = ''
	 */
	public $value = '';

	/**
	 * @var string $expires = null
	 */
	public $expires = null;

	/**
	 * @var int $MaxAge = 0
	 */
	public $MaxAge = 0;

	/**
	 * @var string $path = '/'
	 */
	public $path = '/';

	/**
	 * @var string $domain = null
	 */
	public $domain = null;

	/**
	 * @var bool $HttpOnly = false
	 */
	public $HttpOnly = false;

	/**
	 * @var bool $Secure = false
	 */
	public $Secure = false;

	/**
	 * @var string $SameSite = self::SAME_SITE_LAX
	 */
	public $SameSite = self::SAME_SITE_LAX;

	/**
	 * @param string $name = ''
	 * @param string $value = ''
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

			$expires = QuarkDate::GMTNow()->Offset(($seconds > 0 ? '+' : '') . $seconds . ' seconds', true);

			$this->expires = $expires->Format(self::EXPIRES_FORMAT);
		}

		return QuarkDate::GMTNow()->Interval(QuarkDate::GMTOf($this->expires));
	}

	/**
	 * @param string $header = ''
	 *
	 * @return QuarkCookie[]
	 */
	public static function FromCookie ($header = '') {
		$out = array();
		$cookies = array_merge(explode(';', $header));
		$delimiter = false;

		foreach ($cookies as $i => &$raw) {
			$raw = trim($raw);
			$delimiter = strpos($raw, '=');
			if ($delimiter === false) continue;

			$out[] = new QuarkCookie(
				substr($raw, 0, $delimiter),
				substr($raw, $delimiter + 1)
			);
		}

		unset($i, $raw, $cookie, $cookies);

		return $out;
	}

	/**
	 * @param string $header = ''
	 *
	 * @return QuarkCookie
	 */
	public static function FromSetCookie ($header = '') {
		$cookie = explode(';', $header);

		$instance = new QuarkCookie();

		foreach ($cookie as $i => &$component) {
			$item = explode('=', $component);

			$key = trim($item[0]);
			$value = isset($item[1]) ? trim($item[1]) : '';

			if (isset($instance->$key)) $instance->$key = $value;
			else {
				$instance->name = $key;
				$instance->value = $value;
			}
		}

		unset($i, $component, $item);

		return $instance;
	}

	/**
	 * @param QuarkCookie[] $cookies = []
	 *
	 * @return string
	 */
	public static function SerializeCookies ($cookies = []) {
		$out = '';

		foreach ($cookies as $i => &$cookie)
			$out .= $cookie->name . '=' . $cookie->value . '; ';

		unset($i, $cookie);

		return substr($out, 0, strlen($out) - 2);
	}

	/**
	 * @param bool $full = false
	 *
	 * @return string
	 */
	public function Serialize ($full = false) {
		$out = '';

		if ($full) {
			foreach ($this as $field => &$value) {
				if ($field == 'name' || $field == 'value') continue;
				if ($field == 'SameSite' && !$this->Secure) continue;
				if ($value === null || $value === false) continue;

				if ($value === true)
					$out .= '; ' . $field;

				if (is_string($value) && $value != '')
					$out .= '; ' . $field . '=' . $value;
			}

			unset($key, $value);
		}

		return $this->name . '=' . $this->value . $out;
	}
}

/**
 * Class QuarkLanguage
 *
 * @package Quark
 */
class QuarkLanguage {
	const ANY = '*';
	const EN_EN = 'en-EN';
	const EN_GB = 'en-GB';
	const EN_US = 'en-US';
	const RU_RU = 'ru-RU';
	const MD_MD = 'md-MD';

	/**
	 * @var string $_name = self::ANY
	 */
	private $_name = '';

	/**
	 * @var int|float $_quantity = 1
	 */
	private $_quantity = 1;

	/**
	 * @var string $_family = ''
	 */
	private $_family = '';

	/**
	 * @var string $_location = ''
	 */
	private $_location = '';

	/**
	 * @param string $name = self::ANY
	 * @param int $quantity = 1
	 * @param string $location = ''
	 */
	public function __construct ($name = self::ANY, $quantity = 1, $location = '') {
		$this->_name = $name;
		$this->_quantity = $quantity;

		$name = explode('-', $name);
		
		$this->_family = $name[0];
		$this->_location = strtoupper(func_num_args() == 3
			? $location
			: array_reverse($name)[0]
		);
	}

	/**
	 * @param string $name = self::ANY
	 *
	 * @return string
	 */
	public function Name ($name = self::ANY) {
		if (func_num_args() != 0)
			$this->_name = $name;

		return $this->_name;
	}

	/**
	 * @param int|float $quantity = 1
	 *
	 * @return int|float
	 */
	public function Quantity ($quantity = 1) {
		if (func_num_args() != 0)
			$this->_quantity = $quantity;

		return $this->_quantity;
	}

	/**
	 * @return string
	 */
	public function Family () {
		return $this->_family;
	}

	/**
	 * @return string
	 */
	public function Location () {
		return $this->_location;
	}

	/**
	 * @param string $language
	 * @param bool $strict = false
	 *
	 * @return bool
	 */
	public function Is ($language, $strict = false) {
		if ($strict) return $this->_name == $language;
		if ($language == self::ANY) return true;
		if ($this->_name == self::ANY) return true;
		if ($this->_name == $language) return true;

		$item = QuarkKeyValuePair::ByDelimiter('-', $language);

		return $this->_family == $item->Key()
			? ($this->_location == '' || $item->Value() == '')
			: false;
	}

	/**
	 * @param string $header = ''
	 *
	 * @return QuarkLanguage[]
	 */
	public static function FromAcceptLanguage ($header = '') {
		$out = array();
		$languages = explode(',', $header);

		foreach ($languages as $i => &$raw) {
			$language = explode(';', $raw);
			$loc = explode('-', $language[0]);
			$q = explode('=', sizeof($language) == 1 ? 'q=1' : $language[1]);

			$out[] = new QuarkLanguage($language[0], array_reverse($q)[0], array_reverse($loc)[0]);
		}

		unset($i, $raw, $language, $loc, $q, $languages);

		return $out;
	}

	/**
	 * @param string $header = ''
	 *
	 * @return QuarkLanguage[]
	 */
	public static function FromContentLanguage ($header = '') {
		$out = array();
		$languages = explode(',', $header);

		foreach ($languages as $i => &$raw)
			$out[] = new QuarkLanguage(trim($raw));

		unset($i, $raw, $languages);

		return $out;
	}

	/**
	 * @param QuarkLanguage[] $languages = []
	 *
	 * @return string
	 */
	public static function SerializeAcceptLanguage ($languages = []) {
		if (!is_array($languages)) return '';

		$out = array();

		/**
		 * @var QuarkLanguage[] $languages
		 */
		foreach ($languages as $i => &$language)
			$out[] = $language->Name() . ';q=' . $language->Quantity();

		unset($i, $language);

		return implode(',', $out);
	}

	/**
	 * @param QuarkLanguage[] $languages = []
	 *
	 * @return string
	 */
	public static function SerializeContentLanguage ($languages = []) {
		if (!is_array($languages)) return '';

		$out = array();

		/**
		 * @var QuarkLanguage[] $languages
		 */
		foreach ($languages as $i => &$language)
			$out[] = $language->Name();

		unset($i, $language);

		return implode(',', $out);
	}
}
/**
 * Class QuarkMIMEType
 *
 * @package Quark
 */
class QuarkMIMEType {
	const ANY = '*/*';

	/**
	 * @var string $_name = self::ANY
	 */
	private $_name = self::ANY;

	/**
	 * @var int|float $_quantity = 1
	 */
	private $_quantity = 1;

	/**
	 * @var string $_range = '*'
	 */
	private $_range = '*';

	/**
	 * @var string $_type = '*'
	 */
	private $_type = '*';

	/**
	 * @var array $_params = []
	 */
	private $_params = array();

	/**
	 * @param string $name = self::ANY
	 * @param int $quantity = 1
	 * @param string $type = '*'
	 */
	public function __construct ($name = self::ANY, $quantity = 1, $type = '*') {
		$this->_name = $name;
		$this->_quantity = $quantity;
		$this->_params['q'] = $quantity;
		
		$type = explode('/', $name);

		$this->_range = $type[0];
		$this->_type = func_num_args() == 3
			? $type
			: array_reverse($type)[0];
	}

	/**
	 * @param string $name = self::ANY
	 *
	 * @return string
	 */
	public function Name ($name = self::ANY) {
		if (func_num_args() != 0)
			$this->_name = $name;
		
		return $this->_name;
	}

	/**
	 * @param int|float $quantity = 1
	 *
	 * @return int|float
	 */
	public function Quantity ($quantity = 1) {
		if (func_num_args() != 0)
			$this->_quantity = $quantity;

		return $this->_quantity;
	}

	/**
	 * @return string
	 */
	public function Range () {
		return $this->_range;
	}

	/**
	 * @return string
	 */
	public function Type () {
		return $this->_type;
	}

	/**
	 * @param array $params = []
	 *
	 * @return array
	 */
	public function Params ($params = []) {
		if (func_num_args() != 0)
			$this->_params = $params;
		
		return $this->_params;
	}

	/**
	 * @param string $key
	 * @param string $value
	 *
	 * @return QuarkMIMEType
	 */
	public function Param ($key, $value) {
		$this->_params[$key] = $value;

		if ($key == 'q')
			$this->_quantity = (float)$value;

		return $this;
	}

	/**
	 * @param string $type
	 * @param bool $strict = false
	 *
	 * @return bool
	 */
	public function Is ($type, $strict = false) {
		if ($strict) return $this->_name == $type;
		if ($type == self::ANY) return true;
		if ($this->_name == self::ANY) return true;
		if ($this->_name == $type) return true;

		$item = QuarkKeyValuePair::ByDelimiter('/', $type);
		
		return $this->_range == $item->Key()
			? ($this->_type == '*' || $item->Value() == '*')
			: false;
	}
	
	/**
	 * @param string $header = ''
	 *
	 * @return QuarkMIMEType[]
	 */
	public static function FromHeader ($header = '') {
		$out = array();
		$types = explode(',', $header);

		foreach ($types as $i => &$raw) {
			$type = explode(';', trim($raw));
			$item = new QuarkMIMEType($type[0]);
			
			if (sizeof($type) > 1) {
				$params = array_slice($type, 1);
				
				foreach ($params as $j => &$param) {
					$pair = explode('=', trim($param));

					if (sizeof($pair) == 2)
						$item->Param($pair[0], $pair[1]);
				}

				unset($j, $param, $pair, $params);
			}

			$out[] = $item;
		}

		unset($i, $raw, $type, $types);

		return $out;
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

	const MODE_ANYONE = 0777;
	const MODE_GROUP = 0771;
	const MODE_USER = 0711;
	const MODE_DEFAULT = self::MODE_ANYONE;

	const SEEK_FULL = -1;

	/**
	 * @var string $location = ''
	 */
	public $location = '';

	/**
	 * @var string $name = ''
	 */
	public $name = '';

	/**
	 * @var string $type = ''
	 */
	public $type = '';

	/**
	 * @var string $tmp_name = ''
	 */
	public $tmp_name = '';

	/**
	 * @var int $size = 0
	 */
	public $size = 0;

	/**
	 * @var string $extension = ''
	 */
	public $extension = '';

	/**
	 * @var bool $isDir = false
	 */
	public $isDir = false;

	/**
	 * @var string $parent = ''
	 */
	public $parent = '';

	/**
	 * @var string $_content = ''
	 */
	protected $_content = '';

	/**
	 * @var bool $_loaded = ''
	 */
	protected $_loaded = false;

	/**
	 * @var string $_lastCopy = ''
	 */
	protected $_lastCopy = '';
	
	/**
	 * @var QuarkDate $_dateCreated
	 */
	protected $_dateCreated;
	
	/**
	 * @var QuarkDate $_dateModified
	 */
	protected $_dateModified;
	
	/**
	 * @var int $_permissions = self::MODE_DEFAULT
	 */
	protected $_permissions = self::MODE_DEFAULT;

	/**
	 * @var int $_seekStart = 0
	 */
	protected $_seekStart = 0;
	
	/**
	 * @var int $_seekLength = self::SEEK_FULL
	 */
	protected $_seekLength = self::SEEK_FULL;

	/**
	 * @param bool $warn = true
	 * 
	 * @return bool
	 */
	public static function MimeExtensionExists ($warn = true) {
		if (function_exists('\finfo_open')) return true;
		
		if ($warn)
			Quark::Log('[QuarkFile] Mime extension not loaded. Check your PHP configuration. ' . self::TYPE_APPLICATION_OCTET_STREAM . ' used for response of file type', Quark::LOG_WARN);
		
		return false;
	}

	/**
	 * @param string $location
	 * @warning memory leak in native `finfo_file` realization
	 *
	 * @return mixed
	 */
	public static function Mime ($location) {
		if (!$location) return false;
		if (!self::MimeExtensionExists()) return self::TYPE_APPLICATION_OCTET_STREAM;

		$info = \finfo_open(FILEINFO_MIME_TYPE);
		$type = \finfo_file($info, $location);
		\finfo_close($info);

		return $type;
	}

	/**
	 * @param string $content
	 *
	 * @return mixed
	 */
	public static function MimeOf ($content) {
		if (!$content) return false;
		if (!self::MimeExtensionExists()) return self::TYPE_APPLICATION_OCTET_STREAM;
		
		$info = \finfo_open(FILEINFO_MIME_TYPE);
		$type = \finfo_buffer($info, $content);
		\finfo_close($info);

		return $type;
	}

	/**
	 * @param string $mime
	 *
	 * @return string
	 */
	public static function ExtensionByMime ($mime) {
		$extension = array_reverse(explode('/', $mime));

		if ($extension[0] == 'jpeg') $extension[0] = 'jpg';
		if ($mime == 'text/plain') $extension[0] = 'txt';
		if ($mime == 'audio/x-m4a') $extension[0] = 'm4a';
		if ($mime == 'audio/mpeg') $extension[0] = 'mp3';

		return sizeof($extension) == 2 && substr_count($extension[0], '-') == 0 ? $extension[0] : null;
	}

	/**
	 * @param string $location = ''
	 * @param bool $load = false
	 */
	public function __construct ($location = '', $load = false) {
		$now = QuarkDate::GMTNow();
		
		$this->_dateCreated = clone $now;
		$this->_dateModified = clone $now;
		
		if (func_num_args() != 0 && $location)
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
	 * @param string $location = ''
	 * @param string $name = ''
	 *
	 * @return string
	 */
	public function Location ($location = '', $name = '') {
		if (func_num_args() != 0) {
			$this->location = str_replace('\\', '/', $location);
			
			$delimiter = strrpos($this->location, '/');
			
			$this->name = $name ? $name : array_reverse(explode('/', (string)$this->location))[0];
			$this->parent = $delimiter !== false ? substr($this->location, 0, $delimiter) : '';
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
	 * @param string $location = ''
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

		$this->Content(
			$this->_seekLength == self::SEEK_FULL
				? file_get_contents($this->location, false, null, $this->_seekStart)
				: file_get_contents($this->location, false, null, $this->_seekStart, $this->_seekLength),
			true,
			true
		);

		return $this;
	}
	
	/**
	 * @return bool
	 */
	public function Loaded () {
		return $this->_loaded;
	}

	/**
	 * @param bool $unloadLocation = true
	 *
	 * @return QuarkFile
	 */
	public function Unload ($unloadLocation = true) {
		$this->_loaded = false;
		$this->_content = '';

		if ($unloadLocation)
			$this->Location('');

		return $this;
	}

	/**
	 * @param int $mode = self::MODE_DEFAULT
	 *
	 * @return bool
	 */
	private function _followParent ($mode = self::MODE_DEFAULT) {
		if (is_dir($this->parent) || is_file($this->parent)) return true;

		$ok = @mkdir($this->parent, $mode, true);
		
		if (!$ok)
			Quark::Log('[QuarkFile::_followParent] Can not create dir "' . $this->parent . '". Error: ' . QuarkException::LastError());

		return $ok;
	}

	/**
	 * @param int $start = 0
	 * @param int $length = self::SEEK_FULL
	 *
	 * @return QuarkFile
	 *
	 * @throws QuarkArchException
	 */
	public function Seek ($start = 0, $length = self::SEEK_FULL) {
		$this->_seekStart = $start;
		$this->_seekLength = $length;

		return $this->Load();
	}

	/**
	 * @return int
	 */
	public function SizeOnDisk () {
		return filesize($this->location);
	}

	/**
	 * @return mixed
	 */
	public function Type () {
		return self::MimeOf($this->_content);
	}

	/**
	 * @return mixed
	 */
	public function TypeOnDisk () {
		return self::Mime($this->location);
	}

	/**
	 * @param int $mode = self::MODE_DEFAULT
	 * @param bool $upload = false
	 *
	 * http://php.net/manual/ru/function.mkdir.php#114960
	 *
	 * @return bool
	 */
	public function SaveContent ($mode = self::MODE_DEFAULT, $upload = false) {
		if ($upload && $this->tmp_name)
			return $this->Upload(true, $mode);

		$this->_followParent($mode);

		return file_put_contents($this->location, $this->_content, LOCK_EX) !== false;
	}

	/**
	 * @param string $location = ''
	 * @param bool $changeLocation = false
	 *
	 * @return bool
	 */
	public function SaveTo ($location = '', $changeLocation = false) {
		$_location = $this->location;

		$this->Location($location);
		$ok = $this->SaveContent();

		if (!$changeLocation)
			$this->Location($_location);

		return $ok;
	}

	/**
	 * @param string $name = ''
	 *
	 * @return bool
	 */
	public function SaveCopy ($name = '') {
		$this->_lastCopy = $this->parent . (func_num_args() == 0 ? $this->name : $name) . '.' . $this->extension;

		return file_put_contents($this->_lastCopy, $this->_content, LOCK_EX) !== false;
	}

	/**
	 * @return string
	 */
	public function LastCopy () {
		return $this->_lastCopy;
	}

	/**
	 * @param string $name = ''
	 *
	 * @return string
	 */
	public function ChangeName ($name = '') {
		return $this->Location($this->parent . '/' . $name . '.' . $this->extension);
	}

	/**
	 * @param string $location = ''
	 *
	 * @return bool
	 */
	public function Rename ($location = '') {
		if (!rename($this->Location(), $location)) return false;

		$this->Location($location);

		return true;
	}

	/**
	 * @return bool
	 */
	public function DeleteFromDisk () {
		if (!@unlink($this->location)) {
			Quark::Log('[QuarkFile::DeleteFromDisk] ' . QuarkException::LastError() . '. Location: "' . $this->location . '"', Quark::LOG_WARN);
			return false;
		}

		return true;
	}

	/**
	 * @param string $parent = ''
	 *
	 * @return string
	 */
	public function WebLocation ($parent = '') {
		return Quark::WebLocation(func_num_args() != 0
			? ($parent . $this->name)
			: $this->location
		);
	}

	/**
	 * @param string $content = ''
	 * @param bool $load = false
	 * @param bool $mime = false
	 *
	 * @return string
	 */
	public function Content ($content = '', $load = false, $mime = false) {
		if (func_num_args() != 0) {
			$this->_content = $content;
			$this->size = strlen($this->_content);

			if (func_num_args() > 1)
				$this->_loaded = $load;

			if ($mime) {
				$this->type = self::MimeOf($this->_content);
				$this->extension = self::ExtensionByMime($this->type);
			}
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
			$this->extension = $ext;
		}

		$this->_followParent($mode);

		if (!is_file($this->tmp_name) || !is_dir(dirname($this->location)))  {
			Quark::Log('[QuarkFile::Upload] The [tmp_name:' . $this->tmp_name . '] or parent dir of [location:' . $this->location . ']. does not exists ' . QuarkException::LastError(), Quark::LOG_WARN);
			return false;
		}

		if (!rename($this->tmp_name, $this->location))  {
			Quark::Log('[QuarkFile::Upload] Cannot move from [tmp_name:' . $this->tmp_name . '] to [location:' . $this->location . ']. ' . QuarkException::LastError(), Quark::LOG_WARN);
			return false;
		}

		if (!chmod($this->location, $mode))  {
			Quark::Log('[QuarkFile::Upload] Cannot set mode [mode:' . sprintf('%o', $mode) . '] to [location:' . $this->location . ']. ' . QuarkException::LastError(), Quark::LOG_WARN);
			return false;
		}

		$this->Location($this->location);

		return true;
	}

	/**
	 * @param string $location = ''
	 * @param bool $mime = true
	 * @param int $mode = self::MODE_DEFAULT
	 *
	 * @return bool
	 */
	public function UploadTo ($location = '', $mime = true, $mode = self::MODE_DEFAULT) {
		$this->Location($location);
		
		return $this->Upload($mime, $mode);
	}

	/**
	 * @param string $name = ''
	 *
	 * @return QuarkDTO
	 */
	public function Download ($name = '') {
		$response = new QuarkDTO(new QuarkPlainIOProcessor());

		$response->Header(QuarkDTO::HEADER_CONTENT_TYPE, $this->type);
		$response->Header(QuarkDTO::HEADER_CONTENT_DISPOSITION, 'attachment; filename="' . (func_num_args() != 0 ? $name : $this->name) . '"');

		if (!$this->_loaded)
			$this->Content(file_get_contents($this->location));

		$response->Data($this->_content);

		return $response;
	}

	/**
	 * @return QuarkDTO
	 */
	public function Render () {
		$response = new QuarkDTO(new QuarkPlainIOProcessor());

		$response->Header(QuarkDTO::HEADER_CONTENT_TYPE, $this->type);

		if (!$this->_loaded)
			$this->Content(file_get_contents($this->location));

		$response->Data($this->_content);

		return $response;
	}
	
	/**
	 * @param IQuarkIOProcessor $processor
	 * @param $data = []
	 *
	 * @return QuarkFile
	 */
	public function Encode (IQuarkIOProcessor $processor, $data = []) {
		$this->Content($processor->Encode($data));
		
		return $this;
	}
	
	/**
	 * @param IQuarkIOProcessor $processor
	 * @param bool $load = false
	 *
	 * @return mixed
	 */
	public function Decode (IQuarkIOProcessor $processor, $load = false) {
		if ($load && !$this->_loaded)
			$this->Load();
		
		return $this->_loaded ? $processor->Decode($this->_content) : null;
	}
	
	/**
	 * @param QuarkDate $date = null
	 *
	 * @return QuarkDate
	 */
	public function DateCreated (QuarkDate $date = null) {
		if ($date instanceof QuarkDate)
			$this->_dateCreated = $date;
		
		return $this->_dateCreated;
	}
	
	/**
	 * @param QuarkDate $date = null
	 *
	 * @return QuarkDate
	 */
	public function DateModified (QuarkDate $date = null) {
		if ($date instanceof QuarkDate)
			$this->_dateModified = $date;
		
		return $this->_dateModified;
	}
	
	/**
	 * @param int $permissions = self::MODE_DEFAULT
	 * @param bool $set = false
	 * 
	 * @return int
	 */
	public function Permissions ($permissions = self::MODE_DEFAULT, $set = false) {
		if (func_num_args() != 0) {
			$this->_permissions = $permissions;
			
			if ($set)
				chmod($this->location, $this->_permissions);
		}
		
		return $this->_permissions;
	}

	/**
	 * @param bool $recursive = false
	 *
	 * @return QuarkCollection|QuarkFile[]
	 */
	public function Children ($recursive = false) {
		$children = self::Directory($this->location, $recursive);

		$this->isDir = $children !== null;

		return $children;
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
	 * @return array
	 */
	public function Rules () {
		return array(
			QuarkField::is($this->name, QuarkField::TYPE_STRING),
			QuarkField::is($this->type, QuarkField::TYPE_STRING),
			QuarkField::is($this->size, QuarkField::TYPE_INT),
			QuarkField::is($this->tmp_name, QuarkField::TYPE_STRING),
			QuarkField::MinLength($this->name, 1)
		);
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Link ($raw) {
		return new QuarkModel($raw ? new QuarkFile($raw) : clone $this);
	}

	/**
	 * @return mixed
	 */
	public function Unlink () {
		return $this->location;
	}
	
	/**
	 * @param string $location = ''
	 * @param bool $load = false
	 *
	 * @return QuarkFile
	 */
	public static function FromLocation ($location = '', $load = false) {
		return new self($location, $load);
	}
	
	/**
	 * @param array $files
	 * 
	 * https://github.com/zendframework/zend-http/blob/master/src/PhpEnvironment/Request.php
	 *
	 * @return array
	 */
	public static function FromFiles ($files) {
		$output = array();
		
        foreach ($files as $name => &$value) {
			$output[$name] = array();
			
			foreach ($value as $param => &$data) {
				if (!is_array($data)) {
					self::_file_populate($output, $name, $param, $data);
					continue;
				}
				
				foreach ($data as $k => &$v)
					self::_file_buffer($output[$name], $param, $k, $v);

				unset($k, $v);
			}

			unset($param, $data);
        }

		unset($name, $value);

		return $output;
	}
	
	/**
	 * @param string $location = ''
	 * @param string $content = ''
	 *
	 * @return QuarkFile
	 */
	public static function ForTransfer ($location = '', $content = '') {
		$file = new self($location);
		
		$file->_loaded = true;
		$file->Content($content);
		
		return $file;
	}

	/**
	 * @param array &$item
	 * @param string|int $name
	 * @param string|int $index
	 * @param string|int $value
	 */
	private static function _file_buffer (&$item, $name, $index, $value) {
		if (!is_array($value)) {
			self::_file_populate($item, $index, $name, $value);
			return;
		}

		foreach ($value as $i => &$v)
			self::_file_buffer($item[$index], $name, $i, $v);

		unset($i, $v);
	}

	/**
	 * @param array|QuarkModel[] &$source
	 * @param string|int $key
	 * @param string|int $name
	 * @param string|int $value
	 */
	private static function _file_populate (&$source, $key, $name, $value) {
		if (!isset($source[$key]))
			$source[$key] = array();

		if (!($source[$key] instanceof QuarkModel && $source[$key]->Model() instanceof QuarkFile))
			$source[$key] = new QuarkModel(new QuarkFile());

		$source[$key]->PopulateWith(array(
			$name => $value
		));
	}

	/**
	 * @param string $location = ''
	 * @param bool $recursive = false
	 *
	 * @return QuarkCollection|QuarkFile[]
	 */
	public static function Directory ($location = '', $recursive = false) {
		if (!is_dir($location)) return null;

		/**
		 * @var QuarkCollection|QuarkFile[] $out
		 */
		$out = new QuarkCollection(new self());

		if ($recursive) {
			$dir = new \RecursiveDirectoryIterator($location);
			$fs = new \RecursiveIteratorIterator($dir);
		}
		else {
			$dir = new \DirectoryIterator($location);
			$fs = new \IteratorIterator($dir);
		}

		foreach ($fs as $file) {
			/**
			 * @var \FilesystemIterator $file
			 */

			$name = $file->getFilename();
			if ($name == '.' || $name == '..') continue;

			/**
			 * @var QuarkModel|QuarkFile $item
			 */
			$item = new QuarkModel(new self());

			$item->Location($file->getRealPath());
			$item->isDir = $file->isDir();

			$out[] = $item;
		}

		unset($file, $dir, $fs);

		return $out;
	}

	/**
	 * @param string $location = ''
	 *
	 * @return QuarkFile
	 */
	public static function AsDirectory ($location = '') {
		if (!is_dir($location)) return null;

		$out = new self(rtrim($location, '/'));
		$out->isDir = true;

		return $out;
	}
}

/**
 * Class QuarkRegEx
 *
 * @package Quark
 */
class QuarkRegEx {
	const PCRE_UNGREEDY = 'U';
	const PCRE_CASELESS = 'i';
	const PCRE_DOTALL = 's';
	const PCRE_UTF8 = 'u';

	/**
	 * @var string $_regEx = ''
	 */
	private $_regEx = '';

	/**
	 * @var string $_delimiter = ''
	 */
	private $_delimiter = '';

	/**
	 * @var string $_expression = ''
	 */
	private $_expression = '';

	/**
	 * @var string[] $_flags = []
	 */
	private $_flags = array();

	/**
	 * @param string $regEx
	 */
	public function __construct ($regEx = '') {
		$this->RegEx($regEx);
	}

	/**
	 * @param string $regEx = ''
	 *
	 * @return string
	 */
	public function RegEx ($regEx = '') {
		if (func_num_args() != 0) {
			$this->_regEx = $regEx;

			if (!preg_match('#^([^a-zA-Z0-9\\\s])(.*)\1([a-zA-Z]*)$#', $this->_regEx, $meta)) $this->_expression = $regEx;
			else {
				$this->_delimiter = $meta[1];
				$this->_expression = $meta[2];
				$this->_flags = (array)$meta[3];
			}
		}

		return $this->_regEx;
	}

	/**
	 * @return string
	 */
	public function Delimiter () {
		return $this->_delimiter;
	}

	/**
	 * @return string
	 */
	public function Expression () {
		return $this->_expression;
	}

	/**
	 * @return string[]
	 */
	public function Flags () {
		return $this->_flags;
	}

	/**
	 * @param string $flag = ''
	 *
	 * @return bool
	 */
	public function HasFlag ($flag = '') {
		return in_array($flag, $this->_flags);
	}

	/**
	 * @param string $regEx = ''
	 *
	 * @return mixed
	 */
	public static function Escape ($regEx = '') {
		return preg_replace('#([\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|])#Uis', '\\\$1', $regEx);
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
	const DATETIME = 'Y-m-d H:i:s';
	const DATE = 'Y-m-d';
	const TIME = 'H:i:s';
	
	/**
	 * @return string
	 */
	public function DateTimeFormat () { return self::DATETIME; }

	/**
	 * @return string
	 */
	public function DateFormat () { return self::DATE; }

	/**
	 * @return string
	 */
	public function TimeFormat () { return self::TIME; }
}

/**
 * Class QuarkCultureRU
 *
 * @package Quark
 */
class QuarkCultureRU implements IQuarkCulture {
	const DATETIME = 'd.m.Y H:i:s';
	const DATE = 'd.m.Y';
	const TIME = 'H:i:s';
	
	/**
	 * @return string
	 */
	public function DateTimeFormat () { return self::DATETIME; }

	/**
	 * @return string
	 */
	public function DateFormat () { return self::DATE; }

	/**
	 * @return string
	 */
	public function TimeFormat () { return self::TIME; }
}

/**
 * Class QuarkCultureCustom
 *
 * @package Quark
 */
class QuarkCultureCustom implements IQuarkCulture {
	/**
	 * @var string $_dateTime = '';
	 */
	private $_dateTime = '';

	/**
	 * @var string $_date = ''
	 */
	private $_date = '';

	/**
	 * @var string $_time = ''
	 */
	private $_time = '';

	/**
	 * @param string $dateTime = ''
	 * @param string $date = ''
	 * @param string $time = ''
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

	/**
	 * @param bool $array = false
	 *
	 * @return string|array|null
	 */
	public static function LastError ($array = false) {
		$error = error_get_last();

		if (!$error || !is_array($error)) return null;

		return $array ? $error : (array_key_exists('message', $error) ? str_replace('&quot;', '"', $error['message']) : '');
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
	 * @var string $class = ''
	 */
	public $class = '';

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
	 * @param string $class = ''
	 *
	 * @return QuarkHTTPException
	 */
	public static function ForStatus ($status, $log = '', $class = '') {
		$exception = new self();
		$exception->status = $status;
		$exception->log = $log;
		$exception->class = $class;

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
	 * @param string $additional = ''
	 */
	public function __construct (QuarkURI $uri, $lvl = Quark::LOG_WARN, $additional = '') {
		$this->lvl = $lvl;
		$this->message = 'Unable to connect to ' . $uri->URI() . (strlen($additional) == 0 ? '' : '. ' . $additional);

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
	 * @param bool $fallback
	 *
	 * @return mixed
	 */
	public function Batch($raw, $fallback);

	/**
	 * @return bool
	 */
	public function ForceInput();
}

/**
 * Class QuarkPlainIOProcessor
 *
 * @package Quark
 */
class QuarkPlainIOProcessor implements IQuarkIOProcessor {
	const MIME = 'plain/text';

	/**
	 * @return string
	 */
	public function MimeType () { return self::MIME; }

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
	 * @param bool $fallback
	 *
	 * @return mixed
	 */
	public function Batch ($raw, $fallback) {
		return array($raw);
	}

	/**
	 * @return bool
	 */
	public function ForceInput () {
		return false;
	}
}

/**
 * Class QuarkHTMLIOProcessor
 *
 * @package Quark
 */
class QuarkHTMLIOProcessor implements IQuarkIOProcessor {
	const MIME = 'text/html';
	const TYPE_KEY = self::MIME;

	/**
	 * @return string
	 */
	public function MimeType () { return self::MIME; }

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
	 * @param bool $fallback
	 *
	 * @return mixed
	 */
	public function Batch ($raw, $fallback) {
		return array($raw);
	}

	/**
	 * @return bool
	 */
	public function ForceInput () {
		return false;
	}
}

/**
 * Class QuarkFormIOProcessor
 *
 * @package Quark
 */
class QuarkFormIOProcessor implements IQuarkIOProcessor {
	const MIME = 'application/x-www-form-urlencoded';

	/**
	 * @return string
	 */
	public function MimeType () { return self::MIME; }

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
	 * @param bool $fallback
	 *
	 * @return mixed
	 */
	public function Batch ($raw, $fallback) {
		return array($raw);
	}

	/**
	 * @return bool
	 */
	public function ForceInput () {
		return false;
	}
}

/**
 * Class QuarkJSONIOProcessor
 *
 * @package Quark
 */
class QuarkJSONIOProcessor implements IQuarkIOProcessor {
	const MIME = 'application/json';

	/**
	 * @return string
	 */
	public function MimeType () { return self::MIME; }

	/**
	 * @param $data
	 *
	 * @return string
	 */
	public function Encode ($data) { return \json_encode($data, JSON_UNESCAPED_UNICODE); } // TODO: add escaping control

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Decode ($raw) {
		$raw = trim($raw);

		return self::IsValid($raw) ? \json_decode($raw) : null;
	}

	/**
	 * @param string $raw
	 * @param bool $fallback
	 *
	 * @return mixed
	 */
	public function Batch ($raw, $fallback) {
		$out = trim($raw);
		$length = strlen($out);

		if ($length >= 2) {
			if ($out[0] == '{') return explode('}-{', preg_replace('#}\s*{#Uis', '}}-{{', $out));
			if ($out[0] == '[') return explode(']-[', preg_replace('#\]\s*\[#Uis', ']]-[[', $out));
		}

		return $fallback ? array($raw) : array();
	}

	/**
	 * @return bool
	 */
	public function ForceInput () {
		return false;
	}

	/**
	 * @param string $raw = ''
	 *
	 * @return bool
	 */
	public static function IsValid ($raw = '') {
		$raw = trim($raw);
		$length = strlen($raw);

		if ($length < 2) return false;
		$last = $length - 1;

		$valid = false;
		if ($raw[0] == '{' && $raw[$last] == '}') $valid = true;
		if ($raw[0] == '[' && $raw[$last] == ']') $valid = true;

		if (!$valid) return false;

		return true;
	}
}

/**
 * Class QuarkXMLIOProcessor
 *
 * @package Quark
 */
class QuarkXMLIOProcessor implements IQuarkIOProcessor {
	const PATTERN_ATTRIBUTE = '#([a-zA-Z0-9\:\_\.\-]+)\=\"(.*)\"#UisS';
	const PATTERN_ELEMENT = '#\<([a-zA-Z0-9\:\_\.\-]+)\s*((([a-zA-Z0-9\:\_\.\-]+)\=\"(.*)\")*)\s*(\>(.*)\<\/\1|\/)\>#UisS';
	const PATTERN_META = '#^\s*\<\?xml\s*((([a-zA-Z0-9\:\_\-\.]+?)\=\"(.*)\")*)\s*\?\>#UisS';
	const PATTERN_COMMENT = '#\<\!\-\-(.*)\-\-\>#is';

	const MIME = 'text/xml';
	const ROOT = 'root';
	const ITEM = 'item';
	const VERSION_1_0 = '1.0';
	const ENCODING_UTF_8 = 'utf-8';

	/**
	 * @var string $version = self::VERSION_1_0
	 */
	private $_version = self::VERSION_1_0;

	/**
	 * @var string $_encoding = self::ENCODING_UTF_8
	 */
	private $_encoding = self::ENCODING_UTF_8;

	/**
	 * @var QuarkXMLNode $root = null
	 */
	private $_root = self::ROOT;

	/**
	 * @var string $_item = self::ITEM
	 */
	private $_item = self::ITEM;

	/**
	 * @var bool $_forceNull = true
	 */
	private $_forceNull = true;

	/**
	 * @var bool $_forceInput = false
	 */
	private $_forceInput = false;

	/**
	 * @var bool $_forceNode = false
	 */
	private $_forceNode = false;

	/**
	 * @var bool $_init = false
	 */
	private $_init = false;
	
	/**
	 * @var int $_lists = 0;
	 */
	private $_lists = 0;

	/**
	 * @var string[] $_comments = []
	 */
	private $_comments = array();

	/**
	 * @param QuarkXMLNode $root = null
	 * @param string $item = self::ITEM
	 * @param bool $forceNull = true
	 * @param bool $forceInput = false
	 * @param string $version = self::VERSION_1_0
	 * @param string $encoding = self::ENCODING_UTF_8
	 */
	public function __construct (QuarkXMLNode $root = null, $item = self::ITEM, $forceNull = true, $forceInput = false, $version = self::VERSION_1_0, $encoding = self::ENCODING_UTF_8) {
		\libxml_use_internal_errors(true);

		$this->Root($root == null ? new QuarkXMLNode(self::ROOT) : $root);
		$this->Item($item);
		$this->ForceNull($forceNull);
		$this->ForceInput($forceInput);
		$this->Version($version);
		$this->Encoding($encoding);
	}
	
	/**
	 * @param QuarkXMLNode $root = null
	 *
	 * @return QuarkXMLNode
	 */
	public function Root (QuarkXMLNode $root = null) {
		if (func_num_args() != 0)
			$this->_root = $root;
		
		return $this->_root;
	}
	
	/**
	 * @param string $item = self::ITEM
	 *
	 * @return string
	 */
	public function Item ($item = self::ITEM) {
		if (func_num_args() != 0)
			$this->_item = $item;
		
		return $this->_item;
	}
	
	/**
	 * @param bool $forceNull = true
	 *
	 * @return bool
	 */
	public function ForceNull ($forceNull = true) {
		if (func_num_args() != 0)
			$this->_forceNull = $forceNull;
		
		return $this->_forceNull;
	}

	/**
	 * @param bool $forceInput = false
	 *
	 * @return bool
	 */
	public function ForceInput ($forceInput = false) {
		if (func_num_args() != 0)
			$this->_forceInput = $forceInput;

		return $this->_forceInput;
	}

	/**
	 * @param bool $forceNode = false
	 *
	 * @return bool
	 */
	public function ForceNode ($forceNode = false) {
		if (func_num_args() != 0)
			$this->_forceNode = $forceNode;

		return $this->_forceNode;
	}

	/**
	 * @param string $version = self::VERSION_1_0
	 *
	 * @return string
	 */
	public function Version ($version = self::VERSION_1_0) {
		if (func_num_args() != 0)
			$this->_version = $version;

		return $this->_version;
	}

	/**
	 * @param string $encoding = self::ENCODING_UTF_8
	 *
	 * @return string
	 */
	public function Encoding ($encoding = self::ENCODING_UTF_8) {
		if (func_num_args() != 0)
			$this->_encoding = $encoding;
		
		return $this->_encoding;
	}

	/**
	 * @return string[]
	 */
	public function Comments () {
		return $this->_comments;
	}

	/**
	 * @return string
	 */
	public function MimeType () { return self::MIME; }

	/**
	 * @param $data
	 * @param bool $meta = true
	 *
	 * @return string
	 */
	public function Encode ($data, $meta = true) {
		if (!$this->_init) {
			$this->_init = true;

			if (!($data instanceof QuarkXMLNode)) $this->_root->Data($data);
			else {
				if ($data->IsRoot()) $this->_root = $data;
				else $this->_root->Data(array($data));
			}

			$out = ($meta ? ('<?xml version="' . $this->_version . '" encoding="' . $this->_encoding . '" ?>') : '')
				 . $this->_root->ToXML($this);

			$this->_init = false;

			return $out;
		}

		$out = '';
		$i = $this->_lists == 0 ? '' : $this->_lists;
		
		if (QuarkObject::isIterative($data)) {
			$this->_lists++;

			foreach ($data as $item)
				$out .= $item instanceof QuarkXMLNode
					? $item->ToXML($this, $this->_item)
					: ('<' . $this->_item . $i . '>' . $this->Encode($item) . '</' . $this->_item . $i . '>');

			$this->_lists--;
			
			return $out;
		}

		if (QuarkObject::isAssociative($data)) {
			foreach ($data as $key => &$value)
				$out .= $value instanceof QuarkXMLNode
					? $value->ToXML($this, $key)
					: '<' . $key . '>' . $this->Encode($value) . '</' . $key . '>';

			unset($key, $value);

			return $out;
		}

		return $data;
	}

	/**
	 * @param $raw
	 * @param bool $_meta = true
	 *
	 * @return mixed
	 */
	public function Decode ($raw, $_meta = true) {
		$raw = preg_replace_callback(self::PATTERN_COMMENT, function ($item) {
			$this->_comments[] = trim($item[1]);

			return '';
		}, $raw);

		if ($_meta && preg_match(self::PATTERN_META, $raw, $info)) {
			$meta = self::DecodeAttributes($info[2]);

			if (isset($meta->version))
				$this->_version = $meta->version;
			
			if (isset($meta->encoding))
				$this->_encoding = $meta->encoding;
		}

		if (!preg_match_all(self::PATTERN_ELEMENT, $raw, $xml, PREG_SET_ORDER)) return null;

		$out = array();
		$item = '';

		if ($_meta) {
			$this->_root->Name($xml[0][1]);

			if ($xml[0][2] != '')
				$this->_root->Attributes(self::DecodeAttributes($xml[0][2]));
		}

		foreach ($xml as $k => &$value) {
			$key = $value[1];
			$buffer = null;

			if (sizeof($value) == 8) {
				$buffer = $this->Decode($value[6] . '>', false);

				if (!$buffer)
					$buffer = $this->Decode($value[7], false);

				if (!$buffer) $buffer = $value[7];
			}

			$attributes = self::DecodeAttributes($value[2]);

			if ($this->_forceNode || $attributes !== null) {
				$buffer = new QuarkXMLNode($key, $buffer, $attributes);
				$buffer->Single(!isset($value[7]));
			}

			if (isset($out[$key])) {
				$item = $key;
				
				if (!isset($out[0][$key])) {
					$tmp = is_object($out[$key]) ? clone $out[$key] : $out[$key];
					unset($out[$key]);
					$out[] = $tmp;
				}

				$out[] = $buffer;
			}
			else {
				if (isset($out[0]) && $item != '' && $item == $key) $out[] = $buffer;
				else $out[$key] = $buffer;
			}
		}

		unset($k, $value, $xml);

		return QuarkObject::isIterative($out) ? $out : (object)$out;
	}

	/**
	 * @param string $data = ''
	 *
	 * @return object
	 */
	public static function DecodeAttributes ($data = '') {
		if (!preg_match_all(self::PATTERN_ATTRIBUTE, $data, $attributes, PREG_SET_ORDER)) return null;

		$out = array();

		foreach ($attributes as $i => &$attribute)
			$out[$attribute[1]] = $attribute[2];

		unset($i, $attribute, $attributes);

		return (object)$out;
	}

	/**
	 * @param string $raw
	 * @param bool $fallback
	 *
	 * @return mixed
	 */
	public function Batch ($raw, $fallback) {
		return array($raw);
	}

	/**
	 * @param QuarkXMLNode $root = null
	 * @param string $item = self::ITEM
	 * @param bool $forceNull = true
	 * @param string $version = self::VERSION_1_0
	 * @param string $encoding = self::ENCODING_UTF_8
	 *
	 * @return QuarkXMLIOProcessor
	 */
	public static function ForcedInput (QuarkXMLNode $root = null, $item = self::ITEM, $forceNull = true, $version = self::VERSION_1_0, $encoding = self::ENCODING_UTF_8) {
		return new self($root, $item, $forceNull, true, $version, $encoding);
	}
}

/**
 * Class QuarkXMLNode
 *
 * @package Quark
 */
class QuarkXMLNode {
	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @var array $_attributes = []
	 */
	private $_attributes = array();

	/**
	 * @var bool $_single = false
	 */
	private $_single = false;

	/**
	 * @var bool $_root = false
	 */
	private $_root = false;
	
	/**
	 * @var $_data = null
	 */
	private $_data = null;

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
	 * @param $key
	 */
	public function __unset ($key) {
		unset($this->_data->$key);
	}

	/**
	 * @param string $name = ''
	 * @param $data = []
	 * @param array|object $attributes = []
	 * @param bool $single = false
	 */
	public function __construct ($name = '', $data = [], $attributes = [], $single = false) {
		$this->Name($name);
		$this->Data($data);
		$this->Attributes($attributes);
		$this->Single($single);
	}

	/**
	 * @param string $name = ''
	 *
	 * @return string
	 */
	public function Name ($name = '') {
		if (func_num_args() != 0)
			$this->_name = $name;

		return $this->_name;
	}
	
	/**
	 * @param $data
	 *
	 * @return mixed
	 */
	public function Data ($data = []) {
		if (func_num_args() != 0)
			$this->_data = $data;
		
		return $this->_data;
	}

	/**
	 * @param array|object $attributes
	 *
	 * @return array|object
	 */
	public function Attributes ($attributes = []) {
		if (func_num_args() != 0)
			$this->_attributes = (object)$attributes;

		return $this->_attributes;
	}
	
	/**
	 * @param string $key
	 * @param string $value = ''
	 *
	 * @return mixed
	 */
	public function Attribute ($key, $value = '') {
		if (func_num_args() == 2)
			$this->_attributes->$key = $value;
		
		return isset($this->_attributes->$key) ? $this->_attributes->$key : null;
	}

	/**
	 * @param bool $single = false
	 *
	 * @return bool
	 */
	public function Single ($single = false) {
		if (func_num_args() != 0)
			$this->_single = $single;
		
		return $this->_single;
	}

	/**
	 * @param bool $root = false
	 *
	 * @return bool
	 */
	public function IsRoot ($root = false) {
		if (func_num_args() != 0)
			$this->_root = $root;

		return $this->_root;
	}

	/**
	 * @param string $key = ''
	 *
	 * @return mixed
	 */
	public function Get ($key = '') {
		return isset($this->_data->$key) ? $this->_data->$key : null;
	}

	/**
	 * @param QuarkXMLIOProcessor $processor
	 * @param string $node
	 *
	 * @return string
	 */
	public function ToXML (QuarkXMLIOProcessor $processor, $node = '') {
		$attributes = '';
		$node = $this->_name == '' ? $node : $this->_name;

		foreach ($this->_attributes as $key => &$value)
			if ($value !== null || ($value === null && $processor->ForceNull()))
				$attributes .= ' ' . $key . '="'. $value . '"';

		unset($key, $value);

		return $this->_single
			? ('<' . $node . $attributes . ' />')
			: ('<' . $node . $attributes . '>' . $processor->Encode($this->_data) . '</' . $node . '>');
	}

	/**
	 * @param string $name = ''
	 * @param array|object $attributes = []
	 * @param $data = []
	 *
	 * @return QuarkXMLNode
	 */
	public static function Root  ($name = '', $attributes = [], $data = []) {
		$out = new self($name, $data, $attributes);
		$out->IsRoot(true);

		return $out;
	}

	/**
	 * @param string $name = ''
	 * @param array|object $attributes = []
	 *
	 * @return QuarkXMLNode
	 */
	public static function SingleNode ($name = '', $attributes = []) {
		return new self($name, null, $attributes, true);
	}

	/**
	 * @param \SimpleXMLElement $xml
	 * @param array $out
	 * 
	 * @return QuarkXMLNode|object
	 */
	public static function FromXMLElement (\SimpleXMLElement $xml, $out) {
		if (sizeof($xml->attributes()) == 0) return (object)$out;
		
		$attributes = array();
			
		foreach ($xml->attributes() as $key => $value)
			$attributes[$key] = (string)$value;
		
		return new self($xml->getName(), $out, $attributes);
	}
}

/**
 * Class QuarkWDDXIOProcessor
 *
 * @package Quark
 */
class QuarkWDDXIOProcessor implements IQuarkIOProcessor {
	const MIME = 'text/xml';

	/**
	 * @return string
	 */
	public function MimeType () { return self::MIME; }

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
	 * @param bool $fallback
	 *
	 * @return mixed
	 */
	public function Batch ($raw, $fallback) {
		return array($raw);
	}

	/**
	 * @return bool
	 */
	public function ForceInput () {
		return false;
	}
}

/**
 * Class QuarkINIIOProcessor
 *
 * @package Quark
 */
class QuarkINIIOProcessor implements IQuarkIOProcessor {
	const PATTERN_BLOCK = '#\[([^\n]*)\][\s\n]*(([^\n]*\s?\=\s?[^\n]*[\s\n]*)*)#is';
	const PATTERN_PAIR = '#([^\n]*)\s?\=\s?([^\n]*)\n#Ui';
	const PATTERN_COMMENT = '#\n[\;\#](.*)\n|(.*\=\s*\"(.*)\")\s*[\;\#]\s*(.*)\n|\n\[(.*)\]\s*[\;\#](.*)\n#UiS';

	const MIME = 'plain/text';

	/**
	 * @var bool $_cast = true
	 */
	private $_cast = true;

	/**
	 * @var string[] $_comments = []
	 */
	private $_comments = array();
	
	/**
	 * @param bool $cast = true
	 */
	public function __construct ($cast = true) {
		$this->Cast($cast);
	}

	/**
	 * @param bool $cast = true
	 *
	 * @return bool
	 */
	public function Cast ($cast = true) {
		if (func_num_args() != 0)
			$this->_cast = $cast;
		
		return $this->_cast;
	}

	/**
	 * @return string[]
	 */
	public function Comments () {
		return $this->_comments;
	}

	/**
	 * @return string
	 */
	public function MimeType () { return self::MIME; }
	
	/**
	 * @param $data
	 *
	 * @return string
	 */
	public function Encode ($data) {
		if (!QuarkObject::isTraversable($data)) {
			Quark::Log('[QuarkINIIOProcessor::Encode] Provided $data argument is not an object or array. Cannot encode. Data (' . gettype($data) . '): ' . $data, Quark::LOG_WARN);
			return null;
		}

		$out = '';
		
		foreach ($data as $name => &$section) {
			if (!is_array($section) && !is_object($section)) {
				$out .= $name . ' = ' . QuarkObject::Stringify($section) . "\r\n";
				continue;
			}
			
			$out .= '[' . $name . ']' . "\r\n";
			
			foreach ($section as $key => &$value)
				$out .= $key . ' = ' . QuarkObject::Stringify($value) . "\r\n";

			unset($key, $value);
			
			$out .= "\r\n";
		}

		unset($name, $section);
		
		return $out;
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Decode ($raw) {
		if (!is_string($raw)) {
			Quark::Log('[QuarkINIIOProcessor::Decode] Provided $raw argument is not a string. Cannot decode. Raw (' . gettype($raw) . '): ' . print_r($raw, true), Quark::LOG_WARN);
			return null;
		}

		$raw = preg_replace_callback(self::PATTERN_COMMENT, function ($item) {
			$size = sizeof($item);
			$this->_comments[] = trim($item[$size - 1]);

			if ($size == 7) return '[' . $item[5] . ']';
			if ($size == 5) return $item[2];

			return '';
		}, "\n" . str_replace("\n", "\n\n", $raw) . "\n");

		if (!preg_match_all(self::PATTERN_BLOCK, $raw, $ini, PREG_SET_ORDER)) return null;

		$out = array();

		foreach ($ini as $key => &$value)
			$out[$value[1]] = self::DecodePairs($value[2], $this->_cast);

		unset($key, $value, $ini);

		return QuarkObject::Merge($out);
	}

	/**
	 * @param string $raw = ''
	 * @param bool $cast = true
	 *
	 * @return mixed
	 */
	public static function DecodePairs ($raw = '', $cast = true) {
		if (!preg_match_all(self::PATTERN_PAIR, $raw, $pairs, PREG_SET_ORDER)) return null;

		$out = array();

		foreach ($pairs as $i => &$pair) {
			$value = trim($pair[2]);

			if ($cast) {
				if (strtolower($value) == 'true') $value = true;
				if (strtolower($value) == 'false') $value = false;
				if (strtolower($value) == 'null') $value = null;
				if (QuarkField::Int($value)) $value = (int)$value;
				if (QuarkField::Float($value)) $value = (float)$value;
			}

			if (is_string($value))
				$value = preg_replace('#\\\(.)#Ui', '$1', preg_replace('#^\"(.*)\"$#i', '$1', $value));

			$out[trim($pair[1])] = $value;
		}

		unset($i, $pair, $pairs);
		
		return QuarkObject::Merge($out);
	}

	/**
	 * @param string $raw
	 * @param bool $fallback
	 *
	 * @return mixed
	 */
	public function Batch ($raw, $fallback) {
		return array($raw);
	}

	/**
	 * @return bool
	 */
	public function ForceInput () {
		return false;
	}
}

/**
 * Interface IQuarkIOFilter
 *
 * @package Quark
 */
interface IQuarkIOFilter {
	/**
	 * @param QuarkDTO $input
	 * @param QuarkSession $session
	 *
	 * @return QuarkDTO
	 */
	public function FilterInput(QuarkDTO $input, QuarkSession $session);

	/**
	 * @param QuarkDTO $output
	 * @param QuarkSession $session
	 *
	 * @return QuarkDTO
	 */
	public function FilterOutput(QuarkDTO $output, QuarkSession $session);
}

/**
 * Class QuarkXSSFilter
 *
 * @package Quark
 */
class QuarkXSSFilter implements IQuarkIOFilter {
	/**
	 * @param QuarkDTO $input
	 * @param QuarkSession $session
	 *
	 * @return QuarkDTO
	 */
	public function FilterInput (QuarkDTO $input, QuarkSession $session) {
		$data = $input->Data();

		QuarkObject::Walk($data, function (&$key, &$value) {
			$key = strip_tags($key);
			$value = strip_tags($value);
		});

		$input->Data($data);

		return $input;
	}

	/**
	 * @param QuarkDTO $output
	 * @param QuarkSession $session
	 *
	 * @return QuarkDTO
	 */
	public function FilterOutput (QuarkDTO $output, QuarkSession $session) {
		return $output;
	}
}

/**
 * Class QuarkCertificate
 *
 * @package Quark
 */
class QuarkCertificate extends QuarkFile {
	const ALGORITHM_SHA256 = 'sha256';
	const ALGORITHM_SHA512 = 'sha512';

	const DEFAULT_BITS = 4096;

	const DEFAULT_COUNTRY_NAME = 'AA';
	const DEFAULT_STATE = 'International';
	const DEFAULT_LOCALITY = 'Worldwide';
	const DEFAULT_ORGANIZATION = 'WorldWideWeb';
	const DEFAULT_ORGANIZATION_UNIT = 'WorldWideWeb';
	const DEFAULT_EMAIL = 'admin@example.com';

	/**
	 * @var string $countryName = ''
	 */
	public $countryName = '';
	
	/**
	 * @var string $stateOrProvinceName = ''
	 */
	public $stateOrProvinceName = '';
	
	/**
	 * @var string $localityName = ''
	 */
	public $localityName = '';
	
	/**
	 * @var string $organizationName = ''
	 */
	public $organizationName = '';
	
	/**
	 * @var string $organizationalUnitName = ''
	 */
	public $organizationalUnitName = '';
	
	/**
	 * @var string $commonName = ''
	 */
	public $commonName = '';

	/**
	 * @var string $subjectAltName = ''
	 */
	public $subjectAltName = '';
	
	/**
	 * @var string $emailAddress = ''
	 */
	public $emailAddress = '';

	/**
	 * @var string $_passphrase = null
	 */
	private $_passphrase = null;

	/**
	 * @var QuarkCipherKeyPair $_key
	 */
	private $_key;

	/**
	 * @var string[] $_allowed
	 */
	private static $_allowed = array(
		'countryName',
		'stateOrProvinceName',
		'localityName',
		'organizationName',
		'organizationalUnitName',
		'commonName',
		'subjectAltName',
		'emailAddress'
	);

	/**
	 * @param string $location = ''
	 * @param string $passphrase = null
	 * @param bool $load = false
	 */
	public function __construct ($location = '', $passphrase = null, $load = false) {
		parent::__construct($location, $load);
		$this->Passphrase($passphrase);
	}

	/**
	 * @param string $passphrase = null
	 *
	 * @return string
	 */
	public function Passphrase ($passphrase = null) {
		if (func_num_args() != 0)
			$this->_passphrase = $passphrase;

		return $this->_passphrase;
	}

	/**
	 * @param QuarkCipherKeyPair $key = null
	 *
	 * @return QuarkCipherKeyPair
	 */
	public function &Key (QuarkCipherKeyPair $key = null) {
		if (func_num_args() != 0)
			$this->_key = $key;

		return $this->_key;
	}

	/**
	 * @param QuarkCertificateSAN $san = null
	 *
	 * @return QuarkCertificate
	 */
	public function AltName (QuarkCertificateSAN $san = null) {
		if ($san != null) {
			if (strlen($this->subjectAltName) != 0)
				$this->subjectAltName .= ',';

			$this->subjectAltName .= $san->Record();
		}

		return $this;
	}

	/**
	 * @param string $algorithm = self::ALGORITHM_SHA512
	 * @param int $length = self::DEFAULT_BITS
	 * @param int $type = OPENSSL_KEYTYPE_RSA
	 * @param bool $text = false
	 *
	 * @return string|null
	 */
	public function SigningRequest ($algorithm = self::ALGORITHM_SHA512, $length = self::DEFAULT_BITS, $type = OPENSSL_KEYTYPE_RSA, $text = false) {
		$config = self::OpenSSLConfig($algorithm, $length, $type, $this->subjectAltName);

		if (!($this->_key instanceof QuarkCipherKeyPair))
			return self::_error('SigningRequest: Private key is invalid');

		$data = array();

		foreach ($this as $property => &$value)
			if (in_array($property, self::$_allowed, true)) $data[$property] = $value;

		unset($property, $value);

		$key_private = $this->_key->PrivateKey(false);
		$csr = @openssl_csr_new($data, $key_private, $config);
		if (!$csr)
			return self::_error('SigningRequest: Cannot generate CSR');

		$out = '';
		$ok = openssl_csr_export($csr, $out, $text);

		return $ok ? $out : null;
	}

	/**
	 * @param string $algorithm = self::ALGORITHM_SHA512
	 * @param int $length = self::DEFAULT_BITS
	 * @param int $type = OPENSSL_KEYTYPE_RSA
	 *
	 * @return string|null
	 */
	public function SigningRequestWithText ($algorithm = self::ALGORITHM_SHA512, $length = self::DEFAULT_BITS, $type = OPENSSL_KEYTYPE_RSA) {
		return $this->SigningRequest($algorithm, $length, $type, true);
	}

	/**
	 * @param string $cert = ''
	 * @param string $key = ''
	 *
	 * @return bool
	 */
	public function SaveCertificatePair ($cert = '', $key = '') {
		$content_cert = $this->Content();
		$content_key = $this->_key->Content();
		$ok = true;

		$this->Content(str_replace($content_key, '', $content_cert));
		$ok &= $this->SaveTo($cert);
		$this->Content($content_cert);

		return $ok && $this->_key->SaveTo($key);
	}

	/**
	 * @param string $message = ''
	 * @param bool $openssl = true
	 *
	 * @return null
	 */
	private static function _error ($message = '', $openssl = true) {
		Quark::Log('[QuarkCertificate] ' . $message . ($openssl ? '. OpenSSL error: "' . openssl_error_string() . '".' : ''), Quark::LOG_WARN);

		return null;
	}

	/**
	 * @return string[]
	 */
	public static function AllowedDataKeys () {
		return self::$_allowed;
	}

	/**
	 * @param string $algorithm = self::ALGORITHM_SHA512
	 * @param int $length = self::DEFAULT_BITS
	 * @param int $type = OPENSSL_KEYTYPE_RSA
	 * @param string $altName = ''
	 *
	 * @return array
	 */
	public static function OpenSSLConfig ($algorithm = self::ALGORITHM_SHA512, $length = self::DEFAULT_BITS, $type = OPENSSL_KEYTYPE_RSA, $altName = '') {
		$conf = array(
			'req' => array(
				'x509_extensions' => 'v3_ca',
				'req_extensions' => 'v3_req',
				'distinguished_name' => 'req_distinguished_name',
				'default_md' => $algorithm,
				'digest_alg' => $algorithm,
				'default_bits' => (int)$length,
				'private_key_bits' => (int)$length,
				'private_key_type' => $type,
				'encrypt_key' => true,
				'prompt' => 'no'
			),
			'req_distinguished_name' => array(),
			'ca' => array(
				'default_ca' => 'CA_default'
			),
			'CA_default' => array(
				'copy_extensions' => 'copy',
				'default_md' => $algorithm
			),
			'v3_req' => array(
				'basicConstraints' => 'CA:FALSE',
				'keyUsage' => 'nonRepudiation, digitalSignature, keyEncipherment',
			),
			'v3_ca' => array(
				'subjectKeyIdentifier' => 'hash',
				'authorityKeyIdentifier' => 'keyid:always,issuer',
				'basicConstraints' => 'CA:true'
			)
		);

		if (func_num_args() == 4) {
			$conf['v3_req']['subjectAltName'] = $altName;
			$conf['v3_ca']['subjectAltName'] = $altName;
		}

		$ini = new QuarkINIIOProcessor();
		$tmp = Quark::TempFile();
		$tmp->Content($ini->Encode($conf));
		$tmp->SaveContent();

		return array('config' => $tmp->Location());
	}

	/**
	 * @param QuarkCertificate|string $location
	 * @param bool $load = false
	 *
	 * @return string
	 */
	public static function FromLocation ($location = '', $load = false) {
		return $location instanceof QuarkCertificate ? $location : new self($location, $load);
	}

	/**
	 * @param string $commonName = ''
	 * @param string $passphrase = null
	 * @param string $algorithm = self::ALGORITHM_SHA512
	 * @param int $length = self::DEFAULT_BITS
	 * @param int $type = OPENSSL_KEYTYPE_RSA
	 *
	 * @return QuarkCertificate
	 */
	public static function ForCSR ($commonName = '', $passphrase = null, $algorithm = self::ALGORITHM_SHA512, $length = self::DEFAULT_BITS, $type = OPENSSL_KEYTYPE_RSA) {
		if (strlen($commonName) == 0)
			return self::_error('ForCSR: CommonName must not be empty');

		$certificate = new self();

		$certificate->_passphrase = $passphrase;
		$certificate->_key = QuarkCipherKeyPair::GenerateNew($passphrase, $algorithm, $length, $type);

		$certificate->countryName = self::DEFAULT_COUNTRY_NAME;
		$certificate->stateOrProvinceName = self::DEFAULT_STATE;
		$certificate->localityName = self::DEFAULT_LOCALITY;
		$certificate->organizationName = self::DEFAULT_ORGANIZATION;
		$certificate->organizationalUnitName = self::DEFAULT_ORGANIZATION_UNIT;
		$certificate->commonName = $commonName;
		$certificate->emailAddress = self::DEFAULT_EMAIL;

		return $certificate;
	}

	/**
	 * @param string $commonName = ''
	 * @param string $passphrase = null
	 * @param string $algorithm = self::ALGORITHM_SHA512
	 * @param int $length = self::DEFAULT_BITS
	 * @param int $type = OPENSSL_KEYTYPE_RSA
	 *
	 * @return QuarkCertificate
	 */
	public static function ForDomainCSR ($commonName = '', $passphrase = null, $algorithm = self::ALGORITHM_SHA512, $length = self::DEFAULT_BITS, $type = OPENSSL_KEYTYPE_RSA) {
		$certificate = self::ForCSR($commonName, $passphrase, $algorithm, $length, $type);

		if ($certificate != null)
			$certificate->AltName(new QuarkCertificateSAN($commonName));

		return $certificate;
	}
}

/**
 * Class QuarkCertificateSAN
 *
 * @package Quark
 */
class QuarkCertificateSAN {
	const TYPE_DNS = 'DNS';
	const TYPE_EMAIL = 'email';
	const TYPE_IP = 'IP Address';
	const TYPE_OTHER = 'otherName';
	const TYPE_DIR = 'dirName';

	/**
	 * @var string $_value = ''
	 */
	private $_value = '';

	/**
	 * @var string $_type = self::TYPE_DNS
	 */
	private $_type = self::TYPE_DNS;

	/**
	 * @param string $value = ''
	 * @param string $type = self::TYPE_DNS
	 */
	public function __construct ($value = '', $type = self::TYPE_DNS) {
		$this->Value($value);
		$this->Type($type);
	}

	/**
	 * @param string $value = ''
	 *
	 * @return string
	 */
	public function Value ($value = '') {
		if (func_num_args() != 0)
			$this->_value = $value;

		return $this->_value;
	}

	/**
	 * @param string $type = self::TYPE_DNS
	 *
	 * @return string
	 */
	public function Type ($type = self::TYPE_DNS) {
		if (func_num_args() != 0)
			$this->_type = $type;

		return $this->_type;
	}

	/**
	 * @return string
	 */
	public function Record () {
		return $this->_type . ':' . $this->_value;
	}

	/**
	 * @param string $altName = ''
	 *
	 * @return QuarkCertificateSAN[]
	 */
	public static function FromAltName ($altName = '') {
		$out = array();
		$sans = explode(',', $altName);

		foreach ($sans as $i => &$item) {
			$san = explode(':', $item);

			if (sizeof($san) == 2)
				$out[] = new self($san[1], $san[0]);
		}

		unset($i, $item, $san, $sans);

		return $out;
	}
}

/**
 * Class QuarkCipher
 *
 * @package Quark
 */
class QuarkCipher {
	const HASH_MD5 = '1';
	const HASH_BLOW_FISH = '2';
	const HASH_EKS_BLOW_FISH = '2a';
	const HASH_SHA256 = '5';
	const HASH_SHA512 = '6';
	
	/**
	 * @var IQuarkEncryptionProtocol $_protocol
	 */
	private $_protocol;
	
	/**
	 * @param IQuarkEncryptionProtocol $protocol = null
	 */
	public function __construct (IQuarkEncryptionProtocol $protocol = null) {
		$this->Protocol($protocol);
	}
	
	/**
	 * @param IQuarkEncryptionProtocol $protocol = null
	 *
	 * @return IQuarkEncryptionProtocol
	 */
	public function &Protocol (IQuarkEncryptionProtocol $protocol = null) {
		if (func_num_args() != 0)
			$this->_protocol = $protocol;
		
		return $this->_protocol;
	}
	
	/**
	 * @param string $key = ''
	 * @param string $data = ''
	 *
	 * @return string
	 */
	public function Encrypt ($key = '', $data = '') {
		return $this->_protocol == null ? '' : $this->_protocol->Encrypt($key, $data);
	}
	
	/**
	 * @param string $key = ''
	 * @param string $data = ''
	 *
	 * @return string
	 */
	public function Decrypt ($key = '', $data = '') {
		return $this->_protocol == null ? '' : $this->_protocol->Decrypt($key, $data);
	}
	
	/**
	 * http://www.slashroot.in/how-are-passwords-stored-linux-understanding-hashing-shadow-utils
	 * https://ubuntuforums.org/showthread.php?t=1169551&p=7348429#post7348429
	 *
	 * @param string $password
	 * @param string $salt
	 * @param string $hash = self::HASH_SHA512
	 *
	 * @return string
	 */
	public static function UnixPassword ($password = '', $salt = '', $hash = self::HASH_SHA512) {
		return crypt($password, '$' . $hash . '$' . $salt . '$');
	}
	
	/**
	 * @param int $chars = 8
	 * @param string $alphabet = Quark::ALPHABET_PASSWORD
	 *
	 * @return string
	 */
	public static function UnixPasswordSalt ($chars = 8, $alphabet = Quark::ALPHABET_PASSWORD) {
		return Quark::GenerateByPattern('\c{' . $chars . '}', $alphabet);
	}
}

/**
 * Interface IQuarkEncryptionProtocol
 *
 * @package Quark
 */
interface IQuarkEncryptionProtocol {
	/**
	 * @param string $key
	 * @param string $data
	 *
	 * @return string
	 */
	public function Encrypt($key, $data);

	/**
	 * @param string $key
	 * @param string $data
	 *
	 * @return string
	 */
	public function Decrypt($key, $data);
}

/**
 * Interface IQuarkEncryptionAlgorithm
 *
 * @package Quark
 */
interface IQuarkEncryptionAlgorithm {
	/**
	 * @param QuarkEncryptionKey $key
	 *
	 * @return bool
	 */
	public function EncryptionAlgorithmKeyGenerate(QuarkEncryptionKey &$key);

	/**
	 * @param QuarkEncryptionKey $keyPrivate
	 * @param QuarkEncryptionKey $keyPublic
	 *
	 * @return string
	 */
	public function EncryptionAlgorithmKeySharedSecret(QuarkEncryptionKey &$keyPrivate, QuarkEncryptionKey &$keyPublic);

	/**
	 * @param QuarkEncryptionKey $key
	 *
	 * @return QuarkPEMDTO[]
	 */
	public function EncryptionAlgorithmKeyPEMEncode(QuarkEncryptionKey &$key);

	/**
	 * @param QuarkEncryptionKey $key
	 * @param QuarkPEMDTO $dto
	 *
	 * @return bool
	 */
	public function EncryptionAlgorithmKeyPEMDecode(QuarkEncryptionKey &$key, QuarkPEMDTO $dto);

	/**
	 * @param QuarkEncryptionKey $key
	 * @param string $data
	 *
	 * @return string
	 */
	public function EncryptionAlgorithmSign(QuarkEncryptionKey &$key, $data);
}

/**
 * Interface IQuarkEncryptionPrimitive
 *
 * @package Quark
 */
interface IQuarkEncryptionPrimitive {
	/**
	 * @param string $kind
	 *
	 * @return bool
	 */
	public function EncryptionPrimitiveRecognizeKind($kind);

	/**
	 * @param IQuarkEncryptionPrimitive[] $elements
	 *
	 * @return
	 */
	public function EncryptionPrimitiveRecognizeCompound(&$elements);

	/**
	 * @return QuarkPEMDTO[]
	 */
	public function EncryptionPrimitivePEMEncode();

	/**
	 * @param QuarkPEMDTO $dto
	 *
	 * @return bool
	 */
	public function EncryptionPrimitivePEMDecode(QuarkPEMDTO $dto);
}

/**
 * Class QuarkEncryptionKey
 *
 * @package Quark
 */
class QuarkEncryptionKey implements IQuarkEncryptionPrimitive {
	const HKDF_HASH_SHA256 = 'sha256';
	const HKDF_HASH_SHA384 = 'sha384';
	const HKDF_HASH_SHA512 = 'sha512';

	/**
	 * @var IQuarkEncryptionAlgorithm $_algorithm
	 */
	private $_algorithm;

	/**
	 * @var string $_valueSymmetric
	 */
	private $_valueSymmetric;

	/**
	 * @var string $_valueAsymmetricPublic
	 */
	private $_valueAsymmetricPublic;

	/**
	 * @var string $_valueAsymmetricPrivate
	 */
	private $_valueAsymmetricPrivate;

	/**
	 * @var QuarkEncryptionKeyDetails $_details
	 */
	private $_details;

	/**
	 * @var QuarkFile $_file
	 */
	private $_file;

	/**
	 * @var string $_passphrase
	 */
	private $_passphrase;

	/**
	 * @param QuarkFile $file = null
	 *
	 * @return QuarkFile
	 */
	public function &File (QuarkFile $file = null) {
		if (func_num_args() != 0)
			$this->_file = $file;

		return $this->_file;
	}

	/**
	 * @param IQuarkEncryptionAlgorithm $algorithm = null
	 *
	 * @return IQuarkEncryptionAlgorithm
	 */
	public function &Algorithm (IQuarkEncryptionAlgorithm $algorithm = null) {
		if (func_num_args() != 0)
			$this->_algorithm = $algorithm;

		return $this->_algorithm;
	}

	/**
	 * @param string $value = null
	 *
	 * @return string
	 */
	public function ValueSymmetric ($value = null) {
		if (func_num_args() != 0)
			$this->_valueSymmetric = $value;

		return $this->_valueSymmetric;
	}

	/**
	 * @param string $value = null
	 *
	 * @return string
	 */
	public function ValueAsymmetricPublic ($value = null) {
		if (func_num_args() != 0)
			$this->_valueAsymmetricPublic = $value;

		return $this->_valueAsymmetricPublic;
	}

	/**
	 * @param string $value = null
	 *
	 * @return string
	 */
	public function ValueAsymmetricPrivate ($value = null) {
		if (func_num_args() != 0)
			$this->_valueAsymmetricPrivate = $value;

		return $this->_valueAsymmetricPrivate;
	}

	/**
	 * @param QuarkEncryptionKeyDetails $details = null
	 *
	 * @return QuarkEncryptionKeyDetails
	 */
	public function &Details (QuarkEncryptionKeyDetails $details = null) {
		if (func_num_args() != 0)
			$this->_details = $details;

		return $this->_details;
	}

	/**
	 * @param string $passphrase = null
	 *
	 * @return string
	 */
	public function Passphrase ($passphrase = null) {
		if (func_num_args() != 0)
			$this->_passphrase = $passphrase;

		return $this->_passphrase;
	}

	/**
	 * @param QuarkEncryptionKey $with = null
	 *
	 * @return string
	 */
	public function SharedSecret (QuarkEncryptionKey $with = null) {
		return $with == null || $this->_algorithm == null ? null : $this->_algorithm->EncryptionAlgorithmKeySharedSecret($this, $with);
	}

	/**
	 * @param QuarkEncryptionKey $with = null
	 *
	 * @return string
	 */
	public function SharedSecretOpenSSL (QuarkEncryptionKey $with = null) {
		if ($with == null || $this->_algorithm == null) return null;

		$keyPrivate = null;
		$keyPublic = null;

		if (($this->IsAsymmetricPair() || $this->IsAsymmetricPrivate()) && $with->IsAsymmetricPublic()) {
			$keyPrivate = clone $this;
			$keyPublic = clone $with;
		}

		if (($with->IsAsymmetricPair() || $with->IsAsymmetricPrivate()) && $this->IsAsymmetricPublic()) {
			$keyPrivate = clone $with;
			$keyPublic = clone $this;
		}

		if ($keyPrivate == null || $keyPublic == null) return null;

		$keyPrivateDTO = $keyPrivate->EncryptionPrimitivePEMEncode();
		$keyPublicDTO = $keyPublic->EncryptionPrimitivePEMEncode();

		$keyPrivateOut = null;
		$keyPublicOut = null;

		if (isset($keyPrivateDTO[0]) && $keyPrivateDTO[0]->KindIs(QuarkPEMIOProcessor::KIND_KEY_PRIVATE))
			$keyPrivateOut = $keyPrivateDTO[0];

		if (isset($keyPrivateDTO[1]) && $keyPrivateDTO[1]->KindIs(QuarkPEMIOProcessor::KIND_KEY_PRIVATE))
			$keyPrivateOut = $keyPrivateDTO[1];

		if (isset($keyPublicDTO[0]) && $keyPublicDTO[0]->KindIs(QuarkPEMIOProcessor::KIND_KEY_PUBLIC))
			$keyPublicOut = $keyPublicDTO[0];

		if ($keyPrivateOut == null || $keyPublicOut == null) return null;

		return openssl_pkey_derive(
			openssl_pkey_get_public($keyPublicOut->PEMEncode()),
			openssl_pkey_get_private($keyPrivateOut->PEMEncode()),
			$this->_details->Bits()
		);
	}

	/**
	 * @return string
	 */
	public function ExportOpenSSL () {
		return openssl_pkey_export($this->_valueAsymmetricPrivate, $output, $this->_passphrase) ? $output : null;
	}

	/**
	 * @return bool
	 */
	public function Generate () {
		return $this->_algorithm != null && $this->_algorithm->EncryptionAlgorithmKeyGenerate($this);
	}

	/**
	 * @param string $data
	 *
	 * @return string
	 */
	public function Sign ($data = '') {
		return $this->_algorithm == null ? null : $this->_algorithm->EncryptionAlgorithmSign($this, $data);
	}

	/**
	 * @return bool
	 */
	public function IsSymmetric () {
		return $this->_valueAsymmetricPublic == null && $this->_valueAsymmetricPrivate == null && $this->_valueSymmetric != null;
	}

	/**
	 * @return bool
	 */
	public function IsAsymmetricPublic () {
		return $this->_valueAsymmetricPublic != null && $this->_valueAsymmetricPrivate == null && $this->_valueSymmetric == null;
	}

	/**
	 * @return bool
	 */
	public function IsAsymmetricPrivate () {
		return $this->_valueAsymmetricPublic == null && $this->_valueAsymmetricPrivate != null && $this->_valueSymmetric == null;
	}

	/**
	 * @return bool
	 */
	public function IsAsymmetricPair () {
		return $this->_valueAsymmetricPublic != null && $this->_valueAsymmetricPrivate != null && $this->_valueSymmetric == null;
	}

	/**
	 * @param QuarkEncryptionKey $key = null
	 *
	 * @return QuarkEncryptionKey
	 */
	public function Merge (QuarkEncryptionKey &$key = null) {
		if ($key != null) {
			if ($key->ValueSymmetric() != null)
				$this->ValueSymmetric($key->ValueSymmetric());

			if ($key->ValueAsymmetricPublic() != null)
				$this->ValueAsymmetricPublic($key->ValueAsymmetricPublic());

			if ($key->ValueAsymmetricPrivate() != null)
				$this->ValueAsymmetricPrivate($key->ValueAsymmetricPrivate());

			$this->_details->Populate($key->Details());
		}

		return $this;
	}

	/**
	 * @param QuarkFile $file = null
	 *
	 * @return QuarkFile
	 */
	public static function FromFile (QuarkFile $file = null) {
		if ($file == null) return null;

		$out = new self();
		$out->File();

		return $file;
	}

	/**
	 * @param string $location = ''
	 * @param IQuarkEncryptionAlgorithm $algorithm = null
	 *
	 * @return QuarkEncryptionKey
	 */
	public static function FromFileLocation ($location = '', IQuarkEncryptionAlgorithm $algorithm = null) {
		$processor = new QuarkPEMIOProcessor();
		$processor->Primitives()[0]->Algorithm($algorithm); // TODO: refactor

		$file = new QuarkFile($location);
		$data = $file->Decode($processor, true);

		if (sizeof($data) != 1 || !($data[0] instanceof QuarkEncryptionKey)) return null;

		$data[0]->File($file);

		return $data[0];
	}

	/**
	 * @deprecated
	 *
	 * @param string $location = ''
	 * @param IQuarkEncryptionAlgorithm $algorithm = null
	 *
	 * @return QuarkEncryptionKey
	 */
	public static function FromFileLocationPair ($location = '', IQuarkEncryptionAlgorithm $algorithm = null) {
		$processor = new QuarkPEMIOProcessor();
		$processor->Primitives()[0]->Algorithm($algorithm); // TODO: refactor

		$file = new QuarkFile($location);
		$data = $file->Decode($processor, true);

		print_r($data);

		if (sizeof($data) != 2) return null;
		if (!($data[0] instanceof QuarkEncryptionKey)) return null;
		if (!($data[1] instanceof QuarkEncryptionKey)) return null;

		if ($data[0]->IsAsymmetricPrivate() && $data[1]->IsAsymmetricPublic())
			return $data[0]->Merge($data[1]);

		if ($data[1]->IsAsymmetricPrivate() && $data[0]->IsAsymmetricPublic())
			return $data[1]->Merge($data[0]);

		return null;
	}

	/**
	 * @param string $kind
	 *
	 * @return bool
	 */
	public function EncryptionPrimitiveRecognizeKind ($kind) {
		return preg_match('#KEY$#s', $kind);
	}

	/**
	 * @param IQuarkEncryptionPrimitive[] $elements
	 *
	 * @return bool
	 */
	public function EncryptionPrimitiveRecognizeCompound (&$elements) {
		$out = false;

		if (sizeof($elements) == 2 && QuarkObject::IsArrayOf($elements, new QuarkEncryptionKey())) {
			/**
			 * @var QuarkEncryptionKey[] $elements
			 */

			if (!$out && $elements[0]->IsAsymmetricPrivate() && $elements[1]->IsAsymmetricPublic()) {
				$elements[0]->Merge($elements[1]);
				unset($elements[1]);
				$out = true;
			}

			if (!$out && $elements[1]->IsAsymmetricPrivate() && $elements[0]->IsAsymmetricPublic()) {
				$elements[1]->Merge($elements[0]);
				unset($elements[0]);
				$out = true;
			}
		}

		return $out;
	}

	/**
	 * @return QuarkPEMDTO[]
	 */
	public function EncryptionPrimitivePEMEncode () {
		return $this->_algorithm == null ? null : $this->_algorithm->EncryptionAlgorithmKeyPEMEncode($this);
	}

	/**
	 * @param QuarkPEMDTO $dto
	 *
	 * @return bool
	 */
	public function EncryptionPrimitivePEMDecode (QuarkPEMDTO $dto) {
		return $this->_algorithm == null ? false : $this->_algorithm->EncryptionAlgorithmKeyPEMDecode($this, $dto);
	}

	/**
	 * @param string $ikm = ''
	 * @param int $length = 0
	 * @param string $info = ''
	 * @param string $salt = ''
	 * @param string $hash = self::HKDF_HASH_SHA256
	 *
	 * @return string
	 */
	public static function HKDF ($ikm = '', $length = 0, $info = '', $salt = '', $hash = self::HKDF_HASH_SHA256) {
		$prk = self::HKDFHash($ikm, $salt, $hash);
		$dkm = self::HKDFHash($info . chr(1), $prk);

		return substr($dkm, 0, $length);
	}

	/**
	 * @param string $data = ''
	 * @param string $key = ''
	 * @param string $hash = self::HKDF_HASH_SHA256
	 *
	 * @return string
	 */
	public static function HKDFHash ($data = '', $key = '', $hash = self::HKDF_HASH_SHA256) {
		return hash_hmac($hash, $data, $key, true);
	}
}

/**
 * Class QuarkEncryptionKeyDetails
 *
 * @package Quark
 */
class QuarkEncryptionKeyDetails {
	const OPENSSL_KEY_BITS = 'bits';
	const OPENSSL_KEY_TYPE = 'type';
	const OPENSSL_KEY_PUBLIC = 'key';

	/**
	 * @var string[] $_properties
	 */
	private static $_properties = array(
		'Curve' => 'curve_name',
		'CurveID' => 'curve_oid',
		'CurveCoordinateX' => 'x',
		'CurveCoordinateY' => 'y',

		'ExponentPrivate' => 'd',

		'ExponentPublic' => 'e',
		'Modulus' => 'n',

		'FactorFirstPrime' => 'p',
		'FactorFirstExponent' => 'dmp1',
		'FactorSecondPrime' => 'q',
		'FactorSecondExponent' => 'dmq1',
		'FactorCoefficient' => 'iqmp',

		'Generator' => 'g',
		'KeyPublic' => 'pub_key',
		'KeyPrivate' => 'priv_key'
	);

	/**
	 * @var int $_bits
	 */
	private $_bits;

	/**
	 * @var int $_openSSLType
	 */
	private $_openSSLType;

	/**
	 * @var string $_openSSLPublic
	 */
	private $_openSSLPublic;

	/**
	 * @var string $_curve
	 */
	private $_curve;

	/**
	 * @var string $_curveID
	 */
	private $_curveID;

	/**
	 * @var string $_curveCoordinateX
	 */
	private $_curveCoordinateX;

	/**
	 * @var string $_curveCoordinateY
	 */
	private $_curveCoordinateY;

	/**
	 * @var string $_exponentPrivate
	 */
	private $_exponentPrivate;

	/**
	 * @var string $_exponentPublic
	 */
	private $_exponentPublic;

	/**
	 * @var string $_modulus
	 */
	private $_modulus;

	/**
	 * @var string $_factorFirstPrime
	 */
	private $_factorFirstPrime;

	/**
	 * @var string $_factorFirstExponent
	 */
	private $_factorFirstExponent;

	/**
	 * @var string $_factorSecondPrime
	 */
	private $_factorSecondPrime;

	/**
	 * @var string $_factorSecondExponent
	 */
	private $_factorSecondExponent;

	/**
	 * @var string $_factorCoefficient
	 */
	private $_factorCoefficient;

	/**
	 * @var string $_generator
	 */
	private $_generator;

	/**
	 * @var string $_keyPrivate
	 */
	private $_keyPrivate;

	/**
	 * @var string $_keyPublic
	 */
	private $_keyPublic;

	/**
	 * @param int $bits = null
	 *
	 * @return int
	 */
	public function Bits ($bits = null) {
		if (func_num_args() != 0)
			$this->_bits = $bits;

		return $this->_bits;
	}

	/**
	 * @param int $type = null
	 *
	 * @return int
	 */
	public function OpenSSLType ($type = null) {
		if (func_num_args() != 0)
			$this->_openSSLType = $type;

		return $this->_openSSLType;
	}

	/**
	 * @param string $key = null
	 *
	 * @return string
	 */
	public function OpenSSLPublic ($key = null) {
		if (func_num_args() != 0)
			$this->_openSSLPublic = $key;

		return $this->_openSSLPublic;
	}

	/**
	 * @return string
	 */
	public function OpenSSLPublicBinary () {
		return QuarkPEMIOProcessor::DecodeContentDirect($this->_openSSLPublic, QuarkPEMIOProcessor::KIND_KEY_PUBLIC);
	}

	/**
	 * @param string $curve = null
	 *
	 * @return string
	 */
	public function Curve ($curve = null) {
		if (func_num_args() != 0)
			$this->_curve = $curve;

		return $this->_curve;
	}

	/**
	 * @param string $id = null
	 *
	 * @return string
	 */
	public function CurveID ($id = null) {
		if (func_num_args() != 0)
			$this->_curveID = $id;

		return $this->_curveID;
	}

	/**
	 * @param string $coordinate = null
	 *
	 * @return string
	 */
	public function CurveCoordinateX ($coordinate = null) {
		if (func_num_args() != 0)
			$this->_curveCoordinateX = $coordinate;

		return $this->_curveCoordinateX;
	}

	/**
	 * @param string $coordinate = null
	 *
	 * @return string
	 */
	public function CurveCoordinateY ($coordinate = null) {
		if (func_num_args() != 0)
			$this->_curveCoordinateY = $coordinate;

		return $this->_curveCoordinateY;
	}

	/**
	 * @param string $exponent = null
	 *
	 * @return string
	 */
	public function ExponentPrivate ($exponent = null) {
		if (func_num_args() != 0)
			$this->_exponentPrivate = $exponent;

		return $this->_exponentPrivate;
	}

	/**
	 * @param string $exponent = null
	 *
	 * @return string
	 */
	public function ExponentPublic ($exponent = null) {
		if (func_num_args() != 0)
			$this->_exponentPublic = $exponent;

		return $this->_exponentPublic;
	}

	/**
	 * @param string $modulus = null
	 *
	 * @return string
	 */
	public function Modulus ($modulus = null) {
		if (func_num_args() != 0)
			$this->_modulus = $modulus;

		return $this->_modulus;
	}

	/**
	 * @param string $prime = null
	 *
	 * @return string
	 */
	public function FactorFirstPrime ($prime = null) {
		if (func_num_args() != 0)
			$this->_factorFirstPrime = $prime;

		return $this->_factorFirstPrime;
	}

	/**
	 * @param string $exponent = null
	 *
	 * @return string
	 */
	public function FactorFirstExponent ($exponent = null) {
		if (func_num_args() != 0)
			$this->_factorFirstExponent = $exponent;

		return $this->_factorFirstExponent;
	}

	/**
	 * @param string $prime = null
	 *
	 * @return string
	 */
	public function FactorSecondPrime ($prime = null) {
		if (func_num_args() != 0)
			$this->_factorSecondPrime = $prime;

		return $this->_factorSecondPrime;
	}

	/**
	 * @param string $exponent = null
	 *
	 * @return string
	 */
	public function FactorSecondExponent ($exponent = null) {
		if (func_num_args() != 0)
			$this->_factorSecondExponent = $exponent;

		return $this->_factorSecondExponent;
	}

	/**
	 * @param string $coefficient = null
	 *
	 * @return string
	 */
	public function FactorCoefficient ($coefficient = null) {
		if (func_num_args() != 0)
			$this->_factorCoefficient = $coefficient;

		return $this->_factorCoefficient;
	}

	/**
	 * @param string $generator = null
	 *
	 * @return string
	 */
	public function Generator ($generator = null) {
		if (func_num_args() != 0)
			$this->_generator = $generator;

		return $this->_generator;
	}

	/**
	 * @param string $key = null
	 *
	 * @return string
	 */
	public function KeyPrivate ($key = null) {
		if (func_num_args() != 0)
			$this->_keyPrivate = $key;

		return $this->_keyPrivate;
	}

	/**
	 * @param string $key = null
	 *
	 * @return string
	 */
	public function KeyPublic ($key = null) {
		if (func_num_args() != 0)
			$this->_keyPublic = $key;

		return $this->_keyPublic;
	}

	/**
	 * @param QuarkEncryptionKeyDetails $details = null
	 *
	 * @return QuarkEncryptionKeyDetails
	 */
	public function Populate (QuarkEncryptionKeyDetails $details = null) {
		if ($details != null) {
			$buffer = null;

			foreach (self::$_properties as $property => &$field) {
				$buffer = $details->$property();

				if ($buffer !== null)
					$this->$property($buffer);
			}

			unset($property, $field, $buffer);
		}

		return $this;
	}

	/**
	 * @param string $openSSLType = ''
	 * @param array|object $data = []
	 *
	 * @return QuarkEncryptionKeyDetails
	 */
	public function PopulateOpenSSL ($openSSLType = '', $data = []) {
		if (is_object($data)) $data = (array)$data;

		if (is_array($data)) {
			if (isset($data[self::OPENSSL_KEY_TYPE]))
				$this->OpenSSLType($data[self::OPENSSL_KEY_TYPE]);

			if (isset($data[self::OPENSSL_KEY_PUBLIC]))
				$this->OpenSSLPublic($data[self::OPENSSL_KEY_PUBLIC]);

			if (isset($data[self::OPENSSL_KEY_BITS]))
				$this->Bits($data[self::OPENSSL_KEY_BITS]);

			if (isset($data[$openSSLType])) {
				if (is_object($data[$openSSLType]))
					$data[$openSSLType] = (array)$data[$openSSLType];

				if (is_array($data[$openSSLType]))
					foreach (self::$_properties as $property => &$key)
						if (isset($data[$openSSLType][$key]))
							$this->$property($data[$openSSLType][$key]);
			}

			unset($property, $key);
		}

		return $this;
	}

	/**
	 * @param string $curve = ''
	 *
	 * @return QuarkEncryptionKeyDetails
	 */
	public static function FromCurve ($curve = '') {
		$out = new self();
		$out->Curve($curve);

		return $out;
	}

	/**
	 * @param string $x = ''
	 * @param string $y = ''
	 *
	 * @return QuarkEncryptionKeyDetails
	 */
	public static function FromCurveCoordinates ($x = '', $y = '') {
		$out = new self();
		$out->CurveCoordinateX($x);
		$out->CurveCoordinateY($y);

		return $out;
	}
}

/**
 * @deprecated
 *
 * Class QuarkOpenSSLCipher
 *
 * @package Quark
 */
class QuarkOpenSSLCipher implements IQuarkEncryptionProtocol {
	const CIPHER_AES_256 = 'aes-256-cbc';

	/**
	 * @var string $_iv = ''
	 */
	private $_iv = '';

	/**
	 * @var string $_algorithm = self::CIPHER_AES_256
	 */
	private $_algorithm = self::CIPHER_AES_256;

	/**
	 * @var bool $_asymmetricPublic = false
	 */
	private $_asymmetricPublic = false;

	/**
	 * @var bool $_asymmetricPrivate = false
	 */
	private $_asymmetricPrivate = false;

	/**
	 * @param string $iv = ''
	 * @param string $algorithm = self::CIPHER_AES_256
	 */
	public function __construct ($iv = '', $algorithm = self::CIPHER_AES_256) {
		$this->InitializationVector($iv);
		$this->Algorithm($algorithm);
	}
	
	/**
	 * @param string $key
	 * @param string $data
	 *
	 * @return string
	 */
	public function Encrypt ($key, $data) {
		$encrypt = '';

		if ($this->_asymmetricPrivate) $encrypt = 'openssl_private_encrypt';
		if ($this->_asymmetricPublic) $encrypt = 'openssl_public_encrypt';

		if (!$encrypt)
			return base64_encode(openssl_encrypt($data, $this->_algorithm, $key, OPENSSL_RAW_DATA, $this->_iv()));

		$out = '';
		return $encrypt($data, $out, $key) ? base64_encode($out) : '';
	}

	/**
	 * @param string $key
	 * @param string $data
	 *
	 * @return string
	 */
	public function Decrypt ($key, $data) {
		$decrypt = '';

		if ($this->_asymmetricPrivate) $decrypt = 'openssl_private_decrypt';
		if ($this->_asymmetricPublic) $decrypt = 'openssl_public_decrypt';

		if (!$decrypt)
			return openssl_decrypt(base64_decode($data), $this->_algorithm, $key, OPENSSL_RAW_DATA, $this->_iv());

		$out = '';
		return $decrypt(base64_decode($data), $out, $key) ? $out : '';
	}

	/**
	 * @param string $iv = ''
	 *
	 * @return string
	 */
	public function InitializationVector ($iv = '') {
		if (func_num_args() != 0)
			$this->_iv = $iv;

		return $this->_iv;
	}

	/**
	 * @param string $algorithm = self::CIPHER_AES_256
	 *
	 * @return string
	 */
	public function Algorithm ($algorithm = self::CIPHER_AES_256) {
		if (func_num_args() != 0)
			$this->_algorithm = $algorithm;
		
		return $this->_algorithm;
	}

	/**
	 * @return string
	 */
	private function _iv () {
		return substr(hash('sha256', $this->_iv), 0, 16);
	}

	/**
	 * @param string $iv = ''
	 * @param string $algorithm = self::CIPHER_AES_256
	 *
	 * @return QuarkOpenSSLCipher
	 */
	public static function AsymmetricPublic ($iv = '', $algorithm = self::CIPHER_AES_256) {
		$cipher = new self($iv, $algorithm);
		$cipher->_asymmetricPublic = true;

		return $cipher;
	}

	/**
	 * @param string $iv = ''
	 * @param string $algorithm = self::CIPHER_AES_256
	 *
	 * @return QuarkOpenSSLCipher
	 */
	public static function AsymmetricPrivate ($iv = '', $algorithm = self::CIPHER_AES_256) {
		$cipher = new self($iv, $algorithm);
		$cipher->_asymmetricPrivate = true;

		return $cipher;
	}
}

/**
 * @deprecated
 *
 * Class QuarkCipherKeyPair
 *
 * @package Quark
 */
class QuarkCipherKeyPair extends QuarkFile {
	const KEY_PRIVATE_ENCRYPTED = '#-----BEGIN ENCRYPTED PRIVATE KEY-----(.*)-----END ENCRYPTED PRIVATE KEY-----#Uis';
	const KEY_PRIVATE = '#-----BEGIN PRIVATE KEY-----(.*)-----END PRIVATE KEY-----#Uis';
	const KEY_PUBLIC = '#-----BEGIN PUBLIC KEY-----(.*)-----END PUBLIC KEY-----#Uis';

	const PEM_TARGET_PUBLIC = 'PUBLIC';
	const PEM_TARGET_PRIVATE = 'PRIVATE';
	const PEM_CHUNK = 64;

	/**
	 * @var array $_config
	 */
	private $_config;

	/**
	 * @var string $_passphrase = null
	 */
	private $_passphrase = null;

	/**
	 * @var resource $_key
	 */
	private $_key;

	/**
	 * @var QuarkCipher|IQuarkEncryptionProtocol $_cipherPrivate
	 */
	private $_cipherPrivate;

	/**
	 * @var QuarkCipher|IQuarkEncryptionProtocol $_cipherPublic
	 */
	private $_cipherPublic;

	/**
	 * @var bool $_located = true
	 */
	private $_located = true;

	/**
	 * @var bool $_init = false
	 */
	private $_init = false;

	/**
	 * @param string $location = ''
	 * @param string $passphrase = null
	 * @param bool $load = false
	 * @param array $config = null
	 */
	public function __construct ($location = '', $load = false, $passphrase = null, $config = null) {
		$this->Passphrase($passphrase);
		$this->Config($config == null ? QuarkCertificate::OpenSSLConfig() : $config);

		$this->_cipherPublic = new QuarkCipher(QuarkOpenSSLCipher::AsymmetricPublic());
		$this->_cipherPrivate = new QuarkCipher(QuarkOpenSSLCipher::AsymmetricPrivate());

		parent::__construct($location, $load);
	}

	/**
	 * @param array $config = null
	 *
	 * @return array
	 */
	public function Config ($config = null) {
		if (func_num_args() != 0)
			$this->_config = $config;

		return $this->_config;
	}

	/**
	 * @param string $passphrase = null
	 *
	 * @return string
	 */
	public function Passphrase ($passphrase = null) {
		if (func_num_args() != 0)
			$this->_passphrase = $passphrase;

		return $this->_passphrase;
	}

	/**
	 * @return bool
	 */
	public function Init () {
		return $this->_init;
	}

	/**
	 * @param string $message = ''
	 * @param bool $openssl = true
	 *
	 * @return bool
	 */
	private static function _error ($message = '', $openssl = true) {
		Quark::Log('[QuarkCipherKeyPair] ' . $message . ($openssl ? '. OpenSSL error: "' . openssl_error_string() . '".' : ''), Quark::LOG_WARN);
		return false;
	}

	/**
	 * @param string $passphrase = null
	 * @param string $algorithm = QuarkCertificate::ALGORITHM_SHA512
	 * @param int $length = QuarkCertificate::DEFAULT_BITS
	 * @param int $type = OPENSSL_KEYTYPE_RSA
	 *
	 * @return QuarkCipherKeyPair
	 */
	public static function GenerateNew ($passphrase = null, $algorithm = QuarkCertificate::ALGORITHM_SHA512, $length = QuarkCertificate::DEFAULT_BITS, $type = OPENSSL_KEYTYPE_RSA) {
		$config = QuarkCertificate::OpenSSLConfig($algorithm, $length, $type);
		$out = new self('', false, null, $config);

		return $out->Generate($passphrase) ? $out : null;
	}

	/**
	 * @param array $params = []
	 * @param string $passphrase = null
	 * @param $config = null
	 *
	 * @return null|QuarkCipherKeyPair
	 */
	public static function GenerateNewByParams ($params = [], $passphrase = null, $config = null) {
		$out = new self('', false, null, $config);

		return $out->GenerateByParams($params, $passphrase) ? $out : null;
	}

	/**
	 * @param string $content = ''
	 * @param string $passphrase = null
	 *
	 * @return QuarkCipherKeyPair
	 */
	public static function FromContent ($content = '', $passphrase = null) {
		$key = new self();
		$key->_located = false;
		$key->_passphrase = $passphrase;

		if (func_num_args() != 0) {
			$key->_content = $content;
			$key->_loaded = true;

			if (func_num_args() == 2 || !preg_match(self::KEY_PRIVATE_ENCRYPTED, $key->_content))
				$key->_load();
		}

		return $key;
	}

	/**
	 * @return array|null
	 */
	public function Details () {
		return $this->_key ? openssl_pkey_get_details($this->_key) : null;
	}

	/**
	 * @param bool $pem = true
	 *
	 * @return string|resource
	 */
	public function PublicKey ($pem = true) {
		$details = $this->Details();
		if (!isset($details['key'])) return null;

		return $pem ? $details['key'] : openssl_pkey_get_public($details['key']);
	}

	/**
	 * @return array
	 */
	public function PublicKeyDetails () {
		return openssl_pkey_get_details($this->PublicKey(false));
	}

	/**
	 * @param IQuarkEncryptionProtocol $cipher = null
	 *
	 * @return QuarkCipher|IQuarkEncryptionProtocol
	 */
	public function PublicCipher (IQuarkEncryptionProtocol $cipher = null) {
		if (func_num_args() != 0)
			$this->_cipherPublic->Protocol($cipher);

		return $this->_cipherPublic;
	}

	/**
	 * @param string $data = ''
	 *
	 * @return string
	 */
	public function PublicEncrypt ($data = '') {
		return $this->_cipherPublic->Encrypt($this->PublicKey(false), $data);
	}

	/**
	 * @param string $data = ''
	 *
	 * @return string
	 */
	public function PublicDecrypt ($data = '') {
		return $this->_cipherPublic->Decrypt($this->PublicKey(false), $data);
	}

	/**
	 * @param bool $pem = true
	 *
	 * @return string|resource
	 */
	public function PrivateKey ($pem = true) {
		if (!$this->_key) return '';
		if (!$pem) return $this->_key;

		$key = '';
		return openssl_pkey_export($this->_key, $key, $this->_passphrase, $this->_config) ? $key : null;
	}

	/**
	 * @return array
	 */
	public function PrivateKeyDetails () {
		return openssl_pkey_get_details($this->PrivateKey(false));
	}

	/**
	 * @param IQuarkEncryptionProtocol $cipher = null
	 *
	 * @return QuarkCipher|IQuarkEncryptionProtocol
	 */
	public function PrivateCipher (IQuarkEncryptionProtocol $cipher = null) {
		if (func_num_args() != 0)
			$this->_cipherPrivate->Protocol($cipher);

		return $this->_cipherPrivate;
	}

	/**
	 * @param string $data = ''
	 *
	 * @return string
	 */
	public function PrivateEncrypt ($data = '') {
		return $this->_cipherPrivate->Encrypt($this->PrivateKey(false), $data);
	}

	/**
	 * @param string $data = ''
	 *
	 * @return string
	 */
	public function PrivateDecrypt ($data = '') {
		return $this->_cipherPrivate->Decrypt($this->PrivateKey(false), $data);
	}

	private function _generate ($passphrase = null, $config = []) {
		$key = openssl_pkey_new($config);

		if (!$key)
			return self::_error('Generate: Cannot generate new private key');

		$this->_key = $key;
		$this->_passphrase = $passphrase;
		$this->_loaded = true;
		$this->_init = true;

		return true;
	}

	/**
	 * @param string $passphrase = null
	 * @param string $algorithm = QuarkCertificate::ALGORITHM_SHA512
	 * @param int $length = QuarkCertificate::DEFAULT_BITS
	 * @param int $type = OPENSSL_KEYTYPE_RSA
	 *
	 * @return bool
	 */
	public function Generate ($passphrase = null, $algorithm = QuarkCertificate::ALGORITHM_SHA512, $length = QuarkCertificate::DEFAULT_BITS, $type = OPENSSL_KEYTYPE_RSA) {
		if (func_num_args() > 1)
			$this->_config = QuarkCertificate::OpenSSLConfig($algorithm, $length, $type);

		return $this->_generate($passphrase, $this->_config);
	}

	/**
	 * @param array $params = []
	 * @param string $passphrase = null
	 * @param $config = null
	 *
	 * @return bool
	 */
	public function GenerateByParams ($params = [], $passphrase = null, $config = null) {
		if (func_num_args() < 3)
			$config = $this->_config;

		if ($config == null)
			$config = array();

		$config = array_replace_recursive($config, $params);

		return $this->_generate($passphrase, $config);
	}

	/**
	 * @param string $location = ''
	 *
	 * @return bool
	 */
	public function SaveKeyPair ($location = '') {
		$_location = $this->location;
		$_content = $this->_content;
		$ok = true;

		$this->Location($location . '.public.key');
		$this->Content($this->PublicKey());
		$ok &= $this->SaveContent();

		$this->Location($location . '.private.key');
		$this->Content($this->PrivateKey());
		$ok &= $this->SaveContent();

		$this->Location($_location);
		$this->Content($_content);

		return $ok;
	}

	/**
	 * @param string $location = ''
	 *
	 * @return QuarkCipherKeyPair
	 * @throws QuarkArchException
	 */
	public function Load ($location = '') {
		if (func_num_args() == 0) parent::Load();
		else parent::Load($location);

		if ($this->Loaded())
			$this->_load();

		return $this;
	}

	/**
	 * @param string $passphrase = null
	 *
	 * @return bool
	 */
	public function Reload ($passphrase = null) {
		if (func_num_args() != 0)
			$this->_passphrase = $passphrase;

		return $this->_load();
	}

	/**
	 * @return bool
	 */
	private function _load () {
		$key = openssl_pkey_get_private($this->_content, $this->_passphrase);

		if (!$key)
			return self::_error('Load: Cannot get private key from specified content');

		$this->_key = $key;
		$this->_init = true;

		return true;
	}

	/**
	 * @param string $content = ''
	 * @param bool $load = false
	 * @param bool $mime = false
	 *
	 * @return string
	 */
	public function Content ($content = '', $load = false, $mime = false) {
		if (func_num_args() != 0) {
			if (func_num_args() == 2) parent::Content($content, $load, $mime);
			else parent::Content($content);

			$this->_load();
		}

		return $this->_content;
	}

	/**
	 * @param int $mode = self::MODE_DEFAULT
	 * @param bool $upload = false
	 *
	 * @return bool
	 */
	public function SaveContent ($mode = self::MODE_DEFAULT, $upload = false) {
		$this->_content = $this->PrivateKey();

		return parent::SaveContent($mode, $upload);
	}

	/**
	 * @return array
	 */
	public function Rules () {
		return !$this->_located ? array() : array(
			QuarkField::is($this->name, QuarkField::TYPE_STRING),
			QuarkField::is($this->type, QuarkField::TYPE_STRING),
			QuarkField::is($this->size, QuarkField::TYPE_INT),
			QuarkField::is($this->tmp_name, QuarkField::TYPE_STRING),
			QuarkField::MinLength($this->name, 1)
		);
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Link ($raw) {
		return new QuarkModel($raw
			? ($this->_located ? new QuarkCipherKeyPair($raw, true) : self::FromContent($raw))
			: clone $this
		);
	}

	/**
	 * @return mixed
	 */
	public function Unlink () {
		return $this->_located
			? $this->Location()
			: ($this->_init
				? $this->PrivateKey()
				: $this->_content
			);
	}

	/**
	 * @param string $target = ''
	 * @param string $data = ''
	 * @param string $type = ''
	 * @param int $chunk = self::PEM_CHUNK
	 *
	 * @return string
	 */
	public static function SerializePEM ($target = '', $data = '', $type = '', $chunk = self::PEM_CHUNK) {
		$type = trim(strtoupper($type) . ' ' . $target);

		return ''
			. '-----BEGIN ' . $type . ' KEY-----' . "\r\n"
			. chunk_split(base64_encode($data), $chunk, "\r\n")
			. '-----END ' . $type . ' KEY-----' . "\r\n";
	}

	/**
	 * @param string $data = ''
	 * @param string $type = ''
	 * @param int $chunk = self::PEM_CHUNK
	 *
	 * @return string
	 */
	public static function SerializePEMPublic ($data = '', $type = '', $chunk = self::PEM_CHUNK) {
		return self::SerializePEM(self::PEM_TARGET_PUBLIC, $data, $type, $chunk);
	}

	/**
	 * @param string $data = ''
	 * @param string $type = ''
	 * @param int $chunk = self::PEM_CHUNK
	 *
	 * @return string
	 */
	public static function SerializePEMPrivate ($data = '', $type = '', $chunk = self::PEM_CHUNK) {
		return self::SerializePEM(self::PEM_TARGET_PRIVATE, $data, $type, $chunk);
	}
}

/**
 * Class QuarkPEMIOProcessor
 *
 * https://pki-tutorial.readthedocs.io/en/latest/mime.html
 *
 * @package Quark
 */
class QuarkPEMIOProcessor implements IQuarkIOProcessor {
	const REGEX = '#(.*)----[- ]BEGIN ([A-Z0-9\\- ]+)[- ]----\\r?\\n(.*\\r?\\n\\r?\\n)?([a-zA-Z0-9+\\/\\r\\n]*={0,2})\\r?\\n----[- ]END([A-Z0-9 ]+)[- ]----.*#Us';

	const CHUNK = 64;

	const TOKEN_DELIMITER = '-----';
	const TOKEN_BEGIN = 'BEGIN';
	const TOKEN_END = 'END';

	const KIND_CERTIFICATE = 'CERTIFICATE';
	const KIND_CERTIFICATE_REQUEST = 'CERTIFICATE REQUEST';
	const KIND_CERTIFICATE_ATTRIBUTE = 'ATTRIBUTE CERTIFICATE';
	const KIND_KEY_PUBLIC = 'PUBLIC KEY';
	const KIND_KEY_PRIVATE = 'PRIVATE KEY';
	const KIND_KEY_PRIVATE_ENCRYPTED = 'ENCRYPTED PRIVATE KEY';
	const KIND_X509_CRL = 'X509 CRL';
	const KIND_PKCS7 = 'PKCS7';
	const KIND_CMS = 'CMS';

	const MIME_X_PEM_FILE = 'application/x-pem-file';

	/**
	 * @var IQuarkEncryptionPrimitive[] $_primitives
	 */
	private $_primitives = array();

	/**
	 * QuarkPEMIOProcessor constructor
	 */
	public function __construct () {
		$this->_primitives = array(
			new QuarkEncryptionKey()
		);
	}

	/**
	 * @return IQuarkEncryptionPrimitive[]
	 */
	public function &Primitives () {
		return $this->_primitives;
	}

	/**
	 * @return string
	 */
	public function MimeType () {
		return self::MIME_X_PEM_FILE; // TODO: add support for other PKI MIME types, maybe within private field
	}

	/**
	 * @param $data
	 *
	 * @return string
	 */
	public function Encode ($data) {
		if ($data instanceof IQuarkEncryptionPrimitive)
			$data = array($data);

		if (!QuarkObject::isTraversable($data)) return null;

		$out = '';
		$buffer = array();

		foreach ($data as $i => &$item) {
			$buffer = array($item);

			if ($item instanceof IQuarkEncryptionPrimitive)
				$buffer = $item->EncryptionPrimitivePEMEncode();

			if (is_array($buffer))
				foreach ($buffer as $j => &$dto)
					if ($dto instanceof QuarkPEMDTO)
						$out .= $dto->PEMEncode();
		}

		unset($i, $j, $item, $buffer);

		return $out;
	}

	/**
	 * @param $raw
	 *
	 * @return IQuarkEncryptionPrimitive[]
	 */
	public function Decode ($raw) {
		$out = array();

		if (preg_match_all(self::REGEX, $raw, $found, PREG_SET_ORDER) !== false) {
			$item = null;
			$ok = false;
			foreach ($found as $i => &$element) {
				if ($element[2] == '') continue;

				$item = null;

				foreach ($this->_primitives as $j => &$primitive)
					if ($primitive->EncryptionPrimitiveRecognizeKind($element[2]))
						$item = clone $primitive;

				if ($item == null) continue;

				$ok = $item->EncryptionPrimitivePEMDecode(QuarkPEMDTO::FromPemRegularExpression(
					$element[0],
					$element[2],
					self::DecodeHeaders($element[3]),
					self::DecodeHeaders($element[1]),
					self::DecodeContent($element[4])
				));

				if ($ok)
					$out[] = $item;
			}

			foreach ($this->_primitives as $j => &$primitive)
				if ($primitive->EncryptionPrimitiveRecognizeCompound($out))
					$out = array_values($out);

			unset($i, $j, $element, $primitive);
		}

		unset($found);

		return $out;
	}

	/**
	 * @param string $raw
	 * @param bool $fallback
	 *
	 * @return mixed
	 */
	public function Batch ($raw, $fallback) {
		// TODO: Implement Batch() method.
	}

	/**
	 * @return bool
	 */
	public function ForceInput () {
		// TODO: Implement ForceInput() method.
	}

	/**
	 * @param string[] $headers = []
	 *
	 * @return string
	 */
	public static function EncodeHeaders ($headers = []) {
		$out = '';

		if (QuarkObject::isTraversable($headers)) {
			foreach ($headers as $key => &$value)
				$out .= $key . ': ' . $value . "\r\n";

			unset($key, $value);
		}

		return $out;
	}

	/**
	 * @param string $content = ''
	 * @param int $chunk = self::CHUNK
	 *
	 * @return string
	 */
	public static function EncodeContent ($content = '', $chunk = self::CHUNK) {
		return chunk_split(base64_encode($content), $chunk, "\r\n");
	}

	/**
	 * @param string $source = ''
	 *
	 * @return string[]
	 */
	public static function DecodeHeaders ($source = '') {
		$out = array();
		$headers = explode("\n", str_replace("\r", '', $source));
		$buffer = null;

		foreach ($headers as $i => &$header) {
			$buffer = strpos($header, ':');

			if ($buffer !== false)
				$out[substr($header, 0, $buffer)] = trim(substr($header, $buffer + 1));
		}

		unset($i, $buffer, $header, $headers);

		return $out;
	}

	/**
	 * @param string $content = ''
	 *
	 * @return string
	 */
	public static function DecodeContent ($content = '') {
		return base64_decode(str_replace("\n", '', str_replace("\r", '', $content)));
	}

	/**
	 * @param string $raw = ''
	 * @param string $kind = ''
	 *
	 * @return string
	 */
	public static function DecodeContentDirect ($raw = '', $kind = '') {
		$raw = str_replace("\n", "\r\n", str_replace("\r\n", "\n", $raw));
		$raw = str_replace(self::TokenBegin($kind), '', $raw, $tb);
		$raw = str_replace(self::TokenEnd($kind), '', $raw, $te);
		$raw = str_replace("\r", '', $raw);

		$content = explode("\n\n", $raw);

		return self::DecodeContent($content[sizeof($content) == 2 ? 1 : 0]);
	}

	/**
	 * @param string $kind = ''
	 *
	 * @return string
	 */
	public static function TokenBegin ($kind = '') {
		return self::TOKEN_DELIMITER . self::TOKEN_BEGIN . ' ' . $kind . self::TOKEN_DELIMITER . "\r\n";
	}

	/**
	 * @param string $kind = ''
	 *
	 * @return string
	 */
	public static function TokenEnd ($kind = '') {
		return self::TOKEN_DELIMITER . self::TOKEN_END . ' ' . $kind . self::TOKEN_DELIMITER . "\r\n";
	}
}

/**
 * Class QuarkPEMDTO
 *
 * @package Quark
 */
class QuarkPEMDTO {
	/**
	 * @var string $_raw
	 */
	private $_raw;

	/**
	 * @var string $_kind
	 */
	private $_kind;

	/**
	 * @var string $_delimiter = QuarkPEMIOProcessor::TOKEN_DELIMITER
	 */
	private $_delimiter = QuarkPEMIOProcessor::TOKEN_DELIMITER;

	/**
	 * @var string[] $_headersInside = []
	 */
	private $_headersInside = array();

	/**
	 * @var string[] $_headersOutside = []
	 */
	private $_headersOutside = array();

	/**
	 * @var string $_content
	 */
	private $_content;

	/**
	 * @param string $raw = null
	 *
	 * @return string
	 */
	public function Raw ($raw = null) {
		if (func_num_args() != 0)
			$this->_raw = $raw;

		return $this->_raw;
	}

	/**
	 * @param string $kind = null
	 *
	 * @return string
	 */
	public function Kind ($kind = null) {
		if (func_num_args() != 0)
			$this->_kind = $kind;

		return $this->_kind;
	}

	/**
	 * @param string $kind = ''
	 *
	 * @return bool
	 */
	public function KindIs ($kind = '') {
		return $kind != '' && preg_match('#' . $kind . '$#is', $this->_kind); // maybe need regex escape
	}

	/**
	 * @param string $kind = QuarkPEMIOProcessor::TOKEN_DELIMITER
	 *
	 * @return string
	 */
	public function Delimiter ($delimiter = QuarkPEMIOProcessor::TOKEN_DELIMITER) {
		if (func_num_args() != 0)
			$this->_delimiter = $delimiter;

		return $this->_delimiter;
	}

	/**
	 * @param string $key = ''
	 * @param string $value = null
	 *
	 * @return string
	 */
	public function HeaderInside ($key = '', $value = null) {
		if (func_num_args() > 1)
			$this->_headersInside[$key] = $value;

		return isset($this->_headeraInside[$key]) ? $this->_headeraInside[$key] : null;
	}

	/**
	 * @param string[] $headers = []
	 *
	 * @return string[]
	 */
	public function &HeadersInside ($headers = []) {
		if (func_num_args() != 0)
			$this->_headersInside = $headers;

		return $this->_headersInside;
	}

	/**
	 * @param string $key = ''
	 * @param string $value = null
	 *
	 * @return string
	 */
	public function HeaderOutside ($key = '', $value = null) {
		if (func_num_args() > 1)
			$this->_headersOutside[$key] = $value;

		return isset($this->_headeraOutside[$key]) ? $this->_headeraOutside[$key] : null;
	}

	/**
	 * @param string[] $headers = []
	 *
	 * @return string[]
	 */
	public function &HeadersOutside ($headers = []) {
		if (func_num_args() != 0)
			$this->_headersOutside = $headers;

		return $this->_headersOutside;
	}

	/**
	 * @param string $content = null
	 *
	 * @return string
	 */
	public function Content ($content = null) {
		if (func_num_args() != 0)
			$this->_content = $content;

		return $this->_content;
	}

	/**
	 * @return string
	 */
	public function PEMEncode () {
		return ltrim(''
			. QuarkPEMIOProcessor::EncodeHeaders($this->_headersOutside)
			. QuarkPEMIOProcessor::TokenBegin($this->_kind)
			. trim(QuarkPEMIOProcessor::EncodeHeaders($this->_headersInside) . "\r\n" . QuarkPEMIOProcessor::EncodeContent($this->_content)) . "\r\n"
			. QuarkPEMIOProcessor::TokenEnd($this->_kind)
		);
	}

	/**
	 * @param string $raw = ''
	 * @param string $kind = ''
	 * @param string[] $headersInside = []
	 * @param string[] $headersOutside = []
	 * @param string $content = ''
	 *
	 * @return QuarkPEMDTO
	 */
	public static function FromPEMRegularExpression ($raw = '', $kind = '', $headersInside = [], $headersOutside = [], $content = '') {
		$out = new self();

		$out->Raw($raw);
		$out->Kind($kind);
		$out->HeadersInside($headersInside);
		$out->HeadersOutside($headersOutside);
		$out->Content($content);

		return $out;
	}
}

/**
 * Class QuarkMathNumber
 *
 * https://github.com/web-token/jwt-util-ecc/blob/master/Math.php
 *
 * @package Quark
 */
class QuarkMathNumber {
	const ENCODING_8BIT = '8bit';

	/**
	 * @var \GMP|resource $_data
	 */
	private $_data;

	/**
	 * @var int $_base = 0
	 */
	private $_base = 0;

	/**
	 * @param \GMP|resource $data
	 * @param int $base = 0
	 */
	private function __construct ($data, $base = 0) {
		$this->_data = $data;
		$this->_base = $base;
	}

	/**
	 * @return string
	 */
	public function __toString () {
		return $this->Stringify();
	}

	/**
	 * @return string
	 */
	public function Stringify () {
		return $this->Serialize($this->_base);
	}

	/**
	 * @param int $base = 10
	 *
	 * @return string
	 */
	public function Serialize ($base = 10) {
		return gmp_strval($this->_data, $base);
	}

	/**
	 * @return bool
	 */
	public function ToBool () {
		return (bool)$this->Serialize();
	}

	/**
	 * @return int
	 */
	public function ToInt () {
		return (int)$this->Serialize();
	}

	/**
	 * @return float
	 */
	public function ToFloat () {
		return (float)$this->Serialize();
	}

	/**
	 * @return \GMP|resource
	 */
	public function &Data () {
		return $this->_data;
	}

	/**
	 * @return int
	 */
	public function Base () {
		return $this->_base;
	}

	/**
	 * @param int $to
	 * @param int $from = null
	 *
	 * @return QuarkMathNumber
	 */
	public function BaseConvert ($to, $from = null) {
		return self::Init($this->Serialize($to), $from === null ? $this->_base : $from);
	}

	/**
	 * @param QuarkMathNumber $with = null
	 *
	 * @return int
	 */
	private function _compare (QuarkMathNumber &$with = null) {
		return $with == null ? null : \gmp_cmp($this->_data, $with->_data);
	}

	/**
	 * @param QuarkMathNumber $with = null
	 *
	 * @return bool
	 */
	public function LessThan (QuarkMathNumber &$with = null) {
		return $this->_compare($with) < 0;
	}

	/**
	 * @param int|float $number = 0
	 *
	 * @return bool
	 */
	public function LessThanPrimitive ($number = 0) {
		$with = self::InitDecimal($number);

		return $this->LessThan($with);
	}

	/**
	 * @param QuarkMathNumber $with = null
	 *
	 * @return bool
	 */
	public function LessThanOrEqual (QuarkMathNumber &$with = null) {
		return $this->LessThan($with) || $this->Equal($with);
	}

	/**
	 * @param int|float $number = 0
	 *
	 * @return bool
	 */
	public function LessThanOrEqualPrimitive ($number = 0) {
		$with = self::InitDecimal($number);

		return $this->LessThanOrEqual($with);
	}

	/**
	 * @param QuarkMathNumber $with = null
	 *
	 * @return bool
	 */
	public function Equal (QuarkMathNumber &$with = null) {
		return $this->_compare($with) === 0;
	}

	/**
	 * @param int|float $number = 0
	 *
	 * @return bool
	 */
	public function EqualPrimitive ($number = 0) {
		$with = self::InitDecimal($number);

		return $this->Equal($with);
	}

	/**
	 * @param QuarkMathNumber $with = null
	 *
	 * @return bool
	 */
	public function GreatThanOrEqual (QuarkMathNumber &$with = null) {
		return $this->GreatThan($with) || $this->Equal($with);
	}

	/**
	 * @param int|float $number = 0
	 *
	 * @return bool
	 */
	public function GreatThanOrEqualPrimitive ($number = 0) {
		$with = self::InitDecimal($number);

		return $this->GreatThanOrEqual($with);
	}

	/**
	 * @param QuarkMathNumber $with = null
	 *
	 * @return bool
	 */
	public function GreatThan (QuarkMathNumber &$with = null) {
		return $this->_compare($with) > 0;
	}

	/**
	 * @param int|float $number = 0
	 *
	 * @return bool
	 */
	public function GreatThanPrimitive ($number = 0) {
		$with = self::InitDecimal($number);

		return $this->GreatThan($with);
	}

	/**
	 * @return bool
	 */
	public function Odd () {
		return !$this->Even();
	}

	/**
	 * @return bool
	 */
	public function Even () {
		return $this->ModuloPrimitive(2)->EqualPrimitive(0);
	}

	/**
	 * @return int
	 */
	public function LengthBytes () {
		return (int)ceil($this->LengthBits() / 8);
	}

	/**
	 * @return int
	 */
	public function LengthBits () {
		$zero = self::Zero();
		$out = 0;
		$check = true;
		$copy = clone $this;

		while ($check) {
			$copy = $copy->BitwiseShiftRight(1);
			if ($copy->Equal($zero)) break;

			$out++;
		}

		return $out;
	}

	/**
	 * @param int $base = null
	 *
	 * @return int
	 */
	public function LengthBase ($base = null) {
		return mb_strlen($this->Serialize($base === null ? $this->_base : $base), self::ENCODING_8BIT);
	}

	/**
	 * @param QuarkMathNumber $number = null
	 *
	 * @return QuarkMathNumber
	 */
	public function Add (QuarkMathNumber $number = null) {
		return $number == null ? null : new self(gmp_add($this->_data, $number->_data), $this->_base);
	}

	/**
	 * @param int|float $number = 0
	 *
	 * @return QuarkMathNumber
	 */
	public function AddPrimitive ($number = 0) {
		return new self(gmp_add($this->_data, $number), $this->_base);
	}

	/**
	 * @param QuarkMathNumber $number = null
	 *
	 * @return QuarkMathNumber
	 */
	public function Subtract (QuarkMathNumber $number = null) {
		return $number == null ? null : new self(gmp_sub($this->_data, $number->_data), $this->_base);
	}

	/**
	 * @param int|float $number = 0
	 *
	 * @return QuarkMathNumber
	 */
	public function SubtractPrimitive ($number = 0) {
		return new self(gmp_sub($this->_data, $number), $this->_base);
	}

	/**
	 * @param QuarkMathNumber $number = null
	 * @param QuarkMathNumber $modulus = null
	 *
	 * @return QuarkMathNumber
	 */
	public function SubtractModular (QuarkMathNumber $number = null, QuarkMathNumber $modulus = null) {
		return $number == null || $modulus == null ? null : $this->Subtract($number)->Modulo($modulus);
	}

	/**
	 * @param QuarkMathNumber $number = null
	 *
	 * @return QuarkMathNumber
	 */
	public function Multiply (QuarkMathNumber $number = null) {
		return $number == null ? null : new self(gmp_mul($this->_data, $number->_data), $this->_base);
	}

	/**
	 * @param int|float $number = 0
	 *
	 * @return QuarkMathNumber
	 */
	public function MultiplyPrimitive ($number = 0) {
		return new self(gmp_mul($this->_data, $number), $this->_base);
	}

	/**
	 * @param QuarkMathNumber $number = null
	 * @param QuarkMathNumber $modulus = null
	 *
	 * @return QuarkMathNumber
	 */
	public function MultiplyModular (QuarkMathNumber $number = null, QuarkMathNumber $modulus = null) {
		return $number == null || $modulus == null ? null : $this->Multiply($number)->Modulo($modulus);
	}

	/**
	 * @param QuarkMathNumber $number = null
	 *
	 * @return QuarkMathNumber
	 */
	public function Divide (QuarkMathNumber $number = null) {
		return $number == null ? null : new self(gmp_div($this->_data, $number->_data), $this->_base);
	}

	/**
	 * @param int|float $number = 0
	 *
	 * @return QuarkMathNumber
	 */
	public function DividePrimitive ($number = 0) {
		return new self(gmp_div($this->_data, $number), $this->_base);
	}

	/**
	 * @param QuarkMathNumber $number = null
	 * @param QuarkMathNumber $modulus = null
	 *
	 * @return QuarkMathNumber
	 */
	public function DivideModular (QuarkMathNumber $number = null, QuarkMathNumber $modulus = null) {
		return $number == null || $modulus == null ? null : $this->Divide($number)->Modulo($modulus);
	}

	/**
	 * @param QuarkMathNumber $number = null
	 *
	 * @return QuarkMathNumber
	 */
	public function Modulo (QuarkMathNumber $number = null) {
		return $number == null ? null : new self(gmp_mod($this->_data, $number->_data), $this->_base);
	}

	/**
	 * @param QuarkMathNumber $number = null
	 *
	 * @return QuarkMathNumber
	 */
	public function ModuloInverse (QuarkMathNumber $number = null) {
		return $number == null ? null : new self(gmp_invert($this->_data, $number->_data), $this->_base);
	}

	/**
	 * @param int|float $number = 0
	 *
	 * @return QuarkMathNumber
	 */
	public function ModuloPrimitive ($number = 0) {
		return new self(gmp_mod($this->_data, $number), $this->_base);
	}

	/**
	 * @param int $exponent = 0
	 *
	 * @return QuarkMathNumber
	 */
	public function Power ($exponent = 0) {
		return new self(gmp_pow($this->_data, $exponent), $this->_base);
	}

	/**
	 * @param QuarkMathNumber $number = null
	 *
	 * @return QuarkMathNumber
	 */
	public function BitwiseAnd (QuarkMathNumber $number = null) {
		return $number == null ? null : new self(gmp_and($this->_data, $number->_data), $this->_base);
	}

	/**
	 * @param int|float $number = 0
	 *
	 * @return QuarkMathNumber
	 */
	public function BitwiseAndPrimitive ($number = 0) {
		return new self(gmp_and($this->_data, $number), $this->_base);
	}

	/**
	 * @param QuarkMathNumber $number = null
	 *
	 * @return QuarkMathNumber
	 */
	public function BitwiseOr (QuarkMathNumber $number = null) {
		return $number == null ? null : new self(gmp_or($this->_data, $number->_data), $this->_base);
	}

	/**
	 * @param int|float $number = 0
	 *
	 * @return QuarkMathNumber
	 */
	public function BitwiseOrPrimitive ($number = 0) {
		return new self(gmp_or($this->_data, $number), $this->_base);
	}

	/**
	 * @param QuarkMathNumber $number = null
	 *
	 * @return QuarkMathNumber
	 */
	public function BitwiseXOr (QuarkMathNumber $number = null) {
		return $number == null ? null : new self(gmp_xor($this->_data, $number->_data), $this->_base);
	}

	/**
	 * @param int|float $number = 0
	 *
	 * @return QuarkMathNumber
	 */
	public function BitwiseXOrPrimitive ($number = 0) {
		return new self(gmp_xor($this->_data, $number), $this->_base);
	}

	/**
	 * @note EXPERIMENTAL
	 *
	 * @param QuarkMathNumber $number = null
	 * @param int $condition = 0
	 *
	 * @return QuarkMathNumber
	 */
	public function BitwiseSwap (QuarkMathNumber &$number = null, $condition = 0) {
		//echo '--- BITSWAP.INIT (size, mask) ---', "\r\n";
		$size = max($this->LengthBase(2), $number->LengthBase(2));
		$mask = self::InitBinary(str_pad('', $size, (string)(1 - $condition), STR_PAD_LEFT));
		//var_dump((string)(1 - $condition));
		/*$taA = Math::bitwiseAnd($sa, $mask);
		$taB = Math::bitwiseAnd($sb, $mask);
		$sa = Math::bitwiseXor(Math::bitwiseXor($sa, $sb), $taB);
		$sb = Math::bitwiseXor(Math::bitwiseXor($sa, $sb), $taA);
		$sa = Math::bitwiseXor(Math::bitwiseXor($sa, $sb), $taB);*/

		$sa = clone $this;
		$sb = clone $number;

		$taA = $sa->BitwiseAnd($mask);
		$taB = $sb->BitwiseAnd($mask);

		$sa = $sa->BitwiseXOr($sb)->BitwiseXOr($taB);
		$sb = $sa->BitwiseXOr($sb)->BitwiseXOr($taA);
		$sa = $sa->BitwiseXOr($sb)->BitwiseXOr($taB);

		$this->_data = $sa->Data();
		$number->_data = $sb->Data();

		print_r($this);
		print_r($number);

		/*$maskedThis = $this->BitwiseAnd($mask);
		$maskedNumber = $number->BitwiseAnd($mask);

		$buffer = $this->BitwiseXOr($number)->BitwiseXOr($maskedNumber);

		$number->_data = $buffer->BitwiseXOr($number)->BitwiseXOr($maskedThis)->Data();
		$this->_data = $buffer->BitwiseXOr($number)->BitwiseXOr($maskedNumber)->Data();*/

		return $this;
	}

	/**
	 * @param int $positions = 0
	 *
	 * @return QuarkMathNumber
	 */
	public function BitwiseShiftRight ($positions = 0) {
		return $this->Divide(self::InitDecimal(2)->Power($positions));
	}

	/**
	 * @param string $data = ''
	 * @param int $base = 0
	 *
	 * @return QuarkMathNumber
	 */
	public static function Init ($data = '', $base = 0) {
		return new self(\gmp_init($data, $base), $base);
	}

	/**
	 * @param int $data = 0
	 *
	 * @return QuarkMathNumber
	 */
	public static function InitBinary ($data = 0) {
		return self::Init($data, 2);
	}

	/**
	 * @param int $data = 0
	 *
	 * @return QuarkMathNumber
	 */
	public static function InitOctal ($data = 0) {
		return self::Init($data, 8);
	}

	/**
	 * @param int $data = 0
	 *
	 * @return QuarkMathNumber
	 */
	public static function InitDecimal ($data = 0) {
		return self::Init($data, 10);
	}

	/**
	 * @param int $data = 0
	 *
	 * @return QuarkMathNumber
	 */
	public static function InitHexadecimal ($data = 0) {
		return self::Init($data, 16);
	}

	/**
	 * @return QuarkMathNumber
	 */
	public static function Zero () {
		return self::Init(0, 10);
	}

	/**
	 * @param int $bytes = 64
	 *
	 * @return QuarkMathNumber
	 */
	public static function Max ($bytes = 64) {
		return self::Init(str_repeat('f', $bytes), 16);
	}
}

/**
 * Class QuarkArchive
 *
 * @package Quark
 */
class QuarkArchive extends QuarkFile implements IQuarkCollectionWithArrayAccess {
	use QuarkCollectionBehaviorWithArrayAccess {
		Exists as private _exists;
	}
	
	/**
	 * @var IQuarkArchive $_archive
	 */
	private $_archive;
	
	/**
	 * @var bool $_unpacked = false
	 */
	private $_unpacked = false;
	
	/**
	 * @var bool $_existsFile = false
	 */
	private $_existsFile = false;

	/**
	 * @param IQuarkArchive $archive
	 * @param string $location = ''
	 */
	public function __construct (IQuarkArchive $archive, $location = '') {
		parent::__construct($location, false);

		$this->_archive = $archive;
	}
	
	/**
	 * @param QuarkArchiveItem[] $items = []
	 *
	 * @return QuarkArchiveItem[]
	 */
	public function Items ($items = []) {
		if (func_num_args() != 0 && QuarkObject::IsArrayOf($items, new QuarkArchiveItem()))
			$this->_collection = $items;
		
		return $this->_collection;
	}
	
	/**
	 * @param QuarkArchiveItem[] $items = []
	 *
	 * @return QuarkArchive
	 */
	public function Pack ($items = []) {
		if (func_num_args() != 0)
			$this->_collection = $items;
		
		$this->Content($this->_archive->Pack($this->_collection));
		$this->_unpacked = true;
		
		return $this;
	}
	
	/**
	 * @param string $location = ''
	 *
	 * @return bool
	 */
	public function PackTo ($location = '') {
		return $this->Pack()->SaveTo($location);
	}
	
	/**
	 * @return QuarkArchive
	 */
	public function Unpack () {
		$this->_existsFile = true;

		if (!$this->_loaded)
			$this->Load();

		$this->_existsFile = false;
		
		$this->_collection = $this->_archive->Unpack($this->_content);
		$this->_unpacked = true;
		
		return $this;
	}
	
	/**
	 * @param string $location = ''
	 *
	 * @return bool
	 */
	public function UnpackTo ($location = '') {
		if (!$this->_unpacked)
			$this->Unpack();
		
		$ok = true;
		
		foreach ($this->_collection as $i => &$item) {
			/**
			 * @var QuarkArchiveItem $item
			 */
			if ($item->name == '') continue;
		
			$ok &= $item->SaveTo($location . '/' . $item->location);
		}
		
		return $ok;
	}

	/**
	 * @param array $query = []
	 * @param array $options = []
	 *
	 * @return bool
	 */
	public function Exists ($query = [], $options = []) {
		return $this->_existsFile ? parent::Exists() : $this->_exists($query, $options);
	}

	/**
	 * @return bool
	 */
	public function FileExists () {
		return parent::Exists();
	}
}

/**
 * Class QuarkArchiveItem
 *
 * @package Quark
 */
class QuarkArchiveItem extends QuarkFile {
	/**
	 * @var int $_next = 0
	 */
	private $_next = 0;
	
	/**
	 * @param string $location = ''
	 * @param string $content = ''
	 * @param QuarkDate $date = null
	 * @param int $size = 0
	 * @param bool $dir = false
	 */
	public function __construct ($location = '', $content = '', $date = null, $size = 0, $dir = false) {
		parent::__construct($location, false);

		$this->Content($content, true, true);
		
		$args = func_num_args();
		if ($args > 2) $this->_dateModified = $date;
		if ($args > 3) $this->size = $size;
		if ($args > 4) $this->isDir = $dir;
	}
	
	/**
	 * @param int $next = 0
	 *
	 * @return int
	 */
	public function Next ($next = 0) {
		if (func_num_args() != 0)
			$this->_next = $next;
		
		return $this->_next;
	}
}

/**
 * Interface IQuarkArchive
 *
 * @package Quark
 */
interface IQuarkArchive {
	/**
	 * @param QuarkArchiveItem[] $items
	 *
	 * @return string
	 */
	public function Pack($items);
	
	/**
	 * @param string $data
	 *
	 * @return QuarkArchiveItem[]
	 */
	public function Unpack($data);
}

/**
 * Interface IQuarkCompressor
 *
 * @package Quark
 */
interface IQuarkCompressor {
	/**
	 * @param string $data
	 *
	 * @return string
	 */
	public function Compress($data);
	
	/**
	 * @param string $data
	 *
	 * @return string
	 */
	public function Decompress($data);
}

/**
 * Class QuarkSQL
 *
 * @package Quark
 */
class QuarkSQL {
	const OPTION_ALIAS = 'option.alias';
	const OPTION_SCHEMA_GENERATE_PRINT = 'option.schema_print';
	const OPTION_QUERY_TEST = 'option.query.test';
	const OPTION_QUERY_DEBUG = 'option.query.debug';
	const OPTION_QUERY_REVIEWER = 'option.query.reviewer';
	const OPTION_RESULT_REVIEWER = 'option.result.reviewer';
	const OPTION_FIELDS = '__sql_fields__';
	const OPTION_JOIN = 'option.join';
	const OPTION_GROUP_BY = 'option.group_by';

	const FIELD_COUNT_ALL = 'COUNT(*)';

	const FLAG_JOIN_MODE = 'join_mode';
	const FLAG_JOIN_MODEL = 'join_model';
	const FLAG_JOIN_TABLE = 'join_table';
	const FLAG_JOIN_CONDITION = 'join_condition';
	const FLAG_JOIN_ALIAS = 'join_alias';
	const FLAG_JOIN_QUERY = 'join_query';
	const FLAG_JOIN_QUERY_OPTIONS = 'join_query_options';

	const JOIN_INNER = 'INNER';
	const JOIN_OUTER = 'OUTER';
	const JOIN_LEFT = 'LEFT';
	const JOIN_RIGHT = 'RIGHT';
	const JOIN_CROSS = 'CROSS';
	const JOIN_FULL = 'FULL';
	const JOIN_DEFAULT = '';

	const NULL = 'NULL';

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
	 * @param string $connection
	 *
	 * @return IQuarkSQLDataProvider
	 */
	private static function _source ($connection) {
		$source = Quark::Component($connection);

		if (!($source instanceof QuarkModelSource)) return null;

		$provider = $source->Connect()->Provider();

		return $provider instanceof IQuarkSQLDataProvider ? $provider : null;
	}

	/**
	 * @param string $connection
	 * @param string $query
	 * @param array $options = []
	 *
	 * @return bool|mixed
	 */
	public static function Command ($connection, $query = '', $options = []) {
		$provider = self::_source($connection);

		return $provider ? $provider->Query($query, $options) : false;
	}

	/**
	 * @param string $connection
	 * @param string $table
	 *
	 * @return QuarkField[]|bool
	 */
	public static function Schema ($connection, $table) {
		$provider = self::_source($connection);

		return $provider ? $provider->Schema($table) : false;
	}

	/**
	 * @param IQuarkModel $model = null
	 * @param string $mode = self::JOIN_DEFAULT
	 * @param array $condition = []
	 * @param string $alias = ''
	 * @param array $query = []
	 * @param array $query_options = []
	 *
	 * @return array
	 */
	public static function Join (IQuarkModel $model = null, $mode = self::JOIN_DEFAULT, $condition = [], $alias = '', $query = [], $query_options = []) {
		$out = array(
			self::FLAG_JOIN_MODEL => $model,
			self::FLAG_JOIN_MODE => $mode,
			self::FLAG_JOIN_CONDITION => $condition,
			self::FLAG_JOIN_ALIAS => $alias
		);

		$args = func_num_args();
		if ($args > 4) $out[self::FLAG_JOIN_QUERY] = $query;
		if ($args > 5) $out[self::FLAG_JOIN_QUERY_OPTIONS] = $query_options;

		return $out;
	}

	/**
	 * @param string $table = ''
	 * @param string $mode = self::JOIN_DEFAULT
	 * @param array $condition = []
	 * @param string $alias = ''
	 * @param array $query = []
	 * @param array $query_options = []
	 *
	 * @return array
	 */
	public static function JoinTable ($table = '', $mode = self::JOIN_DEFAULT, $condition = [], $alias = '', $query = [], $query_options = []) {
		$out = array(
			self::FLAG_JOIN_TABLE => $table,
			self::FLAG_JOIN_MODE => $mode,
			self::FLAG_JOIN_CONDITION => $condition,
			self::FLAG_JOIN_ALIAS => $alias
		);

		$args = func_num_args();
		if ($args > 4) $out[self::FLAG_JOIN_QUERY] = $query;
		if ($args > 5) $out[self::FLAG_JOIN_QUERY_OPTIONS] = $query_options;

		return $out;
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
	 * @param bool $test = false
	 *
	 * @return mixed
	 */
	public function Query ($model, $options, $query, $test = false) {
		$i = 1;
		$query = str_replace(
			self::Collection($model),
			$this->_provider->EscapeCollection(QuarkModel::CollectionName($model, $options)),
			$query,
			$i
		);

		if (!isset($options[self::OPTION_QUERY_TEST]))
			$options[self::OPTION_QUERY_TEST] = false;
		
		if (isset($options[self::OPTION_QUERY_REVIEWER])) {
			$reviewer = $options[self::OPTION_QUERY_REVIEWER];
			$query = is_callable($reviewer) ? $reviewer($query) : $query;
		}
		
		$out = $test || $options[self::OPTION_QUERY_TEST]
			? $query
			: $this->_provider->Query($query, $options);

		if (isset($options[self::OPTION_QUERY_DEBUG]) && $options[self::OPTION_QUERY_DEBUG])
			Quark::Log('[QuarkSQL] Query: "' . $query . '"');

		if (isset($options[self::OPTION_RESULT_REVIEWER])) {
			$reviewer = $options[self::OPTION_RESULT_REVIEWER];
			$out = is_callable($reviewer) ? $reviewer($out) : $out;
		}

		return $out;
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
	 * @param $key = 'id'
	 * @param $value = 0
	 *
	 * @return QuarkKeyValuePair
	 */
	public function Pk (IQuarkModel $model, $key = 'id', $value = 0) {
		return new QuarkKeyValuePair(
			$model instanceof IQuarkModelWithCustomPrimaryKey ? $model->PrimaryKey() : $key,
			$value
		);
	}

	/**
	 * @param string $field
	 *
	 * @return string
	 */
	public function Field ($field) {
		$escape_dot = $this->_provider->EscapeField('.');
		$escape_star= $this->_provider->EscapeField('*');

		return is_string($field)
			? str_replace($escape_star, '*', str_replace('.', $escape_dot, $this->_provider->EscapeField($field)))
			: '';
	}

	/**
	 * @param $type
	 *
	 * @return string
	 */
	public function FieldTypeFromProvider ($type) {
		return $this->_provider->FieldTypeFromProvider($type);
	}

	/**
	 * @param $field
	 *
	 * @return string
	 */
	public function FieldTypeFromModel ($field) {
		return $this->_provider->FieldTypeFromModel($field);
	}

	/**
	 * @param $value
	 *
	 * @return bool|float|int|string
	 */
	public function Value ($value) {
		if ($value === null) return self::NULL;
		// TODO: need refactor
		//if ($value === null) $value = self::NULL;
		// TODO: investigate, seems like QuarkCollection on this step is not used. UPD: used, but it's weird
		if ($value instanceof QuarkCollection) $value = json_encode($value->Extract(), JSON_UNESCAPED_UNICODE);
		// TODO: investigate, maybe need additional handling of nested models
		/*if ($value instanceof IQuarkModel) $value = new QuarkModel($value);
		if ($value instanceof QuarkModel) $value = json_encode($value->Extract());*/
		if (is_array($value)) $value = json_encode($value, JSON_UNESCAPED_UNICODE);
		if (!is_scalar($value)) $value = null;
		if (is_bool($value))
			$value = $value ? 1 : 0;

		$output = $this->_provider->EscapeValue($value);

		return is_string($value) ? '\'' . $output . '\'' : $output;
	}

	/**
	 * @param $condition
	 * @param string $glue = ''
	 * @param string $target = ''
	 *
	 * @return string
	 */
	public function Condition ($condition, $glue = '', $target = '') {
		if (!is_array($condition) || sizeof($condition) == 0) return '';

		$output = array();
		// TODO: maybe need
		//$target = str_replace('.', $this->Field('.'), $target);

		foreach ($condition as $key => &$rule) {
			$field = $this->Field($key);
			$value = $this->Value($rule);

			if (is_array($rule))
				$value = $this->Condition($rule, ' AND ', $field);

			switch ($field) {
				case $this->Field('$eq'): $output[] = $target . ($value === null ? ' IS ' . self::NULL : '=' . $value); break;
				case $this->Field('$lte'): $output[] = $target . '<=' . $value; break;
				case $this->Field('$lt'): $output[] = $target . '<' . $value; break;
				case $this->Field('$gt'): $output[] = $target . '>' . $value; break;
				case $this->Field('$gte'): $output[] = $target . '>=' . $value; break;
				case $this->Field('$ne'): $output[] = $target . ($value === null ? ' IS NOT ' . self::NULL : '<>' . $value); break;

				case $this->Field('$regex'):
					$regEx = new QuarkRegEx($rule);
					$output[] = $target . ' REGEXP ' . ($regEx->HasFlag(QuarkRegEx::PCRE_CASELESS) ? '' : 'BINARY ') . $this->Value($regEx->Expression());
					break;

				case $this->Field('$like'):
					$output[] = $target . ' LIKE ' . $value;
					break;

				case $this->Field('$ilike'):
					$output[] = $target . ' ILIKE ' . $value; // TODO: ILIKE is not supported in MySQL
					break;

				case $this->Field('$and'):
					$value = $this->Condition($rule, ' AND ');
					$output[] = ' (' . $value . ') ';
					break;

				case $this->Field('$or'):
					$value = $this->Condition($rule, ' OR ');
					$output[] = ' (' . $value . ') ';
					break;

				case $this->Field('$nor'):
					$value = $this->Condition($rule, ' NOT OR ');
					$output[] = ' (' . $value . ') ';
					break;

				case $this->Field('$in'):
					// TODO: support native for DBs 'IN' (for the moment - huge differences for different DB providers)
					// TODO: for example https://phpclub.ru/talk/threads/mysql-in-%D0%B8-%D1%81%D0%BE%D1%80%D1%82%D0%B8%D1%80%D0%BE%D0%B2%D0%BA%D0%B0.12493/

					$values = [];

					foreach ($rule as $i => &$val)
						$values[] = $this->Value($val);

					$output[] = $target . ' IN (' . implode(', ', $values) . ') ';
					break;

				case $this->Field('$quark_in'):
					$field_match = isset($rule['$sql_field']);
					$values = array('[,', ',,', ',]', '[]');

					foreach ($values as $i => &$val)
						$values[$i] = $target . ' LIKE ' . ($field_match
							? 'CONCAT(' . $this->Value('%' . $val[0]) . ', ' . $this->Field($rule['$sql_field']) . ', ' . $this->Value($val[1] . '%') . ')'
							: $this->Value('%' . $val[0] . $rule . $val[1] . '%')
						);

					$output[] = ' (' . implode(' OR ', $values) . ') ';
					break;

				case $this->Field('$sql_field'):
					$output[] = $target . '=' . $this->Field($rule);
					break;

				default:
					$output[] = (is_string($key) && !is_array($rule)  ? $field : '') . (is_scalar($rule) ? '=' : ($value == self::NULL ? ' IS ' : '')) . $value;
					break;
			}
		}

		unset($key, $rule);

		return ($glue == '' ? ' WHERE ' : '') . implode($glue == '' ? ' AND ' : $glue, $output);
	}

	/**
	 * @param $options
	 *
	 * @return string
	 */
	private function _cursor ($options) {
		$output = '';

		if (isset($options[self::OPTION_GROUP_BY]) && is_array($options[self::OPTION_GROUP_BY])) {
			$output .= ' GROUP BY ';

			foreach ($options[self::OPTION_GROUP_BY] as $i => &$key)
				$output .= $this->Field($key) . ',';

			unset($i, $key);

			$output = trim($output, ',');
		}

		if (isset($options[QuarkModel::OPTION_SORT]) && is_array($options[QuarkModel::OPTION_SORT])) {
			$output .= ' ORDER BY ';

			foreach ($options[QuarkModel::OPTION_SORT] as $key => &$order) {
				switch ($order) {
					case 1: $sort = 'ASC'; break;
					case -1: $sort = 'DESC'; break;
					default: $sort = ''; break;
				}

				$output .= (strpos($key, '(') !== false ? $key : $this->Field($key)) . ' ' . $sort . ',';
			}

			unset($key, $order);

			$output = trim($output, ',');
		}

		if (isset($options[QuarkModel::OPTION_LIMIT]))
			$output .= ' LIMIT ' . $this->_provider->EscapeValue($options[QuarkModel::OPTION_LIMIT]);

		if (isset($options[QuarkModel::OPTION_SKIP]))
			$output .= ' OFFSET ' . $this->_provider->EscapeValue($options[QuarkModel::OPTION_SKIP]);

		return $output;
	}

	/**
	 * @param string $table = ''
	 * @param array $criteria = []
	 * @param array $options = []
	 *
	 * @return string
	 */
	public function SelectRaw ($table = '', $criteria = [], $options = []) {
		$query_fields = '';

		if (isset($options[self::OPTION_FIELDS])) $query_fields = $options[self::OPTION_FIELDS];
		else {
			$query_fields = (isset($options[self::OPTION_ALIAS]) ? $options[self::OPTION_ALIAS] . '.' : '') . '*';

			if (isset($options[QuarkModel::OPTION_FIELDS]) && is_array($options[QuarkModel::OPTION_FIELDS])) {
				$fields = array();

				foreach ($options[QuarkModel::OPTION_FIELDS] as $k => &$v) {
					switch ($v) {
						case self::FIELD_COUNT_ALL:
							$fields[] = $v;
							break;

						default:
							$fields[] = (is_int($k) ? '' : (strpos($k, '(') !== false ? $k : $this->Field($k)) . ' AS ') . $this->Field($v);
							break;
					}
				}

				unset($i, $field);

				$query_fields = implode(', ', $fields);
			}
		}

		$joins = '';
		if (isset($options[self::OPTION_JOIN]) && is_array($options[self::OPTION_JOIN])) {
			$join_target = null;

			foreach ($options[self::OPTION_JOIN] as $i => &$join) {
				if (!is_array($join) || !isset($join[self::FLAG_JOIN_MODE]) || !isset($join[self::FLAG_JOIN_CONDITION])) continue;

				$join_target = null;
				if (isset($join[self::FLAG_JOIN_MODEL]) && $join[self::FLAG_JOIN_MODEL])
					$join_target = $this->_provider->EscapeCollection(QuarkModel::CollectionName($join[self::FLAG_JOIN_MODEL]));

				if (isset($join[self::FLAG_JOIN_TABLE]))
					$join_target = $this->_provider->EscapeCollection($join[self::FLAG_JOIN_TABLE]);

				if (isset($join[self::FLAG_JOIN_QUERY]) && $join_target != null)
					$join_target = '(' . $this->SelectRaw($join_target, $join[self::FLAG_JOIN_QUERY], isset($join[self::FLAG_JOIN_QUERY_OPTIONS]) ? $join[self::FLAG_JOIN_QUERY_OPTIONS] : array()) . ')';

				if ($join_target == null) continue;

				$joins .= ' ' . $join[self::FLAG_JOIN_MODE] . ' JOIN ' . $join_target . (isset($join[self::FLAG_JOIN_ALIAS]) && $join[self::FLAG_JOIN_ALIAS] != '' ? ' AS ' . $join[self::FLAG_JOIN_ALIAS] : '') . ' ON ' . $this->Condition($join[self::FLAG_JOIN_CONDITION], ' ');
			}

			unset($i, $join, $join_target);
		}

		return 'SELECT ' . $query_fields . ' FROM ' . $table . (isset($options[self::OPTION_ALIAS]) ? ' AS ' . $options[self::OPTION_ALIAS] : '') . $joins . $this->Condition($criteria) . $this->_cursor($options);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param array $options = []
	 *
	 * @return mixed
	 */
	public function Select (IQuarkModel $model, $criteria, $options = []) {
		return $this->Query($model, $options, $this->SelectRaw(self::Collection($model), $criteria, $options));
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

		foreach ($model as $key => &$value) {
			$keys[] = $this->Field($key);
			$values[] = $this->Value($value);
		}

		unset($key, $value);

		return $this->Query(
			$model,
			$options,
			/** @lang text */
			'INSERT INTO ' . self::Collection($model)
			. ' (' . implode(', ', $keys) . ') '
			. 'VALUES (' . implode(', ', $values) . ')'
		);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param array $options = []
	 *
	 * @return mixed
	 */
	public function Update (IQuarkModel $model, $criteria, $options = []) {
		if (isset($options[self::OPTION_FIELDS])) $query_fields = $options[self::OPTION_FIELDS];
		else {
			$fields = array();
			$_fields = isset($options[QuarkModel::OPTION_FIELDS]) && is_array($options[QuarkModel::OPTION_FIELDS])
				? $options[QuarkModel::OPTION_FIELDS]
				: null;

			foreach ($model as $key => &$value)
				if ($_fields === null || in_array($key, $_fields))
					$fields[] = $this->Field($key) . '=' . $this->Value($value);

			$query_fields = implode(', ', $fields);
		}

		return $this->Query(
			$model,
			$options,
			'UPDATE ' . self::Collection($model) . ' SET ' . $query_fields . $this->Condition($criteria) . $this->_cursor($options)
		);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param $options
	 *
	 * @return mixed
	 */
	public function Delete (IQuarkModel $model, $criteria, $options) {
		return $this->Query(
			$model,
			$options,
			/** @lang text */ 'DELETE FROM ' . self::Collection($model) . $this->Condition($criteria) . $this->_cursor($options)
		);
	}

	/**
	 * @param IQuarkModel $model
	 * @param $criteria
	 * @param array $options = []
	 *
	 * @return mixed
	 */
	public function Count (IQuarkModel $model, $criteria, $options = []) {
		return $this->Select($model, $criteria, array_merge($options, array(
			'fields' => array(self::FIELD_COUNT_ALL)
		)));
	}

	/**
	 * @param string $like = ''
	 *
	 * @return string
	 */
	public static function LikeEscape ($like = '') {
		// yes, it's ridiculous, but that's the story...
		$like = str_replace('\\', '\\\\\\\\\\', $like);
		$like = str_replace('"',  '\\\\\\"', $like);
		//$like = str_replace('_',  '\_"', $like);

		return $like;
	}

	/**
	 * @info incorrect behavior someimes
	 *
	 * @param string $like = ''
	 *
	 * @return array
	 */
	public static function RegexUnicode ($like = '') {
		$like = trim(json_encode($like), '"');
		$like = ltrim(str_replace('\\u', "\0" . '\\u', $like)) . "\0";
		$like = str_replace('\\', '\\', $like);

		return array(
			'$regex' => '/.*' . $like . '.*/'
		);
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
	 * @param string $name
	 *
	 * @return string
	 */
	public function EscapeCollection($name);

	/**
	 * @param string $field
	 *
	 * @return string
	 */
	public function EscapeField($field);

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	public function EscapeValue($value);

	/**
	 * @param $type
	 *
	 * @return string
	 */
	public function FieldTypeFromProvider($type);
	
	/**
	 * @param $field
	 *
	 * @return string
	 */
	public function FieldTypeFromModel($field);

	/**
	 * @param string $table
	 *
	 * @return QuarkField[]
	 */
	public function Schema($table);
	
	/**
	 * @param IQuarkModel $model
	 * @param array $options = []
	 *
	 * @return mixed
	 */
	public function GenerateSchema(IQuarkModel $model, $options = []);
}

/**
 * Class QuarkSource
 *
 * @package Quark
 */
class QuarkSource extends QuarkFile {
	const TRIM = '. , ; ? : ( ) { } [ ] + - * / += -= *= /= > < >= <= != == !== === = => -> && || & | << >>';

	/**
	 * @var string[] $_trim = []
	 */
	private $_trim = array();

	/**
	 * @param string $location = ''
	 * @param bool $load = false
	 */
	public function __construct ($location = '', $load = false) {
		parent::__construct($location, $load);
		$this->_trim = explode(' ', self::TRIM);
	}

	/**
	 * @param string[] $trim = []
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
	public function Minimize () {
		$this->_content = self::MinimizeString($this->_content, $this->_trim);

		return $this;
	}

	/**
	 * @param string $source = ''
	 * @param string[] $trim = []
	 *
	 * @return string
	 */
	public static function MinimizeString ($source = '', $trim = []) {
		$trim = func_num_args() > 1 ? $trim : explode(' ', self::TRIM);
		$slash = ':\\\\' . Quark::GuID() . '\\\\';

		$source = str_replace('://', $slash, $source);
		$source = preg_replace('#\/\/(.*)\\n#Uis', '', $source);
		$source = str_replace($slash, '://', $source);
		$source = preg_replace('#\/\*(.*)\*\/#Uis', '', $source);
		$source = str_replace("\r\n", '', $source);
		$source = str_replace("\n", '', $source);
		$source = preg_replace('/\s+/', ' ', $source);
		$source = str_replace('<?php', '<?php ', $source);

		foreach ($trim as $i => &$rule) {
			$source = str_replace(' ' . $rule . ' ', $rule, $source);
			$source = str_replace(' ' . $rule, $rule, $source);
			$source = str_replace($rule . ' ', $rule, $source);
		}

		unset($i, $rule);

		return trim($source);
	}

	/**
	 * @param string[] $exclude = []
	 *
	 * @return string[]
	 */
	public static function TrimChars ($exclude = []) {
		$trim = self::TRIM . ' ';

		foreach ($exclude as $i => &$char)
			$trim = str_replace($char . ' ', '', $trim);

		return explode(' ', trim($trim));
	}
}

Quark::_init();