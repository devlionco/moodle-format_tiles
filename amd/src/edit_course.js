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
 * Main Javascript module for format_tiles for when user *IS* editing.
 * See course.js for if they are not editing.
 * Handles the UI changes when tiles are selected and anything else not
 * covered by the specific modules
 *
 * @module edit_course
 * @package course/format
 * @subpackage tiles
 * @copyright 2019 David Watson {@link http://evolutioncode.uk}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 3.3
 */
/* global document, window */
/* eslint space-before-function-paren: 0 */

define(
    ["jquery", "core/str", "format_tiles/edit_browser_storage"],
    function($, str, browserStorageEdit) {

        var Selector = {
            EDITING_COLLAPSE_SECID: "#collapse",
            EDITING_COLLAPSE_SEC: ".collapse-section",
            EDITING_EXPAND_SEC: ".expand-section",
            EDITING_EXPAND_SECID: "#expand",
            ACTIVITY: "li.activity",
            SECTION_ID: "#section-",
            TILE_TITLE: "li.section.main .tile_bar_text .inplaceeditable a:not(.quickeditlink)",
            TILE_BAR_TEXT: ".tile_bar_text",
            SECTION: "ul.section",
            SECTION_MAIN: "li.section.main",
            DNDUPLOAD_HIDDEN: "dndupload-hidden",
            ACTIVITYACTION: 'a.cm-edit-action',
            ACTIONAREA: '.actions',
            EXPAND_ALL_BTNS: ".expand-collapse-all-btns a",
            EXPAND_COLLAPSE_SEC: ".expand-collapse-sec ",
            EDIT_TITLE_PENCIL: ".quickeditlink",
            ICON_PICKER_BTN: ".tileiconcontainer",
            TILE_BAR: ".tile_bar",
            SECTIONACTIONMENU: '.section_action_menu',
            MENU_ACTION: ".menu-action"
        };

        var Keyboard = {
            ESCAPE: 27,
            TAB: 9,
            RETURN: 13
        };

        var Event = {
            CLICK: "click",
            KEYDOWN: "keydown",
            SCROLL: "scroll"
        };

        var DataAttributes = {
            SECTION: "data-section",
            ORIG_TITLE: "data-original-title"
        };

        var courseId;

        /**
         * Collapse a given section according to the collapse button pressed.
         * @param {number} secNum
         */
        var collapseSectionFromSecNum = function(secNum) {
            var section = $(Selector.SECTION_ID + secNum);
            $(Selector.SECTION_ID + secNum + "-content").animate({opacity: 0}, 300);
            section.find(Selector.ACTIVITY).slideUp(500);
            section.find(".section-modchooser").slideUp(500);
            section.addClass("collapsed").removeClass("expanded");
            section.find(".mod-chooser-outer").fadeOut(500);
            browserStorageEdit.setSectionStatus(secNum, false);
        };

        var togglePinnedSectionIndicator = function(target) {
            var pinBefore,
                pinAfter,
                title,
                action,
                text,
                classBefore,
                classAfter;
          // TODO remove this function
            if (target.hasClass('tounpinsection')) {
              pinBefore = 0;
              pinAfter = 1;
              title = 'This section is pinned';
              action = 'tounpinsection';
              text = 'Unpin section';
              classBefore = 'fa-lock';
              classAfter = 'fa-unlock';
              target.parents('li.section').addClass('pinned');
              target.parents('li.section').find('a.move').first().addClass('hidden');
            } else {
              pinBefore = 1;
              pinAfter = 0;
              title = 'Pin this section to show it in the front';
              action = 'topinsection';
              text = 'Pin Section';
              classBefore = 'fa-unlock';
              classAfter = 'fa-lock';
              target.parents('li.section').removeClass('pinned');
              target.parents('li.section').find('a.move').first().removeClass('hidden');
            }

          // TODO remove setTimeout

            target.attr('href', target.attr('href').replace('pinned=' + pinBefore, 'pinned=' + pinAfter));
            target.attr('title', title);
            target.attr('data-action', action);
            target.find('.icon.fa').removeClass(classBefore).addClass(classAfter);
            target.find('.menu-action-text').text(text);

        };

        var countPinnedSections = function() {
            return $('.pinnedsections .tile').length;
        };

        return {
            // All args down to "filttilestowidth" are copied from course.js.
            init: function(
                courseIdInit,
                useJavascriptNav, // Set by site admin see settings.php.
                maxContentSectionsToStore, // Set by site admin see settings.php.
                isMobile,
                sectionNum,
                storedContentExpirySecs, // Set by site admin see settings.php.
                storedContentDeleteMins, // Set by site admin see settings.php.
                useFilterButtons,
                assumeDataStoreConsent, // Set by site admin see settings.php.
                reopenLastSection, // Set by site admin see settings.php.
                userId,
                fitTilesToWidth,
                pageType,
                allowPhotoTiles,
                useSubTiles,
                areConvertingLabel,
                documentationurl
            ) {
                courseId = courseIdInit;
                // Some args are strings or ints but we prefer bool.  Change to bool now as they are passed on elsewhere.
                assumeDataStoreConsent = assumeDataStoreConsent === "1";
                // This is also called from lib.php, via edit_form_helper, if user is on course/edit.php or editsection.php.
                require(['format_tiles/edit_icon_picker'], function(iconPicker) {
                    iconPicker.init(courseId, pageType, allowPhotoTiles, documentationurl);
                });

                if (useSubTiles) {
                    require(['format_tiles/edit_course_mod'], function (editCourseMod) {
                        editCourseMod.init(
                            courseId,
                            sectionNum,
                            areConvertingLabel
                        );
                    });
                }

                $(document).ready(function() {
                    $(Selector.TILE_BAR_TEXT).on(Event.KEYDOWN, function(e) {
                        if (e.keyCode === Keyboard.RETURN && !$(e.target).hasClass('form-control')) {
                            // Return key has been pressed and *not* while the user was inplace editing the title.
                            window.location = M.cfg.wwwroot + '/course/view.php?id=' + courseId
                                + '&section=' + $(e.currentTarget).parent().attr(DataAttributes.SECTION);
                        }
                    });

                    // If the user preference is for JS off, or site admin has disabled, or user is mobile, no JS nav.
                    if (useJavascriptNav && !isMobile) {
                        var collapsingAllSectionFromURL = (window.location.search).indexOf("expanded=-1") !== -1;
                        var finalSectionInCourse = $(Selector.SECTION_MAIN).last().attr("data-section");
                        browserStorageEdit.init(
                            userId,
                            courseId,
                            maxContentSectionsToStore,
                            storedContentDeleteMins,
                            assumeDataStoreConsent,
                            finalSectionInCourse,
                            collapsingAllSectionFromURL
                        );
                    }

                    if (!isMobile) {
                        // Initialise tooltips shown for example when hover over tile icon "Click to change icon".
                        // But not on mobile as they make clicks harder.
                        var toolTips = $("[data-toggle=tooltip]");
                        if (toolTips.length !== 0) {
                            try {
                                toolTips.tooltip();
                            } catch (err) {
                                require(["core/log"], function(log) {
                                    log.debug(err);
                                });
                            }
                        }
                    }

                    // We don't want to re-implement show/hide sections, so we let core handle it.
                    // Core will re-render all activties when it hides section, and they will be in non subtiles form.
                    // So we just delete them and can add them back when we un-hide or expand.
                    $('body').on('click keypress', Selector.SECTION_MAIN + ' ' +
                        Selector.SECTIONACTIONMENU + '[data-sectionid] ' +
                        'a[data-action]', function(e) {
                            var target = $(e.target).closest(Selector.MENU_ACTION);
                            var sectionMain = target.closest(Selector.SECTION_MAIN);
                            if (target.attr("data-action") === "hide" || target.attr("data-action") === "show") {
                                // If teacher has clicked "show" we still collapse sec to hide the core change - they can re-expand.
                                collapseSectionFromSecNum(sectionMain.attr("data-section"));
                                sectionMain.find(Selector.SECTION).find(Selector.ACTIVITY).slideUp(300).remove();
                            }
                        });

                    $('.section.pinned').each(function() {
                        var newSection = $(this).clone();
                        newSection.addClass('tile').removeClass('section');
                        newSection.find('.left.side').remove();
                        newSection.find('.right.side').remove();
                        newSection.find('.content').remove();
                        newSection.find('.sectionname').removeClass('hidden');
                        newSection.appendTo('.pinnedsections');
                    });

                    $(document).on('click', '#multi_section_tiles .topinsection', function() {
                        if (countPinnedSections() < 4) {
                            var menuItem = $(this);
                            menuItem.removeClass('editing_highlight topinsection').addClass('editing_pinning tounpinsection');
                            var sectionId = menuItem.parents('.section').data('section');
                            togglePinnedSectionIndicator(menuItem);
                            var newSection = $('#section-' + sectionId).clone();
                            newSection.addClass('pinned tile').removeClass('section editinprogress');
                            newSection.find('.lightbox').remove();
                            newSection.find('.left.side').remove();
                            newSection.find('.right.side').remove();
                            newSection.find('.content').remove();
                            newSection.find('.sectionname').removeClass('hidden');
                            newSection.appendTo('.pinnedsections');
                        }
                    });
                    $(document).on('click', '.tounpinsection', function() {
                        var menuItem = $(this);
                        menuItem.removeClass('editing_pinning tounpinsection').addClass('editing_highlight topinsection');
                        var pinnedSection = menuItem.parents('.section');
                        var sectionId = pinnedSection.data('section');
                        pinnedSection.removeClass('pinned');
                        togglePinnedSectionIndicator(menuItem);
                        $('.pinnedsections #section-' + sectionId).slideUp(500, function() {
                            $(this).remove();
                        });
                    });
                });
            }
        };
    }
);
