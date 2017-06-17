<?php
namespace Quark\Extensions\Mail\Providers;

use Quark\QuarkURI;

use Quark\Extensions\Mail\IQuarkMailProvider;

/**
 * Class YandexMail
 *
 * @package Quark\Extensions\Mail\Providers
 */
class YandexMail implements IQuarkMailProvider {
	/**
	 * @param object $ini
	 *
	 * @return void
	 */
	public function MailINI ($ini) {
		// TODO: Implement MailINI() method.
	}

	/**
	 * @param string $username
	 * @param string $password
	 *
	 * @return QuarkURI
	 */
	public function MailSMTP ($username, $password) {
		return QuarkURI::FromURI('ssl://smtp.yandex.ru:587')->User($username, $password);
	}

	/**
	 * @return bool
	 */
	public function MailStartTLS () {
		return true;
	}
}