#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
FRONTEND="$ROOT/frontend"

usage() {
    cat <<EOF
Usage: $(basename "$0") [OPTIONS] [-- <extra-args-for-backend>]

Гоняет тесты с покрытием: сначала backend (pest), потом по очереди
каждое frontend-приложение (vitest). Workspace'ы без 'test' script
пропускаются молча.

Options:
  --backend-only      Только backend
  --frontend-only     Только frontend workspaces
  --min N             Минимальный порог backend coverage (% к app/)
  --no-backend-fail   Не падать, если backend coverage упал ниже --min
  -h, --help          Справка

Любые аргументы после '--' пробрасываются в scripts/coverage.sh.

Примеры:
  $(basename "$0")
  $(basename "$0") --min 80
  $(basename "$0") --frontend-only
  $(basename "$0") -- --html
EOF
}

# ── tty colors ──
if [[ -t 1 ]] && command -v tput >/dev/null 2>&1 && [[ "$(tput colors 2>/dev/null || echo 0)" -ge 8 ]]; then
    BOLD=$(tput bold); GREEN=$(tput setaf 2); YELLOW=$(tput setaf 3); RED=$(tput setaf 1); DIM=$(tput dim); RESET=$(tput sgr0)
else
    BOLD=""; GREEN=""; YELLOW=""; RED=""; DIM=""; RESET=""
fi

section() {
    echo
    echo "${BOLD}━━━ $* ━━━${RESET}"
    echo
}

# ── args ──
RUN_BACKEND=1
RUN_FRONTEND=1
NO_BACKEND_FAIL=0
BACKEND_ARGS=()

while [[ $# -gt 0 ]]; do
    case "$1" in
        --backend-only)  RUN_FRONTEND=0; shift ;;
        --frontend-only) RUN_BACKEND=0;  shift ;;
        --no-backend-fail) NO_BACKEND_FAIL=1; shift ;;
        --min)
            BACKEND_ARGS+=(--min "$2"); shift 2 ;;
        --)
            shift; BACKEND_ARGS+=("$@"); break ;;
        -h|--help) usage; exit 0 ;;
        *)
            echo "Неизвестный аргумент: $1" >&2; usage >&2; exit 1 ;;
    esac
done

backend_status=0
declare -A frontend_status=()

# ── backend ──
if [[ "$RUN_BACKEND" == 1 ]]; then
    section "Backend: pest + coverage"
    if "$ROOT/scripts/coverage.sh" "${BACKEND_ARGS[@]}"; then
        echo "${GREEN}✓ backend OK${RESET}"
    else
        backend_status=$?
        echo "${RED}✗ backend coverage failed (exit $backend_status)${RESET}"
        if [[ "$NO_BACKEND_FAIL" != 1 ]]; then
            exit "$backend_status"
        fi
    fi
fi

# ── frontend ──
if [[ "$RUN_FRONTEND" == 1 ]]; then
    if [[ ! -f "$FRONTEND/package.json" ]]; then
        echo "${YELLOW}frontend/package.json не найден, пропускаю${RESET}"
    else
        # список workspaces из root package.json
        mapfile -t WORKSPACES < <(
            node -e "const p=require('$FRONTEND/package.json'); (p.workspaces||[]).forEach(w=>console.log(w))"
        )

        has_coverage_v8=0
        if [[ -d "$FRONTEND/node_modules/@vitest/coverage-v8" ]]; then
            has_coverage_v8=1
        fi

        if [[ "$has_coverage_v8" == 0 ]]; then
            echo "${YELLOW}@vitest/coverage-v8 не установлен. Покрытие фронта пропущено.${RESET}"
            echo "${DIM}  cd frontend && npm install${RESET}"
        fi

        for ws in "${WORKSPACES[@]}"; do
            ws_dir="$FRONTEND/$ws"
            pkg="$ws_dir/package.json"
            [[ -f "$pkg" ]] || continue

            has_test=$(node -e "
                const p=require('$pkg');
                process.stdout.write(p.scripts && p.scripts.test ? '1' : '0');
            ")
            if [[ "$has_test" != "1" ]]; then
                echo "${DIM}— $ws: нет 'test' script, пропускаю${RESET}"
                continue
            fi

            section "Frontend: $ws"

            if [[ "$has_coverage_v8" == 1 ]]; then
                if (cd "$FRONTEND" && npm run --workspace "$ws" test --silent -- --coverage --coverage.reporter=text --coverage.reporter=text-summary); then
                    frontend_status["$ws"]=0
                    echo "${GREEN}✓ $ws OK${RESET}"
                else
                    frontend_status["$ws"]=$?
                    echo "${RED}✗ $ws failed (exit ${frontend_status[$ws]})${RESET}"
                fi
            else
                if (cd "$FRONTEND" && npm run --workspace "$ws" test --silent); then
                    frontend_status["$ws"]=0
                    echo "${GREEN}✓ $ws OK (без coverage)${RESET}"
                else
                    frontend_status["$ws"]=$?
                    echo "${RED}✗ $ws failed (exit ${frontend_status[$ws]})${RESET}"
                fi
            fi
        done
    fi
fi

# ── summary ──
section "Итог"

if [[ "$RUN_BACKEND" == 1 ]]; then
    if [[ "$backend_status" == 0 ]]; then
        echo "${GREEN}backend:${RESET} OK"
    else
        echo "${RED}backend:${RESET} exit $backend_status"
    fi
fi

failed=0
for ws in "${!frontend_status[@]}"; do
    code="${frontend_status[$ws]}"
    if [[ "$code" == 0 ]]; then
        echo "${GREEN}$ws:${RESET} OK"
    else
        echo "${RED}$ws:${RESET} exit $code"
        failed=1
    fi
done

if [[ "$failed" == 1 ]] || [[ "$backend_status" != 0 ]]; then
    exit 1
fi
