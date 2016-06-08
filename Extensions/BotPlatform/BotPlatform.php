<?php
namespace Quark\Extensions\BotPlatform;

use Quark\IQuarkExtension;

use Quark\Quark;
use Quark\QuarkDTO;
use Quark\QuarkKeyValuePair;

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
	 * @var QuarkKeyValuePair[] $_events = []
	 */
	private $_events = array();

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
	 * @param QuarkDTO $request = null
	 *
	 * @return bool
	 */
	public function In (QuarkDTO $request = null) {
		if ($request == null) {
			Quark::Log('[BotPlatform] Given $request is null', Quark::LOG_WARN);

			return false;
		}

		if (!$this->_config->BotPlatformProvider()->BotValidation($request)) {
			Quark::Log('[BotPlatform] Attempt of request forgery of ' . get_class($this->_config->BotPlatformProvider()), Quark::LOG_WARN);
			Quark::Trace($request);

			return false;
		}

		foreach ($this->_events as $event) {
			$e = $event->Key();

			/**
			 * @var IQuarkBotPlatformEventHandler $handler
			 */
			$handler = $event->Value();
			$out = $this->_config->BotPlatformProvider()->BotIn($request);

			if ($out instanceof $e) $handler->BotEvent($this, $out);
		}

		return true;
	}

	/**
	 * @param IQuarkBotPlatformEvent $event = null
	 *
	 * @return bool
	 */
	public function Out (IQuarkBotPlatformEvent $event = null) {
		return $event == null ? false : $this->_config->BotPlatformProvider()->BotOut($event);
	}

	/**
	 * @param IQuarkBotPlatformEvent $event = null
	 * @param IQuarkBotPlatformEvent $reply = null
	 *
	 * @return bool
	 */
	public function OutReply (IQuarkBotPlatformEvent $event = null, IQuarkBotPlatformEvent $reply = null) {
		return $event == null || $reply == null
			? false
			: $this->_config->BotPlatformProvider()->BotOut($event->BotEventReply($reply));
	}

	/**
	 * @param IQuarkBotPlatformEvent $event
	 * @param IQuarkBotPlatformEventHandler $handler
	 *
	 * @return $this
	 */
	public function On (IQuarkBotPlatformEvent $event, IQuarkBotPlatformEventHandler $handler) {
		$this->_events[] = new QuarkKeyValuePair($event, $handler);

		return $this;
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
}