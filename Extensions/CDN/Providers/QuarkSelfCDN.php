<?php
namespace Quark\Extensions\CDN\Providers;

use Quark\IQuarkModel;
use Quark\IQuarkModelWithDataProvider;

use Quark\Quark;
use Quark\QuarkConfig;
use Quark\QuarkDate;
use Quark\QuarkFile;
use Quark\QuarkHTTPClient;
use Quark\QuarkModel;
use Quark\QuarkModelSource;
use Quark\QuarkObject;
use Quark\QuarkURI;

use Quark\DataProviders\QuarkDNA;

use Quark\Extensions\CDN\IQuarkCDNProvider;

/**
 * Class QuarkSelfCDN
 *
 * @property string $id = ''
 * @property string $app = ''
 * @property string $origin = ''
 * @property string[] $hosts = []
 *
 * @package Quark\Extensions\CDN\Providers
 */
class QuarkSelfCDN implements IQuarkCDNProvider, IQuarkModel, IQuarkModelWithDataProvider {
	const STORAGE = 'quark.cdn';

	/**
	 * @var string $_webHost = ''
	 */
	private $_webHost = '';

	/**
	 * @var string $_fsHost = ''
	 */
	private $_fsHost = '';

	/**
	 * @var string $_storage
	 */
	private $_storage = '';

	/**
	 * @var bool $_init = false
	 */
	private $_init = false;

	/**
	 * @var string $_appId = ''
	 */
	private $_appId = '';

	/**
	 * @var string $_appSecret = ''
	 */
	private $_appSecret = '';

	/**
	 * @param string $webHost = ''
	 * @param string $fsHost = ''
	 * @param string $storage = self::STORAGE
	 */
	public function __construct ($webHost = '', $fsHost = '', $storage = self::STORAGE) {
		$this->_webHost = $webHost;
		$this->_fsHost  = $fsHost;
		$this->_storage = $storage;
		$this->_init = func_num_args() == 3;
	}

	/**
	 * @param string $appId
	 * @param string $appSecret
	 * @param object $ini
	 *
	 * @return void
	 */
	public function CDNApplication ($appId, $appSecret, $ini) {
		$this->_appId = $appId;
		$this->_appSecret = $appSecret;

		if (isset($ini->WebHost))
			$this->_webHost = $ini->WebHost;

		if (isset($ini->FSHost))
			$this->_fsHost = $ini->FSHost;

		if (isset($ini->Storage)) {
			$this->_storage = QuarkObject::ConstValue($ini->Storage);
			$this->_init = true;
		}
	}

	/**
	 * @param string $id
	 *
	 * @return string
	 */
	public function CDNResourceURL ($id) {
		$hostWeb = $this->_host($id);
		$hostFs = $this->_host($id, false);

		$file = new QuarkFile($hostFs);

		$item = $this->_resource($id);
		$host = sha1($hostWeb);

		if ($item == null) {
			if ($file->Exists())
				$file->DeleteFromDisk();

			return false;
		}

		if (!$file->Exists()) $file->Location($hostFs);
		elseif (in_array($host, $item->hosts)) return $hostWeb;

		$origin = QuarkHTTPClient::Download($item->origin);
		if (!$origin) return false;

		$file->Content($origin->Content());
		if (!$file->SaveContent()) return false;

		$item->hosts[] = $host;
		return $item->Save() ? $hostWeb : false;
	}

	/**
	 * @param QuarkFile $file
	 *
	 * @return string
	 */
	public function CDNResourceCreate (QuarkFile $file) {
		$now = QuarkDate::GMTNow(QuarkDate::NOW_FULL);
		$parent = implode('/', array(
			$now->Format('Y/m/d'),
			$now->Format('H'),
			Quark::GuID()
		));

		$file->Location($this->_fsHost . '/' . $parent);

		if (!$file->SaveContent(QuarkFile::MODE_DEFAULT, true)) return false;

		$id = base64_encode($parent . '.' . $file->extension);
		$origin = $this->_host($id);

		/**
		 * @var QuarkModel|QuarkSelfCDN $resource
		 */
		$resource = new QuarkModel($this, array(
			'id' => $id,
			'app' => $this->_appId,
			'origin' => $origin,
			'hosts' => array(sha1($origin))
		));

		return $resource->Create() ? $resource->id : false;
	}

	/**
	 * @param string $id
	 * @param QuarkFile $file
	 *
	 * @return bool
	 */
	public function CDNResourceUpdate ($id, QuarkFile $file) {
		$resource = $this->_resource($id);
		
		if ($resource == null) return false;

		$origin = $this->_host($id);

		if (!$file->Exists())
			$file->Location($this->_host($id, false));

		$resource->origin = $origin;
		$resource->hosts = array(sha1($origin));

		return $resource->Save();
	}

	/**
	 * @param string $id
	 *
	 * @return bool
	 */
	public function CDNResourceDelete ($id) {
		$resource = $this->_resource($id);

		return $resource ? $resource->Remove() : false;
	}

	/**
	 * @return string
	 */
	public function DataProvider () {
		if (!$this->_init) {
			QuarkModelSource::Register($this->_storage, new QuarkDNA(), QuarkURI::FromFile(Quark::Config()->Location(QuarkConfig::RUNTIME) . '/cdn.qd'));
			$this->_init = true;
		}

		return $this->_storage;
	}

	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'id' => '',
			'app' => '',
			'origin' => '',
			'hosts' => array()
		);
	}

	/**
	 * @return void
	 */
	public function Rules () {
		// TODO: Implement Rules() method.
	}

	/**
	 * @param string $id
	 * @param bool $web = true
	 *
	 * @return string
	 */
	private function _host ($id, $web = true) {
		return ($web ? $this->_webHost : $this->_fsHost) . '/' . base64_decode($id);
	}

	/**
	 * @param string $id
	 *
	 * @return QuarkModel|QuarkSelfCDN
	 */
	private function _resource ($id) {
		$resource = QuarkModel::FindOne($this, array(
			'id' => $id,
			'app' => $this->_appId
		));

		if ($resource == null)
			Quark::Log('[QuarkSelfCDN] Resource with id "' . $id . '" for application "' . $this->_appId . '" not found', Quark::LOG_WARN);

		return $resource;
	}
}