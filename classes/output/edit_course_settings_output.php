<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Tiles course format, edit course settings course output class (Course admin > edit settings)
 *
 * @package format_tiles
 * @copyright 2018 David Watson
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace format_tiles\output;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot .'/course/format/lib.php');

/**
 * Gets icon picker data ready
 * @copyright 2018 David Watson
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_course_settings_output implements \renderable, \templatable {

    /**
     * The icons abvailable for teacher to allocate to a tile
     * Made up of /pix/tileicon and any icons specified in
     * @see format_tiles_get_fontawesome_icon_map()
     * @var array
     */
    private $availableicons;

    /**
     * course_output constructor
     * @param array $availableicons the icons available to be selected for a tile
     */
    public function __construct($availableicons) {
        $this->availableicons = $availableicons;
    }

    /**
     * Export the data for the mustache template.
     * @param \renderer_base $output
     * @return array|\stdClass
     */
    public function export_for_template(\renderer_base $output) {
        foreach ($this->availableicons as $filename => $displayname) {
            $data['icon_picker_icons'][] = array('filename' => $filename, 'displayname' => $displayname);
        }
        $data['secid'] = 0; // Section id is zero as we are concerned with course icon not section.
        return $data;
    }
}