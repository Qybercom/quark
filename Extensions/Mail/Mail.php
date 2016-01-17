<?php
namespace Quark\Extensions\Mail;

use Quark\IQuarkExtension;
use Quark\IQuarkExtensionConfig;

use Quark\Quark;
use Quark\QuarkClient;
use Quark\QuarkDTO;
use Quark\QuarkField;
use Quark\QuarkFile;
use Quark\QuarkHTMLIOProcessor;
use Quark\QuarkTCPNetworkTransport;
use Quark\QuarkView;

/**
 * Class Mail
 *
 * @package Quark\Extensions\Mail
 */
class Mail implements IQuarkExtension {
	const HEADER_SUBJECT = 'Subject';
	const HEADER_TO = 'To';
	const HEADER_FROM = 'From';

	/**
	 * @var IQuarkExtensionConfig|IQuarkMailProvider $_config
	 */
	private $_config;

	/**
	 * @var QuarkDTO $_dto
	 */
	private $_dto;

	/**
	 * @var string $_sender = ''
	 */
	private $_sender = '';

	/**
	 * @var string[] $_receivers = []
	 */
	private $_receivers = array();

	/**
	 * @var QuarkFile[] $_files = []
	 */
	private $_files = array();

	/**
	 * @var string $_log = ''
	 */
	private $_log = '';

	/**
	 * @var int $_timeout = 100000 (microseconds)
	 */
	private $_timeout = 100000;

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
	 * @param QuarkView|string $content
	 * @param string $to = ''
	 */
	public function __construct ($config, $subject, $content, $to = '') {
		$this->_config = Quark::Config()->Extension($config);

		if (!($this->_config instanceof IQuarkMailProvider)) return;

		$this->_dto = new QuarkDTO(new QuarkHTMLIOProcessor());
		$this->_dto->Header(self::HEADER_FROM, $this->_config->From());

		$this->Subject($subject);
		$this->Content($content);

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

		if (QuarkField::Email($email) && !in_array($email, $this->_receivers, true))
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
	 * @param QuarkView|string $content
	 *
	 * @return Mail|string
	 */
	public function Content ($content = '') {
		if (func_num_args() == 0)
			return $this->_dto->Data();

		if ($content instanceof QuarkView)
			$content->InlineStyles(true);

		$this->_dto->Data($content instanceof QuarkView
			? $content->Compile()
			: $content);

		return $this;
	}

	/**
	 * @return bool
	 */
	public function Send () {
		if (sizeof($this->_files) != 0)
			$this->_dto->Data(
				array(
					QuarkHTMLIOProcessor::TYPE_KEY => $this->_dto->Data(),
					'files' => $this->_files
				));

		$client = new QuarkClient($this->_config->SMTP(), new QuarkTCPNetworkTransport(), null, 5, false);

		$client->On(QuarkClient::EVENT_CONNECT, function (QuarkClient $client) {
			$this->_dto->Header(QuarkDTO::HEADER_CONTENT_TRANSFER_ENCODING, QuarkDTO::TRANSFER_ENCODING_BASE64);
			$this->_dto->Encoding(QuarkDTO::TRANSFER_ENCODING_BASE64);

			$smtp = $this->_config->SMTP();
			$response = $this->_dto->SerializeResponse();

			$this->_cmd($client);
			$this->_cmd($client, 'HELO Quark');
			$this->_cmd($client, 'AUTH LOGIN');
			$this->_cmd($client, base64_encode($smtp->user));
			$this->_cmd($client, base64_encode($smtp->pass));
			$this->_cmd($client, 'MAIL FROM: <' . $smtp->user . '>');

			foreach ($this->_receivers as $receiver)
				$this->_cmd($client, 'RCPT TO: <' . $receiver . '>');

			$this->_cmd($client, 'DATA');
			$this->_cmd($client, trim(substr($response, strpos($response, "\r\n"))) . "\r\n.");
			$this->_cmd($client, 'QUIT');
		});

		return $client->Connect();
	}

	/**
	 * @return string
	 */
	public function Log () {
		return $this->_log;
	}

	/**
	 * @param QuarkClient $client
	 * @param string $cmd
	 */
	private function _cmd (QuarkClient $client, $cmd = '') {
		if (func_num_args() == 2)
			$client->Send($cmd . "\r\n");

		usleep($this->_timeout);

		$response = $client->Receive(515);

		$this->_log .= $cmd . ': ' . $response . '<br>';
	}
}