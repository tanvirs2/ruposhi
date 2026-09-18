#!/usr/bin/env bash
#
# ডিপ্লয় স্ক্রিপ্ট — সার্ভারে এই একটাই কমান্ড চালানো হয়:
#     ssh root@168.144.90.82 'cd /var/www/ruposhi_pos && bash deploy.sh'
#
# কেন স্ক্রিপ্ট, লম্বা `&&` চেইন নয়:
#   1. প্রতিটা ধাপের আউটপুট ধরে রেখে একটা ডিপ্লয় লগ লেখা যায় — কোন কমিট
#      থেকে কোন কমিটে গেল, কোন ফাইল বদলাল, কোন মাইগ্রেশন চলল।
#   2. কোথায় ব্যর্থ হলো সেটা লগে লেখা যায় (চেইনে শুধু থেমে যেত)।
#   3. পুরনো লগ ঘুরিয়ে মুছে ফেলা যায়, তাই সার্ভার ভরে যায় না।
#
# ⚠️ স্ক্রিপ্টটা নিজেই রিপো থেকে আসে, তাই `git pull` এই ফাইলের যে সংস্করণ
#    এখন চলছে সেটাকে বদলায় না — স্ক্রিপ্টের নতুন পরিবর্তন কাজে লাগে পরের
#    ডিপ্লয় থেকে। (চলতি শেল ফাইলটা আগেই পড়ে ফেলেছে।)
set -uo pipefail

BRANCH="${DEPLOY_BRANCH:-main}"
KEEP_BACKUPS=30          # predeploy ডাম্প কতগুলো রাখা হবে
KEEP_LOGS=30             # ডিপ্লয় লগ কতগুলো রাখা হবে

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_DIR"

TS="$(date +%Y-%m-%d_%H%M%S)"
LOG_DIR="storage/app/deploy-logs"
LOG="$LOG_DIR/deploy_${TS}.md"
mkdir -p "$LOG_DIR"

STEP="শুরু"
STATUS="চলছে"

log()  { printf '%s\n' "$*" >> "$LOG"; }
say()  { printf '\n\033[1m▶ %s\033[0m\n' "$*"; }

# ধাপগুলো একে একে; যেটা ব্যর্থ হয় সেখানেই থেমে যায় এবং লগে কারণ লেখা হয়
run() {
    STEP="$1"; shift
    say "$STEP"
    local out
    if ! out="$("$@" 2>&1)"; then
        printf '%s\n' "$out"
        log ""
        log "### ❌ ব্যর্থ ধাপ: ${STEP}"
        log '```'
        log "$out"
        log '```'
        STATUS="ব্যর্থ"
        finish
        exit 1
    fi
    printf '%s\n' "$out"
    LAST_OUT="$out"
}

finish() {
    # ব্যর্থ হলেও মালিকানা ঠিক করে যাওয়া হয় — যদি ক্যাশের ধাপে ব্যর্থ হয়,
    # তখন root-owned ক্যাশ ফাইল পড়ে থাকলে www-data লিখতে না পেরে ৫০০ দিত।
    if [ "$STATUS" = "ব্যর্থ" ]; then
        chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
    fi

    log ""
    log "**ফলাফল:** ${STATUS}"
    log ""
    log "_শেষ: $(date '+%Y-%m-%d %H:%M:%S %Z')_"

    # পুরনো লগ ঘুরিয়ে মুছে ফেলা — নইলে বছর ঘুরতে ডিস্ক ভরে যেত
    # shellcheck disable=SC2012
    ls -1t "$LOG_DIR"/deploy_*.md 2>/dev/null | tail -n +$((KEEP_LOGS + 1)) | while read -r old; do
        rm -f "$old"
    done

    printf '\n\033[1mডিপ্লয় লগ:\033[0m %s/%s\n' "$APP_DIR" "$LOG"
}

BEFORE="$(git rev-parse HEAD)"
BEFORE_SHORT="$(git rev-parse --short HEAD)"

log "# ডিপ্লয় — $(date '+%Y-%m-%d %H:%M:%S %Z')"
log ""
log "- **ব্রাঞ্চ:** \`$BRANCH\`"
log "- **আগের কমিট:** \`$BEFORE_SHORT\` — $(git log -1 --pretty=%s)"
log "- **চালিয়েছেন:** $(whoami)@$(hostname)"

