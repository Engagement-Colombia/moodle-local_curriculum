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
 * Simple markdown documentation viewer.
 *
 * @package    local_curriculum
 * @copyright  2026 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

require_login();

$page = optional_param('page', 'README', PARAM_ALPHANUMEXT);
$lang = optional_param('lang', '', PARAM_ALPHA);

if (empty($lang)) {
    $lang = current_language();
}

// Scan available documentation languages.
$availablelangs = [];
foreach (scandir(__DIR__) as $entry) {
    if ($entry !== '.' && $entry !== '..' && is_dir(__DIR__ . '/' . $entry)) {
        $availablelangs[] = $entry;
    }
}

if (!in_array($lang, $availablelangs)) {
    $lang = 'en';
}

$page = basename($page);
if (empty($page)) {
    $page = 'README';
}

$filepath = __DIR__ . '/' . $lang . '/' . $page . '.md';

$pluginname = get_string('pluginname', 'local_curriculum');
$docstitle = get_string('docs_title', 'local_curriculum');

if (!file_exists($filepath)) {
    http_response_code(404);
    $content = '# ' . get_string('docs_pagenotfound', 'local_curriculum');
    $title = get_string('docs_pagenotfound', 'local_curriculum');
} else {
    $content = file_get_contents($filepath);
    $title = $page;
    if (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
        $title = $matches[1];
    }
}

// Rewrite internal .md links to viewer URLs.
$content = preg_replace(
    '/\[([^\]]+)\]\(([a-zA-Z0-9_\-]+)\.md\)/',
    '[$1](?lang=' . $lang . '&page=$2)',
    $content
);

$content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

// Page setup.
$url = new moodle_url('/local/curriculum/docs/index.php', ['lang' => $lang, 'page' => $page]);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title($title . ' - ' . $pluginname . ' ' . $docstitle);
$PAGE->set_heading($pluginname . ' - ' . $docstitle);

// Build template data.
$langs = [];
foreach ($availablelangs as $l) {
    $langs[] = [
        'code' => $l,
        'label' => strtoupper($l),
        'selected' => ($l === $lang),
    ];
}

$templatedata = [
    'contentjson' => json_encode($content),
    'lang' => $lang,
    'page' => $page,
    'topbartitle' => $pluginname . ' - ' . $docstitle,
    'readmeurl' => (new moodle_url('/local/curriculum/docs/index.php', ['lang' => $lang, 'page' => 'README']))->out(false),
    'langs' => $langs,
];

$PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/marked/marked.min.js'), true);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_curriculum/docs', $templatedata);
echo $OUTPUT->footer();
