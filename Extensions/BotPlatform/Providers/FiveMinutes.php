<?php
namespace Quark\Extensions\BotPlatform\Providers;

use Quark\QuarkDate;
use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;

use Quark\Extensions\BotPlatform\IQuarkBotPlatformProvider;

use Quark\Extensions\BotPlatform\BotPlatformMessage;
use Quark\Extensions\BotPlatform\BotPlatformMember;

/**
 * Class FiveMinutes
 *
 * @package Quark\Extensions\BotPlatform\Providers
 */
class FiveMinutes implements IQuarkBotPlatformProvider {
	const PLATFORM = 'FiveMinutes';
	
	//const API_ENDPOINT = 'http://5min.im/';
	const API_ENDPOINT = 'http://fm.alex025.dev.funwayhq.com/';

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
	 * @param QuarkDTO $request
	 *
	 * @return bool
	 */
	public function BotIncomingValidation (QuarkDTO $request) {
		return $request->signature == sha1($this->_appSecret);
	}

	/**
	 * @param QuarkDTO $request
	 *
	 * @return BotPlatformMessage
	 */
	public function BotIncomingMessage (QuarkDTO $request) {
		if (!isset($request->payload)) return null;

		return new BotPlatformMessage(
			$request->payload,
			$request->msg,
			$request->type,
			new BotPlatformMember($request->from, $request->fromName),
			QuarkDate::GMTOf($request->date),
			$request->room,
			self::PLATFORM
		);
	}

	/**
	 * @param BotPlatformMessage $message
	 *
	 * @return bool
	 */
	public function BotOutgoingMessage (BotPlatformMessage $message) {
		$api = $this->BotAPI('chat/message', array(
			'bot' => $this->_appSecret,
			'room' => $message->Channel(),
			'type' => $message->Type(),
			'payload' => $message->Payload()
		));

		return isset($api->status) && $api->status == 200;
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
	 * @param string $type
	 *
	 * @return string
	 */
	public function BotMessageType ($type) {
		if ($type == BotPlatformMessage::TYPE_TEXT)
			return self::MESSAGE_TEXT;

		if ($type == BotPlatformMessage::TYPE_IMAGE)
			return self::MESSAGE_IMAGE;

		return '';
	}
}