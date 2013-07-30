<?PHP

namespace helpers;

/**
 * Helper class for loading extern items
 *
 * @package    helpers
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class ContentLoader {

    /**
     * @var \daos\Items database access for saving new item
     */
    private $itemsDao;

    /**
     * @var \daos\Sourcesdatabase access for saveing sources last update
     */
    private $sourceDao;

    /**
     * ctor
     */
    public function __construct() {
        // include htmLawed
        if(!function_exists('htmLawed'))
            require('libs/htmLawed.php');

        $this->itemsDao = new \daos\Items();
        $this->sourceDao = new \daos\Sources();
    }
    
    
    /**
     * updates current user
     *
     * @return void
     */
    public function update() {
        $sourcesDao = new \daos\Sources();
        foreach($sourcesDao->getByLastUpdate() as $source) {
            $this->fetch($source);
        }
        $this->cleanup();
    }
    

    /**
     * updates all sources
     *
     * @return void
     */
    public function updateAlluser() {
    	$sourcesDao = new \daos\Sources();
    	foreach($sourcesDao->getallByLastUpdate() as $source) {
    		$this->fetch($source);
    	}
    	$this->cleanup();
    }
    
    /**
     * updates a given source
     * returns an error or true on success
     *
     * @return void
     * @param mixed $source the current source
     */
    public function fetch($source) {
        
        @set_time_limit(5000);
        @error_reporting(E_ERROR);
        
        // logging
        \F3::get('logger')->log('---', \DEBUG);
        \F3::get('logger')->log('start fetching source "'. $source['title'] . ' (id: '.$source['id'].') '.' username:'.$source['username'], \DEBUG);
        
        // get spout
        $spoutLoader = new \helpers\SpoutLoader();
        $spout = $spoutLoader->get($source['spout']);
        if($spout===false) {
            \F3::get('logger')->log('unknown spout: ' . $source['spout'], \ERROR);
            return;
        }
        \F3::get('logger')->log('spout successfully loaded: ' . $source['spout'], \DEBUG);
        
        // receive content
        \F3::get('logger')->log('fetch content', \DEBUG);
        try {
            $spout->load(
                json_decode(html_entity_decode($source['params']), true)
            );
        } catch(\exception $e) {
            \F3::get('logger')->log('error loading feed content for ' . $source['title'] . ': ' . $e->getMessage(), \ERROR);
            $this->sourceDao->error($source['id'], date('Y-m-d H:i:s') . 'error loading feed content: ' . $e->getMessage());
            return;
        }
        
        // current date
        $minDate = new \DateTime();
        $minDate->sub(new \DateInterval('P'.\F3::get('items_lifetime').'D'));
        \F3::get('logger')->log('minimum date: ' . $minDate->format('Y-m-d H:i:s'), \DEBUG);
        
        // insert new items in database
        \F3::get('logger')->log('start item fetching', \DEBUG);

        $lasticon = false;
        foreach ($spout as $item) {
            // item already in database?
            if($this->itemsDao->exists($item->getId(),$source['username'])===true)
                continue;
            
            // test date: continue with next if item too old
            $itemDate = new \DateTime($item->getDate());
            if($itemDate < $minDate) {
                \F3::get('logger')->log('item "' . $item->getTitle() . '" (' . $item->getDate() . ') older than '.\F3::get('items_lifetime').' days', \DEBUG);
                continue;
            }
            
            // date in future? Set current date
            $now = new \DateTime();
            if($itemDate > $now)
                $itemDate = $now;
            
            // insert new item
            \F3::get('logger')->log('start insertion of new item "'.$item->getTitle().'"', \DEBUG);
            
            // sanitize content html
            $content = $this->sanitizeContent($item->getContent());

            // sanitize title
            $title = htmlspecialchars_decode($item->getTitle());
            $title = htmLawed($title, array("deny_attribute" => "*", "elements" => "-*"));
            if(strlen(trim($title))==0)
                $title = "[" . \F3::get('lang_no_title') . "]";

            \F3::get('logger')->log('item content sanitized', \DEBUG);

            $icon = $item->getIcon();
            $newItem = array(
            		'username'     => $source['username'],
                    'title'        => $title,
                    'content'      => $content,
                    'source'       => $source['id'],
                    'datetime'     => $itemDate->format('Y-m-d H:i:s'),
                    'uid'          => $item->getId(),
                    'thumbnail'    => $item->getThumbnail(),
                    'icon'         => $icon!==false ? $icon : "",
                    'link'         => htmLawed($item->getLink(), array("deny_attribute" => "*", "elements" => "-*"))      	
            );
            
            // save thumbnail
            $newItem = $this->fetchThumbnail($item->getThumbnail(), $newItem);

            // save icon
            $newItem = $this->fetchIcon($item->getIcon(), $newItem, $lasticon);

            // insert new item
            $this->itemsDao->add($newItem);
            \F3::get('logger')->log('item inserted', \DEBUG);
            
            \F3::get('logger')->log('Memory usage: '.memory_get_usage(), \DEBUG);
            \F3::get('logger')->log('Memory peak usage: '.memory_get_peak_usage(), \DEBUG);
        }
    
        // destroy feed object (prevent memory issues)
        \F3::get('logger')->log('destroy spout object', \DEBUG);
        $spout->destroy();

        // remove previous errors and set last update timestamp
        $this->updateSource($source);
    }


    /**
     * Sanitize content for preventing XSS attacks.
     *
     * @param $content content of the given feed
     * @return mixed|string sanitized content
     */
    protected function sanitizeContent($content) {
        return htmLawed(
            htmlspecialchars_decode($content),
            array(
                "safe"           => 1,
                "deny_attribute" => '* -alt -title -src -href',
                "keep_bad"       => 0,
                "comment"        => 1,
                "cdata"          => 1,
                "elements"       => 'div,p,ul,li,a,img,dl,dt,h1,h2,h3,h4,h5,h6,ol,br,table,tr,td,blockquote,pre,ins,del,th,thead,tbody,b,i,strong,em,tt'
            )
        );
    }


    /**
     * Fetch the thumbanil of a given item
     *
     * @param $thumbnail the thumbnail url
     * @param $newItem new item for saving in database
     * @return the newItem Object with thumbnail
     */
    protected function fetchThumbnail($thumbnail, $newItem) {
        if (strlen(trim($thumbnail)) > 0) {
            $imageHelper = new \helpers\Image();
            $thumbnailAsPng = $imageHelper->loadImage($thumbnail, 150, 150);
            if ($thumbnailAsPng !== false) {
                file_put_contents(
                    'data/thumbnails/' . md5($thumbnail) . '.png',
                    $thumbnailAsPng
                );
                $newItem['thumbnail'] = md5($thumbnail) . '.png';
                \F3::get('logger')->log('thumbnail generated: ' . $thumbnail, \DEBUG);
            } else {
                $newItem['thumbnail'] = '';
                \F3::get('logger')->log('thumbnail generation error: ' . $thumbnail, \ERROR);
            }
        }

        return $newItem;
    }


    /**
     * Fetch the icon of a given feed item
     *
     * @param $icon icon given by the spout
     * @param $newItem new item for saving in database
     * @param $lasticon the last fetched icon (byref)
     * @return mixed newItem with icon
     */
    protected function fetchIcon($icon, $newItem, &$lasticon) {
        if(strlen(trim($icon)) > 0) {
            if($icon==$lasticon) {
                \F3::get('logger')->log('use last icon: '.$lasticon, \DEBUG);
                $newItem['icon'] = md5($lasticon) . '.png';
            } else {
                $imageHelper = new \helpers\Image();
                $iconAsPng = $imageHelper->loadImage($icon, 30, 30);
                if($iconAsPng!==false) {
                    file_put_contents(
                        'data/favicons/' . md5($icon) . '.png',
                        $iconAsPng
                    );
                    $newItem['icon'] = md5($icon) . '.png';
                    $lasticon = $icon;
                    \F3::get('logger')->log('icon generated: '.$icon, \DEBUG);
                } else {
                    $newItem['icon'] = '';
                    \F3::get('logger')->log('icon generation error: '.$icon, \ERROR);
                }
            }
        }
        return $newItem;
    }


    /**
     * clean up messages, thumbnails etc.
     *
     * @return void
     */
    public function cleanup() {
        // cleanup orphaned and old items
        \F3::get('logger')->log('cleanup orphaned and old items', \DEBUG);
        $this->itemsDao->cleanup(\F3::get('items_lifetime'));
        \F3::get('logger')->log('cleanup orphaned and old items finished', \DEBUG);
        
        // delete orphaned thumbnails
        \F3::get('logger')->log('delete orphaned thumbnails', \DEBUG);
        $this->cleanupFiles('thumbnails');
        \F3::get('logger')->log('delete orphaned thumbnails finished', \DEBUG);
        
        // delete orphaned icons
        \F3::get('logger')->log('delete orphaned icons', \DEBUG);
        $this->cleanupFiles('icons');
        \F3::get('logger')->log('delete orphaned icons finished', \DEBUG);
        
        // optimize database
        \F3::get('logger')->log('optimize database', \DEBUG);
        $database = new \daos\Database();
        $database->optimize();
        \F3::get('logger')->log('optimize database finished', \DEBUG);
    }
    
    
    /**
     * clean up orphaned thumbnails or icons
     *
     * @return void
     * @param string $type thumbnails or icons
     */
    protected function cleanupFiles($type) {
        \F3::set('im', $this->itemsDao);
        if($type=='thumbnails') {
            $checker = function($file) { return \F3::get('im')->hasThumbnail($file);};
            $itemPath = 'data/thumbnails/';
        } else if($type=='icons') {
            $checker = function($file) { return \F3::get('im')->hasIcon($file);};
            $itemPath = 'data/favicons/';
        }
        
        foreach(scandir($itemPath) as $file) {
            if(is_file($itemPath . $file) && $file!=".htaccess") {
                $inUsage = $checker($file);
                if($inUsage===false) {
                    unlink($itemPath . $file);
                }
            }
        }
    }


    /**
     * Update source (remove previous errors, update last update)
     *
     * @param $source source object
     */
    protected function updateSource($source) {
        // remove previous error
        if (strlen(trim($source['error'])) != 0) {
            $this->sourceDao->error($source['id'], '');
        }
        // save last update
        $this->sourceDao->saveLastUpdate($source['id']);
    }
}
