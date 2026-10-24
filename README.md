# Forum grade visibility (local_forumgradevisibility)

Moodle's forum activity can grade the **whole forum**. The gradebook lets you
hide those grades, or release them on a date, but a student can still open the
forum and click **View grades** at any time. Assignments and quizzes don't have
this problem. This plugin closes the gap in the page: the student's
**View grades** button only appears once their forum grade is visible in the
gradebook.

## What it does

On a forum's main page, for a user who **cannot grade the forum** and **cannot
see hidden grades**, the "View grades" button is removed while any of these is
true in the gradebook:

- the forum grade item is hidden;
- the forum grade item is hidden until a date that hasn't arrived yet;
- that student's own grade is hidden (or hidden until a future date).

There is nothing to configure. Whatever you set in the gradebook decides.

Teachers and anyone else who can grade the forum are never affected: the
**Grade users** button is a different button and is never touched.

## Important limitation

This hides the button. It is **not a security feature**. Moodle's forum does
not check the gradebook's hidden setting when the grade pop-up asks the server
for the grade, and a plugin has no way to add that check. A student who sends
that request by hand (for example from the browser's developer tools) could
still read their own grade. Ordinary students using the page will not see it.
Closing the gap completely needs a change to Moodle core.

## Requirements

Moodle 4.5 or later (uses the hooks API). Tested in CI on 4.5, 5.0 and 5.1.

## Installation

Copy the plugin folder to `local/forumgradevisibility` (the folder must be
named exactly that) and visit **Site administration > Notifications**.

## Privacy

The plugin stores no personal data.

## Licence

GNU GPL v3 or later.
