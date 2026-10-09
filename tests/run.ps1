param([string]$Php = 'php', [string]$Node = 'node')
$ErrorActionPreference = 'Stop'
$project = Split-Path -Parent $PSScriptRoot
Push-Location $project
try {
    if (-not (Get-Command $Php -ErrorAction SilentlyContinue)) {
        $Php = 'D:/laragon/bin/php/php-8.3.33-Win32-vs16-x64/php.exe'
    }
    if (-not (Get-Command $Node -ErrorAction SilentlyContinue)) {
        $Node = 'D:/laragon/bin/nodejs/node-v22/node.exe'
    }
    $failures = @()
    $files = Get-ChildItem app,config,tests -Filter '*.php' -Recurse
    foreach ($file in $files) {
        $result = & $Php -l $file.FullName 2>&1
        if ($LASTEXITCODE -ne 0) { Write-Output $result; $failures += $file.FullName }
    }
    & $Php -l public/index.php
    if ($LASTEXITCODE -ne 0) { $failures += 'public/index.php' }
    $javascript = @(Get-ChildItem tests -Filter '*.test.cjs' | ForEach-Object { $_.FullName })
    & $Node --test @javascript
    if ($LASTEXITCODE -ne 0) { $failures += 'JavaScript tests' }
    foreach ($test in @('public-home.php','ui-consistency.php','flight-availability.php','admin-dashboard.mysql.php','application.integration.php','application.functional.php','database.seed.php')) {
        & $Php ('tests/' + $test)
        if ($LASTEXITCODE -ne 0) { $failures += $test }
    }
    foreach ($scenario in @('sold-out','available','missing','database-error')) {
        # The database-error fixture deliberately writes a handled error to stderr.
        $ErrorActionPreference = 'Continue'
        & $Php tests/flight-availability.php $scenario 2>&1 | ForEach-Object { Write-Output "$_" }
        $scenarioExit = $LASTEXITCODE
        $ErrorActionPreference = 'Stop'
        if ($scenarioExit -ne 0) { $failures += ('flight-availability: ' + $scenario) }
    }
    if ($failures.Count) { Write-Output ('Failed: ' + ($failures -join ', ')); exit 1 }
    Write-Output 'All AeroBook test suites passed.'
} finally { Pop-Location }
