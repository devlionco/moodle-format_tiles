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
 * Strings for component 'format_tiles', language 'en', branch 'MOODLE_33_STABLE'
 *
 * @package   format_tiles
 * @copyright David Watson
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['all'] = "All";
$string['allcomplete'] = 'All complete';
$string['allowlabelconversion'] = 'Allow label conversion to page (experimental)';
$string['allowlabelconversion_desc'] = 'If checked, editing teachers will be given an option in each label\'s edit settings drop down to convert the label to a page.  This is an experimental setting.';
$string['allowsubtilesview'] = 'Allow sub tiles view';
$string['allowsubtilesview_desc'] = 'Allow use of a course setting which, if selected, within a tile shows activities (except labels) as sub tiles, instead of standard list';
$string['areyousure?'] = 'Are you sure?';
$string['asfraction'] = 'Show as fraction';
$string['aspercentagedial'] = 'Show as % in circle';
$string['basecolour_help'] = 'Colour set here will be applied to all tiles in the course';
$string['basecolour'] = 'Colour for tiles';
$string['brandcolour'] = 'Brand colour';
$string['browsersessionstorage'] = 'Browser session storage (storing course content)';
$string['changecourseicon'] = 'Click to pick new icon';
$string['colourblue'] = 'Blue';
$string['colourdarkgreen'] = 'Dark Green';
$string['colourgreen'] = 'Green';
$string['colourlightblue'] = 'Light Blue';
$string['colourpurple'] = 'Purple';
$string['colourred'] = 'Red';
$string['close'] = 'Close';
$string['collapse'] = 'Collapse section';
$string['collapsesections'] = 'Collapse all sections';
$string['coloursettings'] = 'Colour settings';
$string['complete'] = 'complete';
$string['completion_help'] = 'A tick to the right of an activity may be used to indicate when the activity is complete (an empty circle will be shown if not).<br><br>
Depending on the setting, a tick may appear automatically when you have completed the activity according to conditions set by the teacher.<br><br>
In other cases, you can click the empty circle when you think you have completed the activity and it will turn into a solid green tick. (Clicking it again removes the tick if you change your mind.)';
$string['completionswitchhelp'] = '<p>You have selected to show completion tracking on each tile.  We have therefore set "Completion Tracking > Enable" further down this page to "Yes".</p>
<p>In addition, you need to switch on completion tracking for <b>each item</b> that you are tracking.  e.g. for a PDF, click "Edit settings", look under Activity Completion, and pick the setting you need.</p>
<p>You can also do this in <b>bulk</b> as explained in the <a href="https://docs.moodle.org/35/en/Activity_completion_settings" target="_blank">detailed explanation of completion tracking on moodle.org</a></p>';
$string['complete-n-auto'] = 'Item not complete.  It will be marked as complete when you meet the completion criteria. You cannot change this manually.';
$string['complete-y-auto'] = 'Item complete.  It was marked as complete when you met the completion criteria. You cannot change this manually.';
$string['completionwarning'] = 'You have completion tracking switched on at the course level, but at the individual activity level, no items have tracking enabled, so there is nothing to track.';
$string['completionwarning_help'] = 'You need to make individual items trackable by editing them (under Activity Completion > Completion tracking) or you can do this in bulk under Course Administration > Course Completion > Bulk edit activity completion';
$string['completionwarning_changeinbulk'] = 'Change in bulk';
$string['contents'] = 'Contents';
$string['converttopage'] = 'Convert to page';
$string['converttopage_confirm'] = 'Are you sure?.  This cannot be un-done (you would have to create the label again manually if needed).';
$string['courseshowtileprogress_error'] = "You have 'Completion tracking > Enable completion tracking' set to 'No' (see further down this page) which conflicts with this setting.  If you wish to display progress on the tiles, please set 'Completion tracking > Enable completion tracking' to 'Yes'.  Otherwise, please set this setting to 'No'.";
$string['courseshowtileprogress_help'] = '<p>When selected, the user\'s progress with activities will be shown in each tile, either as a <em>fraction</em> (e.g. \'Progress 2/10\' meaning 2 out of 10 activities complete), or as a <em>percentage</em> in a circle.</p><p>This can only be used if \'Completion > Enable completion tracking\' has been switched on.</p><p>If there are no trackable activities within a given tile, indicator will not be shown for that tile.</p>';
$string['courseshowtileprogress_link'] = 'Activity_completion_settings#Activity_settings';
$string['courseshowtileprogress'] = 'Progress on each tile';
$string['courseusebarforheadings_help'] = 'Display a coloured tab to the left of the heading in the course whenever a heading style is selected in the text editor';
$string['courseusebarforheadings'] = 'Emphasise headings with coloured tab';
$string['courseusesubtiles'] = 'Use sub tiles for activities';
$string['courseusesubtiles_help'] = 'Within each tile, show every activity as a sub tile, instead of as a list of activities down the page.  This does not apply to labels which will not be shown as sub tiles so can be used as headings between tiles.  ';
$string['currentsection'] = 'This tile';
$string['datapref'] = 'Data preference';
$string['dataprefquestion'] = '<p>To make this site easier to use, we store functional information in your browser such as the contents of the last tile you opened.  This remains on your machine for a short while in case you visit that page again.  We do not use it for tracking.  Is that okay?</p><p>We will remember your choice until you clear your browsing history.  Saying "No" may result in slower page loading times.</p>';
$string['datapreferror'] = 'The data preference feature is ony available if you have javascript available in your browser. Otherwise, data storage cannot be enabled.';
$string['defaulttileicon_help'] = 'The icon selected here will appear on <em>all</em> tiles in this course.  Individual tiles can have a different icon selected, using the different setting at the tile level.';
$string['defaulttileicon'] = 'Tile icon';
$string['defaultthiscourse'] = 'Default for this course';
$string['deletesection'] = 'Delete tile';
$string['displayfilterbar_error'] = 'Unless you have set up outcomes for this course, you can only display a filter bar based on tile numbers, and not based on outcomes.  Create some outcomes first then come back here. See';
$string['displayfilterbar_help'] = '<p>When selected, will automatically display an array of buttons before the tile screen in a course, which users can click to filter down tiles to certain ranges</p><p>When \'based on tile numbers\' is selected, a series of buttons will be displayed e.g. a button for tiles  1-4, a button for tiles 5-8 etc.</p><p>When \'based on course outcomes\' is selected, there will be one button per course outcome.  Each each tile can be assigned to a given outcome (and therefore to a given button) from that tile\'s settings page.</p> ';
$string['displayfilterbar_link'] = 'Outcomes';
$string['displayfilterbar'] = 'Filter bar';
$string['displaytitle_mod_pdf'] = 'PDF';
$string['displaytitle_mod_mp3'] = 'Audio';
$string['displaytitle_mod_mp4'] = 'Video';
$string['displaytitle_mod_doc'] = 'Word document';
$string['displaytitle_mod_jpeg'] = 'Image';
$string['displaytitle_mod_html'] = 'Web page';
$string['displaytitle_mod_xls'] = 'Spreadsheet';
$string['displaytitle_mod_txt'] = 'Text';
$string['displaytitle_mod_ppt'] = 'Powerpoint presentation';
$string['displaytitle_mod_zip'] = 'Zip';
$string['download'] = 'Download';
$string['editsectionname'] = 'Edit tile name';
$string['entersection'] = 'Enter section';
$string['expand'] = 'Expand';
$string['expandsections'] = 'Reveal all activities (all sections)';
$string['fileaddedtobottom'] = 'File added to bottom of section';
$string['filenoshowtext'] = 'If file does not show here, please use the buttons on the right to download or view in new window';
$string['filterboth'] = 'Show buttons based on tile numbers and course outcomes';
$string['filternumbers'] = 'Show buttons based on tile numbers';
$string['filteroutcomes'] = 'Show buttons based on course outcomes';
$string['folderdisplayerror'] = 'Folders set to display content inline are not compatible with sub-tiles format.  This <a href="{$a}">folder</a> has therefore been changed to display on a separate page';
$string['followthemecolour_desc'] = 'If set to yes, teachers will not be given a choice by this plugin and all tile colours below will be ignored.  Instead an attempt will be made to get the theme\'s main brand colour and use that instead';
$string['followthemecolour'] = 'Force follow theme colour';
$string['hide'] = 'Hide';
$string['hidefromothers'] = 'Hide tile';
$string['highlightoff'] = 'De-highlight';
$string['home'] = 'Course home';
$string['icontitle-address-book-o'] = 'Address book';
$string['icontitle-assessment_graded'] = 'Assessment A+';
$string['icontitle-assessment_timer'] = 'Assessment timer';
$string['icontitle-asterisk'] = 'Asterisk';
$string['icontitle-award-solid'] = 'Award rosette';
$string['icontitle-balance-scale'] = 'Balance scales';
$string['icontitle-bar-chart'] = 'Bar Chart';
$string['icontitle-bell-o'] = 'Bell';
$string['icontitle-binoculars'] = 'Binoculars';
$string['icontitle-bitcoin'] = 'Bitcoin';
$string['icontitle-book'] = 'Book';
$string['icontitle-bookmark-o'] = 'Bookmark';
$string['icontitle-briefcase'] = 'Briefcase';
$string['icontitle-building'] = 'Building';
$string['icontitle-bullhorn'] = 'Bullhorn';
$string['icontitle-bullseye'] = 'Bullseye';
$string['icontitle-calculator'] = 'Calculator';
$string['icontitle-calendar'] = 'Calendar';
$string['icontitle-calendar-check-o'] = 'Calendar with check mark';
$string['icontitle-check'] = 'Check';
$string['icontitle-child'] = 'Child';
$string['icontitle-clock-o'] = 'Clock';
$string['icontitle-clone'] = 'Clone';
$string['icontitle-cloud-download'] = 'Cloud (download)';
$string['icontitle-cloud-upload'] = 'Cloud (upload)';
$string['icontitle-comment-o'] = 'Comment';
$string['icontitle-comments-o'] = 'Comments';
$string['icontitle-compass'] = 'Compass';
$string['icontitle-diamond'] = 'Diamond';
$string['icontitle-dollar'] = 'Dollar';
$string['icontitle-euro'] = 'Euro';
$string['icontitle-exclamation-triangle'] = 'Exclamation in triangle';
$string['icontitle-feed'] = 'Feed';
$string['icontitle-file-text-o'] = 'Text file';
$string['icontitle-film'] = 'Film';
$string['icontitle-flag-checkered'] = 'Flag (checkered)';
$string['icontitle-flag-o'] = 'Flag';
$string['icontitle-flash'] = 'Flash';
$string['icontitle-flask'] = 'Flask';
$string['icontitle-flipchart'] = 'Flip chart';
$string['icontitle-frown-o'] = 'Frown';
$string['icontitle-gavel'] = 'Gavel';
$string['icontitle-gbp'] = 'British pound';
$string['icontitle-globe'] = 'Globe';
$string['icontitle-handshake-o'] = 'Handshake';
$string['icontitle-headphones'] = 'Headphones';
$string['icontitle-heartbeat'] = 'Heartbeat';
$string['icontitle-history'] = 'History clock';
$string['icontitle-home'] = 'Home';
$string['icontitle-id-card-o'] = 'ID card';
$string['icontitle-info'] = 'Info';
$string['icontitle-jigsaw'] = 'Jigsaw';
$string['icontitle-key'] = 'Key';
$string['icontitle-laptop'] = 'Laptop';
$string['icontitle-life-buoy'] = 'Life belt / life buoy';
$string['icontitle-lightbulb-o'] = 'Light bulb';
$string['icontitle-line-chart'] = 'Line chart';
$string['icontitle-list'] = 'List (bullet points)';
$string['icontitle-list-ol'] = 'List (numbered)';
$string['icontitle-location-arrow'] = 'Location arrow';
$string['icontitle-map-marker'] = 'Map marker';
$string['icontitle-map-o'] = 'Map';
$string['icontitle-map-signs'] = 'Map signs';
$string['icontitle-microphone'] = 'Microphone';
$string['icontitle-mobile-phone'] = 'Mobile phone';
$string['icontitle-mortar-board'] = 'Mortar board';
$string['icontitle-music'] = 'Music';
$string['icontitle-newspaper-o'] = 'Newspaper';
$string['icontitle-number_1'] = 'Number 1';
$string['icontitle-number_10'] = 'Number 10';
$string['icontitle-number_2'] = 'Number 2';
$string['icontitle-number_3'] = 'Number 3';
$string['icontitle-number_4'] = 'Number 4';
$string['icontitle-number_5'] = 'Number 5';
$string['icontitle-number_6'] = 'Number 6';
$string['icontitle-number_7'] = 'Number 7';
$string['icontitle-number_8'] = 'Number 8';
$string['icontitle-number_9'] = 'Number 9';
$string['icontitle-pencil-square-o'] = 'Pencil in square';
$string['icontitle-person'] = 'Person';
$string['icontitle-pie-chart'] = 'Pie chart';
$string['icontitle-podcast'] = 'Podcast';
$string['icontitle-puzzle-piece'] = 'Puzzle piece';
$string['icontitle-question-circle'] = 'Question mark in circle';
$string['icontitle-random'] = 'Random';
$string['icontitle-refresh'] = 'Refresh';
$string['icontitle-road'] = 'Road';
$string['icontitle-search'] = 'Magnifying glass';
$string['icontitle-sliders'] = 'Sliders';
$string['icontitle-smile-o'] = 'Smile';
$string['icontitle-star'] = 'Star (shaded)';
$string['icontitle-star-half-o'] = 'Star (half shaded)';
$string['icontitle-star-o'] = 'Star (unshaded)';
$string['icontitle-survey'] = 'Survey';
$string['icontitle-tags'] = 'Tags';
$string['icontitle-tasks'] = 'Tasks';
$string['icontitle-television'] = 'Television';
$string['icontitle-thinking-person'] = 'Person with light bulb';
$string['icontitle-thumbs-o-down'] = 'Thumbs down';
$string['icontitle-thumbs-o-up'] = 'Thumbs up';
$string['icontitle-trophy'] = 'Trophy';
$string['icontitle-umbrella'] = 'Umbrella';
$string['icontitle-university'] = 'University';
$string['icontitle-user-o'] = 'Person (unshaded)';
$string['icontitle-users'] = 'People';
$string['icontitle-volume-up'] = 'Speaker';
$string['icontitle-wrench'] = 'Wrench';
$string['items'] = 'items';
$string['jsdeactivate'] = 'Animated navigation off';
$string['jsactivate'] = 'Animated navigation on';
$string['jsdeactivated'] = 'You have deactivated animated navigation on your account';
$string['jsreactivated'] = 'You have activated animated navigation on your account.  This may be quicker to use.  It requires JavaScript enabled.';
$string['jsmaxstoreditems'] = 'Max content items in browser session storage';
$string['jsmaxstoreditems_desc'] = 'When users are browsing on the main tiles screen, the browser will store the HTML for the Tiles overview screen itself, and the content of each Tile, up to the maximum number of items selected here.  This enables a very fast response when a new tile is clicked.  Setting this too high may result in the browser storage becoming full (although in testing so far this has not been an issue)';
$string['jsnavsettings'] = 'Javascript navigation settings';
$string['jsstoredcontentexpirysecs'] = 'Session stored content expires after (seconds)';
$string['jsstoredcontentexpirysecs_desc'] = 'When a user clicks a tile, if the tile content in the browser\'s Session Storage is older than this, it will still be displayed, but a background request will be made to the server for a fresh copy to replace it.  This is to allow for subsequent updates to the course after the browser stored its copy. If the stored content is younger than this, it will be assumed to be up to date and will be displayed, with no server request made';
$string['jsstoredcontentdeletemins'] = 'Session stored content delete after (minutes)';
$string['jsstoredcontentdeletemins_desc'] = 'After each tile click, in order to keep space free for current HTML, the user\'s browser will run a clean up and <em>delete</em> from Session storage all stored content older than this (on the basis that it is no longer reliable, and that a new copy will be needed from the server anyway)';
$string['labelconverted'] = 'Converted label';
$string['loading'] = 'Loading';
$string['newsectionname'] = 'New name for topic {$a}';
$string['notrecommended'] = 'Not recommended';
$string['modalmodules'] = 'Modal modules';
$string['modalmodules_desc'] = 'Launch these course modules in a modal window';
$string['modalresources'] = 'Modal resources ';
$string['modalresources_desc'] = 'Launch these resources in modal window';
$string['nexttopic'] = 'Next topic';
$string['nojswarning'] = 'Your browser does not seem to support javascript, or it is disabled.  An enhanced interface is available if you enable Javascript';
$string['none'] = 'None';
$string['noconnectionerror'] = 'Unable to load content.  Check your internet connection';
$string['notcomplete'] = 'Not complete';
$string['other'] = 'Other';
$string['othersettings'] = 'Other settings';
$string['outcomes'] = 'outcomes';
$string['outcomesunavailable'] = 'Outcomes unavailable';
$string['overallprogress'] = 'Activity completion - progress overall';
$string['overallprogressshort'] = 'Overall progress';
$string['overall'] = 'Overall';
$string['pickicon'] = 'Pick a new icon';
$string['pluginname'] = 'Tiles format';
$string['previoustopic'] = 'Previous topic';
$string['progress'] = 'Progress';
$string['reactivate'] = 'reactivate';
$string['reopenlastsection'] = 'Re-open last visited tile';
$string['reopenlastsection_desc'] = 'When checked, if a user revisits a course, the last section they had open will be re-opened on arrival';
$string['section0name'] = 'General';
$string['sectionname'] = 'Tile';
$string['sectionnumber'] = 'Section / tile number';
$string['sectionerrorstring'] = "Your session may have expired.  Try refreshing this page.";
$string['sectionerrortitle'] = 'Error loading content';
$string['selected'] = 'Selected';
$string['show'] = 'Show';
$string['showalltiles'] = "Show all tiles";
$string['showseparatewin'] = 'Show file in separate window';
$string['snapwarning'] = 'It may be possible to edit your course in Tiles format using another theme, and then switch back to Snap theme once you have finished editing.';
$string['snapwarning_help'] = 'Theme_settings#Allow_user_themes';
$string['subtileszeczerotoggled'] = 'Top section toggled between list and sub tiles format';
$string['revealcontents'] = 'Reveal tile contents';
$string['showfromothers'] = 'Show tile';
$string['showseczerocoursewide'] = 'Show section zero at top of all sections';
$string['showseczerocoursewide_desc'] = 'If checked, section zero (the very top section) will be shown on the course landing page and at the top of <b>every course section page</b> (i.e. at the top of every tile\'s contents).  If unchecked (recommended), it will only be shown on the course landing page';
$string['tileicon_help'] = 'Item selected here will override, for this tile only, whatever tile icon has been set at the course level.';
$string['tileicon'] = 'Icon to display on this tile only';
$string['tileselecttip'] = "You don't need to bother using this form to change a tile icon.  An easier way is, in the course, to click the icon you want to change (with editing mode on)";
$string['tip'] = 'Tip';
$string['togglecompletion'] = 'Click to toggle completion status';
$string['tooltipchangeicon'] = "Click to change icon";
$string['usejavascriptnav'] = 'Use javascript navigation from Tiles main page';
$string['usejavascriptnav_desc'] = 'When checked, if user clicks a Tile on the main course overview page, and has javascript, JS will be used to transition to tile contents.  Tile contents will be stored locally in browser according to the other settings below.  If unchecked, JS will not be used and legacy navigation will be used instead.  The other JS settings below will be ignored.';
$string['usejsnavforsinglesection'] = 'Use JS navigation for single section page';
$string['usejsnavforsinglesection_desc'] = 'When checked, any call for a single section page (&section=xx) will be handled using javascript, by launching the course main page, animated to open at the requested section via JS, rather than calling the old style PHP single section page';
$string['usesubtilesseczero'] = 'Use sub tiles in top section';
$string['usesubtilesseczero_help'] = 'If selected, sub tiles will be used in top section of course as well as within all tiles.  This is as the sub tiles take up a lot of room at the very top of the course.  It may be better to leave this unselected, so that any items in the top section are shown in standard list format instead.';
$string['yourprogress'] = 'Your progress';

