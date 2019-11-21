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
use Ocrend\Kernel\Helpers as Helper;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

$app->get('/', function () use ($app) {
    global $http;
    $temp = Helper\Strings::ocrend_decode($http->headers->get('X-REQUEST-T'), 'temp');
    $html = file_get_contents('../' . $temp);
    return $app->json(array($html));
});

$app->get('/modulos', function () use ($app) {
    $m = new Model\Users;
    return $app->json($m->getModulos());
});

$app->get('/usuarios', function () use ($app) {
    $m = new Model\Users;
    return $app->json($m->getUsers());
});

$app->get('/usuario', function () use ($app) {
    global $http;
    $m = new Model\Users;
    return $app->json($m->getUserById());
});

$app->get('/laboratorio/resultados', function () use ($app) {
    $m = new Model\Laboratorio;
    return $app->json($m->getResultadosLab());
});

$app->get('/laboratorio/resultado', function () use ($app) {
    global $http;
    $m = new Model\Laboratorio;
    return $app->json($m->getResultadosLabById(
        $http->headers->get('X-REQUEST-ID-DOCUMENTO'),
        $http->headers->get('X-REQUEST-FECHA-DOCUMENTO')
    ));
});

/**
 * DESPLIGUE DE DOCUMENTOS RESULTADOS DE LABORATORIO
 *
 * @return json
 */

$app->get('/documentos/resultados/{filename}', function ($filename = 'dummy.pdf') use ($app) {

    global $config;

    $file = '../assets/descargas/' . $filename;

    $doc = file_exists($file);
    # si eiste documento renderizar elemtno
    if ($doc) {
        return new BinaryFileResponse($file);
    } else {

        $file = '../assets/descargas/dummy.pdf';

        return new BinaryFileResponse($file);

        // hrow new ModelsException('No existe el documento solicitado. => ' . $file);
    }

});

/**
 * opciones de perfil
 *
 * @return json
 */
$app->get('/convenio', function () use ($app) {

    global $http;

    $track = $http->headers->get('X-REQUEST-TRACK');

    # si existe la accion para reeenviar verficacion por correo electronico
    if (!is_null($track)) {

        $u = new Model\Configuracion;
        switch ($track) {

            case 'view':
                return $app->json($u->getConvenio($http->headers->get('X-REQUEST-ID-CV'), '*'));
                break;

            default:
                return $app->json(array());
                break;
        }

    } else {
        return $app->json(array());
    }

});
