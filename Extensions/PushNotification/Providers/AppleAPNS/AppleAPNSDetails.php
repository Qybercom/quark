<?php
namespace Quark\Extensions\PushNotification\Providers\AppleAPNS;

use Quark\Extensions\PushNotification\IQuarkPushNotificationDetails;

/**
 * Class AppleAPNSDetails
 *
 * @package Quark\Extensions\PushNotification\Providers\AppleAPNS
 */
class AppleAPNSDetails implements IQuarkPushNotificationDetails {
	const SOUND_DEFAULT = 'default';

	/**
	 * @var string $_alert = ''
	 */
	private $_alert = '';

	/**
	 * @var int $_badge = 1
	 */
	private $_badge = 1;

	/**
	 * @var string $_sound = self::SOUND_DEFAULT
	 */
	private $_sound = self::SOUND_DEFAULT;

	/**
	 * @param string $alert = ''
	 * @param int $badge = 1
	 * @param string $sound = self::SOUND_DEFAULT
	 */
	public function __construct ($alert = '', $badge = 1, $sound = self::SOUND_DEFAULT) {
		$this->Alert($alert);
		$this->Badge($badge);
		$this->Sound($sound);
	}

	/**
	 * @param string $alert = ''
	 *
	 * @return string
	 */
	public function Alert ($alert = '') {
		if (func_num_args() != 0)
			$this->_alert = $alert;

		return $this->_alert;
	}

	/**
	 * @param int $badge = 1
	 *
	 * @return int
	 */
	public function Badge ($badge = 1) {
		if (func_num_args() != 0)
			$this->_badge = $badge;

		return $this->_badge;
	}

	/**
	 * @param string $sound = self::SOUND_DEFAULT
	 *
	 * @return string
	 */
	public function Sound ($sound = self::SOUND_DEFAULT) {
		if (func_num_args() != 0)
			$this->_sound = $sound;

		return $this->_sound;
	}

	/**
	 * @return string
	 */
	public function PNProviderType () {
		return AppleAPNS::TYPE;
	}

	/**
	 * @param object|array $payload
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function PNDetails ($payload, $options) {
		return array(
			'alert' => $this->_alert,
			'badge' => $this->_badge,
			'sound' => $this->_sound
		);
	}
}