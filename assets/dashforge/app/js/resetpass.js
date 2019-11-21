function _ini_resetpass() {
    $('#response').removeClass('alert-danger');
    $('#response').removeClass('alert-warning');
    $('#response').addClass('alert-warning');
    $("#response").html('Procesando...');
    $('#response').removeClass('d-none');
    $('#resetpass').attr('disabled', true);
    $.ajax({
        type: "POST",
        url: "api/lostpass",
        data: $('#resetpass_mx').serialize(),
        success: function(json) {
            if (json.success == 1) {
                $('#response').html(json.message).css('font-weight', 'bold');
                $("#response").removeClass('alert-warning');
                $("#response").addClass('alert-success');
                $('#resetpass').removeAttr('disabled');
                $('#resetpass').html('Recuperar Contraseña');
                $('#resetpass_mx').trigger("reset");
            } else {
                $('#response').html(json.message).css('font-weight', 'bold');
                $("#response").removeClass('alert-warning');
                $("#response").addClass('alert-danger');
                $('#resetpass').removeAttr('disabled');
                $('#resetpass').html('Recuperar Contraseña');
            }
        },
        error: function() {
            window.alert('#Request Error!');
        }
    });
};
if (document.getElementById('resetpass')) {
    document.getElementById('resetpass').onclick = function(e) {
        e.preventDefault();
        _ini_resetpass();
    };
}
if (document.getElementById('resetpass_mx')) {
    document.getElementById('resetpass_mx').onkeypress = function(e) {
        if (!e) e = window.event;
        var keyCode = e.keyCode || e.which;
        if (keyCode == '13') {
            _ini_resetpass();
            return false;
        }
    };
}