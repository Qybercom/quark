<?php
namespace Quark\Extensions\Quark\MicroService;

use Quark\QuarkKeyValuePair;

/**
 * Interface IQuarkMicroServiceProvider
 *
 * @package Quark\Extensions\Quark\MicroService
 */
interface IQuarkMicroServiceProvider {
	/**
	 * @param QuarkKeyValuePair $client
	 * @param QuarkKeyValuePair $app
	 *
	 * @return bool
	 */
	public function MicroServiceAuth(QuarkKeyValuePair $client, QuarkKeyValuePair $app);

	/**
	 * @param string $appId
	 * @param QuarkKeyValuePair $client
	 *
	 * @return string
	 */
	public function MicroServiceToken($appId, QuarkKeyValuePair $client);

	/**
	 * @param string $appId
	 *
	 * @return string
	 */
	public function MicroServiceEndpoint($appId);
}