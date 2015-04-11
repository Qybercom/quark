<?php
namespace Quark\Extensions\Mail;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;
use Quark\IQuarkTransportProvider;

use Quark\Quark;
use Quark\QuarkArchException;
use Quark\QuarkCertificate;
use Quark\QuarkClient;
use Quark\QuarkDTO;
use Quark\QuarkField;
use Quark\QuarkFile;
use Quark\QuarkHTMLIOProcessor;
use Quark\QuarkMultipartIOProcessor;
use Quark\QuarkURI;

/**
 * Class Mail
 *
 * @package Quark\Extensions\Mail
 */
class Mail implements IQuarkExtension, IQuarkTransportProvider {
	const HEADER_SUBJECT = 'Subject';
	const HEADER_TO = 'To';
	const HEADER_FROM = 'From';

	/**
	 * @var IQuarkExtensionConfig|IQuarkMailProvider $_config
	 */
	private $_config;

	/**
	 * @var QuarkURI $_uri
	 */
	private $_uri;

	/**
	 * @var QuarkDTO $_dto
	 */
	private $_dto;

	/**
	 * @var string $_sender
	 */
	private $_sender = '';

	/**
	 * @var string[] $_receivers
	 */
	private $_receivers = array();

	/**
	 * @var QuarkFile[] $_files
	 */
	private $_files = array();

	/**
	 * @var string $_log
	 */
	private $_log = '';

	/**
	 * @param $name
	 * @param $email
	 *
	 * @return string
	 */
	public static function Sender ($name, $email) {
		return $name . ' <' . $email . '>';
	}

	/**
	 * @param string $config
	 * @param string $subject
	 * @param string $text
	 * @param string $to
	 */
	public function __construct ($config, $subject, $text, $to = '') {
		$this->_config = Quark::Config()->Extension($config);

		$this->_dto = new QuarkDTO(QuarkMultipartIOProcessor::ForAttachment(new QuarkHTMLIOProcessor()));
		$this->_dto->Header(self::HEADER_FROM, $this->_config->From());

		$this->Subject($subject);
		$this->Text($text);

		if (func_num_args() == 4)
			$this->To($to);
	}

	/**
	 * @param QuarkFile $file
	 *
	 * @return Mail
	 */
	public function File (QuarkFile $file) {
		$this->_files[] = $file;

		return $this;
	}

	/**
	 * @param string $email
	 *
	 * @return Mail|string[]
	 */
	public function To ($email = '') {
		if (func_num_args() == 0)
			return $this->_receivers;

		if (QuarkField::Email($email) && !in_array($email, $this->_receivers))
			$this->_receivers[] = $email;

		return $this;
	}

	/**
	 * @param string $email
	 * @param string $name
	 *
	 * @return Mail|string[]
	 */
	public function From ($email = '', $name = '') {
		if (func_num_args() == 0)
			return $this->_sender;

		$this->_sender = $name . ' <' . $email . '>';
		$this->_dto->Header(self::HEADER_FROM, $this->_sender);

		return $this;
	}

	/**
	 * @param string $subject
	 *
	 * @return Mail|string
	 */
	public function Subject ($subject = '') {
		if (func_num_args() == 0)
			return $this->_dto->Header(self::HEADER_SUBJECT);

		$this->_dto->Header(self::HEADER_SUBJECT, $subject);
		return $this;
	}

	/**
	 * @param string $text
	 *
	 * @return Mail|string
	 */
	public function Text ($text = '') {
		if (func_num_args() == 0)
			return $this->_dto->Data();

		$this->_dto->Data($text);
		return $this;
	}

	/**
	 * @return bool
	 */
	public function Send () {
		if (sizeof($this->_files) != 0)
			$this->_dto->AttachData(array('files' => $this->_files));

		$client = new QuarkClient($this->_config->SMTP(), $this, null, 5);
		$client->Action();

		return true;
	}

	/**
	 * @return string
	 */
	public function Log () {
		return $this->_log;
	}

	/**
	 * @param QuarkURI         $uri
	 * @param QuarkCertificate $certificate
	 *
	 * @return mixed
	 */
	public function Setup (QuarkURI $uri, QuarkCertificate $certificate = null) {
		$this->_uri = $uri;
	}

	/**
	 * @param QuarkClient $client
	 * @param int         $expect
	 * @param string      $cmd
	 *
	 * @throws QuarkArchException
	 */
	private function _cmd (QuarkClient $client, $expect, $cmd = '') {
		if (func_num_args() == 3)
			$client->Send($cmd . "\r\n");

		$response = $client->Receive(QuarkClient::MODE_BUCKET);
		$code = substr($response, 0, 3);

		$this->_log .= $cmd . ': ' . $response . '<br>';

		if ($code != $expect)
			throw new QuarkArchException('SMTP server returned unexpected [' . $code . '] for command ' . $cmd);
	}

	/**
	 * @param QuarkClient $client
	 *
	 * @return mixed
	 */
	public function Action (QuarkClient $client) {
		if (!$client->Connect()) return false;

		$smtp = $this->_config->SMTP();
		$this->_dto->Header(QuarkDTO::HEADER_CONTENT_TRANSFER_ENCODING, QuarkMultipartIOProcessor::TRANSFER_ENCODING_BASE64);

		try {
			$this->_cmd($client, 220);
			$this->_cmd($client, 250, 'HELO Quark');
			$this->_cmd($client, 334, 'AUTH LOGIN');
			$this->_cmd($client, 334, base64_encode($smtp->user));
			$this->_cmd($client, 235, base64_encode($smtp->pass));
			$this->_cmd($client, 250, 'MAIL FROM: <' . $smtp->user . '>');

			foreach ($this->_receivers as $receiver)
				$this->_cmd($client, 250, 'RCPT TO: <' . $receiver . '>');

			$this->_cmd($client, 354, 'DATA');
			$this->_cmd($client, 250, $this->_dto->Serialize() . "\r\n.");
			$this->_cmd($client, 221, 'QUIT');
		}
		catch (QuarkArchException $e) {
			return false;
		}

		return $client->Close();
	}
}