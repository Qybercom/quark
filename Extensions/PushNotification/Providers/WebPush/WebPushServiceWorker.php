<?php
namespace Quark\Extensions\PushNotification\Providers\WebPush;

use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkViewResourceWithDependencies;

use Quark\Quark;
use Quark\QuarkDate;
use Quark\QuarkEncryptionKey;
use Quark\QuarkInlineJSViewResource;
use Quark\QuarkJSViewResourceType;
use Quark\QuarkLocalCoreJSViewResource;
use Quark\QuarkMinimizableViewResourceBehavior;
use Quark\QuarkURI;

use Quark\Extensions\PushNotification\PushNotificationConfig;

/**
 * Class WebPushServiceWorker
 *
 * @package Quark\Extensions\PushNotification\Providers\WebPush
 */
class WebPushServiceWorker implements IQuarkViewResource, IQuarkViewResourceWithDependencies {
	use QuarkMinimizableViewResourceBehavior;

	/**
	 * @var string $_keyVAPID = ''
	 */
	private $_keyVAPID = '';

	/**
	 * @var string $_urlDeviceRegister = ''
	 */
	private $_urlDeviceRegister = '';

	/**
	 * @param string $keyVAPID = ''
	 * @param string $urlDeviceRegister = ''
	 */
	public function __construct ($keyVAPID = '', $urlDeviceRegister = '') {
		$this->KeyVAPID($keyVAPID);
		$this->URLDeviceRegister($urlDeviceRegister);
	}

	/**
	 * @param string $key = ''
	 *
	 * @return string
	 */
	public function KeyVAPID ($key = '') {
		if (func_num_args() != 0)
			$this->_keyVAPID = $key;

		return $this->_keyVAPID;
	}

	/**
	 * @param string $url = ''
	 *
	 * @return string
	 */
	public function URLDeviceRegister ($url = '') {
		if (func_num_args() != 0)
			$this->_urlDeviceRegister = $url;

		return $this->_urlDeviceRegister;
	}

	/**
	 * @return IQuarkViewResourceType
	 */
	public function Type () {
		return new QuarkJSViewResourceType();
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return array(
			new QuarkLocalCoreJSViewResource(),
			new QuarkInlineJSViewResource('/*' . QuarkDate::GMTNow()->Format(QuarkDate::FORMAT_ISO) . '*/' . $this->MinimizeString('
				Quark.ServiceWorker.Ready(function (e) {
					if (Quark.ServiceWorker.Event.Ready instanceof Function)
						Quark.ServiceWorker.Event.Ready(e);
					
					Quark.Notification.SubscribeAndRegister(\'' . $this->_keyVAPID . '\', \'' . $this->_urlDeviceRegister . '\');
				});
			'))
		);;
	}
}