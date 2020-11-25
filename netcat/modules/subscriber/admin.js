/* $Id: admin.js 8300 2012-10-29 14:42:06Z vadim $ */

nc_subscriber = function () {
    this.xhr = null;
    this.ajax();
  
    this.site = 0;
    this.sub = 0;
    this.cc = 0;

    instance = this;
}

nc_subscriber.prototype = {
    // for AJAX
    ajax: function () {
        this.xhr = null;
    
        try {
            this.xhr = new XMLHttpRequest();
        }
        catch(e) {
            // Mozilla, IE7
            try {
                this.xhr = new ActiveXObject("Msxml2.XMLHTTP");
            }
            catch(e) {
                // Old IE
                try {
                    this.xhr = new ActiveXObject("Microsoft.XMLHTTP");
                }
                catch(e) {
                    return false;
                }
            }
        }

        return true;
    },
  
    change_type : function  () {
        var sc_type = document.getElementById('Type');
        var type = sc_type.options[sc_type.selectedIndex].value;
        if(  type == 1 ) {
            document.getElementById('div_type_1').style.display = 'block';
            document.getElementById('div_type_23').style.display = 'none';
        }
        else {
            document.getElementById('div_type_1').style.display = 'none';
            document.getElementById('div_type_23').style.display = 'block';
        }

        return;
    },
  
    change_site : function  () {
        var list = document.getElementById('site_list');
    
        this.site = list.options[list.selectedIndex].value;
        this.sub = 0;
        this.cc = 0;
        this.init();
    
        return;
    },

    change_sub : function  () {
        var list = document.getElementById('sub_list');
    
        this.sub = list.options[list.selectedIndex].value;
        this.cc = 0;
        this.init();
 
        return ;
    },
  
    set_sub : function ( sub ) {
        this.sub = sub;
    },
  
    set_site : function ( site ) {
        this.site = site;
    },
  
    set_cc : function ( cc ){
        this.cc = cc;
    },
  
    init : function () {
        var i, response;

        response = jQuery.ajax({
            url: "../../admin/user/index.php?phase=20",
            type: "POST",
            data: 'getsublist_cc='+this.site,
            async: false
        }).responseText;

        var div_sc = document.getElementById('div_sub_list');
        div_sc.innerHTML = "<select name='sub_list' id='sub_list' style='width: 100%' onchange='nc_subs.change_sub(); return false;'>"
        +response+
        "</select>";
    
        var list = document.getElementById('sub_list');
        if ( !this.sub ) {
            this.sub = list.options[0].value;
        }
    
    
        for ( i = 0; i < list.options.length; i++) {
            if ( list.options[i].value == this.sub ) {
                list.selectedIndex = i;
                break;
            }
        }

        response = jQuery.ajax({
            url: "../../admin/user/index.php?phase=20",
            type: "POST",
            data: 'getsubclasslist='+this.sub,
            async: false
        }).responseText;
    
        div_sc = document.getElementById('div_subclass_list');
        if ( response ) {
            div_sc.innerHTML = "<select name='Sub_Class_ID' style='width: 100%' id='subclass_list'>"+response+"</select>";

            list = document.getElementById('subclass_list');

            if ( !this.cc ) {
                this.cc = list.options[0].value;
            }

            for ( i = 0; i < list.options.length; i++) {
                if ( list.options[i].value == this.cc ) {
                    list.selectedIndex = i;
                    break;
                }
            }
        }
        else {
            div_sc.innerHTML = "<div>" + none_cc_text + "</div>";
        }

        return 0;
    }
}
