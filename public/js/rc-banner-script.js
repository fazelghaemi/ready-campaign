/**
 * rc-banner-script.js
 * اسکریپت بخش کاربری (فرانت‌اند) برای نمایش و مدیریت بنرهای افزونه ردی کمپین.
 * این اسکریپت با بنرهایی که به عنوان Custom Post Type ایجاد شده‌اند کار می‌کند
 * و تنظیمات هر بنر را از data-attributes می‌خواند.
 */
jQuery(document).ready(function($) {

    // انتخاب تمام کانتینرهای بنر که در صفحه وجود دارند
    $('.rc-banner-container').each(function() {
        var $container = $(this);
        var bannerId = $container.data('id') || $container.attr('id'); // شناسه منحصر به فرد بنر

        // --- بررسی اولیه شرایط نمایش (مثلاً کوکی برای بنرهای بسته شده) ---
        // اگر می‌خواهید وضعیت بسته شدن هر بنر را در کوکی ذخیره کنید:
        /*
        var cookieName = 'rc_banner_closed_' + bannerId;
        if (getCookie(cookieName) === '1') {
            $container.remove(); // اگر قبلا بسته شده، نمایش نده
            return; // ادامه به بنر بعدی با .each()
        }
        */

        // --- بررسی نمایش بر اساس دستگاه (از data-attribute) ---
        // این بخش می‌تواند مکمل کلاس‌های CSS (.rc-desktop-only, .rc-mobile-only) باشد
        // یا اگر می‌خواهید بنر به طور کامل از DOM حذف شود، اینجا انجام شود.
        var deviceTarget = $container.data('device-target') || 'all';
        var windowWidth = $(window).width();
        var breakPoint = 768; // نقطه شکست موبایل/دسکتاپ (می‌تواند قابل تنظیم باشد)

        if ( (deviceTarget === 'desktop_only' && windowWidth <= breakPoint) ||
             (deviceTarget === 'mobile_only' && windowWidth > breakPoint) ) {
            $container.remove(); // یا $container.hide() اگر نمی‌خواهید از DOM حذف شود
            return; // ادامه به بنر بعدی
        }

        // --- تابع برای تنظیم موقعیت و ابعاد بنر ---
        var positionAndSizeBanner = function() {
            // دریافت مقادیر از data-attributes با مقادیر پیش‌فرض
            var position = $container.data('position') || 'bottom-right';
            var margin = parseInt($container.data('margin'), 10) || 20;         // فاصله از لبه‌های عمودی (بالا/پایین)
            var sideMargin = parseInt($container.data('side-margin'), 10) || 20; // فاصله از لبه‌های افقی (چپ/راست)
            var desktopWidth = parseInt($container.data('desktop-width'), 10) || 350;
            var mobileWidth = parseInt($container.data('mobile-width'), 10) || 300;
            
            windowWidth = $(window).width(); // به‌روزرسانی عرض پنجره
            var currentWidth = (windowWidth <= breakPoint) ? mobileWidth : desktopWidth;

            $container.css('width', currentWidth + 'px');

            var styles = { top: 'auto', right: 'auto', bottom: 'auto', left: 'auto', transform: 'none' };

            switch (position) {
                case 'bottom-right':
                    styles.bottom = margin + 'px';
                    styles.right = sideMargin + 'px';
                    break;
                case 'bottom-left':
                    styles.bottom = margin + 'px';
                    styles.left = sideMargin + 'px';
                    break;
                case 'top-right':
                    styles.top = margin + 'px';
                    styles.right = sideMargin + 'px';
                    break;
                case 'top-left':
                    styles.top = margin + 'px';
                    styles.left = sideMargin + 'px';
                    break;
                case 'center':
                    styles.top = '50%';
                    styles.left = '50%';
                    styles.transform = 'translate(-50%, -50%)';
                    // برای حالت وسط، margin معمولا کاربردی ندارد مگر اینکه بخواهید از لبه‌ها فاصله ایجاد کنید که پیچیده‌تر می‌شود.
                    break;
                // می‌توانید موقعیت‌های دیگری مانند 'top-center', 'bottom-center' و ... را نیز اضافه کنید.
            }
            $container.css(styles);
        };

        // اجرای اولیه تنظیم موقعیت و ابعاد
        positionAndSizeBanner();

        // تنظیم مجدد موقعیت و ابعاد با تغییر اندازه پنجره (با debounce برای بهبود عملکرد)
        var resizeTimer;
        $(window).on('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                // قبل از تنظیم مجدد، وضعیت نمایش بر اساس دستگاه را دوباره چک کنید
                // (اگر کاربر پنجره را از دسکتاپ به موبایل تغییر اندازه دهد و برعکس)
                windowWidth = $(window).width();
                if ( (deviceTarget === 'desktop_only' && windowWidth <= breakPoint) ||
                     (deviceTarget === 'mobile_only' && windowWidth > breakPoint) ) {
                    $container.hide().css('animation', 'none'); // مخفی کردن و حذف انیمیشن
                } else {
                    if ($container.is(':hidden')) { // اگر قبلا مخفی شده بود، دوباره نمایش بده (بدون انیمیشن اولیه)
                         $container.show().css('animation', 'none'); // انیمیشن ورودی دوباره اجرا نشود
                    }
                    positionAndSizeBanner();
                }
            }, 250); // تاخیر ۲۵۰ میلی‌ثانیه برای debounce
        });

        // --- مدیریت انیمیشن ---
        var animationIn = $container.data('anim-in') || 'fadeIn';   // انیمیشن ورود از data attribute
        var animationOut = $container.data('anim-out') || 'fadeOut'; // انیمیشن خروج از data attribute
        var animationDuration = '0.7s'; // مدت زمان انیمیشن (می‌تواند از data attribute نیز خوانده شود)

        // نمایش بنر با انیمیشن ورود
        // display: block در CSS پایه است یا اگر از flex/grid استفاده می‌کنید، آن را تنظیم کنید.
        // اطمینان حاصل کنید انیمیشن‌ها (keyframes) در فایل rc-style.css تعریف شده‌اند.
        $container.css({
            'display': $container.css('display') === 'none' ? 'block' : $container.css('display'), // اگر display قبلا توسط resize تغییر کرده
            'animation': animationIn + ' ' + animationDuration + ' ease forwards'
        });

        // --- مدیریت دکمه بستن بنر ---
        $container.find('.rc-banner-close').on('click', function(e) {
            e.preventDefault();
            $container.css({
                'animation': animationOut + ' ' + animationDuration + ' ease forwards'
            });

            // حذف کامل بنر از DOM پس از اتمام انیمیشن خروج
            setTimeout(function() {
                $container.remove();
            }, parseFloat(animationDuration) * 1000); // تبدیل مدت زمان به میلی‌ثانیه

            // ذخیره وضعیت بسته شدن در کوکی (اگر از آن استفاده می‌کنید)
            /*
            if (bannerId) { // اطمینان از وجود شناسه برای نام کوکی
                setCookie('rc_banner_closed_' + bannerId, '1', 1); // کوکی برای ۱ روز
            }
            */
        });
    }); // پایان .each() برای هر بنر

    // --- توابع کمکی برای کوکی (در صورت نیاز) ---
    /*
    function setCookie(name, value, days) {
        var expires = "";
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/";
    }

    function getCookie(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }
    */
});
