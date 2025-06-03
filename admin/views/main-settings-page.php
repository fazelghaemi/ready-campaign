<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    
    <h2 class="nav-tab-wrapper">
        <?php /* تب لینک ساز UTM */ ?>
        <a href="?page=rc-main-settings&tab=utm_builder" 
           class="nav-tab <?php echo $this->active_tab == 'utm_builder' ? 'nav-tab-active' : ''; ?>">
            <?php _e( 'لینک ساز کمپین', 'ready-campaign' ); ?>
        </a>
        
        <?php /* تب تنظیمات عمومی - اگر لازم شد */ ?>
        <a href="?page=rc-main-settings&tab=global_settings" 
           class="nav-tab <?php echo $this->active_tab == 'global_settings' ? 'nav-tab-active' : ''; ?>">
            <?php _e( 'تنظیمات عمومی', 'ready-campaign' ); ?>
        </a>
        <?php // سایر تب‌ها در آینده (مثلاً گزارش‌ها، یکپارچه‌سازی و...) ?>
    </h2>
    
    <form method="post" action="options.php">
        <?php
            if ( $this->active_tab == 'utm_builder' ) {
                settings_fields( 'rc_utm_option_group' ); // گروه آپشن UTM
                do_settings_sections( 'rc-utm-settings-admin' ); // شناسه بخش UTM
            } elseif ( $this->active_tab == 'global_settings' ) {
                // settings_fields( 'rc_global_option_group' );
                // do_settings_sections( 'rc-global-settings-admin' );
                echo "<p>" . __( 'تنظیمات عمومی افزونه در اینجا قرار خواهند گرفت.', 'ready-campaign' ) . "</p>";
            }
            // ... سایر تب‌ها
            
            // دکمه ذخیره فقط برای تب‌هایی که از Settings API استفاده می‌کنند
            if ( $this->active_tab == 'utm_builder' || $this->active_tab == 'global_settings' /* && group exists */ ) {
                submit_button();
            }
        ?>
    </form>
</div>