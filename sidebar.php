<?php
/**
 * ============================================================
 * UNIFIED SIDEBAR — Systellio CRM
 * ============================================================
 * সব পেজে এভাবে include করুন:
 *
 *   <?php $activePage = 'campaigns'; include 'sidebar.php'; ?>
 *
 * $activePage মান:
 *   dashboard | user_list | user_tasks | user_activity
 *   company_list | client_list | deal_pipeline | campaigns
 *   analytics | settings
 * ============================================================
 */

if (!isset($activePage)) $activePage = '';

/* Active class helpers */
function sa($page, $ap)  { return $page === $ap ? ' active' : ''; }
function sd($pages, $ap) { return in_array($ap, $pages) ? ' open' : ''; }
function si($page, $ap)  { return $page === $ap ? ' class="active-sub"' : ''; }
?>
<style>
/* ================================================================
   SIDEBAR CSS — Systellio CRM (shared, do not duplicate in pages)
   ================================================================ */

/* Core layout */
.sidebar {
    width: 260px; min-width: 260px;
    background-color: #0b1524; color: #ffffff;
    display: flex; flex-direction: column;
    transition: margin-left 0.3s ease;
    box-shadow: 2px 0 10px rgba(0,0,0,0.15);
    z-index: 1000; overflow: hidden;
}
.sidebar.collapsed { margin-left: -260px; }

/* Header / logo */
.sidebar-header {
    padding: 25px 20px 20px;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    border-bottom: 1px solid #162235; flex-shrink: 0;
}
.sidebar-logo { width: 100px; height: auto; margin-bottom: 8px; }
.brand-role {
    font-size: 10px; font-weight: 700;
    color: #60a5fa; letter-spacing: 1.8px; text-transform: uppercase;
}

/* Menu list */
.sidebar-menu {
    list-style: none; padding: 12px 0 0;
    flex-grow: 1; overflow-y: auto; overflow-x: hidden;
}

