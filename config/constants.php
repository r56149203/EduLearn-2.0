<?php
// Site configuration
define('SITE_NAME', 'EduPlatform');
define('SITE_URL', 'http://localhost/edulearn/');
define('SITE_EMAIL', 'noreply@eduplatform.com');

// Pagination settings
define('ITEMS_PER_PAGE', 10);
define('COURSES_PER_PAGE', 6);
define('QUIZZES_PER_PAGE', 6);

// Quiz settings
define('DEFAULT_QUIZ_DURATION', 30);
define('PASSING_SCORE', 70);

// Upload settings
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Time settings
define('TOKEN_EXPIRY_HOURS', 24);
define('RESET_TOKEN_EXPIRY_MINUTES', 60);

// Session timeout (30 minutes)
define('SESSION_TIMEOUT', 1800);
?>