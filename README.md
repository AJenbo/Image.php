Image.php
================

Helper function for simple image manipulation using GD functions.

###Samples
Open an image, remove any boarder, resize to 64x64 and save it.

`
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
`

