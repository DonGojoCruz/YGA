import serial
import serial.tools.list_ports
import time
from datetime import datetime
import os
import atexit
import threading
import mysql.connector
from mysql.connector import Error
import requests
import json
import pytz

# Manila timezone
MANILA_TZ = pytz.timezone('Asia/Manila')

def get_manila_time():
    """Get current time in Manila timezone."""
    return datetime.now(MANILA_TZ)

# File paths - will be updated based on subject
def get_subject_file_path(subject_name):
    """Generate file path based on subject name."""
    if not subject_name or subject_name.strip() == '':
        return None  # No file path when no subject assigned
    
    # Convert subject name to lowercase and replace spaces with underscores
    subject_key = subject_name.lower().replace(' ', '_').replace('-', '_')
    return f"C:/xampp/htdocs/SOFTDEV/{subject_key}rfid.txt"

def get_exit_logging_file_path():
    """Generate file path for exit logging."""
    return f"C:/xampp/htdocs/SOFTDEV/exitrfid.txt"

# Default file path (will be updated when subject is loaded)
showrfid_file = None  # Will be set based on subject configuration

# Database configuration
DB_CONFIG = {
    'host': 'localhost',
    'database': 'samaria',
    'user': 'root',
    'password': ''
}

def send_attendance_email_notification(rfid_tag, subject, grade, section):
    """Send email notification for attendance confirmation."""
    try:
        # Prepare the data for the email API
        email_data = {
            'rfid_tag': rfid_tag,
            'subject': subject,
            'grade': grade,
            'section': section
        }
        
        # Send POST request to the email notification API
        response = requests.post(
            'http://localhost/SOFTDEV/functions/send_attendance_email.php',
            json=email_data,
            timeout=10
        )
        
        if response.status_code == 200:
            result = response.json()
            if result.get('success'):
                print(f"📧 Email notification sent for UID {rfid_tag} - {subject}")
            else:
                print(f"⚠️ Email notification failed for UID {rfid_tag}: {result.get('message', 'Unknown error')}")
        else:
            print(f"⚠️ Email API error for UID {rfid_tag}: HTTP {response.status_code}")
            
    except requests.exceptions.RequestException as e:
        print(f"⚠️ Email notification error for UID {rfid_tag}: {e}")
    except Exception as e:
        print(f"⚠️ Unexpected error sending email for UID {rfid_tag}: {e}")

def send_exit_email_notification(rfid_tag, grade, section, student_name):
    """Send email notification for exit logging confirmation."""
    try:
        # Prepare the data for the exit email API
        email_data = {
            'rfid_tag': rfid_tag,
            'grade': grade,
            'section': section,
            'student_name': student_name,
            'type': 'exit'
        }
        
        # Send POST request to the email notification API
        response = requests.post(
            'http://localhost/SOFTDEV/functions/send_attendance_email.php',
            json=email_data,
            timeout=10
        )
        
        if response.status_code == 200:
            result = response.json()
            if result.get('success'):
                print(f"📧 Exit email notification sent for UID {rfid_tag} - {student_name}")
            else:
                print(f"⚠️ Exit email notification failed for UID {rfid_tag}: {result.get('message', 'Unknown error')}")
        else:
            print(f"⚠️ Exit email API error for UID {rfid_tag}: HTTP {response.status_code}")
            
    except requests.exceptions.RequestException as e:
        print(f"⚠️ Exit email notification error for UID {rfid_tag}: {e}")
    except Exception as e:
        print(f"⚠️ Unexpected error sending exit email for UID {rfid_tag}: {e}")

def delete_file_after_delay(file_path, delay=5):
    """Delete file after specified delay in seconds."""
    def delete_file():
        print(f"⏰ Scheduling deletion of {file_path} in {delay} seconds")
        time.sleep(delay)
        if os.path.exists(file_path):
            try:
                os.remove(file_path)
                print(f"🗑️ Deleted {file_path} after {delay} seconds")
            except Exception as e:
                print(f"❌ Failed to delete {file_path}: {e}")
        else:
            print(f"⚠️ File {file_path} no longer exists when trying to delete")
    
    # Run deletion in a separate thread to avoid blocking main loop
    thread = threading.Thread(target=delete_file)
    thread.daemon = True  # Thread will die when main program exits
    thread.start()

