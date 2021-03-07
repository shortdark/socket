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

require_once 'vendor/autoload.php';

$socket = new Shortdark\Socket;

$dataArray = [
    ['date'=>'2021-03-05', 'col1'=>116, 'col2'=> 156, 'col3'=>125],
    ['date'=>'2021-03-04', 'col1'=>115, 'col2'=> 155, 'col3'=>109],
    ['date'=>'2021-03-03', 'col1'=>114, 'col2'=> 154, 'col3'=>104],
    ['date'=>'2021-03-02', 'col1'=>113, 'col2'=> 153, 'col3'=>99],
    ['date'=>'2021-03-01', 'col1'=>112, 'col2'=> 152, 'col3'=>94],
    ['date'=>'2021-02-26', 'col1'=>111, 'col2'=> 151, 'col3'=>89]
];

$legends = ['Graph Title', ['pre ',' post'], ['', 'Column One Title', 'col 2 title', 'col 3 title']];

echo $socket->draw_svg($dataArray, $legends);
```

### Data Array

* The graph can be empty (0 lines) or have up to 4 lines.
* The number of lines is dependent on the data array that is passed in.
* The columns must be named 'date', 'col1', 'col2', 'col3' and 'col4'.
* 'date' must be a string, format: '2021-02-26'.
* The Y-axis data can be integers or floats.
* The order of the data matters, the array should start with the newest  and work backwards.

### Legends Array

* The $legends array is optional, it allows you to describe each line.
* Each title is followed by the latest value. 
* For col1 this example would look like: "Column One Title pre 116 post"
* The idea is that 'pre ' could be '£' or '$' and ' post' could be '%'.

### Cutomization

Defaults are shown, below. Each of these variables can be altered, if desired.

* **width_of_svg** INT (default: 1400)
* **height_of_svg** INT (default: 540)
* **separator** FLOAT (default: 15)
* **iterations** INT (default: 10)
* **branding_url** STRING (default: 'https://shortdark.co.uk')
* **branding_text** STRING (default: 'shortdark.co.uk')
* **brand_x_from_right** INT (default: 120)
* **brand_y_from_bottom** INT (default: 15)
* **colors** ARRAY (default ['col1' => 'green', 'col2' => 'blue', 'col3' => 'red', 'col4' => 'orange'])

You can modify any/all of the above like so...

    $socket = new Shortdark\Socket;
    
    // Change the width of the SVG
    $socket->width_of_svg = 1000;
    
    // Change the height of the SVG
    $socket->height_of_svg = 500;

    // The distance in pixels between data points on the X-axis
    $socket->separator = 1.5;

    // The number of iterations on the Y-axis
    $socket->iterations = 10;
    
    // The text/link in the bottom right hand corner
    $socket->branding_url = '';
    $socket->branding_text = '';
    $socket->brand_x_from_right = 0;
    $socket->brand_y_from_bottom = 0;

    // The color of each line
    $socket->colors = [
        'col1' => 'blue',
        'col2' => 'green',
        'col3' => 'orange',
        'col4' => 'red'
    ];
    
    echo $socket->draw_svg($dataArray, $legends);



### Author

Neil Ludlow - <neil@shortdark.net> - <https://shortdark.co.uk>
