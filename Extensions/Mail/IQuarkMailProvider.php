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
	 * @return QuarkURI
	 */
	public function SMTP();
}