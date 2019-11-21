<?php

/*
 * This file is part of the Ocrend Framewok 3 package.
 *
 * (c) Ocrend Software <info@ocrend.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace app\controllers;

use app\models as Model;
use Ocrend\Kernel\Controllers\Controllers;
use Ocrend\Kernel\Controllers\IControllers;
use Ocrend\Kernel\Router\IRouter;

/**
 * Controlador recuperar20contraseñaController/
 *
 * @author Ocrend Software C.A <bnarvaez@ocrend.com>
 */
class recuperar20contraseñaController extends Controllers implements IControllers
{

    public function __construct(IRouter $router)
    {
        parent::__construct($router, array(
            'users_not_logged' => true,
        ));

        global $http;

        if (!is_null($http->query->get('token'))) {
            $http->query->set('token', $http->query->get('token'));
            $m = new Model\Users();
            $m->changeTemporalPass();
        }

        $this->template->display('resetpass/resetpass');

    }
}
