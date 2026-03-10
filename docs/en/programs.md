[← Back to index](README.md)

# Programs

A program is the highest level of the curriculum structure. It represents a complete learning plan.

## Management

Accessed from **Site administration > Courses > Curriculum > Manage curriculum**.

### Form fields

| Field | Description |
|---|---|
| Name | Program name (required) |
| Description | Program description |
| Status | Enabled or Disabled |

Additionally, any [custom fields](custom-fields.md) configured for programs will appear in the form.

### Creating a program

1. Click **new**.
2. Fill in the form fields.
3. Save.

### Editing or deleting

From the program list, use the actions available in each row.
A program cannot be deleted if it has associated Versions.

## Hierarchical structure

```
Program
 └── Version
      └── Cycle
           └── Item (courses)
```

A program can have multiple versions, but only one will be active at any given time.
