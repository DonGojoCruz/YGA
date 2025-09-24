import serial
import serial.tools.list_ports
import time
from datetime import datetime
import os
import threading

# Text file to save RFID UIDs (in parent directory for web access)
output_file = "../manage.txt"

# Auto-detect Arduino COM port
def find_arduino_port():
    """Find Arduino COM port automatically."""
    ports = list(serial.tools.list_ports.comports())
    arduino_port = None
    
    for p in ports:
        if "Arduino" in p.description or "CH340" in p.description:
            arduino_port = p.device
            print(f"✅ Arduino found on port: {arduino_port}")
            break
    
    if not arduino_port:
        print("❌ Arduino not found. Available ports:")
        for p in ports:
            print(f"   - {p.device}: {p.description}")
        return None
    
    return arduino_port

# Initialize text file 
def initialize_file():
    """Create or reset the output text file."""
    try:
        with open(output_file, "w") as f:
            f.write("")  # Empty file
        print(f"✅ Output file initialized: {output_file}")
    except Exception as e:
        print(f"❌ Error initializing file: {e}")

# Clean and validate UID
def clean_uid(raw_uid):
    """Clean and validate the RFID UID."""
    uid = raw_uid.strip()
    
    # Remove common prefixes
    if uid.startswith("UID:"):
        uid = uid.replace("UID:", "").strip()
    
    # Remove unwanted text patterns
    unwanted_patterns = ["Place your", "place your", "PLACE YOUR", "UID:", "uid:", 
                        "RFID card/tag near the reader", "rfid card/tag near the reader",
                        "card/tag near the reader", "near the reader"]
    for pattern in unwanted_patterns:
        uid = uid.replace(pattern, "").strip()
    
    # Validate UID length (minimum 8 characters for valid RFID)
    if len(uid) < 8:
        return None
    
    return uid

# Delete file after delay
def delete_file_after_delay(delay_seconds=5):
    """Delete the output file after specified delay."""
    def delete_file():
        time.sleep(delay_seconds)
        try:
            if os.path.exists(output_file):
                os.remove(output_file)
                print(f"🗑️ File deleted after {delay_seconds} seconds: {output_file}")
        except Exception as e:
            print(f"❌ Error deleting file: {e}")
    
    # Start deletion timer in a separate thread
    deletion_thread = threading.Thread(target=delete_file, daemon=True)
    deletion_thread.start()

# Save UID to text file
def save_uid(uid):
    """Save UID to text file (overwrites previous) and schedule deletion after 5 seconds."""
    try:
        with open(output_file, "w") as f:
            f.write(uid)
        print(f"✅ UID saved: {uid}")
        print(f"⏰ File will be deleted in 5 seconds...")
        
        # Schedule file deletion after 5 seconds
        delete_file_after_delay(5)
        
    except Exception as e:
        print(f"❌ Error saving UID: {e}")

# Main function
def main():
    print("🚀 Starting RFID UID Logger...")
    
    # Find Arduino port
    arduino_port = find_arduino_port()
    if not arduino_port:
        print("❌ Cannot continue without Arduino connection.")
        return
    
    # Initialize output file
    initialize_file()
    
    # Connect to Arduino
    try:
        arduino = serial.Serial(arduino_port, 9600, timeout=1)
        time.sleep(2)  # Wait for Arduino to initialize
        print(f"✅ Connected to Arduino on {arduino_port}")
        print("📡 Waiting for RFID tags...")
        print("Press Ctrl+C to stop")
        print("-" * 50)
    except Exception as e:
        print(f"❌ Error connecting to Arduino: {e}")
        return
    
    # Track last scan time to prevent duplicates
    last_uid = ""
    last_scan_time = 0
    
    try:
        while True:
            if arduino.in_waiting > 0:
                # Read data from Arduino
                raw_data = arduino.readline().decode(errors="ignore").strip()
                
                if raw_data:
                    # Clean and validate UID
                    uid = clean_uid(raw_data)
                    
                    if uid:
                        current_time = time.time()
                        
                        # Prevent duplicate scans within 3 seconds
                        if uid != last_uid or (current_time - last_scan_time) > 3:
                            save_uid(uid)
                            last_uid = uid
                            last_scan_time = current_time
                        else:
                            print(f"⏳ Duplicate scan ignored: {uid}")
            
            # Small delay to prevent high CPU usage
            time.sleep(0.1)
            
    except KeyboardInterrupt:
        print("\n🛑 Stopping RFID scanner...")
    except Exception as e:
        print(f"❌ Error during scanning: {e}")
    finally:
        # Close serial connection
        try:
            arduino.close()
            print("✅ Arduino connection closed")
        except:
            pass
        
        print(f"✅ Log saved to: {output_file}")

if __name__ == "__main__":
    main()
