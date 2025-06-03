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
        // شناسه منحصر به فرد بنر از data-id خوانده می‌شود (مثلاً "rc-banner-123")
        // اگر می‌خواهید فقط خود ID عددی را داشته باشید، باید آن را از رشته استخراج کنید.
        // فعلاً از همان data-id برای نام کوکی (در صورت استفاده) استفاده می‌کنیم.
        var bannerDataId = $container.data('id');

        // --- بررسی اولیه شرایط نمایش (مثلاً کوکی برای بنرهای بسته شده) ---
        var cookieName = 'rc_banner_closed_' + bannerDataId;
        if (getCookie(cookieName) === '1') {
            $container.remove(); // اگر قبلا بسته شده، نمایش نده
            return; // ادامه به بنر بعدی با .each()
        }

        // --- بررسی نمایش بر اساس دستگاه (از data-attribute) ---
        var deviceTarget = $container.data('device-target') || 'all';
        var windowWidth = $(window).width();
        var breakPoint = 768; // نقطه شکست موبایل/دسکتاپ (می‌تواند قابل تنظیم باشد)

        if ( (deviceTarget === 'desktop_only' && windowWidth <= breakPoint) ||
             (deviceTarget === 'mobile_only' && windowWidth > breakPoint) ) {
            // $container.remove(); // یا $container.hide() اگر نمی‌خواهید از DOM حذف شود
            // به جای حذف، بهتر است با display:none از طریق CSS یا اینجا کنترل شود،
            // تا در صورت resize، وضعیت دوباره بررسی شود.
            // CSS ارائه شده قبلی با کلاس‌های rc-desktop-only/rc-mobile-only این کار را انجام می‌دهد اگر آن کلاس‌ها توسط PHP اضافه شوند
            // یا اگر CSS بر اساس خود data-device-target استایل‌دهی کند.
            // اگر JS باید نمایش اولیه را کنترل کند:
            $container.hide(); // اگر شرایط دستگاه را ندارد، مخفی کن
            // در resize مجددا چک می‌شود
        }

        // --- تابع برای تنظیم موقعیت و ابعاد بنر ---
        var positionAndSizeBanner = function() {
            // به‌روزرسانی عرض پنجره در هر بار اجرا
            windowWidth = $(window).width();

            // اگر بنر به دلیل شرایط دستگاه نباید نمایش داده شود، محاسبات را انجام نده
            if ( (deviceTarget === 'desktop_only' && windowWidth <= breakPoint) ||
                 (deviceTarget === 'mobile_only' && windowWidth > breakPoint) ) {
                if ($container.is(':visible')) { // اگر قبلا نمایش داده شده بود
                    $container.hide().css('animation', 'none');
                }
                return; // خارج شو
            } else {
                if ($container.is(':hidden') && !($container.data('rc-closed-by-user'))) { // اگر مخفی بود و توسط کاربر بسته نشده بود
                     // $container.show(); // نمایش بده - انیمیشن اولیه را دوباره فعال نکنیم
                }
            }

            // دریافت مقادیر از data-attributes با مقادیر پیش‌فرض
            var position = $container.data('position') || 'bottom-right';
            var marginVertical = parseInt($container.data('margin'), 10) || 20;
            var marginHorizontal = parseInt($container.data('side-margin'), 10) || 20; // استفاده از data-side-margin
            var desktopWidth = parseInt($container.data('desktop-width'), 10) || 350;
            var mobileWidth = parseInt($container.data('mobile-width'), 10) || 300;

            var currentWidth = (windowWidth <= breakPoint) ? mobileWidth : desktopWidth;

            $container.css('width', currentWidth + 'px');

            var styles = { top: 'auto', right: 'auto', bottom: 'auto', left: 'auto', transform: 'none' };

            switch (position) {
                case 'bottom-right':
                    styles.bottom = marginVertical + 'px';
                    styles.right = marginHorizontal + 'px';
                    break;
                case 'bottom-left':
                    styles.bottom = marginVertical + 'px';
                    styles.left = marginHorizontal + 'px';
                    break;
                case 'top-right':
                    styles.top = marginVertical + 'px';
                    styles.right = marginHorizontal + 'px';
                    break;
                case 'top-left':
                    styles.top = marginVertical + 'px';
                    styles.left = marginHorizontal + 'px';
                    break;
                case 'center':
                    styles.top = '50%';
                    styles.left = '50%';
                    styles.transform = 'translate(-50%, -50%)';
                    break;
                case 'sticky-top':
                    styles.top = '0';
                    styles.left = '0'; // یا 50% با transform برای وسط چین افقی
                    styles.right = '0'; // یا 50%
                    styles.width = '100%'; // برای نوارهای تمام عرض
                    // marginVertical و marginHorizontal ممکن است برای این حالت معنای دیگری داشته باشند یا استفاده نشوند
                    // یا برای padding داخلی کانتینر استفاده شوند.
                    // اگر می‌خواهید وسط چین باشد:
                    // styles.left = '50%';
                    // styles.transform = 'translateX(-50%)';
                    // styles.width = currentWidth + 'px'; // اگر نباید تمام عرض باشد
                    break;
                case 'sticky-bottom':
                    styles.bottom = '0';
                    styles.left = '0';
                    styles.right = '0';
                    styles.width = '100%';
                    // styles.left = '50%';
                    // styles.transform = 'translateX(-50%)';
                    // styles.width = currentWidth + 'px';
                    break;
            }
            $container.css(styles);
        };

        // اجرای اولیه تنظیم موقعیت و ابعاد (فقط اگر بنر از ابتدا مخفی نبوده به دلیل شرایط دستگاه)
        if ($container.is(':hidden') &&
            !( (deviceTarget === 'desktop_only' && $(window).width() <= breakPoint) ||
               (deviceTarget === 'mobile_only' && $(window).width() > breakPoint) ) ){
            // اگر مخفی است اما نباید به دلیل شرایط دستگاه مخفی باشد (یعنی توسط style="display:none;" اولیه مخفی شده)
            // positionAndSizeBanner(); // موقعیت را تنظیم کن قبل از نمایش
        } else if ($container.is(':visible')) {
            positionAndSizeBanner();
        }


        // تنظیم مجدد موقعیت و ابعاد با تغییر اندازه پنجره (با debounce برای بهبود عملکرد)
        var resizeTimer;
        $(window).on('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                positionAndSizeBanner(); // این تابع خودش وضعیت نمایش را بر اساس عرض جدید چک می‌کند
            }, 250);
        });

        // --- مدیریت انیمیشن ---
        var animationIn = $container.data('anim-in') || 'fadeIn';
        var animationOut = $container.data('anim-out') || 'fadeOut';
        var animationDuration = '0.7s';

        // نمایش بنر با انیمیشن ورود (فقط اگر به دلیل شرایط دستگاه مخفی نشده باشد)
        if (! ( (deviceTarget === 'desktop_only' && windowWidth <= breakPoint) ||
                (deviceTarget === 'mobile_only' && windowWidth > breakPoint) ) ) {
            
            positionAndSizeBanner(); // اطمینان از تنظیم موقعیت قبل از انیمیشن
            
            if (animationIn !== 'none') {
                $container.css({
                    'display': 'block', // یا flex/grid بسته به نیاز
                    'animation': animationIn + ' ' + animationDuration + ' ease forwards'
                });
            } else {
                $container.css('display', 'block'); // نمایش بدون انیمیشن
            }
        }


        // --- مدیریت دکمه بستن بنر ---
        $container.find('.rc-banner-close').on('click', function(e) {
            e.preventDefault();
            $container.data('rc-closed-by-user', true); // علامت‌گذاری برای resize handler

            if (animationOut !== 'none') {
                $container.css({
                    'animation': animationOut + ' ' + animationDuration + ' ease forwards'
                });
                setTimeout(function() {
                    $container.remove();
                }, parseFloat(animationDuration) * 1000);
            } else {
                $container.remove(); // حذف بدون انیمیشن
            }

            // ذخیره وضعیت بسته شدن در کوکی
            if (bannerDataId) {
                setCookie(cookieName, '1', 1); // کوکی برای ۱ روز
            }
        });
    }); // پایان .each() برای هر بنر

    // --- توابع کمکی برای کوکی ---
    function setCookie(name, value, days) {
        var expires = "";
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/; SameSite=Lax"; // SameSite برای امنیت
    }

    function getCookie(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }
});
