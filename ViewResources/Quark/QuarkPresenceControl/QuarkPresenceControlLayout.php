<!DOCTYPE html>
<html>
<head>
	<title><?php echo $this->PresenceTitle(); ?></title>

	<?php echo $this->Resources(); ?>
</head>
<body>
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
	<div class="quark-presence-screen">
		<div class="quark-presence-container">
			<div class="quark-presence-column left">
				<div class="quark-presence-container left" id="presence-menu-side">
					<a class="quark-button">Lorem ipsum</a>
					<a class="quark-button">Lorem ipsum</a>
					<a class="quark-button">Lorem ipsum</a>
					<a class="quark-button">Lorem ipsum</a>
					<a class="quark-button">Lorem ipsum</a>
				</div>
			</div>
			<?php echo $this->View(); ?>
		</div>
	</div>
</body>
</html>