# Daily database backup runner for FoodDash
param(
	[string]$ProjectRoot = "C:\xampp\htdocs\FoodDash",
	[string]$Label = "daily"
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

if (-not (Test-Path -Path $ProjectRoot)) {
	throw "Project root was not found: $ProjectRoot"
}

Push-Location $ProjectRoot
try {
	& php spark db:backup --label=$Label --by=scheduler
	if ($LASTEXITCODE -ne 0) {
		throw "Database backup command failed with exit code $LASTEXITCODE"
	}
}
finally {
	Pop-Location
}
