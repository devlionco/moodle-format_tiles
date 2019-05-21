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
 * Specialised restore for format_tiles (based on the equivalent for format_topics
 *
 * @package   format_tiles
 * @category  backup
 * @copyright 2019 David Watson {@link http://evolutioncode.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Specialised backup for format_tiles
 *
 * Ensure that photo background images are included in course backups.
 *
 * @package   format_tiles
 * @category  backup
 * @copyright 2019 David Watson {@link http://evolutioncode.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_format_tiles_plugin extends backup_format_plugin {

    /**
     * Returns the format information to attach to section element.
     */
    protected function define_section_plugin_structure() {
        $fileapiparams = \format_tiles\tile_photo::file_api_params();

        // Define the virtual plugin element with the condition to fulfill.
        $plugin = $this->get_plugin_element(null, $this->get_format_condition(), 'tiles');

        // Define each element separated.
        $tile = new backup_nested_element('tile', array('id'), array('tilephoto'));

        // Define sources.
        $tile->set_source_table('course_sections', array('id' => backup::VAR_SECTIONID));

        // Define file annotations.
        $tile->annotate_files($fileapiparams['component'], $fileapiparams['filearea'], null);

        $plugin->add_child($tile);
        return $plugin;
    }
}