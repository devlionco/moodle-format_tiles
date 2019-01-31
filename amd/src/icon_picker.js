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
 * Javascript Module to handle the icon picker dialogue for format_tiles
 * which the editing user uses to select an icon for a tile or the default icon
 * for all tiles in the course
 *
 * @module      icon_picker
 * @package     course/format
 * @subpackage  tiles
 * @copyright   2018 David Watson
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since       Moodle 3.3
 */

define(["jquery", "core/templates", "core/ajax", "core/str", "core/notification"],
    function ($, Templates, ajax, str, Notification) {
        "use strict";

        var modalStored;
        var stringStore = {pickAnIcon: ''};
        var iconSet = [];

        /**
         * Set the selected icon in the database via AJAX to the web service.
         * When successful, then change the icon being displayed to the current editing user.
         * If we are on an edit form, also select the selected icon in the hidden HTML selecftBox.
         * The select box
         * @param {number} sectionId
         * @param {number} sectionNum
         * @param {string} icon
         * @param {string} displayname
         * @param {string} pageType
         * @param {number} courseId
         */
        var setIcon = function (sectionId, sectionNum, icon, displayname, pageType, courseId) {
            var ajaxIconPickArgs = {
                icon: icon,
                courseid: courseId,
                sectionid: sectionId
                // Sectionid will be zero if relates to whole course not just one sec.
            };
            var setIconDbPromises = ajax.call([{
                methodname: "format_tiles_set_icon",
                args: ajaxIconPickArgs
            }]);
            setIconDbPromises[0].done(function (response) {
                if (response === true) {
                    if (pageType === "course-view-tiles") {
                        // We are changing an icon for a specific section from within the course.
                        // We are doing this by clicking an existing icon.
                        var iconToChange = $("#tileicon_" + sectionNum).find(".icon");
                        iconToChange.animate({opacity: 0}, 500, function () {
                            Templates.render("format_tiles/tileicon", {
                                tileicon: icon,
                                tileid: sectionNum,
                                secid: sectionId
                            }).done(function (html) {
                                iconToChange.fadeOut(0).replaceWith($(html).find(".icon"))
                                    .animate({opacity: 1}, 500);
                            });
                        });
                    } else if (pageType === "course-edit" || pageType === "course-editsection") {
                        // We are changing the icon using a drop down menu not the icon picker modal.
                        // Either for the whole course or for one section.
                        // Select new icon in drop down.
                        var selectBox = $("#id_defaulttileicon"); // Valid if page type is course-edit.
                        if (pageType === "course-editsection") {
                            selectBox = $("#id_tileicon");
                        }
                        selectBox.val(icon);
                        // Then change the image shown next to it.
                        Templates.renderPix("tileicon/" + icon, "format_tiles", displayname)
                            .done(function (newIcon) {
                                $("#selectedicon").html(newIcon);
                                if (pageType === "course-editsection") {
                                    str.get_strings([
                                        {key: "tip", component: "format_tiles"},
                                        {key: "tileselecttip", component: "format_tiles"}
                                    ]).done(function (strings) {
                                        Notification.alert(
                                            strings[0],
                                            strings[1]
                                        );
                                    });
                                }
                            });
                    }
                }
            }).fail(Notification.exception);
        };

        /**
         * When user clicks to launch an icon picker modal, set which section it relates to
         * so that we know which section the icon clicked is for.  This is so that only one modal needs
         * to be rendered (with all the icons in it) - we can use it to assign icons to any section
         * @param {string} pageType
         * @param {number} courseId
         *
         */
        var watchLaunchButtons = function (pageType, courseId) {
            // Launch icon picker can be a tile icon (if editing course) or a button (if on a form).
            $(".launchiconpicker").click(function (e) {
                var clickedIcon = $(e.currentTarget);
                if (typeof modalStored !== "object") {
                    // We only have one modal per page which we recycle.  We dont have it yet so create it.
                    Templates.render("format_tiles/icon_picker_modal_body", {
                        icon_picker_icons: iconSet
                    }).done(function (iconsHTML) {
                        require(["core/modal_factory"], function (modalFact) {
                            modalFact.create({
                                type: modalFact.types.DEFAULT,
                                title: stringStore.pickAnIcon,
                                body: iconsHTML
                            }).done(function (modal) {
                                modalStored = modal;
                                modal.setLarge();
                                modal.show();
                                var modalRoot = $(modal.root);
                                modalRoot.attr("id", "icon_picker_modal");
                                modalRoot.attr("data-sectionid", clickedIcon.attr("data-sectionid"));
                                modalRoot.addClass("icon_picker_modal");
                                modalRoot.on("click", ".pickericon", function (e) {
                                    var newIcon = $(e.currentTarget);
                                    setIcon(
                                        clickedIcon.attr("data-sectionid"),
                                        clickedIcon.attr("data-section"),
                                        newIcon.attr("data-icon"),
                                        newIcon.attr("title"),
                                        pageType,
                                        courseId
                                    );
                                    modal.hide();
                                });

                                // Icon search box handling.
                                modalRoot.on("input", "input.iconsearch", function (e) {
                                    var searchText = e.currentTarget.value.toLowerCase();
                                    modalRoot.find(".pickericon").show();
                                    if (searchText.length >= 3) {
                                        modalRoot.find(".pickericon").filter(function (index, icon) {
                                            // Show all icons then hide icons which do not match the search term.
                                            return $(icon).attr('data-original-title').toLowerCase().indexOf(searchText) < 0;
                                        }).hide();
                                    }
                                });
                                $(".pickericon").tooltip();
                            });
                        });
                    });

                } else {
                    // We already have the modal so recycle it instead of re-rendering.
                    modalStored.root.attr("data-sectionid", clickedIcon.attr("data-sectionid"));
                    modalStored.root.off("click");
                    modalStored.root.on("click", ".pickericon", function (e) {
                        var newIcon = $(e.currentTarget);
                        setIcon(
                            clickedIcon.attr("data-sectionid"),
                            clickedIcon.attr("data-section"),
                            newIcon.attr("data-icon"),
                            newIcon.attr("title"),
                            pageType,
                            courseId
                        );
                        modalStored.hide();
                    });
                    modalStored.show();
                }

            });
        };

        return {
            init: function (courseId, pageType) {
                $(document).ready(function () {
                    str.get_string("pickicon", "format_tiles").done(function (pickAnIcon) {
                        stringStore.pickAnIcon = pickAnIcon;
                    });
                    // Get the core icon set now so that we don't have to wait later.
                    ajax.call([{
                        methodname: "format_tiles_get_icon_set",
                        args: {courseid: courseId}
                    }])[0].done(function (response) {
                        var icons = JSON.parse(response.icons);
                        Object.keys(icons).forEach(function(icon) {
                            iconSet.push({filename: icon, displayname: icons[icon]});
                        });
                    });
                    watchLaunchButtons(pageType, courseId);
                });
            }
        };
    }
);