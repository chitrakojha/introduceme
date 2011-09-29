// usage: log('inside coolFunc',this,arguments);
// paulirish.com/2009/log-a-lightweight-wrapper-for-consolelog/
window.log = function () {
	log.history = log.history || [];   // store logs to an array for reference
	log.history.push(arguments);
	if (this.console) {
		console.log(Array.prototype.slice.call(arguments));
	}
};

// Log errors with a web service
window.onerror = function(message, file, line) {
	new Image().src = 'http://logger.nodester.com/log/'
	+ '?project=' + encodeURIComponent('introduceme')
	+ '&file=' + encodeURIComponent(file)
	+ '&line=' + encodeURIComponent(line)
	+ '&message=' + encodeURIComponent(message);
};


/*!
 * HTML5 Placeholder jQuery Plugin v1.8.3
 * @link http://mths.be/placeholder
 * @author Mathias Bynens <http://mathiasbynens.be/>
 */
(function(f){var e='placeholder' in document.createElement('input'),a='placeholder' in document.createElement('textarea');if(e&&a){f.fn.placeholder=function(){return this};f.fn.placeholder.input=f.fn.placeholder.textarea=true}else{f.fn.placeholder=function(){return this.filter((e?'textarea':':input')+'[placeholder]').bind('focus.placeholder',b).bind('blur.placeholder',d).trigger('blur.placeholder').end()};f.fn.placeholder.input=e;f.fn.placeholder.textarea=a;f(function(){f('form').bind('submit.placeholder',function(){var g=f('.placeholder',this).each(b);setTimeout(function(){g.each(d)},10)})});f(window).bind('unload.placeholder',function(){f('.placeholder').val('')})}function c(h){var g={},i=/^jQuery\d+$/;f.each(h.attributes,function(k,j){if(j.specified&&!i.test(j.name)){g[j.name]=j.value}});return g}function b(){var g=f(this);if(g.val()===g.attr('placeholder')&&g.hasClass('placeholder')){if(g.data('placeholder-password')){g.hide().next().attr('id',g.removeAttr('id').data('placeholder-id')).show().focus()}else{g.val('').removeClass('placeholder')}}}function d(h){var l,k=f(this),g=k,j=this.id;if(k.val()===''){if(k.is(':password')){if(!k.data('placeholder-textinput')){try{l=k.clone().attr({type:'text'})}catch(i){l=f('<input>').attr(f.extend(c(this),{type:'text'}))}l.removeAttr('name').data('placeholder-password',true).data('placeholder-id',j).bind('focus.placeholder',b);k.data('placeholder-textinput',l).data('placeholder-id',j).before(l)}k=k.removeAttr('id').hide().prev().attr('id',j).show()}k.addClass('placeholder').val(k.attr('placeholder'))}else{k.removeClass('placeholder')}}}(jQuery));


/*
 * timeago: a jQuery plugin, version: 0.9.3 (2011-01-21)
 * @requires jQuery v1.2.3 or later
 *
 * Timeago is a jQuery plugin that makes it easy to support automatically
 * updating fuzzy timestamps (e.g. "4 minutes ago" or "about 1 day ago").
 *
 * For usage and examples, visit:
 * http://timeago.yarp.com/
 *
 * Licensed under the MIT:
 * http://www.opensource.org/licenses/mit-license.php
 *
 * Copyright (c) 2008-2011, Ryan McGeary (ryanonjavascript -[at]- mcgeary [*dot*] org)
 */
(function(d){d.timeago=function(g){if(g instanceof Date){return a(g)}else{if(typeof g==="string"){return a(d.timeago.parse(g))}else{return a(d.timeago.datetime(g))}}};var f=d.timeago;d.extend(d.timeago,{settings:{refreshMillis:60000,allowFuture:false,strings:{prefixAgo:null,prefixFromNow:null,suffixAgo:"ago",suffixFromNow:"from now",seconds:"less than a minute",minute:"about a minute",minutes:"%d minutes",hour:"about an hour",hours:"about %d hours",day:"a day",days:"%d days",month:"about a month",months:"%d months",year:"about a year",years:"%d years",numbers:[]}},inWords:function(l){var m=this.settings.strings;var i=m.prefixAgo;var q=m.suffixAgo;if(this.settings.allowFuture){if(l<0){i=m.prefixFromNow;q=m.suffixFromNow}l=Math.abs(l)}var o=l/1000;var g=o/60;var n=g/60;var p=n/24;var j=p/365;function h(r,t){var s=d.isFunction(r)?r(t,l):r;var u=(m.numbers&&m.numbers[t])||t;return s.replace(/%d/i,u)}var k=o<45&&h(m.seconds,Math.round(o))||o<90&&h(m.minute,1)||g<45&&h(m.minutes,Math.round(g))||g<90&&h(m.hour,1)||n<24&&h(m.hours,Math.round(n))||n<48&&h(m.day,1)||p<30&&h(m.days,Math.floor(p))||p<60&&h(m.month,1)||p<365&&h(m.months,Math.floor(p/30))||j<2&&h(m.year,1)||h(m.years,Math.floor(j));return d.trim([i,k,q].join(" "))},parse:function(h){var g=d.trim(h);g=g.replace(/\.\d\d\d+/,"");g=g.replace(/-/,"/").replace(/-/,"/");g=g.replace(/T/," ").replace(/Z/," UTC");g=g.replace(/([\+\-]\d\d)\:?(\d\d)/," $1$2");return new Date(g)},datetime:function(h){var i=d(h).get(0).tagName.toLowerCase()==="time";var g=i?d(h).attr("datetime"):d(h).attr("title");return f.parse(g)}});d.fn.timeago=function(){var h=this;h.each(c);var g=f.settings;if(g.refreshMillis>0){setInterval(function(){h.each(c)},g.refreshMillis)}return h};function c(){var g=b(this);if(!isNaN(g.datetime)){d(this).text(a(g.datetime))}return this}function b(g){g=d(g);if(!g.data("timeago")){g.data("timeago",{datetime:f.datetime(g)});var h=d.trim(g.text());if(h.length>0){g.attr("title",h)}}return g.data("timeago")}function a(g){return f.inWords(e(g))}function e(g){return(new Date().getTime()-g.getTime())}document.createElement("abbr");document.createElement("time")}(jQuery));

