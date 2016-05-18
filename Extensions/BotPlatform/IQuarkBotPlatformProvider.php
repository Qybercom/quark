<?php
namespace Quark\Extensions\BotPlatform;

use Quark\QuarkDTO;

/**
 * Interface IQuarkBotPlatformProvider
 *
 * @package Quark\Extensions\BotPlatform
 */
interface IQuarkBotPlatformProvider {
	/**
	 * @param string $appId
	 * @param string $appSecret
	 *
	 * @return mixed
	 */
	public function BotApplication($appId, $appSecret);

	/**
	 * @param string $method
	 * @param array $data
	 *
	 * @return QuarkDTO
	 */
	public function BotAPI($method, $data);

	/**
	 * @param string $channel = ''
	 * @param string $text
	 *
	 * @return bool
	 */
	public function BotSendMessage($channel, $text);
}