<?php

/**
 * CropCast • Collapsible Sidebar (centered, polished)
 * Path: /Dashboard/partials/sidebar.php
 *
 * What’s improved:
 * - Centered pills/icons in BOTH collapsed and expanded states.
 * - One knob: --item-max-width controls pill width in expanded mode.
 * - Collapsed mode constrains pills to the rail and centers icons.
 * - Absolute links via $BASE.
 */

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

/* Change to '' if deployed at web root */
$BASE = '/ProjectFolder';

/* Active link helper */
$currentUri = $_SERVER['REQUEST_URI'] ?? '/';
function kts_active(string $needle, string $haystack): string
{
  return (stripos($haystack, $needle) !== false) ? 'active' : '';
}
?>
<style>
  /* =================== Sidebar Variables ===================
   Tweak these to restyle the whole sidebar quickly. */
  :root {
    /* Layout widths */
    --sb-width: 232px;
    /* expanded width of sidebar */
    --sb-collapsed: 72px;
    /* collapsed width (icons only rail) */

    /* Shared width for nav pills & bottom buttons (expanded state) */
    --item-max-width: 80%;
    /* e.g. 90%, 85%, 80% of sidebar width */

    /* Colors */
    --sb-bg: #ffffff;
    --sb-text: #0f172a;
    --sb-muted: #6b7280;
    --sb-border: #e5e7eb;
    --sb-hover: #eef2ff;
    /* active/hover pill color */
    --sb-brand-pill: #eef2ff;
    /* brand logo chip */
    --sb-accent: #2563eb;

    /* Effects */
    --sb-shadow: 0 6px 18px rgba(31, 41, 55, .08);
    --sb-radius: 12px;
    /* pill/button corner radius */
    --sb-icon-size: 18px;
    --sb-z: 1000;

    /* Buttons */
    --btn-padding: 12px;
    /* inner padding for Pin/Logout */
    --btn-gap: 10px;
    /* spacing between bottom buttons */
    --btn-radius: 12px;
  }

  /* =================== Page offset by sidebar =================== */
  body.kts-has-sidebar {
    margin-left: var(--sb-collapsed);
    transition: margin-left .18s ease;
    background: #f5f7fb;
    color: var(--sb-text);
    font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji";
  }

  body.kts-sidebar-expanded {
    margin-left: var(--sb-width);
  }

  /* =================== Sidebar shell =================== */
  .kts-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: var(--sb-collapsed);
    height: 100vh;
    background: var(--sb-bg);
    border-right: 1px solid var(--sb-border);
    box-shadow: var(--sb-shadow);
    z-index: var(--sb-z);
    padding: 12px 10px;
    display: grid;
    grid-template-rows: auto 1fr auto;
    gap: 8px;
    transition: width .18s ease;
  }

  .kts-sidebar:hover {
    width: var(--sb-width);
  }

  body.kts-sidebar-expanded .kts-sidebar {
    width: var(--sb-width);
  }

  /* =================== Brand =================== */
  .kts-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    border-radius: var(--sb-radius);
    color: var(--sb-text);
    text-decoration: none;

    /* Center the brand block inside the rail/sidebar */
    max-width: var(--item-max-width);
    margin: 0 auto;
  }

  .kts-logo {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--sb-brand-pill);
    color: var(--sb-accent);
    font-weight: 800;
  }

  .kts-title {
    font-weight: 800;
    letter-spacing: .2px;
    white-space: nowrap;
    display: none;
  }

  .kts-sidebar:hover .kts-title,
  body.kts-sidebar-expanded .kts-title {
    display: inline;
  }

  /* Collapsed: center the logo pill perfectly by trimming paddings/gaps */
  body.kts-has-sidebar:not(.kts-sidebar-expanded) .kts-sidebar:not(:hover) .kts-brand {
    justify-content: center;
    gap: 0;
    padding-left: 0;
    padding-right: 0;
    max-width: calc(var(--sb-collapsed) - 8px);
  }

  /* =================== Navigation =================== */
  .kts-nav {
    list-style: none;
    margin: 6px 0 0;
    padding: 0;
  }

  .kts-nav li {
    margin: 2px 0;
  }

  /* Base nav link pill (expanded defaults) */
  .kts-link {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: var(--sb-text);
    padding: 10px 12px;
    border-radius: 10px;
    transition: background .12s ease, transform .02s ease;

    /* Center pill block and keep it narrower than the sidebar */
    max-width: var(--item-max-width);
    margin: 0 auto;
  }

  .kts-link:hover {
    background: var(--sb-hover);
  }

  .kts-link.active {
    background: var(--sb-hover);
    font-weight: 700;
  }

  .kts-icon {
    width: 28px;
    min-width: 28px;
    height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: var(--sb-icon-size);
  }

  .kts-label {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: none;
  }

  .kts-sidebar:hover .kts-label,
  body.kts-sidebar-expanded .kts-label {
    display: inline;
  }

  /* Collapsed (icons only): center the emoji + pill inside the rail */
  body.kts-has-sidebar:not(.kts-sidebar-expanded) .kts-sidebar:not(:hover) .kts-link {
    justify-content: center;
    /* center icon horizontally */
    gap: 0;
    /* no gap without label */
    padding-left: 0;
    padding-right: 0;
    /* remove side padding for visual centering */
    max-width: calc(var(--sb-collapsed) - 8px);
    /* pill fits the rail with a small inset */
  }

  /* =================== Bottom Controls =================== */
  .kts-bottom {
    display: grid;
    gap: var(--btn-gap);
    margin-top: 8px;
  }

  /* Base buttons (Pin / Logout) — centered like nav pills */
  .kts-btn,
  .kts-logout {
    border: 1px solid var(--sb-border);
    background: #fff;
    color: var(--sb-text);
    border-radius: var(--btn-radius);
    padding: var(--btn-padding);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    text-decoration: none;

    max-width: var(--item-max-width);
    margin: 0 auto;
    /* center horizontally */
  }

  .kts-btn:hover,
  .kts-logout:hover {
    background: var(--sb-hover);
  }

  /* Hide labels in collapsed (show on hover/pin) */
  .kts-btn .kts-label,
  .kts-logout .kts-label {
    display: none;
  }

  .kts-sidebar:hover .kts-btn .kts-label,
  .kts-sidebar:hover .kts-logout .kts-label,
  body.kts-sidebar-expanded .kts-btn .kts-label,
  body.kts-sidebar-expanded .kts-logout .kts-label {
    display: inline;
  }

  /* Collapsed: center the icon button blocks too (match rail) */
  body.kts-has-sidebar:not(.kts-sidebar-expanded) .kts-sidebar:not(:hover) .kts-btn,
  body.kts-has-sidebar:not(.kts-sidebar-expanded) .kts-sidebar:not(:hover) .kts-logout {
    max-width: calc(var(--sb-collapsed) - 8px);
  }

  /* =================== Responsive =================== */
  @media (max-width: 860px) {

    /* On mobile, treat as a sticky top bar; items full-width look better */
    body.kts-has-sidebar,
    body.kts-sidebar-expanded {
      margin-left: 0;
    }

    .kts-sidebar {
      position: sticky;
      top: 0;
      width: 100%;
      height: auto;
      border-right: 0;
      border-bottom: 1px solid var(--sb-border);
      grid-template-rows: auto auto auto;
    }

    .kts-title,
    .kts-label {
      display: inline !important;
    }

    .kts-brand,
    .kts-link,
    .kts-btn,
    .kts-logout {
      max-width: 100%;
    }
  }
