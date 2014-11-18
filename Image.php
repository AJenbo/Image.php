<?php
/**
 * Implement image class
 *
 * PHP version 5.5
 *
 * @category Image
 * @package  Føniks
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  LGPL http://www.gnu.org/licenses/lgpl.html
 * @link     http://anders.jenbo.dk/
 */

/**
 * Helper function for simple image manipulation using GD functions
 *
 * @category Image
 * @package  Føniks
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  LGPL http://www.gnu.org/licenses/lgpl.html
 * @link     http://anders.jenbo.dk/
 */
class Image
{
    private $_image = null;
    public $transparent = false;
    public $width = 0;
    public $height = 0;

    /**
     * Load image from file
     *
     * Requires that mime types are set up correctly
     *
     * @param string $path Path to input image
     */
    function __construct($path)
    {
        if (!$path) {
            throw new Exception(_('No path specified!'));
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $path);
        finfo_close($finfo);
        $mimeType = explode(';', $mimeType);
        $mimeType = $mimeType[0];

        switch($mimeType) {
        case 'image/jpeg':
            $this->_image = imagecreatefromjpeg($path);
            break;
        case 'image/png':
            $this->transparent = true;
            $this->_image = imagecreatefrompng($path);
            break;
        case 'image/gif':
            $this->transparent = true;
            $this->_image = imagecreatefromgif($path);
            break;
        case 'image/webp':
            $this->_image = imagecreatefromwebp($path);
            break;
        case 'image/vnd.wap.wbmp':
            $this->_image = imagecreatefromwbmp($path);
            break;
        }

        if (!$this->_image) {
            throw new Exception(_('Could not open image.'));
        }

        $this->width = imagesx($this->_image);
        $this->height = imagesy($this->_image);
    }

    /**
     * Destroy image
     */
    function __destruct()
    {
        if ($this->_image) {
            imagedestroy($this->_image);
        }
    }

    /**
     * Flip the image
     *
     * @param string $axis What axis to flip the image on
     *
     * @return null
     */
    function flip($axis = 'x')
    {
        $temp = imagecreatetruecolor($this->width, $this->height);
        if ($this->transparent) {
            imagealphablending($temp, false);
        }
        if ($axis === 'x') {
            for ($x = 0; $x < $this->width; $x++) {
                imagecopy(
                    $temp,
                    $this->_image,
                    $this->width - $x - 1,
                    0,
                    $x,
                    0,
                    1,
                    $this->height
                );
            }
        } elseif ($axis === 'y') {
            for ($y=0; $y < $this->height; $y++) {
                imagecopy(
                    $temp,
                    $this->_image,
                    0,
                    $this->height - $y - 1,
                    0,
                    $y,
                    $this->width,
                    1
                );
            }
        }

        imagedestroy($this->_image);
        if ($this->transparent) {
            imagealphablending($temp, true);
        }
        $this->_image = $temp;
    }

    /**
     * Resample the image
     *
     * @param int  $width        The desired width
     * @param int  $height       The desired height
     * @param bool $retainAspect Add a border if aspects doesn't match
     *
     * @return null
     */
    function resize($width, $height, $retainAspect = true)
    {
        if (!$width
            || !$height
            || ($width == $this->width && $height == $this->height)
        ) {
            return;
        }

        if ($retainAspect) {
            $ratio = $this->width / $this->height;

            //which side exceeds the bounds the most
            if ($this->width / $width > $this->height / $height) {
                $height = round($width / $ratio);
            } else {
                $width = round($height * $ratio);
            }
        }

        $temp = imagecreatetruecolor($width, $height);
        if ($this->transparent) {
            imagealphablending($temp, false);
        }
        imagecopyresampled(
            $temp,
            $this->_image,
            0,
            0,
            0,
            0,
            $width,
            $height,
            $this->width,
            $this->height
        );
        imagedestroy($this->_image);
        if ($this->transparent) {
            imagealphablending($temp, true);
        }
        $this->_image = $temp;

        $this->width = imagesx($this->_image);
        $this->height = imagesy($this->_image);
    }

    /**
     * Crop image
     *
     * @param int $X      Where to start on the X axis
     * @param int $Y      Where to start on the Y axis
     * @param int $width  The desired width
     * @param int $height The desired height
     *
     * @return null
     */
    function crop($X = 0, $Y = 0, $width = 0, $height = 0)
    {
        if (!$width
            || !$height
            || ($width == $this->width && $height == $this->height)
        ) {
            return;
        }

        $temp = imagecreatetruecolor($width, $height); // Create a blank image
        if ($this->transparent) {
            imagealphablending($temp, false);
        }
        imagecopy($temp, $this->_image, 0, 0, $X, $Y, $width, $height);
        imagedestroy($this->_image);
        if ($this->transparent) {
            imagealphablending($temp, true);
        }
        $this->_image = $temp;

        $this->width = imagesx($this->_image);
        $this->height = imagesy($this->_image);
    }

