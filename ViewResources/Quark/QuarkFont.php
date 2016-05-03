<?php
namespace Quark\ViewResources\Quark;

use Quark\IQuarkInlineViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;

use Quark\Quark;

/**
 * Class QuarkFont
 *
 * @package Quark\ViewResources\Quark
 */
class QuarkFont implements IQuarkViewResource, IQuarkInlineViewResource {
	const FALLBACK_SERIF = 'serif';
	const FALLBACK_SANS_SERIF = 'sans-serif';
	const FALLBACK_CURSIVE = 'cursive';
	const FALLBACK_FANTASY = 'fantasy';
	const FALLBACK_MONOSPACE = 'monospace';

	/**
	 * @var string $_font = ''
	 */
	private $_font = '';

	/**
	 * @var string $_family = ''
	 */
	private $_family = '';

	/**
	 * @var string $_fallback = self::FALLBACK_SANS_SERIF
	 */
	private $_fallback = self::FALLBACK_SANS_SERIF;

	/**
	 * @param $font
	 * @param $family
	 * @param string $fallback = self::FALLBACK_SANS_SERIF
	 */
	public function __construct ($font, $family, $fallback = self::FALLBACK_SANS_SERIF) {
		$this->_font = $font;
		$this->_family = $family;
		$this->_fallback = $fallback;
	}

	/**
	 * @return IQuarkViewResourceType
	 */
	public function Type () {
		// TODO: Implement Type() method.
	}

	/**
	 * @return string
	 */
	public function Location () {
		// TODO: Implement Location() method.
	}

	/**
	 * @return string
	 */
	public function HTML () {
		return '<style type="text/css">@font-face{src: url(' . Quark::WebLocation($this->_font) . ');font-family: ' . $this->_family . ',' . $this->_fallback . ';}</style>';
	}
}