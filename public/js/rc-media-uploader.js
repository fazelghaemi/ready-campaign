/**
 * rc-media-uploader.js
 * مدیریت کننده آپلود تصاویر در پنل ادمین وردپرس.
 * این اسکریپت برای استفاده در متاباکس‌های Custom Post Type بنرها
 * و سایر بخش‌هایی که نیاز به انتخاب تصویر از کتابخانه رسانه دارند، طراحی شده است.
 */
jQuery(document).ready(function($) {
    // رویداد کلیک برای تمام دکمه‌هایی که برای آپلود رسانه در نظر گرفته شده‌اند.
    // اطمینان حاصل کنید که دکمه‌های شما کلاس 'rc-media-upload-button'
    // و اتریبیوت 'data-target' که به ID فیلد متنی هدف اشاره می‌کند را دارند.
    // همچنین، یک والد با کلاس 'rc-media-uploader-wrapper' و یک فرزند با کلاس 'rc-media-preview' برای پیش‌نمایش در نظر بگیرید.

    // برای جلوگیری از تداخل، بهتر است رویداد را به یک والد ثابت که در زمان بارگذاری صفحه وجود دارد، واگذار کنیم (event delegation)
    // این کار مخصوصاً زمانی مفید است که محتوای متاباکس‌ها با AJAX بارگذاری شود (در سناریوی فعلی ما لازم نیست اما روش خوبی است).
    $('body').on('click', '.rc-media-upload-button', function(e) {
        e.preventDefault();

        var $button = $(this);
        var $targetField = $($button.data('target')); // فیلد متنی که URL تصویر در آن ذخیره می‌شود
        var $previewContainer = $button.closest('.rc-media-uploader-wrapper').find('.rc-media-preview');

        if (!$targetField.length) {
            console.error('Ready Campaign Uploader: Target field not found for button:', $button);
            return;
        }

        // باز کردن آپلودر رسانه وردپرس
        var frame = wp.media({
            title: $button.data('uploader-title') || 'انتخاب یا آپلود تصویر', // عنوان پنجره آپلودر
            button: {
                text: $button.data('uploader-button-text') || 'انتخاب تصویر' // متن دکمه انتخاب
            },
            multiple: false // اجازه انتخاب یک تصویر در هر بار
        });

        // هنگامی که یک تصویر انتخاب می‌شود
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();

            // قرار دادن URL تصویر در فیلد متنی هدف
            $targetField.val(attachment.url).trigger('change'); // trigger change برای اینکه سایر اسکریپت‌ها (مثل UTM ساز) متوجه تغییر شوند

            // نمایش پیش‌نمایش تصویر (اگر کانتینر پیش‌نمایش وجود دارد)
            if ($previewContainer.length) {
                var $img = $('<img>').attr('src', attachment.url).css({
                    'max-width': '200px',
                    'max-height': '150px',
                    'height': 'auto',
                    'display': 'block',
                    'margin-top': '10px'
                });
                $previewContainer.html('').append($img); // پاک کردن پیش‌نمایش قبلی و افزودن جدید
            }
        });

        // باز کردن پنجره آپلودر
        frame.open();
    });
});
