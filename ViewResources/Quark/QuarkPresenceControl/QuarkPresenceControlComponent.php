<?php
namespace Quark\ViewResources\Quark\QuarkPresenceControl;

use Quark\QuarkKeyValuePair;
use Quark\QuarkObject;
use Quark\QuarkView;
use Quark\QuarkViewBehavior;

use Quark\ViewResources\Google\GoogleMap;
use Quark\ViewResources\Google\GoogleMapPoint;

/**
 * Class QuarkPresenceControlComponent
 *
 * @package Quark\ViewResources\Quark\QuarkPresenceControl
 */
trait QuarkPresenceControlComponent {
	use QuarkViewBehavior;

	/**
	 * @var bool $_menuHeader = false
	 */
	private $_menuHeader = false;

	/**
	 * @param bool $right = false
	 *
	 * @return string
	 */
	private function _headerWidget ($right = false) {
		return '</div></div><div class="quark-presence-column ' . ($right ? 'right' : 'left') . '"><div class="quark-presence-container">';
	}

	/**
	 * @param string $href = ''
	 * @param string $text = ''
	 * @param string $fa = ''
	 * @param string $title = ''
	 * @param string $id = ''
	 *
	 * @return string
	 */
	public function MenuWidgetItem ($href = '', $text = '', $fa = '', $title = '', $id = '') {
		return '<a class="quark-button fa ' . $fa . '" href="' . $href . '" title="' . $title . '"' . ($id != '' ? ' id="' . $id . '"' : '') . '>' . $text . '</a>';
	}

	/**
	 * @param string|string[] $items = ''
	 * @param string $additional = ''
	 *
	 * @return string
	 */
	public function MenuSideWidget ($items = '', $additional = '') {
		return '
			<div class="quark-presence-column left" id="presence-menu-side-parent">
				<div class="quark-presence-container left" id="presence-menu-side">' . (is_array($items) ? implode('', $items) : $items) . '</div>' . $additional . '
			</div>
		';
	}

	/**
	 * @param string[] $links = []
	 * @param bool $right = false
	 *
	 * @return string
	 */
	public function MenuHeaderWidget ($links = [], $right = false) {
		$items = $this->_headerWidget($right);
		
		foreach ($links as $link)
			$items .= $link;

		return $items;
	}

	/**
	 * @param string[] $items = []
	 * @param string $additional = ''
	 * @param callable($href, $text) $button = null
	 * @param callable($text) $node = null
	 *
	 * @return string
	 */
	public function MenuSideWidgetTree ($items = [], $additional = '', callable $button = null, callable $node = null) {
		if ($button == null)
			$button = function ($href, $text) { return $this->MenuWidgetItem($href, $text); };

		return $this->MenuSideWidget(QuarkView::TreeMenu(QuarkObject::TreeBuilder($items), $button, $node), $additional);
	}

	/**
	 * @param IQuarkPresenceControlViewModel $view = null
	 * @param string $delimiter = '&gt;'
	 * @param bool $right = false
	 * @param bool $header = true
	 *
	 * @return string
	 */
	public function BreadcrumbWidget (IQuarkPresenceControlViewModel $view = null, $delimiter = '&gt;', $right = false, $header = true) {
		if (!($view instanceof IQuarkPresenceControlViewModelWithBreadcrumbs)) return '';

		$breadcrumbs = $view->PresenceBreadcrumbs();
		$out = array();

		if (QuarkObject::isTraversable($breadcrumbs))
			foreach ($breadcrumbs as $breadcrumb)
				if ($breadcrumb instanceof QuarkKeyValuePair)
					$out[] = '<a class="quark-button presence-breadcrumb" href="' . $breadcrumb->Key() . '">' . $breadcrumb->Value() . '</a>';

		return ($header ? $this->_headerWidget($right) : '') . implode('<span class="presence-breadcrumb-delimiter">' . $delimiter . '</span>', $out);
	}

	/**
	 * @param string $text = ''
	 * @param string $color = green
	 * @param bool $visible = true
	 *
	 * @return string
	 */
	public function LabelWidget ($text = '', $color = 'green', $visible = true) {
		return '<p class="presence-label ' . $color . ' ' . ($visible ? 'visible' : 'hidden') . '">' . $text . '</p>';
	}

	/**
	 * @param string $action = ''
	 * @param string $method = 'POST'
	 * @param string $name = 'search'
	 * @param string $placeholder = 'Search'
	 * @param string $fa = 'fa-search'
	 *
	 * @return string
	 */
	public function SearchWidget ($action = '', $method = 'POST', $name = 'search', $placeholder = 'Search', $fa = 'fa-search') {
		return '
			<form id="presence-search" action="' . $action . '" method="' . $method . '" enctype="multipart/form-data">
				<input class="quark-input" name="' . $name . '" placeholder="' . $placeholder . '" />
				<button class="quark-button fa ' . $fa . '" type="submit"></button>
			</form>
		';
	}

	/**
	 * @param string $name = 'FooBar'
	 * @param string $photo = 'http://placehold.it/45x45'
	 * @param string $logoutTitle = 'Exit'
	 * @param string $logoutAddr = '/user/logout'
	 * @param bool $logoutSigned = true
	 *
	 * @return string
	 */
	public function UserWidget ($name = 'FooBar', $photo = 'http://placehold.it/45x45', $logoutTitle = 'Exit', $logoutAddr = '/user/logout', $logoutSigned = true) {
		return '
			<div class="quark-presence-column left-inverse" id="presence-user-info">
				<div class="quark-presence-container">
					' . $name . '<br />
					<a href="' . $this->Link($logoutAddr, $logoutSigned). '" class="quark-button">' . $logoutTitle . '</a>
				</div>
			</div>
			<div class="quark-presence-column right" id="presence-user-photo">
				<div class="quark-presence-container" style="background-image: url(' . $photo . ');"></div>
			</div>
		';
	}

	/**
	 * @param string $selector
	 * @param GoogleMapPoint $center
	 * @param string $type = GoogleMap::TYPE_ROADMAP
	 * @param int $zoom = 15
	 * @param string $var = 'map'
	 *
	 * @return string
	 */
	public function OverlaidMapWidget ($selector, GoogleMapPoint $center, $type = GoogleMap::TYPE_ROADMAP, $zoom = 16, $var = 'map') {
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
	 * @param string $external = ''
	 * @param string $internal = ''
	 *
	 * @return string
	 */
	public function ClusterControllerWidget ($external = '', $internal = '') {
		return '
			<div class="presence-cluster-node controller">
				<div class="addr external">' . $external . '</div>
				<div class="addr internal">' . $internal . '</div>
			</div>
		';
	}

	/**
	 * @param string $external = ''
	 * @param string $internal = ''
	 *
	 * @return string
	 */
	public function ClusterNodeWidget ($external = '', $internal = '') {
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