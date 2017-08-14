<?php
namespace Quark\ViewResources\Quark\QuarkFont;

use Quark\IQuarkInlineViewResource;
use Quark\IQuarkMultipleViewResource;
use Quark\IQuarkViewResource;

use Quark\QuarkObject;
use Quark\QuarkKeyValuePair;
use Quark\QuarkMinimizableViewResourceBehavior;

/**
 * Class QuarkFont
 *
 * @package Quark\ViewResources\Quark\QuarkFont
 */
class QuarkFont implements IQuarkViewResource, IQuarkInlineViewResource, IQuarkMultipleViewResource {
	use QuarkMinimizableViewResourceBehavior;

	/**
	 * @var QuarkFontItem[] $_fonts = []
	 */
	private $_fonts = array();

	/**
	 * @param bool $minimize
	 *
	 * @return string
	 */
	public function HTML ($minimize) {
		$fonts = '';

		foreach ($this->_fonts as $i => &$font)
			$fonts .= $font->FontFace();

		return '<style type="text/css">' . $fonts . '</style>';
	}

	/**
	 * @param QuarkFontItem[] $fonts = []
	 *
	 * @return QuarkFontItem[]
	 */
	public function Fonts ($fonts = []) {
		if (QuarkObject::IsArrayOf($fonts, new QuarkFontItem()))
			$this->_fonts = $fonts;

		return $this->_fonts;
	}

	/**
	 * @param string $family = ''
	 * @param QuarkFontSource|string $source = ''
	 * @param string[] $local = []
	 *
	 * @return QuarkFont
	 */
	public static function FromSource ($family = '', $source = '', $local = []) {
		$font = new self();

		if (is_array($source)) {
			$sources = QuarkObject::IsArrayOf($source, new QuarkFontSource());

			foreach ($source as $i => &$item)
				$font->_fonts[] = $item instanceof QuarkFontItem
					? $item
					: new QuarkFontItem(
						$family,
						$sources ? $item : new QuarkFontSource($item instanceof QuarkKeyValuePair ? $item->Value() : $item),
						$local,
						QuarkFontItem::STYLE_DEFAULT,
						$item instanceof QuarkKeyValuePair ? $item->Key() : QuarkFontItem::WEIGHT_DEFAULT
					);
		}
		else $font->_fonts[] = new QuarkFontItem($family, $source instanceof QuarkFontSource ? $source : new QuarkFontSource($source), $local);

		return $font;
	}
}