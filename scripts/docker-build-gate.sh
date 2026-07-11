#!/usr/bin/env bash
# shellcheck shell=bash
#
# docker-build-gate.sh — Wiederkehrendes Docker-Build-Quality-Gate (OPS-2 / DEV-640)
#
# Führt den reproduzierbaren Container-Build des qrm.sg-Produkt-Images aus und
# fungiert als Quality Gate:
#   1. Image bauen (podman bevorzugt, docker als Fallback)
#   2. Bei Build-Fehler  -> strukturierten BUG-Bericht erzeugen (Routinen-Run
#      erzeugt daraus ein BUG-Issue im Tracker)
#   3. Bei Build-Erfolg   -> Smoke-Test (Stack hochfahren, /health prüfen, teardown)
#   4. Ergebnis dokumentieren (JSON + Markdown Report unter BUILD_RESULTS_DIR)
#
# Exit-Codes: 0 = gate grün (build + smoke ok), 1 = build fehlgeschlagen,
#             2 = build ok, smoke fehlgeschlagen, 3 = konfigurations-/laufzeitfehler
#
# Siehe docs/operations/docker-build-gate.md (Runbook & Kadenz).
#
# Co-Authored-By: Paperclip <noreply@paperclip.ing>

set -euo pipefail

# ---------------------------------------------------------------------------
# Konfiguration (ueberschreibbar via Umgebungsvariablen)
# ---------------------------------------------------------------------------
PRODUCT_REPO_URL="${PRODUCT_REPO_URL:-https://github.com/xsPQ/qrm.sg-new.git}"
PRODUCT_REPO_DIR="${PRODUCT_REPO_DIR:-}"          # leer -> Clone nach Temp/Update
DOCKERFILE="${DOCKERFILE:-docker/app/Dockerfile}" # Fallback auf ./Dockerfile
BUILD_TARGET="${BUILD_TARGET:-production}"
IMAGE_NAME="${IMAGE_NAME:-qrm-sg}"
REGISTRY_IMAGE="${REGISTRY_IMAGE:-ghcr.io/xspq/qrm-sg}"
HEALTH_URL="${HEALTH_URL:-http://127.0.0.1:8080/health}"
COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.yml}"
HEALTH_TIMEOUT="${HEALTH_TIMEOUT:-120}"           # Sekunden auf /health
BUILD_RESULTS_DIR="${BUILD_RESULTS_DIR:-build-results}"
PUSH_IMAGE="${PUSH_IMAGE:-0}"                      # 1 -> nach Build pushen (CI/Release)

# ---------------------------------------------------------------------------
# interne Zustaende
# ---------------------------------------------------------------------------
DRY_RUN=0
SIMULATE=""
KEEP_STACK=0
CLONED_REPO=0
REPO_PATH=""
TS="$(date -u +%Y%m%dT%H%M%SZ)"
REPORT_JSON=""
REPORT_MD=""
LOG_FILE=""
RUNTIME=""

usage() {
  cat <<'USAGE'
Usage: docker-build-gate.sh [options] [-- path-to-product-repo]

Options:
  --dry-run              Flow ohne Container-Runtime ausfuehren (Verifikation).
  --simulate <result>    Nur mit --dry-run: 'pass' (default) oder 'failure'
                         simulieren (uebt den BUG-Pfad).
  --repo <path>          Pfad zum ausgecheckten Produkt-Repo (default: Clone).
  --no-clone             Produkt-Repo nicht klonen; --repo muss gesetzt sein.
  --push                 Image nach erfolgreichen Build pushen (REGISTRY_IMAGE).
  --keep-stack           Stack nach Smoke-Test nicht abraeumen (Debug).
  --image <name>         Lokaler Image-Name (default: qrm-sg).
  --health-url <url>     Smoke-Health-URL (default: http://127.0.0.1:8080/health).
  -h, --help             Diese Hilfe.
USAGE
}

log()  { printf '[gate][%s] %s\n' "$(date -u +%H:%M:%SZ)" "$*" >&2; }
die()  { log "ERROR: $*"; exit 3; }

cleanup() {
  local code=$?
  if [[ "${KEEP_STACK}" == "1" ]]; then
    log "--keep-stack gesetzt, belasse Stack/Runtime-Zustand."
  elif [[ "${DRY_RUN}" == "0" && -n "${RUNTIME}" && -n "${REPO_PATH}" ]]; then
    if [[ -f "${REPO_PATH}/${COMPOSE_FILE}" ]]; then
      log "Raeume Stack ab (compose down) ..."
      ( cd "${REPO_PATH}" && compose_cmd down --remove-orphans --volumes -t 15 >/dev/null 2>&1 || true )
    fi
  fi
  if [[ "${CLONED_REPO}" == "1" && -n "${REPO_PATH}" && -d "${REPO_PATH}" ]]; then
    rm -rf "${REPO_PATH}" || true
  fi
  exit "${code}"
}
trap cleanup EXIT

# ---------------------------------------------------------------------------
# Argumente
# ---------------------------------------------------------------------------
NO_CLONE=0
while [[ $# -gt 0 ]]; do
  case "$1" in
    --dry-run)        DRY_RUN=1; shift ;;
    --simulate)       SIMULATE="${2:-}"; shift 2 ;;
    --repo)           PRODUCT_REPO_DIR="${2:-}"; shift 2 ;;
    --no-clone)       NO_CLONE=1; shift ;;
    --push)           PUSH_IMAGE=1; shift ;;
    --keep-stack)     KEEP_STACK=1; shift ;;
    --image)          IMAGE_NAME="${2:-}"; shift 2 ;;
    --health-url)     HEALTH_URL="${2:-}"; shift 2 ;;
    -h|--help)        usage; exit 0 ;;
    --)               shift; [[ $# -gt 0 ]] && PRODUCT_REPO_DIR="$1"; shift $# ;;
    -*)               die "Unbekannte Option: $1" ;;
    *)                PRODUCT_REPO_DIR="$1"; shift ;;
  esac
