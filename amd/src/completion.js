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
 * Load the format_tiles JavaScript for the course edit settings page /course/edit.php?id=xxx
 *
 * @module      format_tiles
 * @package     course/format
 * @subpackage  tiles
 * @copyright   2018 David Watson
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(["jquery", "core/templates", "core/config", "format_tiles/completion"], function ($, Templates, config) {
    "use strict";

    var strings = {};
    var dataKeys = {
        cmid: "data-cmid",
        numberComplete: "data-numcomplete",
        numberOutOf: "data-numoutof",
        section: "data-section"
    };
    var Selector = {
        launchModuleModal: '[data-action="launch-tiles-module-modal"]',
        pageContent: "#page-content",
        resourceModule: '.activity.resource'
    };

    // This will be populated on init with the items which we treat as labels.
    // I.e. which we ignore for completion tracking.
    var noCompletionTrackingMods = [];

    /**
     * When toggleCompletionTiles() makes an AJAX call it needs to send some data
     * and this helps assemble the data
     * @param {number} tileId which tile is this for
     * @param {number} numComplete how many items has the user completed
     * @param {number} outOf how many items are there to complete
     * @param {boolean} asPercent should we show this as a percentage
     * @returns {{}}
     */
    var progressTemplateData = function (tileId, numComplete, outOf, asPercent) {
        var data = {
            tileid: tileId,
            numComplete: numComplete,
            numOutOf: outOf,
            showAsPercent: asPercent,
            percent: Math.round(numComplete / outOf * 100),
            percentCircumf: 106.8,
            percentOffset: Math.round(((outOf - numComplete) / outOf) * 106.8),
            isComplete: false,
            isSingleDigit: false
        };
        if (tileId === 0) {
            data.isOverall = 1;
        } else {
            data.isOverall = 0;
        }
        if (numComplete >= outOf) {
            data.isComplete = true;
        }
        if (data.percent < 10) {
            data.isSingleDigit = true;
        }
        return data;
    };

    /**
     * When a progress change happens, e.g. an item is marked as complete or not, this fires.
     * It changes the current tile's progress up or down by 1 according to the progressChange arg.
     * It then does the same for the course's overall progress indicator.
     * @param {int} sectionNum the number of this tile/section.
     * @param {object} tileProgressIndicator the indicator for this tile
     * @param {int} progressChange the amount we are changing e.g. +1 or -1
     */
    var changeProgressIndicators = function(sectionNum, tileProgressIndicator, progressChange) {
        // TODO create a web service to get current value from server so we know it's correct.
        // This can also handle updating the competion status instead of core below.
        if (tileProgressIndicator.attr(dataKeys.numberComplete) == 0 && progressChange < 0) {
            // If we are already at zero, do not reduce.  May happen rarely if user presses repeatedly.
            // Will not cause a long term issue as will be resolved when user refreshes page.
            return;
        }
        // Get the tile's new progress value.
        var newTileProgressValue = Math.min(
            parseInt(tileProgressIndicator.attr(dataKeys.numberComplete)) + progressChange,
            tileProgressIndicator.attr(dataKeys.numberOutOf)
        );
        // Get the new overall progress value.
        var overallProgressIndicator = $("#tileprogress-0");
        var newOverallProgressValue = Math.min(
            parseInt(overallProgressIndicator.attr(dataKeys.numberComplete)) + progressChange,
            overallProgressIndicator.attr(dataKeys.numberOutOf)
        );

        // Render and replace the progress indicator for *this tile*.
        Templates.render("format_tiles/progress", progressTemplateData(
            sectionNum,
            newTileProgressValue,
            tileProgressIndicator.attr(dataKeys.numberOutOf),
            tileProgressIndicator.hasClass("percent")
        )).done(function (html) {
            // Need to repeat jquery selector as it is being replaced (replacwith).
            tileProgressIndicator.replaceWith(html);
            $("#tileprogress-" + sectionNum).tooltip();
        });

        // Render and replace the *overall* progress indicator for the *whole course*.
        Templates.render("format_tiles/progress", progressTemplateData(
            0,
            newOverallProgressValue,
            overallProgressIndicator.attr(dataKeys.numberOutOf),
            true
        )).done(function (html) {
            $("#tileprogress-0").replaceWith(html).fadeOut(0).animate({opacity: 1}, 500);
        });
    };

    /**
     * When a user clicks a completion tracking checkbox in this format, pass the click through to core
     * This is partly based on the core functionality in completion.js but is included here as otherwise clicks on
     * check boxes added dynamically after page load are not detected
     * @param {object} form the form and check box
     */
    var toggleCompletionTiles = function (form) {
        // Get the existing completion state for this completion form.
        // For PDFs there will be two forms - one in the section and one within the modal - grab both with class.
        var cmid = form.attr(dataKeys.cmid);
        var completionState = $("#completionstate_" + cmid);
        var data = {
            id: cmid,
            completionstate: parseInt(completionState.attr("value")),
            fromajax: 1,
            sesskey: config.sesskey
        };
        form.tooltip('hide');
        var url = config.wwwroot + "/course/togglecompletion.php";
        $.post(url, data, function (returnData, status) {
            if (status === "success" && returnData === "OK") {
                var progressChange;
                var completionImage = $(".completion_img_" + cmid);
                if (completionState.attr("value") === "1") {
                    // We have checked a progress box.
                    // Change check box(es) to ticked,
                    // And set the value(s) to zero so that if re-clicked, goes back to unchecked.
                    $("#completion_dynamic_change").attr("value", 0);
                    completionState.attr("value", 0);
                    progressChange = +1;
                    completionImage.addClass("completion-state-1").removeClass("completion-state-0");
                    $(".complete-y-" + cmid).fadeIn(200).fadeOut(1000);
                } else {
                    // We have un-checked a progress box.
                    $("#completion_dynamic_change").attr("value", 1);
                    completionState.attr("value", 1);
                    progressChange = -1;
                    $(".complete-n-" + cmid).fadeIn(200).fadeOut(1000);
                    completionImage.addClass("completion-state-0").removeClass("completion-state-1");
                }
                if (!completionState.closest("li.activity").is(
                    // If the activity is not one of the mods we ignore for completion tracking e.g. label.
                    noCompletionTrackingMods.map(function(cls) {
                        return "." + cls;
                    }).join(','))
                ) {
                    // We do not do this for labels, as they are not included in completion tracking.
                    changeProgressIndicators(
                        form.attr(dataKeys.section),
                        $("#tileprogress-" + form.attr(dataKeys.section)),
                        progressChange
                    );
                }
            }
        })
            .fail(function () {
                throw new Error("Failed to register completion change with server");
            });
    };

    /**
     * When automatic completion tracking is being used, on modal launch we need to:
     * - change the completion icon to complete.
     * - recalculate the % complete for this tile and overall.
     * We do not need to notify the server that the item is complete.
     * This is because that is already covered when course_mod_modal calls log_mod_view().
     * @param {object} e the event when the launch modal click happened.
     */
    var markAsAutoComplete = function(e) {
        var completionIcon = $(e.currentTarget).closest("li.activity").find('.completion-icon');
        if (completionIcon.attr('data-ismanual') === "0" && completionIcon.attr('data-completionstate') === "0") {
            completionIcon.addClass("completion-state-1").removeClass("completion-state-0");
            completionIcon.attr('data-completionstate', 1);
            completionIcon.attr('data-original-title', strings.completeauto);
            completionIcon.tooltip();

            var sectionNum = completionIcon.closest('li.section.main').attr('data-section');
            changeProgressIndicators(sectionNum, $("#tileprogress-" + sectionNum), 1);
        }
    };

    return {
        init: function (strCompleteAuto, labelLikeCourseMods) {
            $(document).ready(function () {
                noCompletionTrackingMods = JSON.parse(labelLikeCourseMods);
                strings.completeauto = strCompleteAuto;
                // Trigger toggle completion event if check box is clicked.
                // Included like this so that later dynamically added boxes are covered.
                $("body").on("click", ".togglecompletion", function (e) {
                    // Send the toggle to the database and change the displayed icon.
                    e.preventDefault();
                    toggleCompletionTiles($(e.currentTarget));
                });

                $(Selector.pageContent).on("click", Selector.resourceModule, function (e) {
                    // We do this for all resources e.g. Word docs, PDFs as soon as the user clicks them.
                    markAsAutoComplete(e);
                });

                $(Selector.pageContent).on("click", Selector.launchModuleModal, function (e) {
                    markAsAutoComplete(e);
                });
            });
        }
    };
});