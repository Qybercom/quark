<?php
namespace Quark\Extensions\Mail\Providers;

use Quark\QuarkURI;

use Quark\Extensions\Mail\IQuarkMailProvider;

/**
 * Class MicrosoftMail
 *
 * @package Quark\Extensions\Mail\Providers
 */
class MicrosoftMail implements IQuarkMailProvider {
	/**
	 * @param string $username
	 * @param string $password
	 *
	 * @return QuarkURI
	 */
	public function MailSMTP ($username, $password) {
		return QuarkURI::FromURI('ssl://smtp.live.com:587')->User($username, $password);
	}
}