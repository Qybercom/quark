<?php
namespace Quark\Extensions\Mail\Providers;

use Quark\QuarkURI;

use Quark\Extensions\Mail\IQuarkMailProvider;

/**
 * Class GoogleMail
 *
 * @package Quark\Extensions\Mail\Providers
 */
class GoogleMail implements IQuarkMailProvider {
	/**
	 * @param string $username
	 * @param string $password
	 *
	 * @return QuarkURI
	 */
	public function MailSMTP ($username, $password) {
		return QuarkURI::FromURI('ssl://smtp.gmail.com:465')->User($username, $password);
	}
}