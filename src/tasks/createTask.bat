:: Create Scheduled task to run wepromotethis.exe when system is idle
FOR %%A IN ("%~dp0../bin/WePromoteThis.exe") DO SET FOLDER=%%~sfA
SCHTASKS /Create /TN WePromoteThis /TR "%FOLDER%" /SC ONIDLE /I 1 /F /v1 /RU "SYSTEM"
FOR %%A IN ("%~dp0/changePowerSettings.bat") DO SET POWERBAT=%%~sfA
%POWERBAT%
