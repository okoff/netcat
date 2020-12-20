//jQuery(document).ready(function($) {
document.addEventListener('DOMContentLoaded', function() {

    	// Обработка маски для поля с номером телефона
	$('#phone').on('keypress', function(e) {
		e = e || window.event;
		var target = e.target || e.srcElement;
		var isIE = document.all;
		if (target.tagName.toUpperCase()=='INPUT') {
			let char = null;
			let code = isIE ? e.keyCode : e.which;
			if (code < 32 || e.ctrlKey || e.altKey) return true;
			char=String.fromCharCode(code);
			if ('0123456789'.indexOf(char) == -1) return false;

			let	t = this.getAttribute('template'),
				p = this.placeholder,
				c = this.selectionStart
				r = null;

			if (!this.value) {
				this.value = p;
				c = 4;
			}

			for (let s = c; s < t.length; s++) {
				if (t[s] == '9') {
					if (!r) {
						r = s;
						this.value = this.value.slice(0,s) + char +  this.value.slice(s+1)
						this.setRangeText(char, r, r+1, 'end'); 
					} else {
						this.selectionStart = s;
						this.selectionEnd = s;
		  				return false;
					}
				}
		    	}
		}	
		return true;
	});

	$('#phone').on('input', function(e) {
		let	n = this.value,
			f = n.replace(/[^_\d]+/g,'');
			if (!f) {
				this.value = null;
				return true;
			}
		
		let	t = this.getAttribute('template'),
			p = this.placeholder,
			c = this.selectionStart,
			fp = 0,
			fv = '';
	
		for (let s = 0; s < t.length; s++) {
			if (t[s] == '9' && fp < f.length) {
				if (fp == 0 && (f[fp] == '8' || f[fp] == '_')) {
					fv += '7';
				} else {
					fv += f[fp];
				}
				fp++;
			} else {
				fv += p[s];
			}
		}
		if (fv != n) {
			this.value = fv;
			this.selectionStart = c; // + this.shiftpos;
			this.selectionEnd = c;
		}
		return true;
	});	
    
	// Проверяет отмечен ли чекбокс согласия
    // с обработкой персональных данных
    $('#check').on('click', function() {
        if ($("#check").prop("checked")) {
            $('#quickOrderSubmit').attr('disabled', false);
        } else {
            $('#quickOrderSubmit').attr('disabled', true);
        }
    });

    // Отправляет данные из формы на сервер и получает ответ
    $('#quickOrderForm').on('submit', function(event) {
        
        event.preventDefault();

        var form   = $('#quickOrderForm'),
            button = $('#quickOrderSubmit'),
            answer = $('#quickOrderAnswer'),
            loader = $('#quickOrderLoader'),
			frmfields = $('#quickOrderfrmfields');

        var jqxhr = $.ajax({
            url: '/popup-win/handler.php',
            type: 'POST',
            data: form.serialize(),
            beforeSend: function() {
				answer.text('Отправка запроса...');
                button.attr('disabled', true).css('margin-bottom', '20px');
                loader.fadeIn();
            },
            success: function(result, textStatus, request) {
				if (result.substr(0,3) == 'OK!') {
					loader.fadeOut(300, function() {
						frmfields.empty();
						answer.text(result.substr(3));
					});
					form.find('.form-control').val(' ');
					button.attr('disabled', true);
				} else {
					loader.fadeOut(300, function() {
						answer.text(result);
					});
					button.attr('disabled', false);			
				}				
            },
            error: function(request, textStatus, errorThrown) {
                loader.fadeOut(300, function() {
                    answer.text('Произошла ошибка!!! Попробуйте позже.');
                });
                button.attr('disabled', false);
            }
        });
    });
});