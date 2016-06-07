<?php
namespace Quark\Extensions\BotPlatform\Providers;

use Quark\QuarkDTO;

use Quark\Extensions\BotPlatform\IQuarkBotPlatformEvent;
use Quark\Extensions\BotPlatform\IQuarkBotPlatformProvider;

/**
 * Class Slack
 *
 * @package Quark\Extensions\BotPlatform\Providers
 */
class Slack implements IQuarkBotPlatformProvider {
	const PLATFORM = 'Slack';

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
		return $request->token == $this->_appSecret;
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