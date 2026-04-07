# enviarproGithub.ps1 — Commit + push para GitHub (branch main)
# Sempre mostra resumo, pergunta se quer fechar a janela e pede Enter no final (a janela não some sozinha).

$ErrorActionPreference = "Continue"
# Sem Set-StrictMode: evita fechar a janela por detalhes do PowerShell com $LASTEXITCODE

$RepoRoot = if ($PSScriptRoot) { $PSScriptRoot } else { (Get-Location).Path }
Set-Location -LiteralPath $RepoRoot

# Mesmo repositório que o servidor usa em `git pull` (public_html/vendaffacil).
$RemoteUrl = "https://github.com/IsraeldasChagas/boasvendas.git"
$DefaultCommitMessage = "Atualização"

# Estado para o resumo final (sempre exibido no finally)
$script:Log = [System.Collections.Generic.List[string]]::new()
$script:DidCommit = $false
$script:PushOk = $false
$script:CommitMsgUsada = ""
$script:Cancelado = $false
$script:ErroGit = ""

function Add-Log([string]$m) { [void]$script:Log.Add($m) }

function Test-GitRepo {
    Test-Path -LiteralPath (Join-Path $RepoRoot ".git")
}

function Invoke-GitQuiet {
    param([string[]]$GitArguments)
    $old = $ErrorActionPreference
    $ErrorActionPreference = "SilentlyContinue"
    & git @GitArguments 2>&1 | Out-Host
    $ok = ($LASTEXITCODE -eq 0)
    $ErrorActionPreference = $old
    return $ok
}

