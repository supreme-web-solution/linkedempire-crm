# 🕐 Cron Jobs & Commands Setup Guide

This document lists all the scheduled tasks, queue workers, and commands that need to be set up for the application to run properly.

---

## 📋 **REQUIRED CRON JOB**

Add this **single cron job** to your server's crontab. It runs Laravel's scheduler every minute:

```bash
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

**For Windows (using Task Scheduler):**
- Create a task that runs every minute
- Command: `php artisan schedule:run`
- Working directory: Your project path

**For Laragon (Local Development):**
- Use Windows Task Scheduler or a tool like Laragon's built-in scheduler

---

## ⏰ **SCHEDULED COMMANDS** (Auto-run via Scheduler)

These commands are automatically scheduled in `routes/console.php` and will run based on their schedule:

### 1. **Post Scheduler** 
- **Command:** `php artisan app:post-scheduler`
- **Schedule:** Every minute
- **Purpose:** Publishes scheduled LinkedIn posts when their time arrives
- **Status:** ✅ Active

### 2. **Update Post Analytics**
- **Command:** `php artisan app:update-post-analytics`
- **Schedule:** Every hour
- **Purpose:** Updates engagement metrics (likes, comments, shares, views) for published LinkedIn posts
- **Status:** ✅ Active

### 3. **Fetch LinkedIn Feeds** (Inspiration Library)
- **Command:** `php artisan app:fetch-linkedin-feeds`
- **Schedule:** Twice daily at 12:15 PM and 6:15 PM
- **Purpose:** Fetches viral LinkedIn posts for the Inspiration Library page
- **Status:** ✅ Active

### 4. **Call Reminders**
- **Command:** `php artisan calls:send-reminders`
- **Schedule:** Every 15 minutes
- **Purpose:** Sends automated call reminders (16-24 hours before, 2 hours before, 10-40 minutes before)
- **Status:** ✅ Active

### 5. **Process Auto Comments**
- **Command:** `php artisan app:process-auto-comments`
- **Schedule:** Every hour
- **Purpose:** Fetches posts, generates AI comments, and schedules/posts them automatically
- **Status:** ✅ Active

### 6. **Flush Pending Email Batches**
- **Command:** `php artisan email:flush-pending-batches`
- **Schedule:** Every 5 minutes
- **Purpose:** Processes pending email fetch batches that have been waiting (batches with less than 20 items after 5 minutes)
- **Status:** ✅ Active

### 7. **Reset Daily Email Scraping Counts**
- **Command:** `php artisan email:reset-daily-scraping-counts`
- **Schedule:** Daily at 00:00 (midnight)
- **Purpose:** Resets daily email scraping counts for all users at the start of each new day
- **Status:** ✅ Active

---

## 🚀 **LARAVEL HORIZON** (Recommended for Production)

The application uses **Laravel Horizon** for queue management and monitoring. Horizon provides a dashboard and automatically manages queue workers.

### **Starting Horizon:**

**For Local Development:**
```bash
php artisan horizon
```

**For Production (with Supervisor):**

Create supervisor config `/etc/supervisor/conf.d/linkdominator-horizon.conf`:

```ini
[program:linkdominator-horizon]
process_name=%(program_name)s
command=php /path/to/your/project/artisan horizon
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/horizon.log
stopwaitsecs=3600
```

Then:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start linkdominator-horizon
```

### **Restarting Horizon (IMPORTANT):**

**After changing Horizon configuration** (`config/horizon.php`), you **MUST** restart Horizon:

```bash
# Graceful restart (waits for current jobs to finish)
php artisan horizon:terminate

# Then start again
php artisan horizon
```

**Or if using Supervisor:**
```bash
sudo supervisorctl restart linkdominator-horizon
```

**⚠️ CRITICAL:** If jobs are stuck in "pending" after config changes, restart Horizon immediately!

### **Horizon Dashboard:**

Access the Horizon dashboard at: `http://your-domain.com/horizon`

**Configuration:**
- `supervisor-1`: Handles `default` queue with 10 workers, 900s timeout (15 minutes)
- `supervisor-phantombuster`: Handles `phantombuster` queue with 5 workers, 600s timeout (10 minutes)

---

## 🔄 **QUEUE WORKER** (Alternative - If NOT using Horizon)

If you're **NOT using Horizon**, you **MUST** run queue workers manually:

### **Start Queue Workers:**

**For Local Development (Laragon/Windows):**

Open **TWO** terminal windows and run:

**Terminal 1 - Default Queue:**
```bash
php artisan queue:work --queue=default
```

**Terminal 2 - PhantomBuster Queue (for batch email fetching):**
```bash
php artisan queue:work --queue=phantombuster
```

**OR run both queues in one worker:**
```bash
php artisan queue:work --queue=default,phantombuster
```

### **For Production (with Supervisor):**

Create supervisor config files:

**1. Default Queue Worker** `/etc/supervisor/conf.d/linkdominator-queue.conf`:

```ini
[program:linkdominator-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work --queue=default --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/queue-worker.log
stopwaitsecs=3600
```

**2. PhantomBuster Queue Worker** `/etc/supervisor/conf.d/linkdominator-queue-phantombuster.conf`:

```ini
[program:linkdominator-queue-phantombuster]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work --queue=phantombuster --sleep=3 --tries=2 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/queue-phantombuster-worker.log
stopwaitsecs=3600
```

