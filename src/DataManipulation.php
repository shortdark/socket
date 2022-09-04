<?php

namespace Shortdark;


class DataManipulation
{

    /**
     * CONFIG, CAN BE OVERWRITTEN
     */

    // Width of SVG bounds
    public int $width_of_svg = 1400;

    // Height of SVG bounds
    public int $height_of_svg = 540;

    // The number of iterations on the Y-axis
    public int $iterations = 10;

    public array $colors = [
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

    public float $fill_opacity = 0.9;

    // Show week numbers across the top of the graph (multiples of 5)
    public bool $show_week_numbers = false;

    // Show the year below the months on the x-axis
    public bool $show_year_xaxis = true;

    public string $month_format = 'short'; // none, letter, short, long

    // Optional, show a legends box to describe each graph line
    public bool $show_legend_box = true;

    // Optional, legends box, specify the width of the box
    public int $legend_box_width = 200;

    // Optional, legends box, title in the legends box, default is 'Key' but could also be the title of the graph
    public string $graph_name = 'Key';

    // Optional, legends box, descriptive strings for each of the 4 graph lines to be displayed in the legends box
    public array $legends = [];

    // Optional, legends box, we can display the latest value for each line in the legend
    public bool $show_last_value_in_legend = false;

    // Optional, legends box, if last value is true, add string that would be before the value in the legend, e.g. $
    public string $legend_pre_value = '';

    // Optional, legends box, if last value is true, add string that would be after the value in the legend, e.g. %
    public string $legend_post_value = '';

    // Optional branding, no action required for no branding
    public string $branding_text = ''; // String e.g. 'Shortdark Web Dev'
    public string $branding_url = ''; // String e.g. 'https://shortdark.co.uk'
    public int $brand_x_from_right = 120;
    public int $brand_y_from_bottom = 15;

    // Representing two lines as a range, i.e. the space between the two lines is filled
    public bool $filled_lines = false;

    // Generally the graph starts/ends at a multiple of 10, the nearest value would be the nearest number above or below the max/min value.
    public bool $nearest_value = false;

    /**
     * CLASS VARIABLES
     */

    protected array $lines = [];

    protected array $points = [];

    protected int $end_of_graph_x;

    protected int $end_of_graph_y;

    protected int $width_of_graph;

    protected int $height_of_graph;

    protected float $min;

    protected float $max;

    protected float $pixels_per_unit_x;

    protected float $pixels_per_unit_y;

    protected int $graph_lines_count;

    protected int $number_of_days;

    protected int $number_of_points;

    /**
     * PUBLIC METHODS
     */

    protected function manipulateData()
    {
        $this->assign_dimensions_from_config();

        $this->get_data_limits();

        $this->check_legends();
    }

    /**
     * PRIVATE METHODS
     */

    private function assign_dimensions_from_config()
    {
        $this->end_of_graph_x = $this->width_of_svg - 30;
        $this->end_of_graph_y = $this->height_of_svg - 30;
        $this->width_of_graph = $this->width_of_svg - 40;
        $this->height_of_graph = $this->height_of_svg - 40;
    }

    private function get_data_limits()
    {
        $this->graph_lines_count = count($this->lines[0]) - 1;
        $this->number_of_days = count($this->lines);

        $this->number_of_points = count($this->points);

        $this->modify_separator_to_make_graph_fit_on_screen();

        $this->getHighestLowest();

        $this->pixels_per_unit_y = $this->height_of_graph / ($this->max - $this->min);
    }

    private function modify_separator_to_make_graph_fit_on_screen()
    {
        $this->pixels_per_unit_x = $this->width_of_graph / ($this->number_of_days - 1);
    }

    private function getHighestLowest()
    {
        $i = 0;
        $this->min = $this->max = $this->lines[$i]['col1'];

        $this->getMinMaxFromLines();

        $this->getMinMaxFromPoints();

        if ($this->max < 3 && $this->min > 0) {
            $temp_max = (float) sprintf('%.1f', $this->max);
            $temp_min = (float) sprintf('%.1f', $this->min);

            // Make sure max isn't rounded down
            if ($this->max > $temp_max) {
                $temp_max += 0.1;
            }
            // Make sure min isn't rounded up
            if ($this->min < $temp_min) {
                $temp_min -= 0.1;
            }

            // Make min and max a multiple of 0.2, i.e. if not modulus of 0.2 add/subtract 0.1
            if (($temp_max * 10) % 2 !== 0) {
                $temp_max += 0.1;
            }
            if (($temp_min * 10) % 2 !== 0) {
                $temp_min -= 0.1;
            }
            $this->max = $temp_max;
            $this->min = $temp_min;
        } else {
            $this->roundMax();
            $this->roundMin();
        }


    }

    private function getMinMaxFromLines()
    {
        $i = 0;
        while ($i <= $this->number_of_days) {
            for ($j = 1; $j <= $this->graph_lines_count; $j++) {
                $colName = 'col' . $j;
                if (isset($this->lines[$i][$colName]) && $this->lines[$i][$colName] > $this->max) {
                    $this->max = $this->lines[$i][$colName];
                }
                if (isset($this->lines[$i][$colName]) && $this->lines[$i][$colName] < $this->min) {
                    $this->min = $this->lines[$i][$colName];
                }
            }
            $i++;
        }
    }

    private function getMinMaxFromPoints()
    {
        $j = 0;
        while ($j <= $this->number_of_points) {
            if (isset($this->points[$j]['value']) && $this->points[$j]['value'] > $this->max) {
                $this->max = $this->points[$j]['value'];
            }
            if (isset($this->points[$j]['value']) && $this->points[$j]['value'] < $this->min) {
                $this->min = $this->points[$j]['value'];
            }
            $j++;
        }
    }

    private function roundMax()
    {
        $this->max = (int)ceil($this->max);
        if ($this->nearest_value === false && 8 < $this->max) {
            while ($this->max % 10 !== 0) {
                $this->max++;
            }
        }
    }

    private function roundMin()
    {
        $this->min = (int)floor($this->min);
        if ($this->nearest_value === false && (10 < $this->min || 10 < $this->max)) {
            while ($this->min % 10 !== 0) {
                $this->min--;
            }
        }
    }

    private function check_legends()
    {
        for ($i = 1; $i <= $this->graph_lines_count; $i++) {
            $colName = 'col' . $i;
            if (empty($this->legends[$colName])) {
                $this->legends[$colName] = 'Column ' . $i;
            }
        }
    }

}
