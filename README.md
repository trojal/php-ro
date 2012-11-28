# PHP-RO

Php-ro is a collection of classes/utilities to interact with RO files.

## Usage

Grf Reader:

    require('grf.php');
    $grf = new GrfReader('data.grf');
    $grf->get_header();
    $fileTable = $grf->get_file_table();
    foreach ($fileTable->files as $file) {
        if($grf->is_file($file))
              $grf->get_file_contents($file);
    }


Sprite Reader:

    include('DataReader.php');
    include('Sprite.php');
    include('SpriteFrame.php');
    include('Palette.php');
    
    $elderSprite = new Sprite(
        new DataReader('elder.spr')
        );
    file_put_contents('test.gif', $elderSprite->getImage(13));


DataReader constants:

    construct($dataSource, $type = PHPRO_DATA_FILE)
    dataTypes: PHPRO_DATA_FILE, PHPRO_DATA_STRING


getImage constants:

    getImage($frameNumber, $imageType = PHPRO_IMG_GIF)
    imageTypes: PHPRO_IMG_GIF, PHPRO_IMG_PNG, PHPRO_IMG_JPG
