<?php
/**
 * GD based image library.
 *
 * Implement image manipulation techniques using the GD image library.
 *
 * @package SSC
 * @subpackage Libraries
 * @see sscAbstractImage
 */

/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

/**
 * GD image library implementation 
 */
class sscImageGD2 extends sscAbstractImage{

	/**
	 * Constructor
	 */
	function __construct($file){
		$this->file = $file;
	}
	
	/**
	 * Does the file resizing using the GD library
	 * @see sscAbstractImage::_resize()
	 */
	function _resize($target, $width, $height){
	
	 	$fileinfo = pathinfo($this->file);
	 	$ext = strtolower($fileinfo['extension']);
	 	$gdinfo = gd_info();
	 	// Support only for JPG and PNG at the moment
	 	switch ($ext){
		case 'jpeg':
		case 'jpg':
			// JPG support?
			$status = false;
			if (isset($gdinfo['JPG Support']) && $gdinfo['JPG Support'] == true)
				$status = true;
			if (isset($gdinfo['JPEG Support']) && $gdinfo['JPEG Support'] == true)
				$status = true;
			
			if ($status)	
				$img = imagecreatefromjpeg($this->file);
			else
				return false;
			break;			
					
		case 'png':
			if ($gdinfo['PNG Support'] == false)
				return false;
				
			$img = imagecreatefrompng($this->file);
			break;
		}

		// Image handle valid?
		if (!$img)
			return false;
			
		// Work out approximage compression levels
		switch (true){		
		case ($width <= 150):
			$comp = 70;
			break;
		case ($width <= 500):
			$comp = 75;
			break;
		case ($width <= 1024):	
			$comp = 80;
			break;
		default:
			$comp = 65;
			break;
		}
		
		// Calculate resize values
		$nx = $x = imagesx($img);
		$ny = $y = imagesy($img);
		$this->_get_resize($nx, $width, $ny, $height);
		
		// Perform resize
		$new_img = imagecreatetruecolor($nx, $ny);
		imagecopyresampled($new_img, $img, 0, 0, 0, 0, $nx, $ny, $x, $y);

		// Destroy old image reference
		imagedestroy($img);
		
		// Check if new image handle exists - if not, die 
		if (!$new_img)
			return false;
		
		imageinterlace($new_img, 1);
				
		// Drop case in extension
		$target = substr_replace($target, $ext, 0 - strlen($ext));
				
		// Save			
		 switch ($ext){
			case 'jpeg':
			case 'jpg':
				$saved = imagejpeg($new_img, $target, $comp);
				break;			

			case 'png':
				if ($comp < 10) $comp = 10;
				$saved = imagepng($new_img, $target, 10 - (int)($comp / 10));
				break;
		} 
				
		// Free resources
		imagedestroy($new_img);
		return $saved;
	}
	
	
}