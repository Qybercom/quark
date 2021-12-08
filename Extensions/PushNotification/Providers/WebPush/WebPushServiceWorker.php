<?php
namespace Quark\Extensions\PushNotification\Providers\WebPush;

use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkViewResourceWithDependencies;

use Quark\QuarkDate;
use Quark\QuarkInlineJSViewResource;
use Quark\QuarkJSViewResourceType;
use Quark\QuarkLocalCoreJSViewResource;
use Quark\QuarkMinimizableViewResourceBehavior;

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
	 * @var bool $_preventDisplay = false
	 */
	private $_preventDisplay = false;

	/**
	 * @param string $keyVAPID = ''
	 * @param string $urlDeviceRegister = ''
	 * @param bool $preventDisplay = false
	 */
	public function __construct ($keyVAPID = '', $urlDeviceRegister = '', $preventDisplay = false) {
		$this->KeyVAPID($keyVAPID);
		$this->URLDeviceRegister($urlDeviceRegister);
		$this->PreventDisplay($preventDisplay);
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
	 * @param bool $preventDisplay = false
	 *
	 * @return bool
	 */
	public function PreventDisplay ($preventDisplay = false) {
		if (func_num_args() != 0)
			$this->_preventDisplay = $preventDisplay;

		return $this->_preventDisplay;
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
					Quark.Notification.PreventDisplay = ' . ($this->_preventDisplay ? 'true' : 'false') . ';
					Quark.Notification.Subscribe(\'' . $this->_keyVAPID . '\', function (subscription) {
						Quark.Request.POSTFormURLEncoded(
							\'' . $this->_urlDeviceRegister . '\',
							{
								device: {
									type: \'' . WebPush::TYPE . '\',
									id: JSON.stringify(subscription)
								}
							}
						);
					});
				});
			'))
		);;
	}
}