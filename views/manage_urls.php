<?php
$active_services = $this->get_active_services();
$active_services_count = count($active_services);
//print_r($active_services); exit;
$this->wpUrlList = new wpUrlList($this, $items);
?>
<style>
    #poststuff #submitdiv .inside,
    #poststuff #submitdiv1 .inside{
        margin: 6px 6px;
    }
#post-body .overlay{
	background-color: #1f2123;
	display: none;
	height: 100%;
	opacity: 0.61;
	position: absolute;
	width: 100%;
	z-index: 1000;
}

#post-body-content,
#submitdiv,
#submitdiv{
	position: relative;
}
th#url {
    width: 35%;
}
td.url.column-url.has-row-actions.column-primary {
    font-size: 12px;
}
.manage-column {
    font-size: 12px !important;
	font-weight: bold !important;
	background-color: #34b0d4;
    color: #fff !important;
}
.manage-column a {
    color: #fff;
}
td.fb.column-fb {
    font-size: 12px;
}
td.tw.column-tw {
    font-size: 12px;
}
td.g.column-g {
    font-size: 12px;
}
td.ln.column-ln {
    font-size: 12px;
}
td.p.column-p {
    font-size: 12px;
}
td.su.column-su {
    font-size: 12px;
}
td.re.column-re {
    font-size: 12px;
}
td.total.column-total {
    font-size: 12px;
	font-weight: bold;
}
td.ct.column-ct {
    font-size: 12px;
}

</style>
<div class="wrap">
	<div>
		<h2>Social Footprint Dashboard</h2>
	</div>


	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<div>
				<svg id="progress-bar" display="none"></svg>
			</div>
			<div id="post-body-content">
				<div class="overlay"></div>
				<div class="meta-box-sortables ui-sortable">
					<form method="post">
						<?php
						$this->wpUrlList->prepare_items();
						$this->wpUrlList->display(); ?>
					</form>
				</div>
			</div>
            <div class="postbox-container" id="postbox-container-1">
                <div class="meta-box-sortables ui-sortable" id="side-sortables" style="">
                    <div class="postbox" id="submitdiv">
						<div class="overlay"></div>
                        <div style="display: none;" title="Click to toggle" class="handlediv"><br></div>
                        <h3 class="hndle ui-sortable-handle"><span>Total Shares</span></h3>
                        <div id="total-share-status" class="inside">
                            <?php
                            print $this->_get_total_status();
                            ?>
                        </div>
                    </div>
                    <div class="postbox" id="submitdiv1">
						<div class="overlay"></div>
                        <div style="display: none;" title="Click to toggle" class="handlediv"><br></div>
                        <h3 class="hndle ui-sortable-handle"><span>Import Content</span></h3>
                        <div id="import-content-form" class="inside">
                            <form method="POST" action="<?php echo $this->get_page_url(); ?>">
                                <input type="hidden" name="cmd" value="create_url" />
                                <?php if (!empty($form_errors)) { ?>
                                    <div style="text-align:center; color:#ee0000;margin-bottom:10px"><?php echo join('<br />', $form_errors); ?></div>
                                <?php } ?>

                                <label>URL: </label>
                                <input type="text" name="f[url]" class="custom-form-elem ltr" placeholder="http://example.com/" value="<?php echo !empty($form_data['url']) ? $form_data['url'] : ''; ?>"/>
                                <br/>
                                <input class="button" type="submit" value="Add" />
                            </form>
                            <p style="margin-top: 15px;">Alternatively, upload a .csv file with a list of URLs:</p>
                            <form action="<?php echo $this->get_page_url(); ?>" method="post" enctype="multipart/form-data">
                                <input type="file" name="csv" value="" />
                                <input type="hidden" name="cmd" value="upload_csv" />
                                <input class="button" type="submit" name="submit" value="Upload" /></form>
                            <form action="<?php echo $this->get_page_url(); ?>" method="post" enctype="multipart/form-data" style="margin-top: 25px;">
                                <input type="hidden" name="cmd" value="import_wordpress" />
                                <input type="submit" class="button" id="import-all-wp-post" name="submit" value="Import all WordPress Posts" /></form>
                         </div>
                    </div>
                    <a href="http://www.matthewbarby.com/?utm_source=Social%20Footprint%20Plugin&utm_medium=Plugin&utm_campaign=WordPress%20Plugin
" target="_blank"><?php
echo '<img src="' . plugins_url( 'includes/mattLogo.png', __FILE__ ) . '" alt="Matthew Barby" width="150px">'; ?></a>
                </div>
            </div>
        </div>
	</div>
</div>
<div style="clear: both">&nbsp;</div>
