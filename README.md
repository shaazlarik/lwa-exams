# LWA Exams - WordPress Quiz Plugin

![Plugin Banner](assets/banner-1544x500.jpg)

> A professional exam and quiz system with advanced tracking designed for educators and learners.

## 🚀 Features
- **Multiple Question Types**  
  Supports multiple choice, multiple select, true/false, and fill-in-the-blank questions.
- **Time-Limited Exams**  
  Configure countdown timers for each exam to simulate real test conditions.
- **Detailed Analytics**  
  Track user attempts, scores, and time spent for insightful reporting.
- **Responsive Design**  
  Fully responsive and works seamlessly on desktops, tablets, and mobile devices.

## 📦 Installation

### Via WordPress Admin
1. Download the [latest release](https://github.com/shaazlarik/lwa-exams/releases).
2. Navigate to **Plugins → Add New → Upload Plugin**.
3. Upload the ZIP file, then install and activate the plugin.

### Required Pages Setup
After activating the plugin, create the following pages and add the corresponding shortcodes:

1. **Exams Listing**  
  - Page Title: `Exams`  
  - Shortcode: `[lwa_exams_list]`  
  *(Displays a list of all available exams)*

2. **Exam Interface**  
  - Page Title: `Take Exam`  
  - Shortcode: `[lwa_exam]`  
  *(Where users will take the exams)*

3. **Attempt History**  
  - Page Title: `My Attempts`  
  - Shortcode: `[lwa_attempts]`  
  *(Shows users their past exam attempts and results)*

## 📝 Changelog
See the [Changelog](CHANGELOG.md) for release history.

### Via Git (For Developers)
```bash
git clone https://github.com/shaazlarik/lwa-exams.git