/* Hide scrollbar but keep scroll */
.sidebar-menu::-webkit-scrollbar { width: 4px; }
.sidebar-menu::-webkit-scrollbar-track { background: transparent; }
.sidebar-menu::-webkit-scrollbar-thumb { background: #1e3a5f; border-radius: 4px; }

/* Menu items */
.sidebar-menu > li {
    padding: 13px 20px 13px 21px;
    display: flex; align-items: center; gap: 14px;
    cursor: pointer;
    transition: background 0.2s, color 0.2s, border-color 0.2s;
    color: #94a3b8; border-left: 3px solid transparent;
    white-space: nowrap;
}
.sidebar-menu > li:hover { background-color: #162235; color: #ffffff; }
.sidebar-menu > li i { font-size: 15px; width: 20px; text-align: center; flex-shrink: 0; }
.sidebar-menu > li a {
    color: inherit; text-decoration: none;
    font-size: 13px; font-weight: 500; width: 100%;
    pointer-events: none;
}

/* Active top-level item */
.sidebar-menu > li.active {
    background-color: #1a2844; color: #3b82f6;
    border-left: 3px solid #3b82f6;
}
.sidebar-menu > li.active i { color: #3b82f6; }

/* Dropdown container */
.dropdown-item {
    padding: 0 !important; display: block !important;
    border-left: none !important; cursor: default;
}
.dropdown-title {
    padding: 13px 20px 13px 21px;
    display: flex; align-items: center; justify-content: space-between;
    cursor: pointer; color: #94a3b8;
    transition: background 0.2s, color 0.2s;
    border-left: 3px solid transparent;
}
.dropdown-title:hover { background-color: #162235; color: #ffffff; }
.dropdown-title-left { display: flex; align-items: center; gap: 14px; }
.dropdown-title-left i { font-size: 15px; width: 20px; text-align: center; flex-shrink: 0; }
.dropdown-title-left span { font-size: 13px; font-weight: 500; }
.dropdown-icon { font-size: 10px !important; transition: transform 0.25s ease; flex-shrink: 0; }

/* Open dropdown */
.dropdown-item.open > .dropdown-title { color: #ffffff; background-color: #111f35; }
.dropdown-item.open .dropdown-icon { transform: rotate(180deg); }

/* Submenu */
.submenu {
    list-style: none; display: none;
    background-color: #0b1524; padding: 4px 0 10px;
}
.dropdown-item.open .submenu { display: block; }
.submenu li {
    padding: 9px 20px 9px 59px !important;
    border-left: none !important; background-color: transparent !important;
    position: relative; transition: background 0.2s; cursor: pointer;
}
.submenu li::before {
    content: ''; position: absolute;
    left: 38px; top: 50%; transform: translateY(-50%);
    width: 5px; height: 5px; border-radius: 50%;
    background-color: #334155; transition: background 0.2s;
}
.submenu li:hover::before { background-color: #94a3b8; }
.submenu li a {
    color: #64748b; text-decoration: none;
    font-size: 12px; font-weight: 500;
    transition: color 0.2s; pointer-events: none;
}
.submenu li:hover a { color: #e2e8f0; }

/* Active submenu item */
.submenu li.active-sub::before { background-color: #3b82f6; }
.submenu li.active-sub a { color: #3b82f6; font-weight: 600; }

/* Logout section */
.sidebar-logout {
    padding: 0; margin: 0;
    border-top: 1px solid #162235; flex-shrink: 0;
}
.sidebar-logout li { color: #f87171 !important; border-left-color: transparent !important; }
.sidebar-logout li:hover { background-color: #1c0d0d !important; color: #ef4444 !important; }
.sidebar-logout li i { color: #f87171 !important; }

/* Dark mode sidebar adjustments */
body.dark-mode .sidebar { background-color: #070f1c; box-shadow: 2px 0 15px rgba(0,0,0,0.4); }
body.dark-mode .sidebar-header { border-color: #0f1e35; }
body.dark-mode .dropdown-item.open > .dropdown-title { background-color: #0a1628; }
body.dark-mode .submenu { background-color: #070f1c; }
body.dark-mode .sidebar-menu > li:hover,
body.dark-mode .dropdown-title:hover { background-color: #0f1e35; }
body.dark-mode .sidebar-menu > li.active { background-color: #0f1e35; }
body.dark-mode .sidebar-logout { border-color: #0f1e35; }
body.dark-mode .sidebar-logout li:hover { background-color: #1a0808 !important; }
</style>

<div class="sidebar" id="sidebar">

    <div class="sidebar-header">
        <img src="img/logo.png" alt="Systellio Logo" class="sidebar-logo">
        <span class="brand-role"><?php echo isset($sidebarRole) ? $sidebarRole : 'Super Admin'; ?></span>
    </div>

    <ul class="sidebar-menu">

        <!-- Dashboard -->
        <li class="<?= trim(sa('dashboard', $activePage)) ?>"
            onclick="window.location.href='<?php echo isset($dashboardFile) ? $dashboardFile : 'super_admin_dashboard.php'; ?>'">
            <i class="fa-solid fa-table-cells-large"></i>
            <a href="<?php echo isset($dashboardFile) ? $dashboardFile : 'super_admin_dashboard.php'; ?>">Dashboard</a>
        </li>

        <!-- User Management -->
        <li class="dropdown-item<?= sd(['user_list','user_tasks','user_activity'], $activePage) ?>" id="sb-userMenu">
            <div class="dropdown-title" onclick="sbToggle('sb-userMenu')">
                <div class="dropdown-title-left">
                    <i class="fa-solid fa-user-group"></i>
                    <span>User Management</span>
                </div>
                <i class="fa-solid fa-chevron-down dropdown-icon"></i>
            </div>
            <ul class="submenu">
                <?php if(isset($dashboardMode) && $dashboardMode): ?>
                <li onclick="showUserList(this)"><a href="#">User List</a></li>
                <li onclick="showComingSoon('User Tasks', this)"><a href="#">User Tasks</a></li>
                <li onclick="showComingSoon('User Activity', this)"><a href="#">User Activity</a></li>
                <?php else: ?>
                <li<?= si('user_list',     $activePage) ?> onclick="window.location.href='user_list.php'">
                    <a href="user_list.php">User List</a>
                </li>
                <li<?= si('user_tasks',    $activePage) ?> onclick="window.location.href='user_tasks.php'">
                    <a href="user_tasks.php">User Tasks</a>
                </li>
                <li<?= si('user_activity', $activePage) ?> onclick="window.location.href='user_activity.php'">
                    <a href="user_activity.php">User Activity</a>
                </li>
                <?php endif; ?>
            </ul>
        </li>

        <!-- Leads & Accounts -->
        <li class="dropdown-item<?= sd(['company_list','client_list'], $activePage) ?>" id="sb-leadsMenu">
            <div class="dropdown-title" onclick="sbToggle('sb-leadsMenu')">
                <div class="dropdown-title-left">
                    <i class="fa-solid fa-briefcase"></i>
                    <span>Leads &amp; Accounts</span>
                </div>
                <i class="fa-solid fa-chevron-down dropdown-icon"></i>
            </div>
            <ul class="submenu">
                <?php if(isset($dashboardMode) && $dashboardMode): ?>
                <li onclick="showCompanyOrg(this)"><a href="#">Company &amp; Org</a></li>
                <li onclick="showAccountsClients(this)"><a href="#">Accounts &amp; Clients</a></li>
                <?php else: ?>
                <li<?= si('company_list', $activePage) ?> onclick="window.location.href='company_list.php'">
                    <a href="company_list.php">Company &amp; Org</a>
                </li>
                <li<?= si('client_list', $activePage) ?> onclick="window.location.href='client_list.php'">
                    <a href="client_list.php">Accounts &amp; Clients</a>
                </li>
                <?php endif; ?>
            </ul>
        </li>

        <!-- Deals & Campaign -->
        <li class="dropdown-item<?= sd(['deal_pipeline','campaigns'], $activePage) ?>" id="sb-dealsMenu">
            <div class="dropdown-title" onclick="sbToggle('sb-dealsMenu')">
                <div class="dropdown-title-left">
                    <i class="fa-solid fa-bullhorn"></i>
                    <span>Deals &amp; Campaign</span>
                </div>
                <i class="fa-solid fa-chevron-down dropdown-icon"></i>
            </div>
            <ul class="submenu">
                <?php if(isset($dashboardMode) && $dashboardMode): ?>
                <li onclick="showDealPipeline(this)"><a href="#">Deal Pipeline</a></li>
                <li onclick="showComingSoon('Campaigns', this)"><a href="#">Campaigns</a></li>
                <?php else: ?>
                <li<?= si('deal_pipeline', $activePage) ?> onclick="window.location.href='deal_pipeline.php'">
                    <a href="deal_pipeline.php">Deal Pipeline</a>
                </li>
                <li<?= si('campaigns', $activePage) ?> onclick="window.location.href='campaigns.php'">
                    <a href="campaigns.php">Campaigns</a>
                </li>
                <?php endif; ?>
            </ul>
        </li>

        <!-- Task Manager -->
        <li class="<?= trim(sa('task_manager', $activePage)) ?>"
            onclick="window.location.href='task_manager.php'">
            <i class="fa-solid fa-list-check"></i>
            <a href="task_manager.php">Task Manager</a>
        </li>

        <!-- Analytics -->
        <li class="<?= trim(sa('analytics', $activePage)) ?>"
            onclick="<?php echo (isset($dashboardMode) && $dashboardMode) ? 'showComingSoon(\'Analytics\', this)' : 'window.location.href=\'analytics_reports.php\''; ?>">
            <i class="fa-solid fa-chart-column"></i>
            <a href="analytics_reports.php">Analytics &amp; Reports</a>
        </li>

        <!-- Settings -->
        <li class="<?= trim(sa('settings', $activePage)) ?>"
            onclick="window.location.href='settings.php'">
            <i class="fa-solid fa-gear"></i>
            <a href="settings.php">Settings</a>
        </li>
    </ul>

    <!-- Logout -->
    <ul class="sidebar-menu sidebar-logout">
        <li onclick="window.location.href='logout.php'">
            <i class="fa-solid fa-right-from-bracket"></i>
            <a href="logout.php">Logout</a>
        </li>
    </ul>

</div><!-- /sidebar -->

<script>
(function () {
    'use strict';

    /* All sidebar dropdown IDs */
    var SB_DROPDOWNS = ['sb-userMenu', 'sb-leadsMenu', 'sb-dealsMenu'];

    /* Dropdown toggle — closes all others, opens clicked one,
       and auto-navigates to the first submenu link if it was closed */
    window.sbToggle = function (id) {
        var target = document.getElementById(id);
        if (!target) return;

        var isOpen = target.classList.contains('open');

        /* Close every dropdown first */
        SB_DROPDOWNS.forEach(function (did) {
            var el = document.getElementById(did);
            if (el) el.classList.remove('open');
        });

        /* If it was closed, open it and navigate to first submenu item */
        if (!isOpen) {
            target.classList.add('open');

            /* Find the first <a> inside the submenu and navigate to it */
            var firstLink = target.querySelector('.submenu li:first-child a');
            if (firstLink && firstLink.getAttribute('href')) {
                window.location.href = firstLink.getAttribute('href');
            }
        }
    };

    /* Legacy alias: pages that still call toggleSubMenu() keep working */
    window.toggleSubMenu = function (id) { window.sbToggle('sb-' + id.replace(/^sb-/, '')); };

    /* Dark mode ও hamburger toggle topbar.php handle করে */
}());
</script>