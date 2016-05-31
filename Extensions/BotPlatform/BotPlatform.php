<?php
namespace Quark\Extensions\BotPlatform;

use Quark\IQuarkExtension;

use Quark\Quark;
use Quark\QuarkDTO;

/**
 * Class BotPlatform
 *
 * @package Quark\Extensions\BotPlatform
 */
class BotPlatform implements IQuarkExtension {
	/**
	 * @var BotPlatformConfig $_config
	 */
	private $_config;

	/**
	 * @param string $config
	 * @param string $authorization = ''
	 */
	public function __construct ($config, $authorization = '') {
		$this->_config = Quark::Config()->Extension($config);

		$this->_config->BotPlatformProvider()->BotApplication(
			$this->_config->appId,
			func_num_args() == 2 ? $authorization : $this->_config->appSecret
		);
	}

	/**
	 * @param QuarkDTO $request
	 *
	 * @return BotPlatformMessage
	 */
	public function IncomingMessage (QuarkDTO $request) {
		if (!$this->_config->BotPlatformProvider()->BotIncomingValidation($request)) {
			Quark::Log('[BotPlatform] Attempt of request forgery of ' . get_class($this->_config->BotPlatformProvider()), Quark::LOG_WARN);
			Quark::Trace($request);
			
			return null;
		}

		return $this->_config->BotPlatformProvider()->BotIncomingMessage($request);
	}

	/**
	 * @param BotPlatformMessage $message
	 *
	 * @return bool
	 */
	public function OutgoingMessage (BotPlatformMessage $message) {
		return $this->_config->BotPlatformProvider()->BotOutgoingMessage($message);
	}

	/**
	 * @param BotPlatformMessage $reply
	 * @param int $iterations = 10
	 */
	public function Typing (BotPlatformMessage $reply, $iterations = 1) {
		$reply->Type(BotPlatformMessage::TYPE_TYPING);

		$i = 0;
		while ($i < $iterations) {
			$this->OutgoingMessage($reply);
			usleep(500000);

			$i++;
		}
	}

	/**
	 * @param string $method
	 * @param array $data = []
	 *
	 * @return QuarkDTO
	 */
	public function API ($method, $data = []) {
		return $this->_config->BotPlatformProvider()->BotAPI($method, $data);
	}

	/**
	 * @param string $type
	 *
	 * @return string
	 */
	public function MessageType ($type) {
		return $this->_config->BotPlatformProvider()->BotMessageType($type);
	}
}