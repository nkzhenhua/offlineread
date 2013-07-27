<?PHP

namespace helpers;

/**
 * Helper class for authenticate user
 *
 * @package    helpers
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Authentication extends \daos\Database {

    /**
     * loggedin
     * @var bool
     */
    private $loggedin = false;
    
    
    /**
     * enabled
     * @var bool
     */
    private $enabled = false;
    
    
    /**
     * start session and check login
     */
    public function __construct() {
    	parent::__construct();
        // session cookie will be valid for one month
        session_set_cookie_params((3600*24*30), "/");
        
        session_name();
        if(session_id()=="")
            session_start();
        if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']===true)
            $this->loggedin = true;
//        $this->enabled = strlen(trim(\F3::get('username')))!=0 && strlen(trim(\F3::get('password')))!=0;
			//must login with username and passwd
          $this->enabled = true;
          
        // autologin if request contains unsername and password
        if( $this->enabled===true 
            && $this->loggedin===false
            && isset($_REQUEST['username'])
            && isset($_REQUEST['password'])) {
            $this->login($_REQUEST['username'], $_REQUEST['password']);
        }
    }
    
    
    /**
     * login enabled
     *
     * @return bool
     * @param string $username
     * @param string $password
     */
    public function enabled() {
        return $this->enabled;
    }
    
    
    /**
     * login user
     *
     * @return bool
     * @param string $username
     * @param string $password
     */
    public function loginWithoutUser() {
        $this->loggedin = true;
    }
    
    
    /**
     * login user
     *
     * @return bool
     * @param string $username
     * @param string $password
     */
    public function login($username, $password) {
        if($this->enabled()) {
        	$res = \F3::get('db')->exec('SELECT passwd AS passwd FROM users WHERE username=:username',array(':username' => $username));
            if(isset($res[0]['passwd']) &&
               hash("md5", \F3::get('salt') . $password) == $res[0]['passwd']
) {
                $this->loggedin = true;
                $_SESSION['username'] = $username;
                \F3::set('username',$username);
                $_SESSION['loggedin'] = true;
                return true;
            }
        }
        return false;
    }
    
	/**
	 * register user
	 *
	 * @return bool
	 * @param string $username        	
	 * @param string $password        	
	 */
	public function register($username, $password) {
		$res = \F3::get ( 'db' )->exec ( 'SELECT username AS username FROM users WHERE username=:username', array (
				':username' => $username 
		) );
		if (isset ( $res[0]['username'] )) {
			return false;
		}
		
		$pswd = hash ( "md5", \F3::get ( 'salt' ) . $password );
		\F3::get ( 'db' )->exec ( 'insert into users (username,passwd) values (:username, :passwd)', array (
				':username' => $username,
				':passwd' => $pswd 
		) );
		
		$this->loggedin = true;
		$_SESSION ['username'] = $username;
		\F3::set ( 'username', $username );
		$_SESSION ['loggedin'] = true;
		return true;
	}
    
    
    /**
     * isloggedin
     *
     * @return bool
     */
    public function isLoggedin() {
        if($this->enabled()===false)
            return true;
        return $this->loggedin;
    }
    
    
    /**
     * logout
     *
     * @return void
     */
    public function logout() {
        $this->loggedin = false;
        $_SESSION['loggedin'] = false;
        $_SESSION['username'] = "";
        unset($_SESSION['username']);
        \F3::set('username',"");
        session_destroy();
    }
}