/**
 * اسکریپت نمایش بنر در فرانت‌اند سایت
 * مدیریت انیمیشن‌ها و عملکرد دکمه بستن
 */
jQuery(document).ready(function($) {
    var $container = $('#rc-banner-container');
    
    if ($container.length) {
        // تنظیم موقعیت بنر
        var positionContainer = function() {
            var windowWidth = $(window).width();
            var position;
            
            if (windowWidth <= 768) {
                // استفاده از موقعیت موبایل در صفحات کوچک
                position = $container.data('mobile-position');
                $container.attr('data-active-position', position);
            } else {
                // استفاده از موقعیت دسکتاپ در صفحات بزرگ
                position = $container.data('desktop-position');
                $container.attr('data-active-position', position);
            }
        };
        
        // اجرای اولیه تنظیم موقعیت
        positionContainer();
        
        // تنظیم مجدد موقعیت با تغییر اندازه صفحه
        $(window).on('resize', function() {
            positionContainer();
        });
        
        // تعیین نوع انیمیشن برای نمایش و مخفی کردن
        var animationIn, animationOut;
        
        if ($(window).width() <= 768) {
            // انیمیشن‌های موبایل
            animationIn = $container.css('--mobile-animation-in');
            animationOut = $container.css('--mobile-animation-out');
        } else {
            // انیمیشن‌های دسکتاپ
            animationIn = $container.css('--desktop-animation-in');
            animationOut = $container.css('--desktop-animation-out');
        }
        
        // پیش‌فرض در صورت عدم تعریف انیمیشن
        if (!animationIn || animationIn === '') animationIn = 'fadeIn';
        if (!animationOut || animationOut === '') animationOut = 'fadeOut';
        
        // برای مقایسه از فرمت خالص استفاده می‌کنیم (بدون نقل قول)
        animationIn = animationIn.replace(/['"]/g, '');
        animationOut = animationOut.replace(/['"]/g, '');
        
        // نمایش بنر با انیمیشن مناسب
        $container.css({
            'display': 'block',
            'animation': animationIn + ' 0.7s ease forwards'
        });
        
        // ذخیره بنر در کوکی به مدت یک روز
        var setCookie = function(name, value, days) {
            var expires = '';
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = '; expires=' + date.toUTCString();
            }
            document.cookie = name + '=' + (value || '') + expires + '; path=/';
        };
        
        // رویداد کلیک روی دکمه بسته‌شدن
        $container.find('.rc-banner-close').on('click', function() {
            // مخفی کردن بنر با انیمیشن مناسب
            $container.css({
                'animation': animationOut + ' 0.7s ease forwards'
            });
            
            // حذف بنر پس از اتمام انیمیشن
            setTimeout(function() {
                $container.remove();
            }, 700);
            
            // ذخیره وضعیت بستن در کوکی
            setCookie('rc_banner_closed', '1', 1);
        });
    }
});