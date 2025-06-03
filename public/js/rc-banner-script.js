jQuery(document).ready(function($) {
    // به جای یک کانتینر، تمام کانتینرهای بنر را انتخاب می‌کنیم
    $('.rc-banner-container').each(function() {
        var $container = $(this);
        var bannerId = $container.data('id'); // یا $container.attr('id')

        // بررسی اینکه آیا این بنر قبلا بسته شده (اگر از کوکی برای هر بنر استفاده می‌کنید)
        // var isClosed = getCookie('rc_banner_closed_' + bannerId);
        // if (isClosed) {
        //     $container.remove();
        //     return; // ادامه به بنر بعدی
        // }

        // نمایش بر اساس دستگاه از طریق data attribute (می‌تواند با CSS هم ترکیب شود)
        var deviceTarget = $container.data('device-target');
        var windowWidth = $(window).width();

        if (deviceTarget === 'desktop_only' && windowWidth <= 768) {
            $container.remove(); // یا display: none
            return;
        }
        if (deviceTarget === 'mobile_only' && windowWidth > 768) {
            $container.remove(); // یا display: none
            return;
        }
        
        // تابع تنظیم موقعیت بر اساس data-attributes (مشابه کد فعلی اما برای this.$container)
        var positionBanner = function() {
            var position = $container.data('position') || 'bottom-right';
            var margin = $container.data('margin') || 20;
            var desktopWidth = $container.data('desktop-width') || 350;
            var mobileWidth = $container.data('mobile-width') || 300;
            var currentWidth = (windowWidth <= 768) ? mobileWidth : desktopWidth;

            $container.css('width', currentWidth + 'px');

            // بازنویسی استایل‌های موقعیت‌دهی از CSS بر اساس data-position
            // کد فعلی شما در rc-style.css این کار را با [data-desktop-position] انجام می‌دهد که خوب است.
            // فقط باید اطمینان حاصل کنید که این data attribute به درستی به $container اصلی داده شده.
            // $container.removeClass('pos-bottom-right pos-bottom-left ...').addClass('pos-' + position);
            // یا استایل‌های inline اگر لازم است:
            switch(position) {
                case 'bottom-right':
                    $container.css({ bottom: margin + 'px', right: '20px', top: 'auto', left: 'auto' });
                    break;
                case 'bottom-left':
                    $container.css({ bottom: margin + 'px', left: '20px', top: 'auto', right: 'auto' });
                    break;
                // ... سایر موقعیت‌ها
                case 'center':
                     $container.css({ top: '50%', left: '50%', transform: 'translate(-50%, -50%)', bottom: 'auto', right: 'auto' });
                    break;
            }
        };

        positionBanner(); // اجرای اولیه
        $(window).on('resize', positionBanner); // تنظیم مجدد با تغییر اندازه

        var animationIn = $container.data('anim-in') || 'fadeIn';
        var animationOut = $container.data('anim-out') || 'fadeOut';
        
        // نمایش بنر
        $container.css({
            'display': 'block', // یا 'flex' or 'grid' بسته به ساختار داخلی
            'animation': animationIn + ' 0.7s ease forwards'
        });

        // دکمه بستن برای هر بنر
        $container.find('.rc-banner-close').on('click', function() {
            $container.css({
                'animation': animationOut + ' 0.7s ease forwards'
            });
            setTimeout(function() {
                $container.remove();
            }, 700);
            // setCookie('rc_banner_closed_' + bannerId, '1', 1); // ذخیره وضعیت بسته شدن برای هر بنر
        });
    });

    // تابع getCookie (اگر برای هر بنر کوکی جداگانه نیاز دارید)
    // function getCookie(name) { ... }
    // function setCookie(name, value, days) { ... }
});