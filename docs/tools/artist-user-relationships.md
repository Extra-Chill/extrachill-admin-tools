# Artist-User Relationships Tool

## Overview

The Artist-User Relationships tool manages the mappings between user accounts and artist profiles. This tool is essential for maintaining data integrity in the artist platform where users can be linked to multiple artist profiles and artists can have multiple team members.

**Requirements**: extrachill-artist-platform plugin must be active

## Relationship Mapping Architecture

### Data Structure

The relationship is stored bidirectionally:
- **Artist post type**: `artist_profile`
- **Artist post meta**: `_artist_member_ids` (array of user IDs)
- **User meta**: `_artist_profile_ids` (array of artist_profile post IDs)

### Relationship Flow

```
User (wp_users)
  ├─ user_id (primary key)
  ├─ user_email
  └─ User Meta (wp_usermeta)
      └─ artist associations (if any)

       ↓ links to ↓

Artist Profile (wp_posts)
  ├─ post_id (artist_profile post type)
  ├─ post_author (original creator)
  ├─ post_title (artist name)
  └─ Post Meta (wp_postmeta)
      └─ _artist_members (array of user IDs)
```

## Admin Interface

### Three Management Views

Navigate to **Tools → Admin Tools → Artist-User Relationships** to access:

#### 1. Artists View (Default)
Lists all artist profiles with:
- Artist name and post ID
- Number of linked team members
- Link/unlink interface
- Search by artist name
- Actions: Add member, view members, manage access

#### 2. Users View
Lists all users with:
- User name and ID
- Artist profiles linked to user
- Member status (team member, owner, etc.)
- Search by username/email
- Actions: Link to artist, unlink from artist, manage roles

#### 3. Orphans View
Identifies data integrity issues:
- **Orphaned Posts**: Artist profiles with missing post_author
- **Orphaned Relationships**: User IDs linked to non-existent artists
- **Orphaned Users**: User accounts referenced in artist meta but deleted
- Provides cleanup/repair actions for each issue type

## REST API Calls

The admin UI uses REST routes registered in the network-activated `extrachill-api` plugin.

> The JavaScript for this tool calls these endpoints via `ecAdminTools.restUrl` and sends the nonce as `X-WP-Nonce`.

### Add User to Artist

**Request**:
```http
POST /wp-json/extrachill/v1/users/{user_id}/artists
Content-Type: application/json
X-WP-Nonce: <nonce>

{"artist_id":456}
```

**Response (Success)**:
```json
{
  "success": true,
  "message": "Artist relationship added.",
  "user_id": 123,
  "artist_id": 456
}
```

### Remove User from Artist

**Request**:
```http
DELETE /wp-json/extrachill/v1/users/{user_id}/artists/{artist_id}
X-WP-Nonce: <nonce>
```

### Validation Checks

Before adding relationship, tool validates:
1. User exists in `wp_users` table
2. Artist profile exists (post type = artist_profile)
3. Relationship doesn't already exist
4. User has permission to manage artists
5. Artist isn't already at max team member limit (if configured)

## Database Operations

### Get All Members of Artist

```php
$artist_id = 456;
$members = get_post_meta( $artist_id, '_artist_member_ids', true );
// Returns: [123, 124, 125] (array of user IDs)
```

### Get All Artists for User

```php
$user_id = 123;
$user_artists = get_posts(array(
    'post_type'      => 'artist_profile',
    'post__in'       => (array) get_user_meta( $user_id, '_artist_profile_ids', true ),
    'posts_per_page' => -1,
));
// Returns: WP_Post array of artist profiles user is linked to
```

### Detect Orphaned Relationships

```php
global $wpdb;

// Find user IDs referenced in artist meta that don't exist in wp_users
$orphaned_users = $wpdb->get_results(
    "SELECT DISTINCT pm.meta_value as user_id
FROM {$wpdb->postmeta} pm
     WHERE pm.meta_key = '_artist_member_ids'
      AND pm.meta_value NOT IN (SELECT ID FROM {$wpdb->users})"

);
```

### Detect Orphaned Artist Posts

