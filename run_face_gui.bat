@echo off
chcp 65001 > nul
title CYBER SECURITY - BIOMETRIC FACE ENGINE & FEATURE CLASSIFIER

echo ===================================================================
echo   HET HONG GIAM DINH SINH TRAC HOC - CYBER BIOMETRIC ENGINE v3.0
echo ===================================================================
echo.
echo [INFO] Dang khoi chay giao dien Cyberpunk Biometric HUD GUI...

cd /d "%~dp0"

where python >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERROR] Khong tim thay Python tren he thong! Vui long cai dat Python.
    echo.
    pause
    exit /b 1
)

python face_feature_classifier.py --gui

if %errorlevel% neq 0 (
    echo.
    echo [WARNING] Ung dung da thoat voi ma loi %errorlevel%.
    pause
)
