function _ini_send() {
    $('#response').removeClass('alert-danger');
    $('#response').removeClass('alert-warning');
    $('#response').addClass('alert-warning');
    $("#response").html('Procesando...');
    $('#response').removeClass('d-none');
    $('#send').attr('disabled', true);
    $.ajax({
        type: "POST",
        url: "api/login",
        headers: {
            'X-REQUEST-V': $('#send').attr('data-token'),
        },
        data: $('#send_mx').serialize(),
        success: function(json) {
            if (json.success) {
                $('#response').html(json.message).css('font-weight', 'bold');
                $("#response").removeClass('alert-warning');
                $("#response").addClass('alert-success');
                $('#send').removeAttr('disabled');
            } else {
                $('#response').html(json.message).css('font-weight', 'bold');
                $("#response").removeClass('alert-warning');
                $("#response").addClass('alert-danger');
                $('#send').removeAttr('disabled');
            }
        },
        error: function() {
            window.alert('#Request Error!');
        }
    });
};
if (document.getElementById('send')) {
    document.getElementById('send').onclick = function(e) {
        e.preventDefault();
        _ini_send();
    };
}
if (document.getElementById('send_mx')) {
    document.getElementById('send_mx').onkeypress = function(e) {
        if (!e) e = window.event;
        var keyCode = e.keyCode || e.which;
        if (keyCode == '13') {
            _ini_send();
            return false;
        }
    };
}