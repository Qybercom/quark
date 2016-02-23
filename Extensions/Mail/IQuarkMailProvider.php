<?php
namespace Quark\Extensions\Mail;

use Quark\QuarkURI;

/**
 * Interface IQuarkMailProvider
 *
 * @package Quark\Extensions\Mail
 */
interface IQuarkMailProvider {
	/**
	 * @param string $username
	 * @param string $password
	 *
	 * @return QuarkURI
	 */
	public function MailSMTP($username, $password);
}