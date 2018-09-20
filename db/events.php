<?php

$observers = array (
    array(
    'eventname'     =>  '\core\event\course_content_deleted',
    'includefile'   =>  '/course/format/tiles/locallib.php',
    'callback'      => 'format_tiles_delete_course',
    ),
);