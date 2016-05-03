<!DOCTYPE html>
<html>
<head>
	<title><?php echo $this->TitleWidget(); ?></title>

	<?php echo $this->Resources(); ?>
</head>
<body>
	<?php echo $this->OverlaidContainerWidget(); ?>
	<div class="quark-presence-screen" id="presence-header">
		<div class="quark-presence-container">
			<div class="quark-presence-column left" id="presence-logo">
				<div class="quark-presence-container">
					<?php echo $this->LogoWidget(); ?>
				</div>
			</div>
			<div class="quark-presence-column left" id="presence-menu-header">
				<div class="quark-presence-container">
					<?php echo $this->MenuHeaderWidget(); ?>
				</div>
			</div>
			<div class="quark-presence-column right" id="presence-user">
				<div class="quark-presence-container">
					<?php echo $this->UserWidget(); ?>
				</div>
			</div>
		</div>
	</div>
	<div class="quark-presence-screen" id="presence-content">
		<div class="quark-presence-container">
			<?php echo $this->MenuSideWidget(), $this->View(); ?>
		</div>
	</div>
</body>
</html>