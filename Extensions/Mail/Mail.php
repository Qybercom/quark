<?php
namespace Quark\Extensions\Mail;

use Quark\IQuarkExtension;

use Quark\QuarkDTO;
use Quark\QuarkFile;
use Quark\QuarkHTMLIOProcessor;
use Quark\QuarkURI;

/**
 * Class Mail
 *
 * @package Quark\Extensions\Mail
 */
class Mail implements IQuarkExtension {
	/**
	 * @var QuarkURI $_uri
	 */
	private static $_uri;

	/**
	 * @var QuarkDTO $_dto
	 */
	private $_dto;

	/**
	 * @var string[] $_receivers
	 */
	private $_receivers = array();

	/**
	 * @param string $text
	 * @param string $to
	 */
	public function __construct ($text = '', $to = '') {
		$this->_dto = new QuarkDTO(new QuarkHTMLIOProcessor(), self::$_uri);
		$this->_dto->Header(QuarkDTO::HEADER_CONTENT_TRANSFER_ENCODING, 'base64');

		$this->_dto->Data($text);

		if (func_num_args() == 2)
			$this->_receivers[] = $to;
	}

	/**
	 * @param QuarkURI $uri
	 *
	 * @return QuarkURI
	 */
	public function Server (QuarkURI $uri = null) {
		if (func_num_args() == 1)
			self::$_uri = $uri;

		return self::$_uri;
	}

	/**
	 * @param QuarkFile $file
	 */
	public function File (QuarkFile $file) {
		$this->_dto->AttachData(array('file' => $file));
	}

	/**
	 * @param string $email
	 */
	public function To ($email) {
		$this->_receivers[] = $email;
	}

	/**
	 * @return bool
	 */
	public function Send () {
		//print_r($this);
		echo $this->_dto->Serialize();

		return true;
	}
}