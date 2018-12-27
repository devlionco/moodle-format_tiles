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
 * This file contains main class for the course format Tiles
 *
 * @since     Moodle 2.7
 * @package   format_tiles
 * @copyright 2016 David Watson
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

define('FORMAT_TILES_FILTERBAR_NONE', 0);
define('FORMAT_TILES_FILTERBAR_NUMBERS', 1);
define('FORMAT_TILES_FILTERBAR_OUTCOMES', 2);
define('FORMAT_TILES_FILTERBAR_BOTH', 3);

require_once($CFG->dirroot . '/course/format/lib.php');

/**
 * Enable a renderable object to be generated for the course footer
 * @see format_tiles::course_footer()
 * @see \format_tiles_renderer::render_format_tiles_icon_picker_icons()
 * @copyright 2018 David Watson
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_tiles_icon_picker_icons implements renderable {
}

/**
 * Main class for the course format Tiles
 *
 * @since     Moodle 2.7
 * @package   format_tiles
 * @copyright 2016 David Watson
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_tiles extends format_base {
    /**
     * Creates a new instance of class
     *
     * Please use {@link course_get_format($courseorid)} to get an instance of the format class
     *
     * @param string $format
     * @param int $courseid
     */
    protected function __construct($format, $courseid) {
        if ($courseid === 0) {
            global $COURSE;
            $courseid = $COURSE->id;  // Save lots of global $COURSE as we will never be the site course.
        }
        parent::__construct($format, $courseid);
    }

    /**
     * Returns true if this course format uses sections
     *
     * @return bool
     */
    public function uses_sections() {
        return true;
    }

    /**
     * Returns the display name of the given section that the course prefers.
     *
     * Use section name is specified by user. Otherwise use default ("Topic #")
     *
     * @param int|stdClass $section Section object from database or just field section.section
     * @return string Display name that the course format prefers, e.g. "Topic 2"
     * @throws moodle_exception
     */
    public function get_section_name($section) {
        $section = $this->get_section($section);
        if ((string)$section->name !== '') {
            return format_string($section->name, true,
                array('context' => context_course::instance($this->courseid)));
        } else if ($section->section == 0) {
            return get_string('section0name', 'format_tiles');
        } else {
            return get_string('sectionname', 'format_tiles') . ' ' . $section->section;
        }
    }

    /**
     * Returns the default section name for the topics course format.
     *
     * If the section number is 0, it will use the string with key = section0name from the course format's lang file.
     * If the section number is not 0, the base implementation of format_base::get_default_section_name which uses
     * the string with the key = 'sectionname' from the course format's lang file + the section number will be used.
     *
     * @param stdClass $section Section object from database or just field course_sections section
     * @return string The default value for the section name.
     * @throws coding_exception
     */
    public function get_default_section_name($section) {
        if ($section->section == 0) {
            // Return the general section.
            return get_string('section0name', 'format_tiles');
        } else {
            // Use format_base::get_default_section_name implementation which will display the section name in "Topic n" format.
            return parent::get_default_section_name($section);
        }
    }

    /**
     * Indicates whether the course format supports the creation of a news forum.
     * Required in Moodle 3.2 onwards
     *
     * @return bool
     */
    public function supports_news() {
        return true;
    }

    /**
     * The URL to use for the specified course (with section)
     *
     * @param int|stdClass $section Section object from database or just field course_sections.section
     *     if omitted the course view page is returned
     * @param array $options options for view URL. At the moment core uses:
     *     'navigation' (bool) if true and section has no separate page, the function returns null
     *     'sr' (int) used by multipage formats to specify to which section to return
     * @return null|moodle_url
     * @throws moodle_exception
     */
    public function get_view_url($section, $options = array()) {
        $course = $this->get_course();
        $url = new moodle_url('/course/view.php', array('id' => $course->id));

        $sr = null;
        if (array_key_exists('sr', $options)) {
            $sr = $options['sr'];
        }
        if (is_object($section)) {
            $sectionno = $section->section;
        } else {
            $sectionno = $section;
        }
        if ($sectionno !== null) {
            if ($sr !== null) {
                $sectionno = $sr;
            }
            if ($sectionno != 0) {
                $url->param('section', $sectionno);
            } else {
                if (!empty($options['navigation'])) {
                    return null;
                }
                $url->set_anchor('section-' . $sectionno);
            }
        }
        return $url;
    }

    /**
     * Returns the information about the ajax support in the given source format
     *
     * The returned object's property (boolean)capable indicates that
     * the course format supports Moodle course ajax features.
     *
     * @return stdClass
     */
    public function supports_ajax() {
        $ajaxsupport = new stdClass();
        $ajaxsupport->capable = true;
        return $ajaxsupport;
    }

    /**
     * Override if you need to perform some extra validation of the format options
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @param array $errors errors already discovered in edit form validation
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK.
     *         Do not repeat errors from $errors param here
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function edit_form_validation($data, $files, $errors) {
        $courseid = $data['id'];
        $reterrors = array();
        if (!$data['enablecompletion'] && $data['courseshowtileprogress']) {
            $reterrors['courseshowtileprogress'] = get_string('courseshowtileprogress_error', 'format_tiles');
        }
        if (($data['displayfilterbar'] == FORMAT_TILES_FILTERBAR_OUTCOMES
                || $data['displayfilterbar'] == FORMAT_TILES_FILTERBAR_BOTH)
            && empty($this->format_tiles_get_course_outcomes($courseid))) {
            $outcomeslink = html_writer::link(
                new moodle_url('/grade/edit/outcome/course.php', array('id' => $courseid)),
                new lang_string('outcomes', 'format_tiles')
            );
            $reterrors['displayfilterbar'] = get_string('displayfilterbar_error', 'format_tiles') . ' ' . $outcomeslink;
        }
        return $reterrors;
    }

    /**
     * Loads all of the course sections into the navigation
     *
     * @param global_navigation $navigation
     * @param navigation_node $node The course node within the navigation
     * @return void
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function extend_course_navigation($navigation, navigation_node $node) {
        global $PAGE;
        // If section is specified in course/view.php, make sure it is expanded in navigation.
        if ($navigation->includesectionnum === false) {
            $selectedsection = optional_param('section', null, PARAM_INT);
            if ($selectedsection !== null && (!defined('AJAX_SCRIPT') || AJAX_SCRIPT == '0') &&
                $PAGE->url->compare(new moodle_url('/course/view.php'), URL_MATCH_BASE)) {
                $navigation->includesectionnum = $selectedsection;
            }
        }
        // Check if there are callbacks to extend course navigation.
        parent::extend_course_navigation($navigation, $node);

        // We want to remove the general section if it is empty.
        $course = $this->get_course();
        $modinfo = get_fast_modinfo($course);
        $sections = $modinfo->get_sections();
        if (!isset($sections[0])) {
            // The general section is empty to find the navigation node for it we need to get its ID.
            $section = $modinfo->get_section_info(0);
            $generalsection = $node->get($section->id, navigation_node::TYPE_SECTION);
            if ($generalsection) {
                // We found the node - now remove it.
                $generalsection->remove();
            }
        }
        if (get_config('format_tiles', 'usejavascriptnav') && !$PAGE->user_is_editing()) {
            if (!get_user_preferences('format_tiles_stopjsnav', 0)) {
                $url = new moodle_url('/course/view.php', array('id' => $course->id, 'stopjsnav' => 1));
                $settingnode = $node->add(
                    get_string('jsdeactivate', 'format_tiles'),
                    $url->out(),
                    navigation_node::TYPE_SETTING
                );
                $settingnode->nodetype = navigation_node::NODETYPE_LEAF;
                // Can't add classes or ids here if using boost (works in clean).
                $settingnode->id = 'tiles_stopjsnav';
                $settingnode->add_class('tiles_coursenav hidden');

                // Now the Data Preference menu item.
                if (!get_config('format_tiles', 'assumedatastoreconsent')) {
                    $url = new moodle_url('/course/view.php', array('id' => $course->id, 'datapref' => 1));
                    $settingnode = $node->add(
                        get_string('datapref', 'format_tiles'),
                        $url->out(),
                        navigation_node::TYPE_SETTING
                    );
                    $settingnode->nodetype = navigation_node::NODETYPE_LEAF;

                    // Can't add classes or ids here if using boost (works in clean).
                    $settingnode->id = 'tiles_datapref';
                    $settingnode->add_class('tiles_coursenav hidden');
                }

            } else {
                $settingnode = $node->add(
                    get_string('jsactivate', 'format_tiles'),
                    new moodle_url('/course/view.php', array('id' => $course->id, 'stopjsnav' => 1)),
                    navigation_node::TYPE_SETTING
                );
                $settingnode->nodetype = navigation_node::NODETYPE_LEAF;

                // Can't add classes or ids here if using boost (works in clean).
                $settingnode->id = 'tiles_stopjsnav';
                $settingnode->add_class('tiles_coursenav hidden');
            }
        }
    }

    /**
     * Custom action after section has been moved in AJAX mode
     *
     * Used in course/rest.php
     *
     * @return array This will be passed in ajax response
     * @throws moodle_exception
     */
    public function ajax_section_move() {
        global $PAGE;
        $titles = array();
        $course = $this->get_course();
        $modinfo = get_fast_modinfo($course);
        $renderer = $this->get_renderer($PAGE);
        if ($renderer && ($sections = $modinfo->get_section_info_all())) {
            foreach ($sections as $number => $section) {
                $titles[$number] = $renderer->section_title($section, $course);
            }
        }
        return array('sectiontitles' => $titles, 'action' => 'move');
    }

    /**
     * Returns the list of blocks to be automatically added for the newly created course
     *
     * @return array of default blocks, must contain two keys BLOCK_POS_LEFT and BLOCK_POS_RIGHT
     *     each of values is an array of block names (for left and right side columns)
     */
    public function get_default_blocks() {
        return array(
            BLOCK_POS_LEFT => array(),
            BLOCK_POS_RIGHT => array()
        );
    }

    /**
     * Iterates through all the colours entered by the administrator under the plugin settings page
     * @return array list of all the colours and their names for use in the settings forms
     * @throws dml_exception
     */
    private function format_tiles_get_tiles_palette() {
        $palette = array();
        for ($i = 1; $i <= 10; $i++) {
            $colourname = get_config('format_tiles', 'colourname' . $i);
            $tilecolour = get_config('format_tiles', 'tilecolour' . $i);
            if ($tilecolour != '' and $tilecolour != '#000') {
                $palette[$tilecolour] = $colourname;
            }
        }
        return $palette;
    }

    /**
     * In order to populate the option menus under course setting which allow the user to select
     * a tile icon from all those available, iterates through all font awesome icons and images in
     * the relevant directory and generates a suitable menu option for each icon.
     * @return array of tile icons
     */
    public function format_tiles_available_icons() {
        global $CFG;
        $availableicons = [];
        // First identify which of the font awesome icons used by this plugin are intended for use as tile icons.
        // I.e. they have the path tileicon/...
        $fontawesomeicons = array_keys(format_tiles_get_fontawesome_icon_map());
        foreach ($fontawesomeicons as $faicon) {
            if (strpos($faicon, 'tileicon/') !== false) {
                $iconname = explode('/', $faicon)[1];
                $displayname = ucwords(str_replace('_', ' ', (str_replace('-', ' ', $iconname))));
                $availableicons[$iconname] = $displayname;
            }
        }
        // Now look for any supplemental image file (i.e. non font awesome icons) which are available as tile icons.
        // Add them to the list.
        $iconsindirectory = get_directory_list($CFG->dirroot
            . '/course/format/tiles/pix/tileicon', '', false, false, true);
        foreach ($iconsindirectory as $icon) {
            $filename = explode('.', $icon)[0];
            $displayname = ucwords(str_replace('_', ' ', (str_replace('-', ' ', $filename))));
            $availableicons[$filename] = $displayname;
        }
        ksort($availableicons);
        return $availableicons;
    }

    /**
     * Whether this format allows to delete sections (Moodle 3.1+)
     * If format supports deleting sections it is also recommended to define language string
     * 'deletesection' inside the format.
     * Do not call this function directly, instead use {@link course_can_delete_section()}
     *
     * @param int|stdClass|section_info $section
     * @return bool
     */
    public function can_delete_section($section) {
        return true;
    }

    /**
     * Definitions of the additional options that this course format uses for course
     *
     * @param bool $foreditform
     * @return array of options
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function course_format_options($foreditform = false) {
        static $courseformatoptions = false;
        if ($courseformatoptions === false) {
            $courseformatoptions = array(
                'hiddensections' => array(
                    'default' => 1,
                    'type' => PARAM_INT,
                ),
                'coursedisplay' => array(
                    'default' => 1,
                    'type' => PARAM_INT,
                ),
                'defaulttileicon' => array(
                    'default' => 'pie-chart',
                    'type' => PARAM_TEXT,
                ),
                'basecolour' => array(
                    'default' => get_config('format_tiles', 'tilecolour1'),
                    'type' => PARAM_TEXT,
                ),
                'courseusesubtiles' => array(
                    'default' => 0,
                    'type' => PARAM_INT,
                ),
                'courseshowtileprogress' => array(
                    'default' => 0,
                    'type' => PARAM_INT,
                ),
                'displayfilterbar' => array(
                    'default' => 0,
                    'type' => PARAM_INT,
                ),
                'usesubtilesseczero' => array(
                    'default' => 0,
                    'type' => PARAM_INT
                ),
                'courseusebarforheadings' => array(
                    'default' => 1,
                    'type' => PARAM_INT,
                )
            );
            if ((get_config('format_tiles', 'followthemecolour'))) {
                unset($courseformatoptions['basecolour']);
            }
            if (!get_config('format_tiles', 'allowsubtilesview')) {
                unset($courseformatoptions['courseusesubtiles']);
            }
        }

        if ($foreditform && !isset($courseformatoptions['coursedisplay']['label'])) {
            $tilespalette = $this->format_tiles_get_tiles_palette();
            $tileicons = $this->format_tiles_available_icons();
            $courseconfig = get_config('moodlecourse');
            $max = $courseconfig->maxsections;
            if (!isset($max) || !is_numeric($max)) {
                $max = 52;
            }
            $sectionmenu = array();
            for ($i = 0; $i <= $max; $i++) {
                $sectionmenu[$i] = "$i";
            }
            $courseformatoptionsedit = array(
                'hiddensections' => array(
                    'label' => new lang_string('hiddensections'),
                    'element_type' => 'hidden',
                    'element_attributes' => array(
                        array(1 => new lang_string('hiddensectionsinvisible'))
                    ),
                ),
                'coursedisplay' => array(
                    'label' => new lang_string('coursedisplay'),
                    'element_type' => 'hidden',
                    'element_attributes' => array(
                        array(
                            COURSE_DISPLAY_MULTIPAGE => new lang_string('coursedisplay_multi')
                        )
                    ),
                ),
            );
            $label = get_string('defaulttileicon', 'format_tiles');
            $courseformatoptionsedit['defaulttileicon'] = array(
                'label' => $label,
                'element_type' => 'select',
                'element_attributes' => array($tileicons),
                'help' => 'defaulttileicon',
                'help_component' => 'format_tiles',
            );
            if (!(get_config('format_tiles', 'followthemecolour'))) {
                $courseformatoptionsedit['basecolour'] = array(
                    'label' => new lang_string('basecolour', 'format_tiles'),
                    'element_type' => 'select',
                    'element_attributes' => array($tilespalette),
                    'help' => 'basecolour',
                    'help_component' => 'format_tiles',
                );
            }
            $attributes = array(
                FORMAT_TILES_FILTERBAR_NONE => new lang_string('hide', 'format_tiles'),
                FORMAT_TILES_FILTERBAR_NUMBERS => new lang_string('filternumbers', 'format_tiles'),
            );
            $outcomeslink = '(' . new lang_string('outcomesunavailable', 'format_tiles') . ')';
            global $CFG;
            if (!empty($CFG->enableoutcomes)) {
                $outcomeslink = html_writer::link(
                    new moodle_url('/grade/edit/outcome/course.php',
                        array('id' => $this->get_courseid())),
                    '(' . new lang_string('outcomes', 'format_tiles') . ')'
                );
                $attributes[FORMAT_TILES_FILTERBAR_OUTCOMES] = new lang_string('filteroutcomes', 'format_tiles');
                $attributes[FORMAT_TILES_FILTERBAR_BOTH] = new lang_string('filterboth', 'format_tiles');
            }
            $courseformatoptionsedit['displayfilterbar'] = array(
                'label' => new lang_string('displayfilterbar', 'format_tiles', $outcomeslink),
                'element_type' => 'select',
                'element_attributes' => array($attributes),
                'help' => 'displayfilterbar',
                'help_component' => 'format_tiles',
            );
            $courseformatoptionsedit['courseshowtileprogress'] = array(
                'label' => new lang_string('courseshowtileprogress', 'format_tiles'),
                'element_type' => 'select',
                'element_attributes' => array(
                    array(
                        0 => new lang_string('hide', 'format_tiles'),
                        1 => new lang_string('asfraction', 'format_tiles'),
                        2 => new lang_string('aspercentagedial', 'format_tiles'),
                    ),
                ),
                'help' => 'courseshowtileprogress',
                'help_component' => 'format_tiles'
            );

            if (get_config('format_tiles', 'allowsubtilesview')) {
                $courseformatoptionsedit['courseusesubtiles'] = array(
                    'label' => new lang_string('courseusesubtiles', 'format_tiles'),
                    'element_type' => 'advcheckbox',
                    'element_attributes' => array(get_string('yes')),
                    'help' => 'courseusesubtiles',
                    'help_component' => 'format_tiles',
                );
            }
            $courseformatoptionsedit['courseusebarforheadings'] = array(
                'label' => new lang_string(
                    'courseusebarforheadings', 'format_tiles'
                ),
                'element_type' => 'advcheckbox',
                'element_attributes' => array(get_string('yes')),
                'help' => 'courseusebarforheadings',
                'help_component' => 'format_tiles',
            );

            $courseformatoptionsedit['usesubtilesseczero'] = array(
                'label' => new lang_string('usesubtilesseczero', 'format_tiles'),
                'element_type' => 'advcheckbox',
                'element_attributes' => array(get_string('notrecommended', 'format_tiles')),
                'help' => 'usesubtilesseczero',
                'help_component' => 'format_tiles',
            );

            $courseformatoptions = array_merge_recursive($courseformatoptions, $courseformatoptionsedit);
        }
        return $courseformatoptions;
    }

    /**
     * Definitions of the additional options that this course format uses for section
     *
     * See {@link format_base::course_format_options()} for return array definition.
     *
     * Additionally section format options may have property 'cache' set to true
     * if this option needs to be cached in {@link get_fast_modinfo()}. The 'cache' property
     * is recommended to be set only for fields used in {@link format_base::get_section_name()},
     * {@link format_base::extend_course_navigation()} and {@link format_base::get_view_url()}
     *
     * For better performance cached options are recommended to have 'cachedefault' property
     * Unlike 'default', 'cachedefault' should be static and not access get_config().
     *
     * Regardless of value of 'cache' all options are accessed in the code as
     * $sectioninfo->OPTIONNAME
     * where $sectioninfo is instance of section_info, returned by
     * get_fast_modinfo($course)->get_section_info($sectionnum)
     * or get_fast_modinfo($course)->get_section_info_all()
     *
     * All format options for particular section are returned by calling:
     * $this->get_format_options($section);
     *
     * @param bool $foreditform
     * @return array
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function section_format_options($foreditform = false) {
        $course = $this->get_course();
        $sectionformatoptions = array(
            'tileicon' => array(
                'default' => '',
                'type' => PARAM_TEXT,
            ),
        );
        if ($course->displayfilterbar == FORMAT_TILES_FILTERBAR_OUTCOMES
            || $course->displayfilterbar == FORMAT_TILES_FILTERBAR_BOTH) {
            $sectionformatoptions['tileoutcomeid'] = array(
                'default' => 0,
                'type' => PARAM_INT,
            );
        }
        if ($foreditform) {
            $defaultcoursetile = $course->defaulttileicon;
            $defaulticonarray = array(
                '' => get_string('defaultthiscourse', 'format_tiles') . ' (' . $defaultcoursetile . ')'
            );
            $tileicons = $this->format_tiles_available_icons();
            $tileicons = array_merge($defaulticonarray, $tileicons);
            $sectionformatoptionsedit = array();

            $label = get_string('tileicon', 'format_tiles');
            $sectionformatoptionsedit['tileicon'] = array(
                'label' => $label,
                'element_type' => 'select',
                'element_attributes' => array($tileicons),
                'help' => 'tileicon',
            );

            if ($course->displayfilterbar == FORMAT_TILES_FILTERBAR_OUTCOMES
                || $course->displayfilterbar == FORMAT_TILES_FILTERBAR_BOTH) {
                $outcomeslink = html_writer::link(
                    new moodle_url('/grade/edit/outcome/course.php', array('id' => $course->id)),
                    '(' . new lang_string('outcomes', 'format_tiles') . ')'
                );
                $label = get_string('tileoutcome', 'format_tiles') . ' ' . $outcomeslink;
                $outcomes = $this->format_tiles_get_course_outcomes($course->id);
                if (!empty($outcomes)) {
                    $outcomes[0] = get_string('none', 'format_tiles');
                }
                $sectionformatoptionsedit['tileoutcomeid'] = array(
                    'label' => $label,
                    'element_type' => 'select',
                    'element_attributes' => array($outcomes),
                    'help' => 'tileoutcome',
                );
            }
            $sectionformatoptions = array_merge_recursive($sectionformatoptions, $sectionformatoptionsedit);
        }
        return $sectionformatoptions;
    }

    /**
     * Adds format options elements to the course/section edit form.
     *
     * This function is called from {@link course_edit_form::definition_after_data()}.
     *
     * @param MoodleQuickForm $mform form the elements are added to.
     * @param bool $forsection 'true' if this is a section edit form, 'false' if this is course edit form.
     * @return array array of references to the added form elements.
     * @throws HTML_QuickForm_Error
     * @throws coding_exception
     * @throws dml_exception
     */
    public function create_edit_form_elements(&$mform, $forsection = false) {
        global $COURSE;
        $elements = parent::create_edit_form_elements($mform, $forsection);

        if (!$forsection && (empty($COURSE->id) || $COURSE->id == SITEID)) {
            // Add "numsections" to create course form - will force the course pre-populated with empty sections.
            // The "Number of sections" option is no longer available when editing course.
            // Instead teachers should delete and add sections when needed.

            $courseconfig = get_config('moodlecourse');
            $max = (int)$courseconfig->maxsections;
            $element = $mform->addElement('select', 'numsections', get_string('numberweeks'), range(0, $max ?: 52));
            $mform->setType('numsections', PARAM_INT);
            if (is_null($mform->getElementValue('numsections'))) {
                $mform->setDefault('numsections', $courseconfig->numsections);
            }
            array_unshift($elements, $element);
        }
        return $elements;
    }

    /**
     * Updates format options for a course
     *
     * If course format was changed to 'tiles', we try to copy options
     * from the previous format.  We do not copy 'coursedisplay',
     * and 'hiddensections' as a defaut value of one makes sense for these for tiles format,
     * regardless of what they were.
     *
     * @param stdClass|array $data return value from {@link moodleform::get_data()} or array with data
     * @param stdClass $oldcourse if this function is called from {@link update_course()}
     *     this object contains information about the course before update
     * @return bool whether there were any changes to the options values
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function update_course_format_options($data, $oldcourse = null) {
        global $DB;

        $data = (array)$data;
        if ($oldcourse !== null) {
            $oldcourse = (array)$oldcourse;
            $options = $this->course_format_options();
            foreach ($options as $key => $unused) {
                if (!array_key_exists($key, $data)) {
                    if (array_key_exists($key, $oldcourse)) {
                        $data[$key] = $oldcourse[$key];
                    }
                }
            }
        }
        // While we are changing the format options, set section zero to visible if it is hidden.
        // Should never be hidden but rarely it happens, for reasons which are not clear esp with onetopic format.
        // See https://moodle.org/mod/forum/discuss.php?d=356850 and MDL-37256).

        if (isset($data['id'])
            && $section = $DB->get_record("course_sections", array('course' => $data['id'], 'section' => 0))) {
            if (!$section->visible) {
                set_section_visible($section->course, 0, 1);
            }
        }
        if (isset($data['courseusesubtiles']) && $data['courseusesubtiles'] == 0) {
            // We are deactivating sub tiles at course level so do it at sec zero level too.
            $data['usesubtilesseczero'] = 0;
        }
        return $this->update_format_options($data);
    }

    /**
     * Updates format options for a section
     * Includes a check to strip out default values for tile icon or outcome id
     * as it would be wasteful to store large volumes of these on a per section basis
     *
     * Section id is expected in $data->id (or $data['id'])
     * If $data does not contain property with the option name, the option will not be updated
     *
     * @param stdClass|array $data return value from {@link moodleform::get_data()} or array with data
     * @return bool whether there were any changes to the options values
     * @throws dml_exception
     */
    public function update_section_format_options($data) {
        global $DB;
        $data = (array)$data;
        $oldvalues = array(
            'iconthistile' => $DB->get_field(
                'course_format_options', 'value',
                ['format' => 'tiles', 'sectionid' => $data['id'], 'name' => 'tileicon']
            ),
            'outcomethistile' => $DB->get_record(
                'course_format_options',
                ['format' => 'tiles', 'sectionid' => $data['id'], 'name' => 'tileoutcomeid']
            )
        );

        // If the edit is taking place from format_tiles_inplace_editable(),
        // the data array may not contain the tile icon and outcome id at all.
        // So add these items in if missing.
        if (!isset($data['tileicon']) && $oldvalues['iconthistile']) {
            $data['tileicon'] = $oldvalues['iconthistile'];
        }
        if (!isset($data['tileoutcomeid']) && $oldvalues['outcomethistile']) {
            $data['tileoutcomeid'] = $oldvalues['outcomethistile'];
        }

        // Unset the new values if null, before we send to update.
        // This is so that we don't get a false positive as to whether it has changed or not.
        if ($data['tileicon'] == '') {
            unset($data['tileicon']);
        }
        if (isset($data['tileoutcomeid']) && $data['tileoutcomeid'] == '0') {
            unset($data['tileoutcomeid']);
        }

        // Now send the update.
        $result = $this->update_format_options($data, $data['id']);

        // Now remove any default values such as '' or '0' which the update stored in the database as they are redundant.
        $keystoremove = ['tileicon', 'tileoutcomeid'];
        foreach ($keystoremove as $key) {
            if (!isset($data[$key])) {
                $DB->delete_records('course_format_options', ['format' => 'tiles', 'sectionid' => $data['id'], 'name' => $key]);
                if (isset($oldvalues[$key]) && $oldvalues[$key]) {
                    // Used to have a value so return true to indicate it changed.
                    $result = true;
                }
            }
        }
        return $result;
    }

    /**
     * Prepares the templateable object to display section name
     *
     * @param \section_info|\stdClass $section
     * @param bool $linkifneeded
     * @param bool $editable
     * @param null|lang_string|string $edithint
     * @param null|lang_string|string $editlabel
     * @return \core\output\inplace_editable
     * @throws coding_exception
     */
    public function inplace_editable_render_section_name($section, $linkifneeded = true,
                                                         $editable = null, $edithint = null, $editlabel = null) {
        if (empty($edithint)) {
            $edithint = new lang_string('editsectionname', 'format_tiles');
        }
        if (empty($editlabel)) {
            $title = get_section_name($section->course, $section);
            $editlabel = new lang_string('newsectionname', 'format_tiles', $title);
        }
        return parent::inplace_editable_render_section_name($section, $linkifneeded, $editable, $edithint, $editlabel);
    }


    /**
     * Get an array of all the Outcomes set for this course by the teacher, so that they can
     * be attached to individual Tiles, and then used to filter tiles by Outcome
     * @see get_filter_outcome_buttons()
     * @see course_format_options() and the displayfilterbar option
     * @param int $courseid
     * @return array|null
     */
    public function format_tiles_get_course_outcomes($courseid) {
        global $CFG;
        if (!empty($CFG->enableoutcomes)) {
            require_once($CFG->libdir . '/gradelib.php');
            $outcomes = [];
            $outcomesfull = grade_outcome::fetch_all_available($courseid);
            foreach ($outcomesfull as $outcome) {
                $outcomes[$outcome->id] = $outcome->fullname;
            }
            asort($outcomes);
            return $outcomes;
        } else {
            return null;
        }
    }

    /**
     * Returns whether this course format allows the activity to
     * have "triple visibility state" - visible always, hidden on course page but available, hidden.
     * Copied from format_topics
     *
     * @param stdClass|cm_info $cm course module (may be null if we are displaying a form for adding a module)
     * @param stdClass|section_info $section section where this module is located or will be added to
     * @return bool
     */
    public function allow_stealth_module_visibility($cm, $section) {
        // Allow the third visibility state inside visible sections or in section 0.
        return !$section->section || $section->visible;
    }

    /**
     * Callback used in WS core_course_edit_section when teacher performs an AJAX action on a section (show/hide)
     *
     * Access to the course is already validated in the WS but the callback has to make sure
     * that particular action is allowed by checking capabilities
     *
     * Course formats should register
     *
     * @param stdClass|section_info $section
     * @param string $action
     * @param int $sr
     * @return null|array|stdClass any data for the Javascript post-processor (must be json-encodeable)
     * @throws moodle_exception
     * @throws required_capability_exception
     */
    public function section_action($section, $action, $sr) {
        global $PAGE;

        if ($section->section && ($action === 'setmarker' || $action === 'removemarker')) {
            // Format 'tiles' allows to set and remove markers in addition to common section actions.
            require_capability('moodle/course:setcurrentsection', context_course::instance($this->courseid));
            course_set_marker($this->courseid, ($action === 'setmarker') ? $section->section : 0);
            return null;
        }

        // For show/hide actions call the parent method and return the new content for .section_availability element.
        $rv = parent::section_action($section, $action, $sr);
        $renderer = $PAGE->get_renderer('format_tiles');
        $rv['section_availability'] = $renderer->section_availability($this->get_section($section));
        return $rv;
    }

    /**
     * Course-specific information to be output on any course page (usually in the beginning of
     * standard footer)
     *
     * See {@link format_base::course_header()} for usage
     *
     * In the case of this format, checks if the user is on the course/edit/php?id=xxx page (edit course settings)
     * and if they are, includes necessary JS to enable icon picker window to be launched (choose default tile icon)
     * and returns a renderable object representing the modal window to be added to course footer
     *
     * Previously tried course_footer() but essential theme does not call it and this works just as well
     * @see \format_tiles_icon_picker
     * @see \format_tiles_renderer::render_format_tiles_icon_picker()
     *
     * @return null|format_tiles_icon_picker_icons null for no output or object with data for plugin renderer
     * @throws moodle_exception
     */
    public function course_content_footer() {
        global $PAGE;
        if ($PAGE->has_set_url()) {
            if ($PAGE->user_allowed_editing()) {
                $courseid = $PAGE->course->id;
                $editingcoursesettings = $PAGE->pagetype == 'course-edit'
                    && $PAGE->url->compare(new moodle_url('/course/edit.php', array('id' => $courseid)), URL_MATCH_EXACT);
                $editingcoursesection = $PAGE->pagetype == 'course-editsection'
                    && $PAGE->url->compare(new moodle_url('/course/editsection.php'), URL_MATCH_BASE);
                if ($editingcoursesettings) {
                    // Only require this on the edit course settings page, not edit section.
                    $PAGE->requires->js_call_amd('format_tiles/edit_form_helper', 'init', array());
                }
                if ($editingcoursesettings || $editingcoursesection) {
                    $PAGE->requires->js_call_amd('format_tiles/icon_picker', 'init',
                        array('courseId' => $courseid, 'pagetype' => $PAGE->pagetype)
                    );
                    return new format_tiles_icon_picker_icons();
                }
            }

        }
        return null;
    }

    /**
     * Allows course format to execute code on moodle_page::set_course()
     * Used here to ensure that, before starting to load the page,
     * we establish if the user is changing their pref for using JS nav
     * and change the setting if so
     *
     * @param moodle_page $page instance of page calling set_course
     * @throws coding_exception
     * @throws dml_exception
     */
    public function page_set_course(moodle_page $page) {
        if (get_config('format_tiles', 'usejavascriptnav')) {
            if (optional_param('stopjsnav', 0, PARAM_INT) == 1) {
                // User is toggling JS nav setting.
                $existingstoppref = get_user_preferences('format_tiles_stopjsnav', 0);
                if (!$existingstoppref) {
                    // Did not already have it disabled.
                    set_user_preference('format_tiles_stopjsnav', 1);
                } else {
                    // User previously disabled it, but now is re-enabling.
                    unset_user_preference('format_tiles_stopjsnav');
                    \core\notification::success(get_string('jsreactivated', 'format_tiles'));
                }
            }
        }
    }
}

