<?php namespace AJenbo;

use AJenbo\ImagePhp\Exception;

/**
 * @license  LGPL http://www.gnu.org/licenses/lgpl.html
 *
 * @see     https://github.com/AJenbo/Image.php
 */
class Image
{
    public $transparent = false;

    private $image;
    private $width = 0;
    private $height = 0;

    /**
     * Load image from file.
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

        imagesetinterpolation($this->image, IMG_BICUBIC);

        $this->width = imagesx($this->image);
        $this->height = imagesy($this->image);
    }

    /**
     * Clear image from memory.
     */
    public function __destruct()
    {
        if ($this->image) {
            imagedestroy($this->image);
        }
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * Rotate image by 90 or 270 degrees.
     *
     * @param int $degrees
     */
    public function rotate(int $degrees): void
    {
        if (!$degrees) {
            return;
        }

        $max = max($this->width, $this->height);

        $temp = $this->image;
        if (180 !== $degrees) {
            $temp = imagecreatetruecolor($max, $max);
            imagecopy($temp, $this->image, 0, 0, 0, 0, $this->width, $this->height);
            imagedestroy($this->image);
        }

        $this->image = imagerotate($temp, $degrees, 0, 1);
        imagedestroy($temp);

        if (180 !== $degrees) {
            $temp = imagecreatetruecolor($this->height, $this->width);

            $left = 0;
            $top = 0;
            if ($this->height !== $this->width) {
                if (90 === $degrees) {
                    $top = $max - $this->width;
                } elseif (270 === $degrees) {
                    $left = $max - $this->height;
                }
            }

            imagecopy($temp, $this->image, 0, 0, $left, $top, $this->height, $this->width);
            imagedestroy($this->image);
            $this->image = $temp;
        }

        $this->width = imagesx($this->image);
        $this->height = imagesy($this->image);
    }

    /**
     * Flip the image.
     *
     * @param string $axis Whether to flip along the 'x' or 'y' axis
     */
    public function flip(string $axis = 'x'): void
    {
        $temp = imagecreatetruecolor($this->width, $this->height);
        if ($this->transparent) {
            imagealphablending($temp, false);
        }

        $left = 0;
        $width = $this->width;
        $height = 1;
        $destLeft = 0;
        $lines = $this->height;
        if ('x' === $axis) {
            $width = 1;
            $height = $this->height;
            $lines = $this->width;
        }

        for ($line = 0; $line < $lines; ++$line) {
            $top = $this->height - $line - 1;
            $destTop = $line;
            if ('x' === $axis) {
                $left = $this->width - $line - 1;
                $top = 0;
                $destLeft = $line;
                $destTop = 0;
            }

            imagecopy($temp, $this->image, $left, $top, $destLeft, $destTop, $width, $height);
        }

        imagedestroy($this->image);
        if ($this->transparent) {
            imagealphablending($temp, true);
        }
        $this->image = $temp;
    }

    /**
     * Resample the image.
     *
     * @param int  $width        The desired width
     * @param int  $height       The desired height
     * @param bool $retainAspect Add a border if aspects don't match
     */
    public function resize(int $width, int $height, bool $retainAspect = true): void
    {
        if ($width === $this->width && $height === $this->height) {
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
     * Crop image.
     *
     * @param int $left      Where to start on the X axis
     * @param int $top    Where to start on the Y axis
     * @param int $width  The desired width
     * @param int $height The desired height
     */
    public function crop(int $left = 0, int $top = 0, int $width = 0, int $height = 0): void
    {
        if ($width === $this->width && $height === $this->height) {
            return;
        }

        $temp = imagecreatetruecolor($width, $height); // Create a blank image
        if ($this->transparent) {
            imagealphablending($temp, false);
        }
        imagecopy($temp, $this->image, 0, 0, $left, $top, $width, $height);
        imagedestroy($this->image);
        if ($this->transparent) {
            imagealphablending($temp, true);
        }
        $this->image = $temp;

        $this->width = imagesx($this->image);
        $this->height = imagesy($this->image);
    }

    /**
     * Resize image canvace, centering the original image.
     *
     * Fills background color with white if image isn't transparent
     *
     * @param int $width  The desired width
     * @param int $height The desired height
     */
    public function resizeCanvas(int $width, int $height): void
    {
        if (!$width || !$height) {
            return;
        }

        //FIXME When cropping, the image is not centered

        $temp = imagecreatetruecolor($width, $height); // Create a blank image
        if ($this->transparent) {
            imagealphablending($temp, false);
        }
        $background = imagecolorallocate($temp, 255, 255, 255);
        if ($this->transparent) {
            $background = imagecolorallocatealpha($temp, 0, 0, 0, 127);
        }
        imagefilledrectangle($temp, 0, 0, $width, $height, $background);

        $canvasX = (int) round(($width - $this->width) / 2);
        if ($this->width > $width) {
            $canvasX = (int) round(($this->width - $width) / 2);
        }
        $canvasY = (int) round(($height - $this->height) / 2);
        if ($this->height > $height) {
            $canvasY = (int) -round(($this->height - $height) / 2);
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
     * Scan image for a single colored border.
     *
     * @param int $tolerance Tolerance of color variation
     *
     * @return int[]
     */
    public function findContent(int $tolerance = 5): array
    {
        $colors = [
            imagecolorat($this->image, 0, 0),
            imagecolorat($this->image, $this->width - 1, $this->height - 1),
        ];
        foreach ($colors as $rgb) {
            $cr = ($rgb >> 16) & 0xFF;
            $cg = ($rgb >> 8) & 0xFF;
            $cb = $rgb & 0xFF;

            // Scan for left edge
            $left = 0;
            for ($iLeft = 0; $iLeft < $this->width; ++$iLeft) {
                for ($iTop = 0; $iTop < $this->height; ++$iTop) {
                    $rgb = imagecolorat($this->image, $iLeft, $iTop);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    if ($r < $cr - $tolerance || $r > $cr + $tolerance
                        || $g < $cg - $tolerance || $g > $cg + $tolerance
                        || $b < $cb - $tolerance || $b > $cb + $tolerance
                    ) {
                        $left = $iLeft;
                        break 2;
                    }
                }
            }

            // Scan for top edge
            $top = 0;
            for ($iTop = 0; $iTop < $this->height; ++$iTop) {
                for ($iLeft = 0; $iLeft < $this->width; ++$iLeft) {
                    $rgb = imagecolorat($this->image, $iLeft, $iTop);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    if ($r < $cr - $tolerance || $r > $cr + $tolerance
                        || $g < $cg - $tolerance || $g > $cg + $tolerance
                        || $b < $cb - $tolerance || $b > $cb + $tolerance
                    ) {
                        $top = $iTop;
                        break 2;
                    }
                }
            }

            // Scan for right edge
            $width = 0;
            for ($iLeft = $this->width - 1; $iLeft >= 0; --$iLeft) {
                for ($iTop = $this->height - 1; $iTop >= 0; --$iTop) {
                    $rgb = imagecolorat($this->image, $iLeft, $iTop);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    if ($r < $cr - $tolerance || $r > $cr + $tolerance
                        || $g < $cg - $tolerance || $g > $cg + $tolerance
                        || $b < $cb - $tolerance || $b > $cb + $tolerance
                    ) {
                        $width = $iLeft - $left + 1;
                        break 2;
                    }
                }
            }

            // Scan for bottom edge
            $height = 0;
            for ($iTop = $this->height - 1; $iTop >= 0; --$iTop) {
                for ($iLeft = $this->width - 1; $iLeft >= 0; --$iLeft) {
                    $rgb = imagecolorat($this->image, $iLeft, $iTop);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    if ($r < $cr - $tolerance || $r > $cr + $tolerance
                        || $g < $cg - $tolerance || $g > $cg + $tolerance
                        || $b < $cb - $tolerance || $b > $cb + $tolerance
                    ) {
                        $height = $iTop - $top + 1;
                        break 2;
                    }
                }
            }

            if ($width !== $this->width || $height !== $this->height) {
                break;
            }
        }

        return ['x' => $left, 'y' => $top, 'width' => $width, 'height' => $height];
    }

    /**
     * Save image.
     *
     * @param string|null $path    Save path, if null the image will be sent to the output
     * @param string      $format  webp|png|jpeg|gif|wbmp
     * @param int         $quality 0-100, only for jpeg
     */
    public function save(string $path = null, string $format = 'jpeg', int $quality = 80): void
    {
        if ('png' === $format) {
            if ($this->transparent) {
                imagesavealpha($this->image, true);
            }
            imagepng($this->image, $path, 9, PNG_ALL_FILTERS);

            return;
        }

        if ('gif' === $format) {
            imagegif($this->image, $path);

            return;
        }

        if ('webp' === $format) {
            imagewebp($this->image, $path);

            return;
        }

        if ($this->transparent) {
            $this->transparent = false;
            $this->resizeCanvas($this->width, $this->height);
        }

        if ('wbmp' === $format) {
            imagewbmp($this->image, $path);

            return;
        }

        imagejpeg($this->image, $path, $quality);
    }
}
