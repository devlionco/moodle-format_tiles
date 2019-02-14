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
 * This file contains local methods for the course format Tiles (not included in lib.php as that's widely called)
 * @since     Moodle 2.7
 * @package   format_tiles
 * @copyright 2018 David Watson
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Experimental feature allowing a teacher to click and convert any label into a page
 * @param int $cmid the course module id of the label (which is recycled)
 * @param stdClass $course
 * @throws dml_exception
 * @throws required_capability_exception
 * @throws coding_exception
 */
function format_tiles_convert_label_to_page($cmid, $course) {
    global $DB;
    $cm = $DB->get_record('course_modules', array('id' => $cmid), '*', MUST_EXIST);
    $labelmoduleid = $DB->get_record('modules', array('name' => 'label'), 'id', MUST_EXIST)->id;
    if ($cm->module != $labelmoduleid) {
        debugging("Cannot convert a non label - course module id " . $cmid, DEBUG_DEVELOPER);
        return;
    }
    $label = $DB->get_record('label', array('id' => $cm->instance), '*', MUST_EXIST);
    if ($label->course !== $course->id || $cm->course !== $course->id) {
        debugging("Cannot convert label - incorrect course id " . $course->id, DEBUG_DEVELOPER);
        return;
    }
    $coursecontext = context_course::instance($course->id);
    require_capability('mod/page:addinstance', $coursecontext);
    require_capability('moodle/course:manageactivities', $coursecontext);

    // Prepare the new page database object using the label as a basis.
    $newpage = $label;

    // New page display name - label names may be multi line but we only want first line.
    $newpage->name = format_tiles_get_first_line($label->name);

    // Now the content - if the first line contains a repetition of the 'name', remove the repetition.
    $newpage->content = $label->intro;
    $firstline = format_tiles_get_first_line($newpage->content);
    if (strpos($firstline, $newpage->name) !== false && strpos($firstline, 'PLUGINFILE') === false) {
        // The first line seems to include what we are using for the name.
        // Also it does not seem to contain a file link so is not adding anything - remove it.
        $remainder = substr($newpage->content, strpos($newpage->content, $firstline) + strlen($firstline));
        $newfirstline = str_replace($newpage->name, '', $firstline);
        $newpage->content = $newfirstline . $remainder;
    }
    $newpage->intro = '';
    $newpage->contentformat = FORMAT_HTML;
    $newpage->display = 0;
    $newpage->displayoptions = serialize(array(
        'printheading' => get_config('page', 'printheading'),
        'printintro' => get_config('page', 'printintro')
    ));
    $newpage->timemodified = time();
    $newpage->revision = 0;
    // Vars $newpage->legacyfiles and $newpage->legacyfileslast are left null.

    // Now add new record to the page table.
    $newpageid = $DB->insert_record('page', $newpage, true);

    // Make necessary changes to the course modules table.
    $cm->module = $DB->get_record('modules', array('name' => 'page'), 'id', MUST_EXIST)->id;
    $cm->instance = $newpageid;
    $cm->timemodified = time();
    $DB->update_record('course_modules', $cm);

    // If label contained embedded images etc, update files table to show they now relate to page not label.
    $contextid = $DB->get_record('context', array('contextlevel' => CONTEXT_MODULE, 'instanceid' => $cmid), 'id', MUST_EXIST)->id;
    $files = $DB->get_records('files', array('component' => 'mod_label', 'contextid' => $contextid));
    if (count($files) > 0) {
        $fs = get_file_storage();
        foreach ($files as $file) {
            // We modify only the fields we need to in order to convert label to page i.e. component and filearea.
            // We ensure that we leave the others including contextid unchanged as they are needed to generate the file URL.
            // They are contextid, filepath ('/') and itemid ('0').
            // We do change the pathnamehash as it is a hash of the other values so needs recalculating.
            // The embedded URL is like [wwwroot]/pluginfile.php/[contextid]/[component]/[filearea]/[itemid][filepath][filename].
            // e.g. [wwwroot]/pluginfile.php/7577/mod_page/content/0/an_image.png.

            $pathnamehash = $fs->get_pathname_hash(
                $file->contextid,
                'mod_page', // New component.
                'content', // New filearea.
                $file->itemid,
                $file->filepath,
                $file->filename
            );
            $params = array(
                'pathnamehash' => $pathnamehash,
                'timemodified' => time(),
                'id' => $file->id
            );
            $DB->execute(
                "UPDATE {files}
                        SET pathnamehash = :pathnamehash, component = 'mod_page', filearea='content', timemodified = :timemodified
                        WHERE id = :id",
                $params
            );
        }
    }
    // Finally remove the old label.
    $DB->delete_records('label', array('id' => $label->id));
    rebuild_course_cache($course->id, true);
    \core\notification::info(get_string('labelconverted', 'format_tiles'));
    $cm->modname = "page";
    $cm->name = $newpage->name;
    $event = \format_tiles\event\label_converted::create_from_cm($cm);
    $event->trigger();
}

/**
 * Get the first line of some text, i.e. all the text
 * before char with ASCII code 10 or 13
 * @param string $text the text to search
 * @return string the resulting text
 */
function format_tiles_get_first_line($text) {
    $text = explode(chr(13), $text)[0];  // Newline char \n.
    if (strpos($text, chr(10))) { // Return char \r in case it is used instead.
        $text = explode(chr(10), $text)[0];
    }
    return $text;
}

/**
 * Which course modules is the site administrator allowing to be displayed in a modal?
 * @return array the permitted modules including resource types e.g. page, pdf, HTML
 */
function format_tiles_allowed_modal_modules() {
    $devicetype = \core_useragent::get_device_type();
    if ($devicetype != \core_useragent::DEVICETYPE_TABLET && $devicetype != \core_useragent::DEVICETYPE_MOBILE) {
        return array(
            'resources' => explode(",", get_config('format_tiles', 'modalresources')),
            'modules' => explode(",", get_config('format_tiles', 'modalmodules'))
        );
    } else {
        return array('resources' => [], 'modules' => []);
    }
}