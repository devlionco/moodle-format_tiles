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
 * Page called by administrator from plugin settings page to register plugin.
 *
 * @package format_tiles
 * @copyright  2019 David Watson {@link http://evolutioncode.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 **/

require_once('../../../config.php');

global $PAGE, $DB;

use format_tiles\form\registration_form;
use format_tiles\registration_manager;

require_login();
require_sesskey();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$pageurl = new moodle_url('/course/format/tiles/register.php');
$settingsurl = new moodle_url('/admin/settings.php', array('section' => 'formatsettingtiles'));
$key = optional_param('key', '', PARAM_TEXT);

$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_heading(get_string('tilesformatregistration', 'format_tiles'));
$PAGE->navbar->add(get_string('administrationsite'), new moodle_url('/admin/search.php'));
$PAGE->navbar->add(get_string('plugins', 'admin'), new moodle_url('/admin/category.php', array('category' => 'modules')));
$PAGE->navbar->add(get_string('courseformats'), new moodle_url('/admin/category.php', array('category' => 'formatsettings')));
$PAGE->navbar->add(get_string('pluginname', 'format_tiles'), $settingsurl);
$PAGE->navbar->add(get_string('tilesformatregistration', 'format_tiles'));

// User is passing key URL param so process it.
if ($key && registration_manager::validate_key($key)) {
    registration_manager::set_registered();
    redirect($settingsurl);
}

$formparams = array(
    'contextid' => $context->id,
);

$mform = new registration_form(null, $formparams);

$formdata = new stdClass();
if ($mform->is_cancelled()) {
    // Someone has hit the 'cancel' button.
    redirect($settingsurl);
} else if ($data = $mform->get_data()) { // Form has been submitted.
    $registrationmanager = new registration_manager();
    $result = $registrationmanager->request_key(process_data($data));
    if ($result['status'] && registration_manager::validate_key($result['key'])) {
        $registrationmanager->set_registered($result['key']);
        redirect($settingsurl);
    } else {
        // We have form data but did not succeed registering from the server. So try using JavaScript.
        $jsparams = array(
            'sesskey' => sesskey(),
            'registrationUrl' => registration_manager::registration_server_url(),
            process_data($data, true)
        );
        $PAGE->requires->js_call_amd('format_tiles/registration', 'attemptRegistration', $jsparams);

        // We also schedule an attempt to register by cron but this will be ignored if JS succeeds.
        // We do this because we cannot know here is JS succeeds.
        $registrationmanager->schedule_registration_attempt($data);
    }
}

echo $OUTPUT->header();
echo html_writer::start_div('ml-5');
echo html_writer::div(get_string('registerintro1', 'format_tiles'));
echo html_writer::tag('ul',
    html_writer::tag('li', get_string('registerintro2', 'format_tiles'), [])
    . html_writer::tag('li', get_string('registerintro3', 'format_tiles'), [])
    . html_writer::tag('li', get_string('registerintro4', 'format_tiles'), [])
    , array('class' => 'ml-3')
);
echo html_writer::div(get_string('registerintro5', 'format_tiles'));
echo html_writer::end_div();
echo $OUTPUT->box_start('generalbox');
$mform->display();
echo $OUTPUT->box_end();
echo $OUTPUT->footer();

/**
 * Take the data submitted from the form and supplement it / remove submit button.
 * @package format_tiles
 * @param object $data
 * @param bool $forjs
 * @return array
 */
function process_data($data, $forjs = false) {
    $returndata = (array)$data;
    $returndata['ip'] = getremoteaddr();
    unset($returndata['submitbutton']);
    if (!$forjs) {
        $returndata['browserlanguages'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        $returndata['useragent'] = \core_useragent::get_user_agent_string();
    }
    return $returndata;
}