# --- Fluxo principal (erros não derrubam o script; finally sempre roda) ---
try {
    Write-Host ""
    Write-Host "========== enviarproGithub ==========" -ForegroundColor Cyan
    Write-Host "Pasta: $RepoRoot" -ForegroundColor DarkGray
    Write-Host ""

    if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
        Write-Host "ERRO: Git nao esta instalado ou nao esta no PATH." -ForegroundColor Red
        Add-Log "Git nao encontrado."
        throw "sem git"
    }

    # ----- Primeira vez (como no tutorial GitHub) -----
    if (-not (Test-GitRepo)) {
        Write-Host "Nao ha repositorio Git aqui ainda." -ForegroundColor Yellow
        $init = Read-Host "Executar 'git init'? (S/N)"
        if ($init -notmatch '^[sSyY]') {
            Write-Host "Cancelado por voce." -ForegroundColor Yellow
            Add-Log "Usuario cancelou o git init."
            $script:Cancelado = $true
        }
        else {
            if (-not (Invoke-GitQuiet @("init"))) {
                $script:ErroGit = "git init falhou."
                Add-Log $script:ErroGit
            }
            else { Add-Log "git init OK." }

            $readmePath = Join-Path $RepoRoot "README.md"
            if (-not (Test-Path -LiteralPath $readmePath)) {
                $mk = Read-Host "Criar README.md com linha '# vendaffacil'? (S/N)"
                if ($mk -match '^[sSyY]') {
                    Add-Content -LiteralPath $readmePath -Value "# vendaffacil" -Encoding UTF8
                    Write-Host "README.md criado." -ForegroundColor Green
                    Add-Log "README.md criado com # vendaffacil"
                }
            }

            # Tutorial: git add README.md — aqui usamos add -A para pegar tudo (inclui README)
            if (-not (Invoke-GitQuiet @("add", "-A"))) {
                Add-Log "git add falhou."
            }
            else { Add-Log "git add -A OK." }

            $st = & git status --porcelain 2>$null
            if ($st) {
                if (Invoke-GitQuiet @("commit", "-m", "first commit")) {
                    Add-Log 'Commit "first commit" OK.'
                }
                else { Add-Log "git commit first commit falhou." }
            }
            else { Add-Log "Nada para commitar apos init." }

            Invoke-GitQuiet @("branch", "-M", "main") | Out-Null
            Add-Log "Branch main."
        }
    }

    if (-not $script:Cancelado) {

        # ----- remote origin -----
        $hasOrigin = $false
        $remotes = & git remote 2>$null
        if ($LASTEXITCODE -eq 0 -and $remotes) { $hasOrigin = $remotes -contains "origin" }

        if (-not $hasOrigin) {
            Write-Host "Adicionando remote origin -> $RemoteUrl" -ForegroundColor Cyan
            if (Invoke-GitQuiet @("remote", "add", "origin", $RemoteUrl)) {
                Add-Log "remote add origin OK."
            }
            else {
                Add-Log 'remote add falhou; talvez origin ja exista.'
            }
        }
        else {
            $cur = (& git remote get-url origin 2>$null)
            if ($cur -and $cur.Trim() -ne $RemoteUrl) {
                Write-Host "Origin atual: $cur" -ForegroundColor DarkYellow
                $chg = Read-Host "Trocar para $RemoteUrl ? (S/N)"
                if ($chg -match '^[sSyY]') {
                    Invoke-GitQuiet @("remote", "set-url", "origin", $RemoteUrl) | Out-Null
                    Add-Log "remote set-url OK."
                }
            }
            else { Add-Log "Remote origin ja configurado." }
        }

        Invoke-GitQuiet @("branch", "-M", "main") | Out-Null

        # ----- Mensagem do commit (envio): padrao ou digitar -----
        Write-Host ""
        Write-Host "--- Descricao / mensagem do commit ---" -ForegroundColor Cyan
        Write-Host "[1] Deixar PADRAO: '$DefaultCommitMessage'"
        Write-Host "[2] Eu quero DIGITAR a mensagem"
        $opc = Read-Host "Escolha 1 ou 2"

        $msg = $DefaultCommitMessage
        if ($opc -eq "2") {
            $dig = Read-Host "Digite a mensagem do commit"
            if (-not [string]::IsNullOrWhiteSpace($dig)) { $msg = $dig.Trim() }
            else {
                Write-Host "Vazio - usando padrao: $DefaultCommitMessage" -ForegroundColor Yellow
            }
        }
        $script:CommitMsgUsada = $msg

        # ----- add + commit -----
        Invoke-GitQuiet @("add", "-A") | Out-Null
        $porcelain = & git status --porcelain 2>$null
        if ([string]::IsNullOrWhiteSpace($porcelain)) {
            Write-Host ""
            Write-Host "Nenhuma alteracao nova para commitar." -ForegroundColor Yellow
            Add-Log "Sem mudancas para commit."
        }
        else {
            if (Invoke-GitQuiet @("commit", "-m", $msg)) {
                $script:DidCommit = $true
                Add-Log "Commit criado: $msg"
            }
            else {
                Add-Log "git commit falhou."
            }
        }

        # ----- push -----
        Write-Host ""
        Write-Host 'Enviando: git push -u origin main...' -ForegroundColor Cyan
        if (Invoke-GitQuiet @("push", "-u", "origin", "main")) {
            $script:PushOk = $true
            Add-Log "Push concluido."
        }
        else {
            Add-Log 'Push falhou: login, rede ou permissao no repositorio.'
            Write-Host "Dica: confira usuario/token GitHub ou se o repositorio existe." -ForegroundColor DarkYellow
        }
    }
}
catch {
    Write-Host "Erro: $_" -ForegroundColor Red
    Add-Log "Excecao: $_"
}
finally {
    # Sempre: resumo + pergunta fechar + Enter (janela nao some sozinha)
    Write-Host ""
    Write-Host "========== O QUE FOI FEITO (resumo) ==========" -ForegroundColor Green
    foreach ($line in $script:Log) {
        Write-Host " - $line"
    }
    Write-Host " Pasta: $RepoRoot"
    Write-Host " Branch desejada: main"
    $ou = (& git remote get-url origin 2>$null)
    if ($ou) { Write-Host " Origin: $($ou.Trim())" }
    if ($script:CommitMsgUsada) {
        Write-Host " Ultima mensagem de commit usada: $($script:CommitMsgUsada)"
    }
    if ($script:DidCommit) { Write-Host " Commit novo: SIM" } else { Write-Host " Commit novo: NAO" }
    if ($script:PushOk) { Write-Host " Push: OK" } else { Write-Host " Push: nao OK ou nao executado" }
    Write-Host "=============================================" -ForegroundColor Green
    Write-Host ""

    $fechar = Read-Host 'Deseja FECHAR a janela agora? S=sim N=nao'
    if ($fechar -match '^[sSyY]') {
        Write-Host "Ok. Fechando apos Enter..." -ForegroundColor DarkGray
    }
    else {
        Write-Host "Janela permanece; finalize quando quiser." -ForegroundColor DarkGray
    }

    Read-Host "Pressione ENTER para encerrar esta janela"
}
