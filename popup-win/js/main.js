jQuery(document).ready(function($) {

    // ��������� ����� ��� ���� � ������ ��������
    $('#phone').mask('+7 (999) 999-99-99 ');

    // ��������� ������� �� ������� ��������
    // � ���������� ������������ ������
    $('#check').on('click', function() {
        if ($("#check").prop("checked")) {
            $('#button').attr('disabled', false);
        } else {
            $('#button').attr('disabled', true);
        }
    });

    // ���������� ������ �� ����� �� ������ � �������� �����
    $('#contactForm').on('submit', function(event) {
        
        event.preventDefault();

        var form = $('#contactForm'),
            button = $('#button'),
            answer = $('#answer'),
            loader = $('#loader'),
			frmfields = $('#frmfields');

        $.ajax({
            url: '/popup-win/handler.php',
            type: 'POST',
            data: form.serialize(),
            beforeSend: function() {
                answer.empty();
                button.attr('disabled', true).css('margin-bottom', '20px');
                loader.fadeIn();
            },
            success: function(result) {
                loader.fadeOut(300, function() {
					frmfields.empty();
					answer.text(result);
                });
                form.find('.form-control').val(' ');
                button.attr('disabled', true);
				
            },
            error: function() {
                loader.fadeOut(300, function() {
                    answer.text('��������� ������!!! ���������� �����.');
                });
                button.attr('disabled', false);
            }
        });

    });

});