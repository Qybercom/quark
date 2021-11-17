<?php
namespace Quark\Extensions\PushNotification;

use Quark\IQuarkLinkedModel;
use Quark\IQuarkModel;
use Quark\IQuarkModelWithAfterPopulate;

use Quark\Quark;
use Quark\QuarkDate;
use Quark\QuarkField;
use Quark\QuarkModel;
use Quark\QuarkModelBehavior;

use Quark\Extensions\PushNotification\Providers\AppleAPNS\AppleAPNS;
use Quark\Extensions\PushNotification\Providers\AppleAPNS\AppleAPNSDevice;
use Quark\Extensions\PushNotification\Providers\GoogleFCM\GoogleFCM;
use Quark\Extensions\PushNotification\Providers\GoogleFCM\GoogleFCMDevice;
use Quark\Extensions\PushNotification\Providers\GoogleGCM\GoogleGCM;
use Quark\Extensions\PushNotification\Providers\GoogleGCM\GoogleGCMDevice;
use Quark\Extensions\PushNotification\Providers\OneSignal\OneSignal;
use Quark\Extensions\PushNotification\Providers\OneSignal\OneSignalDevice;
use Quark\Extensions\PushNotification\Providers\MicrosoftWNS\MicrosoftWNS;
use Quark\Extensions\PushNotification\Providers\MicrosoftWNS\MicrosoftWNSDevice;
use Quark\Extensions\PushNotification\Providers\WebPush\WebPush;
use Quark\Extensions\PushNotification\Providers\WebPush\WebPushDevice;

/**
 * Class PushNotificationDevice
 *
 * @package Quark\Extensions\PushNotification
 */
class PushNotificationDevice implements IQuarkModel, IQuarkModelWithAfterPopulate, IQuarkLinkedModel {
	use QuarkModelBehavior;

	/**
	 * @var string $type = ''
	 */
	public $type = '';

	/**
	 * @var string $id = ''
	 */
	public $id = '';

	/**
	 * @var QuarkDate|string $date = ''
	 */
	public $date = '';

	/**
	 * @var bool $deleted = false
	 */
	public $deleted = false;

	/**
	 * @var IQuarkPushNotificationDevice $_device
	 */
	private $_device;

	/**
	 * @param string $type = ''
	 * @param string $id = ''
	 * @param QuarkDate|string $date = ''
	 */
	public function __construct ($type = '', $id = '', $date = '') {
		$this->type = (string)$type;
		$this->id = (string)$id;
		$this->date = QuarkDate::From($date);
	}

	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'id' => '',
			'type' => '',
			'date' => QuarkDate::GMTNow(),
			'deleted' => false
		);
	}

	/**
	 * @return mixed
	 */
	public function Rules () {
		return array(
			QuarkField::is($this->id, QuarkField::TYPE_STRING),
			QuarkField::is($this->type, QuarkField::TYPE_STRING)
		);
	}

	/**
	 * @param $raw
	 *
	 * @return void
	 */
	public function AfterPopulate ($raw) {
		$raw = (object)$raw;

		if (!isset($raw->date) || $raw->date == null)
			$this->date = null;
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Link ($raw) {
		return new QuarkModel(new PushNotificationDevice(), $raw);
	}

	/**
	 * @return mixed
	 */
	public function Unlink () {
		return json_encode($this->Extract());
	}

	/**
	 * @param IQuarkPushNotificationProvider $provider
	 * @param QuarkDate $ageEdge = null
	 *
	 * @return bool
	 */
	public function Valid (IQuarkPushNotificationProvider &$provider, QuarkDate &$ageEdge = null) {
		if ($this->type == '' || $this->id == '') return false;
		if ($this->type != $provider->PushNotificationProviderType()) return false;
		if ($ageEdge != null && ($this->date == null || $this->date->Earlier($ageEdge))) return false;

		return $provider->PushNotificationProviderDevice()->PushNotificationDeviceValidate($this);
	}

	/**
	 * @param IQuarkPushNotificationDevice $device
	 *
	 * @return IQuarkPushNotificationDevice
	 */
	public function ToDevice (IQuarkPushNotificationDevice $device) {
		return $device->PushNotificationDeviceFromDevice($this) ? $device : null;
	}

	/**
	 * @return mixed
	 */
	public function CriteriaSQL () {
		return $this->_device == null ? null : $this->_device->PushNotificationDeviceCriteriaSQL($this);
	}

	/**
	 * @return bool
	 */
	public function UpdateNeed () {
		return $this->_device == null ? null : $this->_device->PushNotificationDeviceUpdateNeed($this);
	}

	/**
	 * @param PushNotificationDevice $target = null
	 *
	 * @return bool
	 */
	public function IsSame (PushNotificationDevice $target = null) {
		return $target != null && $this->type == $target->type && $this->id == $target->id;
	}

	/**
	 * @return bool
	 */
	public function Recognize () {
		// TODO: refactor, need auto-recognizing
		if ($this->type == GoogleFCM::TYPE) $this->_device = new GoogleFCMDevice();
		if ($this->type == GoogleGCM::TYPE) $this->_device = new GoogleGCMDevice();
		if ($this->type == AppleAPNS::TYPE) $this->_device = new AppleAPNSDevice();
		if ($this->type == OneSignal::TYPE) $this->_device = new OneSignalDevice();
		if ($this->type == MicrosoftWNS::TYPE) $this->_device = new MicrosoftWNSDevice();
		if ($this->type == WebPush::TYPE) $this->_device = new WebPushDevice();

		return $this->_device != null && $this->_device->PushNotificationDeviceFromDevice($this);
	}

	/**
	 * @param object|array $device = null
	 * @param string $date = ''
	 *
	 * @return PushNotificationDevice
	 */
	public static function FromObject ($device = null, $date = '') {
		if (is_array($device))
			$device = (object)$device;

		return !is_object($device) ? null : new self(
			isset($device->type) ? $device->type : null,
			isset($device->id) ? $device->id : null,
			$date
		);
	}
}