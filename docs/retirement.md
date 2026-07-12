# Retirement Runbook

`extrachill-admin-tools` owns no persistence and is retired after its capabilities move to their domain owners. The final package is intentionally inert for one release cycle; it registers no menu, assets, ability category, abilities, hooks, routes, or callbacks.

## Prerequisites

Do not merge or deploy the retirement change until all owner migrations are merged, released, and deployed:

- [Extra-Chill/extrachill-users#173](https://github.com/Extra-Chill/extrachill-users/issues/173): artist access, team management, and lifetime membership.
- [Extra-Chill/extrachill-artist-platform#109](https://github.com/Extra-Chill/extrachill-artist-platform/issues/109): artist-user relationship administration.
- [Extra-Chill/extrachill-network#103](https://github.com/Extra-Chill/extrachill-network/issues/103): QR generation and temporary legacy URL routing.
- [Extra-Chill/extrachill-api#101](https://github.com/Extra-Chill/extrachill-api/issues/101): REST ownership inversions, taxonomy synchronization, and artist-access redirects.
- [Extra-Chill/extrachill-cli#82](https://github.com/Extra-Chill/extrachill-cli/issues/82): QR command owner messaging.

No prerequisite pull request existed when this retirement change was prepared. Record each pull request in the retirement PR before marking it ready.

## Merge And Deployment Order

1. Merge, release, and deploy `extrachill-users#173`, `extrachill-artist-platform#109`, and `extrachill-network#103`.
2. Merge, release, and deploy `extrachill-api#101` after its owner-level primitives are available.
3. Merge, release, and deploy `extrachill-cli#82` after Network owns `extrachill/generate-qr-code`.
4. Run the disablement matrix below with Admin Tools active, then with it network-deactivated.
5. Merge, release, and deploy `extrachill-admin-tools#17` as the inert final package.
6. Network-deactivate Admin Tools, repeat the matrix, wait one release cycle, then remove the deployed package and archive this repository.

## Removed Contracts

The retirement change removes Admin Tools ownership of:

- Network menu slug/page `extrachill-admin-tools` and React mount `extrachill-admin-tools-root`.
- Script/style handle `extrachill-admin-tools` and localized object `ecAdminToolsConfig`.
- Ability category `extrachill-admin-tools`.
- Abilities `extrachill/grant-lifetime-membership`, `extrachill/revoke-lifetime-membership`, `extrachill/sync-team-members`, `extrachill/manage-team-member`, `extrachill/sync-taxonomies`, and `extrachill/generate-qr-code`.
- All `extrachill_admin_tools_*` functions and handlers.
- QR generation implementation and its Endroid runtime path.
- The six-tab UI source, shared UI components, API client, webpack configuration, npm package, and build script.

The ability names and public REST/CLI response contracts are not retired. The prerequisite owner changes must preserve them before this change can merge.

## Retained Compatibility

- `extrachill-admin-tools.php` remains as an inert WordPress plugin entry point for a final upgrade and orderly deactivation.
- `EXTRACHILL_ADMIN_TOOLS_VERSION` remains solely as the Homeboy-managed release target.
- Historical release notes remain unchanged.
- Temporary routing for old `page=extrachill-admin-tools` URLs belongs to `extrachill-network#103`; this shell does not recreate it.

## Disablement Test Matrix

Run each check first with Admin Tools active and then network-deactivated. Results must be equivalent.

| Area | Verification | Expected result |
| --- | --- | --- |
| Ability ownership | `wp ability list --fields=name` filtered to the six migrated ability names | All six names appear exactly once and report domain-owner registration. |
| QR REST | Authenticated `POST /wp-json/extrachill/v1/tools/qr-code` with a valid URL | HTTP status and base64 PNG response match the pre-retirement contract. |
| QR CLI | `wp extrachill tools qr generate https://extrachill.com --output=/tmp/extrachill-retirement-qr.png` | PNG is written successfully without Admin Tools owner messaging. |
| Team sync | Execute `extrachill/sync-team-members` as an authorized network operator | Existing result keys and role behavior are preserved. |
| Team management | Execute `extrachill/manage-team-member` for grant and revoke against a test user | `extra_chill_team` role changes network-wide with the existing response shape. |
| Membership | Execute grant and revoke abilities against a test user | Membership metadata and response shapes remain compatible. |
| Taxonomy sync | Call the existing taxonomy REST route with valid taxonomy and target-site values | Sync completes without a REST-to-ability ownership loop. |
| Artist access | List requests and exercise approval/rejection against test data | Workflow succeeds and redirects target an owner-native screen. |
| Artist relationships | List, link, unlink, find orphans, and clean a test relationship | Owner-native workflow succeeds with no Admin Tools runtime dependency. |
| Legacy navigation | Open the old network-admin `page=extrachill-admin-tools` URL | Network-owned temporary redirect reaches a valid owner-native destination. |
| Runtime errors | Inspect PHP and application logs after the matrix | No missing function, class, callback, asset, route, or duplicate-ability errors. |

## Repository Verification

Run from the repository root:

```bash
php -l extrachill-admin-tools.php
composer run lint:php
git grep -nE 'wp_register_ability|add_(sub)?menu_page|wp_enqueue_(script|style)|extrachill_admin_tools_' -- ':!docs/CHANGELOG.md' ':!docs/retirement.md'
git ls-files 'src/**' 'inc/**' 'package.json' 'webpack.config.js' 'build.sh'
```

The grep and file inventory commands must return no output.
