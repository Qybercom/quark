<?php
namespace Quark\AuthorizationProviders;

use Quark\IQuarkAuthorizableModel;
use Quark\IQuarkAuthorizationProvider;
use Quark\IQuarkModel;
use Quark\IQuarkModelWithCustomCollectionName;
use Quark\IQuarkModelWithDataProvider;

use Quark\Quark;
use Quark\QuarkCookie;
use Quark\QuarkGenericModel;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkKeyValuePair;
use Quark\QuarkDTO;
use Quark\QuarkModel;

use Quark\DataProviders\QuarkDNA;

/**
 * Class Session
 *
 * @property string $name
 * @property string $sid
 * @property string $signature
 * @property int $lifetime
 * @property QuarkModel|QuarkGenericModel|IQuarkAuthorizableModel $user
 * @property object $session
 *
 * @package Quark\AuthorizationProviders
 */
class Session implements IQuarkAuthorizationProvider, IQuarkModel, IQuarkModelWithDataProvider, IQuarkModelWithCustomCollectionName {
	const COOKIE_NAME = 'PHPSESSID';
	const STORAGE = 'quark.session';
	const COLLECTION = 'Session';

	/**
	 * @var string $_storage = self::STORAGE
	 */
	private $_storage = self::STORAGE;

	/**
	 * @var string $_cookieName = self::COOKIE_NAME
	 */
	private $_cookieName = self::COOKIE_NAME;

	/**
	 * @var string $_cookieDomain = ''
	 */
	private $_cookieDomain = '';

	/**
	 * @var string $_cookieSameSite = QuarkCookie::SAME_SITE_LAX
	 */
	private $_cookieSameSite = QuarkCookie::SAME_SITE_LAX;

	/**
	 * @var bool $_cookieSecure = false
	 */
	private $_cookieSecure = false;

	/**
	 * @var bool $_cookieHTTPOnly = false
	 */
	private $_cookieHTTPOnly = false;

	/**
	 * @var string $_collection = self::COLLECTION
	 */
	private $_collection = self::COLLECTION;

	/**
	 * @var bool $_init = false
	 */
	private $_init = false;

	/**
	 * @var QuarkJSONIOProcessor $_processor
	 */
	private $_processor;

	/**
	 * @param string $storage = self::STORAGE
	 * @param string $cookieName = self::COOKIE_NAME
	 * @param string $collection = self::COLLECTION
	 */
	public function __construct ($storage = self::STORAGE, $cookieName = self::COOKIE_NAME, $collection = self::COLLECTION) {
		$this->_storage = $storage;
		$this->_cookieName = $cookieName;
		$this->_collection = $collection;
		$this->_init = func_num_args() != 0;
		$this->_processor = new QuarkJSONIOProcessor();
	}

	/**
	 * @param string $name
	 * @param IQuarkAuthorizableModel $model
	 * @param QuarkDTO $input
	 *
	 * @return QuarkDTO
	 */
	public function Session ($name, IQuarkAuthorizableModel $model, QuarkDTO $input) {
		$cookie = $input->GetCookieByName($this->_cookieName);

		if ($cookie != null)
			$input->AuthorizationProvider(new QuarkKeyValuePair($name, $cookie->value));

		$session = $input->AuthorizationProvider();

		if ($session == null) return null;

		/**
		 * @var QuarkModel|Session $record
		 */
		$record = QuarkModel::FindOne($this, array(
			'name' => $name,
			'sid' => $session->Value()
		));

		if ($record == null) return null;

		$output = new QuarkDTO();
		$output->AuthorizationProvider($session);
		$output->Signature($record->signature);
		$output->Data($this->_processor->Decode($record->user));

		if ($cookie != null)
			$output->Cookie($this->Cookie($record->sid, $record->lifetime));

		return $output;
	}

