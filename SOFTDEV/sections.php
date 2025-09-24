<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$page_title = "Manage Section";
$additional_css = "css/sections.css";

include 'db/db_connect.php';

// Build the WHERE clause for filtering
$where = [];
if (!empty($_GET['grade'])) {
  $grade = $conn->real_escape_string($_GET['grade']);
  $where[] = "s.grade_level = '$grade'";
}
if (!empty($_GET['section'])) {
  $section = $conn->real_escape_string($_GET['section']);
  $where[] = "s.section = '$section'";
}

// Build the main SQL query with filtering, student counts, and adviser info
$sql = "SELECT s.id, s.grade_level, s.section,
        COALESCE(COUNT(DISTINCT st.student_id), 0) AS total_students,
        GROUP_CONCAT(DISTINCT t.first_name, ' ', t.last_name SEPARATOR ', ') AS teachers,
        adviser.first_name AS adviser_first_name,
        adviser.last_name AS adviser_last_name,
        adviser.gender AS adviser_gender
        FROM section s
        LEFT JOIN students st ON st.section_id = s.id
        LEFT JOIN teachers t ON FIND_IN_SET(s.section, REPLACE(t.sections, ', ', ','))
        LEFT JOIN teachers adviser ON adviser.advisory_section_id = s.id
        ";

if (!empty($where)) {
  $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " GROUP BY s.id, s.grade_level, s.section
          ORDER BY
          CASE 
            WHEN s.grade_level = 'Nursery' THEN 1
            WHEN s.grade_level = 'Kinder 1' THEN 2
            WHEN s.grade_level = 'Kinder 2' THEN 3
            WHEN s.grade_level REGEXP '^[0-9]+$' THEN 4
            ELSE 5
          END,
          CAST(s.grade_level AS UNSIGNED),
          s.section ASC";

$result = $conn->query($sql);

// Function to get subjects for a specific section from database
function getSubjectsForSection($section_id, $conn) {
    $section_id = intval($section_id);
    $sql = "SELECT DISTINCT subject_name FROM subjects WHERE section_id = $section_id ORDER BY subject_name";
    $result = $conn->query($sql);
    
    $subjects = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row['subject_name'];
        }
    }
    
    // Debug: Log if no subjects found for this section
    if (empty($subjects)) {
        error_log("No subjects found for section_id: $section_id");
    }
    
    return $subjects;
}

