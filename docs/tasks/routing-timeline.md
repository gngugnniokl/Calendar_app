# Prompt: Scaffold a `/timeline` Route in a New PHP Project

Use this prompt to recreate the full routing logic, authentication gating, and file structure needed to serve a `/timeline` route modelled after the triBBBal/WoWonder architecture.

---

## Goal

Set up a new PHP project folder with:
1. Apache `.htaccess` URL rewriting that maps clean URLs to query parameters
2. A front-controller `index.php` that determines the active page, checks login state, and dispatches to a source file
3. A bootstrap file (`assets/init.php`) that loads DB, session, config, and all include files
4. A `functions_general.php` with the core helpers needed for routing and rendering
5. A page controller (`sources/timeline.php`) that handles the `/timeline` route
6. An AJAX dispatcher (`requests.php`) with auth gating for XHR calls
7. An `xhr/` folder for AJAX handler files

---

## 1. Directory Structure

```
project-root/
├── .htaccess                  # Apache rewrite rules
├── index.php                  # Front controller / router
├── requests.php               # XHR/AJAX dispatcher
├── config.php                 # DB credentials & site_url
├── assets/
│   ├── init.php               # Bootstrap (session, DB, includes)
│   └── includes/
│       ├── functions_general.php  # Core helpers (Wo_LoadPage, Wo_Secure, Wo_SeoLink, etc.)
│       ├── functions_one.php      # Extended functions (user data, auth checks)
│       ├── tabels.php             # Table constant definitions (T_USERS, T_POSTS, etc.)
│       └── cache.php             # Cache class
├── sources/
│   ├── .htaccess              # "deny from all" — blocks direct access
│   ├── home.php               # Logged-in homepage controller
│   ├── welcome.php            # Logged-out landing page
│   ├── timeline.php           # User/Page/Group profile controller
│   └── 404.php                # Not found
├── xhr/
│   ├── .htaccess              # "deny from all"
│   ├── posts.php              # Example XHR handler
│   └── index.html             # Empty — prevents directory listing
├── themes/
│   └── default/
│       └── layout/
│           ├── home/
│           │   └── content.phtml
│           ├── timeline/
│           │   └── content.phtml
│           └── main.phtml     # Master layout wrapper
└── cache/                     # File-based cache directory
```

---

## 2. `.htaccess` — URL Rewriting

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# Static assets — let Apache serve them directly or 404
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule \.(?:css|gif|ico|jpe?g|js|json|mp3|mp4|png|svg|webp|woff2?)$ - [NC,L]

# --- Named routes ---
RewriteRule ^home(/?|)$             index.php?link1=home [QSA]
RewriteRule ^welcome(.*)$           index.php?link1=welcome [QSA,L]
RewriteRule ^404(/?|)$              index.php?link1=404 [QSA,L]
RewriteRule ^search(/?|)$           index.php?link1=search [NC,QSA]
RewriteRule ^setting(/?|)$          index.php?link1=setting [QSA]
RewriteRule ^messages(/?|)$         index.php?link1=messages [QSA]
RewriteRule ^logout(/?|)$           index.php?link1=logout [QSA]

# Settings sub-page
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^setting/([A-Za-z0-9_-]+)$  index.php?link1=setting&page=$1 [NC,QSA]

# XHR endpoint — all AJAX goes through requests.php
RewriteRule ^_$ requests.php [QSA]

# --- Timeline (user profile) catch-all — MUST be LAST ---
# @username format
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !\.[A-Za-z0-9]{1,8}$ [NC]
RewriteRule ^@([^\/]+)(\/|)$  index.php?link1=timeline&u=$1 [QSA]

# username/type format (e.g., /johndoe/photos)
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !\.[A-Za-z0-9]{1,8}$ [NC]
RewriteRule ^([A-Za-z0-9_]+)/([^\/]+)(\/|)$  index.php?link1=timeline&u=$1&type=$2 [QSA]

