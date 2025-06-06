=== LWA Exams ===
Contributors: shaazlarik
Tags: quizzes, exams, assessments, education
Requires at least: 6.0
Tested up to: 6.8.1
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
LWA Exams is a powerful and user-friendly plugin for creating interactive quizzes and exams within your WordPress site. Designed specifically for educators and learners, it allows you to build timed assessments with multiple question types including Multiple Choice, Multiple Select, True/False, and Fill-in-the-Blank.

Track user progress with detailed attempt histories, scores, and completion times. The plugin is fully responsive, ensuring a seamless experience on desktops, tablets, and mobile devices.

Whether you're running a classroom, training course, or online learning platform, LWA Exams provides an easy-to-use system to engage students, evaluate knowledge, and improve learning outcomes.

== Installation ==
1. Upload the plugin via WordPress admin or FTP
2. Activate the plugin from the Plugins screen

== Frequently Asked Questions ==

= Do I need to set up pages manually? =
Yes. After activation, create pages with the required shortcodes.  
See full documentation in [README.md](https://github.com/shaazlarik/lwa-exams/blob/main/README.md).

== Changelog ==
= v1.2.2 – 2025-06-06 =
* Fixed form submission bug where exam answers weren't being processed
* Adjusted results page styling for better score visibility
* Optimized confirmation dialog flow during exam submission

= v1.2.1 – 2025-06-06 =
* Fixed cache issues preventing real-time exam result updates
* Added comprehensive cache prevention for all exam pages
* Ensured proper timestamp recording for abandoned exams
* Removed redundant cache headers from template files
* Centralized cache control logic in authentication handler

= 1.2.0 - 2025-05-26 =
* Added support for keyboard navigation using left/right arrow keys for switching questions
* Enhanced user experience with smoother navigation controls
* Updated various CSS properties for improved layout and design consistency

= 1.1.2 - 2025-05-25 =
* Improved UI/UX with updated CSS styles
* Enhanced layout and spacing for cleaner interface
* Better color contrast for accessibility compliance
* Optimized responsiveness for mobile and tablet devices
* Minor visual tweaks and transition enhancements
* Cleaned up redundant styles for improved performance

= 1.1.1 - 2025-05-24 =
* Fixed Exam Timer Column Update
* Fixed `time_taken_seconds` column by removing `UNSIGNED` to support accurate time tracking
* Bumped database version to 1.0.2

= 1.1.0 - 2025-05-24 =
* Database versioning system added for controlled schema updates
* correct_answer column in wp_questions table modified to VARCHAR(255)
* Bumped database version to 1.0.1
* Optimized plugin structure for compatibility with upcoming features.
* Stable and backward-compatible — no data loss or breaking changes.

= 1.0.1 - 2025-05-24 =
* Plugin banner updated

= 1.0.0 - 2025-05-24 =
* Initial Plugin Release

== Screenshots ==
1. Exam listing page with available quizzes

2. Take exam screen with question layout

3. User's attempt history and scores