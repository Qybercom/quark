<?php
namespace Quark\Extensions\SocialNetwork;

use Quark\QuarkLanguage;

/**
 * Class SocialNetworkPublishingChannel
 *
 * @package Quark\Extensions\SocialNetwork
 */
class SocialNetworkPublishingChannel {
	/**
	 * @var string $_id = ''
	 */
	private $_id = '';

	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @var string $_description = ''
	 */
	private $_description = '';

	/**
	 * @var string $_url = ''
	 */
	private $_url = '';

	/**
	 * @var string $_logo = ''
	 */
	private $_logo = '';

	/**
	 * @var string $_cover = ''
	 */
	private $_cover = '';

	/**
	 * @var string $_language = QuarkLanguage::ANY
	 */
	private $_language = QuarkLanguage::ANY;

	/**
	 * @param string $id = ''
	 * @param string $name = ''
	 * @param string $url = ''
	 */
	public function __construct ($id = '',  $name = '', $url = '') {
		$this->ID($id);
		$this->Name($name);
		$this->URL($url);
	}

	/**
	 * @param string $id = ''
	 *
	 * @return string
	 */
	public function ID ($id = '') {
		if (func_num_args() != 0)
			$this->_id = $id;

		return $this->_id;
	}

	/**
	 * @param string $name = ''
	 *
	 * @return string
	 */
	public function Name ($name = '') {
		if (func_num_args() != 0)
			$this->_name = $name;

		return $this->_name;
	}

	/**
	 * @param string $description = ''
	 *
	 * @return string
	 */
	public function Description ($description = '') {
		if (func_num_args() != 0)
			$this->_description = $description;

		return $this->_description = $description;
	}

	/**
	 * @param string $url = ''
	 *
	 * @return string
	 */
	public function URL ($url = '') {
		if (func_num_args() != 0)
			$this->_url = $url;

		return $this->_url;
	}

	/**
	 * @param string $logo = ''
	 *
	 * @return string
	 */
	public function Logo ($logo = '') {
		if (func_num_args() != 0)
			$this->_logo = $logo;

		return $this->_logo;
	}

	/**
	 * @param string $cover = ''
	 *
	 * @return string
	 */
	public function Cover ($cover = '') {
		if (func_num_args() != 0)
			$this->_cover = $cover;

		return $this->_cover;
	}

	/**
	 * @param string $language = QuarkLanguage::ANY
	 *
	 * @return string
	 */
	public function Language ($language = QuarkLanguage::ANY) {
		if (func_num_args() != 0)
			$this->_language = $language;

		return $this->_language;
	}
}