# Plain username (ultimate fallback)
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !\.[A-Za-z0-9]{1,8}$ [NC]
RewriteRule ^([^\/]+)(\/|)$  index.php?link1=timeline&u=$1 [QSA]
```

**Key rules for the timeline route:**
- `/@username` → `index.php?link1=timeline&u=username`
- `/username` → same (fallback catch-all)
- `/username/photos` → `index.php?link1=timeline&u=username&type=photos`
- The timeline catch-all MUST be the last rewrite rule because it matches any single-segment path

---

## 3. `assets/init.php` — Bootstrap

```php
<?php
@ini_set('session.cookie_httponly', 1);
@ini_set('session.use_only_cookies', 1);
@header("X-FRAME-OPTIONS: SAMEORIGIN");

if (!version_compare(PHP_VERSION, '7.1.0', '>=')) {
    exit("Required PHP >= 7.1.0, yours is: " . PHP_VERSION);
}

date_default_timezone_set('UTC');
session_start();

// Database connection
require_once('includes/db.php');           // provides $sqlConnect, $db
require_once('includes/cache.php');
require_once('includes/functions_general.php');
require_once('includes/tabels.php');
require_once('includes/functions_one.php');

// --- Determine login state ---
// The $wo global array is the central state container.
// $wo['loggedin'] is set in functions_one.php during session validation.
// $wo['user'] holds the authenticated user's data array.
// $wo['config'] holds all site settings loaded from T_CONFIG table.
```

**What init.php establishes:**
- `$sqlConnect` — raw mysqli connection
- `$db` — MysqliDb ORM instance (joshcam/mysqli-database-class)
- `$wo['loggedin']` — boolean, true if valid session
- `$wo['user']` — associative array of current user data (user_id, username, name, etc.)
- `$wo['config']` — all site settings from the config DB table
- `$site_url` — base URL string (e.g., `https://example.com`)

---

## 4. `index.php` — Front Controller / Router

```php
<?php
require_once('assets/init.php');

// --- HTTPS/www redirect logic ---
// (SSL enforcement and www normalization based on $site_url)

// --- Update last seen for logged-in users ---
if ($wo['loggedin'] == true) {
    $update_last_seen = Wo_LastSeen($wo['user']['user_id']);
}

// --- Sanitize all GET/POST/REQUEST superglobals ---
if (!empty($_GET)) {
    foreach ($_GET as $key => $value) {
        if (!is_array($value)) {
            $value      = preg_replace('/on[^<>=]+=[^<>]*/m', '', $value);
            $value      = preg_replace('/\((.*?)\)/m', '', $value);
            $_GET[$key] = strip_tags($value);
        }
    }
}
// (Same pattern for $_POST and $_REQUEST)

// --- Determine current page ---
$page = '';
if ($wo['loggedin'] == true && !isset($_GET['link1'])) {
    $page = 'home';                          // Default for logged-in users
} elseif (isset($_GET['link1'])) {
    $page = $_GET['link1'];                  // From .htaccess rewrite
}
if ((!isset($_GET['link1']) && $wo['loggedin'] == false) || 
    (isset($_GET['link1']) && $wo['loggedin'] == false && $page == 'home')) {
    $page = 'welcome';                       // Default for logged-out users
}

// Expose to templates
$wo['page']  = $page;
$wo['link1'] = isset($_GET['link1']) ? $_GET['link1'] : $page;
$wo['link2'] = isset($_GET['link2']) ? $_GET['link2'] : '';
$wo['link3'] = isset($_GET['link3']) ? $_GET['link3'] : '';

// --- Route dispatch (logged-in) ---
if ($wo['loggedin'] == true) {
    switch ($page) {
        case 'home':
            include('sources/home.php');
            break;
        case 'timeline':
            include('sources/timeline.php');
            break;
        case 'search':
            include('sources/search.php');
            break;
        case 'messages':
            include('sources/messages.php');
            break;
        case 'setting':
            include('sources/setting.php');
            break;
        case 'logout':
            include('sources/logout.php');
            break;
        case '404':
            include('sources/404.php');
            break;
        default:
            include('sources/404.php');
            break;
    }
} else {
    // --- Route dispatch (logged-out) ---
    // Only allow specific public routes
    switch ($page) {
        case 'welcome':
            include('sources/welcome.php');
            break;
        case 'timeline':
            // Optional: allow public profile viewing based on config
            if ($wo['config']['profile_privacy'] == 1) {
                include('sources/timeline.php');
            } else {
                header("Location: " . Wo_SeoLink('index.php?link1=welcome'));
                exit();
            }
            break;
        case '404':
            include('sources/404.php');
            break;
        default:
            include('sources/welcome.php');
            break;
    }
}

// --- Render final output ---
// The source file sets $wo['content'] via Wo_LoadPage()
// Then a master layout template wraps it
$theme_url = $site_url . '/themes/' . $wo['config']['theme'];
require('themes/' . $wo['config']['theme'] . '/layout/main.phtml');
```

