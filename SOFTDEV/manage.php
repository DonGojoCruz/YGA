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

$page_title = "Student Masterlist";
$additional_css = "css/masterlist.css";

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
        <div class="page-actions">
          <button class="btn btn-primary" id="createNewLink">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Create New
          </button>
          <a href="functions/export_students.php" class="btn btn-secondary" id="exportLink">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <polyline points="14,2 14,8 20,8"></polyline>
              <line x1="16" y1="13" x2="8" y2="13"></line>
              <line x1="16" y1="17" x2="8" y2="17"></line>
              <polyline points="10,9 9,9 8,9"></polyline>
            </svg>
            Export Excel
          </a>
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

          <select class="filter-select" id="filterSection">
            <option value="">--Select Section--</option>
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
            <span id="studentCount">0</span> students found
          </div>
        </div>
        <table class="data-table" aria-label="Students table">
          <thead>
            <tr>
              <th>#</th>
              <th>LRN</th>
              <th>LAST NAME</th>
              <th>FIRST NAME</th>
              <th>MID. NAME</th>
              <th>GUARDIAN</th>
              <th>EMAIL</th>
              <th>RFID TAG</th>
              <th>GRADE</th>
              <th>SECTION</th>
              <th>ACTIONS</th>
            </tr>
          </thead>
          <tbody id="studentsTableBody">
            <tr>
              <td colspan="11" class="loading-message">
                <i class="fa-solid fa-spinner fa-spin"></i> Loading students...
              </td>
            </tr>
          </tbody>
        </table>
      </section>
    </main>
  </div>

  <!-- Student Entry Modal -->
  <div id="studentModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Student Entry</h2>
        <button class="close-btn" onclick="closeModal()">&times;</button>
      </div>

      <form class="student-form">
        <div class="form-columns">
          <div class="form-column">
            <h3>Basic Information</h3>
            <div class="form-group">
              <label for="rfidTag" class="required">
                RFID Tag ID 
                <span id="rfidStatus" style="font-size: 0.8em; color: #28a745; font-weight: normal;">🔄 Scanning...</span>
              </label>
              <input type="text" id="rfidTag" name="rfidTag" required readonly style="background-color: #f5f5f5; color: #666;">
            </div>
            <div class="form-group">
              <label for="lrn">LRN</label>
              <input type="text" id="lrn" name="lrn" pattern="[0-9]{1,12}" inputmode="numeric" maxlength="12" onkeypress="return event.charCode >= 48 && event.charCode <= 57">
            </div>
            <div class="form-group">
              <label for="lastName">Last Name</label>
              <input type="text" id="lastName" name="lastName" pattern="[A-Za-z' -]{1,50}" title="Letters, spaces, hyphens (-) and apostrophes (') only" maxlength="50" oninput="this.value=this.value.replace(/[^A-Za-z\s'\-]/g,'')">
            </div>
            <div class="form-group">
              <label for="firstName">First Name</label>
              <input type="text" id="firstName" name="firstName" pattern="[A-Za-z' -]{1,50}" title="Letters, spaces, hyphens (-) and apostrophes (') only" maxlength="50" oninput="this.value=this.value.replace(/[^A-Za-z\s'\-]/g,'')">
            </div>
            <div class="form-group">
              <label for="middleInitial">Middle Name</label>
              <input type="text" id="middleInitial" name="middleInitial" maxlength="50" pattern="[A-Za-z' -]{0,50}" title="Letters, spaces, hyphens (-) and apostrophes (') only" oninput="this.value=this.value.replace(/[^A-Za-z\s'\-]/g,'')">
            </div>
            <div class="form-group">
              <label for="birthdate">Birthdate</label>
              <input type="date" id="birthdate" name="birthdate" placeholder="mm/dd/year">
            </div>
            <div class="form-group">
              <label for="gender">Gender</label>
              <select id="gender" name="gender">
                <option value="">--Select--</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
              </select>
            </div>
          </div>

          <div class="form-column-right">
            <div class="form-right-content">
              <div class="form-section">
                <h3>Contact Information</h3>
                <div class="form-group">
                  <label for="guardian">Guardian</label>
                  <input type="text" id="guardian" name="guardian" pattern="[A-Za-z ]{1,50}" title="Letters and spaces only" maxlength="50" oninput="this.value=this.value.replace(/[^A-Za-z\s]/g,'')">
                </div>
                <div class="form-group">
                  <label for="email">Email <!-- <span class="verification-note">(Verification Required)</span> --></label>
                  <input type="email" id="email" name="email">
                </div>
              </div>
              <div class="form-section">
                <h3>Grade Level and Section</h3>
                <div class="form-group">
                  <label for="gradeLevel">Grade Level</label>
                  <select id="gradeLevel" name="gradeLevel" onchange="updateModalSectionOptions()">
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
                  <label for="section">Section</label>
                  <select id="section" name="section">
                    <option value="">--Select--</option>
                  </select>
                </div>

                <div class="form-actions">
                  <button type="button" class="btn btn-save">Save</button>
                  <button type="button" class="btn btn-cancel" onclick="closeModal()">Cancel</button>
                </div>
              </div>
            </div>

            <div class="form-right-photo">
              <div class="photo-uploader">
                <div class="photo-preview" id="photoPreviewContainer" aria-label="Photo preview">
                  <img id="photoPreview" alt="Student photo preview" style="display:none;" />
                  <div class="photo-placeholder" id="photoPlaceholder">No photo selected</div>
                  <video id="cameraStream" autoplay playsinline style="display:none; width:100%; max-height:200px; border-radius:10px;"></video>
                  <canvas id="photoCanvas" style="display:none;"></canvas>
                </div>
                <div class="photo-actions">
                  <button type="button" class="btn btn-browse" id="btnStartCamera">Open Camera</button>
                  <button type="button" class="btn btn-capture" id="btnCapturePhoto" style="display:none;">Capture</button>
                  <button type="button" class="btn btn-remove" id="btnRemovePhoto" style="display:none;">Remove</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>
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


  // Update section options when grade level changes
  function updateModalSectionOptions() {
    const gradeSelect = document.getElementById('gradeLevel');
    const sectionSelect = document.getElementById('section');
    const selectedGrade = gradeSelect.value;
    
    sectionSelect.innerHTML = '<option value="">--Select--</option>';
    
    if (selectedGrade) {
      return fetch(`functions/get_sections.php?grade=${selectedGrade}`)
        .then(response => {
          if (!response.ok) throw new Error('Network response was not ok');
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
          }
        })
        .catch(error => {
          console.error('Error fetching sections:', error);
          sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
        });
    }
    
    return Promise.resolve();
  }

  // Photo uploader handlers
  function handlePhotoChange(event) {
    const file = event.target.files && event.target.files[0];
    const preview = document.getElementById('photoPreview');
    const placeholder = document.getElementById('photoPlaceholder');
    const removeBtn = document.getElementById('btnRemovePhoto');
    if (!file) { return; }
    const reader = new FileReader();
    reader.onload = function(e) {
      preview.src = e.target.result;
      preview.style.display = 'block';
      placeholder.style.display = 'none';
      removeBtn.style.display = 'inline-block';
    };
    reader.readAsDataURL(file);
  }

  // Reset photo preview & camera
  function resetPhoto() {
    const preview = document.getElementById('photoPreview');
    const placeholder = document.getElementById('photoPlaceholder');
    const video = document.getElementById('cameraStream');
    const btnStartCamera = document.getElementById('btnStartCamera');
    const btnCapturePhoto = document.getElementById('btnCapturePhoto');
    const btnRemovePhoto = document.getElementById('btnRemovePhoto');

    preview.src = "";
    preview.style.display = "none";
    placeholder.style.display = "block";
    video.style.display = "none";
    btnStartCamera.style.display = "inline-block";
    btnCapturePhoto.style.display = "none";
    btnRemovePhoto.style.display = "none";

    if (video.srcObject) {
      video.srcObject.getTracks().forEach(track => track.stop());
      video.srcObject = null;
    }
  }

  // Global variable to track polling interval
  let rfidPollingInterval = null;
  
  // Global variables to track edit mode
  let isEditMode = false;
  let editingStudentId = null;

  // Function to fetch RFID data from manage.txt
  function fetchRFIDData() {
    fetch('functions/get_rfid_data.php')
      .then(response => response.json())
      .then(data => {
        const rfidField = document.getElementById('rfidTag');
        const statusElement = document.getElementById('rfidStatus');
        
        if (data.success && data.rfid) {
          const originalRfid = rfidField.dataset.originalRfid;
          rfidField.value = data.rfid;
          rfidField.style.color = '#333'; // Normal text color when data is found
          
          // Update status to show data found
          if (statusElement) {
            if (isEditMode && originalRfid && originalRfid !== data.rfid) {
              statusElement.innerHTML = `✅ New tag detected! (Was: ${originalRfid})`;
            } else if (isEditMode && originalRfid && originalRfid === data.rfid) {
              statusElement.innerHTML = '✅ Same tag confirmed!';
            } else {
              statusElement.innerHTML = '✅ Tag Detected!';
            }
            statusElement.style.color = '#28a745';
          }
        } else {
          // Only clear the field if it's empty, to preserve existing valid data
          if (!rfidField.value) {
            rfidField.value = '';
            rfidField.style.color = '#999'; // Lighter color when no data
          }
          
          // Keep scanning status if no data found
          if (statusElement && rfidPollingInterval) {
            if (isEditMode && rfidField.dataset.originalRfid) {
              statusElement.innerHTML = `📋 Current: ${rfidField.dataset.originalRfid} | 🔄 Scanning for new tag...`;
            } else {
              statusElement.innerHTML = '🔄 Scanning...';
            }
            statusElement.style.color = '#ffc107';
          }
        }
      })
      .catch(error => {
        console.error('Error fetching RFID data:', error);
        // Don't clear the field on network errors to preserve existing data
      });
  }

  // Function to start continuous RFID polling
  function startRFIDPolling() {
    // Clear any existing interval
    if (rfidPollingInterval) {
      clearInterval(rfidPollingInterval);
    }
    
    // Update status indicator
    const statusElement = document.getElementById('rfidStatus');
    const rfidField = document.getElementById('rfidTag');
    if (statusElement) {
      if (isEditMode && rfidField && rfidField.dataset.originalRfid) {
        statusElement.innerHTML = `📋 Current: ${rfidField.dataset.originalRfid} | 🔄 Scanning for new tag...`;
      } else {
        statusElement.innerHTML = '🔄 Scanning...';
      }
      statusElement.style.color = '#ffc107';
    }
    
    // Fetch immediately
    fetchRFIDData();
    
    // Then poll every 1 second
    rfidPollingInterval = setInterval(fetchRFIDData, 1000);
    console.log('🔄 Started RFID polling (every 1 second)');
  }

  // Function to stop RFID polling
  function stopRFIDPolling() {
    if (rfidPollingInterval) {
      clearInterval(rfidPollingInterval);
      rfidPollingInterval = null;
      console.log('⏹️ Stopped RFID polling');
      
      // Update status indicator
      const statusElement = document.getElementById('rfidStatus');
      if (statusElement) {
        statusElement.innerHTML = '⏹️ Stopped';
        statusElement.style.color = '#6c757d';
      }
    }
  }

  // Script Loading Overlay Functions
  function showScriptLoadingOverlay() {
    let overlay = document.getElementById('scriptLoadingOverlay');
    if (!overlay) {
      // Create loading overlay if it doesn't exist
      overlay = document.createElement('div');
      overlay.id = 'scriptLoadingOverlay';
      overlay.innerHTML = `
        <div class="loading-overlay-content">
          <div class="loading-spinner"></div>
          <div class="loading-message" id="scriptLoadingMessage">Processing RFID scripts...</div>
        </div>
      `;
      document.body.appendChild(overlay);
    }
    overlay.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }

  function hideScriptLoadingOverlay() {
    const overlay = document.getElementById('scriptLoadingOverlay');
    if (overlay) {
      overlay.style.display = 'none';
      document.body.style.overflow = 'auto';
    }
  }

  // Prevent modal closing during script loading
  function isScriptLoading() {
    const overlay = document.getElementById('scriptLoadingOverlay');
    return overlay && overlay.style.display === 'flex';
  }

  function updateLoadingMessage(message) {
    const messageElement = document.getElementById('scriptLoadingMessage');
    if (messageElement) {
      messageElement.textContent = message;
    }
  }

  // Modal functions
  async function openModal() {
    document.getElementById('studentModal').style.display = 'block';

    // Only reset form if not in edit mode
    if (!isEditMode) {
      const form = document.querySelector('.student-form');
      form.reset();
      resetPhoto();
      resetModalToCreateMode();
    }

    // Show loading overlay while preparing Python scripts
    showScriptLoadingOverlay();
    updateLoadingMessage('VeriTap Loading...');

    // Always start RFID scanning for both create and edit modes
    // Ensure txtRFID is running in background (VBS preferred, fallback BAT), then poll manage.txt
    try { await executeStopRFIDScript(); } catch(_) { /* ignore stop errors */ }
    try {
      await executeStartRFIDScript();
    } catch (e) { console.warn('Could not start txtRFID script:', e); }

    // Small delay to let Python initialize
    await new Promise(r => setTimeout(r, 300));

    // Hide overlay and begin polling
    hideScriptLoadingOverlay();
    startRFIDPolling();
  }
  
  async function closeModal() {
    // Stop RFID polling and run stopRFID scripts
    stopRFIDPolling();
    
    try {
      await executeStopRFIDScript();
    } catch (e) {
      console.warn('Could not stop RFID script:', e);
    }

    const modal = document.getElementById('studentModal');
    modal.style.display = 'none';

    // Reset form and photo when closing
    const form = document.querySelector('.student-form');
    form.reset();
    resetPhoto();
    clearAllErrors();
    
    // Reset edit mode
    resetModalToCreateMode();
  }
  
  window.onclick = function(event) {
    const modal = document.getElementById('studentModal');
    if (event.target === modal && !isScriptLoading()) {
      closeModal();
    }
  }
  
  document.addEventListener('DOMContentLoaded', function() {
    const createBtn = document.getElementById('createNewLink');
    createBtn.addEventListener('click', async function(e) {
      e.preventDefault();
      // Set button loading state
      const originalHtml = createBtn.innerHTML;
      createBtn.disabled = true;
      createBtn.innerHTML = '<span class="spinner" style="display:inline-block;width:14px;height:14px;border:2px solid #fff;border-top-color:transparent;border-radius:50%;margin-right:8px;vertical-align:-2px;animation:spin 0.8s linear infinite"></span> Loading...';

      try {
        await openModal();
      } finally {
        // Restore button state
        createBtn.disabled = false;
        createBtn.innerHTML = originalHtml;
      }
    });
     
    // Add save button event listener
    document.querySelector('.btn-save').addEventListener('click', function() {
      saveStudent();
    });
     
    // Set birthdate limits (at least 3 years old, not future)
    setBirthdateLimits();

    // Load students when page loads
    loadStudents();
     
    // Setup filter event listeners
    setupFilterListeners();

    // Wire export link to current filters
    const exportLink = document.getElementById('exportLink');
    function updateExportHref() {
      const gradeLevel = document.getElementById('filterGradeLevel')?.value || '';
      const section = document.getElementById('filterSection')?.value || '';
      const params = new URLSearchParams();
      if (gradeLevel) params.append('grade_level', gradeLevel);
      if (section) params.append('section', section);
      exportLink.href = 'functions/export_students.php' + (params.toString() ? ('?' + params.toString()) : '');
    }
    updateExportHref();
    document.getElementById('filterGradeLevel').addEventListener('change', updateExportHref);
    document.getElementById('filterSection').addEventListener('change', updateExportHref);
  });
  
  function setFieldError(el, message) {
    if (!el) return;
    el.classList.add('input-error');

    if (el.type === "date") {
      el.value = ""; // clear invalid date
      el.classList.add("date-error"); // for red text
      // Convert to text input to show error message inside the box
      el.type = "text";
      el.value = message;
      el.style.color = "#ef4444";
      el.readOnly = true;
      
      // Store original attributes
      el.dataset.originalType = "date";
      el.dataset.errorMessage = message;
      
      // When user clicks, restore to date input
      el.addEventListener('click', function restoreDateInput() {
        if (el.readOnly) {
          el.type = "date";
          el.value = "";
          el.style.color = "";
          el.readOnly = false;
          el.removeEventListener('click', restoreDateInput);
          delete el.dataset.originalType;
          delete el.dataset.errorMessage;
        }
      }, { once: true });
      
    } else if (el.tagName.toLowerCase() === 'select') {
      el.classList.add('select-error');
      const firstOption = el.options && el.options[0];
      if (firstOption) {
        if (!firstOption.dataset.originalText) {
          firstOption.dataset.originalText = firstOption.textContent;
        }
        firstOption.textContent = message;
        if (!el.value) el.selectedIndex = 0;
      }
    } else if (el.type === "text" || el.type === "email") {
      // For text inputs, show error message inside the box
      // Store original value before showing error
      if (!el.dataset.originalValue) {
        el.dataset.originalValue = el.value;
      }
      el.value = message;
      el.style.color = "#ef4444";
      el.readOnly = true;
      el.dataset.errorMessage = message;
      
      // When user clicks, restore original input
      el.addEventListener('click', function restoreTextInput() {
        if (el.readOnly) {
          el.value = el.dataset.originalValue || "";
          el.readOnly = false;
          el.style.color = "";
          el.classList.remove("input-error");
          el.removeEventListener('click', restoreTextInput);
          delete el.dataset.originalValue;
          delete el.dataset.errorMessage;
        }
      }, { once: true });
    } else {
      if (!el.value) el.placeholder = message;
    }
  }

  function clearFieldError(el) {
    if (!el) return;
    el.classList.remove('input-error', 'date-error');
    
    // Handle date inputs that were converted to text for error display
    if (el.dataset.originalType === "date") {
      el.type = "date";
      el.value = "";
      el.style.color = "";
      el.readOnly = false;
      delete el.dataset.originalType;
      delete el.dataset.errorMessage;
    } else if (el.type === "date") {
      el.placeholder = ""; // reset placeholder (though it won't show on date inputs)
    }
    
    // Handle text inputs that were set to read-only for error display
    if (el.dataset.errorMessage && (el.type === "text" || el.type === "email")) {
      el.value = el.dataset.originalValue || "";
      el.style.color = "";
      el.readOnly = false;
      delete el.dataset.originalValue;
      delete el.dataset.errorMessage;
    }
    
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
    const photoBox = document.getElementById('photoPreviewContainer');
    if (photoBox) photoBox.classList.remove('photo-error');
  }

  function hasSelectedPhoto() {
    const preview = document.getElementById('photoPreview');
    return !!(preview && preview.src && preview.style.display !== 'none');
  }

  function setBirthdateLimits() {
    const birth = document.getElementById('birthdate');
    if (!birth) return;
    // Allow any past date up to today; the 4-year minimum age is enforced in validation
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    birth.setAttribute('max', `${yyyy}-${mm}-${dd}`);
  }

  function validateStudentForm() {
    const requiredFields = [
      { id: 'rfidTag', message: 'RFID Tag is required' },
      { id: 'lrn', message: 'LRN Tag is required' },
      { id: 'lastName', message: 'Last name is required' },
      { id: 'firstName', message: 'First name is required' },
      { id: 'gradeLevel', message: 'Select grade level' },
      { id: 'section', message: 'Select section' },
      { id: 'gender', message: 'Select gender' },
      { id: 'guardian', message: 'Guardian is required' },
      { id: 'email', message: 'Email is required' },
      { id: 'birthdate', message: 'Birthday is required' }
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

    // Photo required
    const photoBox = document.getElementById('photoPreviewContainer');
    if (photoBox) photoBox.classList.remove('photo-error');
    if (!hasSelectedPhoto()) {
      if (photoBox) photoBox.classList.add('photo-error');
      isValid = false;
    }

    // Birthdate validation: not future, at least 4 years old
    const birth = document.getElementById('birthdate');
    if (birth && birth.value) {
      const selected = new Date(birth.value + 'T00:00:00');
      const today = new Date();
      const minAgeDate = new Date(today.getFullYear() - 4, today.getMonth(), today.getDate());

      if (selected > today) {
        setFieldError(birth, 'Birthdate cannot be in the future');
        isValid = false;
      } else if (selected > minAgeDate) {
        setFieldError(birth, 'Age should be atleast 4 yrs. old');
        isValid = false;
      }
    }

    // Optional simple email format check if provided
    const email = document.getElementById('email');
    if (email && email.value.trim()) {
      const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!re.test(email.value.trim())) {
        setFieldError(email, 'Enter a valid email');
        isValid = false;
      }
    }

    return isValid;
  }

  // Clear error styles on input/change
  document.addEventListener('input', function(e){
    if (e.target.matches('.input-error')) clearFieldError(e.target);
  });
  document.addEventListener('change', function(e){
    if (e.target.matches('select.select-error')) clearFieldError(e.target);
  });

  // When removing or adding photo, clear photo error
  document.addEventListener('click', function(e){
    if (e.target && (e.target.id === 'btnStartCamera' || e.target.id === 'btnCapturePhoto' || e.target.id === 'btnRemovePhoto')) {
      const photoBox = document.getElementById('photoPreviewContainer');
      if (photoBox) photoBox.classList.remove('photo-error');
    }
  });

  // Function to show confirmation dialog before saving
  function saveStudent() {
    if (!validateStudentForm()) {
      return; // stop if invalid
    }
    
    // Get form data and populate confirmation dialog
    const form = document.querySelector('.student-form');
    const formData = new FormData(form);
    
    // Populate student summary in confirmation dialog
    populateStudentSummary(formData);
    
    // Show confirmation dialog
    showConfirmationDialog();
  }

  // Function to populate student summary in confirmation dialog
  function populateStudentSummary(formData) {
    const summaryContainer = document.getElementById('studentSummary');
    
    // Get form values
    const rfidTag = formData.get('rfidTag') || 'N/A';
    const lrn = formData.get('lrn') || 'N/A';
    const lastName = formData.get('lastName') || 'N/A';
    const firstName = formData.get('firstName') || 'N/A';
    const middleInitial = formData.get('middleInitial') || 'N/A';
    const birthdate = formData.get('birthdate') || 'N/A';
    const gender = formData.get('gender') || 'N/A';
    const guardian = formData.get('guardian') || 'N/A';
    const email = formData.get('email') || 'N/A';
    const gradeLevel = formData.get('gradeLevel') || 'N/A';
    const section = formData.get('section') || 'N/A';
    
    // Format the summary HTML
    const summaryHTML = `
      <h4>Student Information Summary</h4>
      <div class="summary-item">
        <span class="summary-label">RFID Tag:</span>
        <span class="summary-value">${rfidTag}</span>
      </div>
      <div class="summary-item">
        <span class="summary-label">LRN:</span>
        <span class="summary-value">${lrn}</span>
      </div>
      <div class="summary-item">
        <span class="summary-label">Name:</span>
        <span class="summary-value">${lastName}, ${firstName} ${middleInitial}</span>
      </div>
      <div class="summary-item">
        <span class="summary-label">Birthdate:</span>
        <span class="summary-value">${birthdate}</span>
      </div>
      <div class="summary-item">
        <span class="summary-label">Gender:</span>
        <span class="summary-value">${gender}</span>
      </div>
      <div class="summary-item">
        <span class="summary-label">Guardian:</span>
        <span class="summary-value">${guardian}</span>
      </div>
      <div class="summary-item">
        <span class="summary-label">Email:</span>
        <span class="summary-value">${email}</span>
      </div>
      <div class="summary-item">
        <span class="summary-label">Grade Level:</span>
        <span class="summary-value">${gradeLevel}</span>
      </div>
      <div class="summary-item">
        <span class="summary-label">Section:</span>
        <span class="summary-value">${section}</span>
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

  // Function to actually save the student (called from confirmation dialog)
  function confirmSaveStudent() {
    // Stop RFID polling during save process
    stopRFIDPolling();
    
    const form = document.querySelector('.student-form');
    const formData = new FormData(form);
    
    // Add student ID if in edit mode
    if (isEditMode && editingStudentId) {
      formData.append('student_id', editingStudentId);
      formData.append('is_edit', '1');
    }
    
    // Attach captured photo (from dataURL) as a file upload
    try {
      const preview = document.getElementById('photoPreview');
      if (preview && preview.src && preview.style.display !== 'none') {
        // Check if it's a data URL (captured photo) or existing image path
        if (preview.src.startsWith('data:')) {
          const blob = dataURLToBlob(preview.src);
          if (blob) {
            formData.append('photo', new File([blob], 'captured_photo.png', { type: blob.type || 'image/png' }));
          }
        }
        // If it's an existing image path, we don't need to do anything as the photo_path will be preserved
      }
    } catch (e) {
      console.warn('Could not append captured photo:', e);
    }
    
    // Show loading state
    const confirmBtn = document.getElementById('confirmSaveBtn');
    const originalText = confirmBtn.textContent;
    confirmBtn.textContent = isEditMode ? 'Updating...' : 'Saving...';
    confirmBtn.disabled = true;
    
    fetch('functions/save_student.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.status === 'success') {
        // Check if email verification is required
        if (data.requires_verification) {
          // Hide confirmation dialog
          hideConfirmationDialog();
          
          // Reset form and close modal
          form.reset();
          resetPhoto();
          closeModal();
          loadStudents();
          
          // Show email verification message
          showSuccessMessage(data.message);
        } else {
          // Hide confirmation dialog
          hideConfirmationDialog();
          
          // Reset form and close modal
          form.reset();
          resetPhoto();
          closeModal();
          loadStudents();
          
          // Only show success popup if email verification was actually sent
          // Check for messages that indicate verification email was sent (not just mentioned)
          if (data.message && (
              data.message.includes('verification email has been sent') || 
              data.message.includes('A verification email has been sent') ||
              data.message.includes('Verification email sent to')
          )) {
            showSuccessMessage(data.message);
          }
          // For regular updates (including "Email unchanged - no verification required"), no popup is shown
          
          // Success - no additional alert needed since confirmation dialog was the final step
        }
      } else {
        // Handle error(s)
        if (data.errors && Array.isArray(data.errors)) {
          // Handle multiple errors
          data.errors.forEach(error => {
            const el = document.getElementById(error.field);
            if (el) {
              setFieldError(el, error.message || 'Invalid value');
            }
          });
        } else if (data.field) {
          // Handle single error (backward compatibility)
          const el = document.getElementById(data.field);
          setFieldError(el, data.message || 'Invalid value');
        } else {
          console.error('Error:', data.message);
          showErrorMessage(data.message || 'Error saving student');
        }
        
        // Hide confirmation dialog on error
        hideConfirmationDialog();
      }
    })
    .catch(error => {
      console.error('Error:', error);
      showErrorMessage('Network error occurred while saving student');
      hideConfirmationDialog();
    })
    .finally(() => {
      confirmBtn.textContent = originalText;
      confirmBtn.disabled = false;
    });
  }

  function dataURLToBlob(dataURL) {
    if (!dataURL || typeof dataURL !== 'string' || !dataURL.startsWith('data:')) return null;
    const parts = dataURL.split(',');
    const mimeMatch = parts[0].match(/data:(.*?);base64/);
    const mime = mimeMatch ? mimeMatch[1] : 'image/png';
    const byteString = atob(parts[1]);
    const arrayBuffer = new ArrayBuffer(byteString.length);
    const uint8Array = new Uint8Array(arrayBuffer);
    for (let i = 0; i < byteString.length; i++) {
      uint8Array[i] = byteString.charCodeAt(i);
    }
    return new Blob([uint8Array], { type: mime });
  }

  // Function to execute start RFID script
  function executeStartRFIDScript() {
    return fetch('functions/execute_rfid_script.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: 'action=start'
    })
    .then(response => response.json())
    .then(data => {
      if (data.status === 'success') {
        console.log('RFID start script executed successfully');
        return data;
      } else {
        console.warn('Failed to execute RFID start script:', data.message);
        throw new Error(data.message);
      }
    })
    .catch(error => {
      console.error('Error executing RFID start script:', error);
      throw error;
    });
  }

  // Function to execute stop RFID script
  function executeStopRFIDScript() {
    return fetch('functions/execute_rfid_script.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: 'action=stop'
    })
    .then(response => response.json())
    .then(data => {
      if (data.status === 'success') {
        console.log('RFID stop script executed successfully');
        return data;
      } else {
        console.warn('Failed to execute RFID stop script:', data.message);
        throw new Error(data.message);
      }
    })
    .catch(error => {
      console.error('Error executing RFID stop script:', error);
      throw error;
    });
  }

  // Function to execute start display RFID script (runRFID)
  function executeStartDisplayRFIDScript() {
    return fetch('functions/execute_rfid_script.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: 'action=start_display'
    })
    .then(response => response.json())
    .then(data => {
      if (data.status === 'success') {
        console.log('RFID display script executed successfully');
        return data;
      } else {
        console.warn('Failed to execute RFID display script:', data.message);
        throw new Error(data.message);
      }
    })
    .catch(error => {
      console.error('Error executing RFID display script:', error);
      throw error;
    });
  }
   
  // Function to load and display students
  function loadStudents(gradeLevel = '', section = '') {
    const tableBody = document.getElementById('studentsTableBody');
    const studentCount = document.getElementById('studentCount');
    
    tableBody.innerHTML = `
      <tr>
        <td colspan="11" class="loading-message">
          <i class="fa-solid fa-spinner fa-spin"></i> Loading students...
        </td>
      </tr>
    `;
    
    let url = 'functions/get_students.php';
    const params = new URLSearchParams();
    if (gradeLevel) params.append('grade_level', gradeLevel);
    if (section) params.append('section', section);
    if (params.toString()) url += '?' + params.toString();
    
    fetch(url)
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        
        if (data.error) {
          tableBody.innerHTML = `
            <tr>
              <td colspan="11" class="error-message">
                <i class="fa-solid fa-exclamation-triangle"></i> Error: ${data.message}
              </td>
            </tr>
          `;
          return;
        }
        
        if (data.length === 0) {
          tableBody.innerHTML = `
            <tr>
              <td colspan="11" class="no-data-message">
                <i class="fa-solid fa-info-circle"></i> No students found. Click "Create New" to add a student.
              </td>
            </tr>
          `;
          studentCount.textContent = '0';
          return;
        }
        
        studentCount.textContent = data.length;
        
        const tableRows = data.map((student, index) => {
          // Safely escape data to prevent JavaScript injection
          const safeId = parseInt(student.id) || 0;
          const safeLrn = (student.lrn || '').replace(/'/g, '&#39;').replace(/"/g, '&quot;');
          const safeLastName = (student.last_name || '').replace(/'/g, '&#39;').replace(/"/g, '&quot;');
          const safeFirstName = (student.first_name || '').replace(/'/g, '&#39;').replace(/"/g, '&quot;');
          const safeMiddleInitial = (student.middle_initial || '').replace(/'/g, '&#39;').replace(/"/g, '&quot;');
          const safeGuardian = (student.guardian || '').replace(/'/g, '&#39;').replace(/"/g, '&quot;');
          const safeEmail = (student.email || '').replace(/'/g, '&#39;').replace(/"/g, '&quot;');
          const safeRfidTag = (student.rfid_tag || '').replace(/'/g, '&#39;').replace(/"/g, '&quot;');
          const safeSectionName = (student.section_name || '').replace(/'/g, '&#39;').replace(/"/g, '&quot;');
          
          return `
          <tr>
            <td>${index + 1}</td>
            <td>${safeLrn || '-'}</td>
            <td>${safeLastName}</td>
            <td>${safeFirstName}</td>
            <td>${safeMiddleInitial || '-'}</td>
            <td>${safeGuardian || '-'}</td>
            <td>${safeEmail || '-'}</td>
            <td><span class="rfid-tag">${safeRfidTag}</span></td>
            <td>${formatGradeLabel(student.grade_level)}</td>
            <td>${safeSectionName}</td>
            <td>
              <button class="btn btn-icon btn-edit" title="Edit" onclick="editStudent(${safeId})">
                <i class="fa-solid fa-pen-to-square"></i>
              </button>
            </td>
          </tr>
          `;
        }).join('');
        
        tableBody.innerHTML = tableRows;
      })
      .catch(error => {
        console.error('Error loading students:', error);
        tableBody.innerHTML = `
          <tr>
            <td colspan="11" class="error-message">
              <i class="fa-solid fa-exclamation-triangle"></i> Failed to load students. Please try again.
            </td>
          </tr>
        `;
      });
  }

  async function editStudent(studentId) {
    console.log('Edit student clicked:', studentId);
    
    // Validate student ID
    if (!studentId || studentId === undefined || studentId === null) {
      console.error('Invalid student ID:', studentId);
      alert('Error: Invalid student ID. Please refresh the page and try again.');
      return;
    }
    
    // Set edit mode
    isEditMode = true;
    editingStudentId = studentId;
    
    try {
      // Show loading state
      const editBtn = document.querySelector(`button[onclick="editStudent(${studentId})"]`);
      const originalHtml = editBtn.innerHTML;
      editBtn.disabled = true;
      editBtn.innerHTML = '<span class="spinner" style="display:inline-block;width:12px;height:12px;border:2px solid #fff;border-top-color:transparent;border-radius:50%;animation:spin 0.8s linear infinite"></span>';

      // Fetch student data
      const response = await fetch(`functions/get_student.php?student_id=${studentId}`);
      const data = await response.json();
      
      if (data.error) {
        throw new Error(data.message || 'Failed to fetch student data');
      }
      
      // Open modal and populate with student data
      await openModal();
      populateStudentForm(data.student);
      
      // Update modal title and button text for edit mode
      updateModalForEditMode();
      
      // Restore button state
      editBtn.disabled = false;
      editBtn.innerHTML = originalHtml;
      
    } catch (error) {
      console.error('Error editing student:', error);
      alert('Error: ' + error.message);
      
      // Reset edit mode on error
      isEditMode = false;
      editingStudentId = null;
    }
  }

  // Function to populate student form with existing data
  function populateStudentForm(student) {
    // Populate basic information
    const rfidField = document.getElementById('rfidTag');
    rfidField.value = student.rfid_tag || '';
    rfidField.dataset.originalRfid = student.rfid_tag || ''; // Store original RFID for reference
    
    document.getElementById('lrn').value = student.lrn || '';
    document.getElementById('lastName').value = student.last_name || '';
    document.getElementById('firstName').value = student.first_name || '';
    document.getElementById('middleInitial').value = student.middle_initial || '';
    document.getElementById('birthdate').value = student.birthdate || '';
    document.getElementById('gender').value = student.gender || '';
    document.getElementById('guardian').value = student.guardian || '';
    document.getElementById('email').value = student.email || '';
    document.getElementById('gradeLevel').value = student.grade_level || '';
    
    // Update section options based on grade level, then set section
    if (student.grade_level) {
      updateModalSectionOptions().then(() => {
        document.getElementById('section').value = student.section_name || '';
      });
    }
    
    // Handle photo if exists
    if (student.photo_path) {
      const preview = document.getElementById('photoPreview');
      const placeholder = document.getElementById('photoPlaceholder');
      const removeBtn = document.getElementById('btnRemovePhoto');
      
      preview.src = student.photo_path;
      preview.style.display = 'block';
      placeholder.style.display = 'none';
      removeBtn.style.display = 'inline-block';
    }
    
    // Update RFID status and field properties for edit mode
    const statusElement = document.getElementById('rfidStatus');
    
    if (statusElement) {
      if (student.rfid_tag) {
        statusElement.innerHTML = `📋 Current: ${student.rfid_tag} | 🔄 Scanning for new tag...`;
      } else {
        statusElement.innerHTML = '🔄 Scanning for new tag...';
      }
      statusElement.style.color = '#ffc107';
    }
    
    // In edit mode, make RFID field ready for new scan but show current value
    if (rfidField) {
      rfidField.readOnly = true; // Keep readonly so it gets updated by scanner
      rfidField.style.backgroundColor = '#f5f5f5'; // Same as create mode
      rfidField.style.color = '#666'; // Same as create mode  
      rfidField.title = 'Current RFID tag - will be updated when new tag is scanned';
    }
  }

  // Function to update modal for edit mode
  function updateModalForEditMode() {
    const modalTitle = document.querySelector('.modal-header h2');
    const saveBtn = document.querySelector('.btn-save');
    
    if (modalTitle) {
      modalTitle.textContent = 'Edit Student';
    }
    
    if (saveBtn) {
      saveBtn.textContent = 'Update Student';
    }
  }

  // Function to reset modal to create mode
  function resetModalToCreateMode() {
    const modalTitle = document.querySelector('.modal-header h2');
    const saveBtn = document.querySelector('.btn-save');
    const rfidField = document.getElementById('rfidTag');
    
    if (modalTitle) {
      modalTitle.textContent = 'Student Entry';
    }
    
    if (saveBtn) {
      saveBtn.textContent = 'Save';
    }
    
    // Reset RFID field styling for create mode
    if (rfidField) {
      rfidField.readOnly = true;
      rfidField.style.backgroundColor = '#f5f5f5';
      rfidField.style.color = '#666';
      rfidField.title = '';
    }
    
    // Reset edit mode variables
    isEditMode = false;
    editingStudentId = null;
  }
    
  function setupFilterListeners() {
    const gradeFilter = document.getElementById('filterGradeLevel');
    gradeFilter.addEventListener('change', function() {
      updateFilterSectionOptions();
    });
  }
    
  function updateFilterSectionOptions() {
    const gradeFilter = document.getElementById('filterGradeLevel');
    const sectionFilter = document.getElementById('filterSection');
    const selectedGrade = gradeFilter.value;
    
    sectionFilter.innerHTML = '<option value="">--Select Section--</option>';
    
    if (selectedGrade) {
      fetch(`functions/get_sections.php?grade=${selectedGrade}`)
        .then(response => {
          if (!response.ok) throw new Error('Network response was not ok');
          return response.json();
        })
        .then(data => {
          if (Array.isArray(data)) {
            data.forEach(section => {
              const option = document.createElement('option');
              option.value = section;
              option.textContent = section;
              sectionFilter.appendChild(option);
            });
          }
        })
        .catch(error => {
          console.error('Error fetching sections:', error);
          sectionFilter.innerHTML = '<option value="">Error loading sections</option>';
        });
    }
  }
    
  function applyFilters() {
    const gradeLevel = document.getElementById('filterGradeLevel').value;
    const section = document.getElementById('filterSection').value;
    loadStudents(gradeLevel, section);
  }
    
  function clearFilters() {
    document.getElementById('filterGradeLevel').value = '';
    document.getElementById('filterSection').value = '';
    loadStudents();
  }
    
  const video = document.getElementById('cameraStream');
  const canvas = document.getElementById('photoCanvas');
  const photoPreview = document.getElementById('photoPreview');
  const placeholder = document.getElementById('photoPlaceholder');
  const btnStartCamera = document.getElementById('btnStartCamera');
  const btnCapturePhoto = document.getElementById('btnCapturePhoto');
  const btnRemovePhoto = document.getElementById('btnRemovePhoto');
  let stream;

  async function getCameras() {
    try {
      // First request camera permission to get device labels
      await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
      
      // Now enumerate devices (should have labels after permission granted)
      const devices = await navigator.mediaDevices.enumerateDevices();
      const videoDevices = devices.filter(device => device.kind === "videoinput");
      
      console.log('Available cameras:', videoDevices.map(d => ({ id: d.deviceId, label: d.label })));
      return videoDevices;
    } catch (error) {
      console.warn('Error getting camera permission for enumeration:', error);
      // Fallback: try to enumerate without labels
      try {
        const devices = await navigator.mediaDevices.enumerateDevices();
        return devices.filter(device => device.kind === "videoinput");
      } catch (fallbackError) {
        console.error('Failed to enumerate devices:', fallbackError);
        return [];
      }
    }
  }

  btnStartCamera.onclick = async () => {
    try {
      // Show loading state
      btnStartCamera.textContent = 'Opening Camera...';
      btnStartCamera.disabled = true;
      
      const cameras = await getCameras();
      if (cameras.length === 0) {
        alert("No camera found. Please ensure your camera is connected and you have granted camera permissions.");
        return;
      }
      
      console.log(`Found ${cameras.length} camera(s)`);
      
      // Try different approaches to access camera
      let cameraStream = null;
      let attempts = [];
      
      // Approach 1: Auto-select icspring camera if available
      const icspringCamera = cameras.find(camera => 
        camera.label && camera.label.toLowerCase().includes('icspring')
      );
      
      if (icspringCamera) {
        attempts.push({
          name: `icspring camera (${icspringCamera.label})`,
          constraints: { video: { deviceId: { exact: icspringCamera.deviceId } }, audio: false }
        });
        console.log('Found icspring camera, using it automatically:', icspringCamera.label);
      }
      
      // Approach 2: Try other cameras if icspring is not available or fails
      cameras.forEach((camera, index) => {
        // Skip icspring camera since we already added it first
        if (camera === icspringCamera) return;
        
        attempts.push({
          name: `Camera ${index + 1} (${camera.label || 'Unknown camera'})`,
          constraints: { video: { deviceId: { exact: camera.deviceId } }, audio: false }
        });
      });
      
      // Approach 3: Try ideal constraints (let browser choose best camera)
      attempts.push({
        name: 'Browser default camera',
        constraints: { video: { width: { ideal: 640 }, height: { ideal: 480 } }, audio: false }
      });
      
      // Approach 4: Basic video constraints
      attempts.push({
        name: 'Basic video',
        constraints: { video: true, audio: false }
      });
      
      // Try each approach until one works
      for (let i = 0; i < attempts.length; i++) {
        try {
          console.log(`Trying approach ${i + 1}: ${attempts[i].name}`);
          cameraStream = await navigator.mediaDevices.getUserMedia(attempts[i].constraints);
          console.log(`Success with approach ${i + 1}: ${attempts[i].name}`);
          break;
        } catch (error) {
          console.warn(`Approach ${i + 1} failed:`, error.message);
          if (i === attempts.length - 1) {
            // All approaches failed
            throw new Error(`Failed to access camera after ${attempts.length} attempts. Last error: ${error.message}`);
          }
        }
      }
      
      if (cameraStream) {
        stream = cameraStream;
        video.srcObject = stream;
        video.style.display = 'block';
        placeholder.style.display = 'none';
        btnCapturePhoto.style.display = 'inline-block';
        btnStartCamera.style.display = 'none';
        
        // Log camera info
        const videoTrack = stream.getVideoTracks()[0];
        if (videoTrack) {
          const settings = videoTrack.getSettings();
          console.log('Camera settings:', settings);
        }
      } else {
        throw new Error('No camera stream obtained');
      }
      
    } catch (err) {
      console.error('Camera access error:', err);
      
      // Provide more helpful error messages
      let userMessage = "Could not access camera. ";
      
      if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
        userMessage += "Please allow camera access in your browser and try again.";
      } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
        userMessage += "No camera detected. Please ensure your camera is connected.";
      } else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
        userMessage += "Camera is already in use by another application. Please close other camera applications and try again.";
      } else if (err.name === 'OverconstrainedError' || err.name === 'ConstraintNotSatisfiedError') {
        userMessage += "Camera does not support the required settings. Trying a basic approach...";
        
        // Last resort: try with minimal constraints
        try {
          stream = await navigator.mediaDevices.getUserMedia({ video: { width: 320, height: 240 }, audio: false });
          video.srcObject = stream;
          video.style.display = 'block';
          placeholder.style.display = 'none';
          btnCapturePhoto.style.display = 'inline-block';
          btnStartCamera.style.display = 'none';
          return; // Success with minimal constraints
        } catch (lastError) {
          userMessage += " Failed even with basic settings.";
        }
      } else {
        userMessage += `Error: ${err.message}`;
      }
      
      alert(userMessage);
    } finally {
      // Reset button state
      btnStartCamera.textContent = 'Open Camera';
      btnStartCamera.disabled = false;
    }
  };

  btnCapturePhoto.onclick = () => {
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const context = canvas.getContext('2d');
    context.drawImage(video, 0, 0, canvas.width, canvas.height);
    const dataURL = canvas.toDataURL('image/png');
    photoPreview.src = dataURL;
    photoPreview.style.display = 'block';
    stream.getTracks().forEach(track => track.stop());
    video.style.display = 'none';
    btnCapturePhoto.style.display = 'none';
    btnRemovePhoto.style.display = 'inline-block';
  };

  btnRemovePhoto.onclick = () => {
    resetPhoto();
  };

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
      if (e.key === 'Escape' && !isScriptLoading()) {
        hideConfirmationDialog();
        hideSuccessModal();
      }
    });
    
    // Close success modal button
    const closeSuccessBtn = document.getElementById('closeSuccessBtn');
    if (closeSuccessBtn) {
      closeSuccessBtn.addEventListener('click', hideSuccessModal);
    }
    
    // Close success modal when clicking outside
    const successModal = document.getElementById('successModal');
    if (successModal) {
      successModal.addEventListener('click', function(e) {
        if (e.target === successModal) {
          hideSuccessModal();
        }
      });
    }
  });

  // Function to show success message
  function showSuccessMessage(message) {
    const modal = document.getElementById('successModal');
    const messageElement = document.getElementById('successMessage');
    
    if (messageElement) {
      messageElement.textContent = message;
    }
    
    if (modal) {
      modal.style.display = 'flex';
      document.body.style.overflow = 'hidden';
    }
  }

  // Function to hide success modal
  function hideSuccessModal() {
    const modal = document.getElementById('successModal');
    if (modal) {
      modal.style.display = 'none';
      document.body.style.overflow = 'auto';
    }
  }

  // Function to show error message
  function showErrorMessage(message) {
    // You can implement a toast notification or use alert for now
    alert('Error: ' + message);
  }
</script>

</body>
</html>
