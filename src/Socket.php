<?php

/**
 * Socket - takes data arrays and plots them as an SVG line or range graph with optional data points represented as dots.
 * PHP Version >= 7.0
 * Version 0.3.02
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

    public $show_week_numbers = false;

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

    public $filled_lines = false;

    public $nearest_value = false;

    /**
     * ################
     * ##
     * ##  CLASS VARIABLES
     * ##
     * ################
     */

    private $lines = [];

    private $points = [];

    private $end_of_graph_x;

    private $end_of_graph_y;

    private $width_of_graph;

    private $height_of_graph;

    private $start_axis;

    private $end_axis;

    private $pixels_per_unit_x;

    private $pixels_per_unit_y;

    private $graph_lines_count;

    private $number_of_days;

    private $number_of_points;





    /**
     * ################
     * ##
     * ##  PUBLIC METHODS
     * ##
     * ################
     */

    public function draw_svg (array $lineArray, array $pointArray=[]): string
    {
        // Ideally there would be 3 arrays: lines, points and ranges and all of them could be displayed at the same time
        // Or, the naming convention of $lineArray uses line1, line2 for lines, and range1, range2 for a range.
        // In that scenario, you might also get bar1, bar2 or different variations.

        $this->assign_dimensions_from_config();

        $this->lines = $lineArray;
        $this->points = $pointArray;

        $this->get_data_limits();

        $this->check_legends();

        return $this->draw_graph();
    }

    /**
     * ################
     * ##
     * ##  METHODS
     * ##
     * ################
     */

    private function draw_graph(): string
    {
        $graph = $this->set_up_svg_graph();
        $graph .= $this->set_up_svg_axis();

        if ($this->filled_lines !== true) {
            $graph .= $this->draw_all_graph_lines();
        } else {
            $graph .= $this->draw_filled_graph_lines();
        }

        $graph .= $this->add_data_points();

        $graph .= $this->add_weeks_months_years();
        $graph .= $this->add_key();
        $graph .= $this->add_branding();
        $graph .= "</svg>";
        return $graph;
    }

    private function get_data_limits()
    {
        $this->graph_lines_count = count($this->lines[0]) -1;
        $this->number_of_days = count($this->lines);

        $this->number_of_points = count($this->points);

        $this->modify_separator_to_make_graph_fit_on_screen();

        $this->end_axis = $this->getHighest() ?? 100;
        $this->start_axis = $this->getLowest() ?? 0;

        $this->pixels_per_unit_y = $this->height_of_graph / ($this->end_axis - $this->start_axis);
    }

    private function modify_separator_to_make_graph_fit_on_screen () {
        $this->pixels_per_unit_x = $this->width_of_graph / ($this->number_of_days - 1);
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
        $output = '';
        while ($n <= $this->graph_lines_count) {
            $columnName = 'col' . $n;
            $output .= $this->draw_graph_line($columnName);
            $n++;
        }
        return $output;
    }

    private function draw_filled_graph_lines(): string
    {
        $n = 1;
        $output = '';
        while ($n <= $this->graph_lines_count) {
            if ($n % 2 !== 0 && isset($this->lines[0]['col'.($n+1)])) {
                $output .= '<polygon points="';
            }
            $output .= $this->draw_filled_graph_line($n);
            if ($n % 2 === 0) {
                $color = $this->colors['col'.$n];
                $output .= "\" fill=\"$color\" stroke=\"$color\" opacity=\"0.5\" />";
            }
            $n++;
        }


        return $output;
    }

    private function getHighest (): int
    {
        // TODO: Refactor
        $i=$j=0;
        $max = $this->lines[$i]['col1'];
        // lines
        while ($i <= $this->number_of_days) {
            for ($j=1; $j <= $this->graph_lines_count; $j++) {
                $colName = 'col' . $j;
                if (isset($this->lines[$i][$colName]) && $this->lines[$i][$colName] > $max) {
                    $max = $this->lines[$i][$colName];
                }
            }
            $i++;
        }
        // points
        while ($j <= $this->number_of_points) {
            if (isset($this->points[$j]['value']) && $this->points[$j]['value'] > $max) {
                $max = $this->points[$j]['value'];
            }
            $j++;
        }
        $max = (int) ceil($max);
        if ($this->nearest_value === false) {
            if (8 < $max) {
                while ( $max % 10 !== 0 ) {
                    $max++;
                }
            }
        }
        return $max;
    }

    private function getLowest (): int
    {
        // TODO: Refactor
        $i=$j=0;
        $min = $this->lines[$i]['col1'];
        // lines
        while ($i <= $this->number_of_days) {
            for ($j=1; $j <= $this->graph_lines_count; $j++) {
                $colName = 'col' . $j;
                if ( isset($this->lines[$i][$colName]) && $this->lines[$i][$colName] < $min) {
                    $min = $this->lines[$i][$colName];
                }
            }
            $i++;
        }
        // points
        while ($j <= $this->number_of_points) {
            if (isset($this->points[$j]['value']) && $this->points[$j]['value'] < $min) {
                $min = $this->points[$j]['value'];
            }
            $j++;
        }
        $min = (int) floor($min);
        if ($this->nearest_value === false) {
            if (10 < $min || 10 < $this->end_axis) {
                while ( $min % 10 !== 0 ) {
                    $min--;
                }
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
            $heightAtt = $this->end_of_graph_y + $this->start_axis * $this->pixels_per_unit_y;
            $graph .= "<path stroke=\"black\" stroke-width=\"0.4\" d=\"M10 $heightAtt h $this->width_of_graph\"/>";
        }
        return $graph;
    }

    private function draw_graph_line ($columnName): string
    {
        $g = 0;
        $closed = true;
        $color = $this->colors[$columnName];
        $line='';

        while ($g <= $this->number_of_days) {
            if (isset($this->lines[$g][$columnName]) && null !== $this->lines[$g][$columnName]) {
                $xValue = $this->end_of_graph_x - ($g * $this->pixels_per_unit_x);
                $graphVal = (float)$this->lines[$g][$columnName];
                $yValue = $this->end_of_graph_y - (($graphVal - $this->start_axis) * $this->pixels_per_unit_y);

                if (0 === $g || $closed === true) {
                    $line .= "<path d=\"M$xValue $yValue";
                } else {
                    $line .= " L$xValue $yValue";
                }
                $closed = false;

            } elseif ('' !== $line && $closed !== true) {
                $line .= "\" stroke-linejoin=\"round\" stroke=\"$color\" fill=\"none\"/>";
                $closed = true;
            }
            $g++;
        }

        if (true !== $closed) {
            $line .= "\" stroke-linejoin=\"round\" stroke=\"$color\" fill=\"none\"/>";
        }
        return $line;
    }

    private function draw_filled_graph_line ($n): string
    {
        $line='';
        $columnName = 'col' . $n;
        if ($n % 2 !== 0) {
            $g = 0;
            while ($g <= $this->number_of_days) {
                if (isset($this->lines[$g][$columnName]) && null !== $this->lines[$g][$columnName]) {
                    $xValue = $this->end_of_graph_x - ($g * $this->pixels_per_unit_x);
                    $graphVal = (float)$this->lines[$g][$columnName];
                    $yValue = $this->end_of_graph_y - (($graphVal - $this->start_axis) * $this->pixels_per_unit_y);
                    $line .= " $xValue,$yValue";
                }
                $g++;
            }
        } elseif ($n % 2 === 0) {
            $g = $this->number_of_days;
            while ($g >= 0) {
                if (isset($this->lines[$g][$columnName]) && null !== $this->lines[$g][$columnName]) {
                    $xValue = $this->end_of_graph_x - ($g * $this->pixels_per_unit_x);
                    $graphVal = (float)$this->lines[$g][$columnName];
                    $yValue = $this->end_of_graph_y - (($graphVal - $this->start_axis) * $this->pixels_per_unit_y);
                    $line .= " $xValue,$yValue";
                }
                $g--;
            }
        }
        return $line;
    }

    private function add_data_points (): string
    {
        $graph = '';
        if (empty($this->points)) {
            return $graph;
        }

        $d = $this->number_of_days -1;
        while (0 <= $d) {
            $dateString = $this->lines[$d]['date'];
            $xValue = $this->end_of_graph_x - ($d * $this->pixels_per_unit_x);
            for ($i=0; $i < $this->number_of_points; $i++) {
                if ($this->points[$i]['date'] === $dateString) {
                    $point_color = 'black';
                    if ($this->points[$i]['volume'] < 0) {
                        $point_color = 'red';
                    }
                    $yValue = $this->end_of_graph_y - (((float) $this->points[$i]['value'] - $this->start_axis) * $this->pixels_per_unit_y);
                    $graph .= "<circle cx=\"$xValue\" cy=\"$yValue\" r=\"3\" fill='$point_color'/>";
                }
            }
            $d--;
        }
        return $graph;
    }


    private function add_weeks_months_years(): string
    {
        $d = $this->number_of_days -1;
        $graph = '';
        $currentMonth = 0;
        $currentYear = 0;
        if ($this->lines[$d]['date']) {
            if (true === $this->show_week_numbers) {
                $weekLegendX = ($this->width_of_graph / 2) - 20;
                $graph .= "<text x=\"$weekLegendX\" y=\"30\" font-family=\"sans-serif\" font-size=\"12px\" fill=\"black\">Week Numbers</text>";
            }
            while (0 <= $d) {
                $dateString = $this->lines[$d]['date'];
                $xValue = $this->end_of_graph_x - ($d * $this->pixels_per_unit_x);

                if (0 <= $xValue) {
                    $year = (int) substr($dateString, 0, 4);
                    $month = (int) substr($dateString, 5, 2);
                    $day = (int) substr($dateString, 8, 2);
                    $dayOfWeek = (int) date("w", mktime(0, 0, 0, $month, $day, $year));
                    if (5 === $dayOfWeek) {
                        $graph .= $this->drawWeekLine($month, $day, $year, $xValue);
                    }
                    if ($currentMonth !== $month) {
                        $graph .= $this->drawMonthLine($month, $day, $year, $xValue, $d);
                        $currentMonth = $month;
                    }
                    if ($currentYear !== $year) {
                        $graph .= $this->drawYearValue($month, $day, $year, $xValue);
                        $currentYear = $year;
                    }
                }
                $d--;
            }
        }
        return $graph;
    }

    private function drawWeekLine(int $month, int $day, int $year, float $xValue): string
    {
        $weekNumber = (int) date("W", mktime(0, 0, 0, $month, $day, $year));
        $output = "<path stroke=\"green\" stroke-width=\"0.2\" d=\"M$xValue 10 v $this->height_of_graph\"/>";
        if (0 === $weekNumber % 5 && true === $this->show_week_numbers) {
            $output .= "<text x=\"$xValue\" y=\"50\" font-family=\"sans-serif\" font-size=\"12px\" fill=\"black\">$weekNumber</text>";
        }
        return $output;
    }

    private function drawMonthLine(int $month, int $day, int $year, float $xValue, $d): string
    {
        $output = '';
        $monthWords = date("M", mktime(0, 0, 0, $month, $day, $year));
        if ($d !== $this->number_of_days - 1) {
            $output .= "<path stroke=\"black\" stroke-width=\"0.4\" d=\"M$xValue 10 v $this->height_of_graph\"/>";
        }
        $monthLegendY = $this->height_of_graph + 20;
        $output .= "<text x=\"$xValue\" y=\"$monthLegendY\" font-family=\"sans-serif\" font-size=\"12px\" fill=\"black\">$monthWords</text>";

        return $output;
    }

    private function drawYearValue(int $month, int $day, int $year, float $xValue): string
    {
        $yearLegendY = $this->height_of_graph + 35;
        $yearWords = date("Y", mktime(0, 0, 0, $month, $day, $year));
        return "<text x=\"$xValue\" y=\"$yearLegendY\" font-family=\"sans-serif\" font-size=\"12px\" fill=\"black\">$yearWords</text>";
    }

    private function add_key(): string
    {
        if (false === $this->show_legend_box) {
            return '';
        }

        if ($this->filled_lines !== true) {
            $vertical = 45 + (30 * ($this->graph_lines_count));
        } else {
            $vertical = 45 + (30 * ($this->graph_lines_count/2));
        }

        $upperName = strtoupper($this->graph_name);
        $graph = "<path fill-opacity=\"0.9\" d=\"M20 12 v{$vertical} h{$this->legend_box_width} v-{$vertical} h-{$this->legend_box_width}\" fill=\"white\"></path>";
        $graph .= "<text x=\"50\" y=\"40\" font-family=\"sans-serif\" font-size=\"16px\" fill=\"black\" text-decoration=\"underline\">{$upperName}</text>";

        for ($i=1; $i<=$this->graph_lines_count; $i++) {
            if ($this->filled_lines !== true) {
                $graph .= $this->draw_keys($i, $i, $graph);
            } elseif ($i % 2 === 0) {
                $graph .= $this->draw_keys($i, $i/2, $graph);
            }
        }
        return $graph;
    }

    private function draw_keys (int $i, int $positioning_i, string $graph): string
    {
        $column = 'col' . $i;
        $lastValue = '';
        $lineYstart = 25 + (30 * $positioning_i);
        $lineYend = 45 + (30 * $positioning_i);
        $textY = 40 + (30 * $positioning_i);
        if (false !== $this->show_last_value_in_legend && !empty($this->lines[0][$column])) {
            $lastValue = "{$this->legend_pre_value}{$this->lines[0][$column]}{$this->legend_post_value}";
        }
        $graph .= "<path d=\"M30 {$lineYstart} L40 {$lineYend}\" stroke-linejoin=\"round\" stroke=\"{$this->colors[$column]}\" fill=\"none\"/>";
        $graph .= "<text x=\"50\" y=\"{$textY}\" font-family=\"sans-serif\" font-size=\"16px\" fill=\"black\">{$this->legends[$column]} {$lastValue}</text>";
        return $graph;
    }


}

