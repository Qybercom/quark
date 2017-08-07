<?php
namespace Quark\ViewResources\Quark;

use Quark\IQuarkInlineViewResource;
use Quark\IQuarkViewResource;

use Quark\Quark;
use Quark\QuarkMinimizableViewResourceBehavior;

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

	use QuarkMinimizableViewResourceBehavior;

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
	 * @param bool $minimize
	 *
	 * @return string
	 */
	public function HTML ($minimize) {
		return '<style type="text/css">@font-face{src: url(' . Quark::WebLocation($this->_font) . ');font-family: ' . $this->_family . ',' . $this->_fallback . ';}</style>';
	}
}