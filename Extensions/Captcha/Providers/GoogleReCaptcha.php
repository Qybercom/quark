<?php
namespace Quark\Extensions\Captcha\Providers;

use Quark\IQuarkViewResource;

use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkFormIOProcessor;
use Quark\QuarkGenericViewResource;

use Quark\Extensions\Captcha\IQuarkCaptchaProvider;

/**
 * Class GoogleReCaptcha
 *
 * http://oldesign.ru/recaptcha
 * https://itchief.ru/lessons/php/how-to-install-recaptcha-on-website
 * https://xn--d1acnqm.xn--j1amh/%D0%B7%D0%B0%D0%BF%D0%B8%D1%81%D0%B8/%D0%B4%D0%BE%D0%B1%D0%B0%D0%B2%D0%BB%D1%8F%D0%B5%D0%BC-recaptcha-%D0%BE%D1%82-google-%D0%BD%D0%B0-%D1%81%D0%B0%D0%B9%D1%82
 *
 * https://developers.google.com/recaptcha/docs/display
 * https://developers.google.com/recaptcha/docs/verify
 *
 * @package Quark\Extensions\Captcha\Providers
 */
class GoogleReCaptcha implements IQuarkCaptchaProvider {
	/**
	 * @var string $_appId = ''
	 */
	private $_appId = '';

	/**
	 * @var string $_appSecret = ''
	 */
	private $_appSecret = '';

	/**
	 * @param string $appId
	 * @param string $appSecret
	 * @param object $ini
	 *
	 * @return mixed
	 */
	public function CaptchaApplication ($appId, $appSecret, $ini) {
		$this->_appId = $appId;
		$this->_appSecret = $appSecret;
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function CaptchaViewDependencies () {
		$js = QuarkGenericViewResource::ForeignJS('https://www.google.com/recaptcha/api.js');
		$js->Type()->Async(true);
		$js->Type()->Defer(true);

		return array($js);
	}

	/**
	 * @return string
	 */
	public function CaptchaViewFragment () {
		return '<div class="g-recaptcha" data-sitekey="' . $this->_appId . '"></div>';
	}

	/**
	 * @param string $data
	 *
	 * @return bool
	 */
	public function CaptchaVerify ($data) {
		$request = QuarkDTO::ForPOST(new QuarkFormIOProcessor());
		$request->Data(array(
			'secret' => $this->_appSecret,
			'response' => $data
		));

		$response = QuarkHTTPClient::To(
			'https://www.google.com/recaptcha/api/siteverify',
			$request,
			new QuarkDTO(new QuarkJSONIOProcessor())
		);

		return isset($response->success) && $response->success == true;
	}
}