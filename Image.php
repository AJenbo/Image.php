<?php namespace AJenbo;

/**
 * @license  LGPL http://www.gnu.org/licenses/lgpl.html
 * @link     https://github.com/AJenbo/Image.php
 */

class Image
{
    private $image = null;
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
    public function __construct(string $path)
    {
        if (!$path) {
            throw new Exception(_('No path specified!'));
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $path);
        finfo_close($finfo);
        $mimeType = explode(';', $mimeType);
        $mimeType = $mimeType[0];

        switch ($mimeType) {
            case 'image/jpeg':
                $this->image = imagecreatefromjpeg($path);
                break;
            case 'image/png':
                $this->transparent = true;
                $this->image = imagecreatefrompng($path);
                break;
            case 'image/gif':
                $this->transparent = true;
                $this->image = imagecreatefromgif($path);
                break;
            case 'image/webp':
                $this->image = imagecreatefromwebp($path);
                break;
            case 'image/vnd.wap.wbmp':
                $this->image = imagecreatefromwbmp($path);
                break;
        }

        if (!$this->image) {
            throw new Exception(_('Could not open image.'));
        }

        $this->width = imagesx($this->image);
        $this->height = imagesy($this->image);
    }

    /**
     * Clear image from memory
     */
    public function __destruct()
    {
        if ($this->image) {
            imagedestroy($this->image);
        }
    }

    /**
     * Flip the image
     *
     * @param string $axis Whether to flip along the 'x' or 'y' axis
     *
     * @return null
     */
    public function flip(string $axis = 'x')
    {
        $temp = imagecreatetruecolor($this->width, $this->height);
        if ($this->transparent) {
            imagealphablending($temp, false);
        }
        if ($axis === 'x') {
            for ($x = 0; $x < $this->width; $x++) {
                imagecopy(
                    $temp,
                    $this->image,
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
                    $this->image,
                    0,
                    $this->height - $y - 1,
                    0,
                    $y,
                    $this->width,
                    1
                );
            }
        }

        imagedestroy($this->image);
        if ($this->transparent) {
            imagealphablending($temp, true);
        }
        $this->image = $temp;
    }

    /**
     * Resample the image
     *
     * @param int  $width        The desired width
     * @param int  $height       The desired height
     * @param bool $retainAspect Add a border if aspects don't match
     *
     * @return null
     */
    public function resize(int $width, int $height, bool $retainAspect = true)
    {
        if (!$width
            || !$height
            || ($width == $this->width && $height == $this->height)
        ) {
            return;
        }

        if ($retainAspect) {
            $ratio = $this->width / $this->height;

            // Which side most exceeds the bounds
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
            $this->image,
            0,
            0,
            0,
            0,
            $width,
            $height,
            $this->width,
            $this->height
        );
        imagedestroy($this->image);
        if ($this->transparent) {
            imagealphablending($temp, true);
        }
        $this->image = $temp;

        $this->width = imagesx($this->image);
        $this->height = imagesy($this->image);
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
    public function crop(int $X = 0, int $Y = 0, int $width = 0, int $height = 0)
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
        imagecopy($temp, $this->image, 0, 0, $X, $Y, $width, $height);
        imagedestroy($this->image);
        if ($this->transparent) {
            imagealphablending($temp, true);
        }
        $this->image = $temp;

        $this->width = imagesx($this->image);
        $this->height = imagesy($this->image);
    }

    /**
     * Resize image canvace, centering the original image
     *
     * Fills background color with white if image isn't transparent
     *
     * @param int $width  The desired width
     * @param int $height The desired height
     *
     * @return null
     */
    public function resizeCanvas(int $width, int $height)
    {
        if (!$width || !$height) {
            return;
        }

        //FIXME When cropping, the image is not centered

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
            $this->image,
            $canvasX,
            $canvasY,
            0,
            0,
            $this->width,
            $this->height
        );
        imagedestroy($this->image);
        if ($this->transparent) {
            imagealphablending($temp, true);
        }
        $this->image = $temp;

        $this->width = imagesx($this->image);
        $this->height = imagesy($this->image);
    }

    /**
     * Scan image for a single colored border
     *
     * @param int $tolerance Tolerance of color variation
     *
     * @return array
     */
    public function findContent(int $tolerance = 5): array
    {
        $rgb = imagecolorat($this->image, 0, 0);
        $cr = ($rgb >> 16) & 0xFF;
        $cg = ($rgb >> 8) & 0xFF;
        $cb = $rgb & 0xFF;

        // Scan for left edge
        $x = 0;
        for ($ix = 0; $ix < $this->width; $ix++) {
            for ($iy = 0; $iy < $this->height; $iy++) {
                $rgb = imagecolorat($this->image, $ix, $iy);
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

        // Scan for top edge
        $y = 0;
        for ($iy = 0; $iy < $this->height; $iy++) {
            for ($ix = 0; $ix < $this->width; $ix++) {
                $rgb = imagecolorat($this->image, $ix, $iy);
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

        // Scan for right edge
        $width = 0;
        for ($ix = $this->width - 1; $ix >= 0; $ix--) {
            for ($iy = $this->height-1; $iy >= 0; $iy--) {
                $rgb = imagecolorat($this->image, $ix, $iy);
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

        // Scan for bottom edge
        $height = 0;
        for ($iy = $this->height - 1; $iy >= 0; $iy--) {
            for ($ix = $this->width - 1; $ix >= 0; $ix--) {
                $rgb = imagecolorat($this->image, $ix, $iy);
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
     * @param string $path    Save path, if null the image will be sent to the output
     * @param string $format  webp|png|jpeg|gif|wbmp
     * @param string $quality 0-100, only for jpeg
     *
     * @return null
     */
    public function save(string $path = null, string $format = 'jpeg', int $quality = 80)
    {
        if ($format === 'png') {
            if ($this->transparent) {
                imagesavealpha($this->image, true);
            }
            imagepng($this->image, $path, 9, PNG_ALL_FILTERS);
            return;
        }

        if ($format === 'gif') {
            imagegif($this->image, $path);
            return;
        }

        if ($format === 'webp') {
            imagewebp($this->image, $path);
            return;
        }

        if ($this->transparent) {
            $this->transparent = false;
            $this->resizeCanvas($this->width, $this->height);
        }

        if ($format === 'wbmp') {
            imagewbmp($this->image, $path);
            return;
        }

        imagejpeg($this->image, $path, 80);
    }
}
