<?php
// Get current page name to highlight active nav item
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Define info panels for each page
$info_panels = [
    'registrar_dashboard' => [
        'title' => 'Registrar Dashboard',
        'description' => 'Overview of student management and enrollment.',
        'info' => 'Monitor student statistics, enrollment trends, and administrative tasks.',
        'guide' => 'Check daily enrollment patterns and student data completion.',
        'icon' => 'dashboard'
    ],
    'manage' => [
        'title' => 'Student Masterlist',
        'description' => 'Manage student records with RFID integration.',
        'info' => 'Add, edit, and organize student information. Manage RFID tags and export data.',
        'guide' => 'Use filters to find students. Assign RFID tags for auto-attendance.',
        'icon' => 'person'
    ],
    'sections' => [
        'title' => 'Manage Section',
        'description' => 'Organize students into classes and sections.',
        'info' => 'Create sections, assign advisers, and manage student groupings.',
        'guide' => 'Assign experienced teachers as advisers.',
        'icon' => 'group'
    ],
    'subjects' => [
        'title' => 'Manage Subjects',
        'description' => 'Define subjects and assign teachers.',
        'info' => 'Create subject catalogs and assign teachers to specific subjects.',
        'guide' => 'Organize by grade level. Assign teachers before school year.',
        'icon' => 'book'
    ]
];

