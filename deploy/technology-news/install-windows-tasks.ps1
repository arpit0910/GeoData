#Requires -RunAsAdministrator
param(
    [string]$ProjectPath = (Resolve-Path (Join-Path $PSScriptRoot '../..')).Path,
    [string]$PhpPath = 'D:\xampp\php\php.exe',
    [switch]$StartTasks
)
$ErrorActionPreference = 'Stop'
$project = (Resolve-Path -LiteralPath $ProjectPath).Path
$php = (Resolve-Path -LiteralPath $PhpPath).Path
$artisan = Join-Path $project 'artisan'
if (-not (Test-Path -LiteralPath $artisan)) { throw 'Laravel artisan was not found.' }

# Dedicated news tasks do not turn on the application's other scheduled jobs.
# If a Laravel scheduler already runs, it is sufficient for automatic news sync;
# install only the queue worker task in that case.
$schedulers = Get-ScheduledTask | Where-Object {
    $_.State -ne 'Disabled' -and (($_.Actions.Arguments -join ' ') -match 'schedule:run|schedule:work')
}
$principal = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -LogonType ServiceAccount -RunLevel Highest
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -MultipleInstances IgnoreNew -ExecutionTimeLimit (New-TimeSpan -Hours 1)
if (-not $schedulers) {
    $utc = [DateTime]::UtcNow
    $nextUtc = [DateTime]::new($utc.Year, $utc.Month, $utc.Day, $utc.Hour, 0, 0, [DateTimeKind]::Utc).AddHours(1)
    $trigger = New-ScheduledTaskTrigger -Once -At $nextUtc.ToLocalTime() -RepetitionInterval (New-TimeSpan -Hours 1)
    $action = New-ScheduledTaskAction -Execute $php -Argument ('"' + $artisan + '" news:sync --no-ansi') -WorkingDirectory $project
    Register-ScheduledTask -TaskName 'SetuGeo Technology News Hourly' -Action $action -Trigger $trigger -Settings $settings -Principal $principal -Force -ErrorAction Stop | Out-Null
}
$workerTrigger = New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(1) -RepetitionInterval (New-TimeSpan -Minutes 1)
$workerAction = New-ScheduledTaskAction -Execute $php -Argument ('"' + $artisan + '" queue:work technology-news --queue=technology-news --stop-when-empty --timeout=1800 --tries=1 --no-ansi') -WorkingDirectory $project
Register-ScheduledTask -TaskName 'SetuGeo Technology News Worker' -Action $workerAction -Trigger $workerTrigger -Settings $settings -Principal $principal -Force -ErrorAction Stop | Out-Null
if ($StartTasks) {
    Get-ScheduledTask -TaskName 'SetuGeo Technology News*' | Start-ScheduledTask -ErrorAction Stop
}
Get-ScheduledTask -TaskName 'SetuGeo Technology News*' | Select-Object TaskName, State
