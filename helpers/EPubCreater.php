<?php

namespace helpers;

class EPubCreater {
	// Create a test book
	// ePub uses XHTML 1.1, preferably strict.
	private $content_start = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n <!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"\n \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n <html xmlns=\"http://www.w3.org/1999/xhtml\">\n <head> <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n <link rel=\"stylesheet\" type=\"text/css\" href=\"styles.css\" />\n </head>\n <body>\n";
	private $content_end = "</body>\n</html>\n";
	private $cssData = "body {\n  margin-left: .5em;\n  margin-right: .5em;\n  text-align: justify;\n}\n\np {\n  font-family: serif;\n  font-size: 10pt;\n  text-align: justify;\n  text-indent: 1em;\n  margin-top: 0px;\n  margin-bottom: 1ex;\n}\n\nh1, h2 {\n  font-family: sans-serif;\n  font-style: italic;\n  text-align: center;\n  background-color: #6b879c;\n  color: white;\n  width: 100%;\n}\n\nh1 {\n    margin-bottom: 2px;\n}\n\nh2 {\n    margin-top: -2px;\n    margin-bottom: 2px;\n}\n";
	private $tStart;
	private $tLast;
	/**
	 *
	 * @var \daos\Items database access for deliver
	 */
	private $itemsDao;
	
	public function __construct() {
		include_once ("libs/Epub/EPub.php");
		$tStart = gettimeofday();
		$tLast = $this->tStart;
		$this->itemsDao = new \daos\Items ();
	}
	public function crate_book($username, $title,$filename) {
		$book = new \EPub ();
		logLine ( "new EPub()" );
		logLine ( "EPub version: " . EPub::VERSION );
		logLine ( "EPub Req. Zip version: " . EPub::REQ_ZIP_VERSION );
		logLine ( "Zip version: " . Zip::VERSION );
		logLine ( "getCurrentServerURL: " . $book->getCurrentServerURL () );
		logLine ( "getCurrentPageURL..: " . $book->getCurrentPageURL () );
		
		// Title and Identifier are mandatory!
		$book->setTitle ( $title );
		$book->setIdentifier ( "http://offlineread.com", EPub::IDENTIFIER_URI ); // Could also be the ISBN number, prefered for published books, or a UUID.
		$book->setLanguage ( "en" ); // Not needed, but included for the example, Language is mandatory, but EPub defaults to "en". Use RFC3066 Language codes, such as "en", "da", "fr" etc.
		$book->setDescription ( "generated from www.offlineread.com" );
		$book->setAuthor ( "offlineread.com", "offlineread.com" );
		$book->setPublisher ( "offlineread.com", "http://www.offlineread.com/" ); // I hope this is a non existant address :)
		$book->setDate ( time () ); // Strictly not needed as the book date defaults to time().
		$book->setRights ( "Copyright and licence information specific for the book." ); // As this is generated, this _could_ contain the name or licence information of the user who purchased the book, if needed. If this is used that way, the identifier must also be made unique for the book.
		$book->setSourceURL ( "http://JohnJaneDoePublications.com/books/TestBook.html" );
		logLine ( "Set up parameters" );
		$book->addCSSFile ( "styles.css", "css1", $this->cssData);
		logLine ( "Add css" );
		
		// This test requires you have an image, change "demo/cover-image.jpg" to match your location.
		// $book->setCoverImage("Cover.jpg", file_get_contents("demo/cover-image.jpg"), "image/jpeg");
		
		// A better way is to let EPub handle the image itself, as it may need resizing. Most Ebooks are only about 600x800
		// pixels, adding megapix images is a waste of place and spends bandwidth. setCoverImage can resize the image.
		// When using this method, the given image path must be the absolute path from the servers Document root.
		
		/* $book->setCoverImage("/absolute/path/to/demo/cover-image.jpg"); */
		
		// setCoverImage can only be called once per book, but can be called at any point in the book creation.
		$options = array (
				'username' => $username,
				'offset' => 0,
				'items' => 100, // the max number of deliver
				'delivered' => 'undeliver' 
		);
		$items=$this->itemsDao->get( $options );
		foreach ( $items as $item ) {
			$book->addChapter(chop($item['title']), chop($item['uid']).'.html', 
					$this->content_start.$item['content'].$this->content_end,true,EPub::EXTERNAL_REF_ADD);
		}		
		$book->finalize (); // Finalize the book, and build the archive.
		 
		// Save book as a file relative to your script (for local ePub generation)
		// Notice that the extions .epub will be added by the script.
		// The second parameter is a directory name which is '.' by default. Don't use trailing slash!
		$book->saveBook ( $filename, '.' );
	}
	
	// After this point your script should call exit. If anything is written to the output,
	// it'll be appended to the end of the book, causing the epub file to become corrupt.
	function logLine($line) {
		\F3::get('logger')->log($line, \DEBUG);
	}
}
?>
