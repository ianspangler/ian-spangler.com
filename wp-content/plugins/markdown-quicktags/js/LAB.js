/*! LAB.js (LABjs :: Loading And Blocking JavaScript)
    v1.0.4 (c) Kyle Simpson
    MIT License
*/
(function(p){var q="string",w="head",H="body",I="script",t="readyState",j="preloaddone",x="loadtrigger",J="srcuri",C="preload",Z="complete",y="done",z="which",K="preserve",D="onreadystatechange",ba="onload",L="hasOwnProperty",bb="script/cache",M="[object ",bw=M+"Function]",bx=M+"Array]",e=null,h=true,i=false,k=p.document,by=p.location,bc=p.ActiveXObject,A=p.setTimeout,bd=p.clearTimeout,N=function(a){return k.getElementsByTagName(a)},O=Object.prototype.toString,P=function(){},r={},Q={},be=/^[^?#]*\//.exec(by.href)[0],bf=/^\w+\:\/\/\/?[^\/]+/.exec(be)[0],bz=N(I),bg=p.opera&&O.call(p.opera)==M+"Opera]",bh=("MozAppearance"in k.documentElement.style),bi=(k.createElement(I).async===true),u={cache:!(bh||bg),order:bh||bg||bi,xhr:h,dupe:h,base:"",which:w};u[K]=i;u[C]=h;r[w]=k.head||N(w);r[H]=N(H);function R(a){return O.call(a)===bw}function S(a,b){var c=/^\w+\:\/\//,d;if(typeof a!=q)a="";if(typeof b!=q)b="";d=(c.test(a)?"":b)+a;return((c.test(d)?"":(d.charAt(0)==="/"?bf:be))+d)}function bA(a){return(S(a).indexOf(bf)===0)}function bB(a){var b,c=-1;while(b=bz[++c]){if(typeof b.src==q&&a===S(b.src)&&b.type!==bb)return h}return i}function E(v,l){v=!(!v);if(l==e)l=u;var bj=i,B=v&&l[C],bk=B&&l.cache,F=B&&l.order,bl=B&&l.xhr,bC=l[K],bD=l.which,bE=l.base,bm=P,T=i,G,s=h,m={},U=[],V=e;B=bk||bl||F;function bn(a,b){if((a[t]&&a[t]!==Z&&a[t]!=="loaded")||b[y]){return i}a[ba]=a[D]=e;return h}function W(a,b,c){c=!(!c);if(!c&&!(bn(a,b)))return;b[y]=h;for(var d in m){if(m[L](d)&&!(m[d][y]))return}bj=h;bm()}function bo(a){if(R(a[x])){a[x]();a[x]=e}}function bF(a,b){if(!bn(a,b))return;b[j]=h;A(function(){r[b[z]].removeChild(a);bo(b)},0)}function bG(a,b){if(a[t]===4){a[D]=P;b[j]=h;A(function(){bo(b)},0)}}function X(b,c,d,g,f,n){var o=b[z];A(function(){if("item"in r[o]){if(!r[o][0]){A(arguments.callee,25);return}r[o]=r[o][0]}var a=k.createElement(I);if(typeof d==q)a.type=d;if(typeof g==q)a.charset=g;if(R(f)){a[ba]=a[D]=function(){f(a,b)};a.src=c;if(bi){a.async=i}}r[o].insertBefore(a,(o===w?r[o].firstChild:e));if(typeof n==q){a.text=n;W(a,b,h)}},0)}function bp(a,b,c,d){Q[a[J]]=h;X(a,b,c,d,W)}function bq(a,b,c,d){var g=arguments;if(s&&a[j]==e){a[j]=i;X(a,b,bb,d,bF)}else if(!s&&a[j]!=e&&!a[j]){a[x]=function(){bq.apply(e,g)}}else if(!s){bp.apply(e,g)}}function br(a,b,c,d){var g=arguments,f;if(s&&a[j]==e){a[j]=i;f=a.xhr=(bc?new bc("Microsoft.XMLHTTP"):new p.XMLHttpRequest());f[D]=function(){bG(f,a)};f.open("GET",b);f.send("")}else if(!s&&a[j]!=e&&!a[j]){a[x]=function(){br.apply(e,g)}}else if(!s){Q[a[J]]=h;X(a,b,c,d,e,a.xhr.responseText);a.xhr=e}}function bs(a){if(a.allowDup==e)a.allowDup=l.dupe;var b=a.src,c=a.type,d=a.charset,g=a.allowDup,f=S(b,bE),n,o=bA(f);if(typeof d!=q)d=e;g=!(!g);if(!g&&((Q[f]!=e)||(s&&m[f])||bB(f))){if(m[f]!=e&&m[f][j]&&!m[f][y]&&o){W(e,m[f],h)}return}if(m[f]==e)m[f]={};n=m[f];if(n[z]==e)n[z]=bD;n[y]=i;n[J]=f;T=h;if(!F&&bl&&o)br(n,f,c,d);else if(!F&&bk)bq(n,f,c,d);else bp(n,f,c,d)}function bt(a){U.push(a)}function Y(a){if(v&&!F)bt(a);if(!v||B)a()}function bu(a){var b=[],c;for(c=-1;++c<a.length;){if(O.call(a[c])===bx)b=b.concat(bu(a[c]));else b[b.length]=a[c]}return b}G={script:function(){bd(V);var a=bu(arguments),b=G,c;if(bC){for(c=-1;++c<a.length;){if(c===0){Y(function(){bs((typeof a[0]==q)?{src:a[0]}:a[0])})}else b=b.script(a[c]);b=b.wait()}}else{Y(function(){for(c=-1;++c<a.length;){bs((typeof a[c]==q)?{src:a[c]}:a[c])}})}V=A(function(){s=i},5);return b},wait:function(a){bd(V);s=i;if(!R(a))a=P;var b=E(h,l),c=b.trigger,d=function(){try{a()}catch(err){}c()};delete b.trigger;var g=function(){if(T&&!bj)bm=d;else d()};if(v&&!T)bt(g);else Y(g);return b}};if(v){G.trigger=function(){var a,b=-1;while(a=U[++b])a();U=[]}}return G}function bv(a){var b,c={},d={"UseCachePreload":"cache","UseLocalXHR":"xhr","UsePreloading":C,"AlwaysPreserveOrder":K,"AllowDuplicates":"dupe"},g={"AppendTo":z,"BasePath":"base"};for(b in d)g[b]=d[b];c.order=!(!u.order);for(b in g){if(g[L](b)&&u[g[b]]!=e)c[g[b]]=(a[b]!=e)?a[b]:u[g[b]]}for(b in d){if(d[L](b))c[d[b]]=!(!c[d[b]])}if(!c[C])c.cache=c.order=c.xhr=i;c.which=(c.which===w||c.which===H)?c.which:w;return c}p.$LAB={setGlobalDefaults:function(a){u=bv(a)},setOptions:function(a){return E(i,bv(a))},script:function(){return E().script.apply(e,arguments)},wait:function(){return E().wait.apply(e,arguments)}};(function(a,b,c){if(k[t]==e&&k[a]){k[t]="loading";k[a](b,c=function(){k.removeEventListener(b,c,i);k[t]=Z},i)}})("addEventListener","DOMContentLoaded")})(window);