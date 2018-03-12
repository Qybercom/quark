<?php
namespace Quark\Extensions\SMS\Providers;

use Quark\QuarkArchException;
use Quark\QuarkDTO;
use Quark\QuarkFormIOProcessor;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;

use Quark\Extensions\SMS\IQuarkSMSProvider;

/**
 * Class Twilio
 *
 * @package Quark\Extensions\SMS\Providers
 */
class Twilio implements IQuarkSMSProvider {
	const URL_API = 'https://api.twilio.com/2010-04-01';

	/**
	 * @var string $_appID = ''
	 */
	private $_appID = '';

	/**
	 * @var string $_appSecret = ''
	 */
	private $_appSecret = '';

	/**
	 * @var string $_appName = null
	 */
	private $_appName = null;

	/**
	 * @param string $url = ''
	 * @param array|object $params = []
	 *
	 * @return QuarkDTO
	 *
	 * @throws QuarkArchException
	 */
	public function API ($url = '', $params = []) {
		$request = QuarkDTO::ForPOST(new QuarkFormIOProcessor());

		$request->AuthorizationBasic($this->_appID, $this->_appSecret);
		$request->Data($params);

		$response = QuarkHTTPClient::To(self::URL_API . $url, $request, new QuarkDTO(new QuarkJSONIOProcessor()));

		if (isset($response->status) && isset($response->message))
			throw new QuarkArchException('Twilio API error: ' . $response->status . '(' . $response->code . '):' . $response->message);

		return $response;
	}

	/**
	 * @param string $appID
	 * @param string $appSecret
	 * @param string $appName
	 *
	 * @return mixed
	 */
	public function SMSProviderApplication ($appID, $appSecret, $appName) {
		$this->_appID = $appID;
		$this->_appSecret = $appSecret;
		$this->_appName = $appName;
	}

	/**
	 * @param array|object $ini
	 *
	 * @return mixed
	 */
	public function SMSProviderOptions ($ini) {
		// TODO: Implement SMSProviderOptions() method.
	}

	/**
	 * @param string $message
	 * @param string[] $phones
	 *
	 * @return bool
	 */
	public function SMSSend ($message, $phones) {
		$ok = 0;

		foreach ($phones as $i => &$phone) {
			$query = array(
				'To' => $phone,
				'Body' => $message
			);

			if ($this->_appName !== null)
				$query['From'] = $this->_appName;

			$response = $this->API('/Accounts/' . $this->_appID . '/Messages.json', $query);

			if ($response != null) $ok++;
		}

		return $ok;
	}

	/**
	 * @param string $message
	 * @param string[] $phones
	 *
	 * @return float
	 */
	public function SMSCost ($message, $phones) {
		// TODO: Implement SMSCost() method.
	}
}