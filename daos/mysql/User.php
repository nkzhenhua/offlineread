<?PHP

namespace daos\mysql;

/**
 * Class for accessing persistent saved sources -- mysql
 *
 * @package    daos
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class User extends Database {

    /**
     * get an user info
     * @return obj
     */
    public function getUserinfo($username)
    {
    	$res = \F3::get('db')->exec(' SELECT username,passwd,deliver_email,deliver_time,deliver_end_date,deliver_enable
    			,source_num_limit,items_num_limit FROM users  where username=:username',
        array(':username' => $username));
    	return $res[0];
    }
    
    /**
     * get the email of username
     * @param unknown $username
     * @return the email addr
     */
    public function getEmail($username)
    {
    	$res = \F3::get('db')->exec('SELECT deliver_email FROM users WHERE username=:username',
    			array(
    					':username' => $username
    			));
    	return $res[0]['deliver_email'];
    	 
    }
    public function setEmail($username,$emailaddr)
    {
    	\F3::get('db')->exec('UPDATE users SET deliver_email=:deliver_email where username=:username',array(
    		':deliver_email' => $emailaddr,
    		':username' => $username
    	));
    }
    /**
     * whether auto deliver file to user
     * @param unknown $username
     * @return boolean
     */
    public function isDelivable($username)
    {
    	$res = \F3::get('db')->exec('SELECT deliver_enable FROM users WHERE username=:username',
    			array(
    					':username' => $username
    			));
    	return $res[0]['deliver_enable']>0;
    }
	public function addUser($userinfo) {
		if ($this->hasUser( $userinfo['username'] ) === true) {
			return;
		} else {
			\F3::get ( 'db' )->exec ( 'INSERT INTO users (
                    username,
                    passwd,
					deliver_enable,
					source_num_limit,
					items_num_limit
                  ) VALUES (
                    :username,
                    :passwd,
					1,
					10,
					200
                  )', array (
					':username' => $userinfo['username'],
					':passwd' => $userinfo['passwd']
                  			) );
		}
	}
    
    /**
     * get all the user info
     * @return obj
     */
    public function getAlluser()
    {
    	$res = \F3::get('db')->exec(' SELECT username,deliver_email,deliver_time,deliver_end_date,deliver_enable,source_num_limit,items_num_limit FROM users ');
    	return $res;
    }
    
    /**
     * check whether user exist
     *
     * @return boolean true if user exist
     */
    public function hasUser($username) {
        $res = \F3::get('db')->exec('SELECT COUNT(*) AS amount FROM users WHERE username=:username',
                    array(
                    ':username' => $username         		
        ));
        return $res[0]['amount']>0;
    }
    
    /**
     * get password
     *
     * @return string pass
     * @param string $username
     */
    public function getpasswd($username) {
        $res = \F3::get('db')->exec('select passwd from users WHERE username=:username',
        		array(
        			':username' => $username
                 ));
        return $res;
    }
}
