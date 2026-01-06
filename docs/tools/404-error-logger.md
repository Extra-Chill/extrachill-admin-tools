# 404 Error Logger Tool

## Overview

The 404 Error Logger tool tracks and reports broken links (404 errors) across the entire WordPress multisite network. 

**Version 2.0.0 Update**: The administrative view for the 404 logger has been migrated to a React component (`src/tools/ErrorLogger.jsx`). It provides real-time statistics and summary views of current logged errors.

## 404_log Custom Table Schema

The tool uses a custom `wp_404_log` table (network-wide prefix) to store all error data:

### Table Structure

```sql
CREATE TABLE wp_404_log (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    blog_id INT NOT NULL,
    time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    url varchar(2000) NOT NULL,
    referrer varchar(2000) DEFAULT '' NOT NULL,
    user_agent text NOT NULL,
    ip_address varchar(100) NOT NULL,
    PRIMARY KEY (id),
    INDEX blog_id_idx (blog_id),
    INDEX time_idx (time),
    INDEX url_idx (url(50))
)
```

### Column Descriptions

| Column | Type | Purpose | Notes |
|--------|------|---------|-------|
| `id` | mediumint(9) | Primary key for each log entry | Auto-incrementing, 16M max rows |
| `blog_id` | INT | Identifies which network site the error occurred on | Enables per-site filtering and network-wide tracking |
| `time` | datetime | Timestamp when 404 error occurred | MySQL datetime format, indexed for date-based queries |
| `url` | varchar(2000) | The requested URL that returned 404 | Supports long URLs with query parameters and tracking codes; 1990 char safety limit applied before insertion |
| `referrer` | varchar(2000) | HTTP Referer header (where user came from) | Optional field, helps identify external broken links; same 1990 char safety limit |
| `user_agent` | text | Browser/client user agent string | Identifies browser type and device for analysis |
| `ip_address` | varchar(100) | IP address of requester | Useful for bot vs. human traffic analysis |

### Indexes

- **Primary Key (`id`)**: Unique identifier, auto-increment
- **`blog_id_idx`**: Speeds up per-site filtering in multisite network
- **`time_idx`**: Enables fast date-range queries for daily reports
- **`url_idx`**: Partial index (first 50 chars) for URL pattern matching

## Data Retention Policy

**Default Behavior**: Log entries are automatically deleted after the daily email report is sent.

**Process**:
1. Daily scheduled action triggers at configured time
2. Query all 404 errors from previous 24 hours across all network sites
3. Group errors by URL (deduplicate repeated 404s)
4. Count occurrences per URL
5. Generate and send email report to site admin
6. Delete logged entries from database
7. Reset for next day

**Storage Lifespan**: 24 hours (approximately 1 day maximum)

**Exception**: If email fails to send, entries remain until next successful email delivery attempt.

## Querying 404 Logs

### Get Today's 404 Errors (All Sites)

```php
global $wpdb;
$table_name = $wpdb->base_prefix . '404_log';

$errors = $wpdb->get_results(
    "SELECT url, COUNT(*) as count 
     FROM {$table_name} 
     WHERE DATE(time) = CURDATE() 
     GROUP BY url 
     ORDER BY count DESC"
);

foreach ($errors as $error) {
    echo "{$error->url}: {$error->count} hits\n";
}
```

### Get 404 Errors for Specific Site

```php
$blog_id = 2; // community.extrachill.com

$errors = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT url, COUNT(*) as count, MAX(time) as last_occurred
         FROM {$table_name}
         WHERE blog_id = %d AND DATE(time) = CURDATE()
         GROUP BY url
         ORDER BY count DESC
         LIMIT 20",
        $blog_id
    )
);
```

### Get All Errors in Date Range

