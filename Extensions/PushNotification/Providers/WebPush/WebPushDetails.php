<?php
namespace Quark\Extensions\PushNotification\Providers\WebPush;

use Quark\Quark;
use Quark\QuarkDTO;
use Quark\QuarkURI;
use Quark\QuarkDate;
use Quark\QuarkDateInterval;
use Quark\QuarkEncryptionKey;
use Quark\QuarkPlainIOProcessor;

use Quark\Extensions\Quark\EncryptionAlgorithms\EncryptionAlgorithmEC;

use Quark\Extensions\JOSE\JOSE;
use Quark\Extensions\JOSE\JOSETokenClaim;

use Quark\Extensions\PushNotification\IQuarkPushNotificationDetails;
use Quark\Extensions\PushNotification\IQuarkPushNotificationDevice;

use Quark\Extensions\PushNotification\PushNotificationDetails;

use Quark\Extensions\PushNotification\Providers\WebPush\ContentEncoding\WebPushContentEncodingAESGCM;

/**
 * Class WebPushDetails
 *
 * https://developers.google.com/web/fundamentals/push-notifications/display-a-notification
 *
 * @package Quark\Extensions\PushNotification\Providers\WebPush
 */
class WebPushDetails implements IQuarkPushNotificationDetails {
	const DEFAULT_TTL = QuarkDateInterval::SECONDS_IN_MONTH;
	const DEFAULT_JWT_EXPIRATION_OFFSET_INTERVAL = QuarkDateInterval::SECONDS_IN_DAY;

	const DIRECTION_AUTO = 'auto';
	const DIRECTION_LEFT_TO_RIGHT = 'ltr';
	const DIRECTION_RIGHT_TO_LEFT = 'rtl';

	/**
	 * @var string[] $_properties
	 */
	private static $_properties = array(
		'Title' => 'title',
		'Body' => 'body',
		'Icon' => 'icon',
		'Badge' => 'badge',
		'Sound' => 'sound',

		'Image' => 'Image',
		'Vibrate' => 'vibrate',
		'Direction' => 'direction',

		'Tag' => 'tag',
		'Data' => 'data',
		'RequireInteraction' => 'requireInteraction',
		'Renotify' => 'renotify',
		'Silent' => 'silent',

		'Actions' => 'actions',

		'Timestamp' => 'timestamp',

		'PreventDisplay' => 'preventDisplay'
	);

	/**
	 * @var string[] $_propertiesMatch
	 */
	private static $_propertiesMatch = array(
		'Title' => 'Title',
		'Body' => 'Body',
		'Icon' => 'Icon',
		'Badge' => 'Badge',
		'Sound' => 'Sound'
	);

	/**
	 * @var string $_payload
	 */
	private $_payload;

	/**
	 * @var int $_ttl = self::DEFAULT_TTL
	 */
	private $_ttl = self::DEFAULT_TTL;

	/**
	 * @var string $_urgency
	 */
	private $_urgency;

	/**
	 * @var string $_topic
	 */
	private $_topic;

	/**
	 * @var IWebPushContentEncoding $_encoding
	 */
	private $_encoding;

	/**
	 * @var QuarkEncryptionKey $_keyVAPID
	 */
	private $_keyVAPID;

	/**
	 * @var QuarkEncryptionKey $_keyDH
	 */
	private $_keyDH;

	/**
	 * @var string $_jwtAudience
	 */
	private $_jwtAudience;

	/**
	 * @var string $_jwtSubject
	 */
	private $_jwtSubject;

	/**
	 * @var QuarkDate $_jwtExpiration
	 */
	private $_jwtExpiration;

	/**
	 * @var string $_title
	 */
	private $_title;

	/**
	 * @var string $_body
	 */
	private $_body;

	/**
	 * @var string $_icon
	 */
	private $_icon;

	/**
	 * @var string $_badge
	 */
	private $_badge;

	/**
	 * @var string $_sound
	 */
	private $_sound;

	/**
	 * @var string $_image
	 */
	private $_image;

	/**
	 * @var int[] $_vibrate
	 */
	private $_vibrate;

	/**
	 * @var string $_direction
	 */
	private $_direction;

	/**
	 * @var string $_tag
	 */
	private $_tag;

	/**
	 * @var $_data
	 */
	private $_data;

