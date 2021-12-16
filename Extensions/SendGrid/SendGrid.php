<?php
namespace Quark\Extensions\SendGrid;

use Quark\IQuarkExtension;

use Quark\Quark;
use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkKeyValuePair;

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
		$request->Authorization(new QuarkKeyValuePair('Bearer', $this->_config->APIKey()));
		$request->Data($data);

		return QuarkHTTPClient::To(self::URL_API . $url, $request, new QuarkDTO(new QuarkJSONIOProcessor()), null, 10, true, true);
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
		$data = array(
			'template_id' => $template,
			'from' => array(
				'email' => $this->_config->FromAddress()
			),
			'personalizations' => array()
		);

		foreach ($emails as $i => &$email)
			$data['personalizations'][] = array('to' => array(array('email' => $email)));

		if ($this->_config->FromName() != '')
			$data['from']['name'] = $this->_config->FromName();

		$response = $this->API('/mail/send', $data);

		if ($response->StatusCode() != 202) {
			$this->_errors = $response->errors;

			return false;
		}

		return true;
	}
}