<?php
namespace Quark\ViewResources\Quark\QuarkNetwork;

use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceWithDependencies;
use Quark\IQuarkInlineViewResource;
use Quark\IQuarkMultipleViewResource;

use Quark\QuarkStreamEnvironment;
use Quark\QuarkURI;

/**
 * Class QuarkNetworkNode
 *
 * @package Quark\ViewResources\Quark\QuarkNetwork
 */
class QuarkNetworkNode implements IQuarkViewResource, IQuarkInlineViewResource, IQuarkMultipleViewResource, IQuarkViewResourceWithDependencies {
	/**
	 * @var string $_var = ''
	 */
	private $_var = '';

	/**
	 * @var QuarkURI $_stream
	 */
	private $_stream;

	/**
	 * @param string $var = ''
	 * @param string $stream = ''
	 */
	public function __construct ($var = '', $stream = '') {
		$this->_var = $var;

		if ($stream != '');
			$this->_stream = QuarkStreamEnvironment::ConnectionURI($stream);
	}

	/**
	 * @return string
	 */
	public function HTML () {
		return $this->_stream == null
			? ''
			: (
				'<script type="text/javascript">var ' . $this->_var . '=new Quark.Network.Client(\''
				. $this->_stream->host . '\',' . $this->_stream->port
				. ');'
				. ($this->_stream->Secure() ? ($this->_var . '.secure=true;') : '')
				. '</script>'
			);
	}

	/**
	 * @return IQuarkViewResource[]
	 */
	public function Dependencies () {
		return array(
			new QuarkNetwork()
		);
	}

	/**
	 * @param string $var = ''
	 * @param string $connection = ''
	 *
	 * @return QuarkNetworkNode
	 */
	public static function ForConnection ($var = '', $connection = '') {
		$node = new self();

		$node->_var = $var;
		$node->_stream = QuarkURI::FromURI($connection);

		return $node;
	}
}