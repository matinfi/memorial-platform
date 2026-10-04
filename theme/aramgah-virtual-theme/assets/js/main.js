(function(){'use strict';
 const reduced=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;
 if(reduced||!window.gsap||!window.ScrollTrigger)return;
 gsap.registerPlugin(ScrollTrigger);
 const q=s=>document.querySelector(s), qa=s=>Array.from(document.querySelectorAll(s));
 qa('[data-reveal]').forEach(el=>gsap.fromTo(el,{autoAlpha:0,y:34},{autoAlpha:1,y:0,duration:1.15,ease:'power2.out',scrollTrigger:{trigger:el,start:'top 78%',once:true}}));
 qa('.avam-pinned').forEach(section=>{
   const title=section.querySelector('.avam-section-title, .avam-ending h2');
   if(!title)return;
   gsap.fromTo(title,{y:50,autoAlpha:.2},{y:0,autoAlpha:1,ease:'none',scrollTrigger:{trigger:section,start:'top 80%',end:'top 30%',scrub:1.1}});
 });
 const portrait=q('.avam-portrait-frame'); if(portrait)gsap.to(portrait,{y:-35,rotate:1,scrollTrigger:{trigger:'.avam-hero',start:'top top',end:'bottom top',scrub:1.2}});
 qa('.avam-word').forEach((el,i)=>gsap.fromTo(el,{y:35,autoAlpha:.35},{y:0,autoAlpha:1,duration:.8,scrollTrigger:{trigger:el,start:'top 82%',once:true},delay:i*.05}));
})();