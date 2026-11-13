<?php
declare(strict_types=1);

require BASE_PATH . '/app/controllers/site.php';
require BASE_PATH . '/app/controllers/api.php';
require BASE_PATH . '/app/controllers/admin/auth.php';
require BASE_PATH . '/app/controllers/admin/kb.php';
require BASE_PATH . '/app/controllers/admin/ai.php';
require BASE_PATH . '/app/controllers/admin/content.php';
require BASE_PATH . '/app/controllers/admin/settings.php';
require BASE_PATH . '/app/controllers/admin/recover.php'; // TEMPORARY: delete after use

// Public site
route('GET', '/', 'site_home');
route('GET', '/blog', 'site_blog');
route('GET', '/blog/category/{slug}', 'site_blog_category');
route('GET', '/blog/tag/{slug}', 'site_blog_tag');
route('GET', '/blog/{slug}', 'site_post');
route('GET', '/sitemap.xml', 'site_sitemap');
route('GET', '/robots.txt', 'site_robots');
route('POST', '/api/chat', 'api_chat');
route('POST', '/api/track', 'api_track');

// Admin
route('GET|POST', '/admin/login', 'admin_login');
route('GET|POST', '/admin/recover', 'admin_recover'); // TEMPORARY: delete after use
route('POST', '/admin/logout', 'admin_logout');
route('GET', '/admin', 'admin_dashboard');

route('GET', '/admin/kb', 'admin_kb_index');
route('GET|POST', '/admin/kb/new', 'admin_kb_create');
route('GET', '/admin/kb/test', 'admin_kb_test');
route('GET|POST', '/admin/kb/{id}', 'admin_kb_edit');
route('POST', '/admin/kb/{id}/reindex', 'admin_kb_reindex');
route('POST', '/admin/kb/{id}/delete', 'admin_kb_delete');
route('GET', '/admin/kb/{id}/download', 'admin_kb_download');

route('GET|POST', '/admin/ai', 'admin_ai_settings');
route('POST', '/admin/ai/test', 'admin_ai_test');
route('GET', '/admin/chats', 'admin_chats');
route('GET', '/admin/chats/{id}', 'admin_chat_show');
route('POST', '/admin/chats/{id}/delete', 'admin_chat_delete');

route('GET', '/admin/pages', 'admin_pages');
route('GET|POST', '/admin/pages/new', 'admin_page_form');
route('GET|POST', '/admin/pages/{id}', 'admin_page_form');
route('POST', '/admin/pages/{id}/delete', 'admin_page_delete');

route('GET', '/admin/posts', 'admin_posts');
route('GET|POST', '/admin/posts/new', 'admin_post_form');
route('GET|POST', '/admin/posts/{id}', 'admin_post_form');
route('POST', '/admin/posts/{id}/delete', 'admin_post_delete');

route('GET|POST', '/admin/categories', 'admin_categories');
route('POST', '/admin/categories/{id}/delete', 'admin_category_delete');
route('POST', '/admin/tags/{id}/delete', 'admin_tag_delete');

route('GET|POST', '/admin/media', 'admin_media');
route('POST', '/admin/media/upload', 'admin_media_upload');
route('GET', '/admin/media/list', 'admin_media_list');
route('POST', '/admin/media/{id}', 'admin_media_update');
route('POST', '/admin/media/{id}/delete', 'admin_media_delete');

route('GET|POST', '/admin/homepage', 'admin_homepage');
route('GET|POST', '/admin/settings', 'admin_settings');
route('GET', '/admin/users', 'admin_users');
route('GET|POST', '/admin/users/new', 'admin_user_form');
route('GET|POST', '/admin/users/{id}', 'admin_user_form');
route('POST', '/admin/users/{id}/delete', 'admin_user_delete');
route('GET|POST', '/admin/account', 'admin_account');

route('GET', '/admin/r/{resource}', 'admin_resource_index');
route('GET|POST', '/admin/r/{resource}/new', 'admin_resource_form');
route('GET|POST', '/admin/r/{resource}/{id}', 'admin_resource_form');
route('POST', '/admin/r/{resource}/{id}/toggle', 'admin_resource_toggle');
route('POST', '/admin/r/{resource}/{id}/delete', 'admin_resource_delete');

// Custom pages, at whatever URL an admin assigns. Must stay last.
route('GET', '/{path*}', 'site_page');
