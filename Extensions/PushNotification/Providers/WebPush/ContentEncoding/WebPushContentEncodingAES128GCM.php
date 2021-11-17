<?php
namespace Quark\Extensions\PushNotification\Providers\WebPush\ContentEncoding;

use Quark\Extensions\Quark\EncryptionAlgorithms\EncryptionAlgorithmEC;
use Quark\QuarkDTO;
use Quark\QuarkURI;
use Quark\QuarkEncryptionKey;
use Quark\QuarkKeyValuePair;

use Quark\Extensions\JOSE\JOSE;

use Quark\Extensions\PushNotification\Providers\WebPush\IWebPushContentEncoding;
use Quark\Extensions\PushNotification\Providers\WebPush\WebPush;

/**
 * Class WebPushContentEncodingAES128GCM
 *
 * @package Quark\Extensions\PushNotification\Providers\WebPush\ContentEncoding
 */
class WebPushContentEncodingAES128GCM implements IWebPushContentEncoding {
	const TYPE = 'aes128gcm';

	const IKM_PREFIX = 'WebPush: info';

	/**
	 * @return string
	 */
	public function WebPushContentEncodingType () {
		return self::TYPE;
	}

	/**
	 * @param string $context
	 * @param string $type
	 *
	 * @return string
	 */
	public function WebPushContentEncodingHKDFInfo ($context, $type) {
		return WebPush::PREFIX_CONTENT_ENCODING . $type . chr(0);
	}

	/**
	 * @param QuarkEncryptionKey $keyServer
	 * @param QuarkEncryptionKey $keyClient
	 *
	 * @return string
	 */
	public function WebPushContentEncodingHKDFIKM (QuarkEncryptionKey $keyServer, QuarkEncryptionKey $keyClient) {
		return self::IKM_PREFIX . chr(0) . $keyClient->ValueAsymmetricPublic() . $keyServer->ValueAsymmetricPublic();
	}

	/**
	 * @param QuarkEncryptionKey $keyServer
	 * @param QuarkEncryptionKey $keyClient
	 *
	 * @return string
	 */
	public function WebPushContentEncodingHKDFContext (QuarkEncryptionKey $keyServer, QuarkEncryptionKey $keyClient) {
		return '';
	}

	/**
	 * @param QuarkEncryptionKey $keyVAPID
	 * @param QuarkEncryptionKey $keyDH
	 * @param QuarkDTO $request
	 * @param JOSE $jose
	 * @param string $salt
	 *
	 * @return QuarkDTO
	 */
	public function WebPushContentEncodingRequestDTO (QuarkEncryptionKey $keyVAPID, QuarkEncryptionKey $keyDH, QuarkDTO $request, JOSE $jose, $salt) {
		$request->Authorization(new QuarkKeyValuePair('vapid', 't=' . $jose->CompactSerialize() . ', k=' . QuarkURI::Base64Encode($keyVAPID->ValueAsymmetricPublic())));

		$request->Header(QuarkDTO::HEADER_CONTENT_ENCODING, self::TYPE);

		return $request;
	}

	/**
	 * @param string $payload
	 *
	 * @return string
	 */
	public function WebPushContentEncodingPayload ($payload) {
		$payloadLen = strlen($payload);
		$padLen = 3052 - $payloadLen;

		return str_pad($payload . chr(2), $padLen + $payloadLen, chr(0), STR_PAD_RIGHT);
	}

	/**
	 * @param QuarkEncryptionKey $keyDH
	 * @param string $salt
	 *
	 * @return string
	 */
	public function WebPushContentEncodingPayloadPrefix (QuarkEncryptionKey $keyDH, $salt) {
		$localKey = EncryptionAlgorithmEC::SECEncode($keyDH);

		return $salt . pack('N*', 4096) . pack('C*', strlen($localKey)) . $localKey;
	}
}