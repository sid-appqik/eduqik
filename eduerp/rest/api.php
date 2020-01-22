<?php

error_reporting(E_ALL);
require_once "class/Main.php";
define('BASE_URL', 'http://www.appqik.com/appqik/');
class API extends Main
{

    public $data = "";

    // const DB_SERVER = "localhost";
    // const DB_USER = "root";
    // const DB_PASSWORD = "root";
    // const DB = "field_mate_dev";

    const SITE_URL = "http://www.appqik.com/appqik/";
    const DB_SERVER = "localhost";
    const DB_USER = "appqik";
    const DB_PASSWORD = "";
    const DB = "appqik_erp";
    const DB2 = "ebioserver";

    public function __construct()
    {
        parent::__construct(); // Init parent contructor
        $this->dbConnect(); // Initiate Database connection
    }

    /*
     *  Database connection
     */

    private function dbConnect()
    {
        $this->db = mysqli_connect(self::DB_SERVER, self::DB_USER, self::DB_PASSWORD);
        if ($this->db) {
            mysqli_select_db($this->db, self::DB);
        }
    }

    private function biosDbConnect()
    {
        $this->biosdb = mysqli_connect('localhost', 'essl', 'essl@123');
        if ($this->biosdb) {
            mysqli_select_db($this->biosdb, self::DB2);
        }
    }

    /*
     * Public method for access api.
     * This method dynmically call the method based on the query string
     *
     */

    public function processApi()
    {
        $func = strtolower(trim(str_replace("/", "", $_REQUEST['rquest'])));
        if ((int) method_exists($this, $func) > 0) {
            $this->$func();
        } else {
            $this->response('', 404);
        }
        // If the method not exist with in this class, response would be "Page not found".
    }

    /*
     *  Simple login API
     *  Login must be POST method
     *  email : <USER EMAIL>
     *  pwd : <USER PASSWORD>
     */

    private function login()
    {
        // Cross validation if the request method is POST else it will return "Not Acceptable" status

        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }

        if ($this->validate(['imei', 'device_token'])) {

            $mobile = $this->_request['mobile_no'];
            $imei = $this->_request['imei'];
            $device_type = $this->_request['device_type'];
            $device_token = $this->_request['device_token'];
            $lat = $this->_request['lat'];
            $long = $this->_request['long'];

            /*
             * user_type_id 103 is end user
             * check list table
             */

            $sql = "SELECT m.id as parent_id,m.name,m.email,m.mobile,IF(c.company_type_id=301, '1', '2') as company_type_id,c.company_type_id as company_type,'' as employee_id, m.company_id FROM membership m JOIN company c ON(m.company_id=c.id)  WHERE m.mobile='$mobile' AND m.user_type_id=103 LIMIT 1";
            $this->query($sql);

            if ($this->numRows() > 0) {
                $empData = $this->fetchAssoc();
                $member_id = $empData['parent_id'];
                if ($empData['company_type'] == 302) {
                    /* Getting employee id from employee table */
                    $sql = "SELECT e.id FROM employee e JOIN membership m ON e.employee_mobile=m.mobile WHERE m.mobile=$mobile";
                    $this->query($sql);

                    if ($this->numRows() > 0) {
                        $emp = $this->fetchAssoc();
                        $empData['employee_id'] = $emp['id'];
                    } else {
                        $this->response($this->json(['success' => false, "msg" => "Invalid user"]), 200);
                    }

                    /* <close> Getting employee id from employee table */

                    $empData['parent_id'] = '';
                    $emp_id = $empData['employee_id'];

                    if (isset($lat) && isset($long)) {
                        // $this->query("INSERT INTO employee_track_history SET emp_id='$emp_id', latitude='$lat', longitude='$long', event=1");
                    }
                } else {
                    $member_id = $empData['parent_id'];
                }

                /* Check and insert/update parent device data into table */
                /* $sql = "SELECT id FROM membership2token WHERE device_imei = '$imei' AND membership_id =  $member_id"; */

                $sql = "SELECT id FROM membership2token WHERE membership_id = $member_id AND device_imei='$imei'";
                $this->query($sql);

                if ($this->numRows() > 0) {
                    $this->query("UPDATE membership2token SET device_imei = '$imei',device_type = '$device_type', device_token = '$device_token' WHERE membership_id =  $member_id");
                } else {
                    $sql = "INSERT INTO membership2token SET membership_id =  $member_id, device_imei = '$imei', device_type = '$device_type', device_token = '$device_token', created_date = NOW() ";
                    $this->query($sql);
                }
                //                print_r($empData);
                $result = ['success' => true, 'data' => $empData, 'msg' => ''];

                // If success everythig is good send header as "OK" and user details
                //                print_r($result);
                $this->response($this->json($result), 200);
            }
            $this->response($this->json(['success' => false, "msg" => "Invalid user"]), 200); // If no records "No Content" status
        }

