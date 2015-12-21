<!DOCTYPE html>
<html>
<head>
	<title><?php echo $this->PresenceTitle(); ?></title>

	<?php echo $this->Resources(); ?>
</head>
<body>
	<?php echo $this->OverlaidContainer(); ?>
	<div class="quark-presence-screen" id="presence-header">
		<div class="quark-presence-container">
			<div class="quark-presence-column left" id="presence-logo">
				<div class="quark-presence-container">
					QuarkPresence Control
				</div>
			</div>
			<div class="quark-presence-column left" id="presence-menu-header">
				<div class="quark-presence-container">
					<a class="quark-button fa fa-envelope-o">
						<p class="presence-label yellow">5</p></a>
					<a class="quark-button fa fa-gear">
						<p class="presence-label red">5</p></a>
					<a class="quark-button fa fa-bell">
						<p class="presence-label green">5</p>
					</a>
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
			<?php echo $this->View(); ?>
		</div>
	</div>
</body>
</html>