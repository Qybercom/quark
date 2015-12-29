<?php
namespace Quark\ViewResources\TwitterEmoji;

use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkJSViewResourceType;

/**
 * Class TwitterEmoji
 *
 * https://github.com/twitter/twemoji
 *
 * @package Quark\ViewResources\TwitterEmoji
 */
class TwitterEmoji implements IQuarkViewResource {
	/**
	 * @return IQuarkViewResourceType
	 */
	public function Type () {
		return new QuarkJSViewResourceType();
	}

	/**
	 * @return string
	 */
	public function Location () {
		return 'https://twemoji.maxcdn.com/twemoji.min.js';
	}
}