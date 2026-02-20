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
 * Module for managing curriculum tree interactions with lazy-loading via AJAX.
 *
 * @module     local_curriculum/tree
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/templates', 'core/notification', 'core/str'], function($, Ajax, Templates, Notification, Str) {
    'use strict';

    const SELECTOR_TOGGLE = '.curriculum-tree-toggle';
    const SELECTOR_CHILDREN = '.curriculum-tree-children';
    const SELECTOR_NODE = '.curriculum-tree-node';
    const SELECTOR_EXPAND_ALL = '.curriculum-tree-expand-all';
    const SELECTOR_COLLAPSE_ALL = '.curriculum-tree-collapse-all';
    const CLASS_COLLAPSED = 'curriculum-tree-collapsed';

    /**
     * Map of node types to their AJAX configuration.
     */
    var NODE_CONFIG = {
        'program': {
            ws: 'local_curriculum_get_versions',
            template: 'local_curriculum/tree_versions',
            param: 'programid'
        },
        'version': {
            ws: 'local_curriculum_get_cycles',
            template: 'local_curriculum/tree_cycles',
            param: 'versionid'
        },
        'cycle': {
            ws: 'local_curriculum_get_items',
            template: 'local_curriculum/tree_items',
            param: 'cycleid'
        }
    };

    /**
     * Ordered list of expandable node types for "Expand All".
     */
    var EXPANDABLE_TYPES = ['program', 'version', 'cycle'];

    return {
        /**
         * Pre-loaded strings for partial templates.
         */
        strings: {},

        /**
         * Whether strings have been loaded.
         */
        stringsLoaded: null,

        /**
         * Initializes tree interactions.
         */
        init: function() {
            this.loadStrings();
            this.attachEventHandlers();
        },

        /**
         * Pre-loads all strings needed by partial templates.
         */
        loadStrings: function() {
            var self = this;
            this.stringsLoaded = Str.get_strings([
                {key: 'expandall', component: 'local_curriculum'},
                {key: 'configure', component: 'local_curriculum'},
                {key: 'noversions', component: 'local_curriculum'},
                {key: 'nocycles', component: 'local_curriculum'},
                {key: 'noitems', component: 'local_curriculum'},
                {key: 'stage', component: 'local_curriculum'},
                {key: 'durationdays', component: 'local_curriculum'},
                {key: 'validitydays', component: 'local_curriculum'},
                {key: 'loading', component: 'local_curriculum'},
            ]).then(function(strings) {
                self.strings = {
                    expandlabel: strings[0],
                    configurelabel: strings[1],
                    noversionslabel: strings[2],
                    nocycleslabel: strings[3],
                    noitemslabel: strings[4],
                    stagelabel: strings[5],
                    durationlabel: strings[6],
                    validitylabel: strings[7],
                    loadinglabel: strings[8],
                };
                return self.strings;
            });
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
            var $button = $(e.currentTarget);
            var $node = $button.closest(SELECTOR_NODE);
            var $children = $node.children(SELECTOR_CHILDREN);

            if ($children.length === 0) {
                return;
            }

            var isExpanded = $button.attr('aria-expanded') === 'true';

            if (isExpanded) {
                // Collapse.
                this.collapseNode($button, $node, $children);
            } else {
                // Expand: load children if not loaded yet.
                var loaded = $children.attr('data-loaded');
                if (loaded === 'false') {
                    this.loadChildren($node, $children);
                } else {
                    this.expandNode($button, $node, $children);
                }
            }
        },

        /**
         * Expands a node visually.
         *
         * @param {jQuery} $button The toggle button.
         * @param {jQuery} $node The tree node.
         * @param {jQuery} $children The children container.
         */
        expandNode: function($button, $node, $children) {
            $button.attr('aria-expanded', 'true');
            $node.removeClass(CLASS_COLLAPSED);
            $children.slideDown(200);
        },

        /**
         * Collapses a node visually.
         *
         * @param {jQuery} $button The toggle button.
         * @param {jQuery} $node The tree node.
         * @param {jQuery} $children The children container.
         */
        collapseNode: function($button, $node, $children) {
            $button.attr('aria-expanded', 'false');
            $node.addClass(CLASS_COLLAPSED);
            $children.slideUp(200);
        },

        /**
         * Loads children via AJAX for a given node.
         *
         * @param {jQuery} $node The tree node.
         * @param {jQuery} $container The children container.
         * @return {Promise} A promise that resolves when children are loaded and rendered.
         */
        loadChildren: function($node, $container) {
            var type = $node.data('node-type');
            var id = $node.data('node-id');
            var config = NODE_CONFIG[type];

            if (!config) {
                return $.Deferred().resolve().promise();
            }

            // Show loading indicator.
            var self = this;
            $container.html('<div class="curriculum-tree-loading p-2 text-muted">' +
                '<i class="fa fa-spinner fa-spin"></i> ' + (self.strings.loadinglabel || '') + '</div>');
            $container.show();
            $node.removeClass(CLASS_COLLAPSED);
            $node.find('> .curriculum-tree-item > ' + SELECTOR_TOGGLE).attr('aria-expanded', 'true');

            var args = {};
            args[config.param] = id;

            // Wait for strings to be loaded, then fetch data.
            return self.stringsLoaded
                .then(function() {
                    return Ajax.call([{methodname: config.ws, args: args}])[0];
                })
                .then(function(data) {
                    var context = $.extend({items: data, manageurl: M.cfg.wwwroot}, self.strings);
                    return Templates.render(config.template, context);
                })
                .then(function(html, js) {
                    $container.html(html);
                    $container.attr('data-loaded', 'true');
                    Templates.runTemplateJS(js);

                    // Ensure expanded state.
                    var $button = $node.find('> .curriculum-tree-item > ' + SELECTOR_TOGGLE);
                    self.expandNode($button, $node, $container);
                    return true;
                })
                .catch(function(ex) {
                    $container.html('<div class="alert alert-danger p-2">Error loading data.</div>');
                    Notification.exception(ex);
                });
        },

        /**
         * Loads all unloaded nodes of a specific type.
         *
         * @param {jQuery} $tree The tree root element.
         * @param {string} nodeType The node type to expand (program, version, cycle).
         * @return {Promise} A promise that resolves when all nodes of this type are loaded.
         */
        expandNodesByType: function($tree, nodeType) {
            var self = this;
            var promises = [];

            $tree.find('[data-node-type="' + nodeType + '"]').each(function() {
                var $node = $(this);
                var $children = $node.children(SELECTOR_CHILDREN);

                if ($children.length === 0) {
                    return;
                }

                var loaded = $children.attr('data-loaded');
                if (loaded === 'false') {
                    promises.push(self.loadChildren($node, $children));
                } else {
                    var $button = $node.find('> .curriculum-tree-item > ' + SELECTOR_TOGGLE);
                    self.expandNode($button, $node, $children);
                }
            });

            if (promises.length === 0) {
                return $.Deferred().resolve().promise();
            }

            return $.when.apply($, promises);
        },

        /**
         * Expands all nodes in the tree, loading children level by level.
         *
         * @param {Object} e - Event object
         */
        handleExpandAll: function(e) {
            e.preventDefault();
            var $btn = $(e.currentTarget);
            var $tree = $btn.closest('.curriculum-tree');
            var self = this;

            // Disable button during loading.
            $btn.prop('disabled', true);

            // Expand level by level: programs → versions → cycles.
            var chain = $.Deferred().resolve().promise();

            $.each(EXPANDABLE_TYPES, function(index, type) {
                chain = chain.then(function() {
                    return self.expandNodesByType($tree, type);
                });
            });

            chain.always(function() {
                $btn.prop('disabled', false);
            });
        },

        /**
         * Collapses all nodes in the tree.
         *
         * @param {Object} e - Event object
         */
        handleCollapseAll: function(e) {
            e.preventDefault();
            var $tree = $(e.currentTarget).closest('.curriculum-tree');
            var $toggles = $tree.find(SELECTOR_TOGGLE);
            var self = this;

            $toggles.each(function() {
                var $toggle = $(this);
                var $node = $toggle.closest(SELECTOR_NODE);
                var $children = $node.children(SELECTOR_CHILDREN);

                if ($children.length > 0) {
                    self.collapseNode($toggle, $node, $children);
                }
            });
        },
    };
});
