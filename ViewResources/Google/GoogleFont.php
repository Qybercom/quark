<?php
namespace Quark\ViewResources\Google;

use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkForeignViewResource;
use Quark\IQuarkMultipleViewResource;

use Quark\QuarkDTO;

use Quark\QuarkCSSViewResourceType;

/**
 * Class GoogleFont
 *
 * @package Quark\ViewResources\Google
 */
class GoogleFont implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource, IQuarkMultipleViewResource {
	const FAMILY_OPEN_SANS = 'Open Sans';
	const FAMILY_OPEN_SANS_CONDENSED = 'Open Sans Condensed';
	const FAMILY_ROBOTO = 'Roboto';
	const FAMILY_MATERIAL_ICONS = 'Material Icons';
	const FAMILY_MONTSERRAT = 'Montserrat';
	const FAMILY_PT_SANS = 'PT Sans';
	const FAMILY_LATO = 'Lato';
	const FAMILY_RALEWAY = 'Raleway';
	const FAMILY_OSWALD = 'Oswald';
	const FAMILY_SOURCE_SANS_PRO = 'Source Sans Pro';
	const FAMILY_EXO = 'Exo';
	const FAMILY_EXO_2 = 'Exo 2';
	const FAMILY_UBUNTU = 'Ubuntu';

	const ITALIC = 'i';

	const WEIGHT_100 = 100;
	const WEIGHT_200 = 200;
	const WEIGHT_300 = 300;
	const WEIGHT_400 = 400;
	const WEIGHT_500 = 500;
	const WEIGHT_600 = 600;
	const WEIGHT_700 = 700;
	const WEIGHT_800 = 800;
	const WEIGHT_900 = 900;

	const WEIGHT_THIN_100 = '100';
	const WEIGHT_THIN_100_ITALIC = '100i';
	const WEIGHT_EXTRA_LIGHT_200 = '200';
	const WEIGHT_EXTRA_LIGHT_200_ITALIC = '200i';
	const WEIGHT_LIGHT_300 = '300';
	const WEIGHT_LIGHT_300_ITALIC = '300i';
	const WEIGHT_REGULAR_400 = '400';
	const WEIGHT_REGULAR_400_ITALIC = '400i';
	const WEIGHT_MEDIUM_500 = '500';
	const WEIGHT_MEDIUM_500_ITALIC = '500i';
	const WEIGHT_SEMI_BOLD_600 = '600';
	const WEIGHT_SEMI_BOLD_600_ITALIC = '600i';
	const WEIGHT_BOLD_700 = '700';
	const WEIGHT_BOLD_700_ITALIC = '700i';
	const WEIGHT_EXTRA_BOLD_800 = '800';
	const WEIGHT_EXTRA_BOLD_800_ITALIC = '800i';
	const WEIGHT_BLACK_900 = '900';
	const WEIGHT_BLACK_900_ITALIC = '900i';

	const SUBSET_LATIN = 'latin';
	const SUBSET_LATIN_EXT = 'latin-ext';
	const SUBSET_CYRILLIC = 'cyrillic';
	const SUBSET_CYRILLIC_EXT = 'cyrillic-ext';
	const SUBSET_GREEK = 'greek';
	const SUBSET_GREEK_EXT = 'greek-ext';
	const SUBSET_VIETNAMESE = 'vietnamese';

	/**
	 * @var string $_family = ''
	 */
	private $_family = '';

	/**
	 * @var string[] $_weights = []
	 */
	private $_weights = array();

	/**
	 * @var string[] $_subsets = [self::SUBSET_LATIN, self::SUBSET_CYRILLIC]
	 */
	private $_subsets = array(self::SUBSET_LATIN, self::SUBSET_CYRILLIC);

	/**
	 * @param string $family
	 * @param string[] $weights = []
	 * @param string[] $subsets = [self::SUBSET_LATIN, self::SUBSET_CYRILLIC]
	 */
	public function __construct ($family, $weights = [], $subsets = [self::SUBSET_LATIN, self::SUBSET_CYRILLIC]) {
		$this->_family = $family;
		$this->_weights = $weights;
		$this->_subsets = $subsets;
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
		return str_replace(' ', '+', 'https://fonts.googleapis.com/css?family='
			. $this->_family
			. (sizeof($this->_weights) != 0 ? ':' . implode(',', $this->_weights) : '')
			. (sizeof($this->_subsets) != 0 ? '&amp;subset=' . implode(',', $this->_subsets) : ''));
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}

	/**
	 * @param int $min = self::WEIGHT_100
	 * @param int $max = self::WEIGHT_900
	 * @param bool $italic = true
	 *
	 * @return string[]
	 */
	public static function SizeRange ($min = self::WEIGHT_100, $max = self::WEIGHT_900, $italic = true) {
		$weights = array();

		while ($min <= $max) {
			$weights[] = $min;

			if ($italic)
				$weights[] = $min . self::ITALIC;

			$min += 100;
		}

		return $weights;
	}
}