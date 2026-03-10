[← Back to index](README.md)

# Cycle items

An item links courses to a cycle. It defines which courses the user must complete in that stage.

## Main fields

| Field | Description |
|---|---|
| Course code (`coursecode`) | Course identifier in Moodle (`idnumber`) |
| Group template (`grouptemplate`) | Name of the group the user will be assigned to in the course (optional) |

## Course code

The `coursecode` field searches for courses by their `idnumber` field in Moodle.

- **Exact:** `COURSE-001` → searches for the course with that exact idnumber.
- **With wildcard:** `COURSE-%` → searches for all courses whose idnumber starts with `COURSE-`.

This allows a single item to link multiple courses.

## Group template

If `grouptemplate` is defined, when the user is enrolled in the course they are assigned to a group with that name.

### Available placeholders

Replaced with data from the user's profile:

| Placeholder | Value |
|---|---|
| `{institution}` | User's institution |
| `{department}` | Department |
| `{city}` | City |
| `{country}` | Country |
| `{lang}` | Language |
| `{profile_field_X}` | Custom profile field (where X is the field's short name) |

### Example

If `grouptemplate` is `Group-{department}` and the user's department is "Sales", they will be assigned to the group `Group-Sales`.

If the group doesn't exist and auto-creation is enabled, it is created automatically.
