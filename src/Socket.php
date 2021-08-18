<?php

/**
 * Socket - takes data as an array and plots it as a SVG graph.
 * PHP Version >= 7.0
 * Version 0.2.00
 * @package Socket
 * @link https://github.com/shortdark/socket/
 * @author Neil Ludlow (shortdark) <neil@shortdark.net>
 * @copyright 2021 Neil Ludlow
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @note This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Shortdark;
class Socket {

    /**
     * ################
     * ##
     * ##  CONFIG, CAN BE OVERWRITTEN
     * ##
     * ################
     */

    // Width of SVG bounds
    public $width_of_svg = 1400;

    // Height of SVG bounds
    public $height_of_svg = 540;

    // The distance in pixels between data points on the X-axis
    public $separator = 15;

    // The number of iterations on the Y-axis
    public $iterations = 10;

    public $colors = [
        'col1' => '#006400', // dark green
        'col2' => '#0000CD', // medium blue
        'col3' => '#FF4500', // orange red
        'col4' => '#FFA500', // orange
        'col5' => '#00FA9A', // medium spring green
        'col6' => '#663399', // rebecca purple
        'col7' => '#98FB98', // pale green
        'col8' => '#000080', // navy
        'col9' => '#800000', // maroon
        'col10' => '#6A5ACD', // slate blue
    ];

    // Optional, show a legends box to describe each graph line
    public $show_legend_box = true;

    // Optional, legends box, specify the width of the box
    public $legend_box_width = 200;

    // Optional, legends box, title in the legends box, default is 'Key' but could also be the title of the graph
    public $graph_name = 'Key';

    // Optional, legends box, descriptive strings for each of the 4 graph lines to be displayed in the legends box
    public $legends = [];

    // Optional, legends box, we can display the latest value for each line in the legend
    public $show_last_value_in_legend = false;

    // Optional, legends box, if last value is true, add string that would be before the value in the legend, e.g. $
    public $legend_pre_value = '';

    // Optional, legends box, if last value is true, add string that would be after the value in the legend, e.g. %
    public $legend_post_value = '';

    // Optional branding, no action required for no branding
    public $branding_text = ''; // String e.g. 'Shortdark Web Dev'
    public $branding_url = ''; // String e.g. 'https://shortdark.co.uk'
    public $brand_x_from_right = 120;
    public $brand_y_from_bottom = 15;

    /**
     * ################
     * ##
     * ##  CLASS VARIABLES
     * ##
     * ################
     */

    private $results = [];

    private $end_of_graph_x;

    private $end_of_graph_y;

    private $width_of_graph;

    private $height_of_graph;

    private $days_for_graph;

    private $start_axis;

    private $end_axis;

    private $graph_lines_count;

    private $data_points;

    /**
     * ################
     * ##
     * ##  SETUP METHODS
     * ##
     * ################
     */

    private function modify_separator_to_make_graph_fit_on_screen () {
        if ($this->data_points * $this->separator > $this->end_of_graph_x) {
            $this->separator = floor($this->end_of_graph_x / $this->data_points);
        }
    }

    private function assign_number_of_days()
    {
        // Only add the number of days for the size of graph that is being called
        $this->days_for_graph = (int)$this->data_points;
    }

    private function assign_dimensions_from_config ()
    {
        $this->end_of_graph_x = $this->width_of_svg - 30;
        $this->end_of_graph_y = $this->height_of_svg - 30;
        $this->width_of_graph = $this->width_of_svg - 40;
        $this->height_of_graph = $this->height_of_svg - 40;
    }

    private function add_branding (): string
    {
        if ( '' !== $this->branding_text && '' !== $this->branding_url ) {
            $logox = $this->end_of_graph_x - $this->brand_x_from_right;
            $logoy = $this->end_of_graph_y - $this->brand_y_from_bottom;
            return "<a xlink:href=\"{$this->branding_url}\" xlink:title=\"{$this->branding_text}\"><text x=\"$logox\" y=\"$logoy\" font-family=\"sans-serif\" font-size=\"16px\" fill=\"black\">{$this->branding_text}</text></a>";
        }
        return '';
    }

    /**
     * ################
     * ##
     * ##  METHODS
     * ##
     * ################
     */

    public function draw_svg(array $dataArray): string
    {
        $this->assign_dimensions_from_config();

        $this->results = $dataArray;

        $this->get_data_limits();

        $this->check_legends();

        return $this->draw_graph();
    }

    private function draw_graph(): string
    {
        $graph = $this->set_up_svg_graph();
        $graph .= $this->set_up_svg_axis();

        $graph .= $this->draw_all_graph_lines();

        $graph .= $this->add_weeks_months_years();
        $graph .= $this->add_key();
        $graph .= $this->add_branding();
        $graph .= "</svg>";
        return $graph;
    }

    private function get_data_limits()
    {
        $this->graph_lines_count = count($this->results[0]) -1;
        $this->data_points = count($this->results);

        $this->modify_separator_to_make_graph_fit_on_screen();

        $this->assign_number_of_days();

        $this->end_axis = $this->getHighest() ?? 100;
        $this->start_axis = $this->getLowest() ?? 0;
    }

    private function check_legends ()
    {
        for ($i = 1; $i <= $this->graph_lines_count; $i++) {
            $colName = 'col'.$i;
            if (empty($this->legends[$colName])) {
                $this->legends[$colName] = 'Column '.$i;
            }
        }
    }

    private function draw_all_graph_lines(): string
    {
        $n = 1;
        $columnName = 'col' . $n;
        $output = '';
        while (isset($this->results[0][$columnName])) {
            $output .= $this->draw_graph_line($columnName);
            $n++;
            $columnName = 'col' . $n;
        }
        return $output;
    }

    private function getHighest (): int
    {
        $i=0;
        $max = $this->results[$i]['col1'];
        while (isset($this->results[$i]['col1'])) {
            for ($j=1; $j <= $this->graph_lines_count; $j++) {
                $colName = 'col' . $j;
                if (isset($this->results[$i][$colName]) && $this->results[$i][$colName] > $max) {
                    $max = $this->results[$i][$colName];
                }
            }
            $i++;
        }
        $max = (int) ceil($max);
        if (8 < $max) {
            while ( $max % 10 !== 0 ) {
                $max++;
            }
        }
        return $max;
    }

    private function getLowest (): int
    {
        $i=0;
        $min = $this->results[$i]['col1'];
        while (isset($this->results[$i]['col1'])) {
            for ($j=1; $j <= $this->graph_lines_count; $j++) {
                $colName = 'col' . $j;
                if ( isset($this->results[$i][$colName]) && $this->results[$i][$colName] < $min) {
                    $min = $this->results[$i][$colName];
                }
            }
            $i++;
        }
        $min = (int) floor($min);
        if (10 < $min || 10 < $this->end_axis) {
            while ( $min % 10 !== 0 ) {
                $min--;
            }
        }
        return $min;
    }

    private function set_up_svg_graph(): string
    {
        $graph = "<svg id=\"graph\" width=\"" . $this->width_of_svg . "px\" height=\"" . $this->height_of_svg . "px\" version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\">";
        $graph .= "<path stroke=\"black\" stroke-width=\"0.4\" d=\"M10 10 v $this->height_of_graph\"/>";
        $graph .= "<path stroke=\"black\" stroke-width=\"0.4\" d=\"M$this->end_of_graph_x 10 v $this->height_of_graph\"/>";
        $graph .= "<path stroke=\"black\" stroke-width=\"0.4\" d=\"M10 $this->end_of_graph_y h $this->width_of_graph\"/>";
        return $graph;
    }

    private function set_up_svg_axis(): string
    {
        $graph = '';
        $zero_line_drawn = false;
        $data_range = $this->end_axis - $this->start_axis;
        $iterationsInt = $this->iterations;
        $value_per_iteration = $data_range / $iterationsInt;
        for ($i = 0; $i <= $iterationsInt; $i++) {
            $heightAtt = $this->end_of_graph_y - ($i * $this->height_of_graph / $iterationsInt);
            $textVal = $this->start_axis + ($i * $value_per_iteration);
            $graph .= "<text x=\"1\" y=\"$heightAtt\" font-family=\"sans-serif\" font-size=\"12px\" fill=\"black\">$textVal</text>";
            $graph .= "<text x=\"$this->width_of_graph\" y=\"$heightAtt\" font-family=\"sans-serif\" font-size=\"12px\" fill=\"black\">$textVal</text>";
            $graph .= "<path stroke=\"black\" stroke-width=\"0.2\" d=\"M10 $heightAtt h $this->width_of_graph\"/>";
            if (0 === $textVal) {
                $zero_line_drawn = true;
            }
        }
        if (true !== $zero_line_drawn && 0 > $this->start_axis && 0 < $this->end_axis) {
            $pixels_per_unit = $this->height_of_graph / ($this->end_axis - $this->start_axis);
            $heightAtt = $this->end_of_graph_y + $this->start_axis * $pixels_per_unit;
            $graph .= "<path stroke=\"black\" stroke-width=\"0.4\" d=\"M10 $heightAtt h $this->width_of_graph\"/>";
        }
        return $graph;
    }

    private function draw_graph_line($columnName): string
    {
        $g = 0;
        $color = $this->colors[$columnName];
        $line='';
        $pixels_per_unit = $this->height_of_graph / ($this->end_axis - $this->start_axis);
        if (isset($this->results[$g][$columnName])) {
            while (isset($this->results[$g][$columnName]) && $g < $this->days_for_graph) {
                $xvalue = $this->end_of_graph_x - ($g * $this->separator);
                $graphVal = (float)$this->results[$g][$columnName];
                $yvalue = $this->end_of_graph_y - (($graphVal - $this->start_axis) * $pixels_per_unit);
                if (0 <= $xvalue) {
                    if (0 === $g) {
                        $line = "<path d=\"M$xvalue $yvalue";
                    } else {
                        $line .= " L$xvalue $yvalue";
                    }
                }
                $g++;
            }
            $line .= "\" stroke-linejoin=\"round\" stroke=\"$color\" fill=\"none\"/>";
        }
        return $line;
    }

    private function add_weeks_months_years(): string
    {
        $d = 0;
        $graph = '';
        if ($this->results[$d]['date']) {
            $weeklegendx = ($this->width_of_graph / 2) - 20;
            $graph .= "<text x=\"$weeklegendx\" y=\"30\" font-family=\"sans-serif\" font-size=\"12px\" fill=\"black\">Week Numbers</text>";
            while ($this->results[$d]['date']) {
                $dateval = $this->results[$d]['date'];
                $xvalue = $this->end_of_graph_x - ($d * $this->separator);
                if (10 <= $xvalue) {
                    $year = substr($dateval, 0, 4);
                    $month = substr($dateval, 5, 2);
                    $day = (int)substr($dateval, 8, 2);
                    $numericday = date("w", mktime(0, 0, 0, $month, $day, $year));
                    // If there is a bank holiday on a Friday and it is not the end of the month we need to add the week line.
                    // Hard-coding but needs rewriting...
                    if (5 == $numericday || '2020-12-29' === $dateval ) {
                        $weeknumber = (int)date("W", mktime(0, 0, 0, $month, $day, $year));
                        $graph .= "<path stroke=\"green\" stroke-width=\"0.2\" d=\"M$xvalue 10 v $this->height_of_graph\"/>";
                        if (0 == $weeknumber % 5) {
                            $graph .= "<text x=\"$xvalue\" y=\"50\" font-family=\"sans-serif\" font-size=\"12px\" fill=\"black\">$weeknumber</text>";
                        }
                    }
                    $dayofmonth = date("j", mktime(0, 0, 0, $month, $day, $year));
                    // The first working day of the month should be on the 1st, 2nd or 3rd unless it is on a Friday and a bank holiday.
                    // Hard-coding for the special case of January 2021, but needs rewriting...
                    if (
                        1 == $dayofmonth ||
                        ( 1 == $numericday && 4 == $dayofmonth && "01" == $month ) ||
                        ( 1 == $numericday && ( in_array( $dayofmonth, [2,3] ) ) )
                    ) {
                        $dayofyear = date("z", mktime(0, 0, 0, $month, $day, $year));
                        $monthwords = date("M", mktime(0, 0, 0, $month, $day, $year));
                        $yearwords = date("Y", mktime(0, 0, 0, $month, $day, $year));
                        $graph .= "<path stroke=\"black\" stroke-width=\"0.4\" d=\"M$xvalue 10 v $this->height_of_graph\"/>";
                        $monthlegendy = $this->height_of_graph + 20;
                        $graph .= "<text x=\"$xvalue\" y=\"$monthlegendy\" font-family=\"sans-serif\" font-size=\"12px\" fill=\"black\">$monthwords</text>";
                        // Hard-coding for the special case of January 2021, but needs rewriting...
                        if (0 == $dayofyear || ( 3 == $dayofyear && '2021' == $year ) ) {
                            $yearlegendy = $this->height_of_graph + 35;
                            $graph .= "<text x=\"$xvalue\" y=\"$yearlegendy\" font-family=\"sans-serif\" font-size=\"12px\" fill=\"black\">$yearwords</text>";
                        }
                    }
                }
                $d++;
            }
        }
        return $graph;
    }

    private function add_key(): string
    {
        if (false === $this->show_legend_box) {
            return '';
        }

        $lastValue='';
        $count = count($this->legends);
        $vertical = 45 + (30 * ($count));
        $upperName = strtoupper($this->graph_name);
        $graph = "<path fill-opacity=\"0.9\" d=\"M20 12 v{$vertical} h{$this->legend_box_width} v-{$vertical} h-{$this->legend_box_width}\" fill=\"white\"></path>";
        $graph .= "<text x=\"50\" y=\"40\" font-family=\"sans-serif\" font-size=\"16px\" fill=\"black\" text-decoration=\"underline\">{$upperName}</text>";

        for ($i=1; $i<=$count; $i++) {
            $column = 'col' . $i;
            if (isset($this->results[0][$column])) {
                $lineYstart = 25 + (30* $i);
                $lineYend = 45 + (30* $i);
                $textY = 40 + (30* $i);
                if (false !== $this->show_last_value_in_legend) {
                    $lastValue = "{$this->legend_pre_value}{$this->results[0][$column]}{$this->legend_post_value}";
                }
                $graph .= "<path d=\"M30 {$lineYstart} L40 {$lineYend}\" stroke-linejoin=\"round\" stroke=\"{$this->colors[$column]}\" fill=\"none\"/>";
                $graph .= "<text x=\"50\" y=\"{$textY}\" font-family=\"sans-serif\" font-size=\"16px\" fill=\"black\">{$this->legends[$column]} {$lastValue}</text>";
            }

        }
        return $graph;
    }

    public function __construct()
    {

    }





}

