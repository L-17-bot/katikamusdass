/* morph.js - initialize morph transitions site-wide */
(function(){
    // Inject preload and overlay markup
    function injectHelpers(){
        if(!document.getElementById('km-preloader')){
            const pre = document.createElement('div');
            pre.id = 'km-preloader';
            pre.innerHTML = '<div class="loader" aria-hidden="true"></div>';
            document.body.appendChild(pre);
        }
        if(!document.getElementById('km-page-overlay')){
            const ov = document.createElement('div');
            ov.id = 'km-page-overlay';
            document.body.appendChild(ov);
        }
    }

    // Apply automatic classes to elements so we don't need to edit HTML heavily
    function autoAnnotate(){
        // apply morph to headings, paragraphs, sections
        document.querySelectorAll('h1,h2,h3,h4, .feature-card h3, .mission-card h3').forEach(el=>{
            el.classList.add('morph','morph-strong');
            // make primary headings wave
            if(el.tagName.toLowerCase()==='h1' || el.classList.contains('hero-title')){
                el.classList.add('wave');
            }
        });

        document.querySelectorAll('p,li,blockquote, .section-content .inner').forEach(el=>{
            el.classList.add('morph');
        });

        // nav links and CTAs
        document.querySelectorAll('a,button,input[type="submit"],.inline-block').forEach(el=>{
            // don't add to icon-only anchors
            if(el.closest('footer')) return; // skip footer
            el.classList.add('morph-hover');
            if(el.classList.contains('btn') || el.classList.contains('morph-cta') || el.matches('.inline-flex')){
                el.classList.add('morph-cta');
            }
        });
    }

    function initObserver(){
        const obs = new IntersectionObserver((entries)=>{
            entries.forEach(entry=>{
                if(entry.isIntersecting){
                    entry.target.classList.add('in');
                    // if wave, animate letters
                    if(entry.target.classList.contains('wave') && !entry.target.classList.contains('wave-ready')){
                        const txt = entry.target.textContent.trim();
                        entry.target.textContent='';
                        txt.split('').forEach((c,i)=>{
                            const s = document.createElement('span'); s.textContent = c===' ' ? '\u00A0' : c; s.style.animationDelay = (i*0.04)+'s';
                            entry.target.appendChild(s);
                        });
                        entry.target.classList.add('animated','wave-ready');
                    }
                    // reveal swipe
                    if(entry.target.classList.contains('reveal') && !entry.target.classList.contains('reveal-ready')){
                        entry.target.classList.add('revealed','reveal-ready');
                    }
                    // unobserve to avoid repeat
                    obs.unobserve(entry.target);
                }
            });
        },{threshold:0.08});

        document.querySelectorAll('.morph, .wave, .reveal').forEach(el=>obs.observe(el));
    }

    // page transition for internal links
    function enablePageTransition(){
        document.addEventListener('click', function(e){
            const a = e.target.closest('a');
            if(!a) return;
            const href = a.getAttribute('href');
            if(!href) return;
            if(href.startsWith('#') || href.startsWith('mailto:') || a.target==='_'|| a.target==='_blank') return;
            // only same-origin, relative links
            if(href.indexOf('http')===0 && location.origin !== (new URL(href)).origin) return;

            // let things like file anchors pass
            if(href.indexOf('http')===0 || href.endsWith('.html') || href.startsWith('/')){
                e.preventDefault();
                const overlay = document.getElementById('km-page-overlay');
                overlay.classList.add('show');
                setTimeout(()=>{ window.location = href; }, 480);
            }
        }, true);
    }

    // preloader hide logic
    function hidePreloader(){
        const pre = document.getElementById('km-preloader');
        if(!pre) return;
        setTimeout(()=>{ pre.style.transition='opacity 420ms ease'; pre.style.opacity='0'; setTimeout(()=>pre.remove(),480); }, 650);
    }

    // init
    function init(){
        injectHelpers();
        autoAnnotate();
        initObserver();
        enablePageTransition();
        hidePreloader();
    }

    if(document.readyState==='loading'){
        document.addEventListener('DOMContentLoaded', init);
    } else init();

})();
