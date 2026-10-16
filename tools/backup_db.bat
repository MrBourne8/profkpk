@echo off
setlocal
REM Настройте путь к mysqldump под вашу установку OSPanel
set MYSQLDUMP=D:\OSPanel\modules\database\MySQL-8.0-Win10\bin\mysqldump.exe
set DB_USER=root
set DB_PASS=
set DB_NAME=profkpk
set OUTDIR=%~dp0..\backups

if not exist "%OUTDIR%" mkdir "%OUTDIR%"
set STAMP=%date:~6,4%-%date:~3,2%-%date:~0,2%_%time:~0,2%-%time:~3,2%-%time:~6,2%
set STAMP=%STAMP: =0%
set OUTFILE=%OUTDIR%\profkpk_%STAMP%.sql

if "%DB_PASS%"=="" (
  "%MYSQLDUMP%" -u%DB_USER% --databases %DB_NAME% > "%OUTFILE%"
) else (
  "%MYSQLDUMP%" -u%DB_USER% -p%DB_PASS% --databases %DB_NAME% > "%OUTFILE%"
)

echo Backup saved to %OUTFILE%
endlocal
