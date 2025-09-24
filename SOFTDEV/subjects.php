<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

include 'includes/head.php';
?>
<link rel="stylesheet" href="css/subjects.css">
<link rel="stylesheet" href="css/subjects-inline.css">

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
            <h1 class="page-title">Manage Subjects</h1>
            
            <div class="content-body">
                <!-- Filters and Action Buttons -->
                <div class="filter-card">
                    <div class="filter-header">
                        <h3>Filter Sections</h3>
                        <button type="button" id="manageSubjectsBtn" class="btn btn-info">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                            Manage All Subjects
                        </button>
                    </div>
                    <div class="filter-content">
                        <div class="form-group">
                            <label for="grade_level">Grade Level</label>
                            <select id="grade_level" name="grade_level">
                                <option value="">All Grade Levels</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="section">Section</label>
                            <select id="section" name="section">
                                <option value="">All Sections</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Sections Grid -->
                <div class="sections-grid" id="sectionsGrid">
                    <!-- Sections will be loaded here via JavaScript -->
                </div>
            </div>
        </main>
    </div>


    <!-- Edit Subjects Modal -->
    <div id="editSubjectsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Manage All Subjects</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div id="subjectsList">
                    <!-- Subjects will be loaded here -->
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Subjects Modal -->
    <div id="sectionSubjectsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="sectionSubjectsTitle">Section Subjects</h2>
                <span class="close" onclick="closeSectionSubjectsModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div id="sectionSubjectsList">
                    <!-- Subjects will be loaded here -->
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-success" onclick="openAddSubjectModalFromSection()">Add Subject</button>
                    <button type="button" class="btn btn-info" onclick="openEditSubjectsModalFromSection()">Edit Subjects</button>
                    <button type="button" class="btn btn-secondary" onclick="closeSectionSubjectsModal()">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Subject Modal -->
    <div id="addSubjectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add Subject to Section</h2>
                <span class="close" onclick="closeAddSubjectModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="addSubjectForm">
                    <div class="form-group">
                        <label for="addGradeLevelSelect">Grade Level</label>
                        <select id="addGradeLevelSelect" name="grade_level" required disabled>
                            <option value="">Select Grade Level</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="addSectionSelect">Section</label>
                        <select id="addSectionSelect" name="section_id" required disabled>
                            <option value="">Select Section</option>
                        </select>
                    </div>
                    
                    <!-- Subjects Container -->
                    <div id="subjectsContainer">
                        <div class="subject-group" data-subject-index="0">
                            <div class="subject-group-header">
                                <h4>Subject 1</h4>
                                <button type="button" class="btn btn-danger btn-sm remove-subject" onclick="removeSubjectGroup(this)">Remove</button>
                            </div>
                            <div class="form-group">
                                <label for="addSubjectName_0">Subject Name <span class="required-indicator">*</span></label>
                                <input type="text" id="addSubjectName_0" name="subjects[0][subject_name]" required>
                            </div>
                            <div class="form-group">
                                <label for="addSubjectCode_0">Subject Code <span class="required-indicator">*</span></label>
                                <input type="text" id="addSubjectCode_0" name="subjects[0][subject_code]" placeholder="Auto generated (e.g., G1MMATH)" required>
                            </div>
                            <div class="form-group">
                                <label for="addSubjectDescription_0">Description (Optional)</label>
                                <textarea id="addSubjectDescription_0" name="subjects[0][subject_description]" rows="3" placeholder="Brief description of the subject"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="addTeacherSelect_0">Teacher</label>
                                <select id="addTeacherSelect_0" name="subjects[0][teacher_id]">
                                    <option value="">Select Teacher</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-info" id="addMoreBtn" onclick="addMoreSubject()">Add More Subject</button>
                        <button type="button" class="btn btn-secondary" onclick="closeAddSubjectModal()">Cancel</button>
                        <button type="submit" class="btn btn-success">Add All Subjects</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Subject Modal -->
    <div id="editSubjectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Subject</h2>
                <span class="close" onclick="closeEditSubjectModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editSubjectForm">
                    <input type="hidden" id="editSubjectId" name="subject_id">
                    <div class="form-group">
                        <label for="editGradeLevelSelect">Grade Level</label>
                        <select id="editGradeLevelSelect" name="grade_level" required disabled>
                            <option value="">Select Grade Level</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editSectionSelect">Section</label>
                        <select id="editSectionSelect" name="section_id" required disabled>
                            <option value="">Select Section</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editSubjectName">Subject Name <span class="required-indicator">*</span></label>
                        <input type="text" id="editSubjectName" name="subject_name" required>
                    </div>
                    <div class="form-group">
                        <label for="editSubjectCode">Subject Code <span class="required-indicator">*</span></label>
                        <input type="text" id="editSubjectCode" name="subject_code" placeholder="e.g., MATH101" required>
                    </div>
                    <div class="form-group">
                        <label for="editSubjectDescription">Description (Optional)</label>
                        <textarea id="editSubjectDescription" name="subject_description" rows="3" placeholder="Brief description of the subject"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="editTeacherSelect">Teacher</label>
                        <select id="editTeacherSelect" name="teacher_id">
                            <option value="">Select Teacher</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeEditSubjectModal()">Cancel</button>
                        <button type="submit" class="btn btn-success">Update Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Subject Confirmation Modal -->
    <div id="deleteSubjectModal" class="modal">
        <div class="modal-content simple-delete-modal">
            <div class="modal-header">
                <span class="close" onclick="closeDeleteSubjectModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="delete-warning">
                    <div class="warning-icon">
                        <div class="warning-circle">×</div>
                    </div>
                    <h3>Are you sure?</h3>
                    <p>Do you really want to delete this subject? This process cannot be undone.</p>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-cancel" onclick="closeDeleteSubjectModal()">Cancel</button>
                    <button type="button" class="btn btn-delete" onclick="confirmDeleteSubject()">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <div class="toast-content">
            <div class="toast-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 12l2 2 4-4"/>
                    <circle cx="12" cy="12" r="10"/>
                </svg>
            </div>
            <div class="toast-message"></div>
        </div>
    </div>

    <!-- Non-Curriculum Subject Confirmation Modal -->
    <div id="nonCurriculumModal" class="modal">
        <div class="modal-content simple-delete-modal">
            <div class="modal-header">
                <span class="close" onclick="closeNonCurriculumModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="delete-warning">
                    <div class="warning-icon">
                        <div class="warning-circle">?</div>
                    </div>
                    <h3>Subject not in curriculum</h3>
                    <p>The subject is not in the curriculum. Do you still want to add the subject?</p>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-cancel" onclick="closeNonCurriculumModal()">Cancel</button>
                    <button type="button" class="btn btn-delete" onclick="confirmNonCurriculumAdd()">Add Subject</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Delete Subjects Confirmation Modal -->
    <div id="bulkDeleteModal" class="modal">
        <div class="modal-content simple-delete-modal">
            <div class="modal-header">
                <span class="close" onclick="closeBulkDeleteModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="delete-warning">
                    <div class="warning-icon">
                        <div class="warning-circle">×</div>
                    </div>
                    <h3>Delete selected subjects?</h3>
                    <p id="bulkDeleteCountMsg">You are about to delete selected subjects. This cannot be undone.</p>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-cancel" onclick="closeBulkDeleteModal()">Cancel</button>
                    <button type="button" class="btn btn-delete" onclick="confirmBulkDelete()">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentSectionId = null;
        let sections = [];

        // Load sections with advisers
        function loadSections() {
            fetch('functions/get_sections_with_advisers.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        sections = data.sections;
                        renderSections();
                        populateFilterDropdowns();
                    } else {
                        console.error('Error loading sections:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }



        // Populate filter dropdowns
        function populateFilterDropdowns() {
            // Populate grade level filter
            const gradeFilter = document.getElementById('grade_level');
            gradeFilter.innerHTML = '<option value="">All Grade Levels</option>';
            
            const gradeLevels = [...new Set(sections.map(section => section.grade_level))];
            gradeLevels.sort((a, b) => {
                const order = ['Nursery', 'Kinder 1', 'Kinder 2'];
                const aIndex = order.indexOf(a);
                const bIndex = order.indexOf(b);
                
                if (aIndex !== -1 && bIndex !== -1) return aIndex - bIndex;
                if (aIndex !== -1) return -1;
                if (bIndex !== -1) return 1;
                
                return parseInt(a) - parseInt(b);
            });
            
            gradeLevels.forEach(grade => {
                const option = document.createElement('option');
                option.value = grade;
                // Add "Grade" prefix for numerical grade levels
                const displayText = /^\d+$/.test(grade) ? `Grade ${grade}` : grade;
                option.textContent = displayText;
                gradeFilter.appendChild(option);
            });

            // Populate section filter with all sections initially
            populateSectionFilter();
        }

        // Populate section filter based on selected grade level
        function populateSectionFilter() {
            const sectionFilter = document.getElementById('section');
            const selectedGrade = document.getElementById('grade_level').value;
            
            sectionFilter.innerHTML = '<option value="">All Sections</option>';
            
            let sectionsToShow = sections;
            if (selectedGrade) {
                sectionsToShow = sections.filter(section => section.grade_level === selectedGrade);
            }
            
            sectionsToShow.forEach(section => {
                const option = document.createElement('option');
                option.value = section.id;
                option.textContent = section.section;
                sectionFilter.appendChild(option);
            });
        }

        // Filter sections based on selected filters
        function filterSections() {
            const selectedGrade = document.getElementById('grade_level').value;
            const selectedSection = document.getElementById('section').value;
            
            let filteredSections = sections;
            
            if (selectedGrade) {
                filteredSections = filteredSections.filter(section => section.grade_level === selectedGrade);
            }
            
            if (selectedSection) {
                // Check if the selected section is still valid with the current grade level filter
                const sectionExists = filteredSections.some(section => section.id == selectedSection);
                if (sectionExists) {
                    filteredSections = filteredSections.filter(section => section.id == selectedSection);
                } else {
                    // If section is not valid with current grade level, clear section selection
                    document.getElementById('section').value = '';
                }
            }
            
            renderFilteredSections(filteredSections);
        }

        // Render filtered sections
        function renderFilteredSections(filteredSections) {
            const grid = document.getElementById('sectionsGrid');
            grid.innerHTML = '';

            if (filteredSections.length === 0) {
                grid.innerHTML = `
                    <div class="no-sections">
                        <div class="no-sections-icon">📚</div>
                        <h3 class="no-sections-title">No subjects found</h3>
                        <p class="no-sections-message">Try adjusting your filters or add a new section.</p>
                    </div>
                `;
                return;
            }

            filteredSections.forEach(section => {
                const card = document.createElement('div');
                card.className = 'section-card clickable-section';
                card.onclick = () => openSectionSubjectsModal(section.id, section.grade_level, section.section, section.adviser_name);
                
                // Add "Grade" prefix for numerical grade levels
                const gradeDisplay = /^\d+$/.test(section.grade_level) ? `Grade ${section.grade_level}` : section.grade_level;
                
                card.innerHTML = `
                    <div class="section-title">${gradeDisplay} - ${section.section}</div>
                    <div class="adviser-label">Adviser</div>
                    <div class="adviser-name">${section.adviser_name}</div>
                    <div class="subjects-label">Click to view subjects</div>
                    <div class="subject-actions">
                        <button class="btn btn-success btn-small" onclick="event.stopPropagation(); openAddSubjectModal(${section.id}, '${section.grade_level}', '${section.section}', '${section.adviser_name}', ${section.adviser_id || 'null'})">Add Subject</button>
                        <button class="btn btn-info btn-small" onclick="event.stopPropagation(); openEditSubjectsModal(${section.id})">Edit Subjects</button>
                    </div>
                `;
                grid.appendChild(card);
            });
        }

        // Render sections grid
        function renderSections() {
            renderFilteredSections(sections);
        }





        // Open section subjects modal
        function openSectionSubjectsModal(sectionId, gradeLevel, sectionName, adviserName) {
            currentSectionId = sectionId;
            document.getElementById('sectionSubjectsTitle').textContent = `${sectionName} Subjects`;
            loadSectionSubjects(sectionId);
            document.getElementById('sectionSubjectsModal').classList.add('show');
        }

        // Open edit subjects modal
        function openEditSubjectsModal(sectionId) {
            // Clear any existing bulk selection when opening modal
            clearBulkSelection();
            clearSectionBulkSelection();
            
            currentSectionId = sectionId;
            loadSectionSubjects(sectionId);
            document.getElementById('editSubjectsModal').classList.add('show');
        }

        // Load subjects for a section
        function loadSectionSubjects(sectionId) {
            fetch(`functions/get_subjects.php?section_id=${sectionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderSubjectsList(data.subjects);
                        renderSectionSubjectsList(data.subjects);
                    } else {
                        console.error('Error loading subjects:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        // Render subjects list in edit modal
        function renderSubjectsList(subjects) {
            const container = document.getElementById('subjectsList');
            container.innerHTML = '';

            if (subjects.length === 0) {
                container.innerHTML = '<p>No subjects assigned to this section.</p>';
                return;
            }

            // Add bulk actions toolbar for section subjects
            const toolbar = document.createElement('div');
            toolbar.id = 'sectionBulkActionsBar';
            toolbar.className = 'bulk-actions-toolbar';
            toolbar.innerHTML = `
                <div class="bulk-selected-count" id="sectionBulkSelectedCount">0 selected</div>
                <div class="bulk-actions-buttons">
                    <button class="btn btn-danger" onclick="openSectionBulkDeleteModal()">Delete Selected</button>
                    <button class="btn btn-secondary" onclick="clearSectionBulkSelection()">Clear</button>
                </div>
            `;
            container.appendChild(toolbar);

            subjects.forEach(subject => {
                const item = document.createElement('div');
                item.className = 'subject-item';
                item.innerHTML = `
                    <div class="subject-info subject-info-flex">
                        <input type="checkbox" data-section-bulk-subject value="${subject.id}" onchange="toggleSectionBulkSubjectSelection(this, ${subject.id})">
                        <div class="subject-details">
                            <div class="subject-name">${subject.subject_name}</div>
                            <div class="teacher-name">Teacher: ${subject.teacher_name}</div>
                        </div>
                    </div>
                    <div class="subject-actions-modal">
                        <button class="btn btn-small btn-secondary" onclick="editSubject(${subject.id})">Edit</button>
                        <button class="btn btn-small btn-danger" onclick="deleteSubject(${subject.id})">Delete</button>
                    </div>
                `;
                container.appendChild(item);
            });
            
            updateSectionBulkActionsToolbar();
        }

        // Render subjects list in section subjects modal
        function renderSectionSubjectsList(subjects) {
            const container = document.getElementById('sectionSubjectsList');
            container.innerHTML = '';

            if (subjects.length === 0) {
                container.innerHTML = '<p class="section-subjects-empty">No subjects assigned to this section.</p>';
                return;
            }

            subjects.forEach(subject => {
                const item = document.createElement('div');
                item.className = 'subject-item section-subject-item';
                item.innerHTML = `
                    <div class="subject-info">
                        <div class="subject-name">${subject.subject_name}</div>
                        <div class="subject-code">${subject.subject_code || 'No code'}</div>
                        <div class="teacher-name">Teacher: ${subject.teacher_name}</div>
                        ${subject.subject_description ? `<div class="subject-description">${subject.subject_description}</div>` : ''}
                    </div>
                `;
                container.appendChild(item);
            });
        }

        // Edit subject
        function editSubject(subjectId) {
            if (!subjectId) {
                showToast('Missing subject ID for edit', 'error');
                return;
            }
            loadSubjectForEdit(subjectId);
        }

        // Load subject data for editing
        function loadSubjectForEdit(subjectId) {
            fetch(`functions/get_subject.php?id=${subjectId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        populateEditModal(data.subject);
                        document.getElementById('editSubjectModal').classList.add('show');
                    } else {
                        alert('Error loading subject: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while loading the subject');
                });
        }

        // Populate edit modal with subject data
        function populateEditModal(subject) {
            // Populate dropdowns
            populateEditGradeLevelDropdown();
            populateEditSectionDropdown();
            populateEditTeachersDropdown();

            // Set form values
            document.getElementById('editSubjectId').value = subject.id;
            document.getElementById('editSubjectName').value = subject.subject_name;
            document.getElementById('editSubjectCode').value = subject.subject_code || '';
            document.getElementById('editSubjectDescription').value = subject.subject_description || '';
            
            // Set grade level and section
            document.getElementById('editGradeLevelSelect').value = subject.grade_level;
            populateEditSectionDropdown(subject.grade_level);
            
            // Wait a bit for section dropdown to populate, then set section
            setTimeout(() => {
                document.getElementById('editSectionSelect').value = subject.section_id;
                populateEditTeachersDropdown(subject.grade_level, subject.teacher_id);
            }, 100);
        }

        // Populate grade level dropdown for edit modal
        function populateEditGradeLevelDropdown() {
            const gradeSelect = document.getElementById('editGradeLevelSelect');
            gradeSelect.innerHTML = '<option value="">Select Grade Level</option>';
            
            const gradeLevels = [...new Set(sections.map(section => section.grade_level))];
            gradeLevels.sort((a, b) => {
                const order = ['Nursery', 'Kinder 1', 'Kinder 2'];
                const aIndex = order.indexOf(a);
                const bIndex = order.indexOf(b);
                
                if (aIndex !== -1 && bIndex !== -1) return aIndex - bIndex;
                if (aIndex !== -1) return -1;
                if (bIndex !== -1) return 1;
                
                return parseInt(a) - parseInt(b);
            });
            
            gradeLevels.forEach(grade => {
                const option = document.createElement('option');
                option.value = grade;
                const displayText = /^\d+$/.test(grade) ? `Grade ${grade}` : grade;
                option.textContent = displayText;
                gradeSelect.appendChild(option);
            });
        }

        // Populate section dropdown for edit modal
        function populateEditSectionDropdown(selectedGradeLevel = '') {
            const sectionSelect = document.getElementById('editSectionSelect');
            sectionSelect.innerHTML = '<option value="">Select Section</option>';
            
            let filteredSections = sections;
            if (selectedGradeLevel) {
                filteredSections = sections.filter(section => section.grade_level === selectedGradeLevel);
            }
            
            filteredSections.forEach(section => {
                const option = document.createElement('option');
                option.value = section.id;
                option.textContent = section.section;
                sectionSelect.appendChild(option);
            });
        }

        // Populate teachers dropdown for edit modal
        function populateEditTeachersDropdown(gradeLevel = '', preselectedTeacherId = null) {
            const teacherSelect = document.getElementById('editTeacherSelect');
            teacherSelect.innerHTML = '<option value="">Select Teacher</option>';
            
            let url = 'functions/get_teachers.php';
            if (gradeLevel) {
                url += `?grade_level=${encodeURIComponent(gradeLevel)}`;
            }
            
            fetch(url)
                .then(response => response.json())
                .then(teachers => {
                    teachers.forEach(teacher => {
                        const option = document.createElement('option');
                        option.value = teacher.id;
                        option.textContent = `${teacher.first_name} ${teacher.last_name}`;
                        if (preselectedTeacherId && teacher.id == preselectedTeacherId) {
                            option.selected = true;
                        }
                        teacherSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error loading teachers:', error);
                });
        }

        // Close edit subject modal
        function closeEditSubjectModal() {
            const modal = document.getElementById('editSubjectModal');
            if (modal) {
                modal.classList.remove('show');
                document.getElementById('editSubjectForm').reset();
            }
        }

        // Toast Notification Functions
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMessage = toast.querySelector('.toast-message');
            const toastIcon = toast.querySelector('.toast-icon svg');
            
            // Set message
            toastMessage.textContent = message;
            
            // Remove existing type classes
            toast.classList.remove('success', 'error', 'warning', 'info');
            
            // Add new type class
            toast.classList.add(type);
            
            // Update icon based on type
            let iconPath = '';
            switch(type) {
                case 'success':
                    iconPath = '<path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/>';
                    break;
                case 'error':
                    iconPath = '<path d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>';
                    break;
                case 'warning':
                    iconPath = '<path d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>';
                    break;
                case 'info':
                    iconPath = '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>';
                    break;
            }
            toastIcon.innerHTML = iconPath;
            
            // Show toast
            toast.classList.add('show');
            
            // Auto hide after 3 seconds
            setTimeout(() => {
                hideToast();
            }, 3000);
        }
        
        function hideToast() {
            const toast = document.getElementById('toast');
            toast.classList.remove('show');
            toast.classList.add('hide');
            
            // Remove hide class after animation
            setTimeout(() => {
                toast.classList.remove('hide');
            }, 300);
        }

        // Delete Subject Modal Functions
        let subjectToDelete = null;

        function openDeleteSubjectModal(subjectId) {
            subjectToDelete = subjectId;
            document.getElementById('deleteSubjectModal').classList.add('show');
        }

        function closeDeleteSubjectModal() {
            const modal = document.getElementById('deleteSubjectModal');
            if (modal) {
                modal.classList.remove('show');
                subjectToDelete = null;
            }
        }

        function confirmDeleteSubject() {
            if (!subjectToDelete) return;

            const formData = new FormData();
            formData.append('subject_id', subjectToDelete);

            fetch('functions/delete_subject.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeDeleteSubjectModal();
                    
                    // Check if we're in the "Manage All Subjects" modal
                    const editModal = document.getElementById('editSubjectsModal');
                    if (editModal && editModal.classList.contains('show')) {
                        // Refresh the "Manage All Subjects" modal
                        showAllSubjectsModal();
                    } else if (currentSectionId) {
                        // Refresh the section subjects modal
                        loadSectionSubjects(currentSectionId);
                    }
                    
                    // Show success toast
                    showToast(data.message, 'success');
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred while deleting the subject', 'error');
            });
        }

        // Non-Curriculum Confirmation Flow
        let pendingAddFormSubmit = null;
        function openNonCurriculumModal(onConfirm) {
            pendingAddFormSubmit = onConfirm || null;
            document.getElementById('nonCurriculumModal').classList.add('show');
        }
        function closeNonCurriculumModal() {
            const modal = document.getElementById('nonCurriculumModal');
            if (modal) modal.classList.remove('show');
        }
        function confirmNonCurriculumAdd() {
            const cb = pendingAddFormSubmit;
            closeNonCurriculumModal();
            if (typeof cb === 'function') cb();
            pendingAddFormSubmit = null;
        }

        // Bulk delete selection management for "Manage All Subjects"
        let bulkSelectedSubjectIds = new Set();
        function toggleBulkSubjectSelection(checkbox, subjectId) {
            if (checkbox.checked) bulkSelectedSubjectIds.add(subjectId);
            else bulkSelectedSubjectIds.delete(subjectId);
            updateBulkActionsToolbar();
        }
        function clearBulkSelection() {
            bulkSelectedSubjectIds.clear();
            document.querySelectorAll('#subjectsList input[type="checkbox"][data-bulk-subject]')
                .forEach(cb => cb.checked = false);
            updateBulkActionsToolbar();
        }
        function updateBulkActionsToolbar() {
            let toolbar = document.getElementById('bulkActionsBar');
            if (!toolbar) return;
            const count = bulkSelectedSubjectIds.size;
            toolbar.style.display = count > 0 ? 'flex' : 'none';
            const countEl = document.getElementById('bulkSelectedCount');
            if (countEl) countEl.textContent = `${count} selected`;
        }

        // Bulk delete selection management for "Edit Subjects" modal (section-specific)
        let sectionBulkSelectedSubjectIds = new Set();
        function toggleSectionBulkSubjectSelection(checkbox, subjectId) {
            if (checkbox.checked) sectionBulkSelectedSubjectIds.add(subjectId);
            else sectionBulkSelectedSubjectIds.delete(subjectId);
            updateSectionBulkActionsToolbar();
        }
        function clearSectionBulkSelection() {
            sectionBulkSelectedSubjectIds.clear();
            document.querySelectorAll('#subjectsList input[type="checkbox"][data-section-bulk-subject]')
                .forEach(cb => cb.checked = false);
            updateSectionBulkActionsToolbar();
        }
        function updateSectionBulkActionsToolbar() {
            let toolbar = document.getElementById('sectionBulkActionsBar');
            if (!toolbar) return;
            const count = sectionBulkSelectedSubjectIds.size;
            toolbar.style.display = count > 0 ? 'flex' : 'none';
            const countEl = document.getElementById('sectionBulkSelectedCount');
            if (countEl) countEl.textContent = `${count} selected`;
        }
        function openSectionBulkDeleteModal() {
            const count = sectionBulkSelectedSubjectIds.size;
            if (count === 0) return;
            const msg = document.getElementById('bulkDeleteCountMsg');
            if (msg) msg.textContent = `You are about to delete ${count} subject${count>1?'s':''}. This cannot be undone.`;
            document.getElementById('bulkDeleteModal').classList.add('show');
        }
        function openBulkDeleteModal() {
            const count = bulkSelectedSubjectIds.size;
            if (count === 0) return;
            const msg = document.getElementById('bulkDeleteCountMsg');
            if (msg) msg.textContent = `You are about to delete ${count} subject${count>1?'s':''}. This cannot be undone.`;
            document.getElementById('bulkDeleteModal').classList.add('show');
        }
        function closeBulkDeleteModal() {
            const modal = document.getElementById('bulkDeleteModal');
            if (modal) modal.classList.remove('show');
        }
        function confirmBulkDelete() {
            // Check which bulk selection is active
            const isSectionBulk = sectionBulkSelectedSubjectIds.size > 0;
            const ids = Array.from(isSectionBulk ? sectionBulkSelectedSubjectIds : bulkSelectedSubjectIds);
            
            if (ids.length === 0) { closeBulkDeleteModal(); return; }
            
            // Delete sequentially to keep backend simple
            let successCount = 0;
            let failCount = 0;
            const deleteNext = (index) => {
                if (index >= ids.length) {
                    closeBulkDeleteModal();
                    showToast(`Deleted ${successCount}/${ids.length} subject${ids.length>1?'s':''}`,
                              failCount ? 'warning' : 'success');
                    
                    // Clear the appropriate selection
                    if (isSectionBulk) {
                        clearSectionBulkSelection();
                    } else {
                        clearBulkSelection();
                    }
                    
                    // Refresh the appropriate modal
                    const editModal = document.getElementById('editSubjectsModal');
                    if (editModal && editModal.classList.contains('show')) {
                        if (isSectionBulk && currentSectionId) {
                            // Refresh section subjects
                            loadSectionSubjects(currentSectionId);
                        } else {
                            // Refresh all subjects
                            showAllSubjectsModal();
                        }
                    }
                    return;
                }
                const formData = new FormData();
                formData.append('subject_id', ids[index]);
                fetch('functions/delete_subject.php', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => {
                        if (data && data.success) successCount++; else failCount++;
                        deleteNext(index + 1);
                    })
                    .catch(() => { failCount++; deleteNext(index + 1); });
            };
            deleteNext(0);
        }

        // Handle edit subject form submission
        function handleEditSubjectForm(event) {
            event.preventDefault();
            
            // Get values directly from DOM elements since disabled fields are not included in FormData
            const subjectId = document.getElementById('editSubjectId').value;
            const gradeLevel = document.getElementById('editGradeLevelSelect').value;
            const sectionId = document.getElementById('editSectionSelect').value;
            
            if (!gradeLevel) {
                alert('Please select a grade level');
                return;
            }
            
            if (!sectionId) {
                alert('Please select a section');
                return;
            }
            
            // Create FormData and manually add the disabled field values
            const formData = new FormData(event.target);
            formData.set('subject_id', subjectId);
            formData.set('section_id', sectionId);
            formData.set('grade_level', gradeLevel);
            
            fetch('functions/update_subject.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    closeEditSubjectModal();
                    
                    // Check if we're in the "Manage All Subjects" modal
                    const editModal = document.getElementById('editSubjectsModal');
                    if (editModal && editModal.classList.contains('show')) {
                        // Refresh the "Manage All Subjects" modal
                        showAllSubjectsModal();
                    } else if (currentSectionId) {
                        // Refresh the section subjects modal
                        loadSectionSubjects(currentSectionId);
                    }
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred while updating the subject', 'error');
            });
        }

        // Delete subject
        function deleteSubject(subjectId) {
            openDeleteSubjectModal(subjectId);
        }

        // Show all subjects modal
        function showAllSubjectsModal() {
            // Clear any existing bulk selection when opening modal
            clearBulkSelection();
            clearSectionBulkSelection();
            
            fetch('functions/get_subjects.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderAllSubjects(data.subjects);
                        document.getElementById('editSubjectsModal').classList.add('show');
                    } else {
                        console.error('Error loading subjects:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        // Render all subjects in edit modal
        function renderAllSubjects(subjects) {
            const container = document.getElementById('subjectsList');
            container.innerHTML = '';

            if (subjects.length === 0) {
                container.innerHTML = '<p>No subjects found.</p>';
                return;
            }

            // Bulk actions toolbar
            const toolbar = document.createElement('div');
            toolbar.id = 'bulkActionsBar';
            toolbar.className = 'bulk-actions-toolbar';
            toolbar.innerHTML = `
                <div class="bulk-selected-count" id="bulkSelectedCount">0 selected</div>
                <div class="bulk-actions-buttons">
                    <button class="btn btn-danger" onclick="openBulkDeleteModal()">Delete Selected</button>
                    <button class="btn btn-secondary" onclick="clearBulkSelection()">Clear</button>
                </div>
            `;
            container.appendChild(toolbar);

            // Group subjects by section
            const groupedSubjects = {};
            subjects.forEach(subject => {
                // Add "Grade" prefix for numerical grade levels
                const gradeDisplay = /^\d+$/.test(subject.section_grade) ? `Grade ${subject.section_grade}` : subject.section_grade;
                const key = `${gradeDisplay} - ${subject.section_name}`;
                if (!groupedSubjects[key]) {
                    groupedSubjects[key] = [];
                }
                groupedSubjects[key].push(subject);
            });

            // Render grouped subjects
            Object.keys(groupedSubjects).forEach(sectionName => {
                const sectionDiv = document.createElement('div');
                sectionDiv.innerHTML = `<h4 class="section-group-header">${sectionName}</h4>`;
                container.appendChild(sectionDiv);

                groupedSubjects[sectionName].forEach(subject => {
                    const item = document.createElement('div');
                    item.className = 'subject-item';
                    item.innerHTML = `
                        <div class="subject-info subject-info-flex">
                            <input type="checkbox" data-bulk-subject value="${subject.id}" onchange="toggleBulkSubjectSelection(this, ${subject.id})">
                            <div class="subject-details">
                                <div class="subject-name">${subject.subject_name}</div>
                                <div class="teacher-name">Teacher: ${subject.teacher_name}</div>
                            </div>
                        </div>
                        <div class="subject-actions-modal">
                            <button class="btn btn-small btn-secondary" onclick="editSubject(${subject.id})">Edit</button>
                            <button class="btn btn-small btn-danger" onclick="deleteSubject(${subject.id})">Delete</button>
                        </div>
                    `;
                    container.appendChild(item);
                });
            });
            updateBulkActionsToolbar();
        }

        // Close modals
        function closeSectionSubjectsModal() {
            const modal = document.getElementById('sectionSubjectsModal');
            if (modal) {
                modal.classList.remove('show');
            }
        }

        function closeEditModal() {
            const modal = document.getElementById('editSubjectsModal');
            if (modal) {
                modal.classList.remove('show');
                // Clear bulk selection when modal is closed
                clearBulkSelection();
                clearSectionBulkSelection();
                console.log('Edit Subjects Modal closed');
            } else {
                console.log('Edit modal element not found');
            }
        }

        // Add Subject Modal Functions
        function openAddSubjectModal(sectionId, gradeLevel, sectionName, adviserName, adviserId) {
            // Ensure the modal exists before proceeding
            const modal = document.getElementById('addSubjectModal');
            if (!modal) {
                console.error('Add subject modal not found');
                return;
            }
            
            // Populate both dropdowns
            populateGradeLevelDropdown();
            populateSectionDropdown();
            
            // Pre-populate values if provided
            if (sectionId && gradeLevel) {
                const gradeSelect = document.getElementById('addGradeLevelSelect');
                const sectionSelect = document.getElementById('addSectionSelect');
                
                if (gradeSelect) {
                    gradeSelect.value = gradeLevel;
                }
                if (sectionSelect) {
                    populateSectionDropdown(gradeLevel);
                    sectionSelect.value = sectionId;
                }
                
                // Auto-populate adviser if available
                if (adviserId && adviserId !== 'null' && adviserId !== null) {
                    populateTeachersDropdown(gradeLevel, adviserId);
                } else {
                    populateTeachersDropdown(gradeLevel);
                }
            } else {
                populateTeachersDropdown();
            }
            
            // Clear form
            const form = document.getElementById('addSubjectForm');
            if (form) {
                form.reset();
            }
            
            // Re-populate if we have pre-selected values
            if (sectionId && gradeLevel) {
                const gradeSelect = document.getElementById('addGradeLevelSelect');
                const sectionSelect = document.getElementById('addSectionSelect');
                
                if (gradeSelect) {
                    gradeSelect.value = gradeLevel;
                }
                if (sectionSelect) {
                    populateSectionDropdown(gradeLevel);
                    sectionSelect.value = sectionId;
                }
            }
            
            modal.classList.add('show');
            // Setup auto-code generation for initial group(s)
            setupAutoCodeForAllGroups();
        }

        function closeAddSubjectModal() {
            const modal = document.getElementById('addSubjectModal');
            if (modal) {
                modal.classList.remove('show');
                document.getElementById('addSubjectForm').reset();
                
                // Reset to single subject group
                const container = document.getElementById('subjectsContainer');
                container.innerHTML = `
                    <div class="subject-group" data-subject-index="0">
                        <div class="subject-group-header">
                            <h4>Subject 1</h4>
                            <button type="button" class="btn btn-danger btn-sm remove-subject" onclick="removeSubjectGroup(this)">Remove</button>
                        </div>
                        <div class="form-group">
                            <label for="addSubjectName_0">Subject Name <span class="required-indicator">*</span></label>
                            <input type="text" id="addSubjectName_0" name="subjects[0][subject_name]" required>
                        </div>
                        <div class="form-group">
                            <label for="addSubjectCode_0">Subject Code <span class="required-indicator">*</span></label>
                            <input type="text" id="addSubjectCode_0" name="subjects[0][subject_code]" placeholder="Auto generated (e.g., G1MMATH)" required>
                        </div>
                        <div class="form-group">
                            <label for="addSubjectDescription_0">Description (Optional)</label>
                            <textarea id="addSubjectDescription_0" name="subjects[0][subject_description]" rows="3" placeholder="Brief description of the subject"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="addTeacherSelect_0">Teacher</label>
                            <select id="addTeacherSelect_0" name="subjects[0][teacher_id]">
                                <option value="">Select Teacher</option>
                            </select>
                        </div>
                    </div>
                `;
                
                // Load teachers for the reset select
                loadTeachersForSelect('addTeacherSelect_0');
                // Setup auto-code for the initial group
                setupAutoCodeForAllGroups();
            }
        }

        // Helper functions for section subjects modal
        function openAddSubjectModalFromSection() {
            closeSectionSubjectsModal();
            const selectedSection = sections.find(section => section.id == currentSectionId);
            if (selectedSection) {
                openAddSubjectModal(currentSectionId, selectedSection.grade_level, selectedSection.section, selectedSection.adviser_name, selectedSection.adviser_id);
            }
        }

        function openEditSubjectsModalFromSection() {
            closeSectionSubjectsModal();
            openEditSubjectsModal(currentSectionId);
        }

        // Populate grade level dropdown for add subject modal
        function populateGradeLevelDropdown() {
            const gradeSelect = document.getElementById('addGradeLevelSelect');
            if (!gradeSelect) {
                console.warn('Grade level select element not found');
                return;
            }
            gradeSelect.innerHTML = '<option value="">Select Grade Level</option>';
            
            const gradeLevels = [...new Set(sections.map(section => section.grade_level))];
            gradeLevels.sort((a, b) => {
                const order = ['Nursery', 'Kinder 1', 'Kinder 2'];
                const aIndex = order.indexOf(a);
                const bIndex = order.indexOf(b);
                
                if (aIndex !== -1 && bIndex !== -1) return aIndex - bIndex;
                if (aIndex !== -1) return -1;
                if (bIndex !== -1) return 1;
                
                return parseInt(a) - parseInt(b);
            });
            
            gradeLevels.forEach(grade => {
                const option = document.createElement('option');
                option.value = grade;
                // Add "Grade" prefix for numerical grade levels
                const displayText = /^\d+$/.test(grade) ? `Grade ${grade}` : grade;
                option.textContent = displayText;
                gradeSelect.appendChild(option);
            });
        }

        // Populate section dropdown based on selected grade level
        function populateSectionDropdown(selectedGradeLevel = '') {
            const sectionSelect = document.getElementById('addSectionSelect');
            if (!sectionSelect) {
                console.warn('Section select element not found');
                return;
            }
            sectionSelect.innerHTML = '<option value="">Select Section</option>';
            
            let filteredSections = sections;
            if (selectedGradeLevel) {
                filteredSections = sections.filter(section => section.grade_level === selectedGradeLevel);
            }
            
            filteredSections.forEach(section => {
                const option = document.createElement('option');
                option.value = section.id;
                option.textContent = section.section;
                sectionSelect.appendChild(option);
            });
        }

        // Populate teachers dropdown
        function populateTeachersDropdown(gradeLevel = '', preselectedTeacherId = null) {
            // Find all teacher select elements in the subjects container
            const teacherSelects = document.querySelectorAll('#subjectsContainer select[name*="[teacher_id]"]');
            
            if (teacherSelects.length === 0) {
                console.warn('No teacher select elements found');
                return;
            }
            
            // Build URL with grade level filter if provided
            let url = 'functions/get_teachers.php';
            if (gradeLevel) {
                url += `?grade_level=${encodeURIComponent(gradeLevel)}`;
            }
            
            fetch(url)
                .then(response => response.json())
                .then(teachers => {
                    // Update all teacher select elements
                    teacherSelects.forEach(teacherSelect => {
                        if (teacherSelect) {
                            teacherSelect.innerHTML = '<option value="">Select Teacher</option>';
                            
                            teachers.forEach(teacher => {
                                const option = document.createElement('option');
                                option.value = teacher.id;
                                option.textContent = `${teacher.first_name} ${teacher.last_name}`;
                                if (preselectedTeacherId && teacher.id == preselectedTeacherId) {
                                    option.selected = true;
                                }
                                teacherSelect.appendChild(option);
                            });
                        }
                    });
                })
                .catch(error => {
                    console.error('Error loading teachers:', error);
                });
        }

        // Handle grade level change to filter sections
        function handleGradeLevelChange() {
            const gradeSelect = document.getElementById('addGradeLevelSelect');
            const selectedGradeLevel = gradeSelect.value;
            
            // Update section dropdown based on selected grade level
            populateSectionDropdown(selectedGradeLevel);
            
            // Clear section selection
            document.getElementById('addSectionSelect').value = '';
            
            // Update teachers dropdown based on grade level
            if (selectedGradeLevel) {
                populateTeachersDropdown(selectedGradeLevel);
            } else {
                populateTeachersDropdown();
            }
        }

        // Handle section change to auto-populate adviser
        function handleSectionChange() {
            const sectionSelect = document.getElementById('addSectionSelect');
            const selectedSectionId = sectionSelect.value;
            
            if (selectedSectionId) {
                const selectedSection = sections.find(section => section.id == selectedSectionId);
                if (selectedSection) {
                    populateTeachersDropdown(selectedSection.grade_level, selectedSection.adviser_id);
                }
            } else {
                // Get the selected grade level and populate teachers accordingly
                const gradeSelect = document.getElementById('addGradeLevelSelect');
                const selectedGradeLevel = gradeSelect.value;
                populateTeachersDropdown(selectedGradeLevel);
            }
        }

        // Handle edit grade level change to filter sections
        function handleEditGradeLevelChange() {
            const gradeSelect = document.getElementById('editGradeLevelSelect');
            const selectedGradeLevel = gradeSelect.value;
            
            // Update section dropdown based on selected grade level
            populateEditSectionDropdown(selectedGradeLevel);
            
            // Clear section selection
            document.getElementById('editSectionSelect').value = '';
            
            // Update teachers dropdown based on grade level
            if (selectedGradeLevel) {
                populateEditTeachersDropdown(selectedGradeLevel);
            } else {
                populateEditTeachersDropdown();
            }
        }

        // Handle edit section change to auto-populate adviser
        function handleEditSectionChange() {
            const sectionSelect = document.getElementById('editSectionSelect');
            const selectedSectionId = sectionSelect.value;
            
            if (selectedSectionId) {
                const selectedSection = sections.find(section => section.id == selectedSectionId);
                if (selectedSection) {
                    populateEditTeachersDropdown(selectedSection.grade_level, selectedSection.adviser_id);
                }
            } else {
                // Get the selected grade level and populate teachers accordingly
                const gradeSelect = document.getElementById('editGradeLevelSelect');
                const selectedGradeLevel = gradeSelect.value;
                populateEditTeachersDropdown(selectedGradeLevel);
            }
        }

        // Handle add subject form submission
        function handleAddSubjectForm(event) {
            event.preventDefault();
            
            // Get values directly from DOM elements since disabled fields are not included in FormData
            const sectionId = document.getElementById('addSectionSelect').value;
            const gradeLevel = document.getElementById('addGradeLevelSelect').value;
            
            if (!gradeLevel) {
                alert('Please select a grade level');
                return;
            }
            
            if (!sectionId) {
                alert('Please select a section');
                return;
            }
            
            // Validate that at least one subject has a name
            const subjectGroups = document.querySelectorAll('.subject-group');
            let hasValidSubject = false;
            
            subjectGroups.forEach(group => {
                const subjectName = group.querySelector('input[name*="[subject_name]"]').value.trim();
                if (subjectName) {
                    hasValidSubject = true;
                }
            });
            
            if (!hasValidSubject) {
                alert('Please enter at least one subject name');
                return;
            }
            
            // Enforce valid subject codes and regenerate as needed (only Math, English, Filipino, Science)
            const sectionName = (function(){
                const sel = document.getElementById('addSectionSelect');
                return sel && sel.options && sel.selectedIndex >= 0 ? sel.options[sel.selectedIndex].text : '';
            })();
            const allowed = ['MATH','ENG','FIL','SCI'];
            let hasNonCurriculum = false;
            for (const group of subjectGroups) {
                const nameInput = group.querySelector('input[name*="[subject_name]"]');
                const codeInput = group.querySelector('input[name*="[subject_code]"]');
                const abbrev = getSubjectAbbreviation(nameInput.value);
                if (!allowed.includes(abbrev)) hasNonCurriculum = true;
                const expected = generateSubjectCode(gradeLevel, sectionName, nameInput.value);
                if (!expected) { showToast('Unable to generate subject code. Please check inputs.', 'error'); return; }
                codeInput.value = expected;
            }
            // If any is non-curriculum, show confirmation modal first
            if (hasNonCurriculum) {
                openNonCurriculumModal(() => handleAddSubjectFormSubmit(event.target, sectionId, gradeLevel));
                return;
            }
            return handleAddSubjectFormSubmit(event.target, sectionId, gradeLevel);
         
        }

        // Extracted submitter to allow reuse after modal confirmation
        function handleAddSubjectFormSubmit(formEl, sectionId, gradeLevel) {
            // Create FormData and manually add the disabled field values
            const formData = new FormData(formEl);
            formData.set('section_id', sectionId);
            formData.set('grade_level', gradeLevel);
            
            fetch('functions/save_subjects_batch.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    closeAddSubjectModal();
                    
                    // Refresh the subjects list if we're viewing a specific section
                    if (currentSectionId) {
                        loadSectionSubjects(currentSectionId);
                    }
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred while adding the subjects', 'error');
            });
        }
        
        // Function to add more subject groups
        function addMoreSubject() {
            const container = document.getElementById('subjectsContainer');
            const existingGroups = container.querySelectorAll('.subject-group');
            const newIndex = existingGroups.length;
            
            // Create new subject group
            const newGroup = document.createElement('div');
            newGroup.className = 'subject-group';
            newGroup.setAttribute('data-subject-index', newIndex);
            
            newGroup.innerHTML = `
                <div class="subject-group-header">
                    <h4>Subject ${newIndex + 1}</h4>
                    <button type="button" class="btn btn-danger btn-sm remove-subject" onclick="removeSubjectGroup(this)">Remove</button>
                </div>
                <div class="form-group">
                    <label for="addSubjectName_${newIndex}">Subject Name <span style="color: red;">*</span></label>
                    <input type="text" id="addSubjectName_${newIndex}" name="subjects[${newIndex}][subject_name]" required>
                </div>
                <div class="form-group">
                    <label for="addSubjectCode_${newIndex}">Subject Code <span style="color: red;">*</span></label>
                    <input type="text" id="addSubjectCode_${newIndex}" name="subjects[${newIndex}][subject_code]" placeholder="Auto generated (e.g., G1MMATH)" required>
                </div>
                <div class="form-group">
                    <label for="addSubjectDescription_${newIndex}">Description (Optional)</label>
                    <textarea id="addSubjectDescription_${newIndex}" name="subjects[${newIndex}][subject_description]" rows="3" placeholder="Brief description of the subject"></textarea>
                </div>
                <div class="form-group">
                    <label for="addTeacherSelect_${newIndex}">Teacher</label>
                    <select id="addTeacherSelect_${newIndex}" name="subjects[${newIndex}][teacher_id]">
                        <option value="">Select Teacher</option>
                    </select>
                </div>
            `;
            
            // Add the new group to container
            container.appendChild(newGroup);
            
            // Load teachers for the new select
            loadTeachersForSelect(`addTeacherSelect_${newIndex}`);
            
            // Show remove buttons for all groups if more than one
            if (existingGroups.length >= 1) {
                document.querySelectorAll('.remove-subject').forEach(btn => {
                    btn.classList.add('show');
                });
            }
            
            // Focus on the new subject name field
            document.getElementById(`addSubjectName_${newIndex}`).focus();
            
            // Setup auto-code for the new group
            setupAutoCodeForGroup(newGroup);
        }
        
        // Function to remove a subject group
        function removeSubjectGroup(button) {
            const group = button.closest('.subject-group');
            const container = document.getElementById('subjectsContainer');
            const allGroups = container.querySelectorAll('.subject-group');
            
            // Don't allow removing the last group
            if (allGroups.length <= 1) {
                return;
            }
            
            // Remove the group
            group.remove();
            
            // Reindex remaining groups
            reindexSubjectGroups();
            
            // Hide remove buttons if only one group left
            if (container.querySelectorAll('.subject-group').length === 1) {
                document.querySelectorAll('.remove-subject').forEach(btn => {
                    btn.classList.remove('show');
                });
            }
        }
        
        // Function to reindex subject groups after removal
        function reindexSubjectGroups() {
            const container = document.getElementById('subjectsContainer');
            const groups = container.querySelectorAll('.subject-group');
            
            groups.forEach((group, index) => {
                group.setAttribute('data-subject-index', index);
                
                // Update header
                const header = group.querySelector('h4');
                header.textContent = `Subject ${index + 1}`;
                
                // Update input names and IDs
                const inputs = group.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    const name = input.getAttribute('name');
                    const id = input.getAttribute('id');
                    
                    if (name) {
                        const newName = name.replace(/subjects\[\d+\]/, `subjects[${index}]`);
                        input.setAttribute('name', newName);
                    }
                    
                    if (id) {
                        const newId = id.replace(/_\d+$/, `_${index}`);
                        input.setAttribute('id', newId);
                    }
                });
                
                // Update labels
                const labels = group.querySelectorAll('label');
                labels.forEach(label => {
                    const forAttr = label.getAttribute('for');
                    if (forAttr) {
                        const newFor = forAttr.replace(/_\d+$/, `_${index}`);
                        label.setAttribute('for', newFor);
                    }
                });
            });
        }

        // ===== Subject Code Auto-generation & Validation =====
        function getSubjectAbbreviation(subjectName) {
            const name = (subjectName || '').trim().toLowerCase();
            if (!name) return '';
            if (name.startsWith('math')) return 'MATH';
            if (name.startsWith('eng')) return 'ENG';
            if (name.startsWith('fil')) return 'FIL';
            if (name.startsWith('sci')) return 'SCI';
            const letters = name.replace(/[^a-z]/g, '').toUpperCase();
            return letters.substring(0, 4);
        }

        function getSelectedSectionName() {
            const sectionSelect = document.getElementById('addSectionSelect');
            if (!sectionSelect || !sectionSelect.options || sectionSelect.selectedIndex < 0) return '';
            return sectionSelect.options[sectionSelect.selectedIndex].text || '';
        }

        function generateSubjectCode(gradeLevel, sectionName, subjectName) {
            const gradeMatch = String(gradeLevel || '').match(/^\d{1,2}$/);
            const gradePart = gradeMatch ? `G${gradeMatch[0]}` : '';
            const sectionPart = (sectionName || '').trim().charAt(0).toUpperCase();
            const subjectPart = getSubjectAbbreviation(subjectName);
            if (!gradePart || !sectionPart || !subjectPart) return '';
            return `${gradePart}${sectionPart}${subjectPart}`;
        }

        function updateSubjectCodeForGroup(groupEl) {
            const gradeLevel = document.getElementById('addGradeLevelSelect').value;
            const sectionName = getSelectedSectionName();
            const nameInput = groupEl.querySelector('input[name*="[subject_name]"]');
            const codeInput = groupEl.querySelector('input[name*="[subject_code]"]');
            if (!nameInput || !codeInput) return;
            const proposed = generateSubjectCode(gradeLevel, sectionName, nameInput.value);
            if (proposed) {
                codeInput.value = proposed;
            }
        }

        function setupAutoCodeForGroup(groupEl) {
            const nameInput = groupEl.querySelector('input[name*="[subject_name]"]');
            if (!nameInput) return;
            nameInput.addEventListener('input', () => updateSubjectCodeForGroup(groupEl));
            updateSubjectCodeForGroup(groupEl);
        }

        function setupAutoCodeForAllGroups() {
            const groups = document.querySelectorAll('#subjectsContainer .subject-group');
            groups.forEach(group => setupAutoCodeForGroup(group));
        }

        // Function to load teachers for a specific select element
        function loadTeachersForSelect(selectId) {
            const select = document.getElementById(selectId);
            if (!select) {
                console.warn(`Teacher select element with ID '${selectId}' not found`);
                return;
            }
            
            // Clear existing options except the first one
            select.innerHTML = '<option value="">Select Teacher</option>';
            
            // Load teachers from the same grade level
            const gradeLevel = document.getElementById('addGradeLevelSelect').value;
            if (!gradeLevel) return;
            
            fetch(`functions/get_teachers.php?grade_level=${encodeURIComponent(gradeLevel)}`)
                .then(response => response.json())
                .then(teachers => {
                    if (Array.isArray(teachers)) {
                        teachers.forEach(teacher => {
                            const option = document.createElement('option');
                            option.value = teacher.id;
                            option.textContent = `${teacher.first_name} ${teacher.last_name}`;
                            select.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading teachers:', error);
                });
        }

        // Show notification message
        function showNotification(message, type = 'info') {
            // Remove existing notifications
            const existingNotifications = document.querySelectorAll('.notification');
            existingNotifications.forEach(notification => notification.remove());
            
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <span class="notification-message">${message}</span>
                    <button class="notification-close" onclick="this.parentElement.parentElement.remove()">&times;</button>
                </div>
            `;
            
            // Add to page
            document.body.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }


        // Button event handlers
        document.getElementById('manageSubjectsBtn').addEventListener('click', function() {
            // Show all subjects in a modal
            showAllSubjectsModal();
        });


        // Filter change handlers
        document.getElementById('grade_level').addEventListener('change', function() {
            populateSectionFilter(); // Update section dropdown first
            filterSections(); // Then filter sections
        });
        document.getElementById('section').addEventListener('change', filterSections);

        // Close modals when clicking outside
        window.onclick = function(event) {
            const editModal = document.getElementById('editSubjectsModal');
            const addModal = document.getElementById('addSubjectModal');
            const sectionModal = document.getElementById('sectionSubjectsModal');
            const editSubjectModal = document.getElementById('editSubjectModal');
            const deleteSubjectModal = document.getElementById('deleteSubjectModal');
            const nonCurriculumModal = document.getElementById('nonCurriculumModal');
            const bulkDeleteModal = document.getElementById('bulkDeleteModal');
            
            if (event.target === editModal && editModal && editModal.classList.contains('show')) {
                closeEditModal();
            }
            
            if (event.target === addModal && addModal && addModal.classList.contains('show')) {
                closeAddSubjectModal();
            }
            
            if (event.target === sectionModal && sectionModal && sectionModal.classList.contains('show')) {
                closeSectionSubjectsModal();
            }
            
            if (event.target === editSubjectModal && editSubjectModal && editSubjectModal.classList.contains('show')) {
                closeEditSubjectModal();
            }
            
            if (event.target === deleteSubjectModal && deleteSubjectModal && deleteSubjectModal.classList.contains('show')) {
                closeDeleteSubjectModal();
            }

            if (event.target === nonCurriculumModal && nonCurriculumModal && nonCurriculumModal.classList.contains('show')) {
                closeNonCurriculumModal();
            }

            if (event.target === bulkDeleteModal && bulkDeleteModal && bulkDeleteModal.classList.contains('show')) {
                closeBulkDeleteModal();
            }
        }

        // Close modals with ESC key
        function setupModalCloseButtons() {
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const editModal = document.getElementById('editSubjectsModal');
                    const addModal = document.getElementById('addSubjectModal');
                    const sectionModal = document.getElementById('sectionSubjectsModal');
                    const editSubjectModal = document.getElementById('editSubjectModal');
                    const deleteSubjectModal = document.getElementById('deleteSubjectModal');
                    const nonCurriculumModal = document.getElementById('nonCurriculumModal');
                    const bulkDeleteModal = document.getElementById('bulkDeleteModal');
                    
                    if (editModal && editModal.classList.contains('show')) {
                        closeEditModal();
                    }
                    
                    if (addModal && addModal.classList.contains('show')) {
                        closeAddSubjectModal();
                    }
                    
                    if (sectionModal && sectionModal.classList.contains('show')) {
                        closeSectionSubjectsModal();
                    }
                    
                    if (editSubjectModal && editSubjectModal.classList.contains('show')) {
                        closeEditSubjectModal();
                    }
                    
                    if (deleteSubjectModal && deleteSubjectModal.classList.contains('show')) {
                        closeDeleteSubjectModal();
                    }

                    if (nonCurriculumModal && nonCurriculumModal.classList.contains('show')) {
                        closeNonCurriculumModal();
                    }

                    if (bulkDeleteModal && bulkDeleteModal.classList.contains('show')) {
                        closeBulkDeleteModal();
                    }
                }
            });
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadSections();
            setupModalCloseButtons();
            
            // Add subject form event listeners
            document.getElementById('addSubjectForm').addEventListener('submit', handleAddSubjectForm);
            // Note: Grade Level and Section change handlers removed since fields are now disabled
            
            // Edit subject form event listeners
            document.getElementById('editSubjectForm').addEventListener('submit', handleEditSubjectForm);
            document.getElementById('editGradeLevelSelect').addEventListener('change', handleEditGradeLevelChange);
            document.getElementById('editSectionSelect').addEventListener('change', handleEditSectionChange);
            
            // Check if we're coming from section creation with section pre-selection
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('preselect_section')) {
                const sectionId = urlParams.get('preselect_section');
                const gradeLevel = urlParams.get('grade_level');
                const section = urlParams.get('section');
                
                console.log('URL Parameters:', { sectionId, gradeLevel, section }); // Debug log
                
                if (sectionId && gradeLevel && section && gradeLevel !== 'null' && section !== 'null') {
                    // Wait for sections to load, then pre-select the filters
                    setTimeout(() => {
                        // Pre-select the grade level filter
                        const gradeLevelSelect = document.getElementById('grade_level');
                        if (gradeLevelSelect) {
                            gradeLevelSelect.value = gradeLevel;
                            // Trigger change event to update section filter
                            gradeLevelSelect.dispatchEvent(new Event('change'));
                            
                            // Wait a bit more for section filter to populate, then select the section
                            setTimeout(() => {
                                const sectionSelect = document.getElementById('section');
                                if (sectionSelect) {
                                    sectionSelect.value = section;
                                    // Trigger change event to filter the sections grid
                                    sectionSelect.dispatchEvent(new Event('change'));
                                }
                                
                                // Show notification that section was pre-selected
                                const gradeDisplay = /^\d+$/.test(gradeLevel) ? `Grade ${gradeLevel}` : gradeLevel;
                                showNotification(`Now viewing subjects for ${gradeDisplay} - ${section}. You can add subjects using the "Add Subject" button on the section card.`, 'success');
                            }, 500);
                        }
                    }, 1000);
                    
                    // Clean the URL to remove the parameters
                    window.history.replaceState({}, document.title, window.location.pathname);
                } else {
                    // If parameters are invalid (null/undefined), just clean the URL without showing notification
                    console.log('Invalid parameters received:', { sectionId, gradeLevel, section });
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            }
            
            // Add touch-friendly interactions for mobile
            if ('ontouchstart' in window) {
                // Add touch class to body for touch-specific styles
                document.body.classList.add('touch-device');
                
                // Improve touch targets
                const touchElements = document.querySelectorAll('.btn, .subject-action');
                touchElements.forEach(element => {
                    element.style.minHeight = '44px';
                    element.style.minWidth = '44px';
                });
            }
            
            // Handle sidebar toggle for mobile
            const menuButton = document.querySelector('.menu-button');
            if (menuButton) {
                menuButton.addEventListener('click', function() {
                    document.body.classList.toggle('sidebar-open');
                });
            }
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 920) {
                    const sidebar = document.querySelector('.sidebar');
                    const menuButton = document.querySelector('.menu-button');
                    
                    if (sidebar && !sidebar.contains(e.target) && !menuButton.contains(e.target)) {
                        document.body.classList.remove('sidebar-open');
                    }
                }
            });
            
            // Handle window resize
            let resizeTimeout;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(function() {
                    // Close sidebar on resize to desktop
                    if (window.innerWidth > 920) {
                        document.body.classList.remove('sidebar-open');
                    }
                    
                    // Recalculate grid layout if needed
                    const grid = document.getElementById('sectionsGrid');
                    if (grid) {
                        grid.style.display = 'none';
                        grid.offsetHeight; // Trigger reflow
                        grid.style.display = '';
                    }
                }, 250);
            });
        });
    </script>

    <?php include 'includes/scripts.php'; ?>
</body>
</html>