	/**
	 * @param string $name
	 * @param IQuarkAuthorizableModel $model
	 * @param $criteria
	 * @param $lifetime
	 *
	 * @return QuarkDTO
	 */
	public function Login ($name, IQuarkAuthorizableModel $model, $criteria, $lifetime) {
		/**
		 * @var QuarkModel|Session $session
		 */
		$session = new QuarkModel($this, array(
			'name' => $name,
			'sid' => Quark::GuID(),
			'signature' => Quark::GuID(),
			'lifetime' => $lifetime,
			'user' => $this->_processor->Encode($model)
		));

		if (!$session->Create()) {
			Quark::Log('[Session] Unable to create session: instance of ' . get_class($model) . ' has validation errors');
			Quark::Trace($session->RawValidationErrors());

			return null;
		}

		$output = new QuarkDTO();
		$output->AuthorizationProvider(new QuarkKeyValuePair($name, $session->sid));
		$output->Signature($session->signature);
		$output->Cookie($this->Cookie($session->sid, $lifetime));

		return $output;
	}

	/**
	 * @param string $name
	 * @param IQuarkAuthorizableModel $model
	 * @param QuarkKeyValuePair $id
	 *
	 * @return QuarkDTO
	 */
	public function Logout ($name, IQuarkAuthorizableModel $model, QuarkKeyValuePair $id) {
		/**
		 * @var QuarkModel|Session $session
		 */
		$session = QuarkModel::FindOne($this, array(
			'name' => $name,
			'sid' => $id->Value()
		));

		if ($session == null || !$session->Remove()) return null;

		$output = new QuarkDTO();
		$output->Cookie($this->Cookie($id->Value(), -3600));

		return $output;
	}

	/**
	 * @param string $name
	 * @param QuarkKeyValuePair $id
	 * @param $data
	 * @param bool $commit
	 *
	 * @return bool
	 */
	public function SessionData ($name, QuarkKeyValuePair $id, $data, $commit) {
		/**
		 * @var QuarkModel|Session $session
		 */
		$session = QuarkModel::FindOne($this, array(
			'name' => $name,
			'sid' => $id->Value()
		));

		if ($session == null) return null;

		if ($commit) {
			$session->session = json_encode($data);

			if (!$session->Save()) return null;
		}

		return $this->_processor->Decode($session->session);
	}

	/**
	 * @param object $ini
	 *
	 * @return void
	 */
	public function SessionOptions ($ini) {
		if (isset($ini->DataProvider))
			$this->_storage = $ini->DataProvider;

		if (isset($ini->Collection))
			$this->_collection = $ini->Collection;

		if (isset($ini->CookieName))
			$this->_cookieName = $ini->CookieName;

		if (isset($ini->CookieDomain))
			$this->_cookieDomain = $ini->CookieDomain;

		if (isset($ini->CookieSameSite))
			$this->_cookieSameSite = $ini->CookieSameSite;

		if (isset($ini->CookieSecure))
			$this->_cookieSecure = $ini->CookieSecure;

		if (isset($ini->CookieHTTPOnly))
			$this->_cookieHTTPOnly = $ini->CookieHTTPOnly;
	}

	/**
	 * @return string
	 */
	public function DataProvider () {
		if (!$this->_init) {
			QuarkDNA::RuntimeStorage($this->_storage, 'session.qd');
			$this->_init = true;
		}

		return $this->_storage;
	}

	/**
	 * @return string
	 */
	public function CollectionName () {
		return $this->_collection;
	}

	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'name' => '',
			'sid' => '',
			'signature' => '',
			'lifetime' => 0,
			'user' => '',
			'session' => ''
		);
	}

	/**
	 * @return mixed
	 */
	public function Rules () {
		return array(
			$this->name != '',
			$this->sid != ''
		);
	}

	/**
	 * @param string $value = ''
	 * @param int $lifetime = 0
	 *
	 * @return QuarkCookie
	 */
	public function Cookie ($value = '', $lifetime = 0) {
		$cookie = new QuarkCookie($this->_cookieName, $value, $lifetime);

		$cookie->domain = $this->_cookieDomain;
		$cookie->SameSite = $this->_cookieSameSite;
		$cookie->Secure = (bool)$this->_cookieSecure;
		$cookie->HttpOnly = (bool)$this->_cookieHTTPOnly;

		return $cookie;
	}
}