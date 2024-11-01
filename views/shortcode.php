<?php if ($send_refresh_request) {
	echo '<script type="text/javascript" async="async" src="' . $this->get_refresh_stats_url() . '"></script>';
} ?>
<?php if (!$stats) return; ?>

<?php
// Font Awesome
wp_enqueue_style( 'fa', 'http://maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css', null, '1.0');
// Main.css
wp_enqueue_style( 'fsc', plugin_dir_url(dirname(__FILE__)) . '/assets/main.css', null, '1.0');


$isFontAwasomeMode = true;
if ($isFontAwasomeMode) {
	$serviceKeyToClass = array(
		'fb' => 'fa fa-facebook',
		'tw' => 'fa fa-twitter',
		'g' => 'fa fa-google-plus',
		'ln' => 'fa fa-linkedin',
		'p' => 'fa fa-pinterest',
		're' => 'fa fa-reddit',
		'su' => 'fa fa-stumbleupon',
	);
} else {
	$serviceKeyToClass = array(
		'fb' => 'stat-icon icon-facebook',
		'tw' => 'stat-icon icon-twitter',
		'g' => 'stat-icon icon-google-plus',
		'ln' => 'stat-icon icon-linkedin',
		'p' => 'stat-icon icon-pinterest',
		're' => 'stat-icon icon-reddit',
		'su' => 'stat-icon icon-stumbleupon',
	);
}
?>

<?php if ($is_single) { ?>
	<?php foreach ($stats as $key => $value) {
		$iconClass = isset($serviceKeyToClass[$key]) ? $serviceKeyToClass[$key] : '';
		if ($isFontAwasomeMode) {
			$parts[] = "<span class='i-wrapper'><i class='{$iconClass}'></i></span>";
		} else {
			$parts[] = "<i class='{$iconClass}'></i>";
		}
	} ?>
	<div id="pageShareStatsWidget">
		<ul id="pageShareStats"><li><?php echo join('</li><li>', $parts); ?></li></ul>
		<strong class="total"><?php echo array_sum($stats); ?></strong>
	</div>
<?php } else { ?>
<?php
	$parts = array();
	foreach ($stats as $key => $value) {
		// if ($value < 1) continue;
		$iconClass = isset($serviceKeyToClass[$key]) ? $serviceKeyToClass[$key] : '';
		if ($isFontAwasomeMode) {
			$parts[] = "<span class='i-wrapper'><i class='{$iconClass}'></i></span><strong>{$value}</strong>";
		} else {
			$parts[] = "<i class='{$iconClass}'></i><strong>{$value}</strong>";
		}
	}
?>
	<div id="pageShareStatsWidget">
		<ul id="pageShareStats"><li><?php echo join('</li><li>', $parts); ?></li></ul>
	</div>
<?php } ?>