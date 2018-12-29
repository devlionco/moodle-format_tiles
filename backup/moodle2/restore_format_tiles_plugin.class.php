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
 * @copyright 2017 David Watson, Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

define('FILTER_NONE', 0);
define('FILTER_NUMBERS_ONLY', 1);
define('FILTER_OUTCOMES_ONLY', 2);
define('FILTER_OUTCOMES_AND_NUMBERS', 3);

/**
 * Specialised restore for format_tiles
 *
 * Processes 'numsections' from the old backup files and hides sections that used to be "orphaned"
 *
 * @package   format_tiles
 * @category  backup
 * @copyright 2017 David Watson, Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_format_tiles_plugin extends restore_format_plugin {

    /** @var int */
    protected $originalnumsections = 0;

    /**
     * Checks if backup file was made on Moodle before 3.3 and we should respect the 'numsections'
     * and potential "orphaned" sections in the end of the course.
     *
     * @return bool
     */
    protected function need_restore_numsections() {
        $backupinfo = $this->step->get_task()->get_info();
        $backuprelease = $backupinfo->backup_release;
        return version_compare($backuprelease, '3.3', 'lt');
    }

    /**
     * Creates a dummy path element in order to be able to execute code after restore
     *
     * @return restore_path_element[]
     * @throws dml_exception
     */
    public function define_course_plugin_structure() {
        global $DB;

        // Since this method is executed before the restore we can do some pre-checks here.
        // In case of merging backup into existing course find the current number of sections.
        $target = $this->step->get_task()->get_target();
        if (($target == backup::TARGET_CURRENT_ADDING || $target == backup::TARGET_EXISTING_ADDING) &&
                $this->need_restore_numsections()) {
            $maxsection = $DB->get_field_sql(
                'SELECT max(section) FROM {course_sections} WHERE course = ?',
                [$this->step->get_task()->get_courseid()]);
            $this->originalnumsections = (int)$maxsection;
        }

        // Dummy path element is needed in order for after_restore_course() to be called.
        return [new restore_path_element('dummy_course', $this->get_pathfor('/dummycourse'))];
    }

    /**
     * Dummy process method
     */
    public function process_dummy_course() {

    }

    /**
     * Executed after course restore is complete
     *
     * This method is only executed if course configuration was overridden
     * @throws dml_exception
     * @throws coding_exception
     */
    public function after_restore_course() {
        global $DB;
        // This function will be executed on every restore, whether or not the restored course uses this format.
        // So before doing anything else, check if the restored course is using format_tiles or not.
        $backupinfo = $this->step->get_task()->get_info();
        if ($backupinfo->original_course_format !== 'tiles') {
            // Backup is from another course format, so we bail out (the other format will take care of everything).
            // Moving this here fixes issue #4.
            return;
        }
        $currentfilterbarsetting = $DB->get_record(
            'course_format_options',
            array('name' => 'displayfilterbar', 'format' => 'tiles', 'courseid' => $this->step->get_task()->get_courseid())
        );
        if ($currentfilterbarsetting && $currentfilterbarsetting->value == FILTER_OUTCOMES_ONLY
            || $currentfilterbarsetting->value == FILTER_OUTCOMES_AND_NUMBERS) {
            // If the new course has the filter bar set to use outcomes then switch it.
            // Tile outcomes will not work correctly in the new course as they include ids from the old course.
            // This is a temporary solution until the tile outcomes code can be refactored not to use outcome ids.
            $newrecord = new stdClass;
            $newrecord->id = $currentfilterbarsetting->id;
            if ($currentfilterbarsetting->value == FILTER_OUTCOMES_ONLY) {
                $newrecord->value = FILTER_NONE;
                $DB->update_record('course_format_options', $newrecord);
            } else if ($currentfilterbarsetting->value == FILTER_OUTCOMES_AND_NUMBERS) {
                $newrecord->value = FILTER_NUMBERS_ONLY;
                $DB->update_record('course_format_options', $newrecord);
            }

            // Delete references to tile outcomes under section format options (now incorrect in restored course).
            // Users will have to set out up outcomes in new course for now if they want to.
            $DB->delete_records(
                'course_format_options',
                array('name' => 'tileoutcomeid', 'format' => 'tiles', 'courseid' => $this->step->get_task()->get_courseid())
            );
            core\notification::add(get_string('filteroutcomesrestore', 'format_tiles'), core\notification::SUCCESS);
        }

        // The name of course format option "defaulttileicon" for a course used to be "defaulttiletopleftdisplay".
        // Before this was changed for clarity in summer 2018 release, so change it if present in the backup if present.
        // Same for the topic level option "tiletopleftthistile" which becomes "tileicon".
        $courseid = $this->step->get_task()->get_courseid();
        $DB->set_field('course_format_options', 'name', 'defaulttileicon',
            array('format' => 'tiles', 'name' => 'defaulttiletopleftdisplay', 'courseid' => $courseid));
        $DB->set_field('course_format_options', 'name', 'tileicon',
            array('format' => 'tiles', 'name' => 'tiletopleftthistile', 'courseid' => $courseid));

        // Old versions of this plugin used to refer to "course default" for each icon if the user had not selected one.
        // This no longer applies so delete them if present.
        $DB->delete_records_select(
            'course_format_options',
            "format  = 'tiles' AND name = 'tileicon' AND value = 'course default' AND courseid = :courseid",
            array("courseid" => $courseid)
        );

        $data = $this->connectionpoint->get_data();
        if (!isset($data['tags']['numsections']) || !$this->need_restore_numsections()) {
            // Backup file does not even have 'numsections' or was made in Moodle 3.3+, we don't need to process 'numsections'.
            return;
        }

        $numsections = (int)$data['tags']['numsections'];
        // Check each section from the backup file.
        // If it was "orphaned" in the original course, mark it as hidden.
        // This will leave all activities in it visible and available just as it was in the original course.
        // Exception is when we restore with merging and the course already had a section with this section number.
        // In this case we don't modify the visibility.
        foreach ($backupinfo->sections as $key => $section) {
            if ($this->step->get_task()->get_setting_value($key . '_included')) {
                $sectionnum = (int)$section->title;
                if ($sectionnum > $numsections && $sectionnum > $this->originalnumsections) {
                    $DB->execute("UPDATE {course_sections} SET visible = 0 WHERE course = ? AND section = ?",
                        [$this->step->get_task()->get_courseid(), $sectionnum]);
                }
            }
        }
    }
}
