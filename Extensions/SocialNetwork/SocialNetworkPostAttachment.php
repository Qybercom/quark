<?php
namespace Quark\Extensions\SocialNetwork;

use Quark\QuarkFile;
use Quark\QuarkHTTPClient;

/**
 * Class SocialNetworkPostAttachment
 *
 * @package Quark\Extensions\SocialNetwork
 */
class SocialNetworkPostAttachment {
	const TYPE_URL = 'url';
	const TYPE_IMAGE = 'image';
	const TYPE_VIDEO = 'video';
	const TYPE_STICKER = 'sticker';

	/**
	 * @var string $_type = self::TYPE_URL
	 */
	private $_type = self::TYPE_URL;

	/**
	 * @var string $_content = ''
	 */
	private $_content = '';

	/**
	 * @param string $type = self::TYPE_URL
	 * @param string $content = ''
	 */
	public function __construct ($type = self::TYPE_URL, $content = '') {
		$this->Type($type);
		$this->Content($content);
	}

	/**
	 * @param string $type = self::TYPE_URL
	 *
	 * @return string
	 */
	public function Type ($type = self::TYPE_URL) {
		if (func_num_args() != 0)
			$this->_type = $type;

		return $this->_type;
	}

	/**
	 * @param string $content = ''
	 *
	 * @return string
	 */
	public function Content ($content = '') {
		if (func_num_args() != 0)
			$this->_content = $content;

		return $this->_content;
	}

	/**
	 * @return QuarkFile
	 */
	public function ToFile () {
		return $this->_type == self::TYPE_URL || $this->_type == self::TYPE_IMAGE
			? QuarkHTTPClient::Download($this->_content)
			: null;
	}
}