Then:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start linkdominator-queue:*
sudo supervisorctl start linkdominator-queue-phantombuster:*
```

### **Queue Jobs Used:**

1. **FetchAudienceEmailJob** - Fetches email addresses for LinkedIn profiles via PhantomBuster (uses `default` queue)
2. **FetchAudienceEmailBatchJob** - Batch email fetching (up to 20 profiles) (uses `phantombuster` queue) ⚠️ **REQUIRES `phantombuster` QUEUE WORKER**
3. **FetchCompetitorFollowersJob** - Fetches competitor followers data (uses `default` queue)
4. **PublishLinkedInPost** - Publishes scheduled LinkedIn posts (uses `default` queue)

---

## 🛠️ **MANUAL COMMANDS** (Optional - Run as needed)

These commands are not scheduled but can be run manually when needed:

### **Fetch Missing Audience Emails**
```bash
# Fetch emails for all audience items without emails (limit 50)
php artisan audience:fetch-missing-emails

# For specific audience
php artisan audience:fetch-missing-emails --audience-id=123

# Custom limit and batch size
php artisan audience:fetch-missing-emails --limit=100 --batch-size=20
```

### **Process Pending Messages**
```bash
php artisan app:process-pending-messages
```
*Note: This command is currently empty/stub - may need implementation*

### **List PhantomBuster Phantoms** (Debug)
```bash
php artisan phantom:list-phantoms
```

### **Playground** (Development)
```bash
php artisan playground
```

---

## 📊 **QUEUE CONFIGURATION**

**For Horizon (Recommended):**
The application uses **Redis queue** with Horizon. Make sure your `.env` has:

```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

**If NOT using Horizon:**
The application can use **database queue** by default. Make sure your `.env` has:

```env
QUEUE_CONNECTION=database
```

**To use Redis instead:**
```env
QUEUE_CONNECTION=redis
```

**Make sure to run migrations for jobs table:**
```bash
php artisan migrate
```

---

## ✅ **SETUP CHECKLIST**

- [ ] Add cron job: `* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1`
- [ ] **Start Horizon:** `php artisan horizon` (or set up Supervisor) **OR** start queue worker: `php artisan queue:work` (if not using Horizon)
- [ ] Verify `.env` has `QUEUE_CONNECTION=redis` (for Horizon) or `QUEUE_CONNECTION=database` (for manual workers)
- [ ] Run migrations: `php artisan migrate`
- [ ] Test scheduler: `php artisan schedule:list` (shows all scheduled tasks)
- [ ] Test queue: `php artisan queue:work --once` (processes one job) or check Horizon dashboard

---

## 🧪 **TESTING COMMANDS**

### **List All Scheduled Tasks:**
```bash
php artisan schedule:list
```

### **Run Scheduler Manually (for testing):**
```bash
php artisan schedule:run
```

### **Test Individual Commands:**
```bash
# Test post scheduler
php artisan app:post-scheduler

# Test analytics update
php artisan app:update-post-analytics

# Test LinkedIn feeds
php artisan app:fetch-linkedin-feeds

# Test call reminders
php artisan calls:send-reminders

# Test auto comments
php artisan app:process-auto-comments

# Test flush pending email batches
php artisan email:flush-pending-batches

# Test reset daily scraping counts
php artisan email:reset-daily-scraping-counts
```

### **Check Queue Status:**
```bash
# See failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

---

## 📝 **NOTES**

1. **Scheduler runs every minute** - This is normal. Laravel's scheduler checks which tasks need to run and executes them.

2. **Queue worker must run continuously** - If the queue worker stops, background jobs (email fetching, post publishing) will not process.

3. **Database queue** - Jobs are stored in the `jobs` table. Make sure this table exists (created by migrations).

4. **Production recommendations:**
   - Use Supervisor or systemd to keep queue worker running
   - Monitor queue worker logs
   - Set up queue retry limits
   - Consider using Redis for better performance with high job volume

5. **Local development:**
   - You can run `php artisan queue:work` in a separate terminal
   - Or use `php artisan queue:listen` (slower but auto-restarts on code changes)

---

## 🚨 **TROUBLESHOOTING**

### **Scheduler not running?**
- Check cron job is installed: `crontab -l`
- Check Laravel logs: `storage/logs/laravel.log`
- Test manually: `php artisan schedule:run`

### **Queue jobs not processing?**
- **If using Horizon:**
  - Check Horizon is running: `ps aux | grep horizon`
  - Check Horizon dashboard: `http://your-domain.com/horizon`
  - **Restart Horizon after config changes:** `php artisan horizon:terminate && php artisan horizon`
  - Check Horizon logs: `storage/logs/horizon.log`
- **If using manual workers:**
  - Check queue worker is running: `ps aux | grep queue:work`
  - Check failed jobs: `php artisan queue:failed`
  - Check database connection
  - Verify `jobs` table exists

### **Jobs stuck in "pending" in Horizon?**
- **This usually means Horizon workers need to be restarted after config changes**
- Run: `php artisan horizon:terminate` then `php artisan horizon`
- Or if using Supervisor: `sudo supervisorctl restart linkdominator-horizon`
- Verify timeout settings in `config/horizon.php` match job timeouts
- Check that `QUEUE_CONNECTION=redis` in `.env`

### **Jobs failing?**
- Check logs: `storage/logs/laravel.log`
- Check failed jobs table: `php artisan queue:failed`
- Review job implementation for errors

---

**Last Updated:** 2026-01-14
**File Location:** `routes/console.php` (scheduled tasks)
