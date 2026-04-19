<?php
// Site Configuration
define('SITE_NAME', 'EduLearn');
define('SITE_URL', 'http://localhost/EduLearn/');  // Change this to your actual URL
define('SITE_EMAIL', 'noreply@edulearn.com');

// Pagination Settings
define('ITEMS_PER_PAGE', 10);
define('COURSES_PER_PAGE', 6);
define('QUIZZES_PER_PAGE', 6);

// Quiz Settings
define('DEFAULT_QUIZ_DURATION', 30);
define('PASSING_SCORE', 70);

// Upload Settings
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Token Expiry (in hours)
define('TOKEN_EXPIRY_HOURS', 24);
define('RESET_TOKEN_EXPIRY_MINUTES', 60);

// Session Timeout (30 minutes)
define('SESSION_TIMEOUT', 1800);

// Default Admin Credentials (change after first login)
define('DEFAULT_ADMIN_EMAIL', 'admin@example.com');
define('DEFAULT_ADMIN_PASSWORD', 'admin123');
?>