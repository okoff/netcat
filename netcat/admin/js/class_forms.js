function generateForm(classID, sysTable, act, confirmation) {

	if(!classID || !act) return false;

	var values = [];
	var res, confirmText;
	var url = SUB_FOLDER + NETCAT_PATH + 'alter_form.php';
	var needTextArea = document.getElementById(act);

  if (window.frames["frame_"+act] && editAreas[act]["displayed"]==true){
    needTextArea.value = window.frames["frame_"+act].editArea.textarea.value;
  }
	// если поле не пустое - вызываем диалог
	if(needTextArea.value && !confirmation) {
				
		switch(act) {
			// альтернативные формы
			case "AddTemplate":
				confirmText = CONTROL_CLASS_CLASS_FORMS_ADDFORM_GEN_WARN;
			break;    
			case "EditTemplate":
				confirmText = CONTROL_CLASS_CLASS_FORMS_EDITFORM_GEN_WARN;
			break;
			case "FullSearchTemplate":
				confirmText = CONTROL_CLASS_CLASS_FORMS_QSEARCH_GEN_WARN;
			break;
			case "SearchTemplate":
				confirmText = CONTROL_CLASS_CLASS_FORMS_SEARCH_GEN_WARN;
			break;
			// условия альтернативных форм
			case "AddCond":
				confirmText = CONTROL_CLASS_CLASS_FORMS_ADDCOND_GEN_WARN;
			break;
			case "EditCond":
				confirmText = CONTROL_CLASS_CLASS_FORMS_EDITCOND_GEN_WARN;
			break;
			// действия альтернативных форм
			case "AddActionTemplate":
				confirmText = CONTROL_CLASS_CLASS_FORMS_ADDACTION_GEN_WARN;
			break;
			case "EditActionTemplate":
				confirmText = CONTROL_CLASS_CLASS_FORMS_EDITACTION_GEN_WARN;
			break;
			case "CheckActionTemplate":
				confirmText = CONTROL_CLASS_CLASS_FORMS_CHECKACTION_GEN_WARN;
			break;
			case "DeleteTemplate":
				confirmText = CONTROL_CLASS_CLASS_FORMS_ADDFORM_GEN_WARN;
			break;
			case "DeleteActionTemplate":
				confirmText = CONTROL_CLASS_CLASS_FORMS_DELETEACTION_GEN_WARN;
			break;
			default:
				confirmText = "Replace content in this field?";
		}
		
		var dlgValue = confirm(confirmText);
		
		if(dlgValue) {
			generateForm(classID, sysTable, act, 1);
		}
		return false;
	}
	
	// предупредить сервер, что данные переданы через Ajax в кодировке utf8
	values["NC_HTTP_REQUEST"] = 1; 

	// инициализируем
	var xhr = new httpRequest();
		
	req = xhr.request('POST', url, {'classID':classID, 'act':act, 'systemTableID':sysTable});

	res = xhr.getResponseText();
	
	needTextArea.value = res;

  if (window.frames["frame_"+act] && editAreas[act]["displayed"]==true) {
    window.frames["frame_"+act].editArea.textarea.value = res;
    //EAL.setValue(act,res);
    //window.frames["frame_"+ act].editArea.check_line_selection(false);
		//window.frames["frame_"+ act].editArea.execCommand("onchange");
    window.frames["frame_"+ act].editArea.execCommand("resync_highlight");

  }
  
}