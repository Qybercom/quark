<?php
namespace Quark\Extensions\LabelCode;

/**
 * Interface IQuarkLabelCodeRenderer
 *
 * @package Quark\Extensions\LabelCode
 */
interface IQuarkLabelCodeRenderer {
	/**
	 * @param string $data
	 * @param int $scale
	 * @param int $pointWidth
	 * @param int $pointHeight
	 *
	 * @return string
	 */
	public function LCRendererRender($data, $scale, $pointWidth, $pointHeight);
}