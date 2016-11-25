<?php
namespace Quark\ViewResources\Quark\ChatKit;

use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceWithDependencies;

use Quark\QuarkProxyJSViewResource;
use Quark\QuarkStreamEnvironment;
use Quark\QuarkURI;

use Quark\ViewResources\Quark\QuarkPresence\QuarkPresence;

/**
 * Class ChatKit
 *
 * @package Quark\ViewResources\Quark\ChatKit
 */
class ChatKit implements IQuarkViewResource, IQuarkViewResourceWithDependencies {
	/**
	 * @var QuarkURI $_stream
	 */
	private $_stream;

	/**
	 * @param string $stream = ''
	 */
	public function __construct ($stream = '') {
		if ($stream != '');
			$this->_stream = QuarkStreamEnvironment::ConnectionURI($stream);
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return array(
			$this->_stream != null
				? new QuarkProxyJSViewResource('_chat', array(
					'host' => $this->_stream->host,
					'port' => $this->_stream->port
				))
				: null,
			new QuarkPresence(),
			new ChatKitCSS(),
			new ChatKitJS()
		);
	}

	/**
	 * @param string $connection = ''
	 *
	 * @return ChatKit
	 */
	public static function ForConnection ($connection = '') {
		$chat = new self();
		$chat->_stream = QuarkURI::FromURI($connection);

		return $chat;
	}
}