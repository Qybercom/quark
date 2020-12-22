<?php
namespace Quark\ViewResources\CodeMirror;

use Quark\IQuarkForeignViewResource;
use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkCSSViewResourceType;
use Quark\QuarkDTO;

/**
 * Class CodeMirrorTheme
 *
 * @package Quark\ViewResources\CodeMirror
 */
class CodeMirrorTheme implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource {
	const NAME_ABCDEF = 'abcdef';
	const NAME_AMBIANCE_MOBILE = 'ambiance-mobile';
	const NAME_AMBIANCE = 'ambiance';
	const NAME_AYU_DARK = 'ayu-dark';
	const NAME_AYU_MIRAGE = 'ayu-mirage';
	const NAME_BASE16_DARK = 'base16-dark';
	const NAME_BASE16_LIGHT = 'base16-light';
	const NAME_BESPIN = 'bespin';
	const NAME_BLACKBOARD = 'blackboard';
	const NAME_COBALT = 'cobalt';
	const NAME_COLORFORTH = 'colorforth';
	const NAME_DARCULA = 'darcula';
	const NAME_DRACULA = 'dracula';
	const NAME_DUOTONEDARK = 'duotone-dark';
	const NAME_DUOTONE_LIGHT = 'duotone-light';
	const NAME_ECLIPSE = 'eclipse';
	const NAME_ELEGANT = 'elegant';
	const NAME_ERLANG_DARK = 'erlang-dark';
	const NAME_GRUVBOX = 'gruvbox-dark';
	const NAME_HOPSCOTCH = 'hopscotch';
	const NAME_ICECODER = 'icecoder';
	const NAME_IDEA = 'idea';
	const NAME_ISOTOPE = 'isotope';
	const NAME_LESSER_DARK = 'lesser-dark';
	const NAME_LIQUIBYTE = 'liquibyte';
	const NAME_LUCARIO = 'lucario';
	const NAME_MATERIAL_DARKER = 'material-darker';
	const NAME_MATERIAL_OCEAN = 'material-ocean';
	const NAME_MATERIAL_PALENIGHT = 'material-palenight';
	const NAME_MATERIAL = 'material';
	const NAME_MBO = 'mbo';
	const NAME_MDN_LIKE = 'mdn-like';
	const NAME_MIDNIGHT = 'midnight';
	const NAME_MONOKAI = 'monokai';
	const NAME_MOXER = 'moxer';
	const NAME_NEAT = 'neat';
	const NAME_NEO = 'neo';
	const NAME_NIGHT = 'night';
	const NAME_NORD = 'nord';
	const NAME_OCEANIC_NEXT = 'oceanic-next';
	const NAME_PANDA_SYNTAX = 'panda-syntax';
	const NAME_PARAISO_DARK = 'paraiso-dark';
	const NAME_PARAISO_LIGHT = 'paraiso-light';
	const NAME_PASTEL_ON_DARK = 'pastel-on-dark';
	const NAME_RAILSCASTS = 'railscasts';
	const NAME_RUBYBLUE = 'rubyblue';
	const NAME_SETI = 'seti';
	const NAME_SHADOWFOX = 'shadowfox';
	const NAME_SOLARIZED = 'solarized';
	const NAME_SSMS = 'ssms';
	const NAME_THE_MATRIX = 'the-matrix';
	const NAME_TOMORROW_NIGHT_BRIGHT = 'tomorrow-night-bright';
	const NAME_TOMORROW_NIGHT_EIGHTIES = 'tomorrow-night-eighties';
	const NAME_TTCN = 'ttcn';
	const NAME_TWILIGHT = 'twilight';
	const NAME_VIBRANT_INK = 'vibrant-ink';
	const NAME_XQ_DARK = 'xq-dark';
	const NAME_XQ_LIGHT = 'xq-light';
	const NAME_YETI = 'yeti';
	const NAME_YONCE = 'yonce';
	const NAME_ZENBURN = 'zenburn';

	/**
	 * @var string $_name
	 */
	private $_name;

	/**
	 * @var string $_version
	 */
	private $_version = CodeMirror::VERSION_CURRENT;

	/**
	 * @param string $name
	 * @param string $version = CodeMirror::VERSION_CURRENT
	 */
	public function __construct ($name, $version = CodeMirror::VERSION_CURRENT) {
		$this->_name = $name;
		$this->_version = $version;
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
		return 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/' . $this->_version . '/theme/' . $this->_name . '.min.css';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}