# Load target section and subject configuration from file
def load_section_config():
    """Load target section and subject from config file or use default."""
    config_file = r"C:/xampp/htdocs/SOFTDEV/rfid_config.txt"
    default_grade = '1'
    default_section = 'Mango'
    default_subject = ''
    default_exit_logging = 'false'
    
    try:
        if os.path.exists(config_file):
            with open(config_file, 'r') as f:
                lines = f.readlines()
            
            grade = default_grade
            section = default_section
            subject = default_subject
            exit_logging = default_exit_logging
            
            for line in lines:
                line = line.strip()
                if line.startswith('TARGET_GRADE='):
                    grade = line.replace('TARGET_GRADE=', '').strip()
                elif line.startswith('TARGET_SECTION='):
                    section = line.replace('TARGET_SECTION=', '').strip()
                elif line.startswith('TARGET_SUBJECT='):
                    subject = line.replace('TARGET_SUBJECT=', '').strip()
                elif line.startswith('EXIT_LOGGING='):
                    exit_logging = line.replace('EXIT_LOGGING=', '').strip()
            
            print(f"📖 Config loaded: Grade {grade} - {section} - Subject: {subject} - Exit Logging: {exit_logging}")
            return grade, section, subject, exit_logging
        else:
            # Create default config file
            print(f"📝 Creating default config file: {config_file}")
            with open(config_file, 'w') as f:
                f.write(f"TARGET_GRADE={default_grade}\n")
                f.write(f"TARGET_SECTION={default_section}\n")
                f.write(f"TARGET_SUBJECT={default_subject}\n")
                f.write(f"EXIT_LOGGING={default_exit_logging}\n")
            print(f"📝 Default config created: Grade {default_grade} - {default_section} - Subject: {default_subject} - Exit Logging: {default_exit_logging}")
            return default_grade, default_section, default_subject, default_exit_logging
    except Exception as e:
        print(f"❌ Error loading config: {e}")
        return default_grade, default_section, default_subject, default_exit_logging

# Load current target section and subject
TARGET_GRADE, TARGET_SECTION, TARGET_SUBJECT, EXIT_LOGGING = load_section_config()

# Update file path based on subject
showrfid_file = get_subject_file_path(TARGET_SUBJECT)

print(f"🔧 DEBUG STARTUP:")
print(f"🔧 TARGET_SUBJECT: '{TARGET_SUBJECT}'")
print(f"🔧 Expected file path: {showrfid_file}")
print(f"🔧 File path from function: {get_subject_file_path(TARGET_SUBJECT)}")

# Reset subject-specific file on start (only if subject is assigned)
if showrfid_file:
    try:
        with open(showrfid_file, "w") as f:
            f.write("")
        print(f"✅ Subject file initialized: {showrfid_file}")
        print(f"✅ File exists after init: {os.path.exists(showrfid_file)}")
    except Exception as e:
        print(f"❌ Error initializing file: {e}")
else:
    print(f"⚠️ No subject assigned - no subject file will be created")
    print(f"⚠️ Display will show 'Choose Subject First.' message")

