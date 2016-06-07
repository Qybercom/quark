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
	 * @param QuarkDTO $request
	 *
	 * @return bool
	 */
	public function BotValidation(QuarkDTO $request);

	/**
	 * @param QuarkDTO $request
	 *
	 * @return IQuarkBotPlatformEvent
	 */
	public function BotIn(QuarkDTO $request);

	/**
	 * @param IQuarkBotPlatformEvent $event
	 *
	 * @return bool
	 */
	public function BotOut(IQuarkBotPlatformEvent $event);

	/**
	 * @param string $method
	 * @param array $data
	 *
	 * @return QuarkDTO
	 */
	public function BotAPI($method, $data);
}