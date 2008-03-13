<?php



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
			if ($gdinfo['JPG Support'] == false)
				return false;
				
			$img = imagecreatefromjpeg($this->file);
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
				
		// Save			
		 switch ($ext){
			case 'jpeg':
			case 'jpg':
				$saved = imagejpeg($new_img, $target, $comp);
				break;			

			case 'png':
				$saved = imagepng($new_img, $target, $comp);
				break;
		} 
				
		// Free resources
		imagedestroy($new_img);
		return $saved;
	}
	
	
}