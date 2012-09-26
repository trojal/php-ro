# PHP-RO

Php-ro is a collection of classes/utilities to interact with RO files.

## Usage

Grf Reader:
```require('grf.php');
$grf = new GrfReader('data.grf');
$grf->get_header();
$fileTable = $grf->get_file_table();
foreach ($fileTable->files as $file) {
    if($grf->is_file($file))
          $grf->get_file_contents($file);
}
```

Spr Reader:
```require('spr.php');
$sprite = new SpriteReader('npc.spr');
$sprite->get_header();
$sprite->get_frames();
$sprite->get_palette();
$sprite->get_frame_data(1);
$img = $sprite->get_frame_image(1, 'gif');
file_put_contents('npc.gif', $img);
```