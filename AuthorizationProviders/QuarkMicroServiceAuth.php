<?php
namespace Quark\AuthorizationProviders;

use Quark\IQuarkAuthorizableModel;
use Quark\IQuarkAuthorizationProvider;

use Quark\Quark;
use Quark\QuarkDTO;
use Quark\QuarkKeyValuePair;

/**
 * Class QuarkMicroServiceAuth
 *
 * @package Quark\AuthorizationProviders
 */
class QuarkMicroServiceAuth implements IQuarkAuthorizationProvider {
	/**
	 * @var string $_appId = ''
	 */
	private $_appId = '';

	/**
	 * @var string $_appSecret = ''
	 */
	private $_appSecret = '';

	/**
	 * @param string $appId = ''
	 * @param string $appSecret = ''
	 */
	public function __construct ($appId = '', $appSecret = '') {
		$this->_appId = $appId;
		$this->_appSecret = $appSecret;
	}

	/**
	 * @param string $name
	 * @param IQuarkAuthorizableModel $model
	 * @param QuarkDTO $input
	 *
	 * @return QuarkDTO
	 */
	public function Session ($name, IQuarkAuthorizableModel $model, QuarkDTO $input) {
		if (!isset($input->appId)) {
			Quark::Log('[QuarkMicroServiceAuth] Cannot init session. Client did not passed application ID.', Quark::LOG_WARN);
			return null;
		}

		if (!isset($input->appToken)) {
			Quark::Log('[QuarkMicroServiceAuth] Cannot init session. Client did not passed application token.', Quark::LOG_WARN);
			return null;
		}

		$output = new QuarkDTO();
		$output->AuthorizationProvider(new QuarkKeyValuePair($input->appId, $input->appToken));
		$output->Data($input->Data());

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
		// TODO: Implement Login() method.
	}

	/**
	 * @param string $name
	 * @param IQuarkAuthorizableModel $model
	 * @param QuarkKeyValuePair $id
	 *
	 * @return QuarkDTO
	 */
	public function Logout ($name, IQuarkAuthorizableModel $model, QuarkKeyValuePair $id) {
		// TODO: Implement Logout() method.
	}

	/**
	 * @param string $name
	 * @param IQuarkAuthorizableModel $model
	 * @param QuarkKeyValuePair $id
	 *
	 * @return bool
	 */
	public function SessionCommit ($name, IQuarkAuthorizableModel $model, QuarkKeyValuePair $id) {
		// TODO: Implement SessionCommit() method.
	}

	/**
	 * @param object $ini
	 *
	 * @return void
	 */
	public function SessionOptions ($ini) {
		if (isset($ini->AppID))
			$this->_appId = $ini->AppID;

		if (isset($ini->AppSecret))
			$this->_appSecret = $ini->AppSecret;
	}
}