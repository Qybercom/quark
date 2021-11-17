<?php
namespace Quark\Extensions\PushNotification\Providers\WebPush\ContentEncoding;

use Quark\Quark;
use Quark\QuarkDTO;
use Quark\QuarkURI;
use Quark\QuarkKeyValuePair;
use Quark\QuarkEncryptionKey;

use Quark\Extensions\Quark\EncryptionAlgorithms\EncryptionAlgorithmEC;

use Quark\Extensions\JOSE\JOSE;
use Quark\Extensions\JOSE\Algorithms\JOSEAlgorithmEC;

use Quark\Extensions\PushNotification\Providers\WebPush\IWebPushContentEncoding;
use Quark\Extensions\PushNotification\Providers\WebPush\WebPush;

/**
 * Class WebPushContentEncodingAESGCM
 *
 * @package Quark\Extensions\PushNotification\Providers\WebPush\ContentEncoding
 */
class WebPushContentEncodingAESGCM implements IWebPushContentEncoding {
	const TYPE = 'aesgcm';

	const IKM_PREFIX = 'Content-Encoding: auth';

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
		return strlen($context) == 135
			? WebPush::PREFIX_CONTENT_ENCODING . $type . chr(0) . JOSEAlgorithmEC::CURVE_P_256 . $context
			: null;
	}

	/**
	 * @param QuarkEncryptionKey $keyServer
	 * @param QuarkEncryptionKey $keyClient
	 *
	 * @return string
	 */
	public function WebPushContentEncodingHKDFIKM (QuarkEncryptionKey $keyServer, QuarkEncryptionKey $keyClient) {
		return self::IKM_PREFIX . chr(0);
	}

	/**
	 * https://github.com/web-push-libs/web-push-php/blob/d87e9e3034ca2b95b1822b1b335e7761c14b89f6/src/Encryption.php
	 *
	 * @param QuarkEncryptionKey $keyServer
	 * @param QuarkEncryptionKey $keyClient
	 *
	 * @return string
	 */
	public function WebPushContentEncodingHKDFContext (QuarkEncryptionKey $keyServer, QuarkEncryptionKey $keyClient) {
		$len = chr(0) . 'A'; // 65 as Uint16BE

		return chr(0) . $len. $keyClient->ValueAsymmetricPublic() . $len . EncryptionAlgorithmEC::SECEncode($keyServer);
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
		$request->Authorization(new QuarkKeyValuePair('WebPush', $jose->CompactSerialize()));

		$request->Header(QuarkDTO::HEADER_CONTENT_ENCODING, self::TYPE);

		$request->Header(WebPush::HEADER_ENCRYPTION, 'salt=' . QuarkURI::Base64Encode($salt));
		$request->Header(WebPush::HEADER_CRYPTO_KEY, implode(';', array(
			'dh=' . QuarkURI::Base64Encode(EncryptionAlgorithmEC::SECEncode($keyDH)), // that's why it must be generated like salt each time
			'p256ecdsa=' . QuarkURI::Base64Encode(EncryptionAlgorithmEC::SECEncode($keyVAPID))
		)));

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

		return ''
			 . pack('n*', $padLen)
			 . str_pad($payload, $padLen + $payloadLen, chr(0), STR_PAD_LEFT);
	}

	/**
	 * @param QuarkEncryptionKey $keyDH
	 * @param string $salt
	 *
	 * @return string
	 */
	public function WebPushContentEncodingPayloadPrefix (QuarkEncryptionKey $keyDH, $salt) {
		return '';
	}
}