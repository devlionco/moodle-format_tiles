<?php

namespace format_tiles\output;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot .'/course/format/lib.php');

class inline_css_output implements \renderable, \templatable
{

    private $course;
    /**
     * course_output constructor
     * @param \stdClass $course the course DB object
     */
    public function __construct($course)
    {
        $this->course = $course;
    }

    /**
     * @param \renderer_base $output
     * @return array|\stdClass
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function export_for_template(\renderer_base $output)
    {
        $basecolour = $this->get_tile_base_colour($this->course);
        $outputdata = array(
            'base_colour' => $this->rgbacolour($basecolour),
            'tile_light_colour' => $this->rgbacolour($basecolour, 0.05),
            'tile_hover_colour' => get_config('format_tiles', 'hovercolour'),
            'custom_css' => get_config('format_tiles', 'customcss'),
            'button_hover_colour' => $this->rgbacolour($basecolour,0.1)
        );


        if ($this->course->courseusebarforheadings !=0 && $this->course->courseusebarforheadings != 'standard') { // will be 1 or 0 for use or not use now (legacy values could be 'standard' for not use, or a colour for use, but in that case treat as 'use')
            $outputdata['shade_heading_bar'] = true;
        }
        return $outputdata;
    }

    /**
     * Convert hex colour from plugin settings admin page to RGBA
     * so that can add transparency to it when used as background
     * @param string $hex the colour in hex form e.g. #979797
     * @param int $opacity
     * @return string rgba colour
     */
    private function rgbacolour($hex, $opacity = 1){
        list($r, $g, $b) = sscanf($hex, "#%02x%02x%02x");
        return 'rgba(' . $r . ',' . $g . ',' . $b . ', ' . $opacity . ')';
    }

    /**
     * get the colour which should be used as the base course for this course
     * (Can depend on theme, plugin and/or course settings
     * @param $course
     * @return mixed|string the hex colour
     * @throws \dml_exception
     */
    private function get_tile_base_colour($course){
        global $PAGE;
        //get tile colours to echo in CSS
        $basecolour = '';
        if(!(get_config('format_tiles', 'followthemecolour'))){
            if (!$basecolour = $course->basecolour) {
                // if no course tile colour is set, use plugin default colour
                $basecolour = get_config('format_tiles', 'tilecolour1');
            }
        }
        // we are following theme's main colour so find out what it is
        if(!$basecolour){
            // if boost theme is in use, it uses "brandcolor" so try to get that if current theme has it
            $basecolour = get_config('theme_' . $PAGE->theme->name, 'brandcolor');
            if(!$basecolour){
                // if not got a colour yet, look where essential theme stores its brand color and try that
                $basecolour = get_config('theme_' . $PAGE->theme->name, 'themecolor');
            }
        }
        if(!$basecolour){
            //if still no colour set, use a default colour
            $basecolour = '#1670CC';
        }
        return $basecolour;
    }
}