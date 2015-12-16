<!DOCTYPE html>
<html>
<head>
	<title><?php echo $this->PresenceTitle(); ?></title>

	<?php echo $this->Resources(); ?>
	<style type="text/css">
		#map-container {
			position: fixed; left: 0; top: 0; width: 100%; height: 100%;
		}

		#map {
			position: fixed; left: 0; top: 0; width: 100%; height: 100%;
		}
	</style>
</head>
<body>
	<!--<div id="map-container">
		<div id="map"></div>
	</div>-->
	<div class="quark-presence-screen" id="presence-header">
		<div class="quark-presence-container">
			<div class="quark-presence-column left" id="presence-logo">
				<div class="quark-presence-container">
					QuarkPresence Control
				</div>
			</div>
			<div class="quark-presence-column left" id="presence-menu-header">
				<div class="quark-presence-container">
					<a class="quark-button fa fa-envelope-o"></a>
					<a class="quark-button fa fa-gear"></a>
					<a class="quark-button fa fa-bell"></a>
				</div>
			</div>
			<div class="quark-presence-column left" id="presence-search">
				<div class="quark-presence-container">
					<input class="quark-input" placeholder="Search" />
					<a class="quark-button fa fa-search"></a>
				</div>
			</div>
			<div class="quark-presence-column right" id="presence-user">
				<div class="quark-presence-container">
					<div class="quark-presence-column left-inverse">
						FooBar<br />
						<a class="quark-button">Exit</a>
					</div>
					<div class="quark-presence-column right" id="presence-user-photo">
						<div class="quark-presence-container" style="background-image: url(http://placehold.it/45x45);"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="quark-presence-screen" id="presence-content">
		<div class="quark-presence-container">
			<div class="quark-presence-column left" id="presence-menu-parent">
				<div class="quark-presence-container left" id="presence-menu-side">
					<a class="quark-button fa fa-bars">Dashboard</a>
					<a class="quark-button fa fa-newspaper-o">Posts</a>
					<a class="quark-button fa fa-user">Users</a>
					<a class="quark-button fa fa-folder">Storage</a>
					<a class="quark-button fa fa-feed">News</a>
					<a class="quark-button fa fa-map">Hover map</a>
					<a class="quark-button fa fa-tasks">Tasks</a>
					<a class="quark-button fa fa-phone">Support</a>
					<a class="quark-button fa fa-support">Help</a>
				</div>
			</div>
			<?php echo $this->View(); ?>
		</div>
	</div>
</body>
</html>