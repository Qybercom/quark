<?php
namespace Quark\AuthorizationProviders;

use Quark\IQuarkAuthorizableModel;
use Quark\IQuarkAuthorizationProvider;
use Quark\IQuarkModel;
use Quark\IQuarkModelWithDataProvider;

use Quark\Quark;
use Quark\QuarkConfig;
use Quark\QuarkCookie;
use Quark\QuarkGenericModel;
use Quark\QuarkKeyValuePair;
use Quark\QuarkDTO;
use Quark\QuarkModel;
use Quark\QuarkModelSource;
use Quark\QuarkURI;

use Quark\DataProviders\QuarkDNA;

/**
 * Class Session
 *
 * @property string $name
 * @property string $sid
 * @property string $signature
 * @property $user
 *
 * @package Quark\AuthorizationProviders
 */
class Session implements IQuarkAuthorizationProvider, IQuarkModel, IQuarkModelWithDataProvider {
	const COOKIE_NAME = 'PHPSESSID';
	const STORAGE = 'quark.session';

	/**
	 * @var string $_storage
	 */
	private $_storage = '';

	/**
	 * @var string $_cookie = self::COOKIE_NAME
	 */
	private $_cookie = self::COOKIE_NAME;

	/**
	 * @var bool $_init = false
	 */
	private $_init = false;

	/**
	 * @param string $storage = self::STORAGE
	 * @param string $cookie = self::COOKIE_NAME
	 */
	public function __construct ($storage = self::STORAGE, $cookie = self::COOKIE_NAME) {
		$this->_storage = $storage;
		$this->_cookie = $cookie;
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

		if (!$session->Create()) return null;

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

		return $session->Save();
	}

	/**
	 * @return string
	 */
	public function DataProvider () {
		if (!$this->_init) {
			QuarkModelSource::Register($this->_storage, new QuarkDNA(), QuarkURI::FromFile(Quark::Config()->Location(QuarkConfig::RUNTIME) . '/session.qd'));
			$this->_init = true;
		}

		return $this->_storage;
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
			'user' => new QuarkGenericModel()
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