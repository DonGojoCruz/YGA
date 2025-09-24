import os
import subprocess
import sys


def collect_processes():
	"""Return list of (pid:int, cmdline:str) for running processes.

	Tries WMIC first, then PowerShell CIM as a fallback.
	"""
	processes = []

	# Try WMIC (available on many Windows systems)
	try:
		output = subprocess.check_output(
			["wmic", "process", "get", "ProcessId,CommandLine"],
			stderr=subprocess.STDOUT,
			text=True,
		)
		for line in output.splitlines():
			line = line.strip()
			if not line or line.lower().startswith("commandline"):
				continue
			# Extract PID from the end of the line (wmic aligns columns; PID is last token)
			parts = line.rsplit(" ", 1)
			if len(parts) != 2:
				continue
			cmdline, pid_str = parts[0].strip(), parts[1].strip()
			if not pid_str.isdigit():
				continue
			processes.append((int(pid_str), cmdline))
		return processes
	except Exception:
		processes = []

	# Fallback: PowerShell CIM query
	try:
		ps_cmd = (
			"Get-CimInstance Win32_Process | "
			"Select-Object ProcessId,CommandLine | "
			"ForEach-Object { ($_.ProcessId.ToString() + '\t' + ($_.CommandLine -replace '\r|\n','')) }"
		)
		output = subprocess.check_output(
			["powershell", "-NoProfile", "-Command", ps_cmd],
			stderr=subprocess.STDOUT,
			text=True,
		)
		for line in output.splitlines():
			line = line.strip()
			if not line:
				continue
			if "\t" not in line:
				# Sometimes CommandLine can be empty; handle lines with only PID
				pid_str = line.strip()
				cmdline = ""
			else:
				pid_str, cmdline = line.split("\t", 1)
			if pid_str.isdigit():
				processes.append((int(pid_str), cmdline))
		return processes
	except Exception:
		return []


def kill_pid(pid):
	"""Force kill a PID using taskkill for reliability on Windows."""
	try:
		subprocess.run(["taskkill", "/PID", str(pid), "/F"], capture_output=True, text=True)
	except Exception:
		pass


def main():
	targets = {"displayrfid.py", "txtrfid.py"}
	self_pid = os.getpid()
	procs = collect_processes()
	if not procs:
		print("No process list available or no matching processes found.")
		return 0

	killed = []
	for pid, cmdline in procs:
		if pid == self_pid:
			continue
		low = (cmdline or "").lower()
		if any(target in low for target in targets):
			kill_pid(pid)
			killed.append((pid, cmdline))

	if killed:
		print("Terminated processes:")
		for pid, cmd in killed:
			print(f"- PID {pid}: {cmd}")
		return 0
	else:
		print("No running displayRFID.py or txtRFID.py processes found.")
		return 0


if __name__ == "__main__":
	sys.exit(main())


