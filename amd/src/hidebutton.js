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
 * Removes the student "View grades" button from a forum's main page.
 *
 * Only the button marked data-grade-action="view" is touched. The grader's
 * "Grade users" button is marked "launch" and is never matched here.
 *
 * @module     local_forumgradevisibility/hidebutton
 * @copyright  2026 Harvey / Equip English
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Removes every "View grades" button currently on the page.
 */
const removeButtons = () => {
    document.querySelectorAll('[data-grade-action="view"]').forEach((button) => {
        button.remove();
    });
};

/**
 * Initialise: remove the button now, or as soon as the page has loaded.
 */
export const init = () => {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', removeButtons);
    } else {
        removeButtons();
    }
};

/**
 * Writes the server's decision to the browser console (Developer debugging only).
 *
 * @param {String} reason Why the button was or wasn't removed.
 */
export const report = (reason) => {
    window.console.log('local_forumgradevisibility: ' + reason);
};
