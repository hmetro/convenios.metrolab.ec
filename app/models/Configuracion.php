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
use Ocrend\Kernel\Helpers as Helper;
use Ocrend\Kernel\Models\IModels;
use Ocrend\Kernel\Models\Models;
use Ocrend\Kernel\Models\ModelsException;
use Ocrend\Kernel\Models\Traits\DBModel;
use Ocrend\Kernel\Router\IRouter;

/**
 * Modelo Configuracions
 */
class Configuracion extends Models implements IModels
{
    use DBModel;

    /**
     * Máximos intentos de inincio de sesión de un usuario
     *
     * @var int
     */
    const MAX_ATTEMPTS = 8;

    /**
     * Tiempo entre máximos intentos en segundos
     *
     * @var int
     */
    const MAX_ATTEMPTS_TIME = 120; # (dos minutos)

    /**
     * Log de intentos recientes con la forma 'email' => (int) intentos
     *
     * @var array
     */
    private $recentAttempts = array();

    /**
     * ROL MINIMO PARA MANIPULACION DEL USUARIO
     *
     * @var int
     */

    const ROL_USER = 4;

    /**
     * HASH DE ENCRIPTACION
     *
     * @var int
     */

    private $hash = null;

    /**
     * url redireccion
     *
     * @var int
     */

    private $url = null;

    /**
     * url redireccion
     *
     * @var int
     */

    private $track_user = null;

    /**
     * vERIFICACIOND E PERMISOS MINIMOS
     *
     * @var int
     */

    public function setControl()
    {

        global $config;

        if (null !== $this->id_user) {

            # setear valores si el rol es mayor al permitido por la clase
            if ($this->id_user['rol'] > self::ROL_USER) {
                throw new \RuntimeException('El usuario no tiene suficientes privilegios.');
            }

            $this->hash = $config['sessions']['user_cookie']['key_encrypt'];

        } else {

            throw new \RuntimeException('El usuario no está logeado.');

        }

    }

    /**
     * Obtiene datos de un convenio por su id
     *
     * @param int $id: Id del usuario a obtener
     * @param string $select : Por defecto es *, se usa para obtener sólo los parámetros necesarios
     *
     * @return false|array con información del usuario
     */
    public function getConvenio(string $id, string $select = '*')
    {

        $this->setControl();

        $id_convenio = Helper\Strings::ocrend_decode($id, $this->hash);

        $convenios = $this->db->select($select, "convenios", null, "convenio='" . $id_convenio . "'");

        # No hay resultados
        if (false == $convenios) {
            return array('customData' => $convenios);
        }

        $filter_convenios = array();

        foreach ($convenios as $key => $val) {

            $data                = json_decode($val['data_convenio'], true);
            $data['id_convenio'] = Helper\Strings::ocrend_encode($val['id_convenio'], $this->hash);
            $filter_convenios[]  = $data;

        }

        $m = new Model\Users;

        return array(
            'convenio'   => $filter_convenios[0],
            'customData' => $m->getOwnerUser('*'),
        );
    }

