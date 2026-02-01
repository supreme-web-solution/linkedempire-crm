# PhantomBuster Multi-Workspace Key Rotation Setup

## Overview

This system allows you to use multiple PhantomBuster API keys (workshops) to process multiple jobs concurrently. When one key is in use, the system automatically uses the next available key. If all keys are in use, jobs will wait for a key to become available.

## Configuration

### Environment Variables

In your `.env` file, configure multiple API keys as a comma-separated list:

```env
# Single key (backward compatible)
PHANTOMBUSTER_API_KEY=your_first_key_here

# Multiple keys (comma-separated) - RECOMMENDED
PHANTOMBUSTER_API_KEY=key1,key2,key3,key4,key5

# Phantom IDs (can be the same for all keys, or different per workspace)
PHANTOMBUSTER_LINKEDIN_POST_LIKERS_PHANTOM_ID=7813531509134332
PHANTOMBUSTER_LINKEDIN_SEARCH_EXPORT_PHANTOM_ID=5321708808605213
PHANTOMBUSTER_LINKEDIN_PROFILE_SCRAPER_PHANTOM_ID=6026868704588014
PHANTOMBUSTER_LINKEDIN_POST_COMMENTS_PHANTOM_ID=8329101369849238
```

### Example Configuration

```env
# 5 PhantomBuster workshops/keys
PHANTOMBUSTER_API_KEY=Pj02SzXbQYd8WqDv42EnF3oyZrjVoE4qRgE3sjonzh0,second_key_here,third_key_here,fourth_key_here,fifth_key_here

# Phantom IDs (same for all workshops)
PHANTOMBUSTER_LINKEDIN_POST_LIKERS_PHANTOM_ID=7813531509134332
PHANTOMBUSTER_LINKEDIN_SEARCH_EXPORT_PHANTOM_ID=5321708808605213
PHANTOMBUSTER_LINKEDIN_PROFILE_SCRAPER_PHANTOM_ID=6026868704588014
PHANTOMBUSTER_LINKEDIN_POST_COMMENTS_PHANTOM_ID=8329101369849238
```

## How It Works

1. **Key Acquisition**: When a job needs to use PhantomBuster, it acquires an available API key using a cache lock
2. **Key Rotation**: The system tries keys in order (key 1, then key 2, etc.) until it finds an available one
3. **Lock Management**: Each key has a lock that prevents multiple jobs from using it simultaneously
4. **Automatic Release**: When a job completes, the key lock is automatically released
5. **Waiting**: If all keys are in use, jobs wait up to 5 minutes (configurable) for a key to become available

## Benefits

- **Concurrent Processing**: Process multiple jobs simultaneously (one per key)
- **Automatic Load Balancing**: System automatically distributes jobs across available keys
- **No Manual Intervention**: Keys are acquired and released automatically
- **Backward Compatible**: Still works with a single key configuration

## Key Lock Duration

- **Lock Timeout**: 15 minutes (900 seconds) - how long a key is reserved for a job
- **Wait Timeout**: 5 minutes (300 seconds) - maximum time to wait for a key
- **Lock Release**: Automatic when job completes or fails

## Methods Using Key Rotation

The following methods automatically use key rotation:

- `fetchCompanyPostEngagers()` - Fetches engagers from company posts
- `scrapeLinkedInProfile()` - Scrapes individual LinkedIn profiles
- `fetchPostLikersForUrl()` - Fetches likers for a specific post

## Monitoring

The system logs key acquisition and release:

```
🔑 PhantomBuster: Acquired API key
⏳ PhantomBuster: All API keys in use, waiting for availability...
🔓 PhantomBuster: Released API key lock
```

## Troubleshooting

### All Keys In Use Error

If you see: "All PhantomBuster API keys are currently in use"

- **Solution**: Wait for running jobs to complete, or add more API keys
- **Check**: Review Horizon dashboard to see active jobs
- **Increase**: Add more keys to `PHANTOMBUSTER_API_KEY` in `.env`

### Key Not Released

If a key seems stuck:

- **Check**: Review logs for lock release messages
- **Timeout**: Locks automatically expire after 15 minutes
- **Manual**: Clear cache if needed: `php artisan cache:clear`

## Best Practices

1. **Number of Keys**: Use 3-5 keys for optimal performance
2. **Same Phantom IDs**: Use the same Phantom IDs across all workshops (simpler)
3. **Monitor Usage**: Watch Horizon dashboard to see key utilization
4. **Queue Configuration**: Ensure Horizon has enough workers to process jobs concurrently

## Notes

- Each key represents a separate PhantomBuster workspace
- All keys should have the same phantoms configured
- The system automatically handles key rotation - no manual configuration needed
- Jobs are queued and will wait for an available key if all are in use
