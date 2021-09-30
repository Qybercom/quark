<?php
namespace Quark\Extensions\DiceBearAvatar;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;

/**
 * Class DiceBearAvatarConfig
 *
 * @package Quark\Extensions\DiceBearAvatar
 */
class DiceBearAvatarConfig implements IQuarkExtensionConfig {
	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @var string $_url = DiceBearAvatar::URL_API
	 */
	private $_url = DiceBearAvatar::URL_API;

	/**
	 * @param string $url = DiceBearAvatar::URL_API
	 *
	 * @return string
	 */
	public function URL ($url = DiceBearAvatar::URL_API) {
		if (func_num_args() != 0)
			$this->_url = $url;

		return $this->_url;
	}

	/**
	 * @param string $name
	 */
	public function Stacked ($name) {
		$this->_name = $name;
	}

	/**
	 * @return string
	 */
	public function ExtensionName () {
		return $this->_name;
	}

	/**
	 * @param object $ini
	 *
	 * @return mixed
	 */
	public function ExtensionOptions ($ini) {
		if (isset($ini->URL))
			$this->URL($ini->URL);
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		return new DiceBearAvatar($this->_name);
	}
}