	/**
	 * @var bool $_requireInteraction
	 */
	private $_requireInteraction;

	/**
	 * @var bool $_renotify
	 */
	private $_renotify;

	/**
	 * @var bool $_silent
	 */
	private $_silent;

	/**
	 * @var WebPushAction[]
	 */
	private $_actions;

	/**
	 * @var int $_timestamp
	 */
	private $_timestamp;

	/**
	 * @var bool $_preventDisplay
	 */
	private $_preventDisplay;

	/**
	 * @param IWebPushContentEncoding $encoding = null
	 */
	public function __construct (IWebPushContentEncoding $encoding = null) {
		if (func_num_args() == 0)
			$encoding = new WebPushContentEncodingAESGCM(); // TODO: maybe need recognizing

		$this->Encoding($encoding);
	}

	/**
	 * @param string $payload = ''
	 *
	 * @return string
	 */
	public function Payload ($payload = '') {
		if (func_num_args() != 0)
			$this->_payload = $payload;

		return $this->_payload;
	}

	/**
	 * @param int $seconds = self::DEFAULT_TTL
	 *
	 * @return int
	 */
	public function TTL ($seconds = self::DEFAULT_TTL) {
		if (func_num_args() != 0)
			$this->_ttl = $seconds;

		return $this->_ttl;
	}

	/**
	 * @param string $urgency = null
	 *
	 * @return string
	 */
	public function Urgency ($urgency = null) {
		if (func_num_args() != 0)
			$this->_urgency = $urgency;

		return $this->_urgency;
	}

	/**
	 * @param string $topic = null
	 *
	 * @return string
	 */
	public function Topic ($topic = null) {
		if (func_num_args() != 0)
			$this->_topic = $topic;

		return $this->_topic;
	}

	/**
	 * @param IWebPushContentEncoding $encoding = null
	 *
	 * @return IWebPushContentEncoding
	 */
	public function &Encoding (IWebPushContentEncoding $encoding = null) {
		if (func_num_args() != 0)
			$this->_encoding = $encoding;

		return $this->_encoding;
	}

	/**
	 * @param QuarkEncryptionKey $key = null
	 *
	 * @return QuarkEncryptionKey
	 */
	public function &KeyVAPID (QuarkEncryptionKey $key = null) {
		if (func_num_args() != 0)
			$this->_keyVAPID = $key;

		return $this->_keyVAPID;
	}

	/**
	 * @param QuarkEncryptionKey $key = null
	 *
	 * @return QuarkEncryptionKey
	 */
	public function &KeyDH (QuarkEncryptionKey $key = null) {
		if (func_num_args() != 0)
			$this->_keyDH = $key;

		return $this->_keyDH;
	}

	/**
	 * @param string $audience = null
	 *
	 * @return string
	 */
	public function JWTAudience ($audience = null) {
		if (func_num_args() != 0)
			$this->_jwtAudience = $audience;

		return $this->_jwtAudience;
	}

	/**
	 * @param string $subject = null
	 *
	 * @return string
	 */
	public function JWTSubject($subject = null) {
		if (func_num_args() != 0)
			$this->_jwtSubject = $subject;

		return $this->_jwtSubject;
	}

	/**
	 * @param QuarkDate $expiration = null
	 *
	 * @return QuarkDate
	 */
	public function JWTExpiration (QuarkDate $expiration = null) {
		if (func_num_args() != 0)
			$this->_jwtExpiration = $expiration;

		return $this->_jwtExpiration;
	}

	/**
	 * @param string $title = null
	 *
	 * @return string
	 */
	public function Title ($title = null) {
		if (func_num_args() != 0)
			$this->_title = $title;

		return $this->_title;
	}

	/**
	 * @param string $body = null
	 *
	 * @return string
	 */
	public function Body ($body = null) {
		if (func_num_args() != 0)
			$this->_body = $body;

		return $this->_body;
	}

	/**
	 * @param string $icon = null
	 *
	 * @return string
	 */
	public function Icon ($icon = null) {
		if (func_num_args() != 0)
			$this->_icon = $icon;

		return $this->_icon;
	}

	/**
	 * @param string $badge = null
	 *
	 * @return string
	 */
	public function Badge ($badge = null) {
		if (func_num_args() != 0)
			$this->_badge = $badge;

		return $this->_badge;
	}

