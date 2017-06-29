<?php
namespace Quark\ViewResources\Quark;

use Quark\IQuarkInlineViewResource;
use Quark\IQuarkViewResource;

use Quark\QuarkMinimizableViewResourceBehavior;

/**
 * Class QuarkLegacyIESupport
 *
 * @package Quark\ViewResources\Quark
 */
class QuarkLegacyIESupport implements IQuarkViewResource, IQuarkInlineViewResource {
	const VERSION_HTML5SHIV = '3.7.3';
	const VERSION_RESPONDJS = '1.4.2';

	use QuarkMinimizableViewResourceBehavior;

	/**
	 * @var string $_html5shiv = self::VERSION_HTML5SHIV
	 */
	private $_html5shiv = self::VERSION_HTML5SHIV;

	/**
	 * @var string $_respondJs = self::VERSION_RESPONDJS
	 */
	private $_respondJs = self::VERSION_RESPONDJS;

	/**
	 * @param string $html5shiv = self::VERSION_HTML5SHIV
	 * @param string $respondJs = self::VERSION_RESPONDJS
	 */
	public function __construct ($html5shiv = self::VERSION_HTML5SHIV, $respondJs = self::VERSION_RESPONDJS) {
		$this->_html5shiv = $html5shiv;
		$this->_respondJs = $respondJs;
	}

	/**
	 * @param bool $minimize
	 *
	 * @return string
	 */
	public function HTML ($minimize) {
		return
			'<!--[if lt IE 9]>' .
			'<script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/' . $this->_html5shiv . '/html5shiv.min.js"></script>' .
			'<script src="https://cdnjs.cloudflare.com/ajax/libs/respond.js/' . $this->_respondJs . '/respond.min.js"></script>' .
			'<![endif]-->';
	}
}