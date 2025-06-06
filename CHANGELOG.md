## 📝 Changelog
### v1.2.2 – 2025-06-06
- Fixed form submission bug where exam answers weren't being processed
- Adjusted results page styling for better score visibility
- Optimized confirmation dialog flow during exam submission

### v1.2.1 – 2025-06-06
- Fixed cache issues preventing real-time exam result updates
- Added comprehensive cache prevention for all exam pages
- Ensured proper timestamp recording for abandoned exams
- Removed redundant cache headers from template files
- Centralized cache control logic in authentication handler

### v1.2.0 – 2025-05-26
- Added support for keyboard navigation using left/right arrow keys for switching questions
- Enhanced user experience with smoother navigation controls
- Updated various CSS properties for improved layout and design consistency

### v1.1.2 – 2025-05-25
- Improved UI/UX with updated CSS styles
- Enhanced layout and spacing for cleaner interface
- Better color contrast for accessibility compliance
- Optimized responsiveness for mobile and tablet devices
- Minor visual tweaks and transition enhancements
- Cleaned up redundant styles for improved performance

### v1.1.1 – 2025-05-24
- Fixed Exam Timer Column Update
- Fixed `time_taken_seconds` column by removing `UNSIGNED` to support accurate time tracking
- Bumped database version to 1.0.2

### v1.1.0 – 2025-05-24
- Database versioning system added for controlled schema updates
- correct_answer column in wp_questions table modified to VARCHAR(255)
- Bumped database version to 1.0.1
- Optimized plugin structure for compatibility with upcoming features.
- Stable and backward-compatible — no data loss or breaking changes.

### v1.0.1 – 2025-05-24
- Plugin banner updated

### v1.0.0 – 2025-05-24
- Initial Plugin Release