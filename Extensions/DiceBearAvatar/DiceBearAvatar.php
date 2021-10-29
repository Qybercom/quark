<?php
namespace Quark\Extensions\DiceBearAvatar;

use Quark\IQuarkExtension;

use Quark\Quark;
use Quark\QuarkHTTPClient;

/**
 * Class DiceBearAvatar
 *
 * @package Quark\Extensions\DiceBearAvatar
 */
class DiceBearAvatar implements IQuarkExtension {
	const URL_API = 'https://avatars.dicebear.com/api';

	const SPRIES_MALE = 'male';
	const SPRIES_FEMALE = 'female';
	const SPRIES_HUMAN = 'human';
	const SPRIES_IDENTICON = 'identicon';
	const SPRIES_INITIALS = 'initials';
	const SPRIES_BOTTTS = 'bottts';
	const SPRIES_AVATAAARS = 'avataaars';
	const SPRIES_JDENTICON = 'jdenticon';
	const SPRIES_GRIDY = 'gridy';
	const SPRIES_MICAH = 'micah';

	/**
	 * @var DiceBearAvatarConfig $_config
	 */
	private $_config;

	/**
	 * @var string $_sprites = self::SPRIES_JDENTICON
	 */
	private $_sprites = self::SPRIES_JDENTICON;

	/**
	 * @var string $_version = null
	 */
	private $_version = null;

	/**
	 * @param string $config = ''
	 */
	public function __construct ($config = '') {
		$this->_config = Quark::Config()->Extension($config);
	}

	/**
	 * @param string $sprites = self::SPRIES_JDENTICON
	 *
	 * @return string
	 */
	public function Sprites ($sprites = self::SPRIES_JDENTICON) {
		if (func_num_args() != 0)
			$this->_sprites = $sprites;

		return $this->_sprites;
	}

	/**
	 * @param string $version = null
	 *
	 * @return string
	 */
	public function Version ($version = null) {
		if (func_num_args() != 0)
			$this->_version = $version;

		return $this->_version;
	}

	/**
	 * @param string $seed = ''
	 *
	 * @return string
	 */
	public function Generate ($seed = '') {
		$out = $this->_config->URL();

		if ($this->_version != null)
			$out .= '/' . $this->_version;

		return $out . '/' . $this->_sprites . '/' . $seed . '.svg';
	}
}