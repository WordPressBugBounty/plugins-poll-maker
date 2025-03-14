(function($) {
    'use strict';

    $(document).on('click', '[data-slug="poll-maker"] .deactivate a', function () {
        swal({
            html: "<h2>Do you want to upgrade to Pro version or permanently delete the plugin?</h2><ul><li>Upgrade: Your data will be saved for upgrade.</li><li>Deactivate: Your data will be deleted completely.</li></ul>",
            footer: '<a href="" class="ays-poll-temporary-deactivation">Temporary deactivation</a>',
            type: 'question',
            showCancelButton: true,
            showCloseButton: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Upgrade',
            cancelButtonText: 'Deactivate',
            confirmButtonClass: "ays-poll-upgrade-button",
            cancelButtonClass: "ays-poll-cancel-button",
            customClass: ".ays-poll-deactivate-popup",
        }).then((result) => {
            let upgrade_plugin = false;
            if (result.value) upgrade_plugin = true;

            if( result.dismiss && result.dismiss == 'close' ){
                return false;
            }

            var wp_nonce = $(document).find('#ays_poll_maker_ajax_deactivate_plugin_nonce').val();

            let data = {
                action: 'apm_deactivate_plugin_option_pm',
                upgrade_plugin,
                _ajax_nonce: wp_nonce,
            };
            $.ajax({
                url: apm_admin_ajax_obj.ajaxUrl,
                method: "post",
                dataType: 'json',
                data,
                success() {
                    location.replace($('[data-slug="poll-maker"] .deactivate a').attr('href'))
                }
            });
        });
        return false;
    });

    $(document).on('click', '.ays-poll-temporary-deactivation', function (e) {
        e.preventDefault();

        $(document).find('.ays-poll-upgrade-button').trigger('click');

    });

    $('.ays-poll-upgrade-plugin-btn').hover(
        function() {
            $(this).css('color', '#3B8D3F');
        },
        function() {
            $(this).css('color', '#01A32A');
        }
    );

})(jQuery);