**Routing logic summary:**
1. `.htaccess` rewrites `/username` → `?link1=timeline&u=username`
2. `index.php` reads `$_GET['link1']` to determine `$page`
3. If no `link1` and logged in → `$page = 'home'`
4. If no `link1` and logged out → `$page = 'welcome'`
5. A `switch($page)` dispatches to the correct `sources/*.php` file
6. The source file populates `$wo['content']` using `Wo_LoadPage('path/to/template')`

---

## 5. `sources/timeline.php` — Timeline Page Controller

```php
<?php
// --- Auth gate ---
if ($wo['loggedin'] == false) {
    if ($wo['config']['profile_privacy'] == 0) {
        header("Location: " . Wo_SeoLink('index.php?link1=welcome'));
        exit();
    }
}

// --- Resolve the entity (user, page, or group) ---
if (isset($_GET['u'])) {
    $check_user = Wo_IsNameExist($_GET['u'], 1);
    if (in_array(true, $check_user)) {
        if ($check_user['type'] == 'user') {
            $id                 = $user_id = Wo_UserIdFromUsername($_GET['u']);
            $wo['user_profile'] = Wo_UserData($user_id);
            $type               = 'timeline';
            $about              = $wo['user_profile']['about'];
            $name               = $wo['user_profile']['name'];
        } else if ($check_user['type'] == 'page') {
            $id                 = $page_id = Wo_PageIdFromPagename($_GET['u']);
            $wo['page_profile'] = Wo_PageData($page_id);
            $type               = 'page';
        } else if ($check_user['type'] == 'group') {
            $id                  = $group_id = Wo_GroupIdFromGroupname($_GET['u']);
            $wo['group_profile'] = Wo_GroupData($group_id);
            $type                = 'group';
        }
    } else {
        header("Location: " . Wo_SeoLink('index.php?link1=404'));
        exit();
    }
} else {
    header("Location: " . $wo['config']['site_url']);
    exit();
}

if (empty($type)) {
    header("Location: " . Wo_SeoLink('index.php?link1=404'));
    exit();
}

// --- Block check (logged-in only) ---
if ($type == 'timeline' && $wo['loggedin'] == true) {
    $is_blocked = Wo_IsBlocked($user_id);
    if ($is_blocked) {
        // Render blocked state or redirect
    }
}

// --- Set page meta & load template ---
$wo['description'] = $about ?? '';
$wo['title']       = $name ?? 'Profile';
$wo['page']        = 'timeline';
$wo['content']     = Wo_LoadPage('timeline/content');
```

**Key patterns:**
- `$_GET['u']` comes from the `.htaccess` rewrite
- `Wo_IsNameExist()` checks if the username belongs to a user, page, or group
- Data is loaded into `$wo['user_profile']`, `$wo['page_profile']`, or `$wo['group_profile']`
- The controller redirects to 404 or welcome if entity not found or access denied

---

## 6. `requests.php` — AJAX/XHR Dispatcher

