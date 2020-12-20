document.addEventListener('DOMContentLoaded', function() {
	document.querySelector('#quickCart').onfocus = function() {
		let 	form_data = 
			'cart_mode=add'+'&'+
			encodeURIComponent(cart_key)+'=1';
		let 	start  = $('#quickCartStart'),
			answer = $('#quickCartAnswer'),
			loader = $('#quickCartLoader'),
			button1 = $('#quickCartSubmit'),
			button2 = $('#quickCartContinue');

		let jqxhr = $.ajax({
			url: '/popup-win/post_cart.php',
			type: 'POST',
			data: form_data,
			beforeSend: function() {
				start.html(cart_add);
				loader.fadeIn();
			},
			success: function(result, textStatus, request) {
				if (result.substr(0,6) == 'CartOk') {
					$('#quickCartTitle').text('Состав корзины');
					start.hide();
					loader.fadeOut(300, function() {
						answer.html(result.substr(6));
						button1.prop('href', '/Netshop/Cart/');
						button1.show();
						button2.prop('href', (!cart_uri ? document.location.href : cart_uri));
						button2.show();
					});
				} else {
					loader.fadeOut(300, function() {
						answer.html('<b>Непонятный ответ сервера:</b><br>'+result);
					});
				}				
			},
			error: function(request, textStatus, errorThrown) {
				loader.fadeOut(300, function() {
					answer.html('<b>Произошла ошибка!!! Попробуйте позже.</b>');
				});
			}
		});
	};
	document.querySelector('#quickCart').onblur = function() {
		document.location.href = (!cart_uri ? document.location.href : cart_uri);
	};
});