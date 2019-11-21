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
 * Modelo Users
 */
class Users extends Models implements IModels
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
     * Hace un set() a la sesión login_user_recentAttempts con el valor actualizado.
     *
     * @return void
     */
    private function updateSessionAttempts()
    {
        global $session;

        $session->set('login_user_recentAttempts', $this->recentAttempts);
    }

    /**
     * Revisa si las contraseñas son iguales
     *
     * @param string $pass : Contraseña sin encriptar
     * @param string $pass_repeat : Contraseña repetida sin encriptar
     *
     * @throws ModelsException cuando las contraseñas no coinciden
     */
    private function checkPassMatch(string $pass, string $pass_repeat)
    {
        if ($pass != $pass_repeat) {
            throw new ModelsException('Las contraseñas no coinciden.');
        }
    }

    /**
     * Verifica el email introducido, tanto el formato como su existencia en el sistema
     *
     * @param string $email: Email del usuario
     *
     * @throws ModelsException en caso de que no tenga formato válido o ya exista
     */
    private function checkEmail(string $email)
    {
        # Formato de email
        if (!Helper\Strings::is_email($email)) {
            throw new ModelsException('El email no tiene un formato válido.');
        }
        # Existencia de email
        $email = $this->db->scape($email);
        $query = $this->db->select('id', 'users', null, "JSON_UNQUOTE(data->'$.email')='$email'", 1);
        if (false !== $query) {
            throw new ModelsException('El email ingresado ya existe.');
        }
    }

    /**
     * Restaura los intentos de un usuario al iniciar sesión
     *
     * @param string $email: Email del usuario a restaurar
     *
     * @throws ModelsException cuando hay un error de lógica utilizando este método
     * @return void
     */
    private function restoreAttempts(string $email)
    {
        if (array_key_exists($email, $this->recentAttempts)) {
            $this->recentAttempts[$email]['attempts'] = 0;
            $this->recentAttempts[$email]['time']     = null;
            $this->updateSessionAttempts();
        } else {
            throw new ModelsException('Error lógico');
        }
    }

    /**
     * Genera la sesión con el id del usuario que ha iniciado
     *
     * @param array $user_data: Arreglo con información de la base de datos, del usuario
     *
     * @return void
     */
    private function generateSession(array $user_data)
    {
        global $session, $cookie, $config;

        # Generar un session hash
        $cookie->set('session_hash', md5(time()), $config['sessions']['user_cookie']['lifetime']);

        # Generar la sesión del usuario
        $session->set($cookie->get('session_hash') . '__user_id', (int) $user_data['id_user']);

        # Generar data encriptada para prolongar la sesión
        if ($config['sessions']['user_cookie']['enable']) {
            # Generar id encriptado
            $encrypt = Helper\Strings::ocrend_encode($user_data['id_user'], $config['sessions']['user_cookie']['key_encrypt']);

            # Generar cookies para prolongar la vida de la sesión
            $cookie->set('appsalt', Helper\Strings::hash($encrypt), $config['sessions']['user_cookie']['lifetime']);
            $cookie->set('appencrypt', $encrypt, $config['sessions']['user_cookie']['lifetime']);
        }
    }

    /**
     * Verifica en la base de datos, el email y contraseña ingresados por el usuario
     *
     * @param string $email: Email del usuario que intenta el login
     * @param string $pass: Contraseña sin encriptar del usuario que intenta el login
     *
     * @return bool true: Cuando el inicio de sesión es correcto
     *              false: Cuando el inicio de sesión no es correcto
     */
    private function authentication(string $user, string $pass): bool
    {
        global $config;

        $user  = $this->db->scape($user);
        $query = $this->db->select("
            id,
            JSON_UNQUOTE(data->'$.email') as email,
            JSON_UNQUOTE(data->'$.user') as user,
            JSON_UNQUOTE(data->'$.pass') as pass,
            JSON_UNQUOTE(data->'$.status') as status,
            data",
            'users',
            null,
            " data->'$.user'='$user' or data->'$.email'='$user' ",
            1
        );

        # Incio de sesión con éxito
        if (false !== $query && Helper\Strings::chash($query[0]['pass'], $pass)) {

            # Verificar activaciond e cuenta
            if (!filter_var($query[0]['status'], FILTER_VALIDATE_BOOLEAN)) {

                $this->url = $config['build']['url'] . 'activar-cuenta/?error=not-actived&token=' . Helper\Strings::ocrend_encode($query[0]['id'] . '-' . time(), 'user');

                throw new ModelsException('Usuario no confirma su correo electrónico. No se puede ingresar al sistema.');

            }

            # Verificar si ya tiene un perfil asignado
            $permissions = json_decode($query[0]['data'], true);

            if (count($permissions['permissions']) == 0) {
                throw new ModelsException('Usuario no tiene un perfil asignado. No se puede ingresar al sistema.');
            }

            # Restaurar intentos
            $this->restoreAttempts($user);

            # Generar la sesión
            $query[0]['id_user'] = $query[0]['id'];

            $this->generateSession($query[0]);

            return true;
        }

        return false;
    }

    /**
     * Establece los intentos recientes desde la variable de sesión acumulativa
     *
     * @return void
     */
    private function setDefaultAttempts()
    {
        global $session;

        if (null != $session->get('login_user_recentAttempts')) {
            $this->recentAttempts = $session->get('login_user_recentAttempts');
        }
    }

    /**
     * Establece el intento del usuario actual o incrementa su cantidad si ya existe
     *
     * @param string $email: Email del usuario
     *
     * @return void
     */
    private function setNewAttempt(string $email)
    {
        if (!array_key_exists($email, $this->recentAttempts)) {
            $this->recentAttempts[$email] = array(
                'attempts' => 0, # Intentos
                'time'     => null, # Tiempo
            );
        }

        $this->recentAttempts[$email]['attempts']++;
        $this->updateSessionAttempts();
    }

    /**
     * Controla la cantidad de intentos permitidos máximos por usuario, si llega al límite,
     * el usuario podrá seguir intentando en self::MAX_ATTEMPTS_TIME segundos.
     *
     * @param string $email: Email del usuario
     *
     * @throws ModelsException cuando ya ha excedido self::MAX_ATTEMPTS
     * @return void
     */
    private function maximumAttempts(string $email)
    {
        if ($this->recentAttempts[$email]['attempts'] >= self::MAX_ATTEMPTS) {

            # Colocar timestamp para recuperar más adelante la posibilidad de acceso
            if (null == $this->recentAttempts[$email]['time']) {
                $this->recentAttempts[$email]['time'] = time() + self::MAX_ATTEMPTS_TIME;
            }

            if (time() < $this->recentAttempts[$email]['time']) {
                # Setear sesión
                $this->updateSessionAttempts();
                # Lanzar excepción
                throw new ModelsException('Ya ha superado el límite de intentos para iniciar sesión.');
            } else {
                $this->restoreAttempts($email);
            }
        }
    }

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
     * Obtiene datos de un usuario según su id en la base de datos
     *
     * @param int $id: Id del usuario a obtener
     * @param string $select : Por defecto es *, se usa para obtener sólo los parámetros necesarios
     *
     * @return false|array con información del usuario
     */
    public function getUserById()
    {
        $this->setControl();

        global $http;

        $id = Helper\Strings::ocrend_decode($http->headers->get('X-REQUEST-ID-USER'), $this->hash);

        $usuario = $this->db->select('*', "users", "INNER JOIN roles ON data->'$.rol' = roles.role
            INNER JOIN usr_convenios ON usr_convenios.user_convenio = users.id INNER JOIN convenios ON convenios.convenio = usr_convenios.convenio ", "users.id='$id'");

        # No hay resultados
        if (false == $usuario) {
            return array('customData' => $usuario);
        }

        $filter_usuario = array();

        foreach ($usuario as $key => $val) {

            $data                  = json_decode($val['data'], true);
            $data['id']            = Helper\Strings::ocrend_encode($val['id'], $this->hash);
            $data['last_session']  = date('d-m-Y H:i:s', time());
            $data['name_rol']      = $val['name'];
            $data['convenio']      = Helper\Strings::ocrend_encode($val['convenio'], $this->hash);
            $data['name_convenio'] = $val['name_convenio'];
            $data['permissions']   = (count($data['permissions']) != 0) ? explode(',', $data['permissions']) : false;
            unset($data['pass']);

            $filter_usuario[] = $data;

        }

        return array('customData' => $filter_usuario[0]);
    }

    /**
     * Obtiene a todos los usuarios
     *
     * @param string $select : Por defecto es *, se usa para obtener sólo los parámetros necesarios
     *
     * @return false|array con información de los usuarios
     */
    public function getUsers(string $select = '*')
    {
        $this->setControl();

        $usuarios = $this->db->select($select, "users", "INNER JOIN roles ON data->'$.rol' = roles.role
            INNER JOIN usr_convenios ON usr_convenios.user_convenio = users.id INNER JOIN convenios ON convenios.convenio = usr_convenios.convenio ");

        # No hay resultados
        if (false == $usuarios) {
            return array('customData' => false);
        }

        $filter_usuarios = array();

        foreach ($usuarios as $key => $val) {

            $data                  = json_decode($val['data'], true);
            $data['id']            = Helper\Strings::ocrend_encode($val['id'], $this->hash);
            $data['last_session']  = date('d-m-Y H:i:s', time());
            $data['name_rol']      = $val['name'];
            $data['convenio']      = Helper\Strings::ocrend_encode($val['convenio'], $this->hash);
            $data['name_convenio'] = $val['name_convenio'];
            $data['permissions']   = (count($data['permissions']) != 0) ? explode(',', $data['permissions']) : false;
            unset($data['pass']);

            $filter_usuarios[] = $data;

        }

        return array('customData' => $filter_usuarios);
    }

    /**
     * Obtiene datos del usuario conectado actualmente
     *
     * @param string $select : Por defecto es *, se usa para obtener sólo los parámetros necesarios
     *
     * @throws ModelsException si el usuario no está logeado
     * @return array con datos del usuario conectado
     */
    public function getOwnerUser(string $select = '*'): array
    {
        if (null !== $this->id_user) {

            $this->setControl();

            $usuario = $this->db->select($select, "users",
                "INNER JOIN roles ON data->'$.rol' = roles.role
            INNER JOIN usr_convenios ON usr_convenios.user_convenio = users.id INNER JOIN convenios ON convenios.convenio = usr_convenios.convenio ", "users.id = '" . $this->id_user . "'");

            # Si se borra al usuario desde la base de datos y sigue con la sesión activa
            if (false === $usuario) {
                $this->logout();
            }

            $filter_usuario = array();

            foreach ($usuario as $key => $val) {

                $data                  = json_decode($val['data'], true);
                $data['id']            = Helper\Strings::ocrend_encode($val['id'], $this->hash);
                $data['last_session']  = date('d-m-Y H:i:s', time());
                $data['name_rol']      = $val['name'];
                $data['convenio']      = Helper\Strings::ocrend_encode($val['convenio'], $this->hash);
                $data['name_convenio'] = $val['name_convenio'];
                $data['permissions']   = (count($data['permissions']) != 0) ? explode(',', $data['permissions']) : false;
                unset($data['pass']);

                $filter_usuario[] = $data;

            }

            return $filter_usuario[0];

        }

        throw new \RuntimeException('El usuario no está logeado.');
    }

    /**
     * Realiza la acción de login dentro del sistema
     *
     * @return array : Con información de éxito/falla al inicio de sesión.
     */
    public function login(): array
    {
        try {

            global $config, $http;

            # Definir de nuevo el control de intentos
            $this->setDefaultAttempts();

            # Obtener los datos $_POST
            $user = strtolower($http->request->get('user'));
            $pass = $http->request->get('pass');

            # Verificar que no están vacíos
            if (Helper\Functions::e($user, $pass)) {
                throw new ModelsException('Credenciales incompletas.');
            }

            # Añadir intentos
            $this->setNewAttempt($user);

            # Verificar intentos
            $this->maximumAttempts($user);

            # Autentificar
            if ($this->authentication($user, $pass)) {
                return array('success' => true, 'message' => 'Conectado con éxito.', 'url' => $config['build']['url']);
            }

            throw new ModelsException('Credenciales incorrectas.');

        } catch (ModelsException $e) {
            return array('success' => false, 'message' => $e->getMessage(), 'url' => $this->url);
        }
    }

    /**
     * Editar usuario
     *
     * @return array : Con información de éxito/falla al editar.
     */
    public function editarUsuario()
    {
        try {

            global $config, $http;

            $this->setControl();

            # Obtener los datos $_POST
            $email     = $http->request->get('email');
            $user      = $http->request->get('user');
            $nombres   = $http->request->get('nombres');
            $apellidos = $http->request->get('apellidos');
            $id_user   = Helper\Strings::ocrend_decode($http->headers->get('X-REQUEST-ID-USER'), $this->hash);

            # Verificar que no están vacíos
            if (Helper\Functions::e($email, $user, $nombres, $apellidos)) {
                throw new ModelsException('Todos los datos son necesarios');
            }

            # Formato de email
            if (!Helper\Strings::is_email($email)) {
                throw new ModelsException('El email no tiene un formato válido.');
            }

            $owner_user = $this->getOwnerUser();

            # setear valores si el usuario es mayor a administrador y el usuario es diferente del user loggueado
            if ($owner_user['rol'] > 1 && $this->id_user != $id_user) {
                throw new ModelsException('El usuario no tiene suficientes privilegios.');
            }

            # Existencia de email en otra cuenta
            $email = $this->db->scape($email);
            $query = $this->db->select('id', 'users', null, "JSON_UNQUOTE(data->'$.email')='$email' AND id!='$id_user'", 1);

            if (false !== $query) {
                throw new ModelsException('El email ingresado ya pertenece a otro usuario.');
            }

            # Existencia de user en otra cuenta
            $user  = $this->db->scape($user);
            $query = $this->db->select('id', 'users', null, "JSON_UNQUOTE(data->'$.user')='$user' AND id!='$id_user'", 1);

            if (false !== $query) {
                throw new ModelsException('El nombre de usuario ingresado ya pertenece a otro perfil.');
            }

            $user_data = $this->db->select('*', 'users', null, "id='" . $id_user . "'", 1);
            $user_data = json_decode($user_data[0]['data'], true);

            # setear nuevos datos
            $user_data['user']                   = strtolower($user);
            $user_data['email']                  = strtolower($email);
            $user_data['user_data']['nombres']   = strtoupper($this->db->scape($nombres));
            $user_data['user_data']['apellidos'] = strtoupper($this->db->scape($apellidos));

            $update = $this->db->update('users', array(
                'data' => json_encode($user_data, JSON_UNESCAPED_UNICODE),
            ), "id='" . $id_user . "'", 1);

            return array('success' => true, 'message' => 'Hemos procesado su petición con exito.');

        } catch (ModelsException $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    /**
     * eliminarUsuario
     *
     * @return array : Con información de éxito/falla al editar.
     */
    public function eliminarUsuario()
    {
        try {

            global $config, $http;

            $this->setControl();

            # Obtener los datos $_POST
            $id_user = Helper\Strings::ocrend_decode($http->headers->get('X-REQUEST-ID-USER'), $this->hash);

            $owner_user = $this->getOwnerUser();

            # setear valores si el usuario es mayor a administrador y el usuario es diferente del user loggueado
            if ($owner_user['rol'] > 1) {
                throw new ModelsException('El usuario no tiene suficientes privilegios.');
            }

            # Eliminar de tabla usr_convenios
            $del_usr_convenios = $this->db->delete('usr_convenios', "user_convenio='$id_user'");

            if (!$del_usr_convenios['status']) {
                throw new ModelsException($del_usr_convenios['logs']);
            }

            # Eliminar de tabla usuarios
            $del_users = $this->db->delete('users', "id='$id_user'", 1);

            if (!$del_users['status']) {
                throw new ModelsException($del_users['logs']);
            }

            return array('success' => true, 'message' => 'Hemos procesado su petición con exito.');

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
            $email       = $this->db->scape($http->request->get('email'));
            $pass        = $this->db->scape($http->request->get('pass'));
            $pass_repeat = $this->db->scape($http->request->get('pass2'));

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
                'permissions' => '',
                'user_data'   => array(
                    'nombres'  => '',
                    'apelldos' => '',
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

            global $config, $http;

            $this->setControl();

            # Obtener los datos $_POST
            $user  = $this->db->scape($http->request->get('user'));
            $email = $this->db->scape($http->request->get('email'));
            $pass  = $this->db->scape($http->request->get('pass'));
            $pass2 = $this->db->scape($http->request->get('pass2'));
            $rol   = $this->db->scape($http->request->get('rol'));

            # setear valores demodulos sellecionados del usuario
            $_modulos = array();

            foreach ($http->request->all() as $key => $value) {

                if (strpos($key, 'modulos_') !== false) {
                    $v          = explode('_', $key);
                    $_modulos[] = $v[1];
                }

            }

            # Verificar que no están vacíos
            if (Helper\Functions::e($user, $email, $pass, $pass2)) {
                throw new ModelsException('Todos los datos son necesarios');
            }

            if (count($_modulos) == 0) {
                throw new ModelsException('Todos los datsdsdos son necesarios');
            }

            # Verificar email
            $this->checkEmail($email);

            # Veriricar contraseñas
            $this->checkPassMatch($pass, $pass2);

            $user_convenio = $this->getOwnerUser();

            $convenio = Helper\Strings::ocrend_decode($user_convenio['convenio'], $this->hash);

            # Registrar al usuario
            $data = array(
                'user'        => strtolower($user),
                'email'       => strtolower($email),
                'pass'        => Helper\Strings::hash($pass),
                'status'      => false,
                'permissions' => implode(',', $_modulos),
                'user_data'   => array(
                    'nombres'  => '',
                    'apelldos' => '',
                ),
                'rol'         => (int) $rol, # Rol por default para usuarios nuevos rol cliente
            );

            # Insertar datos nuevo usuario
            $id_user = $this->db->insert('users', array(
                'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            ));

            # insertar datos usr_convenio
            $this->db->insert('usr_convenios', array(
                'user_convenio' => $id_user,
                'convenio'      => $convenio,
                'status'        => 1,
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

            return array('success' => true, 'message' => 'Registrado con éxito. Usuario debe activar su cuenta confirmando su dirección de correo electrónico.');

        } catch (ModelsException $e) {

            return array(
                'success' => false,
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
            $modulos = $this->db->select('*', 'modulos');

            $_modulos = array();

            $user = $this->getOwnerUser();

            foreach ($modulos as $key) {

                # borrar modulos de sistema para dministradores

                if ($key['role'] >= $user['rol'] && $key['modulo'] != 'Configuracion') {
                    $_modulos[] = array(
                        'val' => $key['modulo'],
                    );
                }

            }

            return $_modulos;

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
