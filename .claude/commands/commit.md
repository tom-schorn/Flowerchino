# Commit Workflow

Führe einen vollständigen, sicheren Commit-Prozess durch. Arbeite die sechs Phasen der Reihe nach ab. Stelle bei Unklarheiten interaktive Rückfragen, bevor du weitermachst.

---

## Phase 1: Zustand erfassen

Führe folgende Befehle parallel aus:

```bash
git status --short
git branch --show-current
git diff --cached --name-only
git diff --name-only
gh api user --jq .login 2>/dev/null || git config user.name | sed 's/ /-/g' | tr '[:upper:]' '[:lower:]'
ls .github/workflows/ 2>/dev/null || echo "__no_workflows__"
cat .gitignore 2>/dev/null || echo "__no_gitignore__"
ls -1 2>/dev/null
```

Notiere:
- Aktueller Branch
- GitHub-Nutzername
- Staged & unstaged Änderungen (Dateipfade)
- Vorhandene Workflow-Dateien
- Inhalt der bestehenden `.gitignore`
- Dateien im Projekt-Root (Stack-Erkennung)

---

## Phase 2: Branch-Schutz

**Wenn der aktuelle Branch `main` oder `master` ist:**

1. Zeige eine klare Warnung: Direkte Commits auf `main` sind nicht erlaubt.
2. Frage interaktiv:
   - Welcher Änderungstyp liegt vor? (`feature` / `fix` / `chore` / `docs` / `refactor` / `hotfix`)
   - Ein kurzer, aussagekräftiger Bezeichner für die Änderung (2–4 Wörter, Bindestriche, Kleinbuchstaben, Englisch)
3. Schlage den Branch-Namen vor: `{typ}/{github-nutzername}/{bezeichner}`
   - Beispiel: `feature/tom-schorn/user-authentication`
4. Warte auf Bestätigung oder Korrektur.
5. Führe aus:
   ```bash
   git stash push -m "pre-branch-switch" --include-untracked 2>/dev/null || true
   git checkout -b {branch-name}
   git stash pop 2>/dev/null || true
   ```
6. Bestätige den neuen Branch und fahre mit Phase 3 fort.

**Wenn der aktuelle Branch kein Protected Branch ist:** direkt zu Phase 3.

---

## Phase 3: .gitignore-Hygiene

### Stack-Erkennung

Prüfe anhand vorhandener Dateien und Verzeichnisse, welche Technologien aktiv sind:

| Indikator | Stack |
|---|---|
| `composer.json` | PHP / Composer |
| `vendor/` vorhanden (ungetrackt) | Composer-Abhängigkeiten |
| `package.json` | Node.js / npm |
| `node_modules/` vorhanden (ungetrackt) | npm-Abhängigkeiten |
| `Dockerfile` oder `docker-compose.yml` | Docker |
| `.devcontainer/` | Dev Container |
| `*.env` oder `.env` vorhanden | Umgebungsvariablen |
| `artisan` | Laravel |
| `symfony.lock` | Symfony |
| `*.sql` oder `dumps/` | Datenbankdumps |
| `*.log` oder `logs/` | Logs |

### Pflicht-Patterns nach Stack

**Immer (Basis):**
```
.env
.env.local
.env.*.local
*.log
logs/
*.sql
*.sql.gz
*.dump
.DS_Store
Thumbs.db
desktop.ini
*.swp
*.swo
*.key
*.pem
*.crt
*.p12
*.secret
*credentials*
```

**PHP / Composer:**
```
vendor/
.phpunit.result.cache
.phpunit.cache/
```

**Laravel (zusätzlich zu PHP):**
```
storage/logs/
storage/framework/cache/
storage/framework/sessions/
storage/framework/views/
bootstrap/cache/
public/hot
public/storage
```

**Symfony (zusätzlich zu PHP):**
```
var/
```

**Node.js:**
```
node_modules/
npm-debug.log*
yarn-debug.log*
yarn-error.log*
.pnp.*
.npm
dist/
build/
```

**Docker:**
```
docker-compose.override.yml
.docker/
```

**IDE:**
```
.idea/
*.iml
.vscode/*.log
.history/
```

### Vorgehen

1. Lies die vorhandene `.gitignore` (oder stelle fest, dass sie fehlt).
2. Prüfe, welche Patterns aus der Pflichtliste (je nach erkanntem Stack) noch fehlen.
3. Prüfe zusätzlich: Gibt es im Projekt untracked Dateien oder Verzeichnisse, die offensichtlich nicht ins Repository gehören (z.B. tatsächlich vorhandene `vendor/`, `node_modules/`, `.env`-Dateien, Log-Dateien, Dumps)?
4. **Wenn Lücken gefunden:**
   - Liste die fehlenden Einträge übersichtlich auf, gruppiert nach Kategorie.
   - Frage: „Soll ich diese Einträge zur `.gitignore` hinzufügen?" (Ja / Nein / Auswahl)
   - Bei Ja: Füge sie strukturiert und kommentiert ein.
5. **Wenn alles in Ordnung:** kurze Bestätigung, weiter zu Phase 4.

---

## Phase 4: Workflow-Konsistenz

### Bestehende Workflows prüfen

Für jede Datei in `.github/workflows/`:

1. Lies den Inhalt.
2. Prüfe: Referenziert der Workflow Pfade, Dateien oder Verzeichnisse, die **nicht mehr existieren**?
   - `paths:` Filter auf nicht vorhandene Verzeichnisse
   - `working-directory:` auf nicht existente Pfade
   - `uses:` Actions mit veralteten Versionen (nur flaggen, nicht automatisch ändern)
3. Prüfe: Sind alle `on:` Trigger noch sinnvoll angesichts der aktuellen Projektstruktur?

### Neue Workflows vorschlagen

