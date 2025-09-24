<?php
session_start();

// Prevent caching and going back to previous pages
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$page_title = "Teachers List";
$additional_css = "css/teachers.css";

include 'db/db_connect.php';

// Helper function to format grade
function formatGrade($grade) {
    return is_numeric($grade) ? "Grade {$grade}" : $grade;
}
?>
<!doctype html>
<html lang="en">
<head>
  <title><?php echo $page_title; ?></title>
  <?php include 'includes/head.php'; ?>
  <link rel="stylesheet" href="<?php echo $additional_css; ?>">
  <link rel="stylesheet" href="css/teachers-inline.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <?php include 'includes/header.php'; ?>

  <div class="layout">
    <?php include 'includes/sidebar.php'; ?>

    <main>
      <div class="page-header">
        <h1 class="page-title"><?php echo $page_title; ?></h1>
        <div class="page-actions">
          <button class="btn btn-primary" id="createNewLink">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Create New Teacher
          </button>
          <!-- <a href="functions/export_teachers.php" class="btn btn-info" id="exportLink">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <polyline points="14,2 14,8 20,8"></polyline>
              <line x1="16" y1="13" x2="8" y2="13"></line>
              <line x1="16" y1="17" x2="8" y2="17"></line>
              <polyline points="10,9 9,9 8,9"></polyline>
            </svg>
            Export Excel
          </a> -->
        </div>
      </div>

      <section class="filters" aria-label="Search Filter">
        <div class="filters-title">Search Filter</div>
        <div class="filters-row">
          <select class="filter-select" id="filterGradeLevel">
            <option value="">--Select Grade Level--</option>
            <?php
            $gradeQuery = "SELECT DISTINCT grade_level FROM section";
            $gradeResult = $conn->query($gradeQuery);

            // Custom priority order
            $gradeOrder = ['Nursery', 'Kinder 1', 'Kinder 2'];

            if ($gradeResult && $gradeResult->num_rows > 0) {
                $grades = [];
                while ($g = $gradeResult->fetch_assoc()) {
                    $grades[] = htmlspecialchars($g['grade_level']);
                }

                // Custom sorting
                usort($grades, function($a, $b) use ($gradeOrder) {
                    $aIndex = array_search($a, $gradeOrder);
                    $bIndex = array_search($b, $gradeOrder);

                    if ($aIndex !== false && $bIndex !== false) {
                        return $aIndex - $bIndex; // Both in special order
                    } elseif ($aIndex !== false) {
                        return -1; // A is in special order
                    } elseif ($bIndex !== false) {
                        return 1; // B is in special order
                    }

                    // Handle numeric grades ("1"–"12")
                    if (is_numeric($a) && is_numeric($b)) {
                        return (int)$a - (int)$b;
                    }

                    // Fallback: alphabetical
                    return strcmp($a, $b);
                });

                // Render options
                foreach ($grades as $gradeValue) {
                    $label = is_numeric($gradeValue) ? "Grade {$gradeValue}" : $gradeValue;
                    echo "<option value='{$gradeValue}'>{$label}</option>";
                }
            }
            ?>
          </select>


          <button class="btn btn-filter" onclick="applyFilters()">
            <i class="fa-solid fa-filter"></i> Filter
          </button>
          <button class="btn btn-clear" onclick="clearFilters()">
            <i class="fa-solid fa-times"></i> Clear
          </button>
        </div>
      </section>

      <section class="table-wrap">
        <div class="table-header">
          <div class="table-info">
            <span id="teacherCount">0</span> teachers found
          </div>
        </div>
        <table class="data-table" aria-label="Teachers table">
          <thead>
            <tr>
              <th>#</th>
              <th>Teacher ID</th>
              <th>Teacher's Name</th>
              <th>Grade Level</th>
              <th>Advisory</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="teachersTableBody">
            <tr>
              <td colspan="6" class="loading-message">
                <i class="fa-solid fa-spinner fa-spin"></i> Loading teachers...
              </td>
            </tr>
          </tbody>
        </table>
      </section>
    </main>
  </div>

  <!-- Teacher Entry Modal -->
  <div id="teacherModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Teacher Registration</h2>
        <button class="close-btn" onclick="closeModal()">&times;</button>
      </div>

      <form class="teacher-form">
        <div class="form-columns">
          <div class="form-column">
            <h3>Basic Information</h3>
            <div class="form-group">
              <label for="teacherId" class="required">Teacher ID</label>
              <input type="text" id="teacherId" name="teacherId" required readonly style="background-color: #f3f4f6; cursor: not-allowed;">
              <small class="form-text text-muted">Automatically generated</small>
            </div>
            <div class="form-group">
              <label for="firstName" class="required">First Name</label>
              <input type="text" id="firstName" name="firstName" required>
            </div>
            <div class="form-group">
              <label for="lastName" class="required">Last Name</label>
              <input type="text" id="lastName" name="lastName" required>
            </div>
            <div class="form-group">
              <label for="middleInitial">Middle Initial</label>
              <input type="text" id="middleInitial" name="middleInitial" maxlength="4">
            </div>
            <div class="form-group">
              <label for="gender" class="required">Gender</label>
              <select id="gender" name="gender" required>
                <option value="">--Select Gender--</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
            </div>

          </div>

          <div class="form-column-right">
            <div class="form-right-content">
              <div class="form-section">
                <h3>Teaching Assignment</h3>
                <div class="form-group">
                  <label for="gradeLevel" class="required">Grade Level</label>
                  <select id="gradeLevel" name="gradeLevel" onchange="updateModalSectionOptions()" required>
                    <option value="">--Select--</option>
                    <?php
                    $gradesResult = $conn->query("SELECT DISTINCT grade_level FROM section");

                    if ($gradesResult && $gradesResult->num_rows > 0) {
                      $grades = [];
                      while ($g = $gradesResult->fetch_assoc()) {
                        $grades[] = htmlspecialchars($g['grade_level']);
                      }

                      // Custom order priority
                      $gradeOrder = ['Nursery', 'Kinder 1', 'Kinder 2'];

                      usort($grades, function($a, $b) use ($gradeOrder) {
                        $aIndex = array_search($a, $gradeOrder);
                        $bIndex = array_search($b, $gradeOrder);

                        if ($aIndex !== false && $bIndex !== false) {
                          return $aIndex - $bIndex;
                        } elseif ($aIndex !== false) {
                          return -1;
                        } elseif ($bIndex !== false) {
                          return 1;
                        }

                        // Numeric sorting if both are numbers
                        if (is_numeric($a) && is_numeric($b)) {
                          return (int)$a - (int)$b;
                        }

                        // Fallback: alphabetical
                        return strcmp($a, $b);
                      });

                      foreach ($grades as $gradeValue) {
                        $label = is_numeric($gradeValue) ? "Grade {$gradeValue}" : $gradeValue;
                        echo "<option value='{$gradeValue}'>{$label}</option>";
                      }
                    }
                    ?>
                  </select>
                </div>


                <div class="form-group">
                  <label for="advisory">Advisory</label>
                  <select id="advisory" name="advisory">
                    <option value="">--Select Advisory Section--</option>
                  </select>
                  <small style="color: #6b7280; font-size: 0.75rem;">Select the section this teacher will be an advisor for (filtered by grade level)</small>
                </div>



                <div class="form-actions">
                  <button type="button" class="btn btn-save">Save</button>
                  <button type="button" class="btn btn-cancel" onclick="closeModal()">Cancel</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Teacher Edit Modal -->
  <div id="editTeacherModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Edit Teacher</h2>
        <button class="close-btn" onclick="closeEditModal()">&times;</button>
      </div>

      <form class="teacher-form" id="editTeacherForm">
        <input type="hidden" id="editTeacherId" name="teacherId">
        <div class="form-columns">
          <div class="form-column">
            <h3>Basic Information</h3>
            <div class="form-group">
              <label for="editTeacherIdField" class="required">Teacher ID</label>
              <input type="text" id="editTeacherIdField" name="teacherId" required readonly style="background-color: #f3f4f6; cursor: not-allowed;">
              <small class="form-text text-muted">Teacher ID cannot be changed</small>
            </div>
            <div class="form-group">
              <label for="editFirstName" class="required">First Name</label>
              <input type="text" id="editFirstName" name="firstName" required>
            </div>
            <div class="form-group">
              <label for="editLastName" class="required">Last Name</label>
              <input type="text" id="editLastName" name="lastName" required>
            </div>
            <div class="form-group">
              <label for="editMiddleInitial">Middle Initial</label>
              <input type="text" id="editMiddleInitial" name="middleInitial" maxlength="4">
            </div>
            <div class="form-group">
              <label for="editGender" class="required">Gender</label>
              <select id="editGender" name="gender" required>
                <option value="">--Select Gender--</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
            </div>
          </div>

          <div class="form-column-right">
            <div class="form-right-content">
              <div class="form-section">
                <h3>Teaching Assignment</h3>
                <div class="form-group">
                  <label for="editGradeLevel" class="required">Grade Level</label>
                  <select id="editGradeLevel" name="gradeLevel" onchange="updateEditSectionOptions()" required>
                    <option value="">--Select--</option>
                    <?php
                    $gradesResult = $conn->query("SELECT DISTINCT grade_level FROM section");

                    if ($gradesResult && $gradesResult->num_rows > 0) {
                      $grades = [];
                      while ($g = $gradesResult->fetch_assoc()) {
                        $grades[] = htmlspecialchars($g['grade_level']);
                      }

                      // Custom order priority
                      $gradeOrder = ['Nursery', 'Kinder 1', 'Kinder 2'];

                      usort($grades, function($a, $b) use ($gradeOrder) {
                        $aIndex = array_search($a, $gradeOrder);
                        $bIndex = array_search($b, $gradeOrder);

                        if ($aIndex !== false && $bIndex !== false) {
                          return $aIndex - $bIndex;
                        } elseif ($aIndex !== false) {
                          return -1;
                        } elseif ($bIndex !== false) {
                          return 1;
                        }

                        // Numeric sorting if both are numbers
                        if (is_numeric($a) && is_numeric($b)) {
                          return (int)$a - (int)$b;
                        }

                        // Fallback: alphabetical
                        return strcmp($a, $b);
                      });

                      foreach ($grades as $gradeValue) {
                        $label = is_numeric($gradeValue) ? "Grade {$gradeValue}" : $gradeValue;
                        echo "<option value='{$gradeValue}'>{$label}</option>";
                      }
                    }
                    ?>
                  </select>
                </div>


                <div class="form-group">
                  <label for="editAdvisory">Advisory</label>
                  <select id="editAdvisory" name="advisory">
                    <option value="">--Select Advisory Section--</option>
                  </select>
                  <small style="color: #6b7280; font-size: 0.75rem;">Select the section this teacher will be an advisor for (filtered by grade level)</small>
                </div>



                <div class="form-actions">
                  <button type="button" class="btn btn-save" onclick="updateTeacher()">Update</button>
                  <button type="button" class="btn btn-cancel" onclick="closeEditModal()">Cancel</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Delete Teacher Modal -->
  <div id="deleteTeacherModal" class="modal">
    <div class="modal-content simple-delete-modal">
      <div class="modal-header">
        <span class="close" onclick="closeDeleteTeacherModal()">&times;</span>
      </div>
      <div class="modal-body">
        <div class="delete-warning">
          <div class="warning-icon">
            <div class="warning-circle">×</div>
          </div>
          <h3>Are you sure?</h3>
          <p>Do you really want to delete this teacher? This process cannot be undone.</p>
        </div>
        <div class="form-actions">
          <button type="button" class="btn btn-cancel" onclick="closeDeleteTeacherModal()">Cancel</button>
          <button type="button" class="btn btn-delete" onclick="confirmDeleteTeacher()">Delete</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Toast Notification -->
  <div id="toast" class="toast">
    <div class="toast-content">
      <div class="toast-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="20,6 9,17 4,12"></polyline>
        </svg>
      </div>
      <div class="toast-message"></div>
      <button class="toast-close" onclick="hideToast()">&times;</button>
    </div>
  </div>

  <?php include 'includes/scripts.php'; ?>
  <?php include 'includes/confirmation_dialog.php'; ?>
  
  <script>
  // Format grade label: prefix with 'Grade' only for numeric values
  function formatGradeLabel(grade) {
    const value = String(grade || '').trim();
    return /^\d+$/.test(value) ? `Grade ${value}` : value;
  }

  // Update advisory options when grade level changes
  function updateModalSectionOptions() {
    const gradeSelect = document.getElementById('gradeLevel');
    const selectedGrade = gradeSelect.value;
    
    // Update advisory options based on grade level
    loadAdvisoryOptions(selectedGrade, 'create');
  }

  // Load sections for advisory dropdown based on grade level
  function loadAdvisoryOptions(gradeLevel = '', targetForm = 'both', excludeTeacherId = '', callback = null) {
    const advisorySelect = document.getElementById('advisory');
    const editAdvisorySelect = document.getElementById('editAdvisory');
    
    let url = 'functions/get_all_sections.php';
    const params = new URLSearchParams();
    
    if (gradeLevel) {
      params.append('grade_level', gradeLevel);
    }
    if (excludeTeacherId) {
      params.append('exclude_teacher_id', excludeTeacherId);
    }
    
    if (params.toString()) {
      url += '?' + params.toString();
    }
    
    fetch(url)
      .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
      })
      .then(data => {
        if (Array.isArray(data)) {
          // Populate create form advisory dropdown
          if ((targetForm === 'both' || targetForm === 'create') && advisorySelect) {
            advisorySelect.innerHTML = '<option value="">--Select Advisory Section--</option>';
            data.forEach(section => {
              // Skip sections that are already assigned as advisory
              if (section.is_assigned) {
                return;
              }
              
              const option = document.createElement('option');
              option.value = section.section_name;
              option.textContent = section.display_name;
              advisorySelect.appendChild(option);
            });
          }
          
          // Populate edit form advisory dropdown
          if ((targetForm === 'both' || targetForm === 'edit') && editAdvisorySelect) {
            editAdvisorySelect.innerHTML = '<option value="">--Select Advisory Section--</option>';
            data.forEach(section => {
              // Skip sections that are already assigned as advisory (except current teacher's advisory)
              if (section.is_assigned) {
                return;
              }
              
              const option = document.createElement('option');
              option.value = section.section_name;
              option.textContent = section.display_name;
              editAdvisorySelect.appendChild(option);
            });
            
            // Execute callback after options are loaded
            if (callback && typeof callback === 'function') {
              callback();
            }
          }
        }
      })
      .catch(error => {
        console.error('Error fetching sections for advisory:', error);
        if ((targetForm === 'both' || targetForm === 'create') && advisorySelect) {
          advisorySelect.innerHTML = '<option value="">Error loading sections</option>';
        }
        if ((targetForm === 'both' || targetForm === 'edit') && editAdvisorySelect) {
          editAdvisorySelect.innerHTML = '<option value="">Error loading sections</option>';
        }
      });
  }


  // Modal functions
  function openModal() {
    document.getElementById('teacherModal').style.display = 'block';

    // Always start fresh when opening
    const form = document.querySelector('.teacher-form');
    form.reset();
    
    // Fetch and populate next Teacher ID
    fetchNextTeacherId();
  }
  
  // Function to fetch the next Teacher ID
  function fetchNextTeacherId() {
    // Show loading state
    const teacherIdInput = document.getElementById('teacherId');
    teacherIdInput.value = 'Loading...';
    
    fetch('functions/get_next_teacher_id.php')
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.json();
      })
      .then(data => {
        if (data.success) {
          teacherIdInput.value = data.next_teacher_id;
        } else {
          console.error('Error fetching next teacher ID:', data.error);
          teacherIdInput.value = 'Error loading ID';
          showErrorMessage('Failed to generate Teacher ID. Please try again.');
        }
      })
      .catch(error => {
        console.error('Error fetching next teacher ID:', error);
        teacherIdInput.value = 'Error loading ID';
        showErrorMessage('Network error while generating Teacher ID. Please try again.');
      });
  }
  
  // Function to show error message
  function showErrorMessage(message) {
    // You can implement a toast notification or use alert for now
    alert('Error: ' + message);
  }
  
  // Function to capitalize first letter of each word
  function capitalizeFirstLetter(str) {
    if (!str) return str;
    return str.split(' ').map(word => {
      if (word.length === 0) return word;
      return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
    }).join(' ');
  }
  
  // Function to handle name field capitalization
  function handleNameCapitalization(event) {
    const input = event.target;
    const cursorPosition = input.selectionStart;
    const originalLength = input.value.length;
    
    // Capitalize the input value
    input.value = capitalizeFirstLetter(input.value);
    
    // Restore cursor position accounting for potential length change
    const newLength = input.value.length;
    const lengthDifference = newLength - originalLength;
    input.setSelectionRange(cursorPosition + lengthDifference, cursorPosition + lengthDifference);
  }
  
  // Function to add capitalization listeners to name fields
  function addNameCapitalizationListeners() {
    // Name fields in the create modal
    const nameFields = ['firstName', 'lastName', 'middleInitial'];
    nameFields.forEach(fieldId => {
      const field = document.getElementById(fieldId);
      if (field) {
        field.addEventListener('input', handleNameCapitalization);
        field.addEventListener('blur', handleNameCapitalization);
      }
    });
    
    // Name fields in the edit modal
    const editNameFields = ['editFirstName', 'editLastName', 'editMiddleInitial'];
    editNameFields.forEach(fieldId => {
      const field = document.getElementById(fieldId);
      if (field) {
        field.addEventListener('input', handleNameCapitalization);
        field.addEventListener('blur', handleNameCapitalization);
      }
    });
  }
  
  // Function to capitalize name fields before form submission
  function capitalizeNameFields() {
    // Create modal name fields
    const nameFields = ['firstName', 'lastName', 'middleInitial'];
    nameFields.forEach(fieldId => {
      const field = document.getElementById(fieldId);
      if (field && field.value) {
        field.value = capitalizeFirstLetter(field.value);
      }
    });
    
    // Edit modal name fields
    const editNameFields = ['editFirstName', 'editLastName', 'editMiddleInitial'];
    editNameFields.forEach(fieldId => {
      const field = document.getElementById(fieldId);
      if (field && field.value) {
        field.value = capitalizeFirstLetter(field.value);
      }
    });
  }
  
  function closeModal() {
    const modal = document.getElementById('teacherModal');
    modal.style.display = 'none';

    // Reset form when closing
    const form = document.querySelector('.teacher-form');
    form.reset();
    clearAllErrors();
  }
  
  window.onclick = function(event) {
    const modal = document.getElementById('teacherModal');
    const deleteModal = document.getElementById('deleteTeacherModal');
    if (event.target === modal) {
      closeModal();
    }
    if (event.target === deleteModal) {
      closeDeleteTeacherModal();
    }
  }
  
    document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('createNewLink').addEventListener('click', function(e) {
      e.preventDefault();
      openModal();
    });
     
    // Add save button event listener
    document.querySelector('.btn-save').addEventListener('click', function() {
      saveTeacher();
    });
    
    // Add capitalization event listeners to name fields
    addNameCapitalizationListeners();

    // Load teachers when page loads
    loadTeachers();
    
    // Load advisory options when page loads
    loadAdvisoryOptions();
      
    // Setup filter event listeners
    setupFilterListeners();
    
    // Check if we're coming from section creation with adviser assignment
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('action') === 'add_adviser') {
      const sectionId = urlParams.get('section_id');
      const gradeLevel = urlParams.get('grade_level');
      const section = urlParams.get('section');
      
      if (sectionId && gradeLevel && section) {
        // Open the modal and pre-select the advisory section
        openModal();
        
        // Set grade level first
        const gradeLevelSelect = document.getElementById('gradeLevel');
        if (gradeLevelSelect) {
          gradeLevelSelect.value = gradeLevel;
          updateModalSectionOptions(); // This will load advisory options for the grade
          
          // Wait a bit for the options to load, then select the advisory section
          setTimeout(() => {
            const advisorySelect = document.getElementById('advisory');
            if (advisorySelect) {
              advisorySelect.value = section;
            }
          }, 500);
        }
        
        // Clean the URL to remove the parameters
        window.history.replaceState({}, document.title, window.location.pathname);
      }
    }
  });
  
  function setFieldError(el, message) {
    if (!el) return;
    el.classList.add('input-error');

    if (el.tagName.toLowerCase() === 'select') {
      el.classList.add('select-error');
      const firstOption = el.options && el.options[0];
      if (firstOption) {
        if (!firstOption.dataset.originalText) {
          firstOption.dataset.originalText = firstOption.textContent;
        }
        firstOption.textContent = message;
        if (!el.value) el.selectedIndex = 0;
      }
    } else {
      if (!el.value) el.placeholder = message;
    }
  }

  function clearFieldError(el) {
    if (!el) return;
    el.classList.remove('input-error');
    
    if (el.classList.contains('select-error')) {
      const firstOption = el.options && el.options[0];
      if (firstOption && firstOption.dataset.originalText) {
        firstOption.textContent = firstOption.dataset.originalText;
        delete firstOption.dataset.originalText;
      }
      el.classList.remove('select-error');
    }
  }

  function clearAllErrors() {
    document.querySelectorAll('.input-error').forEach(el => {
      clearFieldError(el);
      if (el.type !== "date" && !el.dataset.originalType) {
        el.placeholder = ""; // clear gray error text for non-date fields
      }
    });
    document.querySelectorAll('select.select-error').forEach(el => {
      el.classList.remove('select-error');
      const firstOption = el.options && el.options[0];
      if (firstOption && firstOption.dataset.originalText) {
        firstOption.textContent = firstOption.dataset.originalText;
        delete firstOption.dataset.originalText;
      }
    });
  }

  function validateTeacherForm() {
    const requiredFields = [
      { id: 'teacherId', message: 'Teacher ID is required' },
      { id: 'firstName', message: 'First name is required' },
      { id: 'lastName', message: 'Last name is required' },
      { id: 'gradeLevel', message: 'Select grade level' }
    ];

    let isValid = true;
    requiredFields.forEach(f => {
      const el = document.getElementById(f.id);
      clearFieldError(el);
      const value = el && (el.tagName.toLowerCase() === 'select' ? el.value : el.value.trim());
      if (!value) {
        setFieldError(el, f.message);
        isValid = false;
      }
    });

    return isValid;
  }

  // Clear error styles on input/change
  document.addEventListener('input', function(e){
    if (e.target.matches('.input-error')) clearFieldError(e.target);
  });
  document.addEventListener('change', function(e){
    if (e.target.matches('select.select-error')) clearFieldError(e.target);
  });

  // Function to show confirmation dialog before saving
  function saveTeacher() {
    // Ensure names are capitalized before validation
    capitalizeNameFields();
    
    if (!validateTeacherForm()) {
      return; // stop if invalid
    }
    
    // Get form data and populate confirmation dialog
    const form = document.querySelector('.teacher-form');
    const formData = new FormData(form);
    
    // Populate teacher summary in confirmation dialog
    populateTeacherSummary(formData);
    
    // Show confirmation dialog
    showConfirmationDialog();
  }

  // Function to populate teacher summary in confirmation dialog
  function populateTeacherSummary(formData) {
    const summaryContainer = document.getElementById('studentSummary');
    
    // Get form values
    const teacherId = formData.get('teacherId') || 'N/A';
    const firstName = formData.get('firstName') || 'N/A';
    const lastName = formData.get('lastName') || 'N/A';
    const middleInitial = formData.get('middleInitial') || 'N/A';
    const gender = formData.get('gender') || 'N/A';
    const gradeLevel = formData.get('gradeLevel') || 'N/A';
    
    // Format the summary HTML
    const summaryHTML = `
      <h4>Teacher Information Summary</h4>
      <div class="summary-item">
        <span class="summary-label">Teacher ID:</span>
        <span class="summary-value">${teacherId}</span>
      </div>
      <div class="summary-item">
        <span class="summary-label">Name:</span>
        <span class="summary-value">${lastName}, ${firstName} ${middleInitial}</span>
      </div>
      <div class="summary-item">
        <span class="summary-label">Gender:</span>
        <span class="summary-value">${gender}</span>
      </div>
      <div class="summary-item">
        <span class="summary-label">Grade Level:</span>
        <span class="summary-value">${gradeLevel}</span>
      </div>
    `;
    
    summaryContainer.innerHTML = summaryHTML;
  }

  // Function to show confirmation dialog
  function showConfirmationDialog() {
    const modal = document.getElementById('confirmationModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }

  // Function to hide confirmation dialog
  function hideConfirmationDialog() {
    const modal = document.getElementById('confirmationModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
  }

  // Function to actually save the teacher (called from confirmation dialog)
  function confirmSaveStudent() {
    const form = document.querySelector('.teacher-form');
    const formData = new FormData(form);
    
    // Show loading state
    const confirmBtn = document.getElementById('confirmSaveBtn');
    const originalText = confirmBtn.textContent;
    confirmBtn.textContent = 'Saving...';
    confirmBtn.disabled = true;
    
    fetch('functions/save_teacher.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.status === 'success') {
        // Hide confirmation dialog
        hideConfirmationDialog();
        
        // Reset form and close modal
        form.reset();
        closeModal();
        loadTeachers();
        
        // Success message
        showToast('Teacher registered successfully!', 'success');
      } else {
        // Handle error
        if (data.field) {
          const el = document.getElementById(data.field);
          setFieldError(el, data.message || 'Invalid value');
        } else {
          console.error('Error:', data.message);
          showToast(data.message || 'Error saving teacher', 'error');
        }
        
        // Hide confirmation dialog on error
        hideConfirmationDialog();
      }
    })
    .catch(error => {
      console.error('Error:', error);
      showToast('Network error occurred while saving teacher', 'error');
      hideConfirmationDialog();
    })
    .finally(() => {
      confirmBtn.textContent = originalText;
      confirmBtn.disabled = false;
    });
  }
   
  // Function to load and display teachers
  function loadTeachers(gradeLevel = '') {
    const tableBody = document.getElementById('teachersTableBody');
    const teacherCount = document.getElementById('teacherCount');
    
    tableBody.innerHTML = `
      <tr>
        <td colspan="6" class="loading-message">
          <i class="fa-solid fa-spinner fa-spin"></i> Loading teachers...
        </td>
      </tr>
    `;
    
    let url = 'functions/get_teachers.php';
    const params = new URLSearchParams();
    if (gradeLevel) params.append('grade_level', gradeLevel);
    if (params.toString()) url += '?' + params.toString();
    
    fetch(url)
      .then(response => response.json())
      .then(data => {
        if (data.error) {
          tableBody.innerHTML = `
            <tr>
              <td colspan="6" class="error-message">
                <i class="fa-solid fa-exclamation-triangle"></i> Error: ${data.message}
              </td>
            </tr>
          `;
          return;
        }
        
        if (data.length === 0) {
          tableBody.innerHTML = `
            <tr>
              <td colspan="6" class="no-data-message">
                <i class="fa-solid fa-info-circle"></i> No teachers found. Click "Create New" to add a teacher.
              </td>
            </tr>
          `;
          teacherCount.textContent = '0';
          return;
        }
        
        teacherCount.textContent = data.length;
        
        const tableRows = data.map((teacher, index) => `
          <tr>
            <td>${index + 1}</td>
            <td>${teacher.teacher_id || '-'}</td>
            <td>${teacher.last_name}, ${teacher.first_name} ${teacher.middle_initial || ''}</td>
            <td>${formatGradeLabel(teacher.grade_level)}</td>
            <td>${teacher.advisory || '-'}</td>
            <td>
              <button class="btn-edit" onclick="editTeacher(${teacher.id})" title="Edit">
                <i class="fa-solid fa-edit"></i>
              </button>
              <button class="btn-delete" onclick="deleteTeacher(${teacher.id})" title="Delete">
                <i class="fa-solid fa-trash"></i>
              </button>
            </td>
          </tr>
        `).join('');
        
        tableBody.innerHTML = tableRows;
      })
      .catch(error => {
        console.error('Error loading teachers:', error);
        tableBody.innerHTML = `
          <tr>
            <td colspan="6" class="error-message">
              <i class="fa-solid fa-exclamation-triangle"></i> Failed to load teachers. Please try again.
            </td>
          </tr>
        `;
      });
  }
    
  function setupFilterListeners() {
    // Section filter removed - only grade level filter remains
  }
    
  function applyFilters() {
    const gradeLevel = document.getElementById('filterGradeLevel').value;
    loadTeachers(gradeLevel);
  }
    
  function clearFilters() {
    document.getElementById('filterGradeLevel').value = '';
    loadTeachers();
  }

  // Function to delete teacher
  function deleteTeacher(id) {
    // Store the teacher ID for the modal
    window.currentDeleteTeacherId = id;
    // Open delete confirmation modal
    openDeleteTeacherModal();
  }

  // Open delete teacher modal
  function openDeleteTeacherModal() {
    document.getElementById('deleteTeacherModal').style.display = 'block';
  }

  // Close delete teacher modal
  function closeDeleteTeacherModal() {
    document.getElementById('deleteTeacherModal').style.display = 'none';
    window.currentDeleteTeacherId = null;
  }

  // Confirm delete teacher
  function confirmDeleteTeacher() {
    const id = window.currentDeleteTeacherId;
    if (!id) {
      showToast('No teacher selected', 'error');
      return;
    }
    
    // Show loading state
    const deleteButton = document.querySelector('#deleteTeacherModal .btn-delete');
    const originalText = deleteButton.textContent;
    deleteButton.textContent = 'Deleting...';
    deleteButton.disabled = true;
    
    // Send delete request
    fetch('functions/delete_teacher.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: 'id=' + encodeURIComponent(id)
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        closeDeleteTeacherModal();
        showToast('Teacher deleted successfully!', 'success');
        loadTeachers();
      } else {
        showToast(data.message || 'Error deleting teacher', 'error');
      }
    })
    .catch(error => {
      console.error('Delete error:', error);
      showToast('Error deleting teacher: ' + error.message, 'error');
    })
    .finally(() => {
      // Restore button state
      if (deleteButton) {
        deleteButton.textContent = originalText;
        deleteButton.disabled = false;
      }
    });
  }

  // Toast notification functions
  function showToast(message, type = 'info') {
    const toast = document.getElementById('toast');
    const toastMessage = toast.querySelector('.toast-message');
    const toastIcon = toast.querySelector('.toast-icon svg');
    
    // Set message
    toastMessage.textContent = message;
    
    // Remove existing type classes
    toast.classList.remove('success', 'error', 'warning', 'info');
    toast.classList.add(type);
    
    // Set appropriate icon based on type
    let iconSvg = '';
    switch(type) {
      case 'success':
        iconSvg = '<polyline points="20,6 9,17 4,12"></polyline>';
        break;
      case 'error':
        iconSvg = '<circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>';
        break;
      case 'warning':
        iconSvg = '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>';
        break;
      default:
        iconSvg = '<circle cx="12" cy="12" r="10"></circle><path d="m9 12 2 2 4-4"></path>';
    }
    toastIcon.innerHTML = iconSvg;
    
    // Show toast
    toast.classList.add('show');
    
    // Auto hide after 5 seconds
    setTimeout(() => {
      hideToast();
    }, 5000);
  }

  function hideToast() {
    const toast = document.getElementById('toast');
    toast.classList.remove('show');
  }

  // Add event listeners for confirmation dialog
  document.addEventListener('DOMContentLoaded', function() {
    // Confirm Save button
    const confirmSaveBtn = document.getElementById('confirmSaveBtn');
    if (confirmSaveBtn) {
      confirmSaveBtn.addEventListener('click', confirmSaveStudent);
    }
    
    // Back to Form button
    const backToFormBtn = document.getElementById('backToFormBtn');
    if (backToFormBtn) {
      backToFormBtn.addEventListener('click', hideConfirmationDialog);
    }
    
    // Close confirmation dialog when clicking outside
    const confirmationModal = document.getElementById('confirmationModal');
    if (confirmationModal) {
      confirmationModal.addEventListener('click', function(e) {
        if (e.target === confirmationModal) {
          hideConfirmationDialog();
        }
      });
    }
    
    // Close confirmation dialog with Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        hideConfirmationDialog();
      }
    });
  });


  // Edit teacher functionality
  function editTeacher(id) {
    console.log('editTeacher called with ID:', id);
    
    // Show loading state
    const modal = document.getElementById('editTeacherModal');
    modal.style.display = 'block';
    
    // Fetch teacher data
    fetch(`functions/get_teacher.php?id=${id}`)
      .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
          throw new Error(`Expected JSON but got ${contentType || 'unknown content type'}`);
        }
        
        // Check if response is ok
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return response.text().then(text => {
          console.log('Raw response:', text);
          try {
            return JSON.parse(text);
          } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response text:', text);
            throw new Error('Invalid JSON response: ' + text.substring(0, 100));
          }
        });
      })
      .then(data => {
        console.log('Teacher data received:', data);
        if (data.error) {
          showToast(data.message, 'error');
          modal.style.display = 'none';
          return;
        }

        // Populate edit form
        document.getElementById('editTeacherId').value = data.id;
        document.getElementById('editTeacherIdField').value = data.teacher_id;
        document.getElementById('editFirstName').value = data.first_name;
        document.getElementById('editLastName').value = data.last_name;
        document.getElementById('editMiddleInitial').value = data.middle_initial || '';
        document.getElementById('editGender').value = data.gender || '';
        document.getElementById('editGradeLevel').value = data.grade_level;

        // Load advisory options for the selected grade level
        loadAdvisoryOptions(data.grade_level, 'edit', data.id, () => {
          // Set the advisory value after advisory options are loaded
          document.getElementById('editAdvisory').value = data.advisory || '';
        });

        // Show edit modal
        document.getElementById('editTeacherModal').style.display = 'block';
      })
      .catch(error => {
        console.error('Error fetching teacher data:', error);
        showToast('Failed to load teacher data: ' + error.message, 'error');
        modal.style.display = 'none';
      });
  }

  // Update advisory options for edit modal
  function updateEditSectionOptions() {
    const gradeSelect = document.getElementById('editGradeLevel');
    const selectedGrade = gradeSelect.value;
    
    // Update advisory options based on grade level
    loadAdvisoryOptions(selectedGrade, 'edit');
  }

  // Close edit modal
  function closeEditModal() {
    const modal = document.getElementById('editTeacherModal');
    modal.style.display = 'none';

    // Reset form when closing
    const form = document.getElementById('editTeacherForm');
    form.reset();
    clearAllErrors();
  }

  // Update teacher function
  function updateTeacher() {
    const form = document.getElementById('editTeacherForm');
    
    // Ensure names are capitalized before validation
    capitalizeNameFields();
    
    // Validate form
    if (!validateEditForm()) {
      return;
    }

    const formData = new FormData(form);
    formData.append('originalTeacherId', document.getElementById('editTeacherIdField').value);

    // Show loading state
    const updateBtn = document.querySelector('#editTeacherModal .btn-save');
    const originalText = updateBtn.textContent;
    updateBtn.textContent = 'Updating...';
    updateBtn.disabled = true;

    fetch('functions/update_teacher.php', {
      method: 'POST',
      body: formData
    })
    .then(response => {
      console.log('Update response status:', response.status);
      console.log('Update response headers:', response.headers);
      
      // Check if response is JSON
      const contentType = response.headers.get('content-type');
      if (!contentType || !contentType.includes('application/json')) {
        throw new Error(`Expected JSON but got ${contentType || 'unknown content type'}`);
      }
      
      // Check if response is ok
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      return response.text().then(text => {
        console.log('Update raw response:', text);
        try {
          return JSON.parse(text);
        } catch (e) {
          console.error('Update JSON parse error:', e);
          console.error('Update response text:', text);
          throw new Error('Invalid JSON response: ' + text.substring(0, 100));
        }
      });
    })
    .then(data => {
      console.log('Update data received:', data);
      if (data.status === 'success') {
        // Close modal and refresh table
        closeEditModal();
        loadTeachers();
        showToast('Teacher updated successfully!', 'success');
      } else {
        // Handle error
        if (data.field) {
          const el = document.getElementById('edit' + data.field.charAt(0).toUpperCase() + data.field.slice(1));
          if (el) {
            setFieldError(el, data.message || 'Invalid value');
          }
        } else {
          showToast(data.message || 'Error updating teacher', 'error');
        }
      }
    })
    .catch(error => {
      console.error('Error:', error);
      showToast('Network error occurred while updating teacher: ' + error.message, 'error');
    })
    .finally(() => {
      updateBtn.textContent = originalText;
      updateBtn.disabled = false;
    });
  }

  // Validate edit form
  function validateEditForm() {
    const requiredFields = [
      { id: 'editTeacherIdField', message: 'Teacher ID is required' },
      { id: 'editFirstName', message: 'First name is required' },
      { id: 'editLastName', message: 'Last name is required' },
      { id: 'editGender', message: 'Gender is required' },
      { id: 'editGradeLevel', message: 'Select grade level' }
    ];

    let isValid = true;
    requiredFields.forEach(f => {
      const el = document.getElementById(f.id);
      clearFieldError(el);
      const value = el && (el.tagName.toLowerCase() === 'select' ? el.value : el.value.trim());
      if (!value) {
        setFieldError(el, f.message);
        isValid = false;
      }
    });

    return isValid;
  }

  // Close edit modal when clicking outside
  window.addEventListener('click', function(event) {
    const editModal = document.getElementById('editTeacherModal');
    if (event.target === editModal) {
      closeEditModal();
    }
  });
</script>

</body>
</html>
