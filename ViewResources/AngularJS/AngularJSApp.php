<?php
namespace Quark\ViewResources\AngularJS;

use Quark\IQuarkExtension;
use Quark\IQuarkViewModel;
use Quark\IQuarkViewModelWithComponents;
use Quark\IQuarkViewModelWithResources;
use Quark\IQuarkViewResource;

use Quark\Quark;
use Quark\QuarkCSSViewResourceType;
use Quark\QuarkFile;
use Quark\QuarkGenericViewResource;
use Quark\QuarkJSViewResourceType;
use Quark\QuarkModel;
use Quark\QuarkObject;
use Quark\QuarkProxyJSViewResource;
use Quark\QuarkView;
use Quark\QuarkViewBehavior;

/**
 * Class AngularJSApp
 *
 * @package Quark\ViewResources\AngularJS
 */
class AngularJSApp implements IQuarkExtension, IQuarkViewModel, IQuarkViewModelWithComponents, IQuarkViewModelWithResources {
	const VIEW_ENVIRONMENT = '__environment__';

	use QuarkViewBehavior;

	/**
	 * @var AngularJSAppConfig $_config
	 */
	private $_config;

	/**
	 * @var IQuarkViewResource[] $_resources = []
	 */
	private $_resources = array();

	/**
	 * @param string $config = ''
	 * @param IQuarkViewResource[] $resources = []
	 */
	public function __construct ($config = '', $resources = []) {
		$this->_config = Quark::Config()->Extension($config);
		$this->Resources($resources);
	}

	/**
	 * @return AngularJSAppConfig
	 */
	public function &Config () {
		return $this->_config;
	}

	/**
	 * @param IQuarkViewResource[] $resources = []
	 *
	 * @return IQuarkViewResource[]
	 */
	public function Resources ($resources = []) {
		if (func_num_args() != 0)
			$this->_resources = $resources;

		return $this->_resources;
	}

	/**
	 * @param $vars = []
	 *
	 * @return IQuarkViewResource[]
	 */
	public function Environment ($vars = []) {
		if (func_num_args() != 0) {
			$this->_resources[self::VIEW_ENVIRONMENT] = array();

			if (QuarkObject::isTraversable($vars))
				foreach ($vars as $key => $value)
					$this->_resources[self::VIEW_ENVIRONMENT][] = new QuarkProxyJSViewResource($key, $value instanceof QuarkModel ? $value->Extract() : $value);
		}

		return isset($this->_resources[self::VIEW_ENVIRONMENT]) ? $this->_resources[self::VIEW_ENVIRONMENT] : array();
	}

	/**
	 * @return string
	 */
	public function HTML () {
		$viewFile = new QuarkFile($this->Config()->ArtifactLocation('IndexHTML'));
		if (!$viewFile->Load()) return '';

		$html = $viewFile->Content();

		$resources = AngularJSAppConfig::ArtifactResources();

		foreach ($resources as $name => &$type) {
			if ($type == 'CSS')
				$html = str_replace($this->_artifactCSS($name), '', $html);

			if ($type == 'JS')
				$html = str_replace($this->_artifactJS($name), '', $html);
		}

		/**
		 * @var QuarkView|AngularJSApp $view
		 */
		$view = $this->Container();
		$html = str_replace('</body>', $view->Resources(false) . '</body>', $html);

		return $html;
	}

	/**
	 * @param string $name = ''
	 *
	 * @return string
	 */
	private function _artifactCSS ($name = '') {
		return '<link rel="stylesheet" href="' . $this->_config->Artifact($name . 'CSS') . '">';
	}

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	private function _artifactJS ($name = '') {
		return '<script src="' . $this->_config->Artifact($name . 'JS') . '" defer></script>';
	}

	/**
	 * @return string
	 */
	public function View () {
		return __DIR__ . '/AngularJSAppLayout.php';
	}

	/**
	 * @return IQuarkViewResource|string
	 */
	public function ViewStylesheet () {
		return new QuarkGenericViewResource($this->_config->ArtifactLocation('StylesCSS'), new QuarkCSSViewResourceType(), false);
	}

	/**
	 * @return IQuarkViewResource|string
	 */
	public function ViewController () {
		return new QuarkGenericViewResource($this->_config->ArtifactLocation('MainJS'), new QuarkJSViewResourceType(true), false);
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function ViewResources () {
		$resources = $this->_resources;
		if (isset($this->_resources[self::VIEW_ENVIRONMENT])) {
			unset($resources[self::VIEW_ENVIRONMENT]);

			$resources = array_merge($resources, $this->_resources[self::VIEW_ENVIRONMENT]);
		}

		$artifacts = AngularJSAppConfig::ArtifactResources();
		foreach ($artifacts as $name => &$type) {
			if ($name == 'MainJS' || $name == 'StylesCSS') continue;

			if ($type == 'CSS')
				$resources[] = new QuarkGenericViewResource($this->_config->ArtifactLocation($name . $type), new QuarkCSSViewResourceType(), false);

			if ($type == 'JS')
				$resources[] = new QuarkGenericViewResource($this->_config->ArtifactLocation($name . $type), new QuarkJSViewResourceType(true), false);
		}

		return $resources;
	}
}