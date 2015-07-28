<?php
namespace Quark\Extensions\Mail;

use Quark\IQuarkExtensionConfig;

use Quark\QuarkURI;

/**
 * Interface IQuarkMailProvider
 *
 * @package Quark\Extensions\Mail
 */
interface IQuarkMailProvider extends IQuarkExtensionConfig {
	/**
	 * @return QuarkURI
	 */
	public function SMTP();

	/**
	 * @return string
	 */
	public function From();
}