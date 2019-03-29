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
/*global document, window */
/* eslint space-before-function-paren: 0 */

/**
 * Javascript Module to handle rendering of course modules (e.g. resource/PDF, resource/html, page) in modal windows
 *
 * When the user clicks a PDF course module subtile or old style resource
 * if we are using modals for it (e.g. PDF) , create, populate, launch and size the modal
 *
 * @module      course_mod_modal
 * @package     course/format
 * @subpackage  tiles
 * @copyright   2018 David Watson
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since       Moodle 3.3
 */

define(["jquery", "core/modal_factory", "core/config", "core/templates", "core/notification", "core/ajax"],
    function ($, modalFactory, config, Templates, Notification, ajax) {
        "use strict";

        /**
         * Keep references for all modals we have already added to the page,
         * so that we can relaunch then if needed
         * @type {{}}
         */
        var modalStore = {};
        var loadingIconHtml;
        var win = $(window);

        var Selector = {
            launchResourceModal: '[data-action="launch-tiles-resource-modal"]',
            launchModuleModal: '[data-action="launch-tiles-module-modal"]',
            toggleCompletion: ".togglecompletion",
            modal: ".modal",
            modalDialog: ".modal-dialog",
            modalBody: ".modal-body",
            sectionMain: ".section.main",
            pageContent: "#page-content",
            completionState: "#completionstate_",
            cmModalClose: ".embed_cm_modal .close",
            cmModal: ".embed_cm_modal",
            modalClearOnDismissButton: ".clear-on-dismiss button.close"
        };

        var Class = {
            modalClearOnDismiss: "clear-on-dismiss"
        };

        var modalMinWidth = function () {
            return Math.min(win.width(), 900);
        };

        /**
         * Launch a Course Resource Modal if we have it already, or make one and launch e.g. for PDF
         * @param {object} clickedCmObject the course module object which was clicked
         * @returns {boolean} if successful or not
         */
        var launchCourseResourceModal = function (clickedCmObject) {
            var cmid = clickedCmObject.attr("data-cmid");
            modalFactory.create({
                type: modalFactory.types.DEFAULT,
                title: clickedCmObject.attr("data-title"),
                body: loadingIconHtml
            }).done(function (modal) {
                modalStore[cmid] = modal;
                modal.setLarge();
                modal.show();
                var modalRoot = $(modal.root);
                modalRoot.attr("id", "embed_mod_modal_" + cmid);
                modalRoot.attr("data-cmid", cmid);
                modalRoot.addClass("embed_cm_modal");

                // Render the modal body and set it to the page.
                // First a blank template data object.
                var templateData = {
                    id: cmid,
                    pluginfileUrl: clickedCmObject.attr("data-url"),
                    objectType: "text/html",
                    width: "100%",
                    height: Math.round(win.height() - 60), // Embedded object height in modal - make as high as poss.
                    cmid: cmid,
                    tileid: clickedCmObject.closest(Selector.sectionMain).attr("data-section"),
                    isediting: 0,
                    sesskey: config.sesskey,
                    modtitle: clickedCmObject.attr("data-title"),
                    config: {wwwroot: config.wwwroot},
                    showDownload: 0,
                    showNewWindow: 0,
                    completionInUseForCm: 0
                };

                // If it's a PDF in this modal, change from the defaults assigned above.
                if (clickedCmObject.attr('data-modtype') === "resource_pdf") {
                    templateData.objectType = 'application/pdf';
                    templateData.showDownload = 1;
                    templateData.showNewWindow = 1;
                }

                Templates.render("format_tiles/embed_file_modal_body", templateData).done(function (html) {
                    modal.setBody(html);
                    modalRoot.find(Selector.modalBody).animate({"min-height": Math.round(win.height() - 60)}, "fast");

                    if (clickedCmObject.attr('data-modtype') === "resource_html") {
                        // HTML files only - set widths to 100% since they may contain embedded videos etc.
                        modalRoot.find(Selector.modal).animate({"max-width": "100%"}, "fast");
                        modalRoot.find(Selector.modalDialog).animate({"max-width": "100%"}, "fast");
                        modalRoot.find(Selector.modalBody).animate({"max-width": "100%"}, "fast");
                        modalRoot.addClass(Class.modalClearOnDismiss);
                    } else {
                        // Otherwise (e.g for PDF) we don't need 100% width.
                        modalRoot.find(Selector.modal).animate({"max-width": modalMinWidth()}, "fast");
                    }

                }).fail(Notification.exception);
                // Render the modal header / title and set it to the page.
                if (clickedCmObject.find(Selector.toggleCompletion).length !== 0) {
                    var inverseCompletionState = parseInt(
                        $(Selector.completionState + cmid).attr("value")
                    );
                    templateData.completionInUseForCm = 1;
                    templateData.completionstate = 1 - inverseCompletionState;
                    templateData.completionicon = inverseCompletionState === 1 ? 'n' : 'y';
                    templateData.completionstateInverse = inverseCompletionState;
                    templateData.completionIsManual = clickedCmObject
                        .find(Selector.toggleCompletion).attr("data-ismanual");
                }
                Templates.render("format_tiles/embed_module_modal_header", templateData).done(function (html) {
                    modal.setTitle(html);
                }).fail(Notification.exception);

                return true;
            });
            return false;
        };

        // TODO refactor these to avoid repetition?
        /**
         * Launch a Course activity Modal if we have it already, or make one and launch e.g. for "Page"
         * @param {object} clickedCmObject the course module object which was clicked
         * @param {number} courseId the course id for this course
         * @returns {boolean} if successful or not
         */
        var launchCourseActivityModal = function (clickedCmObject, courseId) {
            var cmid = clickedCmObject.attr("data-cmid");
            // TODO code envisages potentially adding in other web services for other mod types, but for now we have page only.
            var methodName = "format_tiles_get_mod_" + clickedCmObject.attr("data-modtype") + "_html";

            modalFactory.create({
                type: modalFactory.types.DEFAULT,
                title: clickedCmObject.attr("data-title"),
                body: loadingIconHtml
            }).done(function (modal) {
                modalStore[cmid] = modal;
                modal.setLarge();
                modal.show();
                var modalRoot = $(modal.root);
                modalRoot.attr("data-cmid", cmid);
                modalRoot.attr("id", "embed_mod_modal_" + cmid);
                modalRoot.addClass("embed_cm_modal");
                modalRoot.addClass(clickedCmObject.attr("data-modtype"));
                ajax.call([{
                    methodname: methodName,
                    args: {
                        courseid: courseId,
                        cmid: cmid
                    }
                }])[0].done(function(response) {
                    var templateData = {
                        cmid: cmid,
                        modtitle: clickedCmObject.attr("data-title"),
                        content: response.html
                    };
                    if (clickedCmObject.find(Selector.toggleCompletion).length !== 0) {
                        var inverseCompletionState = parseInt(
                            $(Selector.completionState + cmid).attr("value")
                        );
                        templateData.completionInUseForCm = 1;
                        templateData.completionstate = 1 - inverseCompletionState;
                        templateData.completionstateInverse = inverseCompletionState;
                        templateData.completionIsManual = clickedCmObject
                            .find(Selector.toggleCompletion).attr("data-ismanual");
                        templateData.completionicon = inverseCompletionState === 1 ? 'n' : 'y';
                    } else {
                        templateData.completionInUseForCm = 0;
                    }
                    modal.setBody(templateData.content);
                    Templates.render("format_tiles/embed_module_modal_header", templateData).done(function (html) {
                        modal.setTitle(html);
                    }).fail(Notification.exception);

                    modalRoot.find(Selector.modal).animate({"max-width": modalMinWidth()}, "fast");

                    var MODAL_MARGIN = 70;
                    // If the activity contains an iframe (e.g. is a page with a YouTube video in it), ensure modal is big enough.
                    // Do this for every iframe in the course module.
                    modalRoot.find("iframe").each(function (index, iframe) {

                        // Get the modal.
                        var modal;
                        // Boost calls the modal "modal dialog" so try this first.
                        modal = modalRoot.find(Selector.modalDialog);

                        // If no luck, try what Clean and Adaptable do instead.
                        if (modal.length == 0) {
                            modal = modalRoot.find(Selector.modal);
                        }

                        // Now check and adjust the width of the modal.
                        var iframeWidth = Math.min($(iframe).width(), win.width());
                        if (iframeWidth > modal.width() - MODAL_MARGIN) {
                            modal.animate(
                                {"max-width": Math.max(iframeWidth + MODAL_MARGIN, modalMinWidth())},
                                "fast"
                            );
                            modalRoot.find(Selector.modal).animate(
                                {"max-width": Math.max(iframeWidth + MODAL_MARGIN, modalMinWidth())},
                                "fast"
                            );
                        }

                        // Then the height of the modal body.
                        var iframeHeight = Math.min($(iframe).height(), win.height());
                        var modalBody = modalRoot.find(Selector.modalBody);
                        if (iframeHeight > modalBody.height() - MODAL_MARGIN) {
                            modalBody.animate({"min-height": Math.min(iframeHeight + MODAL_MARGIN, win.height())}, "fast");
                        }

                        // Align the iframe in the centre of the modal.
                        modalBody.css("text-align", "center");

                        // Add this class so we know to clear the modal on dismiss, not just hide.
                        // This is because it may contain a video which needs to be stopped.
                        // See also event below for what happens when this class is clicked.
                        modalRoot.addClass(Class.modalClearOnDismiss);
                    });
                    return true;
                }).fail(function(ex) {
                    if (config.developerdebug !== true) {
                        // Load the activity using PHP instead.
                        window.location = config.wwwroot + "/mod/" + clickedCmObject.attr("data-modtype") + "/view.php?id=" + cmid;
                    } else {
                        Notification.exception(ex);
                    }
                });
            });
            return false;
        };

        return {
            init: function (courseId) {
                $(document).ready(function () {
                    $(Selector.pageContent).on("click", Selector.launchResourceModal, function (e) {
                        e.preventDefault();
                        var clickedCmObject = $(e.currentTarget).closest("li.activity");

                        // If we already have this modal on the page, launch it.
                        var existingModal = modalStore[clickedCmObject.attr("data-cmid")];
                        if (typeof existingModal === "object") {
                            existingModal.show();
                        } else {
                            // We don't already have it, so make it.
                            launchCourseResourceModal(clickedCmObject);
                            // Log the fact we viewed it (only do this once not every time the modal launches).
                            ajax.call([{
                                methodname: "format_tiles_log_mod_view", args: {
                                    courseid: courseId,
                                    cmid: clickedCmObject.attr("data-cmid")
                                }
                                }])[0].fail(Notification.exception);
                        }
                    });

                    $(Selector.pageContent).on("click", Selector.launchModuleModal, function (e) {
                        e.preventDefault();
                        var clickedCmObject = $(e.currentTarget).closest("li.activity");
                        // If we already have this modal on the page, launch it.
                        var existingModal = modalStore[clickedCmObject.attr("data-cmid")];
                        if (typeof existingModal === "object") {
                            existingModal.show();
                        } else {
                            // We don't already have it, so make it.
                            launchCourseActivityModal(clickedCmObject, courseId);
                            ajax.call([{
                                methodname: "format_tiles_log_mod_view", args: {
                                    courseid: courseId,
                                    cmid: clickedCmObject.attr("data-cmid")
                                }
                                }])[0].fail(Notification.exception);
                        }
                        return false;
                    });

                    // Some modals need to be emptied when dismissed (e.g. contain a video which needs to be stopped).
                    $("body").on("click", Selector.modalClearOnDismissButton, function (e) {
                        var modalClosingId = $(e.currentTarget).closest(Selector.cmModal).attr("data-cmid");
                        $(e.currentTarget).closest(Selector.cmModal).find(Selector.modalBody).empty();
                        modalStore[modalClosingId] = undefined;
                    });

                     // Render the loading icon and append it to body so that we can use it later.
                    Templates.render("format_tiles/loading", {})
                        .catch(Notification.exception)
                        .done(function (html) {
                            loadingIconHtml = html; // TODO get this from elsewhere.
                        }).fail(Notification.exception);
                });
            }
        };
    }
);