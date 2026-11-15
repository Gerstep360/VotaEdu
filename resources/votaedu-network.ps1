param(
    [Parameter(Mandatory = $true)][string]$PhpPath,
    [Parameter(Mandatory = $true)][ValidateRange(1024, 65535)][int]$Port
)

$ErrorActionPreference = 'Stop'
try {
    $PhpPath = (Resolve-Path -LiteralPath $PhpPath).Path
    $hash = [System.Security.Cryptography.SHA256]::Create()
    try {
        $key = [BitConverter]::ToString($hash.ComputeHash([Text.Encoding]::UTF8.GetBytes($PhpPath.ToLowerInvariant()))).Replace('-', '').Substring(0, 12)
    } finally {
        $hash.Dispose()
    }
    $ruleName = "VotaEdu-LAN-$key-$Port"
    $rule = Get-NetFirewallRule -Name $ruleName -ErrorAction SilentlyContinue
    if ($rule -and $rule.Enabled -eq 'True' -and $rule.Action -eq 'Allow' -and $rule.Direction -eq 'Inbound' -and $rule.Profile -eq 'Any' -and $rule.EdgeTraversalPolicy -eq 'Block') {
        $program = $rule | Get-NetFirewallApplicationFilter
        $ports = $rule | Get-NetFirewallPortFilter
        $addresses = $rule | Get-NetFirewallAddressFilter
        if ($program.Program -eq $PhpPath -and $ports.LocalPort -eq "$Port" -and $ports.Protocol -eq 'TCP' -and ($addresses.RemoteAddress -join ',') -eq 'LocalSubnet') {
            exit 0
        }
    }

    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($identity)
    if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
        $escapedScript = $PSCommandPath.Replace("'", "''")
        $escapedPhp = $PhpPath.Replace("'", "''")
        $invocation = "& '$escapedScript' -PhpPath '$escapedPhp' -Port $Port"
        $encoded = [Convert]::ToBase64String([Text.Encoding]::Unicode.GetBytes($invocation))
        $elevated = Start-Process -FilePath "$PSHOME/powershell.exe" -Verb RunAs -WindowStyle Hidden -ArgumentList @('-NoProfile', '-NonInteractive', '-ExecutionPolicy', 'Bypass', '-EncodedCommand', $encoded) -Wait -PassThru
        exit $elevated.ExitCode
    }

    if ($rule) {
        Remove-NetFirewallRule -Name $ruleName
    }
    New-NetFirewallRule -Name $ruleName -DisplayName "VotaEdu - red local (TCP $Port)" -Description 'Permite VotaEdu solamente desde la red local, para este PHP y puerto.' -Direction Inbound -Action Allow -Enabled True -Profile Any -Protocol TCP -LocalPort $Port -Program $PhpPath -RemoteAddress LocalSubnet -EdgeTraversalPolicy Block | Out-Null
    exit 0
} catch {
    Write-Error "No se pudo autorizar VotaEdu en la red local: $($_.Exception.Message)" -ErrorAction Continue
    exit 1
}
