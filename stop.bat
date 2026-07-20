@echo off
echo ========================================================
echo Stopping Research Project System Local Server...
echo ========================================================
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :8000') do (
    taskkill /F /PID %%a
    echo Stopped server process with PID %%a
)
exit
