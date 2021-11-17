<?php
namespace Quark\Extensions\PushNotification\Providers\WebPush;

use Quark\IQuarkViewModel;
use Quark\IQuarkViewModelWithResources;
use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResource;
use Quark\QuarkJSViewResourceType;

/**
 * Class WebPushServiceWorkerView
 *
 * @property string $keyVAPID
 * @property string $urlDeviceRegistration
 *
 * @package Quark\Extensions\PushNotification\Providers\WebPush
 */
class WebPushServiceWorkerView implements IQuarkViewModel, IQuarkViewModelWithResources {
	/**
	 * @var IQuarkSpecifiedViewResource $_custom
	 */
	private $_custom;

	/**
	 * @param IQuarkSpecifiedViewResource $custom = null
	 */
	public function __construct (IQuarkSpecifiedViewResource $custom = null) {
		$this->Custom($custom);
	}

	/**
	 * @param IQuarkSpecifiedViewResource $custom = null
	 *
	 * @return IQuarkSpecifiedViewResource
	 */
	public function &Custom (IQuarkSpecifiedViewResource $custom = null) {
		if (func_num_args() != 0)
			$this->_custom = $custom;

		return $this->_custom;
	}

	/**
	 * @return string
	 */
	public function View () {
		return __DIR__ . '/WebPushServiceWorkerTemplate.php';
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function ViewResources () {
		$out = array(
			new WebPushServiceWorker($this->keyVAPID, $this->urlDeviceRegistration)
		);

		if ($this->_custom != null && $this->_custom->Type() instanceof QuarkJSViewResourceType)
			$out[] = $this->_custom;

		return $out;
	}
}