```php
$start_date = '2025-01-01';
$end_date = '2025-01-31';

$errors = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT blog_id, url, COUNT(*) as count, MAX(time) as last_hit
         FROM {$table_name}
         WHERE time BETWEEN %s AND %s
         GROUP BY blog_id, url
         ORDER BY blog_id, count DESC",
        $start_date . ' 00:00:00',
        $end_date . ' 23:59:59'
    )
);
```

### Find Broken External Links (By Referrer)

```php
// Identify 404s coming from specific external site
$external_referrer = 'https://external-site.com/article';

$errors = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT url, COUNT(*) as count, MAX(time) as last_hit
         FROM {$table_name}
         WHERE referrer LIKE %s AND DATE(time) = CURDATE()
         GROUP BY url
         ORDER BY count DESC",
        '%' . $wpdb->esc_like($external_referrer) . '%'
    )
);
```

### Most Frequently Hit 404s

```php
$top_errors = $wpdb->get_results(
    "SELECT url, COUNT(*) as total_hits, MAX(time) as last_hit
     FROM {$table_name}
     GROUP BY url
     ORDER BY total_hits DESC
     LIMIT 10"
);
```

## Admin Dashboard Integration

### Enable/Disable Logging

Navigate to **Tools → Admin Tools → 404 Error Logger**:
- Checkbox: "Enable 404 Error Logging (Network-Wide)"
- Displays current count of today's errors
- Save setting affects all network sites

### Data Display

When logging is enabled, the dashboard shows:
- **Current Status**: Enabled/Disabled checkbox
- **Today's Errors**: Live count of 404s in current 24-hour period across all sites
- **Last Report Sent**: Timestamp of most recent daily email report
- **Preview of Top Errors**: Snippet of most frequently occurring 404 URLs

### Manual Log Export

To export 404 logs for analysis:

```bash
# Using WP-CLI
wp db query "SELECT * FROM wp_404_log WHERE DATE(time) = CURDATE()" --csv > errors-today.csv

# Or access via phpMyAdmin:
# 1. Select wp_404_log table
# 2. Click Export tab
# 3. Choose CSV format
# 4. Optionally filter by date range
```

## Triggering Manual Log Exports

### Via WordPress Admin

1. Navigate to **Tools → Admin Tools → 404 Error Logger**
2. View the live counter showing today's errors
3. Copy/paste error URLs into a text document for analysis
4. Delete entries manually if cleanup is needed

### Via WP-CLI

```bash
# Export all 404 errors to CSV
wp db query "SELECT blog_id, time, url, referrer, ip_address \
  FROM wp_404_log \
  WHERE DATE(time) = CURDATE() \
  ORDER BY time DESC" --csv > errors-today.csv

# Export errors for specific site
wp db query "SELECT time, url, COUNT(*) as hits \
  FROM wp_404_log \
  WHERE blog_id = 2 AND DATE(time) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) \
  GROUP BY url ORDER BY hits DESC" --csv > site-2-errors-week.csv

# Delete old entries (older than 30 days)
wp db query "DELETE FROM wp_404_log \
  WHERE DATE(time) < DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
```

### Via phpMyAdmin

1. Login to phpMyAdmin (hosting control panel)
2. Select WordPress database
3. Click **wp_404_log** table
4. Click **Export** tab
5. Select **CSV** format
6. Optional filters:
   - Add WHERE clause: `time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)`
   - Add GROUP BY: `GROUP BY url`
   - Add ORDER BY: `ORDER BY count DESC`
7. Click **Go** to download CSV

## Common Queries for Troubleshooting

### Identify Bot Traffic (High Hit Count on Single URL)

```php
// Find URLs with suspicious hit patterns (possible bot scanning)
$suspicious = $wpdb->get_results(
    "SELECT url, COUNT(*) as hits, COUNT(DISTINCT ip_address) as unique_ips, MAX(user_agent) as sample_ua
     FROM {$table_name}
     WHERE DATE(time) = CURDATE()
     GROUP BY url
     HAVING hits > 50
     ORDER BY hits DESC"
);
```

### Check for Broken Internal Links (Same Site)

