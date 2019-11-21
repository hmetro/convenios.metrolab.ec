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
use DateTime;
use Doctrine\DBAL\DriverManager;
use Ocrend\Kernel\Helpers as Helper;
use Ocrend\Kernel\Models\IModels;
use Ocrend\Kernel\Models\Models;
use Ocrend\Kernel\Models\ModelsException;
use Ocrend\Kernel\Models\Traits\DBModel;
use Ocrend\Kernel\Router\IRouter;
use SoapClient;

/**
 * Modelo Laboratorio
 */
class Laboratorio extends Models implements IModels
{

    use DBModel;
    # Variables de clase
    private $pstrSessionKey = 0;
    private $USER           = null;
    private $sortField      = 'ROWNUM';
    private $sortType       = 'desc'; # desc
    private $start          = 1;
    private $length         = 10;
    private $searchField    = null;
    private $startDate      = null;
    private $endDate        = null;
    private $tresMeses      = null;
    private $_conexion      = null;
    private $dia            = null;
    private $mes            = null;
    private $anio           = null;
    private $hora           = null;
    private $hash           = null;

    /**
     * ROL MINIMO PARA MANIPULACION DEL USUARIO
     *
     * @var int
     */

    const ROL_USER = 4;

    /**
     * Conexion
     *
     */

