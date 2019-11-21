function _ini_registro() {
    $('#response').removeClass('alert-danger');
    $('#response').removeClass('alert-warning');
    $('#response').addClass('alert-warning');
    $("#response").html('Procesando...');
    $('#response').removeClass('d-none');
    $('#registro').html('Procesando...');
    $('#registro').attr('disabled', true);
    $.ajax({
        type: "POST",
        url: "api/registro",
        data: $('#registro_mx').serialize(),
        success: function(json) {
            if (json.success == 1) {
                $('#response').html(json.message).css('font-weight', 'bold');
                $("#response").removeClass('alert-warning');
                $("#response").addClass('alert-success');
                $('#registro').removeAttr('disabled');
                $('#registro').html('Crear Cuenta');
                $('#registro_mx').trigger("reset");
            } else {
                $('#response').html(json.message).css('font-weight', 'bold');
                $("#response").removeClass('alert-warning');
                $("#response").addClass('alert-danger');
                $('#registro').removeAttr('disabled');
                $('#registro').html('Crear Cuenta');
            }
        },
        error: function() {
            window.alert('#Request Error!');
        }
    });
};
if (document.getElementById('registro')) {
    document.getElementById('registro').onclick = function(e) {
        e.preventDefault();
        _ini_registro();
    };
}
if (document.getElementById('registro_mx')) {
    document.getElementById('registro_mx').onkeypress = function(e) {
        if (!e) e = window.event;
        var keyCode = e.keyCode || e.which;
        if (keyCode == '13') {
            _ini_registro();
            return false;
        }
    };
}