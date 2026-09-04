# Build a WordPress-installable low-mega-menu.zip (no node_modules / .git / tests).
# Usage (from plugin root):
#   .\bin\package-release.ps1
#   .\bin\package-release.ps1 -SkipBuild
#   .\bin\package-release.ps1 -OutDir "$env:USERPROFILE\Desktop"

param(
	[switch] $SkipBuild,
	[string] $OutDir = (Join-Path $env:TEMP 'low-mm-release')
)

$ErrorActionPreference = 'Stop'
$pluginRoot = Split-Path -Parent $PSScriptRoot
Set-Location $pluginRoot

if ( -not $SkipBuild ) {
	Write-Host 'Installing deps and building assets...'
	npm install
	npm install --prefix admin-app
	if ( -not ( Test-Path 'vendor\autoload.php' ) ) {
		composer install --no-dev --optimize-autoloader
	}
	npm run build:all
}

$required = @(
	'public\build\main.css',
	'public\build\controller.js',
	'admin-app\build\index.js',
	'vendor\autoload.php'
)
foreach ( $path in $required ) {
	if ( -not ( Test-Path $path ) ) {
		throw "Missing required build artifact: $path (run without -SkipBuild)"
	}
}

$stageRoot = Join-Path $OutDir 'stage'
$pluginDir = Join-Path $stageRoot 'low-mega-menu'
$zipPath = Join-Path $OutDir 'low-mega-menu.zip'

if ( Test-Path $stageRoot ) { Remove-Item -Recurse -Force $stageRoot }
if ( Test-Path $zipPath ) { Remove-Item -Force $zipPath }
New-Item -ItemType Directory -Path $pluginDir -Force | Out-Null

$excludeNames = @(
	'.git',
	'node_modules',
	'tests',
	'bin',
	'.gitignore',
	'.gitattributes',
	'package.json',
	'package-lock.json',
	'composer.json',
	'composer.lock',
	'CHANGELOG.md'
)

Get-ChildItem -Force | ForEach-Object {
	$name = $_.Name
	if ( $excludeNames -contains $name ) { return }

	if ( $name -eq 'admin-app' ) {
		$destAdmin = Join-Path $pluginDir 'admin-app'
		New-Item -ItemType Directory -Path $destAdmin | Out-Null
		# Ship only the built admin bundle — not src / node_modules / lockfiles.
		Copy-Item ( Join-Path $_.FullName 'build' ) -Destination ( Join-Path $destAdmin 'build' ) -Recurse -Force
		return
	}

	if ( $name -eq 'public' ) {
		$destPublic = Join-Path $pluginDir 'public'
		New-Item -ItemType Directory -Path $destPublic | Out-Null
		Copy-Item ( Join-Path $_.FullName 'build' ) -Destination ( Join-Path $destPublic 'build' ) -Recurse -Force
		return
	}

	Copy-Item $_.FullName -Destination ( Join-Path $pluginDir $name ) -Recurse -Force
}

Compress-Archive -Path $pluginDir -DestinationPath $zipPath -Force

$sizeMb = [math]::Round( ( Get-Item $zipPath ).Length / 1MB, 2 )
Write-Host "Created $zipPath ($sizeMb MB)"
Write-Host 'Root folder inside ZIP: low-mega-menu/'
