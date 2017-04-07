require(['jquery'], function ($){
    $(function (){
        var wcLipscoreInit = function (){
            var tabSelector = '#js-lipscore-reviews-tab';
            var tabExists = $(tabSelector).length > 0;
            if (tabExists) {
                // show review count
                lipscore.on('review-count-set', function (data){
                    if (data.value > 0) {
                        $('#js-lipscore-reviews-tab-count').show();
                    }
                });

                // open reviews tab if reviews link clicked
                lipscore.on('review-count-link-clicked', function (data){
                    $(tabSelector).parent().click();
                });
            }
        };

        if (typeof lipscore !== 'undefined') {
            wcLipscoreInit();
        } else {
            $(document).on('lipscore-created', wcLipscoreInit);
        }
    });
});
