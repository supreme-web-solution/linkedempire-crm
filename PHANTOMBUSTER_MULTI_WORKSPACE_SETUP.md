# PhantomBuster Multi-Workspace Setup Guide

## Overview
This system allows you to use multiple PhantomBuster workspaces (API keys) to enable concurrent processing. When one workspace is busy, the system automatically uses the next available one. If all workspaces are busy, jobs will wait until one becomes available.

## Configuration

### Environment Variables (.env)

Add your workspace API keys and phantom IDs as comma-separated values:

```env
# Multi-workspace API keys (comma-separated)
PHANTOMBUSTER_API_KEY=Pj02SzXbQYd8WqDv42EnF3oyZrjVoE4qRgE3sjonzh0,second_key_here,third_key_here,fourth_key_here,fifth_key_here

# Multi-workspace Phantom IDs (comma-separated, must match workspace count)
PHANTOMBUSTER_LINKEDIN_POST_LIKERS_PHANTOM_ID=7813531509134332,second_phantom_id,third_phantom_id,fourth_phantom_id,fifth_phantom_id
PHANTOMBUSTER_LINKEDIN_POST_COMMENTS_PHANTOM_ID=8329101369849238,second_phantom_id,third_phantom_id,fourth_phantom_id,fifth_phantom_id
PHANTOMBUSTER_LINKEDIN_SEARCH_EXPORT_PHANTOM_ID=5321708808605213,second_phantom_id,third_phantom_id,fourth_phantom_id,fifth_phantom_id
PHANTOMBUSTER_LINKEDIN_PROFILE_SCRAPER_PHANTOM_ID=6026868704588014,second_phantom_id,third_phantom_id,fourth_phantom_id,fifth_phantom_id

# Optional: Workspace lock timeout (seconds to wait for available workspace)
PHANTOMBUSTER_WORKSPACE_LOCK_TIMEOUT=300
```

### Important Notes

1. **Order Matters**: The order of API keys and phantom IDs must match. The first API key uses the first set of phantom IDs, the second API key uses the second set, etc.

2. **Workspace Count**: You can have as many workspaces as you want (recommended: 3-5 for good concurrency).

3. **Backward Compatibility**: If you only provide a single API key (no commas), the system will work in single-workspace mode.

## How It Works

1. **Workspace Acquisition**: When a job needs to use PhantomBuster:
   - The system checks each workspace in round-robin fashion
   - Acquires the first available workspace using a cache lock
   - Uses that workspace's API key and phantom IDs for the operation

2. **Concurrent Processing**: 
   - Multiple jobs can run simultaneously, each using a different workspace
   - If all workspaces are busy, new jobs wait (up to the timeout period)

3. **Workspace Release**: 
   - When an operation completes (success or failure), the workspace is automatically released
   - The next waiting job can then acquire it

4. **Automatic Rotation**: 
   - Workspaces are checked in order (0, 1, 2, 3, 4...)
   - If workspace 0 is busy, it tries workspace 1, then 2, etc.
   - This ensures even distribution of load

## Example Scenario

With 5 workspaces configured:
- User A dispatches job → Acquires Workspace 1
- User B dispatches job → Acquires Workspace 2  
- User C dispatches job → Acquires Workspace 3
- User D dispatches job → Acquires Workspace 4
- User E dispatches job → Acquires Workspace 5
- User F dispatches job → Waits (all workspaces busy)
- When User A's job completes → Workspace 1 is released
- User F's job → Acquires Workspace 1 and starts processing

## Benefits

- **Concurrent Processing**: Multiple users can fetch competitor followers simultaneously
- **No Conflicts**: Each workspace operates independently
- **Automatic Load Balancing**: System automatically distributes jobs across workspaces
- **Fault Tolerance**: If one workspace has issues, others continue working
- **Scalable**: Easy to add more workspaces as your user base grows

## Monitoring

Check logs for workspace acquisition/release:
- `🔓 PhantomBuster: Acquired workspace` - Workspace acquired successfully
- `🔓 PhantomBuster: Released workspace lock` - Workspace released
- `❌ PhantomBuster: All workspaces busy, timeout reached` - All workspaces busy (job will fail)

## Troubleshooting

**Issue**: Jobs stuck in "pending" even with workspaces available
- **Solution**: Check if Horizon timeout matches job timeout (should be 900 seconds)

**Issue**: "All workspaces busy" errors
- **Solution**: Add more workspaces or increase `PHANTOMBUSTER_WORKSPACE_LOCK_TIMEOUT`

**Issue**: Workspace not releasing
- **Solution**: Workspaces auto-release after 30 minutes (safety net), but should release immediately when operations complete
