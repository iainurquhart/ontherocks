/*!
 * Craft by Pixel & Tonic
 *
 * @package   Craft
 * @author    Pixel & Tonic, Inc.
 * @copyright Copyright (c) 2013, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license1.0.html Craft License
 * @link      http://buildwithcraft.com
 */
if(typeof Assets=="undefined"){Assets={}}Assets.FileManager=Garnish.Base.extend({$activeFolder:null,init:function(a,c){this.$manager=a;this.setSettings(c,Assets.FileManager.defaults);this.$toolbar=$(".toolbar",this.$manager);this.$upload=$(".buttons .assets-upload");this.$spinner=$(".spinner",this.$manager);this.$status=$(".asset-status");this.$scrollpane=null;this.$folders=Craft.cp.$sidebarNav.find(".assets-folders:first");this.$folderContainer=$(".folder-container");this.$search=$("> .search",this.$toolbar);this.$searchInput=$("input",this.$search);this.$searchOptions=$(".search-options",this.$search);this.$searchModeCheckbox=$("input",this.$searchOptions);this.$viewAsThumbsBtn=$("a.thumbs",this.$toolbar);this.$viewAsListBtn=$("a.list",this.$toolbar);this.$uploadProgress=$("> .assets-uploadprogress",this.$manager);this.$uploadProgressBar=$(".assets-pb-bar",this.$uploadProgress);this.$modalContainerDiv=null;this.$prompt=null;this.$promptApplyToRemainingContainer=null;this.$promptApplyToRemainingCheckbox=null;this.$promptApplyToRemainingLabel=null;this.$promptButtons=null;this.modal=null;this.sort="asc";this.requestId=0;this.promptArray=[];this.offset=0;this.nextOffset=0;this.lastPageReached=false;this.searchTimeout=null;this.searchVal="";this.showingSearchOptions=false;this.selectedFileIds=[];this.folders=[];this.folderSelect=null;this.fileSelect=null;this.filesView=null;this.fileDrag=null;this.folderDrag=null;this._singleFileMenu=[];this._multiFileMenu=[];this._promptCallback=function(){};if(this.settings.mode=="full"){this.$scrollpane=Garnish.$win}else{this.$scrollpane=this.$manager}this.currentState={view:"thumbs",currentFolder:null,folders:{},searchMode:"shallow",orderBy:"filename",sortOrder:"ASC"};this.storageKey="Craft_Assets_"+this.settings.namespace;if(typeof(localStorage)!=="undefined"){if(typeof(localStorage[this.storageKey])=="undefined"){localStorage[this.storageKey]=JSON.stringify(this.currentState)}else{this.currentState=JSON.parse(localStorage[this.storageKey])}}this.storeState=function(e,f){this.currentState[e]=f;if(typeof(localStorage)!=="undefined"){localStorage[this.storageKey]=JSON.stringify(this.currentState)}};this.setFolderState=function(e,g){if(typeof this.currentState.folders!="object"){var f={}}else{f=this.currentState.folders}f[e]=g;this.storeState("folders",f)};this.uploader=new qqUploader.FileUploader({element:this.$upload[0],action:Craft.actionUrl+"/assets/uploadFile",template:'<div class="assets-qq-uploader"><div class="assets-qq-upload-drop-area"></div><a href="javascript:;" class="btn submit assets-qq-upload-button" data-icon="↑" style="position: relative; overflow: hidden; direction: ltr; ">'+Craft.t("Upload files")+'</a><ul class="assets-qq-upload-list"></ul></div>',fileTemplate:'<li><span class="assets-qq-upload-file"></span><span class="assets-qq-upload-spinner"></span><span class="assets-qq-upload-size"></span><a class="assets-qq-upload-cancel" href="#">Cancel</a><span class="assets-qq-upload-failed-text">Failed</span></li>',classes:{button:"assets-qq-upload-button",drop:"assets-qq-upload-drop-area",dropActive:"assets-qq-upload-drop-area-active",list:"assets-qq-upload-list",file:"assets-qq-upload-file",spinner:"assets-qq-upload-spinner",size:"assets-qq-upload-size",cancel:"assets-qq-upload-cancel",success:"assets-qq-upload-success",fail:"assets-qq-upload-fail"},onSubmit:$.proxy(this,"_onUploadSubmit"),onProgress:$.proxy(this,"_onUploadProgress"),onComplete:$.proxy(this,"_onUploadComplete")});if(this.$upload.length==2){$(this.$upload[1]).replaceWith($(this.$upload[0]).clone(true))}this.folderSelect=new Garnish.Select(this.$folders,{selectedClass:"sel",multi:false,waitForDblClick:false,vertical:true,onSelectionChange:$.proxy(this,"loadFolderContents")});this.$topFolderUl=this.$folders;this.$topFolderLis=this.$topFolderUl.children().filter("li");if(!this.$topFolderLis.length){return}for(var b=0;b<this.$topFolderLis.length;b++){d=new Assets.FileManagerFolder(this,this.$topFolderLis[b],1)}if(this.currentState.searchMode=="deep"){this.$searchModeCheckbox.prop("checked",true)}this.folderDrag=new Garnish.DragDrop({activeDropTargetClass:"sel assets-fm-dragtarget",helperOpacity:0.5,filter:$.proxy(function(){var f=this.folderSelect.getSelectedItems(),h=[];for(var g=0;g<f.length;g++){var e=$(f[g]).parent()[0];if($.inArray(e,this.$topFolderLis)!=-1){this.folderSelect.deselectItem($(f[g]));continue}h.push(e)}return $(h)},this),helper:$.proxy(function(f){var e=$('<ul class="assets-fm-folderdrag" />').append(f);$("> a",f).removeClass("assets-fm-expanded");$("> ul",f).hide();e.width(this.$folders[0].scrollWidth);return e},this),dropTargets:$.proxy(function(){var f=[];for(var e in this.folders){var g=this.folders[e];if(g.visible&&$.inArray(g.$li[0],this.folderDrag.$draggee)==-1){f.push(g.$a)}}return f},this),onDragStart:$.proxy(function(){this.tempExpandedFolders=[];$("> a.assets-fm-expanded + ul",this.folderDrag.$draggee).hide()},this),onDropTargetChange:$.proxy(this,"_onDropTargetChange"),onDragStop:$.proxy(function(){$("> a.assets-fm-expanded + ul",this.folderDrag.$draggee).show();if(this.folderDrag.$activeDropTarget&&this.folderDrag.$activeDropTarget.siblings("ul").find(">li").filter(this.folderDrag.$draggee).length==0){var e=this.folderDrag.$activeDropTarget.attr("data-id");this._collapseExtraExpandedFolders(e);var g=[];for(var m=0;m<this.folderDrag.$draggee.length;m++){var l=$("> a",this.folderDrag.$draggee[m]),j=l.attr("data-id"),h=this.folders[j];if(h.parent.id!=e){g.push(j)}}if(g.length){g.sort();g.reverse();this.setAssetsBusy();this._initProgressBar();var f=[];for(var m=0;m<g.length;m++){f.push({folderId:g[m],parentId:e})}this.responseArray=[];this.requestId++;var q=[];var k=[];var o={};var p=[];var n=$.proxy(function(x){this.promptArray=[];for(var s=0;s<x.length;s++){var v=x[s];if(v.success){if(v.transferList&&v.deleteList&&v.changedFolderIds){for(var t=0;t<v.transferList.length;t++){q.push(v.transferList[t])}for(var t=0;t<v.deleteList.length;t++){k.push(v.deleteList[t])}for(var w in v.changedFolderIds){o[w]=v.changedFolderIds[w]}p.push(v.removeFromTree)}}if(v.prompt){this.promptArray.push(v)}if(v.error){alert(v.error)}}if(this.promptArray.length>0){var u=$.proxy(function(A){this.promptArray=[];this.$folderContainer.html("");var y=[];for(var z=0;z<A.length;z++){if(A[z].choice=="cancel"){continue}f[0].action=A[z].choice;y.push(f[0])}if(y.length==0){$.proxy(this,"_performActualFolderMove",q,k,o,p)()}else{this.setAssetsBusy();this._initProgressBar();r(y,0,n)}},this);this._showBatchPrompts(this.promptArray,u);this.setAssetsAvailable();this._hideProgressBar()}else{$.proxy(this,"_performActualFolderMove",q,k,o,p)()}},this);var r=$.proxy(function(s,i,t){if(i==0){this.responseArray=[]}Craft.postActionRequest("assets/moveFolder",s[i],$.proxy(function(v){i++;var u=Math.min(100,Math.round(100*i/s.length))+"%";this.$uploadProgressBar.width(u);this.responseArray.push(v);if(i>=s.length){t(this.responseArray)}else{r(s,i,t)}},this))},this);r(f,0,n);return}}else{this._collapseExtraExpandedFolders()}this.folderDrag.returnHelpersToDraggees()},this)});this.fileDrag=new Garnish.DragDrop({activeDropTargetClass:"sel assets-fm-dragtarget",helperOpacity:0.5,filter:$.proxy(function(){return this.fileSelect.getSelectedItems()},this),helper:$.proxy(function(e){return this.filesView.getDragHelper(e)},this),dropTargets:$.proxy(function(){var f=[];for(var e in this.folders){var g=this.folders[e];if(g.visible){f.push(g.$a)}}return f},this),onDragStart:$.proxy(function(){this.tempExpandedFolders=[];$selectedFolders=this.folderSelect.getSelectedItems();$selectedFolders.removeClass("sel")},this),onDropTargetChange:$.proxy(this,"_onDropTargetChange"),onDragStop:$.proxy(function(){if(this.fileDrag.$activeDropTarget){this.fileDrag.$activeDropTarget.addClass("sel");var h=this.fileDrag.$activeDropTarget.attr("data-id");var m=[],g=[];for(var e=0;e<this.fileDrag.$draggee.length;e++){var k=this.fileDrag.$draggee[e].getAttribute("data-id"),l=this.fileDrag.$draggee[e].getAttribute("data-fileName");m.push(k);g.push(l)}if(m.length){this.setAssetsBusy();this._initProgressBar();var f=[];for(var e=0;e<m.length;e++){f.push({fileId:m[e],folderId:h,fileName:g[e]})}var j=$.proxy(function(q){this.promptArray=[];for(var n=0;n<q.length;n++){var p=q[n];if(p.prompt){this.promptArray.push(p)}if(p.error){alert(p.error)}}this.setAssetsAvailable();this._hideProgressBar();if(this.promptArray.length>0){var o=$.proxy(function(u){this.$folderContainer.html("");var r=[];for(var s=0;s<u.length;s++){if(u[s].choice=="cancel"){continue}for(var t=0;t<f.length;t++){if(f[t].fileName==u[s].fileName){f[t].action=u[s].choice;r.push(f[t])}}}if(r.length==0){this.loadFolderContents()}else{this.setAssetsBusy();this._initProgressBar();this._moveFile(r,0,j)}},this);this.fileDrag.fadeOutHelpers();this._showBatchPrompts(this.promptArray,o)}else{this.fileDrag.fadeOutHelpers();this.loadFolderContents()}},this);this._moveFile(f,0,j);return}}else{this._collapseExtraExpandedFolders()}$selectedFolders.addClass("sel");this.fileDrag.returnHelpersToDraggees()},this)});this._moveFile=$.proxy(function(f,e,g){if(e==0){this.responseArray=[]}Craft.postActionRequest("assets/moveFile",f[e],$.proxy(function(i){e++;var h=Math.min(100,Math.round(100*e/f.length))+"%";this.$uploadProgressBar.width(h);this.responseArray.push(i);if(e>=f.length){g(this.responseArray)}else{this._moveFile(f,e,g)}},this))},this);this._performActualFolderMove=$.proxy(function(g,e,f,i){this.setAssetsBusy();this._initProgressBar();var h=$.proxy(function(l,o,p){var k;for(var q in o){var j=o[q].newId;var r=o[q].newParentId;var n=this.folders[q];this.folders[j]=n;$('li.assets-fm-folder > a[data-id="'+q+'"]:first').attr("data-id",j);k=this.folders[j];k.moveTo(r);k.select()}for(var m=0;m<l.length;m++){Craft.postActionRequest("assets/deleteFolder",{folderId:l[m]})}if(p.length>0){for(var m=0;m<p.length;m++){if(p[m].length){this.folders[p[m]].onDelete(true)}}}this.setAssetsAvailable();this._hideProgressBar();this.loadFolderContents();this.folderDrag.returnHelpersToDraggees()},this);if(g.length>0){this._moveFile(g,0,$.proxy(function(){h(e,f,i)},this))}else{h(e,f,i)}},this);this.$searchInput.keydown($.proxy(this,"_onSearchKeyDown"));this.$searchModeCheckbox.change($.proxy(this,"_onSearchModeChange"));this.$viewAsThumbsBtn.click($.proxy(function(){this.selectViewType("thumbs");this.markActiveViewButton()},this));this.$viewAsListBtn.click($.proxy(function(){this.selectViewType("list");this.markActiveViewButton()},this));if(typeof this.currentState.currentFolder=="undefined"||this.currentState.currentFolder==null){this.storeState("currentFolder",this.$folders.find("a[data-id]").attr("data-id"))}this.markActiveFolder(this.currentState.currentFolder);this.markActiveViewButton();for(var d in this.currentState.folders){if(this.currentState.folders[d]=="expanded"&&typeof this.folders[d]!=="undefined"&&this.folders[d].hasSubfolders()){this.folders[d]._prepForSubfolders();this.folders[d].expand()}}this.loadFolderContents()},_onSearchKeyDown:function(a){if(a.metaKey||a.ctrlKey){return}a.stopPropagation();clearTimeout(this.searchTimeout);setTimeout($.proxy(function(){switch(a.keyCode){case 13:a.preventDefault();this._checkKeywordVal();break;case 27:a.preventDefault();this.$searchInput.val("");this._checkKeywordVal();break;default:this.searchTimeout=setTimeout($.proxy(this,"_checkKeywordVal"),500)}},this),0)},_checkKeywordVal:function(){if(this.searchVal!==(this.searchVal=this.$searchInput.val())){if(this.searchVal&&!this.showingSearchOptions){this._showSearchOptions()}else{if(!this.searchVal&&this.showingSearchOptions){this._hideSearchOptions()}}this.updateFiles()}},_showSearchOptions:function(){this.showingSearchOptions=true;this.$searchOptions.stop().slideDown("fast")},_hideSearchOptions:function(){this.showingSearchOptions=false;this.$searchOptions.stop().slideUp("fast")},_onSearchModeChange:function(){if(this.$searchModeCheckbox.prop("checked")){var a="deep"}else{var a="shallow"}this.storeState("searchMode",a);this.updateFiles()},selectViewType:function(a){this.storeState("view",a);this.loadFolderContents()},markActiveViewButton:function(){if(this.currentState.view=="thumbs"){this.$viewAsThumbsBtn.addClass("active");this.$viewAsListBtn.removeClass("active");this.$folderContainer.addClass("assets-tv-file").removeClass("assets-listview").removeClass("assets-tv-bigthumb")}else{this.$viewAsThumbsBtn.removeClass("active");this.$viewAsListBtn.addClass("active");this.$folderContainer.removeClass("assets-tv-file").addClass("assets-listview").removeClass("assets-tv-bigthumb")}},markActiveFolder:function(a){if(this.$activeFolder){this.$activeFolder.removeClass("sel")}this.$activeFolder=this.$folders.find("a[data-id="+a+"]:first").addClass("sel");if(Craft.cp.$altSidebarNavBtn){Craft.cp.$altSidebarNavBtn.text(this.$activeFolder.text())}},loadFolderContents:function(){var a=this.$folders.find("a.sel");if(a.length==0){a=this.$folders.find("li:first")}this.markActiveFolder(a.attr("data-id"));this.storeState("currentFolder",a.attr("data-id"));this.updateFiles()},updateFiles:function(b){this._setUploadFolder(this.getCurrentFolderId());this.setAssetsBusy();this.offset=0;this.nextOffset=0;this._singleFileMenu=[];this._multiFileMenu=[];if(this.settings.mode=="full"){this.fileDrag.removeAllItems()}this._beforeLoadFiles();var a=this._prepareFileViewPostData();if(this.fileSelect){this.fileSelect.destroy()}if(this.filesView){this.filesView.destroy()}this.fileSelect=this.filesView=null;Craft.postActionRequest("assets/viewFolder",a,$.proxy(function(e,f){if(e.requestId!=this.requestId){return}this.$folderContainer.attr("data",this.getCurrentFolderId());this.$folderContainer.html(e.html);if(this.currentState.view=="list"){this.filesView=new Assets.ListView($("> .folder-contents > .listview",this.$folderContainer),{orderby:this.currentState.orderBy,sort:this.currentState.sortOrder,onSortChange:$.proxy(function(h,g){this.storeState("orderBy",h);this.storeState("sortOrder",g);this.updateFiles()},this)})}else{this.filesView=new Assets.ThumbView($("> .folder-contents > .thumbs",this.$folderContainer))}this.fileSelect=new Garnish.Select(this.$files,{selectedClass:"assets-selected",multi:this.settings.multiSelect,waitForDblClick:(this.settings.multiSelect&&this.settings.mode=="select"),vertical:(this.currentState.view=="list"),onSelectionChange:$.proxy(this,"_onFileSelectionChange"),$scrollpane:this.$scrollpane});var d=this.filesView.getItems().not(".assets-disabled");this._afterLoadFiles(e,d);this._onFileSelectionChange();if(this.selectedFileIds.length){var c=this.fileSelect.getSelectedItems();Garnish.scrollContainerToElement(this.$scrollpane,c)}if(typeof b=="function"){b()}this.setAssetsAvailable();this._initializePageLoader()},this))},_beforeLoadFiles:function(){if(typeof this.settings.onBeforeUpdateFiles=="function"){this.settings.onBeforeUpdateFiles()}},_prepareFileViewPostData:function(a){var b={requestId:++this.requestId,folderId:this.currentState.currentFolder,viewType:this.currentState.view,keywords:this.$searchInput.val(),searchMode:this.currentState.searchMode};if(this.currentState.view=="list"){b.orderBy=this.currentState.orderBy;b.sortOrder=this.currentState.sortOrder}return b},_afterLoadFiles:function(b,a){if(b.total>0){this.nextOffset+=b.total;this.lastPageReached=false}else{this.lastPageReached=true}this.fileSelect.addItems(a);if(this.settings.mode=="full"){this.fileDrag.addItems(a)}this.addListener(a,"dblclick",function(e){switch(this.settings.mode){case"select":clearTimeout(this.fileSelect.clearMouseUpTimeout());this.settings.onSelect();break;case"full":this._showProperties(e);break}});var c=[{label:Craft.t("View file"),onClick:$.proxy(this,"_viewFile")}];if(this.settings.mode=="full"){c.push({label:Craft.t("Edit properties"),onClick:$.proxy(this,"_showProperties")});c.push({label:Craft.t("Rename file"),onClick:$.proxy(this,"_renameFile")});c.push("-");c.push({label:Craft.t("Delete file"),onClick:$.proxy(this,"_deleteFile")})}this._singleFileMenu.push(new Garnish.ContextMenu(a,c,{menuClass:"assets-contextmenu"}));if(this.settings.mode=="full"){var d=new Garnish.ContextMenu(a,[{label:Craft.t("Delete"),onClick:$.proxy(this,"_deleteFiles")}],{menuClass:"assets-contextmenu"});d.disable();this._multiFileMenu.push(d)}},_initializePageLoader:function(){if(!this.lastPageReached){var a=function(){if(!this.$manager.hasClass("assets-page-loading")&&Garnish.$win.scrollTop()+Garnish.$win.height()>Garnish.$doc.height()-400){this.$manager.addClass("assets-page-loading");Garnish.$win.unbind("scroll",$.proxy(a,this));this.loadMoreFiles()}};a.call(this);Garnish.$win.bind("scroll",$.proxy(a,this))}},loadMoreFiles:function(){if(this.lastPageReached){return}this.requestId++;this._beforeLoadFiles();var a=this._prepareFileViewPostData();a.offset=this.nextOffset;Craft.postActionRequest("assets/viewFolder",a,$.proxy(function(b,c){this.$manager.removeClass("assets-page-loading");if(c=="success"){if(b.requestId!=this.requestId){return}if(this.currentState.view=="list"){$newFiles=$(b.html).find("tbody>tr")}else{$newFiles=$(b.html).find("ul li")}if($newFiles.length>0){$enabledFiles=$newFiles.not(".assets-disabled");if(this.filesView!=null){this.filesView.addItems($newFiles);this._afterLoadFiles(b,$enabledFiles)}this.$folderContainer.append($(b.html).find("style"));this._initializePageLoader()}}},this))},_viewFile:function(a){window.open($(a.currentTarget).attr("data-url"))},_renameFile:function(f){var g=this._getDataContainer(f);var c=g.attr("data-id"),d=g.attr("data-filename"),b=prompt(Craft.t("Rename file"),d);if(b&&b!=d){this.setAssetsBusy();var a={fileId:c,folderId:g.attr("data-folder"),fileName:b};var e=function(h,i){this.setAssetsAvailable();if(i=="success"){if(h.prompt){this._showPrompt(h.prompt,h.choices,$.proxy(function(j){if(j!="cancel"){a.action=j;Craft.postActionRequest("assets/moveFile",a,$.proxy(e,this))}},this))}if(h.success){this.updateFiles()}if(h.error){alert(h.error)}}};Craft.postActionRequest("assets/moveFile",a,$.proxy(e,this))}},_deleteFile:function(b){var d=this._getDataContainer(b);var a=d.attr("data-id");var c=d.attr("data-fileName");if(confirm(Craft.t('Are you sure you want to delete "{file}"?',{file:c}))){this.setAssetsBusy();Craft.postActionRequest("assets/deleteFile",{fileId:a},$.proxy(function(e,f){this.setAssetsAvailable();if(f=="success"){if(e.error){alert(e.error)}this.updateFiles()}},this))}},_deleteFiles:function(){if(confirm(Craft.t("Are you sure you want to delete these {number} files?",{number:this.fileSelect.getTotalSelected()}))){this.setAssetsBusy();var a={};for(var b=0;b<this.selectedFileIds.length;b++){a["fileId["+b+"]"]=this.selectedFileIds[b]}Craft.postActionRequest("assets/deleteFile",a,$.proxy(function(c,d){this.setAssetsAvailable();if(d=="success"){if(c.error){alert(c.error)}this.updateFiles()}},this))}},_showProperties:function(a){this.setAssetsBusy();var c=this._getDataContainer(a);var b={requestId:++this.requestId,fileId:c.attr("data-id")};Craft.postActionRequest("assets/viewFile",b,$.proxy(function(d,e){if(d.requestId!=this.requestId){return}this.setAssetsAvailable();$modalContainerDiv=this.$modalContainerDiv;if($modalContainerDiv==null){$modalContainerDiv=$('<div class="modal"></div>').addClass().appendTo(Garnish.$bod)}if(this.modal==null){this.modal=new Garnish.Modal()}$modalContainerDiv.empty().append(d.headHtml);$modalContainerDiv.append(d.bodyHtml);$modalContainerDiv.append(d.footHtml);this.modal.setContainer($modalContainerDiv);this.modal.show();this.modal.addListener(Garnish.Modal.$shade,"click",function(){this.hide()});this.modal.addListener(this.modal.$container.find(".btn.cancel"),"click",function(){this.hide()});this.modal.addListener(this.modal.$container.find(".btn.submit"),"click",function(){this.removeListener(Garnish.Modal.$shade,"click");var f=$("form#file-fields").serialize();Craft.postActionRequest("assets/saveFileContent",f,$.proxy(function(g,h){this.hide()},this))})},this))},_getDataContainer:function(a){if(typeof a.currentTarget!="undefined"){target=a.currentTarget}if(this.currentState.view=="thumbs"){return $(target).is("li")?$(target):$(target).parents("li")}else{return $(target).is("tr")?$(target):$(target).parents("tr")}},_collapseExtraExpandedFolders:function(a){clearTimeout(this.expandDropTargetFolderTimeout);for(var b=this.tempExpandedFolders.length-1;b>=0;b--){var c=this.tempExpandedFolders[b];if(!a||!c.isParent(a)){c.collapse();this.tempExpandedFolders.splice(b,1)}}},_onFileSelectionChange:function(){if(this.settings.mode=="full"){var b=0;if(this.fileSelect.getTotalSelected()==1){for(b=0;b<this._singleFileMenu.length;b++){this._singleFileMenu[b].enable();this._multiFileMenu[b].disable()}}else{if(this.fileSelect.getTotalSelected()>1){for(b=0;b<this._singleFileMenu.length;b++){this._singleFileMenu[b].disable();this._multiFileMenu[b].enable()}}}}this.selectedFileIds=[];var a=this.fileSelect.getSelectedItems();for(var b=0;b<a.length;b++){this.selectedFileIds.push($(a[b]).attr("data-id"))}if(typeof this.settings.onSelectionChange=="function"){this.settings.onSelectionChange()}},getCurrentFolderId:function(){if(this.currentState.currentFolder==null||this.currentState.currentFolder==0||typeof this.currentState.currentFolder=="undefined"){var a=this.$folderContainer.attr("data-id");if(a==null||typeof a=="undefined"){a=this.$folders.find("a[data-id]").attr("data-id")}this.storeState("currentFolder",a)}return this.currentState.currentFolder},_setUploadFolder:function(a){this.uploader.setParams({folderId:a})},_setStatus:function(a){this.$status.html(a)},_setUploadStatus:function(){this._setStatus("")},_onUploadSubmit:function(b,a){if(!this.uploader.getInProgress()){this.setAssetsBusy();this._initProgressBar();this._uploadFileProgress={};this._uploadTotalFiles=1;this._uploadedFiles=0}else{this._uploadTotalFiles++}this._uploadFileProgress[b]=0;this._setUploadStatus()},_onUploadProgress:function(d,c,a,b){this._uploadFileProgress[d]=a/b;this._updateProgressBar()},_onUploadComplete:function(c,b,a){this._uploadFileProgress[c]=1;this._updateProgressBar();if(a.success||a.prompt){this._uploadedFiles++;if(this.settings.multiSelect||!this.selectedFileIds.length){this.selectedFileIds.push(a.fileId)}this._setUploadStatus();if(a.prompt){this.promptArray.push(a)}}if(!this.uploader.getInProgress()){if(this._uploadedFiles){this.setAssetsAvailable();this._hideProgressBar();if(this.promptArray.length){this._showBatchPrompts(this.promptArray,this._uploadFollowup)}else{this.loadFolderContents()}}else{this._hideProgressBar();this.setAssetsAvailable()}}},_uploadFollowup:function(b){this.setAssetsBusy();this._initProgressBar();this.promptArray=[];var c=$.proxy(function(){this.setAssetsAvailable();this._hideProgressBar();this.loadFolderContents()},this);var a=$.proxy(function(f,d,g){var e={additionalInfo:f[d].additionalInfo,fileName:f[d].fileName,userResponse:f[d].choice};Craft.postActionRequest("assets/uploadFile",e,$.proxy(function(i){d++;var h=Math.min(100,Math.round(100*d/f.length))+"%";if(d==f.length){g()}else{a(f,d,g)}},this))},this);a(b,0,c)},_updateProgressBar:function(){var a=0;for(var c in this._uploadFileProgress){a+=this._uploadFileProgress[c]}var b=Math.round(100*a/this._uploadTotalFiles)+"%";this.$uploadProgressBar.width(b)},_hideProgressBar:function(){this.$uploadProgress.fadeTo("fast",0.01,$.proxy(function(){this.$uploadProgress.addClass("hidden").fadeTo(1,1,function(){})},this))},_initProgressBar:function(){this.$uploadProgressBar.width("0%");this.$uploadProgress.removeClass("hidden")},_showPrompt:function(d,f,e,a){this._promptCallback=e;if(this.modal==null){this.modal=new Garnish.Modal()}if(this.$modalContainerDiv==null){this.$modalContainerDiv=$('<div class="modal"></div>').addClass().appendTo(Garnish.$bod)}this.$prompt=$('<div class="body"></div>').appendTo(this.$modalContainerDiv.empty());this.$promptMessage=$('<p class="assets-prompt-msg"/>').appendTo(this.$prompt);$("<p>").html(Craft.t("What do you want to do?")).appendTo(this.$prompt);this.$promptApplyToRemainingContainer=$('<label class="assets-applytoremaining"/>').appendTo(this.$prompt).hide();this.$promptApplyToRemainingCheckbox=$('<input type="checkbox"/>').appendTo(this.$promptApplyToRemainingContainer);this.$promptApplyToRemainingLabel=$("<span/>").appendTo(this.$promptApplyToRemainingContainer);this.$promptButtons=$('<div class="buttons"/>').appendTo(this.$prompt);this.modal.setContainer(this.$modalContainerDiv);this.$promptMessage.html(d);for(var b=0;b<f.length;b++){var c=$('<div class="assets-btn btn" data-choice="'+f[b].value+'">'+f[b].title+"</div>");this.addListener(c,"activate",function(i){var h=i.currentTarget.getAttribute("data-choice"),g=this.$promptApplyToRemainingCheckbox.prop("checked");this._selectPromptChoice(h,g)});this.$promptButtons.append(c).append("<br />")}if(a){this.$promptApplyToRemainingContainer.show();this.$promptApplyToRemainingLabel.html(" "+Craft.t("Apply this to the {number} remaining conflicts",{number:a}))}this.modal.show();this.modal.removeListener(Garnish.Modal.$shade,"click");this.addListener(Garnish.Modal.$shade,"click","_cancelPrompt")},_selectPromptChoice:function(b,a){this.$prompt.fadeOut("fast",$.proxy(function(){this.modal.hide();this._promptCallback(b,a)},this))},_cancelPrompt:function(){this._selectPromptChoice("cancel",true)},_showBatchPrompts:function(a,b){this._promptBatchData=a;this._promptBatchCallback=b;this._promptBatchReturnData=[];this._promptBatchNum=0;this._showNextPromptInBatch()},_showNextPromptInBatch:function(){var a=this._promptBatchData[this._promptBatchNum].prompt,b=this._promptBatchData.length-(this._promptBatchNum+1);this._showPrompt(a.message,a.choices,$.proxy(this,"_handleBatchPromptSelection"),b)},_handleBatchPromptSelection:function(d,b){var a=this._promptBatchData[this._promptBatchNum],c=this._promptBatchData.length-(this._promptBatchNum+1);this._promptBatchReturnData.push({fileName:a.fileName,choice:d,additionalInfo:a.additionalInfo});if(c){this._promptBatchNum++;if(b){this._handleBatchPromptSelection(d,true)}else{this._showNextPromptInBatch()}}else{if(typeof this._promptBatchCallback=="function"){this._promptBatchCallback(this._promptBatchReturnData)}}},setAssetsBusy:function(){this.$spinner.removeClass("hidden")},setAssetsAvailable:function(){this.$spinner.addClass("hidden")},isAssetsAvailable:function(){return this.$spinner.hasClass("hidden")}},{defaults:{mode:"full",multiSelect:true,kinds:"any",disabledFiles:[],namespace:"panel"}});