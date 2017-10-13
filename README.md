Image.php
================

[![Codacy Badge](https://api.codacy.com/project/badge/Grade/e795c8fb840040118bdba4f2a0ac64a1)](https://www.codacy.com/app/AJenbo/Image-php?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=AJenbo/Image.php&amp;utm_campaign=Badge_Grade)

Helper function for simple image manipulation using GD functions.

###Samples
Open an image, remove any boarder, resize to 64x64 and save it.

<pre><code>
$path = 'test.png';
$image = new Image($path);
$imageContent = $image->findContent();
$image->crop(
    $imageContent['x'],
    $imageContent['y'],
    $imageContent['width'],
    $imageContent['height']
);
$image->resize(64, 64);
$image->save($path, 'png');
</code></pre>

