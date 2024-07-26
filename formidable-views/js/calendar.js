(()=>{"use strict";class t{constructor(t,e,i){this.element=e,this.data=t,this.popup=null,this.parentWrapper=e.closest(".frmcal"),this.createElement=i,this.gutenbergCustomizeClassname=this.parentWrapper.getAttribute("data-gutenberg-classname"),!this.isDataEmpty()&&this.createElement&&(this.createPopupWrapper(),this.createContent(),this.show())}createPopupWrapper(){t.prototype.popup?this.popup=t.prototype.popup:(t.prototype.popup=document.createElement("div"),this.popup=t.prototype.popup,this.popup.classList.add("frmcal-popup"),document.body.appendChild(this.popup),this.popup.addEventListener("mouseover",(()=>t.fadeIn())),this.popup.addEventListener("mouseout",(()=>t.hide())))}createContent(){this.popup.innerHTML="",this.data.thumbnail&&this.popup.appendChild(this.createThumbnail(this.data.thumbnail)),this.data.title&&this.popup.appendChild(this.createTitle(this.data.title)),this.data.time&&this.popup.appendChild(this.createTime(this.data.time)),this.data.date&&this.popup.appendChild(this.createDate(this.data.date)),this.data.description&&this.popup.appendChild(this.createDescription(this.data.description))}isDataEmpty(){for(let t in this.data)if(null!==this.data[t])return!1;return!0}show(){let e=this.element.getBoundingClientRect();this.popup.className="",this.popup.classList.add("frmcal-popup",this.gutenbergCustomizeClassname),this.popup.style.display="block",this.popup.style.top=e.top+window.scrollY+"px",this.popup.style.left=this.getLeftOffset(e)<0?0:this.getLeftOffset(e)+"px",t.fadeIn()}getLeftOffset(t){return this.element.offsetWidth>this.popup.offsetWidth?t.left:Math.round(t.left-(this.popup.offsetWidth-this.element.offsetWidth)/2)}createThumbnail(t){var e=document.createElement("img");return e.src=t,this.createElement("div",e,"frmcal-popup--thumbnail")}createTitle(t){return this.createElement("h3",t)}createDescription(t){return this.createElement("p",t)}createTime(t){return this.createElement("h4",t,"frmcal-popup--time")}createDate(t){return this.createElement("h4",t,"frmcal-popup--date")}static hide(){t.prototype.popup&&(t.prototype.popup.classList.remove("frm-active"),t.prototype.popup.style.opacity=0)}static fadeIn(){t.prototype.popup&&(t.prototype.popup.classList.add("frm-active"),t.prototype.popup.style.opacity=1)}}class e{constructor(t){this.container,this.calendar=t,this.createWrapper(),this.initDayClickEvent()}createWrapper(){let t=document.querySelector(".frmcal"),e=document.querySelector(".frmcal-mobile-events-wrapper");null!==t&&(null===e?(this.container=this.calendar.createElement("div","","frmcal-mobile-events-wrapper"),t.appendChild(this.container)):this.container=e)}initDayClickEvent(){this.calendar.days.forEach((t=>{t.addEventListener("click",(()=>{this.calendar.mobileBreakpoint<window.innerWidth||(this.removeActivStatusFromDays(),t.classList.add("frmcal-day--active"),this.showEvents(t))}))}))}removeActivStatusFromDays(){this.calendar.days.forEach((t=>{t.classList.remove("frmcal-day--active")}))}showEvents(t){let e=this.getContent(t);if(!e)return this.container.innerHTML="",this.container.classList.remove("frm-active"),void t.closest(".frmcal").classList.remove("frmcal--mobile-event-active");this.container.closest(".frmcal").classList.add("frmcal--mobile-event-active"),this.container.classList.add("frm-active"),this.container.innerHTML=e}getContent(t){let e,i="",a=t.querySelectorAll(".frmcal-daily-event");return null===t?null:(a.forEach((t=>{if(e=t.querySelector(".frmcal-event-content"),null===e)return;const a=this.calendar.sanitizeHTML(e.cloneNode(!0).innerHTML);i+=this.calendar.createElement("div",a,"frmcal--mobile-event-item").outerHTML})),i)}}class i{constructor(){this.multiDayEvents=[],this.initPrototypes(),window.innerWidth<this.mobileBreakpoint?new e(this):(this.initMultiDayEvents(),this.initMultiDayEventsByWeekData(),this.initSingleDayEventsPosition(),this.initDesktopEventsPopup())}static getInstance(){if(void 0===i.prototype.config)return i.prototype.config={tryInit:{activeAttempts:0,maxAttempts:6}},i.tryInit()}static tryInit(){return 0===document.querySelectorAll(".frmcal-calendar").length&&i.prototype.config.tryInit.activeAttempts<i.prototype.config.tryInit.maxAttempts?(i.prototype.config.tryInit.activeAttempts++,void setTimeout((()=>i.tryInit()),300)):new i}initPrototypes(){void 0===i.prototype.weeks&&(i.prototype.weeks=document.querySelectorAll(".frmcal-calendar > div:not(.frmcal-row-headings)"),i.prototype.days=document.querySelectorAll(".frmcal-day"),i.prototype.multiDayEventElements=document.querySelectorAll(".frmcal-multi-day-event"),i.prototype.mobileBreakpoint=768,i.prototype.createElement=(t,e,i="")=>{let a=document.createElement(t);return""!==i&&a.classList.add(i),e instanceof Element?(a.appendChild(e),a):(a.innerHTML=e,a)},i.prototype.sanitizeHTML=t=>{let e,i,a,n=document.createElement("div");n.innerHTML=t;for(let t=n.children.length-1;t>=0;t--){e=n.children[t],"script"===e.tagName.toLowerCase()&&e.parentNode.removeChild(e),i=e.attributes;for(let t=i.length-1;t>=0;t--)a=i[t].name,a.startsWith("on")&&e.removeAttribute(a)}return n.innerHTML})}initMultiDayEvents(){let t,e,i,a;0!==this.multiDayEventElements.length&&this.multiDayEventElements.forEach((n=>{t=parseInt(n.getAttribute("data-start-day"),10),e=parseInt(n.getAttribute("data-days-count"),10),i=7-t>e?e:7-t,a=i>4?100*i+2:100*i+1,n.style.width="calc("+a+"% )",n.classList.remove("frmcal-hide")}))}initMultiDayEventsByWeekData(){this.weeks.forEach(((t,e)=>{const i=t.querySelectorAll(".frmcal-day:not(.frm-inactive)"),a=new Date(i[0].getAttribute("data-date"));i.forEach((t=>{t.querySelectorAll(".frmcal-multi-day-event:not(.frmcal-multi-day-event--duplicate)").forEach((t=>{const i={startDate:new Date(t.getAttribute("data-start-date")),endDate:new Date(t.getAttribute("data-end-date")),element:t,id:parseInt(t.getAttribute("data-entry-id"),10),height:t.clientHeight};void 0===this.multiDayEvents[e]&&(this.multiDayEvents[e]=[]),this.multiDayEvents[e].push(i);for(let t=1;t<this.weeks.length-e+1;t++){const n=new Date(a);if(n.setDate(n.getDate()+7*t),i.endDate>=n){void 0===this.multiDayEvents[e+t]&&(this.multiDayEvents[e+t]=[]);const a=this.prependMultiDayEvent(this.weeks[e+t],i);null!==a&&this.multiDayEvents[e+t].push({...i,element:a,height:a.clientHeight})}}}))}))}))}findMultidayIndexById(t,e){return t.findIndex((t=>t.id===e))}countDaysBetweenDates(t,e){let i=t.getTime(),a=e.getTime()-i;return Math.floor(a/864e5)}prependMultiDayEvent(t,e){const i=t.querySelector(".frmcal-day:not(.frm-inactive)"),a=new Date(i.getAttribute("data-date")),n=e.element.cloneNode(!0),r=this.countDaysBetweenDates(a,e.endDate)>7?7:this.countDaysBetweenDates(a,e.endDate)+1,s=r>4?100*r+2:100*r+1;return n.style.width="calc("+s+"% )",n.classList.add("frmcal-multi-day-event--duplicate"),null!==i.querySelector(".frmcal-content")?(i.querySelector(".frmcal-content").prepend(n),n):null}getDayFromDate(t){return(t=new Date(t)).getDate()}calculateTopPosition(t,e=null){let i=0,a=null!==e?parseInt(e.getAttribute("data-entry-id"),10):0,n=0!==a?this.findMultidayIndexById(t,a):t.length;for(let e=0;e<n;e++)i+=t[e].height+10;return i}initSingleDayEventsPosition(){let t,e;this.weeks.forEach(((i,a)=>{void 0!==this.multiDayEvents[a]&&i.querySelectorAll(".frmcal-day").forEach((i=>{t=i.querySelectorAll(".frmcal-multi-day-event"),e=i.querySelector(".frmcal-content"),t.forEach((t=>{t.style.top=this.calculateTopPosition(this.multiDayEvents[a],t)+42+"px"})),e&&(e.style.marginTop=this.calculateTopPosition(this.multiDayEvents[a],null)+"px")}))}))}initDesktopEventsPopup(){let e=document.querySelectorAll(".frmcal-daily-event");0!==e.length&&e.forEach((e=>{e.addEventListener("mouseover",(()=>{window.innerWidth<this.mobileBreakpoint||new t({thumbnail:e.getAttribute("data-calpopup-image"),title:e.getAttribute("data-calpopup-title"),time:e.getAttribute("data-calpopup-time"),date:e.getAttribute("data-calpopup-date"),description:e.getAttribute("data-calpopup-description")},e,this.createElement)})),e.addEventListener("mouseout",(()=>t.hide()))}))}}i.getInstance()})();