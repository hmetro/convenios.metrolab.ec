<?php

/*
 * This file is part of the Ocrend Framewok 3 package.
 *
 * (c) Ocrend Software <info@ocrend.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use app\models as Model;

/**
 * Inicio de sesión
 *
 * @return json
 */
$app->post('/login', function () use ($app) {

    global $http;
    # si existe la accion para reeenviar verficacion por correo electronico
    if (!is_null($http->headers->get('X-REQUEST-V'))) {
        $u = new Model\Users;
        return $app->json($u->verifySendMail());
    } else {
        $u = new Model\Users;
        return $app->json($u->login());
    }

});

/**
 * Registro de un usuario
 *
 * @return json
 */
$app->post('/registro', function () use ($app) {
    $u = new Model\Users;
    return $app->json($u->register());
});

/**
 * Recuperar contraseña perdida
 *
 * @return json
 */
$app->post('/lostpass', function () use ($app) {
    $u = new Model\Users;

    return $app->json($u->lostpass());
});

/**
 * opciones de perfil
 *
 * @return json
 */
$app->post('/perfil', function () use ($app) {

    global $http;

    $track = $http->headers->get('X-REQUEST-TRACK');

    # si existe la accion para reeenviar verficacion por correo electronico
    if (!is_null($track)) {

        $u = new Model\Users;
        switch ($track) {

            case 'edit':
                return $app->json($u->editarUsuario());
                break;

            case 'new':
                return $app->json($u->register_back());
                break;

            case 'change-pass':
                return $app->json($u->cambiarContraseña());
                break;

            case 'del':
                return $app->json($u->eliminarUsuario());
                break;

            default:
                return $app->json(array());
                break;
        }

    } else {
        return $app->json(array());
    }

});

/**
 * opciones de perfil
 *
 * @return json
 */
$app->post('/convenio', function () use ($app) {

    global $http;

    $track = $http->headers->get('X-REQUEST-TRACK');

    # si existe la accion para reeenviar verficacion por correo electronico
    if (!is_null($track)) {

        $u = new Model\Configuracion;
        switch ($track) {

            case 'edit':
                return $app->json($u->editarConvenio());
                break;

            default:
                return $app->json(array());
                break;
        }

    } else {
        return $app->json(array());
    }

});

# send mail
$app->post('/laboratorio/enviar-resultado', function () use ($app) {
    global $http;
    $m = new Model\Laboratorio;
    return $app->json($m->sedmailLab());
});
