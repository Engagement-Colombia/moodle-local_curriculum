[← Back to index](README.md)

# Cycles

A cycle is a stage within a version. Users progress through cycles sequentially.

## Main fields

| Field | Description |
|---|---|
| Name | Cycle name |
| Description | Optional description |
| Duration (days) | Duration in days |
| Stage | Order of the cycle within the version (lower = first) |

## Cycle activation

When a user is assigned to the first cycle of a program, subsequent cycles are activated automatically based on accumulated time:

```
Activation time for cycle N = start date + sum of durations of previous cycles
```

A scheduled task (`activate_user_cycles`) runs daily at 1:00 AM and creates the assignments for cycles that should already be active.

## Cycle states for the user

| State | Meaning |
|---|---|
| **In progress** | The user is currently in this cycle |
| **Completed** | The user finished all courses in the cycle |
| **Locked** | The activation date has not been reached yet |

## Management

From a version's view, cycles can be added, edited, and deleted.
Cycles cannot be deleted if they have associated items.
