<?php
namespace Quark\Extensions\Captcha;

use Quark\IQuarkExtension;
use Quark\IQuarkInlineViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceWithDependencies;

use Quark\Quark;
use Quark\QuarkMinimizableViewResourceBehavior;

/**
 * Class Captcha
 *
 * @package Quark\Extensions\Captcha
 */
class Captcha implements IQuarkExtension, IQuarkViewResource, IQuarkInlineViewResource, IQuarkViewResourceWithDependencies {
	use QuarkMinimizableViewResourceBehavior;

	/**
	 * @var CaptchaConfig $_config
	 */
	private $_config;

	/**
	 * @param string $config
	 */
	public function __construct ($config) {
		$this->_config = Quark::Config()->Extension($config);
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return $this->_config->CaptchaProvider()->CaptchaViewDependencies();
	}

	/**
	 * @param bool $minimize
	 *
	 * @return string
	 */
	public function HTML ($minimize) {}

	/**
	 * @return string
	 */
	public function Widget () {
		return $this->_config->CaptchaProvider()->CaptchaViewFragment();
	}

	/**
	 * @param string $data = ''
	 *
	 * @return bool
	 */
	public function Verify ($data = '') {
		return $this->_config->CaptchaProvider()->CaptchaVerify($data);
	}
}