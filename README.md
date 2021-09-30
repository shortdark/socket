# Shortdark/Socket

Shortdark/Socket creates an SVG line graph from and array of up to 10 series of date-based data.
This version currently assumes that the data will be from week days only, i.e. it expects not to have any data from 
weekends.
Public holidays should be included in the array.
Null values are not represented by a line, graph lines that contain null values will have gaps.
More than 10 lines are possible on the graph if the extra line colors are specified manually.

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

echo $socket->draw_svg($dataArray);
```

### Data Array

* The graph can be empty (0 lines) or have up to 10 graph lines. 
* More than 10 graph lines can be passed in, but you would need to manually assign colors for the columns beyond the starting 10.
* The number of lines is dependent on the data array that is passed in.
* The columns must be named 'date', 'col1', 'col2', 'col3', 'col4', etc.
* 'date' must be a string, format: '2021-02-26'.
* The Y-axis data can be integers, floats or null.
* Null values will not be shown on the graph.
* The array should start with the newest date and work backwards. 
* It's assumed that the data will be continuous (mon-fri) without gaps in the data. Data can be 7 days a week, however, the week lines are currently on Friday.
* Public holidays should also be included in the array with either the same value from the previous day, or with the value as null.

### Optional Cutomization

Defaults are shown, below. Each of these variables can be altered, if desired.

* **width_of_svg** INT (default: 1400)
* **height_of_svg** INT (default: 540)
* **iterations** INT (default: 10)
* **colors** ARRAY (default ['col1' => 'dark green', 'col2' => 'medium blue', 'col3' => 'orange red', 'col4' => 'orange', ...])
* **show_week_numbers** (default: false)
* **show_legend_box** BOOL (default: true)
* **legend_box_width** INT (default: 200)
* **legends** ARRAY (default ['col1' => 'Column 1', 'col2' => 'Column 2', 'col3' => 'Column 3', 'col4' => 'Column 4', ...])
* **graph_name** STRING (default: 'Key')
* **show_last_value_in_legend** BOOL (default: false)
* **legend_pre_value** STRING (default: '')
* **legend_post_value** STRING (default: '')
* **branding_url** STRING (default: '')
* **branding_text** STRING (default: '')
* **brand_x_from_right** INT (default: 120)
* **brand_y_from_bottom** INT (default: 15)

You can modify any/all the above like so...

    $socket = new Shortdark\Socket;
    
    // Change the width of the SVG
    $socket->width_of_svg = 1000;
    
    // Change the height of the SVG
    $socket->height_of_svg = 500;

    // The number of horizontal lines
    $socket->iterations = 10;

    // The color of each line
    $socket->colors = [
        'col1' => 'blue',
        'col2' => 'green',
        'col3' => 'orange',
        'col4' => 'red'
    ];

    // Whether to show the week number from the start of the year (every fifth week: 5, 10...)
    $socket->show_week_numbers = true;
    
    // Optional, show a legends box to describe each graphline
    $socket->show_legend_box = true;

    // Optional, legends box, specify the width of the box
    $socket->legend_box_width = 150;
    
    // Give each graph line a label in the "legends box"
    $socket->legends = [
        'col1' => 'This is column 1',
        'col2' => 'The second column',
        'col3' => 'Third',
        'col4' => 'Lastly... another'
    ];
    $socket->graph_name = 'Something vs. Something else vs Another';
    $socket->show_last_value_in_legend = true;
    $socket->legend_pre_value = '&dollar;';
    $socket->legend_post_value = '&#37;';

    // The text/link in the bottom right hand corner
    $socket->branding_text = 'Shortdark Web Dev';
    $socket->branding_url = 'https://shortdark.co.uk';
    $socket->brand_x_from_right = 0;
    $socket->brand_y_from_bottom = 0;
    
    echo $socket->draw_svg($dataArray);



### Author

Neil Ludlow - <neil@shortdark.net> - <https://shortdark.co.uk>
