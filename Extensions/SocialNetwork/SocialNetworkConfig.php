<?php
namespace Quark\Extensions\SocialNetwork;

use Quark\DataProviders\QuarkDNA;
use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;
use Quark\Quark;
use Quark\QuarkConfig;
use Quark\QuarkModelSource;
use Quark\QuarkURI;

/**
 * Class SocialNetworkConfig
 *
 * @package Quark\Extensions\SocialNetwork
 */
class SocialNetworkConfig implements IQuarkExtensionConfig {
	const STORAGE = 'quark.social';

	/**
	 * @var IQuarkSocialNetworkProvider $social
	 */
	public $social;

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
	 * @param IQuarkSocialNetworkProvider $social
	 * @param string $id
	 * @param string $secret
	 * @param string $dataProvider
	 */
	public function __construct (IQuarkSocialNetworkProvider $social, $id, $secret, $dataProvider = '') {
		$this->social = $social;
		$this->appId = $id;
		$this->appSecret = $secret;
		$this->_dataProvider = $dataProvider;

		$this->social->Init($this->appId, $this->appSecret);
	}

	/**
	 * @return array
	 */
	public function Credentials () {
		return array(
			'appId' => $this->appId,
			'secret' => $this->appSecret
		);
	}

	/**
	 * @return IQuarkSocialNetworkProvider
	 */
	public function SocialNetwork () {
		return $this->social;
	}

	/**
	 * @param string $dataProvider
	 *
	 * @return string
	 */
	public function DataProvider ($dataProvider = '') {
		if (func_num_args() != 0)
			$this->_dataProvider = $dataProvider;

		if ($this->_dataProvider == '')
			$this->_dataProvider = QuarkModelSource::Register(self::STORAGE, new QuarkDNA(), QuarkURI::FromFile(Quark::Config()->Location(QuarkConfig::RUNTIME) . '/social.qd'));

		return $this->_dataProvider;
	}

	/**
	 * @param string $name
	 */
	public function Stacked ($name) {
		$this->_name = $name;
	}

	/**
	 * @return IQuarkExtension
	 */
	public function ExtensionInstance () {
		return new SocialNetwork($this->_name);
	}
}