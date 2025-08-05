<?php
// Configurações da API OpenRouter
define('OPENROUTER_API_KEY', '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');
define('OPENROUTER_API_URL', 'https://openrouter.ai/api/v1/chat/completions');
define('DEFAULT_MODEL', 'google/gemini-2.0-flash-001');

// Configurações de cache
define('CACHE_ENABLED', true);
define('CACHE_DIR', __DIR__ . '/../cache/');
define('CACHE_TIME', 3600); // 1 hora em segundos

// Configurações de rate limiting
define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_PER_MINUTE', 60);
define('LOG_DIR', __DIR__ . '/../logs/');
define('LOG_ENABLED', true);

// Configurações do site
define('SITE_URL', 'https://www.eurobetgame.com');
define('SITE_NAME', 'DesireChat');
?> 