<?php
namespace Quark\Extensions\SMS;

/**
 * Interface IQuarkSMSProvider
 *
 * @package Quark\Extensions\SMS
 */
interface IQuarkSMSProvider {
	/**
	 * @param string $appID
	 * @param string $appSecret
	 * @param string $appName
	 *
	 * @return mixed
	 */
	public function SMSProviderApplication($appID, $appSecret, $appName);

	/**
	 * @param array|object $ini
	 *
	 * @return mixed
	 */
	public function SMSProviderOptions($ini);

	/**
	 * @param string $message
	 * @param string[] $phones
	 *
	 * @return bool
	 */
	public function SMSSend($message, $phones);

	/**
	 * @param string $message
	 * @param string[] $phones
	 *
	 * @return float
	 */
	public function SMSCost($message, $phones);
}