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
 * Format tiles external API
 *
 * @package    format_tiles
 * @copyright  2018 David Watson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot . '/course/format/tiles/locallib.php');

/**
 * Format tiles external functions
 *
 * @package    format_tiles
 * @category   external
 * @copyright  2018 David Watson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.3
 */
class format_tiles_external extends external_api
{
    /**
     * Teacher is changing the icon for a course section or whole course using AJAX
     * @param Integer $courseid the id of this course
     * @param Integer $sectionid the number of the section in this course - zero if whole course
     * @param String $icon
     * @return bool success
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     */
    public static function set_icon($courseid, $sectionid, $icon) {
        global $DB;

        $data = self::validate_parameters(self::set_icon_parameters(),
            array(
                'courseid' => $courseid,
                'sectionid' => $sectionid,
                'icon' => $icon,
            )
        );
        $context = context_course::instance($data['courseid']);
        self::validate_context($context);
        require_capability('moodle/course:update', $context);

        $availableicons = (new \format_tiles\icon_set)->available_tile_icons();
        if (!isset($availableicons[$data['icon']])) {
            throw new invalid_parameter_exception('Icon is invalid');
        }

        if ($data['sectionid'] === 0) {
            $optionname = 'defaulttileicon'; // All default icon for whole course.
        } else {
            $optionname = 'tileicon'; // Icon for just this tile.
        }

        $existingicon = $DB->get_record(
            'course_format_options',
            ['format' => 'tiles', 'name' => $optionname, 'courseid' => $data['courseid'], 'sectionid' => $data['sectionid']]
        );
        if (!isset($existingicon->value)) {
            // No icon is presently stored for this so we need to insert new record.
            $record = new stdClass();
            $record->format = 'tiles';
            $record->courseid = $data['courseid'];
            $record->sectionid = $data['sectionid'];
            $record->name = $optionname;
            $record->value = $data['icon'];
            $result = $DB->insert_record('course_format_options', $record);
        } else if ($data['sectionid'] != 0) {
            // We are dealing with a tile icon for one particular section, so check if user has picked the course default.
            $defaulticonthiscourse = $DB->get_record(
                'course_format_options',
                ['format' => 'tiles', 'name' => 'defaulttileicon', 'courseid' => $data['courseid'], 'sectionid' => 0]
            )->value;
            if ($params['icon'] == $defaulticonthiscourse) {
                // Using default icon for a tile do don't store anything in database = default.
                $result = $DB->delete_records(
                    'course_format_options',
                    ['format' => 'tiles', 'name' => 'tileicon', 'courseid' => $data['courseid'], 'sectionid' => $data['sectionid']]
                );
            } else {
                // User has not picked default and there is an existing record so update it.
                $existingicon->value = $data['icon'];
                $result = $DB->update_record('course_format_options', $existingicon);
            }
        } else {
            // Updating existing course icon record.
            $existingicon->value = $data['icon'];
            $result = $DB->update_record('course_format_options', $existingicon);
        }
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns description of get_instance_info() parameters.
     *
     * @return external_function_parameters
     */
    public static function set_icon_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id to edit'),
                'sectionid' => new external_value(PARAM_INT, 'section id to edit - zero means whole course not just one section'),
                'icon' => new external_value(PARAM_RAW, 'file name for the icon picked')
                )
        );
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function set_icon_returns() {
        return new external_value(PARAM_BOOL, 'Whether the icon was set');
    }

    /**
     * Get the HTML for a single section page for a course
     * (i.e. the list of activities and resources comprising the contents of a tile)
     * Intended to be called from AJAX so that the result can be added to the multi
     * tiles page by JS
     *
     * The method returns the HTML rather than the underlying course data to save making
     * another round trip to the server to render the HTML from the data, via the mustache
     * template. This would have been another way of doing it, and would be easy to achieve
     * by calling the template from JS.
     *
     * @param int $courseid
     * @param int $sectionid we want to display
     * @param boolean $setjsusedsession whether to set the session jsenabled flag to true
     * @return array of warnings and status result
     * @since Moodle 3.0
     * @throws moodle_exception
     */
    public static function get_single_section_page_html($courseid, $sectionid, $setjsusedsession = false) {
        global $PAGE, $SESSION;
        $params = self::validate_parameters(
            self::get_single_section_page_html_parameters(),
            array(
                'courseid' => $courseid,
                'sectionid' => $sectionid,
                'setjsusedsession' => $setjsusedsession
            )
        );

        // Request and permission validation.
        // Ensure user has access to course context.
        // validate_context() below ends up calling require_login($courseid).
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);

        $course = get_course($params['courseid']);
        $renderer = $PAGE->get_renderer('format_tiles');
        $templateable = new \format_tiles\output\course_output($course, true, $params['sectionid']);
        $data = $templateable->export_for_template($renderer);
        $result = array(
            'html' => $renderer->render_from_template('format_tiles/single_section', $data)
        );
        // This session var is used later, when user revisits main course page, or a single section, for a course using this format.
        // If set to true, the page can safely be rendered from PHP in the javascript friendly format.
        // (A <noscript> box will be displayed only to users who have JS disabled with a link to switch to non JS format).
        if ($params['setjsusedsession']) {
            $SESSION->format_tiles_jssuccessfullyused = 1;
        }
        return $result;
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function get_single_section_page_html_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'Course id'),
                'sectionid' => new external_value(PARAM_INT, 'Section id'),
                'setjsusedsession' => new external_value(
                    PARAM_BOOL,
                    'Whether to set the session flag for JS successfully used',
                    VALUE_DEFAULT,
                    0,
                    true
                )
            )
        );
    }

    /**
     *
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function get_single_section_page_html_returns () {
        return new external_single_structure(
            array(
                'html' => new external_value(PARAM_RAW, 'HTML for the single section (tile contents)')
            )
        );
    }

    /**
     * Get the HTML for a single page for display in a modal window
     * @param int $courseid
     * @param int $cmid we want to display
     * @return array of warnings and status result
     * @since Moodle 3.0
     * @throws moodle_exception
     */
    public static function get_mod_page_html($courseid, $cmid) {
        global $DB, $PAGE;
        $params = self::validate_parameters(
            self::get_mod_page_html_parameters(),
            array('courseid' => $courseid, 'cmid' => $cmid)
        );
        // Request and permission validation.
        $modcontext = context_module::instance($params['cmid']);
        self::validate_context($modcontext);

        $result = array('status' => false, 'warnings' => [], 'html' => '');
        $mod = get_fast_modinfo($params['courseid'])->get_cm($params['cmid']);
        require_capability('mod/' . $mod->modname . ':view', $modcontext);
        if ($mod && $mod->available) {
            if (array_search($mod->modname, explode(",", get_config('format_tiles', 'modalmodules'))) === false) {
                throw new invalid_parameter_exception('Not allowed to call this mod type - disabled by site admin');
            }
            if ($mod->modname == 'page') {
                // Record from the page table.
                $record = $DB->get_record($mod->modname, array('id' => $mod->instance), 'intro, content, revision, contentformat');
                $renderer = $PAGE->get_renderer('format_tiles');
                $content = $renderer->format_cm_content_text($mod, $record);
                $result['status'] = true;
                $result['html'] = $content;
                return $result;
            } else {
                throw new invalid_parameter_exception('Only page modules allowed through this service');
            }
        } else {
            $result['status'] = false;
            $result['html'] = '';
            $result['warnings'][] = 'Course module is not available';
        }
        return $result;
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function get_mod_page_html_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'Course id'),
                'cmid' => new external_value(PARAM_INT, 'Course module id'),
            )
        );
    }

    /**
     *
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function get_mod_page_html_returns () {
        return new external_single_structure(
            array(
                'html' => new external_value(PARAM_RAW, 'HTML for the course module')
            )
        );
    }

    /**
     * Log that fact that the user clicked a tile
     * @param int $courseid
     * @param int $sectionid we are viewing
     * @return array of warnings and status result
     * @since Moodle 3.0
     * @throws moodle_exception
     */
    public static function log_tile_click($courseid, $sectionid) {
        $params = self::validate_parameters(
            self::log_tile_click_parameters(),
            array('courseid' => $courseid, 'sectionid' => $sectionid)
        );
        // Request and permission validation.
        $coursecontext = context_course::instance($params['courseid']);
        self::validate_context($coursecontext);

        course_view(context_course::instance($courseid), $sectionid);
        $result = array('status' => true, 'warnings' => []);
        return $result;
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function log_tile_click_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'Course id'),
                'sectionid' => new external_value(PARAM_INT, 'Section id viewed', VALUE_DEFAULT, 0, true),
            )
        );
    }

    /**
     *
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function log_tile_click_returns () {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'status: true if success')
            )
        );
    }

    /**
     * Simulate the resource/view.php and page/view.php etc logging when caleld from AJAX
     *
     * This is a re-implementation of the core service only required because the core
     * version is not callable from AJAX
     * @see mod_resource_external::log_resource_view() for example
     * @param int $courseid the course id where the module is
     * @param int $cmid the resource module instance id
     * @return array of warnings and status result
     * @since Moodle 3.0
     * @throws moodle_exception
     */
    public static function log_mod_view($courseid, $cmid) {
        global $DB, $USER;
        $params = self::validate_parameters(
            self::log_mod_view_parameters(),
            array(
                'courseid' => $courseid,
                'cmid' => $cmid
            )
        );
        list($course, $cm) = get_course_and_cm_from_cmid($params['cmid'], '', $params['courseid']);

        // Request and permission validation.
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/' . $cm->modname . ':view', $context);

        $allowedmodalmodules  = format_tiles_allowed_modal_modules();
        if (array_search($cm->modname, $allowedmodalmodules['modules']) === false
            && count($allowedmodalmodules['resources']) == 0) {
            throw new invalid_parameter_exception('Not allowed to log views of this mod type - disabled by site admin');
        }
        $modobject = $DB->get_record($cm->modname, array('id' => $cm->instance), '*', MUST_EXIST);

        // Trigger course_module_viewed event.
        switch ($cm->modname) {
            case 'page':
                page_view($modobject, $course, $cm, $context);
                break;
            case 'resource':
                resource_view($modobject, $course, $cm, $context);
                break;
            default:
                throw new invalid_parameter_exception('No logging method provided for type ' . $cm->modname);
            // TODO add more to these if more modules added.
        }

        // If this item is using automatic completion, mark the item as complete.
        $completion = new completion_info($course);
        if ($completion->is_enabled() && $cm->completion == COMPLETION_TRACKING_AUTOMATIC) {
            $completion->update_state($cm, COMPLETION_COMPLETE, $USER->id);
        }

        $result = array();
        $result['status'] = true;
        return $result;
    }

    /**
     * Simulate the resource/view.php web interface page: trigger events, completion, etc...
     *
     * This is a re-implementation of the core service, only required because the core
     * version is not callable from AJAX
     * @see mod_resource_external::log_resource_view_parameters()
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function log_mod_view_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'cmid' => new external_value(PARAM_INT, 'course module id')
            )
        );
    }

    /**
     *
     * Returns description of method result value
     *
     * This is a re-implementation of the core service only required because the core
     * version is not callable from AJAX
     * @see mod_resource_external::log_resource_view_returns()
     * @return external_description
     * @since Moodle 3.0
     */
    public static function log_mod_view_returns () {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'status: true if success')
            )
        );
    }


    /**
     * Get the available icon set
     * @param int $courseid
     * @return array of warnings and status result
     * @since Moodle 3.3
     * @throws moodle_exception
     */
    public static function get_icon_set($courseid) {
        $params = self::validate_parameters(
            self::get_icon_set_parameters(),
            array('courseid' => $courseid)
        );
        // Request and permission validation.
        // Note course id could be zero if creating new course.

        if ($params['courseid'] != 0) {
            $context = context_course::instance($params['courseid']);
        } else {
            $context = context_coursecat::instance(optional_param('category', 0, PARAM_INT));
        }
        self::validate_context($context);
        if (!has_capability('moodle/course:update', $context) && !has_capability('moodle/course:create', $context)) {
            if (!has_capability('moodle/course:update', $context)) {
                throw new required_capability_exception(
                    $context,
                    'moodle/course:update',
                    "nopermissions",
                    ""
                );
            } else {
                throw new required_capability_exception(
                    $context,
                    'moodle/course:create',
                    "nopermissions",
                    ""
                );
            }
        };
        return array(
            'status' => true,
            'warnings' => [],
            'icons' => json_encode((new \format_tiles\icon_set)->available_tile_icons())
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_icon_set_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'Course id'),
            )
        );
    }

    /**
     *
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.3
     */
    public static function get_icon_set_returns () {
        return new external_single_structure(
            array(
                'icons' => new external_value(PARAM_RAW, 'Icon set available for use on tile icons (JSON array)')
            )
        );
    }
}