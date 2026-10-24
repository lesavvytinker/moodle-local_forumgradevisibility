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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/forum/lib.php');
require_once($CFG->libdir . '/gradelib.php');

/**
 * Unit tests for local_forumgradevisibility\visibility.
 *
 * @package    local_forumgradevisibility
 * @copyright  2026 Harvey / Equip English
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_forumgradevisibility\visibility
 */
final class visibility_test extends \advanced_testcase {
    /**
     * Creates a course with a whole-forum-graded forum, a student and a teacher.
     *
     * @param int $gradeforum The forum's "whole forum grading" maximum (0 for none).
     * @return \stdClass
     */
    private function create_fixture(int $gradeforum = 100): \stdClass {
        $course = $this->getDataGenerator()->create_course();
        $forum = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_instance([
            'course' => $course->id,
            'grade_forum' => $gradeforum,
        ]);
        $student = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');

        $gradeitem = \grade_item::fetch([
            'itemtype' => 'mod',
            'itemmodule' => 'forum',
            'iteminstance' => $forum->id,
            'itemnumber' => visibility::WHOLE_FORUM_ITEMNUMBER,
            'courseid' => $course->id,
        ]);

        return (object) [
            'course' => $course,
            'forum' => $forum,
            'context' => \context_module::instance($forum->cmid),
            'student' => $student,
            'teacher' => $teacher,
            'gradeitem' => $gradeitem,
        ];
    }

    /**
     * Shortcut for the method under test.
     *
     * @param \stdClass $fixture
     * @param \stdClass $user
     * @return bool
     */
    private function hidden_for(\stdClass $fixture, \stdClass $user): bool {
        return visibility::should_hide_view_button(
            (int) $fixture->forum->id,
            (int) $fixture->course->id,
            $fixture->context,
            (int) $user->id
        );
    }

    /**
     * The fixture really does have a whole-forum grade item.
     */
    public function test_fixture_has_a_whole_forum_grade_item(): void {
        $this->resetAfterTest();
        $fixture = $this->create_fixture();
        $this->assertNotEmpty($fixture->gradeitem);
    }

    /**
     * A visible grade keeps the button for the student.
     */
    public function test_button_is_kept_when_the_grade_is_visible(): void {
        $this->resetAfterTest();
        $fixture = $this->create_fixture();
        $this->assertFalse($this->hidden_for($fixture, $fixture->student));
    }

    /**
     * A grade item hidden in the gradebook removes the student's button.
     */
    public function test_button_is_hidden_when_the_grade_item_is_hidden(): void {
        $this->resetAfterTest();
        $fixture = $this->create_fixture();
        $fixture->gradeitem->set_hidden(1);
        $this->assertTrue($this->hidden_for($fixture, $fixture->student));
    }

    /**
     * A grade item hidden until a future date removes the button until then.
     */
    public function test_button_is_hidden_until_a_future_release_date(): void {
        $this->resetAfterTest();
        $fixture = $this->create_fixture();
        $fixture->gradeitem->set_hidden(time() + DAYSECS);
        $this->assertTrue($this->hidden_for($fixture, $fixture->student));
    }

    /**
     * Once the release date has passed the button is back.
     */
    public function test_button_returns_once_the_release_date_has_passed(): void {
        $this->resetAfterTest();
        $fixture = $this->create_fixture();
        $fixture->gradeitem->set_hidden(time() - HOURSECS);
        $this->assertFalse($this->hidden_for($fixture, $fixture->student));
    }

    /**
     * A single student's grade hidden in the gradebook removes only their button.
     */
    public function test_button_follows_a_single_students_hidden_grade(): void {
        $this->resetAfterTest();
        $fixture = $this->create_fixture();
        $other = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($other->id, $fixture->course->id, 'student');

        $fixture->gradeitem->update_final_grade($fixture->student->id, 80);
        $fixture->gradeitem->update_final_grade($other->id, 70);
        $grade = \grade_grade::fetch(['itemid' => $fixture->gradeitem->id, 'userid' => $fixture->student->id]);
        $grade->set_hidden(1);

        $this->assertTrue($this->hidden_for($fixture, $fixture->student));
        $this->assertFalse($this->hidden_for($fixture, $other));
    }

    /**
     * The grader is never affected, so "Grade users" always stays.
     */
    public function test_graders_are_never_affected(): void {
        $this->resetAfterTest();
        $fixture = $this->create_fixture();
        $fixture->gradeitem->set_hidden(1);
        $this->assertFalse($this->hidden_for($fixture, $fixture->teacher));
    }

    /**
     * A forum with no whole-forum grading has nothing to hide.
     */
    public function test_nothing_is_hidden_when_forum_grading_is_off(): void {
        $this->resetAfterTest();
        $fixture = $this->create_fixture(0);
        $this->assertFalse($this->hidden_for($fixture, $fixture->student));
    }
}
