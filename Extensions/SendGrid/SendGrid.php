<?php
namespace Quark\Extensions\SendGrid;

use Quark\IQuarkExtension;

use Quark\Quark;
use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;

/**
 * Class SendGrid
 *
 * @package Quark\SendGrid
 */
class SendGrid implements IQuarkExtension {
	const URL_API = 'https://api.sendgrid.com/v3/';

	/**
	 * @var SendGridConfig $_config
	 */
	private $_config;

	/**
	 * @var array $_errors = []
	 */
	private $_errors = array();

	/**
	 * @param string $config = ''
	 */
	public function __construct ($config = '') {
		$this->_config = Quark::Config()->Extension($config);
	}

	/**
	 * @return SendGridConfig
	 */
	public function &Config () {
		return $this->_config;
	}

	/**
	 * @param string $url = ''
	 * @param $data = []
	 * @param string $method = QuarkDTO::METHOD_POST
	 *
	 * @return bool|QuarkDTO
	 */
	public function API ($url = '', $data = [], $method = QuarkDTO::METHOD_POST) {
		$request = QuarkDTO::ForRequest($method, new QuarkJSONIOProcessor());

		return QuarkHTTPClient::To(self::URL_API . $url, $request, new QuarkDTO(new QuarkJSONIOProcessor()));
	}

	/**
	 * @return array
	 */
	public function Errors () {
		return $this->_errors;
	}

	/**
	 * @param string $template = ''
	 * @param string[] $emails = []
	 *
	 * @return bool
	 */
	public function MailSend ($template = '', $emails = []) {
		$request = QuarkDTO::ForPOST(new QuarkJSONIOProcessor());
		$request->Data(array(
			'template_id' => $template,
			'emails' => $emails
		));

		$response = $this->API('/marketing/test/send_email', $request, new QuarkDTO(new QuarkJSONIOProcessor()));

		if ($response->StatusCode() == 400) {
			$this->_errors = $response->errors;

			return false;
		}

		return true;
	}
}