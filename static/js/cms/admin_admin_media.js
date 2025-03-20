ips.templates.set('templates.media.grid',"	<ul class='ipsGrid ipsGrid--pages-media'>		{{{contents}}}	</ul>");ips.templates.set('templates.media.noItems',"	<div class='i-text-align_center i-font-size_2 i-color_soft i-padding_4'>		{{#lang}}mediaEmptyFolder{{/lang}}	</div>");ips.templates.set('templates.media.noSearchResults',"	<div class='i-text-align_center i-font-size_2 i-color_soft i-padding_4'>		{{#lang}}mediaNoResults{{/lang}}	</div>");;
;(function($,_,undefined){"use strict";ips.controller.register('cms.admin.media.main',{_fileListing:null,_sidebar:null,_folderListing:null,_cachedFileInfo:{},_cachedFolders:{},_folderAjax:null,_searchTimer:null,_uploadURL:'',initialize:function(){this.on('click','[data-role="mediaItem"]',this.clickItem);this.on('click','[data-role="fileListing"]',this.clickWrapper);this.on('click','[data-role="mediaFolder"] > a',this.clickMediaFolder);this.on('click','[data-action="deleteSelected"]',this.deleteSelected);this.on('keydown','[data-role="mediaSearch"]',this.searchKeyPress);this.on('submitDialog','[data-role="uploadButton"]',this.dialogSubmitted);this.on('submitDialog','[data-role="replaceFile"]',this.replaceDialogSubmitted);this.on('click','[data-role="replaceFile"]',this.uploadNewFile);this.on(window,'resize',this.resizePanels);this.setup();},setup:function(){this._fileListing=this.scope.find('[data-role="fileListing"]');this._searchResults=this.scope.find('[data-role="searchResults"]');this._sidebar=this.scope.find('[data-role="mediaSidebar"]');this._folderListing=this.scope.find('[data-role="folderList"]');this._uploadURL=this.scope.find('[data-role="uploadButton"]').attr('href');this._newFolderURL=this.scope.find('[data-role="folderButton"]').attr('href');this._deleteFolderURL=this.scope.find('[data-action="deleteFolder"]').find('a').attr('href');this.scope.find('[data-role="uploadButton"]').attr('href',this._uploadURL+'&media_parent=0');this.scope.find('[data-role="folderButton"]').attr('href',this._newFolderURL+'&media_parent=0');this.scope.find('[data-role="fileListing"]').click();this.resizePanels();},resizePanels:function(){var windowHeight=$(window).height();this.scope.find('#elMedia_sidebar, #elMedia_fileList, #elMedia_searchResults').each(function(){var top=$(this).offset().top;$(this).css({height:(windowHeight-top)+'px'});});},dialogSubmitted:function(e,data){var newHTML=[];var folderID=data.response.folderID;_.each(data.response.rows,function(file,key){newHTML.push(file);});this._cachedFolders[folderID]=newHTML;this._buildFileListing(folderID,newHTML);ips.ui.flashMsg.show(ips.pluralize(ips.getString('mediaUploadedCount'),data.response.count));},replaceDialogSubmitted:function(e,data){var newHTML=[];var folderID=data.response.folderID;_.each(data.response.rows,function(file,key){newHTML.push(file);});this._cachedFolders[folderID]=newHTML;this._buildFileListing(folderID,newHTML);delete this._cachedFileInfo[data.response.fileID];ips.ui.flashMsg.show(ips.getString('mediaUploadedReplace'));this._fileListing.find('[data-fileid="'+data.response.fileID+'"]').click();},searchKeyPress:function(e){clearTimeout(this._searchTimer);this._searchTimer=setTimeout(_.bind(this._doSearch,this),500);},clickMediaFolder:function(e){e.preventDefault();var self=this;var allRows=this._folderListing.find('[data-role="mediaFolder"]');var row=$(e.currentTarget).closest('[data-role="mediaFolder"]');var rowURL=row.find('> a').attr('href');var rowID=row.attr('data-folderID');this._resetSearch();if(row.hasClass('ipsTreeList_activeBranch')){row.removeClass('ipsTreeList_activeBranch').addClass('ipsTreeList_inactiveBranch');}else{row.removeClass('ipsTreeList_inactiveBranch').addClass('ipsTreeList_activeBranch');}
allRows.removeClass('ipsTreeList_activeNode');row.addClass('ipsTreeList_activeNode');this.scope.find('[data-role="uploadButton"]').attr('href',this._uploadURL+'&media_parent='+rowID);this.scope.find('[data-role="folderButton"]').attr('href',this._newFolderURL+'&media_parent='+rowID);this.scope.find('[data-role="folderButton"]').attr('href',this._newFolderURL+'&media_parent='+rowID);this.scope.find('[data-action="deleteFolder"]').find('a').attr('href',this._deleteFolderURL+'&id='+rowID);if(rowID>0){this.scope.find('[data-action="deleteFolder"]').removeClass('ipsHide');}else{this.scope.find('[data-action="deleteFolder"]').addClass('ipsHide');}
this._loadFolders(rowID,rowURL);this._loadFiles(rowID,rowURL);this._unselectAll();this._updatePreview();this._checkDeleteButton();},deleteSelected:function(e){var self=this;var selected=this._getSelected().closest('[data-role="mediaItem"]');var selectedIDs=[];$.each(selected,function(){selectedIDs.push($(this).attr('data-fileid'));});ips.ui.alert.show({type:'confirm',message:ips.pluralize(ips.getString('mediaConfirmDelete'),selected.length),icon:'warn',callbacks:{ok:function(){selected.find('.cMedia_itemSelected').removeClass('cMedia_itemSelected')
ips.utils.anim.go('fadeOutDown',selected).done(function(){selected.remove();self._updatePreview();self._checkDeleteButton();});ips.getAjax()('?app=cms&module=pages&controller=media&do=deleteByFileIds',{data:{fileIds:selectedIDs}}).fail(function(){ips.ui.alert.show({type:'alert',message:ips.pluralize(ips.getString('mediaErrorDeleting'),selected.length)});})}}});},clickWrapper:function(e){if(!$(e.target).closest('[data-role="mediaItem"]').length){this._unselectAll();this._updatePreview();this._checkDeleteButton();}},clickItem:function(e){var item=$(e.currentTarget).find('> .cMedia_item');var metaPressed=e.metaKey;var shiftPressed=e.shiftKey;if(!metaPressed&&!shiftPressed){this._unselectAll();item.addClass('cMedia_itemSelected');this._switchToTab('overview');}else if(metaPressed){item.toggleClass('cMedia_itemSelected',(!item.hasClass('cMedia_itemSelected')));}else if(shiftPressed){var all=this._fileListing.find('.cMedia_item');var indexOfFirst=all.index(all.filter('.cMedia_itemSelected').first());var indexOfClicked=all.index(item);var itemsToSelect=null;this._unselectAll();if(indexOfFirst==indexOfClicked){itemsToSelect=item;}else if(indexOfFirst<indexOfClicked){itemsToSelect=all.slice(indexOfFirst,indexOfClicked).addBack(item);}else if(indexOfFirst>indexOfClicked){itemsToSelect=all.slice(indexOfClicked,indexOfFirst+1);}
itemsToSelect.addClass('cMedia_itemSelected');}
this._updatePreview();this._checkDeleteButton();},uploadNewFile:function(e){if(e){e.preventDefault();}
var itemId=this._getSelected().closest('[data-role="mediaItem"]').attr('data-fileid');$(e.currentTarget).ipsDialog({remoteSubmit:true,forceReload:true,url:$(e.currentTarget).attr('data-baseUrl')+itemId,destructOnClose:true,title:ips.getString('replaceMediaFile')});},_checkDeleteButton:function(){var selected=this._getSelected().closest('[data-role="mediaItem"]');if(selected.length==0){this.scope.find('[data-action="deleteSelected"]').addClass('ipsHide');}else{this.scope.find('[data-action="deleteSelected"]').removeClass('ipsHide');}},_loadFolders:function(rowID,url){var self=this;var row=this._folderListing.find('[data-folderID="'+rowID+'"]');if(this._folderAjax&&_.isFunction(this._folderAjax.abort)){this._folderAjax.abort();}
if(row.attr('data-loaded')){return;}
this._folderAjax=ips.getAjax()(url,{data:{get:'folders'}}).done(function(response){var subFolder=row.find('ul');var newHTML=[];_.each(response,function(folder,key){newHTML.push(folder);});subFolder.html(newHTML.join(''));row.attr('data-loaded',true);});},_loadFiles:function(folderID,url){var self=this;if(this._filesAjax&&_.isFunction(this._filesAjax.abort)){this._filesAjax.abort();}
if(this._fileListing.attr('data-showing')==folderID){return;}
if(!_.isUndefined(this._cachedFolders[folderID])){this._buildFileListing(folderID,this._cachedFolders[folderID]);return;}
this._fileListing.addClass('ipsLoading').html('');ips.getAjax()(url,{data:{get:'files'}}).done(function(response){var newHTML=[];_.each(response,function(file,key){newHTML.push(file);});self._cachedFolders[folderID]=newHTML;self._buildFileListing(folderID,newHTML);}).always(function(){self._fileListing.removeClass('ipsLoading');});},_buildFileListing:function(folderID,data){var output;if(!data.length){output=ips.templates.render('templates.media.noItems');}else{output=ips.templates.render('templates.media.grid',{contents:data.join('')});}
this._fileListing.attr('data-showing',folderID).html(output);$(document).trigger('contentChange',[this._fileListing]);},_updatePreview:function(){var self=this;var selected=this._getSelected().closest('[data-role="mediaItem"]');if(selected.length==0||selected.length>1){var language=(selected.length==0)?ips.getString('mediaNoneSelected'):ips.pluralize(ips.getString('mediaMultipleSelected'),selected.length);this._sidebar.find('[data-role="multipleItemsMessage"]').html(language);this._sidebar.find('[data-role="itemInformation"]').hide().end().find('[data-role="multipleItems"]').show();this._sidebar.find('[data-role="replaceFile"]').hide();}else{var info={itemFilename:selected.attr('data-filename'),itemUploaded:selected.attr('data-uploaded'),itemTag:'{media="'+selected.attr('data-fileid')+'"}',itemUrl:selected.attr('data-url'),itemID:selected.attr('data-fileid'),itemIsImage:selected.attr('data-fileType')==='image',itemFilesize:null,itemDimensions:null};var cache=this._cachedFileInfo[info.itemID];if(!_.isUndefined(cache)){info.itemFilesize=cache['itemFilesize'];info.itemDimensions=cache['itemDimensions'];}else{this._infoAjax=ips.getAjax()('?app=cms&module=pages&controller=media&do=getFileInfo&id='+info.itemID).done(function(response){self._cachedFileInfo[info.itemID]={itemFilesize:response.fileSize,itemDimensions:response.dimensions};self._sidebar.find('[data-role="itemFilesize"]').text(response.fileSize);self._sidebar.find('[data-role="itemDimensions"]').text(response.dimensions);});}
if(info.itemIsImage){info.itemPreview=$('<img/>').attr('src',selected.attr('data-url')).attr('data-ipsLightbox',true);}else{info.itemPreview='';}
_.each(info,function(value,key){var elem=self._sidebar.find('[data-role="'+key+'"]');if(elem.is('input')){elem.val(value);}else if(key=='itemPreview'){elem.html(value);}else if(key=='itemUrl'){elem.html($('<a/>').attr('href',value).text(value).attr('target','_blank'));}
else if(value===null){elem.html($('<span/>').addClass('i-color_soft').text('Loading...'));}else{elem.text(value);}});this._sidebar.find('[data-role="itemDimensionsRow"]').toggle(info.itemIsImage);this._sidebar.find('[data-role="itemTag"]').get(0).select();this._sidebar.find('[data-role="itemInformation"]').show().end().find('[data-role="multipleItems"]').hide();this._sidebar.find('[data-role="replaceFile"]').show();$(document).trigger('contentChange',[this._sidebar]);}},_doSearch:function(){if(this._searchAjax&&_.isFunction(this._searchAjax.abort)){this._searchAjax.abort();}
var self=this;var value=this.scope.find('[data-role="mediaSearch"]').val();if(!_.isEmpty(value)){this._searchResults.show().addClass('ipsLoading');this._fileListing.hide();this._folderListing.addClass('cMedia_treeDisabled');this._searchAjax=ips.getAjax()('?app=cms&module=pages&controller=media&do=search',{data:{input:value}}).done(function(response){var newHTML=[];var output='';_.each(response,function(file,key){newHTML.push(file);});if(!newHTML.length){output=ips.templates.render('templates.media.noSearchResults');}else{output=ips.templates.render('templates.media.grid',{contents:newHTML.join('')});}
self._searchResults.removeClass('ipsLoading').html(output);$(document).trigger('contentChange',[self._searchResults]);self._unselectAll();self._updatePreview();self._checkDeleteButton();})}else{this._searchResults.hide().html('');this._fileListing.show();this._folderListing.removeClass('cMedia_treeDisabled');this._updatePreview();this._checkDeleteButton();}},_getSelected:function(){if(this._fileListing.is(':visible')){return this._fileListing.find('.cMedia_itemSelected');}else{return this._searchResults.find('.cMedia_itemSelected');}},_resetSearch:function(){this.scope.find('[data-role="mediaSearch"]').val('');this._searchResults.hide();this._fileListing.show();this._unselectAll();this._updatePreview();this._checkDeleteButton();},_unselectAll:function(){this._fileListing.find('.cMedia_item').removeClass('cMedia_itemSelected');this._searchResults.find('.cMedia_item').removeClass('cMedia_itemSelected');},_switchToTab:function(tab){if(tab=='overview'){this.scope.find('#ipsTabs_mediaSidebar_overview').click();}}});}(jQuery,_));;