<?php
namespace Quark\ViewResources\Quark;

use Quark\IQuarkInlineViewResource;
use Quark\IQuarkViewResource;

/**
 * Class QuarkMainMeta
 *
 * @package Quark\ViewResources\Quark
 */
class QuarkMainMeta implements IQuarkViewResource, IQuarkInlineViewResource {
	/**
	 * @var string $_description
	 */
	private $_description = '';

	/**
	 * @var string[] $_keywords
	 */
	private $_keywords = array();

	/**
	 * @param string $description
	 * @param array $keywords
	 */
	public function __construct ($description = '', $keywords = []) {
		$this->_description = $description;
		$this->_keywords = $keywords;
	}

	/**
	 * @return string
	 */
	public function HTML () {
		return '<meta name="description" content="' . $this->_description . '" /><meta name="keywords" content="' . implode(', ', $this->_keywords) . '" />';
	}
}