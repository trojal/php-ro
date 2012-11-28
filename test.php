#!/usr/bin/php
<?php

namespace Trojal\PhpRo;

include('DataReader.php');
include('Sprite.php');
include('SpriteFrame.php');
include('Palette.php');
include('Grf.php');

/*$elderSprite = new Sprite(
    new DataReader('/var/www/ro/elder.spr')
);
file_put_contents('/var/www/ro/test.gif', $elderSprite->getImage(13));*/

/*$troGrf = new Grf(
    new DataReader('/var/www/ro/tro1.grf')
);
var_dump($troGrf->getFilesArray());*/

/*$troGrf = new Grf(
    new DataReader('/var/www/ro/uv-ro1.grf')
);
var_dump($troGrf->getFile('data\sprite\¾ÆÀÌÅÛ\ÆÐ¹Ð¸®¸ðÀÚ.spr'));*/

$uvroGrf = new Grf(
    new DataReader('/var/www/ro/uv-ro1.grf')
);
$sprFile = $uvroGrf->getFile('data\sprite\¾ÆÀÌÅÛ\ÆÐ¹Ð¸®¸ðÀÚ.spr');
$someSprite = new Sprite(
    new DataReader($sprFile, PHPRO_DATA_STRING)
);
file_put_contents('/var/www/ro/test.gif', $someSprite->getImage(0));

echo 'Check OK!';
