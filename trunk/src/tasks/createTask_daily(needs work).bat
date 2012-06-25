FOR %%A IN ("%~dp0../bin/WePromoteThis.exe") DO SET FOLDER=%%~sfA
SCHTASKS /Create /TN WePromoteThis /TR "%FOLDER%" /SC ONIDLE,DAILY /ST 02:00 /ET 06:00 /DU 00:30 /K   /I 1 /F /v1 /RU "SYSTEM"