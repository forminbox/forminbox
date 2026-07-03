# WordPress.org Submission Checklist

> GitHub is the development repository. WordPress.org SVN is the release
> repository after approval.

## Before submitting

- [ ] **Confirm slug and name.** Requested slug: `forminbox`, display name
      "FormInbox". The slug is assigned permanently at review time — verify it is
      still available and matches the plugin's text domain (`forminbox`) and
      main file (`forminbox.php`).
- [ ] **Validate `readme.txt`** with the official validator
      (https://wordpress.org/plugins/developers/readme-validator/): headers,
      `Stable tag`, `Tested up to` current, changelog present, no markdown-only
      syntax that the wp.org parser mangles.
- [ ] **Quality gates** (all must pass — see README for the Docker equivalents
      if the host has no PHP/Node):
  - [ ] `composer check` (PHPCS, PHPStan level 8, unit tests)
  - [ ] `npm run typecheck`
  - [ ] `npm run lint:js`
  - [ ] `npm run build`
  - [ ] `npm run test:php` (where wp-env is available)
- [ ] **Create the release ZIP from a tag** (`git tag vX.Y.Z && git push origin vX.Y.Z`)
      and let `release.yml` produce it — it enforces version sync, `.distignore`
      leak checks, and a clean-install smoke test. Do not hand-roll the ZIP.
- [ ] **Test the ZIP on a clean WordPress install** (the workflow does
      install/activate/schema automatically; also run the human checklist in
      `docs/RELEASING.md` — block editor flow, submissions with and without JS,
      inbox, uninstall toggle in both positions).

## Plugin directory assets (not part of the ZIP)

These live in the SVN `assets/` directory, not in the plugin. Prepare before
or right after approval; the Screenshots section in `readme.txt` (numbered
captions) must match the `screenshot-N.png` files.

| File | Purpose |
|---|---|
| `assets/icon-128x128.png` | Directory icon (small) |
| `assets/icon-256x256.png` | Directory icon (retina) |
| `assets/banner-772x250.png` | Plugin page banner (a 1544x500 retina variant is optional) |
| `assets/screenshot-1.png` | Forms list |
| `assets/screenshot-2.png` | Form editor |
| `assets/screenshot-3.png` | Lead inbox with status filters |
| `assets/screenshot-4.png` | Lead detail |
| `assets/screenshot-5.png` | Settings screen |

No placeholder images are committed to this repo; produce final PNGs from a
seeded site (`bin/seed.php`) at the exact dimensions above.

## Submission and after approval

- [ ] Submit the tagged release ZIP through
      https://wordpress.org/plugins/developers/add/ and respond to reviewer
      feedback from the plugins team (initial review commonly takes days to weeks).
- [ ] After approval, publish through **WordPress.org SVN**: check out the
      assigned SVN repo, copy the ZIP contents into `trunk/`, upload directory
      assets to `assets/`, tag the release under `tags/X.Y.Z/`, and confirm
      `Stable tag` in `trunk/readme.txt` points at it.
- [ ] Add the SVN deploy step to `release.yml` afterwards (ARCHITECTURE §9
      anticipates this) so GitHub tags and SVN releases cannot drift.

## Review-readiness notes (what reviewers look for)

- All output escaped at render time; direct DB queries only through the
  repository classes with `$wpdb->prepare()` — both enforced by PHPCS/PHPStan
  in CI.
- Public REST endpoint is intentionally unauthenticated (submissions) and
  hardened server-side; every other route has an explicit capability check.
- No external services, no tracking, no bundled PHP runtime dependencies
  (Composer autoloader only). Raw visitor IPs are never stored.
- Uninstall is destructive only with the explicit opt-in setting.
