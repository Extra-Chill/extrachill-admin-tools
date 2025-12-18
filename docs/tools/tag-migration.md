# Tag Migration Tool

## Overview

The Tag Migration tool provides bulk conversion of existing post tags into one of three specialized taxonomies: festival, artist, or venue taxonomies. This tool is essential when restructuring content organization or migrating from a flat tagging system to a more granular taxonomy system.

## Purpose & Scope

**Use Cases**:
- Converting legacy post tags into proper festival taxonomies
- Migrating artist tags to the dedicated artist taxonomy for better platform organization
- Reorganizing venue information from tags into proper venue taxonomy
- Consolidating related tags during content restructuring
- Gutenberg migration when shifting content models

**What It Does**:
- Queries all existing post tags (regardless of post type)
- Provides searchable, paginated interface for tag selection
- Migrates selected tags to the target taxonomy
- Reports results with breakdown of successful and failed migrations
- Preserves all posts and their associations during migration

**What It Doesn't Do**:
- Delete the original tags (they're converted, not removed)
- Merge duplicate terms automatically
- Modify post content or metadata (only taxonomy assignments change)
- Validate tag/taxonomy name conflicts (manual review recommended)

## Migration Process

### 1. Access the Tool

Navigate to **Tools → Admin Tools → Tag Migration** in WordPress admin.

### 2. Select Target Taxonomy

Choose from three available taxonomies:
- **Festival**: For festival-related content organization
- **Artist**: For artist-specific content and references
- **Venue**: For venue-related content and information

### 3. Search & Filter Tags

The interface displays all existing post tags in a searchable list with pagination (20 tags per page):
- Type a tag name to filter results
- Browse through paginated list
- Tags are displayed with their post count for context

### 4. Select Tags for Migration

Check the checkboxes next to tags you want to migrate. The tool supports:
- Selecting individual tags
- Bulk selection via "Select All" on current page
- Deselecting tags as needed

### 5. Confirm Migration

Click the **Migrate Tags** button. The tool displays:
- A confirmation dialog with the number of tags selected
- The target taxonomy being migrated to
- Total posts affected by the migration

### 6. Review Results

After migration, the results section shows:
- **Successfully Migrated**: Count of tags converted to the target taxonomy
- **Already Exist**: Count of tags that already existed in target taxonomy (merged with existing terms)
- **Failed**: Count of tags that couldn't be migrated (with error details)
- **Total Posts Updated**: Total posts affected by the migration

## Database Schema Changes

The migration process modifies the following WordPress tables:

### wp_term_taxonomy
- **Modified**: `taxonomy` column updated from 'post_tag' to target taxonomy
- **Preserved**: `term_id`, `description`, `parent` remain unchanged
- **Operation**: UPDATE statement reassigns taxonomy without data loss

### wp_posts
- **Preserved**: No direct post modifications
- **Implicit**: Posts retain all associations through term_relationship table

### wp_term_relationships
- **Unchanged**: Row links between posts and terms remain intact
- **Effect**: Posts automatically associated with migrated terms in new taxonomy

### wp_postmeta
- **Unchanged**: All post metadata preserved

## Data Validation Before Migration

**Pre-Migration Checks**:
1. Verify selected tags exist in database
2. Confirm target taxonomy is registered
3. Check for taxonomy permission conflicts
4. Validate no duplicate term names in target taxonomy
5. Count total posts affected

**Recommendations**:
- Back up database before large migrations
- Test migration on 5-10 tags first to validate process
- Review results for any failed migrations before proceeding
- Cross-reference post counts before and after migration

## Error Handling & Recovery

### Common Errors

**"Target taxonomy does not exist"**
- Verify target taxonomy is registered via plugin activation
- Check that the taxonomy plugin is active on current site

**"Duplicate term name exists in target taxonomy"**
- Two tags migrating to same term name in target taxonomy
- Resolve by renaming one tag before migration or manually merging in target taxonomy

**"Database constraint violation"**
- Rare: occurs if term_id collision or database integrity issue
- Workaround: Clean up orphaned terms via database admin tool

### Rollback Procedure

**Manual Rollback**:

1. Access WordPress database admin (phpMyAdmin)
2. Restore `wp_term_taxonomy.taxonomy` column:
   ```sql
   UPDATE wp_term_taxonomy 
   WHERE term_id IN (SELECT id FROM wp_terms WHERE name IN ('migrated_term_names'))
   SET taxonomy = 'post_tag';
   ```
3. Verify post counts match pre-migration baseline
4. Test posts display correct terms

**Backup-Based Rollback** (if available):
1. Stop WordPress
2. Restore database from pre-migration backup
3. Verify data integrity
4. Resume WordPress

## When to Use

**Ideal Scenarios**:
- Implementing new content taxonomy structure
- Consolidating multiple tagging systems into single taxonomy
- Shifting from post_tag to custom taxonomy for organizational clarity
- Supporting Gutenberg migration where block editors expect specific taxonomies
- Restructuring content organization for improved UX

**Not Recommended For**:
- Merging entirely different content types
- Migrating tags with semantic meaning to unrelated taxonomy
- Large-scale migrations without proper backup
- Converting tag data that provides unique context

## Example Scenarios with Expected Outcomes

### Scenario 1: Festival Content Migration
**Situation**: Blog has 150 posts tagged with festival names (Burning Man, Lightning in a Bottle, Coachella) using regular post_tag taxonomy. Platform needs festival taxonomy for filtering and organization.

**Process**:
1. Select target taxonomy: Festival
2. Search for festival tags (shows ~30 tags)
3. Select all festival-related tags
4. Migrate 30 tags to festival taxonomy

**Outcomes**:
- 150 posts reassigned from post_tag → festival taxonomy
- Festival taxonomy now has 30 terms
- Posts accessible via `/festival/burning-man/` archives
- Tag taxonomy still exists (orphaned) - can be manually cleaned
- No post content modified, all associations preserved

### Scenario 2: Artist Reference Migration
**Situation**: Multiple post tags reference artist names (The Weeknd, Tame Impala, etc.). Blog also has artist custom post type with dedicated artist taxonomy. Need to migrate tag references to artist taxonomy.

**Process**:
1. Filter tags: search "The" to find artist-related tags
2. Select relevant artist name tags
3. Choose target taxonomy: Artist
4. Migrate selected tags

**Outcomes**:
- 45 artist name tags converted to artist taxonomy
- Posts now properly linked to artist taxonomy entries
- Artist profile pages can query these associations
- Cross-site artist platform integration now possible
- Original tag references removed from post_tag

### Scenario 3: Partial Migration with Failures
**Situation**: Migrating 50 venue-related tags, but 5 tags have name conflicts with existing venue taxonomy terms.

**Process**:
1. Select all 50 venue tags
2. Attempt migration
3. Tool reports: 45 successful, 5 duplicates (already exist in target)

**Outcomes**:
- 45 tags successfully migrated
- 5 duplicate-named tags merged with existing venue terms
- Posts linked to existing terms automatically
- Total: 45 + 5 = 50 tags successfully converted
- Migration complete despite conflicts (expected behavior)

## Best Practices

1. **Backup First**: Always backup database before large migrations
2. **Test Small**: Migrate 5-10 tags first to validate process
3. **Review Taxonomy**: Examine target taxonomy before migration
4. **Post-Migration Check**: Review posts in target taxonomy archives
5. **Cleanup Orphaned Tags**: Remove unused post_tag entries after migration
6. **Document Changes**: Record which tags migrated to which taxonomy
7. **Notify Team**: Alert content editors about taxonomy structure changes
