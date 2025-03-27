/**
 * اسکریپت‌های بخش مدیریت
 * کنترل‌کننده‌های رابط کاربری و لینک‌ساز UTM
 */
jQuery(document).ready(function($) {
    // کپی کردن لینک UTM
    $('#copy-utm-url').on('click', function() {
        var textArea = $('#utm_result');
        textArea.select();
        document.execCommand('copy');
        
        var originalText = $(this).text();
        $(this).text('کپی شد!');
        
        setTimeout(function() {
            $('#copy-utm-url').text(originalText);
        }, 2000);
    });
    
    // تولید خودکار لینک UTM
    var generateUTM = function() {
        var websiteURL = $('#website_url').val();
        var campaignID = $('#campaign_id').val();
        var campaignSource = $('#campaign_source').val();
        var campaignMedium = $('#campaign_medium').val();
        var campaignName = $('#campaign_name').val();
        var campaignTerm = $('#campaign_term').val();
        var campaignContent = $('#campaign_content').val();
        
        var utmURL = '';
        
        if (websiteURL && campaignSource && campaignMedium) {
            // اطمینان از اینکه آدرس با / پایان می‌یابد
            if (websiteURL.slice(-1) !== '/') {
                websiteURL += '/';
            }
            
            utmURL = websiteURL + '?utm_source=' + encodeURIComponent(campaignSource);
            utmURL += '&utm_medium=' + encodeURIComponent(campaignMedium);
            
            if (campaignID) {
                utmURL += '&utm_id=' + encodeURIComponent(campaignID);
            }
            
            if (campaignName) {
                utmURL += '&utm_campaign=' + encodeURIComponent(campaignName);
            }
            
            if (campaignTerm) {
                utmURL += '&utm_term=' + encodeURIComponent(campaignTerm);
            }
            
            if (campaignContent) {
                utmURL += '&utm_content=' + encodeURIComponent(campaignContent);
            }
        }
        
        $('#utm_result').val(utmURL);
    };
    
    // ایجاد لینک UTM با تغییر فیلدها
    $('#website_url, #campaign_id, #campaign_source, #campaign_medium, #campaign_name, #campaign_term, #campaign_content')
        .on('input', function() {
            generateUTM();
        });
    
    // اعمال لینک UTM به بنر دسکتاپ
    $('#apply-to-desktop').on('click', function() {
        var utmURL = $('#utm_result').val();
        if (utmURL) {
            // ذخیره لینک در فیلد desktop_link در تب دسکتاپ
            $('#desktop_link').val(utmURL);
            
            // نمایش پیام موفقیت
            var originalText = $(this).text();
            $(this).text('لینک در بنر دسکتاپ اعمال شد!');
            
            setTimeout(function() {
                $('#apply-to-desktop').text(originalText);
            }, 2000);
        }
    });
    
    // اعمال لینک UTM به بنر موبایل
    $('#apply-to-mobile').on('click', function() {
        var utmURL = $('#utm_result').val();
        if (utmURL) {
            // ذخیره لینک در فیلد mobile_link در تب موبایل
            $('#mobile_link').val(utmURL);
            
            // نمایش پیام موفقیت
            var originalText = $(this).text();
            $(this).text('لینک در بنر موبایل اعمال شد!');
            
            setTimeout(function() {
                $('#apply-to-mobile').text(originalText);
            }, 2000);
        }
    });
    
    // تولید اولیه لینک UTM در هنگام بارگذاری صفحه
    generateUTM();
});