:: Change to a balanced power scheme
powercfg -s 381b4222-f694-41f0-9685-ff5bb260df2e
:: Delete the power scheme that was put on the system
powercfg -delete 2acf7215-c938-4c3c-a7dc-28a6759545e2
:: Delete the scheduled task that was created
SCHTASKS /Delete /F /TN WePromoteThis
