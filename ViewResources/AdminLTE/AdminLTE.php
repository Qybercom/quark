<?php
namespace Quark\ViewResources\AdminLTE;

use Quark\IQuarkViewResource;
use Quark\IQuarkForeignViewResource;
use Quark\IQuarkViewResourceWithBackwardDependencies;
use Quark\IQuarkViewResourceType;

use Quark\QuarkDTO;
use Quark\QuarkCSSViewResourceType;

use Quark\ViewResources\FontAwesome\FontAwesome;
use Quark\ViewResources\IonIcons\IonIcons;
use Quark\ViewResources\jQuery\jQueryCore;
use Quark\ViewResources\TwitterBootstrap\TwitterBootstrap;
use Quark\ViewResources\Quark\QuarkResponsiveUI;
use Quark\ViewResources\Quark\QuarkLegacyIESupport;

/**
 * Class AdminLTE
 *
 * @package Quark\ViewResources\AdminLTE
 */
class AdminLTE implements IQuarkViewResource, IQuarkForeignViewResource, IQuarkViewResourceWithBackwardDependencies {
	const CURRENT_VERSION = '2.3.3';

	/**
	 * @var string $_version = self::CURRENT_VERSION
	 */
	private $_version = self::CURRENT_VERSION;

	/**
	 * @var string $_skin = AdminLTESkin::ALL
	 */
	private $_skin = AdminLTESkin::ALL;

	/**
	 * @var bool $_app = true
	 */
	private $_app = true;

	/**
	 * @param string $skin = AdminLTESkin::ALL
	 * @param bool $app = true
	 * @param string $version = self::CURRENT_VERSION
	 */
	public function __construct ($skin = AdminLTESkin::ALL, $app = true, $version = self::CURRENT_VERSION) {
		$this->_version = $version;
		$this->_skin = $skin;
		$this->_app = $app;
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
		return 'https://cdnjs.cloudflare.com/ajax/libs/admin-lte/' . $this->_version . '/css/AdminLTE.min.css';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function BackwardDependencies () {
		return array(
			new FontAwesome(),
			new IonIcons(),
			new jQueryCore(),
			new TwitterBootstrap(),
			new QuarkResponsiveUI(QuarkResponsiveUI::DEVICE_WIDTH),
			new QuarkLegacyIESupport(),
			new AdminLTESkin($this->_skin, $this->_version),
			$this->_app ? new AdminLTEApp($this->_version) : null
		);
	}
}