/**
 * Implements callback inplace_editable() allowing to edit values in-place
 *
 * @param string $itemtype
 * @param int $itemid
 * @param mixed $newvalue
 * @return \core\output\inplace_editable | null
 * @throws dml_exception
 */
function format_tiles_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');
    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        $section = $DB->get_record_sql(
            'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
            array($itemid, 'tiles'), MUST_EXIST);
        return course_get_format($section->course)->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
}

/**
 * Get icon mapping for font-awesome.
 * To make additional font awesome icons available as tile icons, add them below (or indeed remove)
 * Ideally all of the icons specific here would be removed from the pix directory as those images
 * are never called if the theme is font awesome compatible.  However they are left in pix for now
 * as fallbacks, since the clean theme does not yet support font awesome
 * (does not specify $THEME->iconsystem as fa like Boost and Essential do)
 */
function format_tiles_get_fontawesome_icon_map() {
    return [
        // First the general icons (not specific to tiles).
        'format_tiles:bullhorn' => 'fa-bullhorn',
        'format_tiles:check' => 'fa-check',
        'format_tiles:chevron-down' => 'fa-chevron-down',
        'format_tiles:chevron-left' => 'fa-chevron-left',
        'format_tiles:chevron-right' => 'fa-chevron-right',
        'format_tiles:chevron-up' => 'fa-chevron-up',
        'format_tiles:clone' => 'fa-clone',
        'format_tiles:close' => 'fa-close',
        'format_tiles:cloud-download' => 'fa-cloud-download',
        'format_tiles:filter' => 'fa-filter',
        'format_tiles:expand2' => 'fa-expand',
        'format_tiles:eye-slash' => 'fa-eye-slash',
        'format_tiles:file-pdf-o' => 'fa-file-pdf-o',
        'format_tiles:home' => 'fa-home',
        'format_tiles:list' => 'fa-list',
        'format_tiles:lock' => 'fa-lock',
        'format_tiles:star-o' => 'fa-star-o',
        'format_tiles:pencil' => 'fa-pencil',
        'format_tiles:random' => 'fa-random',
        'format_tiles:star' => 'fa-star',
        'format_tiles:stop' => 'fa-stop',
        'format_tiles:table' => 'fa-table',
        'format_tiles:trash-o' => 'fa-trash-o',
        'format_tiles:volume-up' => 'fa-volume-up',

        // Sub tile icons.
        'format_tiles:subtile/comments-o' => 'fa-comments-o',
        'format_tiles:subtile/database' => 'fa-database',
        'format_tiles:subtile/feedback' => 'fa-bullhorn',
        'format_tiles:subtile/file-excel' => 'fa-table',
        'format_tiles:subtile/file-pdf-o' => 'fa-file-pdf-o',
        'format_tiles:subtile/file-powerpoint-o' => 'fa-file-powerpoint-o',
        'format_tiles:subtile/file-text-o' => 'fa-file-text-o',
        'format_tiles:subtile/file-word-o' => 'fa-file-word-o',
        'format_tiles:subtile/file-zip-o' => 'fa-file-zip-o',
        'format_tiles:subtile/film' => 'fa-film',
        'format_tiles:subtile/folder-o' => 'fa-folder-o',
        'format_tiles:subtile/globe' => 'fa-globe',
        'format_tiles:subtile/puzzle-piece' => 'fa-puzzle-piece',
        'format_tiles:subtile/question-circle' => 'fa-question-circle',
        'format_tiles:subtile/star' => 'fa-star',
        'format_tiles:subtile/star-o' => 'fa-star-o',
        'format_tiles:subtile/survey' => 'fa-bar-chart',
        'format_tiles:subtile/volume-up' => 'fa-volume-up',

        // Now the tile icons.
        'format_tiles:tileicon/asterisk' => 'fa-asterisk',
        'format_tiles:tileicon/address-book' => 'fa-address-book-o',
        'format_tiles:tileicon/balance-scale' => 'fa-balance-scale',
        'format_tiles:tileicon/bell-o' => 'fa-bell-o',
        'format_tiles:tileicon/binoculars' => 'fa-binoculars',
        'format_tiles:tileicon/bitcoin' => 'fa-bitcoin',
        'format_tiles:tileicon/bookmark-o' => 'fa-bookmark-o',
        'format_tiles:tileicon/briefcase' => 'fa-briefcase',
        'format_tiles:tileicon/building' => 'fa-building',
        'format_tiles:tileicon/bullhorn' => 'fa-bullhorn',
        'format_tiles:tileicon/bullseye' => 'fa-bullseye',
        'format_tiles:tileicon/calculator' => 'fa-calculator',
        'format_tiles:tileicon/calendar' => 'fa-calendar',
        'format_tiles:tileicon/calendar-check-o' => 'fa-calendar-check-o',
        'format_tiles:tileicon/check' => 'fa-check',
        'format_tiles:tileicon/child' => 'fa-child',
        'format_tiles:tileicon/clock-o' => 'fa-clock-o',
        'format_tiles:tileicon/clone' => 'fa-clone',
        'format_tiles:tileicon/cloud-download' => 'fa-cloud-download',
        'format_tiles:tileicon/cloud-upload' => 'fa-cloud-upload',
        'format_tiles:tileicon/comment-o' => 'fa-comment-o',
        'format_tiles:tileicon/comments-o' => 'fa-comments-o',
        'format_tiles:tileicon/compass' => 'fa-compass',
        'format_tiles:tileicon/diamond' => 'fa-diamond',
        'format_tiles:tileicon/dollar' => 'fa-dollar',
        'format_tiles:tileicon/euro' => 'fa-euro',
        'format_tiles:tileicon/exclamation-triangle' => 'fa-exclamation-triangle',
        'format_tiles:tileicon/feed' => 'fa-feed',
        'format_tiles:tileicon/file-text-o' => 'fa-file-text-o',
        'format_tiles:tileicon/film' => 'fa-film',
        'format_tiles:tileicon/flag-checkered' => 'fa-flag-checkered',
        'format_tiles:tileicon/flag-o' => 'fa-flag-o',
        'format_tiles:tileicon/flash' => 'fa-flash',
        'format_tiles:tileicon/flask' => 'fa-flask',
        'format_tiles:tileicon/frown-o' => 'fa-frown-o',
        'format_tiles:tileicon/gavel' => 'fa-gavel',
        'format_tiles:tileicon/gbp' => 'fa-gbp',
        'format_tiles:tileicon/globe' => 'fa-globe',
        'format_tiles:tileicon/handshake-o' => 'fa-handshake-o',
        'format_tiles:tileicon/headphones' => 'fa-headphones',
        'format_tiles:tileicon/heartbeat' => 'fa-heartbeat',
        'format_tiles:tileicon/history' => 'fa-history',
        'format_tiles:tileicon/home' => 'fa-home',
        'format_tiles:tileicon/id-card-o' => 'fa-id-card-o',
        'format_tiles:tileicon/info' => 'fa-info',
        'format_tiles:tileicon/key' => 'fa-key',
        'format_tiles:tileicon/laptop' => 'fa-laptop',
        'format_tiles:tileicon/life-buoy' => 'fa-life-buoy',
        'format_tiles:tileicon/lightbulb-o' => 'fa-lightbulb-o',
        'format_tiles:tileicon/line-chart' => 'fa-line-chart',
        'format_tiles:tileicon/list' => 'fa-list',
        'format_tiles:tileicon/list-ol' => 'fa-list-ol',
        'format_tiles:tileicon/location-arrow' => 'fa-location-arrow',
        'format_tiles:tileicon/map-marker' => 'fa-map-marker',
        'format_tiles:tileicon/map-o' => 'fa-map-o',
        'format_tiles:tileicon/map-signs' => 'fa-map-signs',
        'format_tiles:tileicon/microphone' => 'fa-microphone',
        'format_tiles:tileicon/mobile-phone' => 'fa-mobile-phone',
        'format_tiles:tileicon/mortar-board' => 'fa-mortar-board',
        'format_tiles:tileicon/newspaper-o' => 'fa-newspaper-o',
        'format_tiles:tileicon/pencil-square-o' => 'fa-pencil-square-o',
        'format_tiles:tileicon/pie-chart' => 'fa-pie-chart',
        'format_tiles:tileicon/podcast' => 'fa-podcast',
        'format_tiles:tileicon/puzzle-piece' => 'fa-puzzle-piece',
        'format_tiles:tileicon/question-circle' => 'fa-question-circle',
        'format_tiles:tileicon/random' => 'fa-random',
        'format_tiles:tileicon/refresh' => 'fa-refresh',
        'format_tiles:tileicon/road' => 'fa-road',
        'format_tiles:tileicon/search' => 'fa-search',
        'format_tiles:tileicon/sliders' => 'fa-sliders',
        'format_tiles:tileicon/smile-o' => 'fa-smile-o',
        'format_tiles:tileicon/star' => 'fa-star',
        'format_tiles:tileicon/star-half-o' => 'fa-star-half-o',
        'format_tiles:tileicon/star-o' => 'fa-star-o',
        'format_tiles:tileicon/tags' => 'fa-tags',
        'format_tiles:tileicon/tasks' => 'fa-tasks',
        'format_tiles:tileicon/television' => 'fa-television',
        'format_tiles:tileicon/thumbs-o-down' => 'fa-thumbs-o-down',
        'format_tiles:tileicon/thumbs-o-up' => 'fa-thumbs-o-up',
        'format_tiles:tileicon/trophy' => 'fa-trophy',
        'format_tiles:tileicon/umbrella' => 'fa-umbrella',
        'format_tiles:tileicon/university' => 'fa-university',
        'format_tiles:tileicon/user-o' => 'fa-user-o',
        'format_tiles:tileicon/users' => 'fa-users',
        'format_tiles:tileicon/volume-up' => 'fa-volume-up',
        'format_tiles:tileicon/wrench' => 'fa-wrench',
    ];
}