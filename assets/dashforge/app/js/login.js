function _ini_login() {
    var error_icon = '',
        success_icon = '<span class="glyphicon glyphicon-ok" aria-hidden="true"></span> ',
        process_icon = '<span class="fa fa-spinner fa-spin" aria-hidden="true"></span> ';
    $('#response').removeClass('alert-danger');
    $('#response').removeClass('alert-warning');
    $('#response').addClass('alert-warning');
    $("#response").html('Iniciando sesión, por favor espere...');
    $('#response').removeClass('d-none');
    $('#login').html('Conectando...');
    $('#login').attr('disabled', true);
    $.ajax({
        type: "POST",
        url: "api/login",
        data: $('#login_mx').serialize(),
        success: function(json) {
            if (json.success == 1) {
                $('#response').html(json.message).css('font-weight', 'bold');
                $("#response").removeClass('alert-warning');
                $("#response").addClass('alert-success');
                $('#login').removeAttr('disabled');
                $('#login').html('Bienvenido');
                var redirect = getGET();
                if (redirect) {
                    // redireccionar si es existe variable redirect en httt url
                    window.location = redirect['redirect'];
                } else {
                    // no se ha recibido ningun parametro por GET
                    if (!(json.url == null)) {
                        setTimeout(function() {
                            window.location = json.url;
                        }, 2000);
                    } else {
                        window.location.reload();
                    }
                }
            } else {
                $('#response').html(json.message).css('font-weight', 'bold');
                $("#response").removeClass('alert-warning');
                $("#response").addClass('alert-danger');
                $('#login').removeAttr('disabled');
                $('#login').html('Entrar');
                if (!(json.url == null)) {
                    setTimeout(function() {
                        window.location = json.url;
                    }, 2000);
                }
            }
        },
        error: function() {
            window.alert('#Request Error!');
        }
    });
};
if (document.getElementById('login')) {
    document.getElementById('login').onclick = function(e) {
        e.preventDefault();
        _ini_login();
    };
}
if (document.getElementById('login_mx')) {
    document.getElementById('login_mx').onkeypress = function(e) {
        if (!e) e = window.event;
        var keyCode = e.keyCode || e.which;
        if (keyCode == '13') {
            _ini_login();
            return false;
        }
    };
}

function getGET() {
    // capturamos la url
    var loc = document.location.href;
    // si existe el interrogante
    if (loc.indexOf('?') > 0) {
        // cogemos la parte de la url que hay despues del interrogante
        var getString = loc.split('?')[1];
        // obtenemos un array con cada clave=valor
        var GET = getString.split('&');
        var get = {};
        // recorremos todo el array de valores
        for (var i = 0, l = GET.length; i < l; i++) {
            var tmp = GET[i].split('=');
            get[tmp[0]] = unescape(decodeURI(tmp[1]));
        }
        return get;
    }
}