<?php
namespace Quark\Extensions\LabelCode;

/**
 * Class LabelCode
 *
 * https://ru.wikipedia.org/wiki/European_Article_Number
 * https://ru.wikipedia.org/wiki/Universal_Product_Code
 *
 * @package Quark\Extensions\LabelCode
 */
class LabelCode {
	/**
	 * @var IQuarkLabelCodeProvider $_provider
	 */
	private $_provider;
	
	/**
	 * @var IQuarkLabelCodeRenderer $_renderer
	 */
	private $_renderer;
	
	/**
	 * @param IQuarkLabelCodeProvider $provider = null
	 * @param IQuarkLabelCodeRenderer $renderer = null
	 */
	public function __construct (IQuarkLabelCodeProvider $provider = null, IQuarkLabelCodeRenderer $renderer = null) {
		$this->Provider($provider);
		$this->Renderer($renderer);
	}
	
	/**
	 * @param IQuarkLabelCodeProvider $provider = null
	 *
	 * @return IQuarkLabelCodeProvider
	 */
	public function Provider (IQuarkLabelCodeProvider $provider = null) {
		if (func_num_args() != 0)
			$this->_provider = $provider;
		
		return $this->_provider;
	}
	
	/**
	 * @param IQuarkLabelCodeRenderer $renderer = null
	 *
	 * @return IQuarkLabelCodeRenderer
	 */
	public function Renderer (IQuarkLabelCodeRenderer $renderer = null) {
		if (func_num_args() != 0)
			$this->_renderer = $renderer;
		
		return $this->_renderer;
	}
	
	/**
	 * @param string $data = ''
	 * @param int $scale = 1
	 *
	 * @return string
	 */
	public function Encode ($data = '', $scale = 1) {
		return $this->_renderer->LCRendererRender(
			$this->_provider->LCProviderEncode($data),
			$scale,
			$this->_provider->LCProviderPointWidth(),
			$this->_provider->LCProviderPointHeight()
		);
	}
	
	// TODO: recognition and validation
}