        // If invalid inputs "Bad Request" status message and reason
        $error = ['success' => false, 'status' => "Failed", "msg" => "Invalid Username or Password"];
        $this->response($this->json($error), 400);
    }

    /* Below method is for testing purpose and can be deleted */

    private function testlogin()
    {
        // Cross validation if the request method is POST else it will return "Not Acceptable" status

        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }

        if ($this->validate(['imei', 'device_token'])) {

            $mobile = $this->_request['mobile_no'];
            $imei = $this->_request['imei'];
            $device_type = $this->_request['device_type'];
            $device_token = $this->_request['device_token'];
            $lat = $this->_request['lat'];
            $long = $this->_request['long'];

            /*
             * user_type_id 103 is end user
             * check list table
             */

            $sql = "SELECT m.id as parent_id,m.name,m.email,m.mobile,IF(c.company_type_id=301, '1', '2') as company_type_id,c.company_type_id as company_type,'' as employee_id, m.company_id FROM membership m JOIN company c ON(m.company_id=c.id) WHERE m.mobile='$mobile' AND m.user_type_id=103 LIMIT 1";
            $this->query($sql);

            if ($this->numRows() > 0) {
                $empData = $this->fetchAssoc();

                if ($empData['company_type'] == 302) {

                    /* Getting employee id from employee table */
                    $this->query("SELECT e.id FROM employee e JOIN membership m ON(e.employee_mobile=m.mobile AND e.employee_email=m.email) WHERE m.mobile=$mobile");

                    if ($this->numRows() > 0) {
                        $emp = $this->fetchAssoc();
                        $empData['employee_id'] = $emp['id'];
                    } else {
                        $this->response($this->json(['success' => false, "msg" => "Invalid user"]), 200);
                    }
                    /* <close> Getting employee id from employee table */

                    $empData['parent_id'] = '';
                    $member_id = $empData['employee_id'];

                    if (isset($lat) && isset($long)) {
                        $this->query("INSERT INTO employee_track_history SET emp_id='$member_id', latitude='$lat', longitude='$long', event=1");
                    }
                } else {
                    $member_id = $empData['parent_id'];
                }

                /* Check and insert/update parent device data into table */
                $this->query("SELECT id FROM membership2token WHERE device_imei = '$imei' AND membership_id =  $member_id");

                if ($this->numRows() > 0) {
                    $this->query("UPDATE membership2token SET device_imei = '$imei',device_type = '$device_type', device_token = '$device_token' WHERE device_imei = '$imei' AND membership_id =  $member_id");
                } else {
                    $this->query("INSERT INTO membership2token SET membership_id =  $member_id, device_imei = '$imei', device_type = '$device_type', device_token = '$device_token', created_date = NOW() ");
                }

                $result = ['success' => true, 'data' => $empData, 'msg' => ''];

                // If success everythig is good send header as "OK" and user details
                $this->response($this->json($result), 200);
            }
            $this->response($this->json(['success' => false, "msg" => "Invalid user"]), 200); // If no records "No Content" status
        }

        // If invalid inputs "Bad Request" status message and reason
        $error = ['success' => false, 'status' => "Failed", "msg" => "Invalid Username or Password"];
        $this->response($this->json($error), 400);
    }

    private function getChildList()
    {
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }

        if ($this->validate()) {
            $parent_id = $this->_request['parent_id'];

            /*   
            $sql = "SELECT s.id,s.student_name,CONCAT('" . self::SITE_URL . "/admin/skins/dist/company-data/student/',s.student_avatar) as student_image,IF(s.class IS NULL, '',s.class) as class,IF(s.section IS NULL, '',s.section) as section,IF(s.roll_number IS NULL, '',s.roll_number) as roll_number,get_device_imei_by_student_id(s.id) as imei FROM `membership` m JOIN student_info s ON((m.email=s.father_email || m.email=s.mother_email) OR (m.mobile=s.father_mobile || m.mobile=s.mother_mobile)) WHERE m.id=$parent_id";
          

            
            $sql = "SELECT s.id,s.student_name,CONCAT('" . self::SITE_URL . "/admin/skins/dist/company-data/student/',s.student_avatar) as student_image,"
                    . "IF(s.class IS NULL, '',s.class) as class,IF(s.section IS NULL, '',s.section) as section,"
                    . "IF(s.roll_number IS NULL, '',s.roll_number) as roll_number,"
                    . "get_device_imei_by_student_id(s.id) as imei, l.name as class FROM `membership` m "
                    . "JOIN student_info s ON((m.mobile=s.father_mobile || m.mobile=s.mother_mobile)) "
                    . "JOIN list as l ON(l.id=s.class_type_id) WHERE m.id=$parent_id";
            */

            /*start new update (Siddharth 13 Aug)*/
            //student attendance
            $company_id = $this->_request['company_id'];
            $total_present = 0;
            $total_absent = 0;
            $total_completed_class = 0;
            $attQuery = "SELECT s.id,s.student_rfid, d.device_serial_number
                       FROM student_info AS s
                       JOIN membership m ON((m.mobile=s.father_mobile || m.mobile=s.mother_mobile))
                       JOIN device AS d ON d.id=s.biometric_device_id
                       WHERE s.company_id=$company_id AND m.id=$parent_id";
            $this->query($attQuery);
            if ($this->numRows() > 0) {
                $std = $this->fetchAssoc();
                $user_id = $std['student_rfid'];
                $device_serial = $std['device_serial_number'];
                $month = (int) date('m');
                $year  = (int) date('Y');
                $end_day = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                $table_name = "deviceLogs_" . $month . "_" . $year;
                $start_date = $year . "-" . $month . "-" . "01";
                $end_date   = $year . "-" . $month . "-" . $end_day;
                //check table exist or not
                $this->biosDbConnect();
                $checkTableQuery = "SELECT count(*) as countTable FROM information_schema.tables WHERE table_schema = 'ebioserver' AND table_name = '$table_name'";
                $this->query($checkTableQuery, $this->biosdb);
                if ($this->numRows() > 0) {
                    $tbl = $this->fetchAssoc();
                    $checkTable = (int) $tbl['countTable'];
                    if ($checkTable === 1) {
                        // Connecting to ebios-server
                        $totalClassQuery = "SELECT dl.DeviceLogId FROM $table_name dl
                                                JOIN devices d ON(d.DeviceId=dl.DeviceId) 
                                                WHERE d.SerialNumber='" . $device_serial . "' AND DATE(dl.LogDate) >='" . $start_date . "' AND DATE(dl.LogDate) <='" . $end_date . "'
                                                GROUP BY DATE(LogDate)";
                        $this->query($totalClassQuery, $this->biosdb);
                        if ($this->numRows() > 0) {
                            $total_completed_class = $this->numRows();
                        }
                        //end
                        //present days
                        $presentQuery = "SELECT dl.DeviceLogId FROM $table_name dl 
                                             JOIN devices d ON(d.DeviceId=dl.DeviceId) 
                                             WHERE d.SerialNumber='" . $device_serial . "' AND dl.UserId='" . $user_id . "' AND DATE(dl.LogDate) >='" . $start_date . "' AND DATE(dl.LogDate) <='" . $end_date . "'
                                             GROUP BY DATE(LogDate)";
                        $this->query($presentQuery, $this->biosdb);
                        if ($this->numRows() > 0) {
                            $total_present = $this->numRows();
                        }
                        //end
                        $total_absent = $total_completed_class - $total_present;
                    }
                }
            }
            //end student attendance

            //Start bus route
            $branchLatLong = '';
            $parentLatLong = '';
            $routeSql = "SELECT cp.id,c.location AS company_address,address.address AS parent_address
                       FROM student_info sf
                       JOIN membership m ON(m.mobile=sf.father_mobile || m.mobile=sf.mother_mobile)
                       JOIN company c ON c.id=sf.company_id
                       JOIN contact_person cp ON (cp.company_id=sf.company_id AND cp.mobile=m.mobile) 
                       JOIN address ON address.contact_person_id=cp.id
                       WHERE m.id=$parent_id AND sf.company_id=$company_id limit 1";
            $this->query($routeSql);
            if ($this->numRows() > 0) {
                $r = $this->fetchAssoc();
                $branch_address = $r['company_address'];
                $parent_address  = $r['parent_address'];

                //company lat long
                if ($branch_address != '') {
                    $url = "https://maps.google.com/maps/api/geocode/json?key=AIzaSyAP7eFQKBxl3Xh3B60jlMsJHZ2pi2d49eg&address=" . urlencode($branch_address);
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    $responseJson = curl_exec($ch);
                    curl_close($ch);
                    $response = json_decode($responseJson);
                    if ($response->status == 'OK') {
                        $branchLatitude = $response->results[0]->geometry->location->lat;
                        $branchLongitude = $response->results[0]->geometry->location->lng;
                    }
                    $branchLatLong = "$branchLatitude,$branchLongitude";
                }
                //end

                //parent lat long
                if ($parent_address != '') {
                    $url = "https://maps.google.com/maps/api/geocode/json?key=AIzaSyAP7eFQKBxl3Xh3B60jlMsJHZ2pi2d49eg&address=" . urlencode($parent_address);
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    $responseJson = curl_exec($ch);
                    curl_close($ch);
                    $response = json_decode($responseJson);
                    if ($response->status == 'OK') {
                        $pLatitude = $response->results[0]->geometry->location->lat;
                        $pLongitude = $response->results[0]->geometry->location->lng;
                    }
                    $parentLatLong = "$pLatitude,$pLongitude";
                }
                //end
            }
            //End bus route       
            $sql = "SELECT s.id,s.student_name,CONCAT('" . self::SITE_URL . "admin/skins/dist/company-data/student/',s.student_avatar) as student_image,
                    IF(s.section IS NULL, '',s.section) as section,IF(s.roll_number IS NULL, '',s.roll_number) as roll_number,
                    cd.device_imei as imei,cd.bus_route,cd.enrollment_num,cd.driver_name,cd.driver_mobile,cd.bus_number,
                    l.name as class,
                    '$total_present' AS count_present,'$total_absent' AS count_absent,
                    '$branchLatLong' AS branch_LatLong,'$parentLatLong' AS parent_LatLong
                    FROM membership m
                    JOIN student_info s ON((m.mobile=s.father_mobile || m.mobile=s.mother_mobile))
                    LEFT JOIN vw_child_details AS cd ON cd.student_info_id=s.id
                    JOIN list as l ON(l.id=s.class_type_id) 
                    WHERE m.id=$parent_id AND s.company_id=$company_id";
            //end new update (Siddharth 13 Aug)        


            $this->query($sql);

            if ($this->numRows() > 0) {
                $childData = ($this->numRows() == 1) ? [$this->fetchAssoc()] : $this->fetchAssoc();
                $result = ['success' => true, 'data' => $childData, 'msg' => ''];

                // If success everythig is good send header as "OK" and user details
                $this->response($this->json($result), 200);
            }
            $this->response($this->json(['success' => false, "msg" => "Invalid user"]), 200);
        }

        // If invalid inputs "Bad Request" status message and reason
        $error = ['success' => false, 'status' => "Failed", "msg" => "All fields are required"];
        $this->response($this->json($error), 400);
    }

    private function dashboard()
    {

        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }

        if ($this->validate()) {

            $id = $this->_request['parent_id'];
            /*
            updated by Siddharth 19 August 19 
            $sql = $this->query("SELECT a2s.service_id,sr.display_text service_name,sr.description,sr.rate_per_user,CONCAT('" . self::SITE_URL . "/admin/skins/dist/img/service_icon/',sr.display_icon) AS icon
                        FROM `membership` m
                            JOIN company c ON(m.company_id=c.id)
                            JOIN agreement ag ON(c.id=ag.company_id)
                            JOIN agreement2service a2s ON(ag.id=a2s.agreement_id)
                            JOIN service sr ON(a2s.service_id=sr.id)
                        WHERE m.id = $id GROUP BY a2s.service_id");
           */

            $sql = $this->query("SELECT a2s.service_id,sr.display_text service_name,sr.description,sr.rate_per_user,CONCAT('" . self::SITE_URL . "/admin/skins/dist/img/service_icon/',sr.display_icon) AS icon
						FROM `membership` m
					    	JOIN company c ON(m.company_id=c.id)
					        JOIN agreement ag ON(c.parent_company_id=ag.company_id)
					        JOIN agreement2service a2s ON(ag.id=a2s.agreement_id)
					        JOIN service sr ON(a2s.service_id=sr.id)
					    WHERE m.id = $id"); //remove  AND sr.is_active=1 for test 

            if ($this->numRows() > 0) {
                $fetchAssoc = ($this->numRows() == 1) ? [$this->fetchAssoc()] : $this->fetchAssoc();
                $data['services'] = $fetchAssoc;

                /* Fetch parent info */
                //                $sql = $this->query("SELECT s.father_name,s.father_mobile,s.father_email,s.mother_name,s.mother_mobile,s.mother_email FROM `membership` m JOIN student_info s ON((m.email=s.father_email || m.email=s.mother_email) OR (m.mobile=s.father_mobile || m.mobile=s.mother_mobile)) WHERE m.id = $id LIMIT 1");
                $sql = $this->query("SELECT s.father_name,s.father_mobile,s.father_email,s.mother_name,s.mother_mobile,s.mother_email, concat('" . BASE_URL . "admin/skins/dist/company-data/',pc.compan_logo) logo FROM `membership` m JOIN student_info s ON((m.mobile=s.father_mobile || m.mobile=s.mother_mobile)) JOIN company c ON(c.id = s.company_id) JOIN company pc ON(pc.id = c.parent_company_id) WHERE m.id = $id LIMIT 1");
                if ($this->numRows() > 0) {
                    $data['profile'] = $this->fetchAssoc();
                    // $data['profile']['logo'] = 'http://www.eduqik.com/appqik/admin/skins/dist/company-data/1003b91caa5c9f071333ca2b333a2c98.png';
                }

                $result = ['success' => true, 'data' => $data, 'msg' => '', 'version' => '1.3', 'forceclose' => false];

                // If success everythig is good send header as "OK" and user details
                $this->response($this->json($result), 200);
            }
            $this->response($this->json(['success' => false, "msg" => "Invalid user"]), 200); // If no records "No Content" status
        }
    }


    private function tracking()
    {
        //        file_put_contents('request.txt', json_encode($_REQUEST));
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        if ($this->validate()) {
            $id = $this->_request['parent_id'];
            $child_id = $this->_request['child_id'];
            $sql = "SELECT get_pickup_location($child_id) as address";
            $this->query($sql);
            $data = $this->fetchAssoc();

            $child_address = $data['address'];
            // $json = array('status' => 1, 'message' => '', 'data' => []);
            $curl = curl_init();
            $imei = isset($this->_request['imei']) && !empty($this->_request['imei']) ? $this->_request['imei'] : '*';
            $phase = isset($this->_request['phase']) && !empty($this->_request['phase']) ? $this->_request['phase'] : 1;
            /* Route type is mode of bus running on route
              Means bus is running for pickup or drop the students
             * route_type_id 801 is for Pickup and
             * route_type_id 802 is for Drop
             *              */
            $route_type_id = isset($this->_request['route_type']) && !empty($this->_request['route_type']) ? $this->_request['route_type'] : '801';
            $rtArr = array(
                'imei' => $imei,
                'route_type_id' => $route_type_id,
            );
            $route_info = $this->trackobjecttime($rtArr);
            if (!empty($route_info)) {
                curl_setopt_array($curl, array(
                    CURLOPT_URL => "http://mygpstracker.co.in/api/api.php?api=user&ver=1.0&key=BF7E99B87F4E1F1CDBE71D&cmd=OBJECT_GET_LOCATIONS," . $imei,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "GET",
                    CURLOPT_HTTPHEADER => array(
                        "Cache-Control: no-cache",
                    ),
                ));
                $curlData = curl_exec($curl);
                $rows = array();
                //                print_r($curlData);
                if (!empty($curlData)) {
                    /* $data['status'] = 1;
                      $data['message'] = 'Record Found'; */
                    $created_at = time();
                    $apiData = (array) json_decode($curlData);
                    foreach ($apiData as $k => $item) {
                        $d = array(
                            'imei_number' => $k,
                            'object_name' => $item->name,
                            'lat' => $item->lat,
                            'lng' => $item->lng,
                            'dt_server' => $item->dt_server,
                            'dt_tracker' => $item->dt_tracker,
                            'altitude' => $item->altitude,
                            'angle' => $item->angle,
                            'speed' => $item->speed,
                            'loc_valid' => $item->speed,
                            'created_at' => $created_at,
                        );
                        array_push($rows, $d);
                    }
                }
                $latestGeoloc = array();
                if (!empty($rows)) {
                    foreach ($rows as $value) {
                        $dt_server = $value['dt_server'] != "0000-00-00 00:00:00" ? date('Y-m-d H:i:s', strtotime($value['dt_server'])) : "0000-00-00 00:00:00";
                        $dt_tracker = $value['dt_tracker'] != "0000-00-00 00:00:00" ? date('Y-m-d H:i:s', strtotime($value['dt_tracker'])) : "0000-00-00 00:00:00";
                        $imei = $value['imei_number'];
                        $name = $value['object_name'];
                        $lat = $value['lat'];
                        $lag = $value['lng'];
                        $altitude = $value['altitude'];
                        $angle = $value['angle'];
                        $speed = $value['speed'];
                        $loc_valid = $value['loc_valid'];
                        if (!empty($dt_server)) {
                            $this->query("INSERT INTO object_tracking SET imei_number='$imei', object_name='$name',lat='$lat',lng='$lag',dt_server='" . $dt_server . "',
                                  dt_tracker='" . $dt_tracker . "',altitude='$altitude',angle='$angle',speed='$speed',loc_valid='$loc_valid', route_type_id='$route_type_id'");
                        }
                        $latestGeoloc[] = array(
                            'lat' => $lat,
                            'lng' => $lag,
                        );
                    }
                    /* To get list of running path of vehicle by it's assigned imei */
                    $route_list = array();
                    if ($apiData) {
                        //                    $sql = "SELECT lat,lng FROM object_tracking WHERE imei_number='$imei' AND
                        //                        date(created_at)=date(now()) AND time(created_at) BETWEEN '$route_info[route_start_time]' AND '$route_info[route_end_time]'  GROUP BY dt_server,lat,lng;;";
                        if ($phase == 1) {
                            $sql = "SELECT lat,lng FROM object_tracking WHERE imei_number='$imei' AND
                        date(created_at)=date(now()) AND route_type_id='$route_type_id' GROUP BY dt_server,lat,lng;";
                            $this->query($sql);
                            $route_list = $this->fetchAssoc2();
                        } else {
                            $route_list = $latestGeoloc;
                        }
                    }
                    $route_info['pick_loc'] = array('lat' => 0.0, 'lng' => '0.0', 'address' => $child_address);
                    $route_info['drop_loc'] = array('lat' => 0.0, 'lng' => '0.0', 'address' => $child_address);
                    $route_info['eta'] = '0 min';
                    $route_info['etd'] = '0.0 Km';
                    $result = ['success' => true, 'route_info' => $route_info, 'data' => $route_list, 'msg' => ''];
                    $this->response($this->json($result), 200);
                } else {
                    $route_info = $this->getRouteInfoByIMEI($rtArr);
                    $error = ['success' => false, 'pickup_time' => $route_info['pickup'], 'drop_time' => $route_info['drop'],  "msg" => "Opps ! records not found."];
                    $this->response($this->json($error), 200);
                }
            } else {
                $route_info = $this->getRouteInfoByIMEI($rtArr);
                $error = ['success' => false, 'pickup_time' => $route_info['pickup'], 'drop_time' => $route_info['drop'], "msg" => "Your time for pickup or drop is not started yet."];
                $this->response($this->json($error), 200);
            }
        }
    }

    private function trackobjecttime($post = null)
    {
        if (!empty($post['imei'])) {
            /*
             * Getting record as per the IST
             */
            $sql = "SELECT vehicle_start_lat, vehicle_start_lng, route_number, route_start_time, route_end_time FROM vw_route_info WHERE device_imei='$post[imei]' AND route_type_id='$post[route_type_id]' AND (time(now()) >= time(route_start_time) AND time(now()) <= time(route_end_time)) LIMIT 1";
            /*
             * Getting record as per the UTC
             */
            //            $sql = "SELECT vehicle_start_lat, vehicle_start_lng, route_number, route_start_time, route_end_time FROM vw_route_info WHERE device_imei='$post[imei]' AND route_type_id='$post[route_type_id]' /*AND (time(ADDTIME(now(),053000)) >= time(route_start_time) AND time(ADDTIME(now(),053000)) <= time(route_end_time))*/ LIMIT 1";
            $this->query($sql);
            $data = $this->fetchAssoc();
            if (!empty($data)) {
                return $data;
            }

            return false;
        }
    }

    private function getRouteInfoByIMEI($post = null)
    {
        if (!empty($post['imei'])) {
            /*
             * Getting record as per the IST
             */
            $sql = "SELECT vehicle_start_lat, vehicle_start_lng, route_number, TIME_FORMAT(time(route_start_time),'%h:%i:%s %p') route_start_time, TIME_FORMAT(time(route_end_time),'%h:%i:%s %p') route_end_time, LOWER(route_type) route_type FROM vw_route_info WHERE device_imei='$post[imei]' LIMIT 2";
            /*
             * Getting record as per the UTC
             */
            //            $sql = "SELECT vehicle_start_lat, vehicle_start_lng, route_number, route_start_time, route_end_time FROM vw_route_info WHERE device_imei='$post[imei]' AND route_type_id='$post[route_type_id]' /*AND (time(ADDTIME(now(),053000)) >= time(route_start_time) AND time(ADDTIME(now(),053000)) <= time(route_end_time))*/ LIMIT 1";
            $this->query($sql);
            $data = $this->fetchAssoc();
            $row = array();
            if (!empty($data)) {
                foreach ($data as $result) {
                    $row[$result['route_type']] = array(
                        'start_time'    =>  $result['route_start_time'],
                        'end_time'    =>  $result['route_end_time'],
                        'route_number'  =>  $result['route_number']
                    );
                }
                return $row;
            }

            return false;
        }
    }

    private function company()
    {
        // Cross validation if the request method is GET else it will return "Not Acceptable" status
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }

        if ($this->validate()) {

            $id = $this->_request['company_id'];

            $sql = "SELECT
								 c.company_description
								,IF(c.company_website_domain_name IS NULL, '',c.company_website_domain_name) as website
								,cp.contact_person_name
								,cp.mobile
								,cp.email
								,IF(c.compan_logo IS NULL, '',CONCAT('" . BASE_URL . "/admin/skins/dist/company-data/',c.compan_logo)) as compan_logo
							FROM company c
								JOIN contact_person cp ON(c.id=cp.company_id)
							WHERE c.id = '$id' LIMIT 1";
            $this->query($sql);
            // die;

            if ($this->numRows() > 0) {
                $result = $this->fetchAssoc();
                // If success everythig is good send header as "OK" and return list of users in JSON format
                $this->response($this->json($result), 200);
            }
        }

        $this->response('', 204); // If no records "No Content" status
    }

    private function changeStatus()
    {

        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }

        if ($this->validate()) {

            $task_id = $this->_request['task_id'];
            $status = $this->_request['status'];
            $lat = $this->_request['lat'];
            $long = $this->_request['long'];
            $emp_id = $this->_request['emp_id'];

            $this->query("UPDATE task2employee SET status='$status' WHERE id='$task_id' AND 1=1");
            if ($this->resulSet) {

                if ((($lat != '0.0' || $lat != 0.0) && $lat > 0) && (($long != '0.0' || $long != 0.0) && $long > 0)) {

                    $this->query("INSERT INTO employee_track_history SET emp_id='$emp_id', task_id='$task_id', latitude='$lat', longitude='$long'");
                }

                $result = ['success' => true, 'data' => '', 'msg' => 'Task updated successfully'];

                // If success everythig is good send header as "OK" and user details
                $this->response($this->json($result), 200);
            }
        }
        $this->response($this->json(['success' => false, "msg" => "Invalid user"]), 200); // If no records "No Content" status
    }

    private function logout()
    {
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }

        if ($this->validate()) {

            $emp_id = $this->_request['emp_id'];
            $lat = $this->_request['lat'];
            $long = $this->_request['long'];
            $type = $this->_request['lo_flag'];
            $event = null;

            $trackIt = false;
            $event = 'NULL';

            switch ($type) {
                case "1": {
                        $event = 2;
                        $trackIt = true;
                        $result = ['success' => true, 'data' => '', 'msg' => 'Logged out successfully'];
                    }
                    break;

                case "2": {
                        $trackIt = true;
                        $result = ['success' => false, 'data' => '', 'msg' => ''];
                    }
                    break;

                case "3": {
                        $event = 3;
                        $trackIt = true;
                        $this->query("SELECT employee_name,manager_mobile FROM `vw_employee` WHERE id='$emp_id'");
                        $empData = $this->fetchAssoc();

                        $empName = $empData['employee_name'];
                        $supervisor = $empData['manager_mobile'];
                        // $supervisor = $this->gerSupervisor($emp_id)['mobile'];

                        $url = "https://api-alerts.kaleyra.com/v4/?api_key=A491cde3b7f419cf6df49cccf6c&method=sms&message=Emergency!%20Your%20colleague%20$empName%20needs%200and%20his%20traced%20last%20location%20is%27$lat%27%20%27$long%27%20Regards,%20FieldsMate%20Support%20team&to=$supervisor&sender=FsMate";

                        $curl = curl_init();

                        curl_setopt_array($curl, array(
                            CURLOPT_URL => $url,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => "",
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 30,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => "GET",
                            CURLOPT_HTTPHEADER => array(
                                "Cache-Control: no-cache",
                            ),
                        ));

                        $response = curl_exec($curl);
                        $err = curl_error($curl);

                        curl_close($curl);

                        if ($err) {
                            echo "cURL Error #:" . $err;
                        } else {
                            $result = ['success' => false, 'data' => '', 'msg' => 'SOS Message sent successfully'];
                        }
                    }
                    break;
            }
            $lat1 = (int) $lat;
            if (!empty($lat1)) {
                // if ($trackIt && (($lat != '0.0' || $lat != 0.0) && $lat > 0) && (($long != '0.0' || $long != 0.0) && $long > 0)) {

                $sql = $this->query("SELECT * FROM `employee_track_history` WHERE emp_id=$emp_id AND latitude='$lat' AND longitude = '$long'");

                if (!$this->numRows()) {
                    $sql = $this->query("INSERT INTO employee_track_history SET emp_id='$emp_id', latitude='$lat', longitude='$long', event=$event");
                }
            }

            // If success everythig is good send header as "OK" and user details
            $this->response($this->json($result), 200);
        }
    }

    private function attendance()
    {
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }

        if ($this->validate()) {

            $id = $this->_request['child_id'];
            $sql = "SELECT s.student_rfid, d.device_serial_number FROM student_info s JOIN device d ON(d.id=s.biometric_device_id) WHERE s.id = $id";
            $this->query($sql);

            if ($this->numRows() > 0) {
                $dinfo = $this->fetchAssoc();
                $rfid = $dinfo['student_rfid'];
                $srlNum = $dinfo['device_serial_number'];
                /* Connecting to ebios-server */
                $this->biosDbConnect();
                $sql = "SELECT dl.* FROM deviceLogs_" . date('n_Y') . " dl JOIN devices d ON(d.DeviceId=dl.DeviceId) WHERE dl.UserId = $rfid AND d.SerialNumber='" . $srlNum . "'";
                $this->query($sql, $this->biosdb);

                if ($this->numRows() > 0) {
                    $attendanceData = $this->fetchAssoc2();
                    $yearData = array();
                    $curMonth = array();
                    for ($m = 1; $m <= 12; $m++) {

                        $days = cal_days_in_month(CAL_GREGORIAN, $m, date('Y'));
                        for ($d = 1; $d <= $days; $d++) {
                            $date = sprintf("%04d-%02d-%02d", date('Y'), $m, $d);
                            if ($m < date('m')) {
                                $yearData[] = ['date' => date('d-m-Y', strtotime($date)), 'in' => '', 'out' => ''];
                            }

                            if ($m == date('m')) {
                                if (strtotime($date) <= strtotime(date('Y-m-d', time()))) {
                                    $curMonth[] = ['date' => date('d M Y', strtotime($date)), 'in' => '', 'out' => ''];
                                    $addInYear[] = array('date' => date('d-m-Y', strtotime($date)), 'in' => '', 'out' => '');
                                }
                            }
                        }
                    }
                    $attnds['year'] = $yearData;

                    $present_arr = array();

                    foreach ($attendanceData as $attendance) {
                        $day = date('j', strtotime($attendance['LogDate']));
                        if (!in_array($day, $present_arr)) {
                            array_push($present_arr, $day);
                        }
                        $present_inout[date("d M Y", strtotime($attendance['LogDate']))][] = date("H:i A", strtotime($attendance['LogDate']));
                    }

                    foreach ($present_inout as $d => $inout) {

                        $curdate = explode(' ', $d)[0] - 1;
                        if (is_array($inout) && count($inout) >= 2) {
                            $attnd = array('date' => $d, 'in' => $present_inout[$d][0], 'out' => end($present_inout[$d]));
                            $inYear = array('date' => date('d-m-Y', strtotime($d)), 'in' => $present_inout[$d][0], 'out' => end($present_inout[$d]));
                        } else {
                            $in = ($present_inout[$d][0]) ? $present_inout[$d][0] : '';
                            $out = (count($inout) > 1) ? end($present_inout[$d]) : '';

                            $attnd = array('date' => $d, 'in' => $in, 'out' => $out);
                            $inYear = array('date' => date('d-m-Y', strtotime($d)), 'in' => $in, 'out' => $out);
                        }

                        // $attnds['month'][] = $attnd;
                        $curMonth[$curdate] = $attnd;
                        $addInYear[$curdate] = $inYear;
                    }

                    $attnds['month'] = $curMonth;
                    $attnds['year'] = array_merge($attnds['year'], $addInYear);

                    $result = ['success' => true, 'data' => $attnds, 'msg' => ''];

                    // If success everythig is good send header as "OK" and user details
                    $this->response($this->json($result), 200);
                } else {

                    $this->response($this->json(['success' => false, 'data' => '', 'msg' => 'No records found']), 200);
                }
            } else {
                $result = ['success' => true, 'data' => $attnds, 'msg' => 'No records found'];

                // If success everythig is good send header as "OK" and user details
                $this->response($this->json($result), 200);
            }
        }
    }

    private function otp()
    {

        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }

        if ($this->validate()) {

            $mobile = $this->_request['mobile'];
            $type = $this->_request['type'];

            switch ($type) {

                case 'send':

                    $json = array('status' => false, 'message' => 'OTP couldn\'t generated.');
                    $time = time();
                    $len = strlen($time);
                    $otp = substr($time, $len - 6, $len);


                    $dt = array(
                        'mobile' => $mobile,
                        'otp' => $otp,
                    );

                    $message = "Use OTP $otp to verify your mobile number. Keep this OTP to yourself for account safety.";

                    $curl = curl_init();
                    // JSON Format
                    $url = 'https://api-alerts.kaleyra.com/v4/?api_key=A491cded7dea73cf6df49cccf6c&method=sms&message=' . $message . 'mobile&to=' . $mobile . '&sender=FsMate';
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => "",
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_SSL_VERIFYHOST => 0,
                        CURLOPT_SSL_VERIFYPEER => 0,
                        CURLOPT_FOLLOWLOCATION => false,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => "POST",
                    ));
                    $result = curl_exec($curl);
                    $result = json_decode($result);
                    $err = curl_error($curl);
                    curl_close($curl);

                    if ($err) {
                        $success = array('status' => false, "msg" => "cURL Error #:" . $err);
                    } else {
                        if ($result->status != "") {
                            $this->query("DELETE FROM tbl_otp WHERE mobile=$mobile");
                            $this->query("INSERT INTO tbl_otp SET mobile=$mobile, otp='" . $otp . "'");
                            if (!empty($this->affectedRows())) {
                                $success = array('status' => true, "msg" => "OTP sent on your mobile number");
                            }
                        }
                    }
                    break;
                case 'verifyotp':
                    $otp = $this->_request['otp'];
                    $success = array('status' => false, 'message' => 'Given OTP doesn\'t match.');
                    $this->query("SELECT mobile FROM tbl_otp WHERE mobile=$mobile AND otp='" . $otp . "'");
                    if ($this->numRows()) {
                        $success = array('status' => true, "msg" => "OTP verified");
                        $this->query("DELETE FROM tbl_otp WHERE mobile=$mobile");
                    }
            }

            $this->response($this->json($success), 200);
        }
    }

    private function comments()
    {
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }

        if ($this->validate()) {
            $root = 'http://' . $_SERVER['HTTP_HOST'] . '/admin/skins/comment-attachment';
            $emp_id = $this->_request['emp_id'];
            $task_id = $this->_request['task_id'];

            $sql = $this->query("SELECT *,(case when (attechment IS NOT NULL) then CONCAT_WS('/','$root',task_id,attechment) else NULL end ) as attechment, DATE_FORMAT(created_at,'%d-%m-%Y %h:%i %p') as created_at,
						(CASE
							WHEN (SUBSTRING_INDEX(attechment,'.',-1) = 'jpg') THEN 1
							WHEN (SUBSTRING_INDEX(attechment,'.',-1) = 'png') THEN 2
							WHEN (SUBSTRING_INDEX(attechment,'.',-1) = 'pdf') THEN 3 END) as ext
						FROM `taskcomment` WHERE 1=1 AND `emp_id` = $emp_id AND task_id=$task_id");
            if ($this->numRows() > 0) {
                $fetchAssoc = ($this->numRows() == 1) ? [$this->fetchAssoc()] : $this->fetchAssoc();
                $result = ['success' => true, 'data' => $fetchAssoc, 'msg' => ''];

                // If success everythig is good send header as "OK" and user details
                $this->response($this->json($result), 200);
            } else {
                $this->response($this->json(['success' => false, 'data' => '', 'msg' => 'No records found']), 200);
            }
        }
    }

    private function newComment()
    {
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }

        if ($this->validate(['attached', 'ext'])) {

            $comment = $this->_request['comment'];
            $taskid = $this->_request['task_id'];
            $emp_id = $this->_request['emp_id'];
            $attached = $this->_request['attached'];
            $filename = "NULL";

            if ($attached != '' && $attached != null) {
                $file_ext = $this->_request['ext'];

                if (!is_dir('../admin/skins/comment-attachment/' . $taskid)) {
                    mkdir('../admin/skins/comment-attachment/' . $taskid, 0777, true);
                }

                $taskDir = '../admin/skins/comment-attachment/' . $taskid . '/';
                $uniqueFileName = md5(uniqid(rand(), true)) . '.' . $file_ext;
                $uploadPath = $taskDir . $uniqueFileName;

                $filename = "'" . $uniqueFileName . "'";

                switch ($file_ext) {
                    case "pdf":
                        $pdf_decoded = base64_decode($attached);
                        $pdf = fopen($uploadPath, 'w');
                        fwrite($pdf, $pdf_decoded);
                        fclose($pdf);
                        break;

                    default:
                        file_put_contents($uploadPath, base64_decode($attached));
                        break;
                }
            }

            $this->query("INSERT INTO taskcomment SET task_id='$taskid', emp_id='$emp_id', comment='$comment',attechment=$filename");

            if ($this->resulSet) {
                $result = ['success' => true, 'data' => '', 'msg' => 'Comment added successfully'];

                // If success everythig is good send header as "OK" and user details
                $this->response($this->json($result), 200);
            }
        }

        // If invalid inputs "Bad Request" status message and reason
        $error = ['success' => false, 'status' => "Failed", "msg" => "All fields are required"];
        $this->response($this->json($error), 200);
    }

    private function taskList()
    {
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }

        if ($this->validate()) {

            $emp_id = $this->_request['emp_id'];

            $sql = "SELECT t2e.id,t.id as task_id,t2e.employee_id as emp_id,1 as project_id,t2e.comment,t.task_title as title, t.description as task_desc,'25-05-2019' as task_date,0 as priority,'noida' as location, t2e.comment,t2e.status, DATE_FORMAT(t2e.created_date, '%d %b %Y %h:%i %p') as created_at FROM task2employee t2e JOIN task t ON(t2e.task_id=t.id) WHERE 1=1 AND t2e.employee_id='$emp_id'";

            $this->query($sql);
            if ($this->numRows() > 0) {

                $fetchAssoc = ($this->numRows() == 1) ? [$this->fetchAssoc()] : $this->fetchAssoc();
                $result = ['success' => true, 'data' => $fetchAssoc, 'msg' => ''];

                // If success everythig is good send header as "OK" and user details
                $this->response($this->json($result), 200);
            }
            $this->response($this->json(['success' => false, "msg" => "No task assigned"]), 200); // If no records "No Content" status
        }
    }


    /*
    public function elearning() {
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $sql = "SELECT e.company_name, e.company_address, e.content, e.subject, e.class, e.calendar_year, e.video_url, 
            CASE WHEN e.exercise_file is not null then concat('".BASE_URL."admin/skins/dist/company-data/elearning/',e.exercise_file) ELSE '' END AS exercise_file 
            , SUBSTRING_INDEX(e.exercise_file, '.', -1) exercise_file_ext FROM vw_elearning e 
            JOIN student_info s ON(s.class_type_id=e.class_type_id) JOIN membership m ON(m.mobile=s.father_mobile || m.mobile=s.mother_mobile) WHERE 1 
            AND e.status=1 ";
        
        if (isset($this->_request['company_id']) && !empty($this->_request['company_id'])) {
            $sql .= " AND e.company_id='" . $this->_request['company_id'] . "'";
        }
        if (isset($this->_request['parent_id']) && !empty($this->_request['parent_id'])) {
            $sql .= " AND m.id='" . $this->_request['parent_id'] . "'";
        }
        $sql .= " AND m.user_type_id=103";
        
        $this->query($sql);

        if ($this->numRows() > 0) {

            $fetchAssoc = ($this->numRows() == 1) ? [$this->fetchAssoc()] : $this->fetchAssoc();
            $result = ['success' => true, 'data' => $fetchAssoc, 'msg' => ''];

            // If success everythig is good send header as "OK" and user details
            $this->response($this->json($result), 200);
        }
        $this->response($this->json(['success' => false, "msg" => "No records found."]), 200); // If no records "No Content" status
    }
    */

    //created by siddharth
    //date 06-08-2019
    //api for creative notifications
    public function elearning()
    {
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        if ($_REQUEST['company_id'] != '' && $_REQUEST['parent_id'] != '') {
            $sql = "SELECT 'Creative' as company_address, b.title AS subject,b.content,l.name class, l.name AS company_name,
                    CASE WHEN b.broadcast_file !='' THEN CONCAT('" . BASE_URL . "admin/skins/dist/company-data/broadcast_group/',b.broadcast_file) ELSE '' END AS exercise_file,
                    SUBSTRING_INDEX(b.broadcast_file, '.', -1) exercise_file_ext, l2.name AS calendar_year, '' as video_url
                    FROM broadcast_group_member bg 
                    JOIN broadcast_notification b ON (bg.id = b.ref_id)
                    JOIN membership m ON (bg.ref_id = m.id)
                    JOIN list l ON (b.class_type_id = l.id)
                    LEFT JOIN list l2 ON (b.calendar_year_type_id = l2.id)
                    WHERE b.action_type=5 ";
            if (isset($this->_request['company_id']) && !empty($this->_request['company_id'])) {
                $sql .= " AND bg.company_id='" . $this->_request['company_id'] . "'";
            }
            if (isset($this->_request['parent_id']) && !empty($this->_request['parent_id'])) {
                $sql .= " AND bg.ref_id='" . $this->_request['parent_id'] . "'";
            }
            $this->query($sql);
            if ($this->numRows() > 0) {
                $fetchAssoc = ($this->numRows() == 1) ? [$this->fetchAssoc()] : $this->fetchAssoc();
                $result = ['success' => true, 'data' => $fetchAssoc, 'msg' => ''];
                // If success everythig is good send header as "OK" and user details
                $this->response($this->json($result), 200);
            }
            $this->response($this->json(['success' => false, "msg" => "Empty"]), 200); // If no records "No Content" status
        } else {
            $this->response($this->json(['success' => false, "msg" => "All fields are required."]), 200);
        }
    }
    //end



    public function syllabus()
    {
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        if ($_REQUEST['company_id'] != '' && $_REQUEST['parent_id'] != '') {
            $compnay_id = $_REQUEST['company_id'];
            $parent_id  = $_REQUEST['parent_id'];
            $sql = "SELECT sl.company_name, sl.company_address, sl.content, sl.subject, sl.class, sl.calendar_year, "
                . "CASE WHEN sl.syllabus_file is not null then concat('" . BASE_URL . "admin/skins/dist/company-data/syllabus/',sl.syllabus_file) ELSE '' END AS syllabus_file "
                . ", SUBSTRING_INDEX(sl.syllabus_file, '.', -1) syllabus_file_ext FROM vw_syllabus sl
            JOIN student_info s ON(s.class_type_id=sl.class_type_id) 
            JOIN membership m ON(m.mobile=s.father_mobile || m.mobile=s.mother_mobile) WHERE 1 
            AND sl.status=1 ";

            if (isset($compnay_id) && !empty($compnay_id)) {
                $sql .= " AND sl.company_id='" . $compnay_id . "'";
            }
            if (isset($parent_id) && !empty($parent_id)) {
                $sql .= " AND m.id='" . $parent_id . "'";
            }
            $sql .= " AND m.user_type_id=103";
            $this->query($sql);
            if ($this->numRows() > 0) {
                $fetchAssoc = ($this->numRows() == 1) ? [$this->fetchAssoc()] : $this->fetchAssoc();
                $result = ['success' => true, 'data' => $fetchAssoc, 'msg' => ''];
                // If success everythig is good send header as "OK" and user details
                $this->response($this->json($result), 200);
            }
            $this->response($this->json(['success' => false, "msg" => "Records not found"]), 200); // If no records "No Content" status
        } else {
            $this->response($this->json(['success' => false, "msg" => "All fields are required."]), 200);
        }
    }

    //created by siddharth
    //date 04-06-2019
    //api for notice board

    public function notice()
    {
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        $sql = "SELECT  n.name, (SELECT GROUP_CONCAT(name) FROM list WHERE FIND_IN_SET(id, n.class)) class,n.title,n.content,n.calendar_year AS calender_type,n.company_address AS address,
                CASE WHEN n.upload_notice !='' THEN CONCAT('" . BASE_URL . "admin/skins/dist/company-data/noticeboard/',n.upload_notice) ELSE '' END AS notice_file 
                FROM vw_noticeboard n 
                JOIN student_info s ON (s.company_id = n.company_id)
                JOIN membership m ON (m.mobile = s.father_mobile AND m.company_id = n.company_id)
                WHERE 1";
        if (isset($this->_request['parent_id']) && !empty($this->_request['parent_id'])) {
            $sql .= " AND m.id='" . $this->_request['parent_id'] . "'";
        }
        if (isset($this->_request['company_id']) && !empty($this->_request['company_id'])) {
            $sql .= " AND n.company_id='" . $this->_request['company_id'] . "'";
        }
        $this->query($sql);
        if ($this->numRows() > 0) {

            $fetchAssoc = ($this->numRows() == 1) ? [$this->fetchAssoc()] : $this->fetchAssoc();
            $result = ['success' => true, 'data' => $fetchAssoc, 'msg' => ''];

            // If success everythig is good send header as "OK" and user details
            $this->response($this->json($result), 200);
        }
        $this->response($this->json(['success' => false, "msg" => "Records not found."]), 200); // If no records "No Content" status
    }
    //end notice board

    //created by siddharth
    //date 04-06-2019
    //api for notice board
    public function notification()
    {
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }

        /**
        //change notification date 19-11-2019
        //siddharth

        $sql = "SELECT s.class_type_id FROM student_info s 
                JOIN membership m ON(m.company_id=s.company_id) 
                WHERE m.id='" . $this->_request['parent_id'] . "' GROUP by s.class_type_id";
        $this->query($sql);
        if ($this->numRows() > 0) {
            $cond = "";
            foreach($this->fetchAssoc() as $class_type_id) {
                if(empty($cond)) {
                    $cond = "AND (n.class REGEXP '".$class_type_id."'";
                } 
                else {
                    $cond .= " OR n.class REGEXP '".$class_type_id."'";
                }
            }
            if(!empty($cond))
            $cond .= ")";
        }
        $sql = "SELECT n.title,n.content,CASE WHEN n.action_type IS NOT NULL THEN n.action_type ELSE '' END AS action, 
                CASE WHEN n.broadcast_file !='' THEN CONCAT('" . BASE_URL . "admin/skins/dist/company-data/broadcast/',n.broadcast_file) ELSE '' END AS image
                FROM vw_notification n 
                JOIN membership m ON(m.company_id=n.company_id) WHERE 1 $cond AND m.id='".$this->_request['parent_id']."'";
        $this->query($sql);
        if ($this->numRows() > 0) {

            $fetchAssoc = ($this->numRows() == 1) ? [$this->fetchAssoc()] : $this->fetchAssoc();
            $result = ['success' => true, 'data' => $fetchAssoc, 'msg' => ''];

            // If success everythig is good send header as "OK" and user details
            $this->response($this->json($result), 200);
        }
        $this->response($this->json(['success' => false, "msg" => "Nofications not found."]), 200); // If no records "No Content" status
         **/

        if ($_REQUEST['company_id'] != '' && $_REQUEST['parent_id'] != '') {
            $sql = "SELECT  b.title,b.content,l.name AS class_name,
                    CASE WHEN b.broadcast_file !='' THEN CONCAT('" . BASE_URL . "admin/skins/dist/company-data/broadcast_group/',b.broadcast_file) ELSE '' END AS broadcast_file 
                    FROM broadcast_group_member bg 
                    JOIN broadcast_notification b ON (bg.id = b.ref_id)
                    JOIN membership m ON (bg.ref_id = m.id)
                    JOIN list l ON (b.class_type_id = l.id)
                    WHERE b.action_type=2 ";
            if (isset($this->_request['company_id']) && !empty($this->_request['company_id'])) {
                $sql .= " AND bg.company_id='" . $this->_request['company_id'] . "'";
            }
            if (isset($this->_request['parent_id']) && !empty($this->_request['parent_id'])) {
                $sql .= " AND bg.ref_id='" . $this->_request['parent_id'] . "'";
            }
            $this->query($sql);
            if ($this->numRows() > 0) {
                $fetchAssoc = ($this->numRows() == 1) ? [$this->fetchAssoc()] : $this->fetchAssoc();
                $result = ['success' => true, 'data' => $fetchAssoc, 'msg' => ''];
                // If success everythig is good send header as "OK" and user details
                $this->response($this->json($result), 200);
            }
            $this->response($this->json(['success' => false, "msg" => "Empty"]), 200); // If no records "No Content" status
        } else {
            $this->response($this->json(['success' => false, "msg" => "All fields are required."]), 200);
        }
    }
    //end notifications


    //created by siddharth
    //date 05-08-2019
    //api for fee notifications
    public function feeNotification()
    {
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        if ($_REQUEST['company_id'] != '' && $_REQUEST['parent_id'] != '') {
            $sql = "SELECT  b.title,b.content,l.name AS class_name,
                    CASE WHEN b.broadcast_file !='' THEN CONCAT('" . BASE_URL . "admin/skins/dist/company-data/broadcast_group/',b.broadcast_file) ELSE '' END AS broadcast_file 
                    FROM broadcast_group_member bg 
                    JOIN broadcast_notification b ON (bg.id = b.ref_id)
                    JOIN membership m ON (bg.ref_id = m.id)
                    JOIN list l ON (b.class_type_id = l.id)
                    WHERE b.action_type=7 ";
            if (isset($this->_request['company_id']) && !empty($this->_request['company_id'])) {
                $sql .= " AND bg.company_id='" . $this->_request['company_id'] . "'";
            }
            if (isset($this->_request['parent_id']) && !empty($this->_request['parent_id'])) {
                $sql .= " AND bg.ref_id='" . $this->_request['parent_id'] . "'";
            }
            $this->query($sql);
            if ($this->numRows() > 0) {
                $fetchAssoc = ($this->numRows() == 1) ? [$this->fetchAssoc()] : $this->fetchAssoc();
                $result = ['success' => true, 'data' => $fetchAssoc, 'msg' => ''];
                // If success everythig is good send header as "OK" and user details
                $this->response($this->json($result), 200);
            }
            $this->response($this->json(['success' => false, "msg" => "Empty"]), 200); // If no records "No Content" status
        } else {
            $this->response($this->json(['success' => false, "msg" => "All fields are required."]), 200);
        }
    }
    //end

    //created by siddharth
    //date 06-08-2019
    //api for event notifications
    public function eventNotification()
    {
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }
        if ($_REQUEST['company_id'] != '' && $_REQUEST['parent_id'] != '') {
            $sql = "SELECT  b.title,b.content,l.name AS class_name,
                    CASE WHEN b.broadcast_file !='' THEN CONCAT('" . BASE_URL . "admin/skins/dist/company-data/broadcast_group/',b.broadcast_file) ELSE '' END AS broadcast_file 
                    FROM broadcast_group_member bg 
                    JOIN broadcast_notification b ON (bg.id = b.ref_id)
                    JOIN membership m ON (bg.ref_id = m.id)
                    JOIN list l ON (b.class_type_id = l.id)
                    WHERE b.action_type=8 ";
            if (isset($this->_request['company_id']) && !empty($this->_request['company_id'])) {
                $sql .= " AND bg.company_id='" . $this->_request['company_id'] . "'";
            }
            if (isset($this->_request['parent_id']) && !empty($this->_request['parent_id'])) {
                $sql .= " AND bg.ref_id='" . $this->_request['parent_id'] . "'";
            }
            $this->query($sql);
            if ($this->numRows() > 0) {
                $fetchAssoc = ($this->numRows() == 1) ? [$this->fetchAssoc()] : $this->fetchAssoc();
                $result = ['success' => true, 'data' => $fetchAssoc, 'msg' => ''];
                // If success everythig is good send header as "OK" and user details
                $this->response($this->json($result), 200);
            }
            $this->response($this->json(['success' => false, "msg" => "Empty"]), 200); // If no records "No Content" status
        } else {
            $this->response($this->json(['success' => false, "msg" => "All fields are required."]), 200);
        }
    }
    //end

    //admin gps tracking
    private function adminTracking($imei = NULL, $route_type_id = NULL)
    {
        $imei       = !empty($imei) ? $imei : $_REQUEST['imei'];
        $route_type_id = !empty($route_type_id) ? $route_type_id : $_REQUEST['route_type_id'];
        $curl = curl_init();
        $route_info = $this->adminTrackObjectTime($imei, $route_type_id);
        $latestGeoloc = array();
        $route_list = array();
        $sql = "SELECT lat,lng FROM object_tracking
                                    WHERE imei_number='$imei' AND
                                    date(created_at)=date(now()) AND route_type_id='$route_type_id' GROUP BY dt_server,lat,lng;";
        $this->query($sql);
        $route_list = $this->fetchAssoc2();

        $route_info['pick_loc'] = array('lat' => 0.0, 'lng' => '0.0');
        $route_info['drop_loc'] = array('lat' => 0.0, 'lng' => '0.0');
        $route_info['eta'] = '0 min';
        $route_info['etd'] = '0.0 Km';
        $result = ['success' => true, 'route_info' => $route_info, 'data' => $route_list, 'msg' => ''];
        $this->response($this->json($result), 200);
    }

    private function adminTrackObjectTime($imei, $route_type_id)
    {
        $sql = "SELECT vehicle_start_lat, vehicle_start_lng, route_number, route_start_time, route_end_time FROM vw_route_info WHERE device_imei='$imei' AND route_type_id='$route_type_id' AND (time(now()) >= time(route_start_time) AND time(now()) <= time(route_end_time)) LIMIT 1";
        $this->query($sql);
        $data = $this->fetchAssoc();
        if (!empty($data)) {
            return $data;
        }
        return false;
    }
    //end admin tracker

    public function __destruct()
    {
        mysqli_close($this->db);
    }
}


// Initiiate Library

$api = new API;
$api->processApi();