:: Change the power scheme of the system
FOR %%A IN ("%~dp0/WePromoteThis-Power-Scheme.pow") DO SET FOLDER=%%~sfA
:: Change to a balanced power scheme
powercfg -s 381b4222-f694-41f0-9685-ff5bb260df2e
:: Delete the scheme that may have been created before with GUI
powercfg -delete 2acf7215-c938-4c3c-a7dc-28a6759545e2
powercfg -import "%FOLDER%" 2acf7215-c938-4c3c-a7dc-28a6759545e2
powercfg -s 2acf7215-c938-4c3c-a7dc-28a6759545e2