    /**
     * Crop or enlarge image, centering the original image
     *
     * Fills background color with white if transparent = false
     *
     * @param int $width  The desired width
     * @param int $height The desired height
     *
     * @return null
     */
    function resizeCanvas($width, $height)
    {
        if (!$width || !$height) {
            return;
        }

        //FIXME image is not centered if size is smaller then current

        $temp = imagecreatetruecolor($width, $height); // Create a blank image
        if ($this->transparent) {
            imagealphablending($temp, false);
        }
        if (!$this->transparent) {
            $background = imagecolorallocate($temp, 255, 255, 255);
        } else {
            $background = imageColorAllocateAlpha($temp, 0, 0, 0, 127);
        }
        imagefilledrectangle($temp, 0, 0, $width, $height, $background);

        if ($this->width > $width) {
            $canvasX = round(($this->width - $width) / 2);
        } else {
            $canvasX = round(($width - $this->width) / 2);
        }
        if ($this->height > $height) {
            $canvasY = -round(($this->height - $height) / 2);
        } else {
            $canvasY = round(($height - $this->height) / 2);
        }

        imagecopy(
            $temp,
            $this->_image,
            $canvasX,
            $canvasY,
            0,
            0,
            $this->width,
            $this->height
        );
        imagedestroy($this->_image);
        if ($this->transparent) {
            imagealphablending($temp, true);
        }
        $this->_image = $temp;

        $this->width = imagesx($this->_image);
        $this->height = imagesy($this->_image);
    }

    /**
     * Scan image for a single colored border
     *
     * @param int $tolerance Tolerance of color change
     *
     * @return array
     */
    function findContent($tolerance = 5)
    {
        $rgb = imagecolorat($this->_image, 0, 0);
        $cr = ($rgb >> 16) & 0xFF;
        $cg = ($rgb >> 8) & 0xFF;
        $cb = $rgb & 0xFF;

        //Scan for left
        $x = 0;
        for ($ix = 0; $ix < $this->width; $ix++) {
            for ($iy = 0; $iy < $this->height; $iy++) {
                $rgb = imagecolorat($this->_image, $ix, $iy);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                if ($r < $cr - $tolerance || $r > $cr + $tolerance
                    || $g < $cg - $tolerance || $g > $cg + $tolerance
                    || $b < $cb - $tolerance || $b > $cb + $tolerance
                ) {
                    $x = $ix;
                    break 2;
                }
            }
        }

        //Scan for top
        $y = 0;
        for ($iy = 0; $iy < $this->height; $iy++) {
            for ($ix = 0; $ix < $this->width; $ix++) {
                $rgb = imagecolorat($this->_image, $ix, $iy);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                if ($r < $cr - $tolerance || $r > $cr + $tolerance
                    || $g < $cg - $tolerance || $g > $cg + $tolerance
                    || $b < $cb - $tolerance || $b > $cb + $tolerance
                ) {
                    $y = $iy;
                    break 2;
                }
            }
        }

        //Scan for right
        $width = 0;
        for ($ix = $this->width - 1; $ix >= 0; $ix--) {
            for ($iy = $this->height-1; $iy >= 0; $iy--) {
                $rgb = imagecolorat($this->_image, $ix, $iy);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                if ($r < $cr - $tolerance || $r > $cr + $tolerance
                    || $g < $cg - $tolerance || $g > $cg + $tolerance
                    || $b < $cb - $tolerance || $b > $cb + $tolerance
                ) {
                    $width = $ix - $x + 1;
                    break 2;
                }
            }
        }

        //Scan for bottom
        $height = 0;
        for ($iy = $this->height - 1; $iy >= 0; $iy--) {
            for ($ix = $this->width - 1; $ix >= 0; $ix--) {
                $rgb = imagecolorat($this->_image, $ix, $iy);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                if ($r < $cr - $tolerance || $r > $cr + $tolerance
                    || $g < $cg - $tolerance || $g > $cg + $tolerance
                    || $b < $cb - $tolerance || $b > $cb + $tolerance
                ) {
                    $height = $iy - $y + 1;
                    break 2;
                }
            }
        }

        return ['x' => $x, 'y' => $y, 'width' => $width, 'height' => $height];
    }

    /**
     * Save image
     *
     * @param string $path    Save path, if null the image will be send to the output
     * @param string $format  webp|png|jpeg|gif|wbmp
     * @param string $quality 0-100, only for jpeg
     *
     * @return null
     */
    function save($path = null, $format = 'jpeg', $quality = 80)
    {
        if ($format === 'png') {
            if ($this->transparent) {
                imagesavealpha($this->_image, true);
            }
            imagepng($this->_image, $path, 9, PNG_ALL_FILTERS);
            return;
        }

        if ($format === 'gif') {
            imagegif($this->_image, $path);
            return;
        }

        if ($format === 'webp') {
            imagewebp($this->_image, $path);
            return;
        }

        if ($this->transparent) {
            $this->transparent = false;
            $this->resizeCanvas($this->width, $this->height);
        }

        if ($format === 'wbmp') {
            imagewbmp($this->_image, $path);
            return;
        }

        imagejpeg($this->_image, $path, 80);
    }
}
