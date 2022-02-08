<?php
namespace Quark\Extensions\Mail;

use Quark\IQuarkExtension;

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

	const DEFAULT_SUBJECT = 'No subject';

	/**
	 * @var MailConfig $_config
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
	 * @var string $_from = ''
	 */
	private $_from = '';

	/**
	 * @var string[] $_receivers = []
	 */
	private $_receivers = array();

	/**
	 * @var QuarkFile[] $_files = []
	 */
	private $_files = array();

	/**
	 * @var string $_subject = self::DEFAULT_SUBJECT
	 */
	private $_subject = self::DEFAULT_SUBJECT;

	/**
	 * @var string $_log = ''
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
	 * @param string $subject = ''
	 * @param QuarkView|string $content = ''
	 * @param string $to = ''
	 */
	public function __construct ($config, $subject = '', $content = '', $to = '') {
		$this->_config = Quark::Config()->Extension($config);

		if (!($this->_config instanceof MailConfig)) return;

		$this->_dto = new QuarkDTO(new QuarkHTMLIOProcessor());
		$this->_sender = $this->_config->Sender();
		$this->_from = $this->_config->From();

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
	 * @param string $name
	 * @param string $email
	 *
	 * @return string
	 */
	public function From ($name = '', $email = '') {
		$num = func_num_args();

		if ($num != 0)
			$this->_sender = self::Sender($name, $num == 2 ? $email : $this->_config->Username());

		return $this->_sender;
	}

	/**
	 * @param string $subject
	 *
	 * @return string
	 */
	public function Subject ($subject = '') {
		if (func_num_args() != 0)
			$this->_subject = $subject;

		return $this->_subject;
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
		$this->_dto->Header(self::HEADER_FROM, $this->_sender);
		$this->_dto->Header(self::HEADER_SUBJECT, $this->_subject);

		if (sizeof($this->_files) != 0)
			$this->_dto->Data(array(
				QuarkHTMLIOProcessor::TYPE_KEY => $this->_dto->Data(),
				'files' => $this->_files
			));

		$out = false;

		if ($this->_config->LocalSend()) {
			$out = true;
			$prefix = $this->_config->LocalStoragePrefix();

			$dto = new QuarkFile($prefix . '.txt');
			$dto->Content($this->_outgoingDTO());
			$out &= $dto->SaveContent();

			$html = new QuarkFile($prefix . '.html');
			$html->Content($this->_dto->Data());
			$out &= $html->SaveContent();
		}
		else {
			$client = new QuarkClient($this->_config->EndpointSMTP(), new QuarkTCPNetworkTransport(), $this->_config->Certificate(), $this->_config->TimeoutConnect(), false);

			$client->AutoSecure(false);

			$client->On(QuarkClient::EVENT_ERROR_CRYPTOGRAM, function ($error) {
				$this->_log('[Mail] Cryptogram enabling error. ' . $error);
			});

			$client->On(QuarkClient::EVENT_CONNECT, function (QuarkClient $client) {
				$dto = $this->_outgoingDTO();
				$smtp = $this->_config->EndpointSMTP();

				$this->_cmd($client);
				$this->_cmd($client, 'HELO Quark');

				if ($this->_config->Provider()->MailStartTLS()) {
					$this->_cmd($client, 'STARTTLS');
					$client->Secure(true);
					$this->_cmd($client, 'HELO Quark');
				}

				$this->_cmd($client, 'AUTH LOGIN');
				$this->_cmd($client, base64_encode($smtp->user));
				$this->_cmd($client, base64_encode($smtp->pass));
				$this->_cmd($client, 'MAIL FROM: <' . $this->_from . '>');

				foreach ($this->_receivers as $i => &$receiver)
					$this->_cmd($client, 'RCPT TO: <' . $receiver . '>');

				unset($i, $receiver);

				$this->_cmd($client, 'DATA');
				$this->_cmd($client, trim(substr($dto, strpos($dto, "\r\n"))) . "\r\n.");
				$this->_cmd($client, 'QUIT');
			});

			$out = $client->Connect();

			if ($this->_config->Log()) {
				Quark::Log('[Mail] Tracing Mail');
				Quark::Trace($this->Log());
			}
		}

		return $out;
	}

	/**
	 * @return string
	 */
	public function Log () {
		return $this->_log;
	}

	/**
	 * @return string
	 */
	private function _outgoingDTO () {
		$this->_dto->Header(QuarkDTO::HEADER_CONTENT_TRANSFER_ENCODING, QuarkDTO::TRANSFER_ENCODING_BASE64);
		$this->_dto->Encoding(QuarkDTO::TRANSFER_ENCODING_BASE64);

		return $this->_dto->SerializeResponse();
	}

	/**
	 * @param QuarkClient $client
	 * @param string $cmd = ''
	 * @param callable $callback = null
	 */
	private function _cmd (QuarkClient &$client, $cmd = '', callable $callback = null) {
		if (func_num_args() != 1)
			$client->Send($cmd . "\r\n");
			
		if ($callback != null)
			$callback($client);

		usleep($this->_config->TimeoutCommand());

		$response = $client->Receive();

		$this->_log($cmd . ': ' . $response);
	}

	/**
	 * @param string $message = ''
	 *
	 * @return string
	 */
	private function _log ($message = '') {
		if (func_num_args() != 0)
			$this->_log .= $message;

		return $this->_log;
	}
}