<?php
namespace Quark\Extensions\BitTorrent;

use Quark\IQuarkLinkedModel;
use Quark\IQuarkModel;
use Quark\IQuarkStrongModel;

use Quark\QuarkArchException;
use Quark\QuarkFile;

/**
 * Class BitTorrentFile
 *
 * @package Quark\Extensions\BitTorrent
 */
class BitTorrentFile extends QuarkFile implements IQuarkModel, IQuarkStrongModel, IQuarkLinkedModel {
	/**
	 * @var BitTorrentEncode $_processor
	 */
	private $_processor;

	/**
	 * @param string $location = ''
	 * @param bool $load = false
	 */
	public function __construct ($location = '', $load = false) {
		parent::__construct($location, $load);

		$this->_processor = new BitTorrentEncode();
	}

	/**
	 * @param string $location = ''
	 *
	 * @return BitTorrentFile
	 *
	 * @throws QuarkArchException
	 */
	public function Load ($location = '') {
		parent::Load($location);

		$this->_init();

		return $this;
	}

	/**
	 * @param $raw
	 *
	 * @return mixed
	 */
	public function Link ($raw) {
		$content = base64_decode($raw);

		$out = new self();
		$out->Content($content, true);
		$out->TorrentDecode();

		return $out;
	}

	/**
	 * @return string
	 */
	public function Unlink () {
		$this->_content = $this->TorrentEncode();

		return base64_encode($this->_content);
	}

	/**
	 * Populating torrent file
	 */
	public function TorrentDecode () {
		$data = $this->_processor->Decode($this->_content);
		print_r($data);
	}

	/**
	 * @return string
	 */
	public function TorrentEncode () {
		$data = array();

		$out = $this->_processor->Encode($data);
		var_dump($out);

		return $out;
	}
}