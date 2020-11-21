function checkFilter() {
	//alert("check!");
	// check price 
	var start_price = this.document.frm_filter.elements["srchPat[10]"].value;
	var stop_price = this.document.frm_filter.elements["srchPat[11]"].value;
	var intRegex = /^\d+$/;
	var floatRegex = /^((\d+(\.\d *)?)|((\d*\.)?\d+))$/;

	if (start_price!="") {
		if(!(intRegex.test(start_price)) || !( floatRegex.test(start_price))) { 
			this.document.frm_filter.elements["srchPat[10]"].style.backgroundColor="LightPink";
			//alert('Неверные параметры поиска!');
			return false;
		
		}
	} else {
		this.document.frm_filter.elements["srchPat[10]"].value=0;
	}
	if (stop_price!="") {
		if (!(intRegex.test(stop_price)) || !( floatRegex.test(stop_price))) {
			this.document.frm_filter.elements["srchPat[11]"].style.backgroundColor="LightPink";
			//alert('Неверные параметры поиска!');
			return false;
		}
	}
	
	return true;
}