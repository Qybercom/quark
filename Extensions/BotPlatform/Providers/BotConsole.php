<?php
namespace Quark\Extensions\BotPlatform\Providers;

use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;

use Quark\Extensions\BotPlatform\IQuarkBotPlatformProvider;
use Quark\Extensions\BotPlatform\IQuarkBotPlatformEvent;

use Quark\Extensions\BotPlatform\BotPlatformActor;
use Quark\Extensions\BotPlatform\Events\BotPlatformEventMessage;
use Quark\Extensions\BotPlatform\Events\BotPlatformEventTyping;

/**
 * Class BotConsole
 *
 * @package Quark\Extensions\BotPlatform\Providers
 */
class BotConsole implements IQuarkBotPlatformProvider {
	//const API_ENDPOINT = 'http://bot-console.io/';
	const API_ENDPOINT = 'http://bot-console.alex025.dev.funwayhq.com/';

	const EVENT_MESSAGE = 'event.message';
	const EVENT_TYPING = 'event.typing';
	const EVENT_ONLINE = 'event.online';
	const EVENT_OFFLINE = 'event.offline';
	const EVENT_CHANNEL_JOIN = 'event.channel.join'; // when bot was added to channel
	const EVENT_CHANNEL_INVITE = 'event.channel.invite'; //when someone was invited
	const EVENT_CHANNEL_SELECT = 'event.channel.select';

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
	public function BotValidation (QuarkDTO $request) {
		return $request->signature == sha1($this->_appSecret);
	}

	/**
	 * @param QuarkDTO $request
	 *
	 * @return mixed
	 */
	public function BotIn (QuarkDTO $request) {
		if ($request->event == self::EVENT_MESSAGE) {
			$event = BotPlatformEventMessage::BotEventIn(
				new BotPlatformActor($request->from->id, $request->from->name),
				$request->channel,
				$request->platform
			);

			$event->Payload($request->payload);
			$event->ID($request->messageId);
			$event->Type($request->type);

			return $event;
		}

		if ($request->event == self::EVENT_TYPING) {
			$event = BotPlatformEventTyping::BotEventIn(
				new BotPlatformActor($request->from->id, $request->from->name),
				$request->channel,
				$request->platform
			);

			$event->Duration(0);
			$event->Sync(true);

			return $event;
		}

		return null;
	}

	/**
	 * @param IQuarkBotPlatformEvent $event
	 *
	 * @return bool
	 */
	public function BotOut (IQuarkBotPlatformEvent $event) {
		if ($event instanceof BotPlatformEventMessage) {
			$api = $this->BotAPI('api/' . $event->BotEventPlatform() . '/out/' . $event->BotEventChannel(), array(
				'event' => self::EVENT_MESSAGE,
				'app' => $this->_appSecret,
				'type' => $event->Type(),
				'payload' => $event->Payload()
			));

			return isset($api->status) && $api->status == 200;
		}

		if ($event instanceof BotPlatformEventTyping) {
			$api = $this->BotAPI('api/' . $event->BotEventPlatform() . '/out/' . $event->BotEventChannel(), array(
				'event' => self::EVENT_TYPING,
				'app' => $this->_appSecret,
				'duration' => $event->Duration(),
				'sync' => $event->Sync()
			), $event->Sync());

			return isset($api->status) && $api->status == 200;
		}

		return false;
	}

	/**
	 * @param string $method
	 * @param array $data
	 * @param bool $sync = true
	 *
	 * @return QuarkDTO
	 */
	public function BotAPI ($method, $data, $sync = true) {
		$request = QuarkDTO::ForPOST(new QuarkJSONIOProcessor());
		$request->Data($data);

		$response = new QuarkDTO(new QuarkJSONIOProcessor());

		return QuarkHTTPClient::To(self::API_ENDPOINT . $method, $request, $response);
	}
}