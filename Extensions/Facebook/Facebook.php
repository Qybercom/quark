<?php
namespace Quark\Extensions\Facebook;

use Quark\IQuarkAuthorizationProvider;
use Quark\IQuarkExtension;

use Quark\Quark;
use Quark\QuarkDTO;
use Quark\QuarkModel;

use Facebook\FacebookSession;
use Facebook\FacebookRequestException;

/**
 * Class FacebookSession
 *
 * @package Quark\Extensions\Facebook
 */
class Facebook implements IQuarkAuthorizationProvider, IQuarkExtension {
	private $_session;

	/**
	 * @param string $name
	 * @param QuarkDTO $request
	 * @param $lifetime
	 *
	 * @return mixed
	 */
	public function Initialize ($name, QuarkDTO $request, $lifetime) {
		// TODO: Implement Initialize() method.
	}

	/**
	 * @param string $name
	 * @param QuarkDTO $response
	 * @param QuarkModel $user
	 *
	 * @return mixed
	 */
	public function Trail ($name, QuarkDTO $response, QuarkModel $user) {
		// TODO: Implement Trail() method.
	}

	/**
	 * @param string $name
	 * @param QuarkModel $model
	 * @param $criteria
	 *
	 * @return bool
	 */
	public function Login ($name, QuarkModel $model, $criteria) {
		$this->_session = $criteria === null
			? FacebookSession::newAppSession()
			: new FacebookSession($criteria);

		try {
			$this->_session->validate();
		}
		catch (FacebookRequestException $e) {
			Quark::Log('FacebookRequestException: ' . $e->getMessage(), Quark::LOG_WARN);
			return false;
		}
		catch (\Exception $e) {
			Quark::Log('Facebook.Exception: ' . $e->getMessage(), Quark::LOG_WARN);
			return false;
		}

		return true;
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function Logout ($name) {
		// TODO: Implement Logout() method.
	}

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	public function Signature ($name) {
		// TODO: Implement Signature() method.
	}
}