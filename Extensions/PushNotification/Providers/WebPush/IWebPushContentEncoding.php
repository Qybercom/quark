<?php
namespace Quark\Extensions\PushNotification\Providers\WebPush;

use Quark\Extensions\JOSE\JOSE;
use Quark\QuarkDTO;
use Quark\QuarkEncryptionKey;

/**
 * Interface IWebPushContentEncoding
 *
 * @package Quark\Extensions\PushNotification\Providers\WebPush
 */
interface IWebPushContentEncoding {
	/**
	 * @return string
	 */
	public function WebPushContentEncodingType();

	/**
	 * @param string $context
	 * @param string $type
	 *
	 * @return string
	 */
	public function WebPushContentEncodingHKDFInfo($context, $type);

	/**
	 * @param QuarkEncryptionKey $keyServer
	 * @param QuarkEncryptionKey $keyClient
	 *
	 * @return string
	 */
	public function WebPushContentEncodingHKDFIKM(QuarkEncryptionKey $keyServer, QuarkEncryptionKey $keyClient);

	/**
	 * @param QuarkEncryptionKey $keyServer
	 * @param QuarkEncryptionKey $keyClient
	 *
	 * @return string
	 */
	public function WebPushContentEncodingHKDFContext(QuarkEncryptionKey $keyServer, QuarkEncryptionKey $keyClient);

	/**
	 * @param QuarkEncryptionKey $keyVAPID
	 * @param QuarkEncryptionKey $keyDH
	 * @param QuarkDTO $request
	 * @param JOSE $jose
	 * @param string $salt
	 *
	 * @return QuarkDTO
	 */
	public function WebPushContentEncodingRequestDTO(QuarkEncryptionKey $keyVAPID, QuarkEncryptionKey $keyDH, QuarkDTO $request, JOSE $jose, $salt);

	/**
	 * @param string $payload
	 *
	 * @return string
	 */
	public function WebPushContentEncodingPayload($payload);

	/**
	 * @param QuarkEncryptionKey $keyDH
	 * @param string $salt
	 *
	 * @return string
	 */
	public function WebPushContentEncodingPayloadPrefix(QuarkEncryptionKey $keyDH, $salt);
}