done

if [[ -n "${SIMULATE}" && "${DRY_RUN}" == "0" ]]; then
  die "--simulate ist nur mit --dry-run erlaubt."
fi
case "${SIMULATE}" in ""|pass|failure) ;; *) die "--simulate muss 'pass' oder 'failure' sein." ;; esac

# ---------------------------------------------------------------------------
# Hilfsfunktionen
# ---------------------------------------------------------------------------
detect_runtime() {
  if command -v podman >/dev/null 2>&1; then
    RUNTIME="podman"
  elif command -v docker >/dev/null 2>&1; then
    RUNTIME="docker"
  else
    RUNTIME=""
  fi
}

compose_cmd() {
  # Bevorzugt docker/podman compose (Plugin), Fallback docker-compose.
  if command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
    docker compose "$@"
  elif command -v podman >/dev/null 2>&1 && podman compose version >/dev/null 2>&1; then
    podman compose "$@"
  elif command -v docker-compose >/dev/null 2>&1; then
    docker-compose "$@"
  else
    die "Kein compose-Backend gefunden (docker compose / podman compose / docker-compose)."
  fi
}

ensure_repo() {
  if [[ -n "${PRODUCT_REPO_DIR}" && -d "${PRODUCT_REPO_DIR}/.git" ]]; then
    REPO_PATH="${PRODUCT_REPO_DIR}"
    log "Verwende lokales Produkt-Repo: ${REPO_PATH}"
    return
  fi
  if [[ "${NO_CLONE}" == "1" ]]; then
    die "--no-clone gesetzt, aber kein gueltiges Repo unter --repo gefunden."
  fi
  REPO_PATH="$(mktemp -d -t qrm-product-XXXXXX)"
  CLONED_REPO=1
  log "Klone Produkt-Repo nach ${REPO_PATH} ..."
  if ! git clone --depth 1 "${PRODUCT_REPO_URL}" "${REPO_PATH}" >/dev/null 2>&1; then
    die "Clone fehlgeschlagen: ${PRODUCT_REPO_URL}"
  fi
}

resolve_dockerfile() {
  # Liefert den relativen Pfad zum Dockerfile im Repo.
  if [[ -f "${REPO_PATH}/${DOCKERFILE}" ]]; then
    printf '%s' "${DOCKERFILE}"
  elif [[ -f "${REPO_PATH}/Dockerfile" ]]; then
    printf 'Dockerfile'
  else
    printf ''
  fi
}

image_ref() {
  # Lokaler Build-Tag, eindeutig pro Lauf.
  printf '%s:%s\n' "${IMAGE_NAME}" "gate-${TS}"
}

now_ms() { date +%s; }

init_reports() {
  mkdir -p "${BUILD_RESULTS_DIR}"
  REPORT_JSON="${BUILD_RESULTS_DIR}/build-${TS}.json"
  REPORT_MD="${BUILD_RESULTS_DIR}/build-${TS}.md"
  LOG_FILE="${BUILD_RESULTS_DIR}/build-${TS}.log"
  : > "${LOG_FILE}"
}

