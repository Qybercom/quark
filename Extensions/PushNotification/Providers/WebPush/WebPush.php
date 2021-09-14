<?php
namespace Quark\Extensions\PushNotification\Providers\WebPush;

use Quark\Extensions\PushNotification\Device;
use Quark\Extensions\PushNotification\IQuarkPushNotificationDetails;
use Quark\Extensions\PushNotification\IQuarkPushNotificationProvider;

/**
 * Class WebPush
 *
 * @package Quark\Extensions\PushNotification\Providers\WebPush
 */
class WebPush implements IQuarkPushNotificationProvider {
	const TYPE = 'web';

	const INI_KEY_PUBLIC = 'web.KeyPublic';
	const INI_KEY_PRIVATE = 'web.KeyPrivate';

	/**
	 * @var string $_keuPublic = ''
	 */
	private $_keuPublic = '';

	/**
	 * @var string $_keyPrivate = ''
	 */
	private $_keyPrivate = '';

	/**
	 * @var Device[] $_devices = []
	 */
	private $_devices = array();

	/**
	 * @var IQuarkPushNotificationDetails|WebPushDetails $_details
	 */
	private $_details;

	/**
	 * @return string
	 */
	public function KeyPublic () {
		return $this->_keyPublic;
	}

	/**
	 * @return string
	 */
	public function KeyPrivate () {
		return $this->_keyPrivate;
	}

	/**
	 * @return string
	 */
	public function PNPType () {
		return self::TYPE;
	}

	/**
	 * @param $config
	 */
	public function PNPConfig ($config) {
		if (isset($ini[self::INI_KEY_PUBLIC]))
			$this->_keyPublic = $ini[self::INI_KEY_PUBLIC];

		if (isset($ini[self::INI_KEY_PRIVATE]))
			$this->_keyPrivate = $ini[self::INI_KEY_PRIVATE];
	}

	/**
	 * @param string $key
	 * @param $value
	 *
	 * @return mixed
	 */
	public function PNPOption ($key, $value) {
		switch ($key) {
			case self::INI_KEY_PUBLIC:
				$this->_keyPublic = $value;
				break;

			case self::INI_KEY_PRIVATE:
				$this->_keyPrivate = $value;
				break;

			default: break;
		}
	}

	/**
	 * @param Device $device
	 */
	public function PNPDevice (Device &$device) {
		// TODO: Implement PNPDevice() method.
	}

	/**
	 * @return Device[]
	 */
	public function &PNPDevices () {
		// TODO: Implement PNPDevices() method.
	}

	/**
	 * @param IQuarkPushNotificationDetails $details
	 *
	 * @return mixed
	 */
	public function PNPDetails (IQuarkPushNotificationDetails $details) {
		// TODO: Implement PNPDetails() method.
	}

	/**
	 * @param object|array $payload
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function PNPSend ($payload, $options) {
		// TODO: Implement PNPSend() method.
	}

	/**
	 * @return mixed
	 */
	public function PNPReset () {
		// TODO: Implement PNPReset() method.
	}
}