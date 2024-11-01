<?php
/**
 * @array $services
 */
?>

<div style="padding-right:15px;">
	<h2><?php echo $this->get_setting('page-settings-title', 'Settings'); ?></h2>
	<div id="pssSettings">
		<h3>Active services list</h3>
		<form method="POST" action="<?php echo $this->get_page_url('settings'); ?>">
			<input type="hidden" name="cmd" value="update-services" />
			<?php foreach ($services as $serviceKey => $isActive) { ?>
			<div>
				<input type="checkbox" name="services[<?php echo $serviceKey; ?>]" value="1"<?php echo $isActive ? ' checked="checked"' : ''; ?> />
				<label><?php echo $this->get_service_label_by_code($serviceKey); ?></label>
			</div>
			<?php } ?>
			<div style="padding-top:15px;">
				<input type="submit" name="save" value="Save" />
			</div>
		</form>
	</div>
</div>
