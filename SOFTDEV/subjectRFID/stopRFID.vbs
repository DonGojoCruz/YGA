Set WshShell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")

' Get the directory where this VBS script is located
scriptDir = fso.GetParentFolderName(WScript.ScriptFullName)

' Change to the script directory and run Python script in background
WshShell.CurrentDirectory = scriptDir
WshShell.Run "python stopRFID.py", 0, False