# ── ১. ব্যাকআপ (মাইগ্রেশন থাকুক বা না থাকুক) ────────────────────────
# সবার আগে, আর pull-এর আগে: ডাম্প নেয় ইতিমধ্যে চলতে থাকা পরীক্ষিত কোড।
run "ডাটাবেস ব্যাকআপ" php artisan app:backup-db --tag=predeploy --keep="$KEEP_BACKUPS"
BACKUP_FILE="$(ls -1t storage/app/backups/backup_predeploy_*.sql.gz 2>/dev/null | head -1)"
log "- **ব্যাকআপ:** \`$(basename "${BACKUP_FILE:-—}")\`"

# ── ২. কোড টানা ─────────────────────────────────────────────────────
run "কোড টানা (git pull)" git pull origin "$BRANCH"
AFTER="$(git rev-parse HEAD)"
AFTER_SHORT="$(git rev-parse --short HEAD)"
log "- **নতুন কমিট:** \`$AFTER_SHORT\` — $(git log -1 --pretty=%s)"
log ""

if [ "$BEFORE" = "$AFTER" ]; then
    log "## কোড পরিবর্তন"
    log ""
    log "_কোনো নতুন কমিট নেই — কোড অপরিবর্তিত।_"
else
    log "## কমিট (${BEFORE_SHORT} → ${AFTER_SHORT})"
    log ""
    log '```'
    git log --no-merges --pretty='%h %ad %an — %s' --date=short "$BEFORE..$AFTER" >> "$LOG"
    log '```'
    log ""
    log "## বদলানো ফাইল"
    log ""
    log '```'
    git diff --stat "$BEFORE" "$AFTER" >> "$LOG"
    log '```'

    # ডাটাবেস পরিবর্তন আলাদা করে — এটাই সবচেয়ে ঝুঁকির জায়গা, তাই
    # মাইগ্রেশন ফাইল ও তার ভেতরের স্কিমা লাইনগুলো আলাদা সেকশনে।
    NEW_MIGRATIONS="$(git diff --name-only --diff-filter=A "$BEFORE" "$AFTER" -- database/migrations || true)"
    log ""
    log "## ডাটাবেস পরিবর্তন"
    log ""
    if [ -z "$NEW_MIGRATIONS" ]; then
        log "_নতুন কোনো মাইগ্রেশন ফাইল আসেনি._"
    else
        log "নতুন মাইগ্রেশন ফাইল:"
        log ""
        printf '%s\n' "$NEW_MIGRATIONS" | sed 's|^database/migrations/|- `|; s|$|`|' >> "$LOG"
        log ""
        log "স্কিমা লাইন (Schema::/table->):"
        log '```'
        printf '%s\n' "$NEW_MIGRATIONS" | while read -r m; do
            [ -n "$m" ] || continue
            printf '%s:\n' "$(basename "$m")" >> "$LOG"
            grep -E 'Schema::|\$table->' "$m" | sed 's/^\s*/  /' >> "$LOG" || true
        done
        log '```'
    fi
fi

# ── ৩. মাইগ্রেশন ────────────────────────────────────────────────────
# চালানোর আগে কী কী বাকি আছে তা লগে তুলে রাখা হয় — পরে "কী চলেছিল"
# খুঁজতে migrate-এর আউটপুটই যথেষ্ট, কিন্তু আগের অবস্থাটাও কাজে দেয়।
log ""
log "## মাইগ্রেশন"
log ""
log '```'
php artisan migrate:status 2>&1 | grep -i pending >> "$LOG" || printf 'কোনো Pending মাইগ্রেশন নেই\n' >> "$LOG"
log '```'

run "মাইগ্রেশন" php artisan migrate --force
log '```'
log "$LAST_OUT"
log '```'

# ── ৪. ক্যাশ ও পারমিশন ──────────────────────────────────────────────
run "config ক্যাশ"  php artisan config:cache
run "route ক্যাশ"   php artisan route:cache
run "view ক্যাশ"    php artisan view:cache
# ⚠️ কমান্ডগুলো root হিসেবে চলে, তাই ক্যাশ ফাইল root-owned হয়ে যায় আর
# www-data লিখতে না পেরে ৫০০ দেয় — এই chown কখনো বাদ দেওয়া যাবে না।
run "মালিকানা ঠিক করা" chown -R www-data:www-data storage bootstrap/cache

STATUS="✅ সফল"
finish