?>
<!doctype html>
<html lang="en">
<head>
  <title><?php echo $page_title; ?></title>
  <?php include 'includes/head.php'; ?>
  <link rel="stylesheet" href="<?php echo $additional_css; ?>">
  <link rel="stylesheet" href="css/sections-inline.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <?php include 'includes/header.php'; ?>

  <div class="layout">
    <?php 
    // Use registrar sidebar if user is a registrar
    if ($_SESSION['role'] === 'registrar') {
        include 'includes/registrar_sidebar.php';
    } else {
        include 'includes/sidebar.php';
    }
    ?>

    <main>
      <div class="page-header">
        <h1 class="page-title"><?php echo $page_title; ?></h1>
      </div>

      <section class="filters" aria-label="Section Filter">
        <div class="filters-title">Filter Sections</div>
        <form method="get" action="" class="filters-form">
          <div class="filters-row">
            <div class="filter-group">
              <label for="grade" class="filter-label">Grade Level</label>
              <select id="grade" name="grade" class="filter-select" onchange="updateSectionOptions()">
                <option value="">--Select--</option>
                <?php
                $grades = $conn->query("
                  SELECT DISTINCT grade_level 
                  FROM section 
                  ORDER BY 
                    CASE 
                      WHEN grade_level = 'Nursery' THEN 1
                      WHEN grade_level = 'Kinder 1' THEN 2
                      WHEN grade_level = 'Kinder 2' THEN 3
                      WHEN grade_level REGEXP '^[0-9]+$' THEN 4
                      ELSE 5
                    END,
                    CAST(grade_level AS UNSIGNED)
                ");
                while ($g = $grades->fetch_assoc()) {
                  $selected = (isset($_GET['grade']) && $_GET['grade'] == $g['grade_level']) ? "selected" : "";
                  $gradeLabel = is_numeric($g['grade_level']) ? "Grade {$g['grade_level']}" : $g['grade_level'];
                  echo "<option value='{$g['grade_level']}' $selected>{$gradeLabel}</option>";
                }
                ?>
              </select>
            </div>

            <div class="filter-group">
              <label for="section" class="filter-label">Section</label>
              <select id="section" name="section" class="filter-select">
                <option value="">--Select--</option>
                <?php
                if (isset($_GET['grade']) && $_GET['grade'] != "") {
                  $grade = $conn->real_escape_string($_GET['grade']);
                  $sections = $conn->query("SELECT DISTINCT section FROM section WHERE grade_level = '$grade' ORDER BY section ASC");
                  while ($s = $sections->fetch_assoc()) {
                    $selected = (isset($_GET['section']) && $_GET['section'] == $s['section']) ? "selected" : "";
                    echo "<option value='{$s['section']}' $selected>{$s['section']}</option>";
                  }
                }
                ?>
              </select>
            </div>

            <div class="filter-actions">
              <button class="btn btn-primary" type="submit">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polygon points="22,3 2,3 10,12.46 10,19 14,21 14,12.46 22,3"></polygon>
                </svg>
                Filter
              </button>
              <button class="btn btn-success" type="button" onclick="openGradeModal()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="12" y1="5" x2="12" y2="19"></line>
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Add Section
              </button>
              <button class="btn btn-secondary" type="button" onclick="clearFilters()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="18" y1="6" x2="6" y2="18"></line>
                  <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
                Clear
              </button>
            </div>
          </div>
        </form>
      </section>

      <section class="sections-grid">
        <?php
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $gradeLabel = is_numeric($row['grade_level']) ? "Grade " . htmlspecialchars($row['grade_level']) : htmlspecialchars($row['grade_level']);
                $sectionName = htmlspecialchars($row['section']);
                $totalStudents = htmlspecialchars($row['total_students']);
                $teachers = htmlspecialchars($row['teachers'] ?: 'No teacher assigned');
                
                // Get adviser information
                $adviserName = '';
                if (!empty($row['adviser_first_name']) && !empty($row['adviser_last_name'])) {
                    $prefix = ($row['adviser_gender'] === 'Female') ? 'Ms.' : 'Mr.';
                    $adviserName = htmlspecialchars($prefix . ' ' . $row['adviser_first_name'] . ' ' . $row['adviser_last_name']);
                } else {
                    $adviserName = 'No adviser assigned';
                }
                
                $subjects = getSubjectsForSection($row['id'], $conn);
                
                echo "<div class='section-card' data-section-id='{$row['id']}' onclick='viewSectionDetails({$row['id']})' style='cursor: pointer;'>";
                echo "<div class='card-header'>";
                echo "<h3 class='section-title'>{$gradeLabel} - {$sectionName}</h3>";
                echo "<div class='card-actions'>";
                echo "<button class='btn-action' onclick='event.stopPropagation(); viewSubjects({$row['id']})' title='View Subjects'>";
                echo "<i class='fa-solid fa-graduation-cap'></i>";
                echo "</button>";
                echo "<button class='btn-action' onclick='event.stopPropagation(); showSectionMenu({$row['id']})' title='More Options'>";
                echo "<i class='fa-solid fa-ellipsis-v'></i>";
                echo "</button>";
                echo "</div>";
                echo "</div>";
                
                echo "<div class='card-content'>";
                echo "<div class='section-info'>";
                echo "<div class='info-item'>";
                echo "<i class='fa-solid fa-users'></i>";
                echo "<span>{$totalStudents} Students</span>";
                echo "</div>";
                echo "<div class='info-item'>";
                echo "<i class='fa-solid fa-chalkboard-user'></i>";
                echo "<span>{$adviserName}</span>";
                echo "</div>";
                echo "</div>";
                
                echo "<div class='subjects-section'>";
                echo "<h4 class='subjects-title'>Subjects</h4>";
                echo "<div class='subjects-list'>";
                foreach ($subjects as $subject) {
                    echo "<span class='subject-tag'>{$subject}</span>";
                }
                echo "</div>";
                echo "</div>";
                echo "</div>";
                
                echo "</div>";
            }
        } else {
            echo "<div class='no-sections'>";
            echo "<i class='fa-solid fa-inbox'></i>";
            echo "<h3>No sections found</h3>";
            echo "<p>Try adjusting your filters or add a new section.</p>";
            echo "</div>";
        }
        ?>
      </section>
    </main>
  </div>
  
  <!-- Success/Error Popup -->
  <div id="messagePopup" class="message-popup" style="display: none;">
    <div class="message-content">
      <div class="message-icon"></div>
      <div class="message-text"></div>
      <button class="message-close" onclick="closeMessagePopup()">&times;</button>
    </div>
  </div>

  <!-- Grade Level Modal -->
  <div id="gradeModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Add New Section</h2>
        <button class="close-btn" onclick="closeGradeModal()">&times;</button>
      </div>
      <div class="modal-body">
        <form action="functions/add_grade.php" method="post">
          <div class="form-group">
            <label for="gradeLevel" class="form-label">Grade Level</label>
            <input type="text" id="gradeLevel" name="gradeLevel" class="form-input" placeholder="Enter grade level" required>
          </div>
          <div class="form-group">
            <label for="modalSection" class="form-label">Section</label>
            <input type="text" id="modalSection" name="section" class="form-input" placeholder="Enter section" required>
          </div>
          <div class="form-group">
            <label for="modalpassword" class="form-label">Password</label>
            <div class="password-input-container">
              <input type="password" id="modalpassword" name="password" class="form-input" placeholder="Enter password" required>
              <button type="button" class="eye-icon" onclick="togglePasswordVisibility('modalpassword')">
                <i class="fa-solid fa-eye" id="modalpassword-eye"></i>
              </button>
            </div>
          </div>
          <div class="form-group">
            <label for="modalretypepassword" class="form-label">Retype Password</label>
            <div class="password-input-container">
              <input type="password" id="modalretypepassword" name="retype_password" class="form-input" placeholder="Retype password" required>
              <button type="button" class="eye-icon" onclick="togglePasswordVisibility('modalretypepassword')">
                <i class="fa-solid fa-eye" id="modalretypepassword-eye"></i>
              </button>
            </div>
            <div class="password-error" id="password-error">Passwords do not match</div>
            <div class="password-match" id="password-match">Passwords match!</div>
          </div>
          <div class="form-actions">
            <button class="btn btn-success" type="submit" name="save_section">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20,6 9,17 4,12"></polyline>
              </svg>
              Save Section
            </button>
            <button class="btn btn-secondary" type="button" onclick="closeGradeModal()">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Subjects Modal -->
  <div id="subjectsModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="subjectsModalTitle">Section Subjects</h2>
        <button class="close-btn" onclick="closeSubjectsModal()">&times;</button>
      </div>
      <div class="modal-body">
        <div id="subjectsContent">
          <!-- Subjects will be loaded here -->
        </div>
      </div>
    </div>
  </div>

  <!-- Section Menu -->
  <div id="sectionMenu" class="context-menu" style="display: none;">
    <div class="menu-item" onclick="editSection()">
      <i class="fa-solid fa-edit"></i>
      <span>Edit Section</span>
    </div>
    <div class="menu-item" onclick="viewStudents()">
      <i class="fa-solid fa-users"></i>
      <span>View Students</span>
    </div>
    <div class="menu-item danger" onclick="deleteSection()">
      <i class="fa-solid fa-trash"></i>
      <span>Delete Section</span>
    </div>
  </div>

  <!-- Edit Section Modal -->
  <div id="editSectionModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Edit Section</h2>
        <button class="close-btn" onclick="closeEditSectionModal()">&times;</button>
      </div>
      <div class="modal-body">
        <form id="editSectionForm">
          <input type="hidden" id="editSectionId" name="section_id">
          <div class="form-group">
            <label for="editGradeLevel" class="form-label">Grade Level</label>
            <input type="text" id="editGradeLevel" name="grade_level" class="form-input" placeholder="Enter grade level" required>
            <div class="error-message" id="gradeLevelError"></div>
          </div>
          <div class="form-group">
            <label for="editSection" class="form-label">Section</label>
            <input type="text" id="editSection" name="section" class="form-input" placeholder="Enter section" required>
            <div class="error-message" id="sectionError"></div>
          </div>
          <div class="form-group">
            <label for="editOldPassword" class="form-label">Current Password</label>
            <input type="password" id="editOldPassword" name="old_password" class="form-input" placeholder="Enter current password" required>
            <div class="error-message" id="oldPasswordError"></div>
          </div>
          <div class="form-group">
            <label for="editNewPassword" class="form-label">New Password</label>
            <input type="password" id="editNewPassword" name="new_password" class="form-input" placeholder="Enter new password" required>
            <div class="error-message" id="newPasswordError"></div>
          </div>
          <div class="form-group">
            <label for="editConfirmPassword" class="form-label">Confirm New Password</label>
            <input type="password" id="editConfirmPassword" name="confirm_password" class="form-input" placeholder="Confirm new password" required>
            <div class="error-message" id="confirmPasswordError"></div>
          </div>
          <div class="form-actions">
            <button class="btn btn-success" type="submit">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20,6 9,17 4,12"></polyline>
              </svg>
              Update Section
            </button>
            <button class="btn btn-secondary" type="button" onclick="closeEditSectionModal()">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Section Details Modal -->
  <div id="sectionDetailsModal" class="modal">
    <div class="modal-content section-details-modal">
      <div class="modal-header">
        <h2 id="sectionDetailsTitle">Section Details</h2>
        <button class="close-btn" onclick="closeSectionDetailsModal()">&times;</button>
      </div>
      <div class="modal-body">
        <div id="sectionDetailsContent">
          <!-- Section details will be loaded here -->
        </div>
      </div>
    </div>
  </div>

  <!-- Delete Section Modal -->
  <div id="deleteSectionModal" class="modal">
    <div class="modal-content simple-delete-modal">
      <div class="modal-header">
        <span class="close" onclick="closeDeleteSectionModal()">&times;</span>
      </div>
      <div class="modal-body">
        <div class="delete-warning">
          <div class="warning-icon">
            <div class="warning-circle">×</div>
          </div>
          <h3>Are you sure?</h3>
          <p>Do you really want to delete this section? This process cannot be undone.</p>
        </div>
        <div class="form-actions">
          <button type="button" class="btn btn-cancel" onclick="closeDeleteSectionModal()">Cancel</button>
          <button type="button" class="btn btn-delete" onclick="confirmDeleteSection()">Delete</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Post Creation Options Modal -->
  <div id="postCreationModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Section Created Successfully!</h2>
        <button class="close-btn" onclick="closePostCreationModal()">&times;</button>
      </div>
      <div class="modal-body">
        <div class="post-creation-content">
          <div class="success-message">
            <i class="fa-solid fa-check-circle" style="color: #22c55e; font-size: 48px; margin-bottom: 16px;"></i>
            <p>Your section <strong id="createdSectionName"></strong> has been created successfully!</p>
            <p class="sub-message">What would you like to do next?</p>
          </div>
          <div class="action-buttons">
            <button class="btn btn-success" onclick="addAdviserToSection()">
              <i class="fa-solid fa-chalkboard-user"></i>
              Add Adviser
            </button>
            <button class="btn btn-primary" onclick="addSubjectToSection()">
              <i class="fa-solid fa-book"></i>
              Add Subject
            </button>
            <button class="btn btn-secondary" onclick="closePostCreationModal()">
              <i class="fa-solid fa-times"></i>
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php include 'includes/scripts.php'; ?>
  
  <script>
    let currentSectionId = null;
    let newlyCreatedSection = {
      id: null,
      gradeLevel: null,
      section: null
    };

    // Password visibility toggle function
    function togglePasswordVisibility(inputId) {
      const passwordInput = document.getElementById(inputId);
      const eyeIcon = document.getElementById(inputId + '-eye');
      
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.classList.remove('fa-eye');
        eyeIcon.classList.add('fa-eye-slash');
      } else {
        passwordInput.type = 'password';
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
      }
    }

    // Password validation function
    function validatePasswords() {
      const password = document.getElementById('modalpassword').value;
      const retypePassword = document.getElementById('modalretypepassword').value;
      const errorDiv = document.getElementById('password-error');
      const matchDiv = document.getElementById('password-match');
      
      // Clear previous states
      errorDiv.classList.remove('show');
      matchDiv.classList.remove('show');
      
      // Only validate if both fields have values
      if (password && retypePassword) {
        if (password === retypePassword) {
          matchDiv.classList.add('show');
          return true;
        } else {
          errorDiv.classList.add('show');
          return false;
        }
      }
      
      return true; // Allow submission if fields are empty (let HTML5 validation handle it)
    }

    function openGradeModal() {
      document.getElementById('gradeModal').style.display = 'block';
      
      // Reset password validation states
      document.getElementById('password-error').classList.remove('show');
      document.getElementById('password-match').classList.remove('show');
    }
    
    function closeGradeModal() {
      document.getElementById('gradeModal').style.display = 'none';
      
      // Reset form and password validation states
      const form = document.querySelector('#gradeModal form');
      if (form) form.reset();
      document.getElementById('password-error').classList.remove('show');
      document.getElementById('password-match').classList.remove('show');
      
      // Reset eye icons to default state
      const eyeIcons = ['modalpassword-eye', 'modalretypepassword-eye'];
      eyeIcons.forEach(iconId => {
        const icon = document.getElementById(iconId);
        if (icon) {
          icon.classList.remove('fa-eye-slash');
          icon.classList.add('fa-eye');
        }
      });
      
      // Reset password input types
      const passwordInputs = ['modalpassword', 'modalretypepassword'];
      passwordInputs.forEach(inputId => {
        const input = document.getElementById(inputId);
        if (input) input.type = 'password';
      });
    }

    // Post-creation modal functions
    function openPostCreationModal(sectionId, gradeLevel, section) {
      newlyCreatedSection.id = sectionId;
      newlyCreatedSection.gradeLevel = gradeLevel;
      newlyCreatedSection.section = section;
      
      // Format section name for display
      const gradeDisplay = /^\d+$/.test(gradeLevel) ? `Grade ${gradeLevel}` : gradeLevel;
      document.getElementById('createdSectionName').textContent = `${gradeDisplay} - ${section}`;
      
      document.getElementById('postCreationModal').style.display = 'block';
    }

    function closePostCreationModal() {
      document.getElementById('postCreationModal').style.display = 'none';
      // Reset the variables
      newlyCreatedSection = { id: null, gradeLevel: null, section: null };
    }

    function addAdviserToSection() {
      if (!newlyCreatedSection.id) {
        showMessagePopup('Section information not available', 'error');
        return;
      }
      
      closePostCreationModal();
      // Navigate to teachers page with section pre-selected for adviser assignment
      window.location.href = `teachers.php?action=add_adviser&section_id=${newlyCreatedSection.id}&grade_level=${encodeURIComponent(newlyCreatedSection.gradeLevel)}&section=${encodeURIComponent(newlyCreatedSection.section)}`;
    }

    function addSubjectToSection() {
      if (!newlyCreatedSection.id) {
        showMessagePopup('Section information not available', 'error');
        return;
      }
      
      // Validate that we have all required information
      if (!newlyCreatedSection.gradeLevel || !newlyCreatedSection.section || 
          newlyCreatedSection.gradeLevel === 'null' || newlyCreatedSection.section === 'null') {
        console.log('Invalid section data:', newlyCreatedSection);
        showMessagePopup('Section data is incomplete. Please try again.', 'error');
        return;
      }
      
      closePostCreationModal();
      // Navigate to subjects page with section pre-selected in filters (no modal)
      window.location.href = `subjects.php?preselect_section=${newlyCreatedSection.id}&grade_level=${encodeURIComponent(newlyCreatedSection.gradeLevel)}&section=${encodeURIComponent(newlyCreatedSection.section)}`;
    }

    function viewSubjects(sectionId) {
      currentSectionId = sectionId;
      const sectionCard = document.querySelector(`[data-section-id="${sectionId}"]`);
      const sectionTitle = sectionCard.querySelector('.section-title').textContent;
      
      document.getElementById('subjectsModalTitle').textContent = `${sectionTitle} - Subjects`;
      
      // Show loading state
      const subjectsContent = document.getElementById('subjectsContent');
      subjectsContent.innerHTML = '<div class="loading">Loading subjects...</div>';
      document.getElementById('subjectsModal').style.display = 'block';
      
      // Fetch subjects from database
      fetch(`functions/get_subjects_for_section.php?section_id=${sectionId}`)
        .then(response => {
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.json();
        })
        .then(data => {
          if (data.success) {
            const subjects = data.subjects;
            
            if (subjects.length > 0) {
              subjectsContent.innerHTML = `
                <div class="subjects-grid">
                  ${subjects.map(subject => `
                    <div class="subject-card">
                      <i class="fa-solid fa-book"></i>
                      <span>${subject.subject_name}</span>
                      ${subject.subject_code ? `<small>${subject.subject_code}</small>` : ''}
                    </div>
                  `).join('')}
                </div>
              `;
            } else {
              subjectsContent.innerHTML = '<div class="no-subjects">No subjects assigned to this section.</div>';
            }
          } else {
            subjectsContent.innerHTML = `<div class="error">Error: ${data.message}</div>`;
          }
        })
        .catch(error => {
          console.error('Error fetching subjects:', error);
          subjectsContent.innerHTML = '<div class="error">Failed to load subjects. Please try again.</div>';
        });
    }

    function closeSubjectsModal() {
      document.getElementById('subjectsModal').style.display = 'none';
    }

    // Section Details Modal Functions
    function viewSectionDetails(sectionId) {
      currentSectionId = sectionId;
      
      if (!sectionId) {
        showMessagePopup('No section selected', 'error');
        return;
      }
      
      // Show loading state
      const modal = document.getElementById('sectionDetailsModal');
      const content = document.getElementById('sectionDetailsContent');
      content.innerHTML = '<div class="loading">Loading section details...</div>';
      modal.style.display = 'block';
      
      // Fetch section details
      fetch(`functions/get_section_students.php?section_id=${sectionId}`)
        .then(response => {
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.json();
        })
        .then(data => {
          if (data.error) {
            content.innerHTML = `<div class="error">Error: ${data.message}</div>`;
            return;
          }
          
          // Update modal title
          document.getElementById('sectionDetailsTitle').textContent = 
            `${data.section.grade_label} - ${data.section.section}`;
          
          // Build content
          let html = `
            <div class="section-info-header">
              <div class="info-grid">
                <div class="info-item">
                  <i class="fa-solid fa-users"></i>
                  <span><strong>${data.total_students}</strong> Students</span>
                </div>
                <div class="info-item">
                  <i class="fa-solid fa-chalkboard-user"></i>
                  <span><strong>Adviser:</strong> ${data.section.adviser_name}</span>
                </div>
              </div>
            </div>
            
            <div class="subjects-section">
              <h3><i class="fa-solid fa-book"></i> Subjects</h3>
              <div class="subjects-list" id="sectionSubjectsList">
                <div class="loading">Loading subjects...</div>
              </div>
            </div>
            
            <div class="students-section">
              <h3><i class="fa-solid fa-user-graduate"></i> Students (${data.total_students})</h3>
          `;
          
          if (data.students.length > 0) {
            html += `
              <div class="students-table-container">
                <table class="students-table">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Student Name</th>
                      <th>LRN</th>
                      <th>Parent</th>
                    </tr>
                  </thead>
                  <tbody>
            `;
            
            data.students.forEach((student, index) => {
              html += `
                <tr>
                  <td>${index + 1}</td>
                  <td>${student.full_name}</td>
                  <td>${student.lrn || '-'}</td>
                  <td>${student.parent_name || '-'}</td>
                </tr>
              `;
            });
            
            html += `
                  </tbody>
                </table>
              </div>
            `;
          } else {
            html += '<div class="no-students">No students enrolled in this section.</div>';
          }
          
          html += '</div>';
          content.innerHTML = html;
          
          // Load subjects for this section
          loadSectionSubjects(sectionId);
        })
        .catch(error => {
          console.error('Error fetching section details:', error);
          content.innerHTML = '<div class="error">Failed to load section details. Please try again.</div>';
        });
    }

    function closeSectionDetailsModal() {
      document.getElementById('sectionDetailsModal').style.display = 'none';
    }

    function loadSectionSubjects(sectionId) {
      const subjectsList = document.getElementById('sectionSubjectsList');
      if (!subjectsList) return;
      
      fetch(`functions/get_subjects_for_section.php?section_id=${sectionId}`)
        .then(response => {
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.json();
        })
        .then(data => {
          if (data.success) {
            const subjects = data.subjects;
            
            if (subjects.length > 0) {
              subjectsList.innerHTML = subjects.map(subject => 
                `<span class="subject-tag">${subject.subject_name}</span>`
              ).join('');
            } else {
              subjectsList.innerHTML = '<div class="no-subjects">No subjects assigned to this section.</div>';
            }
          } else {
            subjectsList.innerHTML = `<div class="error">Error loading subjects: ${data.message}</div>`;
          }
        })
        .catch(error => {
          console.error('Error fetching subjects:', error);
          subjectsList.innerHTML = '<div class="error">Failed to load subjects.</div>';
        });
    }

    function closeEditSectionModal() {
      document.getElementById('editSectionModal').style.display = 'none';
      // Reset form and clear errors
      document.getElementById('editSectionForm').reset();
      clearFormErrors();
    }

    // Form validation functions
    function validateEditForm() {
      let isValid = true;
      
      // Clear previous errors
      clearFormErrors();
      
      // Get form values
      const gradeLevel = document.getElementById('editGradeLevel').value.trim();
      const section = document.getElementById('editSection').value.trim();
      const oldPassword = document.getElementById('editOldPassword').value.trim();
      const newPassword = document.getElementById('editNewPassword').value.trim();
      const confirmPassword = document.getElementById('editConfirmPassword').value.trim();
      
      // Validate grade level
      if (!gradeLevel) {
        showFieldError('gradeLevel', 'Grade level is required');
        isValid = false;
      } else if (gradeLevel.length < 1) {
        showFieldError('gradeLevel', 'Grade level must be at least 1 character');
        isValid = false;
      }
      
      // Validate section
      if (!section) {
        showFieldError('section', 'Section is required');
        isValid = false;
      } else if (section.length < 1) {
        showFieldError('section', 'Section must be at least 1 character');
        isValid = false;
      }
      
      // Validate old password
      if (!oldPassword) {
        showFieldError('oldPassword', 'Current password is required');
        isValid = false;
      }
      
      // Validate new password
      if (!newPassword) {
        showFieldError('newPassword', 'New password is required');
        isValid = false;
      } else if (newPassword.length < 6) {
        showFieldError('newPassword', 'New password must be at least 6 characters');
        isValid = false;
      }
      
      // Validate confirm password
      if (!confirmPassword) {
        showFieldError('confirmPassword', 'Please confirm your new password');
        isValid = false;
      } else if (newPassword !== confirmPassword) {
        showFieldError('confirmPassword', 'Passwords do not match');
        isValid = false;
      }
      
      return isValid;
    }

    function showFieldError(fieldName, message) {
      const errorElement = document.getElementById(fieldName + 'Error');
      const inputElement = document.getElementById('edit' + fieldName.charAt(0).toUpperCase() + fieldName.slice(1));
      
      if (errorElement) {
        errorElement.textContent = message;
        errorElement.classList.add('show');
      }
      
      if (inputElement) {
        inputElement.classList.add('error');
      }
    }

    function showFieldErrors(errors) {
      Object.keys(errors).forEach(field => {
        showFieldError(field, errors[field]);
      });
    }

    function clearFormErrors() {
      // Clear all error messages
      const errorElements = document.querySelectorAll('.error-message');
      errorElements.forEach(element => {
        element.classList.remove('show');
        element.textContent = '';
      });
      
      // Remove error styling from inputs
      const inputElements = document.querySelectorAll('.form-input');
      inputElements.forEach(element => {
        element.classList.remove('error');
      });
    }

    function showSectionMenu(sectionId) {
      currentSectionId = sectionId;
      const menu = document.getElementById('sectionMenu');
      const card = document.querySelector(`[data-section-id="${sectionId}"]`);
      const rect = card.getBoundingClientRect();
      const viewportWidth = window.innerWidth;
      const viewportHeight = window.innerHeight;
      
      // Calculate menu position with responsive adjustments
      let left = rect.right - 200;
      let top = rect.bottom + 10;
      
      // Adjust for mobile screens
      if (viewportWidth < 768) {
        left = Math.min(left, viewportWidth - 220); // Ensure menu doesn't go off screen
        if (left < 10) left = 10;
        
        // If there's not enough space below, show above the card
        if (top + 150 > viewportHeight) {
          top = rect.top - 160;
        }
      }
      
      // Ensure menu stays within viewport bounds
      if (left < 0) left = 10;
      if (left + 200 > viewportWidth) left = viewportWidth - 210;
      if (top < 0) top = 10;
      if (top + 150 > viewportHeight) top = viewportHeight - 160;
      
      menu.style.left = left + 'px';
      menu.style.top = top + 'px';
      menu.style.display = 'block';
      
      // Close menu when clicking outside
      setTimeout(() => {
        document.addEventListener('click', closeSectionMenu);
      }, 100);
    }

    function closeSectionMenu() {
      document.getElementById('sectionMenu').style.display = 'none';
      document.removeEventListener('click', closeSectionMenu);
    }

    function editSection() {
      closeSectionMenu();
      
      if (!currentSectionId) {
        showMessagePopup('No section selected', 'error');
        return;
      }
      
      // Show loading state
      const modal = document.getElementById('editSectionModal');
      modal.style.display = 'block';
      
      // Fetch section data
      fetch(`functions/get_section.php?id=${currentSectionId}`)
        .then(response => response.json())
        .then(data => {
          if (data.error) {
            showMessagePopup('Error: ' + data.error, 'error');
            closeEditSectionModal();
            return;
          }
          
          // Populate form
          document.getElementById('editSectionId').value = data.id;
          document.getElementById('editGradeLevel').value = data.grade_level;
          document.getElementById('editSection').value = data.section;
          // Clear password fields for security
          document.getElementById('editOldPassword').value = '';
          document.getElementById('editNewPassword').value = '';
          document.getElementById('editConfirmPassword').value = '';
        })
        .catch(error => {
          console.error('Error fetching section:', error);
          showMessagePopup('Failed to load section data', 'error');
          closeEditSectionModal();
        });
    }

    function viewStudents() {
      closeSectionMenu();
      
      if (!currentSectionId) {
        showMessagePopup('No section selected', 'error');
        return;
      }
      
      // Get section details first
      fetch(`functions/get_section.php?id=${currentSectionId}`)
        .then(response => response.json())
        .then(data => {
          if (data.error) {
            showMessagePopup('Error: ' + data.error, 'error');
            return;
          }
          
          // Set session variables and navigate
          const form = document.createElement('form');
          form.method = 'POST';
          form.action = 'viewSection.php';
          
          const sectionIdInput = document.createElement('input');
          sectionIdInput.type = 'hidden';
          sectionIdInput.name = 'section_id';
          sectionIdInput.value = data.id;
          
          const gradeLevelInput = document.createElement('input');
          gradeLevelInput.type = 'hidden';
          gradeLevelInput.name = 'grade_level';
          gradeLevelInput.value = data.grade_level;
          
          const sectionInput = document.createElement('input');
          sectionInput.type = 'hidden';
          sectionInput.name = 'section';
          sectionInput.value = data.section;
          
          form.appendChild(sectionIdInput);
          form.appendChild(gradeLevelInput);
          form.appendChild(sectionInput);
          
          document.body.appendChild(form);
          form.submit();
        })
        .catch(error => {
          console.error('Error fetching section:', error);
          showMessagePopup('Failed to load section data', 'error');
        });
    }

    function deleteSection() {
      closeSectionMenu();
      
      if (!currentSectionId) {
        showMessagePopup('No section selected', 'error');
        return;
      }
      
      // Open delete confirmation modal
      openDeleteSectionModal();
    }

    function openDeleteSectionModal() {
      document.getElementById('deleteSectionModal').style.display = 'block';
    }

    function closeDeleteSectionModal() {
      document.getElementById('deleteSectionModal').style.display = 'none';
    }

    function confirmDeleteSection() {
      if (!currentSectionId) {
        showMessagePopup('No section selected', 'error');
        return;
      }
      
      // Show loading state
      const deleteButton = document.querySelector('#deleteSectionModal .btn-delete');
      const originalText = deleteButton.textContent;
      deleteButton.textContent = 'Deleting...';
      deleteButton.disabled = true;
      
      // Send delete request
      fetch('functions/delete_grade.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id=' + encodeURIComponent(currentSectionId)
      })
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text();
      })
      .then(text => {
        let data;
        try {
          data = JSON.parse(text);
        } catch (e) {
          console.error('Response is not valid JSON:', text);
          throw new Error('Server returned invalid response');
        }
        
        if (data.success) {
          closeDeleteSectionModal();
          showMessagePopup('Section deleted successfully!', 'success');
          setTimeout(() => {
            window.location.href = window.location.pathname;
          }, 1500);
        } else {
          showMessagePopup(data.message || 'Error deleting section', 'error');
        }
      })
      .catch(error => {
        console.error('Delete error:', error);
        showMessagePopup('Error deleting section: ' + error.message, 'error');
      })
      .finally(() => {
        if (deleteButton) {
          deleteButton.textContent = originalText;
          deleteButton.disabled = false;
        }
      });
    }
    
    // Handle edit section form submission
    document.getElementById('editSectionForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      // Clear previous errors
      clearFormErrors();
      
      // Validate form
      if (!validateEditForm()) {
        return;
      }
      
      const formData = new FormData(this);
      const submitButton = this.querySelector('button[type="submit"]');
      const originalText = submitButton.textContent;
      
      // Show loading state
      submitButton.textContent = 'Updating...';
      submitButton.disabled = true;
      
      fetch('functions/edit_section.php', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        if (data.success) {
          showMessagePopup('Section updated successfully!', 'success');
          closeEditSectionModal();
          setTimeout(() => {
            window.location.reload();
          }, 1500);
        } else {
          showMessagePopup(data.message || 'Error updating section', 'error');
          // Show specific field errors if available
          if (data.field_errors) {
            showFieldErrors(data.field_errors);
          }
        }
      })
      .catch(error => {
        console.error('Error updating section:', error);
        showMessagePopup('Error updating section: ' + error.message, 'error');
      })
      .finally(() => {
        submitButton.textContent = originalText;
        submitButton.disabled = false;
      });
    });

    // Close modals when clicking outside
    window.onclick = function(event) {
      const gradeModal = document.getElementById('gradeModal');
      const subjectsModal = document.getElementById('subjectsModal');
      const sectionDetailsModal = document.getElementById('sectionDetailsModal');
      const editSectionModal = document.getElementById('editSectionModal');
      const deleteSectionModal = document.getElementById('deleteSectionModal');
      const postCreationModal = document.getElementById('postCreationModal');
      
      if (event.target === gradeModal) {
        closeGradeModal();
      }
      if (event.target === subjectsModal) {
        closeSubjectsModal();
      }
      if (event.target === sectionDetailsModal) {
        closeSectionDetailsModal();
      }
      if (event.target === editSectionModal) {
        closeEditSectionModal();
      }
      if (event.target === deleteSectionModal) {
        closeDeleteSectionModal();
      }
      if (event.target === postCreationModal) {
        closePostCreationModal();
      }
    }

    // Handle window resize for responsive context menu
    window.addEventListener('resize', function() {
      const menu = document.getElementById('sectionMenu');
      if (menu && menu.style.display === 'block') {
        closeSectionMenu();
      }
    });

    // Handle orientation change for mobile devices
    window.addEventListener('orientationchange', function() {
      setTimeout(() => {
        const menu = document.getElementById('sectionMenu');
        if (menu && menu.style.display === 'block') {
          closeSectionMenu();
        }
      }, 100);
    });
    
    // Show popup message function
    function showMessagePopup(message, type) {
      const popup = document.getElementById('messagePopup');
      const icon = popup.querySelector('.message-icon');
      const text = popup.querySelector('.message-text');
      
      text.textContent = message;
      
      if (type === 'success') {
        icon.innerHTML = '✅';
        popup.className = 'message-popup success';
      } else if (type === 'error') {
        icon.innerHTML = '⚠️';
        popup.className = 'message-popup error';
      }
      
      popup.style.display = 'block';
      
      setTimeout(function() {
        closeMessagePopup();
      }, 5000);
    }
    
    function closeMessagePopup() {
      const popup = document.getElementById('messagePopup');
      popup.style.animation = 'slideOut 0.3s ease-out';
      setTimeout(function() {
        popup.style.display = 'none';
        popup.style.animation = 'slideIn 0.3s ease-out';
      }, 300);
    }
    
    // Update section options when grade level changes
    function updateSectionOptions() {
      const gradeSelect = document.getElementById('grade');
      const sectionSelect = document.getElementById('section');
      const selectedGrade = gradeSelect.value;
      
      sectionSelect.innerHTML = '<option value="">--Select--</option>';
      
      if (selectedGrade) {
        fetch(`functions/get_sections.php?grade=${selectedGrade}`)
          .then(response => {
            if (!response.ok) {
              throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
          })
          .then(data => {
            if (Array.isArray(data)) {
              data.forEach(section => {
                const option = document.createElement('option');
                option.value = section;
                option.textContent = section;
                sectionSelect.appendChild(option);
              });
            } else {
              console.error('Invalid data format received:', data);
              showMessagePopup('Error loading sections', 'error');
            }
          })
          .catch(error => {
            console.error('Error fetching sections:', error);
            showMessagePopup('Error loading sections: ' + error.message, 'error');
            // Fallback to page reload
            window.location.href = `?grade=${selectedGrade}`;
          });
      }
    }
    
    function clearFilters() {
      window.location.href = window.location.pathname;
    }
    
    // Check for success/error messages on page load
    document.addEventListener('DOMContentLoaded', function() {
      <?php if (isset($_GET['success']) && isset($_GET['new_section_id'])): ?>
        // Show post-creation modal instead of success message
        const sectionId = <?= json_encode($_GET['new_section_id']) ?>;
        const gradeLevel = <?= json_encode($_GET['grade_level'] ?? null) ?>;
        const section = <?= json_encode($_GET['section'] ?? null) ?>;
        console.log('Section creation parameters:', { sectionId, gradeLevel, section }); // Debug log
        openPostCreationModal(sectionId, gradeLevel, section);
      <?php elseif (isset($_GET['success'])): ?>
        showMessagePopup('Section added successfully!', 'success');
      <?php elseif (isset($_GET['error'])): ?>
        showMessagePopup('<?= htmlspecialchars($_GET['error']) ?>', 'error');
      <?php endif; ?>
      
      // Add input validation listeners
      addInputValidationListeners();
      
      // Add password validation listeners
      addPasswordValidationListeners();
    });

    // Add real-time validation listeners
    function addInputValidationListeners() {
      // Grade level validation
      const gradeLevelInput = document.getElementById('editGradeLevel');
      if (gradeLevelInput) {
        gradeLevelInput.addEventListener('blur', function() {
          if (this.value.trim() === '') {
            showFieldError('gradeLevel', 'Grade level is required');
          } else {
            clearFieldError('gradeLevel');
          }
        });
      }

      // Section validation
      const sectionInput = document.getElementById('editSection');
      if (sectionInput) {
        sectionInput.addEventListener('blur', function() {
          if (this.value.trim() === '') {
            showFieldError('section', 'Section is required');
          } else {
            clearFieldError('section');
          }
        });
      }

      // Password confirmation validation
      const newPasswordInput = document.getElementById('editNewPassword');
      const confirmPasswordInput = document.getElementById('editConfirmPassword');
      
      if (newPasswordInput && confirmPasswordInput) {
        confirmPasswordInput.addEventListener('blur', function() {
          const newPassword = newPasswordInput.value;
          const confirmPassword = this.value;
          
          if (confirmPassword && newPassword !== confirmPassword) {
            showFieldError('confirmPassword', 'Passwords do not match');
          } else if (confirmPassword) {
            clearFieldError('confirmPassword');
          }
        });
      }
    }

    function clearFieldError(fieldName) {
      const errorElement = document.getElementById(fieldName + 'Error');
      const inputElement = document.getElementById('edit' + fieldName.charAt(0).toUpperCase() + fieldName.slice(1));
      
      if (errorElement) {
        errorElement.classList.remove('show');
        errorElement.textContent = '';
      }
      
      if (inputElement) {
        inputElement.classList.remove('error');
      }
    }

    // Add password validation listeners for section creation modal
    function addPasswordValidationListeners() {
      const passwordInput = document.getElementById('modalpassword');
      const retypePasswordInput = document.getElementById('modalretypepassword');
      
      if (passwordInput && retypePasswordInput) {
        // Validate on input (real-time)
        passwordInput.addEventListener('input', validatePasswords);
        retypePasswordInput.addEventListener('input', validatePasswords);
        
        // Validate on blur
        passwordInput.addEventListener('blur', validatePasswords);
        retypePasswordInput.addEventListener('blur', validatePasswords);
      }
      
      // Add form submission validation
      const form = document.querySelector('#gradeModal form');
      if (form) {
        form.addEventListener('submit', function(e) {
          if (!validatePasswords()) {
            e.preventDefault();
            e.stopPropagation();
            
            // Focus on the retype password field to draw attention
            retypePasswordInput.focus();
            
            return false;
          }
        });
      }
    }
  </script>
</body>
</html>
