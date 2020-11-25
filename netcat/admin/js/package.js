// Файл для пакетной обработки объектов 

nc_package = function () {
  this.messages = new Array();
  this.frm = '';
  this.const_not_selected = '';
}

nc_package.prototype = {
  
  // добавить новый сс для обработки
  new_cc: function ( cc_id, const_not_selected ) {
    this.messages[cc_id] = new Array();
    this.const_not_selected = const_not_selected;  
  },
  
  // вызывается при выборе\снятия выбора объекта
  select: function ( message_id, cc_id ) {
    var i, flag;
    flag = 0;
    // проход по выбранным объектам
    for ( i =0; i < this.messages[cc_id].length; i++ ) {
      if ( this.messages[cc_id][i] == message_id ) {
        // если он уже был выбран - удалим из списка выбранных
        this.messages[cc_id].splice(i, 1);
        flag = 1;
        break;
      }
    }
    // объект не был найден - добавим ее
    if ( !flag  ) this.messages[cc_id].push(message_id);
  },
  
  // обработка объектов
  process: function ( action, cc_id ) {
    var i, isEmpty = 1;
    this.frm = document.getElementById('nc_form_selected_'+cc_id);
    for ( i = 0; i < this.messages[cc_id].length; i++) {
      // в форму нужно добавить скрытые поля
      if (this.messages[cc_id][i]) {
        isEmpty = 0;
        this.frm.innerHTML += "<input type='hidden' name='message["+this.messages[cc_id][i]+"]' value='"+this.messages[cc_id][i]+"' />";
      }
    }
    
    // есть ходин один объект?
    if ( isEmpty ) {
      alert(this.const_not_selected);
    }
    else {
      switch ( action ) {
        case 'checkOn': //действие - включить 
          this.frm.innerHTML += "<input type='hidden' name='checked' value='2' />";
          this.frm.innerHTML += "<input type='hidden' name='posting' value='1' />";
          break;
        case 'checkOff': //действие - выключить 
          this.frm.innerHTML += "<input type='hidden' name='checked' value='1' />";
          this.frm.innerHTML += "<input type='hidden' name='posting' value='1' />";
          break;
        case 'delete': // действие - удалить
          this.frm.innerHTML += "<input type='hidden' name='delete' value='1' />";
          this.frm.innerHTML += "<input type='hidden' name='posting' value='0' />";
          break;  
      }
      // отправка формы
      this.frm.submit();
    }
  }
  
}


nc_package_obj = new nc_package();