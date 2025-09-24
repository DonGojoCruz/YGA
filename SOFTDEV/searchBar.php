<?php
session_start();
require_once __DIR__ . '/db/db_connect.php';

// Handle login via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    header('Content-Type: application/json');
    $gradeLevel = isset($_POST['gradeLevel']) ? trim($_POST['gradeLevel']) : '';
    $section = isset($_POST['section']) ? trim($_POST['section']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if ($gradeLevel === '' || $section === '' || $password === '') {
        echo json_encode(['success' => false, 'message' => 'Missing fields']);
        exit;
    }

    // Normalize "Grade X" to just the number
    if (stripos($gradeLevel, 'grade') === 0) {
        $gradeLevel = trim(str_ireplace('grade', '', $gradeLevel));
    }

    if ($stmt = $conn->prepare('SELECT id FROM section WHERE grade_level = ? AND section = ? AND password = ? LIMIT 1')) {
        $stmt->bind_param('sss', $gradeLevel, $section, $password);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $_SESSION['selected_section_id'] = $row['id'];
            $_SESSION['selected_grade_level'] = $gradeLevel;
            $_SESSION['selected_section'] = $section;

            echo json_encode(['success' => true, 'redirect' => 'sectionDashboard.php']);
            $stmt->close();
            exit;
        }
        $stmt->close();
    }

    echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    exit;
}

// Load grade levels with custom sort
$gradeLevels = [];
$gq = $conn->query('SELECT DISTINCT grade_level FROM section');
if ($gq && $gq->num_rows > 0) {
    while ($g = $gq->fetch_assoc()) {
        $gradeLevels[] = $g['grade_level'];
    }

    $gradeOrder = ['Nursery', 'Kinder 1', 'Kinder 2'];
    usort($gradeLevels, function($a, $b) use ($gradeOrder) {
        $aIndex = array_search($a, $gradeOrder);
        $bIndex = array_search($b, $gradeOrder);
        if ($aIndex === false) $aIndex = 100 + (is_numeric($a) ? (int)$a : 0);
        if ($bIndex === false) $bIndex = 100 + (is_numeric($b) ? (int)$b : 0);
        return $aIndex - $bIndex;
    });
}

