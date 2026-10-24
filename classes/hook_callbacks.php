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

namespace local_forumgradevisibility;

use core\hook\output\before_standard_top_of_body_html_generation;

/**
 * Hook callbacks for local_forumgradevisibility.
 *
 * @package    local_forumgradevisibility
 * @copyright  2026 Harvey / Equip English
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * On a forum's main page, removes the student "View grades" button
     * while the user's forum grade is hidden in the gradebook.
     *
     * The decision is made on the server and the script is only loaded when
     * the button actually has to go, so graders never receive it at all.
     *
     * @param before_standard_top_of_body_html_generation $hook
     */
    public static function before_standard_top_of_body_html_generation(
        before_standard_top_of_body_html_generation $hook
    ): void {
        global $CFG, $PAGE, $USER;

        if (!$PAGE->has_set_url() || strpos($PAGE->url->out_omit_querystring(), '/mod/forum/view.php') === false) {
            return;
        }
        if (!isloggedin() || isguestuser()) {
            return;
        }
        // The page's own cm is not always filled in yet at this point, so find the forum from the context.
        if (!$PAGE->context || $PAGE->context->contextlevel != CONTEXT_MODULE) {
            return;
        }
        $cm = get_coursemodule_from_id('forum', $PAGE->context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }

        $reason = visibility::decide(
            (int) $cm->instance,
            (int) $cm->course,
            $PAGE->context,
            (int) $USER->id
        );
        if ($reason === visibility::REASON_HIDDEN) {
            $PAGE->requires->js_call_amd('local_forumgradevisibility/hidebutton', 'init');
        }
        // With Developer debugging on, say in the browser console what was decided and why.
        if ($CFG->debug >= DEBUG_DEVELOPER) {
            $PAGE->requires->js_call_amd('local_forumgradevisibility/hidebutton', 'report', [$reason]);
        }
    }
}
