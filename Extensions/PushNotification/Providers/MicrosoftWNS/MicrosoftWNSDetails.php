<?php
namespace Quark\Extensions\PushNotification\Providers\MicrosoftWNS;

use Quark\Extensions\PushNotification\IQuarkPushNotificationDevice;
use Quark\QuarkDTO;
use Quark\QuarkXMLIOProcessor;
use Quark\QuarkXMLNode;

use Quark\Extensions\PushNotification\IQuarkPushNotificationDetails;
use Quark\Extensions\PushNotification\PushNotificationDetails;

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
	 * @return MicrosoftWNSNotificationTemplate[]
	 */
	public function &Visual () {
		return $this->_visual;
	}

	/**
	 * @param MicrosoftWNSNotificationTemplate $template = null
	 *
	 * @return MicrosoftWNSDetails
	 */
	public function VisualItem (MicrosoftWNSNotificationTemplate $template = null) {
		if ($template != null)
			$this->_visual[] = $template;

		return $this;
	}

	/**
	 * @param object|array $payload
	 * @param IQuarkPushNotificationDevice $device = null
	 *
	 * @return mixed
	 */
	public function PushNotificationDetailsData ($payload, IQuarkPushNotificationDevice $device = null) {
		$type = str_replace('wns/', '', $this->_type);

		$root = new QuarkXMLNode($type);
		$data = array('data' => json_encode($payload));

		if ($this->_value !== null)
			$root->Attribute('value', $this->_value);

		if (sizeof($this->_visual) != 0) {
			$visual = array();

			foreach ($this->_visual as $i => &$item)
				$visual[] = $item->Binding();

			$data['visual'] = new QuarkXMLNode('visual', $visual);
		}

		$out = QuarkDTO::ForPOST(new QuarkXMLIOProcessor($root, QuarkXMLIOProcessor::ITEM, false));
		$out->Header(MicrosoftWNS::HEADER_TYPE, $type);
		$out->Data($data);

		return $out;
	}

	/**
	 * @param PushNotificationDetails $details
	 *
	 * @return mixed
	 */
	public function PushNotificationDetailsFromDetails (PushNotificationDetails $details) {
		// TODO: Implement PushNotificationDetailsFromDetails() method.
	}
}