```php
// Find artist_profile posts with deleted authors
$orphaned_artists = get_posts(array(
    'post_type' => 'artist_profile',
    'posts_per_page' => -1,
    'post__in' => $wpdb->get_col(
        "SELECT p.ID FROM {$wpdb->posts} p
         WHERE p.post_type = 'artist_profile'
         AND p.post_author NOT IN (SELECT ID FROM {$wpdb->users})"
    )
));
```

## Data Sync Patterns

### With bbPress (Community Integration)

When a user is linked to an artist:
1. Check if artist has associated bbPress forum
2. If forum exists, add user as forum moderator/participant
3. If forum missing, can optionally create new forum
4. Sync user roles and capabilities

### Automatic Sync on User Delete

When a user is deleted from WordPress:
1. Remove all artist_members references to user
2. If user was artist post_author, reassign to admin
3. Notify other team members of role change
4. Update user profile links

## Permission & Capability Checks

### Function: `is_user_member_of_artist()`

```php
/**
 * Check if user is member of artist team
 * @param int $user_id
 * @param int $artist_id
 * @return bool
 */
function is_user_member_of_artist($user_id, $artist_id) {
    $members = get_post_meta($artist_id, '_artist_members', true);
    return in_array($user_id, (array)$members);
}
```

### Managing User Roles

**Team Member**: Can edit artist profile, manage team content
**Manager**: Can add/remove team members, modify settings
**Owner**: Full control, can delete artist profile
**Viewer**: Can view but not edit (for collaborative artists)

## Common Issues & Fixes

### Issue: Orphaned Forum Posts

**Symptom**: Artist profile exists but related bbPress forum posts are orphaned (post_author deleted)

**Detection**:
1. Open Orphans view
2. Look for "Orphaned bbPress Posts" section
3. Shows post count per artist

**Fix**:
1. Click "Repair" button in Orphans view
2. Tool reassigns all forum posts to current artist owner
3. Notification sent to artist managers

### Issue: Permission Conflicts

**Symptom**: User can't edit artist profile despite being linked

**Causes**:
- User doesn't have 'edit_artist_profiles' capability
- Artist owner is deleted (post_author invalid)
- User has 'Subscriber' role (insufficient permissions)

**Fix**:
1. Verify user has 'Artist Member' or higher role
2. Reassign artist post_author if owner deleted
3. Update user capabilities via Users admin

### Issue: Duplicate Relationships

**Symptom**: Same user appears multiple times in artist's member list

**Detection**: Orphans view shows "Duplicate Member Entries"

**Fix**:
1. Open Orphans view
2. Click "Clean Duplicates"
3. Tool removes duplicate user IDs from member meta

## Bulk Operations

### Bulk Add Users to Artist

1. Select target artist from Artists view
2. Click "Bulk Add Members"
3. Paste comma-separated user IDs or emails
4. Tool validates all users before adding
5. Confirmation dialog shows count
6. Click "Confirm" to add all at once

### Bulk Remove User from All Artists

1. Select user from Users view
2. Click "Unlink from All Artists"
3. Tool removes user from all artist_members arrays
4. Confirmation shows affected artist count
5. Optional: Reassign artist posts to admin

## Troubleshooting

### Relationships Not Updating

**Check**:
1. Verify artist plugin is active: `post_type_exists('artist_profile')`
2. Check user has `manage_options` capability
3. Verify REST nonce is sent as `X-WP-Nonce`
4. Check JavaScript console/network tab for REST errors

### Members Not Appearing After Link

**Debug**:
```php
// Verify relationship is stored
$artist_id = 456;
$members = get_post_meta($artist_id, '_artist_members', true);
error_log('Members: ' . print_r($members, true));

// Check if sync to bbPress failed
// Verify forum exists for artist
$forum_id = get_post_meta($artist_id, '_artist_forum_id', true);
```

## Best Practices

1. **Regular Audits**: Run Orphans view monthly to detect data issues
2. **Backup Before Bulk Changes**: Use manual backup before bulk operations
3. **Notify Team**: Alert users when their artist membership changes
4. **Consistent Roles**: Use standardized role assignments (Team Member, Manager, Owner)
5. **Forum Sync**: Keep bbPress integration enabled for proper cross-system linking
