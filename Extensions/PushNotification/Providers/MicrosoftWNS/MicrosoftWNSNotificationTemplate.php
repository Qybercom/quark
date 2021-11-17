<?php
namespace Quark\Extensions\PushNotification\Providers\MicrosoftWNS;

use Quark\QuarkXMLNode;

/**
 * Class MicrosoftWNSNotificationTemplate
 *
 * @package Quark\Extensions\PushNotification\Providers\MicrosoftWNS
 */
class MicrosoftWNSNotificationTemplate {
	const TOAST_TEXT_02 = 'ToastText02';

	/**
	 * @var string $_name = ''
	 */
	private $_name = '';

	/**
	 * @var string $_fallback = null
	 */
	private $_fallback = null;

	/**
	 * @var QuarkXMLNode[] $_elements = []
	 */
	private $_elements = [];

	/**
	 * @var int $_images = 1
	 */
	private $_images = 1;

	/**
	 * @var int $_texts = 1
	 */
	private $_texts = 1;

	/**
	 * @param string $name
	 * @param string $fallback = null
	 */
	public function __construct ($name, $fallback = null) {
		$this->_name = $name;
		$this->_fallback = $fallback;

		$this->_images = 1;
		$this->_texts = 1;
	}

	/**
	 * @return QuarkXMLNode[]
	 */
	public function Elements () {
		return $this->_elements;
	}

	/**
	 * @return QuarkXMLNode
	 */
	public function Binding () {
		return new QuarkXMLNode('binding', $this->_elements, array(
			'template' => $this->_name,
			'fallback' => $this->_fallback
		));
	}

	/**
	 * @param string $name = ''
	 * @param array $data = []
	 * @param array $attributes = []
	 * @param bool $single = false
	 * @param string $id = null
	 *
	 * @return QuarkXMLNode
	 */
	public function Element ($name = '', $data = [], $attributes = [], $single = false, $id = null) {
		if ($id === null && !isset($attributes['id']))
			$attributes['id'] = $this->_id($name, $id);

		return new QuarkXMLNode($name, $data, $attributes, $single);
	}

	/**
	 * @param $elem
	 * @param $id
	 *
	 * @return mixed
	 */
	public function _id ($elem, $id) {
		$elem = '_' . $elem . 's';

		return $id === null || !is_scalar($id) ? $this->$elem++ : $id;
	}

	/**
	 * @param string $contents
	 * @param string $id = null
	 *
	 * @return MicrosoftWNSNotificationTemplate
	 */
	public function Text ($contents, $id = null) {
		$this->_elements[] = $this->Element('text', $contents, array(), false, $id);

		return $this;
	}

	/**
	 * @param string $src = ''
	 * @param string $alt = ''
	 * @param string $id = null
	 *
	 * @return MicrosoftWNSNotificationTemplate
	 */
	public function Image ($src, $alt = '', $id = null) {
		$this->_elements[] = $this->Element('image', null, array(
			'src' => $src,
			'alt' => $alt
		), true, $id);

		return $this;
	}
}