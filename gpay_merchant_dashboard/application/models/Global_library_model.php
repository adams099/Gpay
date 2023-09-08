<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Global_library_model extends CI_Model
{
    public function insert_sprint_otp_sms_log($mbl_phone_no, $headers, $msg, $retval)
    {
        $sql = "insert into sprint_otp_sms_log(msisdn,headers,message,return_value,dt_create)
                values (?,?,?,?,CURRENT_TIMESTAMP)";
        $params = [$mbl_phone_no, $headers, $msg, $retval];
        $this->db->query($sql, $params);
    }

    public function insert_jatis_otp_sms_log($mbl_phone_no, $msg, $retval)
    {
        $sql = "insert into jatis_otp_sms_log(msisdn,message,return_value,dt_create)
                values (?,?,?,CURRENT_TIMESTAMP)";
        $params = [$mbl_phone_no, $msg, $retval];
        $this->db->query($sql, $params);
    }
}
