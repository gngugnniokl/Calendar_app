<?php
$file_container = 'themes/wondertag/layout/container.phtml';
$content = file_get_contents($file_container);

$sidebar_html = <<<HTML
    <div style="display: flex; min-height: calc(100vh - 66px);">
        <?php if (!empty(\$wo['loggedin'])): ?>
        <aside class="global-sidebar">
            <ul class="nav-icons" style="display: flex; flex-direction: column; gap: 15px;">
                <a href="?link1=internship_calendar&week=1" title="Calendar" style="color: inherit; text-decoration: none;">
                    <li class="nav-icon bg-orange">
                        <i class="fa-solid fa-calendar-days"></i>
                        <span class="nav-label">Calendar</span>
                    </li>
                </a>
                <a href="?link1=internship_calendar_dashboard" title="Dashboard" style="color: inherit; text-decoration: none;">
                    <li class="nav-icon bg-blue">
                        <i class="fa-solid fa-chart-pie"></i>
                        <span class="nav-label">Dashboard</span>
                    </li>
                </a>
                <a href="?link1=timeline&u=<?php echo htmlspecialchars(\$wo['user']['username'] ?? ''); ?>" title="Timeline" style="color: inherit; text-decoration: none;">
                    <li class="nav-icon bg-green">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <span class="nav-label">Timeline</span>
                    </li>
                </a>
                <a href="?link1=nudges" title="Nudges" style="color: inherit; text-decoration: none;">
                    <li class="nav-icon bg-pink">
                        <i class="fa-solid fa-hand-point-right"></i>
                        <span class="nav-label">Nudges</span>
                    </li>
                </a>
            </ul>
        </aside>
        <?php endif; ?>
        
        <main class="page-wrap" style="flex: 1; min-width: 0;">
            <?php echo \$wo['content'] ?? ''; ?>
        </main>
    </div>
HTML;

$target = <<<HTML
    <main class="page-wrap">
        <?php echo \$wo['content'] ?? ''; ?>
    </main>
HTML;

$content = str_replace($target, $sidebar_html, $content);

// Remove links from header nav
$targetNav = <<<HTML
                <?php if (!empty(\$wo['loggedin'])): ?>
                    <a href="?link1=internship_calendar&week=1">Calendar</a>
                    <a href="?link1=internship_calendar_dashboard">Dashboard</a>
                    <a href="?link1=timeline&u=<?php echo htmlspecialchars(\$wo['user']['username'] ?? ''); ?>">Timeline</a>
                <?php endif; ?>
HTML;

$content = str_replace($targetNav, '', $content);

file_put_contents($file_container, $content);
