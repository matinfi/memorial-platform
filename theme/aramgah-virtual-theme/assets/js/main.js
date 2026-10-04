(function(){
'use strict';
const root=document.documentElement;
const chapters=[...document.querySelectorAll('.avam-chapter[data-chapter]')];
const rail=[...document.querySelectorAll('.avam-rail-item')];
const progress=document.querySelector('.avam-progress span');
if(!chapters.length)return;
const reduced=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
if(reduced)root.setAttribute('data-reduced-motion','true');

function active(id){rail.forEach(x=>x.classList.toggle('is-active',x.dataset.chapter===id));}
const observer=new IntersectionObserver(entries=>{
 entries.forEach(entry=>{
  if(entry.isIntersecting){active(entry.target.dataset.chapter);entry.target.classList.add('is-visible');}
 });
},{threshold:.55});
chapters.forEach(x=>observer.observe(x));

rail.forEach(item=>{
 item.addEventListener('click',e=>{
  e.preventDefault();
  const target=document.getElementById('chapter-'+item.dataset.chapter);
  if(target)target.scrollIntoView({behavior:reduced?'auto':'smooth',block:'start'});
 });
});

let raf=0;
function update(){
 const max=document.documentElement.scrollHeight-window.innerHeight;
 if(progress)progress.style.width=(max>0?(window.scrollY/max)*100:0)+'%';
 chapters.forEach(ch=>{
  const r=ch.getBoundingClientRect(), center=window.innerHeight*.5;
  const distance=Math.abs(r.top+r.height/2-center);
  const focus=Math.max(0,1-distance/(window.innerHeight*.9));
  ch.style.setProperty('--focus',focus.toFixed(3));
 });
 raf=0;
}
window.addEventListener('scroll',()=>{if(!raf)raf=requestAnimationFrame(update)},{passive:true});
window.addEventListener('resize',update);
update();

chapters.forEach(ch=>{
 ch.addEventListener('mousemove',e=>{
  if(reduced)return;
  const r=ch.getBoundingClientRect();
  ch.style.setProperty('--mx',(((e.clientX-r.left)/r.width-.5)*14).toFixed(2)+'px');
  ch.style.setProperty('--my',(((e.clientY-r.top)/r.height-.5)*10).toFixed(2)+'px');
 });
 ch.addEventListener('mouseleave',()=>{ch.style.setProperty('--mx','0px');ch.style.setProperty('--my','0px');});
});
})();