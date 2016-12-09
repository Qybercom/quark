<?php
namespace Quark\Extensions\PushNotification\Providers\MicrosoftWNS;

use Quark\Extensions\PushNotification\IQuarkPushNotificationDetails;
use Quark\QuarkXMLNode;

/**
 * Class MicrosoftWNSDetails
 *
 * @package Quark\Extensions\PushNotification\Providers\MicrosoftWNS
 */
class MicrosoftWNSDetails implements IQuarkPushNotificationDetails {
	const TYPE_TOAST = 'wns/toast';
	const TYPE_TILE = 'wns/tile';
	const TYPE_BADGE = 'wns/badge';

	/**
	 * @var string $_type = self::TYPE_TOAST
	 */
	private $_type = self::TYPE_TOAST;

	/**
	 * @var string $_value = null
	 */
	private $_value = null;

	/**
	 * @var MicrosoftWNSNotificationTemplate[] $_visual = []
	 */
	private $_visual = array();

	/**
	 * @param string $type = self::TYPE_TOAST
	 */
	public function __construct ($type = self::TYPE_TOAST) {
		$this->Type($type);
	}

	/**
	 * @param string $type = self::TYPE_TOAST
	 *
	 * @return string
	 */
	public function Type ($type = self::TYPE_TOAST) {
		if (func_num_args() != 0)
			$this->_type = $type;

		return $this->_type;
	}

	/**
	 * @param string $value = null
	 *
	 * @return string
	 */
	public function Value ($value = null) {
		if (func_num_args() != 0)
			$this->_value = $value;

		return $this->_value;
	}

	/**
	 * @param MicrosoftWNSNotificationTemplate $template = null
	 *
	 * @return MicrosoftWNSDetails
	 */
	public function Visual (MicrosoftWNSNotificationTemplate $template = null) {
		if ($template != null)
			$this->_visual[] = $template;

		return $this;
	}

	/**
	 * @return string
	 */
	public function PNProviderType () {
		return MicrosoftWNS::TYPE;
	}

	/**
	 * @param object|array $payload
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function PNDetails ($payload, $options) {
		if (sizeof($this->_visual) == 0) return null;

		$visual = array();

		foreach ($this->_visual as $item)
			$visual[] = $item->Binding();

		return new QuarkXMLNode('visual', $visual);
	}
}