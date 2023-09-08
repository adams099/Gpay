<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cards_model extends CI_Model
{

    // Controller: client/cards/index
    // Fungsi: ambil semua data card
    function get_all_cards($num = NULL, $offset = NULL)
    {
        if(isset($num) && isset($offset)) $this->db->limit($num, $offset);
        return $this->db
            ->get('card');
    }

    // Controller:
    // Fungsi: ambil data card berdasarkan card no
    function get_card_by_id($id)
    {
        return $this->db
            ->join('customer','customer.id = card.customer_id')
            ->get_where('card', array('card_no' => $id));
    }

    function get_search_result($search_param, $num = NULL, $offset = NULL)
    {
        $this->db->select('*');

        if(!empty($search_param['card_no'])){
            $this->db->like('card.card_no', $search_param['card_no'], 'both');
        }

        if(!empty($search_param['username'])){
            $this->db->group_start();
                $this->db->like('LOWER(customer.first_name)', $search_param['username'], 'both');
                $this->db->or_like('LOWER(customer.sure_name)', $search_param['username'], 'both');
            $this->db->group_end();
        }

        if(!empty($search_param['dob'])){
            $this->db->where('customer.birth_date', $search_param['dob']);
        }

        if(!empty($search_param['zip_code'])){
            $this->db->group_start();
                $this->db->like('customer.mailing_zipcode', $search_param['zip_code'], 'both');
                $this->db->or_like('customer.idcard_zipcode', $search_param['zip_code'], 'both');
            $this->db->group_end();
        }

        if(!empty($search_param['city'])){
            $this->db->group_start();
                $this->db->like('LOWER(customer.mailing_city)', $search_param['city'], 'both');
                $this->db->or_like('LOWER(customer.idcard_city)', $search_param['city'], 'both');
            $this->db->group_end();
        }

        if(!empty($search_param['address'])){
            $this->db->group_start();
                $this->db->like('LOWER(customer.mailing_address)', $search_param['address'], 'both');
                $this->db->or_like('LOWER(customer.idcard_address)', $search_param['address'], 'both');
            $this->db->group_end();
        }

        if(!empty($search_param['card_status'])){
            $this->db->where('card.card_status_code', $search_param['card_status']);
        }

        if(!empty($search_param['phone'])){
            $this->db->like('customer.mobile_phone_no', $search_param['phone'], 'both');
        }

        if(isset($num) && isset($offset)) $this->db->limit($num, $offset);
        return $this->db
            ->select('
                (select dsc from master_std where master_std.code = card.card_status_code and master_std.data_group = \'card_status\' limit 1) as card_status,
                (select dsc from master_std where master_std.code = card.customer_type_code and master_std.data_group = \'customer_type\' limit 1) as customer_type
            ')
            ->join('customer','card.customer_id = customer.id')
            ->order_by('card_status_code','asc')
            ->get('card');
    }

    public function get_all_status_card(){
        return $this->db
            ->where('cast(code as integer) <',100)
            ->where('data_group','card_status')
            ->order_by('dsc', 'asc')
            ->get('master_std');
    }

    //============================== Detail ==============================//

    public function get_active_card($customer_id){
        return $this->db
            ->where('card_status_code','1')
            ->where('customer_id',$customer_id)
            ->get('card');
    }

    public function detail_card($card_no){
        return $this->db
            ->select('
                card_history.log_datetime,
                (select dsc from master_std where master_std.code = card_history.card_status_before and master_std.data_group = \'card_status\' limit 1) as card_status_before_detail,
                (select dsc from master_std where master_std.code = card_history.card_status_after and master_std.data_group = \'card_status\' limit 1) as card_status_after_detail
            ')
            ->where('card_no',$card_no)
            ->order_by('log_datetime','asc')
            ->limit($_POST['length'],$_POST['start'])
            ->get('card_history');
    }

    public function detail_card_count($card_no){
        return $this->db
            ->where('card_no',$card_no)
            ->get('card_history');
    }

    public function suspend_status($card_no){
        $data = $this->get_card_by_id($card_no)->row();

        $this->db->where('card_no', $card_no);
        $this->db->update('card', array(
            'card_status_code'=>'2'
        ));

        $this->db->insert('card_history', array(
            'card_no'=>$card_no,
            'card_status_before'=>$data->card_status_code,
            'card_status_after'=>'2',
            'log_datetime'=> date("Y-m-d H:i:s")
        ));
    }

    public function destroy_status($card_no){
        $data = $this->get_card_by_id($card_no)->row();

        $this->db->where('card_no', $card_no);
        $this->db->update('card', array(
            'card_status_code'=>'3'
        ));

        $this->db->insert('card_history', array(
            'card_no'=>$card_no,
            'card_status_before'=>$data->card_status_code,
            'card_status_after'=>'3',
            'log_datetime'=> date("Y-m-d H:i:s")
        ));
    }

    public function active_status($card_no){
        $data = $this->get_card_by_id($card_no)->row();

        $this->db->where('card_no', $card_no);
        $this->db->update('card', array(
            'card_status_code'=>'1'
        ));

        $this->db->insert('card_history', array(
            'card_no'=>$card_no,
            'card_status_before'=>$data->card_status_code,
            'card_status_after'=>'1',
            'log_datetime'=> date("Y-m-d H:i:s")
        ));
    }

    public function upgrade_required_status($card_no){
        $data = $this->get_card_by_id($card_no)->row();

        $this->db->where('card_no', $card_no);
        $this->db->update('card', array(
            'card_status_code'=>'5'
        ));

        $this->db->insert('card_history', array(
            'card_no'=>$card_no,
            'card_status_before'=>$data->card_status_code,
            'card_status_after'=>'5',
            'log_datetime'=> date("Y-m-d H:i:s")
        ));
    }

    public function get_card_detail_by_card_no($card_no){
        $this->db->select('card.card_no, m1.dsc as customer_type_code, m2.dsc as card_status_code, card.dt_create, card.customer_id');
        $this->db->join('master_std as m1', 'm1.data_group = \'customer_type\' and card.customer_type_code = m1.code', 'LEFT');
		$this->db->join('master_std as m2', 'm2.data_group = \'card_status\' and card.card_status_code = m2.code', 'LEFT');
        return $this->db->get_where('card', array('card_no' => $card_no));
    }

    //============================== INSERT QUERY ==============================//

	public function insert_fullservice_card($card_no, $customer_id)
	{
        $this->db->insert('card', array(
            'card_no' => $card_no,
			'customer_type_code' => '2',
			'card_status_code' => '1',
			'customer_id' => $customer_id
        ));
    }
    
    public function reactivate_fullservice_card($card_no, $customer_id){
        $update_data = array('card_status_code'=>'1');
        $this->db->where(array('customer_id' => $customer_id, 'card_no' => $card_no));
        $this->db->update('card', $update_data);
	}
}