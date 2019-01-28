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
     * Set course format option for subtiles on for course.
     *
     * @Given /^format_tiles subtiles are on for course "(?P<coursefullname_string>(?:[^"]|\\")*)"$/
     * @param string $coursefullname
     * @throws dml_exception
     */
    public function format_tiles_sub_tiles_are_on_for_course($coursefullname) {
        $this->sub_tiles_on_off($coursefullname, 1);
    }

    /**
     * * Set course format option for subtiles off for course.
     *
     * @Given /^format_tiles subtiles are off for course "(?P<coursefullname_string>(?:[^"]|\\")*)"$/
     * @param string $coursefullname
     * @throws dml_exception
     */
    public function format_tiles_sub_tiles_are_off_for_course($coursefullname) {
        $this->sub_tiles_on_off($coursefullname, 0);
    }

    /**
     * Set course format option for subtiles on or off for course.
     * @param string $coursefullname
     * @param int $onoff
     * @throws dml_exception
     */
    private function sub_tiles_on_off($coursefullname, $onoff) {
        global $DB;
        $onoff = $onoff ? 1 : 0;
        $courseid = $DB->get_field('course', 'id', array('fullname' => $coursefullname), MUST_EXIST);
        $courseformat = course_get_format($courseid);
        $courseformat->update_course_format_options(array('id' => $courseid, 'courseusesubtiles' => $onoff));
    }

    // @codingStandardsIgnoreStart.
    /**
     * Set the course format option for the progress indicator for a course as percent or fraction.
     *
     * @Given /^format_tiles progress indicator is showing as "(?P<progresstype_string>(?:[^"]|\\")*)" for course "(?P<coursefullname_string>(?:[^"]|\\")*)"$/
     * @param string $progresstype
     * @param string $coursefullname
     * @throws \Behat\Mink\Exception\ExpectationException
     * @throws dml_exception
     */
    public function progress_indicator_showing_as($progresstype, $coursefullname) {
        // @codingStandardsIgnoreEnd.
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

    // @codingStandardsIgnoreStart.
    /**
     * For a given page, check that its progress indicator shows a certain value (i.e. complete or not).
     *
     * @Then /^format_tiles progress for "(?P<activitytitle_string>(?:[^"]|\\")*)" in "(?P<coursefullname_string>(?:[^"]|\\")*)" is "(?P<value>\d+)" in the database$/
     * @param string $activitytitle
     * @param string $coursefullname
     * @param int $value
     * @throws \Behat\Mink\Exception\ExpectationException
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function progress_indicator_for_page_in_is_set_to($activitytitle, $coursefullname, $value) {
        // @codingStandardsIgnoreEnd.
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
                "Completion state should be " . $value
                . " but found '" . $completionstate
                . "' for " . $activitytitle . ' cmid ' . $pagecms[$activitytitle],
                $this->getSession()
            );
        }
    }

    /**
     * Check that a named activity is dimmed.
     *
     * @Then /^activity in format tiles is dimmed "(?P<activityname_string>(?:[^"]|\\")*)"$/
     * @param string $activityname
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
     * Check that a named activity is not dimmed.
     *
     * @Then /^activity in format tiles is not dimmed "(?P<activityname_string>(?:[^"]|\\")*)"$/
     * @param string $activityname
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
     * @param string $tileumber
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
     * @param string $tilenumber
     * @throws Exception
     */
    public function i_click_tile_close_button($tilenumber) {
        $tileid = behat_context_helper::escape("closesectionbtn-" . $tilenumber);

        // Click the button.
        $this->execute("behat_general::i_click_on", array("//span[@id=" . $tileid . "]", "xpath_element"));
        $this->execute('behat_general::wait_until_the_page_is_ready');
    }
    // @codingStandardsIgnoreStart.
    /**
     * I wait until a certain activity is visible following AJAX load.
     *
     * @Given /^I wait until activity "(?P<activitytitle_string>(?:[^"]|\\")*)" exists in "(?P<format_string>(?:[^"]|\\")*)" format$/
     * @param string $activitytitle
     * @param string $format
     * @throws Exception
     */
    public function wait_until_activity_exists_in_format($activitytitle, $format) {
        // @codingStandardsIgnoreEnd.
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
     * I click a certain activity.
     *
     * @Given /^I click format tiles activity "(?P<activitytitle_string>(?:[^"]|\\")*)"$/
     * @param string $activitytitle
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
     * I click a tile's progress indicator.
     *
     * @Given /^I click format tiles progress indicator for "(?P<activitytitle_string>(?:[^"]|\\")*)"$/
     * @param string $activitytitle
     * @throws Exception
     */
    public function i_click_progress_indicator_for($activitytitle) {
        $activitytitle = behat_context_helper::escape($activitytitle);

        // Click the button.
        $xpath = "//li[contains(@class, 'activity') and @data-title="
            . $activitytitle . "]/descendant::button[@title=\"Click to toggle completion status\"][1]";
        $this->execute("behat_general::i_click_on", array($xpath, "xpath_element"));
        $this->execute('behat_general::wait_until_the_page_is_ready');
        $this->wait_for_pending_js();  // Important to wait for pending JS here so as await AJAX response.
    }

    /**
     * Progress Indicator for tile shows correct out of values e.g. 1 / 2 complete.
     *
     * @Given /^format_tiles progress indicator for tile "(?P<tilenumber>\d+)" is "(?P<numcomplete>\d+)" out of "(?P<outof>\d+)"$/
     * @param string $tilenumber
     * @param string $numcomplete
     * @param string $outof
     * @throws \Behat\Mink\Exception\ElementNotFoundException
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function progress_indicator_tile_shows_outof($tilenumber, $numcomplete, $outof) {
        $xpath = "//div[@id='tileprogress-" . $tilenumber. "']";
        $node = $this->get_selected_node("xpath_element", $xpath);
        if ($node->getAttribute('data-numcomplete') !== $numcomplete) {
            throw new \Behat\Mink\Exception\ExpectationException(
                'Tile ' . $tilenumber . ': Expected number complete ' . $numcomplete
                . ' but found ' . $node->getAttribute('data-numcomplete'),
                $this->getSession()
            );
        }
        if ($node->getAttribute('data-numoutof') !== $outof) {
            throw new \Behat\Mink\Exception\ExpectationException(
                'Tile ' . $tilenumber . ': Expected number out of ' . $numcomplete
                . ' but found ' . $node->getAttribute('data-numoutof'),
                $this->getSession()
            );
        }
    }

    /**
     * Checks if the course section exists.
     *
     * @throws \Behat\Mink\Exception\ElementNotFoundException Thrown by behat_base::find
     * @throws \Behat\Mink\Exception\ExpectationException
     * @param int $sectionnumber
     * @return string The xpath of the section.
     */
    protected function tile_exists($sectionnumber) {

        // Just to give more info in case it does not exist.
        $xpath = "//li[@id='section-" . $sectionnumber . "']";
        $exception = new \Behat\Mink\Exception\ElementNotFoundException($this->getSession(), "Tile $sectionnumber ");
        $this->find('xpath', $xpath, $exception);

        return $xpath;
    }

    /**
     * Hides the specified visible tile. You need to be in the course page and on editing mode.
     *
     * @Given /^I hide tile "(?P<section_number>\d+)"$/
     * @param int $sectionnumber
     * @throws \Behat\Mink\Exception\ElementNotFoundException
     * @throws \Behat\Mink\Exception\ExpectationException
     * @throws coding_exception
     */
    public function i_hide_tile($sectionnumber) {
        // Ensures the section exists.
        $xpath = $this->tile_exists($sectionnumber);
        $this->i_show_hide($sectionnumber, 'hidefromothers', $xpath);
    }

    /**
     * Hides the specified visible tile. You need to be in the course page and on editing mode.
     *
     * @Given /^I show tile "(?P<section_number>\d+)"$/
     * @param int $sectionnumber
     * @throws \Behat\Mink\Exception\ElementNotFoundException
     * @throws \Behat\Mink\Exception\ExpectationException
     * @throws coding_exception
     */
    public function i_show_tile($sectionnumber) {
        // Ensures the section exists.
        $xpath = $this->tile_exists($sectionnumber);
        $this->i_show_hide($sectionnumber, 'showfromothers', $xpath);
    }

    /**
     * Show or hide a certain tile.
     *
     * @param int $sectionnumber
     * @param string $showhide
     * @param string $xpath
     * @throws \Behat\Mink\Exception\ExpectationException
     * @throws coding_exception
     */
    private function i_show_hide($sectionnumber, $showhide, $xpath) {

        // If javascript is on, link is inside a menu.
        if ($this->running_javascript()) {
            $fullxpath = $xpath
                . "/descendant::div[contains(@class, 'section-actions')]/descendant::a[contains(@class, 'dropdown-toggle')]";
            $exception = new \Behat\Mink\Exception\ExpectationException(
                'Tile "' . $sectionnumber . '" was not found', $this->getSession()
            );
            $menu = $this->find('xpath', $fullxpath, $exception);
            $menu->click();
        }

        // Click on hide link.
        $fullxpath = $xpath . '/descendant::a[@title="' . get_string($showhide, 'format_tiles') .'"]';
        echo $fullxpath;
        $exception = new \Behat\Mink\Exception\ExpectationException(
            'Hide link for tile "' . $sectionnumber . '" was not found', $this->getSession()
        );
        $link = $this->find('xpath', $fullxpath, $exception);
        $link->click();

        if ($this->running_javascript()) {
            $this->getSession()->wait(self::TIMEOUT * 1000, self::PAGE_READY_JS);
        }
    }
}