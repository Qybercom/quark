<?php
namespace Quark\Extensions\LabelCode;

/**
 * Interface IQuarkLabelCodeProvider
 *
 * @package Quark\Extensions\LabelCode
 */
interface IQuarkLabelCodeProvider {
	/**
	 * @return int
	 */
	public function LCProviderPointWidth();
	
	/**
	 * @return int
	 */
	public function LCProviderPointHeight();
	
	/**
	 * @param string $data
	 *
	 * @return string
	 */
	public function LCProviderEncode($data);
}