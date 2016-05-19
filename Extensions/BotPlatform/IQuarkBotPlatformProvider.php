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
	public function BotIncomingValidation(QuarkDTO $request);

	/**
	 * @param QuarkDTO $request
	 *
	 * @return BotPlatformMessage
	 */
	public function BotIncomingMessage(QuarkDTO $request);

	/**
	 * @param BotPlatformMessage $message
	 *
	 * @return bool
	 */
	public function BotOutgoingMessage(BotPlatformMessage $message);

	/**
	 * @param string $method
	 * @param array $data
	 *
	 * @return QuarkDTO
	 */
	public function BotAPI($method, $data);

	/**
	 * @param string $type
	 *
	 * @return string
	 */
	public function BotMessageType($type);
}