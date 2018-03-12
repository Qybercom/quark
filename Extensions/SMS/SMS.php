<?php
namespace Quark\Extensions\SMS;

use Quark\IQuarkExtension;

use Quark\Quark;
use Quark\QuarkArchException;

/**
 * Class SMS
 *
 * @package Quark\Extensions\SMS
 */
class SMS implements IQuarkExtension {
	/**
	 * @var SMSConfig $_config
	 */
	private $_config;

	/**
	 * @var string $_message = ''
	 */
	private $_message = '';

	/**
	 * @var string[] $_phones = []
	 */
	private $_phones = array();

	/**
	 * @param string $config = ''
	 * @param string $message = ''
	 * @param string[] $phones = []
	 *
	 * @throws QuarkArchException
	 */
	public function __construct ($config = '', $message = '', $phones = []) {
		$this->Message($message);
		$this->Phones($phones);

		$this->_config = Quark::Config()->Extension($config);

		if (!($this->_config instanceof SMSConfig))
			throw new QuarkArchException('[SMS] Provided config is not a SMSConfig');
	}

	/**
	 * @param string $message
	 *
	 * @return string
	 */
	public function Message ($message = '') {
		if (func_num_args() == 1)
			$this->_message = (string)$message;

		return $this->_message;
	}

	/**
	 * @param string $phone = ''
	 *
	 * @return SMS
	 */
	public function Phone ($phone = '') {
		if (func_num_args() != 0)
			$this->_phones[] = $phone;

		return $this;
	}

	/**
	 * @param string[] $phones = []
	 *
	 * @return string[]
	 */
	public function Phones ($phones = []) {
		if (func_num_args() == 1 && is_array($phones))
			$this->_phones = $phones;

		return $this->_phones;
	}

	/**
	 * @return bool
	 */
	public function Send () {
		try {
			return strlen($this->_message) == 0 || sizeof($this->_phones) == 0 ? false : $this->_config->Provider()->SMSSend($this->_message, $this->_phones);
		}
		catch (QuarkArchException $e) {
			Quark::Log($e->message, Quark::LOG_WARN);

			return false;
		}
	}

	/**
	 * @return float
	 * @throws QuarkArchException
	 */
	public function Cost () {
		try {
			return $this->_config->Provider()->SMSCost($this->_message, $this->_phones);
		}
		catch (QuarkArchException $e) {
			Quark::Log($e->message, Quark::LOG_WARN);

			return false;
		}
	}
}