    private function conectar_Oracle()
    {
        global $config;

        $_config = new \Doctrine\DBAL\Configuration();
//..
        # SETEAR LA CONNEXION A LA BASE DE DATOS DE ORACLE GEMA
        $this->_conexion = \Doctrine\DBAL\DriverManager::getConnection($config['database']['drivers']['oracle'], $_config);

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

            if ($this->id_user['rol'] > self::ROL_USER) {
                throw new \RuntimeException('El usuario no tiene suficientes privilegios.');
            }

            $this->hash = $config['sessions']['user_cookie']['key_encrypt'];

        } else {

            throw new \RuntimeException('El usuario no está logeado.');

        }

    }

    private function errorsPagination()
    {

        if ($this->length > 10) {
            throw new ModelsException('!Error! Solo se pueden mostrar 10 resultados por página.');
        }

    }

    private function setParameters()
    {

        global $http;

        foreach ($http->request->all() as $key => $value) {
            $this->$key = strtoupper($value);
        }

        /*

        if ($this->startDate != null and $this->endDate != null) {

        $startDate = $this->startDate;
        $endDate   = $this->endDate;

        $sd = new DateTime($startDate);
        $ed = new DateTime($endDate);

        if ($sd->getTimestamp() > $ed->getTimestamp()) {
        throw new ModelsException('!Error! Fecha inicial no puede ser mayor a fecha final.');
        }

        }

         */

        $fecha      = date('d-m-Y');
        $nuevafecha = strtotime('-2 day', strtotime($fecha));

        # SETEAR FILTRO HASTA TRES MESES
        $this->tresMeses = date('d-m-Y', time());

    }

    public function getResultadosLab()
    {

        try {

            global $config, $http;

            $this->setControl();

            $desde = new DateTime();
            $desde->modify('-5 day');

            $desde_dia  = $desde->format('d');
            $desde_mes  = $desde->format('m');
            $desde_anio = $desde->format('Y');
            $desde_hora = $desde->format('H');

            $hasta = new DateTime();
            $hasta->modify('-1 day');

            $hasta_dia  = $hasta->format('d');
            $hasta_mes  = $hasta->format('m');
            $hasta_anio = $hasta->format('Y');
            $hasta_hora = $hasta->format('H');

            # ERRORES DE PETICION
            $this->errorsPagination();

            /*

            $sql = " SELECT *
            FROM (
            SELECT b.*, ROWNUM AS NUM
            FROM (
            SELECT *
            FROM WEB2_RESULTADOS_LAB_CONVENIOS
            ORDER BY COD_PERSONA ASC
            ) b
            WHERE ROWNUM <= " . $this->limit . "
            AND to_char(FECHA, 'DD/MM/YYYY hh24:mi') >= '" . $desde_dia . "/" . $desde_mes . "/" . $desde_anio . " " . $desde_hora . ":59'
            AND COD_PERSONA LIKE '83273%'
            AND COD_CONVENIO = '37301'
            AND TOT_SC != TOD_DC
            ORDER BY to_date(FECHA,'DD/MM/YYYY') ASC
            )

            WHERE NUM > " . $this->offset . " ";

             */

            # seteo de valores para paginacion
            $this->start = (int) $http->query->get('start');

            $this->length = (int) $http->query->get('length');

            if ($this->start >= 10) {
                $this->length = $this->start + 10;
            }

            # seteo de valores para busqueda

            $_searchField = (bool) $http->query->get('search')['value'];

            if ($_searchField != false) {

                $this->searchField = $this->quitar_tildes(mb_strtoupper($this->sanear_string($http->query->get('search')['value']), 'UTF-8'));

                $sql = " SELECT *
                FROM (
                  SELECT b.*, ROWNUM AS NUM
                  FROM (
                    SELECT *
                    FROM WEB2_RESULTADOS_LAB_CONVENIOS
                    ORDER BY COD_PERSONA DESC
                  ) b
                  WHERE ROWNUM <= " . $this->length . "
                  AND to_char(FECHA, 'DD/MM/YYYY hh24:mi') <= '" . $hasta_dia . "/" . $hasta_mes . "/" . $hasta_anio . " 12:59'
                  AND TOT_SC != TOD_DC
                  AND NOMBRE_PERSONA LIKE '%" . $this->searchField . "%'
                  ORDER BY to_date(FECHA,'DD/MM/YYYY') ASC
                )
                WHERE NUM > " . $this->start . " ";

            } else {

                $sql = " SELECT *
                FROM (
                  SELECT b.*, ROWNUM AS NUM
                  FROM (
                    SELECT *
                    FROM WEB2_RESULTADOS_LAB_CONVENIOS
                    ORDER BY COD_PERSONA DESC
                  ) b
                  WHERE ROWNUM <= " . $this->length . "
                  AND to_char(FECHA, 'DD/MM/YYYY hh24:mi') <= '" . $hasta_dia . "/" . $hasta_mes . "/" . $hasta_anio . " 12:59'
                  AND TOT_SC != TOD_DC
                  ORDER BY to_date(FECHA,'DD/MM/YYYY') ASC
                )
                WHERE NUM > " . $this->start . " ";

            }

            # Conectar base de datos
            $this->conectar_Oracle();

            # Execute
            $stmt = $this->_conexion->query($sql);

            # cERRAR CONEXION
            $this->_conexion->close();

            # VERIFICAR RESULTADOS
            $data = $stmt->fetchAll();

            # NO EXITEN RESULTADOS
            $this->notResults($data);

            # Datos de usuario cuenta activa
            $resultados = array();

            foreach ($data as $key) {

                $ID_RESULTADO = Helper\Strings::ocrend_encode($key['SC'], $this->hash);

                $key['ORIGEN']       = strtoupper($key['ORIGEN']);
                $key['FECHA']        = date('d-m-Y', strtotime($key['FECHA']));
                $key['ID_RESULTADO'] = $ID_RESULTADO;
                $key['PDF']          = $config['build']['url'] . 'api/documentos/resultados/' . $ID_RESULTADO . '.pdf';
                unset($key['TOT_SC']);
                unset($key['TOD_DC']);
                unset($key['ROWNUM']);

                $resultados[] = $key;
            }

            # Ya no existe resultadso
            $this->notResults($resultados);

            # Devolver Información
            return array(
                'status'     => true,
                'customData' => $resultados,
                'total'      => count($resultados),
                'start'      => intval($this->start),
                'length'     => intval($this->length),
                # 'dataddd' => $http->request->all(),
            );

        } catch (ModelsException $e) {

            return array('status' => false, 'message' => $e->getMessage());

        }

    }

    public function getResultadosLabById($id_resultado, $fecha)
    {

        try {

            global $config;

            $this->setControl();

            // Volver a encriptar
            $id_resultado = Helper\Strings::ocrend_decode($id_resultado, $this->hash);

            $doc_resultado = $this->wsLab_GET_REPORT_PDF($id_resultado, $fecha);

            // No existe documeneto
            if (!$doc_resultado['status']) {
                throw new ModelsException($doc_resultado['message']);
            }

            $id_resultado = Helper\Strings::ocrend_encode($id_resultado, $this->hash);

            $url         = $doc_resultado['data'];
            $destination = "../assets/descargas/" . $id_resultado . ".pdf";
            $fp          = fopen($destination, 'w+');
            $ch          = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_exec($ch);
            curl_close($ch);

            fclose($fp);

            /*

            # SETAR LOGO PERSONALIZDO

            $destination = "../assets/descargas/" . $id_resultado . ".pdf";

            # setear logo personalizado

            $m = new Model\Users;

            $user = $m->getOwnerUser();

            $id_convenio = Helper\Strings::ocrend_decode($user['convenio'], $this->hash);

            # SETEAR VALORES PARA CUSTON HEADER

            $query   = $this->db->select('*', 'convenios', null, "convenio='$id_convenio'", 1);
            $cv_data = json_decode($query[0]['data_convenio'], true);

            $custom_cv_header = 'DIR: ' . $cv_data['dir'] . ' TEL: ' . $cv_data['tel'] . ' E-MAIL: ' . strtoupper($cv_data['email']);

            $imagen_fondo_logo = "../assets/dashforge/img/convenios/isologos/" . $id_convenio . ".png";

            // Devuelve true
            $exists_logo = is_file($imagen_fondo_logo);

            if ($exists_logo) {
            $imagen_fondo_logo = "../assets/dashforge/img/convenios/isologos/" . $id_convenio . ".png";
            } else {
            $imagen_fondo_logo = "../assets/dashforge/img/convenios/isologos/metrolab.logo.png";

            }

            # setear imagen con texto para footer
            $footer_blank = "../assets/mail/images/blank.png";

            $pdf = new Fpdi();

            $staticIds = array();
            $pageCount = $pdf->setSourceFile($destination);
            for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $staticIds[$pageNumber] = $pdf->importPage($pageNumber);
            }

            // get the page count of the uploaded file
            $pageCount = $pdf->setSourceFile($destination);
            // let's track the page number for the filler page
            $fillerPageCount = 1;
            // import the uploaded document page by page
            for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {

            if ($fillerPageCount == 1) {
            // add the current filler page
            $pdf->AddPage();
            $pdf->useTemplate($staticIds[$fillerPageCount]);
            $pdf->Image($imagen_fondo_logo, 12.5, 13.5, 40, 9.5);
            $pdf->Image($footer_blank, 5, 284, null, 6);

            $pdf->SetY(2.1);
            // Begin with regular font
            $pdf->SetTextColor(51, 51, 51);
            // Arial italic 8
            $pdf->SetFont('Arial', 'B', 6.5);
            // Disclaimer
            $pdf->Cell(0, 11, $custom_cv_header, 0, 0, 'R');

            } else {

            // add the current filler page
            $pdf->AddPage();
            $pdf->useTemplate($staticIds[$fillerPageCount]);
            $pdf->Image($imagen_fondo_logo, 12.5, 12, 40, 9.5);
            $pdf->Image($footer_blank, 5, 283, null, 6);
            $pdf->SetY(0.5);
            // Begin with regular font
            $pdf->SetTextColor(51, 51, 51);
            // Arial italic 8
            $pdf->SetFont('Arial', 'B', 6.5);
            // Disclaimer
            $pdf->Cell(0, 11, $custom_cv_header, 0, 0, 'R');

            }

            // update the filler page number or reset it
            $fillerPageCount++;
            if ($fillerPageCount > count($staticIds)) {
            $fillerPageCount = 1;
            }
            }

            $pdf->Output('F', $destination);

             */

            return array(
                'status'       => true,
                'id_resultado' => $id_resultado,
                'pdf'          => $config['build']['url'] . 'api/documentos/resultados/' . $id_resultado . ".pdf",
            );

        } catch (ModelsException $e) {

            return array('status' => false, 'message' => $e->getMessage());

        }

    }

    # Metodo LOGIN webservice laboratorio ROCHE
    public function wsLab_LOGIN()
    {

        try {

            $client = new SoapClient(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'zdk.ws.wSessions.wsdl.xml');

            $Login = $client->Login(array(
                "pstrUserName" => "CONSULTA",
                "pstrPassword" => "CONSULTA1",
            ));

            # Guaradar  KEY de session WS
            $this->pstrSessionKey = $Login->LoginResult;

            # Retorna KEY de session WS
            # return $Login->LoginResult;

        } catch (SoapFault $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }

    }

    # Metodo LOGOUT webservice laboratorio ROCHE
    public function wsLab_LOGOUT()
    {

        try {

            # INICIAR SESSION
            # $this->wsLab_LOGIN();

            $client = new SoapClient(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'zdk.ws.wSessions.wsdl.xml');

            $Logout = $client->Logout(array(
                "pstrSessionKey" => $this->pstrSessionKey,
            ));

            # return $Logout->LogoutResult;

        } catch (SoapFault $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }

    }

    # Metodo wReports webservice laboratorio ROCHEdevuelve el resultado pdf del paciente
    public function wsLab_GET_REPORT_PDF(string $SC, string $FECHA)
    {

        try {

            # INICIAR SESSION
            $this->wsLab_LOGIN();

            $client = new SoapClient(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'wso.ws.wReports.wsdl.xml');

            $FECHA_final = explode('-', $FECHA);

            $Preview = $client->Preview(array(
                "pstrSessionKey"        => $this->pstrSessionKey,
                "pstrSampleID"          => $SC, # '0015052333',
                "pstrRegisterDate"      => $FECHA_final[2] . '-' . $FECHA_final[1] . '-' . $FECHA_final[0], # '2018-11-05',
                "pstrFormatDescription" => 'METROPOLITANO',
                "pstrPrintTarget"       => 'Destino por defecto',
            ));

            # CERRAR SESSION POR LICENCIAS HSF
            $this->wsLab_LOGOUT();

            # No existe documento

            if (!isset($Preview->PreviewResult)) {
                throw new ModelsException('Error 0 => No existe el documento solicitado.');
            }

            # No existe documento

            if (isset($Preview->PreviewResult) or $Preview->PreviewResult == '0') {

                if ($Preview->PreviewResult == '0') {

                    throw new ModelsException('Error 1 => No existe el documento solicitado.');

                } else {

                    return array(
                        'status' => true,
                        'data'   => str_replace("SERVER-ROCHE", "resultados.hmetro.med.ec", $Preview->PreviewResult),
                    );

                }

            }

            #
            throw new ModelsException('Error 2 => No existe el documento solicitado.');

        } catch (SoapFault $e) {

            if ($e->getCode() == 0) {
                return array('status' => false, 'message' => $e->getMessage());
            } else {
                return array('status' => false, 'message' => $e->getMessage());

            }

        } catch (ModelsException $b) {

            if ($b->getCode() == 0) {
                return array('status' => false, 'message' => $b->getMessage());
            } else {
                return array('status' => false, 'message' => $b->getMessage());

            }
        }

    }

    # Ordenar array por campo
    public function orderMultiDimensionalArray($toOrderArray, $field, $inverse = 'desc')
    {
        $position = array();
        $newRow   = array();
        foreach ($toOrderArray as $key => $row) {
            $position[$key] = $row[$field];
            $newRow[$key]   = $row;
        }
        if ($inverse == 'desc') {
            arsort($position);
        } else {
            asort($position);
        }
        $returnArray = array();
        foreach ($position as $key => $pos) {
            $returnArray[] = $newRow[$key];
        }
        return $returnArray;
    }

    private function get_Order_Pagination(array $arr_input)
    {
        # SI ES DESCENDENTE

        $arr = array();
        $NUM = 1;

        if ($this->sortType == 'desc') {

            $NUM = count($arr_input);
            foreach ($arr_input as $key) {
                $key['NUM'] = $NUM;
                $arr[]      = $key;
                $NUM--;
            }

            return $arr;

        }

        # SI ES ASCENDENTE

        foreach ($arr_input as $key) {
            $key['NUM'] = $NUM;
            $arr[]      = $key;
            $NUM++;
        }

        return $arr;
    }

    private function get_page(array $input, $pageNum, $perPage)
    {
        $start = ($pageNum - 1) * $perPage;
        $end   = $start + $perPage;
        $count = count($input);

        // Conditionally return results
        if ($start < 0 || $count <= $start) {
            // Page is out of range
            return array();
        } else if ($count <= $end) {
            // Partially-filled page
            return array_slice($input, $start);
        } else {
            // Full page
            return array_slice($input, $start, $end - $start);
        }
    }

    /**
     * Envía un correo electrónico al usuario que quiere recuperar la contraseña, con un token y una nueva contraseña.
     * Si el usuario no visita el enlace, el sistema no cambiará la contraseña.
     *
     * @return array<string,integer|string>
     */
    public function sedmailLab(): array
    {
        try {
            global $http, $config;

            # Obtener datos $_POST
            $email = strtolower($http->request->get('email'));
            $pdf   = '../assets/descargas/' . $http->headers->get('X-REQUEST-ID-DOCUMENTO') . '.pdf';

            # Campo lleno
            if (Helper\Functions::emp($email)) {
                throw new ModelsException('El campo email debe estar lleno.');
            }

            # Construir mensaje y enviar mensaje
            $content = 'Se ha solicitado el siguiente resultado de laboratorio por correo electrónico.
                    <br />';

            $link = $config['build']['url'] . 'api/documentos/resultados/' . $http->headers->get('X-REQUEST-ID-DOCUMENTO') . '.pdf';

            # Enviar el correo electrónico
            $_html = Helper\Emails::loadTemplate(array(
                # Título del mensaje
                '{{title}}'     => 'Resultado de Laboratorio - ' . $config['build']['name'],
                # Contenido del mensaje
                '{{content}}'   => $content,
                # Url del botón
                '{{btn-href}}'  => $link,
                # Texto del boton
                '{{btn-name}}'  => 'Ver Resultado',
                # Copyright
                '{{copyright}}' => '&copy; ' . date('Y') . ' <a href="' . $config['build']['url'] . '">' . $config['build']['name'] . '</a> Todos los derechos reservados.',
            ), 4);

            # Verificar si hubo algún problema con el envío del correo
            $sednmail = $this->sendMail($_html, $email, 'Resultado de Laboratorio - ' . $config['build']['name']);
            if ($sednmail != 0) {
                throw new ModelsException('No se ha podido enviar el correo electrónico.');
            }

            return array('success' => true, 'message' => 'Proceso realizado con éxito. Se envío un resultado por correo electrónico a: ' . $email);

        } catch (ModelsException $e) {
            return array('success' => false, 'message' => $e->getMessage());
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

    private function notResults(array $data)
    {
        if (count($data) == 0) {
            throw new ModelsException('No existe más resultados.');
        }
    }

    private function quitar_tildes($cadena)
    {
        $no_permitidas = array("%", "é", "í", "ó", "ú", "É", "Í", "Ó", "Ú", "ñ", "À", "Ã", "Ì", "Ò", "Ù", "Ã™", "Ã ", "Ã¨", "Ã¬", "Ã²", "Ã¹", "ç", "Ç", "Ã¢", "ê", "Ã®", "Ã´", "Ã»", "Ã‚", "ÃŠ", "ÃŽ", "Ã”", "Ã›", "ü", "Ã¶", "Ã–", "Ã¯", "Ã¤", "«", "Ò", "Ã", "Ã„", "Ã‹");
        $permitidas    = array("", "e", "i", "o", "u", "E", "I", "O", "U", "n", "N", "A", "E", "I", "O", "U", "a", "e", "i", "o", "u", "c", "C", "a", "e", "i", "o", "u", "A", "E", "I", "O", "U", "u", "o", "O", "i", "a", "e", "U", "I", "A", "E");
        $texto         = str_replace($no_permitidas, $permitidas, $cadena);
        return $texto;
    }

    private function sanear_string($string)
    {

        $string = trim($string);

        //Esta parte se encarga de eliminar cualquier caracter extraño
        $string = str_replace(
            array(">", "< ", ";", ",", ":", "%", "|", "-", "/"),
            ' ',
            $string
        );

        return trim($string);
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
