<?php

/**
 * Socket - takes data arrays and plots them as an SVG line or range graph with optional data points represented as dots.
 * PHP Version >= 8.0
 * Version 0.3.04
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

class Socket extends DataManipulation
{


    /**
     * PUBLIC METHODS
     */

    public function draw_svg(array $lineArray, array $pointArray = []): string
    {
        // Ideally there would be 3 arrays: lines, points and ranges and all of them could be displayed at the same time
        // Or, the naming convention of $lineArray uses line1, line2 for lines, and range1, range2 for a range.
        // In that scenario, you might also get bar1, bar2 or different variations.

        $this->lines = $lineArray;
        $this->points = $pointArray;

        $this->manipulateData();

        return $this->draw_graph();
    }

    /**
     * PRIVATE METHODS
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


    private function add_branding(): string
    {
        if ('' !== $this->branding_text && '' !== $this->branding_url) {
            $logox = $this->end_of_graph_x - $this->brand_x_from_right;
            $logoy = $this->end_of_graph_y - $this->brand_y_from_bottom;
            return "<a xlink:href=\"{$this->branding_url}\" xlink:title=\"{$this->branding_text}\"><text x=\"$logox\" y=\"$logoy\" font-family=\"sans-serif\" font-size=\"16px\" fill=\"black\">{$this->branding_text}</text></a>";
        }
        return '';
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
            if ($n % 2 !== 0 && isset($this->lines[0]['col' . ($n + 1)])) {
                $output .= '<polygon points="';
            }
            $output .= $this->draw_polygon_points($n);
            if ($n % 2 === 0) {
                $color = $this->colors['col' . $n];
                $output .= "\" fill=\"$color\" stroke=\"$color\" opacity=\"0.5\" />";
            }
            $n++;
        }
        return $output;
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
        $data_range = $this->max - $this->min;
        $iterationsInt = $this->iterations;
        $value_per_iteration = $data_range / $iterationsInt;
        for ($i = 0; $i <= $iterationsInt; $i++) {
            $heightAtt = $this->end_of_graph_y - ($i * $this->height_of_graph / $iterationsInt);
            $textVal = $this->min + ($i * $value_per_iteration);
            $graph .= "<text x=\"1\" y=\"$heightAtt\" font-family=\"sans-serif\" font-size=\"12px\" fill=\"black\">$textVal</text>";
            $graph .= "<text x=\"$this->width_of_graph\" y=\"$heightAtt\" font-family=\"sans-serif\" font-size=\"12px\" fill=\"black\">$textVal</text>";
            $graph .= "<path stroke=\"black\" stroke-width=\"0.2\" d=\"M10 $heightAtt h $this->width_of_graph\"/>";
            if (0 === $textVal) {
                $zero_line_drawn = true;
            }
        }
        if (true !== $zero_line_drawn && 0 > $this->min && 0 < $this->max) {
            $heightAtt = $this->end_of_graph_y + $this->min * $this->pixels_per_unit_y;
            $graph .= "<path stroke=\"black\" stroke-width=\"0.4\" d=\"M10 $heightAtt h $this->width_of_graph\"/>";
        }
        return $graph;
    }


    private function draw_graph_line($columnName): string
    {
        $g = 0;
        $closed = true;
        $color = $this->colors[$columnName];
        $line = '';

        while ($g <= $this->number_of_days) {
            if (isset($this->lines[$g][$columnName]) && null !== $this->lines[$g][$columnName]) {
                $xValue = $this->end_of_graph_x - ($g * $this->pixels_per_unit_x);
                $graphVal = (float)$this->lines[$g][$columnName];
                $yValue = $this->end_of_graph_y - (($graphVal - $this->min) * $this->pixels_per_unit_y);

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


    private function draw_polygon_points($n): string
    {
        $line = '';
        $columnName = 'col' . $n;
        if ($n % 2 !== 0) {
            $g = 0;
            while ($g <= $this->number_of_days) {
                $line = $this->drawPolygonPoint($g, $columnName, $line);
                $g++;
            }
        } elseif ($n % 2 === 0) {
            $g = $this->number_of_days;
            while ($g >= 0) {
                $line = $this->drawPolygonPoint($g, $columnName, $line);
                $g--;
            }
        }
        return $line;
    }


    private function add_data_points(): string
    {
        $graph = '';
        if (empty($this->points)) {
            return $graph;
        }

        $d = $this->number_of_days - 1;
        while (0 <= $d) {
            $dateString = $this->lines[$d]['date'];
            $xValue = $this->end_of_graph_x - ($d * $this->pixels_per_unit_x);
            for ($i = 0; $i < $this->number_of_points; $i++) {
                if ($this->points[$i]['date'] === $dateString) {
                    $point_color = 'black';
                    if ($this->points[$i]['volume'] < 0) {
                        $point_color = 'red';
                    }
                    $yValue = $this->end_of_graph_y - (((float)$this->points[$i]['value'] - $this->min) * $this->pixels_per_unit_y);
                    $graph .= "<circle cx=\"$xValue\" cy=\"$yValue\" r=\"3\" fill='$point_color'/>";
                }
            }
            $d--;
        }
        return $graph;
    }


    private function add_weeks_months_years(): string
    {
        $d = $this->number_of_days - 1;
        $graph = '';
        $currentMonth = 0;
        $currentYear = 0;
        if ($this->lines[$d]['date']) {
            if (true === $this->show_week_numbers) {
                $weekLegendX = ($this->width_of_graph / 2) - 20;
                $graph .= "<text x=\"$weekLegendX\" y=\"30\" font-family=\"sans-serif\" font-size=\"12px\" fill=\"black\">Week Numbers</text>";
            }
            while (0 <= $d) {
                [$graph, $currentMonth, $currentYear] = $this->drawVerticalLinesForDay($d, $graph, $currentMonth, $currentYear);
                $d--;
            }
        }
        return $graph;
    }


    private function drawWeekLine(int $month, int $day, int $year, float $xValue): string
    {
        $weekNumber = (int)date("W", mktime(0, 0, 0, $month, $day, $year));
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
            $vertical = 45 + (30 * ($this->graph_lines_count / 2));
        }

        $upperName = strtoupper($this->graph_name);
        $graph = "<path fill-opacity=\"0.9\" d=\"M20 12 v{$vertical} h{$this->legend_box_width} v-{$vertical} h-{$this->legend_box_width}\" fill=\"white\"></path>";
        $graph .= "<text x=\"50\" y=\"40\" font-family=\"sans-serif\" font-size=\"16px\" fill=\"black\" text-decoration=\"underline\">{$upperName}</text>";

        for ($i = 1; $i <= $this->graph_lines_count; $i++) {
            if ($this->filled_lines !== true) {
                $graph .= $this->draw_keys($i, $i, $graph);
            } elseif ($i % 2 === 0) {
                $graph .= $this->draw_keys($i, $i / 2, $graph);
            }
        }
        return $graph;
    }


    private function draw_keys(int $i, int $positioning_i, string $graph): string
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


    private function drawPolygonPoint(int $g, string $columnName, string $line): string
    {
        if (isset($this->lines[$g][$columnName]) && null !== $this->lines[$g][$columnName]) {
            $xValue = $this->end_of_graph_x - ($g * $this->pixels_per_unit_x);
            $graphVal = (float)$this->lines[$g][$columnName];
            $yValue = $this->end_of_graph_y - (($graphVal - $this->min) * $this->pixels_per_unit_y);
            $line .= " $xValue,$yValue";
        }
        return $line;
    }


    private function drawVerticalLinesForDay(int $d, string $graph, int $currentMonth, int $currentYear): array
    {
        $dateString = $this->lines[$d]['date'];
        $xValue = $this->end_of_graph_x - ($d * $this->pixels_per_unit_x);

        if (0 <= $xValue) {
            $year = (int)substr($dateString, 0, 4);
            $month = (int)substr($dateString, 5, 2);
            $day = (int)substr($dateString, 8, 2);
            $dayOfWeek = (int)date("w", mktime(0, 0, 0, $month, $day, $year));
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
        return [$graph, $currentMonth, $currentYear];
    }


}

