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
 * Settings used by the tiles course format
 *
 * @package format_tiles
 * @copyright  2016 David Watson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 **/

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/format/tiles/lib.php');

if ($ADMIN->fulltree) {
    // Javascript navigation settings.
    $settings->add(new admin_setting_heading('jsnavsettings', get_string('jsnavsettings', 'format_tiles'), ''));
    $name = 'format_tiles/usejavascriptnav';
    $title = get_string('usejavascriptnav', 'format_tiles');
    $description = get_string('usejavascriptnav_desc', 'format_tiles');
    $default = 1;
    $settings->add(new admin_setting_configcheckbox($name, $title, $description, $default));

    $name = 'format_tiles/allowsubtilesview';
    $title = get_string('allowsubtilesview', 'format_tiles');
    $description = get_string('allowsubtilesview_desc', 'format_tiles');
    $default = 1;
    $settings->add(new admin_setting_configcheckbox($name, $title, $description, $default));

    $name = 'format_tiles/reopenlastsection';
    $title = get_string('reopenlastsection', 'format_tiles');
    $description = get_string('reopenlastsection_desc', 'format_tiles');
    $default = 1;
    $settings->add(new admin_setting_configcheckbox($name, $title, $description, $default));

    $name = 'format_tiles/usejsnavforsinglesection';
    $title = get_string('usejsnavforsinglesection', 'format_tiles');
    $description = get_string('usejsnavforsinglesection_desc', 'format_tiles');
    $default = 1;
    $settings->add(new admin_setting_configcheckbox($name, $title, $description, $default));

    // Modal windows for course modules.
    $allowedmodtypes = ['page'];
    $allmodtypes = get_module_types_names();
    $options = [];
    foreach ($allowedmodtypes as $modtype) {
        if (isset($allmodtypes[$modtype])) {
            $options[$modtype] = $allmodtypes[$modtype];
        }
    }
    $name = 'format_tiles/modalmodules';
    $title = get_string('modalmodules', 'format_tiles');
    $description = get_string('modalmodules_desc', 'format_tiles');
    $setting = new admin_setting_configmulticheckbox(
        $name,
        $title,
        $description,
        array('page' => 1),
        $options
    );
    $settings->add($setting);

    // Modal windows for resources.
    $allowedresourcetypes = array('pdf' => 'PDF', 'html' => 'HTML');
    $name = 'format_tiles/modalresources';
    $title = get_string('modalresources', 'format_tiles');
    $description = get_string('modalresources_desc', 'format_tiles');
    $setting = new admin_setting_configmulticheckbox(
        $name,
        $title,
        $description,
        array('pdf' => 1, 'html' => 1),
        $allowedresourcetypes
    );
    $settings->add($setting);

    // Browser Session Storage (storing course content).
    $choices = [];
    for ($x = 0; $x <= 20; $x++) {
        $choices[$x] = $x;
    }
    $settings->add(new admin_setting_heading('browsersessionstorage', get_string('browsersessionstorage', 'format_tiles'), ''));

    $name = 'format_tiles/assumedatastoreconsent';
    $title = get_string('assumedatastoreconsent', 'format_tiles');
    $description = get_string('assumedatastoreconsent_desc', 'format_tiles');
    $default = 0;
    $settings->add(new admin_setting_configcheckbox($name, $title, $description, $default));

    $setting = new admin_setting_configselect(
        'format_tiles/jsmaxstoreditems',
        get_string('jsmaxstoreditems', 'format_tiles'),
        get_string('jsmaxstoreditems_desc', 'format_tiles'),
        8,
        $choices);
    $settings->add($setting);

    $choices = [];
    for ($x = 30; $x <= 300; $x += 30) {
        $choices[$x] = $x;
    }
    $setting = new admin_setting_configselect(
        'format_tiles/jsstoredcontentexpirysecs',
        get_string('jsstoredcontentexpirysecs', 'format_tiles'),
        get_string('jsstoredcontentexpirysecs_desc', 'format_tiles'),
        120,
        $choices);
    $settings->add($setting);

    $choices = [];
    for ($x = 2; $x <= 30; $x += 2) {
        $choices[$x] = $x;
    }
    $setting = new admin_setting_configselect(
        'format_tiles/jsstoredcontentdeletemins',
        get_string('jsstoredcontentdeletemins', 'format_tiles'),
        get_string('jsstoredcontentdeletemins_desc', 'format_tiles'),
        10,
        $choices);
    $settings->add($setting);

    // Colour settings.

    $settings->add(new admin_setting_heading('coloursettings', get_string('coloursettings', 'format_tiles'), ''));
    $name = 'format_tiles/followthemecolour';
    $title = get_string('followthemecolour', 'format_tiles');
    $description = get_string('followthemecolour_desc', 'format_tiles');
    $default = 0;
    $settings->add(new admin_setting_configcheckbox($name, $title, $description, $default));

    $brandcolourdefaults = array(
        '#1670CC' => get_string('colourblue', 'format_tiles'),
        '#00A9CE' => get_string('colourlightblue', 'format_tiles'),
        '#7A9A01' => get_string('colourgreen', 'format_tiles'),
        '#009681' => get_string('colourdarkgreen', 'format_tiles'),
        '#D13C3C' => get_string('colourred', 'format_tiles'),
        '#772583' => get_string('colourpurple', 'format_tiles'),
    );
    $colournumber = 1;
    foreach ($brandcolourdefaults as $hex => $displayname) {
        $settings->add(
            new admin_setting_heading(
                'brand' . $colournumber,
                get_string('brandcolour', 'format_tiles') . ' ' . $colournumber, ''
            )
        );
        // Colour picker for this brand.
        $setting = new admin_setting_configcolourpicker(
            'format_tiles/tilecolour' . $colournumber,
            get_string('tilecolourgeneral', 'format_tiles') . ' ' . $colournumber,
            get_string('tilecolourgeneral_descr', 'format_tiles'),
            $hex
        );
        $settings->add($setting);

        // Display name for this brand.
        $setting = new admin_setting_configtext(
            'format_tiles/colourname' . $colournumber,
            get_string('colournamegeneral', 'format_tiles') . ' ' . $colournumber,
            get_string('colourname_descr', 'format_tiles'),
            $displayname,
            PARAM_RAW,
            30
        );
        $settings->add($setting);
        $colournumber++;
    }

    $settings->add(new admin_setting_heading('hovercolourheading', get_string('hovercolour', 'format_tiles'), ''));
    // Hover colour for all tiles (in hexadecimal RGB with preceding '#').
    $name = 'format_tiles/hovercolour';
    $title = get_string('hovercolour', 'format_tiles');
    $description = get_string('hovercolour_descr', 'format_tiles');
    $default = '#ED8B00';
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default);
    $settings->add($setting);

    // Other settings.

    // Custom css.
    $settings->add(new admin_setting_heading('othersettings', get_string('othersettings', 'format_tiles'), ''));
    $name = 'format_tiles/customcss';
    $title = get_string('customcss', 'format_tiles');
    $description = get_string('customcssdesc', 'format_tiles');
    $default = '';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default);
    $settings->add($setting);

    $name = 'format_tiles/showseczerocoursewide';
    $title = get_string('showseczerocoursewide', 'format_tiles');
    $description = get_string('showseczerocoursewide_desc', 'format_tiles');
    $default = 0;
    $settings->add(new admin_setting_configcheckbox($name, $title, $description, $default));

    $name = 'format_tiles/allowlabelconversion';
    $title = get_string('allowlabelconversion', 'format_tiles');
    $description = get_string('allowlabelconversion_desc', 'format_tiles');
    $default = 0;
    $settings->add(new admin_setting_configcheckbox($name, $title, $description, $default));
}