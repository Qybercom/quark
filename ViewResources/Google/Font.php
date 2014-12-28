<?php
namespace Quark\ViewResources\Google;

use Quark\IQuarkForeignViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;

use Quark\Quark;
use Quark\QuarkCSSViewResourceType;
use Quark\QuarkDTO;

/**
 * Class Font
 *
 * @package Quark\ViewResources\Google
 */
class Font implements IQuarkViewResource, IQuarkForeignViewResource {
	const OPTION_SIZES = 'sizes';
	const OPTION_SUBSETS = 'subsets';

	const N100 = '100';
	const N200 = '200';
	const N300 = '300';
	const N400 = '400';
	const N600 = '600';
	const N700 = '700';
	const N800 = '800';
	const I300 = '300italic';
	const I400 = '400italic';
	const I600 = '600italic';
	const I700 = '700italic';
	const I800 = '800italic';

	const SUBSET_LATIN = 'latin';
	const SUBSET_LATIN_EXT = 'latin-ext';
	const SUBSET_CYRILLIC = 'cyrillic';
	const SUBSET_CYRILLIC_EXT = 'cyrillic-ext';

	private $_family = '';
	private $_sizes = array();
	private $_subsets = array(self::SUBSET_LATIN, self::SUBSET_CYRILLIC);

	/**
	 * @param string $family
	 * @param array $options
	 */
	public function __construct ($family, $options = []) {
		$this->_family = $family;

		$this->_sizes = isset($options[self::OPTION_SIZES]) && is_array($options[self::OPTION_SIZES]) && !Quark::isAssociative($options[self::OPTION_SIZES])
			? $options[self::OPTION_SIZES]
			: $this->_sizes;

		$this->_subsets = isset($options[self::OPTION_SUBSETS]) && is_array($options[self::OPTION_SUBSETS]) && !Quark::isAssociative($options[self::OPTION_SUBSETS])
			? $options[self::OPTION_SUBSETS]
			: $this->_subsets;
	}

	/**
	 * @return IQuarkViewResourceType
	 */
	public function Type () {
		return new QuarkCSSViewResourceType();
	}

	/**
	 * @return string
	 */
	public function Location () {
		return str_replace(' ', '+', '//fonts.googleapis.com/css?family='
			. $this->_family
			. (sizeof($this->_sizes) != 0 ? ':' . implode(',', $this->_sizes) : '')
			. (sizeof($this->_subsets) != 0 ? '&subset=' . implode(',', $this->_subsets) : ''));
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}