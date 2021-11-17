<?php
namespace Quark\Extensions\PushNotification\Providers\GoogleFCM;

use Quark\Extensions\PushNotification\IQuarkPushNotificationDetails;
use Quark\Extensions\PushNotification\IQuarkPushNotificationDevice;

use Quark\Extensions\PushNotification\PushNotificationDetails;

/**
 * Class GoogleFCMDetails
 *
 * @package Quark\Extensions\PushNotification\Providers\GoogleFCM
 */
class GoogleFCMDetails implements IQuarkPushNotificationDetails {
	const SOUND_DEFAULT = 'default';

	/**
	 * @var string[] $_properties
	 */
	private static $_properties = array(
		'Title' => 'title',
		'TitleLocalizationKey' => 'title_loc_key',
		'TitleLocalizationArgs' => 'title_loc_args',
		'Subtitle' => 'subtitle',
		'Body' => 'body',
		'BodyLocalizationKey' => 'body_loc_key',
		'BodyLocalizationArgs' => 'body_loc_args',
		'Icon' => 'icon',
		'Badge' => 'badge',
		'Sound' => 'sound',
		'Tag' => 'tag',
		'Color' => 'color',
		'ClickAction' => 'click_action',
		'AndroidChannelID' => 'android_channel_id'
	);

	/**
	 * @var string[] $_propertiesMatch
	 */
	private static $_propertiesMatch = array(
		'Title' => 'Title',
		'Subtitle' => 'Subtitle',
		'Body' => 'Body',
		'Icon' => 'Icon',
		'Badge' => 'Badge',
		'Sound' => 'Sound'
	);

	/**
	 * @var string $_title = null
	 */
	private $_title = null;

	/**
	 * @var string $_titleLocalizationKey = null
	 */
	private $_titleLocalizationKey = null;

	/**
	 * @var string $_titleLocalizationArgs = null
	 */
	private $_titleLocalizationArgs = null;

	/**
	 * @var string $_subtitle = null
	 */
	private $_subtitle = null;

	/**
	 * @var string $_body = null
	 */
	private $_body = null;

	/**
	 * @var string $_bodyLocalizationKey = null
	 */
	private $_bodyLocalizationKey = null;

	/**
	 * @var string $_bodyLocalizationArgs = null
	 */
	private $_bodyLocalizationArgs = null;

	/**
	 * @var string $_icon = null
	 */
	private $_icon = null;

	/**
	 * @var int $_badge = 1
	 */
	private $_badge = 1;

	/**
	 * @var string $_sound = self::SOUND_DEFAULT
	 */
	private $_sound = self::SOUND_DEFAULT;

	/**
	 * @var string $_tag = null
	 */
	private $_tag = null;

	/**
	 * @var string $_color = null
	 */
	private $_color = null;

	/**
	 * @var string $_clickAction = null
	 */
	private $_clickAction = null;

	/**
	 * @var string $_androidChannelID = null
	 */
	private $_androidChannelID = null;

	/**
	 * @param string $title = null
	 * @param string $body = null
	 * @param string $icon = null
	 */
	public function __construct ($title = null, $body = null, $icon = null) {
		$this->Title($title);
		$this->Body($body);
		$this->Icon($icon);
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
	 * @param string $titleLocalizationKey = null
	 *
	 * @return string
	 */
	public function TitleLocalizationKey ($titleLocalizationKey = null) {
		if (func_num_args() != 0)
			$this->_titleLocalizationKey = $titleLocalizationKey;

		return $this->_titleLocalizationKey;
	}

	/**
	 * @param string $titleLocalizationArgs = null
	 *
	 * @return string
	 */
	public function TitleLocalizationArgs ($titleLocalizationArgs = null) {
		if (func_num_args() != 0)
			$this->_titleLocalizationArgs = $titleLocalizationArgs;

		return $this->_titleLocalizationArgs;
	}

	/**
	 * @param string $subtitle = null
	 *
	 * @return string
	 */
	public function Subitle ($subtitle = null) {
		if (func_num_args() != 0)
			$this->_subtitle = $subtitle;

		return $this->_subtitle;
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
	 * @param string $bodyLocalizationKey = null
	 *
	 * @return string
	 */
	public function BodyLocalizationKey ($bodyLocalizationKey = null) {
		if (func_num_args() != 0)
			$this->_bodyLocalizationKey = $bodyLocalizationKey;

		return $this->_bodyLocalizationKey;
	}

	/**
	 * @param string $bodyLocalizationArgs = null
	 *
	 * @return string
	 */
	public function BodyLocalizationArgs ($bodyLocalizationArgs = null) {
		if (func_num_args() != 0)
			$this->_bodyLocalizationArgs = $bodyLocalizationArgs;

		return $this->_bodyLocalizationArgs;
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
	 * @param int $badge = 1
	 *
	 * @return int
	 */
	public function Badge ($badge = 1) {
		if (func_num_args() != 0)
			$this->_badge = $badge;

		return $this->_badge;
	}

	/**
	 * @param string $sound = self::SOUND_DEFAULT
	 *
	 * @return string
	 */
	public function Sound ($sound = self::SOUND_DEFAULT) {
		if (func_num_args() != 0)
			$this->_sound = $sound;

		return $this->_sound;
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
	 * @param string $color = null
	 *
	 * @return string
	 */
	public function Color ($color = null) {
		if (func_num_args() != 0)
			$this->_color = $color;

		return $this->_color;
	}

	/**
	 * @param string $clickAction = null
	 *
	 * @return string
	 */
	public function ClickAction ($clickAction = null) {
		if (func_num_args() != 0)
			$this->_clickAction = $clickAction;

		return $this->_clickAction;
	}

	/**
	 * @param string $androidChannelID = null
	 *
	 * @return string
	 */
	public function AndroidChannelID ($androidChannelID = null) {
		if (func_num_args() != 0)
			$this->_androidChannelID = $androidChannelID;

		return $this->_androidChannelID;
	}

	/**
	 * @param object|array $payload
	 * @param IQuarkPushNotificationDevice $device = null
	 *
	 * @return mixed
	 */
	public function PushNotificationDetailsData ($payload, IQuarkPushNotificationDevice $device = null) {
		$value = null;

		foreach (self::$_properties as $property => &$field) {
			$value = $this->$property();
			if ($value === null) continue;

			if (!isset($payload['notification']))
				$payload['notification'] = array();

			$payload['notification'][$field] = $value;
		}

		unset($property, $field, $value);

		return $payload;
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
}