	/**
	 * @param string $sound = null
	 *
	 * @return string
	 */
	public function Sound ($sound = null) {
		if (func_num_args() != 0)
			$this->_sound = $sound;

		return $this->_sound;
	}

	/**
	 * @param string $image = null
	 *
	 * @return string
	 */
	public function Image ($image = null) {
		if (func_num_args() != 0)
			$this->_image = $image;

		return $this->_image;
	}

	/**
	 * @param int[] $vibrate = null
	 *
	 * @return int[]
	 */
	public function Vibrate ($vibrate = null) {
		if (func_num_args() != 0)
			$this->_vibrate = $vibrate;

		return $this->_vibrate;
	}

	/**
	 * @param string $direction = null
	 *
	 * @return string
	 */
	public function Direction ($direction = null) {
		if (func_num_args() != 0)
			$this->_direction = $direction;

		return $this->_direction;
	}

	/**
	 * @param string $tag = null
	 *
	 * @return string
	 */
	public function Tag ($tag = null) {
		if (func_num_args() != 0)
			$this->_tag = $tag;

		return $this->_tag;
	}

	/**
	 * @param $data = null
	 *
	 * @return mixed
	 */
	public function Data ($data = null) {
		if (func_num_args() != 0)
			$this->_data = $data;

		return $this->_data;
	}

	/**
	 * @param bool $requireInteraction = null
	 *
	 * @return bool
	 */
	public function RequireInteraction ($requireInteraction = null) {
		if (func_num_args() != 0)
			$this->_requireInteraction = $requireInteraction;

		return $this->_requireInteraction;
	}

	/**
	 * @param bool $renotify = null
	 *
	 * @return bool
	 */
	public function Renotify ($renotify = null) {
		if (func_num_args() != 0)
			$this->_renotify = $renotify;

		return $this->_renotify;
	}

	/**
	 * @param bool $silent = null
	 *
	 * @return bool
	 */
	public function Silent ($silent = null) {
		if (func_num_args() != 0)
			$this->_silent = $silent;

		return $this->_silent;
	}

	/**
	 * @return WebPushAction[]
	 */
	public function &Actions () {
		return $this->_actions;
	}

	/**
	 * @param WebPushAction $action = null
	 *
	 * @return WebPushDetails
	 */
	public function Action (WebPushAction $action = null) {
		if ($action != null)
			$this->_actions[] = $action;

		return $this;
	}

	/**
	 * @param int $timestamp = null
	 *
	 * @return int
	 */
	public function Timestamp ($timestamp = null) {
		if (func_num_args() != 0)
			$this->_timestamp = $timestamp;

		return $this->_timestamp;
	}

	/**
	 * @param bool $preventDisplay = false
	 *
	 * @return bool
	 */
	public function PreventDisplay ($preventDisplay = false) {
		if (func_num_args() != 0)
			$this->_preventDisplay = $preventDisplay;

		return $this->_preventDisplay;
	}

