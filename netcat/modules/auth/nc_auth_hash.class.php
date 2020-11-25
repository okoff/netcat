<?php
/* $Id: nc_auth_hash.class.php 3827 2010-06-18 14:20:18Z denis $ */

/**
 * class nc_auth_hash
 *
 * @category nc_auth
 */
class nc_auth_hash {
  protected   $db;
  protected   $hash;
  protected   $key, $create_session, $delete_hash, $expire_hash;

  
  /**
   * Construct
   *
   */
  protected  function __construct () {
    global $db;
    $this->db = $db;
  }
  
  /**
  * Instance self object method
  *
  * @return self object
  */
  public static function get_object () {
    // call as static
    static $storage;
    // check inited object
    if ( !isset($storage) ) {
      // init object
      $storage = new self();
    }    
    // return object
    return is_object($storage) ? $storage : false;
  }
  
  /**
   * ������� �������������� ������������ �� ����
   *
   * @param str hash
   * @return int user id
   */
  public function authorize_by_hash ( $hash ) {
    
    if ( !$hash ) return 0;
    
    $this->hash = $hash;

    // ������� �� �������� ����� � �� ����������� ����������� �� �������� 
    if (  $this->check_expire() && $this->check_availability() ) { 
      // �������� �� ������������� ���� � ����������� �������������� � ������ �������
      if ( ($user_id = $this->get_user_by_hash()) && $this->check_sub($user_id) ) {
        // �����������, �����������
        $id = Authorize( $user_id, 'authorize', 0, 'hash', 0, $this->create_session);
      }
    }

    // ��������, ��� ����� �������
    $this->_attempt_to_delete ();
    
    return $id;
  }
  
  
  /**
   * ������� ������ ��� �����������
   *
   * @param int user_id
   * @param bool delete_hash - ����� �� ������� ��� (����������� ���������� �������������)
   * @param int expir - ����� ����� ���� ( � ����� )
   * @return str hash
   */
  public function create_auth_hash ( $user_id, $delete_hash = null, $expire = null ) {
    // �������� ����
    $hash = $this->_make_hash ( $user_id, 1, $delete_hash, $expire);  
    // �������� � ��
    $this->db->rows_affected = 0;
    $this->db->query("UPDATE `User` SET `Auth_Hash` = '".$this->db->escape($hash)."' WHERE `User_ID` = '".intval($user_id)."'");
   
    if ( $this->db->rows_affected ) return $hash;
    
    return false;
  }
  
  /**
   * ������ ��� �� ������������
   *
   * @param int $user_id
   * @return str
   */
  public function get_hash_by_user ($user_id) {
    return $this->db->get_var("SELECT `Auth_Hash` FROM `User` WHERE `User_ID` = '".intval($user_id)."'");  
  }
  
  /**
   * ������� ��� �� ������������
   *
   * @param int $user_id
   */
  public function delete_auth_hash ( $user_id ) {
    $this->db->query("UPDATE `User` SET `Auth_Hash` = '' WHERE `User_ID` = '".intval($user_id)."'");
    return 0;
  }
  
  /**
   * �������� �� �������/ �������
   *
   * @param isInsideAdmin - ����������� � �������
   * @param  sub - ������ (�� ��������� - �������)
   * @return bool
   */
  public function check ( $isInsideAdmin = 0, $sub = 0 ) {
    $nc_core = nc_Core::get_object();
    // � �������
    if ( $isInsideAdmin ) return $nc_core->modules->get_vars('auth', 'AUTH_HASH_ENABLE_ADMIN');
    
    // � ������
    if ( !$sub ) $sub = $nc_core->subdivision->get_current('Subdivision_ID'); 
     
    if ( $subs_str = $nc_core->modules->get_vars('auth', 'AUTH_HASH_DISABLED_SUBS') ) { 
      $subs = explode(", ", $subs_str);
      if ( !is_array($subs)) $subs = array($subs);
      if ( empty($subs) ) return true;

      if ( in_array($sub, $subs) ) return false;           
  
    }
    
    return true;
  }
  

   
  
  
  /**
   * �������� ����� ������������ �� ����
   *
   * @param string $hh
   * @return int user id
   */
  protected function get_user_by_hash ( $hh = null) {
    static $storage = array();
    // ��������, ��������� ������� ��� ����
    if ( $storage[$hash] ) return $storage[$hash];
    
    if ( $hh === null ) $hash = $this->db->escape($this->hash);

    $user_id = $this->db->get_var("SELECT `User_ID` FROM `User` WHERE `Auth_Hash` = '".$hash."'");
    // ��������� ���
    $storage[$hash] = $user_id;
    if ( $user_id ) return $user_id;
    
    return 0; 
  }
  
  
  /**
   * ���������� ���
   * ��������� ����: ����(32) + ������� ������ ��� ������ ������������ (1) + ������� ��� (1) + ����� ��������� (�)
   *
   * @param str $hash
   * @return 
   */
  protected function parse_hash () {
    // ��� �������������� ���������� ������
    static $init = 0;
    if ( $init ) return 1;
    $init = 1;
   
    $hash = $this->hash;
    //����������� ����� �����
    if ( strlen($hash) < 34 ) return 0;
   
    $this->key = substr($hash, 0, 32);        // ����
    $this->create_session = $hash{32};        // ��������� ������ ��� ���
    $this->delete_hash = $hash{33};           // ������� ��� ��� ���
    $this->expire_hash = substr( $hash, 34);  // ����� ��������� 

    return 1;
  }
  
