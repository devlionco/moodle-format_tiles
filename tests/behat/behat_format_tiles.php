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
 * Steps definitions related to Format tiles
 *
 * @package    format_tiles
 * @category   test
 * @copyright  2018 David Watson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');

/**
 * Format tiles related steps definitions.
 *
 * @package    format_tiles
 * @category   test
 * @copyright  2018 David Watson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_format_tiles extends behat_base {

    /**
     * @Given /^format_tiles subtiles are on for course "(?P<coursefullname_string>(?:[^"]|\\")*)"$/
     * @param $coursefullname
     */
    public function format_tiles_sub_tiles_are_on_for_course($coursefullname) {
        $this->sub_tiles_on_off($coursefullname, 1);
    }

    /**
     * @Given /^format_tiles subtiles are off for course "(?P<coursefullname_string>(?:[^"]|\\")*)"$/
     * @param $coursefullname
     */
    public function format_tiles_sub_tiles_are_off_for_course($coursefullname) {
        $this->sub_tiles_on_off($coursefullname, 0);
    }

    private function sub_tiles_on_off($coursefullname, $onoff) {
        global $DB;
        $onoff = $onoff ? 1 : 0;
        $courseid = $DB->get_field('course', 'id', array('fullname' => $coursefullname), MUST_EXIST);
        $courseformat = course_get_format($courseid);
        $courseformat->update_course_format_options(array('id' => $courseid, 'courseusesubtiles' => $onoff));
    }


    /**
     * @Given /^format_tiles progress indicator is showing as "(?P<progresstype_string>(?:[^"]|\\")*)" for course "(?P<coursefullname_string>(?:[^"]|\\")*)"$/
     * @param $progresstype
     * @throws \Behat\Mink\Exception\ExpectationException
     * @throws dml_exception
     */
    public function progress_indicator_showing_as($progresstype, $coursefullname) {
        global $DB;
        if (strtolower($progresstype) == 'percent') {
            $numerictype = 2;
        } else if (strtolower($progresstype) == 'numeric') {
            $numerictype = 1;
        } else {
            throw new \Behat\Mink\Exception\ExpectationException("Indicator type must be percent or numeric", $this->getSession());
        }
        $courseid = $DB->get_field('course', 'id', array('fullname' => $coursefullname), MUST_EXIST);
        $courseformat = course_get_format($courseid);
        $courseformat->update_course_format_options(array('id' => $courseid, 'courseshowtileprogress' => $numerictype));
    }

    /**

     * @Then /^format_tiles progress indicator for "(?P<activitytitle_string>(?:[^"]|\\")*)" in "(?P<coursefullname_string>(?:[^"]|\\")*)" is "(?P<value>\d+)" in the database$/
     * @param $activitytitle
     * @param $coursefullname
     * @param $value
     * @throws \Behat\Mink\Exception\ExpectationException
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function progress_indicator_for_page_in_is_set_to($activitytitle, $coursefullname, $value) {
        global $DB;
        $user = $this->get_session_user();
        $courseid = $DB->get_field('course', 'id', array('fullname' => $coursefullname), MUST_EXIST);
        $modinfo = get_fast_modinfo($courseid);
        $cminfos = $modinfo->get_instances_of('page');
        $pagecms = [];
        foreach ($cminfos as $cminfo) {
            $pagecms[$cminfo->name] = $cminfo->id;
        }
        $this->wait_for_pending_js(); // Wait for AJAX request to complete.
        $this->getSession()->wait(1000);
        $completionstate = $DB->get_field(
            'course_modules_completion',
            'completionstate',
            array(
                'coursemoduleid' => $pagecms[$activitytitle],
                'userid' => $user->id
            )
        );
        if ($completionstate == $value || !$completionstate && !$value) {
            return;
        } else if ($completionstate == false) {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Completion state should be " . $value . " but no record found for " . $activitytitle,
                $this->getSession()
            );
        } else {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Completion state should be " . $value . " but found '" . $completionstate . "' for " . $activitytitle . ' cmid ' . $pagecms[$activitytitle],
                $this->getSession()
            );
        }
    }

    /**
     * @Then /^activity in format tiles is dimmed "(?P<activityname_string>(?:[^"]|\\")*)"$/
     * @param $activityname
     * @return bool
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function activity_in_format_tiles_is_dimmed($activityname) {
        $activityname = behat_context_helper::escape($activityname);
        // Var $xpath is to find the li (the ancestor) which contains an element where the text is activity name.
        $xpath = "//text()[contains(.," . $activityname . ")]/ancestor::*[self::li][1]";
        $activitynode = $this->find('xpath', $xpath, false);
        return $activitynode->hasClass('dimmed');
    }

    /**
     * @Then /^activity in format tiles is not dimmed "(?P<activityname_string>(?:[^"]|\\")*)"$/
     * @param $activityname
     * @return bool
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function activity_in_format_tiles_is_not_dimmed($activityname) {
        return !$this->activity_in_format_tiles_is_dimmed($activityname);
    }

    /**
     * I click a tile (to open it)
     *
     * @Given /^I click on tile "(?P<tilenumber>\d+)"$/
     * @param $tileumber
     * @throws Exception
     */
    public function i_click_on_tile($tileumber) {
        $tileid = behat_context_helper::escape("tile-" . $tileumber);

        // Click the tile.
        $this->execute("behat_general::i_click_on", array("//li[@id=" . $tileid . "]", "xpath_element"));
        $this->getSession()->wait(1000); // Important to wait here as page is scrolling and might click wrong thing after.
    }

    /**
     * I click a tile (to open it)
     *
     * @Given /^I click on close button for tile "(?P<tilenumber>\d+)"$/
     * @param $tilenumber
     * @throws Exception
     */
    public function i_click_tile_close_button($tilenumber) {
        $tileid = behat_context_helper::escape("closesectionbtn-" . $tilenumber);

        // Click the button.
        $this->execute("behat_general::i_click_on", array("//span[@id=" . $tileid . "]", "xpath_element"));
        $this->execute('behat_general::wait_until_the_page_is_ready');
    }
    /**
     * I wait until a certain activity is visible following AJAX load
     * @Given /^I wait until activity "(?P<activitytitle_string>(?:[^"]|\\")*)" exists in "(?P<format_string>(?:[^"]|\\")*)" format$/
     * @param $activitytitle
     * @param $format
     * @throws Exception
     */
    public function wait_until_activity_exists_in_format($activitytitle, $format) {
        if ($format == 'subtile' || $format == 'subtiles') {
            $liclass = 'subtile';
        } else if ($format == 'non-subtile') {
            $liclass = 'activity';
        } else {
            throw new \Behat\Mink\Exception\ExpectationException(
                'Invalid activity format - must be subtile or non-subtile',
                $this->getSession()
            );
        }
        // We wait until the AJAX request finishes and the activity is visible.
        // xpath is to find the li (the ancestor) which contains an element where the text is activity name.
        $xpath = "//text()[contains(.,'" . $activitytitle . "')]/ancestor::li[contains(@class, '" . $liclass . "')]";
        $this->wait_for_pending_js();
        $this->execute("behat_general::wait_until_exists",
            array($this->escape($xpath), "xpath_element")
        );
    }

    /**
     * I click a certain activity
     *
     * @Given /^I click format tiles activity "(?P<activitytitle_string>(?:[^"]|\\")*)"$/
     * @param $activitytitle
     * @throws Exception
     */
    public function click_format_tiles_activity($activitytitle) {
        // Var $xpath is to find the li (the ancestor) which contains an element where the text is activity name.
        $xpath = "//text()[contains(.,'" . $activitytitle . "')]/ancestor::*[contains(@class, 'instancename')]";
        $this->execute('behat_general::wait_until_the_page_is_ready');
        if ($this->running_javascript()) {
            $this->wait_for_pending_js();
            $this->getSession()->wait(self::REDUCED_TIMEOUT * 1000);
        }
        $this->execute("behat_general::i_click_on", array($this->escape($xpath), "xpath_element"));
    }

    /**
     * I click a tile's progress indicator
     *
     * @Given /^I click format tiles progress indicator for "(?P<activitytitle_string>(?:[^"]|\\")*)"$/
     * @param $tilenumber
     * @throws Exception
     */
    public function i_click_progress_indicator_for($activitytitle) {
        $activitytitle = behat_context_helper::escape($activitytitle);

        // Click the button.
        $xpath = "//li[contains(@class, 'activity') and @data-title=" . $activitytitle . "]/descendant::button[@title=\"Click to toggle completion status\"][1]";
        $this->execute("behat_general::i_click_on", array($xpath, "xpath_element"));
        $this->execute('behat_general::wait_until_the_page_is_ready');
        $this->wait_for_pending_js();  // Important to wait for pending JS here so as await AJAX response.
    }
}