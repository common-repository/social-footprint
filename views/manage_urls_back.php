<style>
    .text-center{
        text-align:center;
    }
    .uss-remove-item{
        color:red;
    }
    #urlStatsItems{
        width:100%;
    }
    .in-progress-2{
        color:#EEE;
    }

    .in-progress {/* btn class that indicates that ajax in progress */
        display:block;
        -webkit-animation:rotateThis 2s infinite;
        animation:rotateThis 4s infinite;
    }
    @-webkit-keyframes rotateThis {
        from {-webkit-transform:rotateY(0deg);}
        to {-webkit-transform:rotateY(360deg);}
    }
    @keyframes myfirst {
        from {transform:rotateY(0deg);}
        to {transform:rotateY(360deg);}
    }

    .flat-table{
        border-spacing:0;
        border-collapse:collapse;
        border:1px solid #808080;
    }
    .flat-table th{
        background:#D6D6D6;
        padding:5px;
    }
    .flat-table td{
        background:#FFF;
        padding:2px;
    }

    #contentWrapper{
        padding:15px;
    }

    #formWrapper{
        padding:15px;
        border:1px solid #808080;
        float:right;
        margin:15px 0;
    }
</style>

<script>
    ManageUrlPage = {
        init: function(){
            jQuery('.uss-remove-item').click(function(ev){
                ev.preventDefault();
                if (!confirm('Are you sure wnat to remove this item?')) {
                    return false;
                }
                document.location = jQuery(this).attr('href');
            });

            jQuery('#urlStatsItems').find('a[data-role="refresh-stats-all"]').click(function(ev){
                ev.preventDefault();
                var btn = jQuery(this);
                if (btn.hasClass('in-progress')) {
                    return;
                }
                btn.addClass('in-progress');
                jQuery.ajax({
                    url:btn.data('href'),
                    success:function(){
                        location.reload();
                    },
                    complete:function(){
                        btn.addClass('in-progress');
                    }
                });
            });
        }
    };
    jQuery(function(){
        ManageUrlPage.init();
    });
</script>
<?php
$active_services = $this->get_active_services();
$active_services_count = count($active_services);

?>



