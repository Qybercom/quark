<?php
namespace Quark\ViewResources\Quark\QuarkPresenceControl;

use Quark\ViewResources\Google\GoogleMap;
use Quark\ViewResources\Google\MapPoint;

/**
 * Class QuarkPresenceControlComponent
 *
 * @package Quark\ViewResources\Quark\QuarkPresenceControl
 */
trait QuarkPresenceControlComponent {
	/**
	 * @param string $href
	 * @param string $title
	 * @param string $fa
	 *
	 * @return string
	 */
	public static function MenuSideItem ($href = '', $title = '', $fa = '') {
		return '<a class="quark-button fa ' . $fa . '" href="' . $href . '">' . $title . '</a>';
	}

	/**
	 * @param string[] $links
	 *
	 * @return string
	 */
	public function MenuSide ($links = []) {
		$items = '';

		foreach ($links as $link)
			$items .= $link;

		return '
			<div class="quark-presence-column left" id="presence-menu-side-parent">
				<div class="quark-presence-container left" id="presence-menu-side">' . $items . '</div>
			</div>
		';
	}

	/**
	 * @param string $text
	 * @param string $color = green
	 *
	 * @return string
	 */
	public function Label ($text = '', $color = 'green') {
		return '<p class="presence-label ' . $color . '">' . $text . '</p>';
	}

	/**
	 * @param string $selector
	 * @param MapPoint $center
	 * @param string $type = GoogleMap::TYPE_ROADMAP
	 * @param int $zoom = 15
	 *
	 * @return string
	 */
	public function OverlaidMap ($selector, MapPoint $center, $type = GoogleMap::TYPE_ROADMAP, $zoom = 16) {
		return '
			<script type="text/javascript">
			$(function () {
				var map = new GoogleMap(\''. $selector . '\', {
					zoom: ' . $zoom . ',
					type: ' . $type . ',
					center: {
						lat: ' . $center->lat . ',
						lng: ' . $center->lng . '
					}
				});
			});
			</script>
			<div id="map-container" class="presence-overlaid-container">
				<div id="map" style="position: fixed; left: 0; top: 0; width: 100%; height: 100%;"></div>
			</div>
		';
	}
}