<?php
namespace Quark\Extensions\Facebook;

use Quark\IQuarkViewResource;
use Quark\IQuarkInlineViewResource;

/**
 * Class FacebookSharedResource
 *
 * @package Quark\Extensions\Facebook
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

	/**
	 * @var string $_html
	 */
	private $_html = '';

	public function __construct ($type = 'website') {
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
		$og = new self('article');

		$og->Property(self::PROPERTY_OG_URL, $url);
		$og->Property(self::PROPERTY_OG_TITLE, $title);
		$og->Property(self::PROPERTY_OG_DESCRIPTION, func_num_args() < 4 ? $title : $description);
		$og->Property(self::PROPERTY_OG_IMAGE, $image);

		return $og;
	}
}