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
     * @Given format_tiles subtiles are on for course :coursefullname
     * @param $coursefullname
     */
    public function format_tiles_sub_tiles_are_on_for_course($coursefullname) {
        $this->sub_tiles_on_off($coursefullname, 1);
    }

    /**
     * @Given format_tiles subtiles are off for course :coursefullname
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
     * @Given activity in format tiles is dimmed :activityname
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
     * @Given activity in format tiles is not dimmed :activityname
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
     * @Given I click on tile :tilenumber
     * @param $tileumber
     * @throws Exception
     */
    public function i_click_on_tile($tileumber) {
        $tileid = behat_context_helper::escape("tile-" . $tileumber);

        // Click the tile.
        $this->execute("behat_general::i_click_on", array("//li[@id=" . $tileid . "]", "xpath_element"));
    }

    /**
     * I click a tile (to open it)
     *
     * @Given I click on close button for tile :tilenumber
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
     * @Given I wait until activity :activitytitle exists in :format format
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
            throw new invalid_parameter_exception('Invalid activity format - must be subtile or non-subtile');
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
     * @Given I click format tiles activity :activitytitle
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
}