/**
 * Created by mahabub on 10/8/15.
 */


(function ($) {

    $(document).ready(function () {
        function calculatePercentage(currentNumber, totalNumber) {
            return (( currentNumber / totalNumber )) * 100;
        }


        function importAllPostAjax(currentStep, totalStep, progressBar, processPostPerStep) {
            var data = {
                'action': 'import_post_into_asc',
                'currentStep': currentStep,
                'processPostPerStep': processPostPerStep
            };

            // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
            jQuery.post(ajaxurl, data, function (response) {
                currentStep += 1;
                if (currentStep < totalStep) {
                    var currentNumber = (currentStep * processPostPerStep);
                    progressBar.percent(calculatePercentage(currentNumber, parseInt(ascTotalPost.publish, 10)));
                    importAllPostAjax(currentStep, totalStep, progressBar, processPostPerStep);
                } else {

                    if(typeof  list === 'undefined'){
                        location.reload(true);
                        // return true;
                    } else {

                        var new_data = {
                            action: '_reload_table_data',
                            paged: '1',
                            order: 'asc',
                            orderby: 'title',
                        }

                        list.update(new_data)
                    }
                   // $("#progress-bar").hide();
                   // $(".overlay").hide();
                }
            });
        }

        function _setProgessbar(CurrentPercentage) {
            $("#progress-bar").show();
            var progress = $("#progress-bar").Progress({
                percent: 5,
                fontSize: 16,
                increaseTime: 10000.00 / 500.00,
                increaseSpeed: 2,
                barColor: '#c3d21e',
            });
            return progress;
        }

        function _importAllPostToStats() {
            var totalPost = parseInt(ascTotalPost.publish, 10);
            var processPostPerStep = 10;
            if (totalPost > 0) {
                $(".overlay").show();
                var currentStep = 0;
                var totalStep = Math.ceil(totalPost / processPostPerStep);
                var progressBar = _setProgessbar(calculatePercentage(processPostPerStep, totalPost));
                importAllPostAjax(currentStep, totalStep, progressBar, processPostPerStep);

            }
            return false;
        }


        // click function on import
        $("#import-all-wp-post").click(function (e) {
            e.preventDefault();


            return _importAllPostToStats();
        });


    });

})(jQuery);
