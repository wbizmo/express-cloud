# Express Cloud Hardening Phase 0 — Forensic Baseline

Generated: 2026-08-05T05:01:47Z

## Repository identity

- Branch: `main`
- Starting commit: `ab6cadddc703e4900973aead78e02552be838c0c`
- Phase 0 runner: `1.5.0`
- Remote used for publication: `origin`

## Sanitized inventory

| Artifact | Count |
|---|---:|
| Registered routes | 168 |
| Controllers | 67 |
| Models | 59 |
| Services | 53 |
| Actions | 25 |
| Form requests | 38 |
| Middleware | 5 |
| Console commands | 8 |
| Policies | 0 |
| Blade templates | 100 |
| Migrations | 33 |
| Tests | 65 |
| Unit tests | 39 |
| Feature tests | 25 |
| Removed development/backup artifacts | 290 |

## Phase 0 hard gates

- Composer metadata validation passed.
- Every application PHP file passed `php -l` syntax validation.
- Laravel booted and produced the route register.
- Pint normalized inherited formatting debt and the verification pass succeeded.
- PHPUnit/Laravel tests passed.
- Frontend dependencies were installed from `package-lock.json` and the Vite production build passed.
- The staged source tree contains no forbidden backup, patch, shell-runner, release, build, log or local-secret paths.
- The staged source tree contains no high-confidence private-key, GitHub token or AWS access-key signature.
- The commit archive passed the release-path sanitation check before push.

## Static-analysis baseline

PHPStan exit code: `1`

Reported error markers: `148`

PHPStan findings are captured in `docs/hardening/phase-0-phpstan-baseline.txt`. Phase 0 records the inherited debt; later hardening phases are responsible for reducing it to zero actionable errors.

## Generated registers

- `phase-0-code-register.tsv`: source-level controller/model/service/action/request/middleware/command/policy/Blade/migration/test register.
- `phase-0-route-register.json`: Laravel route register.
- `phase-0-removal-register.txt`: paths removed by the sanitation pass.
- `phase-0-source-manifest.sha256`: checksums for every staged source file except the manifest itself.
- `phase-0-phpstan-baseline.txt`: inherited static-analysis findings.
- `phase-0-pint-formatting-register.txt`: PHP files normalized by Pint during Phase 0.

## Important limitation

This phase removes exposed environment and generated release files from the current source snapshot. It cannot rotate credentials already copied elsewhere or erase secrets from historical remote commits. Credential rotation and any deliberate history-rewrite decision remain operational actions outside this script.
