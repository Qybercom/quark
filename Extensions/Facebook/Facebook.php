<?php
namespace Quark\Extensions\Facebook;

use Facebook\FacebookRequest;
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
	/**
	 * @var FaceBookConfig $_config
	 */
	private $_config;
	private $_session;

	/**
	 * @param        $method
	 * @param        $url
	 * @param string $type
	 *
	 * @return bool|mixed
	 */
	public function API ($method, $url, $type = 'Facebook\GraphObject') {
		try {
			if ($this->_session == null) return null;

			$request = new FacebookRequest($this->_session, $method, $url);

			return $request->execute()->getGraphObject($type);
		}
		catch (FacebookRequestException $e) {
			Quark::Log('FacebookRequestException: ' . $e->getMessage(), Quark::LOG_WARN);
			return false;
		}
		catch (\Exception $e) {
			Quark::Log('Facebook.Exception: ' . $e->getMessage(), Quark::LOG_WARN);
			return false;
		}
	}

	/**
	 * @param string $name
	 * @param QuarkDTO $request
	 * @param $lifetime
	 *
	 * @return mixed
	 */
	public function Initialize ($name, QuarkDTO $request, $lifetime) {
		$this->_config = Quark::Config()->Extension($name);
		$this->_session = FacebookSession::newAppSession();
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
	 * @param string     $name
	 * @param QuarkModel $model
	 * @param            $criteria
	 *
	 * @return bool
	 */
	public function Login ($name, QuarkModel $model, $criteria) {
		// TODO: Implement Login() method.
	}

	/**
	 * @param string $name
	 * @param QuarkModel $model
	 * @param $criteria
	 *
	 * @return bool
	 */
	public function Login1 ($name, QuarkModel $model, $criteria) {
		try {
		$this->_session = $criteria === null
			? FacebookSession::newAppSession()
			: new FacebookSession($criteria);

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