<?php
namespace Quark\Extensions\GoogleFirebase;

use Quark\IQuarkExtension;
use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;
use Quark\IQuarkViewResourceWithDependencies;
use Quark\IQuarkViewResourceWithBackwardDependencies;

use Quark\Quark;
use Quark\QuarkGenericViewResource;
use Quark\QuarkInlineJSViewResource;
use Quark\QuarkJSViewResourceType;

use Quark\Extensions\GoogleFirebase\Libraries\GoogleFirebaseAnalytics;

/**
 * Class GoogleFirebase
 *
 * @package Quark\Extensions\GoogleFirebase
 */
class GoogleFirebase implements IQuarkExtension, IQuarkSpecifiedViewResource, IQuarkViewResourceWithDependencies, IQuarkViewResourceWithBackwardDependencies {
	const URL_LIB_JS = 'https://www.gstatic.com/firebasejs/';

	const URL_TEMPLATE = '{project_id}';
	const URL_TEMPLATE_AUTH_DOMAIN = '{project_id}.firebaseapp.com';
	const URL_TEMPLATE_DATABASE = 'https://{project_id}.firebaseio.com';
	const URL_TEMPLATE_STORAGE_BUCKET = '{project_id}.appspot.com';

	const VERSION_JS = '8.1.1';

	const LIB_NAME = 'firebase-app';

	/**
	 * @var GoogleFirebaseConfig $_config
	 */
	private $_config;

	/**
	 * @var string $_version = self::VERSION_JS
	 */
	private $_version = self::VERSION_JS;

	/**
	 * @var IQuarkGoogleFirebaseLibrary[] $_libraries = []
	 */
	private $_libraries = array();

	/**
	 * @param string $config
	 * @param string $version = self::VERSION_JS
	 */
	public function __construct ($config, $version = self::VERSION_JS) {
		$this->_config = Quark::Config()->Extension($config);
		$this->_version = $version;

		$this->_libraries = array(
			new GoogleFirebaseAnalytics()
		);
	}

	/**
	 * @param string $library = ''
	 *
	 * @return string
	 */
	public function LibraryJS ($library = '') {
		return self::URL_LIB_JS . $this->_version . '/' . $library . '.js';
	}

	/**
	 * @return IQuarkViewResourceType
	 */
	public function Type () {
		return new QuarkJSViewResourceType();
	}

	/**
	 * @return string
	 */
	public function Location () {
		return $this->LibraryJS(self::LIB_NAME);
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return array(
			new QuarkInlineJSViewResource('
				var firebaseConfig = ' . $this->_config->JSON() . ';

				firebase.initializeApp(firebaseConfig);
			')
		);
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function BackwardDependencies () {
		$out = array();

		foreach ($this->_libraries as $library)
			$out[] = QuarkGenericViewResource::ForeignJS($library->GoogleFirebaseLibraryURL($this));

		return $out;
	}
}