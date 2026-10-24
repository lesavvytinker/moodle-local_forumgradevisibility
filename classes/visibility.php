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

/**
 * Decides whether a user should be shown the forum's "View grades" button.
 *
 * The rule is simply "follow the gradebook": a user who cannot grade the
 * forum and cannot see hidden grades is only shown the button once their
 * whole-forum grade is visible to them in the gradebook (not hidden, and
 * not hidden until a future date - either on the grade item or on that
 * user's own grade).
 *
 * @package    local_forumgradevisibility
 * @copyright  2026 Harvey / Equip English
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class visibility {
    /** @var int Grade item number of mod_forum's "whole forum" grade (0 is the ratings grade). */
    const WHOLE_FORUM_ITEMNUMBER = 1;

    /** @var string The user can grade the forum, so nothing is hidden. */
    const REASON_GRADER = 'grader';
    /** @var string The user can see hidden grades, so nothing is hidden. */
    const REASON_VIEWHIDDEN = 'viewhidden';
    /** @var string The forum has no whole-forum grade item. */
    const REASON_NOITEM = 'noitem';
    /** @var string The grade is visible to this user. */
    const REASON_VISIBLE = 'visible';
    /** @var string The grade is hidden, so the button is removed. */
    const REASON_HIDDEN = 'hidden';

    /**
     * Whether the "View grades" button should be hidden from this user.
     *
     * @param int $forumid The forum instance id.
     * @param int $courseid The course id.
     * @param \context_module $context The forum's module context.
     * @param int $userid The user viewing the page.
     * @return bool True if the button should be removed for this user.
     */
    public static function should_hide_view_button(int $forumid, int $courseid, \context_module $context, int $userid): bool {
        return self::decide($forumid, $courseid, $context, $userid) === self::REASON_HIDDEN;
    }

    /**
     * Works out what to do for this user and says why.
     *
     * @param int $forumid The forum instance id.
     * @param int $courseid The course id.
     * @param \context_module $context The forum's module context.
     * @param int $userid The user viewing the page.
     * @return string One of the REASON_* constants.
     */
    public static function decide(int $forumid, int $courseid, \context_module $context, int $userid): string {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');

        // Graders keep their own "Grade users" button and are never affected.
        if (has_capability('mod/forum:grade', $context, $userid)) {
            return self::REASON_GRADER;
        }
        // Anyone allowed to see hidden grades is not affected either.
        if (has_capability('moodle/grade:viewhidden', $context, $userid)) {
            return self::REASON_VIEWHIDDEN;
        }

        $gradeitem = \grade_item::fetch([
            'itemtype' => 'mod',
            'itemmodule' => 'forum',
            'iteminstance' => $forumid,
            'itemnumber' => self::WHOLE_FORUM_ITEMNUMBER,
            'courseid' => $courseid,
        ]);
        if (!$gradeitem) {
            return self::REASON_NOITEM;
        }

        $grade = \grade_grade::fetch(['itemid' => $gradeitem->id, 'userid' => $userid]);
        // A grade takes both its own setting and the grade item's into account.
        $hidden = $grade ? $grade->is_hidden() : $gradeitem->is_hidden();
        return $hidden ? self::REASON_HIDDEN : self::REASON_VISIBLE;
    }
}
