<?php
namespace Quark\Extensions\AceStream;

use Quark\IQuarkModel;
use Quark\IQuarkStrongModel;
use Quark\QuarkCollection;

/**
 * Class AceStreamChannelGroup
 *
 * @property string $name
 * @property string $icon
 * @property QuarkCollection|AceStreamChannel[] $items
 * @property AceStreamChannelGroupEPG $epg
 *
 * @package Quark\Extensions\AceStream
 */
class AceStreamChannelGroup implements IQuarkModel, IQuarkStrongModel {
	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'name' => '',
			'icon' => '',
			'items' => new QuarkCollection(new AceStreamChannel()),
			'epg' => new AceStreamChannelGroupEPG()
		);
	}

	/**
	 * @return mixed
	 */
	public function Rules () {
		// TODO: Implement Rules() method.
	}
}