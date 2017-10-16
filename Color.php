<?php namespace AJenbo;

/**
 * @license  LGPL http://www.gnu.org/licenses/lgpl.html
 *
 * @see     https://github.com/AJenbo/Image.php
 */
class Color
{
    public $red = 0;
    public $green = 0;
    public $blue = 0;

    /**
     * @param resource $image GD image resouce
     * @param int      $left
     * @param int      $top
     */
    public function __construct($image, int $left, int $top)
    {
        $colorindex = imagecolorat($image, $left, $top);
        $this->red = ($colorindex >> 16) & 0xFF;
        $this->green = ($colorindex >> 8) & 0xFF;
        $this->blue = $colorindex & 0xFF;
    }

    /**
     * Compare two colors to see if they fall with in the tolerance
     *
     * Comparison is done in RGB space
     */
    public function isSimilar(self $color, int $tolerance = 0): bool
    {
        if ($color->red < $this->red - $tolerance || $color->red > $this->red + $tolerance
            || $color->green < $this->green - $tolerance || $color->green > $this->green + $tolerance
            || $color->blue < $this->blue - $tolerance || $color->red > $this->blue + $tolerance
        ) {
            return true;
        }

        return false;
    }
}
