/**
 * مدیریت کننده آپلود تصاویر در پنل ادمین
 * برای استفاده از Media Uploader وردپرس
 */
jQuery(document).ready(function($) {
    // تابع برای آپلود تصویر
    $('.rc-media-upload-button').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var targetField = $(button.data('target'));
        
        // باز کردن media uploader
        var custom_uploader = wp.media({
            title: 'انتخاب تصویر',
            button: {
                text: 'انتخاب تصویر'
            },
            multiple: false
        }).on('select', function() {
            var attachment = custom_uploader.state().get('selection').first().toJSON();
            targetField.val(attachment.url);
            
            // نمایش پیش‌نمایش تصویر
            targetField.closest('.rc-media-uploader-wrapper').find('.rc-media-preview')
                .html('<img src="' + attachment.url + '" style="max-width:200px; height:auto;" />');
        }).open();
    });
});