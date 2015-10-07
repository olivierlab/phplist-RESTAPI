<?php

namespace phpListRestapi;

defined('PHPLISTINIT') || die;

/**
 * Class Users
 * Manage phplist administrator
 */
class Users {

    /**
     * <p>Check is the user exists</p>
     * <p><strong>Parameters:</strong><br/>
     * [*login] {string} The user login<br/>
     * [*pwd] {string} The user password<br/>
     * </p>
     * <p><strong>Returns:</strong><br/>
     * 1 if login/pwd is correct else 0
     * </p>
     */
    static function userCheck($login, $pwd) {
        if ($login == 0) {
            $login = $_REQUEST['login'];
        }
        if ($pwd == 0) {
            $pwd = $_REQUEST['pwd'];
        }
        
        
        $encryptedPass = hash(ENCRYPTION_ALGO, $pwd);

        $sql = 'SELECT password, disabled, id' . ' from ' . $GLOBALS['table_prefix'] . 'admin where loginname = "' . $login . '"';

        try {
            $db = PDO::getConnection();
            $stmt = $db->query($sql);
            $stmt->execute();
            $user = $stmt->fetchAll(PDO::FETCH_OBJ);
            $db = null;
        } catch (PDOException $e) {
            
        }
        
        $row = array();        
        if(count($user) == 1 && $user[0]->password == $encryptedPass){
            $row['exist'] = 1;
        }else{
            $row['exist'] = 0;
        }
        
        $response = new Response();
        $response->setData('', $row);
        $response->output();
    }

}
