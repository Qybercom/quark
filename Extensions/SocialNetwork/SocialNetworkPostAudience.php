<?php
namespace Quark\Extensions\SocialNetwork;

use Quark\QuarkKeyValuePair;

/**
 * Class SocialNetworkPostAudience
 *
 * @package Quark\Extensions\SocialNetwork
 */
class SocialNetworkPostAudience {
	const TYPE_PUBLIC = 'public';
	const TYPE_PRIVATE = 'private';
	const TYPE_CUSTOM = 'custom';

	/**
	 * @var string $_type = self::TYPE_PUBLIC
	 */
	private $_type = self::TYPE_PUBLIC;

	/**
	 * @var QuarkKeyValuePair[] $_targets = []
	 */
	private $_targets = array();

	/**
	 * @param string $type = self::TYPE_PUBLIC
	 */
	public function __construct ($type = self::TYPE_PUBLIC) {
		$this->Type($type);
	}

	/**
	 * @param string $type = self::TYPE_PUBLIC
	 *
	 * @return string
	 */
	public function Type ($type = self::TYPE_PUBLIC) {
		if (func_num_args() != 0)
			$this->_type = $type;

		return $this->_type;
	}

	/**
	 * @param QuarkKeyValuePair $target = null
	 *
	 * @return $this
	 */
	public function Target (QuarkKeyValuePair $target = null) {
		if ($target != null)
			$this->_targets[] = $target;

		return $this;
	}

	/**
	 * @return QuarkKeyValuePair[]
	 */
	public function Targets () {
		return $this->_targets;
	}

	/**
	 * @param string $provider = ''
	 *
	 * @return QuarkKeyValuePair[]
	 */
	public function TargetsFor ($provider = '') {
		$out = array();

		foreach ($this->_targets as $i => &$target)
			if ($target->Key() == $provider)
				$out[] = $target;

		return $out;
	}
}