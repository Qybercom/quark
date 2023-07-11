<?php
namespace Quark\ViewResources\Quark\QuarkControls\ViewFragments;

use Quark\IQuarkViewFragment;

use Quark\QuarkCollection;

/**
 * Class QuarkViewNavigatorFragment
 *
 * @package Quark\ViewResources\Quark\QuarkControls\ViewFragments
 */
class QuarkViewNavigatorFragment implements IQuarkViewFragment {
	const SYMBOL_BACK_START = '&#171;';
	const SYMBOL_BACK = '&#8249;';
	const SYMBOL_FORWARD = '&#8250;';
	const SYMBOL_FORWARD_END = '&#187;';

	/**
	 * @var QuarkCollection $_collection
	 */
	private $_collection;

	/**
	 * @var callable $_href
	 */
	private $_href;

	/**
	 * @var string $_id = ''
	 */
	private $_id = '';

	/**
	 * @var string $_symbolBackStart = self::SYMBOL_BACK_START
	 */
	private $_symbolBackStart = self::SYMBOL_BACK_START;

	/**
	 * @var string $_symbolBack = self::SYMBOL_BACK
	 */
	private $_symbolBack = self::SYMBOL_BACK;

	/**
	 * @var string $_symbolForward = self::SYMBOL_FORWARD
	 */
	private $_symbolForward = self::SYMBOL_FORWARD;

	/**
	 * @var string $_symbolForwardEnd = self::SYMBOL_FORWARD_END
	 */
	private $_symbolForwardEnd = self::SYMBOL_FORWARD_END;

	/**
	 * @param QuarkCollection $collection = null
	 * @param callable $href = null
	 * @param string $id = ''
	 * @param string $symbolBackStart = self::SYMBOL_BACK_START
	 * @param string $symbolBack = self::SYMBOL_BACK
	 * @param string $symbolForward = self::SYMBOL_FORWARD
	 * @param string $symbolForwardEnd = self::SYMBOL_FORWARD_END
	 */
	public function __construct (QuarkCollection $collection = null, callable $href = null, $id = '', $symbolBackStart = self::SYMBOL_BACK_START, $symbolBack = self::SYMBOL_BACK, $symbolForward = self::SYMBOL_FORWARD, $symbolForwardEnd = self::SYMBOL_FORWARD_END) {
		$this->Collection($collection);
		$this->Href($href);
		$this->ID($id);
		$this->SymbolBackStart($symbolBackStart);
		$this->SymbolBack($symbolBack);
		$this->SymbolForward($symbolForward);
		$this->SymbolForwardEnd($symbolForwardEnd);
	}

	/**
	 * @param QuarkCollection $collection = null
	 *
	 * @return QuarkCollection
	 */
	public function Collection (QuarkCollection $collection = null) {
		if (func_num_args() != 0)
			$this->_collection = $collection;

		return $this->_collection;
	}

	/**
	 * @param callable $href = null
	 *
	 * @return callable
	 */
	public function Href (callable $href = null) {
		if (func_num_args() != 0)
			$this->_href = $href;

		return $this->_href;
	}

	/**
	 * @param string $id = ''
	 *
	 * @return string
	 */
	public function ID ($id = '') {
		if (func_num_args() != 0)
			$this->_id = $id;

		return $this->_id;
	}

	/**
	 * @param string $symbol = self::SYMBOL_BACK_START
	 *
	 * @return string
	 */
	public function SymbolBackStart ($symbol = self::SYMBOL_BACK_START) {
		if (func_num_args() != 0)
			$this->_symbolBackStart = $symbol;

		return $this->_symbolBackStart;
	}

	/**
	 * @param string $symbol = self::SYMBOL_BACK
	 *
	 * @return string
	 */
	public function SymbolBack ($symbol = self::SYMBOL_BACK) {
		if (func_num_args() != 0)
			$this->_symbolBack = $symbol;

		return $this->_symbolBack;
	}

	/**
	 * @param string $symbol = self::SYMBOL_FORWARD
	 *
	 * @return string
	 */
	public function SymbolForward ($symbol = self::SYMBOL_FORWARD) {
		if (func_num_args() != 0)
			$this->_symbolForward = $symbol;

		return $this->_symbolForward;
	}

	/**
	 * @param string $symbol = self::SYMBOL_FORWARD_END
	 *
	 * @return string
	 */
	public function SymbolForwardEnd ($symbol = self::SYMBOL_FORWARD_END) {
		if (func_num_args() != 0)
			$this->_symbolForwardEnd = $symbol;

		return $this->_symbolForwardEnd;
	}

	/**
	 * @return string
	 */
	public function CompileFragment () {
		$page = $this->_collection->Page();
		$pages = $this->_collection->Pages();

		$items = '';
		$i = 1;

		$href = is_callable($this->_href) ? $this->_href : function () { return ''; };

		while ($i <= $pages) {
			if ($i == 1 || $i == $pages || ($i >= $page - 5 && $i <= $page + 5))
				$items .= '<a class="quark-navigator-item ' . ($i == $page ? ' selected' : '') . '" href="' . $href($i) . '">' . $i . '</a>';

			$i++;
		}

		return '
			<div class="quark-navigator" id="' . $this->_id . '">
				<a class="quark-navigator-item back start'  . ($page <= 1 ? ' disabled' : '" href="' . $href(0)) . '">'              . $this->_symbolBackStart . '</a>
				<a class="quark-navigator-item back'        . ($page <= 1 ? ' disabled' : '" href="' . $href($page - 1)) . '">'      . $this->_symbolBack . '</a>
				' . $items . '
				<a class="quark-navigator-item forward'     . ($page >= $pages ? ' disabled' : '" href="' . $href($page + 1)) . '">' . $this->_symbolForward . '</a>
				<a class="quark-navigator-item forward end' . ($page >= $pages ? ' disabled' : '" href="' . $href($pages)) . '">'    . $this->_symbolForwardEnd . '</a>
			</div>
		';
	}
}