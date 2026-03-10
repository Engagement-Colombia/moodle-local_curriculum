[← Back to index](README.md)

# User dashboard

The dashboard shows the user their progress in the assigned curriculum program.

## Access

- From the user profile: **My curriculum** link.
- Direct URL: `/local/curriculum/index.php`
- To view another user's curriculum (requires `viewother` permission): `/local/curriculum/index.php?userid=ID`

## Dashboard content

### Current program

If the user has active cycles (no end date), the following is shown:

- **Program and version name.**
- **Timeline:** visual bar showing the proportion of each cycle and time progress.
- **Elapsed and remaining days.**
- **Cycles:** displayed as numbered steps. Each one shows its state:
  - **Completed** — the number is replaced by a check icon.
  - **In progress** — shows the step number and progress percentage.
  - **Locked** — shows only the step number.
- **Courses per cycle:** each cycle shows its courses with:
  - Link to the course.
  - Progress bar (if the course has completion tracking enabled).
  - Status: completed, in progress, not started, or locked.

### History

If the user had previous programs (cycles with an end date), they are shown in a history section with the end reason:

| Reason | Meaning |
|---|---|
| Completed | Completed all courses |
| Program change | The program was changed in their profile |
| User deleted | The account was deleted |
| Homologated | The cycle was homologated |
