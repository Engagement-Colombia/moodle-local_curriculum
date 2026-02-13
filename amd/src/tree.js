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
 * Module for managing curriculum tree interactions.
 *
 * @module     local_curriculum/tree
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {
    'use strict';

    const SELECTOR_TOGGLE = '.curriculum-tree-toggle';
    const SELECTOR_CHILDREN = '.curriculum-tree-children';
    const SELECTOR_NODE = '.curriculum-tree-node';
    const SELECTOR_EXPAND_ALL = '.curriculum-tree-expand-all';
    const SELECTOR_COLLAPSE_ALL = '.curriculum-tree-collapse-all';
    const CLASS_COLLAPSED = 'curriculum-tree-collapsed';

    return {
        /**
         * Initializes tree interactions.
         */
        init: function() {
            this.attachEventHandlers();
        },

        /**
         * Attaches event handlers for tree interactions.
         */
        attachEventHandlers: function() {
            $(document).on('click', SELECTOR_TOGGLE, this.handleToggleClick.bind(this));
            $(document).on('click', SELECTOR_EXPAND_ALL, this.handleExpandAll.bind(this));
            $(document).on('click', SELECTOR_COLLAPSE_ALL, this.handleCollapseAll.bind(this));
        },

        /**
         * Handles toggle button clicks to expand/collapse nodes.
         *
         * @param {Object} e - Event object
         */
        handleToggleClick: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const $node = $button.closest(SELECTOR_NODE);
            const $children = $node.children(SELECTOR_CHILDREN);

            if ($children.length === 0) {
                return;
            }

            const isExpanded = $button.attr('aria-expanded') === 'true';
            const newState = !isExpanded;

            $button.attr('aria-expanded', newState.toString());

            if (newState) {
                $node.removeClass(CLASS_COLLAPSED);
                $children.slideDown(200);
            } else {
                $node.addClass(CLASS_COLLAPSED);
                $children.slideUp(200);
            }
        },

        /**
         * Expands all nodes in the tree.
         *
         * @param {Object} e - Event object
         */
        handleExpandAll: function(e) {
            e.preventDefault();
            const $tree = $(e.currentTarget).closest('.curriculum-tree');
            const $toggles = $tree.find(SELECTOR_TOGGLE);

            $toggles.each((index, toggle) => {
                const $toggle = $(toggle);
                const $node = $toggle.closest(SELECTOR_NODE);
                const $children = $node.children(SELECTOR_CHILDREN);

                if ($children.length > 0) {
                    $toggle.attr('aria-expanded', 'true');
                    $node.removeClass(CLASS_COLLAPSED);
                    $children.slideDown(200);
                }
            });
        },

        /**
         * Collapses all nodes in the tree.
         *
         * @param {Object} e - Event object
         */
        handleCollapseAll: function(e) {
            e.preventDefault();
            const $tree = $(e.currentTarget).closest('.curriculum-tree');
            const $toggles = $tree.find(SELECTOR_TOGGLE);

            $toggles.each((index, toggle) => {
                const $toggle = $(toggle);
                const $node = $toggle.closest(SELECTOR_NODE);
                const $children = $node.children(SELECTOR_CHILDREN);

                if ($children.length > 0) {
                    $toggle.attr('aria-expanded', 'false');
                    $node.addClass(CLASS_COLLAPSED);
                    $children.slideUp(200);
                }
            });
        },
    };
});
