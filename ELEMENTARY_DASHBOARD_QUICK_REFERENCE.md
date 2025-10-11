# Elementary Dashboard - Quick Reference Card

## 📁 File Locations

```
iomad/theme/remui_kids/
├── templates/
│   └── elementary_dashboard.mustache    ← Template HTML
├── scss/
│   └── elementary_dashboard.scss        ← Styles CSS
├── lib.php                              ← SCSS loader (modified)
└── ELEMENTARY_DASHBOARD_README.md       ← Full documentation
```

## ⚡ Quick Commands

### Purge Cache (Run after any changes)
```bash
cd c:\wamp64\www\kodeit\iomad
php admin/cli/purge_caches.php
```

### View Logs
```bash
php admin/cli/run_tests.php
```

## 🎨 Quick Edits

### Change Card Colors
**File:** `scss/elementary_dashboard.scss`
```scss
.courses-card::before {
    background: linear-gradient(90deg, #667eea, #764ba2);  ← Edit colors here
}
```

### Change Grid Layout
**File:** `scss/elementary_dashboard.scss`
```scss
.elementary-courses-container {
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));  ← Edit 320px
}
```

### Change Animation Speed
**File:** `scss/elementary_dashboard.scss`
```scss
.elementary-course-card.enhanced {
    transition: all 0.3s ease;  ← Edit 0.3s (seconds)
}
```

## 🔧 Common Tasks

| Task | File to Edit | Line to Find |
|------|-------------|--------------|
| Add new section | `elementary_dashboard.mustache` | After line ~400 |
| Change colors | `elementary_dashboard.scss` | Lines 30-80 |
| Modify cards | `elementary_dashboard.scss` | Lines 150-300 |
| Update JavaScript | `elementary_dashboard.mustache` | Bottom of file |

## 🐛 Troubleshooting Checklist

- [ ] Purged Moodle cache?
- [ ] Cleared browser cache?
- [ ] Student in correct cohort?
- [ ] Checked browser console?
- [ ] Verified SCSS file exists?
- [ ] Checked `lib.php` includes SCSS?

## 📊 Data Fields Required

```php
'elementary_stats' => [
    'total_courses',          // Required
    'lessons_completed',      // Required
    'activities_completed',   // Required
    'overall_progress',       // Required
    'last_updated'           // Optional
]

'elementary_courses' => [
    'id',                    // Required
    'fullname',              // Required
    'courseimage',           // Optional
    'progress_percentage',   // Required
    'courseurl'             // Required
]
```

## 🎯 Key Functions

### JavaScript Functions
```javascript
refreshCourseCount()              // Refresh course count via AJAX
toggleCourseDetails(courseId)     // Show/hide course details
animateCountUpdate(elem, value)   // Animate number changes
```

### PHP Functions (lib.php)
```php
theme_remui_kids_get_extra_scss() // Loads SCSS files
theme_remui_kids_get_pre_scss()   // Sets SCSS variables
```

## 🌐 URLs & Paths

| Resource | Path |
|----------|------|
| Dashboard | `/my/` |
| My Courses | `/theme/remui_kids/my_courses.php` |
| AJAX Endpoint | `/theme/remui_kids/tests/test_ajax.php` |

## 📱 Responsive Breakpoints

```scss
Desktop:    > 1200px
Tablet:     768px - 1200px  
Mobile:     < 768px
Small Mobile: < 480px
```

## 🎨 Color Palette

| Card Type | Gradient Colors |
|-----------|----------------|
| Courses | `#667eea` → `#764ba2` |
| Lessons | `#f093fb` → `#f5576c` |
| Activities | `#4facfe` → `#00f2fe` |
| Progress | `#43e97b` → `#38f9d7` |

## ⚙️ Configuration

### Enable/Disable Features

**Auto-refresh (30s):**
```javascript
// In elementary_dashboard.mustache, bottom
setInterval(refreshCourseCount, 30000);  // Change 30000 (ms)
```

**Animation Duration:**
```scss
// In elementary_dashboard.scss
$animation-duration: 0.3s;  // Change duration
```

## 🚀 Deployment Steps

1. ✅ Edit files
2. ✅ Save changes
3. ✅ Purge cache: `php admin/cli/purge_caches.php`
4. ✅ Test on browser
5. ✅ Test on mobile
6. ✅ Verify AJAX works
7. ✅ Check console for errors

## 📞 Quick Help

| Issue | Solution |
|-------|----------|
| Styles not showing | Purge cache + clear browser cache |
| Dashboard blank | Check cohort assignment |
| JavaScript error | Check browser console |
| AJAX fails | Check endpoint exists |

## 💡 Pro Tips

- **Always test on mobile** - Students use tablets
- **Keep backups** before editing
- **Comment your changes** for future reference
- **Test with real student accounts**
- **Use browser DevTools** for debugging

## 🔗 Important Links

- Full docs: `ELEMENTARY_DASHBOARD_README.md`
- Implementation guide: `ELEMENTARY_DASHBOARD_IMPLEMENTATION.md`
- Moodle docs: https://docs.moodle.org/dev/Themes

---

**Print this card and keep it handy!**

**Last Updated:** October 11, 2025  
**Version:** 1.0.0

