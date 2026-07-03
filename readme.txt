=== FormInbox ===
Contributors: forminbox
Tags: form, contact form, leads, lead management, crm
Requires at least: 6.6
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Standalone form and lead management — create forms, capture leads with source context, and track follow-up status.

== Description ==

FormInbox is a standalone form and lead management plugin. It does not depend on any other form plugin: FormInbox owns the whole flow, from building the form to tracking the follow-up.

* **Build forms** with text, email, and paragraph fields in a fast React admin.
* **Embed anywhere** with the FormInbox Form block or the `[forminbox id="…"]` shortcode.
* **Lightweight by design**: the public page loads zero React — a server-rendered form plus a ~2 KB enhancement script, and the form still works with JavaScript disabled.
* **Every lead lands in your inbox** with its source context: the page URL and title it came from, the referrer, and the submission time.
* **Track the follow-up**: statuses (new, contacted, qualified, won, lost, spam) and internal notes with author attribution.
* **Spam resistance built in**: honeypot, signed time-trap token (cache-safe), and rate limiting — no third-party service.
* **Privacy-aware**: visitor IP addresses are never stored, only a keyed hash used for rate limiting.

Email notifications are not included yet — leads are collected in the FormInbox inbox inside wp-admin (notifications are the first item on the 0.2 roadmap).

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/forminbox` directory, or install the plugin through the WordPress plugins screen.
2. Activate FormInbox through the Plugins screen in WordPress.
3. Go to **FormInbox** in the WordPress admin menu.
4. Create a form, then embed it with the **FormInbox Form** block or the `[forminbox id="…"]` shortcode.
5. View submitted leads in the FormInbox inbox.

== Frequently Asked Questions ==

= Does it work with my page builder? =

Yes — anywhere shortcodes work, `[forminbox id="…"]` works. Block themes and the block editor get a native FormInbox Form block.

= What happens to my data if I delete the plugin? =

Nothing, by default. Forms, leads, and notes survive uninstall unless you explicitly enable "Delete all data on uninstall" in FormInbox → Settings.

= Does it send email notifications? =

Not yet — that is the first item on the 0.2 roadmap. Leads live in the FormInbox inbox in wp-admin.

== Development ==

The development source, build tools, and tests are maintained publicly at:
https://github.com/forminbox/forminbox

The WordPress.org release ZIP contains the built plugin assets. To build from source, see the repository README.

== Screenshots ==

1. FormInbox forms list.
2. Form editor with text, email, and paragraph fields.
3. Lead inbox with status filters.
4. Lead detail view with submitted fields, source context, status, and notes.
5. Settings screen with the uninstall data-deletion option.

== Changelog ==

= 0.1.0 =
* Initial release: form builder (text/email/paragraph fields), block + shortcode embedding, server-rendered public form with progressive enhancement, spam guards (honeypot, time-trap token, rate limit), lead inbox with source context, statuses (new, contacted, qualified, won, lost, spam), and notes.
* Privacy and tuning filters: `forminbox_store_ip_hash` (return false to store nothing IP-derived; also disables per-client rate limiting, which keys on the hash) and `forminbox_rate_limit_max` (submissions-per-minute threshold).

== Upgrade Notice ==

= 0.1.0 =
Initial release.
