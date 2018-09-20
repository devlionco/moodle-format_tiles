<?php

namespace format_tiles\output;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot .'/course/format/lib.php');

class edit_course_settings_output implements \renderable, \templatable
{

    private $availableicons;

    /**
     * course_output constructor
     * @param array $availableicons the icons available to be selected for a tile
     */
    public function __construct($availableicons)
    {
        $this->availableicons = $availableicons;
    }

    /**
     * @param \renderer_base $output
     * @return array|\stdClass
     */
    public function export_for_template(\renderer_base $output)
    {
        foreach ($this->availableicons as $filename => $displayname) {
            $data['icon_picker_icons'][] = array('filename' => $filename, 'displayname' => $displayname);
        }
        $data['secid'] = 0; // section id is zero as we are concerned with course icon not section
        return $data;
    }
}