<div id="contentWrapper">
    <h2><?php echo $this->get_setting('page-manage-items-title', 'Manage URLs'); ?></h2>
    <?php $index = 1; ?>
    <form action="<?php echo $this->get_page_url(); ?>" method="post">
        <input type="hidden" name="cmd" value="set_order" />
        <?php foreach ($active_services as $service_key) { ?>
            <input <?php if(isset($_POST['set_order']) && $_POST['set_order'] == $service_key) echo "checked"; ?> type="radio" name="set_order" value="<?php echo $service_key; ?>"> <label for="<?php echo $service_key; ?>"><?php echo $this->get_service_label_by_code($service_key); ?></label><br>
        <?php } ?>
        <input <?php if(isset($_POST['set_order']) && $_POST['set_order'] == 'totals') echo "checked"; ?> type="radio" name="set_order" value="totals"> <label for="totals">Totals</label><br>
        <input type="submit" value="Change order">
        <input type="submit" value="Delete">
    </form>

    <table id="urlStatsItems" class="flat-table">
        <thead>
        <th style="width:20px">#</th>
        <th>URL</th>
        <?php foreach ($active_services as $service_key) {?>
            <th><?php echo $this->get_service_label_by_code($service_key); ?></th>
        <?php } ?>
        <th>Total</th>
        <th>Last check</th>
        <th>
            <?php if ($items) { ?>
                <a href="#" data-href="<?php echo $this->get_refresh_stats_url(true); ?>" data-role="refresh-stats-all" title="recheck stats for all items">All</a>
            <?php } else { ?>
                &nbsp;
            <?php } ?>
        </th>
        <th>&nbsp;</th>
        </thead>
        <?php if (!$items) { ?>
            <tr>
                <td colspan="<?php echo 5+$active_services_count; ?>" class="text-center">List is empty.</td>
            </tr>
        <?php } else { ?>
            <?php
            $items = calculate_total($items);

            if(isset($_POST['set_order'])) {

                echo "Ordering by {$this->get_service_label_by_code($_POST['set_order'])}";

                uasort($items, 'cust_sort');

            }
            ?>
            <?php foreach ($items as $url => $stats) { ?>
                <tr>
                    <td class="text-center"><?php echo $index++; ?></td>
                    <td><a href="<?php echo $url; ?>" title="open in new tab" target="_blank"><?php echo $url; ?></a></td>
                    <?php foreach ($active_services as $service_key) {?>
                        <td class="text-center"><?php echo isset($stats[$service_key]) ? $stats[$service_key] : '0'; ?></td>
                    <?php } ?>
                    <td class="text-center"><?php echo $stats['totals']; ?></td>
                    <td class="text-center"><?php echo isset($stats['ct']) ? date('Y-m-d H:i:s', $stats['ct']) : '-'; ?></td>
                    <td class="text-center"><a href="<?php echo add_query_arg(array('cmd' => 'refresh_stats','url' => $url)); ?>" title="recheck stats">Recheck</a></td>
                    <td class="text-center"><a href="<?php echo add_query_arg(array('cmd' => 'delete_url','url' => $url)); ?>" class="uss-remove-item" title="remove item">Delete</a></td>
                </tr>
            <?php } ?>
        <?php } ?>
    </table>

    <div id="formWrapper">
        <svg id="import-progress-bar" display="none"></svg><br>
        <form method="POST" action="<?php echo $this->get_page_url(); ?>">
            <!--<h4 style="margin:0;text-align:center">Add URL</h4>-->
            <input type="hidden" name="cmd" value="create_url" />

            <?php if (!empty($form_errors)) { ?>
                <div style="text-align:center; color:#ee0000;margin-bottom:10px"><?php echo join('<br />', $form_errors); ?></div>
            <?php } ?>

            <label>URL: </label>
            <input type="text" name="f[url]" class="regular-text ltr" placeholder="http://example.com/" value="<?php echo !empty($form_data['url']) ? $form_data['url'] : ''; ?>"/>

            <input type="submit" value="Add" />
        </form>
        <p>Alternatively, upload a .csv file with a list of URLs:</p>
        <form action="<?php echo $this->get_page_url(); ?>" method="post" enctype="multipart/form-data">
            <input type="file" name="csv" value="" />
            <input type="hidden" name="cmd" value="upload_csv" />
            <input type="submit" name="submit" value="Upload" /></form>
        <form action="<?php echo $this->get_page_url(); ?>" method="post" enctype="multipart/form-data">
            <input type="hidden" name="cmd" value="import_wordpress" />
            <input type="submit" id="import-all-wp-post" name="submit" value="Import all WordPress Posts" /></form>

    </div>
    <div style="clear:both"></div>

    <?php
    $total_stats = $this->get_total_stats();
    $total_result = array_sum($total_stats);
    ?>
    <?php if ($total_result > 0) {?>
        <div style="float:right">
            <table class="flat-table">
                <caption>Total Stats</caption>
                <thead>
                <th style="width:20px">#</th>
                <th style="width:80px">Name</th>
                <th style="width:50px">Value</th>
                </thead>
                <?php $index = 1; ?>
                <?php foreach ($total_stats as $key => $value) { ?>
                    <tr>
                        <td class="text-center"><?php echo $index++; ?></td>
                        <td><?php echo $this->get_service_label_by_code($key); ?></td>
                        <td class="text-center"><?php echo $value;?></td>
                    </tr>
                <?php } ?>
                <tr>
                    <td>&nbsp;</td>
                    <td style="text-align:right;font-weight:bold">TOTAL</td>
                    <td style="font-weight:bold;text-align:center"><?php echo $total_result; ?></td>
                </tr>
            </table>
        </div>
        <div style="clear:both"></div>
    <?php } ?>
</div>
