<?php
namespace Quark\Extensions\Mail\Providers;

use Quark\IQuarkExtensionConfig;
use Quark\Extensions\Mail\IQuarkMailProvider;

use Quark\QuarkURI;

/**
 * Class Google
 *
 * @package Quark\Extensions\Mail\Providers
 */
class Google implements IQuarkMailProvider, IQuarkExtensionConfig {
	public $username;
	public $password;

	/**
	 * @param string $username
	 * @param string $password
	 */
	public function __construct ($username, $password) {
		$this->username = $username;
		$this->password = $password;
	}

	/**
	 * @return QuarkURI
	 */
	public function SMTP () {
		return QuarkURI::FromURI('tls://smtp.gmail.com');
	}
}