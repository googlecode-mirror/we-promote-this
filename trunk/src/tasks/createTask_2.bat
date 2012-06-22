FOR %%A IN ("%~dp0createVideo.bat") DO SET FOLDER=%%~sfA
SCHTASKS /Create /TN WePromoteThis /TR "%FOLDER%" /SC ONIDLE /I 1 /F /v1 /RU "SYSTEM"