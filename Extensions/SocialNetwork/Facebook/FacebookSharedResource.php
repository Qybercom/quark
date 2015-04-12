<?php
namespace Quark\Extensions\SocialNetwork\Facebook;

use Quark\IQuarkViewResource;
use Quark\IQuarkInlineViewResource;

use Quark\Quark;

/**
 * Class FacebookSharedResource
 *
 * @package Quark\Extensions\SocialNetwork\Facebook
 */
class FacebookSharedResource implements IQuarkViewResource, IQuarkInlineViewResource {
	const PROPERTY_FB_APP_ID = 'fb:app_id';
	const PROPERTY_OG_URL = 'og:url';
	const PROPERTY_OG_TYPE = 'og:type';
	const PROPERTY_OG_TITLE = 'og:title';
	const PROPERTY_OG_DESCRIPTION = 'og:description';
	const PROPERTY_OG_IMAGE = 'og:image';
	const PROPERTY_ARTICLE_AUTHOR = 'article:author';
	const PROPERTY_ARTICLE_PUBLISHER = 'article:publisher';
	const PROPERTY_ARTICLE_PUBLISHED = 'article:published';
	const PROPERTY_ARTICLE_SECTION = 'article:section';

	const TYPE_WEBSITE = 'website';
	const TYPE_ARTICLE = 'article';
	const TYPE_BOOKS_AUTHOR = 'books.author';
	const TYPE_BOOKS_BOOK = 'books.book';
	const TYPE_BOOKS_GENRE = 'books.genre';
	const TYPE_BUSINESS_BUSINESS = 'business.business';
	const TYPE_FITNESS_COURSE = 'fitness.course';
	const TYPE_GAME_ACHIEVEMENT = 'game.achievement';
	const TYPE_MUSIC_ALBUM = 'music.album';
	const TYPE_MUSIC_PLAYLIST = 'music.playlist';
	const TYPE_MUSIC_RADIO_STATION = 'music.radio_station';
	const TYPE_MUSIC_SONG = 'music.song';
	const TYPE_PLACE = 'place';
	const TYPE_PRODUCT = 'product';
	const TYPE_PRODUCT_GROUP = 'product.group';
	const TYPE_PRODUCT_ITEM = 'product.item';
	const TYPE_PROFILE = 'profile';
	const TYPE_RESTAURANT_MENU = 'restaurant.menu';
	const TYPE_RESTAURANT_MENU_ITEM = 'restaurant.menu_item';
	const TYPE_RESTAURANT_MENU_SECTION = 'restaurant.menu_section';
	const TYPE_RESTAURANT_RESTAURANT = 'restaurant.restaurant';
	const TYPE_VIDEO_EPISODE = 'video.episode';
	const TYPE_VIDEO_MOVIE = 'video.movie';
	const TYPE_VIDEO_OTHER = 'video.other';
	const TYPE_VIDEO_TV_SHOW = 'video.tv_show';

	/**
	 * @var string $_html
	 */
	private $_html = '';

	/**
	 * @param string $type = self::TYPE_WEBSITE
	 */
	public function __construct ($type = self::TYPE_WEBSITE) {
		$this->Property(self::PROPERTY_OG_TYPE, $type);
	}

	/**
	 * @return string
	 */
	public function Location () {
		// TODO: Implement Location() method.
	}

	/**
	 * @return string
	 */
	public function Type () {
		// TODO: Implement Type() method.
	}

	/**
	 * @param string $property
	 * @param string $content
	 *
	 * @return FacebookSharedResource
	 */
	public function Property ($property, $content) {
		$this->_html .= '<meta property="' . $property . '" content="' . $content . '" />';
		return $this;
	}

	/**
	 * @param $config
	 *
	 * @return FacebookSharedResource
	 */
	public function App ($config) {
		return $this->Property(self::PROPERTY_FB_APP_ID, Quark::Config()->Extension($config)->appId);
	}

	/**
	 * @return string
	 */
	public function HTML () {
		return $this->_html;
	}

	/**
	 * @param string $url
	 * @param string $title
	 * @param string $image
	 * @param string $description
	 *
	 * @return FacebookSharedResource
	 */
	public static function Article ($url, $title, $image, $description = '') {
		$og = new self(self::TYPE_ARTICLE);

		$og->Property(self::PROPERTY_OG_URL, $url);
		$og->Property(self::PROPERTY_OG_TITLE, $title);
		$og->Property(self::PROPERTY_OG_DESCRIPTION, func_num_args() < 4 ? $title : $description);
		$og->Property(self::PROPERTY_OG_IMAGE, $image);

		return $og;
	}
}