```php
// Find 404s where referrer is from same blog
$blog_id = 1;
$site_url = 'https://extrachill.com';

$broken_internal = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT url, COUNT(*) as hits, MAX(time) as last_hit
         FROM {$table_name}
         WHERE blog_id = %d 
         AND referrer LIKE %s
         AND DATE(time) = CURDATE()
         GROUP BY url
         ORDER BY hits DESC",
        $blog_id,
        '%' . $wpdb->esc_like($site_url) . '%'
    )
);
```

### Find Recently Added 404 Errors

```php
// 404s that happened in last hour
$recent = $wpdb->get_results(
    "SELECT DISTINCT url, blog_id, MAX(time) as last_hit
     FROM {$table_name}
     WHERE time >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
     ORDER BY last_hit DESC"
);
```

### Database Size Check

```php
// Check how much space 404_log table uses
$size_info = $wpdb->get_results(
    "SELECT 
        ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)',
        TABLE_ROWS as 'Total Rows'
     FROM information_schema.TABLES
     WHERE TABLE_NAME = '404_log'"
);
```

## Special Behaviors

### URL Filtering

The logger **excludes `/event/` URLs** from logging to avoid cluttering reports with calendar event 404s (Data Machine event plugin integration). This prevents legitimate calendar queries that may trigger 404s from polluting the log.

### URL Truncation

Long URLs with excessive query parameters or tracking codes are safely truncated:
- **Limit**: 1990 characters (10 char buffer below varchar(2000) max)
- **Truncation Marker**: "..." appended when URL exceeds limit
- **Purpose**: Prevents database errors while preserving URL context

Example: `https://example.com/article?utm_source=facebook&utm_medium=post&utm_campaign=...` → `https://example.com/article?utm_source=facebook&utm_medium=post&utm_campaign=...` (truncated)

## Daily Email Report

### Report Format

The daily email includes:
- **Date**: Report date (previous 24 hours)
- **Total Errors**: Count of all 404 occurrences across all sites
- **Top 25 URLs**: Most frequently accessed 404 URLs, ranked by hit count
- **Per-Site Summary**: Breakdown by blog_id with counts
- **Report Generated**: Timestamp of report generation

### Example Report Content

```
Subject: Daily 404 Error Report - January 15, 2025

404 Errors Detected: 127 total hits

TOP 25 BROKEN LINKS:
1. /old-article-url/ - 34 hits
2. /discontinued-product/ - 18 hits
3. /2024/12/deleted-post/ - 12 hits
...

BY SITE:
- extrachill.com (Blog 1): 45 errors
- community.extrachill.com (Blog 2): 28 errors
- shop.extrachill.com (Blog 3): 54 errors

Report generated: 2025-01-15 00:00:15 UTC
```

### Report Delivery

- **Frequency**: Daily at 00:00:00 UTC (configurable via WordPress cron)
- **Recipient**: Site admin email address (`get_option('admin_email')`)
- **Timing**: Runs after log entries are deleted (automatic cleanup)
- **Failure Handling**: Log entries retained if email fails; retry on next scheduled run

## Integration Points

### With Calendar Plugin

The logger excludes `/event/` URLs because the Data Machine calendar plugin may legitimately trigger 404s during event queries. This filtering prevents false positives in reports.

### With Admin Tools

The tool integrates with the admin tools tabbed interface:
- Access via **Tools → Admin Tools → 404 Error Logger**
- Enable/disable via checkbox
- View live count of today's errors
- Administrative logs never counted in report (WP-CLI admin exclusion)

## Security Considerations

- **No Secrets**: IP addresses and user agents logged for debugging, no sensitive data
- **Admin-Only Access**: Tool access restricted to administrators (`manage_options`)
- **Prepared Statements**: All database queries use `$wpdb->prepare()` for SQL injection prevention
- **Input Validation**: URL and referrer truncated and sanitized before database insertion
- **Data Cleanup**: Automatic daily deletion prevents log accumulation
