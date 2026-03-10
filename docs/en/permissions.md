[← Back to index](README.md)

# Permissions

The plugin defines the following capabilities:

| Capability | Type | Description | Default role |
|---|---|---|---|
| `local/curriculum:manage` | Write | Manage programs, versions, cycles, and items | Manager |
| `local/curriculum:configurecustomfields` | Write | Configure curriculum custom fields | Manager |
| `local/curriculum:viewreport` | Read | View curriculum report | Manager |
| `local/curriculum:view` | Read | View own curriculum (dashboard) | Authenticated user |
| `local/curriculum:viewother` | Read | View other users' curriculum | Manager |

All operate at the **system context** level.
