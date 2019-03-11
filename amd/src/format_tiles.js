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

/*global setTimeout, document, window */
/* eslint space-before-function-paren: 0 */

/**
 * General Javascript module for format_tiles
 * Handles the UI changes when tiles are selected and anything else not
 * covered by the specific modules
 *
 * @module      format_tiles/format_tiles
 * @package     course/format
 * @subpackage  tiles
 * @copyright   2018 David Watson
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(["jquery", "core/templates", "core/ajax", "format_tiles/browser_storage",
        "core/notification", "core/str", "core/config"],
    function ($, Templates, ajax, browserStorage, Notification, str, config) {
        "use strict";

        var body = $("body");
        var page = $("#page");
        var bodyHtml = $("body, html");
        var isMobile;
        var isEditing;
        var loadingIconHtml;
        var stringStore;
        var windowOverlay;
        var headerOverlay;
        var reOrgLocked = false;
        var scrollFuncLock = false;
        var sectionIsOpen = false;
        var HEADER_BAR_HEIGHT = 60; // This varies by theme and version so will be reset once pages loads below.
        var reopenLastVisitedSection = "0";
        var backDropZIndex = 0;
        var Selector = {
            PAGE: "#page",
            TILE: ".tile",
            MOVEABLE_SECTION: ".moveablesection",
            FILTER_BUTTON: ".filterbutton",
            TILE_LOADING_ICON: ".tile-loading-icon",
            TILE_COLLAPSED: ".tile-collapsed",
            TILE_CLICKABLE: ".tile-clickable",
            ACTIVITY: ".activity",
            SPACER: ".spacer",
            SECTION_MOVEABLE: ".moveablesection",
            SECTION_ID: "#section-",
            SECTION_TITLE: ".sectiontitle",
            SECTION_MAIN: ".section.main",
            SECTION_BUTTONS: "#sectionbuttons",
            CLOSE_SEC_BTN: ".closesectionbtn",
            HIDE_SEC0_BTN: "#buttonhidesec0",
            SECTION_ZERO: "#section-0",
            LAUNCH_STANDARD: '[data-action="launch-tiles-standard"]',
            HEADER_BAR: ["header.navbar", "nav.fixed-top.navbar", "#essentialnavbar.navbar"]
            // We try several different selectors for header bar as it varies between theme.
            // (Boost based, clean based, essential etc).
        };

        var ClassNames = {
            SELECTED: "selected",
            HEADER_OVERLAY: "header-overlay",
            OPEN: "open",
            CLOSED: "closed",
            LAUNCH_CM_MODAL: "launch-tiles-cm-modal",
            STATE_VISIBLE: 'state-visible' // This is a Snap theme class and was added to make this format cooperate better with it.
        };

        var Event = {
            CLICK: "click",
            KEYDOWN: "keydown",
            SCROLL: "scroll"
        };

        var CSS = {
            DISPLAY: "display",
            Z_INDEX: "z-index",
            HEIGHT: "height",
            BG_COLOUR: "background-color"
        };
        var Keyboard = {
            ESCAPE: 27,
            TAB: 9,
            RETURN: 13
        };
        /**
         * When JS navigation is being used, when a user un-selects a tile, we have to move the tile's z-index back so that it is
         * no longer on top of the overlay, as well as removing its "selected" class, and hiding the overlay
         * @param {number} sectionToFocus if we want to focus a tile after closing, which one
         */
        var cancelTileSelections = function (sectionToFocus) {
            $(Selector.TILE).removeClass(ClassNames.SELECTED).css(CSS.Z_INDEX, "").css(CSS.BG_COLOUR, "");
            $(".section " + ClassNames.SELECTED).removeClass(ClassNames.SELECTED).css(CSS.Z_INDEX, "");
            windowOverlay.fadeOut(300);
            headerOverlay.fadeOut(0);
            $(Selector.MOVEABLE_SECTION).slideUp().removeClass(ClassNames.STATE_VISIBLE); // Excludes section 0.
            if (sectionToFocus !== undefined && sectionToFocus !== 0) {
                $("#tile-" + sectionToFocus).focus();
            }
            $(Selector.TILE_LOADING_ICON).fadeOut(300, function () {
                $(Selector.TILE_LOADING_ICON).html("");
            });
            sectionIsOpen = false;
        };

        /**
         * Content sections need to be displayed after the row in which the tile to which they relate appears
         * e.g. we have a row of tiles 1-3 and then after that we need to have the content divs whcih contain the
         * related content.  As this depends on device window size, we calcuate this on page load and after window changes
         * e.g. navbar button at side is pressed or browser window is resized
         * @returns {Array} of rows, with the tile they need to be displayed after, and the sections in each row
         */
        var getContentSectionPositions = function () {
            var rows = [];
            var currentSectionId;
            var previousTile;
            $("ul.tiles").children(Selector.TILE).not($(Selector.TILE_COLLAPSED)).each(function (index, tile) {
                currentSectionId = $(tile).attr("data-section");
                var maxVerticalPositionDifference = 100;
                if (currentSectionId) {
                    if (index === 0) {
                        // We are on the first tile, so append a row and add tile ID to it.
                        rows[0] = {"displayAfterTile": "", "sections": [currentSectionId]};
                    } else if (Math.abs($(tile).position().top - $(previousTile).position().top) <= maxVerticalPositionDifference) {
                        // We are on the same row as the previous tile so append its ID to same row.
                        // maxVerticalPositionDifference is because tiles on same row may have different vertical positions.
                        // E.g. if one of the is in a hover state.  If they are within 100 px max they must be on same row.
                        rows[(rows.length) - 1].sections.push(currentSectionId);
                    } else {
                        // Since we are on a new row, make a new row and add tile ID to it.
                        rows.push({"displayAfterTile": "", "sections": [currentSectionId]});
                    }
                    previousTile = tile;
                    // Add displayAfterTile value to the current row.
                    // So that is ends up showing the value of the last tile in this row.
                    rows[rows.length - 1].displayAfterTile = currentSectionId;
                }
            });
            return rows;
        };

        /**
         * Move content sections to appear under the correct tiles
         * so that when a tile is clicked, they expand under it
         * @param {Array} positionData
         * @param {function|null} callback
         * @param {function|null} callback2
         */
        var moveContentSectionsToPlaces = function (positionData, callback, callback2) {
            positionData.forEach(function (row) {
                row.sections.forEach(function (contentSection) {
                    if (row.displayAfterTile === positionData[positionData.length - 1].displayAfterTile) {
                        $(Selector.SECTION_ID + contentSection).detach().insertAfter($("ul.tiles .tile").last());
                    } else {
                        $(Selector.SECTION_ID + contentSection).detach().insertAfter($("#tile-" + row.displayAfterTile));
                    }
                });
            });
            if (typeof callback === "function") {
                callback();
            }
            if (typeof callback2 === "function") {
                callback2();
            }
        };

        /**
         * Set the HTML for a course section to the correct div in the page
         * @param {Object} contentArea the jquery object for the content area
         * @param {String} content the HTML
         * @returns {boolean} success
         */
        var setCourseContentHTML = function (contentArea, content) {
            if (content) {
                contentArea.html(content);
                $(Selector.TILE_LOADING_ICON).fadeOut(300, function () {
                    $(Selector.TILE_LOADING_ICON).html("");
                });

                if (contentArea.attr("id") !== Selector.SECTION_ZERO) {
                    // Trap the tab key navigation in the content bearing section.
                    // Until the user clicks the close button.
                    // When user reaches last item, send them back to first.
                    // And vice versa if going backwards.

                    var activities = contentArea.find(Selector.ACTIVITY).not(Selector.SPACER);
                    contentArea.on(Event.KEYDOWN, function (e) {
                        if (e.keyCode === Keyboard.ESCAPE) {
                            // Close open tile, and return focus to closed tile, for screen reader user.
                            browserStorage.setLastVisitedSection(0);
                            cancelTileSelections(0);
                            $('#tile-' + contentArea.attr('data-section')).focus();
                        }
                    });
                    activities.on(Event.KEYDOWN, function (e) {
                        if (e.keyCode === Keyboard.RETURN) {
                            var toClick = $(e.currentTarget).find("a");
                            if (toClick.hasClass(ClassNames.LAUNCH_CM_MODAL)) {
                                toClick.click();
                            } else if (toClick.attr("href") !== undefined) {
                                window.location = toClick.attr("href");
                            }
                        }
                    });
                    if (!isMobile) {
                        activities.last().on(Event.KEYDOWN, function (e) {
                            if (e.keyCode === Keyboard.TAB && !e.shiftKey
                                    && $(e.relatedTarget).closest(Selector.SECTION_MAIN).attr("id") !== contentArea.attr("id")) {
                                // RelatedTarget is the item we tabbed to.
                                // If we reached here, the item we are on is not a member of the section we were in.
                                // (I.e. we are trying to tab out of the bottom of section) so move tab back to first item instead.
                                setTimeout(function () {
                                    // Allow very short delay so we dont skip forward on the basis of our last key press.
                                    contentArea.find(Selector.SECTION_TITLE).focus();
                                    bodyHtml.animate({scrollTop: contentArea.offset().top - HEADER_BAR_HEIGHT}, "slow");
                                    contentArea.find('#sectionbuttons').css("top", "");
                                }, 200);
                            }
                        });
                        contentArea.find(Selector.SECTION_TITLE).on(Event.KEYDOWN, function (e) {
                            if (e.keyCode === Keyboard.TAB && e.shiftKey
                                    && $(e.relatedTarget).closest(Selector.SECTION_MAIN).attr("id") !== contentArea.attr("id")) {
                                // See explanation previous block.
                                // Here we are trying to tab backwards out of the top of our section.
                                // So take us to last item instead.
                                setTimeout(function () {
                                    activities.last().focus();
                                }, 200);
                            }
                        });
                    }
                }

                if (!isMobile) {
                    // Activate tooltips for completion toggle and any "restricted" items in this content.
                    setTimeout(function () {
                        contentArea.find(".togglecompletion").tooltip(); // Manual forms
                        contentArea.find(".completioncheckbox").tooltip(); // Auto icons
                        contentArea.find(".tag-info").tooltip(); // E.g. "Restricted until 1 January..."
                    }, 500);
                }
                // As we have just loaded new content, ensure that we initialise videoJS media player if required.
                if (contentArea.find(".mediaplugin.mediaplugin_videojs").length !== 0) {
                    require(["media_videojs/loader"], function(videoJS) {
                        videoJS.setUp();
                    });
                }

                return true;
            }
            return false;
        };

        /**
         * Expand a content containing section (e.g. on tile click)
         * @param {object} contentArea
         * @param {number} tileId to expand
         */
        var expandSection = function (contentArea, tileId) {
            var expandAndScroll = function () {
                // Scroll to the top of content bearing section
                // we have to wait until possible reOrg and slide down totally before calling this, else co-ords are wrong.
                var scrollTo = $("#tileText-" + tileId).offset().top - HEADER_BAR_HEIGHT;
                if (scrollTo === $(window).scrollTop) {
                    // Scroll by at least one pixel otherwise z-index on selected tile is not changed.
                    // Until mouse moves.
                    scrollTo += 1;
                }
                contentArea.find(Selector.SECTION_TITLE).focus();
                // If user tries to scroll during animation, stop animation.
                var events = "scroll mousedown wheel DOMMouseScroll mousewheel keyup touchmove";
                body.on(events, function () {
                    body.stop();
                });

                bodyHtml.animate({scrollTop: scrollTo}, "slow", function () {
                    // Animation complete, remove stop handler.
                    bodyHtml.off(events, function () {
                        bodyHtml.stop();
                    });
                });
                sectionIsOpen = true;

                // For users with screen readers, move focus to the first item within the tile.
                contentArea.find('li.activity').first().focus();
            };

            /**
             * Make sure that the section close and edit buttons always appear at the top of the section on scroll
             */
            var holdSectionButtonPosition = function () {
                var buttons = contentArea.find(Selector.SECTION_BUTTONS);
                $(window).on(Event.SCROLL, function () {
                    if (!scrollFuncLock && sectionIsOpen) {
                        scrollFuncLock = true;
                        buttons.fadeOut(300);
                        setTimeout(function () {
                            var windowTop = $(window).scrollTop();
                            var desiredNewPositionInSection = (windowTop - contentArea.offset().top + 50);
                            if (desiredNewPositionInSection > 0
                                    && desiredNewPositionInSection < contentArea.outerHeight() - 100) {
                                desiredNewPositionInSection = (windowTop - contentArea.offset().top + 50);
                                buttons.css("top", desiredNewPositionInSection);
                                if (windowOverlay.css(CSS.DISPLAY) === "none") {
                                    windowOverlay.fadeIn(300);
                                }
                            } else if (desiredNewPositionInSection < 0) {
                                buttons.css("top", 0);
                            }
                            if (windowTop > contentArea.offset().top + contentArea.outerHeight() - 50) {
                                // We have scrolled down and content bottom has gone out of the top of window.
                                if (windowOverlay.css(CSS.DISPLAY) === "block") {
                                    windowOverlay.fadeOut(300);
                                    headerOverlay.fadeOut(300);
                                }
                                buttons.css("top", 0);
                            } else if (contentArea.offset().top > windowTop + $(window).outerHeight()) {
                                // We have scrolled up and  content bottom has gone out of the bottom of window.
                                if (windowOverlay.css(CSS.DISPLAY) === "block") {
                                    windowOverlay.fadeOut(300);
                                    headerOverlay.fadeOut(300);
                                }
                                buttons.css("top", 0);
                            } else if (windowOverlay.css(CSS.DISPLAY) === "none") {
                                windowOverlay.fadeIn(300);
                            }
                            buttons.fadeIn(300, function () {
                                // Release lock on this function.
                                scrollFuncLock = false;
                            });
                        }, 500);
                    }
                });
                if (!scrollFuncLock && !sectionIsOpen && windowOverlay.is(":visible")) {
                    windowOverlay.fadeOut(300);
                }
            };
            contentArea.addClass(ClassNames.STATE_VISIBLE);
            contentArea.slideDown(350, function () {
                // Wait until we have finished sliding down before we work out where the top is for scroll.
                if (Math.abs(contentArea.position().top - $("#tile-" + tileId).position().top) > 300) {
                    // If content area is within 300 px of the related tile, sections need re-arranging.
                    // We need to complete re-org then do expand and scroll as a callback.
                    moveContentSectionsToPlaces(
                        getContentSectionPositions(),
                        expandAndScroll,
                        holdSectionButtonPosition
                    );
                } else {
                    // We don't have to wait for re-org so can now expand and scroll.
                    expandAndScroll();
                    holdSectionButtonPosition();
                }
            });
        };

        var failedLoadSectionNotify = function(sectionNum, failResult, contentArea) {
            if (failResult) {
                // Notify the user and invite them to refresh.  We did get a "failResult" from server,
                // So it looks like we do have a connection and can launch this.
                Notification.confirm(
                    stringStore.sectionerrortitle,
                    stringStore.sectionerrorstring,
                    stringStore.refresh,
                    stringStore.cancel,
                    function () {
                        window.location.reload();
                    },
                    null
                );
                contentArea.html(""); // Clear loading icon.
            } else {
                // It looks like we may not have a connection so we can't launch notifications.
                // We can warn the user like this instead.
                setCourseContentHTML(contentArea, "<p>" + stringStore.noconnectionerror + "</p>");
                expandSection(contentArea, sectionNum);
            }
            throw new Error("Not successful retrieving tile content by AJAX for section " + sectionNum);
        };

        /**
         * For a given section, get the content from the server, add it to the store and maybe UI and maybe show it
         * @param {number} courseId the id for the affected course
         * @param {number} sectionNum the section number we are wanting to populate
         * @return {Promise} promise to resolve when the ajax call returns.
         */
        var getSectionContentFromServer = function (courseId, sectionNum) {
            return ajax.call([{
                methodname: "format_tiles_get_single_section_page_html",
                args: {
                    courseid: courseId,
                    sectionid: sectionNum,
                    setjsusedsession: true
                }
            }])[0];
        };

        /**
         * Temporary function to adjust shade of RGB colour
         * (used for shading tiles to get around transparent background on overlay issue)
         * @param {number} R
         * @param {number} G
         * @param {number} B
         * @param {number} percent
         * @returns {string}
         */
        var shadeRGBColor = function (R, G, B, percent) {
            var t = percent < 0 ? 0 : 255;
            var p = percent < 0 ? percent * -1 : percent;
            var r = Math.round((t - R) * p) + R;
            var g = Math.round((t - G) * p) + G;
            var b = Math.round((t - B) * p) + B;
            return "rgb(" + r + "," + g + "," + b + ")";
        };

        /**
         * Add an opaque modal backdrop like div to obscure all other tiles and bring specified tile and content to front
         * @param {number} secNumOnTop the section number which should be displayed on top of the overlay
         */
        var setOverlay = function (secNumOnTop) {
            windowOverlay.fadeIn(300);
            backDropZIndex = parseInt(windowOverlay.css(CSS.Z_INDEX));
            var tile = $("#tile-" + secNumOnTop);
            tile.css(CSS.Z_INDEX, (backDropZIndex + 1));
            headerOverlay.fadeIn(300);
            $(Selector.SECTION_ID + secNumOnTop).css(CSS.Z_INDEX, (backDropZIndex + 1));
            if (tile.css(CSS.BG_COLOUR) && tile.css(CSS.BG_COLOUR).substr(0, 4) === "rgba") {
                // Tile may have transparent background from theme - needs to be solid otherwise modal shows through.
                var existingColour = tile.css(CSS.BG_COLOUR).replace("rgba(", "").replace(")", "").replace(" ", "").split(",");
                tile.css(CSS.BG_COLOUR, shadeRGBColor(
                    parseInt(existingColour[0]),
                    parseInt(existingColour[1]),
                    parseInt(existingColour[2]),
                    0.95
                ));
            }
        };

        /**
         * Used where the user clicks the window overlay but we want the active click to be behind the
         * overlay e.g. the tile or custom menu item behind it.  So we get the co-ordinates of the click
         * on the overlay and then repeat the click at that spot ignoring the overlay
         * @param {object} e the click event object
         */
        var clickItemBehind = function (e) {
            var clickedItem = $(e.currentTarget);
            if (clickedItem.attr("id") === "window-overlay" || clickedItem.attr("id") === ClassNames.HEADER_OVERLAY) {
                // We need to know what is behind the modal, so hide it for an instant to find out.
                clickedItem.hide();
                var BottomElement = $(document.elementFromPoint(e.clientX, e.clientY));
                clickedItem.show();
                if (clickedItem.attr("id") === "window-overlay") {
                    if (BottomElement.hasClass("filterbutton") || BottomElement.hasClass("list-group-item")) {
                        // Must ba a filter button clicked or a nav drawer item.
                        BottomElement.click();
                    } else {
                        // Must be a tile clicked.
                        var clickedTile = BottomElement.closest(Selector.TILE);
                        if (clickedTile) {
                            clickedTile.click();
                        }
                    }
                } else {
                    // Must be a click on the header bar.
                    cancelTileSelections(0);
                    BottomElement.click();
                }
            }
        };

        /**
         * If the user had section zero collapsed in this course previously, collapse it now
         */
        var setSectionZeroFromUserPref = function () {
            var buttonHideSecZero = $(Selector.HIDE_SEC0_BTN);
            var sectionZero = $(Selector.SECTION_ZERO);
            if (browserStorage.storageEnabledLocal()) {
                // Collapse section zero if user had it collapsed before - relies on local storage so only if enabled.
                if (browserStorage.getSecZeroCollapseStatus() === true) {
                    sectionZero.slideUp(0);
                    buttonHideSecZero.addClass(ClassNames.CLOSED).removeClass(ClassNames.OPEN); // Button image.
                } else {
                    sectionZero.slideDown(300);
                    buttonHideSecZero.addClass(ClassNames.OPEN).removeClass(ClassNames.CLOSED); // Button image.
                }
            } else {
                // Storage not available so we dont know if sec zero was previously collapsed - expand it.
                buttonHideSecZero.addClass(ClassNames.OPEN).removeClass(ClassNames.CLOSED);
                sectionZero.slideDown(300);
            }
        };

        /**
         * Re-organise the sections so that they are in the correct order
         * e.g. content section 3 is on the row below tile 3, so that
         * when tile 3 is clicked, content section 3 opens directly under it
         * @param {number} courseId
         * @param {number} delay how long to delay before (required if doing re-org after resize - allow animation to end
         * @param {number} sectionToLaunch which section shoudl we expand when re-org ends
         */
        var reOrgSections = function (courseId, delay, sectionToLaunch) {
            if (reOrgLocked === true) {
                // Avoid repeated re-organisations - one at a time.
                return;
            }
            reOrgLocked = true;
            cancelTileSelections(0);
            setTimeout(function () {
                moveContentSectionsToPlaces(
                    getContentSectionPositions(),
                    function () {
                        var tileToClick = null;
                        if (!isMobile) {
                            if (typeof sectionToLaunch === "number" && sectionToLaunch !== 0) {
                                // Will only happen if the URL param for section was set on initial page load.
                                tileToClick = sectionToLaunch;
                            } else {
                                // Don't use the URL param - check local storage instead.
                                if (reopenLastVisitedSection === "1" && browserStorage.storageEnabledLocal
                                    && browserStorage.storageUserPreference) {
                                    tileToClick = browserStorage.getLastVisitedSection();
                                    // If user is not on mobile, retrieve last visited section id from browser storage (if present).
                                    // And click it.
                                }
                            }
                            if (tileToClick !== null) {
                                $("#tile-" + tileToClick).click();
                            } else {
                                // Set focus to the first tile (not section zero).
                                $("#tile-1").focus();
                            }
                        }
                        reOrgLocked = false;
                    },
                    null
                );
            }, delay);
            // We may need a long delay before the re-org starts to allow browser resize to complete.
            // (but no delay if we just loaded the page from scratch.
            $("body").removeClass("modal-open");
        };

        return {
            init: function (
                courseId,
                isEditingInit, // Is user is editing, is their section number, else False.
                useJavascriptNav, // Set by site admin see settings.php.
                maxContentSectionsToStore, // Set by site admin see settings.php.
                isMobileInit,
                sectionNum,
                storedContentExpirySecs, // Set by site admin see settings.php.
                storedContentDeleteMins, // Set by site admin see settings.php.
                useFilterButtons,
                assumeDataStoreConsent, // Set by site admin see settings.php.
                reopenLastSectionInit, // Set by site admin see settings.php.
                userId
            ) {
                reopenLastVisitedSection = reopenLastSectionInit;
                isMobile = isMobileInit;
                isEditing = isEditingInit;
                 // We want to initialise the browser storage JS module for storing user settings.
                 // And (depending on maxContentSectionsToStore) possibly also content in browser.
                browserStorage.init(
                    courseId,
                    maxContentSectionsToStore,
                    isEditing,
                    sectionNum,
                    storedContentDeleteMins,
                    assumeDataStoreConsent,
                    userId
                );

                $(document).ready(function () {
                    var pageContent = $("#page-content");
                    var windowWidth = $(window).outerWidth();

                    if (useJavascriptNav && !isEditing) {
                        // User is not editing but is usingJS nav to view.

                        // When a tile is clicked we add an overlay to grey out the rest of the tiles on the page, so prepare it.
                        windowOverlay = $("<div></div>").addClass("modal-backdrop fade in").hide()
                            .attr("id", "window-overlay").appendTo(page);

                        // If user clicks the window overlay behind the visible tile content, deselect tile.
                        // (They want to remove the overlay).
                        windowOverlay.click(function (e) {
                            cancelTileSelections(0);
                            clickItemBehind(e);
                        });

                         // On a tile click, decide what to do an do it.
                         // (Collapse if already expanded, or expand it and fill with content).
                        pageContent.on(Event.CLICK, Selector.TILE_CLICKABLE, function (e) {
                            // Prevent the link being followed to reload the PHP page as we are using JS instead.
                            if (!useJavascriptNav) {
                                return;
                            }
                            e.preventDefault();
                            $(window).off(Event.SCROLL); // Stop listening for scroll events on ay previously opened tiles.
                            // if other tiles have loading icons, fade them out (on the tile not the content sec).
                            $(Selector.TILE_LOADING_ICON).fadeOut(300, function () {
                                $(Selector.TILE_LOADING_ICON).html();
                            });
                            var thisTile = $(e.currentTarget).closest(Selector.TILE);
                            var dataSection = parseInt(thisTile.attr("data-section"));
                            var relatedContentArea = $(Selector.SECTION_ID + dataSection);
                            if (thisTile.hasClass(ClassNames.SELECTED)) {
                                // This tile is already expanded so collapse it.
                                cancelTileSelections(dataSection);
                                browserStorage.setLastVisitedSection(0);
                            } else {
                                setOverlay(dataSection);
                                $(Selector.TILE).removeClass(ClassNames.SELECTED); // Remove selected from all tiles.
                                thisTile.addClass(ClassNames.SELECTED);
                                // Then close all open secs.
                                // Timed to finish in 200 so that it completes well before the opening next.
                                $(Selector.MOVEABLE_SECTION).slideUp(200);
                                // Log the fact we viewed the section.
                                ajax.call([{
                                    methodname: "format_tiles_log_tile_click", args: {
                                        courseid: courseId,
                                        sectionid: dataSection
                                    }
                                }])[0].fail(Notification.exception);
                                // Get the content - use locally stored content first if available.
                                if (relatedContentArea.find(".content").length > 0) {
                                    // There is already some content on the screen so display immediately.
                                    expandSection(relatedContentArea, dataSection);
                                    // Then refresh the content in storage only but do not change on screen.
                                    getSectionContentFromServer(courseId, dataSection).done(function (response) {
                                        if (browserStorage.storageEnabledSession()) {
                                            browserStorage.storeCourseContent(courseId, dataSection, $(response.html).html());
                                        }
                                    });
                                } else {
                                    relatedContentArea.html(loadingIconHtml);
                                    if (browserStorage.storageEnabledLocal()) {
                                        var contentAge = browserStorage.getStoredContentAge(courseId, dataSection);
                                        if (contentAge) {
                                            // We have some stored content so display it even if it's expired.
                                            setCourseContentHTML(
                                                relatedContentArea,
                                                browserStorage.getCourseContent(courseId, dataSection)
                                            );
                                            expandSection(relatedContentArea, dataSection);
                                        }
                                        if (!contentAge || contentAge > storedContentExpirySecs) {
                                            // Content in local storage may not exist or have expired.
                                            // If so, get it again from server and display new content on receipt.
                                            var loadingIcon = $("#loading-icon-" + sectionNum);
                                            if (loadingIcon !== undefined) {
                                                loadingIcon.html(loadingIconHtml).fadeIn(200);
                                            } else {
                                                loadingIcon = $("<div>").html(loadingIconHtml);
                                                relatedContentArea.html(loadingIcon);
                                            }
                                            getSectionContentFromServer(courseId, dataSection).done(function (response) {
                                                var contentToDisplay = $(response.html).html();
                                                setCourseContentHTML(relatedContentArea, contentToDisplay);
                                                expandSection(relatedContentArea, dataSection);
                                                if (browserStorage.storageEnabledSession()) {
                                                    browserStorage.storeCourseContent(courseId, dataSection, contentToDisplay);
                                                }
                                            }).fail(function (failResult) {
                                                failedLoadSectionNotify(dataSection, failResult, relatedContentArea);
                                                cancelTileSelections(dataSection);
                                            });
                                        }
                                    } else {
                                        // Not using storage so get from server.
                                        getSectionContentFromServer(courseId, dataSection).done(function (response) {
                                            setCourseContentHTML(relatedContentArea, $(response.html).html());
                                            expandSection(relatedContentArea, dataSection);
                                        }).fail(function (failResult) {
                                            failedLoadSectionNotify(dataSection, failResult, relatedContentArea);
                                            cancelTileSelections(dataSection);
                                        });
                                    }
                                }
                                browserStorage.setLastVisitedSection(dataSection);
                            }
                            // Silently set the *next* section's content to if it exists and if user is not on mobile.
                            // short delay as more important to get current section content first (above).
                            var nextSecIfExists = $(Selector.SECTION_ID + (dataSection + 1));
                            if (!isMobile && nextSecIfExists.length && dataSection > 0) {
                                setTimeout(function () {
                                    var storedContentAge = browserStorage.getStoredContentAge(courseId, dataSection + 1);
                                    if (storedContentAge) {
                                        // We have some stored content so set this to screen (even if expired).
                                        setCourseContentHTML(
                                            nextSecIfExists,
                                            browserStorage.getCourseContent(courseId, dataSection + 1)
                                        );
                                    }
                                    if (!storedContentAge || storedContentAge > storedContentExpirySecs) {
                                        // Stored content is too old or does not exist so get from server.
                                        getSectionContentFromServer(courseId, dataSection + 1).done(function(response) {
                                            setCourseContentHTML(
                                                nextSecIfExists,
                                                $(response.html).html()
                                            );
                                        });
                                    }
                                }, 2000);
                            }
                        });

                        // When window is re-sized, content sections under the tiles may be in wrong place.
                        // So remove them and re-initialise them.
                        // Collapse the selected section before doing this.
                        // Otherwise the re-organisation won.t work as the tiles' flow will be out when they are analysed.

                        $(window).on("resize", function () {
                            // On iOS resize events are triggered often on scroll because the address bar hides itself.
                            // Avoid this.
                            if (windowWidth !== $(window).outerWidth()) {
                                // Real so set new value for later comparison.
                                windowWidth = $(window).outerWidth();
                                reOrgSections(courseId, 1000, 0);
                            }
                        });

                        // If theme uses docked blocks (e.g. more) then re-organise if they move.
                        $(".block-hider-hide").click(function () {
                            reOrgSections(courseId, 1000, 0);
                        });

                        $(".block-hider-show").click(function () {
                            reOrgSections(courseId, 1000, 0);
                        });

                        // If nav drawer is opened or closed, this rezises the window so need to re-initialise content divs.
                        $(".navbar-nav .btn").click(function () {
                            reOrgSections(courseId, 1000, 0);
                        });

                        // We want the main menu at the top to be on top of the tiles.
                        // Even the ones which we have brought to the front on top of the window overlay.
                        // But we also want it to still be greyed out.  So add an opaque overlay.
                        // We can show this when the main overlay is active.
                        // When a user clicks this overlay, they want to close the overlay and click menu item behind.
                        // So provide for that here.
                        // Z-INDICES: active tile will be at overlayZindex + 1.
                        // Header bar will be at overlayZindex + 2 so that it is higher than tiles.
                        // Header bar overlay will be at overlayZindex + 3 so that it is highest of all.

                        var overlayZindex = parseInt(windowOverlay.css(CSS.Z_INDEX));
                        var headerBar = $(Selector.HEADER_BAR.find(function(selector) {
                            return $(selector).length > 0;
                        }));
                        if (headerBar !== undefined) {
                            headerBar.css(CSS.Z_INDEX, overlayZindex + 2);
                        }

                        // The header bar has a separate mini overlay of its own - find and hide this.
                        // If it is clicked, cancel tile selections and click the item behind where clicked.
                        // Do not include for Moodle 3.5 or higher as not needed.

                        if (headerBar.height() !== undefined) {
                            HEADER_BAR_HEIGHT = headerBar.height();
                            headerOverlay = $("<div></div>")
                                .addClass(ClassNames.HEADER_OVERLAY).attr("id", ClassNames.HEADER_OVERLAY)
                                .css(CSS.DISPLAY, "none");
                            headerOverlay.insertAfter(Selector.PAGE)
                                .css(CSS.Z_INDEX, (overlayZindex) + 3).css(CSS.HEIGHT, HEADER_BAR_HEIGHT)
                                .click(function (e) {
                                    cancelTileSelections(0);
                                    clickItemBehind(e);
                                });
                        } else {
                            headerOverlay = $("<div></div>").insertAfter(Selector.PAGE).fadeOut();
                        }

                        // When user clicks to close a section using cross at top right in section.
                        pageContent.on(Event.CLICK, Selector.CLOSE_SEC_BTN, function (e) {
                            cancelTileSelections($(e.currentTarget).attr("data-section"));
                        });

                        // When we first load the page we want to move the tile contents divs.
                        // Put them in the correct rows according to which row of tiles they relate to.
                        reOrgSections(courseId, 0, sectionNum);

                        // If user clicks a sub tile body below the link, treat it as a click on the link itself.
                        pageContent.on(Event.CLICK, Selector.LAUNCH_STANDARD, function(e) {
                            var clickedLk = $(e.currentTarget);
                            if (clickedLk.attr("href") !== undefined) {
                                window.location = clickedLk.attr("href");
                            } else if (clickedLk.find('a').attr("href") !== undefined) {
                                window.location = clickedLk.find('a').attr("href");
                            }
                        });
                    }

                    // When the user presses the button to collapse or expand Section zero (section at the top of the course).
                    pageContent.on(Event.CLICK, Selector.HIDE_SEC0_BTN, function (e) {
                        var sectionZero = $(Selector.SECTION_ZERO);
                        if (sectionZero.css(CSS.DISPLAY) === "none") {
                            // Sec zero is collapsed so expand it on user click.
                            sectionZero.slideDown(250);
                            $(e.currentTarget).addClass(ClassNames.OPEN).removeClass(ClassNames.CLOSED);
                            browserStorage.setSecZeroCollapseStatus("collapsed");
                        } else {
                            // Sec zero is expanded so collapse it on user click.
                            sectionZero.slideUp(250);
                            $(e.currentTarget).addClass(ClassNames.CLOSED).removeClass(ClassNames.OPEN);
                            browserStorage.setSecZeroCollapseStatus("expanded");
                        }
                    });

                    setSectionZeroFromUserPref();

                    // Render the loading icon and append it to body so that we can move it to a tile as needed later.
                    Templates.render("format_tiles/loading", {}).done(function (html) {
                        loadingIconHtml = html;
                    });

                    if (!isMobile) {
                        // Initialise tooltips shown for example when hover over tile icon "Click to change icon".
                        // But not on mobile as they make clicks harder.
                        $("[data-toggle=tooltip]").tooltip();
                    }

                    // Most filter button related JS is in filter_buttons.js module which is required below.
                    if (!isEditing && useFilterButtons) {
                        require(["format_tiles/filter_buttons"], function (filterButtons) {
                            filterButtons.init(courseId, browserStorage.storageEnabledLocal);
                        });
                        if (useJavascriptNav) {
                            pageContent.on(Event.CLICK, Selector.FILTER_BUTTON, function () {
                                cancelTileSelections(0);
                                setTimeout(function () {
                                    moveContentSectionsToPlaces(getContentSectionPositions(), null, null);
                                }, 1500);
                            });
                        }

                    }

                    // If theme is displaying the .tiles_coursenav class items, show items with this class.
                    // They will be hidden otherwise.
                    // They are hidden when initially rendered from PHP as we only want them shown if browser supports JS.
                    // See lib.php extend_course_navigation.
                    $(".tiles_coursenav").removeClass("hidden");

                     // Get these strings now, in case we need them.
                    // E.g. after we lose connection and cannot display content on a user tile click.
                    str.get_strings([
                        {key: "sectionerrortitle", component: "format_tiles"},
                        {key: "sectionerrorstring", component: "format_tiles"},
                        {key: "refresh"},
                        {key: "cancel"},
                        {key: "noconnectionerror", component: "format_tiles"},
                        {key: "show"},
                        {key: "hide"},
                        {key: "other", component: "format_tiles"}
                    ]).done(function (s) {
                        stringStore = {
                            "sectionerrortitle": s[0],
                            "sectionerrorstring": s[1],
                            "refresh": s[2],
                            "cancel": s[3],
                            "noconnectionerror": s[4],
                            "show": s[5],
                            "hide": s[6],
                            "other": s[7]
                        };
                    });

                    // If return is pressed while an item is in focus, click the item.
                    // This is to make the tiles keyboard navigable for users using screen readers.
                    // User tabbing between tiles is handled by tabindex in the HTML.
                    // Once the tile is clicked, the expand tile function will move focus to the first content item.
                    // On escape key, we clear all selections and collapse tiles (handled above not here).
                    if (!isMobile) {
                        $(Selector.TILE).on(Event.KEYDOWN, function (e) {
                            if (e.keyCode === Keyboard.RETURN) { // Return key pressed.
                                $(e.currentTarget).click();
                            }
                        });
                        if (isEditing) {
                            $(".tile_bar_text").on(Event.KEYDOWN, function (e) {
                                if (e.keyCode === Keyboard.RETURN && !$(e.target).hasClass('form-control')) {
                                    // Return key has been pressed and *not* while the user was inplace editing the title.
                                    window.location = config.wwwroot + '/course/view.php?id=' + courseId
                                        + '&section=' + $(e.currentTarget).parent().attr("data-section");
                                }
                            });
                        }

                        // Move focus to the first tile in the course (not sec zero contents if present).
                        $("ul.tiles .tile").first().focus();
                    }

                    // If Adaptable theme is being used, and Glossary auto link filter is on, we need this.
                    // Otherwise when the auto link is clicked, the resulting dialogue is under the main overlay.
                    // Don't need this when Boost or Clean themes are used as they handle it themselves.
                    $(document).on("filter-content-updated", function (event, msg) {
                        if (msg.length > 0) {
                            var elem = $(msg[0]);
                            if (elem.hasClass("moodle-dialogue") && elem.css("z-index") < backDropZIndex) {
                                    elem.css("z-index", backDropZIndex + 1);
                            }
                        }
                    });
                });
            }
        };
    }
);