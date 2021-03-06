# Shortdark/Socket

Shortdark/Socket takes up to 4 series of date-based data in an array and creates a SVG graph out of it.

## Installation

Install the latest version with

```bash
$ composer require shortdark/socket
```

## Basic Usage

```php
<?php

require_once 'vendor/autoload.php';
$socket = new Shortdark\Socket;

$dataArray = [
    ['date'=>'2021-02-26', 'col1'=>123, 'col2'=> 456, 'col3'=>789],
    ['date'=>'2021-03-01', 'col1'=>123, 'col2'=> 456, 'col3'=>789],
    ['date'=>'2021-03-02', 'col1'=>123, 'col2'=> 456, 'col3'=>789],
    ['date'=>'2021-03-03', 'col1'=>123, 'col2'=> 456, 'col3'=>789],
    ['date'=>'2021-03-04', 'col1'=>123, 'col2'=> 456, 'col3'=>789],
    ['date'=>'2021-03-05', 'col1'=>123, 'col2'=> 456, 'col3'=>789],
];
$legends = ['Graph Title', ['pre ',' post'], ['', 'col 1 title', 'col 2 title', 'col 3 title']];

echo $socket->draw_svg($dataArray, $legends);


```

### Author

Neil Ludlow - <neil@shortdark.net> - <https://shortdark.co.uk>
