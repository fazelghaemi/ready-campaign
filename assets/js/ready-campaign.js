(function(){
  function sess() {
    try {
      var k='rc_sess';
      var s=localStorage.getItem(k);
      if(!s){ s=(Math.random().toString(36).slice(2))+Date.now().toString(36); localStorage.setItem(k,s); }
      return s;
    } catch(e){ return ''; }
  }
  function capKey(id){ return 'rc_cap_'+id+'_'+(new Date().toISOString().slice(0,10)); }
  function muteKey(id){ return 'rc_mute_'+id; }
  function send(ev, id){
    var data = new FormData();
    data.append('action','rc_track');
    data.append('type', ev);
    data.append('banner_id', id);
    data.append('page', location.pathname);
    data.append('ref', document.referrer || '');
    var utm = (new URLSearchParams(location.search)).get('utm_source') || '';
    data.append('utm', utm);
    data.append('sess', sess());
    fetch(RCVars.ajax, {method:'POST', body:data, credentials:'same-origin', keepalive:true});
  }

  function inViewportOnce(el, cb){
    if(!('IntersectionObserver' in window)){ cb(); return; }
    var once=false;
    var io = new IntersectionObserver(function(entries){
      entries.forEach(function(e){
        if(!once && e.isIntersecting){
          once=true;
          io.disconnect();
          cb();
        }
      });
    }, {threshold: 0.25});
    io.observe(el);
  }

  function applySafeAreas(){
    var root = document.querySelector('.rc-root');
    if(!root) return;
    ['top','right','bottom','left'].forEach(function(side){
      var v = RCVars && RCVars.safe ? RCVars.safe[side] : '0px';
      root.style.setProperty('--rc-safe-'+side, v || '0px');
    });
  }

  function showWithTriggers(b){
    var id = b.getAttribute('data-banner-id');
    var delay = parseInt(b.getAttribute('data-delay')||'0',10);
    var scrollP = parseInt(b.getAttribute('data-scroll')||'0',10);
    var exit = (b.getAttribute('data-exit')||'0')==='1';
    var cap = parseInt(b.getAttribute('data-capday')||'0',10);
    var muteDays = parseInt(b.getAttribute('data-mutedays')||'0',10);

    try{
      var mk = muteKey(id);
      var mutedUntil = parseInt(localStorage.getItem(mk)||'0',10);
      if(mutedUntil && Date.now() < mutedUntil){ b.remove(); return; }
      if(cap>0){
        var ck = capKey(id);
        var seen = parseInt(localStorage.getItem(ck)||'0',10);
        if(seen >= cap){ b.remove(); return; }
      }
    }catch(e){}

    b.style.display='none';

    var done=false;
    function doReveal(){
      if(done) return;
      done=true;
      setTimeout(function(){
        b.style.display='';
        inViewportOnce(b, function(){
          send('impression', id);
          try{
            if(cap>0){
              var ck = capKey(id);
              var seen = parseInt(localStorage.getItem(ck)||'0',10);
              localStorage.setItem(ck, String((seen||0)+1));
            }
          }catch(e){}
        });
      }, Math.max(0, delay||0));
    }

    if(scrollP>0){
      var onScroll = function(){
        var scrolled = (window.scrollY || window.pageYOffset || 0);
        var h = Math.max(document.body.scrollHeight, document.documentElement.scrollHeight);
        var vp = window.innerHeight || document.documentElement.clientHeight;
        var perc = Math.round(((scrolled+vp)/h)*100);
        if(perc >= scrollP){ window.removeEventListener('scroll', onScroll); doReveal(); }
      };
      window.addEventListener('scroll', onScroll);
    } else if(exit){
      var onMove = function(e){
        if(e.clientY < 10){ document.removeEventListener('mousemove', onMove); doReveal(); }
      };
      document.addEventListener('mousemove', onMove);
    } else {
      doReveal();
    }

    var closeBtn = b.querySelector('.rc-close');
    if(closeBtn){
      closeBtn.addEventListener('click', function(){
        var out = (b.className.match(/rc-out-(fade|slide|zoom|bounce)/) || [])[0] || 'rc-out-fade';
        b.classList.add(out+'-leave');
        setTimeout(function(){ b.remove(); }, 380);
        if(muteDays>0){
          try{
            var mk = muteKey(id);
            var until = Date.now() + muteDays*24*3600*1000;
            localStorage.setItem(mk, String(until));
          }catch(e){}
        }
      });
    }

    var link = b.querySelector('a.rc-link');
    if(link){
      link.addEventListener('click', function(){ send('click', id); });
    }
  }

  document.addEventListener('DOMContentLoaded', function(){
    applySafeAreas();
    document.querySelectorAll('.rc-banner').forEach(showWithTriggers);
  });
})();
