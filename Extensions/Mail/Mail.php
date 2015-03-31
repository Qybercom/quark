<?php
namespace Quark\Extensions\Mail;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;

use Quark\Quark;
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
	 * @var IQuarkExtensionConfig|IQuarkMailProvider $_config
	 */
	private $_config;

	/**
	 * @var QuarkDTO $_dto
	 */
	private $_dto;

	/**
	 * @var string[] $_receivers
	 */
	private $_receivers = array();

	/**
	 * @param string $config
	 * @param string $text
	 * @param string $to
	 */
	public function __construct ($config, $text = '', $to = '') {
		$this->_config = Quark::Config()->Extension($config);

		$this->_dto = new QuarkDTO(new QuarkHTMLIOProcessor());
		$this->_dto->Header(QuarkDTO::HEADER_CONTENT_TRANSFER_ENCODING, 'base64');

		$this->_dto->Data($text);

		if (func_num_args() == 2)
			$this->_receivers[] = $to;
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
		print_r($this);
		//echo $this->_dto->Serialize();

		return true;
	}
}