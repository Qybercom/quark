<?php
namespace Quark\Extensions\CDN;

use Quark\QuarkFile;

/**
 * Interface IQuarkCDNProvider
 *
 * @package Quark\Extensions\CDN
 */
interface IQuarkCDNProvider {
	/**
	 * @param string $appId
	 * @param string $appSecret
	 * @param object $ini
	 *
	 * @return mixed
	 */
	public function CDNApplication($appId, $appSecret, $ini);

	/**
	 * @param string $id
	 *
	 * @return string
	 */
	public function CDNResourceURL($id);

	/**
	 * @param QuarkFile $file
	 *
	 * @return string
	 */
	public function CDNResourceCreate(QuarkFile $file);

	/**
	 * @param string $id
	 * @param QuarkFile $file
	 *
	 * @return bool
	 */
	public function CDNResourceUpdate($id, QuarkFile $file);

	/**
	 * @param string $id
	 *
	 * @return bool
	 */
	public function CDNResourceDelete($id);
}