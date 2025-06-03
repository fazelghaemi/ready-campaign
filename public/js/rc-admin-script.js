/**
 * rc-admin-script.js
 * اسکریپت‌های بخش مدیریت افزونه ردی کمپین.
 * شامل منطق برای لینک‌ساز UTM و سایر تعاملات UI در بخش مدیریت.
 */
jQuery(document).ready(function($) {

    // --- بخش لینک‌ساز کمپین (UTM Builder) ---
    // این بخش فرض می‌کند که IDهای فیلدهای HTML در تب UTM Builder ثابت باقی مانده‌اند.

    var $utmResultTextarea = $('#utm_result'); // فیلد متنی برای نمایش لینک نهایی UTM

    // کپی کردن لینک UTM به کلیپ‌بورد
    $('#copy-utm-url').on('click', function(e) {
        e.preventDefault();
        if (!$utmResultTextarea.length || !$utmResultTextarea.val()) return;

        $utmResultTextarea.select();
        document.execCommand('copy');

        var $button = $(this);
        var originalText = $button.text();
        $button.text('کپی شد!');

        setTimeout(function() {
            $button.text(originalText);
        }, 2000);
    });

    // تابع برای تولید خودکار لینک UTM
    var generateUTM = function() {
        if (!$utmResultTextarea.length) return; // اگر فیلد نتیجه وجود ندارد، خارج شو

        // دریافت مقادیر از فیلدهای ورودی UTM
        // اطمینان حاصل کنید این IDها در فایل view مربوط به تب UTM Builder وجود دارند.
        var websiteURL = $('#rc_utm_website_url').val() || ''; // مثال برای ID جدید احتمالی
        var campaignSource = $('#rc_utm_campaign_source').val() || '';
        var campaignMedium = $('#rc_utm_campaign_medium').val() || '';
        var campaignName = $('#rc_utm_campaign_name').val() || '';
        var campaignID = $('#rc_utm_campaign_id').val() || ''; // قبلا campaign_id بود
        var campaignTerm = $('#rc_utm_campaign_term').val() || '';
        var campaignContent = $('#rc_utm_campaign_content').val() || '';

        var utmURL = '';

        if (websiteURL && campaignSource && campaignMedium) {
            // اطمینان از اینکه آدرس با / پایان نمی‌یابد و http/https دارد
            if (!websiteURL.match(/^https?:\/\//)) {
                // اگر کاربر پروتکل را وارد نکرده، می‌توانید پیش‌فرض http یا https را اضافه کنید
                // یا یک پیام خطا نمایش دهید. فعلا فرض می‌کنیم کاربر آدرس کامل را وارد می‌کند.
            }
            if (websiteURL.slice(-1) === '/') { // اگر با / تمام می‌شود، آن را حذف کن تا دوباره اضافه نشود
                // websiteURL = websiteURL.slice(0, -1);
            }

            // استفاده از URLSearchParams برای ساخت صحیح query string
            var params = new URLSearchParams();
            params.append('utm_source', campaignSource);
            params.append('utm_medium', campaignMedium);

            if (campaignName) params.append('utm_campaign', campaignName);
            if (campaignID) params.append('utm_id', campaignID);
            if (campaignTerm) params.append('utm_term', campaignTerm);
            if (campaignContent) params.append('utm_content', campaignContent);
            
            try {
                // برای مدیریت صحیح آدرس پایه و پارامترها
                var baseURL = new URL(websiteURL);
                baseURL.search = params.toString(); // پارامترهای موجود را بازنویسی می‌کند
                utmURL = baseURL.toString();
            } catch (e) {
                // اگر websiteURL یک URL معتبر نباشد، ممکن است خطا رخ دهد
                // در این حالت، از روش قدیمی‌تر استفاده می‌کنیم یا خطا نمایش می‌دهیم
                // برای سادگی، ترکیب رشته‌ای:
                utmURL = websiteURL + (websiteURL.includes('?') ? '&' : '?') + params.toString();
            }
        }
        $utmResultTextarea.val(utmURL);
    };

    // تولید لینک UTM با هر تغییر در فیلدهای ورودی UTM
    // ID های فیلدها باید با ID های واقعی در HTML شما مطابقت داشته باشند.
    var utmInputFields = '#rc_utm_website_url, #rc_utm_campaign_source, #rc_utm_campaign_medium, #rc_utm_campaign_name, #rc_utm_campaign_id, #rc_utm_campaign_term, #rc_utm_campaign_content';
    $(document).on('input change', utmInputFields, function() {
        generateUTM();
    });

    // تولید اولیه لینک UTM در هنگام بارگذاری صفحه (اگر مقادیری از قبل وجود دارد)
    if ($(utmInputFields).length) { // فقط اگر فیلدهای UTM در صفحه وجود دارند
        generateUTM();
    }


    // --- بخش اعمال لینک UTM به بنرها (نیاز به بازنگری کامل) ---
    // دکمه‌های #apply-to-desktop و #apply-to-mobile در فرمت قبلی که بنرها
    // تنظیمات سراسری داشتند، دیگر کاربردی نیستند، زیرا هر بنر CPT فیلد لینک خود را دارد.

    /*
    // کد مربوط به دکمه‌های اعمال به بنر سراسری حذف شده است.
    // $('#apply-to-desktop').on('click', function() { ... });
    // $('#apply-to-mobile').on('click', function() { ... });

    // راهکارهای پیشنهادی برای معماری جدید (مبتنی بر CPT):
    // ۱. حذف کامل این دکمه‌ها: کاربر لینک ساخته شده را به صورت دستی از #utm_result کپی
    //    و در فیلد "لینک بنر" در متاباکس CPT بنر مورد نظر پیست می‌کند. این ساده‌ترین راه است.

    // ۲. دکمه "اعمال به بنر انتخاب شده" در تب UTM Builder:
    //    - یک لیست کشویی (dropdown) از تمام CPTهای بنر موجود به تب UTM Builder اضافه کنید.
    //    - پس از انتخاب بنر از لیست و ساخت لینک UTM، با کلیک روی یک دکمه "اعمال"،
    //      از طریق AJAX متادیتای 'link_url' آن CPT بنر را با لینک UTM ساخته شده به‌روز کنید.
    //    - این نیازمند کدنویسی PHP برای مدیریت درخواست AJAX و به‌روزرسانی متادیتا است.
    //    مثال HTML (در فایل view تب UTM):
    //    <select id="rc_apply_utm_to_banner_select"> <options with banner CPTs> </select>
    //    <button id="rc_apply_utm_to_selected_banner_button">اعمال به بنر انتخابی</button>
    //    مثال JS (در اینجا):
    //    $('#rc_apply_utm_to_selected_banner_button').on('click', function() {
    //        var selectedBannerId = $('#rc_apply_utm_to_banner_select').val();
    //        var utmLink = $utmResultTextarea.val();
    //        if (selectedBannerId && utmLink) {
    //            // ارسال درخواست AJAX به وردپرس برای ذخیره utmLink به عنوان متادیتای بنر selectedBannerId
    //            $.post(ajaxurl, {
    //                action: 'rc_apply_utm_to_banner', // نام اکشن AJAX شما
    //                nonce: 'your_ajax_nonce',        // Nonce برای امنیت
    //                banner_id: selectedBannerId,
    //                utm_link: utmLink
    //            }, function(response) {
    //                if(response.success) {
    //                    alert('لینک UTM با موفقیت به بنر اعمال شد.');
    //                } else {
    //                    alert('خطا در اعمال لینک: ' + response.data.message);
    //                }
    //            });
    //        }
    //    });

    // ۳. دکمه "استفاده از لینک‌ساز UTM" در متاباکس هر CPT بنر:
    //    - در کنار فیلد "لینک بنر" در متاباکس هر CPT، یک دکمه قرار دهید.
    //    - با کلیک روی آن، یک مودال باز شود که کامپوننت UTM Builder را نمایش دهد.
    //    - پس از ساخت لینک در مودال، با کلیک روی دکمه "اعمال"، لینک به فیلد "لینک بنر" همان CPT منتقل شود.
    //    - این روش تجربه کاربری خوبی دارد اما پیاده‌سازی مودال و ارتباط بین مودال و فیلد متاباکس پیچیده‌تر است.
    */

    // --- سایر اسکریپت‌های بخش مدیریت ---
    // در اینجا می‌توانید کدهای JS دیگری که برای رابط کاربری بخش مدیریت نیاز دارید اضافه کنید.
    // مثلاً برای نمایش/مخفی کردن شرطی فیلدها در متاباکس‌های CPT بنر،
    // یا برای کنترلرهای UI در صفحات تنظیمات جدید (داشبورد کمپین، گزارش‌ها و ...).

    // مثال: مدیریت تب‌ها در صفحه تنظیمات اصلی (اگر از روش PHP برای کلاس 'nav-tab-active' استفاده نمی‌کنید)
    // $('.nav-tab-wrapper a.nav-tab').on('click', function(e) {
    //     // این بخش اگر PHP به درستی تب فعال را مدیریت می‌کند، لازم نیست.
    //     // کد فعلی PHP شما برای مدیریت تب فعال با پارامتر ?tab=... خوب است.
    // });

});
