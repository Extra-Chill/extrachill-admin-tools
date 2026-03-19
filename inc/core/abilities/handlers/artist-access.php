<?php
/**
 * Artist access ability handlers — DEPRECATED.
 *
 * Artist access abilities (list, approve, reject) now live in extrachill-users.
 * This file is kept to avoid fatal errors from the require_once in abilities.php
 * but contains no active code. Remove on next admin-tools release.
 *
 * @package ExtraChillAdminTools
 * @deprecated Use extrachill/list-artist-access-requests, extrachill/approve-artist-access,
 *             extrachill/reject-artist-access abilities from extrachill-users instead.
 */

defined( 'ABSPATH' ) || exit;