```php
<?php
ob_start();
require_once('assets/init.php');
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// --- Read dispatch parameters ---
$f = '';  // function (main handler)
$s = '';  // sub-function
if (isset($_GET['f'])) {
    $f = Wo_Secure($_GET['f'], 0);
} elseif (isset($_POST['f'])) {
    $f = Wo_Secure($_POST['f'], 0);
}
if (isset($_GET['s'])) {
    $s = Wo_Secure($_GET['s'], 0);
} elseif (isset($_POST['s'])) {
    $s = Wo_Secure($_POST['s'], 0);
}

// --- XHR validation (block non-AJAX direct access) ---
$allow_array = array('posts', 'payment', 'upload-blog-image');  // bypass XHR check
if (!in_array($f, $allow_array)) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
            exit("Restricted Area");
        }
    } else {
        exit("Restricted Area");
    }
}

// --- Auth gating ---
$non_login_array = array(
    'login',
    'register',
    'recover',
    'search',
    'load_posts',
    'contact_us',
);

if (!in_array($f, $non_login_array)) {
    if ($wo['loggedin'] == false) {
        echo json_encode(array('status' => 401, 'message' => 'Please login to continue.'));
        exit();
    }
}

// --- Dispatch to handler file ---
$files = scandir('xhr');
if (file_exists('xhr/' . $f . '.php') && in_array($f . '.php', $files)) {
    include 'xhr/' . $f . '.php';
}

mysqli_close($sqlConnect);
exit();
```

**AJAX call pattern (client-side):**
```javascript
// URL: /_ (rewritten to requests.php via .htaccess)
$.ajax({
    url: '/_',
    type: 'POST',
    data: { f: 'posts', s: 'load_timeline_posts', user_id: 123 },
    success: function(response) { /* handle */ }
});
```

**Auth rules:**
- `$allow_array` — endpoints that bypass the XMLHttpRequest header check (for form posts, webhooks)
- `$non_login_array` — endpoints that work without authentication
- Everything else requires `$wo['loggedin'] == true`

---

## 7. `assets/includes/functions_general.php` — Core Helpers

These are the minimum functions needed for the routing system:

```php
<?php

/**
 * Wo_LoadPage — Template renderer
 * Loads a .phtml template from the active theme's layout folder.
 * Uses output buffering to capture rendered HTML.
 */
function Wo_LoadPage($page_url = '') {
    global $wo, $db;
    $page = './themes/' . $wo['config']['theme'] . '/layout/' . $page_url . '.phtml';
    if (!file_exists($page)) {
        error_log("[Wo_LoadPage] Missing template: {$page}");
        return '';
    }
    ob_start();
    require($page);
    $page_content = ob_get_contents();
    ob_end_clean();
    return $page_content;
}

/**
 * Wo_SeoLink — URL generator
 * Converts internal query-string URLs to SEO-friendly paths.
 * e.g., 'index.php?link1=timeline&u=john' → '/john'
 */
function Wo_SeoLink($query = '') {
    global $wo;
    if ($wo['config']['seoLink'] == 1) {
        // Regex replacements to convert ?link1=X&param=Y into /clean/paths
        $query = preg_replace(array(
            '/^index\.php\?link1=timeline&u=([A-Za-z0-9_]+)&type=([A-Za-z0-9_]+)$/i',
            '/^index\.php\?link1=timeline&u=([A-Za-z0-9_]+)$/i',
            '/^index\.php\?link1=welcome&last_url=(.*)$/i',
            '/^index\.php\?link1=setting&page=([A-Za-z0-9_-]+)$/i',
            '/^index\.php\?link1=post&id=(.*)$/i',
            '/^index\.php\?link1=([^\/]+)$/i',
        ), array(
            $wo['config']['site_url'] . '/$1/$2',
            $wo['config']['site_url'] . '/$1',
            $wo['config']['site_url'] . '/welcome?last_url=$1',
            $wo['config']['site_url'] . '/setting/$1',
            $wo['config']['site_url'] . '/post/$1',
            $wo['config']['site_url'] . '/$1',
        ), $query);
    } else {
        $query = $wo['config']['site_url'] . '/' . $query;
    }
    return $query;
}

/**
 * Wo_Secure — Input sanitization
 * Escapes user input for safe DB queries and HTML output.
 */
function Wo_Secure($string, $censored_words = 0, $br = true, $strip = 0) {
    global $sqlConnect;
    $string = trim($string);
    $string = mysqli_real_escape_string($sqlConnect, $string);
    $string = htmlspecialchars($string, ENT_QUOTES);
    if ($br == true) {
        $string = str_replace('\r\n', " <br>", $string);
        $string = str_replace('\n', " <br>", $string);
    }
    if ($strip == 1) {
        $string = stripslashes($string);
    }
    return $string;
}

/**
 * checkHTTPS — Detect if current request is over HTTPS
 */
function checkHTTPS() {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    if ($_SERVER['SERVER_PORT'] == 443) {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
        return true;
    }
    return false;
}

/**
 * full_url — Reconstruct the full request URL (host + URI)
 */
function full_url($s, $use_forwarded_host = false) {
    $host = ($use_forwarded_host && isset($s['HTTP_X_FORWARDED_HOST']))
        ? $s['HTTP_X_FORWARDED_HOST']
        : ($s['HTTP_HOST'] ?? $s['SERVER_NAME']);
    return $host . $s['REQUEST_URI'];
}

/**
 * sanitize_output — Minify HTML output (strip whitespace, comments)
 */
function sanitize_output($buffer) {
    $search  = array('/\>[^\S ]+/s', '/[^\S ]+\</s', '/(\s)+/s', '/<!--(.|\s)*?-->/');
    $replace = array('>', '<', '\\1', '');
    return preg_replace($search, $replace, $buffer);
}
```

