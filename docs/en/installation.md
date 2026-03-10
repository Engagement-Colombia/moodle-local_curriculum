[← Back to index](README.md)

# Installation and configuration

## Requirements

- Moodle 4.5 or higher.
- Enrolment plugin [`enrol_curriculum`](https://github.com/Engagement-Colombia/moodle-enrol_curriculum) installed (used to automatically enrol users in program courses based on the cycle and its items).
- User profile field plugin [`profilefield_curriculums`](https://github.com/Engagement-Colombia/moodle-profilefield_curriculum/) installed (used to associate users with a curriculum program).

## Installation

1. Copy the plugin folder to `/local/curriculum/`.
2. Go to **Site administration > Notifications** to complete the installation.

## General settings

Found at **Site administration > Courses > Curriculum > Settings**.

### Auto-create groups

- **Option:** `autocreategroups`
- If enabled, when a user is assigned to a course that has a group template in the cycle item, the group is automatically created in the course if it doesn't exist.
- If disabled, an email notification is sent indicating the group must be created manually.

### New group notification emails

- **Option:** `newgroupnotifyemails`
- List of email addresses (one per line) that will receive a notification when a new group is created or when one needs to be created manually.

## User profile field

To link users to programs, a custom user profile field of type `curriculum` is used.

When a user is created or updated and their profile field has an assigned program, the system automatically:

1. Starts the first cycle of the program for that user.
2. Enrols them in the corresponding courses.

If the program changes in the profile, the cycles from the previous program are automatically closed.