write_report() {
  # $1=status (pass|build_failed|smoke_failed), $2=image, $3=build_ms, $4=smoke_ms, $5=detail
  local status="$1" image="$2" bms="$3" sms="$4" detail="$5"
  local commit=""
  if [[ -d "${REPO_PATH}/.git" ]]; then
    commit="$(git -C "${REPO_PATH}" rev-parse --short HEAD 2>/dev/null || true)"
  fi
  jq -n \
    --arg ts "${TS}" \
    --arg status "${status}" \
    --arg image "${image}" \
    --arg repo "${PRODUCT_REPO_URL}" \
    --arg commit "${commit}" \
    --arg dockerfile "${DOCKERFILE}" \
    --arg runtime "${RUNTIME:-dry-run}" \
    --argjson build_ms "${bms:-0}" \
    --argjson smoke_ms "${sms:-0}" \
    --arg health "${HEALTH_URL}" \
    --arg detail "${detail}" \
    '{timestamp:$ts, status:$status, image:$image, repo:$repo, commit:$commit,
      dockerfile:$dockerfile, runtime:$runtime, build_ms:$build_ms, smoke_ms:$smoke_ms,
      health_url:$health, detail:$detail}' > "${REPORT_JSON}"

  {
    printf '# Docker-Build-Gate Report - %s\n\n' "${TS}"
    printf '| Feld | Wert |\n|---|---|\n'
    printf '| Status | `%s` |\n' "${status}"
    printf '| Image | `%s` |\n' "${image}"
    printf '| Repo | %s |\n' "${PRODUCT_REPO_URL}"
    printf '| Commit | `%s` |\n' "${commit}"
    printf '| Dockerfile | `%s` |\n' "${DOCKERFILE}"
    printf '| Runtime | `%s` |\n' "${RUNTIME:-dry-run}"
    printf '| Build (ms) | %s |\n' "${bms:-0}"
    printf '| Smoke (ms) | %s |\n' "${sms:-0}"
    printf '| Health | %s |\n\n' "${HEALTH_URL}"
    printf '## Detail\n\n```\n%s\n```\n' "${detail}"
    printf '\n## Vollstaendiges Log\n\nSiehe `%s`.\n' "${LOG_FILE}"
  } > "${REPORT_MD}"
}

emit_bug_block() {
  # Strukturierter Block, den der Routinen-Run in ein BUG-Issue ueberfuehrt.
  local image="$1" log="$2"
  cat <<EOF

================ BUG-REPORT (Build-Fehler) ================
title: BUG: Docker-Build-Quality-Gate fehlgeschlagen (${TS})
summary: |
  Der wiederkehrende Docker-Build (${IMAGE_NAME}) ist im Quality-Gate gescheitert.
  Repro: ${RUNTIME:-docker} build -f ${DOCKERFILE} --target ${BUILD_TARGET} -t ${image} .
image: ${image}
repo: ${PRODUCT_REPO_URL}
log: ${log}
report: ${REPORT_JSON}
============================================================
EOF
}

# ---------------------------------------------------------------------------
# Build
# ---------------------------------------------------------------------------
run_build() {
  local ref df start end rc
  ref="$(image_ref)"
  df="$(resolve_dockerfile)"
  if [[ -z "${df}" ]]; then
    write_report "build_failed" "${ref}" 0 0 "Kein Dockerfile gefunden (${DOCKERFILE} oder ./Dockerfile)."
    emit_bug_block "${ref}" "${LOG_FILE}" >&2
    return 1
  fi
  DOCKERFILE="${df}"
  log "Baue Image ${ref} (file=${df}, target=${BUILD_TARGET}, runtime=${RUNTIME}) ..."

  start="$(now_ms)"
  set +e
  if [[ "${BUILD_TARGET}" == "none" ]]; then
    ( cd "${REPO_PATH}" && "${RUNTIME}" build -f "${df}" -t "${ref}" . ) >"${LOG_FILE}" 2>&1
  else
    ( cd "${REPO_PATH}" && "${RUNTIME}" build -f "${df}" --target "${BUILD_TARGET}" -t "${ref}" . ) >"${LOG_FILE}" 2>&1
  fi
  rc=$?
  set -e
  end="$(now_ms)"

  if [[ ${rc} -ne 0 ]]; then
    local tail
    tail="$(tail -n 40 "${LOG_FILE}" 2>/dev/null || true)"
    write_report "build_failed" "${ref}" "$((end-start))" 0 "Build exit ${rc}. Siehe Log."
    printf '%s\n' "${tail}" >&2
    emit_bug_block "${ref}" "${LOG_FILE}" >&2
    return 1
  fi

  if [[ "${PUSH_IMAGE}" == "1" ]]; then
    log "Push ${ref} -> ${REGISTRY_IMAGE}:latest ..."
    if ! ( "${RUNTIME}" tag "${ref}" "${REGISTRY_IMAGE}:latest" && "${RUNTIME}" push "${REGISTRY_IMAGE}:latest" ) >>"${LOG_FILE}" 2>&1; then
      write_report "build_failed" "${ref}" "$((end-start))" 0 "Push fehlgeschlagen."
      return 1
    fi
  fi

  printf '%s' "${ref}"
  return 0
}

