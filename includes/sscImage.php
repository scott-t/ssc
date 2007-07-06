<?php 
/**
 * Image processing
 * Ability to resize and/or recompress images
 * @package SSC
 * @copyright Copyright (c) Scott Thomas
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');
	
/**
 * @subpackage imageEditor
 * @package SSC
 */
class sscImage{

	/**
	 * Retrieve the extension of the supplied file
	 * @param string File to find extension
	 * @return string Extension for the file
	 */
	static function getExtension($file){
	    $pinfo = pathinfo($file);
		return $pinfo['extension'];
	}
	
	/**
	 * Resize an image to the specified size.  Takes an approximate guess at best compression based on file size
	 * @param string Path to the image to resize
	 * @param string Location to store the resized file
	 * @param string Extension of the file
	 * @param int Width of the image
	 * @param boolean Whether to apply a watermark or not
	 * @return boolean Whether or not the resize was successful
	 */
	static function resize($path, $target, $extension, $width, $watermark = false){
		global $sscConfig_absPath, $sscConfig_webPathShort;
		//bad width
		if($width <= 0){return false;}
		//check the file actually is there
		if(file_exists($path)){
			$extension = strtolower($extension);
			switch ($extension){
				case 'jpeg':
				case 'jpg':
					$img = imagecreatefromjpeg($path);
					break;			
					
				case 'png':
					$img = imagecreatefrompng($path);
					break;
			}
			
			if(!$img){ return false; }	//image appears invalid
			
			//set compression levels
			switch(true){		
				case ($width <= 150):
					$comp = 55;
					break;
				case ($width <= 500):
					$comp = 62;
					break;
				case ($width <= 1024):	
					$comp = 75;
					break;
				default:
					$comp = 65;
					break;
			}
			//do resize / compress
			$img_x = imagesx($img);
			$img_y = imagesy($img);			
			if($img_x > $img_y)
			{
				$img_nx = ($width < $img_x ? $width : $img_x);
				if($img_x != $img_nx){
					$img_ny = ($width < $img_x ? floor($img_y * $width / $img_x) : $img_y);
					$img_d = imagecreatetruecolor($img_nx, $img_ny);
					imagecopyresampled($img_d, $img, 0,0,0,0, $img_nx, $img_ny, $img_x, $img_y);	
					imagedestroy($img);
				}else{
					$img_d = $img;
					$img_ny = $img_y;
				}
			}else{
				$img_ny = ($width < $img_y ? $width : $img_y);
				if($img_y != $img_ny){
					$img_nx = ($width < $img_y ? floor($img_x * $width / $img_y) : $img_x);
					$img_d = imagecreatetruecolor($img_nx, $img_ny);
					imagecopyresampled($img_d, $img, 0,0,0,0, $img_nx, $img_ny, $img_x, $img_y);	
					imagedestroy($img);
				}else{
					$img_d = $img;
					$img_nx = $img_x;
				}

			}
			if(!$img_d){return false;}	//problem in resamp ?
			if($watermark && $img_ny > 100){
				$trans = imagecolorallocate($img_d,254,254,254);
				$text = imagecolorallocate($img_d,255,255,255);
				switch(true){
					case ($img_nx >= 1000):
						$water_p = $sscConfig_absPath . '/includes/watermarkl.png';
						$water_s = 60;
						break;
					case ($img_nx >= 600):
						$water_p = $sscConfig_absPath . '/includes/watermarkm.png';
						$water_s = 40;
						break;
					case ($img_nx >= 300):
						$water_p = $sscConfig_absPath . '/includes/watermarks.png';
						$water_s = 20;
						break;
					default:
						$watermark = false;
						break;
				}

				if($watermark){
					if(!file_exists($water_p)){
						$water_b = imagettfbbox($water_s,0,'arial.ttf',$sscConfig_webPathShort);
						$water_w = $water_b[4] - $water_b[0];
						$water_h = $water_b[1] - $water_b[5];
						$img_water = imagecreatetruecolor($water_w,$water_h);
						imagefill($img_water,0,0,$trans);
						imagecolortransparent($img_water,$trans);
						imagettftext($img_water,$water_s,0,-$water_b[0],-$water_b[5],$text,"arial.ttf",$sscConfig_webPathShort);
						//imagepng($img_water,$water_p);
					}else{
						$img_water = imagecreatefrompng($water_p);
					}
	
					$watermark = imagecopymerge($img_d, $img_water, floor(($img_nx - $water_w) * 0.5), floor(($img_ny - $water_h) * 0.5), 0, 0, $water_w, $water_h, 50);
					imagedestroy($img_water);
					if($watermark == false){return false;}
				}
			}
			imageinterlace($img_d, 1);
			
			$watermark = false;
			//save it			
			 switch ($extension){
				case 'jpeg':
				case 'jpg':
					$watermark = imagejpeg($img_d, $target . '.jpg', $comp);
					break;			
	
				case 'gif':
					$watermark = imagegif($img_d, $target . '.' . $extension, $comp);
					break;
					
				case 'png':
					$watermark = imagepng($img_d, $target . '.' . $extension, $comp);
					break;
			} 
			
			//free resources
			imagedestroy($img_d);
			return $watermark;
			
		} else { return false ; }
	}
}
?>