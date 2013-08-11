#!/usr/bin/php
<?php

namespace Trojal\PhpRo;

include('DataReader.php');
include('Sprite.php');
include('SpriteFrame.php');
include('Palette.php');
include('Grf.php');
include('Action.php');
include('ActionMovement.php');
include('ActionFrame.php');
include('ActionLayer.php');
include('GIFEncoder.php');
include('SpriteTools.php');

/*$elderSprite = new Sprite(
    new DataReader('ro-files/elder.spr')
);
file_put_contents('ro-files/test.gif', $elderSprite->getImage(13));*/

$elderSprite = new Sprite(
    new DataReader('ro-files/vocal.spr')
);
$elderAction = new Action(
    new DataReader('ro-files/vocal.act')
);
$elderAction->setSprite($elderSprite);
file_put_contents('ro-files/test.gif', $elderAction->getAnimation(32));

/*$troGrf = new Grf(
    new DataReader('/var/www/ro/tro1.grf')
);
var_dump($troGrf->getFilesArray());*/

/*$troGrf = new Grf(
    new DataReader('/var/www/ro/uv-ro1.grf')
);
var_dump($troGrf->getFile('data\sprite\¾ÆÀÌÅÛ\ÆÐ¹Ð¸®¸ðÀÚ.spr'));*/

/*$uvroGrf = new Grf(
    new DataReader('ro-files/uv-ro1.grf')
);
$sprFile = $uvroGrf->getFile('data\sprite\¾ÆÀÌÅÛ\ÆÐ¹Ð¸®¸ðÀÚ.spr');
$someSprite = new Sprite(
    new DataReader($sprFile, PHPRO_DATA_STRING)
);
file_put_contents('/Users/mhittinger/test.gif', $someSprite->getImage(0));*/

echo 'Check OK!';
