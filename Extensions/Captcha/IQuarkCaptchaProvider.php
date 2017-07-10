<?php
namespace Quark\Extensions\Captcha;
use Quark\IQuarkViewResource;

/**
 * Interface IQuarkCaptchaProvider
 *
 * @package Quark\Extensions\Captcha
 */
interface IQuarkCaptchaProvider {
	/**
	 * @param string $appId
	 * @param string $appSecret
	 * @param object $ini
	 *
	 * @return mixed
	 */
	public function CaptchaApplication($appId, $appSecret, $ini);

	/**
	 * @return IQuarkViewResource[]
	 */
	public function CaptchaViewDependencies();

	/**
	 * @return string
	 */
	public function CaptchaViewFragment();

	/**
	 * @param string $data
	 *
	 * @return bool
	 */
	public function CaptchaVerify($data);
}