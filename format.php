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
 * Tiles course format.  Display the whole course as "tiles" made of course modules.
 *
 * @package format_tiles
 * @copyright 2018 David Watson
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $PAGE;

// Horrible backwards compatible parameter aliasing..
if ($topic = optional_param('topic', 0, PARAM_INT)) {
    $url = $PAGE->url;
    $url->param('section', $topic);
    debugging('Outdated topic param passed to course/view.php', DEBUG_DEVELOPER);
    redirect($url);
}
// End backwards-compatible aliasing..

$context = context_course::instance($course->id);

if (($marker >= 0) && has_capability('moodle/course:setcurrentsection', $context) && confirm_sesskey()) {
    $course->marker = $marker;
    course_set_marker($course->id, $marker);
}

$courseformat = course_get_format($course);
// make sure all sections are created
$course = $courseformat->get_course();
$isediting = $PAGE->user_is_editing();
$renderer = $PAGE->get_renderer('format_tiles');

// inline CSS may be requried if this course is using different tile colours to default - echo this first if so
$templateable = new \format_tiles\output\inline_css_output($course);
$data = $templateable->export_for_template($renderer);
echo $renderer->render_from_template('format_tiles/inline-css', $data);

if($isediting && $cmid = optional_param('labelconvert', 0, PARAM_INT)) {
    require_sesskey();
    require_once($CFG->dirroot . '/course/format/tiles/locallib.php');
    convert_label_to_page($cmid, $course);
}

$usejsnav = get_config('format_tiles', 'usejavascriptnav') && !get_user_preferences('format_tiles_stopjsnav', 0);
// We display the multi section page if
// (1) the user is not requesting a specific single section or
// (2) the user is requesting a specific single section (URL &section=xx)
// BUT we know that they have JS enabled ($SESSION->format_tiles_jssuccessfullyused is set),
// in which case we don't want to show them the old style PHP single section page,
// but instead we show them the multi section page and use JS to open the section
// they want (using the &section URL param and $JSsectionnum)
if(optional_param('canceljssession', false, PARAM_BOOL)){
    // the user is shown a link to cancel the successful JS flag for this session in <noscript> tags if their JS is off
    unset($SESSION->format_tiles_jssuccessfullyused);
}

if (empty($displaysection) || ($usejsnav && isset($SESSION->format_tiles_jssuccessfullyused) && !$isediting && get_config('format_tiles','usejsnavforsinglesection'))) {
    $renderer->print_multiple_section_page($course, null, null, null, null);
} else {
    $renderer->print_single_section_page($course, null, null, null, null, $displaysection);
}

// Include format.js (required for dragging sections around)
$PAGE->requires->js('/course/format/tiles/format.js');

// include amd module required for AJAX calls to change tile icon, filter buttons etc
if(!empty($displaysection)){
    $JSsectionnum = $displaysection;
} else if (! $JSsectionnum = optional_param('expand', 0, PARAM_INT)){
    $JSsectionnum = 0;
}

$allowedmodmodals = get_allowed_modal_modules();

$jsparams = array(
    $course->id,
    $isediting,
    $usejsnav, // see also lib.php page_set_course()
    get_config('format_tiles', 'jsmaxstoreditems'),
    core_useragent::get_device_type() == core_useragent::DEVICETYPE_MOBILE  ? 1 : 0,
    $JSsectionnum,
    get_config('format_tiles', 'jsstoredcontentexpirysecs'),
    get_config('format_tiles', 'jsstoredcontentdeletemins'),
    $course->displayfilterbar,
    get_config('format_tiles', 'assumedatastoreconsent')
);

$PAGE->requires->js_call_amd(
    'format_tiles/format_tiles', 'init', $jsparams
);
if(sizeof($allowedmodmodals['resources']) > 0 || sizeof($allowedmodmodals['modules']) > 0){
    $PAGE->requires->js_call_amd(
        'format_tiles/course_mod_modal', 'init', array($course->id)
    );
}
if($isediting && get_config('format_tiles', 'allowsubtilesview') && $course->courseusesubtiles){
    $PAGE->requires->js_call_amd(
        'format_tiles/course_mod_edit', 'init', array(optional_param('labelconvert', 0,PARAM_INT))
    );
}
// this is also called from lib.php, if the user is on course/edit.php (edit course settings)
if($isediting){
    $PAGE->requires->js_call_amd('format_tiles/icon_picker', 'init',
        array(
            'courseId' => $course->id,
            'pagetype' => $PAGE->pagetype
        )
    );
}
if($course->enablecompletion){
    $PAGE->requires->js_call_amd('format_tiles/completion', 'init', array());
}