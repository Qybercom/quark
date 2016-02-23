<?php
namespace Quark\Extensions\CDN;

/**
 * Interface IQuarkCDNProvider
 *
 * @package Quark\Extensions\CDN
 */
interface IQuarkCDNProvider {
	/**
	 * @param string $appId
	 * @param string $appSecret
	 *
	 * @return mixed
	 */
	public function CDNApplication($appId, $appSecret);
}