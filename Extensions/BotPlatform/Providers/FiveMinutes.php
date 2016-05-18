<?php
namespace Quark\Extensions\BotPlatform\Providers;

use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;

use Quark\Extensions\BotPlatform\IQuarkBotPlatformProvider;

/**
 * Class FiveMinutes
 *
 * @package Quark\Extensions\BotPlatform\Providers
 */
class FiveMinutes implements IQuarkBotPlatformProvider {
	const API_ENDPOINT = 'http://5min.im/';

	const MESSAGE_TEXT = 'text';
	const MESSAGE_IMAGE = 'image';

	/**
	 * @var string $_appId = ''
	 */
	private $_appId = '';

	/**
	 * @var string $_appSecret = ''
	 */
	private $_appSecret = '';

	/**
	 * @param string $appId
	 * @param string $appSecret
	 *
	 * @return mixed
	 */
	public function BotApplication ($appId, $appSecret) {
		$this->_appId = $appId;
		$this->_appSecret = $appSecret;
	}

	/**
	 * @param string $method
	 * @param array $data
	 *
	 * @return QuarkDTO
	 */
	public function BotAPI ($method, $data) {
		$request = QuarkDTO::ForPOST(new QuarkJSONIOProcessor());
		$request->Data($data);

		$response = new QuarkDTO(new QuarkJSONIOProcessor());

		return QuarkHTTPClient::To(self::API_ENDPOINT . $method, $request, $response);
	}

	/**
	 * @param string $channel = ''
	 * @param string $text
	 *
	 * @return bool
	 */
	public function BotSendMessage ($channel, $text) {
		$api = $this->BotAPI('chat/message', array(
			'bot' => $this->_appSecret,
			'room' => $channel,
			'type' => self::MESSAGE_TEXT,
			'payload' => $text
		));

		return isset($api->status) && $api->status == 200;
	}
}