$current_info = $info_panels[$current_page] ?? $info_panels['registrar_dashboard'];
?>
<aside class="sidebar" aria-label="Registrar Sidebar Navigation">
  <!-- Info Panel -->
  <div class="sidebar-info-panel">
    <div class="info-header">
      <div class="info-icon">
        <?php if ($current_info['icon'] === 'dashboard'): ?>
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <rect x="3" y="3" width="8" height="8" rx="1"/>
            <rect x="13" y="3" width="8" height="8" rx="1"/>
            <rect x="3" y="13" width="8" height="8" rx="1"/>
            <rect x="13" y="13" width="8" height="8" rx="1"/>
          </svg>
        <?php elseif ($current_info['icon'] === 'person'): ?>
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="8" r="4"/>
            <rect x="5" y="14" width="14" height="7" rx="3"/>
          </svg>
        <?php elseif ($current_info['icon'] === 'group'): ?>
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <rect x="4" y="5" width="16" height="3" rx="1"/>
            <rect x="4" y="10" width="16" height="3" rx="1"/>
            <rect x="4" y="15" width="16" height="3" rx="1"/>
          </svg>
        <?php elseif ($current_info['icon'] === 'book'): ?>
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
          </svg>
        <?php endif; ?>
      </div>
      <h3 class="info-title"><?php echo $current_info['title']; ?></h3>
    </div>
    <p class="info-description"><?php echo $current_info['description']; ?></p>
    <div class="info-details">
      <div class="info-section">
        <h4>Information:</h4>
        <p><?php echo $current_info['info']; ?></p>
      </div>
      <div class="info-guide">
        <h4>Quick Guide:</h4>
        <p><?php echo $current_info['guide']; ?></p>
      </div>
    </div>
  </div>

  <nav>
    <ul class="nav">
      <li><a href="registrar_dashboard.php" class="<?php echo ($current_page == 'registrar_dashboard') ? 'active' : ''; ?>">
        <span class="nav-icon">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <rect x="3" y="3" width="8" height="8" rx="1"/>
            <rect x="13" y="3" width="8" height="8" rx="1"/>
            <rect x="3" y="13" width="8" height="8" rx="1"/>
            <rect x="13" y="13" width="8" height="8" rx="1"/>
          </svg>
        </span>Dashboard
      </a></li>
      
      <li><a href="manage.php" class="<?php echo ($current_page == 'manage') ? 'active' : ''; ?>">
        <span class="nav-icon">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="8" r="4"/>
            <rect x="5" y="14" width="14" height="7" rx="3"/>
          </svg>
        </span>Student Masterlist
      </a></li>
      
      <li><a href="sections.php" class="<?php echo ($current_page == 'sections') ? 'active' : ''; ?>">
        <span class="nav-icon">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <rect x="4" y="5" width="16" height="3" rx="1"/>
            <rect x="4" y="10" width="16" height="3" rx="1"/>
            <rect x="4" y="15" width="16" height="3" rx="1"/>
          </svg>
        </span>Manage Section
      </a></li>
      
      <li><a href="subjects.php" class="<?php echo ($current_page == 'subjects') ? 'active' : ''; ?>">
        <span class="nav-icon">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
          </svg>
        </span>Manage Subjects
      </a></li>
    </ul>
    
    <!-- Logout section at the bottom -->
    <div class="nav-footer">
      <ul class="nav">
        <li><a href="includes/logout.php" class="logout-link" onclick="return confirmLogout()">
          <span class="nav-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
              <polyline points="16,17 21,12 16,7"/>
              <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
          </span>Logout
        </a></li>
      </ul>
    </div>
  </nav>
  
  <style>
    .sidebar {
      display: flex;
      flex-direction: column;
      height: 100vh;
    }
    
    .sidebar nav {
      flex: 1;
      display: flex;
      flex-direction: column;
    }
    
    .nav-footer {
      margin-top: auto;
      padding-top: 5px;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .logout-link {
      color: #ef4444 !important;
      transition: all 0.3s ease;
      padding-top: 20px !important;  /* <-- This line was added */
      margin-bottom: 45px;
    }
    
    .logout-link:hover {
      background-color: rgba(239, 68, 68, 0.1) !important;
      color: #f87171 !important;
    }
    
    .logout-link .nav-icon svg {
      stroke: #ef4444;
    }
    
    .logout-link:hover .nav-icon svg {
      stroke: #f87171;
    }

    /* Logout modal */
    .logout-modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.35);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 1000;
    }
    .logout-modal {
      background: #f4f4f4;
      border-radius: 10px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.2);
      width: 420px;
      max-width: calc(100% - 40px);
      border: 2px solid #2aa7b8;
    }
    .logout-modal .content {
      padding: 28px 24px 20px 24px;
      text-align: center;
      font-family: 'Playfair Display', Georgia, serif;
      color: #222;
      font-size: 22px;
      line-height: 1.35;
    }
    .logout-modal .actions {
      display: flex;
      gap: 16px;
      justify-content: center;
      padding: 0 24px 24px 24px;
    }
    .logout-modal .logout-btn {
      appearance: none;
      border: 2px solid #2aa7b8;
      border-radius: 10px;
      padding: 10px 20px;
      font-size: 16px;
      cursor: pointer;
      width: 140px;
      transition: background 0.2s ease, color 0.2s ease;
    }
    .logout-modal .logout-btn-cancel {
      background: #fff;
      color: #0f172a;
    }
    .logout-modal .logout-btn-cancel:hover { background: #f1f5f9; }
    .logout-modal .logout-btn-confirm {
      background: #17a2b8;
      color: #fff;
      border-color: #17a2b8;
    }
    .logout-modal .logout-btn-confirm:hover { background: #1495a9; }
  </style>
  
  <script>
    (function() {
      let pendingLogoutHref = null;

      function showLogoutModal(href) {
        pendingLogoutHref = href;
        const overlay = document.getElementById('logoutModal');
        if (!overlay) return true; // fallback: allow default navigation
        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        return false;
      }

      function hideLogoutModal() {
        const overlay = document.getElementById('logoutModal');
        if (!overlay) return;
        overlay.style.display = 'none';
        document.body.style.overflow = '';
      }

      document.addEventListener('DOMContentLoaded', function() {
        const logoutLink = document.querySelector('.logout-link');
        if (logoutLink) {
          logoutLink.addEventListener('click', function(e) {
            e.preventDefault();
            showLogoutModal(logoutLink.getAttribute('href'));
          });
        }

        const overlay = document.getElementById('logoutModal');
        if (!overlay) return;

        const cancelBtn = overlay.querySelector('.logout-btn-cancel');
        const confirmBtn = overlay.querySelector('.logout-btn-confirm');

        if (cancelBtn) cancelBtn.addEventListener('click', hideLogoutModal);
        if (confirmBtn) confirmBtn.addEventListener('click', function() {
          if (pendingLogoutHref) {
            window.location.href = pendingLogoutHref;
          }
        });

        overlay.addEventListener('click', function(e) {
          if (e.target === overlay) hideLogoutModal();
        });

        document.addEventListener('keydown', function(e) {
          if (e.key === 'Escape') hideLogoutModal();
        });
      });
    })();
  </script>
  
  <!-- Logout Confirmation Modal -->
  <div id="logoutModal" class="logout-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="logoutModalTitle">
    <div class="logout-modal">
      <div class="content" id="logoutModalTitle">Are you sure you want to logout?</div>
      <div class="actions">
        <button type="button" class="logout-btn logout-btn-cancel">Cancel</button>
        <button type="button" class="logout-btn logout-btn-confirm">Logout</button>
      </div>
    </div>
  </div>
</aside>
