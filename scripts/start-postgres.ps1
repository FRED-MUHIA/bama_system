$pgRoot = 'C:\PostgreSQL\postgresql-17.11.0-x86_64-pc-windows-msvc'
$dataDir = Join-Path $pgRoot 'data'
$logFile = Join-Path $pgRoot 'postgresql.log'
$pgCtl = Join-Path $pgRoot 'bin\pg_ctl.exe'

& $pgCtl -D $dataDir -l $logFile -w start