    /**
     * Editar editarConvenio
     *
     * @return array : Con información de éxito/falla al editar.
     */
    public function editarConvenio()
    {
        try {

            global $config, $http;

            $this->setControl();

            # Obtener los datos $_POST
            $dir         = $http->request->get('dir');
            $tel         = $http->request->get('tel');
            $email       = $http->request->get('email');
            $isologo     = $http->files->get('isologo');
            $id_convenio = Helper\Strings::ocrend_decode($http->headers->get('X-REQUEST-ID-CV'), $this->hash);

            # Verificar que no están vacíos
            if (Helper\Functions::e($dir, $tel, $email, $isologo)) {
                throw new ModelsException('Todos los datos son necesarios');
            }

            # Formato de email
            if (!Helper\Strings::is_email($email)) {
                throw new ModelsException('El email no tiene un formato válido.');
            }

            # setear imagen
            $file = $id_convenio . '.' . $isologo->getClientOriginalExtension();

            # setear nuevos datos
            $data_convenio['dir']     = strtoupper($dir);
            $data_convenio['tel']     = strtoupper($tel);
            $data_convenio['isologo'] = 'assets/dashforge/img/convenios/isologos/' . $file;
            $data_convenio['email']   = strtolower($email);

            $isologo->move('../assets/dashforge/img/convenios/isologos/', $file);

            $update = $this->db->update('convenios', array(
                'data_convenio' => json_encode($data_convenio, JSON_UNESCAPED_UNICODE),
            ), "convenio='" . $id_convenio . "'", 1);

            return array('success' => true, 'message' => $http->files->all());

        } catch (ModelsException $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    /**
     * Editar contraseña de usuario
     *
     * @return array : Con información de éxito/falla al editar.
     */
    public function cambiarContraseña(): array
    {
        try {

            global $config, $http;

            $this->setControl();

            # Obtener los datos $_POST
            $pass    = $http->request->get('pass');
            $pass2   = $http->request->get('pass2');
            $pass3   = $http->request->get('pass3');
            $id_user = Helper\Strings::ocrend_decode($http->headers->get('X-REQUEST-ID-USER'), $this->hash);

            $owner_user = $this->getOwnerUser();

            # setear valores si el usuario es mayor a administrador y el usuario es diferente del user loggueado
            if ($owner_user['rol'] > 1 && $this->id_user != $id_user) {
                throw new ModelsException('El usuario no tiene suficientes privilegios.');
            }

            # Verificar que no están vacíos
            if (Helper\Functions::e($pass, $pass2, $pass3)) {
                throw new ModelsException('Todos los datos son necesarios');
            }

            # Veriricar contraseñas
            if ($pass2 != $pass3) {
                throw new ModelsException('Las nuevas contraseñas no coinciden.');
            }

            $user_data = $this->db->select('*', 'users', null, "id='" . $id_user . "'", 1);
            $user_data = json_decode($user_data[0]['data'], true);

            # verifica si la contraseña actual es la misma que la contraeña enviad inicialemnte para proceder actualizar
            if (!(Helper\Strings::chash($user_data['pass'], $pass))) {
                throw new ModelsException('La contraseña actual no es la correcta.');
            }

            # setear nuevos datos
            $user_data['pass'] = Helper\Strings::hash($pass2);

            $update = $this->db->update('users', array(
                'data' => json_encode($user_data, JSON_UNESCAPED_UNICODE),
            ), "id='" . $id_user . "'", 1);

            return array('success' => true, 'message' => 'Hemos procesado su petición con éxito.');

        } catch (ModelsException $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

/**
 * Reenviar verificacion de correo lectronico
 *
 * @return array : Con información de éxito/falla al registrar el usuario nuevo.
 */
    public function verifySendMail(): array
    {
        try {
            global $config, $http;

            # Obtener los datos $_POST
            $token = $http->headers->get('X-REQUEST-V');

            $token  = Helper\Strings::ocrend_decode($token, 'user');
            $_token = explode('-', $token);

            $id_user = $_token[0];

            $user_data = $this->db->select('*', 'users', null, "id='" . $id_user . "'", 1);
            $user_data = json_decode($user_data[0]['data'], true);

            $email = $user_data['email'];

            $token = Helper\Strings::ocrend_encode($id_user . '-' . time(), 'user');
            $link  = $config['build']['url'] . 'activar-cuenta?token=' . $token;

            # Construir mensaje y enviar mensaje
            $content = 'Muchas gracias por su registro. Solo falta un paso más.
                    <br />
                    <br />
                    Para activar su cuenta haga <a href="' . $link . '" target="_blank">clic aquí</a> o en el botón de activar cuenta.';

            # Enviar el correo electrónico
            $_html = Helper\Emails::loadTemplate(array(
                # Título del mensaje
                '{{title}}'     => 'Active su cuenta con nosotros. - ' . $config['build']['name'],
                # Contenido del mensaje
                '{{content}}'   => $content,
                # Url del botón
                '{{btn-href}}'  => $link,
                # Texto del boton
                '{{btn-name}}'  => 'Activar Cuenta',
                # Copyright
                '{{copyright}}' => '&copy; ' . date('Y') . ' <a href="' . $config['build']['url'] . '">' . $config['build']['name'] . '</a> Todos los derechos reservados.',
            ), 3);

            # Verificar si hubo algún problema con el envío del correo
            if ($this->sendMail($_html, $email, 'Active su cuenta con nosotros. - ' . $config['build']['name']) != 0) {
                throw new ModelsException('No se ha podido enviar el correo electrónico.');
            }

            return array('success' => true, 'message' => 'Hemos procesado su petición. Active su cuenta confirmando su dirección de correo electrónico.');

        } catch (ModelsException $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

/**
 * Realiza la acción de registro dentro del sistema
 *
 * @return array : Con información de éxito/falla al registrar el usuario nuevo.
 */
    public function register(): array
    {
        try {
            global $config, $http;

            # Obtener los datos $_POST
            $email       = $http->request->get('email');
            $pass        = $http->request->get('pass');
            $pass_repeat = $http->request->get('pass2');

            # Verificar que no están vacíos
            if (Helper\Functions::e($email, $pass, $pass_repeat)) {
                throw new ModelsException('Todos los datos son necesarios');
            }

            # Verificar email
            $this->checkEmail($email);

            # Veriricar contraseñas
            $this->checkPassMatch($pass, $pass_repeat);

            # Registrar al usuario
            $data = array(
                'user'        => $email,
                'email'       => $email,
                'pass'        => Helper\Strings::hash($pass),
                'status'      => false,
                'permissions' => array(),
                'user_data'   => array(
                    'nombres' => '',
                    'apelldos' >> '',
                ),
                'rol'         => 4, # Rol por default para usuarios nuevos rol cliente
            );

            $id_user = $this->db->insert('users', array(
                'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            ));

            $token = Helper\Strings::ocrend_encode($id_user . '-' . time(), 'user');
            $link  = $config['build']['url'] . 'activar-cuenta?token=' . $token;

            # Construir mensaje y enviar mensaje
            $content = 'Muchas gracias por su registro. Solo falta un paso más.
                    <br />
                    <br />
                    Para activar su cuenta haga <a href="' . $link . '" target="_blank">clic aquí</a> o en el botón de activar cuenta.';

            # Enviar el correo electrónico
            $_html = Helper\Emails::loadTemplate(array(
                # Título del mensaje
                '{{title}}'     => 'Active su cuenta con nosotros. - ' . $config['build']['name'],
                # Contenido del mensaje
                '{{content}}'   => $content,
                # Url del botón
                '{{btn-href}}'  => $link,
                # Texto del boton
                '{{btn-name}}'  => 'Activar Cuenta',
                # Copyright
                '{{copyright}}' => '&copy; ' . date('Y') . ' <a href="' . $config['build']['url'] . '">' . $config['build']['name'] . '</a> Todos los derechos reservados.',
            ), 3);

            # Verificar si hubo algún problema con el envío del correo
            if ($this->sendMail($_html, $email, 'Active su cuenta con nosotros. - ' . $config['build']['name']) != 0) {
                throw new ModelsException('No se ha podido enviar el correo electrónico.');
            }

            # Iniciar sesión
            /*
            $this->generateSession(array(
            'id_user' => $id_user,
            ));
             */

            return array('success' => true, 'message' => 'Registrado con éxito. Active su cuenta confirmando su dirección de correo electrónico.');

        } catch (ModelsException $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

/**
 * Realiza la acción de registro dentro del sistema
 *
 * @return array : Con información de éxito/falla al registrar el usuario nuevo.
 */
    public function register_back(): array
    {
        try {
            global $http;

            # Setear variables
            $http->request->set('user', strtolower($http->request->get('user')));

            # Obtener los datos $_POST
            $name        = $http->request->get('nomb');
            $ape         = $http->request->get('ape');
            $user        = $http->request->get('user');
            $pass        = $http->request->get('pass');
            $email       = strtolower($http->request->get('email'));
            $pass_repeat = $http->request->get('2pass');
            $rol         = ($http->request->get('rol') != null) ? $http->request->get('rol') : 4;
            $modulos     = ($http->request->get('modulos') != null) ? implode(',', $http->request->all()['modulos']) : '';

            # Verificar que no están vacíos
            if (Helper\Functions::e($name, $ape, $email, $pass, $pass_repeat, $rol, $modulos)) {
                throw new ModelsException('Todos los datos son necesarios');
            }

            # Veriricar contraseñas
            $this->checkPassMatch($pass, $pass_repeat);

            # Verificar email
            $this->checkEmail($email);

            # GENERAR ID DE USER
            $usuarios = $this->db->select('MAX(id) AS id_user', 'users');

            # id_user
            $id_user = $usuarios[0]['id_user'] + 1;

            # user box
            if ($user == '') {
                $user_box = 'box' . str_pad($id_user, 3, "0", STR_PAD_LEFT);
            } else {
                $user_box = $user;
            }

            # Dtaos de usuario
            $data = array(
                'user'        => $user_box,
                'email'       => $email,
                'rol'         => (int) $rol,
                'pass'        => Helper\Strings::hash($pass),
                'permissions' => $modulos,
                'box_type'    => false,
                'status'      => false,
                'membresia'   => false,
                'user_data'   => array(
                    'nombres'   => $name,
                    'apellidos' => $ape,
                ),
            );

            # Establecer y asigar los permisso sde los roles del sistema.
            foreach ($http->request->all()['modulos'] as $key) {
                $data[strtolower($key)] = [];
            }

            # Registrar al usuario
            $nuevo_user = $this->db->insert('users', array(
                'id'   => $id_user,
                'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            ));

            $data['id']     = $id_user;
            $data['id_box'] = $user_box;

            # Enviar correo de activacion
            $this->send_to_Active($data);

            return array(
                'success' => 1,
                'message' => 'Registrado con éxito. <br/>Hemos enviado un correo electrónico a: <b>' . $email . '</b>. Para activar la cuenta.');

        } catch (ModelsException $e) {

            return array(
                'success' => 0,
                'message' => $e->getMessage(),
                'data'    => $http->request->all(),
            );
        }
    }

/**
 * Realiza la acción de registro publico dentro del sistema
 *
 * @return array : Con información de éxito/falla al registrar el usuario nuevo.
 */
    public function register_public(): array
    {
        try {
            global $http;

            # Obtener los datos $_POST
            $terms       = $http->request->get('terms');
            $name        = $http->request->get('nombres');
            $ape         = $http->request->get('apellidos');
            $email       = strtolower($http->request->get('email'));
            $pass        = $http->request->get('pass');
            $pass_repeat = $http->request->get('2pass');

            # Verificar que no están vacíos
            if (Helper\Functions::e($name, $ape, $email, $pass, $pass_repeat, $terms)) {
                throw new ModelsException('Todos los datos son necesarios');
            }

            # Veriricar contraseñas
            $this->checkPassMatch($pass, $pass_repeat);

            # Verificar email
            $this->checkEmail($email);

            # GENERAR ID DE USER
            $usuarios = $this->db->select('MAX(id) AS id', 'users');

            # id_user
            $id_user = $usuarios[0]['id'] + 1;

            # id_user
            $user_box = 'box' . str_pad($id_user, 3, "0", STR_PAD_LEFT);

            # Dtaos de usuario publico
            $data = array(
                'id'          => $id_user,
                'user'        => $user_box,
                'email'       => $email,
                'rol'         => 4,
                'pass'        => Helper\Strings::hash($pass),
                'permissions' => "Box",
                'box'         => [],
                'box_type'    => false,
                'status'      => false,
                'membresia'   => false,
                'user_data'   => array(
                    'nombres'   => $name,
                    'apellidos' => $ape,
                ),
            );

            # Registrar al usuario
            $nuevo_user = $this->db->insert('users', array(
                'id'   => $id_user,
                'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            ));

            $data['id_box'] = $user_box;

            # Enviar correo de activacion
            $this->send_to_Active($data);

            return array(
                'success' => 1,
                'message' => 'Registrado con éxito. <br/>Hemos enviado un correo electrónico a: <b>' . $email . '</b>. Para activar tu cuenta.');

        } catch (ModelsException $e) {

            return array(
                'success' => 0,
                'message' => $e->getMessage(),
                'data'    => $http->request->all(),
            );
        }
    }

/**
 * Envía un correo electrónico al usuario que quiere recuperar la contraseña, con un token y una nueva contraseña.
 * Si el usuario no visita el enlace, el sistema no cambiará la contraseña.
 *
 * @return array<string,integer|string>
 */
    public function lostpass(): array
    {
        try {
            global $http, $config;

            # Obtener datos $_POST
            $email = $http->request->get('email');

            # Campo lleno
            if (Helper\Functions::emp($email)) {
                throw new ModelsException('El campo email debe estar lleno.');
            }

            # Filtro
            $email = $this->db->scape($email);

            # Obtener información del usuario
            $user_data = $this->db->select("id,
                JSON_UNQUOTE(data->'$.email') as email", 'users', null, " data->'$.user'='$email' or data->'$.email'='$email' ", 1);

            # Verificar correo en base de datos
            if (false === $user_data) {
                throw new ModelsException('El email no está registrado en el sistema.');
            }

            # Generar token y contraseña
            $token = md5(time());
            $pass  = uniqid();
            $token = Helper\Strings::ocrend_encode($token . '-' . $user_data[0]['id'] . '-' . time(), 'user');
            $link  = $config['build']['url'] . 'recuperar-contraseña?token=' . $token;

            # Construir mensaje y enviar mensaje
            $content = 'Se ha solicitado recuperar su contraseña perdida, si no ha realizado esta acción no necesita hacer nada.
                    <br />
                    <br />
                    Para cambiar su contraseña por <b>' . $pass . '</b> haga <a href="' . $link . '" target="_blank">clic aquí</a> o en el botón de recuperar.';

            # Enviar el correo electrónico
            $_html = Helper\Emails::loadTemplate(array(
                # Título del mensaje
                '{{title}}'     => 'Recuperar contraseña de ' . $config['build']['name'],
                # Contenido del mensaje
                '{{content}}'   => $content,
                # Url del botón
                '{{btn-href}}'  => $link,
                # Texto del boton
                '{{btn-name}}'  => 'Recuperar Contraseña',
                # Copyright
                '{{copyright}}' => '&copy; ' . date('Y') . ' <a href="' . $config['build']['url'] . '">' . $config['build']['name'] . '</a> Todos los derechos reservados.',
            ), 3);

            # Verificar si hubo algún problema con el envío del correo
            if ($this->sendMail($_html, $email, 'Recuperar contraseña de ' . $config['build']['name']) != 0) {
                throw new ModelsException('No se ha podido enviar el correo electrónico.');
            }

            # Actualizar datos
            $id_user = $user_data[0]['id'];

            $user_data = $this->db->select('*', 'users', null, "id='" . $id_user . "'", 1);

            $user_data = json_decode($user_data[0]['data'], true);

            $user_data['token']    = $token;
            $user_data['tmp_pass'] = Helper\Strings::hash($pass);

            $update = $this->db->update('users', array(
                'data' => json_encode($user_data, JSON_UNESCAPED_UNICODE),
            ), "id='" . $id_user . "'", 1);

            return array('success' => 1, 'message' => 'Se ha enviado un enlace a su correo electrónico.');

        } catch (ModelsException $e) {
            return array('success' => 0, 'message' => $e->getMessage());
        }
    }

    /**
     * Sender mail api trx
     *
     * @return void
     */
    public function sendMail($html, $to, $subject)
    {

        global $config;

        $stringData = array(
            "TextBody" => "",
            'From'     => 'Metrolab Convenios metrolab.convenios@hospitalmetropolitano.org',
            'To'       => $to,
            'Subject'  => $subject,
            'HtmlBody' => $html,
        );

        $data = json_encode($stringData);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://api.trx.icommarketing.com/email");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json',
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data),
            'X-Postmark-Server-Token: ' . $config['mailer']['user'])
        );

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            $resultobj = curl_error($ch);
        }
        curl_close($ch);
        $resultobj = json_decode($result);

        return $resultobj->ErrorCode;

    }

/**
 * Desconecta a un usuario si éste está conectado, y lo devuelve al inicio
 *
 * @return void
 */
    public function logout()
    {
        global $session, $cookie;

        $session->remove($cookie->get('session_hash') . '__user_id');
        foreach ($cookie->all() as $name => $value) {
            $cookie->remove($name);
        }

        Helper\Functions::redir();
    }

/**
 * Cambia la contraseña de un usuario en el sistema, luego de que éste haya solicitado cambiarla.
 * Luego retorna al sitio de inicio con la variable GET success=(bool)
 *
 * La URL debe tener la forma URL/lostpass?token=TOKEN&user=ID
 *
 * @return void
 */
    public function changeTemporalPass()
    {
        global $config, $http;

        # Obtener los datos $_GET
        $token = $http->query->get('token');

        $token  = Helper\Strings::ocrend_decode($token, 'user');
        $_token = explode('-', $token);

        $token   = $_token[0];
        $id_user = $_token[1];

        $success = false;

        if (!Helper\Functions::emp($token) && is_numeric($id_user) && $id_user >= 1) {

            # Filtros a los datos

            $id_user = $this->db->scape($id_user);
            $token   = $this->db->scape($token);

            # Actualizar Dtaos
            $user_data = $this->db->select('*', 'users', null, "id='" . $id_user . "'", 1);
            $user_data = json_decode($user_data[0]['data'], true);

            $user_data['pass'] = $user_data['tmp_pass'];

            unset($user_data['tmp_pass']);
            unset($user_data['token']);

            # Ejecutar el cambio
            $this->db->update('users', array(
                'data' => json_encode($user_data, JSON_UNESCAPED_UNICODE),
            ), " id='$id_user' ", 1);

            # Éxito
            $success = true;

        }

        # Devolover al sitio de inicio
        Helper\Functions::redir($config['build']['url'] . '?resetpass=' . (int) $success);
    }

/**
 * activaciond ecuenta cofirmacion de correo electronico
 *
 * La URL debe tener la forma URL/lostpass?token=TOKEN&user=ID
 *
 * @return void
 */
    public function activeAccount()
    {
        global $config, $http;

        # Obtener los datos $_GET
        $token = $http->query->get('token');

        $id_user  = Helper\Strings::ocrend_decode($token, 'user');
        $_id_user = explode('-', $id_user);
        $id_user  = $_id_user[0];

        $success = false;

        if (!Helper\Functions::emp($token) && is_numeric($id_user) && $id_user >= 1) {

            # Filtros a los datos

            $id_user = $this->db->scape($id_user);

            # Actualizar Dtaos
            $user_data = $this->db->select('*', 'users', null, "id='" . $id_user . "'", 1);
            $user_data = json_decode($user_data[0]['data'], true);

            # condicional para uado el usuario ya tiene actavada la cuenta
            if ($user_data['status']) {
                Helper\Functions::redir($config['build']['url'] . '?actived=1');
            }

            $user_data['status'] = true;

            # Ejecutar el cambio
            $this->db->update('users', array(
                'data' => json_encode($user_data, JSON_UNESCAPED_UNICODE),
            ), " id='$id_user' ", 1);

            # Éxito
            $success = true;

        }

        # Devolover al sitio de inicio
        Helper\Functions::redir($config['build']['url'] . '?actived=' . (int) $success);
    }

/**
 * Envia modulos disponibles
 *
 * @return array<string,integer|string>
 */
    public function getModulos()
    {
        try {

            global $http;

            $terms = $http->query->get('term');

            # Obtener información del usuario
            $modulos = $this->db->select('*', 'modulos', null, ' modulo LIKE "%' . $terms['term'] . '%" ');

            $_modulos = array();

            $user = $this->getOwnerUser();

            foreach ($modulos as $key) {

                if (!($this->id_user['rol'] > $key['role'])) {

                    $_modulos[] = array(
                        'id'   => $key['id'],
                        'text' => $key['modulo'],
                        'slug' => $key['modulo'],
                    );

                }

            }

            return array('results' => $_modulos);

        } catch (ModelsException $e) {

            return array('success' => 0, 'message' => $e->getMessage());
        }
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