// Admin Settings page.

$string['addsections'] = 'Add tiles';
$string['assumedatastoreconsent'] = 'Assume consent to browser local storage';
$string['assumedatastoreconsent_desc'] = 'If selected, user will <b>not</b> be shown a dialogue asking for consent to store data in browser local storage';
$string['colourname_descr'] = 'Display name for the colour (e.g. “Blue”) to be used in drop down menus when choosing a colour for a course';
$string['colournamegeneral'] = 'Display name for colour above';
$string['customcss'] = 'Custom CSS';
$string['customcssdesc'] = 'Custom CSS to apply to course content section while course format is used. This will not be validated, so take care to enter valid code. For example: <p>.section { color: red; }</p><p>li.activity.subtile.resource.pdf { background-color: orange !important; }</p>';
$string['filteroutcomesrestore'] = 'The original course used outcomes in the filter bar, which are not yet supported during the restore process.  The filter bar setting has therefore been changed in the restored course.  If you wish to use outcomes to filter tiles in the restored course, please set up the outcomes again.  The original course has not been changed.';
$string['hidden'] = 'Hidden';
$string['hovercolour_descr'] = 'Colour which tiles will display on mouseover';
$string['hovercolour'] = 'Tile hover colour';
$string['restricted'] = 'Restricted';
$string['tilecolourgeneral_descr'] = 'An optional colour to be offered to users as course tile colour on the drop down menu under Course Administration > Edit Settings - leave blank to disable this colour';
$string['tilecolourgeneral'] = 'Colour palette - optional colour';
$string['tileoutcome_help'] = 'If you select an outcome for this tile, you will then under course settings be able to display a set of <em>filter buttons</em>, one for each outcome,  which when pressed filter the displayed tiles according to which outcome they have been assigned';
$string['tileoutcome'] = 'Outcome for this tile';

$string['privacy:metadata:preference:format_tiles_stopjsnav'] = 'Whether the user has disabled animated javascript navigation.';