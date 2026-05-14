<?php
/**
 * ============================================================
 * UNIFIED NOTIFICATION SYSTEM — Systellio CRM
 * ============================================================
 * যেকোনো dashboard page-এ navbar-এর bell icon এর জায়গায়
 * নিচের একটা line include করুন:
 *
 *   <?php include 'notifications.php'; ?>
 *
 * এই file টা নিজেই DB query করে, CSS inject করে,
 * HTML render করে এবং JS attach করে।
 * আলাদা কিছু করতে হবে না।
 * ============================================================
 */

// ── 1. DB থেকে Notification Data fetch করা ──────────────────
$_notif_items  = [];
$_notif_count  = 0;

if (isset($conn)) {

    // নতুন Task (শেষ ২৪ ঘণ্টা)
    try {
        $nq = mysqli_query($conn,
            "SELECT title, assigned_to, created_at
             FROM tasks
             WHERE created_at >= NOW() - INTERVAL 24 HOUR
             ORDER BY created_at DESC LIMIT 5");
        if ($nq) while ($r = mysqli_fetch_assoc($nq)) {
            $_notif_items[] = [
                'icon'  => 'fa-clipboard-list',
                'color' => '#3b82f6',
                'label' => 'New Task',
                'text'  => htmlspecialchars($r['title']) . ' → ' . htmlspecialchars($r['assigned_to']),
                'time'  => $r['created_at'],
            ];
        }
    } catch (Exception $e) {}

    // নতুন Deal (শেষ ৪৮ ঘণ্টা)
    try {
        $nq = mysqli_query($conn,
            "SELECT deal_name, stage, created_at
             FROM deals
             WHERE created_at >= NOW() - INTERVAL 48 HOUR
             ORDER BY created_at DESC LIMIT 5");
        if ($nq) while ($r = mysqli_fetch_assoc($nq)) {
            $_notif_items[] = [
                'icon'  => 'fa-handshake',
                'color' => '#10b981',
                'label' => 'New Deal',
                'text'  => htmlspecialchars($r['deal_name']) . ' — Stage: ' . htmlspecialchars($r['stage']),
                'time'  => $r['created_at'],
            ];
        }
    } catch (Exception $e) {}

    // নতুন User (শেষ ৭২ ঘণ্টা)
    try {
        $nq = mysqli_query($conn,
            "SELECT name, role, created_at
             FROM users
             WHERE created_at >= NOW() - INTERVAL 72 HOUR
             ORDER BY created_at DESC LIMIT 5");
        if ($nq) while ($r = mysqli_fetch_assoc($nq)) {
            $_notif_items[] = [
                'icon'  => 'fa-user-plus',
                'color' => '#f59e0b',
                'label' => 'New User',
                'text'  => htmlspecialchars($r['name']) . ' joined as ' . ucfirst(str_replace('_', ' ', $r['role'])),
                'time'  => $r['created_at'],
            ];
        }
    } catch (Exception $e) {}

    // নতুন Campaign (শেষ ৪৮ ঘণ্টা)
    try {
        $nq = mysqli_query($conn,
            "SELECT campaign_name, campaign_type, status, created_at
             FROM campaigns
             WHERE created_at >= NOW() - INTERVAL 48 HOUR
             ORDER BY created_at DESC LIMIT 3");
        if ($nq) while ($r = mysqli_fetch_assoc($nq)) {
            $_notif_items[] = [
                'icon'  => 'fa-bullhorn',
                'color' => '#8b5cf6',
                'label' => 'New Campaign',
                'text'  => htmlspecialchars($r['campaign_name']) . ' (' . htmlspecialchars($r['campaign_type']) . ')',
                'time'  => $r['created_at'],
            ];
        }
    } catch (Exception $e) {}

    // নতুন Company (শেষ ৭২ ঘণ্টা)
    try {
        $nq = mysqli_query($conn,
            "SELECT company_name, assigned_agent, created_at
             FROM companies
             WHERE created_at >= NOW() - INTERVAL 72 HOUR
             ORDER BY created_at DESC LIMIT 3");
        if ($nq) while ($r = mysqli_fetch_assoc($nq)) {
            $_notif_items[] = [
                'icon'  => 'fa-building',
                'color' => '#06b6d4',
                'label' => 'New Company',
                'text'  => htmlspecialchars($r['company_name']) . ' added',
                'time'  => $r['created_at'],
            ];
        }
    } catch (Exception $e) {}

    // সময় অনুযায়ী sort (সবচেয়ে নতুন আগে)
    usort($_notif_items, fn($a, $b) => strtotime($b['time']) - strtotime($a['time']));

    // সর্বোচ্চ ১৫টা দেখাবে
    $_notif_items = array_slice($_notif_items, 0, 15);
    $_notif_count = count($_notif_items);
}

// ── 2. Notification items HTML তৈরি করা ────────────────────
$_notif_html = '';
foreach ($_notif_items as $_n) {
    $_diff    = time() - strtotime($_n['time']);
    $_timeAgo = $_diff < 3600
        ? floor($_diff / 60) . 'm ago'
        : ($_diff < 86400 ? floor($_diff / 3600) . 'h ago' : floor($_diff / 86400) . 'd ago');

    // hex color → rgba background
    [$_r, $_g, $_b] = sscanf(ltrim($_n['color'], '#'), '%02x%02x%02x');
    $_notif_html .= "
        <div class='sn-item'>
            <div class='sn-icon' style='background:rgba({$_r},{$_g},{$_b},0.12);color:{$_n['color']};'>
                <i class='fa-solid {$_n['icon']}'></i>
            </div>
            <div class='sn-body'>
                <div class='sn-label'>{$_n['label']}</div>
                <div class='sn-text'>{$_n['text']}</div>
                <div class='sn-time'>{$_timeAgo}</div>
            </div>
        </div>";
}
?>

<?php /* ── 3. CSS (একবারই inject হয়) ── */ ?>
<?php if (!defined('SN_CSS_LOADED')): define('SN_CSS_LOADED', true); ?>
<style>
/* ================================================================
   SYSTELLIO NOTIFICATION SYSTEM — notifications.php
   ================================================================ */

/* Wrapper — position: relative দরকার badge আর panel-এর জন্য */
.sn-wrapper {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

/* Bell button */
.sn-bell {
    cursor: pointer;
    font-size: 20px;
    color: #6b7280;
    transition: color 0.25s;
    position: relative;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
}
.sn-bell:hover { color: #3b82f6; background: #eff6ff; }

/* Badge */
.sn-badge {
    position: absolute;
    top: 0px;
    right: 0px;
    background: #ef4444;
    color: #fff;
    font-size: 9px;
    font-weight: 700;
    min-width: 17px;
    height: 17px;
    padding: 0 4px;
    border-radius: 50px;
    border: 2px solid #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    pointer-events: none;
}

/* Dropdown panel */
.sn-panel {
    display: none;
    position: fixed;
    top: 68px;
    right: 18px;
    width: 340px;
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 12px 35px rgba(0,0,0,0.14);
    z-index: 99999;
    overflow: hidden;
    animation: snSlideIn 0.2s ease;
}
.sn-panel.sn-open { display: block; }

@keyframes snSlideIn {
    from { opacity: 0; transform: translateY(-8px); }
    to   { opacity: 1; transform: translateY(0);    }
}

/* Panel header */
.sn-header {
    padding: 16px 18px 12px;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.sn-header h3 {
    font-size: 15px;
    font-weight: 700;
    color: #111827;
    margin: 0;
}
.sn-header-right {
    display: flex;
    align-items: center;
    gap: 10px;
}
.sn-count-pill {
    background: #eff6ff;
    color: #3b82f6;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 20px;
}
.sn-mark-read {
    font-size: 11px;
    color: #9ca3af;
    cursor: pointer;
    font-weight: 600;
    transition: color 0.2s;
    background: none;
    border: none;
    padding: 0;
    font-family: inherit;
}
.sn-mark-read:hover { color: #3b82f6; }

/* Scrollable list */
.sn-list {
    max-height: 370px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #e5e7eb transparent;
}
.sn-list::-webkit-scrollbar { width: 4px; }
.sn-list::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 4px; }

/* Single item */
.sn-item {
    display: flex;
    gap: 13px;
    padding: 13px 18px;
    border-bottom: 1px solid #f9fafb;
    cursor: pointer;
    transition: background 0.15s;
}
.sn-item:hover { background: #f9fafb; }
.sn-item:last-child { border-bottom: none; }

/* Item icon */
.sn-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

/* Item body */
.sn-body { flex: 1; min-width: 0; }
.sn-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #9ca3af;
    margin-bottom: 2px;
}
.sn-text {
    font-size: 13px;
    font-weight: 500;
    color: #111827;
    line-height: 1.4;
    margin-bottom: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.sn-time { font-size: 11px; color: #9ca3af; font-weight: 500; }

/* Empty state */
.sn-empty {
    padding: 36px 20px;
    text-align: center;
    color: #9ca3af;
}
.sn-empty i { font-size: 30px; display: block; margin-bottom: 10px; opacity: 0.5; }
.sn-empty p { font-size: 13px; font-weight: 500; }

/* Footer */
.sn-footer {
    padding: 12px 18px;
    border-top: 1px solid #f3f4f6;
    text-align: center;
}
.sn-footer a {
    font-size: 12px;
    font-weight: 600;
    color: #3b82f6;
    text-decoration: none;
    transition: color 0.2s;
}
.sn-footer a:hover { color: #1d4ed8; }

/* ── Dark Mode ── */
body.dark-mode .sn-bell { color: #94a3b8; }
body.dark-mode .sn-bell:hover { color: #f8fafc; background: #1e3a8a22; }
body.dark-mode .sn-badge { border-color: #1e293b; }

body.dark-mode .sn-panel {
    background: #1e293b;
    border-color: #334155;
    box-shadow: 0 12px 35px rgba(0,0,0,0.45);
}
body.dark-mode .sn-header { border-color: #334155; }
body.dark-mode .sn-header h3 { color: #f8fafc; }
body.dark-mode .sn-count-pill { background: #1e3a8a; color: #93c5fd; }
body.dark-mode .sn-item { border-color: #1e293b; }
body.dark-mode .sn-item:hover { background: #0f172a; }
body.dark-mode .sn-text { color: #e2e8f0; }
body.dark-mode .sn-list::-webkit-scrollbar-thumb { background: #334155; }
body.dark-mode .sn-footer { border-color: #334155; }
body.dark-mode .sn-footer a { color: #60a5fa; }
</style>
<?php endif; ?>

<?php
// সবচেয়ে নতুন notification এর timestamp
$_latest_time = !empty($_notif_items) ? strtotime($_notif_items[0]['time']) : 0;
// প্রতিটা item এর timestamp array — JS এ unread count বের করতে লাগবে
$_ts_array = implode(',', array_map(fn($n) => strtotime($n['time']), $_notif_items));
?>

<?php /* ── 4. HTML — Bell + Panel ── */ ?>
<div class="sn-wrapper" id="snWrapper">

    <!-- Bell Button -->
    <div class="sn-bell" id="snBell" title="Notifications" onclick="snToggle(event)">
        <i class="fa-regular fa-bell"></i>
        <span class="sn-badge" id="snBadge" style="display:none;"></span>
    </div>

    <!-- Notification Panel -->
    <div class="sn-panel" id="snPanel" onclick="event.stopPropagation()">

        <div class="sn-header">
            <h3>Notifications</h3>
            <div class="sn-header-right">
                <span class="sn-count-pill" id="snCountPill" style="display:none;"></span>
                <button class="sn-mark-read" onclick="snMarkAllRead()">Mark all read</button>
            </div>
        </div>

        <div class="sn-list" id="snList">
            <?php if ($_notif_count > 0): ?>
                <?php echo $_notif_html; ?>
            <?php else: ?>
                <div class="sn-empty">
                    <i class="fa-regular fa-bell-slash"></i>
                    <p>No new notifications</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="sn-footer">
            <a href="user_activity.php">View all activity logs →</a>
        </div>
    </div>
</div>

<?php /* ── 5. JavaScript ── */ ?>
<script>
(function () {
    'use strict';

    var STORAGE_KEY    = 'sn_last_seen';
    var latestTime     = <?php echo $_latest_time; ?>;
    var allTimestamps  = [<?php echo $_ts_array; ?>]; // প্রতিটা notification এর unix timestamp

    /* ── Unread count বের করো: lastSeen এর পরে কতটা এসেছে ── */
    function snUnreadCount() {
        var lastSeen = parseInt(localStorage.getItem(STORAGE_KEY) || '0', 10);
        return allTimestamps.filter(function(t) { return t > lastSeen; }).length;
    }

    /* ── Badge ও pill আপডেট করো ── */
    function snUpdateBadge() {
        var badge = document.getElementById('snBadge');
        var pill  = document.getElementById('snCountPill');
        var count = snUnreadCount();

        if (count > 0) {
            if (badge) { badge.textContent = count; badge.style.display = 'flex'; }
            if (pill)  { pill.textContent  = count + ' new'; pill.style.display = 'inline-block'; }
        } else {
            if (badge) badge.style.display = 'none';
            if (pill)  pill.style.display  = 'none';
        }
    }

    /* ── Page load এ badge সেট করো ── */
    function snCheckRead() {
        snUpdateBadge();
    }

    /* ── Panel open/close ── */
    window.snToggle = function (e) {
        if (e) { e.stopPropagation(); e.preventDefault(); }
        var panel = document.getElementById('snPanel');
        var badge = document.getElementById('snBadge');
        var pill  = document.getElementById('snCountPill');
        if (!panel) return;

        var isOpen = panel.classList.contains('sn-open');
        panel.classList.toggle('sn-open');

        if (!isOpen) {
            // Panel খুলল — এখনই "সব দেখা হয়েছে" mark করো
            if (latestTime > 0) {
                localStorage.setItem(STORAGE_KEY, latestTime);
            }
            if (badge) badge.style.display = 'none';
            if (pill)  pill.style.display  = 'none';
        }
    };

    /* ── Mark all read button ── */
    window.snMarkAllRead = function () {
        var list  = document.getElementById('snList');
        var badge = document.getElementById('snBadge');
        var pill  = document.getElementById('snCountPill');

        // localStorage এ save করো যাতে সব page এ badge চলে যায়
        if (latestTime > 0) {
            localStorage.setItem(STORAGE_KEY, latestTime);
        }

        if (list)  list.innerHTML =
            '<div class="sn-empty"><i class="fa-regular fa-bell-slash"></i><p>No new notifications</p></div>';
        if (badge) badge.style.display = 'none';
        if (pill)  pill.style.display  = 'none';
    };

    /* ── Page click করলে panel বন্ধ ── */
    document.addEventListener('click', function (e) {
        var wrapper = document.getElementById('snWrapper');
        var panel   = document.getElementById('snPanel');
        if (!panel || !wrapper) return;
        if (!wrapper.contains(e.target)) {
            panel.classList.remove('sn-open');
        }
    });

    /* ── Page load হতেই check করো ── */
    snCheckRead();

}());
</script>