<?PHP

namespace daos\mysql;
    
/**
 * Base class for database access -- mysql
 *
 * @package    daos
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Database {

    /**
     * indicates whether database connection was
     * initialized
     * @var bool
     */
    static private $initialized = false;

    
    /**
     * establish connection and
     * create undefined tables
     *
     * @return void
     */
    public function __construct() {
        if(self::$initialized===false && \F3::get('db_type')=="mysql") {
            // establish database connection
            \F3::set('db', new \DB\SQL(
                'mysql:host=' . \F3::get('db_host') . ';port=' . \F3::get('db_port') . ';dbname='.\F3::get('db_database'),
                \F3::get('db_username'),
                \F3::get('db_password')
            ));
            
            // create tables if necessary
            $result = @\F3::get('db')->exec('SHOW TABLES');
            $tables = array();
            foreach($result as $table)
                foreach($table as $key=>$value)
                    $tables[] = $value;

            if(!in_array('users', $tables))
            	\F3::get('db')->exec('
                    CREATE TABLE users (
            			username char(64) PRIMARY KEY,
            			passwd char(240) NOT NULL,
            			deliver_email char(64),
            			deliver_time time,
            			deliver_end_date date,
            			deliver_enable BOOL NOT NULL DEFAULT false,
            			source_num_limit int not null default 10,
            			items_num_limit int not null default 200,
                        INDEX (username)
                    ) ENGINE = MYISAM DEFAULT CHARSET=utf8;
                ');
            
            if(!in_array('items', $tables))
                \F3::get('db')->exec('
                    CREATE TABLE items (
                        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                		username char(64) NOT NULL,
                        datetime DATETIME NOT NULL ,
                        title TEXT NOT NULL ,
                        content LONGTEXT NOT NULL ,
                        thumbnail TEXT ,
                        icon TEXT ,
                        unread BOOL NOT NULL ,
                        starred BOOL NOT NULL ,
                        source INT NOT NULL ,
                        uid VARCHAR(255) NOT NULL,
                        link TEXT NOT NULL,
                		delivered BOOL NOT NULL DEFAULT false,
                        INDEX (source),
                		INDEX (username)
                    ) ENGINE = MYISAM DEFAULT CHARSET=utf8;
                ');
            
            $isNewestSourcesTable = false;
            if(!in_array('sources', $tables)) {
                \F3::get('db')->exec('
                    CREATE TABLE sources (
                        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                		username char(64) NOT NULL,
                        title TEXT NOT NULL ,
                        tags TEXT,
                        spout TEXT NOT NULL ,
                        params TEXT NOT NULL ,
                        error TEXT,
                        lastupdate INT
                    ) ENGINE = MYISAM DEFAULT CHARSET=utf8;
                ');
                $isNewestSourcesTable = true;
            }
            
            // version 1 or new
            if(!in_array('version', $tables)) {
                \F3::get('db')->exec('
                    CREATE TABLE version (
                        version INT
                    ) ENGINE = MYISAM DEFAULT CHARSET=utf8;
                ');
                
                \F3::get('db')->exec('
                    INSERT INTO version (version) VALUES (3);
                ');
                
                \F3::get('db')->exec('
                    CREATE TABLE tags (
                        tag         TEXT NOT NULL,
                        color       VARCHAR(7) NOT NULL,
                		username 	char(64) NOT NULL
                    ) DEFAULT CHARSET=utf8;
                ');
            }            
            // just initialize once
            $initialized = true;
        }
    }
    
    
    /**
     * optimize database by
     * database own optimize statement
     *
     * @return void
     */
    public function optimize() {
        @\F3::get('db')->exec("OPTIMIZE TABLE `sources`, `items`");
    }
}
