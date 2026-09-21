(function($){
  // Device tabs keep desktop and mobile content focused without duplicating the form.
  function selectDevice(device){
    $('.rc-device-tab').each(function(){
      var active = $(this).data('device-tab') === device;
      $(this).toggleClass('is-active', active).attr('aria-selected', active ? 'true' : 'false');
    });
    $('.rc-device-field').each(function(){
      $(this).toggleClass('is-hidden', !$(this).hasClass('rc-device-'+device));
    });
  }
  $(document).on('click', '.rc-device-tab', function(){ selectDevice($(this).data('device-tab')); });
  $(function(){ selectDevice('desktop'); });

  $(document).on('click', '#rc_clear_rules', function(){
    ['rc_start','rc_end','rc_days','rc_time_start','rc_time_end','rc_require_utm_source','rc_referrer_contains','rc_include_urls','rc_exclude_urls'].forEach(function(name){
      $('[name="'+name+'"]').val('');
    });
    $(this).text('شرایط پاک شد');
    setTimeout(function(){ $('#rc_clear_rules').text('پاک‌کردن شرایط'); }, 1400);
  });

  // Media uploader
  $(document).on('click', '.rcp-media', function(e){
    e.preventDefault();
    var target = $(this).data('target');
    var $wrap = $(this).closest('p');
    var frame = wp.media({title:'انتخاب تصویر', button:{text:'استفاده از این تصویر'}, multiple:false});
    frame.on('select', function(){
      var att = frame.state().get('selection').first().toJSON();
      $wrap.find('input[name="'+target+'"]').val(att.id);
      $wrap.find('img').attr('src', att.url);
      $('#rc_live_img').attr('src', att.url);
    });
    frame.open();
  });

  // UTM builder
  function build(){
    var base = $('#rc_base').val().trim();
    if(!base) return '';
    var url;
    try { url = new URL(base, window.location.origin); } catch(e) { return ''; }
    var map = {
      utm_source:  $('#rc_source').val().trim(),
      utm_medium:  $('#rc_medium').val().trim(),
      utm_campaign:$('#rc_campaign').val().trim(),
      utm_id:      $('#rc_id').val().trim(),
      utm_term:    $('#rc_term').val().trim(),
      utm_content: $('#rc_content').val().trim()
    };
    Object.keys(map).forEach(function(k){ if(map[k]) url.searchParams.set(k, map[k]); });
    return url.toString();
  }
  $('#rc_build').on('click', function(){
    var result = build();
    $('#rc_result').val(result);
    if(!result) alert('لطفاً یک آدرس معتبر وارد کنید.');
  });
  $('#rc_copy').on('click', function(){
    var value = $('#rc_result').val();
    if(!value) return;
    if(navigator.clipboard) navigator.clipboard.writeText(value);
    else { var $i = $('#rc_result'); $i[0].select(); document.execCommand('copy'); }
    $(this).text('کپی شد ✓'); setTimeout(function(){ $(this).text('کپی لینک'); }.bind(this), 1200);
  });
  function apply(device){
    var url = $('#rc_result').val().trim();
    var id  = $('#rc_banner').val();
    if(!url || !id){ alert('لینک و بنر را مشخص کنید'); return; }
    $.post(RCAdmin.applyUrl, {nonce:RCAdmin.nonce, banner_id:id, url:url, device:device}, function(res){
      alert(res && res.success ? 'لینک روی بنر اعمال شد' : 'خطا در اعمال لینک');
    });
  }
  $('#rc_apply_desktop').on('click', function(){ apply('desktop'); });
  $('#rc_apply_mobile').on('click', function(){ apply('mobile'); });

  // Live Preview bindings
  function updatePreview(){
    var pos   = $('select[name="rc_position"]').val() || 'br';
    var w     = $('input[name="rc_width"]').val() || '320px';
    var ox    = $('input[name="rc_offset_x"]').val() || '16px';
    var oy    = $('input[name="rc_offset_y"]').val() || '16px';
    var rad   = $('input[name="rc_radius"]').val() || '12px';
    var animI = $('select[name="rc_anim_in"]').val() || 'fade';

    var $b = $('#rc_live_stage .rc-banner');
    $b.removeClass(function(i,c){ return (c.match(/rc-pos-\S+/g)||[]).join(' '); })
      .addClass('rc-pos-'+pos)
      .removeClass(function(i,c){ return (c.match(/rc-in-\S+/g)||[]).join(' '); })
      .addClass('rc-in-'+animI)
      .attr('style','width:'+w+';border-radius:'+rad+';--rc-ox:'+ox+';--rc-oy:'+oy+';');
  }
  $(document).on('input change', 'select[name="rc_position"], input[name="rc_width"], input[name="rc_offset_x"], input[name="rc_offset_y"], input[name="rc_radius"], select[name="rc_anim_in"]', updatePreview);
  $(updatePreview);
})(jQuery);
