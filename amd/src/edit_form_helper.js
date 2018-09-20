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

/* eslint space-before-function-paren: 0 */

/**
 * Javascript Module to handle changes which are made to the course > edit settings
 * form as the user changes various options
 * e.g. if user deselects one item, this deselects another linked one for them
 * if the user picks an invalid option it will be detected by format_tiles::edit_form_validation (lib.php)
 * but this is to help them avoid triggering that if they have JS enabled
 *
 * @module      edit_form_helper
 * @package     course/format
 * @subpackage  tiles
 * @copyright   2018 David Watson
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since       Moodle 3.3
 */

define(["jquery", "core/notification", "core/str", "core/templates"], function ($, Notification, str, Templates) {
    "use strict";
    return {
        init: function () {
            $(document).ready(function () {
                $("select#id_courseusesubtiles").change(function (e) {
                    if (e.currentTarget.value !== "0") {
                        // We are changing to use sub tiles so, for convenience, uncheck the
                        //  "Emphasise headings with coloured tab" box - user can change it back if they want
                        $("input#id_courseusebarforheadings").prop("checked", false);
                    }
                });
                $("select#id_courseshowtileprogress").change(function (e) {
                    if (e.currentTarget.value !== "0") {
                        var enableCompBox = $("select#id_enablecompletion");
                        if (enableCompBox.val() === "0") {
                            // We are changing to show progress on tiles, so for convenience, if
                            // completion tracking if off at course level (under "completion tracking > enable)
                            // switch it on and tell the user.  User can change it back if they want
                            enableCompBox.val("1");
                            str.get_strings([
                                {key: "completion", component: "completion"},
                                {key: "completionswitchhelp", component: "format_tiles"}
                            ]).done(function (s) {
                                Notification.alert(
                                    s[0],
                                    s[1]
                                );
                            });
                        }
                    }
                });
                $("select#id_enablecompletion").change(function (e) {
                    if (e.currentTarget.value === "0") {
                        // We are changing switch completion tracking off at course level too (under "completion tracking > enable)
                        // it follows that we must be hiding progress on tiles too
                        $("select#id_courseshowtileprogress").val("0");
                    }
                });

                // Create clickable colour swatch for each colour in the select drop down to help user choose
                var colourSelectMenu = $("select#id_basecolour");
                Templates.render("format_tiles/colour_picker", {
                    colours: colourSelectMenu.find("option").map(
                        function (index, option) {
                            var colour = $(option).attr("value");
                            return {
                                colour: colour,
                                selected: colour === colourSelectMenu.val(),
                                id: colour.replace("#", "")
                            };
                        }
                    ).toArray()
                }).done(function (html) {
                    // Add the newly created colour picker next to the standard select menu
                    $(html).insertAfter(colourSelectMenu);
                    // Watch for clicks on each circle and set select menu to correct colour on click

                    var circles = $(".colourpickercircle");

                    circles.click(function (e) {
                        var clicked = $(e.currentTarget);
                        circles.removeClass("selected");
                        clicked.addClass("selected");
                        colourSelectMenu.val(clicked.attr("data-colour"));
                    });

                    colourSelectMenu.change(function () {
                        circles.removeClass("selected");
                        $("#colourpick_" + colourSelectMenu.val().replace("#", "")).addClass("selected");
                    });
                });
            });
        }
    };
});