	/**
	 * @param object|array $payload
	 * @param IQuarkPushNotificationDevice|WebPushDevice $device = null
	 *
	 * @return mixed
	 */
	public function PushNotificationDetailsData ($payload, IQuarkPushNotificationDevice $device = null) {
		if ($this->_data === null && $payload !== null)
			$this->Data($payload);

		$payload = null;
		$value = null;
		$buffer = null;

		foreach (self::$_properties as $property => &$key) {
			if ($payload === null) $payload = array();

			$value = $this->$property();

			if (is_array($value) && $property != 'Data') {
				$buffer = null;

				foreach ($value as $i => &$item) {
					$value[$i] = $item instanceof WebPushAction ? $item->Data() : $item;

					if ($value[$i] === null) continue;
					if ($buffer === null) $buffer = array();

					$buffer[] = $value[$i];
				}

				$value = $buffer;
			}

			if ($value === null) continue;
			if ($payload === null) $payload = array();

			$payload[$key] = $value;
		}

		unset($i, $item, $property, $key, $value, $buffer);

		if ($this->_payload === null && $payload !== null)
			$this->_payload = json_encode($payload);

		$out = QuarkDTO::ForPOST(new QuarkPlainIOProcessor());
		$out->Header(QuarkDTO::HEADER_CONTENT_TYPE, 'application/octet-stream');

		if ($this->_ttl !== null)
			$out->Header(WebPush::HEADER_TTL, $this->_ttl);

		if ($this->_urgency !== null)
			$out->Header(WebPush::HEADER_URGENCY, $this->_urgency);

		if ($this->_topic !== null)
			$out->Header(WebPush::HEADER_TOPIC, $this->_topic);

		if ($this->_keyDH == null)
			$this->_keyDH = EncryptionAlgorithmEC::KeyGenerate(EncryptionAlgorithmEC::OPENSSL_CURVE_PRIME256V1);

		$salt = null;
		$payload = $this->PayloadEncrypted($device, $salt);

		if ($payload == null) {
			Quark::Log('[PushNotification:WebPush] Can not encrypt payload for given device ' . openssl_error_string(), Quark::LOG_WARN);

			return null;
		}

		$out->Data($this->_encoding->WebPushContentEncodingPayloadPrefix($this->_keyDH, $salt) . $payload);

		$jwt = new JOSETokenClaim();
		$jwt->Audience($this->_jwtAudience == null ? QuarkURI::FromURI($device->Endpoint())->URI(false, false) : $this->_jwtAudience);
		$jwt->Subject($this->_jwtSubject == null ? Quark::Config()->WebHost()->URI(false, false) : $this->_jwtSubject);
		$jwt->DateEdgeEnd($this->_jwtExpiration == null
			? QuarkDate::GMTNow()->Offset('+' . (self::DEFAULT_JWT_EXPIRATION_OFFSET_INTERVAL / 2) . ' seconds')
			: $this->_jwtExpiration
		);

		$jose = JOSE::JWS()
			->PayloadClaim($jwt, false)
			->KeyApply($this->_keyVAPID);

		return $this->_encoding->WebPushContentEncodingRequestDTO($this->_keyVAPID, $this->_keyDH, $out, $jose, $salt);
	}

	/**
	 * @param PushNotificationDetails $details
	 *
	 * @return mixed
	 */
	public function PushNotificationDetailsFromDetails (PushNotificationDetails $details) {
		foreach (self::$_propertiesMatch as $propertyPublic => &$propertyOwn)
			$this->$propertyOwn($details->$propertyPublic());

		unset($propertyPublic, $propertyOwn);
	}

	/**
	 * @param WebPushDevice $device = null
	 * @param string $salt = null
	 *
	 * @return string
	 */
	public function PayloadEncrypted (WebPushDevice $device = null, &$salt = null) {
		if ($this->_encoding == null || $device == null) return null;

		if ($salt === null)
			$salt = openssl_random_pseudo_bytes(WebPush::LENGTH_SALT);

		$devicePublic = $device->KeyPublic();
		$deviceAuth = QuarkURI::Base64Decode($device->KeyAuth());

		$shared = str_pad($this->_keyDH->SharedSecretOpenSSL($devicePublic), 32, chr(0), STR_PAD_LEFT);
		$contentEncoding = $this->_encoding->WebPushContentEncodingType();
		$context = $this->_encoding->WebPushContentEncodingHKDFContext($this->_keyDH, $devicePublic);

		$componentIKM = $deviceAuth == '' ? $shared : QuarkEncryptionKey::HKDF(
			$shared,
			WebPush::LENGTH_COMPONENT_IKM,
			$this->_encoding->WebPushContentEncodingHKDFIKM($this->_keyDH, $devicePublic),
			$deviceAuth
		);

		$payload = $this->_encoding->WebPushContentEncodingPayload($this->_payload);

		return openssl_encrypt(
			$payload,
			WebPush::PAYLOAD_ENCRYPTION_CIPHER_AES128GCM,
			QuarkEncryptionKey::HKDF(
				$componentIKM,
				WebPush::LENGTH_COMPONENT_CONTENT_ENCRYPTION_KEY,
				$this->_encoding->WebPushContentEncodingHKDFInfo($context, $contentEncoding),
				$salt
			),
			OPENSSL_RAW_DATA,
			QuarkEncryptionKey::HKDF(
				$componentIKM,
				WebPush::LENGTH_COMPONENT_NONCE,
				$this->_encoding->WebPushContentEncodingHKDFInfo($context, WebPush::HDKF_INFO_TYPE_NONCE),
				$salt
			),
			$tag
		) . $tag;
	}
}