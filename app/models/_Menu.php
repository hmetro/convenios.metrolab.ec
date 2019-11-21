<?php

/*
 * This file is part of the Ocrend Framewok 3 package.
 *
 * (c) Ocrend Software <info@ocrend.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace app\models;

use app\models as Model;
use Ocrend\Kernel\Models\IModels;
use Ocrend\Kernel\Models\Models;
use Ocrend\Kernel\Models\Traits\DBModel;
use Ocrend\Kernel\Router\IRouter;
use Spatie\Menu\Html;
use Spatie\Menu\Menu;

/**
 * Modelo Menu => Renderiza menu segun los permisos del usuario logueado
 */
class _Menu extends Models implements IModels
{
    use DBModel;

    /**
     * PRIVATE CONTRL
     *
     * @var int
     */

    private $ctrl = null;

    /**
     * ROL ADMINISTRADOR
     *
     * @var int
     */

    const ROL_USER_ADMINISTRADOR = 1;

    /**
     * ROL GESTIONADOR
     *
     * @var int
     */

    const ROL_USER_GESTIONADOR = 2;

    /**
     * ROL MONITOREO
     *
     * @var int
     */

    const ROL_USER_MONITOREO = 3;

    /**
     * ROL CLIENTE USUARIO
     *
     * @var int
     */

    const ROL_USER_CLIENTE = 4;

    /**
     * Paginas modulo usuarios
     *
     * @var int
     */

    private $usuarios = array(
        'usuarios' => 'Ver Usuarios',
    );

    /**
     * Paginas modulo Laboratorio
     *
     * @var int
     */

    private $laboratorio = array(
        'laboratorio#!/resultados' => 'Ver Resultados de Laboratorio',
    );

    /**
     * Paginas modulo Configuracion
     *
     * @var int
     */

    private $configuracion = array(
        'configuracion#!/convenios' => 'Convenios',
        'configuracion#!/usuarios'  => 'Usuarios por Convenio',
    );

    /**
     * Paginas modulo Configuracion
     *
     * @var int
     */

    private $soporte = array(
        'soporte#!/tickets' => 'Ver Tickets',
    );

    /**
     * Get Menu para App
     *
     * @var
     */

    public function getMenu($ctrl)
    {
        global $config, $http;

        $this->ctrl = $ctrl;

        $user = new Model\Users;

        $user = $user->getOwnerUser();

        $menu = Menu::new ();

        if ('home' == $this->ctrl) {

            // Init Modulo Dashboard
            $menu->add(Html::raw('<a href="/" class="nav-link">Dashboard</a>')
                    ->addParentClass('nav-item active')
            );

        } else {

            // Init Modulo Dashboard
            $menu->add(Html::raw('<a href="/" class="nav-link">Dashboard</a>')
                    ->addParentClass('nav-item')
            );

        }

        foreach ($user['permissions'] as $key => $value) {

            if (strtolower($value) == $this->ctrl) {

                $menu->add(Html::raw('<a href="/' . strtolower($value) . '" class="nav-link">' . $value . '</a><ul class="navbar-menu-sub">
                        ' . $this->getLi(strtolower($value)) . '
                    </ul>')
                        ->addParentClass('nav-item with-sub active')
                );

            } else {

                $menu->add(Html::raw('<a href="/' . strtolower($value) . '" class="nav-link">' . $value . '</a><ul class="navbar-menu-sub">
                        ' . $this->getLi(strtolower($value)) . '
                    </ul>')
                        ->addParentClass('nav-item with-sub')
                );

            }

        }

        $menu->addClass('nav navbar-menu');

        return $menu->render();

    }

    public function getLi($modulo)
    {
        global $config;

        $li = '';

        foreach ($this->$modulo as $key => $value) {
            $li .= '<li class="nav-sub-item">
                            <a class="nav-sub-link" href="/' . strtolower($key) . '">
                               <i data-feather="calendar">
                                    </i> ' . $value . '
                            </a>
                        </li>';
        }

        return $li;

    }

    /**
     * __construct()
     */

    public function __construct(IRouter $router = null)
    {
        parent::__construct($router);
        $this->startDBConexion();
    }
}
