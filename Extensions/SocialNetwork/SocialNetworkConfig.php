<?php
namespace Quark\Extensions\SocialNetwork;

use Quark\DataProviders\QuarkDNA;
use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;
use Quark\Quark;
use Quark\QuarkConfig;
use Quark\QuarkModelSource;
use Quark\QuarkObject;
use Quark\QuarkURI;

/**
 * Class SocialNetworkConfig
 *
 * @package Quark\Extensions\SocialNetwork
 */
class SocialNetworkConfig implements IQuarkExtensionConfig {
	const STORAGE = 'quark.social';

	/**
	 * @var IQuarkSocialNetworkProvider $_provider
	 */
	private $_provider;

	/**
	 * @var string $appId
	 */
	public $appId = '';

	/**
	 * @var string $appSecret
	 */
	public $appSecret = '';

	/**
	 * @var string $_dataProvider
	 */
	private $_dataProvider = '';

	/**
	 * @var string $_name
	 */
	private $_name = '';

	/**
	 * @param IQuarkSocialNetworkProvider $provider
	 * @param string $id = ''
	 * @param string $secret = ''
	 * @param string $dataProvider = ''
	 */
	public function __construct (IQuarkSocialNetworkProvider $provider, $id = '', $secret = '', $dataProvider = '') {
		$this->_provider = $provider;
		$this->appId = $id;
		$this->appSecret = $secret;
		$this->_dataProvider = $dataProvider;

		$this->_provider->SocialNetworkApplication($this->appId, $this->appSecret);
	}

	/**
	 * @return object
	 */
	public function Credentials () {
		return (object)array(
			'appId' => $this->appId,
			'secret' => $this->appSecret
		);
	}

	/**
	 * @return IQuarkSocialNetworkProvider
	 */
	public function &SocialNetwork () {
		return $this->_provider;
	}

	/**
	 * @param string $dataProvider
	 *
	 * @return string
	 */
	public function &DataProvider ($dataProvider = '') {
		if (func_num_args() != 0)
			$this->_dataProvider = $dataProvider;

		if ($this->_dataProvider == '') {
			QuarkModelSource::Register(self::STORAGE, new QuarkDNA(), QuarkURI::FromFile(Quark::Config()->Location(QuarkConfig::RUNTIME) . '/social.qd'));
			$this->_dataProvider = self::STORAGE;
		}

		return $this->_dataProvider;
	}

	/**
	 * @param string $name
	 */
	public function Stacked ($name) {
		$this->_name = $name;
	}

	/**
	 * @return string
	 */
	public function ExtensionName () {
		return $this->_name;
	}

	/**
	 * @param object $ini
	 *
	 * @return mixed
	 */
	public function ExtensionOptions ($ini) {
		if (isset($ini->AppID))
			$this->appId = $ini->AppID;

		if (isset($ini->AppSecret))
			$this->appSecret = $ini->AppSecret;

		if (isset($ini->DataProvider))
			$this->_dataProvider = QuarkObject::ConstValue($ini->DataProvider);

		$this->_provider->SocialNetworkApplication($this->appId, $this->appSecret);
	}

	/**
	 * @return SocialNetwork|IQuarkExtension
	 */
	public function ExtensionInstance () {
		return new SocialNetwork($this->_name);
	}
}