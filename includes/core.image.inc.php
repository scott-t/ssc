<?php 
/**
 * Provides image based manipulation functions.  Requires the GD library.
 * @package SSC
 * @subpackage Libraries
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');


/**
 * Image manipulation class
 */
class sscImage{
	/** @var string Path to file */
	var $file;
	
	/**
	 *	Constructor.  Associates an image with this object
	 */
	function __construct($file){
		$this->file = $file;
		if (!file_exists($file))
			return false;
	}
	
	/**
	 * Free up the database objects
	 */
	public function __destruct(){
		
	}
	
	/**
	 * Resize an image to the specified size.  Takes an approximate guess at best compression based on file size
	 * Either $width or $height may be -1 to indicate no maximum width/height but not both 
	 * @param string Location to store the resized file
	 * @param int Maximum width of the image
	 * @param int Maximum height of the image
	 * @return boolean Whether or not the resize was successful
	 */
	function resize($target, $width = -1, $height = -1){
		global $ssc_site_path;
		
		// Perform checks before passing off to the individual implementation
		
		// Can't have both don't-care width AND height
		if ($width < 1 && $height < 1)
			return false;
					
		// Check target location writability
		$dir = dirname($target);
		if (!is_dir($dir) || ((fileperms($dir) & 0200) == 0))
			return false;
			
		// Preliminary checks ok - pass to library implementation
		$lib = 'sscImage' . ssc_var_get('image_library', 'GD2');
		if (!ssc_load_library($lib))
			return false;
			
		if ($imgLib = new $lib($this->file)){
			return $imgLib->_resize($target, $width, $height);
		}
		else{
			return false;
		}
	}	
	
	/**
	 * Calculate resize values
	 * @param int $x Current x value
	 * @param int $max_x Maximum x value
	 * @param int $y Current y value
	 * @param int $max_y Maximum y value
	 */
	protected function _get_resize(&$x, $max_x, &$y, $max_y){
		
		if ($max_x < 0){
			// Only care about y
			// Check if already within limits
			if ($y <= $max_y)
				return;
				
			$ratio = $max_y / $y;
			$y = $max_y;
			$x = (int)floor($x * $ratio);
			return;				
		}
		elseif ($max_y < 0){
			// Only concerned about x
			// Check if already within limits
			if ($x <= $max_x)
				return;
				
			$ratio = $max_x / $x;
			$x = $max_x;
			$y = (int)floor($y * $ratio);
			return;
		}
		else{
			// Both constraints in place
			// Check if already within limits
			if ($y <= $max_y && $x <= $max_x)
				return;
		}
			
	}
}

/**
 * Image manipulation layer blueprint
 */
abstract class sscAbstractImage extends sscImage{
	/** @var string Path to file */
	var $file;
	
	/**
	 * Target platform dependent implementation for image resizing
	 * @param string $target Location to save resized image
	 * @param int $width Maximum image width
	 * @param int $height Maximum image height
	 * @return bool TRUE if image was resized and saved, false otherwise
	 */
	abstract function _resize($target, $width, $height);
}
