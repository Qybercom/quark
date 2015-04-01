<?php
namespace Quark\Extensions\Mail;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;
use Quark\IQuarkTransportProvider;

use Quark\Quark;
use Quark\QuarkCertificate;
use Quark\QuarkClient;
use Quark\QuarkDTO;
use Quark\QuarkField;
use Quark\QuarkFile;
use Quark\QuarkHTMLIOProcessor;
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

		$this->_dto = new QuarkDTO(new QuarkHTMLIOProcessor());
		$this->_dto->Header(QuarkDTO::HEADER_CONTENT_TRANSFER_ENCODING, 'base64');
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
		$this->_dto->AttachData(array('file' => $file));

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

		if (QuarkField::Email($email))
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

		$this->_dto->Data(base64_encode($text));
		return $this;
	}

	/**
	 * @return bool
	 */
	public function Send () {
		print_r($this->_config->SMTP());
		//echo $this->_dto->Serialize();
		$client = new QuarkClient($this->_config->SMTP(), $this, null, 5);
		$client->Action();

		return true;
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
	 *
	 * @return mixed
	 */
	public function Action (QuarkClient $client) {
		if (!$client->Connect()) return false;

		$smtp = $this->_config->SMTP();

		echo 'Connected: ', $client->Receive();
		$client->Send("HELO Quark\r\n");
		echo 'HELO: ', $client->Receive();

		$client->Send("AUTH LOGIN\r\n");
		echo 'AUTH LOGIN: ', $client->Receive();
		$client->Send(base64_encode($smtp->user) . "\r\n");
		echo '<LOGIN>: ', $client->Receive();
		$client->Send(base64_encode($smtp->pass) . "\r\n");
		echo '<PASSWORD>: ', $client->Receive();

		$client->Send('MAIL FROM: ' . $smtp->user . "\r\n");
		echo 'MAIL FROM: ', $client->Receive();

		foreach ($this->_receivers as $receiver) {
			$client->Send('RCPT TO: ' . $receiver . "\r\n");
			echo 'RCPT TO ' . $receiver . ': ', $client->Receive();
		}

		$client->Send("DATA\r\n");
		echo 'DATA: ', $client->Receive();
		$client->Send($this->_dto->Serialize() . "\r\n.\r\n");
		echo '<DATA>: ', $client->Receive();

		$client->Send("QUIT\r\n");
		echo 'QUIT: ', $client->Receive();

		return $client->Close();
	}
}