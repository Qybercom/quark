<?php
namespace Quark\AuthorizationProviders;

use Quark\IQuarkAuthorizableModel;
use Quark\IQuarkAuthorizableModelWithRuntimeFields;
use Quark\IQuarkAuthorizationProvider;
use Quark\IQuarkModel;
use Quark\IQuarkModelWithCustomCollectionName;
use Quark\IQuarkModelWithDataProvider;

use Quark\Quark;
use Quark\QuarkCookie;
use Quark\QuarkGenericModel;
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
	 * @var string $_cookie = self::COOKIE_NAME
	 */
	private $_cookie = self::COOKIE_NAME;

	/**
	 * @var string $_collection = self::COLLECTION
	 */
	private $_collection = self::COLLECTION;

	/**
	 * @var bool $_init = false
	 */
	private $_init = false;

	/**
	 * @param string $storage = self::STORAGE
	 * @param string $cookie = self::COOKIE_NAME
	 * @param string $collection = self::COLLECTION
	 */
	public function __construct ($storage = self::STORAGE, $cookie = self::COOKIE_NAME, $collection = self::COLLECTION) {
		$this->_storage = $storage;
		$this->_cookie = $cookie;
		$this->_collection = $collection;
		$this->_init = func_num_args() != 0;
	}

	/**
	 * @param string $name
	 * @param IQuarkAuthorizableModel $model
	 * @param QuarkDTO $input
	 *
	 * @return QuarkDTO
	 */
	public function Session ($name, IQuarkAuthorizableModel $model, QuarkDTO $input) {
		$cookie = $input->GetCookieByName($this->_cookie);

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

		if ($model instanceof IQuarkAuthorizableModelWithRuntimeFields)
			$record->user->PopulateWith($record->session);

		$output = new QuarkDTO();
		$output->AuthorizationProvider($session);
		$output->Signature($record->signature);
		$output->Data($record->user);

		if ($cookie != null)
			$output->Cookie($cookie);

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
			'user' => $model
		));

		if (!$session->Create()) {
			Quark::Log('[Session] Unable to create session: instance of ' . get_class($model) . ' has validation errors');
			Quark::Trace($session->RawValidationErrors());

			return null;
		}

		$output = new QuarkDTO();
		$output->AuthorizationProvider(new QuarkKeyValuePair($name, $session->sid));
		$output->Signature($session->signature);
		$output->Cookie(new QuarkCookie($this->_cookie, $session->sid, $lifetime));

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
		$output->Cookie(new QuarkCookie($this->_cookie, $id->Value(), -3600));

		return $output;
	}

	/**
	 * @param string $name
	 * @param IQuarkAuthorizableModel $model
	 * @param QuarkKeyValuePair $id
	 *
	 * @return bool
	 */
	public function SessionCommit ($name, IQuarkAuthorizableModel $model, QuarkKeyValuePair $id) {
		/**
		 * @var QuarkModel|Session $session
		 */
		$session = QuarkModel::FindOne($this, array(
			'name' => $name,
			'sid' => $id->Value()
		));

		if ($session == null) return false;

		$session->user->PopulateWith($model);
		$session->session = (object)$session->session;

		if ($model instanceof IQuarkAuthorizableModelWithRuntimeFields) {
			$fields = $model->RuntimeFields();
			$out = $session->user->ExportGeneric($model);

			if (!isset($session->session))
				$session->session = new \stdClass();

			foreach ($fields as $key => $value)
				$session->session->$key = isset($out->$key) ? $out->$key : null;
		}

		return $session->Save();
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
			'user' => new QuarkGenericModel(),
			'session' => new \stdClass()
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
}