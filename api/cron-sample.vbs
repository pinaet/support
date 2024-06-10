Set WshShell = CreateObject("WScript.Shell")
WshShell.Run chr(34) & "E:\histest\support\api\cron.bat" & Chr(34), 0
Set WshShell = Nothing