</style>

<aside class="kts-sidebar" id="ktsSidebar" aria-label="Primary sidebar">
  <!-- Brand -->
  <a class="kts-brand" href="<?php echo $BASE; ?>../../../adminDashboard/maindash/dashboard.php" title="Dashboard">
    <span class="kts-logo">C</span>
    <span class="kts-title">CropCast</span>
  </a>

  <!-- Navigation -->
  <ul class="kts-nav">
    <li><a class="kts-link <?php echo kts_active('/admin/adminDashboard/maindash/', $currentUri); ?>" href="<?php echo $BASE; ?>/admin/adminDashboard/maindash/dashboard.php" title="Dashboard"><span class="kts-icon">📊</span><span class="kts-label">Dashboard</span></a></li>
    <li><a class="kts-link <?php echo kts_active('/admin/adminDashboard/farms/', $currentUri); ?>" href="<?php echo $BASE; ?>/admin/adminDashboard/farms/farms.php" title="Farms"><span class="kts-icon">👤</span><span class="kts-label">Profile</span></a></li>
    <li><a class="kts-link <?php echo kts_active('/admin/adminDashboard/feedback/', $currentUri); ?>" href="<?php echo $BASE; ?>/admin/adminDashboard/feedback/feedback.php" title="Feedbaack"><span class="kts-icon">💬</span><span class="kts-label">Fields</span></a></li>
  </ul>

  <!-- Bottom controls -->
  <div class="kts-bottom">
    <a class="kts-logout" href="<?php echo $BASE; ?>/logout.php" title="Logout">
      <span class="kts-icon">🚪</span><span class="kts-label">Logout</span>
    </a>
    <button type="button" class="kts-btn" id="ktsPinBtn" title="Pin sidebar">
      <span class="kts-icon">📌</span><span class="kts-label"></span>
    </button>
    
  </div>
</aside>

<script>
  /**
   * Expand on hover + optional pinning
   * - Default: collapsed. Hover adds body.kts-sidebar-expanded.
   * - Pin keeps it expanded (restored via localStorage).
   */
  (function() {
    const body = document.body;
    const sb = document.getElementById('ktsSidebar');
    const pinBtn = document.getElementById('ktsPinBtn');

    body.classList.add('kts-has-sidebar');

    let pinned = localStorage.getItem('kts_sidebar_pinned') === '1';
    setPinned(pinned);

    let hoverTimer = null;

    sb.addEventListener('mouseenter', () => {
      if (!pinned) {
        clearTimeout(hoverTimer);
        body.classList.add('kts-sidebar-expanded');
      }
    });
    sb.addEventListener('mouseleave', () => {
      if (!pinned) {
        hoverTimer = setTimeout(() => body.classList.remove('kts-sidebar-expanded'), 80);
      }
    });

    pinBtn.addEventListener('click', () => {
      pinned = !pinned;
      setPinned(pinned);
      localStorage.setItem('kts_sidebar_pinned', pinned ? '1' : '0');
    });

    function setPinned(state) {
      if (state) {
        body.classList.add('kts-sidebar-expanded');
        pinBtn.innerHTML = '<span class="kts-icon">📌</span><span class="kts-label"></span>';
        pinBtn.title = '';
      } else {
        body.classList.remove('kts-sidebar-expanded');
        pinBtn.innerHTML = '<span class="kts-icon">📌</span><span class="kts-label"></span>';
        pinBtn.title = '';
      }
    }
  })();
</script>