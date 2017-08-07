<?php
namespace Quark\Extensions\LabelCode\Renderers;

use Quark\Extensions\LabelCode\IQuarkLabelCodeRenderer;
use Quark\Quark;

/**
 * Class LCRendererHTML
 *
 * @package Quark\Extensions\LabelCode\Renderers
 */
class LCRendererHTML implements IQuarkLabelCodeRenderer {
	/**
	 * @param string $data
	 * @param int $scale
	 * @param int $pointWidth
	 * @param int $pointHeight
	 *
	 * @return string
	 */
	public function LCRendererRender ($data, $scale, $pointWidth, $pointHeight) {
		$id = 'qlc-' . Quark::GuID();
		$out = ''
			. '<style type="text/css">'
			. '#' . $id . '.quark-label-code{display:inline-block;}'
			. '#' . $id . '.quark-label-code .point{'
			. 'display:inline-block;'
			. 'width:' . (1 * $scale * $pointWidth) . 'px;'
			. 'height:' . (1 * $scale * $pointHeight) . 'px;'
			. '}'
			. '#' . $id . '.quark-label-code .point.b{background:#000;}'
			. '#' . $id . '.quark-label-code .point.w{background:#fff;}'
			. '</style>'
			. '<div class="quark-label-code" id="' . $id . '">';
		
		$i = 0;
		$length = strlen($data);
		
		while ($i < $length) {
			$out .= $data[$i] == "\n" ? "\r\n" : '<span class="point ' . ($data[$i] == '1' ? 'b' : 'w') . '"></span>';
			
			$i++;
		}
		
		return $out . '</div>';
	}
}