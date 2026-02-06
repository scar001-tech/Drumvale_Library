@echo off
title Setup Drumvale Library for PHP Desktop
echo ========================================
echo  PHP Desktop Setup Helper
echo  Drumvale Library Management System
echo ========================================
echo.

REM Check if phpdesktop-config.json exists
if not exist "phpdesktop-config.json" (
    echo ERROR: phpdesktop-config.json not found!
    echo Make sure you're running this from the project directory.
    pause
    exit /b 1
)

echo This script will help you prepare your library system for PHP Desktop.
echo.
echo Prerequisites:
echo 1. Download PHP Desktop from GitHub
echo 2. Extract it to a folder (e.g., C:\phpdesktop\)
echo.
echo What this script does:
echo - Creates a deployment folder
echo - Copies all necessary files
echo - Renames config file correctly
echo - Shows you next steps
echo.

set /p phpdesktop_path="Enter the path to your PHP Desktop folder (e.g., C:\phpdesktop\): "

REM Validate path
if not exist "%phpdesktop_path%\phpdesktop-chrome.exe" (
    echo ERROR: phpdesktop-chrome.exe not found in specified path!
    echo Please check the path and try again.
    pause
    exit /b 1
)

echo.
echo Found PHP Desktop at: %phpdesktop_path%
echo.

REM Create deployment folder
set deploy_folder=%~dp0drumvale-library-desktop
echo Creating deployment folder: %deploy_folder%
if exist "%deploy_folder%" (
    echo Removing existing deployment folder...
    rmdir /s /q "%deploy_folder%"
)
mkdir "%deploy_folder%"

REM Copy PHP Desktop files
echo Copying PHP Desktop files...
xcopy "%phpdesktop_path%\*" "%deploy_folder%\" /E /I /H /Y

REM Clear the www folder
echo Clearing default www folder...
if exist "%deploy_folder%\www" (
    rmdir /s /q "%deploy_folder%\www"
)
mkdir "%deploy_folder%\www"

REM Copy project files to www
echo Copying library system files...
xcopy "assets" "%deploy_folder%\www\assets\" /E /I /H /Y
xcopy "books" "%deploy_folder%\www\books\" /E /I /H /Y
xcopy "database" "%deploy_folder%\www\database\" /E /I /H /Y
xcopy "fines" "%deploy_folder%\www\fines\" /E /I /H /Y
xcopy "includes" "%deploy_folder%\www\includes\" /E /I /H /Y
xcopy "members" "%deploy_folder%\www\members\" /E /I /H /Y
xcopy "reports" "%deploy_folder%\www\reports\" /E /I /H /Y
xcopy "settings" "%deploy_folder%\www\settings\" /E /I /H /Y
xcopy "transactions" "%deploy_folder%\www\transactions\" /E /I /H /Y

REM Copy individual files
copy "*.php" "%deploy_folder%\www\" /Y
copy "*.md" "%deploy_folder%\www\" /Y

REM Copy and rename config file
echo Setting up configuration...
copy "phpdesktop-config.json" "%deploy_folder%\settings.json" /Y

REM Create a simple launcher
echo Creating launcher...
echo @echo off > "%deploy_folder%\Launch Drumvale Library.bat"
echo title Drumvale Library Management System >> "%deploy_folder%\Launch Drumvale Library.bat"
echo start "" "phpdesktop-chrome.exe" >> "%deploy_folder%\Launch Drumvale Library.bat"

echo.
echo ========================================
echo  Setup Complete!
echo ========================================
echo.
echo Your standalone library system is ready in:
echo %deploy_folder%
echo.
echo To run the application:
echo 1. Go to: %deploy_folder%
echo 2. Double-click "Launch Drumvale Library.bat"
echo    OR
echo    Double-click "phpdesktop-chrome.exe"
echo.
echo Login credentials:
echo Username: admin
echo Password: admin123
echo.
echo The system will automatically:
echo - Create SQLite database
echo - Insert sample data
echo - Start the application
echo.
echo To distribute to others:
echo - Zip the entire folder: %deploy_folder%
echo - Users just extract and run the launcher
echo.
echo ========================================

pause