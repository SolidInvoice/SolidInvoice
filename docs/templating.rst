table>`a`_ ",d=p.getElementsByTagName("*"),e=p.getElementsByTagName("a
")[0];if(!d||!d.length||!e){return{}}g=c.createElement("select"),h=g.a
ppendChild(c.createElement("option")),i=p.getElementsByTagName("input"
)[0],b={leadingWhitespace:p.firstChild.nodeType===3,tbody:!p.getElemen
tsByTagName("tbody").length,htmlSerialize:!!p.getElementsByTagName("li
nk").length,style:/top/.test(e.getAttribute("style")),hrefNormalized:e
.getAttribute("href")==="/a",opacity:/^0.55/.test(e.style.opacity),css
Float:!!e.style.cssFloat,checkOn:i.value==="on",optSelected:h.selected
,getSetAttribute:p.className!=="t",enctype:!!c.createElement("form").e
nctype,html5Clone:c.createElement("nav").cloneNode(!0).outerHTML!=="<:
nav> ",submitBubbles:!0,changeBubbles:!0,focusinBubbles:!1,deleteExpan
do:!0,noCloneEvent:!0,inlineBlockNeedsLayout:!1,shrinkWrapBlocks:!1,re
liableMarginRight:!0,pixelMargin:!0},f.boxModel=b.boxModel=c.compatMod
e==="CSS1Compat",i.checked=!0,b.noCloneChecked=i.cloneNode(!0).checked
,g.disabled=!0,b.optDisabled=!h.disabled;try{delete p.test}catch(r){b.
deleteExpando=!1}!p.addEventListener&&&&(p.attachEvent("onclick",funct
ion(){b.noCloneEvent=!1}),p.cloneNode(!0).fireEvent("onclick")),i=c.cr
eateElement("input"),i.value="t",i.setAttribute("type","radio"),b.radi
oValue=i.value==="t",i.setAttribute("checked","checked"),i.setAttribut
e("name","t"),p.appendChild(i),j=c.createDocumentFragment(),j.appendCh
ild(p.lastChild),b.checkClone=j.cloneNode(!0).cloneNode(!0).lastChild.
checked,b.appendChecked=i.checked,j.removeChild(i),j.appendChild(p);if
(p.attachEvent){for(n in {submit:1,change:1,focusin:1}){m="on"+n,o=m
in p,o||(p.setAttribute(m,"return;"),o=typeof p[m]=="function"),b[n+"B
ubbles"]=o}}j.removeChild(p),j=g=h=p=i=null,f(function(){var d,e,g,h,i
,j,l,m,n,q,r,s,t,u=c.getElementsByTagName("body")[0];!u||(m=1,t="paddi
ng:0;margin:0;border:",r="position:absolute;top:0;left:0;width:1px;hei
ght:1px;",s=t+"0;visibility:hidden;",n="style='"+r+t+"5px solid
#000;",q=" ",d=c.createElement("div"),d.style.cssText=s+"width:0;heigh
t:0;position:static;top:0;margin-top:"+m+"px",u.insertBefore(d,u.first
Child),p=c.createElement("div"),d.appendChild(p),p.innerHTML=" t ",k=p
.getElementsByTagName("td"),o=k[0].offsetHeight===0,k[0].style.display
="",k[1].style.display="none",b.reliableHiddenOffsets=o&[0].offsetHeig
ht===0,a.getComputedStyle&&(p.innerHTML="",l=c.createElement("div"),l.
style.width="0",l.style.marginRight="0",p.style.width="2px",p.appendCh
ild(l),b.reliableMarginRight=(parseInt((a.getComputedStyle(l,null)||{m
arginRight:0}).marginRight,10)||0)===0),typeof p.style.zoom!="undefine
d"&&(p.innerHTML="",p.style.width=p.style.padding="1px",p.style.border
=0,p.style.overflow="hidden",p.style.display="inline",p.style.zoom=1,b
.inlineBlockNeedsLayout=p.offsetWidth===3,p.style.display="block",p.st
yle.overflow="visible",p.innerHTML=" ",b.shrinkWrapBlocks=p.offsetWidt
h!==3),p.style.cssText=r+s,p.innerHTML=q,e=p.firstChild,g=e.firstChild
,i=e.nextSibling.firstChild.firstChild,j={doesNotAddBorder:g.offsetTop
!==5,doesAddBorderForTableAndCells:i.offsetTop===5},g.style.position="
fixed",g.style.top="20px",j.fixedPosition=g.offsetTop===20||g.offsetTo
p===15,g.style.position=g.style.top="",e.style.overflow="hidden",e.sty
le.position="relative",j.subtractsBorderForOverflowNotVisible=g.offset
Top===-5,j.doesNotIncludeMarginInBodyOffset=u.offsetTop!==m,a.getCompu
tedStyle&&(p.style.marginTop="1%",b.pixelMargin=(a.getComputedStyle(p,
null)||{marginTop:0}).marginTop!=="1%"),typeof d.style.zoom!="undefine
d"&&(d.style.zoom=1),u.removeChild(d),l=p=d=null,f.extend(b,j))});retu
rn b}();var j=/^(?:\{.*\}|\[.*\])$/,k=/([A-Z])/g;f.extend({cache:{},uu
id:0,expando:"jQuery"+(f.fn.jquery+Math.random()).replace(/\D/g,""),no
Data:{embed:!0,object:"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000",app
let:!0},hasData:function(a){a=a.nodeType?f.cache[a[f.expando]]:a[f.exp
ando];return
!!a&&!m(a)},data:function(a,c,d,e){if(!!f.acceptData(a)){var
g,h,i,j=f.expando,k=typeof c=="string",l=a.nodeType,m=l?f.cache:a,n=l?
a[j]:a[j]&,o=c==="events";if((!n||!m[n]||!o&&!e&&!m[n].data)&&===b){re
turn}n||(l?a[j]=n=++f.uuid:n=j),m[n]||(m[n]={},l||(m[n].toJSON=f.noop)
);if(typeof c=="object"||typeof c=="function"){e?m[n]=f.extend(m[n],c)
:m[n].data=f.extend(m[n].data,c)}g=h=m[n],e||(h.data||(h.data={}),h=h.
data),d!==b&&(h[f.camelCase(c)]=d);if(o&&!h[c]){return
g.events}k?(i=h[c],i==null&&(i=h[f.camelCase(c)])):i=h;return
i}},removeData:function(a,b,c){if(!!f.acceptData(a)){var d,e,g,h=f.exp
ando,i=a.nodeType,j=i?f.cache:a,k=i?a[h]:h;if(!j[k]){return}if(b){d=c?
j[k]:j[k].data;if(d){f.isArray(b)||(b in d?b=[b]:(b=f.camelCase(b),b
in d?b=[b]:b=b.split(" ")));for(e=0,g=b.length;e
1,null,!1)},removeData:function(a){return this.each(function(){f.remov
eData(this,a)})}}),f.extend({_mark:function(a,b){a&&(b=(b||"fx")+"mark
",f._data(a,b,(f._data(a,b)||0)+1))},_unmark:function(a,b,c){a!==!0&&(
c=b,b=a,a=!1);if(b){c=c||"fx";var d=c+"mark",e=a?0:(f._data(b,d)||1)-1
;e?f._data(b,d,e):(f.removeData(b,d,!0),n(b,c,"mark"))}},queue:functio
n(a,b,c){var d;if(a){b=(b||"fx")+"queue",d=f._data(a,b),c&&(!d||f.isAr
ray(c)?d=f._data(a,b,f.makeArray(c)):d.push(c));return
d||[]}},dequeue:function(a,b){b=b||"fx";var c=f.queue(a,b),d=c.shift()
,e={};d==="inprogress"&&(d=c.shift()),d&&(b==="fx"&("inprogress"),f._d
ata(a,b+".run",e),d.call(a,function(){f.dequeue(a,b)},e)),c.length||(f
.removeData(a,b+"queue
"+b+".run",!0),n(a,b,"queue"))}}),f.fn.extend({queue:function(a,c){var
d=2;typeof a!="string"&&(c=a,a="fx",d--);if(arguments.length
1)},removeAttr:function(a){return
this.each(function(){f.removeAttr(this,a)})},prop:function(a,b){return
f.access(this,f.prop,a,b,arguments.length>1)},removeProp:function(a){a
=f.propFix[a]||a;return this.each(function(){try{this[a]=b,delete
this[a]}catch(c){}})},addClass:function(a){var
b,c,d,e,g,h,i;if(f.isFunction(a)){return this.each(function(b){f(this)
.addClass(a.call(this,b,this.className))})}if(a&
a=="string"){b=a.split(p);for(c=0,d=this.length;c -1){return
!0}}return !1},val:function(a){var
c,d,e,g=this[0];if(!!arguments.length){e=f.isFunction(a);return
this.each(function(d){var g=f(this),h;if(this.nodeType===1){e?h=a.call
(this,d,g.val()):h=a,h==null?h="":typeof
h=="number"?h+="":f.isArray(h)&&(h=f.map(h,function(a){return a==null?
"":a+""})),c=f.valHooks[this.type]||f.valHooks[this.nodeName.toLowerCa
se()];if(!c||!("set" in c)||c.set(this,h,"value")===b){this.value=h}}}
)}if(g){c=f.valHooks[g.type]||f.valHooks[g.nodeName.toLowerCase()];if(
c&&"get" in c&&(d=c.get(g,"value"))!==b){return d}d=g.value;return
typeof d=="string"?d.replace(q,""):d==null?"":d}}}),f.extend({valHooks
:{option:{get:function(a){var b=a.attributes.value;return
!b||b.specified?a.value:a.text}},select:{get:function(a){var
b,c,d,e,g=a.selectedIndex,h=[],i=a.options,j=a.type==="select-
one";if(g<0){return null}c=j?g:0,d=j?g+1:i.length;for(;c
=0}),c.length||(a.selectedIndex=-1);return c}}},attrFn:{val:!0,css:!0,
html:!0,text:!0,data:!0,width:!0,height:!0,offset:!0},attr:function(a,
c,d,e){var g,h,i,j=a.nodeType;if(!!a&!==3&!==8&!==2){if(e& in
f.attrFn){return f(a)[c](d)}if(typeof
a.getAttribute=="undefined"){return f.prop(a,c,d)}i=j!==1||!f.isXMLDoc
(a),i&&(c=c.toLowerCase(),h=f.attrHooks[c]||(u.test(c)?x:w));if(d!==b)
{if(d===null){f.removeAttr(a,c);return}if(h&&"set" in
h&&&(g=h.set(a,d,c))!==b){return g}a.setAttribute(c,""+d);return
d}if(h&&"get" in h&&&(g=h.get(a,c))!==null){return
g}g=a.getAttribute(c);return
g===null?b:g}},removeAttr:function(a,b){var
c,d,e,g,h,i=0;if(b&===1){d=b.toLowerCase().split(p),g=d.length;for(;i
=0}}})});var z=/^(?:textarea|input|select)$/i,A=/^([^\.]*)?(?:\.(.+))?
$/,B=/(?:^|\s)hover(\.\S+)?\b/,C=/^key/,D=/^(?:mouse|contextmenu)|clic
k/,E=/^(?:focusinfocus|focusoutblur)$/,F=/^(\w*)(?:#([\w\-]+))?(?:\.([
\w\-]+))?$/,G=function(a){var
b=F.exec(a);b&&(b[1]=(b[1]||"").toLowerCase(),b[3]=b[3]&
RegExp("(?:^|\\s)"+b[3]+"(?:\\s|$)"));return b},H=function(a,b){var c=
a.attributes||{};return(!b[1]||a.nodeName.toLowerCase()===b[1])&&(!b[2
]||(c.id||{}).value===b[2])&&(!b[3]||b[3].test((c["class"]||{}).value)
)},I=function(a){return
f.event.special.hover?a:a.replace(B,"mouseenter$1
mouseleave$1")};f.event={add:function(a,c,d,e,g){var h,i,j,k,l,m,n,o,p
,q,r,s;if(!(a.nodeType===3||a.nodeType===8||!c||!d||!(h=f._data(a)))){
d.handler&&(p=d,d=p.handler,g=p.selector),d.guid||(d.guid=f.guid++),j=
h.events,j||(h.events=j={}),i=h.handle,i||(h.handle=i=function(a){retu
rn typeof f!="undefined"&&(!a||f.event.triggered!==a.type)?f.event.dis
patch.apply(i.elem,arguments):b},i.elem=a),c=f.trim(I(c)).split("
");for(k=0;k =0&&(h=h.slice(0,-1),k=!0),h.indexOf(".")>=0&&(i=h.split(
"."),h=i.shift(),i.sort());if((!e||f.event.customEvent[h])&&!f.event.g
lobal[h]){return}c=typeof c=="object"?c[f.expando]?c:new
f.Event(h,c):new f.Event(h),c.type=h,c.isTrigger=!0,c.exclusive=k,c.na
mespace=i.join("."),c.namespace_re=c.namespace?new RegExp("(^|\\.)"+i.
join("\\.(?:.*\\.)?")+"(\\.|$)"):null,o=h.indexOf(":")<0?"on"+h:"";if(
!e){j=f.cache;for(l in j){j[l].events&[l].events[h]&(c,d,j[l].handle.e
lem,!0)}return}c.result=b,c.target||(c.target=e),d=d!=null?f.makeArray
(d):[],d.unshift(c),p=f.event.special[h]||{};if(p.trigger&(e,d)===!1){
return}r=[[e,p.bindType||h]];if(!g&&!p.noBubble&&!f.isWindow(e)){s=p.d
elegateType||h,m=E.test(s+h)?e:e.parentNode,n=null;for(;m;m=m.parentNo
de){r.push([m,s]),n=m}n&===e.ownerDocument&([n.defaultView||n.parentWi
ndow||a,s])}for(l=0;l e&({elem:this,matches:d.slice(e)});for(k=0;k 0?t
his.on(b,null,a,c):this.trigger(b)},f.attrFn&&(f.attrFn[b]=!0),C.test(
b)&&(f.event.fixHooks[b]=f.event.keyHooks),D.test(b)&&(f.event.fixHook
s[b]=f.event.mouseHooks)}),function(){function x(a,b,c,e,f,g){for(var
h=0,i=e.length;h 0){k=j;break}}}j=j[a]}e[h]=k}}}function
w(a,b,c,e,f,g){for(var h=0,i=e.length;h +~,(\[\\]+)+|[>+~])(\s*,\s*)?(
(?:.|\r|\n)*)/g,d="sizcache"+(Math.random()+"").replace(".",""),e=0,g=
Object.prototype.toString,h=!1,i=!0,j=/\\/g,k=/\r\n/g,l=/\W/;[0,0].sor
t(function(){i=!1;return 0});var
m=function(b,d,e,f){e=e||[],d=d||c;var
h=d;if(d.nodeType!==1&!==9){return[]}if(!b||typeof b!="string"){return
e}var i,j,k,l,n,q,r,t,u=!0,v=m.isXML(d),w=[],x=b;do{a.exec(""),i=a.exe
c(x);if(i){x=i[3],w.push(i[1]);if(i[2]){l=i[3];break}}}while(i);if(w.l
ength>1&(b)){if(w.length===2&[w[0]]){j=y(w[0]+w[1],d,f)}else{j=o.relat
ive[w[0]]?[d]:m(w.shift(),d);while(w.length){b=w.shift(),o.relative[b]
&&(b+=w.shift()),j=y(b,j,f)}}}else{!f&>1&===9&&!v&(w[0])&&!o.match.ID.
test(w[w.length-1])&&(n=m.find(w.shift(),d,v),d=n.expr?m.filter(n.expr
,n.set)[0]:n.set[0]);if(d){n=f?{expr:w.pop(),set:s(f)}:m.find(w.pop(),
w.length===1&&(w[0]==="~"||w[0]==="+")&?d.parentNode:d,v),j=n.expr?m.f
ilter(n.expr,n.set):n.set,w.length>0?k=s(j):u=!1;while(w.length){q=w.p
op(),r=q,o.relative[q]?r=w.pop():q="",r==null&&(r=d),o.relative[q](k,r
,v)}}else{k=w=[]}}k||(k=j),k||m.error(q||b);if(g.call(k)==="[object Ar
ray]"){if(!u){e.push.apply(e,k)}else{if(d&===1){for(t=0;k[t]!=null;t++
){k[t]&&(k[t]===!0||k[t].nodeType===1&(d,k[t]))&(j[t])}}else{for(t=0;k
[t]!=null;t++){k[t]&[t].nodeType===1&(j[t])}}}}else{s(k,e)}l&&(m(l,h,e
,f),m.uniqueSort(e));return
e};m.uniqueSort=function(a){if(u){h=i,a.sort(u);if(h){for(var b=1;b
0},m.find=function(a,b,c){var
d,e,f,g,h,i;if(!a){return[]}for(e=0,f=o.order.length;e
":function(a,b){var c,d=typeof
b=="string",e=0,f=a.length;if(d&&!l.test(b)){b=b.toLowerCase();for(;e
=0)?c||d.push(h):c&&(b[g]=!1))}return !1},ID:function(a){return
a[1].replace(j,"")},TAG:function(a,b){return a[1].replace(j,"").toLowe
rCase()},CHILD:function(a){if(a[1]==="nth"){a[2]||m.error(a[0]),a[2]=a
[2].replace(/^\+|\s*/g,"");var b=/(-?)(\d*)(?:n([+\-]?\d*))?/.exec(a[2
]==="even"&&"2n"||a[2]==="odd"&&"2n+1"||!/\D/.test(a[2])&&"0n+"+a[2]||
a[2]);a[2]=b[1]+(b[2]||1)-0,a[3]=b[3]-0}else{a[2]&(a[0])}a[0]=e++;retu
rn a},ATTR:function(a,b,c,d,e,f){var g=a[1]=a[1].replace(j,"");!f&[g]&
&(a[1]=o.attrMap[g]),a[4]=(a[4]||a[5]||"").replace(j,""),a[2]==="~="&&
(a[4]=" "+a[4]+" ");return a},PSEUDO:function(b,c,d,e,f){if(b[1]==="no
t"){if((a.exec(b[3])||"").length>1||/^\w/.test(b[3])){b[3]=m(b[3],null
,null,c)}else{var
g=m.filter(b[3],c,d,!0^f);d||e.push.apply(e,g);return
!1}}else{if(o.match.POS.test(b[0])||o.match.CHILD.test(b[0])){return
!0}}return b},POS:function(a){a.unshift(!0);return
a}},filters:{enabled:function(a){return
a.disabled===!1&!=="hidden"},disabled:function(a){return
a.disabled===!0},checked:function(a){return
a.checked===!0},selected:function(a){a.parentNode&return
a.selected===!0},parent:function(a){return
!!a.firstChild},empty:function(a){return
!a.firstChild},has:function(a,b,c){return !!m(c[3],a).length},header:f
unction(a){return/h\d/i.test(a.nodeName)},text:function(a){var
b=a.getAttribute("type"),c=a.type;return a.nodeName.toLowerCase()==="i
nput"&&"text"===c&&(b===c||b===null)},radio:function(a){return a.nodeN
ame.toLowerCase()==="input"&&"radio"===a.type},checkbox:function(a){re
turn a.nodeName.toLowerCase()==="input"&&"checkbox"===a.type},file:fun
ction(a){return a.nodeName.toLowerCase()==="input"&&"file"===a.type},p
assword:function(a){return a.nodeName.toLowerCase()==="input"&&"passwo
rd"===a.type},submit:function(a){var b=a.nodeName.toLowerCase();return
(b==="input"||b==="button")&&"submit"===a.type},image:function(a){retu
rn a.nodeName.toLowerCase()==="input"&&"image"===a.type},reset:functio
n(a){var b=a.nodeName.toLowerCase();return(b==="input"||b==="button")&
&"reset"===a.type},button:function(a){var
b=a.nodeName.toLowerCase();return b==="input"&&"button"===a.type||b===
"button"},input:function(a){return/input|select|textarea|button/i.test
(a.nodeName)},focus:function(a){return a===a.ownerDocument.activeEleme
nt}},setFilters:{first:function(a,b){return
b===0},last:function(a,b,c,d){return
b===d.length-1},even:function(a,b){return
b%2===0},odd:function(a,b){return b%2===1},lt:function(a,b,c){return b
c[3]-0},nth:function(a,b,c){return
c[3]-0===b},eq:function(a,b,c){return
c[3]-0===b}},filter:{PSEUDO:function(a,b,c,d){var
e=b[1],f=o.filters[e];if(f){return f(a,c,b,d)}if(e==="contains"){retur
n(a.textContent||a.innerText||n([a])||"").indexOf(b[3])>=0}if(e==="not
"){var g=b[3];for(var h=0,i=g.length;h =0}},ID:function(a,b){return
a.nodeType===1&("id")===b},TAG:function(a,b){return
b==="*"&===1||!!a.nodeName&()===b},CLASS:function(a,b){return("
"+(a.className||a.getAttribute("class"))+"
").indexOf(b)>-1},ATTR:function(a,b){var c=b[1],d=m.attr?m.attr(a,c):o
.attrHandle[c]?o.attrHandle[c](a):a[c]!=null?a[c]:a.getAttribute(c),e=
d+"",f=b[2],g=b[4];return d==null?f==="!=":!f&?d!=null:f==="="?e===g:f
==="*="?e.indexOf(g)>=0:f==="~="?(" "+e+" ").indexOf(g)>=0:g?f==="!="?
e!==g:f==="^="?e.indexOf(g)===0:f==="$="?e.substr(e.length-g.length)==
=g:f==="|="?e===g||e.substr(0,g.length+1)===g+"-":!1:e&!==!1},POS:func
tion(a,b,c,d){var e=b[2],f=o.setFilters[e];if(f){return f(a,c,b,d)}}}}
,p=o.match.POS,q=function(a,b){return"\\"+(b-0+1)};for(var r in
o.match){o.match[r]=new RegExp(o.match[r].source+/(?![^\[]*\])(?![^\(]
*\))/.source),o.leftMatch[r]=new RegExp(/(^(?:.|\r|\n)*?)/.source+o.ma
tch[r].source.replace(/\\(\d+)/g,q))}o.match.globalPOS=p;var s=functio
n(a,b){a=Array.prototype.slice.call(a,0);if(b){b.push.apply(b,a);retur
n b}return a};try{Array.prototype.slice.call(c.documentElement.childNo
des,0)[0].nodeType}catch(t){s=function(a,b){var
c=0,d=b||[];if(g.call(a)==="[object
Array]"){Array.prototype.push.apply(d,a)}else{if(typeof
a.length=="number"){for(var e=a.length;c ",e.insertBefore(a,e.firstChi
ld),c.getElementById(d)&&(o.find.ID=function(a,c,d){if(typeof
c.getElementById!="undefined"&&!d){var e=c.getElementById(a[1]);return
e?e.id===a[1]||typeof e.getAttributeNode!="undefined"&("id").nodeValue
===a[1]?[e]:b:[]}},o.filter.ID=function(a,b){var c=typeof
a.getAttributeNode!="undefined"&("id");return
a.nodeType===1&&===b}),e.removeChild(a),e=a=null}(),function(){var a=c
.createElement("div");a.appendChild(c.createComment("")),a.getElements
ByTagName("*").length>0&&(o.find.TAG=function(a,b){var
c=b.getElementsByTagName(a[1]);if(a[1]==="*"){var d=[];for(var
e=0;c[e];e++){c[e].nodeType===1&(c[e])}c=d}return
c}),a.innerHTML="",a.firstChild& a.firstChild.getAttribute!="undefined
"&("href")!=="#"&&(o.attrHandle.href=function(a){return
a.getAttribute("href",2)}),a=null}(),c.querySelectorAll&(){var
a=m,b=c.createElement("div"),d="__sizzle__";b.innerHTML="

