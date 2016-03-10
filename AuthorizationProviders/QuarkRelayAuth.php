<?php
namespace Quark\AuthorizationProviders;

use Quark\IQuarkAuthorizableModel;
use Quark\IQuarkAuthorizationProvider;

use Quark\Quark;
use Quark\QuarkCertificate;
use Quark\QuarkDTO;
use Quark\QuarkFormIOProcessor;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkKeyValuePair;
use Quark\QuarkURI;

/**
 * Class QuarkRelayAuth
 *
 * @package Quark\AuthorizationProviders
 */
class QuarkRelayAuth implements IQuarkAuthorizationProvider {
	/**
	 * @var string $-source = ''
	 */
	private $_source = '';

	/**
	 * @var string $_appId = ''
	 */
	private $_appId = '';

	/**
	 * @var string $_appSecret = ''
	 */
	private $_appSecret = '';

	/**
	 * @var QuarkCertificate $_certificate
	 */
	private $_certificate;

	/**
	 * QuarkRelayAuth constructor.
	 *
	 * @param string $source = ''
	 * @param string $appId = ''
	 * @param string $appSecret = ''
	 * @param QuarkCertificate $certificate = null
	 */
	public function __construct ($source = '', $appId = '', $appSecret = '', QuarkCertificate $certificate = null) {
		$this->_source = $source;
		$this->_appId = $appId;
		$this->_appSecret = $appSecret;
		$this->_certificate = $certificate;
	}

	/**
	 * @param string $name
	 * @param IQuarkAuthorizableModel $model
	 * @param QuarkDTO $input
	 *
	 * @return QuarkDTO
	 */
	public function Session ($name, IQuarkAuthorizableModel $model, QuarkDTO $input) {
		$auth = $input->AuthorizationProvider();

		if ($auth == null || $auth->Key() != $this->_appId) {
			Quark::Log('[QuarkRelayAuth] Cannot init session. Application ID expected "' . $this->_appId . '" but received "' . $auth->Key() . '".');
			return null;
		}

		$request = QuarkDTO::ForGET(new QuarkFormIOProcessor());
		$request->Signature($input->Signature());

		$query = QuarkURI::BuildQuery($this->_source, array(
			'user' => $auth->Value(),
			'session' => sha1($this->_appId . $auth->Value() . $this->_appSecret)
		), true);

		$response = new QuarkDTO(new QuarkJSONIOProcessor());

		/**
		 * @var QuarkDTO|\StdClass $session
		 */
		$session = QuarkHTTPClient::To($this->_source . $query, $request, $response, $this->_certificate);

		if (!isset($session->status) || $session->status != 200 || !isset($session->user)) {
			Quark::Log('[QuarkRelayAuth] Cannot get session from ' . $this->_source . '. Target endpoint response: ' . print_r($session, true));
			return null;
		}

		$output = new QuarkDTO();
		$output->AuthorizationProvider($auth);
		$output->Signature($input->Signature());
		$output->Data($session->user);

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
}