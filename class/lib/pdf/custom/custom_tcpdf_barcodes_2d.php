<?php
/**
 * Tcpdf Extension
 *
 * @package tcpdf
 * @author Alexander Kuballa <akuballa@users.sourceforge.net>
 * @copyright Copyright (c) 2008 - 2022, Alexander Kuballa
 * @license BSD License (see LICENSE.TXT)
 * @version 1.0
 */

class custom_tcpdf_barcodes_2d extends TCPDF2DBarcode
{

	/**
	 * Return a Jpeg image representation of barcode (requires GD or Imagick library).
	 * @param $w (int) Width of a single rectangle element in pixels.
	 * @param $h (int) Height of a single rectangle element in pixels.
	 * @param $color (array) RGB (0-255) foreground color for bar elements (background is transparent).
 	 * @return image or false in case of error.
 	 * @public
	 */
	public function getBarcodeJpgData($w=3, $h=3, $color=array(0,0,0)) {
		// calculate image size
		$width = ($this->barcode_array['num_cols'] * $w);
		$height = ($this->barcode_array['num_rows'] * $h);
		if (function_exists('imagecreate')) {
			// GD library
			$imagick = false;
			$png = imagecreate($width, $height);
			$bgcol = imagecolorallocate($png, 255, 255, 255);
			imagecolortransparent($png, $bgcol);
			$fgcol = imagecolorallocate($png, $color[0], $color[1], $color[2]);
		} elseif (extension_loaded('imagick')) {
			$imagick = true;
			$bgcol = new imagickpixel('rgb(255,255,255');
			$fgcol = new imagickpixel('rgb('.$color[0].','.$color[1].','.$color[2].')');
			$png = new Imagick();
			$png->newImage($width, $height, 'none', 'jpeg');
			$bar = new imagickdraw();
			$bar->setfillcolor($fgcol);
		} else {
			return false;
		}
		// print barcode elements
		$y = 0;
		// for each row
		for ($r = 0; $r < $this->barcode_array['num_rows']; ++$r) {
			$x = 0;
			// for each column
			for ($c = 0; $c < $this->barcode_array['num_cols']; ++$c) {
				if ($this->barcode_array['bcode'][$r][$c] == 1) {
					// draw a single barcode cell
					if ($imagick) {
						$bar->rectangle($x, $y, ($x + $w - 1), ($y + $h - 1));
					} else {
						imagefilledrectangle($png, $x, $y, ($x + $w - 1), ($y + $h - 1), $fgcol);
					}
				}
				$x += $w;
			}
			$y += $h;
		}
		if ($imagick) {
			$png->drawimage($bar);
			return $png;
		} else {
			ob_start();
			imagejpeg($png);
			$imagedata = ob_get_clean();
			imagedestroy($png);
			return $imagedata;
		}
	}
}	
?>
