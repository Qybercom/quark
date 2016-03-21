<?php
namespace Quark\ViewResources\TwitterEmoji;

use Quark\IQuarkForeignViewResource;
use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkDTO;
use Quark\QuarkJSViewResourceType;

/**
 * Class TwitterEmoji
 *
 * https://github.com/twitter/twemoji
 *
 * @package Quark\ViewResources\TwitterEmoji
 */
class TwitterEmoji implements IQuarkViewResource, IQuarkForeignViewResource {
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

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}