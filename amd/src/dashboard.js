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
 * Dashboard stepper interaction module.
 *
 * @module     local_curriculum/dashboard
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {
    'use strict';

    var SELECTORS = {
        STEP: '.curriculum-stepper-step',
        DETAIL: '.curriculum-cycle-detail',
        DASHBOARD: '.curriculum-dashboard'
    };

    var CSS = {
        HIDDEN: 'd-none',
        SELECTED: 'curriculum-stepper-selected'
    };

    return {
        /**
         * Initializes the dashboard interactivity.
         */
        init: function() {
            $(SELECTORS.STEP).on('click', this.handleStepClick.bind(this));
            $(SELECTORS.STEP).on('keydown', this.handleStepKeydown.bind(this));
        },

        /**
         * Handles click on a stepper step.
         *
         * @param {Object} e Event object.
         */
        handleStepClick: function(e) {
            var $step = $(e.currentTarget);
            var cycleid = $step.data('cycleid');
            var $dashboard = $step.closest(SELECTORS.DASHBOARD);

            // Update detail panels.
            $dashboard.find(SELECTORS.DETAIL).addClass(CSS.HIDDEN);
            $dashboard.find('[data-cycle-detail="' + cycleid + '"]').removeClass(CSS.HIDDEN);

            // Update stepper selection.
            $dashboard.find(SELECTORS.STEP).removeClass(CSS.SELECTED);
            $step.addClass(CSS.SELECTED);
        },

        /**
         * Handles keyboard interaction on stepper steps.
         *
         * @param {Object} e Event object.
         */
        handleStepKeydown: function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.handleStepClick(e);
            }
        }
    };
});
