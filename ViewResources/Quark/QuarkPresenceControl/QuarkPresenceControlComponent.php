<?php
namespace Quark\ViewResources\Quark\QuarkPresenceControl;

use Quark\QuarkViewBehavior;

use Quark\ViewResources\Google\GoogleMap;
use Quark\ViewResources\Google\MapPoint;

/**
 * Class QuarkPresenceControlComponent
 *
 * @package Quark\ViewResources\Quark\QuarkPresenceControl
 */
trait QuarkPresenceControlComponent {
	use QuarkViewBehavior;

	/**
	 * @param string $href = ''
	 * @param string $text = ''
	 * @param string $fa = ''
	 * @param string $title = ''
	 *
	 * @return string
	 */
	public static function MenuWidgetItem ($href = '', $text = '', $fa = '', $title = '') {
		return '<a class="quark-button fa ' . $fa . '" href="' . $href . '" title="' . $title . '">' . $text . '</a>';
	}

	/**
	 * @param string[] $links = []
	 *
	 * @return string
	 */
	public function MenuHeaderWidget ($links = []) {
		$items = '';

		foreach ($links as $link)
			$items .= $link;

		return $items;
	}

	/**
	 * @param string[] $links = []
	 * @param string $additional = ''
	 *
	 * @return string
	 */
	public function MenuSideWidget ($links = [], $additional = '') {
		$items = '';

		foreach ($links as $link)
			$items .= $link;

		return '
			<div class="quark-presence-column left" id="presence-menu-side-parent">
				<div class="quark-presence-container left" id="presence-menu-side">' . $items . '</div>' . $additional . '
			</div>
		';
	}

	/**
	 * @param string $text = ''
	 * @param string $color = green
	 *
	 * @return string
	 */
	public function LabelWidget ($text = '', $color = 'green') {
		return '<p class="presence-label ' . $color . '">' . $text . '</p>';
	}

	/**
	 * @param string $action = ''
	 * @param string $method = 'POST'
	 * @param string $placeholder = 'Search'
	 * @param string $fa = 'fa-search'
	 *
	 * @return string
	 */
	public function SearchWidget ($action = '', $method = 'POST', $placeholder = 'Search', $fa = 'fa-search') {
		return '
			<form id="presence-search" action="' . $action . '" method="' . $method . '" enctype="multipart/form-data">
				<input class="quark-input" placeholder="' . $placeholder . '" />
				<a class="quark-button fa ' . $fa . '"></a>
			</form>
		';
	}

	/**
	 * @param string $name = 'FooBar'
	 * @param string $photo = 'http://placehold.it/45x45'
	 * @param string $logoutTitle = 'Exit'
	 * @param string $logoutAddr = '/user/logout'
	 * @param bool $logoutSigned = false
	 *
	 * @return string
	 */
	public function UserWidget ($name = 'FooBar', $photo = 'http://placehold.it/45x45', $logoutTitle = 'Exit', $logoutAddr = '/user/logout', $logoutSigned = false) {
		return '
			<div class="quark-presence-column left-inverse">
				' . $name . '<br />
				<a href="' . (func_num_args() == 5 ? $this->Link($logoutAddr, $logoutSigned) : $logoutAddr). '" class="quark-button">' . $logoutTitle . '</a>
			</div>
			<div class="quark-presence-column right" id="presence-user-photo">
				<div class="quark-presence-container" style="background-image: url(' . $photo . ');"></div>
			</div>
		';
	}

	/**
	 * @param string $selector
	 * @param MapPoint $center
	 * @param string $type = GoogleMap::TYPE_ROADMAP
	 * @param int $zoom = 15
	 * @param string $var = 'map'
	 *
	 * @return string
	 */
	public function OverlaidMapWidget ($selector, MapPoint $center, $type = GoogleMap::TYPE_ROADMAP, $zoom = 16, $var = 'map') {
		return '
			<script type="text/javascript">
			var ' . $var . ' = null;
			$(function () {
				' . $var . ' = new GoogleMap(\''. $selector . '\', {
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

	/**
	 * @param string $external = 'ws://80.25.81.147:25900'
	 * @param string $internal = 'tcp://192.168.1.10:25800'
	 *
	 * @return string
	 */
	public function ClusterControllerWidget ($external = 'ws://80.25.81.147:25900', $internal = 'tcp://192.168.1.10:25800') {
		return '
			<div class="presence-cluster-node controller">
				<div class="addr external">' . $external . '</div>
				<div class="addr internal">' . $internal . '</div>
			</div>
		';
	}

	/**
	 * @param string $external = 'ws://80.25.81.147:25000'
	 * @param string $internal = 'tcp://192.168.1.10:34567'
	 *
	 * @return string
	 */
	public function ClusterNodeWidget ($external = 'ws://80.25.81.147:25000', $internal = 'tcp://192.168.1.10:34567') {
		return '
			<div class="presence-cluster-node">
				<div class="addr external">' . $external . '</div>
				<div class="addr internal">' . $internal . '</div>
				<div class="node-state">
					menu item<br />
					menu item<br />
					menu item<br />
				</div>
			</div>
		';
	}
}