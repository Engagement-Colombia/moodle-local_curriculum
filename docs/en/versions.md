[← Back to index](README.md)

# Versions

A version represents an edition or iteration of a program. It allows modifying the cycle structure without affecting users already in a previous version.

## Main fields

| Field | Description |
|---|---|
| Name | Version name |
| Start date | When it becomes available |
| End date | When it stops being available (optional) |

## Active version

The system considers a version active when:

- Its start date has passed or is today.
- It has no end date, or its end date has not been reached.

If multiple versions meet these conditions, the one with the most recent start date is used.

## Management

From a program's management view, versions can be added, edited, and deleted.
A version cannot be deleted if it has associated cycles.