Prüfe auf Basis der erkannten Stacks, ob sinnvolle Workflows fehlen:

| Erkannter Stack | Fehlender Workflow | Vorschlag |
|---|---|---|
| `composer.json` | Kein PHP-CI-Workflow | PHP Composer Install + Lint/Test |
| `package.json` | Kein Node-CI-Workflow | npm install + build/test |
| `Dockerfile` | Kein Docker-Build-Workflow | Docker build validation |
| Laravel | Kein Laravel-Test-Workflow | php artisan test |
| Symfony | Kein Symfony-Test-Workflow | symfony console tests |

Dependabot: Prüfe, ob `dependabot.yml` alle aktiven Package-Ecosystems abdeckt:
- `composer` falls `composer.json` vorhanden
- `npm` falls `package.json` vorhanden
- `github-actions` falls Workflows vorhanden

### Vorgehen

1. Liste alle gefundenen Probleme oder Vorschläge auf.
2. Frage für jede Änderungskategorie separat: „Soll ich das anpassen / erstellen?"
3. Führe nur bestätigte Änderungen aus.
4. Wenn nichts zu tun: kurze Bestätigung.

---

## Phase 5: GitHub Issues

Prüfe offene Issues, die mit den aktuellen Änderungen in Verbindung stehen könnten:

```bash
gh issue list --state open --limit 50 --json number,title,labels
```

1. Vergleiche die Issue-Titel und Labels mit den geänderten Dateien und dem geplanten Commit-Inhalt.
2. Identifiziere:
   - Issues die durch diesen Commit **geschlossen** werden können (`Closes #X`)
   - Issues die durch diesen Commit **referenziert** werden sollten (`Refs #X`)
3. Zeige die Treffer übersichtlich und frage für jeden:
   - „Schließen (`Closes #X`), referenzieren (`Refs #X`) oder ignorieren?"
4. Die bestätigten Verknüpfungen werden in die Commit-Nachricht (Body) aufgenommen.
5. Wenn keine relevanten Issues gefunden: kurze Bestätigung, weiter zu Phase 6.

---

### Änderungen gruppieren

1. Zeige alle staged und unstaged Änderungen (gruppiert nach Verzeichnis / Thema).
2. Prüfe ob die Änderungen **logisch trennbar** sind – z.B.:
   - Konfigurationsdateien vs. Feature-Code
   - Verschiedene Features in einem Branch
   - Dokumentation vs. Implementierung
3. Wenn trennbare Gruppen erkannt werden: schlage auf, diese in **separate Commits** aufzuteilen.
   - Liste die vorgeschlagenen Gruppen mit je einer Commit-Nachricht
   - Frage: „So aufteilen, anders gruppieren, oder alles in einen Commit?"
4. Bei Aufteilung: jeden Commit der Reihe nach abarbeiten (Stage → Issue-Check → Nachricht → Bestätigung → Commit).
5. Bei einem einzelnen Commit: direkt weiter.

```bash
git add {dateien der aktuellen gruppe}
```

### Commit-Nachricht

Verfasse einen Vorschlag nach folgenden Regeln:

- **Sprache:** Englisch
- **Format:** Conventional Commits – Subjektzeile (`feat:`, `fix:`, `chore:`, `docs:`, `refactor:`, `style:`, `test:`), max. 72 Zeichen
- **Body:** 2–4 Bullet Points (`-`), jeder knapp und präzise, was konkret geändert wurde
- **Stil:** Imperativ (`Add`, `Fix`, `Update`, `Remove`, `Refactor`) – sachlich, kein Marketing-Ton
- **Verboten:** Keinerlei Hinweise auf KI, automatische Generierung, Tools oder Assistenten

Beispiel:
```
feat: add user login flow

- Add login form with email/password fields
- Implement session handling on successful auth
- Redirect to dashboard after login
```

**Zeige den Vorschlag und warte auf ausdrückliche Bestätigung oder Korrektur. Erst nach Freigabe wird committed.**

### Commit ausführen

```bash
git commit -m "$(cat <<'EOF'
{bestätigte commit-nachricht}
EOF
)"
```

Bestätige den Commit mit Hash und Branch.

---

## Phase 7: Push & Pull Request

1. Frage: „Soll ich den Branch pushen und einen Pull Request erstellen?"

> Wenn Issues mit `Closes #X` verknüpft wurden: Diese werden automatisch geschlossen sobald der PR in `main` gemergt wird – das ist das erwartete Verhalten.

**Bei Ja – Push:**
```bash
git push -u origin {branch}
```

**Pull Request erstellen:**

Verfasse einen Vorschlag für Titel und Body nach folgenden Regeln:

- **Sprache:** Englisch
- **Titel:** Kurz, präzise, max. 70 Zeichen – kein KI-Sprachduktus
- **Body:** 2–4 Bullet Points, was geändert wurde und warum – knapp und sachlich
- **Kein Footer** mit Tool-Hinweisen, KI-Attributionen oder automatisch generierten Texten

Beispiel:
```
Title: Add user authentication flow

- Implement login form with session handling
- Add redirect logic post-authentication
- Protect dashboard route for unauthenticated users
```

**Zeige Titel und Body als Vorschlag. Warte auf ausdrückliche Bestätigung oder Korrektur. Erst nach Freigabe wird der PR erstellt.**

```bash
gh pr create --title "{bestätigter titel}" --body "$(cat <<'EOF'
{bestätigter body}
EOF
)" --base main
```

2. Gib die PR-URL aus.

---

## Abschluss

Fasse am Ende kurz zusammen:
- Welcher Branch wurde verwendet / erstellt
- Was wurde committed (Dateien + Nachricht)
- Ob .gitignore oder Workflows angepasst wurden
- Ob ein PR erstellt wurde
