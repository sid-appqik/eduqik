<?php
date_default_timezone_set('Asia/Kolkata');
class REST
{

    public $_allow = array();
    public $_content_type = "application/json";
    public $_request = array();

    protected $_method = "";
    protected $_code = 200;

    const DB_SERVER = "localhost";
    const DB_USER = "eduqik";
    const DB_PASSWORD = "";
    const DB = "eduqik_erp";

    protected $sql = null;
    protected $db = null;
    protected $biosdb = null;
    protected $resulSet = null;
    protected $numRows = null;

    public function __construct()
    {
        $this->inputs();
    }

    public function get_referer()
    {
        return $_SERVER['HTTP_REFERER'];
    }

    public function response($data, $status)
    {
        $this->_code = ($status) ? $status : 200;
        $this->set_headers();
        echo $data;
        exit;
    }

    protected function get_status_message()
    {
        $status = array(
            100 => 'Continue',
            101 => 'Switching Protocols',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => '(Unused)',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported');
        return ($status[$this->_code]) ? $status[$this->_code] : $status[500];
    }

    public function get_request_method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    protected function inputs()
    {
        switch ($this->get_request_method()) {
            case "POST":
                $this->_request = $this->cleanInputs($_POST);
                break;
            case "GET":
            case "DELETE":
                $this->_request = $this->cleanInputs($_GET);
                break;
            case "PUT":
                parse_str(file_get_contents("php://input"), $this->_request);
                $this->_request = $this->cleanInputs($this->_request);
                break;
            default:
                $this->response('', 406);
                break;
        }
    }

    protected function cleanInputs($data)
    {
        $clean_input = array();
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $clean_input[$k] = $this->cleanInputs($v);
            }
        } else {
            if (get_magic_quotes_gpc()) {
                $data = trim(stripslashes($data));
            }
            $data = strip_tags($data);
            $clean_input = trim($data);
        }
        return $clean_input;
    }

    protected function set_headers()
    {
        header("HTTP/1.1 " . $this->_code . " " . $this->get_status_message());
        header("Content-Type:" . $this->_content_type);
    }

    /* =========================== Utility functions for api process ===========================*/

    protected function query($sql, $conf = null)
    {
        $this->sql = $sql;
        $con = (!$conf) ? $this->db : $this->biosdb;
        $this->resulSet = mysqli_query($con, $this->sql) or die(mysqli_error($con));

        if (!$this->resulSet) {
            $s = $this->json(['success' => false, 'msg' => 'Unauthorized Access']);
            $this->response($s, 203);
        }
    }

    protected function affectedRows($conf = null)
    {
        $con = (!$conf) ? $this->db : $this->biosdb;
        return mysqli_affected_rows($con);
    }

    protected function numRows()
    {
        return mysqli_num_rows($this->resulSet);
    }

    protected function fetchAssoc()
    {
        $data = [];

        if ($this->numRows() == 1) {
            return mysqli_fetch_array($this->resulSet, MYSQLI_ASSOC);
        } else {
            while ($res = mysqli_fetch_array($this->resulSet, MYSQLI_ASSOC)) {
                $data[] = $res;
            }
        }

        return $data;
    }

    protected function fetchAssoc2()
    {
        $data = [];

        while ($res = mysqli_fetch_array($this->resulSet, MYSQLI_ASSOC)) {
            $data[] = $res;
        }

        return $data;

    }

    protected function json($data)
    {
        if (is_array($data)) {
            return json_encode($data);
        }
    }

    protected function validate($exclude = array())
    {
        if (!empty($this->_request)) {
            if (!empty($exclude)) {
                $request = array_diff_key($this->_request, array_flip($exclude));
            } else {
                $request = $this->_request;
            }

            foreach ($request as $req) {
                if (empty($req)) {
                    return false;
                    die;
                }
            }

            return true;
        }
    }

    protected function fcm($tokens)
    {
        if (is_array($tokens)) {
            $token = $tokens;
        } else {
            $token = [$tokens];
        }

        define('API_ACCESS_KEY', 'AIzaSyClvBTAOmtscwnjczVgjX5YkJJtb1MYHV0');
        $fcmUrl = 'https://fcm.googleapis.com/fcm/send';
        $data = ['message' => 'Test msg', "title" => 'Test Title', 'img' => 'http://www.fieldsmate.com/images/logo.png'];
        $notification = [
            'title' => 'Alert',
            'body' => 'test message.',
            'data' => $data,
        ];

        $fcmNotification = [
            'registration_ids' => $token, //multple token array
            'data' => $data,
        ];

        $headers = [
            'Authorization: key=' . API_ACCESS_KEY,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fcmUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmNotification));
        $result = curl_exec($ch);
        curl_close($ch);

        echo $result;
    }

}

// Utility Methods
if (!function_exists('h1')) {
    function h1($txt)
    {
        echo "<h1>" . $txt . "</h1>";
    }
}
if (!function_exists('pre')) {
    function pre($a)
    {
        ob_start();
        print_r($a);
        $output = ob_get_clean();
        $output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);

        echo '<pre>';
        echo $output;
    }
}

if (!function_exists('di')) {
    function di($a)
    {
        pre($a);
        die;
    }
}

if (!function_exists('t')) {
    function t()
    {
        echo "Hey! i am here :)";
    }
}
