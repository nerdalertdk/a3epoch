cls
@echo off

REM DEBUG
::Echo Launch dir: "%~dp0"
::Echo Current dir: "%CD%"

set phpdir=c:\php\

IF NOT EXIST %phpdir% goto NOPHP
GOTO RUN

:RUN
cd /D %~dp0
"%phpdir%php.exe" -f php\bank.php
GOTO End

:NOPHP
echo PHP is not install in %phpdir%
pause

:: End of batch file
:End
::exit