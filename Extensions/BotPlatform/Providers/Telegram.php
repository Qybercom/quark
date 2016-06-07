<?php
namespace Quark\Extensions\BotPlatform\Providers;

use Quark\QuarkDTO;

use Quark\Extensions\BotPlatform\IQuarkBotPlatformEvent;
use Quark\Extensions\BotPlatform\IQuarkBotPlatformProvider;

/**
 * Class Telegram
 *
 * @package Quark\Extensions\BotPlatform\Providers
 */
class Telegram implements IQuarkBotPlatformProvider {
	const PLATFORM = 'Telegram';

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
		$last = sizeof($request->URI()->Route()) - 1;

		return $last == $this->_appSecret || $request->__tlgrm___ == $this->_appSecret;
	}

	/**
	 * @param QuarkDTO $request
	 *
	 * @return IQuarkBotPlatformEvent
	 */
	public function BotIn (QuarkDTO $request) {
		// TODO: Implement BotIn() method.
	}

	/**
	 * @param IQuarkBotPlatformEvent $event
	 *
	 * @return bool
	 */
	public function BotOut (IQuarkBotPlatformEvent $event) {
		// TODO: Implement BotOut() method.
	}

	/**
	 * @param string $method
	 * @param array $data
	 *
	 * @return QuarkDTO
	 */
	public function BotAPI ($method, $data) {
		// TODO: Implement BotAPI() method.
	}
}