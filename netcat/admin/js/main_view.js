/* $Id: main_view.js 8377 2012-11-08 14:18:24Z lemonade $ */

mainView = {
    TopTitle: Array(),
    chan :0,  // 1 - есть изменения, 0 - нету
    to_tab_id: 0, // таб, на которой надо переключиться
    count: 0,
    showMainView: function() {},
    showStartScreen: function() {},

    setHeader: function(headerText) {
        document.getElementById('mainViewHeader').innerHTML = "<h1>"+headerText+"</h1>";
        this.TopTitle["headerText"] = headerText;
    },

    activeTab: null,

    /*
    addTab: function(name, caption, action, isActive, unactive) {
        if(caption == 'Система управления NetCat') {
           $nc('div.slider_block').hide();
        } else {
           $nc('div.slider_block').show();
        }
        var spacer = document.createElement('DIV');
        spacer.className = 'between_tabs';

        var tab = document.createElement('DIV');
        tab.id = 'mainViewTab_'+name;
        var liclass = '';

        if (isActive) {
            $nc('#' + tab.id + ' li').addClass('sel');
            this.activeTab = tab.id;
            this.TopTitle["activeTab"] = caption;
            this.updateTopTitle();
            var liclass = 'sel';
        }

        tab.innerHTML = "<li class='"+liclass+"'>" + caption + "</li>";
        if (!unactive) {
            $nc(tab).click(function() {
            	mainView.switchTab(this.id);
            });
        }
        tab.action = action;

        var tabContainer = document.getElementById('mainViewTabs');
        tabContainer.appendChild(tab);
        tabContainer.appendChild(spacer);
    },
    */
    
    addTab: function(name, caption, action, isActive, unactive) {
        if(caption == ncLang.NetcatCMS) {
           $nc('div.slider_block').hide();
        } else {
           $nc('div.slider_block').show();
        }
        var tabContainer = $nc('#mainViewTabs');
        
        var tab = $nc('<li id="mainViewTab_'+name+'"><span>'+caption+'</span></li>');
        
        //var spacer = document.createElement('DIV');
        //spacer.className = 'between_tabs';

        //var tab = document.createElement('DIV');
        //tab.id = 'mainViewTab_'+name;
        //var liclass = '';

        if (isActive) {
        	//$nc('#' + tab.id + ' li').addClass('sel');
            this.activeTab = tab.attr('id');
            this.TopTitle["activeTab"] = caption;
            this.updateTopTitle();
            tab.addClass('sel');
            //var liclass = 'sel';
        }

        //tab.innerHTML = "<li class='"+liclass+"'>" + caption + "</li>";
        if (!unactive) {
            tab.click(function() {
            	mainView.switchTab(this.id);
            });
        }
        tab.get(0).action = action;

        //var tabContainer = document.getElementById('mainViewTabs');
        tabContainer.append(tab);//.append('<div class="between_tabs"></div>')
        //tabContainer.appendChild(tab);
        //tabContainer.appendChild(spacer);
    },

    removeAllTabs: function() {
        document.getElementById('mainViewTabs').innerHTML = '';
        document.getElementById('mainViewToolbar').innerHTML = '';
        this.activeTab = null;
    },

    switchTab: function(tabId, dontEvalAction, dontCheck ) {
        if (tabId == 'mainViewTab_view') {
            eval(document.getElementById(tabId).action);
            return true;
        }

        to_tab_id = tabId;
        if ( !dontCheck && this.chan && REMIND_SAVE == '1' ) {
            this.checkRemindSave();
            return true;
        }
        
        if (this.activeTab) {
            if (this.activeTab == tabId) {
                if( !dontEvalAction ) { //also: call from "onlick",  but no from "update"
                    urlDispatcher.process(window.location.hash);
                }
                return true;
            }
            var oActiveTab = document.getElementById(this.activeTab);
            $nc('li#' + oActiveTab.id + '').removeClass('sel');
        }
        this.displayStar(0);
        this.chan = 0;        
                
        if (!document.getElementById(tabId)) {
            return;
        }
        
        if (null != document.getElementById(this.activeTab)) {
            document.getElementById(this.activeTab).title = '';            
        }        

        document.getElementById(tabId).title = TEXT_REFRESH;
        var oTab = document.getElementById(tabId);
        if (!oTab) return false;
        if (!dontEvalAction && oTab.action) {
            eval(oTab.action);
        }

        $nc('#' + tabId ).addClass('sel');

        this.activeTab = tabId;
        this.TopTitle["activeTab"] = oTab.firstChild.textContent;
        this.updateTopTitle();
    },

    toolbar: null, // to be created during initialization on window load
    init: function() {
        this.toolbar = new toolbar('mainViewToolbar');
        this.oStartScreen = document.getElementById('startScreen');
        this.oMainView = document.getElementById('mainView');
        this.oIframe = document.getElementById('mainViewIframe');
        this.TopTitle["NetCat"] = "NetCat";
    },

    loadIframe: function(url) {
        // FF restores frame locations on reload -- prevent it
        this.showMainView();
        var fullURL = window.location.protocol + '//' + window.location.host + url;
        this.oIframe.contentWindow.location.replace(url);
    },

    refreshIframe: function() {
        var loc = this.oIframe.contentWindow.location.href,
        newLoc = loc.replace(/([&?]rand=)[^&]+/, "$1" + Math.random());

        if (loc == newLoc) {
            newLoc += (loc.match(/\?/) ? '&' : '?') + "rand="+Math.random();
        }

        this.oIframe.contentWindow.location.href = newLoc;
    },

    submitIframeForm: function(formId) {
        //var editors = this.oIframe.contentWindow.CMEditors;
        //for(var key in editors) {
        //    editors[key].save();
        //}

        var oForm;

        if (formId) {
            oForm = document.getElementById('mainViewIframe').contentWindow.document.getElementById(formId);
        }

        if (!oForm) {
            oForm = document.getElementById('mainViewIframe').contentWindow.document.forms.item(0);
        }
        oForm.submit();
    },

    resetIframeForm: function() {
        document.getElementById('mainViewIframe').contentWindow.document.forms.item(0).reset();
    },

    /* { headerText
   *   headerImage
   *   tabs: { id
   *           caption
   *           location
   *           unactive
   *         }
   *   toolbar: [ { id
   *                caption
   *                action | location
   *                group
   *               }
   *            ]
   *   actionButtons:  [ { id
   *                       caption
   *                       action | location
   *                       align
   *                     }
   *                   ]
   *
   *   activeTab
   *   activeToolbarButtons: [ btnname1, btnname2 ]
   *
   *   locationHash
   *
   *   tabsCrc
   *   toolbarCrc
   *   actionButtonsCrc
   *
   *   treeMode
   *   treeSelectedNode
   *
   *   treeChanges { 'addNode': [ <nodeData>+ ],
   *                 'updateNode': [ <nodeData>+ ],
   *                 'deleteNode': [ nodeId+ ],
   *                 'moveNode': [ nodeId, position, refNodeId ]
   *     // both addNode and updateNode call tree.addNode()
   *
   */
    currentSettings: {
        headerText: null,
        headerImage: null,
        tabs: null,
        toolbar: null,
        actionButtions: null,
        activeTab: null,
        locationHash: null,
        tabsCrc: null,
        toolbarCrc: null,
        actionButtonsCrc: null
    },

    updateSettings: function(newSettings) {
        if (!newSettings) return false;
        var currentSettings = this.currentSettings; // 'for short'
        if (newSettings.headerText != currentSettings.headerText) {
            this.setHeader(newSettings.headerText, newSettings.headerImage);
        }

        // hash has changed
        if (newSettings.locationHash) {
            urlDispatcher.updateHash(newSettings.locationHash);
        }
        // of hash has changed

        
        if (newSettings.tabsCrc != currentSettings.tabsCrc) {

            this.removeAllTabs();
            for (var i = 0; i < newSettings.tabs.length; i++) {
                var action = "";
                if (newSettings.tabs[i].location && !newSettings.tabs[i].unactive) {
                    action = 'urlDispatcher.load("' + newSettings.tabs[i].location + '")';
                }
                if ( newSettings.tabs[i].action ) {
                    action = newSettings.tabs[i].action;
                }
                var isActive = (newSettings.activeTab == newSettings.tabs[i].id);
                this.addTab(newSettings.tabs[i].id, newSettings.tabs[i].caption, action, isActive, newSettings.tabs[i].unactive);
            }

            if (newSettings.tabs.length == 1) {
                    $nc('#tabs').hide();
            }
            
            if (null != document.getElementById('mainViewTab_' + newSettings.activeTab)) {
                document.getElementById('mainViewTab_' + newSettings.activeTab).title = TEXT_REFRESH;
            }
        } //  of tabs changed
        else {
            this.switchTab('mainViewTab_'+newSettings.activeTab, true);
        }
        
        // toolbar has been changed
        
		if (!newSettings.toolbar.length) {
			this.toolbar.makeEmptyToolbar();
			$nc('#sub_tabs').hide();
		}
		else if (this.toolbar) {
			this.toolbar.clear();
			$nc('#sub_tabs').css("display", "block");
			for (var i=0; i<newSettings.toolbar.length; i++) {
				var btn = newSettings.toolbar[i];
				if (btn.location) btn.action = 'urlDispatcher.load("' + btn.location + '")';
				this.toolbar.addButton(btn);
			}
		}
        
        // tabs changed

        if (newSettings.activeToolbarButtons.length && this.toolbar) {
            for (var i=0; i < newSettings.activeToolbarButtons.length; i++) {
                var oBtn = document.getElementById(this.toolbar.toolbarId + '_' + newSettings.activeToolbarButtons[i]);
                if (oBtn) {
                    oBtn.turnOn();
                    this.TopTitle["activeButton"] = oBtn.textContent;
                    this.updateTopTitle();
                }
            }
        }

        // ActionButtons have been changed
        if (newSettings.actionButtonsCrc != currentSettings.actionButtonsCrc) {
            var html = '';
            for (var i = 0; i < newSettings.actionButtons.length; i++) {
                var btn = newSettings.actionButtons[i];
                var style = 'save';                
                if (null != btn.style) {
                    style += ' '+btn.style;
                }
                
                var align = (btn.align == 'left') ? 'left' : 'right';
                if (btn.location) btn.action = "urlDispatcher.load('" + btn.location + "')";

                var button_hack = '';
                var lower_caption = btn.caption.toLowerCase();
                if (lower_caption.indexOf(ncLang.DropLite) != -1 || lower_caption.indexOf(ncLang.CancelLite) != -1 ) {
                    button_hack = 'border: 2px solid red; ';
                }

                html += '<div class=\"' + style + '\" style=\"' + button_hack +'float: ' + align + ';\" onclick=\"' + btn.action + '\" title = \"' + btn.caption + '\">' + btn.caption + '</div>';
            }
            var mvb = $nc('#mainViewButtons');
            mvb.html(html);
            if ( $nc('div', mvb).length == 0) {
            	mvb.parent().hide();
            	$nc('.clear_footer').hide();
            	$nc('.middle_border').css({top:'0px'});
            } else {
            	mvb.parent().show();
            	$nc('.clear_footer').show();
            	//$nc('.middle_border').css({top:'-45px'});
            }
        }

        // changes in the tree: addNode, updateNode, deleteNode
        // ANY OTHER tree METHODS CAN BE ADDED TO newSettings WITHOUT CHANGES
        // TO THIS CODE
        // drawback: must be changed in case tree methods are refactored!
        var tree;
        if (newSettings.treeChanges && (tree = document.getElementById('treeIframe').contentWindow.tree)) {
            for (var method in newSettings.treeChanges) {
                if (typeof tree[method]=='function' && newSettings.treeChanges[method].length) {
                    for (var i=0; i < newSettings.treeChanges[method].length; i++) {
                        // call method in the tree
                        tree[method](newSettings.treeChanges[method][i]);
                    }
                }
            }
        } // of if "there are tree changes and tree exists"

        // treeMode determines type of content in the tree
        if (newSettings.treeMode) {
            treeSelector.changeMode(newSettings.treeMode, newSettings.treeSelectedNode);
        }

        // SAVE SETTINGS
        this.currentSettings = newSettings;


        if (!document.all) { // reflow bugs in mozilla & opera
            triggerResize();
        }
    },

    updateTopTitle: function() {
        var title="";
        var i,value,value_bak;
        for (i in this.TopTitle) {
            value = this.TopTitle[i];
            if ( (value_bak && value_bak==value) || value=="" || value==undefined ) continue;
            title += ""+ (value_bak?" / ":"") + value + "";
            value_bak = value;
        }
        this.TopTitle["activeButton"] = "";
        top.window.document.title = title;
    }, // updateTopTitle

    displayStar: function(visible) { //Показать звездочку. 1 - есть, 0 - нету.
        if ( document.getElementById(this.activeTab+'_span') )
            document.getElementById(this.activeTab+'_span').style.display = visible ? 'inline' : 'none';
    }, //displayStar

    checkRemindSave : function () {
        if ( this.chan ) {
            bindEvent(document.getElementById('btn_rs_cancel') , 'click', function() {
                mainView.rsCancel()
                });
            bindEvent(document.getElementById('btn_rs_exit') , 'click', function() {
                mainView.rsExit()
                });
            bindEvent(document.getElementById('btn_rs_save') , 'click', function() {
                mainView.rsSave()
                });
            document.getElementById('remindSave').style.display = 'block';
        }
        else {
            this.switchTab(to_tab_id, 0, 1);
        }
    },

    rsExit : function () {
        document.getElementById('remindSave').style.display = 'none';
        this.switchTab(to_tab_id, 0, 1);
    },


    rsCancel : function () {
        document.getElementById('remindSave').style.display = 'none';
    },


    rsSave : function () {
        var oIframe = top.frames['mainViewIframe'];
        var docum = (oIframe.contentWindow || oIframe.contentDocument || oIframe.document);

        oForm = docum.forms.item(0) || docum.document.forms.item(0);

        if ( typeof oIframe.formAsyncSave == 'function' ) {
            oIframe.formAsyncSave(  oForm, {
                '*':'top.mainView.rsStatus(this.xhr)'
            });
        }
        else if ( typeof docum.formAsyncSave == 'function') {
            docum.formAsyncSave(  oForm, {
                '*':'top.mainView.rsStatus(this.xhr)'
            });
        }
        else {
            this.rsExit();
        }

        document.getElementById('remindSave').style.display = 'none';
    },

    rsStatus: function ( xhr ) {
        if ( xhr.status != "200" || xhr.readyState == 4 ) {
            this.rsExit();
        }
    }

}; // of MAINVIEW

/** INIT **/
bindEvent(window, 'load', function() {
    mainView.init()
    });