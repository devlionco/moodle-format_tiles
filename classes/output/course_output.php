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
 * Tiles course format, main course output class to prepare data for mustache templates
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
 * Tiles course format, main course output class to prepare data for mustache templates
 * @copyright 2018 David Watson
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_output implements \renderable, \templatable
{

    /**
     * Course object for this class
     * @var \stdClass
     */
    private $course;
    /**
     * Whether this class is called from AJAX
     * @var bool
     */
    private $fromajax;
    /**
     * The section id of the section we want to display
     * @var int
     */
    private $sectionid;
    /**
     * The course renderer object
     * @var \renderer_base
     */
    private $courserenderer;
    /**
     * Array of display names to be used at the top of sub tiles depending
     * on resource type of the module.
     * e.g. 'mod/lti' => 'External Tool' 'mod/resource','xls' = "Spreadsheet'
     * @var array
     */
    private $resourcedisplaynames;
    /**
     * Whether this course is using sub tiles
     * @var bool
     */
    private $courseusesubtiles;
    /**
     * Whether this course is using sub tiles for section zero
     * @var bool
     */
    private $usesubtilesseczero;
    /**
     * Names of the modules for which modal windows should be used e.g. 'page'
     * @var array of resources and modules
     */
    private $usemodalsforcoursemodules;

    /**
     * User's device type e.g. DEVICE_TYPE_MOBILE ('mobile')
     * @var string
     */
    private $devicetype;

    /**
     *  We want to treat label and plugins that behave like labels as labels.
     * E.g. we don't render them as subtiles but show their content directly on page.
     * This includes plugins like mod_customlabel and mod_unilabel.
     * The contents of this array are defined in lib.php and populated below.
     * @var []
     */
    private $labellikecoursemods = [];

    /**
     * course_output constructor.
     * @param \stdClass $course the course object.
     * @param bool $fromajax Whether we are rendering for AJAX request.
     * @param int $sectionid the id of the current section
     * @param \renderer_base|null $courserenderer
     */
    public function __construct($course, $fromajax = false, $sectionid = 0, \renderer_base $courserenderer = null) {
        $this->course = $course;
        $this->fromajax = $fromajax;
        $this->sectionid = $sectionid;
        $this->courserenderer = $courserenderer;
        $this->devicetype = \core_useragent::get_device_type();
        $this->usemodalsforcoursemodules = format_tiles_allowed_modal_modules();
    }

    /**
     * Export the course data for the mustache template.
     * @param \renderer_base $output
     * @return array|\stdClass
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function export_for_template(\renderer_base $output) {
        global $PAGE, $SESSION;
        $format = \course_get_format($this->course);
        $this->labellikecoursemods = $format->labellikecoursemods;
        $data['sesskey'] = sesskey();
        $coursecontext = \context_course::instance($this->course->id);
        $data['canviewhidden'] = has_capability('moodle/course:viewhiddensections', $coursecontext);
        $data['canedit'] = has_capability('moodle/course:update', $coursecontext);
        $data['isediting'] = $PAGE->user_is_editing();
        $data['courseid'] = $this->course->id;
        $data['completionenabled'] = $this->course->enablecompletion && !isguestuser();
        $data['usingjsnav'] = get_config('format_tiles', 'usejavascriptnav')
            && !get_user_preferences('format_tiles_stopjsnav');
        $data['userdisabledjsnav'] = get_user_preferences('format_tiles_stopjsnav');
        if (isset($SESSION->format_tiles_jssuccessfullyused)) {
            // If this flag is set, user is being shown JS versions of pages.
            // Allow them to cancel the session var if they have no JS.
            $data['showJScancelLink'] = 1;
        } else {
            $data['showJScancelLink'] = 0;
        };

        // Custom course settings not in course object if called from AJAX, so make sure we get them.
        if (!$this->fromajax) {
            $data['defaulttileicon'] = $this->course->defaulttileicon;
            $data['courseshowtileprogress'] = $this->course->courseshowtileprogress;
            $this->courseusesubtiles = $this->course->courseusesubtiles;
            $this->usesubtilesseczero = $this->course->usesubtilesseczero;
            $data['from_ajax'] = false;
            $data['courseusebarforheadings'] = $this->course->courseusebarforheadings;
        } else {
            $options = $format->get_format_options();
            $data['defaulttileicon'] = $options['defaulttileicon'];
            $data['courseshowtileprogress'] = $options['courseshowtileprogress'];
            $this->courseusesubtiles = $options['courseusesubtiles'];
            $this->usesubtilesseczero = $options['usesubtilesseczero'];
            $data['from_ajax'] = true;
            $data['courseusebarforheadings'] = $options['courseusebarforheadings'];
            $this->courserenderer = $PAGE->get_renderer('core', 'course');
        }
        $data['useSubtiles'] = get_config('format_tiles', 'allowsubtilesview') && $this->courseusesubtiles;

        $data['ismobile'] = $this->devicetype == \core_useragent::DEVICETYPE_MOBILE;
        $modinfo = get_fast_modinfo($this->course);

        if (!$this->fromajax && $data['isediting']) {
            // Copy activity clipboard..
            $data['course_activity_clipboard'] = $output->course_activity_clipboard($this->course, $this->sectionid);
        }

        if ($data['completionenabled']) {
            $completioninfo = new \completion_info($this->course);
        } else {
            $completioninfo = null;
        }
        $seczero = $modinfo->get_section_info(0);
        $data['section_zero']['summary'] = $output->format_summary_text($seczero);
        $data['section_zero']['content'] = $this->section_content($seczero, $modinfo, $completioninfo, $data['canviewhidden']);
        $data['section_zero']['secid'] = $modinfo->get_section_info(0)->id;
        $data['section_zero']['is_section_zero'] = true;
        $data['section_zero']['tileid'] = 0;
        $data['section_zero']['visible'] = true;

        // Only show section zero if we need it.
        $data['section_zero']['show'] = 0;
        if ($this->sectionid == 0 || get_config('format_tiles', 'showseczerocoursewide')) {
            // We only want to show section zero if we are on the landing page, or admin has said we should show it course wide.
            if ($data['isediting'] || $seczero->summary || !empty($data['section_zero']['content']['course_modules'])) {
                // We do have something to show, or are editing, so need to show it.
                $data['section_zero']['show'] = 1;
            }
        }
        if ($this->courseusesubtiles && $this->usesubtilesseczero) {
            $data['usesubtilesseczero'] = 1;
            $data['section_zero']['useSubtiles'] = 1;
        } else {
            $data['section_zero']['useSubtiles'] = 0;
        }

        // We have assembled the "common data" needed for both single and multiple section pages.
        // Now we can go off and get the specific data for the single or multiple page as required.
        if ($this->sectionid) {
            // We are outputting a single section page.
            return $this->append_single_section_page_data($output, $data, $modinfo, $completioninfo);
        } else {
            // We are outputting a single section page.
            return $this->append_multi_section_page_data($output, $data, $modinfo, $completioninfo);
        }
    }

    /**
     * Take the "common data" supplied as the $data argument, and build on it
     * with data which is specific to single section pages, then return
     * the amalgamated data
     * @param \renderer_base $output the renderer for this format
     * @param array $data the common data
     * @param \course_modinfo $modinfo the modinfo for this course
     * @param \completion_info|null $completioninfo
     * @return array the amalgamated data
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    private function append_single_section_page_data($output, $data, $modinfo, $completioninfo) {
        // If we have nothing to output, don't.
        if (!($thissection = $modinfo->get_section_info($this->sectionid))) {
            // This section doesn't exist.
            print_error('unknowncoursesection', 'error', null, $this->course->fullname);
            return $data;
        }
        if (!$thissection->uservisible) {
            // Can't view this section - in that case the template will just render 'Not available' and nothing else.
            $data['hidden_section'] = true;
            return $data;
        }

        // Data for the requested section page.
        $data['title'] = $thissection->name ?
            $thissection->name : get_string('sectionname', 'format_tiles') . ' ' . $this->sectionid;
        $data['summary'] = $output->format_summary_text($thissection);
        $data['tileid'] = $thissection->section;
        $data['secid'] = $thissection->id;
        $data['tileicon'] = $thissection->tileicon;

        // Include completion help icon HTML.
        if ($completioninfo) {
            $data['completion_help'] = true;
        }

        // The list of activities on the page (HTML).
        $sectioncontent = $this->section_content($thissection, $modinfo, $completioninfo, $data['canviewhidden']);
        $data['course_modules'] = $sectioncontent['course_modules'];

        // If lots of content in this section, we include nav arrows again at bottom of page.
        // But otherwise not as looks odd when no content.
        $longsectionlength = 10000;
        if (strlen('single_sec_content') > $longsectionlength) {
            $data['single_sec_content_is_long'] = true;
        }
        $previousnext = $this->get_previous_next_section_ids($thissection->section, $modinfo->get_section_info_all());
        $data['previous_tile_id'] = $previousnext['previous'];
        $data['next_tile_id'] = $previousnext['next'];

        // If user is editing, add the edit controls.
        if ($data['isediting']) {
            if (optional_param('section', 0, PARAM_INT)) {
                $data['inplace_editable_title'] = $output->section_title_without_link($thissection, $this->course);
            } else {
                $data['inplace_editable_title'] = $output->section_title($thissection, $this->course);
            }
            $data['single_sec_add_cm_control_html'] = $this->courserenderer->course_section_add_cm_control(
                $this->course, $thissection->section, $thissection->section
            );
        }
        $data['visible'] = $thissection->visible;
        // If user can view hidden items, include the explanation as to why an item is hidden.
        if ($data['canviewhidden']) {
            $data['availabilitymessage'] = $output->section_availability_message($thissection, $data['canviewhidden']);
        }
        return $data;
    }

    /**
     * Take the "common data" supplied as the $data argument, and build on it
     * with data which is specific to multiple section pages, then return
     * the amalgamated data
     * @param \renderer_base $output the renderer for this format
     * @param array $data the common data
     * @param \course_modinfo $modinfo the modinfo for this course
     * @param \completion_info|null $completioninfo
     * @return array the amalgamated data
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    private function append_multi_section_page_data($output, $data, $modinfo, $completioninfo) {
        $data['is_multi_section'] = true;

        // If using completion tracking, get the data.
        if ($data['completionenabled']) {
            $data['overall_progress']['num_complete'] = 0;
            $data['overall_progress']['num_out_of'] = 0;
        }
        $data['hasNoSections'] = true;

        foreach ($modinfo->get_section_info_all() as $sectionid => $section) {
            // Show the section if the user is permitted to access it, OR if it's not available
            // but there is some available info text which explains the reason & should display,
            // OR it is hidden but the course has a setting to display hidden sections as unavilable.
            $showsection = $section->uservisible ||
                ($section->visible && !$section->available && !empty($section->availableinfo));
            if ($sectionid != 0 && $showsection) {
                $title = htmlspecialchars_decode($this->truncate_title(get_section_name($this->course, $sectionid)));

                $longtitlelength = 65;
                $newtile = array(
                    'tileid' => $section->section,
                    'secid' => $section->id,
                    'title' => $title,
                    'tileicon' => $section->tileicon,
                    'current' => course_get_format($this->course)->is_section_current($section),
                    'hidden' => !$section->visible,
                    'visible' => $section->visible,
                    'restricted' => !($section->available),
                    'userclickable' => $section->available || $section->uservisible,
                    'activity_summary' => $output->section_activity_summary($section, $this->course, null),
                    'titleclass' => strlen($title) >= $longtitlelength ? ' longtitle' : '',
                    'progress' => false,
                    'isactive' => $this->course->marker == $section->section
                );
                // If user is editing, add the edit controls.
                if ($data['isediting']) {
                    $newtile['inplace_editable_title'] = $output->section_title($section, $this->course);
                    $newtile['section_edit_control'] = $output->section_edit_control_menu(
                        $output->section_edit_control_items($this->course, $section, false),
                        $this->course,
                        $section
                    );
                }
                // Include completion tracking data for each tile (if used).
                if ($section->visible && $data['completionenabled']) {
                    if (isset($modinfo->sections[$sectionid])) {
                        $completionthistile = $this->section_progress(
                            $modinfo->sections[$sectionid],
                            $modinfo->cms, $completioninfo
                        );
                        // Keep track of overall progress so we can show this too - add this tile's completion to the totals.
                        $data['overall_progress']['num_out_of'] += $completionthistile['outof'];
                        $data['overall_progress']['num_complete'] += $completionthistile['completed'];

                        // We only add the tile values to the individual tile if courseshowtileprogress is true.
                        // (Otherwise we only retain overall completion as above, not for each tile).
                        if ($data['courseshowtileprogress']) {
                            $showaspercent = $data['courseshowtileprogress'] == 2 ? true : false;
                            $newtile['progress'] = $this->completion_indicator(
                                $completionthistile['completed'],
                                $completionthistile['outof'],
                                $showaspercent,
                                false
                            );
                        }
                    }
                }

                // If item is restricted, user needs to know why.
                $newtile['availabilitymessage'] = $output->section_availability_message($section, $data['canviewhidden']);

                if ($this->course->displayfilterbar == FORMAT_TILES_FILTERBAR_OUTCOMES
                    || $this->course->displayfilterbar == FORMAT_TILES_FILTERBAR_BOTH) {
                    $newtile['tileoutcomeid'] = $section->tileoutcomeid;
                }

                if ($data['isediting']
                    && (optional_param('expand', 0, PARAM_INT) == $section->section) // One section expanded.
                    || optional_param('expanded', 0, PARAM_INT) // All sections expanded.
                ) {
                    // The list of activities on the page (HTML).
                    $sectioncontent = $this->section_content($section, $modinfo, $completioninfo, $data['canviewhidden']);
                    $newtile['course_modules'] = $sectioncontent['course_modules'];
                    $newtile['is_expanded'] = true;

                    // Must not include the below if section is not expanded - will mean that add activity menus don't work.
                    $newtile['single_sec_add_cm_control_html'] = $this->courserenderer->course_section_add_cm_control(
                        $this->course, $section->section, $section->section
                    );
                } else {
                    $newtile['is_expanded'] = false;
                }

                // Finally add tile we constructed to the array.
                $data['tiles'][] = $newtile;
            } else if ($sectionid == 0) {
                // Add in section zero completion data to overall completion count.
                if ($section->visible && $data['completionenabled']) {
                    if (isset($modinfo->sections[$sectionid])) {
                        $completionthistile = $this->section_progress(
                            $modinfo->sections[$sectionid],
                            $modinfo->cms, $completioninfo
                        );
                        // Keep track of overall progress so we can show this too - add this tile's completion to the totals.
                        $data['overall_progress']['num_out_of'] += $completionthistile['outof'];
                        $data['overall_progress']['num_complete'] += $completionthistile['completed'];
                    }
                }
            }
        }
        $data['all_tiles_expanded'] = optional_param('expanded', 0, PARAM_INT);
        // Now the filter buttons (if used).
        $data['has_filter_buttons'] = false;
        if ($this->course->displayfilterbar) {
            $firstidoutcomebuttons = 1;
            if ($this->course->displayfilterbar == FORMAT_TILES_FILTERBAR_NUMBERS
                || $this->course->displayfilterbar == FORMAT_TILES_FILTERBAR_BOTH) {
                $data['fiternumberedbuttons'] = $this->get_filter_numbered_buttons_data($data['tiles']);
                if (count($data['fiternumberedbuttons']) > 0) {
                    $firstidoutcomebuttons = count($data['fiternumberedbuttons']) + 1;
                    $data['has_filter_buttons'] = true;
                }
            }
            if ($this->course->displayfilterbar == FORMAT_TILES_FILTERBAR_OUTCOMES
                || $this->course->displayfilterbar == FORMAT_TILES_FILTERBAR_BOTH) {
                $outcomes = course_get_format($this->course)->format_tiles_get_course_outcomes($this->course->id);
                $data['fiteroutcomebuttons'] = $this->get_filter_outcome_buttons_data(
                    $data['tiles'], $outcomes, $firstidoutcomebuttons
                );
                if (count($data['fiternumberedbuttons']) > 0) {
                    $data['has_filter_buttons'] = true;
                }
            }
        }
        $data['section_zero_add_cm_control_html'] = $this->courserenderer->course_section_add_cm_control($this->course, 0, 0);
        if ($data['completionenabled'] && $data['overall_progress']['num_out_of'] > 0) {
            $data['overall_progress_indicator'] = $this->completion_indicator(
                $data['overall_progress']['num_complete'],
                $data['overall_progress']['num_out_of'],
                true,
                true
            );

            // If completion tracking is on but nothing to track at activity level, display help to teacher.
            if ($data['isediting'] && $data['overall_progress']['num_out_of'] == 0) {
                $bulklink = \html_writer::link(
                  new \moodle_url('/course/bulkcompletion.php', array('id' => $this->course->id)),
                  get_string('completionwarning_changeinbulk', 'format_tiles')
                );
                $helplink = \html_writer::link(
                    get_docs_url('Activity_completion_settings#Changing_activity_completion_settings_in_bulk'),
                    $output->pix_icon('help', '', 'core')
                );
                \core\notification::WARNING(
                    get_string('completionwarning', 'format_tiles') . ' '  . $bulklink . ' ' . $helplink
                );
            }
        }
        return $data;
    }

    /**
     * Count the number of course modules with completion tracking activated
     * in this section, and the number which the student has completed
     * Exclude labels if we are using sub tiles, as these are not checkable
     * Also exclude items the user cannot see e.g. restricted
     * @param array $sectioncmids the ids of course modules to count
     * @param array $coursecms the course module objects for this course
     * @param object $completioninfo the completion info for this course
     * @return array with the completion data x items complete out of y
     */
    private function section_progress($sectioncmids, $coursecms, $completioninfo) {
        $completed = 0;
        $outof = 0;
        foreach ($sectioncmids as $cmid) {
            $thismod = $coursecms[$cmid];
            if ($thismod->uservisible && !$this->treat_as_label($thismod)) {
                if ($completioninfo->is_enabled($thismod) != COMPLETION_TRACKING_NONE) {
                    $outof++;
                    $completiondata = $completioninfo->get_data($thismod, true);
                    if ($completiondata->completionstate == COMPLETION_COMPLETE ||
                        $completiondata->completionstate == COMPLETION_COMPLETE_PASS
                    ) {
                        $completed++;
                    }
                }
            }
        }
        return array('completed' => $completed, 'outof' => $outof);
    }

    /**
     * Get the details of the filter buttons to be displayed at the top of this course
     * where the teacher has selected to use numbered filter buttons e.g. button 1 might
     * filter to tiles 1-3, button 2 to tiles 4-6 etc
     * @see get_button_map() which calls this function
     * @param array $tiles the tiles which relate to filters
     * @return array the button details
     */
    private function get_filter_numbered_buttons_data($tiles) {
        $numberoftiles = count($tiles);
        if ($numberoftiles == 0) {
            return array();
        }

        // Find out the number to use for each tile from its title e.g. "1 Introduction" filters to "1".
        $tilenumbers = [];
        foreach ($tiles as $tile) {
            if ($statednum = $this->get_stated_tile_num($tile)) {
                $tilenumbers[$statednum] = $tile['tileid'];
            }
        }
        ksort($tilenumbers);

        // Break the tiles down into chunks - one chunk per button.

        if ($numberoftiles <= 15) {
            $tilesperbutton = 3;
        } else if ($numberoftiles <= 30) {
            $tilesperbutton = 4;
        } else {
            $tilesperbutton = 6;
        }

        $buttons = array_chunk($tilenumbers, $tilesperbutton, true);

        // Now populate each button and map the tile details to it.
        $buttonmap = [];
        $buttonid = 1;
        foreach ($buttons as $button => $tilesthisbutton) {
            if (!empty($tiles)) {
                $tilestatednumers = array_keys($tilesthisbutton);
                if ($tilestatednumers[0] == end($tilestatednumers)) {
                    $title = $tilestatednumers[0];
                } else {
                    $title = $tilestatednumers[0] . '-' . end($tilestatednumers);
                }
                $buttonmap[] = array(
                    'id' => 'filterbutton' . $buttonid,
                    'title' => $title,
                    'sections' => json_encode(array_values($tilesthisbutton)),
                    'buttonnum' => $buttonid
                );
            }
            $buttonid++;
        }
        return $buttonmap;
    }

    /**
     * Get the details of the filter buttons to be displayed at the top of this course
     * where the teacher has selected to use OUTCOME filter buttons e.g. button 1 might
     * filter to outcome 1, button 2 to outcome 2 etc
     * @param array $tiles the tiles output object showing the outcome ID for each tile
     * @param array $outcomenames the course outcome names to display
     * @param int $firstbuttonid first button id so it follows on from last one
     * @see get_filter_numbered_buttons()
     * @return array|string the button details
     */
    private function get_filter_outcome_buttons_data($tiles, $outcomenames, $firstbuttonid = 1) {
        $outcomebuttons = [];
        if ($outcomenames) {
            // Build array showing, for each outcome, which sections of the course use it.
            $outcomesections = [];
            foreach ($tiles as $index => $tile) {
                if (isset($tile['tileoutcomeid']) && $tile['tileoutcomeid']) {
                    // This tile has an outcome attached, so add it to the array of tiles for that outcome.
                    $outcomesections[$tile['tileoutcomeid']][] = $tile['tileid'];
                }
            }

            // For each outcome found on tiles, add its outcome name and all tiles found for it to return array.
            $buttonid = $firstbuttonid;
            foreach ($outcomesections as $outcomeid => $outcomesectionsthisoutcome) {
                if (array_key_exists($outcomeid, $outcomenames)) {
                    $outcomebuttons[] = array(
                        'id' => 'filterbutton' . $buttonid,
                        'title' => $outcomenames[$outcomeid],
                        'sections' => json_encode(array_values($outcomesectionsthisoutcome)),
                    );
                }
                $buttonid++;
            }
        }
        return $outcomebuttons;
    }

    /**
     * Get the number which the author has stated for this tile so that it can
     * be used for filter buttons.  e.g. "1 Introduction" or "Week 1 Introduction" give
     * a filtering number of 1
     *
     * @param array $tile the tile output data
     * @return string HTML to output.
     */
    private function get_stated_tile_num($tile) {
        if (!$tile['title']) {
            return $tile['tileid'];
        } else {
            // If title for example starts "16.2" or "16)" treat it as "16".
            $title = str_replace(')', ' ', str_replace('.', ' ', $tile['title']));
            $title = explode(' ', $title);
            for ($i = 0; $i <= count($title) - 1; $i++) {
                // Iterate through each word in the title and see if it's a number - if it is, we have what we want.
                $statednumber = preg_replace('/[^0-9]/', '', $title[$i]);
                if ($statednumber && ctype_digit($statednumber)) {
                    return intval($statednumber);
                }
            }
        }
        return null;
    }

    /**
     * Take a title (e.g. from a section) and truncate it if too big for sub tile
     * @param string $title to truncated
     * @return string truncated
     */
    private function truncate_title($title) {
        $maxtitlelength = 75;
        if (strlen($title) >= $maxtitlelength) {
            $lastspace = strripos(substr($title, 0, $maxtitlelength), ' ');
            $title = substr($title, 0, $lastspace) . ' ...';
        }
        return $title;
    }

    /**
     * Gets the data (context) to be used with the activityinstance template
     * @param object $section the section object we want content for
     * @param \course_modinfo $modinfo all the course module information for this course
     * @param \completion_info $completioninfo the course mod completion info for course
     * @param boolean $canviewhidden whether this user can view hidden items
     * @see \cm_info for full detail of $mod instance variables
     * @see \core_completion\manager::get_activities() which covers similar ground
     * @see \core_course_renderer::course_section_cm_completion() which covers similar ground
     * In the snap theme, course_renderer::course_section_cm_list_item() covers similar ground
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    private function section_content($section, $modinfo, $completioninfo, $canviewhidden = false) {
        global $PAGE, $DB;
        $sectioncontent = array('course_modules' => []);

        if (!isset($modinfo->sections[$section->section]) || !$cmids = $modinfo->sections[$section->section]) {
            return $sectioncontent;
        }

        if (empty($cmids)) {
            return $sectioncontent;
        }
        $previouswaslabel = false;
        foreach ($cmids as $index => $cmid) {
            $moduleobject = [];
            $mod = $modinfo->get_cm($cmid);
            if ($canviewhidden) {
                $moduleobject['uservisible'] = true;
                $moduleobject['clickable'] = true;
            } else if (!$mod->uservisible && $mod->visibleoncoursepage && $mod->availableinfo && $mod->visible) {
                // Activity is not available, not hidden from course page and has availability info.
                // So it is actually visible on the course page (with availability info and without a link).
                $moduleobject['uservisible'] = true;
                $moduleobject['clickable'] = false;
            } else {
                $moduleobject['uservisible'] = $mod->uservisible;
                $moduleobject['clickable'] = $mod->uservisible;
            }
            if (!$moduleobject['uservisible'] || $mod->deletioninprogress || ($mod->is_stealth() && !$canviewhidden)) {
                continue;
            }
            // If the module isn't available, or we are a teacher (can view hidden activities) get availability info.
            if (!$mod->available || $canviewhidden) {
                $moduleobject['availabilitymessage'] = $this->courserenderer->course_section_cm_availability($mod, array());
            }
            $moduleobject['available'] = $mod->available;
            $moduleobject['cmid'] = $cmid;
            $moduleobject['modtitle'] = $mod->get_formatted_name();
            $moduleobject['modname'] = $mod->modname;
            $moduleobject['iconurl'] = $mod->get_icon_url()->out(true);
            $moduleobject['url'] = $mod->url;
            $moduleobject['visible'] = $mod->visible;
            $moduleobject['launchtype'] = 'standard';
            $moduleobject['content'] = $mod->get_formatted_content(array('overflowdiv' => true, 'noclean' => true));

            // We set this here, with the value from the last loop, before updating it in the next block.
            // So that we can use it again on the next loop.
            $moduleobject['previouswaslabel'] = $previouswaslabel;
            if ($this->treat_as_label($mod)) {
                $moduleobject['is_label'] = true;
                $moduleobject['long_label'] = strlen($mod->content) > 300 ? 1 : 0;
                if ($index != 0 && !$previouswaslabel && $this->courseusesubtiles) {
                    $moduleobject['hasSpacersBefore'] = 1;
                }
                $previouswaslabel = true;
            } else {
                $previouswaslabel = false;
            }

            if (isset($mod->instance)) {
                $moduleobject['modinstance'] = $mod->instance;
            }
            $moduleobject['modResourceType'] = $this->get_resource_filetype($mod);
            $moduleobject['modnameDisplay'] = $this->mod_displayname($mod->modname, $moduleobject['modResourceType']);

            // Specific handling for embedded resource items (e.g. PDFs)  as allowed by site admin.
            if ($mod->modname == 'resource') {
                if (array_search($moduleobject['modResourceType'], $this->usemodalsforcoursemodules['resources']) > -1) {
                    $moduleobject['isEmbeddedResource'] = 1;
                    $moduleobject['launchtype'] = 'resource-modal';
                    $moduleobject['pluginfileUrl'] = $this->plugin_file_url($mod);
                } else {
                    // We don't want to embed the file in a modal.
                    // If this is a mobile device or tablet, override the standard URL (already allocated above).
                    // Then user can access file natively in their device (better than embedded).
                    // Otherwise the standard URL will remain i.e. mod/resource/view.php?id=...
                    if ($this->devicetype == \core_useragent::DEVICETYPE_TABLET
                        || $this->devicetype == \core_useragent::DEVICETYPE_MOBILE) {
                        $moduleobject['url'] = $this->plugin_file_url($mod);
                    }
                }
            }
            // Specific handling for embedded course module items (e.g. page) as allowed by site admin.
            if (array_search($mod->modname, $this->usemodalsforcoursemodules['modules']) > -1) {
                $moduleobject['isEmbeddedModule'] = 1;
                $moduleobject['launchtype'] = 'module-modal';
            }
            $moduleobject['showdescription'] =
                isset($mod->showdescription) && !$this->treat_as_label($mod) ? $mod->showdescription : 0;
            if ($moduleobject['showdescription']) {
                // The reason we need 'noclean' arg here is that otherwise youtube etc iframes will be stripped out.
                $moduleobject['description'] = $mod->get_formatted_content(array('overflowdiv' => true, 'noclean' => true));
            }
            $moduleobject['extraclasses'] = $mod->extraclasses;
            $moduleobject['afterlink'] = $mod->afterlink;
            if ($mod->is_stealth()) {
                $moduleobject['extraclasses'] .= ' stealth';
                $moduleobject['stealth'] = 1;
            } else if (
                (!$mod->visible && !$mod->visibleold)
                || !$mod->available
                || !$section->visible
                || (isset($moduleobject['availabilitymessage']) && strlen($moduleobject['availabilitymessage']) > 1 )
            ) {
                $moduleobject['extraclasses'] .= ' dimmed';
            }
            if ($PAGE->user_is_editing()) {
                $moduleobject['cmmove'] = course_get_cm_move($mod, $section->section);
                $editactions = $this->tiles_get_cm_edit_actions($mod, $section->section);
                if (isset($editactions['groupsseparate'])
                    || isset($editactions['groupsvisible']) || isset($editactions['groupsnone'])) {
                    $moduleobject['extraclasses'] .= " margin-rt";
                    // We need to change the right margin in CSS if the edit menu contains a separate groups item.
                }

                $moduleobject['cmeditmenu'] = $this->courserenderer->course_section_cm_edit_actions($editactions, $mod);
                $moduleobject['cmeditmenu'] .= $mod->afterediticons;
                if (!$this->treat_as_label($mod)) {
                    if (!$mod->visible || !$section->visible) {
                        $attr = array('class' => 'dimmed');
                    } else {
                        $attr = null;
                    }
                    $moduleobject['modtitle_inplaceeditable'] = array(
                        "displayvalue" => \html_writer::link($mod->url, $mod->get_formatted_name(), $attr),
                        "value" => $mod->name,
                        "itemid" => $mod->id,
                        "component" => "core_course",
                        "itemtype" => "activityname",
                        "edithint" => get_string('edit'),
                        "editlabel" => get_string('newactivityname') . $mod->name,
                        "type" => "text",
                        "options" => "",
                        "linkeverything" => 0
                    );
                }
            }

            if ($mod->modname == 'folder') {
                // Folders set to display inline will not work this theme.
                // This is not a very elegant solution, but it will ensure that the URL is correctly shown.
                // If the user is editing it will change the format of the folder.
                // It will show on a separate page, and alert the editing user as to what it has done.
                $moduleobject['url'] = new \moodle_url('/mod/folder/view.php', array('id' => $mod->id));
                if ($PAGE->user_is_editing()) {
                    $folder = $DB->get_record('folder', array('id' => $mod->instance));
                    if ($folder->display == FOLDER_DISPLAY_INLINE) {
                        $DB->set_field('folder', 'display', FOLDER_DISPLAY_PAGE, array('id' => $folder->id));
                        \core\notification::info(
                            get_string('folderdisplayerror', 'format_tiles', $moduleobject['url']->out())
                        );
                        rebuild_course_cache($mod->course, true);
                    }
                };
            }
            $moduleobject['onclick'] = $mod->onclick;

            // Now completion information for the individual course module.
            $completion = $mod->completion && $completioninfo && $completioninfo->is_enabled($mod) && $mod->available;
            if ($completion) {
                // Add completion icon to the course module if appropriate.
                $moduleobject['completionInUseForCm'] = true;
                $completiondata = $completioninfo->get_data($mod, true);
                $moduleobject['completionstate'] = $completiondata->completionstate;
                $moduleobject['completionstateInverse'] = $completiondata->completionstate == 1 ? 0 : 1;
                if ($mod->completion == COMPLETION_TRACKING_MANUAL) {
                    $moduleobject['completionIsManual'] = 1;
                    switch ($completiondata->completionstate) {
                        case COMPLETION_INCOMPLETE:
                            $moduleobject['completionstring'] = get_string('togglecompletion', 'format_tiles');
                            break;
                        case COMPLETION_COMPLETE:
                            $moduleobject['completionstring'] = get_string('togglecompletion', 'format_tiles');
                            break;
                    }
                } else { // Automatic.
                    switch ($completiondata->completionstate) {
                        case COMPLETION_INCOMPLETE:
                            $moduleobject['completionstring'] = get_string('complete-n-auto', 'format_tiles');
                            break;
                        case COMPLETION_COMPLETE:
                            $moduleobject['completionstring'] = get_string('complete-y-auto', 'format_tiles');
                            break;
                        case COMPLETION_COMPLETE_PASS:
                            $moduleobject['completionstring'] = get_string('complete-y', 'core_completion', $mod->name);
                            break;
                        case COMPLETION_COMPLETE_FAIL:
                            $moduleobject['completionstring'] = get_string('completion-n', 'core_completion', $mod->name);
                            break;
                    }
                }
            }
            $sectioncontent['course_modules'][] = $moduleobject;
        }
        return $sectioncontent;
    }

    /**
     * Get resource file type e.g. 'doc' from the icon URL e.g. 'document-24.png'
     * Not ideal but we already have icon name so it's efficient
     * Adapted from Snap theme
     * @see mod_displayname() which gets the display name for the type
     *
     * @param \cm_info $mod the mod info object we are checking
     * @return string the type e.g. 'doc'
     */
    private function get_resource_filetype(\cm_info $mod) {
        if ($mod->modname === 'resource') {
            $matches = array();
            preg_match('#/(\w+)-#', $mod->icon, $matches);
            $filetype = $matches[1];
            $extensions = array(
                'powerpoint' => 'ppt',
                'document' => 'doc',
                'spreadsheet' => 'xls',
                'archive' => 'zip',
                'pdf' => 'pdf',
                'mp3' => 'mp3',
                'mpeg' => 'mp4',
                'jpeg' => 'jpeg',
                'text' => 'txt',
                'html' => 'html'
            );
            if (in_array($filetype, array_keys($extensions))) {
                return $extensions[$filetype];
            }
        }
        return '';
    }

    /**
     * Adapted from mod/resource/view.php
     * @param \cm_info $cm the course module object
     * @return string url for file
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private function plugin_file_url($cm) {
        global $DB, $CFG;
        $context = \context_module::instance($cm->id);
        $resource = $DB->get_record('resource', array('id' => $cm->instance), '*', MUST_EXIST);
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $context->id, 'mod_resource', 'content', 0, 'sortorder DESC, id ASC', false
        );
        if (count($files) >= 1 ) {
            $file = reset($files);
            unset($files);
            $resource->mainfile = $file->get_filename();
            return $CFG->wwwroot . '/pluginfile.php/' . $context->id . '/mod_resource/content/'
                . $resource->revision . $file->get_filepath() . rawurlencode($file->get_filename());
        }
        return '';
    }

    /**
     * Get the display name for each module or resource type
     * from the modname, to be displayed at the top of each tile
     * e.g. 'mod/lti' => 'External Tool' 'mod/resource','xls' = "Spreadsheet'
     * Once we have it , store it in instance var e.g. to avoid repeated check of 'pdf'
     * @param string $modname the name of the module e.g. 'resource'
     * @param string|null $resourcetype if this is a resource, the specific type eg. 'xls' or 'pdf'
     * @return string to be displayed on tile
     * @see get_resource_filetype()
     * @throws \coding_exception
     */
    private function mod_displayname($modname, $resourcetype = null) {
        if ($modname == 'resource') {
            if (isset($this->resourcedisplaynames[$resourcetype])) {
                return $this->resourcedisplaynames[$resourcetype];
            } else if (get_string_manager()->string_exists('displaytitle_mod_' . $resourcetype, 'format_tiles')) {
                $str = get_string('displaytitle_mod_' . $resourcetype, 'format_tiles');
                $this->resourcedisplaynames[$resourcetype] = $str;
                return $str;
            } else {
                $str = get_string('other', 'format_tiles');
                $this->resourcedisplaynames[$resourcetype] = $str;
                return $str;
            }
        } else {
            return get_string('modulename', 'mod_' . $modname);
        }
    }

    /**
     * For the navigation arrows, establish the id of the next and previous sections
     * @param int $currentsectionid the id of the section we are in
     * @param array $modinfo all the course sections
     * @return array previous and next ids
     */
    private function get_previous_next_section_ids($currentsectionid, $modinfo) {
        $visiblesectionids = [];
        $currentsectionarrayindex = -1;
        foreach ($modinfo as $section) {
            if ($section->uservisible) {
                $visiblesectionids[] = $section->section;
                if ($section->section == $currentsectionid) {
                    $currentsectionarrayindex = $section->section;
                }
            }
        }
        if ($currentsectionarrayindex == 0) {
            $previous = 0; // There is no previous.
        } else {
            $previous = $visiblesectionids[$currentsectionarrayindex - 1];
        }
        if ($currentsectionarrayindex == count($visiblesectionids) - 1) {
            $next = 0; // There is no next.
        } else {
            $next = $visiblesectionids[$currentsectionarrayindex + 1];
        }
        return array('previous' => $previous, 'next' => $next);
    }

    /**
     * Prepare the data required to render a progress indicator (.e. 2/3 items complete)
     * to be shown on the tile or as an overall course progress indicator
     * @param int $numcomplete how many items are complete
     * @param int $numoutof how many items are available for completion
     * @param boolean $aspercent should we show the indicator as a percentage or numeric
     * @param boolean $isoverall whether this is an overall course completion indicator
     * @return array data for output template
     */
    private function completion_indicator($numcomplete, $numoutof, $aspercent, $isoverall) {
        $percentcomplete = $numoutof == 0 ? 0 : round(($numcomplete / $numoutof) * 100, 0);
        $progressdata = array(
            'numComplete' => $numcomplete,
            'numOutOf' => $numoutof,
            'percent' => $percentcomplete,
            'isComplete' => $numcomplete > 0 && $numcomplete == $numoutof ? 1 : 0,
            'isOverall' => $isoverall
        );
        if ($aspercent && $numcomplete != $numoutof) {
            // Percent in circle.
            $progressdata['showAsPercent'] = true;
            $circumference = 106.8;
            $progressdata['percentCircumf'] = $circumference;
            $progressdata['percentOffset'] = round(((100 - $percentcomplete) / 100) * $circumference, 0);
        }
        $progressdata['isSingleDigit'] = $percentcomplete < 10 ? true : false; // Position single digit in centre of circle.
        return $progressdata;
    }

    /**
     * The menu to edit a course module is generated by
     * @see \core_course_renderer::course_section_cm_edit_actions()
     * but its format/content are not ideal for tiles
     * So before we call here we adapt the menu items to make
     * them more compatible with this format
     * @param \cm_info $mod the course module object
     * @param int $sectionnum the id of the section number we are in
     * @return array the amended actions
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    private function tiles_get_cm_edit_actions($mod, $sectionnum) {
        // First get the standard list of actions from course/lib.
        $actions = course_get_cm_edit_actions($mod, -1, $sectionnum);
        if ($mod->modname === "label") {
            $coursecontext = \context_course::instance($mod->course);
            if (get_config('format_tiles', 'allowlabelconversion' )
                && has_capability('mod/page:addinstance', $coursecontext)
                && has_capability('moodle/course:manageactivities', $coursecontext)) {
                $converttext = get_string('converttopage', 'format_tiles');
                $actions['labelconvert'] = new \action_menu_link_secondary(
                    new \moodle_url(
                        '/course/view.php', array(
                            'id' => $mod->course,
                            'section' => $sectionnum,
                            'labelconvert' => $mod->id,
                            'sesskey' => sesskey()
                        )
                    ),
                    new \pix_icon('random', $converttext, 'format_tiles'),
                    $converttext,
                    array('class' => 'editing_labelconvert ', 'data-action' => 'labelconvert',
                        'data-keepopen' => true, 'data-sectionreturn' => $sectionnum)
                );
            }
        }
        if (!$this->courseusesubtiles
            || !get_config('format_tiles', 'allowsubtilesview')
            || ($sectionnum == 0 && !$this->usesubtilesseczero)) {
            // We are not using sub tiles so return the standard list.
            return $actions;
        }

        // Otherwise proceed to adapt the standard items to this format.
        foreach ($actions as $actionname => $action) {
            $actionstomodify = ['hide', 'show', 'duplicate', 'groupsseparate', 'groupsvisible', 'groupsnone', 'stealth'];
            if (!$this->treat_as_label($mod) && array_search($actionname, $actionstomodify) > -1) {
                // For non labels, we don't want core JS to be used to hide/show etc when these menu items are used.
                // Core converts the cm HTML to the standard activity display format (not subtile).
                // Instead we want to use our own JS to render the new cm adding 'tiles-' to the start of data-action.
                // E.g. tiles-show will prevent core JS running and allow our custom JS to run instead.
                // (The core JS is in core_course/actions::editModule (actions.js).
                // Note 'stealth' action can only be available if site admin has allowed stealth activities.
                $action->attributes['data-action'] = "tiles-" . $action->attributes['data-action'];
                $action->attributes['data-cmid'] = $mod->id;
            }
            if (get_class($action) == 'action_menu_link_primary') {
                // We don't want items to be displayed as "action_menu_link_primary" in this format.
                // E.g. separate groups item would be if we left it as is.
                // So make a secondary menu item instead and replace it for the primary one.
                $action = new \action_menu_link_secondary(
                    $action->url,
                    $action->icon,
                    $action->text,
                    $action->attributes
                );

                // And we don't want clicking them to trigger core JS calls.
                $action->attributes['data-action'] = "tiles-" . $action->attributes['data-action'];

            }
            // We want to truncate if too long for this format.
            $containsbracketat = strpos($action->text, '(');
            if ($containsbracketat !== false) {
                // Not much room in the drop down so truncate after open bracket e.g. "Separate Groups (Click to change)".
                $action->text = substr($action->text, 0, $containsbracketat - 1);
            }
        }
        return $actions;
    }

    /**
     *  We want to treat label and plugins that behave like labels as labels.
     * E.g. we don't render them as subtiles but show their content directly on page.
     * This includes plugins like mod_customlabel and mod_unilabel.
     * @param \cm_info $mod the course module.
     * @return bool whether it's to be treated as a label or not.
     */
    private function treat_as_label($mod) {
        return array_search($mod->modname, $this->labellikecoursemods) !== false;
    }
}