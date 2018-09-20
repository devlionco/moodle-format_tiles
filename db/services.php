<?php

$functions = array (
    'format_tiles_set_icon' => array(
        'classname'   => 'format_tiles_external',
        'methodname'  => 'set_icon',
        'classpath'   => 'course/format/tiles/externallib.php',
        'description' => 'Set tile icon (intended to be used from AJAX)',
        'type'        => 'write',
        'ajax'        =>  true,
        'loginrequired' => true,
        'capabilities' => 'moodle/course:update'
    ),
    'format_tiles_log_mod_view' => array(
        'classname'   => 'format_tiles_external',
        'methodname'  => 'log_mod_view',
        'classpath'   => 'course/format/tiles/externallib.php',
        'description' => 'Trigger course module view event (for log) for a resource (for modal use)',
        'type'        => 'write',
        'ajax'        =>  true,
        'loginrequired' => true,
        'capabilities' => 'mod/[modulename]:view'
    ),
    'format_tiles_get_single_section_page_html' => array(
        'classname'   => 'format_tiles_external',
        'methodname'  => 'get_single_section_page_html',
        'classpath'   => 'course/format/tiles/externallib.php',
        'description' => 'Get HTML for single section page for this course (i.e. tile contents)',
        'type'        => 'read',
        'ajax'        =>  true,
        'loginrequired' => true,
        'capabilities' => '' // enrolment check, not capability - see externallib.php
    ),
    'format_tiles_log_tile_click' => array(
        'classname'   => 'format_tiles_external',
        'methodname'  => 'log_tile_click',
        'classpath'   => 'course/format/tiles/externallib.php',
        'description' => 'Trigger course view event for a section (for log) on section tile click',
        'type'        => 'write',
        'ajax'        =>  true,
        'loginrequired' => true,
        'capabilities' => ''  // enrolment check, not capability - see externallib.php
    ),
    'format_tiles_get_mod_page_html' => array(
        'classname'   => 'format_tiles_external',
        'methodname'  => 'get_mod_page_html',
        'classpath'   => 'course/format/tiles/externallib.php',
        'description' => 'Return the HTML for a page course module (for modal use)',
        'type'        => 'read',
        'ajax'        =>  true,
        'loginrequired' => true,
        'capabilities' => 'mod/page:view'
    ),
);