# Create a test file to verify Python is working
try:
    test_file = "C:/xampp/htdocs/SOFTDEV/python_test.txt"
    with open(test_file, "w") as f:
        f.write(f"Python script started at {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print(f"✅ Test file created: {test_file}")
except Exception as e:
    print(f"❌ Error creating test file: {e}")

print(f"Scanning restricted to Grade {TARGET_GRADE} - {TARGET_SECTION} section, Subject: {TARGET_SUBJECT}")

# Auto-detect Arduino COM port
ports = list(serial.tools.list_ports.comports())
arduino_port = None
for p in ports:
    if "Arduino" in p.description or "CH340" in p.description:
        arduino_port = p.device
        break

if not arduino_port:
    exit()  # no Arduino found, exit silently

arduino = serial.Serial(arduino_port, 9600, timeout=1)
time.sleep(2)

# Track last accepted tap time per UID (for 10s rule)
last_tap_times = {}
# Track exit log entries per UID per day (to prevent duplicates)
exit_logged_uids = set()
# Track attendance per UID per day per subject (to prevent duplicates)
attendance_logged_uids = set()
current_date = get_manila_time().strftime("%Y-%m-%d")
last_config_reload = time.time()



def validate_student_section(uid):
    """Check if student belongs to the target section and subject."""
    try:
        connection = mysql.connector.connect(**DB_CONFIG)
        cursor = connection.cursor()
        
        print(f"🔍 Validating UID: {uid}")
        print(f"🎯 Target: Grade {TARGET_GRADE} - {TARGET_SECTION}, Subject: {TARGET_SUBJECT}")
        
        # First, let's check what section this student is actually in
        debug_query = """
        SELECT s.student_id, s.first_name, s.last_name, s.section_id, sec.id as section_table_id, sec.grade_level, sec.section 
        FROM students s 
        LEFT JOIN section sec ON s.section_id = sec.id 
        WHERE s.rfid_tag = %s
        """
        cursor.execute(debug_query, (uid,))
        debug_result = cursor.fetchone()
        
        if debug_result:
            print(f"👤 Student found: {debug_result[1]} {debug_result[2]}")
            print(f"📊 Student section_id: {debug_result[3]}, Section table ID: {debug_result[4]}")
            print(f"📚 Student's Grade: '{debug_result[5]}', Section: '{debug_result[6]}'")
            print(f"🎯 Target Grade: '{TARGET_GRADE}', Target Section: '{TARGET_SECTION}', Target Subject: '{TARGET_SUBJECT}'")
            
            # Check exact string matching
            grade_match = str(debug_result[5]).strip() == str(TARGET_GRADE).strip()
            section_match = str(debug_result[6]).strip() == str(TARGET_SECTION).strip()
            print(f"✅ Grade match: {grade_match} ('{debug_result[5]}' == '{TARGET_GRADE}')")
            print(f"✅ Section match: {section_match} ('{debug_result[6]}' == '{TARGET_SECTION}')")
        else:
            print(f"❌ Student not found in database for UID: {uid}")
            return False
        
        # STRICT Query: check if student is EXACTLY in the target section
        query = """
        SELECT s.student_id, s.first_name, s.last_name, sec.grade_level, sec.section, s.section_id
        FROM students s 
        INNER JOIN section sec ON s.section_id = sec.id 
        WHERE s.rfid_tag = %s AND sec.grade_level = %s AND sec.section = %s
        """
        cursor.execute(query, (uid, TARGET_GRADE, TARGET_SECTION))
        result = cursor.fetchone()
        
        if result:
            # ADDITIONAL VALIDATION: If subject is specified, verify it exists for this section
            if TARGET_SUBJECT and TARGET_SUBJECT.strip():
                subject_query = """
                SELECT id FROM subjects 
                WHERE section_id = %s AND subject_name = %s
                """
                cursor.execute(subject_query, (result[5], TARGET_SUBJECT))  # Use result[5] which is section_id
                subject_result = cursor.fetchone()
                
                if subject_result:
                    print(f"✅ AUTHORIZED: {result[1]} {result[2]} from Grade {result[3]} - {result[4]} for subject {TARGET_SUBJECT}")
                    print(f"✅ Section ID: {result[5]}, Subject validated")
                    return True
                else:
                    print(f"❌ SUBJECT VALIDATION FAILED: Subject '{TARGET_SUBJECT}' not assigned to Grade {TARGET_GRADE} - {TARGET_SECTION}")
                    print(f"❌ Student {result[1]} {result[2]} is in correct section but subject not available")
                    return False
            else:
                print(f"✅ AUTHORIZED: {result[1]} {result[2]} from Grade {result[3]} - {result[4]} (no subject filter)")
                return True
        else:
            # Student exists but not in correct section - EXPLICIT REJECTION
            if debug_result:
                print(f"❌ SECTION VALIDATION FAILED: {debug_result[1]} {debug_result[2]} is from Grade {debug_result[5]} - {debug_result[6]}")
                print(f"❌ REQUIRED: Must be Grade {TARGET_GRADE} - {TARGET_SECTION} to access this scanner")
                print(f"❌ ATTENDANCE WILL NOT BE RECORDED for this student")
            else:
                print(f"❌ STUDENT NOT FOUND: UID {uid} not registered in any section")
            return False
            
    except Error as e:
        print(f"❌ Database error: {e}")
        return False
    finally:
        if connection and connection.is_connected():
            cursor.close()
            connection.close()

def check_uid_in_log(uid, log_file):
    """Check if UID exists in a log file."""
    if not os.path.exists(log_file):
        return False
    with open(log_file, "r") as f:
        for line in f:
            if line.strip().startswith(f"{uid},"):
                return True
    return False

def get_student_info(uid):
    """Get student information from database."""
    try:
        connection = mysql.connector.connect(**DB_CONFIG)
        cursor = connection.cursor()
        
        query = """
        SELECT s.first_name, s.last_name, s.lrn, sec.grade_level, sec.section
        FROM students s 
        INNER JOIN section sec ON s.section_id = sec.id 
        WHERE s.rfid_tag = %s AND sec.grade_level = %s AND sec.section = %s
        """
        cursor.execute(query, (uid, TARGET_GRADE, TARGET_SECTION))
        result = cursor.fetchone()
        
        if result:
            return {
                'first_name': result[0],
                'last_name': result[1],
                'lrn': result[2],
                'grade_level': result[3],
                'section': result[4]
            }
        else:
            return {
                'first_name': 'Unknown',
                'last_name': 'Student',
                'lrn': '',
                'grade_level': TARGET_GRADE,
                'section': TARGET_SECTION
            }
            
    except Error as e:
        print(f"❌ Error getting student info: {e}")
        return {
            'first_name': 'Unknown',
            'last_name': 'Student',
            'lrn': '',
            'grade_level': TARGET_GRADE,
            'section': TARGET_SECTION
        }
    finally:
        if connection and connection.is_connected():
            cursor.close()
            connection.close()





def rollover_day():
    """When day changes, reset trackers."""
    global current_date, last_tap_times, exit_logged_uids, attendance_logged_uids
    manila_now = get_manila_time()
    new_date = manila_now.strftime('%Y-%m-%d')
    print(f"🔄 DAY ROLLOVER: {current_date} -> {new_date}")
    print(f"🔄 Resetting all tracking data for new day")
    last_tap_times = {}
    exit_logged_uids = set()  # Reset exit log tracking for new day
    attendance_logged_uids = set()  # Reset attendance tracking for new day
    current_date = new_date



def cleanup_on_exit():
    """Clean up resources on program exit."""
    # Delete subject file on exit to ensure clean state (if it exists)
    if showrfid_file and os.path.exists(showrfid_file):
        try:
            os.remove(showrfid_file)
        except:
            pass

atexit.register(cleanup_on_exit)

def update_config_if_needed():
    """Update configuration if it has changed."""
    global TARGET_GRADE, TARGET_SECTION, TARGET_SUBJECT, EXIT_LOGGING, showrfid_file, last_config_reload
    
    timestamp = time.time()
    if timestamp - last_config_reload > 5:
        new_grade, new_section, new_subject, new_exit_logging = load_section_config()
        if (new_grade != TARGET_GRADE or new_section != TARGET_SECTION or 
            new_subject != TARGET_SUBJECT or new_exit_logging != EXIT_LOGGING):
            print(f"🔄 Config changing from Grade {TARGET_GRADE} - {TARGET_SECTION} - {TARGET_SUBJECT} - Exit: {EXIT_LOGGING}")
            print(f"🔄 To Grade {new_grade} - {new_section} - {new_subject} - Exit: {new_exit_logging}")
            
            # Update global variables
            TARGET_GRADE = new_grade
            TARGET_SECTION = new_section
            TARGET_SUBJECT = new_subject
            EXIT_LOGGING = new_exit_logging
            
            # Update file path when subject changes
            showrfid_file = get_subject_file_path(TARGET_SUBJECT)
            
            print(f"✅ Config updated to Grade {TARGET_GRADE} - {TARGET_SECTION} - Subject: {TARGET_SUBJECT} - Exit Logging: {EXIT_LOGGING}")
            if showrfid_file:
                print(f"✅ File path updated to: {showrfid_file}")
            else:
                print(f"⚠️ No subject assigned - no subject file will be created")
        last_config_reload = timestamp

while True:
    now = get_manila_time()
    date_str = now.strftime("%Y-%m-%d")
    time_str = now.strftime("%H:%M:%S")
    timestamp = now.timestamp()

    # Reload config every 5 seconds to check for changes
    update_config_if_needed()

    # Day rollover check
    if date_str != current_date:
        rollover_day()

    if arduino.in_waiting > 0:
        uid = arduino.readline().decode(errors="ignore").strip()
        
        if uid.startswith("UID:"):
            uid = uid.replace("UID:", "").strip()
        if not uid:
            continue

        # Clean UID - remove any unwanted text and ensure it's a valid RFID format
        uid = uid.strip()
        # Remove common unwanted text patterns
        unwanted_patterns = ["Place your", "place your", "PLACE YOUR", "UID:", "uid:"]
        for pattern in unwanted_patterns:
            uid = uid.replace(pattern, "").strip()
        
        # Skip if UID is empty or too short after cleaning
        if len(uid) < 8:  # Minimum length for a valid RFID UID
            continue
            
        # Check if we're in exit logging mode FIRST (before cooldown check)
        if EXIT_LOGGING.lower() == 'true':
            print(f"🚪 EXIT LOGGING MODE: UID {uid}")
            print(f"🎯 EXIT LOGGING: Grade {TARGET_GRADE} - {TARGET_SECTION}")
            
            # Check if this UID has already been logged for exit today
            if uid in exit_logged_uids:
                print(f"⚠️ EXIT LOGGING SKIPPED: UID {uid} already logged today")
                print(f"⚠️ REASON: Duplicate prevention - only first tap per day is recorded")
                print(f"⚠️ DISPLAY WILL STILL WORK but no new log entry created")
                print("-" * 80)
                # Still create display files for visual feedback, but don't log to CSV
                try:
                    student_info = get_student_info(uid)
                    student_name = f"{student_info.get('first_name', '')} {student_info.get('last_name', '')}".strip()
                    
                    # Create display files for visual feedback (but don't log to CSV)
                    exit_file = get_exit_logging_file_path()
                    with open(exit_file, "w") as f:
                        f.write(f"{uid}|{now.strftime('%Y-%m-%d %H:%M:%S')}|EXIT|{student_name}")
                    print(f"✅ EXIT DISPLAY UPDATED: UID {uid} - {student_name} at {now.strftime('%Y-%m-%d %H:%M:%S')}")
                    
                    # Create detailed exit logging display file for PHP to read
                    exit_display_file = f"C:/xampp/htdocs/SOFTDEV/exit_display.txt"
                    with open(exit_display_file, "w") as f:
                        f.write(f"{uid}|{now.strftime('%Y-%m-%d %H:%M:%S')}|{student_name}|{TARGET_GRADE}|{TARGET_SECTION}|EXIT")
                    print(f"✅ Exit display file updated: {exit_display_file}")
                    
                    # Schedule file deletion after 5 seconds
                    delete_file_after_delay(exit_file, 5)
                    delete_file_after_delay(exit_display_file, 5)
                    
                except Exception as e:
                    print(f"❌ Error creating exit display files: {e}")
                
                print("-" * 80)
                continue  # Skip CSV logging but allow display
            
            # Validate student for exit logging (same validation as attendance)
            if not validate_student_section(uid):
                print(f"🚫 EXIT LOGGING BLOCKED: UID {uid} - ACCESS DENIED")
                print(f"🚫 REASON: Student not authorized for Grade {TARGET_GRADE} - {TARGET_SECTION}")
                print(f"🚫 NO EXIT LOGGING WILL BE RECORDED")
                print("-" * 80)
                continue  # Skip this scan entirely
            
            # Record exit logging for verified students
            try:
                # Get student information for detailed logging
                student_info = get_student_info(uid)
                student_name = f"{student_info.get('first_name', '')} {student_info.get('last_name', '')}".strip()
                
                # Create exit logging file with detailed information
                exit_file = get_exit_logging_file_path()
                with open(exit_file, "w") as f:
                    f.write(f"{uid}|{now.strftime('%Y-%m-%d %H:%M:%S')}|EXIT|{student_name}")
                print(f"✅ EXIT LOGGING RECORDED: UID {uid} - {student_name} at {now.strftime('%Y-%m-%d %H:%M:%S')}")
                print(f"✅ Exit file created: {exit_file}")
                
                # Record to CSV file for exit logging with detailed information
                exit_log_file = f"C:/xampp/htdocs/SOFTDEV/logs/exitrfid_log_{date_str}.csv"
                os.makedirs(os.path.dirname(exit_log_file), exist_ok=True)
                if not os.path.exists(exit_log_file):
                    with open(exit_log_file, "w") as f:
                        f.write("UID,Student_Name,Date,Time,Type,Grade,Section\n")
                
                with open(exit_log_file, "a") as f:
                    f.write(f"{uid},{student_name},{date_str},{time_str},EXIT,{TARGET_GRADE},{TARGET_SECTION}\n")
                
                print(f"✅ Exit log file: {exit_log_file}")
                
                # Create a detailed exit logging display file for PHP to read
                exit_display_file = f"C:/xampp/htdocs/SOFTDEV/exit_display.txt"
                with open(exit_display_file, "w") as f:
                    f.write(f"{uid}|{now.strftime('%Y-%m-%d %H:%M:%S')}|{student_name}|{TARGET_GRADE}|{TARGET_SECTION}|EXIT")
                print(f"✅ Exit display file created: {exit_display_file}")
                
                # Schedule file deletion after 5 seconds
                delete_file_after_delay(exit_file, 5)
                delete_file_after_delay(exit_display_file, 5)
                
                # Mark this UID as logged for today to prevent duplicates
                exit_logged_uids.add(uid)
                print(f"✅ UID {uid} added to exit log tracking for today")
                
                # Send email notification for exit logging
                send_exit_email_notification(uid, TARGET_GRADE, TARGET_SECTION, student_name)
                
            except Exception as e:
                print(f"❌ Error creating exit logging file: {e}")
            
            print("-" * 80)
            continue  # Skip normal attendance processing in exit logging mode
        
        # Prevent duplicate taps within 10 seconds (cooldown) - only for attendance logging
        if uid in last_tap_times and (timestamp - last_tap_times[uid]) < 10:
            continue
        
        # CRITICAL VALIDATION: Only students from target section allowed
        print(f"🔍 VALIDATING STUDENT: UID {uid}")
        print(f"🎯 SCANNER RESTRICTION: Grade {TARGET_GRADE} - {TARGET_SECTION} - Subject: {TARGET_SUBJECT}")
        
        if not validate_student_section(uid):
            print(f"🚫 ATTENDANCE BLOCKED: UID {uid} - ACCESS DENIED")
            print(f"🚫 REASON: Student not authorized for Grade {TARGET_GRADE} - {TARGET_SECTION}")
            print(f"🚫 NO ATTENDANCE WILL BE RECORDED")
            print(f"🚫 NO FILES WILL BE CREATED")
            print("-" * 80)
            continue  # Skip this scan entirely - no file creation, no logging, no attendance
        
        # Double-check validation (extra security)
        try:
            connection = mysql.connector.connect(**DB_CONFIG)
            cursor = connection.cursor()
            verify_query = """
            SELECT COUNT(*) FROM students s 
            INNER JOIN section sec ON s.section_id = sec.id 
            WHERE s.rfid_tag = %s AND sec.grade_level = %s AND sec.section = %s
            """
            cursor.execute(verify_query, (uid, TARGET_GRADE, TARGET_SECTION))
            count = cursor.fetchone()[0]
            cursor.close()
            connection.close()
            
            if count != 1:
                print(f"🚫 DOUBLE-CHECK FAILED: UID {uid} not valid for Grade {TARGET_GRADE} - {TARGET_SECTION}")
                continue
                
        except Error as e:
            print(f"Verification error: {e}")
            continue
        
        last_tap_times[uid] = timestamp

        # Check if subject is assigned
        if not TARGET_SUBJECT or TARGET_SUBJECT.strip() == '':
            print(f"⚠️ NO SUBJECT ASSIGNED: UID {uid} - Cannot record attendance")
            print(f"⚠️ Please assign a subject in the configuration")
            print(f"⚠️ Display will show 'Choose Subject First.' message")
            
            # Create a display file with "Choose Subject First." message
            try:
                display_file = "C:/xampp/htdocs/SOFTDEV/display_message.txt"
                with open(display_file, "w") as f:
                    f.write("Choose Subject First.")
                print(f"✅ Display message created: {display_file}")
                delete_file_after_delay(display_file, 5)
            except Exception as e:
                print(f"❌ Error creating display message: {e}")
            
            print("-" * 80)
            continue  # Skip attendance recording
        
        # Record attendance ONLY for verified students in target section and subject
        subject_name = TARGET_SUBJECT
        
        # Check if this UID has already been logged for attendance today for this subject
        attendance_key = f"{uid}_{subject_name}_{current_date}"
        if attendance_key in attendance_logged_uids:
            print(f"⚠️ ATTENDANCE SKIPPED: UID {uid} already logged today for {subject_name}")
            print(f"⚠️ REASON: Duplicate prevention - only first tap per day per subject is recorded")
            print(f"⚠️ DISPLAY WILL STILL WORK but no new attendance record created")
            print("-" * 80)
            
            # Still create display files for visual feedback, but don't log to database/CSV
            if showrfid_file:
                try:
                    with open(showrfid_file, "w") as f:
                        f.write(f"{uid}|{now.strftime('%Y-%m-%d %H:%M:%S')}|{subject_name}")
                    print(f"✅ ATTENDANCE DISPLAY UPDATED: UID {uid} - {subject_name} at {now.strftime('%Y-%m-%d %H:%M:%S')}")
                    print(f"✅ File created: {showrfid_file}")
                    delete_file_after_delay(showrfid_file, 5)
                except Exception as e:
                    print(f"❌ Error creating display file: {e}")
            else:
                print(f"⚠️ No subject file created - no subject assigned")
            
            print("-" * 80)
            continue  # Skip attendance recording but allow display
        
        # 1. Record to database attendance_logs table (PRIMARY attendance record)
        try:
            connection = mysql.connector.connect(**DB_CONFIG)
            cursor = connection.cursor()
            
            # Insert attendance record with section validation
            insert_query = """
            INSERT INTO attendance_logs (rfid_uid, subject, scan_time, created_at)
            SELECT %s, %s, %s, %s
            WHERE EXISTS (
                SELECT 1 FROM students s 
                INNER JOIN section sec ON s.section_id = sec.id 
                WHERE s.rfid_tag = %s AND sec.grade_level = %s AND sec.section = %s
            )
            """
            
            scan_datetime = now.strftime('%Y-%m-%d %H:%M:%S')
            cursor.execute(insert_query, (
                uid, subject_name, scan_datetime, scan_datetime,
                uid, TARGET_GRADE, TARGET_SECTION
            ))
            
            if cursor.rowcount > 0:
                print(f"✅ ATTENDANCE RECORDED: UID {uid} for {subject_name} at {scan_datetime}")
                print(f"✅ Database record created for Grade {TARGET_GRADE} - {TARGET_SECTION}")
            else:
                print(f"❌ ATTENDANCE FAILED: Student not in target section Grade {TARGET_GRADE} - {TARGET_SECTION}")
                cursor.close()
                connection.close()
                continue  # Skip file creation if DB record failed
            
            cursor.close()
            connection.close()
            
        except Error as e:
            print(f"❌ Database attendance error: {e}")
            continue  # Skip if database attendance fails
        
        # 2. Record to CSV file (BACKUP/AUDIT record - only if DB succeeded)
        subject_key = subject_name.lower().replace(' ', '_').replace('-', '_')
        entry_file = f"C:/xampp/htdocs/SOFTDEV/logs/{subject_key}rfid_log_{date_str}.csv"
        os.makedirs(os.path.dirname(entry_file), exist_ok=True)
        if not os.path.exists(entry_file):
            with open(entry_file, "w") as f:
                f.write("UID,Date,Time,Subject\n")
        
        # Write to CSV only after successful database record
        with open(entry_file, "a") as f:
            f.write(f"{uid},{date_str},{time_str},{subject_name}\n")
        
        # Mark this UID as logged for today for this subject to prevent duplicates
        attendance_logged_uids.add(attendance_key)
        print(f"✅ UID {uid} added to attendance tracking for {subject_name} on {current_date}")
        
        # Send email notification for attendance confirmation
        send_attendance_email_notification(uid, subject_name, TARGET_GRADE, TARGET_SECTION)
        
        # Only create subject-specific file for authorized students (if subject is assigned)
        if showrfid_file:
            print(f"🔧 DEBUG: About to create file at: {showrfid_file}")
            print(f"🔧 DEBUG: Subject name: '{subject_name}', TARGET_SUBJECT: '{TARGET_SUBJECT}'")
            
            try:
                with open(showrfid_file, "w") as f:
                    f.write(f"{uid}|{now.strftime('%Y-%m-%d %H:%M:%S')}|{subject_name}")
                print(f"✅ SCAN AUTHORIZED: UID {uid} successfully processed for {subject_name}")
                print(f"✅ File created: {showrfid_file}")
                print(f"✅ File exists check: {os.path.exists(showrfid_file)}")
                print(f"✅ File will be deleted in 5 seconds")
                
                # Read back the file content to verify
                if os.path.exists(showrfid_file):
                    with open(showrfid_file, "r") as f:
                        content = f.read()
                        print(f"✅ File content: {content}")
                
                # Schedule file deletion after 5 seconds
                delete_file_after_delay(showrfid_file, 5)
                
            except Exception as e:
                print(f"❌ Error creating file {showrfid_file}: {e}")
        else:
            print(f"⚠️ No subject file created - no subject assigned")
        
        print(f"✅ Log file: {entry_file}")