// Load sections
$sections = [];
$sq = $conn->query('SELECT DISTINCT section FROM section ORDER BY section ASC');
if ($sq && $sq->num_rows > 0) {
    while ($s = $sq->fetch_assoc()) { $sections[] = $s['section']; }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Young Gen. Academy - Section Search</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/searchBar.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
            /* Hide scrollbar for webkit browsers */
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* Internet Explorer 10+ */
        }

        /* Hide scrollbar for webkit browsers */
        body::-webkit-scrollbar {
            display: none;
        }

        /* Hide scrollbar for all elements */
        * {
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* Internet Explorer 10+ */
        }

        *::-webkit-scrollbar {
            display: none;
        }

        /* Subtle pattern background */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 25% 25%, rgba(255, 255, 255, 0.3) 2px, transparent 2px),
                radial-gradient(circle at 75% 75%, rgba(255, 255, 255, 0.2) 1px, transparent 1px);
            background-size: 60px 60px, 40px 40px;
            z-index: -1;
        }

        .main-layout {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1200px;
            margin: 0 auto;
            min-height: calc(100vh - 40px);
            gap: 40px;
        }

        .left-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            animation: slideLeft 0.8s ease-out;
        }

        .logo-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 40px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 400px;
        }

        .logo-icon {
            margin-bottom: 20px;
            text-shadow: none;
        }

        .academy-logo {
            width: 120px;
            height: 120px;
            object-fit: contain;
            border-radius: 50%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .academy-logo:hover {
            transform: scale(1.05);
        }

        .main-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
            text-shadow: none;
            line-height: 1.2;
        }

        .subtitle {
            font-size: 1.2rem;
            color: #666;
            margin-top: 10px;
            font-weight: 400;
        }

        .right-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: slideRight 0.8s ease-out;
        }

        .main-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            max-width: 450px;
            width: 100%;
        }

        @keyframes slideLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Disabled state for section dropdown */
        .search-box select:disabled {
            background-color: #f5f5f5;
            color: #999;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .search-box select:disabled:hover {
            border-color: #ccc;
        }
        
        .search-box.disabled label {
            color: #999;
        }
        
        /* Error state styling */
        .search-box.error select {
            border-color: #e53e3e;
            box-shadow: 0 0 0 3px rgba(229, 62, 62, 0.1);
            animation: shake 0.5s ease-in-out;
        }
        
        .search-box.error label {
            color: #e53e3e;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        /* Tablet styles */
        @media (max-width: 1024px) {
            .main-layout {
                gap: 20px;
                padding: 0 20px;
            }
            
            .main-container {
                max-width: 400px;
                padding: 35px;
            }
            
            .logo-container {
                padding: 30px;
                max-width: 350px;
            }
            
            .main-title {
                font-size: 2.2rem;
            }
            
            .academy-logo {
                width: 100px;
                height: 100px;
            }
        }

        /* Mobile tablet styles */
        @media (max-width: 768px) {
            .main-layout {
                flex-direction: column;
                gap: 30px;
                padding: 20px;
                min-height: auto;
            }
            
            .left-section, .right-section {
                flex: none;
                width: 100%;
            }
            
            .main-title {
                font-size: 2rem;
                line-height: 1.3;
            }
            
            .main-container {
                padding: 30px 25px;
                margin: 0;
                border-radius: 15px;
                max-width: 100%;
            }
            
            .academy-logo {
                width: 90px;
                height: 90px;
            }
            
            .logo-container {
                padding: 25px;
                border-radius: 15px;
                max-width: 100%;
            }
            
            .subtitle {
                font-size: 1.1rem;
            }
        }

        /* Small mobile styles */
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .main-layout {
                gap: 20px;
                padding: 10px;
            }
            
            .main-title {
                font-size: 1.7rem;
                line-height: 1.2;
            }
            
            .main-container {
                padding: 25px 20px;
                margin: 0;
                border-radius: 12px;
            }
            
            .academy-logo {
                width: 80px;
                height: 80px;
            }
            
            .logo-container {
                padding: 20px;
                border-radius: 12px;
            }
            
            .subtitle {
                font-size: 1rem;
            }
        }

        /* Extra small mobile styles */
        @media (max-width: 360px) {
            .main-title {
                font-size: 1.5rem;
            }
            
            .main-container {
                padding: 20px 15px;
            }
            
            .logo-container {
                padding: 15px;
            }
            
            .academy-logo {
                width: 70px;
                height: 70px;
            }
            
            .subtitle {
                font-size: 0.9rem;
            }
        }

        /* Landscape mobile orientation */
        @media (max-height: 500px) and (orientation: landscape) {
            .main-layout {
                flex-direction: row;
                gap: 20px;
                padding: 10px;
                min-height: calc(100vh - 20px);
            }
            
            .left-section, .right-section {
                flex: 1;
                width: auto;
            }
            
            .logo-container {
                padding: 20px;
                max-width: 300px;
            }
            
            .main-container {
                padding: 25px;
                max-width: 350px;
            }
            
            .main-title {
                font-size: 1.8rem;
            }
            
            .academy-logo {
                width: 80px;
                height: 80px;
            }
            
            .subtitle {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-layout">
        <!-- Left Section - Academy Branding -->
        <div class="left-section">
            <div class="logo-container">
                <div class="logo-icon">
                    <img src="img/YGA.png" alt="Young Generation Academy Logo" class="academy-logo">
                </div>
                <h1 class="main-title">Young Generation Academy</h1>
            </div>
        </div>

        <!-- Right Section - Section Search -->
        <div class="right-section">
            <div class="main-container">
                <div class="form-header">
                    <h2><i class="fas fa-search"></i> Section Search</h2>
                    <p>Select your grade level and section to continue</p>
                </div>

                <div class="search-container">
                    <!-- Grade Dropdown -->
                    <div class="search-box">
                        <label for="gradeSelect">
                            <i class="fas fa-layer-group"></i>
                            Grade Level
                        </label>
                        <select id="gradeSelect">
                            <option value="">Select Grade Level</option>
                            <?php foreach ($gradeLevels as $g): ?>
                                <?php
                                    $value = is_numeric($g) ? $g : $g; // send raw value to server
                                    $label = is_numeric($g) ? "Grade $g" : $g;
                                ?>
                                <option value="<?php echo htmlspecialchars($value); ?>"><?php echo htmlspecialchars($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Section Dropdown -->
                    <div class="search-box" id="sectionBox">
                        <label for="sectionSelect">
                            <i class="fas fa-users"></i>
                            Section
                        </label>
                        <select id="sectionSelect" disabled>
                            <option value="">Select Section</option>
                            <?php foreach ($sections as $s): ?>
                                <option value="<?php echo htmlspecialchars($s); ?>"><?php echo htmlspecialchars($s); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Search Button -->
                <button class="main-button" onclick="openModal()">
                    <i class="fas fa-search"></i>
                    Search
                </button>
            </div>
        </div>
    </div>

<!-- Enhanced Password Modal -->
<div class="modal" id="passwordModal">
    <div class="modal-content">
        <h3>Authentication Required</h3>
        <p>Please enter the section password to continue</p>
        <input type="password" id="passwordInput" placeholder="Enter section password">
        <div class="modal-buttons">
            <button class="submit-btn" onclick="checkPassword()">
                <i class="fas fa-unlock"></i>
                Authenticate
            </button>
            <button class="cancel-btn" onclick="closeModal()">
                <i class="fas fa-times"></i>
                Cancel
            </button>
        </div>
    </div>
</div>

<script>
function openModal() {
    const grade = document.getElementById("gradeSelect").value;
    const section = document.getElementById("sectionSelect").value;
    const gradeBox = document.getElementById("gradeSelect").closest('.search-box');
    const sectionBox = document.getElementById("sectionSelect").closest('.search-box');
    
    // Clear any previous error states
    gradeBox.classList.remove('error');
    sectionBox.classList.remove('error');
    
    // Validate that both grade and section are selected
    if (!grade || grade === "") {
        gradeBox.classList.add('error');
        document.getElementById("gradeSelect").focus();
        return;
    }
    
    if (!section || section === "") {
        sectionBox.classList.add('error');
        document.getElementById("sectionSelect").focus();
        return;
    }
    
    // If both are selected, open the password modal
    document.getElementById("passwordModal").style.display = "flex";
}

function closeModal() {
    document.getElementById("passwordModal").style.display = "none";
    document.getElementById("passwordInput").value = "";
}

function checkPassword() {
    const grade = document.getElementById("gradeSelect").value;
    const section = document.getElementById("sectionSelect").value;
    const pass = document.getElementById("passwordInput").value;

    if (!grade || !section || !pass) {
        alert("Please select grade, section, and enter password");
        return;
    }

    const params = new URLSearchParams();
    params.append('action', 'login');
    params.append('gradeLevel', grade);
    params.append('section', section);
    params.append('password', pass);

    fetch('searchBar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params.toString()
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.href = data.redirect || 'viewAttendance.php';
        } else {
            alert('❌ ' + (data.message || 'Wrong password!'));
        }
    })
    .catch(() => alert('❌ Login failed'));
}

// Clear error states when user makes selections
function clearErrorStates() {
    const gradeBox = document.getElementById("gradeSelect").closest('.search-box');
    const sectionBox = document.getElementById("sectionSelect").closest('.search-box');
    gradeBox.classList.remove('error');
    sectionBox.classList.remove('error');
}

// Populate sections based on selected grade
function updateSectionOptions() {
    const grade = document.getElementById('gradeSelect').value;
    const sectionSelect = document.getElementById('sectionSelect');
    const sectionBox = document.getElementById('sectionBox');

    // Clear error states when user makes a selection
    clearErrorStates();

    // Reset options
    sectionSelect.innerHTML = '<option value="">Select Section</option>';

    if (!grade) {
        // Disable section dropdown when no grade is selected
        sectionSelect.disabled = true;
        sectionBox.classList.add('disabled');
        return;
    }

    // Enable section dropdown when grade is selected
    sectionSelect.disabled = false;
    sectionBox.classList.remove('disabled');

    fetch('functions/get_sections.php?grade=' + encodeURIComponent(grade))
        .then(r => {
            if (!r.ok) throw new Error('Network error: ' + r.status);
            return r.json();
        })
        .then(list => {
            if (Array.isArray(list)) {
                if (list.length === 0) {
                    sectionSelect.innerHTML = '<option value="">No sections found for this grade</option>';
                } else {
                    list.forEach(sec => {
                        const opt = document.createElement('option');
                        opt.value = sec;
                        opt.textContent = sec;
                        sectionSelect.appendChild(opt);
                    });
                }
            } else if (list.error) {
                sectionSelect.innerHTML = '<option value="">Error: ' + list.error + '</option>';
            } else {
                sectionSelect.innerHTML = '<option value="">Unexpected response format</option>';
            }
        })
        .catch(error => {
            sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
        });
}

// Hook up change listeners after DOM is ready (script is at end of body)
document.getElementById('gradeSelect').addEventListener('change', updateSectionOptions);
document.getElementById('sectionSelect').addEventListener('change', clearErrorStates);

// Initialize the section dropdown state on page load
document.addEventListener('DOMContentLoaded', function() {
    updateSectionOptions();
    
    // Prevent back navigation to attendance page if user was redirected from there
    if (performance.navigation.type === 2) { // User came here via back button
        window.location.replace('searchBar.php');
    }
    
    // Clear any browser history that might point to attendance pages
    if (window.history && window.history.pushState) {
        window.history.replaceState(null, '', 'searchBar.php');
        
        // Prevent back button from going to attendance pages
        window.addEventListener('popstate', function(event) {
            event.preventDefault();
            window.location.replace('searchBar.php');
        });
    }
});
</script>
</body>
</html>
