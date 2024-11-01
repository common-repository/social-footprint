/**
 * Created by mahabub on 10/10/15.
 */
(function ($) {

    var listProgressBar;
    list = {


        /**
         * Register our triggers
         *
         * We want to capture clicks on specific links, but also value change in
         * the pagination input field. The links contain all the information we
         * need concerning the wanted page number or ordering, so we'll just
         * parse the URL to extract these variables.
         *
         * The page number input is trickier: it has no URL so we have to find a
         * way around. We'll use the hidden inputs added in TT_Example_List_Table::display()
         * to recover the ordering variables, and the default paged input added
         * automatically by WordPress.
         */
        init: function () {
            // This will have its utility when dealing with the page number input
            var timer;
            var delay = 500;

            var total_pages = parseInt($(".tablenav.top .total-pages").text(), 10 );
            if(total_pages > 1 ){
                $(".tablenav-pages").removeClass('no-pages');
                $(".tablenav-pages").removeClass('one-page');

            } else if(total_pages == 1 ){
                $(".tablenav-pages").removeClass('no-pages');
                $(".tablenav-pages").addClass('one-page');
            } else {
                $(".tablenav-pages").removeClass('one-page');
                $(".tablenav-pages").addClass('no-pages');
            }


            $("#cb-select-all-1 , #cb-select-all-2").on('click', function () {
                if ($(this).attr('checked') == 'checked') {
                    $("#cb-select-all-1 , #cb-select-all-2").attr('checked', true);
                    $("input[name='url_status[]']").attr('checked', true);
                } else {
                    $("#cb-select-all-1 , #cb-select-all-2").attr('checked', false);
                    $("input[name='url_status[]']").attr('checked', false);
                }
            });

            $("#doaction").off('click').on('click', function (e) {
                e.preventDefault();
                var doaction = $("#bulk-action-selector-top").val();
                if (isNaN(parseInt(doaction))) {
                    var confirmation = confirm('are you sure, you want to ' + doaction + ' the selected items?');
                    if (confirmation) {
                        var selected_urls = [];
                        $("input[name='url_status[]']:checked").each(function () {
                            selected_urls.push($(this).val());
                        });

                        if (selected_urls.length <= 0) {
                            alert("You did not select any items");
                            return;
                        }
                        var data = {};
                        var progress_percentage = 100;
                        $(".overlay").show();
                        if (doaction == 'delete') {
                            data.progressBar = list.setProgressBar({
                                percent: progress_percentage,
                                increaseTime: 10000.00 / 10000000000000000000.00,
                                increaseSpeed: 20,
                                fontSize: 16,
                                barColor: '#c3d21e'
                            });
                            data.delete_items = selected_urls;
                            data.action = '_delete_selected_urls';
                            if ($("#cb-select-all-1, #cb-select-all-2").attr('checked')) {
                                var val = $('input[name=paged]').val();
                                var current_page = parseInt( val, 10) || 1;
                                if (current_page > 1) {
                                    $('input[name=paged]').val(current_page - 1);
                                }
                            }

                        }


                        if (doaction == 'refresh') {
                            data.delete_items = selected_urls;
                            data.action = '_refresh_selected_urls';
                            data.currentStep = 1;
                            data.processInEachRequest = 2;
                            data.totalStep = Math.ceil(selected_urls.length / 2);
                            data.progressBar = list.setProgressBar({
                                percent: 5,
                                increaseTime: 10000.00 / 1000000.00,
                                increaseSpeed: 2,
                                fontSize: 16,
                                barColor: '#c3d21e'
                            });


                        }


                        if (typeof data.action === 'undefined') {
                            alert('something went wrong');
                            return false;
                        }

                        list.batchProcess(data);
                    }
                }
            });

            $("a.referesh_status").off('click').on('click', function (e) {
                e.preventDefault();
                var result = confirm("Are you sure, you want to refresh the status of this item?");
                if (result) {
                    var data = {
                        paged: parseInt($('input[name=paged]').val(), 10) || '1',
                        order: $('input[name=order]').val() || 'asc',
                        orderby: $('input[name=orderby]').val() || 'title',
                        action: '_refresh_an_item',
                        refresh_url: $(this).attr('href')
                    };
                    $(this).closest("td").append("<span class='spinner is-active'></span>");
                    list.update(data);
                }


            });

            $("a.delete-item").off('click').on('click', function (e) {
                e.preventDefault();
                var result = confirm("Are you sure, you want to delete this item?");
                if (result) {
                    var data = {
                        paged: parseInt($('input[name=paged]').val()) || '1',
                        order: $('input[name=order]').val() || 'asc',
                        orderby: $('input[name=orderby]').val() || 'title',
                        action: '_delete_an_item',
                        delete_url: $(this).attr('href')
                    };
                    list.update(data);
                }


            });

            // Pagination links, sortable link
            $('.tablenav-pages a, .manage-column.sortable a, .manage-column.sorted a').off('click').on('click', function (e) {
                // We don't want to actually follow these links
                e.preventDefault();

                // Simple way: use the URL to extract our needed variables
                var query = this.search.substring(1);


                var data = {
                    paged: list.__query(query, 'paged') || '1',
                    order: list.__query(query, 'order') || 'asc',
                    orderby: list.__query(query, 'orderby') || 'title',
                    action: '_fetch_ajax_response'
                };

                list.update(data);
            });

            // Page number input
            $('input[name=paged]').on('keyup', function (e) {

                // If user hit enter, we don't want to submit the form
                // We don't preventDefault() for all keys because it would
                // also prevent to get the page number!
                if (13 == e.which)
                    e.preventDefault();

                // This time we fetch the variables in inputs
                var data = {
                    paged: parseInt($('input[name=paged]').val()) || '1',
                    order: $('input[name=order]').val() || 'asc',
                    orderby: $('input[name=orderby]').val() || 'title',
                    action: '_fetch_ajax_response'
                };

                // Now the timer comes to use: we wait half a second after
                // the user stopped typing to actually send the call. If
                // we don't, the keyup event will trigger instantly and
                // thus may cause duplicate calls before sending the intended
                // value
                window.clearTimeout(timer);
                timer = window.setTimeout(function () {
                    list.update(data);
                }, delay);
            });
        },


        calculatePercenatge: function (currentStep, totalStep) {
            return (100 / totalStep) * currentStep;
        },

        setProgressBar: function (progressBarData) {
            $("#progress-bar").show();
            listProgressBar = $("#progress-bar").Progress(progressBarData);
        },

        batchProcess: function (data) {
            var new_data = {
                action: '_reload_table_data',
                paged: parseInt($('input[name=paged]').val()) || '1',
                order: $('input[name=order]').val() || 'asc',
                orderby: $('input[name=orderby]').val() || 'title',
            }
            $.post(
                // /wp-admin/admin-ajax.php
                ajaxurl,
                // Add action and nonce to our collected data
                $.extend(
                    {
                        _ajax_custom_list_nonce: $('#_ajax_custom_list_nonce').val(),

                    },
                    data
                ),

                // Handle the successful result
                function ( response ) {
                    if (data.action == '_refresh_selected_urls') {
                        listProgressBar.percent(list.calculatePercenatge(data.currentStep, data.totalStep));
                        if (data.currentStep < data.totalStep) {
                            data.currentStep += 1;
                            list.batchProcess(data);
                        } else {
                            // data.progressBar.percent = 100;
                            list.update(new_data);
                        }

                    } else {

                        list.update(new_data);
                    }

                }
            );
        },

        /** AJAX call
         *
         * Send the call and replace table parts with updated version!
         *
         * @param    object    data The data to pass through AJAX
         */
        update: function (data) {
            $.post(
                // /wp-admin/admin-ajax.php
                ajaxurl + '?orderby='+ data.orderby + '&order='+ data.order,
                // Add action and nonce to our collected data
                $.extend(
                    {
                        _ajax_custom_list_nonce: $('#_ajax_custom_list_nonce').val(),

                    },
                    data
                ),
                // Handle the successful result
                function (response) {
                    var response = $.parseJSON(response);
                    if (response.rows.length)
                        $('#the-list').html(response.rows);
                    // Update column headers for sorting
                    if (response.column_headers.length)
                        $('thead tr, tfoot tr').html(response.column_headers);
                    // Update pagination for navigation
                    if (response.pagination.bottom.length)
                        $('.tablenav.top .tablenav-pages').html($(response.pagination.top).html());
                    if (response.pagination.top.length)
                        $('.tablenav.bottom .tablenav-pages').html($(response.pagination.bottom).html());

                    if (response.total_status.length) {
                        $("#total-share-status").html(response.total_status)
                    }

                    $('input[name=paged]').val(data.paged);
                    $('input[name=order]').val(data.order);
                    $('input[name=orderby]').val(data.orderby);

                    list.init();
                    $("#progress-bar").hide();
                    $(".overlay").hide();
                }
            );
        },

      __query: function (query, variable) {
         var vars = query.split("&");
            for (var i = 0; i < vars.length; i++) {
                var pair = vars[i].split("=");
                if (pair[0] == variable)
                    return pair[1];
            }
            return false;
        }
    };

    $(document).ready(function () {
        list.init();
    });


})(jQuery);
