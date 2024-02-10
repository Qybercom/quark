<?php
namespace Quark\Extensions\CDN\Providers;

use Quark\IQuarkModel;
use Quark\IQuarkModelWithCustomCollectionName;
use Quark\IQuarkModelWithDataProvider;

use Quark\Quark;
use Quark\QuarkDate;
use Quark\QuarkFile;
use Quark\QuarkHTTPClient;
use Quark\QuarkModel;
use Quark\QuarkObject;

use Quark\DataProviders\QuarkDNA;

use Quark\Extensions\CDN\IQuarkCDNProvider;

/**
 * Class QuarkSelfCDN
 *
 * @property string $rid = ''
 * @property string $app = ''
 * @property string $origin = ''
 * @property string[] $hosts = []
 *
 * @package Quark\Extensions\CDN\Providers
 */
class QuarkSelfCDN implements IQuarkCDNProvider, IQuarkModel, IQuarkModelWithDataProvider, IQuarkModelWithCustomCollectionName {
	const STORAGE = 'quark.cdn';
	const STORAGE_FILENAME = 'cdn.qd';

	const COLLECTION = 'QuarkSelfCDN';

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
	 * @var string $_collection = self::COLLECTION
	 */
	private $_collection = self::COLLECTION;

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

		if (isset($ini->Collection))
			$this->_collection = $ini->Collection;
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

		if (!is_array($item->hosts))
			$item->hosts = array();

		if (!$file->Exists()) {
			$file->Location($hostFs);

			$origin = QuarkHTTPClient::Download($item->origin);
			if (!$origin) return false;

			$file->Content($origin->Content());
			if (!$file->SaveContent()) return false;
		}

		if (!in_array($host, $item->hosts)) {
			$item->hosts[] = $host;
			if (!$item->Save()) return false;
		}

		return in_array($host, $item->hosts) ? $hostWeb : false;
	}

	/**
	 * @param QuarkFile $file
	 *
	 * @return string
	 */
	public function CDNResourceCreate (QuarkFile $file) {
		$now = QuarkDate::GMTNow(QuarkDate::FORMAT_ISO_FULL);
		$parent = implode('/', array(
			$now->Format('Y/m/d'),
			$now->Format('H'),
			Quark::GuID()
		));

		$file->Location($this->_fsHost . '/' . $parent . ($file->extension ? ('.' . $file->extension) : ''));

		if (!$file->SaveContent(QuarkFile::MODE_DEFAULT, true)) return false;

		$id = base64_encode($parent . '.' . $file->extension);
		$origin = $this->_host($id);

		/**
		 * @var QuarkModel|QuarkSelfCDN $resource
		 */
		$resource = new QuarkModel($this, array(
			'rid' => $id,
			'app' => $this->_appId,
			'origin' => $origin,
			'hosts' => array(sha1($origin))
		));

		return $resource->Create() ? $resource->rid : false;
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
			QuarkDNA::RuntimeStorage($this->_storage, self::STORAGE_FILENAME);
			$this->_init = true;
		}

		return $this->_storage;
	}

	/**
	 * @return string
	 */
	public function CollectionName () {
		return $this->_collection;
	}

	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'rid' => '',
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
			'rid' => $id,
			'app' => $this->_appId
		));

		if ($resource == null)
			Quark::Log('[QuarkSelfCDN] Resource with rid "' . $id . '" for application "' . $this->_appId . '" not found', Quark::LOG_WARN);

		return $resource;
	}
}