# ---------------------------------------------------------------------------
# Smoke-Test
# ---------------------------------------------------------------------------
run_smoke() {
  local ref="$1" start end http_code
  log "Smoke-Test gegen ${HEALTH_URL} (compose: ${COMPOSE_FILE}) ..."

  if [[ ! -f "${REPO_PATH}/${COMPOSE_FILE}" ]]; then
    log "Kein ${COMPOSE_FILE} im Repo - ueberspringe Stack-Smoke."
    write_report "pass" "${ref}" 0 0 "Build gruen; compose (${COMPOSE_FILE}) fehlt, Stack-Smoke uebersprungen."
    return 0
  fi

  start="$(now_ms)"
  ( cd "${REPO_PATH}" && compose_cmd up -d ) >>"${LOG_FILE}" 2>&1 || {
    write_report "smoke_failed" "${ref}" 0 "$(( $(now_ms)-start ))" "compose up fehlgeschlagen."; return 2; }

  log "Warte bis zu ${HEALTH_TIMEOUT}s auf /health ..."
  local elapsed=0
  http_code="000"
  while [[ ${elapsed} -lt ${HEALTH_TIMEOUT} ]]; do
    http_code="$(curl -s -o /dev/null -w '%{http_code}' --max-time 5 "${HEALTH_URL}" 2>/dev/null || true)"
    [[ "${http_code}" == "200" ]] && break
    sleep 3; elapsed=$((elapsed+3))
  done
  end="$(now_ms)"

  if [[ "${http_code}" != "200" ]]; then
    write_report "smoke_failed" "${ref}" 0 "$((end-start))" "/health antwortete HTTP ${http_code}."
    return 2
  fi
  write_report "pass" "${ref}" 0 "$((end-start))" "Build + Smoke gruen (/health 200)."
  return 0
}

# ---------------------------------------------------------------------------
# Dry-Run (Verifikation ohne Container-Runtime)
# ---------------------------------------------------------------------------
run_dry_run() {
  local result="${SIMULATE:-pass}"
  init_reports
  local ref
  ref="$(image_ref)"
  log "DRY-RUN: simuliere build='${result}' image=${ref}"
  printf '[dry-run] would run: <runtime> build -f %s --target %s -t %s .\n' "${DOCKERFILE}" "${BUILD_TARGET}" "${ref}" >>"${LOG_FILE}"
  printf '[dry-run] would run: <runtime> build -f %s --target %s -t %s .\n' "${DOCKERFILE}" "${BUILD_TARGET}" "${ref}" >&2
  if [[ "${result}" == "failure" ]]; then
    printf '[dry-run] simulated build failure\n' >>"${LOG_FILE}"
    write_report "build_failed" "${ref}" 1234 0 "DRY-RUN simulierte Build-Fehler."
    emit_bug_block "${ref}" "${LOG_FILE}" >&2
    log "DRY-RUN beendet (status=build_failed). Reports unter ${BUILD_RESULTS_DIR}/"
    return 1
  fi
  printf '[dry-run] simulated smoke ok (/health 200)\n' >>"${LOG_FILE}"
  write_report "pass" "${ref}" 1234 567 "DRY-RUN: Build + Smoke simuliert gruen."
  log "DRY-RUN beendet (status=pass). Reports unter ${BUILD_RESULTS_DIR}/"
  return 0
}

# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------
main() {
  init_reports
  detect_runtime

  if [[ "${DRY_RUN}" == "1" ]]; then
    run_dry_run
    exit $?
  fi

  [[ -n "${RUNTIME}" ]] || die "Keine Container-Runtime (podman/docker) verfuegbar. Fuer Verifikation --dry-run verwenden."
  command -v curl >/dev/null 2>&1 || die "curl wird fuer den Smoke-Test benoetigt."

  ensure_repo

  local ref build_ok=1 smoke_rc=0
  ref="$(run_build)" || build_ok=0

  if [[ ${build_ok} -eq 0 ]]; then
    log "Build fehlgeschlagen -> Quality Gate rot. Siehe ${REPORT_JSON}"
    exit 1
  fi

  log "Build gruen: ${ref}"
  run_smoke "${ref}" || smoke_rc=$?
  if [[ ${smoke_rc} -ne 0 ]]; then
    log "Smoke fehlgeschlagen -> Quality Gate rot. Siehe ${REPORT_JSON}"
    exit 2
  fi

  log "Quality Gate GRUEN. Report: ${REPORT_JSON}"
  if [[ "${KEEP_STACK}" != "1" ]]; then
    log "(Stack wird beim Beenden abgeraeumt.)"
  fi
  exit 0
}

main "$@"
