# Shortdark/Socket

Shortdark/Socket takes up to 4 series of date-based data in an array and creates a SVG graph out of it.
This version currently assumes that the data will be from working days only, i.e. it expects not to have any data from 
weekends or bank holidays.

## Installation

```bash
$ composer require shortdark/socket
```

## Basic Usage

```php
<?php

// path to autoload file
require_once 'vendor/autoload.php';

$socket = new Shortdark\Socket;

// The graph can be anything up to four lines.
// Data can be integers or floats but the columns must be named col1, col2, col3 and col4.
// Date must be a string, format: '2021-02-26'
$dataArray = [
    ['date'=>'2021-02-26', 'col1'=>111, 'col2'=> 151, 'col3'=>89],
    ['date'=>'2021-03-01', 'col1'=>112, 'col2'=> 152, 'col3'=>94],
    ['date'=>'2021-03-02', 'col1'=>113, 'col2'=> 153, 'col3'=>99],
    ['date'=>'2021-03-03', 'col1'=>114, 'col2'=> 154, 'col3'=>104],
    ['date'=>'2021-03-04', 'col1'=>115, 'col2'=> 155, 'col3'=>109],
    ['date'=>'2021-03-05', 'col1'=>116, 'col2'=> 156, 'col3'=>125],
];

// The $legends array is optional, it allows you to describe each line.
// Each title is followed by the latest value, for col1 this example would look like:
// "Column One Title pre 116 post"
$legends = ['Graph Title', ['pre ',' post'], ['', 'Column One Title', 'col 2 title', 'col 3 title']];

// echo the SVG graph to the page...
echo $socket->draw_svg($dataArray, $legends);


```

### Author

Neil Ludlow - <neil@shortdark.net> - <https://shortdark.co.uk>
