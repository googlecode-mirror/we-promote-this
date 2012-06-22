::SET "PATH=%~dp0"
FOR %%A IN ("%~dp0..\bin\WePromoteThis.exe") DO SET FOLDER=%%~sfA
START /MIN /B /I /WAIT "We Promote This - Video Creator" "%FOLDER%"