---

## 8. `assets/includes/tabels.php` — Table Constants

```php
<?php
define('T_USERS', 'Wo_Users');
define('T_POSTS', 'Wo_Posts');
define('T_FOLLOWERS', 'Wo_Followers');
define('T_PAGES', 'Wo_Pages');
define('T_GROUPS', 'Wo_Groups');
define('T_CONFIG', 'Wo_Config');
define('T_NOTIFICATION', 'Wo_Notifications');
define('T_MESSAGES', 'Wo_Messages');
define('T_BLOCKS', 'Wo_Blocks');
// ... add as needed
```

---

## 9. `sources/.htaccess` and `xhr/.htaccess`

```apache
deny from all
```

This prevents direct HTTP access to source/controller files — they can only be included by `index.php` or `requests.php`.

---

## 10. Summary of Request Flow

```
Browser Request: GET /johndoe
        │
        ▼
.htaccess rewrites to: index.php?link1=timeline&u=johndoe
        │
        ▼
index.php:
  1. require('assets/init.php')     → DB, session, $wo['loggedin'], $wo['config']
  2. Sanitize $_GET/$_POST
  3. $page = $_GET['link1']         → "timeline"
  4. switch($page) → include('sources/timeline.php')
        │
        ▼
sources/timeline.php:
  1. Check $wo['loggedin'] + profile_privacy config
  2. Wo_IsNameExist($_GET['u'])     → determine if user/page/group
  3. Load entity data into $wo['user_profile']
  4. $wo['content'] = Wo_LoadPage('timeline/content')
        │
        ▼
Back in index.php:
  5. require('themes/default/layout/main.phtml')  → wraps $wo['content'] in master layout
        │
        ▼
HTML Response to Browser
```

```
AJAX Request: POST /_ {f: 'posts', s: 'load_timeline_posts'}
        │
        ▼
.htaccess rewrites ^_$ to: requests.php
        │
        ▼
requests.php:
  1. require('assets/init.php')
  2. Read $f and $s from GET/POST
  3. Validate X-Requested-With header (XHR check)
  4. Check $wo['loggedin'] unless $f is in $non_login_array
  5. include('xhr/' . $f . '.php')  → xhr/posts.php handles sub-function $s
        │
        ▼
JSON Response to Browser
```

---

## Key Conventions to Follow

| Convention | Description |
|-----------|-------------|
| `$wo` global | Central state array — config, user, page, loggedin, content |
| `Wo_Secure()` | Always sanitize user input before DB or output |
| `Wo_LoadPage('path')` | Renders `.phtml` templates from active theme |
| `Wo_SeoLink()` | Generates clean URLs from query-string format |
| `T_` constants | Table name references (defined in tabels.php) |
| `sources/*.php` | Page controllers — set `$wo['content']`, never output directly |
| `xhr/*.php` | AJAX handlers — echo JSON, accessed only via `requests.php` |
| `$wo['loggedin']` | Boolean login check — set during init from session |
| `$wo['config']['setting']` | Site settings loaded from DB config table |
| `.htaccess deny` | Protect `sources/` and `xhr/` from direct access |