  /**
   * �������� �� ��������� �����
   *
   * @return bool
   */
  protected function check_expire ( ) {
    $this->parse_hash();
    
    if ( $this->expire_hash * 3600 > time() ) return 1;
    return 0; 
  }
  
  /**
   * �������� �� ���������������� �� ���� (� ����������� �� �������� ������)
   *
   * @return bool
   */
  protected function check_availability () {
    $nc_core = nc_Core::get_object();
    
    $this->parse_hash();

    if ( $nc_core->modules->get_vars('auth', ($this->create_session ? 'AUTH_HASH_ENABLED' : 'PSEUDOUSERS_ENABLED') ) )
      return true;
   
    return false;
    
  }
  
  
  protected function check_sub ( $user_id ) {
    $nc_core = nc_Core::get_object();

    $this->parse_hash();
    
    // ����������� �� ����
    if ( $this->create_session ) {
     return $this->check();
    }
    else { // ����������� ������������������
      $sub = $nc_core->subdivision->get_current('Subdivision_ID');
      $check_ip = $nc_core->modules->get_vars('auth', 'PSEUDOUSERS_CHECK_IP');
      return $this->db->get_var("SELECT `ID` FROM `Auth_Pseudo` 
                                 WHERE `User_ID` = '".intval($user_id)."' AND `Subdivision_ID` = '".intval($sub)."'
                                 ".( $check_ip ? " AND `IP` = '".ip2long($_SERVER['REMOTE_ADDR'])."' " : "")."");
    }
  }
  
  
  protected function _make_hash ( $user_id, $create_session, $delete_hash = null, $expire = null) {
    $nc_core = nc_Core::get_object();
    // key - ���������� ����, ������������ ��� �������� ����
    $key = nc_auth_get_settings('AUTH_HASH_KEY');
    if ( !$key ) { // ���� ��� ��� � ����, �� ����� �������
      $key = $this->db->get_var("SELECT `LastUpdated` FROM `User` ORDER BY RAND()");
      $key = md5( $key.(time()*$user_id).rand());
      $this->db->query("INSERT INTO `Auth_Settings` (`SettingsKey`, `Value`) VALUES ('AUTH_HASH_KEY', '".$this->db->escape($key)."')");
    }
    
    $hash = substr(sha1( $key.rand().time().$user_id.$expire ), rand(0, 5), 32 );
    
    $hash .= $create_session ? '1' : '0';
    
    if ( $delete_hash === null ) $delete_hash = $nc_core->modules->get_vars('auth', 'AUTH_HASH_DELETE_HASH') ? 1 : 0;
    $hash .= $delete_hash ? '1' : '0';
   
    if ( $expire === null ) {
      $expire = $nc_core->modules->get_vars('auth', 'AUTH_HASH_EXPIRE') ? $nc_core->modules->get_vars('auth', 'AUTH_HASH_EXPIRE') : 120;
    }
    $hash .= intval( time() / 3600 + $expire);
   
    // ����� �� ���� ��������� �����
    //if ( $this->get_user_by_hash($hash ) ) return $this->_make_hash($user_id, $create_session, $delete_hash, $expire);
    
    return $hash;
  
  }
  
  
  protected function _attempt_to_delete () {
    if ( $this->delete_hash ) {
      $this->db->query("UPDATE `User` SET `Auth_Hash` = '' WHERE `Auth_Hash` = '".$this->db->escape($this->hash)."'");
    }
    
    return 0;
  }
  
  
  
  
  
  // ������������������
 
  public function add_pseudo_user ( $fields, $sub, $ip ='', $expire = null ) {
    $sub = intval($sub);
    
    $nc_core = nc_Core::get_object();

    $group =  $nc_core->modules->get_vars('auth', 'PSEUDOUSERS_GROUP');
    
    $uniq_field = $nc_core->modules->get_vars('auth', 'PSEUDOUSERS_FIELD');
    if ( $uniq_field ) {
      $user = $this->db->get_row("SELECT `User_ID`, `Auth_Hash` FROM `User` 
                                  WHERE `".$uniq_field."` = '".$this->db->escape($fields[$uniq_field])."' 
                                  ", ARRAY_A);
      if ( !empty($user) ) {
        return array('User_ID' => $user['User_ID'], 'Hash' => $user['Auth_Hash']);
      }
    }
    
    $fields['PermissionGroup_ID'] = $group;
    $fields['Checked'] = 1;
    $fields['UserType'] = 'pseudo';
    $fields['Created'] = date("Y-m-d H:i:s");
    
    foreach ( $fields as $k => $v ) {
      $sql_field[] = "`".$k."`";
      $sql_value[] = "'".$v."'";
    }
    $this->db->insert_id = 0;
    
    $this->db->query("INSERT INTO `User` (".join(',', $sql_field).") VALUES (".join(',', $sql_value).")");
    
    if ( $user_id = $this->db->insert_id ) {
      
      if ( !$ip ) $ip = $_SERVER['REMOTE_ADDR'];
      $ip = ip2long($ip);
      
      $this->db->query("INSERT INTO `Auth_Pseudo` ( `User_ID`, `Subdivision_ID`, `IP`) VALUES ('".$user_id."','".$sub."', '".$ip."')"); 

      $hash = $this->_make_hash ( $user_id, 0, 0);  

      $this->db->query("UPDATE `User` SET `Auth_Hash` = '".$this->db->escape($hash)."' WHERE `User_ID` = '".intval($user_id)."'");
      
      nc_usergroup_add_to_group($user_id, $group);

    }
    
    return array('User_ID' => $user_id, 'Hash' => $hash);
  }
  
  
  
  public function delete_pseudo_user ( $users_id ) {
    if ( !is_array($users_id) ) $users_id = array($users_id);
    foreach ($users_id as $k => $v) {
      $users_id[$k] = intval($v);
    }
    
    $ids_str = join(',', $users_id);
  
    $this->db->query("DELETE FROM `User` WHERE `User_ID` IN (".$ids_str.") ");
    $this->db->query("DELETE FROM `User_Group`  WHERE `User_ID` IN (".$ids_str.") ");
    $this->db->query("DELETE FROM `Auth_Pseudo`  WHERE `User_ID` IN (".$ids_str.") ");
  
  }


  public function delete_from_sub ( $sub, $user_id) {
    $sub = intval($sub);
    $user_id = intval($user_id);
    if ( !$sub || !$user_id ) return false;

    $this->db->query("DELETE FROM `Auth_Pseudo` WHERE `User_ID` = '".$user_id."' AND `Subdivision_ID` = '".$sub."'");
    // ���� ������������ ����� ��� �� ����� �������������� - �� ��� ���� �������
    if ( !$this->db->get_var("SELECT `User_ID` FROM `Auth_Pseudo` WHERE `User_ID` = '".$user_id."' ") ) {
      $this->delete_pseudo_user($user_id);
    }

    return 0;
  }
  
  
  public function delete_expire_user () {
    $nc_core = nc_Core::get_object();

    $expire =  $nc_core->modules->get_vars('auth', 'PSEUDOUSERS_USER_EXPIRE');
    if ( !$expire ) $expire = 120;
    $expire *= 60*60; // � ��������
    $ids = $this->db->get_col("SELECT `User_ID` FROM `User`
                      WHERE (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(`Created`)) >= '".$expire."'
                      AND `UserType` = 'pseudo' ");
    if (!empty($ids)) $this->delete_pseudo_user($ids);
    
    return 0;
  }
  
  
}

?>
