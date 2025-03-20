;(function($,_,undefined){"use strict";ips.controller.register('core.global.customization.visualLang',{timeout:null,initialize:function(){this.on(document,'mousedown','span[data-vle]',this.mouseDownLang);this.on(document,'mouseup mouseleave','span[data-vle]',this.mouseUpLang);this.on(document,'keypress','input[type="text"][data-role="vle"]',this.keyPressEditBox);this.on(document,'blur','input[type="text"][data-role="vle"]',this.blurEditBox);this.on(document,'contentChange',this.contentChange);this.setup();},setup:function(){var self=this;this._boundHandler=_.bind(this._preventDefaultHandler,this);this._removeLangTag('title');$(document).ready(function(){self._setUpTextNodes('body');self._removeLangTag('body');self.scope.trigger('vleDone');});},contentChange:function(e,data){this._setUpTextNodes(data);this._removeLangTag(data);},mouseDownLang:function(e){this.timeout=setTimeout(_.partial(this._enableLangEditing,e),1000);},mouseUpLang:function(){clearTimeout(this.timeout);},keyPressEditBox:function(e){if(e.keyCode==ips.ui.key.ENTER){e.stopPropagation();$(e.currentTarget).blur();return false;}},blurEditBox:function(e){var inputNode=$(e.currentTarget);var value=inputNode.val();var safeValue=encodeURIComponent(value);var elem=inputNode.closest('[data-vle]');var url='?app=core&module=system&controller=vle&do=set';if(value==elem.attr('data-original')||value==''){elem.html(elem.attr('data-original'));}else{inputNode.val('').addClass('ipsField_loading');ips.getAjax()(url+'&key='+elem.attr('data-vle')+'&value='+safeValue).done(function(response){ipsVle[elem.attr('data-vle')]=response;elem.attr('data-original',response);$(document).find('[data-vle="'+elem.attr('data-vle')+'"]').html(response);}).fail(function(){Debug.log(url+'&key='+elem.attr('data-vle')+'&value='+safeValue);elem.html(inputNode.attr('data-original'));ips.ui.alert.show({type:'alert',icon:'warn',message:ips.getString('js_login_both'),});});}
var parentLink=elem.closest('a');if(parentLink.length){parentLink.off('click',this._boundHandler);if(parentLink.attr('data-vleHref')){parentLink.attr('href',parentLink.attr('data-vleHref')).removeAttr('data-vleHref');}}},_preventDefaultHandler:function(e){e.preventDefault();},_enableLangEditing:function(e){var elem=$(e.currentTarget);var parentLink=elem.closest('a');if(parentLink.length){parentLink.on('click',this._boundHandler).attr('data-vleHref',parentLink.attr('href')).attr('href','#');}
var inputNode=$('<input/>').attr({type:'text'}).addClass('ipsField_loading ipsField_vle').attr('data-role','vle');elem.html('').append(inputNode);ips.getAjax()('?app=core&module=system&controller=vle&do=get&key='+elem.attr('data-vle')).done(function(response){console.log(elem.attr('data-vle'));inputNode.val(response).attr({'data-original':response}).removeClass('ipsField_loading').focus().select()}).fail(function(){ips.ui.alert.show({type:'alert',icon:'warn',message:ips.getString('js_login_both'),});});},_removeLangTag:function(element){if(_.isUndefined(element)){return;}
var elem=$(element);elem.contents().filter(function(){return this.nodeType===3||this.tagName==="LABEL"||this.tagName==="SPAN";}).each(function(){$(this).replaceWith($(this).text().replace(/#VLE#(.+?)#!#/gm,function(match,key){return(ipsVle.hasOwnProperty(key)&&ipsVle[key]!==undefined)?ipsVle[key]:'';}));});elem.find('i[class]').each(function(){$(this).attr('class',$(this).attr('class').replace(/#VLE#(.+?)#!#/gm,function(match,key){return(ipsVle.hasOwnProperty(key)&&ipsVle[key]!==undefined)?ipsVle[key]:'';}));});elem.find('[placeholder]').each(function(){$(this).attr('placeholder',$(this).attr('placeholder').replace(/#VLE#(.+?)#!#/gm,function(match,key){return(ipsVle.hasOwnProperty(key)&&ipsVle[key]!==undefined)?ipsVle[key]:'';}));});elem.find('[title]').each(function(){$(this).attr('title',$(this).attr('title').replace(/#VLE#(.+?)#!#/gm,function(match,key){return(ipsVle.hasOwnProperty(key)&&ipsVle[key]!==undefined)?ipsVle[key]:'';}));});elem.find('[aria-label]').each(function(){$(this).attr('aria-label',$(this).attr('aria-label').replace(/#VLE#(.+?)#!#/gm,function(match,key){return(ipsVle.hasOwnProperty(key)&&ipsVle[key]!==undefined)?ipsVle[key]:'';}));});},_setUpTextNodes:function(element){if(_.isUndefined(element)){return;}
var regex=/#VLE#([0-9a-z_-]+?)#!#/igm;$(element).find('*').contents().filter(function(){var elem=$(this);return!elem.is('iframe')&&!elem.closest('[data-ipsEditor]').length&&!elem.is('textarea')&&(elem.is('[value]')||this.nodeType==3);}).each(function(idx,elem){var elem=$(elem);if(elem.get(0).nodeType===3){elem.replaceWith(elem.text().replace(regex,function(match,key){if(ipsVle.hasOwnProperty(key)&&ipsVle[key]!==undefined){return'<span data-vle="'+key+'" data-original="'+ipsVle[key]+'">'+ipsVle[key]+'</span>';}else{return Debug.isEnabled()?key:'';}}));}else if(elem.is('[value]')){if(elem.val()!==''){elem.attr('data-vle',elem.val().replace(regex,'$1')).val(elem.val().replace(regex,function(match,key){if(ipsVle.hasOwnProperty(key)&&ipsVle[key]!==undefined){return ipsVle[key];}else{return Debug.isEnabled()?key:'';}}));}}});}});}(jQuery,_));;